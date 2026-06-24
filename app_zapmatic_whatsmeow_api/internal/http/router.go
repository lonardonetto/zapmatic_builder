package http

import (
	"context"
	"encoding/json"
	"net/http"
	"strings"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/capabilities"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/sender"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/session"
)

type Router struct {
	mux    *http.ServeMux
	sm     *session.Manager
	sender *sender.Sender
	apiKey string
}

func NewRouter(sm *session.Manager, apiKey string) *Router {
	r := &Router{
		mux:    http.NewServeMux(),
		sm:     sm,
		sender: sender.New(sm),
		apiKey: apiKey,
	}
	r.registerRoutes()
	return r
}

func (r *Router) ServeHTTP(w http.ResponseWriter, req *http.Request) {
	r.mux.ServeHTTP(w, req)
}

func (r *Router) registerRoutes() {
	r.mux.HandleFunc("/health", r.corsMiddleware(r.handleHealth))
	r.mux.HandleFunc("/capabilities", r.corsMiddleware(r.handleCapabilities))
	r.mux.HandleFunc("/status", r.corsMiddleware(r.authGuard(r.handleStatus)))
	r.mux.HandleFunc("/qrcode", r.corsMiddleware(r.authGuard(r.handleQRCode)))
	r.mux.HandleFunc("/profile", r.corsMiddleware(r.authGuard(r.handleProfile)))
	r.mux.HandleFunc("/logout", r.corsMiddleware(r.authGuard(r.handleLogout)))
	r.mux.HandleFunc("/send/text", r.corsMiddleware(r.authGuard(r.handleSendText)))
	r.mux.HandleFunc("/send/", r.corsMiddleware(r.authGuard(r.handleSend)))
}

func (r *Router) corsMiddleware(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, req *http.Request) {
		w.Header().Set("Access-Control-Allow-Origin", "*")
		w.Header().Set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS")
		w.Header().Set("Access-Control-Allow-Headers", "Content-Type, X-Zapmatic-Gateway-Key, Authorization")

		if req.Method == http.MethodOptions {
			w.WriteHeader(http.StatusOK)
			return
		}

		next(w, req)
	}
}

func (r *Router) authGuard(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, req *http.Request) {
		if r.apiKey != "" {
			key := req.Header.Get("X-Zapmatic-Gateway-Key")
			if key == "" {
				key = req.URL.Query().Get("api_key")
			}
			if key != r.apiKey {
				r.writeJSON(w, http.StatusUnauthorized, map[string]string{
					"status":  "error",
					"message": "Invalid or missing API key",
				})
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
		logging.Log.Error().Err(err).Msg("Failed to encode JSON response")
	}
}

func (r *Router) instanceFromRequest(req *http.Request) string {
	if id := req.URL.Query().Get("instance_id"); id != "" {
		return id
	}
	var body struct {
		InstanceID string `json:"instance_id"`
	}
	if req.Body != nil && req.Method == http.MethodPost {
		if err := json.NewDecoder(req.Body).Decode(&body); err == nil && body.InstanceID != "" {
			return body.InstanceID
		}
	}
	return ""
}

func (r *Router) handleHealth(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	instances := r.sm.ListInstances()
	connected := 0
	for _, inst := range instances {
		if inst.Online {
			connected++
		}
	}

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status":         "ok",
		"provider":       "whatsmeow",
		"version":        "0.1.0",
		"total_instances": len(instances),
		"connected":      connected,
	})
}

func (r *Router) handleCapabilities(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	caps := capabilities.Get()
	r.writeJSON(w, http.StatusOK, caps)
}

func (r *Router) handleStatus(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		instances := r.sm.ListInstances()
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status":    "success",
			"instances": instances,
		})
		return
	}

	status := r.sm.GetStatus(instanceID)
	if status == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{
			"status": "error", "message": "Instance not found",
		})
		return
	}

	r.writeJSON(w, http.StatusOK, status)
}

func (r *Router) handleQRCode(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	instanceID := req.URL.Query().Get("instance_id")
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{
			"status": "error", "message": "instance_id is required",
		})
		return
	}

	if err := r.sm.StartInstance(context.Background(), instanceID); err != nil {
		r.writeJSON(w, http.StatusConflict, map[string]string{
			"status": "error", "message": err.Error(),
		})
		return
	}

	qrCode, err := r.sm.WaitQR(instanceID, 25*time.Second)
	if err != nil {
		status := r.sm.GetStatus(instanceID)
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status":      "success",
			"instance_id": instanceID,
			"message":     err.Error(),
			"state":       status,
		})
		return
	}

	r.writeJSON(w, http.StatusOK, map[string]string{
		"status":      "success",
		"instance_id": instanceID,
		"qrcode":      qrCode,
	})
}

func (r *Router) handleProfile(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	instanceID := req.URL.Query().Get("instance_id")
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id is required"})
		return
	}

	inst := r.sm.GetInstance(instanceID)
	if inst == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "Instance not found"})
		return
	}

	if inst.State != session.StateConnected {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status": "success",
			"id":     instanceID,
			"state":  "not_connected",
		})
		return
	}

	// Extrai telefone ANTES do pushName para usar como fallback sem sufixo AD
	phone := inst.Phone
	if phone == "" && inst.Client() != nil && inst.Client().Store != nil && inst.Client().Store.ID != nil {
		phone = inst.Client().Store.ID.User
	}

	pushName := inst.DisplayName()
	if pushName == "" {
		if phone != "" {
			pushName = phone
		} else {
			pushName = instanceID
		}
	}

	// Busca URL da foto de perfil (igual Baileys: retorna URL do CDN, não baixa)
	var avatarURL string
	client := inst.Client()
	if client != nil {
		jid := client.Store.ID
		if jid != nil {
			// ToNonAD() remove sufixo de dispositivo (:XX) — WhatsApp rejeita JIDs AD em profile picture
			cleanJID := jid.ToNonAD()
			picCtx, picCancel := context.WithTimeout(context.Background(), 15*time.Second)
			defer picCancel()
			picInfo, err := client.GetProfilePictureInfo(picCtx, cleanJID, nil)
			if err != nil {
				logging.Log.Warn().Err(err).Str("instance", instanceID).Str("jid", cleanJID.String()).Msg("GetProfilePictureInfo failed")
			}
			if err == nil && picInfo != nil && picInfo.URL != "" {
				avatarURL = picInfo.URL
				logging.Log.Info().Str("instance", instanceID).Str("avatar_url", avatarURL).Msg("Profile picture URL retrieved")
			}
		} else {
			logging.Log.Warn().Str("instance", instanceID).Msg("Store.ID is nil, cannot fetch profile picture")
		}
	}

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status":       "success",
		"id":           instanceID,
		"jid":          inst.JID,
		"phone":        phone,
		"push_name":    pushName,
		"avatar_url":   avatarURL,
		"avatar_base64": "", // ponytail: mantido para compat; remover quando PHP migrar 100%
		"state":        "connected",
	})
}

func (r *Router) handleLogout(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{
			"status": "error", "message": "instance_id is required",
		})
		return
	}

	fullLogout := strings.ToLower(req.URL.Query().Get("type")) == "full"

	if fullLogout {
		if err := r.sm.Logout(instanceID); err != nil {
			r.writeJSON(w, http.StatusNotFound, map[string]string{
				"status": "error", "message": err.Error(),
			})
			return
		}
	} else {
		if err := r.sm.Disconnect(instanceID); err != nil {
			r.writeJSON(w, http.StatusNotFound, map[string]string{
				"status": "error", "message": err.Error(),
			})
			return
		}
	}

	r.writeJSON(w, http.StatusOK, map[string]string{
		"status":      "success",
		"instance_id": instanceID,
	})
}

func (r *Router) handleSendText(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	var sendReq sender.SendRequest
	if err := json.NewDecoder(req.Body).Decode(&sendReq); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}

	if sendReq.InstanceID == "" || sendReq.ChatID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id and chat_id are required"})
		return
	}

	resp := r.sender.SendText(req.Context(), sendReq)
	status := http.StatusOK
	if resp.Status == "error" {
		status = http.StatusInternalServerError
	}
	r.writeJSON(w, status, resp)
}

func (r *Router) handleSend(w http.ResponseWriter, req *http.Request) {
	r.writeJSON(w, http.StatusNotImplemented, map[string]string{
		"status": "error", "message": "send endpoint not implemented yet, use /send/text",
	})
}
