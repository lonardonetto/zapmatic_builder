package sender

import (
	"context"
	"fmt"
	"time"

	"google.golang.org/protobuf/proto"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/types"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

// SendButtons sends interactive buttons as a ListMessage dropdown menu.
// InteractiveMessage/ButtonsMessage are NOT supported by whatsmeow (server
// returns 479 or silently discards). ListMessage is the only interactive
// format that reliably arrives on WhatsApp Web, Android and iOS.
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

	body := req.Body; if body == "" { body = "Escolha:" }
	if len(req.Buttons) > 10 { req.Buttons = req.Buttons[:10] }

	// Converte botões em ListMessage rows
	rows := make([]*waE2E.ListMessage_Row, 0, len(req.Buttons))
	for i, b := range req.Buttons {
		id := b.ID; if id == "" { id = fmt.Sprintf("btn_%d", i+1) }
		text := b.Text; if text == "" { text = fmt.Sprintf("Opção %d", i+1) }
		rows = append(rows, &waE2E.ListMessage_Row{
			RowID: proto.String(id),
			Title: proto.String(text),
		})
	}

	btnText := "Opções"
	if req.ButtonText != "" { btnText = req.ButtonText }

	listMsg := &waE2E.ListMessage{
		ButtonText:  proto.String(btnText),
		Description: proto.String(body),
		Sections:    []*waE2E.ListMessage_Section{{Title: proto.String(req.Title), Rows: rows}},
		ListType:    waE2E.ListMessage_SINGLE_SELECT.Enum(),
	}
	if req.Title != "" { listMsg.Title = proto.String(req.Title) }
	if req.Footer != "" { listMsg.FooterText = proto.String(req.Footer) }

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, &waE2E.Message{ListMessage: listMsg})
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Msg("SendButtons (ListMessage) failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	logging.Log.Info().Str("instance", req.InstanceID).Str("to", req.ChatID).Str("id", resp.ID).Msg("Buttons sent (ListMessage format)")
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
			r := &waE2E.ListMessage_Row{Title: proto.String(row.Title), RowID: proto.String(row.ID)}
			if row.Description != "" { r.Description = proto.String(row.Description) }
			rows = append(rows, r)
		}
		sections = append(sections, &waE2E.ListMessage_Section{Title: proto.String(sec.Title), Rows: rows})
	}

	btnText := req.ButtonText; if btnText == "" { btnText = "Ver opções" }
	listMsg := &waE2E.ListMessage{
		ButtonText: proto.String(btnText), Sections: sections,
		Description: proto.String(req.Body), ListType: waE2E.ListMessage_SINGLE_SELECT.Enum(),
	}
	if req.Title != "" { listMsg.Title = proto.String(req.Title) }
	if req.Footer != "" { listMsg.FooterText = proto.String(req.Footer) }

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
	pollName := req.Body; if req.Title != "" { pollName = req.Title }; if pollName == "" { pollName = "Enquete" }
	pollMsg := &waE2E.PollCreationMessage{
		Name: proto.String(pollName), Options: opts, PollType: waE2E.PollType_POLL.Enum(),
		SelectableOptionsCount: proto.Uint32(uint32(len(opts))),
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
