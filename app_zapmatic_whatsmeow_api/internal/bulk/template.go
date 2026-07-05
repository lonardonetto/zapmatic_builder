package bulk

import (
	"encoding/json"
	"fmt"
	"strings"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/sender"
)

// TemplateLoader loads and renders message templates from the database.
type TemplateLoader struct{}

// TemplateData represents a stored template from sp_whatsapp_template.
type TemplateData struct {
	ID          int                    `json:"-"`
	TeamID      int                    `json:"-"`
	Type        int                    `json:"type"`
	Name        string                 `json:"name"`
	Data        map[string]interface{} `json:"data"`
	Title       string                 `json:"title,omitempty"`
	Subtitle    string                 `json:"subtitle,omitempty"`
	Text        string                 `json:"text,omitempty"`
	Footer      string                 `json:"footer,omitempty"`
	ImageURL    string                 `json:"image,omitempty"`
	VideoURL    string                 `json:"video,omitempty"`
	DocumentURL string                 `json:"document,omitempty"`
	Buttons     []TemplateButton       `json:"templateButtons,omitempty"`
	Sections    []TemplateSection      `json:"sections,omitempty"`
	Options     []string               `json:"options,omitempty"`
	Cards       []TemplateCard         `json:"cards,omitempty"`
}

type TemplateButton struct {
	QuickReply *QuickReplyButton `json:"quickReplyButton,omitempty"`
	URLButton  *URLButton        `json:"urlButton,omitempty"`
	CallButton *CallButton       `json:"callButton,omitempty"`
}

type QuickReplyButton struct {
	DisplayText string `json:"displayText"`
	ID          string `json:"id"`
}

type URLButton struct {
	DisplayText string `json:"displayText"`
	URL         string `json:"url"`
}

type CallButton struct {
	DisplayText string `json:"displayText"`
	PhoneNumber string `json:"phoneNumber"`
}

type TemplateSection struct {
	Title string       `json:"title"`
	Rows  []TemplateRow `json:"rows"`
}

type TemplateRow struct {
	Title       string `json:"title"`
	Description string `json:"description,omitempty"`
	RowID       string `json:"rowId,omitempty"`
}

type TemplateCard struct {
	Title   string           `json:"title"`
	Body    string           `json:"body"`
	Footer  string           `json:"footer"`
	Media   map[string]string `json:"media,omitempty"`
	Buttons []TemplateButton `json:"buttons,omitempty"`
}

// LoadTemplate fetches a template from the database.
func (tl *TemplateLoader) LoadTemplate(templateID int) (*TemplateData, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	var (
		id, teamID, ttype int
		name, rawData     string
	)
	err := mysqlDB.QueryRow(
		`SELECT id, team_id, type, name, data FROM sp_whatsapp_template WHERE id = ?`,
		templateID,
	).Scan(&id, &teamID, &ttype, &name, &rawData)
	if err != nil {
		return nil, fmt.Errorf("load template %d: %w", templateID, err)
	}
	tpl := &TemplateData{ID: id, TeamID: teamID, Type: ttype, Name: name}
	if rawData != "" {
		json.Unmarshal([]byte(rawData), &tpl.Data)
		json.Unmarshal([]byte(rawData), tpl)
	}
	return tpl, nil
}

// ApplySpintax aplica spintax + common data + params em todos os campos do template.
func (tl *TemplateLoader) ApplySpintax(tpl *TemplateData, params map[string]string, waName, instanceID, pushName, phone string) {
	tpl.Title = BuildMessage(tpl.Title, params, waName, instanceID, pushName, phone)
	tpl.Text = BuildMessage(tpl.Text, params, waName, instanceID, pushName, phone)
	tpl.Footer = BuildMessage(tpl.Footer, params, waName, instanceID, pushName, phone)
	tpl.Subtitle = BuildMessage(tpl.Subtitle, params, waName, instanceID, pushName, phone)
	for i, b := range tpl.Buttons {
		if b.QuickReply != nil {
			tpl.Buttons[i].QuickReply.DisplayText = BuildMessage(b.QuickReply.DisplayText, params, waName, instanceID, pushName, phone)
		}
		if b.URLButton != nil {
			tpl.Buttons[i].URLButton.DisplayText = BuildMessage(b.URLButton.DisplayText, params, waName, instanceID, pushName, phone)
		}
		if b.CallButton != nil {
			tpl.Buttons[i].CallButton.DisplayText = BuildMessage(b.CallButton.DisplayText, params, waName, instanceID, pushName, phone)
		}
	}
	for i, s := range tpl.Sections {
		tpl.Sections[i].Title = BuildMessage(s.Title, params, waName, instanceID, pushName, phone)
		for j, r := range s.Rows {
			tpl.Sections[i].Rows[j].Title = BuildMessage(r.Title, params, waName, instanceID, pushName, phone)
			tpl.Sections[i].Rows[j].Description = BuildMessage(r.Description, params, waName, instanceID, pushName, phone)
		}
	}
	for i, o := range tpl.Options {
		tpl.Options[i] = BuildMessage(o, params, waName, instanceID, pushName, phone)
	}
}

// ToButtonsRequest converte template button para sender.InteractiveRequest.
func (tl *TemplateLoader) ToButtonsRequest(tpl *TemplateData, instanceID, chatID string) sender.InteractiveRequest {
	req := sender.InteractiveRequest{
		InstanceID: instanceID,
		ChatID:     chatID,
		Type:       "buttons",
		Title:      tpl.Title,
		Body:       tpl.Text,
		Footer:     tpl.Footer,
	}
	for _, b := range tpl.Buttons {
		if b.QuickReply != nil {
			req.Buttons = append(req.Buttons, sender.Button{
				ID:   b.QuickReply.ID,
				Text: b.QuickReply.DisplayText,
				Type: "reply",
			})
		}
	}
	return req
}

// ToListRequest converte template list para sender.InteractiveRequest.
func (tl *TemplateLoader) ToListRequest(tpl *TemplateData, instanceID, chatID, buttonText string) sender.InteractiveRequest {
	req := sender.InteractiveRequest{
		InstanceID: instanceID,
		ChatID:     chatID,
		Type:       "list",
		Title:      tpl.Title,
		Body:       tpl.Text,
		Footer:     tpl.Footer,
		ButtonText: buttonText,
	}
	if req.ButtonText == "" {
		req.ButtonText = "Ver opções"
	}
	for _, s := range tpl.Sections {
		sec := sender.Section{Title: s.Title}
		for _, r := range s.Rows {
			sec.Rows = append(sec.Rows, sender.Row{
				ID:          r.RowID,
				Title:       r.Title,
				Description: r.Description,
			})
		}
		req.Sections = append(req.Sections, sec)
	}
	return req
}

// ToPollRequest converte template poll para sender.InteractiveRequest.
func (tl *TemplateLoader) ToPollRequest(tpl *TemplateData, instanceID, chatID string) sender.InteractiveRequest {
	req := sender.InteractiveRequest{
		InstanceID: instanceID,
		ChatID:     chatID,
		Type:       "poll",
		Body:       tpl.Text,
	}
	if req.Body == "" {
		req.Body = tpl.Title
	}
	for _, o := range tpl.Options {
		req.Options = append(req.Options, sender.PollOption{Name: o})
	}
	return req
}

// ExtractMediaURL returns the first media URL from template data.
func (tl *TemplateLoader) ExtractMediaURL(tpl *TemplateData) string {
	if tpl.ImageURL != "" {
		return tpl.ImageURL
	}
	if tpl.VideoURL != "" {
		return tpl.VideoURL
	}
	if tpl.DocumentURL != "" {
		return tpl.DocumentURL
	}
	if tpl.Data != nil {
		if img, ok := tpl.Data["image"].(map[string]interface{}); ok {
			if u, ok := img["url"].(string); ok {
				return u
			}
		}
	}
	return ""
}

// NormalizeType converte o tipo da campanha para o handler correto.
func NormalizeType(campaignType CampaignType) string {
	switch campaignType {
	case CampaignButton:
		return "buttons"
	case CampaignList:
		return "list"
	case CampaignPoll:
		return "poll"
	case CampaignCarousel:
		return "carousel"
	default:
		return "text"
	}
}

// ParseTemplateButtonID gera um ID único para botão.
func ParseTemplateButtonID(index int) string {
	return fmt.Sprintf("btn_%d", index)
}

// TemplateButtonToList converte template buttons para lista de botões no formato do sender.
func TemplateButtonToList(buttons []TemplateButton) []sender.Button {
	var list []sender.Button
	for i, b := range buttons {
		if b.QuickReply != nil {
			id := b.QuickReply.ID
			if id == "" {
				id = ParseTemplateButtonID(i)
			}
			list = append(list, sender.Button{
				ID:   id,
				Text: b.QuickReply.DisplayText,
				Type: "reply",
			})
		}
	}
	return list
}

// GetRowID gera um ID para uma linha de lista.
func GetRowID(sectionIdx, rowIdx int) string {
	return fmt.Sprintf("sec_%d_row_%d", sectionIdx, rowIdx)
}

// ApplyParamsToTemplate aplica parâmetros e spintax em todos os campos string do template.
func ApplyParamsToTemplate(tpl map[string]interface{}, params map[string]string, waName, instanceID, pushName, phone string) {
	applyRecursive := func(val interface{}) interface{} {
		s, ok := val.(string)
		if !ok {
			return val
		}
		return BuildMessage(s, params, waName, instanceID, pushName, phone)
	}
	for k, v := range tpl {
		switch typed := v.(type) {
		case string:
			tpl[k] = applyRecursive(typed)
		case []interface{}:
			for i, item := range typed {
				if m, ok := item.(map[string]interface{}); ok {
					ApplyParamsToTemplate(m, params, waName, instanceID, pushName, phone)
				} else if s, ok := item.(string); ok {
					typed[i] = applyRecursive(s)
				}
			}
		case map[string]interface{}:
			ApplyParamsToTemplate(typed, params, waName, instanceID, pushName, phone)
		}
	}
}

// SanitizeText remove quebras de linha excessivas e normaliza espaços.
func SanitizeText(text string) string {
	text = strings.ReplaceAll(text, "\r\n", "\n")
	text = strings.ReplaceAll(text, "\r", "\n")
	return strings.TrimSpace(text)
}
