package http

import (
	"context"
	"net/http"
	"strings"
	"sync"
	"time"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/types"
)

type GroupParticipantInfo struct {
	ID    string `json:"id"`
	LID   string `json:"lid"`
	Admin bool   `json:"admin"`
}

type GroupInfo struct {
	JID           string                 `json:"jid"`
	Name          string                 `json:"name"`
	Participants  []GroupParticipantInfo `json:"participants"`
	Creation      int64                  `json:"creation"`
	IsCommunity   bool                   `json:"isCommunity"`
	Announce      bool                   `json:"announce"`
	Owner         string                 `json:"owner"`
	ProfilePicURL string                 `json:"profilePicUrl"`
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

	ctx, cancel := context.WithTimeout(req.Context(), 45*time.Second)
	defer cancel()

	joinedGroups, err := client.GetJoinedGroups(ctx)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to get joined groups")
		r.writeJSON(w, http.StatusInternalServerError, map[string]string{"status": "error", "message": "failed to fetch groups"})
		return
	}

	groups := make([]GroupInfo, len(joinedGroups))
	var wg sync.WaitGroup

	for i, g := range joinedGroups {
		wg.Add(1)
		go func(i int, g *types.GroupInfo) {
			defer wg.Done()

			participants := make([]GroupParticipantInfo, 0, len(g.Participants))
			for _, p := range g.Participants {
				phone := p.PhoneNumber.User
				if phone == "" {
					phone = p.JID.User
				}
				participants = append(participants, GroupParticipantInfo{
					ID:    phone,
					LID:   p.LID.User,
					Admin: p.IsAdmin || p.IsSuperAdmin,
				})
			}

			// Tentar buscar miniatura e ignorar erro
			var picUrl string
			pic, _ := client.GetProfilePictureInfo(ctx, g.JID, &whatsmeow.GetProfilePictureParams{
				Preview: true,
			})
			if pic != nil {
				picUrl = pic.URL
			}

			groups[i] = GroupInfo{
				JID:           g.JID.User,
				Name:          g.Name,
				Participants:  participants,
				Creation:      g.GroupCreated.Unix(),
				IsCommunity:   g.IsParent,
				Announce:      g.IsAnnounce,
				Owner:         g.OwnerJID.User,
				ProfilePicURL: picUrl,
			}
		}(i, g)
	}

	wg.Wait()

	logging.Log.Info().Str("instance", instanceID).Int("groups", len(groups)).Msg("Groups listed")

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success",
		"groups": groups,
	})
}

func (r *Router) handleFindMemberGroups(w http.ResponseWriter, req *http.Request) {
	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id required"})
		return
	}

	phone := req.URL.Query().Get("phone")
	if phone == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "phone required"})
		return
	}

	cleanPhone := strings.Split(phone, "@")[0]

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

	var matchingGroups []GroupInfo
	for _, g := range joinedGroups {
		var participants []GroupParticipantInfo
		found := false
		for _, p := range g.Participants {
			ph := p.PhoneNumber.User
			if ph == "" {
				ph = p.JID.User
			}
			participants = append(participants, GroupParticipantInfo{
				ID:    ph,
				LID:   p.LID.User,
				Admin: p.IsAdmin || p.IsSuperAdmin,
			})
			if ph == cleanPhone || p.JID.User == cleanPhone {
				found = true
			}
		}
		if found {
			matchingGroups = append(matchingGroups, GroupInfo{
				JID:           g.JID.User,
				Name:          g.Name,
				Participants:  participants,
				Creation:      g.GroupCreated.Unix(),
				IsCommunity:   g.IsParent,
				Announce:      g.IsAnnounce,
				Owner:         g.OwnerJID.User,
				ProfilePicURL: "", // Não buscar fotos aqui para ser rápido
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

func (r *Router) handleResolveParticipant(w http.ResponseWriter, req *http.Request) {
	instanceID := r.instanceFromRequest(req)
	if instanceID == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "instance_id required"})
		return
	}

	lid := req.URL.Query().Get("lid")
	if lid == "" {
		r.writeJSON(w, http.StatusBadRequest, map[string]string{"status": "error", "message": "lid required"})
		return
	}
	cleanLid := strings.Split(lid, "@")[0]

	groupID := req.URL.Query().Get("group_id")
	cleanGroupID := strings.Split(groupID, "@")[0]

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

	// If group_id is provided, search only that group (fast)
	if cleanGroupID != "" {
		groupJID := types.NewJID(cleanGroupID, types.GroupServer)
		groupInfo, err := client.GetGroupInfo(ctx, groupJID)
		if err != nil {
			logging.Log.Error().Err(err).Str("instance", instanceID).Str("group", cleanGroupID).Msg("Failed to get group info for LID resolve")
			r.writeJSON(w, http.StatusOK, map[string]string{"status": "error", "message": "group not found"})
			return
		}
		for _, p := range groupInfo.Participants {
			if p.LID.User == cleanLid {
				phone := p.PhoneNumber.User
				if phone == "" {
					phone = p.JID.User
				}
				logging.Log.Info().Str("instance", instanceID).Str("lid", cleanLid).Str("phone", phone).Msg("LID resolved via group")
				r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "phone": phone})
				return
			}
		}
		logging.Log.Warn().Str("instance", instanceID).Str("lid", cleanLid).Msg("LID not found in group participants, trying store")
		// Fallback: try direct LID→PN mapping from local store
		pnJid, err := client.Store.LIDs.GetPNForLID(ctx, types.NewJID(cleanLid, types.HiddenUserServer))
		if err == nil && pnJid.User != "" && pnJid.User != cleanLid {
			phone := pnJid.User
			logging.Log.Info().Str("instance", instanceID).Str("lid", cleanLid).Str("phone", phone).Msg("LID resolved via store LID→PN mapping")
			r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "phone": phone})
			return
		}
		r.writeJSON(w, http.StatusOK, map[string]string{"status": "error", "message": "lid not found in group"})
		return
	}

	// No group_id provided: search ALL groups (fallback for private messages)
	joinedGroups, err := client.GetJoinedGroups(ctx)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", instanceID).Msg("Failed to get joined groups for LID resolve (all)")
		r.writeJSON(w, http.StatusOK, map[string]string{"status": "error", "message": "failed to fetch groups"})
		return
	}
	for _, g := range joinedGroups {
		for _, p := range g.Participants {
			if p.LID.User == cleanLid {
				phone := p.PhoneNumber.User
				if phone == "" {
					phone = p.JID.User
				}
				logging.Log.Info().Str("instance", instanceID).Str("lid", cleanLid).Str("phone", phone).Str("group", g.Name).Msg("LID resolved via all-groups fallback")
				r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "phone": phone})
				return
			}
		}
	}

	// Fallback: try direct LID→PN mapping from local store (works even without groups)
	pnJid, err := client.Store.LIDs.GetPNForLID(ctx, types.NewJID(cleanLid, types.HiddenUserServer))
	if err == nil && pnJid.User != "" && pnJid.User != cleanLid {
		phone := pnJid.User
		logging.Log.Info().Str("instance", instanceID).Str("lid", cleanLid).Str("phone", phone).Msg("LID resolved via store LID→PN mapping")
		r.writeJSON(w, http.StatusOK, map[string]string{"status": "success", "phone": phone})
		return
	}

	logging.Log.Warn().Str("instance", instanceID).Str("lid", cleanLid).Msg("LID not found in any group nor in local store")
	r.writeJSON(w, http.StatusOK, map[string]string{"status": "error", "message": "lid not found"})
}
