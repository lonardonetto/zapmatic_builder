package sender

import (
	"context"
	"fmt"
	"time"

	"google.golang.org/protobuf/proto"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/types"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
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

type SendResponse struct {
	Status    string `json:"status"`
	Provider  string `json:"provider"`
	MessageID string `json:"message_id,omitempty"`
	Error     string `json:"error,omitempty"`
}

func New(sm *session.Manager) *Sender {
	return &Sender{sm: sm}
}

func (s *Sender) SendText(ctx context.Context, req SendRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found or not started"}
	}

	client := inst.Client()
	if client == nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "client not initialized"}
	}

	if !client.IsConnected() {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected to WhatsApp"}
	}

	jid, err := types.ParseJID(req.ChatID)
	if err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)}
	}

	msg := &waE2E.Message{}
	if req.Payload.Text != "" {
		msg.ExtendedTextMessage = &waE2E.ExtendedTextMessage{
			Text: proto.String(req.Payload.Text),
		}
	} else {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "text payload is empty"}
	}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()

	resp, err := client.SendMessage(sendCtx, jid, msg)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Str("to", req.ChatID).Msg("Send failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}

	logging.Log.Info().
		Str("instance", req.InstanceID).
		Str("to", req.ChatID).
		Str("id", resp.ID).
		Msg("Message sent")

	return SendResponse{
		Status:    "success",
		Provider:  "whatsmeow",
		MessageID: resp.ID,
	}
}
