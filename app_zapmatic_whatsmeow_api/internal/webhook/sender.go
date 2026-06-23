package webhook

import (
	"bytes"
	"net/http"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

type Payload struct {
	InstanceID string       `json:"instance_id"`
	Gateway    string       `json:"gateway"`
	Data       MessagesData `json:"data"`
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
