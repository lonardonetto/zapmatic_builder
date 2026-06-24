package http

import (
	"context"
	"net/http"
	"strings"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/session"
)

func (r *Router) handleStatus(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodGet {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status": "success", "instances": r.rt.Session().ListInstances(),
		})
		return
	}
	status := r.rt.Session().GetStatus(instanceID)
	if status == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "Instance not found"})
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
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id is required"})
		return
	}
	if err := r.rt.Session().StartInstance(context.Background(), instanceID); err != nil {
		r.writeJSON(w, http.StatusConflict, map[string]string{"status": "error", "message": err.Error()})
		return
	}
	qrCode, err := r.rt.Session().WaitQR(instanceID, 25*time.Second)
	if err != nil {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status": "success", "instance_id": instanceID, "message": err.Error(),
			"state": r.rt.Session().GetStatus(instanceID),
		})
		return
	}
	r.writeJSON(w, http.StatusOK, map[string]string{
		"status": "success", "instance_id": instanceID, "qrcode": qrCode,
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
	inst := r.rt.Session().GetInstance(instanceID)
	if inst == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "Instance not found"})
		return
	}
	if inst.State != session.StateConnected {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{"status": "success", "id": instanceID, "state": "not_connected"})
		return
	}
	phone := inst.Phone
	if phone == "" && inst.Client() != nil && inst.Client().Store != nil && inst.Client().Store.ID != nil {
		phone = inst.Client().Store.ID.User
	}
	pushName := inst.DisplayName()
	if pushName == "" {
		for i := 0; i < 25; i++ {
			time.Sleep(1000 * time.Millisecond)
			if pushName = inst.DisplayName(); pushName != "" { break }
		}
	}
	if pushName == "" {
		if phone != "" { pushName = phone } else { pushName = instanceID }
	}
	var avatarURL string
	if client := inst.Client(); client != nil {
		if jid := client.Store.ID; jid != nil {
			picCtx, picCancel := context.WithTimeout(context.Background(), 15*time.Second)
			defer picCancel()
			picInfo, err := client.GetProfilePictureInfo(picCtx, jid.ToNonAD(), nil)
			if err == nil && picInfo != nil && picInfo.URL != "" { avatarURL = picInfo.URL }
		}
	}
	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success", "id": instanceID, "jid": inst.JID,
		"phone": phone, "push_name": pushName, "avatar_url": avatarURL,
		"state": "connected",
	})
}

func (r *Router) handleLogout(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}
	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id is required"})
		return
	}
	var err error
	if strings.ToLower(req.URL.Query().Get("type")) == "full" {
		err = r.rt.Session().Logout(instanceID)
	} else {
		err = r.rt.Session().Disconnect(instanceID)
	}
	if err != nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": err.Error()})
		return
	}
	r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "instance_id": instanceID})
}
