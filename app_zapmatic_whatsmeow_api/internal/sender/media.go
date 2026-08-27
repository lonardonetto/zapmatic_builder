package sender

import (
	"bytes"
	"context"
	"encoding/binary"
	"fmt"
	"io"
	"math"
	"net/http"
	"time"

	"google.golang.org/protobuf/proto"
	"go.mau.fi/whatsmeow"
	"go.mau.fi/whatsmeow/proto/waE2E"
	"go.mau.fi/whatsmeow/types"

	"github.com/lonardonetto/zapmatic-whatsmeow/internal/logging"
)

var imageMagic = map[string][]byte{
	"image/jpeg": {0xFF, 0xD8, 0xFF},
	"image/png":  {0x89, 0x50, 0x4E, 0x47},
	"image/gif":  {0x47, 0x49, 0x46, 0x38},
	"image/webp": {0x52, 0x49, 0x46, 0x46},
}

func checkImageMagic(data []byte, mimeType string) bool {
	if magic, ok := imageMagic[mimeType]; ok {
		if len(data) < len(magic) {
			return false
		}
		return bytes.Equal(data[:len(magic)], magic)
	}
	return true
}

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

	mediaURL := req.Payload.Text
	if mediaURL == "" {
		mediaURL = req.Payload.URL
	}
	caption := req.Payload.Caption

	if mediaURL == "" {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "media URL required in payload.text or payload.url"}
	}

	mediaType := req.Type
	if mediaType == "" || mediaType == "media" {
		mediaType = "image"
	}

	// ========== DOWNLOAD ==========
	logging.Log.Info().
		Str("instance", req.InstanceID).
		Str("url", mediaURL).
		Str("type", mediaType).
		Msg("Downloading media")

	httpClient := &http.Client{
		Timeout: 30 * time.Second,
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			if len(via) >= 3 {
				return fmt.Errorf("too many redirects")
			}
			return nil
		},
	}

	httpReq, err := http.NewRequestWithContext(ctx, "GET", mediaURL, nil)
	if err != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("request failed: %v", err)}
	}
	httpReq.Header.Set("User-Agent", "Zapmatic-Whatsmeow/1.0")
	httpReq.Header.Set("Accept", "image/webp,image/png,image/jpeg,image/gif,*/*")

	httpResp, httpErr := httpClient.Do(httpReq)
	if httpErr != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("download failed: %v", httpErr)}
	}
	defer httpResp.Body.Close()

	if httpResp.StatusCode != http.StatusOK {
		_, _ = io.ReadAll(io.LimitReader(httpResp.Body, 512))
		logging.Log.Error().Int("status", httpResp.StatusCode).Str("instance", req.InstanceID).Str("url", mediaURL).Msg("Media download returned non-200 status")
		return SendResponse{Status: "error", Provider: "whatsmeow",
			Error: fmt.Sprintf("download failed: HTTP %d", httpResp.StatusCode)}
	}

	mediaBytes, readErr := io.ReadAll(httpResp.Body)
	if readErr != nil {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("read failed: %v", readErr)}
	}
	if len(mediaBytes) == 0 {
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: "downloaded file is empty"}
	}

	mimeType := httpResp.Header.Get("Content-Type")
	if mimeType == "" {
		mimeType = http.DetectContentType(mediaBytes)
	}

	if mediaType == "image" {
		if !checkImageMagic(mediaBytes, mimeType) {
			return SendResponse{Status: "error", Provider: "whatsmeow",
				Error: fmt.Sprintf("downloaded content is not a valid image (mime=%s)", mimeType)}
		}
	}

	logging.Log.Info().Int("size_bytes", len(mediaBytes)).Str("mime", mimeType).Str("instance", req.InstanceID).Msg("Media downloaded successfully")

	// Normalizar MIME type para áudio - WhatsApp requer audio/ogg para mensagens de voz
	if mediaType == "audio" {
		// Se for OGG (application/ogg, application/octet-stream, ou detectado pelos magic bytes),
		// usar o MIME type correto para mensagens de voz do WhatsApp
		isOgg := mimeType == "application/ogg" || mimeType == "application/octet-stream" ||
			(len(mediaBytes) >= 4 && string(mediaBytes[:4]) == "OggS")
		if isOgg {
			mimeType = "audio/ogg; codecs=opus"
		}
	}

	// Fallback MIME type
	if mimeType == "" || mimeType == "application/octet-stream" {
		switch mediaType {
		case "image":
			mimeType = "image/jpeg"
		case "audio":
			mimeType = "audio/ogg; codecs=opus"
		case "video":
			mimeType = "video/mp4"
		case "document":
			mimeType = "application/octet-stream"
		}
	}

	// ========== UPLOAD PARA WHATSAPP ==========
	sendCtx, cancel := context.WithTimeout(ctx, 120*time.Second)
	defer cancel()

	var mediaEnum whatsmeow.MediaType
	switch mediaType {
	case "image":
		mediaEnum = whatsmeow.MediaImage
	case "audio":
		mediaEnum = whatsmeow.MediaAudio
	case "video":
		mediaEnum = whatsmeow.MediaVideo
	case "document":
		mediaEnum = whatsmeow.MediaDocument
	default:
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("unsupported type: %s", mediaType)}
	}

	uploaded, uploadErr := client.Upload(sendCtx, mediaBytes, mediaEnum)
	if uploadErr != nil {
		logging.Log.Error().Err(uploadErr).Str("instance", req.InstanceID).Msg("Upload to WhatsApp failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: fmt.Sprintf("upload failed: %v", uploadErr)}
	}

	logging.Log.Info().
		Str("instance", req.InstanceID).
		Str("upload_url", uploaded.URL).
		Str("direct_path", uploaded.DirectPath).
		Str("handle", uploaded.Handle).
		Uint64("file_length", uploaded.FileLength).
		Msg("Media uploaded to WhatsApp servers")

	// ========== MONTAGEM DA MENSAGEM COM TODOS OS CAMPOS ==========
	// DirectPath e Handle são CRÍTICOS para exibição no mobile
	var msg *waE2E.Message
	switch mediaType {
	case "image":
		msg = &waE2E.Message{ImageMessage: &waE2E.ImageMessage{
			URL:           proto.String(uploaded.URL),
			DirectPath:    proto.String(uploaded.DirectPath),
			Mimetype:      proto.String(mimeType),
			Caption:       proto.String(caption),
			FileSHA256:    uploaded.FileSHA256,
			FileEncSHA256: uploaded.FileEncSHA256,
			FileLength:    proto.Uint64(uploaded.FileLength),
			MediaKey:      uploaded.MediaKey,
		}}
		// Handle é opcional mas ajuda na exibição
	case "audio":
		// Generate waveform visualization (required by WhatsApp for audio messages)
		waveform := generateWaveform(mediaBytes)
		audioMsg := &waE2E.AudioMessage{
			URL:           proto.String(uploaded.URL),
			DirectPath:    proto.String(uploaded.DirectPath),
			Mimetype:      proto.String(mimeType),
			FileSHA256:    uploaded.FileSHA256,
			FileEncSHA256: uploaded.FileEncSHA256,
			FileLength:    proto.Uint64(uploaded.FileLength),
			MediaKey:      uploaded.MediaKey,
			PTT:           proto.Bool(true),
			Waveform:      waveform,
		}
		if dur := detectAudioDuration(mediaBytes, mimeType); dur > 0 {
			audioMsg.Seconds = proto.Uint32(dur)
		}
		msg = &waE2E.Message{AudioMessage: audioMsg}
	case "video":
		msg = &waE2E.Message{VideoMessage: &waE2E.VideoMessage{
			URL:           proto.String(uploaded.URL),
			DirectPath:    proto.String(uploaded.DirectPath),
			Mimetype:      proto.String(mimeType),
			Caption:       proto.String(caption),
			FileSHA256:    uploaded.FileSHA256,
			FileEncSHA256: uploaded.FileEncSHA256,
			FileLength:    proto.Uint64(uploaded.FileLength),
			MediaKey:      uploaded.MediaKey,
		}}
	case "document":
		msg = &waE2E.Message{DocumentMessage: &waE2E.DocumentMessage{
			URL:           proto.String(uploaded.URL),
			DirectPath:    proto.String(uploaded.DirectPath),
			Mimetype:      proto.String(mimeType),
			FileSHA256:    uploaded.FileSHA256,
			FileEncSHA256: uploaded.FileEncSHA256,
			FileLength:    proto.Uint64(uploaded.FileLength),
			MediaKey:      uploaded.MediaKey,
			FileName:      proto.String(caption),
		}}
	}

	// ========== ENVIO ==========
	resp, msgErr := client.SendMessage(sendCtx, jid, msg)
	if msgErr != nil {
		logging.Log.Error().Err(msgErr).Str("instance", req.InstanceID).Msg("SendMedia failed")
		return SendResponse{Status: "error", Provider: "whatsmeow", Error: msgErr.Error()}
	}

	logging.Log.Info().
		Str("instance", req.InstanceID).
		Str("message_id", resp.ID).
		Str("to", req.ChatID).
		Str("type", mediaType).
		Int("file_bytes", len(mediaBytes)).
		Str("mime", mimeType).
		Bool("has_direct_path", uploaded.DirectPath != "").
		Bool("has_handle", uploaded.Handle != "").
		Msg("Media sent successfully")

	return SendResponse{Status: "success", Provider: "whatsmeow", MessageID: resp.ID}
}

// detectAudioDuration tries to detect audio duration in seconds.
// For OGG/Opus: parses last OGG page granule position.
// For others: estimates from file size (rough fallback).
func detectAudioDuration(data []byte, mimeType string) uint32 {
	if len(data) < 4 {
		return 0
	}

	// OGG/Opus: parse granule position from last page
	if bytes.HasPrefix(data[:4], []byte("OggS")) || mimeType == "audio/ogg" || mimeType == "audio/ogg; codecs=opus" {
		return detectOggDuration(data)
	}

	// MP3 and others: rough estimate from file size (assume ~128kbps)
	if len(data) > 0 {
		// Very rough: 128kbps = 16000 bytes/sec
		return uint32(len(data) / 16000)
	}

	return 0
}

// detectOggDuration parses OGG container to find total duration from last page granule position.
func detectOggDuration(data []byte) uint32 {
	if len(data) < 27 {
		return 0
	}

	var lastGranule int64
	// Scan all OGG pages to find the last granule position
	offset := 0
	for offset+27 <= len(data) {
		// Check for OGG capture pattern
		if string(data[offset:offset+4]) != "OggS" {
			break
		}

		// Granule position is at bytes 6-13 (little-endian int64)
		if offset+14 <= len(data) {
			granule := int64(binary.LittleEndian.Uint64(data[offset+6 : offset+14]))
			if granule > 0 {
				lastGranule = granule
			}
		}

		// Number of segments at byte 26
		if offset+27 > len(data) {
			break
		}
		numSegments := int(data[offset+26])

		// Calculate page size: header (27) + segment table (numSegments) + segment data
		if offset+27+numSegments > len(data) {
			break
		}
		segmentDataSize := 0
		for i := 0; i < numSegments; i++ {
			segmentDataSize += int(data[offset+27+i])
		}

		pageSize := 27 + numSegments + segmentDataSize
		offset += pageSize
	}

	if lastGranule <= 0 {
		return 0
	}

	// Opus sample rate is always 48000 Hz
	// Granule position = total samples (pre-skip already accounted)
	const opusSampleRate = 48000
	durationSeconds := uint32(lastGranule / opusSampleRate)
	if durationSeconds == 0 {
		durationSeconds = 1 // minimum 1 second
	}
	return durationSeconds
}

// generateWaveform creates a 64-byte waveform visualization from audio data.
// WhatsApp requires this field for audio/voice messages to be displayed correctly.
func generateWaveform(data []byte) []byte {
	const numSamples = 64
	waveform := make([]byte, numSamples)

	if len(data) == 0 {
		// Return a flat waveform for empty data
		for i := range waveform {
			waveform[i] = 1
		}
		return waveform
	}

	// Sample the data at regular intervals and compute amplitude
	chunkSize := len(data) / numSamples
	if chunkSize == 0 {
		chunkSize = 1
	}

	maxVal := byte(0)
	for i := 0; i < numSamples; i++ {
		start := i * chunkSize
		end := start + chunkSize
		if end > len(data) {
			end = len(data)
		}

		// Compute RMS-like amplitude for this chunk
		var sum float64
		count := 0
		for j := start; j < end; j++ {
			// Treat bytes as unsigned samples centered at 128
			sample := float64(data[j]) - 128.0
			sum += sample * sample
			count++
		}
		if count > 0 {
			rms := byte(math.Sqrt(sum/float64(count)))
			if rms > 127 {
				rms = 127
			}
			waveform[i] = rms + 1 // ensure non-zero
			if waveform[i] > maxVal {
				maxVal = waveform[i]
			}
		} else {
			waveform[i] = 1
		}
	}

	// Normalize to 0-255 range
	if maxVal > 0 {
		for i := range waveform {
			waveform[i] = byte(int(waveform[i]) * 255 / int(maxVal))
			if waveform[i] == 0 {
				waveform[i] = 1
			}
		}
	}

	return waveform
}
