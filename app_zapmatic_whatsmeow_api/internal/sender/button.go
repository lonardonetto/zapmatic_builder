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

	if req.Body == "" { req.Body = "Escolha uma opção:" }
	if len(req.Buttons) > 3 { req.Buttons = req.Buttons[:3] }

	// Build NativeFlow buttons with correct params JSON
	btns := make([]*waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton, 0, len(req.Buttons))
	for i, b := range req.Buttons {
		id := b.ID; if id == "" { id = fmt.Sprintf("btn_%d", i+1) }
		text := b.Text; if text == "" { text = fmt.Sprintf("Opção %d", i+1) }

		// WhatsApp espera buttonParamsJSON no formato:
		// {"display_text":"Texto","id":"identificador"}
		paramsJSON := fmt.Sprintf(`{"display_text":"%s","id":"%s"}`, escapeJSON(text), escapeJSON(id))

		btns = append(btns, &waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton{
			Name:             proto.String(fmt.Sprintf("cta_copy_%d", i)),
			ButtonParamsJSON: proto.String(paramsJSON),
		})
	}

	// Build InteractiveMessage
	interactive := &waE2E.InteractiveMessage{
		Body:   &waE2E.InteractiveMessage_Body{Text: proto.String(req.Body)},
		Footer: &waE2E.InteractiveMessage_Footer{Text: proto.String(req.Footer)},
		InteractiveMessage: &waE2E.InteractiveMessage_NativeFlowMessage_{
			NativeFlowMessage: &waE2E.InteractiveMessage_NativeFlowMessage{
				Buttons:        btns,
				MessageVersion: proto.Int32(1),
			},
		},
	}

	// Header com título
	if req.Title != "" {
		interactive.Header = &waE2E.InteractiveMessage_Header{
			Title:              proto.String(req.Title),
			HasMediaAttachment: proto.Bool(false),
		}
	}

	msg := &waE2E.Message{InteractiveMessage: interactive}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, msg)
	if err != nil {
		logging.Log.Error().Err(err).Str("instance", req.InstanceID).Msg("InteractiveMessage failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	logging.Log.Info().Str("instance", req.InstanceID).Str("to", req.ChatID).Str("id", resp.ID).Msg("InteractiveButtons sent")
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

func escapeJSON(s string) string {
	result := ""
	for _, ch := range s {
		switch ch {
		case '"': result += `\"`
		case '\\': result += `\\`
		case '\n': result += `\n`
		case '\r': result += `\r`
		case '\t': result += `\t`
		default: result += string(ch)
		}
	}
	return result
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
		ButtonText:  proto.String(btnText), Sections: sections,
		Description: proto.String(req.Body),
		ListType:    waE2E.ListMessage_SINGLE_SELECT.Enum(),
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
	logging.Log.Info().Str("instance", req.InstanceID).Str("to", req.ChatID).Str("id", resp.ID).Msg("List sent")
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
	logging.Log.Info().Str("instance", req.InstanceID).Str("to", req.ChatID).Str("id", resp.ID).Msg("Poll sent")
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}
