package bulk

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

// WebhookClient sends campaign results back to the PHP backend.
type WebhookClient struct {
	baseURL    string
	httpClient *http.Client
}

// WebhookPayload is sent to PHP after each message in a campaign.
type WebhookPayload struct {
	Event       string `json:"event"`
	CampaignID  int    `json:"campaign_id"`
	TeamID      int    `json:"team_id"`
	PhoneNumber string `json:"phone_number"`
	ContactID   int    `json:"contact_id"`
	Status      string `json:"status"` // "sent" | "failed" | "completed"
	MessageID   string `json:"message_id,omitempty"`
	Error       string `json:"error,omitempty"`
	Sent        int    `json:"sent"`
	Failed      int    `json:"failed"`
	Total       int    `json:"total"`
	Timestamp   int64  `json:"timestamp"`
	AccountID   int    `json:"account_id"`
}

// NewWebhookClient creates a webhook client.
// baseURL should be the PHP backend URL, e.g. "https://zapmatic.tec.br/index.php".
func NewWebhookClient(baseURL string) *WebhookClient {
	return &WebhookClient{
		baseURL: baseURL,
		httpClient: &http.Client{
			Timeout: 10 * time.Second,
		},
	}
}

// Notify sends a webhook event to PHP.
func (wc *WebhookClient) Notify(payload WebhookPayload) error {
	if wc.baseURL == "" {
		return nil // webhook not configured
	}
	body, err := json.Marshal(payload)
	if err != nil {
		return fmt.Errorf("marshal webhook: %w", err)
	}
	url := wc.baseURL + "/bot-builder/webhook/bulk"
	resp, err := wc.httpClient.Post(url, "application/json", bytes.NewReader(body))
	if err != nil {
		logging.Log.Warn().Err(err).Str("url", url).Msg("Webhook notify failed")
		return err
	}
	defer resp.Body.Close()
	logging.Log.Debug().
		Int("campaign_id", payload.CampaignID).
		Str("status", payload.Status).
		Str("phone", payload.PhoneNumber).
		Msg("Webhook sent")
	return nil
}

// NotifyAsync sends notification in background goroutine.
func (wc *WebhookClient) NotifyAsync(payload WebhookPayload) {
	go func() {
		if err := wc.Notify(payload); err != nil {
			logging.Log.Warn().Err(err).Int("campaign", payload.CampaignID).Msg("Async webhook failed")
		}
	}()
}
