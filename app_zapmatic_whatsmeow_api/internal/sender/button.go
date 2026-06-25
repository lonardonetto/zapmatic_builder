package sender

import (
	"context"
	"fmt"
	"time"

	"google.golang.org/protobuf/proto"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/types"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

func (s *Sender) SendButtons(ctx context.Context, req InteractiveRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"}
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"}
	}
	jid, err := types.ParseJID(req.ChatID)
	if err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)}
	}

	// Suporta até 10 botões via NativeFlowMessage (InteractiveMessage)
	if len(req.Buttons) > 3 {
		return s.SendInteractiveButtons(ctx, client, jid, req)
	}

	// Até 3 botões: usa ButtonsMessage (legado, mais compatível)
	btns := make([]*waE2E.ButtonsMessage_Button, 0, len(req.Buttons))
	for i, b := range req.Buttons {
		id := b.ID
		if id == "" { id = fmt.Sprintf("btn_%d", i+1) }
		text := b.Text
		if text == "" { text = fmt.Sprintf("Opção %d", i+1) }
		btns = append(btns, &waE2E.ButtonsMessage_Button{
			ButtonID: proto.String(id),
			ButtonText: &waE2E.ButtonsMessage_Button_ButtonText{DisplayText: proto.String(text)},
			Type: waE2E.ButtonsMessage_Button_RESPONSE.Enum(),
		})
	}

	body := req.Body
	if body == "" { body = "Escolha uma opção:" }

	buttonsMsg := &waE2E.ButtonsMessage{
		ContentText: proto.String(body),
		Buttons:     btns,
		HeaderType:  waE2E.ButtonsMessage_EMPTY.Enum(),
	}
	if req.Title != "" {
		buttonsMsg.Header = &waE2E.ButtonsMessage_Text{Text: req.Title}
		buttonsMsg.HeaderType = waE2E.ButtonsMessage_TEXT.Enum()
	}
	if req.Footer != "" {
		buttonsMsg.FooterText = proto.String(req.Footer)
	}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, &waE2E.Message{ButtonsMessage: buttonsMsg})
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Msg("SendButtons failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

// SendInteractiveButtons usa NativeFlowMessage (interactive) para até 10 botões
func (s *Sender) SendInteractiveButtons(ctx context.Context, client *whatsmeow.Client, jid types.JID, req InteractiveRequest) SendResponse {
	// Escolhe entre botões (nativos) ou lista (mais de 5)
	buttons := req.Buttons
	if len(buttons) > 10 { buttons = buttons[:10] }

	// Se tem mais de 5 botões, usa lista como fallback
	if len(buttons) > 5 {
		// Converte botões para lista com 1 seção
		sections := []Section{{
			Title: req.Body,
			Rows:  make([]Row, 0, len(buttons)),
		}}
		for _, b := range buttons {
			sections[0].Rows = append(sections[0].Rows, Row{
				ID:    b.ID,
				Title: b.Text,
			})
		}
		listReq := req
		listReq.Sections = sections
		listReq.ButtonText = "Opções"
		if req.Footer != "" { listReq.Footer = req.Footer }
		return s.SendList(ctx, listReq)
	}

	// Native flow buttons (InteractiveMessage) — suporta até 10
	type nativeBtn struct {
		Name      string `json:"name"`
		BtnParams string `json:"buttonParamsJson"`
	}
	nativeBtns := make([]nativeBtn, 0, len(buttons))
	for _, b := range buttons {
		params := fmt.Sprintf(`{"id":"%s","display_text":"%s"}`, b.ID, b.Text)
		nativeBtns = append(nativeBtns, nativeBtn{
			Name:      "quick_reply",
			BtnParams: params,
		})
	}

	// Prepara o InteractiveMessage com NativeFlowMessage
	nativeFlow := &waE2E.InteractiveMessage_NativeFlowMessage{
		Buttons: make([]*waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton, len(nativeBtns)),
	}
	for i, nb := range nativeBtns {
		nativeFlow.Buttons[i] = &waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton{
			Name:            proto.String(nb.Name),
			ButtonParamsJSON: proto.String(nb.BtnParams),
		}
	}

	intMsg := &waE2E.InteractiveMessage{
		Header: &waE2E.InteractiveMessage_Header{
			Title: proto.String(req.Title),
		},
		Body: &waE2E.InteractiveMessage_Body{
			Text: proto.String(req.Body),
		},
		InteractiveMessage: &waE2E.InteractiveMessage_NativeFlowMessage_{
			NativeFlowMessage: nativeFlow,
		},
	}
	if req.Footer != "" {
		intMsg.Footer = &waE2E.InteractiveMessage_Footer{Text: proto.String(req.Footer)}
	}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, &waE2E.Message{InteractiveMessage: intMsg})
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Msg("SendInteractiveButtons failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

func (s *Sender) SendList(ctx context.Context, req InteractiveRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"}
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"}
	}
	jid, err := types.ParseJID(req.ChatID)
	if err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)}
	}

	sections := make([]*waE2E.ListMessage_Section, 0, len(req.Sections))
	for _, sec := range req.Sections {
		rows := make([]*waE2E.ListMessage_Row, 0, len(sec.Rows))
		for _, row := range sec.Rows {
			r := &waE2E.ListMessage_Row{
				Title: proto.String(row.Title),
				RowID: proto.String(row.ID),
			}
			if row.Description != "" {
				r.Description = proto.String(row.Description)
			}
			rows = append(rows, r)
		}
		sections = append(sections, &waE2E.ListMessage_Section{
			Title: proto.String(sec.Title),
			Rows:  rows,
		})
	}

	btnText := req.ButtonText
	if btnText == "" {
		btnText = "Ver opções"
	}

	listMsg := &waE2E.ListMessage{
		ButtonText:  proto.String(btnText),
		Sections:    sections,
		Description: proto.String(req.Body),
		ListType:    waE2E.ListMessage_SINGLE_SELECT.Enum(),
	}
	if req.Title != "" {
		listMsg.Title = proto.String(req.Title)
	}
	if req.Footer != "" {
		listMsg.FooterText = proto.String(req.Footer)
	}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, &waE2E.Message{ListMessage: listMsg})
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Msg("SendList failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

func (s *Sender) SendPoll(ctx context.Context, req InteractiveRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"}
	}
	client := inst.Client()
	if client == nil || !client.IsConnected() {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"}
	}
	jid, err := types.ParseJID(req.ChatID)
	if err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)}
	}

	opts := make([]*waE2E.PollCreationMessage_Option, 0, len(req.Options))
	for _, opt := range req.Options {
		opts = append(opts, &waE2E.PollCreationMessage_Option{OptionName: proto.String(opt.Name)})
	}

	pollMsg := &waE2E.PollCreationMessage{
		Name:    proto.String(req.Body),
		Options: opts,
		PollType: waE2E.PollType_POLL.Enum(),
	}
	if len(req.Options) > 1 {
		pollMsg.SelectableOptionsCount = proto.Uint32(uint32(len(req.Options)))
	}
	if req.Title != "" {
		pollMsg.Name = proto.String(req.Title)
		if req.Body != "" {
			pollMsg.Name = proto.String(req.Body)
		}
	}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, &waE2E.Message{PollCreationMessage: pollMsg})
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Msg("SendPoll failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}
