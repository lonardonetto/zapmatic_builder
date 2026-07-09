package http

import (
	"context"
	"net/http"
	"strings"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

type GroupInfo struct {
	JID          string   `json:"jid"`
	Name         string   `json:"name"`
	Participants []string `json:"participants"`
}

func (r *Router) handleListGroups(w http.ResponseWriter, req *http.Request) {
	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id required"})
		return
	}

	inst := r.rt.Session().GetInstance(instanceID)
	if inst == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "instance not found"})
		return
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		r.writeJSON(w, http.StatusServiceUnavailable, map[string]string{"status": "error", "message": "not connected"})
		return
	}

	ctx, cancel := context.WithTimeout(req.Context(), 30*time.Second)
	defer cancel()

	joinedGroups, err := client.GetJoinedGroups(ctx)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to get joined groups")
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": "failed to fetch groups"})
		return
	}

	groups := make([]GroupInfo, 0, len(joinedGroups))
	for _, g := range joinedGroups {
		participants := make([]string, 0, len(g.Participants))
		for _, p := range g.Participants {
			participants = append(participants, p.JID.User) // phone number only
		}
		groups = append(groups, GroupInfo{
			JID:          g.JID.User,
			Name:         g.Name,
			Participants: participants,
		})
	}

	logging.Log.Info().Str("instance", instanceID).Int("groups", len(groups)).Msg("Groups listed")

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success",
		"groups": groups,
	})
}

// Find which groups a specific phone number belongs to
func (r *Router) handleFindMemberGroups(w http.ResponseWriter, req *http.Request) {
	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id required"})
		return
	}

	phone := req.URL.Query().Get("phone")
	if phone == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "phone parameter required"})
		return
	}

	inst := r.rt.Session().GetInstance(instanceID)
	if inst == nil {
		r.writeJSON(w, http.StatusNotFound, map[string]string{"status": "error", "message": "instance not found"})
		return
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		r.writeJSON(w, http.StatusServiceUnavailable, map[string]string{"status": "error", "message": "not connected"})
		return
	}

	ctx, cancel := context.WithTimeout(req.Context(), 30*time.Second)
	defer cancel()

	joinedGroups, err := client.GetJoinedGroups(ctx)
	if err != nil {
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": "failed to fetch groups"})
		return
	}

	// Clean phone: strip @s.whatsapp.net and country code prefix if any
	cleanPhone := strings.TrimSuffix(phone, "@s.whatsapp.net")
	
	var matchingGroups []GroupInfo
	for _, g := range joinedGroups {
		var participants []string
		found := false
		for _, p := range g.Participants {
			participants = append(participants, p.JID.User)
			if p.JID.User == cleanPhone {
				found = true
			}
		}
		if found {
			matchingGroups = append(matchingGroups, GroupInfo{
				JID:          g.JID.User,
				Name:         g.Name,
				Participants: participants,
			})
		}
	}

	logging.Log.Info().Str("instance", instanceID).Str("phone", cleanPhone).Int("matching_groups", len(matchingGroups)).Msg("Member groups found")

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success",
		"phone":  cleanPhone,
		"groups": matchingGroups,
	})
}
