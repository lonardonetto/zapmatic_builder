package sender

import (
	"context"
	"fmt"
	"time"

	"google.golang.org/protobuf/proto"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/types"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

func (s *Sender) SendText(ctx context.Context, req SendRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"}
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"}
	}
	jid, err := types.ParseJID(req.ChatID)
	if err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)}
	}
	msg := &waE2E.Message{ExtendedTextMessage: &waE2E.ExtendedTextMessage{Text: proto.String(req.Payload.Text)}}
	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, msg)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Str("to", req.ChatID).Msg("Send failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	logging.Log.Info().Str("instance", req.InstanceID).Str("to", req.ChatID).Str("id", resp.ID).Msg("Message sent")
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

func (s *Sender) SendPresence(ctx context.Context, req PresenceRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"}
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"}
	}
	jid, err := types.ParseJID(req.ChatID)
	if err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)}
	}

	if req.Presence == "available" {
		sendCtx, cancel := context.WithTimeout(ctx, 5*time.Second)
		defer cancel()
		if err := client.SendPresence(sendCtx, types.PresenceAvailable); err != nil {
			return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
		}
		return SendResponse{Status: "success", Provider: "whatsmeow"}
	}

	var chatPresence types.ChatPresence
	var mediaType types.ChatPresenceMedia
	switch req.Presence {
	case "composing":
		chatPresence, mediaType = types.ChatPresenceComposing, types.ChatPresenceMediaText
	case "recording":
		chatPresence, mediaType = types.ChatPresenceComposing, types.ChatPresenceMediaAudio
	case "paused":
		chatPresence, mediaType = types.ChatPresencePaused, types.ChatPresenceMediaText
	default:
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("unknown presence: %s", req.Presence)}
	}

	sendCtx, cancel := context.WithTimeout(ctx, 10*time.Second)
	defer cancel()
	if err := client.SendChatPresence(sendCtx, jid, chatPresence, mediaType); err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}

	if req.Duration > 0 {
		time.Sleep(time.Duration(req.Duration) * time.Second)
		stopCtx, stopCancel := context.WithTimeout(ctx, 5*time.Second)
		defer stopCancel()
		client.SendChatPresence(stopCtx, jid, types.ChatPresencePaused, types.ChatPresenceMediaText)
		return SendResponse{Status: "success", Provider: "whatsmeow", Warning: fmt.Sprintf("active for %ds", req.Duration)}
	}

	return SendResponse{Status: "success", Provider: "whatsmeow"}
}
