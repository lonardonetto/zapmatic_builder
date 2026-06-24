package sender

import (
	"context"
	"fmt"
	"io"
	"net/http"
	"time"

	"google.golang.org/protobuf/proto"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/types"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

func (s *Sender) SendMedia(ctx context.Context, req SendRequest) SendResponse {
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
	if req.Payload.Text == "" {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "media URL required in payload.text"}
	}

	mediaType := req.Type
	if mediaType == "" || mediaType == "media" { mediaType = "image" }

	httpResp, httpErr := http.Get(req.Payload.Text)
	if httpErr != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("download failed: %v", httpErr)}
	}
	defer httpResp.Body.Close()

	mediaBytes, readErr := io.ReadAll(httpResp.Body)
	if readErr != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("read failed: %v", readErr)}
	}

	mimeType := httpResp.Header.Get("Content-Type")
	if mimeType == "" {
		switch mediaType {
		case "image": mimeType = "image/jpeg"
		case "audio": mimeType = "audio/ogg"
		case "video": mimeType = "video/mp4"
		case "document": mimeType = "application/octet-stream"
		default: mimeType = "image/jpeg"
		}
	}

	sendCtx, cancel := context.WithTimeout(ctx, 120*time.Second)
	defer cancel()

	var mediaEnum whatsmeow.MediaType
	switch mediaType {
	case "image": mediaEnum = whatsmeow.MediaImage
	case "audio": mediaEnum = whatsmeow.MediaAudio
	case "video": mediaEnum = whatsmeow.MediaVideo
	case "document": mediaEnum = whatsmeow.MediaDocument
	default: return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("unsupported: %s", mediaType)}
	}

	uploaded, uploadErr := client.Upload(sendCtx, mediaBytes, mediaEnum)
	if uploadErr != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: uploadErr.Error()}
	}

	var msg *waE2E.Message
	switch mediaType {
	case "image":
		msg = &waE2E.Message{ImageMessage: &waE2E.ImageMessage{
			URL: proto.String(uploaded.URL), Mimetype: proto.String(mimeType),
			Caption: proto.String(req.Payload.Caption),
			FileSHA256: uploaded.FileSHA256, FileEncSHA256: uploaded.FileEncSHA256,
			FileLength: proto.Uint64(uploaded.FileLength), MediaKey: uploaded.MediaKey,
		}}
	case "audio":
		msg = &waE2E.Message{AudioMessage: &waE2E.AudioMessage{
			URL: proto.String(uploaded.URL), Mimetype: proto.String(mimeType),
			FileSHA256: uploaded.FileSHA256, FileEncSHA256: uploaded.FileEncSHA256,
			FileLength: proto.Uint64(uploaded.FileLength), MediaKey: uploaded.MediaKey,
		}}
	case "video":
		msg = &waE2E.Message{VideoMessage: &waE2E.VideoMessage{
			URL: proto.String(uploaded.URL), Mimetype: proto.String(mimeType),
			Caption: proto.String(req.Payload.Caption),
			FileSHA256: uploaded.FileSHA256, FileEncSHA256: uploaded.FileEncSHA256,
			FileLength: proto.Uint64(uploaded.FileLength), MediaKey: uploaded.MediaKey,
		}}
	case "document":
		msg = &waE2E.Message{DocumentMessage: &waE2E.DocumentMessage{
			URL: proto.String(uploaded.URL), Mimetype: proto.String(mimeType),
			FileSHA256: uploaded.FileSHA256, FileEncSHA256: uploaded.FileEncSHA256,
			FileLength: proto.Uint64(uploaded.FileLength), MediaKey: uploaded.MediaKey,
			FileName: proto.String(req.Payload.Caption),
		}}
	}

	resp, msgErr := client.SendMessage(sendCtx, jid, msg)
	if msgErr != nil {
		logging.Log.Error().Err(msgErr).Str("instance", req.InstanceID).Msg("SendMedia failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: msgErr.Error()}
	}
	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}
