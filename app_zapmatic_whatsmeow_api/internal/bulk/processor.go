package bulk

import (
	"context"
	"fmt"
	"math/rand"
	"strings"
	"sync"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/sender"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/session"
	"go.mau.fi/whatsmeow"
)

type Processor struct {
	mu        sync.Mutex
	sm        *session.Manager
	snd       *sender.Sender
	webhook   *WebhookClient
	template  *TemplateLoader
	stats     *StatsManager
	rotators  map[int]*AccountRotator
	running   bool
	stopCh    chan struct{}
}

func NewProcessor(sm *session.Manager, snd *sender.Sender, webhookURL string) *Processor {
	return &Processor{
		sm: sm, snd: snd,
		webhook:   NewWebhookClient(webhookURL),
		template:  &TemplateLoader{},
		stats:     &StatsManager{},
		rotators:  make(map[int]*AccountRotator),
		// selectors removed - persistent offset
		stopCh:    make(chan struct{}),
	}
}

func (p *Processor) Start() {
	p.mu.Lock()
	if p.running { p.mu.Unlock(); return }
	p.running = true
	p.mu.Unlock()

	go func() {
		ticker := time.NewTicker(2 * time.Second)
		defer ticker.Stop()
		logging.Log.Info().Msg("Bulk processor started (ticker every 2s)")
		for {
			select {
			case <-ticker.C:
				p.processDue()
			case <-p.stopCh:
				return
			}
		}
	}()

	go func() {
		ticker := time.NewTicker(30 * time.Second)
		defer ticker.Stop()
		for {
			select {
			case <-ticker.C:
				p.validatePhones()
			case <-p.stopCh:
				return
			}
		}
	}()
}

func (p *Processor) Stop() {
	p.mu.Lock()
	defer p.mu.Unlock()
	if p.running { close(p.stopCh); p.running = false }
}

func (p *Processor) ValidateNow() { p.validatePhones() }

func (p *Processor) processDue() {
	if mysqlDB == nil { return }
	campaigns, err := ListDueCampaigns(5)
	if err != nil { logging.Log.Error().Err(err).Msg("ListDueCampaigns failed"); return }
	for _, c := range campaigns {
		if c.Status == StatusCompleted { continue }
		p.processCampaign(c)
	}
}

// extractPhoneDigits returns only digits from a string (JID or phone).
func extractPhoneDigits(s string) string {
	idx := strings.Index(s, ":")
	if idx > 0 { s = s[:idx] }
	at := strings.Index(s, "@")
	if at > 0 { s = s[:at] }
	var digits strings.Builder
	for _, r := range s {
		if r >= '0' && r <= '9' {
			digits.WriteRune(r)
		}
	}
	return digits.String()
}

func (p *Processor) processCampaign(c *Campaign) {
	if err := LockCampaign(c.ID); err != nil { return }

	sched := NewScheduler(c.Timezone)
	if !sched.IsWithinWindow(c) && len(c.ScheduleTime) > 0 {
		updateCampaignField(c.ID, "time_post", fmt.Sprintf("%d", sched.findNextSlot(c, time.Now().Unix())))
		UnlockCampaign(c.ID); return
	}
	if c.SkipHolidays && sched.IsHoliday(c.TeamID) {
		updateCampaignField(c.ID, "time_post", fmt.Sprintf("%d", time.Now().Unix()+86400))
		UnlockCampaign(c.ID); return
	}

	canSend, _, _ := p.stats.CheckLimit(c.TeamID)
	if !canSend { SetCampaignStatus(c.ID, StatusPaused); UnlockCampaign(c.ID); return }

	// Get contact using persistent offset (sent+failed)
	phone, err := GetNextPhone(c)
	if err != nil { UnlockCampaign(c.ID); return }
	if phone == nil {
		// Check if we actually had phones to begin with
		count, countErr := GetContactPhonesCount(c.ContactID)
		if countErr == nil && count > 0 && c.Sent > 0 {
			// All contacts processed
			logging.Log.Info().Int("campaign", c.ID).Int("sent", c.Sent).Msg("Campaign completed")
			SetCampaignCompleted(c.ID); UnlockCampaign(c.ID)
			p.cleanupCampaign(c.ID); return
		}
		logging.Log.Warn().Int("campaign", c.ID).Int("contact_id", c.ContactID).Int("phone_count", count).Int("processed", c.Sent+c.Failed).Msg("No more phones available")
		SetCampaignCompleted(c.ID); UnlockCampaign(c.ID); return
	}

	phoneNumber, _ := phone["phone"].(string)
	phoneID, _ := phone["id"].(int)
	params, _ := phone["params"].(map[string]string)
	isValidRaw := phone["is_valid"]

	// Resolve instance
	instanceID := p.resolveBestInstance(c)
	if instanceID == "" {
		updateCampaignField(c.ID, "time_post", fmt.Sprintf("%d", time.Now().Unix()+30))
		UnlockCampaign(c.ID); return
	}

	// Normalize phones
	normalizer := &PhoneNormalizer{}
	normalizedTarget := normalizer.NormalizePhone(phoneNumber)
	targetDigits := extractPhoneDigits(normalizedTarget)

	// Get instance phone digits
	inst := p.sm.GetInstance(instanceID)
	instDigits := ""
	if inst != nil && inst.JID != "" {
		instDigits = extractPhoneDigits(inst.JID)
	}

	// CRÍTICO: não enviar para o mesmo número da instância (auto-envio não funciona)
	// CRÍTICO: não enviar para o mesmo número da instância (auto-envio não funciona)
	if targetDigits != "" && instDigits != "" && targetDigits == instDigits {
		logging.Log.Warn().Int("campaign", c.ID).Str("from", instanceID).
			Str("to", phoneNumber).Str("instance_phone", instDigits).
			Msg("Skipping self-contact (same number as instance)")
		// Avança: incrementa sent para pular este contato
		nextTime := time.Now().Unix() + int64(CalculateDelay(c.MinDelay, c.MaxDelay))
		_, _ = mysqlDB.Exec(
			"UPDATE sp_whatsapp_schedules SET sent=sent+1, time_post=?, next_account=?, run=0 WHERE id=?",
			nextTime, c.NextAccount+1, c.ID,
		)
		p.cleanupCampaign(c.ID)
		return
	}

	// Validate phone
	client := p.getClientForInstance(instanceID)
	validator := &PhoneValidator{}
	if client != nil && !IsValidPhone(isValidRaw) && !ShouldSkipPhone(isValidRaw) {
		go UpdatePhoneValidity(phoneID, int(Checking))
		if !validator.CheckPhone(client, phoneNumber) {
			UpdatePhoneValidity(phoneID, int(Invalid))
			recordFailure(c, phoneID, phoneNumber, "invalid on WA", 0)
			UnlockCampaign(c.ID); return
		}
		UpdatePhoneValidity(phoneID, int(Valid))
	}

	// Send
	pushName := p.getPushName(instanceID)
	chatID := EnsureJID(normalizedTarget)

	logging.Log.Info().Int("campaign", c.ID).Str("from", instanceID).Str("to", chatID).Msg("Sending")

	var msgResult sender.SendResponse
	switch c.Type {
	case CampaignText:
		msgResult = p.sendText(c, instanceID, chatID, params, pushName)
	case CampaignButton, CampaignCarousel:
		msgResult = p.sendButton(c, instanceID, chatID, params, pushName)
	case CampaignList:
		msgResult = p.sendList(c, instanceID, chatID, params, pushName)
	case CampaignPoll:
		msgResult = p.sendPoll(c, instanceID, chatID, params, pushName)
	default:
		msgResult = sender.SendResponse{Status: "error", Error: "unsupported type"}
	}

	if msgResult.Status == "success" {
		p.stats.IncrementSent(c.TeamID)
		recordSuccess(c, phoneID, phoneNumber, msgResult.MessageID, 0)
	} else {
		p.stats.IncrementFailed(c.TeamID)
		recordFailure(c, phoneID, phoneNumber, msgResult.Error, 0)
	}
	UnlockCampaign(c.ID)
}

// resolveBestInstance returns a connected instance registered in MySQL.
func (p *Processor) resolveBestInstance(c *Campaign) string {
	// Collect MySQL registered instance tokens
	dbTokens := make(map[string]bool)
	if mysqlDB != nil {
		rows, err := mysqlDB.Query(
			`SELECT token FROM sp_accounts
			 WHERE social_network='whatsapp' AND status=1 AND login_type=3`,
		)
		if err == nil {
			defer rows.Close()
			for rows.Next() {
				var token string
				if rows.Scan(&token) == nil && token != "" {
					dbTokens[token] = true
				}
			}
		}
	}

	// Use campaign-specific accounts
	if len(c.Accounts) > 0 && c.Accounts[0] > 0 {
		rot := p.getOrCreateRotator(c)
		for i := 0; i < len(c.Accounts)*2; i++ {
			accID := rot.Next()
			var token string
			if mysqlDB != nil {
				mysqlDB.QueryRow(
					"SELECT token FROM sp_accounts WHERE id=? AND status=1 AND social_network='whatsapp'", accID,
				).Scan(&token)
			}
			if token != "" {
				for _, s := range p.sm.ListInstances() {
					if s.ID == token && s.State == "connected" { return s.ID }
				}
			}
		}
	}

	// Fallback: any DB-registered instance that is connected
	for id := range dbTokens {
		for _, s := range p.sm.ListInstances() {
			if s.ID == id && s.State == "connected" {
				return id
			}
		}
	}

	return ""
}

func (p *Processor) sendText(c *Campaign, instanceID, chatID string, params map[string]string, pushName string) sender.SendResponse {
	msg := BuildMessage(c.Caption, params, pushName, instanceID, pushName, phoneFromJID(chatID))
	if c.Media != "" {
		mediaURL := BuildMessage(c.Media, params, pushName, instanceID, pushName, "")
		u := sender.SendRequest{InstanceID: instanceID, ChatID: chatID, Type: "image"}
		u.Payload.URL = mediaURL
		u.Payload.Caption = msg
		return p.snd.SendMedia(context.Background(), u)
	}
	u := sender.SendRequest{InstanceID: instanceID, ChatID: chatID, Type: "text"}
	u.Payload.Text = msg
	return p.snd.SendText(context.Background(), u)
}

func (p *Processor) sendButton(c *Campaign, instanceID, chatID string, params map[string]string, pushName string) sender.SendResponse {
	tpl, err := p.template.LoadTemplate(c.Template)
	if err != nil { return sender.SendResponse{Status: "error", Error: err.Error()} }
	p.template.ApplySpintax(tpl, params, pushName, instanceID, pushName, phoneFromJID(chatID))
	return p.snd.SendButtons(context.Background(), p.template.ToButtonsRequest(tpl, instanceID, chatID))
}

func (p *Processor) sendList(c *Campaign, instanceID, chatID string, params map[string]string, pushName string) sender.SendResponse {
	tpl, err := p.template.LoadTemplate(c.Template)
	if err != nil { return sender.SendResponse{Status: "error", Error: err.Error()} }
	p.template.ApplySpintax(tpl, params, pushName, instanceID, pushName, phoneFromJID(chatID))
	return p.snd.SendList(context.Background(), p.template.ToListRequest(tpl, instanceID, chatID, "Ver opções"))
}

func (p *Processor) sendPoll(c *Campaign, instanceID, chatID string, params map[string]string, pushName string) sender.SendResponse {
	tpl, err := p.template.LoadTemplate(c.Template)
	if err != nil { return sender.SendResponse{Status: "error", Error: err.Error()} }
	p.template.ApplySpintax(tpl, params, pushName, instanceID, pushName, phoneFromJID(chatID))
	return p.snd.SendPoll(context.Background(), p.template.ToPollRequest(tpl, instanceID, chatID))
}

func (p *Processor) getOrCreateRotator(c *Campaign) *AccountRotator {
	p.mu.Lock()
	defer p.mu.Unlock()
	if rot, ok := p.rotators[c.ID]; ok { rot.SetIndex(c.NextAccount); return rot }
	rot := NewAccountRotatorWithIndex(c.Accounts, c.NextAccount)
	p.rotators[c.ID] = rot
	return rot
}
func (p *Processor) getClientForInstance(instanceID string) *whatsmeow.Client {
	if instanceID == "" { return nil }
	inst := p.sm.GetInstance(instanceID)
	if inst == nil { return nil }
	return inst.Client()
}

func (p *Processor) getPushName(instanceID string) string {
	inst := p.sm.GetInstance(instanceID)
	if inst == nil { return "" }
	return inst.DisplayName()
}

func (p *Processor) validatePhones() {
	ValidatePendingNumbers(func() *whatsmeow.Client {
		for _, s := range p.sm.ListInstances() {
			if s.State == "connected" {
				inst := p.sm.GetInstance(s.ID)
				if inst != nil { return inst.Client() }
			}
		}
		return nil
	}, 5)
}

func (p *Processor) cleanupCampaign(cid int) {
	p.mu.Lock()
	delete(p.rotators, cid)
	p.mu.Unlock()
}

func recordSuccess(c *Campaign, phoneID int, phone, msgID string, accID int) {
	UpdateCampaignResult(c.ID, true, time.Now().Unix()+int64(CalculateDelay(c.MinDelay, c.MaxDelay)), c.NextAccount+1)
}

func recordFailure(c *Campaign, phoneID int, phone, errMsg string, accID int) {
	UpdateCampaignResult(c.ID, false, time.Now().Unix()+int64(rand.Intn(10)+5), c.NextAccount+1)
}

func phoneFromJID(jid string) string {
	for i := 0; i < len(jid); i++ {
		if jid[i] == '@' { return jid[:i] }
	}
	return jid
}
