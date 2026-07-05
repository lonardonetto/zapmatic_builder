package receiver

import (
	"encoding/json"

	"go.mau.fi/whatsmeow/types/events"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/normalizer"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/webhook"
)

type Receiver struct {
	webhookURL string
}

func New(webhookURL string) *Receiver {
	return &Receiver{webhookURL: webhookURL}
}

func (r *Receiver) HandleMessage(instanceID string, msg *events.Message) {
	if msg.Info.IsFromMe {
		return
	}

	payload := normalizer.NormalizeMessage(instanceID, msg)

	body, err := json.Marshal(payload)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to marshal message payload")
		return
	}

	logging.Log.Info().
		Str("instance", instanceID).
		Str("from", msg.Info.Sender.User).
		Str("chat", msg.Info.Chat.String()).
		Str("type", msg.Info.Type).
		Msg("Incoming message, forwarding to webhook")

	webhook.Send(r.webhookURL, body)
}

func (r *Receiver) HandleEvent(instanceID string, evt interface{}) {
	switch v := evt.(type) {
	case *events.Connected:
		logging.Log.Info().Str("instance", instanceID).Msg("Device connected")
	case *events.Disconnected:
		logging.Log.Warn().Str("instance", instanceID).Msg("Device disconnected")
	case *events.LoggedOut:
		logging.Log.Warn().Str("instance", instanceID).Msg("Device logged out")
	case *events.PairSuccess:
		logging.Log.Info().Str("instance", instanceID).Str("jid", v.ID.String()).Msg("Device paired successfully")
	case *events.Message:
		r.HandleMessage(instanceID, v)
	case *events.HistorySync:
		logging.Log.Debug().Str("instance", instanceID).Msg("History sync received, skipping")
	default:
		logging.Log.Debug().Str("instance", instanceID).Msg("Unhandled event type")
	}
}
