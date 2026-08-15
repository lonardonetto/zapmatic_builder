package bulk

// @spec:AC-025 @spec:AC-026 @spec:AC-027 @spec:AC-029 @spec:AC-030 @spec:AC-031 @spec:AC-032
// — destinos de grupo no motor Go: sufixo @g.us, resolução de chat de grupo,
// restrição a contas Go (login_type=3), delay (CalculateDelay), offset
// persistente, envio pela conta dona do grupo e destino independente do tipo.
import "testing"

func TestEnsureGroupJidSuffix(t *testing.T) {
	if EnsureGroupJid("123456789") != "123456789@g.us" {
		bulkTapNotOk(t, "TestEnsureGroupJidSuffix", "AC-025", "sem sufixo deveria receber @g.us")
		return
	}
	if EnsureGroupJid("123456789@g.us") != "123456789@g.us" {
		bulkTapNotOk(t, "TestEnsureGroupJidSuffix", "AC-025", "com sufixo deveria ser preservado")
		return
	}
	if EnsureGroupJid("") != "" {
		bulkTapNotOk(t, "TestEnsureGroupJidSuffix", "AC-025", "vazio deveria seguir vazio")
		return
	}
	bulkTapOk(t, "TestEnsureGroupJidSuffix", "AC-025")
}

func TestResolveGroupChatNeverIndividual(t *testing.T) {
	chat := ResolveGroupChat("123456789@g.us")
	if chat != "123456789@g.us" {
		bulkTapNotOk(t, "TestResolveGroupChatNeverIndividual", "AC-026", "chat de grupo alterado")
		return
	}
	forced := ResolveGroupChat("5511999999999@s.whatsapp.net")
	if forced != "5511999999999@g.us" {
		bulkTapNotOk(t, "TestResolveGroupChatNeverIndividual", "AC-026", "JID de número deveria virar grupo")
		return
	}
	bulkTapOk(t, "TestResolveGroupChatNeverIndividual", "AC-026")
}

func TestSupportsGroupSendOnlyGo(t *testing.T) {
	if !SupportsGroupSend(3) {
		bulkTapNotOk(t, "TestSupportsGroupSendOnlyGo", "AC-027", "login_type=3 deveria ser suportado")
		return
	}
	if SupportsGroupSend(1) || SupportsGroupSend(2) || SupportsGroupSend(0) {
		bulkTapNotOk(t, "TestSupportsGroupSendOnlyGo", "AC-027", "login_type != 3 não deveria ser suportado")
		return
	}
	bulkTapOk(t, "TestSupportsGroupSendOnlyGo", "AC-027")
}

func TestNextGroupByOffset(t *testing.T) {
	groups := []ScheduleGroup{
		{ID: 1, GroupJID: "a@g.us", Position: 0},
		{ID: 2, GroupJID: "b@g.us", Position: 1},
		{ID: 3, GroupJID: "c@g.us", Position: 2},
	}

	g := NextGroupByOffset(groups, 1)
	if g == nil || g.ID != 2 {
		bulkTapNotOk(t, "TestNextGroupByOffset", "AC-030", "offset 1 deveria retornar o segundo grupo")
		return
	}

	if NextGroupByOffset(groups, 3) != nil {
		bulkTapNotOk(t, "TestNextGroupByOffset", "AC-030", "offset esgotado deveria retornar nil")
		return
	}

	if NextGroupByOffset(groups, -1) != nil {
		bulkTapNotOk(t, "TestNextGroupByOffset", "AC-030", "offset negativo deveria retornar nil")
		return
	}
	bulkTapOk(t, "TestNextGroupByOffset", "AC-030")
}

func TestGroupTargetDelayUsesCalculateDelay(t *testing.T) {
	d := CalculateDelay(10, 30)
	if d < 10 || d > 30 {
		bulkTapNotOk(t, "TestGroupTargetDelayUsesCalculateDelay", "AC-029", "delay fora do intervalo")
		return
	}
	fixed := CalculateDelay(15, 15)
	if fixed != 15 {
		bulkTapNotOk(t, "TestGroupTargetDelayUsesCalculateDelay", "AC-029", "delay fixo inesperado")
		return
	}
	bulkTapOk(t, "TestGroupTargetDelayUsesCalculateDelay", "AC-029")
}

func TestGroupSenderAccountIsOwner(t *testing.T) {
	if GroupSenderAccount("6a70acd24ed9f") != "6a70acd24ed9f" {
		bulkTapNotOk(t, "TestGroupSenderAccountIsOwner", "AC-031", "account_id do dono deveria ser preservado")
		return
	}
	if GroupSenderAccount("  ") != "" {
		bulkTapNotOk(t, "TestGroupSenderAccountIsOwner", "AC-031", "account_id vazio deveria seguir vazio")
		return
	}
	bulkTapOk(t, "TestGroupSenderAccountIsOwner", "AC-031")
}

func TestIsGroupCampaignByTargetType(t *testing.T) {
	group := &Campaign{TargetType: "groups", Type: CampaignText}
	if !group.IsGroupCampaign() {
		bulkTapNotOk(t, "TestIsGroupCampaignByTargetType", "AC-032", "target_type=groups deveria ser grupo mesmo com type=1 (texto)")
		return
	}

	groupButton := &Campaign{TargetType: "groups", Type: CampaignButton}
	if !groupButton.IsGroupCampaign() {
		bulkTapNotOk(t, "TestIsGroupCampaignByTargetType", "AC-032", "target_type=groups deveria ser grupo com type=2 (botão)")
		return
	}

	contacts := &Campaign{TargetType: "contacts", Type: CampaignText}
	if contacts.IsGroupCampaign() {
		bulkTapNotOk(t, "TestIsGroupCampaignByTargetType", "AC-032", "target_type=contacts não deveria ser grupo")
		return
	}

	legacy := &Campaign{Type: CampaignText}
	if legacy.IsGroupCampaign() {
		bulkTapNotOk(t, "TestIsGroupCampaignByTargetType", "AC-032", "target_type vazio (legado) deveria ser contatos")
		return
	}
	bulkTapOk(t, "TestIsGroupCampaignByTargetType", "AC-032")
}
