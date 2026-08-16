package bulk

import (
	"bytes"
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"math/rand"
	"net/http"
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

	// Campanha de grupo: envia dentro de grupos (@g.us), não para números.
	if c.IsGroupCampaign() {
		p.processGroupCampaign(c)
		return
	}

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

	// Resolve instance with provider detection
	resolved := p.resolveBestInstance(c)
	if resolved == nil {
		updateCampaignField(c.ID, "time_post", fmt.Sprintf("%d", time.Now().Unix()+30))
		UnlockCampaign(c.ID); return
	}

	// Skip campaigns that don't use whatsmeow — let Node.js/PHP handle them
	if resolved.Provider != "whatsmeow" {
		UnlockCampaign(c.ID); return
	}

	instanceID := resolved.InstanceID

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
			nextTime, sql.NullInt64{Int64: c.NextAccount.Int64 + 1, Valid: true}, c.ID,
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

	// Route by provider
	if resolved.Provider == "baileys" {
		msgResult = p.sendViaBaileysHTTP(c, resolved, chatID, params, pushName)
	} else if resolved.Provider == "cloud_api" {
		msgResult = p.sendViaCloudAPIHTTP(c, resolved, chatID, params, pushName)
	} else {
		// whatsmeow (default, current behavior)
		switch c.Type {
		case CampaignText:
			msgResult = p.sendText(c, instanceID, chatID, params, pushName)
		case CampaignButton:
			msgResult = p.sendButton(c, instanceID, chatID, params, pushName)
		case CampaignCarousel:
			msgResult = p.sendCarousel(c, instanceID, chatID, params, pushName)
		case CampaignList:
			msgResult = p.sendList(c, instanceID, chatID, params, pushName)
		case CampaignPoll:
			msgResult = p.sendPoll(c, instanceID, chatID, params, pushName)
		default:
			msgResult = sender.SendResponse{Status: "error", Error: "unsupported type"}
		}
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

// processGroupCampaign processa uma campanha cujo destino são grupos. Usa o
// mesmo offset persistente (sent+failed) e o mesmo CalculateDelay, mas envia a
// mensagem dentro do grupo (@g.us).
func (p *Processor) processGroupCampaign(c *Campaign) {
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

	groups, err := ListScheduleGroups(c.ID)
	if err != nil { UnlockCampaign(c.ID); return }

	offset := c.Sent + c.Failed
	group := NextGroupByOffset(groups, offset)
	if group == nil {
		count := len(groups)
		if count > 0 && c.Sent > 0 {
			logging.Log.Info().Int("campaign", c.ID).Int("sent", c.Sent).Msg("Group campaign completed")
		} else {
			logging.Log.Warn().Int("campaign", c.ID).Int("group_count", count).Int("processed", offset).Msg("No more groups available")
		}
		SetCampaignCompleted(c.ID); UnlockCampaign(c.ID)
		p.cleanupCampaign(c.ID); return
	}

	resolved := p.resolveGroupInstance(group.AccountID)
	if resolved == nil {
		updateCampaignField(c.ID, "time_post", fmt.Sprintf("%d", time.Now().Unix()+30))
		UnlockCampaign(c.ID); return
	}
	if resolved.Provider != "whatsmeow" {
		// Primeiro ciclo: envio em grupo só via Go/Whatsmeow.
		UnlockCampaign(c.ID); return
	}

	instanceID := resolved.InstanceID
	chatID := ResolveGroupChat(group.GroupJID)
	pushName := p.getPushName(instanceID)
	msg := BuildMessage(c.Caption, nil, pushName, instanceID, pushName, phoneFromJID(chatID))

	var msgResult sender.SendResponse
	if c.Media != "" {
		mediaURL := BuildMessage(c.Media, nil, pushName, instanceID, pushName, "")
		u := sender.SendRequest{InstanceID: instanceID, ChatID: chatID, Type: "image"}
		u.Payload.URL = mediaURL
		u.Payload.Caption = msg
		msgResult = p.snd.SendMedia(context.Background(), u)
	} else {
		u := sender.SendRequest{InstanceID: instanceID, ChatID: chatID, Type: "text"}
		u.Payload.Text = msg
		msgResult = p.snd.SendText(context.Background(), u)
	}

	logging.Log.Info().Int("campaign", c.ID).Str("from", instanceID).Str("to", chatID).Msg("Sending to group")

	if msgResult.Status == "success" {
		p.stats.IncrementSent(c.TeamID)
		UpdateCampaignResult(c.ID, true, time.Now().Unix()+int64(CalculateDelay(c.MinDelay, c.MaxDelay)), sql.NullInt64{Int64: c.NextAccount.Int64 + 1, Valid: true})
	} else {
		p.stats.IncrementFailed(c.TeamID)
		UpdateCampaignResult(c.ID, false, time.Now().Unix()+int64(rand.Intn(10)+5), sql.NullInt64{Int64: c.NextAccount.Int64 + 1, Valid: true})
	}
	UnlockCampaign(c.ID)
}

// resolveGroupInstance resolves the specific account that owns a group
// (account_id), instead of the campaign round-robin. Each group must be sent
// by the profile that is actually a member of it.
func (p *Processor) resolveGroupInstance(accountID string) *ResolvedInstance {
	accountID = GroupSenderAccount(accountID)
	if accountID == "" {
		return nil
	}

	var token string
	var loginType int
	if mysqlDB != nil {
		mysqlDB.QueryRow(
			"SELECT token, COALESCE(login_type, 2) FROM sp_accounts WHERE ids=? AND status=1 AND social_network='whatsapp'", accountID,
		).Scan(&token, &loginType)
	}
	if token == "" {
		return nil
	}

	provider := "whatsmeow"
	switch loginType {
	case 1:
		provider = "cloud_api"
	case 2:
		provider = "baileys"
	case 3:
		provider = "whatsmeow"
	}

	if provider == "whatsmeow" {
		for _, s := range p.sm.ListInstances() {
			if s.ID == token && s.State == "connected" {
				return &ResolvedInstance{InstanceID: s.ID, Provider: provider, AccountID: 0, Token: token}
			}
		}
		return nil
	}

	return &ResolvedInstance{InstanceID: token, Provider: provider, AccountID: 0, Token: token}
}

// ResolvedInstance holds the result of resolving which instance/gateway to use.
type ResolvedInstance struct {
	InstanceID string
	Provider   string // "whatsmeow", "baileys", "cloud_api"
	AccountID  int
	Token      string
}

// resolveBestInstance returns a connected instance with its provider.
func (p *Processor) resolveBestInstance(c *Campaign) *ResolvedInstance {
	if len(c.Accounts) == 0 || c.Accounts[0] <= 0 {
		return nil
	}

	rot := p.getOrCreateRotator(c)
	for i := 0; i < len(c.Accounts)*2; i++ {
		accID := rot.Next()
		var token string
		var loginType int
		if mysqlDB != nil {
			mysqlDB.QueryRow(
				"SELECT token, COALESCE(login_type, 2) FROM sp_accounts WHERE id=? AND status=1 AND social_network='whatsapp'", accID,
			).Scan(&token, &loginType)
		}
		if token == "" {
			continue
		}

		// Determine provider based on gateway_overrides per-account or login_type
		provider := "whatsmeow"
		// Check per-account override first
		if c.GatewayOverrides != nil {
			if override, ok := c.GatewayOverrides[token]; ok && override != "" {
				provider = override
			} else if override, ok := c.GatewayOverrides[fmt.Sprintf("%d", accID)]; ok && override != "" {
				provider = override
			}
		}
		// Fallback to gateway_mode or login_type
		if provider == "whatsmeow" && (c.GatewayMode != "" && c.GatewayMode != "auto") {
			provider = c.GatewayMode
		} else if provider == "whatsmeow" {
			switch loginType {
			case 1:
				provider = "cloud_api"
			case 2:
				provider = "baileys"
			case 3:
				provider = "whatsmeow"
			}
		}

		// For whatsmeow, verify session is connected
		if provider == "whatsmeow" {
			for _, s := range p.sm.ListInstances() {
				if s.ID == token && s.State == "connected" {
					return &ResolvedInstance{InstanceID: s.ID, Provider: provider, AccountID: accID, Token: token}
				}
			}
			// Not connected, try next account
			continue
		}

		// For baileys/cloud_api, we don't need a whatsmeow session
		// The token is the instance_id for the PHP side
		return &ResolvedInstance{InstanceID: token, Provider: provider, AccountID: accID, Token: token}
	}

	return nil
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

func (p *Processor) sendCarousel(c *Campaign, instanceID, chatID string, params map[string]string, pushName string) sender.SendResponse {
	tpl, err := p.template.LoadTemplate(c.Template)
	if err != nil { return sender.SendResponse{Status: "error", Error: err.Error()} }
	p.template.ApplySpintax(tpl, params, pushName, instanceID, pushName, phoneFromJID(chatID))
	return p.snd.SendCarousel(context.Background(), p.template.ToCarouselRequest(tpl, instanceID, chatID))
}

func (p *Processor) getOrCreateRotator(c *Campaign) *AccountRotator {
	p.mu.Lock()
	defer p.mu.Unlock()
	if rot, ok := p.rotators[c.ID]; ok { rot.SetIndex(int(c.NextAccount.Int64)); return rot }
	rot := NewAccountRotatorWithIndex(c.Accounts, int(c.NextAccount.Int64))
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
	}, 50)
}


// sendViaBaileysHTTP sends a message via the Baileys Node.js server (HTTP).
func (p *Processor) sendViaBaileysHTTP(c *Campaign, resolved *ResolvedInstance, chatID string, params map[string]string, pushName string) sender.SendResponse {
	msg := BuildMessage(c.Caption, params, pushName, resolved.InstanceID, pushName, phoneFromJID(chatID))

	payload := map[string]interface{}{
		"chat_id":    chatID,
		"caption":    msg,
		"message":    msg,
		"instance_id": resolved.InstanceID,
	}

	if c.Media != "" {
		payload["media_url"] = BuildMessage(c.Media, params, pushName, resolved.InstanceID, pushName, "")
		payload["type"] = "media"
	} else {
		payload["type"] = "text"
	}

	// Try common Baileys API endpoints
	endpoints := []string{
		"http://127.0.0.1:8000/send_message",
		"http://localhost:8000/send_message",
	}

	for _, endpoint := range endpoints {
		bodyBytes, _ := json.Marshal(payload)
		req, err := http.NewRequest("POST", endpoint, bytes.NewBuffer(bodyBytes))
		if err != nil { continue }
		req.Header.Set("Content-Type", "application/json")

		client := &http.Client{Timeout: 30 * time.Second}
		resp, err := client.Do(req)
		if err != nil { continue }
		defer resp.Body.Close()

		respBody, _ := io.ReadAll(resp.Body)
		var result map[string]interface{}
		json.Unmarshal(respBody, &result)

		if resp.StatusCode >= 200 && resp.StatusCode < 300 {
			return sender.SendResponse{Status: "success", Provider: "baileys", MessageID: fmt.Sprintf("%v", result["message_id"])}
		}
	}

	return sender.SendResponse{Status: "error", Provider: "baileys", Error: "failed to send via Baileys HTTP"}
}

// sendViaCloudAPIHTTP sends a message via Meta Cloud API (HTTP).
func (p *Processor) sendViaCloudAPIHTTP(c *Campaign, resolved *ResolvedInstance, chatID string, params map[string]string, pushName string) sender.SendResponse {
	// Read Cloud API config from MySQL
	var phoneNumberID, accessToken string
	if mysqlDB != nil {
		err := mysqlDB.QueryRow(
			"SELECT phone_number_id, access_token FROM sp_whatsapp_cloud_api_config WHERE instance_id = ?",
			resolved.Token,
		).Scan(&phoneNumberID, &accessToken)
		if err != nil {
			return sender.SendResponse{Status: "error", Provider: "cloud_api", Error: "Cloud API config not found: " + err.Error()}
		}
	}

	phone := phoneFromJID(chatID)
	msg := BuildMessage(c.Caption, params, pushName, resolved.InstanceID, pushName, phone)

	// Build Cloud API payload
	payload := map[string]interface{}{
		"messaging_product": "whatsapp",
		"to":   phone,
		"type": "text",
		"text": map[string]string{"body": msg},
	}

	if c.Media != "" {
		mediaURL := BuildMessage(c.Media, params, pushName, resolved.InstanceID, pushName, "")
		payload["type"] = "image"
		payload["image"] = map[string]string{"link": mediaURL, "caption": msg}
		delete(payload, "text")
	}

	bodyBytes, _ := json.Marshal(payload)
	url := fmt.Sprintf("https://graph.facebook.com/v21.0/%s/messages", phoneNumberID)
	req, err := http.NewRequest("POST", url, bytes.NewBuffer(bodyBytes))
	if err != nil {
		return sender.SendResponse{Status: "error", Provider: "cloud_api", Error: err.Error()}
	}
	req.Header.Set("Authorization", "Bearer " + accessToken)
	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{Timeout: 30 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		return sender.SendResponse{Status: "error", Provider: "cloud_api", Error: err.Error()}
	}
	defer resp.Body.Close()

	respBody, _ := io.ReadAll(resp.Body)
	var result map[string]interface{}
	json.Unmarshal(respBody, &result)

	if resp.StatusCode >= 200 && resp.StatusCode < 300 {
		messages, _ := result["messages"].([]interface{})
		msgID := ""
		if len(messages) > 0 {
			m, _ := messages[0].(map[string]interface{})
			msgID, _ = m["id"].(string)
		}
		return sender.SendResponse{Status: "success", Provider: "cloud_api", MessageID: msgID}
	}

	errMsg := ""
	if errObj, ok := result["error"].(map[string]interface{}); ok {
		errMsg, _ = errObj["message"].(string)
	}
	return sender.SendResponse{Status: "error", Provider: "cloud_api", Error: fmt.Sprintf("Cloud API HTTP %d: %s", resp.StatusCode, errMsg)}
}
func (p *Processor) cleanupCampaign(cid int) {
	p.mu.Lock()
	delete(p.rotators, cid)
	p.mu.Unlock()
}

func recordSuccess(c *Campaign, phoneID int, phone, msgID string, accID int) {
	UpdateCampaignResult(c.ID, true, time.Now().Unix()+int64(CalculateDelay(c.MinDelay, c.MaxDelay)), sql.NullInt64{Int64: c.NextAccount.Int64 + 1, Valid: true})
}

func recordFailure(c *Campaign, phoneID int, phone, errMsg string, accID int) {
	UpdateCampaignResult(c.ID, false, time.Now().Unix()+int64(rand.Intn(10)+5), sql.NullInt64{Int64: c.NextAccount.Int64 + 1, Valid: true})
}

func phoneFromJID(jid string) string {
	for i := 0; i < len(jid); i++ {
		if jid[i] == '@' { return jid[:i] }
	}
	return jid
}
