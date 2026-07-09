package sender

import (
	"context"
	"crypto/rand"
	"fmt"
	"io"
	"net/http"
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

	sendCtx, cancel := context.WithTimeout(ctx, 60*time.Second)
	defer cancel()

	// WhatsApp Cloud API native interactive only supports 3 buttons max
	// For > 3 buttons, use text fallback (like Baileys) so all options are visible
	if len(req.Buttons) > 3 {
		logging.Log.Info().Str("instance", req.InstanceID).Int("button_count", len(req.Buttons)).Msg(">3 buttons, using text fallback")
		var tf string
		if req.Title != "" { tf = fmt.Sprintf("*%s*\n\n%s", req.Title, req.Body) } else { tf = req.Body }
		for i, b := range req.Buttons { tf += fmt.Sprintf("\n*%d.* %s", i+1, b.Text) }
		if req.Footer != "" { tf += "\n\n_" + req.Footer + "_" }
		tf += "\n\n_Responda com o número ou nome da opção._"
		resp, ferr := client.SendMessage(sendCtx, jid, &waE2E.Message{ExtendedTextMessage: &waE2E.ExtendedTextMessage{Text: proto.String(tf)}})
		if ferr != nil {
			logging.Log.Warn().Err(ferr).Str("instance", req.InstanceID).Msg("Text fallback failed")
			return SendResponse{Status: "error", Provider: "whatsmeow", Error: ferr.Error()}
		}
		return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
	}

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

func (s *Sender) SendCarousel(ctx context.Context, req InteractiveRequest) SendResponse {
	inst := s.sm.GetInstance(req.InstanceID)
	if inst == nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "instance not found"} }
	client := inst.Client()
	if client == nil || !client.IsConnected() { return SendResponse{Status: "error", Provider: "whatsmeow", Error: "not connected"} }
	jid, err := types.ParseJID(req.ChatID)
	if err != nil { return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("invalid JID: %v", err)} }

	slides := make([]*waE2E.InteractiveMessage, 0, len(req.Cards))
	
	httpClient := &http.Client{Timeout: 30 * time.Second}

	for _, card := range req.Cards {
		btns := make([]*waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton, 0, len(card.Buttons))
		for _, b := range card.Buttons {
			btns = append(btns, &waE2E.InteractiveMessage_NativeFlowMessage_NativeFlowButton{
				Name: proto.String("quick_reply"),
				ButtonParamsJSON: proto.String(fmt.Sprintf(`{"display_text":"%s","id":"%s"}`, b.Text, b.ID)),
			})
		}
		
		header := &waE2E.InteractiveMessage_Header{
			Title: proto.String(card.Title),
			HasMediaAttachment: proto.Bool(false),
		}
		
		if card.Image != nil && card.Image.URL != "" {
			httpReq, _ := http.NewRequestWithContext(ctx, "GET", card.Image.URL, nil)
			httpReq.Header.Set("User-Agent", "Zapmatic-Whatsmeow/1.0")
			if httpResp, err := httpClient.Do(httpReq); err == nil {
				if mediaBytes, err := io.ReadAll(httpResp.Body); err == nil && len(mediaBytes) > 0 {
					mimeType := httpResp.Header.Get("Content-Type")
					if mimeType == "" { mimeType = "image/jpeg" }
					if uploaded, err := client.Upload(ctx, mediaBytes, whatsmeow.MediaImage); err == nil {
						header.HasMediaAttachment = proto.Bool(true)
						header.Media = &waE2E.InteractiveMessage_Header_ImageMessage{
							ImageMessage: &waE2E.ImageMessage{
								URL:           proto.String(uploaded.URL),
								DirectPath:    proto.String(uploaded.DirectPath),
								Mimetype:      proto.String(mimeType),
								FileSHA256:    uploaded.FileSHA256,
								FileEncSHA256: uploaded.FileEncSHA256,
								FileLength:    proto.Uint64(uploaded.FileLength),
								MediaKey:      uploaded.MediaKey,
							},
						}
					}
				}
				httpResp.Body.Close()
			}
		}
		
		slide := &waE2E.InteractiveMessage{
			Header: header,
			Body: &waE2E.InteractiveMessage_Body{Text: proto.String(card.Body)},
			InteractiveMessage: &waE2E.InteractiveMessage_NativeFlowMessage_{
				NativeFlowMessage: &waE2E.InteractiveMessage_NativeFlowMessage{
					Buttons: btns,
				},
			},
		}
		if card.Footer != "" { slide.Footer = &waE2E.InteractiveMessage_Footer{Text: proto.String(card.Footer)} }
		slides = append(slides, slide)
	}

	interactive := &waE2E.InteractiveMessage{
		Header: &waE2E.InteractiveMessage_Header{
			HasMediaAttachment: proto.Bool(false),
		},
		InteractiveMessage: &waE2E.InteractiveMessage_CarouselMessage_{
			CarouselMessage: &waE2E.InteractiveMessage_CarouselMessage{
				Cards: slides,
				MessageVersion: proto.Int32(1),
			},
		},
	}
	if req.Body != "" { interactive.Body = &waE2E.InteractiveMessage_Body{Text: proto.String(req.Body)} }
	if req.Footer != "" { interactive.Footer = &waE2E.InteractiveMessage_Footer{Text: proto.String(req.Footer)} }

	bizNode := []waBinary.Node{{
		Tag: "biz",
		Attrs: waBinary.Attrs{
			"actual_actors":   "2",
			"host_storage":    "2",
			"privacy_mode_ts": fmt.Sprintf("%d", time.Now().Unix()),
		},
	}}
	extra := whatsmeow.SendRequestExtra{AdditionalNodes: &bizNode}

	msgSecret := make([]byte, 32)
	rand.Read(msgSecret)

	msg := &waE2E.Message{
		InteractiveMessage: interactive,
		MessageContextInfo: &waE2E.MessageContextInfo{
			MessageSecret: msgSecret,
		},
	}
	
	sendCtx, cancel := context.WithTimeout(ctx, 120*time.Second)
	defer cancel()

	logging.Log.Info().Str("instance", req.InstanceID).Msg("Sending Carousel with Baileys biz node")
	
	resp, err := client.SendMessage(sendCtx, jid, msg, extra)
	if err != nil {
		logging.Log.Warn().Err(err).Str("instance", req.InstanceID).Msg("Carousel failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: err.Error()}
	}
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}
