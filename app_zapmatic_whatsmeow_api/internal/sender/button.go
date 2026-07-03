package sender

import (
	"context"
	"crypto/rand"
	"fmt"
	"time"

	"google.golang.org/protobuf/proto"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/types"
	waBinary "go.mau.fi/whatsmeow/binary"

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
	if req.Body == "" { req.Body = "Escolha:" }
	if len(req.Buttons) > 10 { req.Buttons = req.Buttons[:10] }

	// Text fallback (preparado para todas as contas)
	var text string
	if req.Title != "" { text = fmt.Sprintf("*%s*\n\n%s", req.Title, req.Body) } else { text = req.Body }
	for i, b := range req.Buttons { text += fmt.Sprintf("\n*%d.* %s", i+1, b.Text) }
	if req.Footer != "" { text += "\n\n_" + req.Footer + "_" }

	// Tentar InteractiveMessage (funciona para contas pessoais, erro 405 em business)
	btns := make([]*waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton, 0, len(req.Buttons))
	for _, b := range req.Buttons {
		btns = append(btns, &waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton{
			Name:             proto.String("quick_reply"),
			ButtonParamsJSON: proto.String(fmt.Sprintf(`{"display_text":"%s","id":"%s","disabled":false}`, b.Text, b.ID)),
		})
	}
	interactive := &waE2E.InteractiveMessage{
		Body: &waE2E.InteractiveMessage_Body{Text: proto.String(req.Body)},
		InteractiveMessage: &waE2E.InteractiveMessage_NativeFlowMessage_{
			NativeFlowMessage: &waE2E.InteractiveMessage_NativeFlowMessage{Buttons: btns, MessageVersion: proto.Int32(1)},
		},
	}
	if req.Title != "" { interactive.Header = &waE2E.InteractiveMessage_Header{Title: proto.String(req.Title), HasMediaAttachment: proto.Bool(false)} }
	if req.Footer != "" { interactive.Footer = &waE2E.InteractiveMessage_Footer{Text: proto.String(req.Footer)} }
	interactive.ContextInfo = &waE2E.ContextInfo{Expiration: proto.Uint32(0)}

	msgSecret := make([]byte, 32)
	rand.Read(msgSecret)
	msg := &waE2E.Message{
		InteractiveMessage: interactive,
		MessageContextInfo: &waE2E.MessageContextInfo{
			MessageSecret: msgSecret,
		},
	}
	bizNode := []waBinary.Node{{Tag: "biz", Content: []waBinary.Node{{Tag: "interactive", Attrs: waBinary.Attrs{"type": "native_flow", "v": "1"}, Content: []waBinary.Node{{Tag: "native_flow", Attrs: waBinary.Attrs{"v": "2", "name": "quick_reply"}}}}}}}
	extra := whatsmeow.SendRequestExtra{AdditionalNodes: &bizNode}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, err := client.SendMessage(sendCtx, jid, msg, extra)
	if err != nil {
		logging.Log.Warn().Err(err).Str("instance", req.InstanceID).Msg("Interactive 405, text fallback")
		resp2, _ := client.SendMessage(sendCtx, jid, &waE2E.Message{ExtendedTextMessage: &waE2E.ExtendedTextMessage{Text: proto.String(text)}})
		if resp2.ID == "" { return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()} }
		return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp2.ID}
	}
	logging.Log.Info().Str("instance", req.InstanceID).Str("id", resp.ID).Msg("Interactive buttons sent")
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

func (s *Sender) SendList(ctx context.Context, req InteractiveRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"} }
	client := inst.Client()
	if client == nil || !client.IsConnected() { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"} }
	jid, _ := types.ParseJID(req.ChatID)
	sections := make([]*waE2E.ListMessage_Section, 0)
	for _, sec := range req.Sections {
		rows := make([]*waE2E.ListMessage_Row, 0)
		for _, row := range sec.Rows {
			rows = append(rows, &waE2E.ListMessage_Row{Title: proto.String(row.Title), RowID: proto.String(row.ID)})
		}
		sections = append(sections, &waE2E.ListMessage_Section{Title: proto.String(sec.Title), Rows: rows})
	}
	btnText := req.ButtonText; if btnText == "" { btnText = "Ver opções" }
	listMsg := &waE2E.ListMessage{ButtonText: proto.String(btnText), Sections: sections, Description: proto.String(req.Body), ListType: waE2E.ListMessage_SINGLE_SELECT.Enum()}
	if req.Title != "" { listMsg.Title = proto.String(req.Title) }
	if req.Footer != "" { listMsg.FooterText = proto.String(req.Footer) }
	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, _ := client.SendMessage(sendCtx, jid, &waE2E.Message{ListMessage: listMsg})
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

func (s *Sender) SendPoll(ctx context.Context, req InteractiveRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"} }
	client := inst.Client()
	if client == nil || !client.IsConnected() { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"} }
	jid, _ := types.ParseJID(req.ChatID)
	opts := make([]*waE2E.PollCreationMessage_Option, 0)
	for _, opt := range req.Options { opts = append(opts, &waE2E.PollCreationMessage_Option{OptionName: proto.String(opt.Name)}) }
	pollName := req.Body; if req.Title != "" { pollName = req.Title }; if pollName == "" { pollName = "Enquete" }
	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	resp, _ := client.SendMessage(sendCtx, jid, &waE2E.Message{PollCreationMessage: &waE2E.PollCreationMessage{Name: proto.String(pollName), Options: opts, PollType: waE2E.PollType_POLL.Enum(), SelectableOptionsCount: proto.Uint32(uint32(len(opts)))}})
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}
