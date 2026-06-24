package http

import (
	"encoding/json"
	"net/http"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/sender"
)

func (r *Router) handleSendButtons(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	var ir sender.InteractiveRequest
	if err := json.NewDecoder(req.Body).Decode(&ir); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}
	if ir.InstanceID == "" || ir.ChatID == "" || len(ir.Buttons) == 0 {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id, chat_id and buttons are required"})
		return
	}
	resp := r.sender.SendButtons(req.Context(), ir)
	status := http.StatusOK
	if resp.Status == "error" { status = http.StatusInternalServerError }
	r.writeJSON(w, status, resp)
}

func (r *Router) handleSendList(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	var ir sender.InteractiveRequest
	if err := json.NewDecoder(req.Body).Decode(&ir); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}
	if ir.InstanceID == "" || ir.ChatID == "" || len(ir.Sections) == 0 {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id, chat_id and sections are required"})
		return
	}
	resp := r.sender.SendList(req.Context(), ir)
	status := http.StatusOK
	if resp.Status == "error" { status = http.StatusInternalServerError }
	r.writeJSON(w, status, resp)
}

func (r *Router) handleSendPoll(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	var ir sender.InteractiveRequest
	if err := json.NewDecoder(req.Body).Decode(&ir); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}
	if ir.InstanceID == "" || ir.ChatID == "" || len(ir.Options) < 2 {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id, chat_id and at least 2 options are required"})
		return
	}
	resp := r.sender.SendPoll(req.Context(), ir)
	status := http.StatusOK
	if resp.Status == "error" { status = http.StatusInternalServerError }
	r.writeJSON(w, status, resp)
}
