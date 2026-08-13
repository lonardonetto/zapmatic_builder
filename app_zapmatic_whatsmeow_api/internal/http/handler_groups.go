package http

import (
	"context"
	"net/http"
	"strconv"
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
	Name  string `json:"name,omitempty"`
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

// defaultGroupPageLimit é o tamanho de página usado quando `limit` é ausente
// ou inválido (<= 0).
const defaultGroupPageLimit = 50

// parsePositiveInt converte um parâmetro de query em inteiro positivo; valor
// ausente ou inválido retorna 0 (o paginador trata 0 como "não informado").
func parsePositiveInt(raw string) int {
	n, err := strconv.Atoi(raw)
	if err != nil || n <= 0 {
		return 0
	}
	return n
}

// selectGroups decide entre o comportamento legado (sem `page` → retorna
// todos) e a paginação (com `page` → fatia + total). `page` explícito mas
// inválido (<=0) devolve vazio, sem erro. Devolve (grupos, total, page).
func selectGroups(groups []GroupInfo, pageRaw string, limit int) ([]GroupInfo, int, int) {
	total := len(groups)
	if pageRaw == "" {
		return groups, total, 0
	}
	page := parsePositiveInt(pageRaw)
	pageGroups, total := paginateGroups(groups, page, limit)
	return pageGroups, total, page
}

// paginateGroups devolve a fatia correspondente a `page` (1-based) com
// `limit` itens, além do total. `page` <= 0 ou além do fim devolve fatia
// vazia, nunca erro. `limit` <= 0 cai no defaultGroupPageLimit.
func paginateGroups(groups []GroupInfo, page, limit int) ([]GroupInfo, int) {
	total := len(groups)
	if limit <= 0 {
		limit = defaultGroupPageLimit
	}
	if page < 1 {
		return []GroupInfo{}, total
	}
	offset := (page - 1) * limit
	if offset >= total {
		return []GroupInfo{}, total
	}
	end := offset + limit
	if end > total {
		end = total
	}
	return groups[offset:end], total
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
				name := ""
				if client.Store != nil && client.Store.Contacts != nil {
					// O store de contatos indexa por JID de telefone (5562...@s.whatsapp.net).
					// Tenta PhoneNumber primeiro; cai para JID quando PhoneNumber é vazio.
					contactJID := p.PhoneNumber
					if contactJID.IsEmpty() {
						contactJID = p.JID
					}
					if !contactJID.IsEmpty() {
						contact, err := client.Store.Contacts.GetContact(ctx, contactJID)
						if err == nil && contact.Found {
							name = contact.PushName
							if name == "" {
								name = contact.FullName
							}
						}
					}
				}
				participants = append(participants, GroupParticipantInfo{
					ID:    phone,
					LID:   p.LID.User,
					Admin: p.IsAdmin || p.IsSuperAdmin,
					Name:  name,
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

	pageRaw := req.URL.Query().Get("page")
	limit := parsePositiveInt(req.URL.Query().Get("limit"))
	pageGroups, total, page := selectGroups(groups, pageRaw, limit)

	logging.Log.Info().Str("instance", instanceID).Int("groups", len(groups)).Str("page", pageRaw).Int("limit", limit).Int("total", total).Msg("Groups listed")

	r.writeJSON(w, http.StatusOK, map[string]interface{}{
		"status": "success",
		"groups": pageGroups,
		"total":  total,
		"page":   page,
		"limit":  limit,
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
