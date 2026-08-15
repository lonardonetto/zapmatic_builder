package bulk

import (
	"fmt"
	"strings"
)

// ScheduleGroup é um destino de envio em grupo persistido em
// sp_whatsapp_schedule_groups. O offset persistente da campanha (sent+failed)
// caminha por esta lista.
type ScheduleGroup struct {
	ID        int
	TeamID    int
	ScheduleID int
	AccountID string
	GroupJID  string
	Position  int
}

// EnsureGroupJid monta o JID de grupo com sufixo @g.us. Já contendo "@",
// preserva. Vazio retorna vazio.
func EnsureGroupJid(groupID string) string {
	groupID = strings.TrimSpace(groupID)
	if groupID == "" {
		return ""
	}
	if strings.Contains(groupID, "@") {
		return groupID
	}
	return groupID + "@g.us"
}

// ResolveGroupChat resolve o chat de destino de um envio em grupo: sempre um
// JID de grupo (@g.us), nunca @s.whatsapp.net.
func ResolveGroupChat(groupID string) string {
	jid := EnsureGroupJid(groupID)
	if strings.Contains(jid, "@g.us") {
		return jid
	}
	base := jid
	if at := strings.Index(base, "@"); at >= 0 {
		base = base[:at]
	}
	return base + "@g.us"
}

// SupportsGroupSend indica se a conta (login_type) pode enviar em grupo neste
// ciclo: apenas Go/Whatsmeow (login_type=3).
func SupportsGroupSend(loginType int) bool {
	return loginType == 3
}

// GroupSenderAccount devolve o account_id dono do grupo como a conta que deve
// enviar a mensagem — ou seja, o envio em grupo SEMPRE usa a conta membro do
// grupo, nunca o rotador cego da campanha. account_id vazio devolve vazio.
func GroupSenderAccount(accountID string) string {
	return strings.TrimSpace(accountID)
}

// NextGroupByOffset devolve o grupo na posição `offset` (0-based) de uma lista
// ordenada por posição. Offset esgotado ou lista vazia devolve nil.
func NextGroupByOffset(groups []ScheduleGroup, offset int) *ScheduleGroup {
	if offset < 0 || offset >= len(groups) {
		return nil
	}
	return &groups[offset]
}

// ListScheduleGroups carrega os destinos de grupo de uma campanha, ordenados
// por posição. Sem banco, devolve erro.
func ListScheduleGroups(scheduleID int) ([]ScheduleGroup, error) {
	if mysqlDB == nil {
		return nil, fmt.Errorf("MySQL not initialized")
	}
	rows, err := mysqlDB.Query(
		`SELECT id, team_id, schedule_id, account_id, group_jid, position
		 FROM sp_whatsapp_schedule_groups
		 WHERE schedule_id = ? ORDER BY position ASC`, scheduleID,
	)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var groups []ScheduleGroup
	for rows.Next() {
		var g ScheduleGroup
		if err := rows.Scan(&g.ID, &g.TeamID, &g.ScheduleID, &g.AccountID, &g.GroupJID, &g.Position); err != nil {
			continue
		}
		groups = append(groups, g)
	}
	return groups, nil
}
