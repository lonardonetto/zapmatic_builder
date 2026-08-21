package http

import (
	"context"
	"encoding/json"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"sync"
	"time"

	"github.com/purpshell/meowcaller"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"github.com/lonardonetto/zapmatic-whatsmeow/internal/runtime"
)

// callEvent is one step of a call's lifecycle timeline.
type callEvent struct {
	Event    string    `json:"event"`
	Platform string    `json:"platform,omitempty"`
	Reason   string    `json:"reason,omitempty"`
	At       time.Time `json:"at"`
}

// callEntry tracks one active call for status polling and cancellation.
type callEntry struct {
	CallID     string     `json:"call_id"`
	InstanceID string     `json:"instance_id"`
	Phone      string     `json:"phone"`
	Status     string     `json:"status"` // ringing, active, ended, failed
	StartedAt  time.Time  `json:"started_at"`
	AnsweredAt *time.Time `json:"answered_at,omitempty"`
	EndedAt    *time.Time `json:"ended_at,omitempty"`
	Reason     string     `json:"reason,omitempty"`
	AudioID    string     `json:"audio_id,omitempty"`
	Platform   string     `json:"platform,omitempty"`

	mu                 sync.Mutex
	timeline           []callEvent
	ringTimer          *time.Timer
	endReason          string // override do motivo quando o gateway encerra por ring timeout
	RingDurationSeconds int   `json:"ring_duration_seconds"`
	HeardFullAudio     bool   `json:"heard_full_audio"`
	call               *meowcaller.Call
}

// callStore is a global in-memory registry of active calls.
var (
	callStoreMu sync.RWMutex
	callStore   = make(map[string]*callEntry)
)

// appendTimeline records a lifecycle step on the call entry (thread-safe).
func (e *callEntry) appendTimeline(ev callEvent) {
	if e == nil {
		return
	}
	e.mu.Lock()
	e.timeline = append(e.timeline, ev)
	e.mu.Unlock()
}

// normalizePlatform maps the WhatsApp RemotePlatform string to a stable label:
// "smbi"/"smba" (and any other sm*) -> "mobile", everything else -> "web".
func normalizePlatform(p string) string {
	p = strings.TrimSpace(strings.ToLower(p))
	if p == "" {
		return ""
	}
	if strings.HasPrefix(p, "sm") {
		return "mobile"
	}
	return "web"
}

// applyCallEvent applies one peer lifecycle event to a call entry: it records the
// timeline step and, on "accepted", stores the normalized platform. It is the
// pure, testable core of the call-event bridge.
func applyCallEvent(entry *callEntry, event, platform, reason string) {
	if entry == nil {
		return
	}
	ev := callEvent{Event: event, At: time.Now()}
	switch event {
	case "accepted":
		if p := normalizePlatform(platform); p != "" {
			entry.mu.Lock()
			entry.Platform = p
			entry.mu.Unlock()
			ev.Platform = p
		}
	case "rejected":
		ev.Reason = "rejected"
	case "terminated":
		ev.Reason = reason
	}
	entry.appendTimeline(ev)
}

// RegisterCallEventBridge wires the gateway to the session manager's call
// lifecycle events (CallPreAccept/CallAccept/CallReject/CallTerminate) so the
// platform and peer transitions land in the callStore timeline. It is additive:
// it does not touch the meowcaller fork and coexists with existing handlers.
func RegisterCallEventBridge(rt *runtime.Runtime) {
	if rt == nil || rt.Session() == nil {
		return
	}
	rt.Session().RegisterCallEventListener(func(instanceID, callID, event, platform, reason string) {
		entry := getCall(callID)
		if entry == nil {
			return
		}
		applyCallEvent(entry, event, platform, reason)
	})
}

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

// estimateAudioDuration reads or estimates the duration in seconds of an audio file.
func estimateAudioDuration(filePath string) int {
	if filePath == "" {
		return 0
	}
	info, err := os.Stat(filePath)
	if err != nil || info.Size() == 0 {
		return 0
	}

	ext := strings.ToLower(filepath.Ext(filePath))
	f, err := os.Open(filePath)
	if err != nil {
		// Fallback by size assuming standard 128kbps = 16000 bytes/sec
		return int(info.Size() / 16000)
	}
	defer f.Close()

	if ext == ".wav" {
		// Read WAV header to calculate exact duration
		header := make([]byte, 44)
		if n, err := f.Read(header); err == nil && n >= 44 {
			if string(header[0:4]) == "RIFF" && string(header[8:12]) == "WAVE" {
				byteRate := int64(header[28]) | int64(header[29])<<8 | int64(header[30])<<16 | int64(header[31])<<24
				if byteRate > 0 {
					dataSize := info.Size() - 44
					duration := int(dataSize / byteRate)
					if duration > 0 {
						return duration
					}
				}
			}
		}
	}

	// For MP3 / Opus / OGG / others, estimate based on 128kbps (16KB/s)
	duration := int(info.Size() / 16000)
	if duration <= 0 {
		duration = 1
	}
	return duration
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
		AudioPath     string `json:"audio_path"`
		AudioDuration int    `json:"audio_duration"`
		RingTimeout   int    `json:"ring_timeout"`
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
	// Get the meowcaller client (pre-initialized with the instance)
	mcClient := inst.MeowCaller()
	if mcClient == nil {
		r.writeJSON(w, http.StatusConflict, map[string]string{"status": "error", "message": "meowcaller not initialized for this instance"})
		return
	}

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
	entry.appendTimeline(callEvent{Event: "placed", At: time.Now()})

	// Ring timeout: encerra chamadas que ficam tocando sem resposta (nem accepted
	// nem ended). Default 30s; o worker envia o timeout_ring da campanha.
	ringTimeout := body.RingTimeout
	if ringTimeout <= 0 {
		ringTimeout = 30
	}
	entry.ringTimer = time.AfterFunc(time.Duration(ringTimeout)*time.Second, func() {
		entry.mu.Lock()
		stillRinging := entry.Status == "ringing"
		if stillRinging {
			entry.endReason = "ring_timeout"
		}
		entry.mu.Unlock()
		if stillRinging {
			logging.Log.Info().Str("call_id", call.ID()).Int("ring_timeout", ringTimeout).Msg("Ring timeout exceeded, ending call")
			call.Hangup()
		}
	})

	// Attach audio if provided — load BEFORE OnReady so it's ready when answered
	var audioSrc meowcaller.AudioSource
	resolvedAudioPath := ""
	if body.AudioPath != "" || body.AudioID != "" {
		audioPath := body.AudioPath
		if audioPath == "" {
			audioPath = filepath.Join("storage", "call_audio", body.AudioID+".mp3")
		}
		ext := filepath.Ext(audioPath)
		if ext == "" {
			audioPath += ".mp3"
			ext = ".mp3"
		}
		resolvedAudioPath = audioPath
		if _, err := os.Stat(audioPath); err == nil {
			extLower := strings.ToLower(ext)
			switch extLower {
			case ".wav":
				audioSrc, err = meowcaller.WAVFile(audioPath)
			case ".opus":
				audioSrc, err = meowcaller.OpusFile(audioPath)
			case ".ogg", ".oga":
				audioSrc, err = meowcaller.OpusFile(audioPath)
				if err != nil {
					audioSrc, err = meowcaller.MP3File(audioPath)
				}
			default:
				audioSrc, err = meowcaller.MP3File(audioPath)
			}
			if err != nil || audioSrc == nil {
				logging.Log.Warn().Err(err).Str("audio", audioPath).Msg("Failed to load audio file")
				audioSrc = nil
			}
		} else {
			logging.Log.Warn().Str("audio", audioPath).Msg("Audio file not found")
		}
	}

	// Calculate effective audio duration for fallback timer
	effectiveDuration := body.AudioDuration
	if effectiveDuration <= 0 && resolvedAudioPath != "" {
		effectiveDuration = estimateAudioDuration(resolvedAudioPath)
	}

	// SINGLE OnReady callback — meowcaller only keeps the LAST registered handler
	call.OnReady(func() {
		if entry.ringTimer != nil {
			entry.ringTimer.Stop()
			entry.ringTimer = nil
		}
		now := time.Now()
		entry.mu.Lock()
		entry.Status = "active"
		entry.AnsweredAt = &now
		entry.RingDurationSeconds = int(now.Sub(entry.StartedAt).Seconds())
		// Adiciona o evento "answered" ATOMICO com a mudança de status.
		entry.timeline = append(entry.timeline, callEvent{Event: "answered", At: now})
		entry.mu.Unlock()
		logging.Log.Info().Str("call_id", call.ID()).Msg("Call answered, media flowing")

		if audioSrc != nil {
			logging.Log.Info().Str("call_id", call.ID()).Msg("Playing audio to peer")
			entry.appendTimeline(callEvent{Event: "audio_started", At: time.Now()})
			player := call.Play(audioSrc)
			if player != nil {
				player.OnFinish(func() {
					logging.Log.Info().Str("call_id", call.ID()).Msg("Audio finished (OnFinish), scheduling auto-hangup in 2s")
					entry.mu.Lock()
					entry.HeardFullAudio = true
					entry.timeline = append(entry.timeline, callEvent{Event: "audio_finished", At: time.Now()})
					entry.timeline = append(entry.timeline, callEvent{Event: "hangup_scheduled", At: time.Now()})
					entry.mu.Unlock()
					go func() {
						time.Sleep(2 * time.Second)
						entry.mu.Lock()
						active := entry.Status == "active" || entry.Status == "ringing"
						entry.mu.Unlock()
						if active {
							logging.Log.Info().Str("call_id", call.ID()).Msg("Auto-hangup: 2s after audio ended, hanging up call")
							call.Hangup()
						}
					}()
				})
			}
		}

		// Safety fallback timer: garante encerramento caso OnFinish nao dispare
		if effectiveDuration > 0 {
			hangupDelay := time.Duration(effectiveDuration+3) * time.Second
			logging.Log.Info().Str("call_id", call.ID()).Int("duration", effectiveDuration).Dur("fallback_hangup_in", hangupDelay).Msg("Safety fallback auto-hangup scheduled")
			go func() {
				time.Sleep(hangupDelay)
				entry.mu.Lock()
				active := entry.Status == "active" || entry.Status == "ringing"
				entry.mu.Unlock()
				if active {
					logging.Log.Info().Str("call_id", call.ID()).Msg("Safety fallback auto-hangup: duration exceeded, ending call")
					call.Hangup()
				}
			}()
		}
	})

	call.OnEnd(func(reason string) {
		if entry.ringTimer != nil {
			entry.ringTimer.Stop()
			entry.ringTimer = nil
		}
		now := time.Now()
		entry.mu.Lock()
		if entry.endReason != "" {
			reason = entry.endReason
		}
		entry.Status = "ended"
		entry.Reason = reason
		entry.EndedAt = &now
		// Adiciona o evento "ended" na timeline ATOMICO com a mudança de status,
		// para o /call/status nunca expor "ended" sem o evento correspondente.
		entry.timeline = append(entry.timeline, callEvent{Event: "ended", Reason: reason, At: now})
		entry.mu.Unlock()
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

// callStatusResponse is the JSON-safe snapshot of a call entry, including the
// timeline. It keeps the legacy fields intact (retrocompatibilidade).
type callStatusResponse struct {
	CallID             string      `json:"call_id"`
	InstanceID         string      `json:"instance_id"`
	Phone              string      `json:"phone"`
	Status             string      `json:"status"`
	StartedAt          time.Time   `json:"started_at"`
	AnsweredAt         *time.Time  `json:"answered_at,omitempty"`
	EndedAt            *time.Time  `json:"ended_at,omitempty"`
	Reason             string      `json:"reason,omitempty"`
	AudioID            string      `json:"audio_id,omitempty"`
	Platform           string      `json:"platform,omitempty"`
	RingDurationSeconds int        `json:"ring_duration_seconds,omitempty"`
	HeardFullAudio     bool        `json:"heard_full_audio"`
	Timeline           []callEvent `json:"timeline,omitempty"`
}

// snapshot returns a thread-safe copy of the entry's exported state.
func (e *callEntry) snapshot() callStatusResponse {
	e.mu.Lock()
	defer e.mu.Unlock()
	tl := make([]callEvent, len(e.timeline))
	copy(tl, e.timeline)
	return callStatusResponse{
		CallID:             e.CallID,
		InstanceID:         e.InstanceID,
		Phone:              e.Phone,
		Status:             e.Status,
		StartedAt:          e.StartedAt,
		AnsweredAt:         e.AnsweredAt,
		EndedAt:            e.EndedAt,
		Reason:             e.Reason,
		AudioID:            e.AudioID,
		Platform:           e.Platform,
		RingDurationSeconds: e.RingDurationSeconds,
		HeardFullAudio:     e.HeardFullAudio,
		Timeline:           tl,
	}
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

	r.writeJSON(w, http.StatusOK, entry.snapshot())
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
