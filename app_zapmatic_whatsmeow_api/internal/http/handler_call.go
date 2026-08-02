package http

import (
	"context"
	"encoding/json"
	"net/http"
	"os"
	"path/filepath"
	"sync"
	"time"

	"github.com/purpshell/meowcaller"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

// callEntry tracks one active call for status polling and cancellation.
type callEntry struct {
	CallID    string    `json:"call_id"`
	InstanceID string   `json:"instance_id"`
	Phone     string    `json:"phone"`
	Status    string    `json:"status"` // ringing, active, ended, failed
	StartedAt time.Time `json:"started_at"`
	AnsweredAt *time.Time `json:"answered_at,omitempty"`
	EndedAt   *time.Time `json:"ended_at,omitempty"`
	Reason    string    `json:"reason,omitempty"`
	AudioID   string    `json:"audio_id,omitempty"`
	call      *meowcaller.Call
}

// callStore is a global in-memory registry of active calls.
var (
	callStoreMu sync.RWMutex
	callStore   = make(map[string]*callEntry)
)

func addCall(e *callEntry) {
	callStoreMu.Lock()
	callStore[e.CallID] = e
	callStoreMu.Unlock()
}

func getCall(id string) *callEntry {
	callStoreMu.RLock()
	defer callStoreMu.RUnlock()
	return callStore[id]
}

func removeCall(id string) {
	callStoreMu.Lock()
	delete(callStore, id)
	callStoreMu.Unlock()
}

// handleCallStart starts an outbound WhatsApp voice call.
// POST /call/start  { "instance_id": "...", "phone": "...", "audio_id": "..." }
func (r *Router) handleCallStart(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	var body struct {
		InstanceID string `json:"instance_id"`
		Phone      string `json:"phone"`
		AudioID    string `json:"audio_id"`
	}
	if err := json.NewDecoder(req.Body).Decode(&body); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON"})
		return
	}
	if body.InstanceID == "" || body.Phone == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id and phone are required"})
		return
	}

	// Get the whatsmeow instance
	inst := r.rt.Session().GetInstance(body.InstanceID)
	if inst == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "instance not found"})
		return
	}
	waClient := inst.Client()
	if waClient == nil || !waClient.IsConnected() {
		r.writeJSON(w, http.StatusConflict, map[string]string{"status": "error", "message": "instance not connected"})
		return
	}

	// Create meowcaller client on the existing whatsmeow client
	mcClient := meowcaller.NewClient(waClient)

	// Place the call
	ctx, cancel := context.WithTimeout(req.Context(), 60*time.Second)
	defer cancel()

	call, err := mcClient.Call(ctx, body.Phone)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", body.InstanceID).Str("phone", body.Phone).Msg("Failed to place call")
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": err.Error()})
		return
	}

	entry := &callEntry{
		CallID:     call.ID(),
		InstanceID: body.InstanceID,
		Phone:      body.Phone,
		Status:     "ringing",
		StartedAt:  time.Now(),
		AudioID:    body.AudioID,
		call:       call,
	}

	// Attach audio if provided
	if body.AudioID != "" {
		audioPath := filepath.Join("storage", "call_audio", body.AudioID+".mp3")
		if _, err := os.Stat(audioPath); err == nil {
			src, err := meowcaller.MP3File(audioPath)
			if err == nil {
				call.OnReady(func() {
					logging.Log.Info().Str("call_id", call.ID()).Str("audio", audioPath).Msg("Playing audio to peer")
					call.Play(src)
				})
			}
		}
	}

	// Lifecycle callbacks
	call.OnReady(func() {
		entry.Status = "active"
		now := time.Now()
		entry.AnsweredAt = &now
		logging.Log.Info().Str("call_id", call.ID()).Msg("Call answered, media flowing")
	})

	call.OnEnd(func(reason string) {
		entry.Status = "ended"
		entry.Reason = reason
		now := time.Now()
		entry.EndedAt = &now
		logging.Log.Info().Str("call_id", call.ID()).Str("reason", reason).Msg("Call ended")
		// Clean up after 5 minutes
		go func() {
			time.Sleep(5 * time.Minute)
			removeCall(entry.CallID)
		}()
	})

	addCall(entry)

	logging.Log.Info().Str("call_id", call.ID()).Str("instance", body.InstanceID).Str("phone", body.Phone).Msg("Call placed")

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status":  "success",
		"call_id": call.ID(),
		"state":   "ringing",
	})
}

// handleCallStatus returns the current state of a call.
// GET /call/status?call_id=...
func (r *Router) handleCallStatus(w http.ResponseWriter, req *http.Request) {
	callID := req.URL.Query().Get("call_id")
	if callID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "call_id is required"})
		return
	}

	entry := getCall(callID)
	if entry == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "call not found"})
		return
	}

	r.writeJSON(w, http.StatusOK, entry)
}

// handleCallCancel terminates an active call.
// POST /call/cancel  { "call_id": "..." }
func (r *Router) handleCallCancel(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	var body struct {
		CallID string `json:"call_id"`
	}
	if err := json.NewDecoder(req.Body).Decode(&body); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON"})
		return
	}

	entry := getCall(body.CallID)
	if entry == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "call not found"})
		return
	}

	if entry.call != nil {
		entry.call.Hangup()
	}

	r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "message": "call cancelled"})
}

// handleCallList returns all active calls.
// GET /call/list
func (r *Router) handleCallList(w http.ResponseWriter, req *http.Request) {
	callStoreMu.RLock()
	defer callStoreMu.RUnlock()

	list := make([]*callEntry, 0, len(callStore))
	for _, e := range callStore {
		list = append(list, e)
	}
	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success",
		"calls":  list,
		"total":  len(list),
	})
}

// handleCallUploadAudio handles audio file upload for call campaigns.
// POST /call/upload-audio (multipart form: file, name)
func (r *Router) handleCallUploadAudio(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	file, header, err := req.FormFile("file")
	if err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "file is required"})
		return
	}
	defer file.Close()

	// Ensure directory exists
	audioDir := filepath.Join("storage", "call_audio")
	if err := os.MkdirAll(audioDir, 0755); err != nil {
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": "failed to create audio directory"})
		return
	}

	// Generate unique filename
	audioID := time.Now().Format("20060102150405") + "_" + header.Filename
	dstPath := filepath.Join(audioDir, audioID)

	dst, err := os.Create(dstPath)
	if err != nil {
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": "failed to save file"})
		return
	}
	defer dst.Close()

	written := int64(0)
	buf := make([]byte, 32*1024)
	for {
		n, readErr := file.Read(buf)
		if n > 0 {
			nw, writeErr := dst.Write(buf[:n])
			if writeErr != nil {
				r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": "failed to write file"})
				return
			}
			written += int64(nw)
		}
		if readErr != nil {
			break
		}
	}

	logging.Log.Info().Str("audio_id", audioID).Int64("bytes", written).Msg("Audio uploaded")

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status":   "success",
		"audio_id": audioID,
		"size":     written,
	})
}
