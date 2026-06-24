package sender

import (
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/session"
)

type Sender struct {
	sm *session.Manager
}

type SendRequest struct {
	InstanceID string `json:"instance_id"`
	ChatID     string `json:"chat_id"`
	Type       string `json:"type"`
	Payload    struct {
		Text    string `json:"text,omitempty"`
		URL     string `json:"url,omitempty"`
		Caption string `json:"caption,omitempty"`
	} `json:"payload"`
}

type InteractiveRequest struct {
	InstanceID   string       `json:"instance_id"`
	ChatID       string       `json:"chat_id"`
	Type         string       `json:"type"`
	Title        string       `json:"title,omitempty"`
	Body         string       `json:"body"`
	Footer       string       `json:"footer,omitempty"`
	Buttons      []Button     `json:"buttons,omitempty"`
	Sections     []Section    `json:"sections,omitempty"`
	Options      []PollOption `json:"options,omitempty"`
	ButtonText   string       `json:"button_text,omitempty"`
}

type Button struct {
	ID       string `json:"id"`
	Text     string `json:"text"`
	Type     string `json:"type"`
	URL      string `json:"url,omitempty"`
	Phone    string `json:"phone_number,omitempty"`
	CopyCode string `json:"copy_code,omitempty"`
}

type Section struct {
	Title string `json:"title"`
	Rows  []Row  `json:"rows"`
}

type Row struct {
	ID          string `json:"id"`
	Title       string `json:"title"`
	Description string `json:"description,omitempty"`
}

type PollOption struct {
	Name string `json:"name"`
}

type PresenceRequest struct {
	InstanceID string `json:"instance_id"`
	ChatID     string `json:"chat_id"`
	Presence   string `json:"presence"`
	Duration   int    `json:"duration,omitempty"`
}

type SendResponse struct {
	Status    string `json:"status"`
	Provider  string `json:"provider"`
	MessageID string `json:"message_id,omitempty"`
	Warning   string `json:"warning,omitempty"`
	Error     string `json:"error,omitempty"`
}

func New(sm *session.Manager) *Sender {
	return &Sender{sm: sm}
}
