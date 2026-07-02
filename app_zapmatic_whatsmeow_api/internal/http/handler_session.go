package http

import (
	"context"
	"encoding/json"
	"io"
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

// handleQRCode é o método UNIFICADO de pareamento.
// Conecta o websocket e aguarda o que o WhatsApp enviar:
//   - QR Code → retorna method:"qr", qrcode:"..."
//   - Passkey → retorna method:"passkey", challenge:"..."
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
		// Se já está em andamento, não é erro - apenas continua
		if err.Error() != "instance already connected" {
			r.writeJSON(w, http.StatusConflict, map[string]string{"status": "error", "message": err.Error()})
			return
		}
	}

	result, err := r.rt.Session().WaitConnection(instanceID, 25*time.Second)
	if err != nil {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status": "success", "instance_id": instanceID, "message": err.Error(),
			"state": r.rt.Session().GetStatus(instanceID),
		})
		return
	}

	// Já conectado (sessão existente reconectada)
	if result.State == "connected" {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status": "success", "instance_id": instanceID, "state": "connected",
		})
		return
	}

	// QR Code
	if result.Method == "qr" {
		r.writeJSON(w, http.StatusOK, map[string]string{
			"status": "success", "instance_id": instanceID,
			"method": "qr", "qrcode": result.QRCode,
		})
		return
	}

	// Passkey Challenge
	if result.Method == "passkey" && result.State == "passkey_ready" {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status": "success", "instance_id": instanceID,
			"method": "passkey",
			"challenge": result.Challenge,
			"rp_id":     result.RpID,
			"timeout":   result.Timeout,
		})
		return
	}

	// Fallback
	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success", "instance_id": instanceID,
		"method": result.Method, "state": result.State,
	})
}

// handlePasskeyResponse recebe a resposta WebAuthn do navegador.
// Chamado quando o método de pareamento é passkey.
func (r *Router) handlePasskeyResponse(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	body, err := io.ReadAll(req.Body)
	if err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "Failed to read body"})
		return
	}
	defer req.Body.Close()

	var payload struct {
		InstanceID string          `json:"instance_id"`
		Response   json.RawMessage `json:"response"`
	}
	if err := json.Unmarshal(body, &payload); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "Invalid JSON"})
		return
	}
	if payload.InstanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id is required"})
		return
	}

	if err := r.rt.Session().SendPasskeyResponse(payload.InstanceID, payload.Response); err != nil {
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": err.Error()})
		return
	}

	// Aguarda o código de confirmação
	code, skipUX, err := r.rt.Session().WaitPasskeyCode(payload.InstanceID, 25*time.Second)
	if err != nil {
		r.writeJSON(w, http.StatusOK, map[string]interface{}{
			"status": "success", "instance_id": payload.InstanceID, "message": err.Error(),
		})
		return
	}

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success", "instance_id": payload.InstanceID,
		"state": "passkey_code_ready",
		"code":  code,
		"skip_handoff_ux": skipUX,
	})
}

// handlePasskeyConfirm finaliza o pareamento passkey.
func (r *Router) handlePasskeyConfirm(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	var payload struct {
		InstanceID string `json:"instance_id"`
	}
	body, err := io.ReadAll(req.Body)
	if err == nil {
		json.Unmarshal(body, &payload)
	}
	if payload.InstanceID == "" {
		payload.InstanceID = req.URL.Query().Get("instance_id")
	}
	if payload.InstanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id is required"})
		return
	}

	if err := r.rt.Session().SendPasskeyConfirm(payload.InstanceID); err != nil {
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": err.Error()})
		return
	}

	r.writeJSON(w, http.StatusOK, map[string]string{
		"status": "success", "instance_id": payload.InstanceID, "message": "Passkey confirmation sent",
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
