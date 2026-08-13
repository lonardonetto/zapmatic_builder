package http

import (
	"context"
	"encoding/json"
	"net/http"
	"strings"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/types"
)

// maxGroupNameLength é o limite de caracteres de nome de grupo do WhatsApp.
const maxGroupNameLength = 25

// maxCloneAddBatch é o tamanho máximo de participantes adicionados por chamada
// ao WhatsApp durante o clone (lotes maiores arriscam bloqueio/rate limit).
const maxCloneAddBatch = 50

// truncateGroupName trunca o nome do grupo para maxGroupNameLength caracteres
// (runes, não bytes), preservando a grafia em UTF-8.
func truncateGroupName(name string) string {
	runes := []rune(strings.TrimSpace(name))
	if len(runes) > maxGroupNameLength {
		return string(runes[:maxGroupNameLength])
	}
	if len(runes) == 0 {
		return "Grupo"
	}
	return string(runes)
}

// chunkParticipants divide a lista de participantes em lotes de no máximo
// batch itens. batch <= 0 cai em maxCloneAddBatch.
func chunkParticipants(participants []string, batch int) [][]string {
	if batch <= 0 {
		batch = maxCloneAddBatch
	}
	var chunks [][]string
	for i := 0; i < len(participants); i += batch {
		end := i + batch
		if end > len(participants) {
			end = len(participants)
		}
		chunks = append(chunks, participants[i:end])
	}
	return chunks
}

// toParticipantJIDs converte uma lista de números (apenas dígitos) em JIDs do
// tipo s.whatsapp.net.
func toParticipantJIDs(phones []string) []types.JID {
	jids := make([]types.JID, 0, len(phones))
	for _, phone := range phones {
		phone = strings.TrimSpace(phone)
		if phone == "" {
			continue
		}
		jids = append(jids, types.NewJID(phone, types.DefaultUserServer))
	}
	return jids
}

type cloneGroupRequest struct {
	InstanceID   string   `json:"instance_id"`
	Name         string   `json:"name"`
	Participants []string `json:"participants"`
}

func (r *Router) handleCloneGroup(w http.ResponseWriter, req *http.Request) {
	if req.Method != http.MethodPost {
		r.writeJSON(w, http.StatusMethodNotAllowed, map[string]string{"status": "error", "message": "Method not allowed"})
		return
	}

	var body cloneGroupRequest
	if err := json.NewDecoder(req.Body).Decode(&body); err != nil {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "invalid JSON: " + err.Error()})
		return
	}
	if body.InstanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id required"})
		return
	}

	name := truncateGroupName(body.Name)

	inst := r.rt.Session().GetInstance(body.InstanceID)
	if inst == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "instance not found"})
		return
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		r.writeJSON(w, http.StatusServiceUnavailable, map[string]string{"status": "error", "message": "not connected"})
		return
	}

	ctx, cancel := context.WithTimeout(req.Context(), 90*time.Second)
	defer cancel()

	// Cria o grupo com o nome já truncado. O criador vira admin implicitamente.
	groupInfo, err := client.CreateGroup(ctx, whatsmeow.ReqCreateGroup{
		Name:         name,
		Participants: []types.JID{},
	})
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", body.InstanceID).Msg("Failed to create cloned group")
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": "failed to create group: " + err.Error()})
		return
	}

	newJID := groupInfo.JID
	jids := toParticipantJIDs(body.Participants)

	added := 0
	failed := 0
	for _, chunk := range chunkParticipantsByJID(jids, maxCloneAddBatch) {
		_, err := client.UpdateGroupParticipants(ctx, newJID, chunk, whatsmeow.ParticipantChangeAdd)
		if err != nil {
			failed += len(chunk)
			logging.Log.Warn().Err(err).Str("instance", body.InstanceID).Int("chunk", len(chunk)).Msg("Failed to add participants to cloned group")
		} else {
			added += len(chunk)
		}
	}

	logging.Log.Info().Str("instance", body.InstanceID).Str("group", newJID.String()).Int("added", added).Int("failed", failed).Msg("Group cloned")

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status":   "success",
		"group_jid": newJID.String(),
		"name":     name,
		"added":    added,
		"failed":   failed,
	})
}

// chunkParticipantsByJID fatia []types.JID em lotes de no máximo batch.
func chunkParticipantsByJID(jids []types.JID, batch int) [][]types.JID {
	if batch <= 0 {
		batch = maxCloneAddBatch
	}
	var chunks [][]types.JID
	for i := 0; i < len(jids); i += batch {
		end := i + batch
		if end > len(jids) {
			end = len(jids)
		}
		chunks = append(chunks, jids[i:end])
	}
	return chunks
}
