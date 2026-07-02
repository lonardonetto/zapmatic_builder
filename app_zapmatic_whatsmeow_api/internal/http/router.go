package http

import (
	"encoding/json"
	"net/http"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/runtime"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/sender"
)

type Router struct {
	mux    *http.ServeMux
	rt     *runtime.Runtime
	sender *sender.Sender
	apiKey string
}

func NewRouter(rt *runtime.Runtime, apiKey string) *Router {
	r := &Router{
		mux:    http.NewServeMux(),
		rt:     rt,
		sender: sender.New(rt.Session()),
		apiKey: apiKey,
	}
	r.mux.HandleFunc("/health", r.corsMiddleware(r.handleHealth))
	r.mux.HandleFunc("/capabilities", r.corsMiddleware(r.handleCapabilities))
	r.mux.HandleFunc("/status", r.corsMiddleware(r.authGuard(r.handleStatus)))
	r.mux.HandleFunc("/qrcode", r.corsMiddleware(r.authGuard(r.handleQRCode)))
	r.mux.HandleFunc("/profile", r.corsMiddleware(r.authGuard(r.handleProfile)))
	r.mux.HandleFunc("/logout", r.corsMiddleware(r.authGuard(r.handleLogout)))
	r.mux.HandleFunc("/passkey/response", r.corsMiddleware(r.authGuard(r.handlePasskeyResponse)))
	r.mux.HandleFunc("/passkey/confirm", r.corsMiddleware(r.authGuard(r.handlePasskeyConfirm)))
	r.mux.HandleFunc("/send/text", r.corsMiddleware(r.authGuard(r.handleSendText)))
	r.mux.HandleFunc("/send/presence", r.corsMiddleware(r.authGuard(r.handleSendPresence)))
	r.mux.HandleFunc("/send/media", r.corsMiddleware(r.authGuard(r.handleSendMedia)))
	r.mux.HandleFunc("/send/buttons", r.corsMiddleware(r.authGuard(r.handleSendButtons)))
	r.mux.HandleFunc("/send/list", r.corsMiddleware(r.authGuard(r.handleSendList)))
	r.mux.HandleFunc("/send/poll", r.corsMiddleware(r.authGuard(r.handleSendPoll)))
	r.mux.HandleFunc("/files/", r.corsMiddleware(r.handleFiles))

	// Bulk messaging routes
	r.mux.HandleFunc("/bulk/campaigns", r.corsMiddleware(r.authGuard(r.handleBulkListCampaigns)))
	r.mux.HandleFunc("/bulk/campaign/", r.corsMiddleware(r.authGuard(r.handleBulkCampaignAction)))
	r.mux.HandleFunc("/bulk/start", r.corsMiddleware(r.authGuard(r.handleBulkStart)))
	r.mux.HandleFunc("/bulk/stop", r.corsMiddleware(r.authGuard(r.handleBulkStop)))
	r.mux.HandleFunc("/bulk/status", r.corsMiddleware(r.authGuard(r.handleBulkStatus)))
	r.mux.HandleFunc("/bulk/validate", r.corsMiddleware(r.authGuard(r.handleBulkValidate)))
	return r
}

func (r *Router) ServeHTTP(w http.ResponseWriter, req *http.Request) {
	r.mux.ServeHTTP(w, req)
}

func (r *Router) corsMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, req *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, X-Zapmatic-Gateway-Key, Authorization")
		if req.Method == http.MethodOptions {
			w.WriteHeader(http.StatusOK); return
		}
		next(w, req)
	}
}

func (r *Router) authGuard(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, req *http.Request) {
		if r.apiKey != "" {
			key := req.Header.Get("X-Zapmatic-Gateway-Key")
			if key == "" { key = req.URL.Query().Get("api_key") }
			if key != r.apiKey {
				r.writeJSON(w, http.StatusUnauthorized, map[string]string{"status": "error", "message": "Invalid or missing API key"})
				return
			}
		}
		next(w, req)
	}
}

func (r *Router) writeJSON(w http.ResponseWriter, status int, data interface{}) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	if err := json.NewEncoder(w).Encode(data); err != nil {
		logging.Log.Error().Err(err).Msg("Failed to encode JSON")
	}
}

func (r *Router) instanceFromRequest(req *http.Request) string {
	if id := req.URL.Query().Get("instance_id"); id != "" { return id }
	var body struct{ InstanceID string `json:"instance_id"` }
	if req.Body != nil && req.Method == http.MethodPost {
		json.NewDecoder(req.Body).Decode(&body)
	}
	return body.InstanceID
}
