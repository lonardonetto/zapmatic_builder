package http

import (
	"encoding/json"
	"net/http"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/sender"
)

func (r *Router) handleSendText(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	var sr sender.SendRequest
	if err := json.NewDecoder(req.Body).Decode(&sr); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}
	if sr.InstanceID == "" || sr.ChatID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id and chat_id are required"})
		return
	}
	resp := r.sender.SendText(req.Context(), sr)
	status := http.StatusOK
	if resp.Status == "error" { status = http.StatusInternalServerError }
	r.writeJSON(w, status, resp)
}

func (r *Router) handleSendPresence(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	var pr sender.PresenceRequest
	if err := json.NewDecoder(req.Body).Decode(&pr); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}
	if pr.InstanceID == "" || pr.ChatID == "" || pr.Presence == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id, chat_id and presence are required"})
		return
	}
	resp := r.sender.SendPresence(req.Context(), pr)
	status := http.StatusOK
	if resp.Status == "error" { status = http.StatusInternalServerError }
	r.writeJSON(w, status, resp)
}

func (r *Router) handleSendMedia(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	var sr sender.SendRequest
	if err := json.NewDecoder(req.Body).Decode(&sr); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}
	if sr.InstanceID == "" || sr.ChatID == "" || sr.Payload.Text == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id, chat_id and payload.text (media URL) are required"})
		return
	}
	resp := r.sender.SendMedia(req.Context(), sr)
	status := http.StatusOK
	if resp.Status == "error" { status = http.StatusInternalServerError }
	r.writeJSON(w, status, resp)
}
