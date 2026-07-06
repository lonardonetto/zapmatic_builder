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
	if inst == nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"} }
	client := inst.Client()
	if client == nil || !client.IsConnected() { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"} }
	jid, err := types.ParseJID(req.ChatID)
	if err != nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)} }
	if req.Body == "" { req.Body = "Escolha:" }
	if len(req.Buttons) > 3 { req.Buttons = req.Buttons[:3] }

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()

	btns := make([]*waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton, 0, len(req.Buttons))
	for _, b := range req.Buttons {
		btns = append(btns, &waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton{
			Name: proto.String("quick_reply"),
			ButtonParamsJSON: proto.String(fmt.Sprintf(`{"display_text":"%s","id":"%s","disabled":false}`, b.Text, b.ID)),
		})
	}

	interactive := &waE2E.InteractiveMessage{
		Header: &waE2E.InteractiveMessage_Header{HasMediaAttachment: proto.Bool(false)},
		Body:   &waE2E.InteractiveMessage_Body{Text: proto.String(req.Body)},
		InteractiveMessage: &waE2E.InteractiveMessage_NativeFlowMessage_{
			NativeFlowMessage: &waE2E.InteractiveMessage_NativeFlowMessage{
				Buttons: btns, MessageVersion: proto.Int32(1),
			},
		},
		ContextInfo: &waE2E.ContextInfo{Expiration: proto.Uint32(0)},
	}
	if req.Title != "" { interactive.Header.Title = proto.String(req.Title) }
	if req.Footer != "" { interactive.Footer = &waE2E.InteractiveMessage_Footer{Text: proto.String(req.Footer)} }

	msgSecret := make([]byte, 32)
	rand.Read(msgSecret)

	msg := &waE2E.Message{
		InteractiveMessage: interactive,
		MessageContextInfo: &waE2E.MessageContextInfo{
			MessageSecret: msgSecret,
		},
	}

	// EXACT Baileys biz node (messages-send.js line 1338-1367)
	// v:'9', name:'mixed', quality_control, actual_actors, host_storage, privacy_mode_ts
	bizNode := []waBinary.Node{{
		Tag: "biz",
		Attrs: waBinary.Attrs{
			"actual_actors":  "2",
			"host_storage":   "2",
			"privacy_mode_ts": fmt.Sprintf("%d", time.Now().Unix()),
		},
		Content: []waBinary.Node{
			{
				Tag:   "interactive",
				Attrs: waBinary.Attrs{"type": "native_flow", "v": "1"},
				Content: []waBinary.Node{{
					Tag:   "native_flow",
					Attrs: waBinary.Attrs{"v": "9", "name": "mixed"},
				}},
			},
			{
				Tag:   "quality_control",
				Attrs: waBinary.Attrs{"source_type": "third_party"},
			},
		},
	}}
	extra := whatsmeow.SendRequestExtra{AdditionalNodes: &bizNode}

	logging.Log.Info().Str("instance", req.InstanceID).Msg("Interactive + Baileys exact biz node")

	resp, err := client.SendMessage(sendCtx, jid, msg, extra)
	if err != nil {
		logging.Log.Warn().Err(err).Str("instance", req.InstanceID).Msg("Failed, text fallback")
		var tf string
		if req.Title != "" { tf = fmt.Sprintf("*%s*\n\n%s", req.Title, req.Body) } else { tf = req.Body }
		for i, b := range req.Buttons { tf += fmt.Sprintf("\n*%d.* %s", i+1, b.Text) }
		if req.Footer != "" { tf += "\n\n_" + req.Footer + "_" }
		client.SendMessage(sendCtx, jid, &waE2E.Message{ExtendedTextMessage: &waE2E.ExtendedTextMessage{Text: proto.String(tf)}})
		return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: "fallback"}
	}
	logging.Log.Info().Str("instance", req.InstanceID).Str("id", resp.ID).Msg("Interactive sent (Baileys exact)")
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

func (s *Sender) SendList(ctx context.Context, req InteractiveRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"} }
	client := inst.Client()
	if client == nil || !client.IsConnected() { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"} }
	jid, err := types.ParseJID(req.ChatID)
	if err != nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)} }
	
	sections := make([]*waE2E.ListMessage_Section, 0)
	for _, sec := range req.Sections {
		rows := make([]*waE2E.ListMessage_Row, 0)
		for _, row := range sec.Rows { 
			rows = append(rows, &waE2E.ListMessage_Row{Title: proto.String(row.Title), RowID: proto.String(row.ID)}) 
		}
		sections = append(sections, &waE2E.ListMessage_Section{Title: proto.String(sec.Title), Rows: rows})
	}
	
	btnText := req.ButtonText; if btnText == "" { btnText = "Ver opções" }
	listMsg := &waE2E.ListMessage{
		ButtonText: proto.String(btnText), 
		Sections: sections, 
		Description: proto.String(req.Body), 
		ListType: waE2E.ListMessage_SINGLE_SELECT.Enum(),
	}
	if req.Title != "" { listMsg.Title = proto.String(req.Title) }
	if req.Footer != "" { listMsg.FooterText = proto.String(req.Footer) }
	
	// Apply Baileys ViewOnceMessageV2Extension patch for lists
	msg := &waE2E.Message{
		ViewOnceMessageV2Extension: &waE2E.FutureProofMessage{
			Message: &waE2E.Message{
				MessageContextInfo: &waE2E.MessageContextInfo{
					DeviceListMetadataVersion: proto.Int32(2),
					DeviceListMetadata:        &waE2E.DeviceListMetadata{},
				},
				ListMessage: listMsg,
			},
		},
	}
	
	// EXACT Baileys biz node for lists (messages-send.js)
	bizNode := []waBinary.Node{{
		Tag: "biz",
		Attrs: waBinary.Attrs{
			"actual_actors":   "2",
			"host_storage":    "2",
			"privacy_mode_ts": fmt.Sprintf("%d", time.Now().Unix()),
		},
		Content: []waBinary.Node{
			{
				Tag: "list",
				Attrs: waBinary.Attrs{
					"v":    "2",
					"type": "product_list",
				},
			},
			{
				Tag:   "quality_control",
				Attrs: waBinary.Attrs{"source_type": "third_party"},
			},
		},
	}}
	
	extra := whatsmeow.SendRequestExtra{AdditionalNodes: &bizNode}

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()
	
	logging.Log.Info().Str("instance", req.InstanceID).Msg("ListMessage sent using Baileys ViewOnce patch + biz node")
	
	resp, err := client.SendMessage(sendCtx, jid, msg, extra)
	if err != nil {
		logging.Log.Warn().Err(err).Str("instance", req.InstanceID).Msg("ListMessage failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
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
