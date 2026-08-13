package webhook

import (
	"bytes"
	"encoding/json"
	"net/http"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

type Payload struct {
	InstanceID string       `json:"instance_id"`
	Gateway    string       `json:"gateway"`
	Data       MessagesData `json:"data"`
}

// SessionStatusPayload is sent when a session connects, disconnects, pairs, or logs out.
type SessionStatusPayload struct {
	InstanceID string `json:"instance_id"`
	Gateway    string `json:"gateway"`
	Type       string `json:"type"`            // "session_status"
	Event      string `json:"event"`           // "connected", "disconnected", "logged_out", "pair_success"
	JID        string `json:"jid,omitempty"`   // filled on pair_success / connected
}

type MessagesData struct {
	Messages []Message `json:"messages"`
}

type Message struct {
	Key               map[string]interface{} `json:"key"`
	Message           map[string]interface{} `json:"message"`
	MessageTimestamp  int64                  `json:"messageTimestamp"`
	PushName          string                 `json:"pushName,omitempty"`
	WaID              string                 `json:"_wa_id,omitempty"`
	AutomationContext map[string]string      `json:"_automation_context,omitempty"`
}

var httpClient = &http.Client{
	Timeout: 30 * time.Second,
}

func Send(url string, body []byte) {
	if url == "" {
		return
	}

	req, err := http.NewRequest("POST", url, bytes.NewReader(body))
	if err != nil {
		logging.Log.Error().Err(err).Msg("Failed to create webhook request")
		return
	}
	req.Header.Set("Content-Type", "application/json")

	resp, err := httpClient.Do(req)
	if err != nil {
		logging.Log.Error().Err(err).Str("url", url).Msg("Webhook request failed")
		return
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 300 {
		logging.Log.Warn().Str("url", url).Int("status", resp.StatusCode).Msg("Webhook returned non-2xx")
	}
}

// SendSessionStatus sends a session status event (connected/disconnected/pair_success/logged_out)
// to the webhook URL so the PHP backend can react without polling.
func SendSessionStatus(url, instanceID, event, jid string) {
	if url == "" {
		return
	}

	payload := SessionStatusPayload{
		InstanceID: instanceID,
		Gateway:    "whatsmeow",
		Type:       "session_status",
		Event:      event,
		JID:        jid,
	}

	body, err := json.Marshal(payload)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to marshal session status payload")
		return
	}

	logging.Log.Info().Str("instance", instanceID).Str("event", event).Msg("Sending session status webhook")
	Send(url, body)
}
