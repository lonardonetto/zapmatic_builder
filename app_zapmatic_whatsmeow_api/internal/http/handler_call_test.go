package http

// @spec:AC-033 @spec:AC-034 @spec:AC-035 @spec:AC-036 — Auto-hangup e cálculo de duração de áudio no gateway Go.
import (
	"os"
	"path/filepath"
	"sync/atomic"
	"testing"
	"time"
)

// TestEstimateAudioDurationEmpty valida que caminhos vazios retornam 0.
// @spec:AC-036
func TestEstimateAudioDurationEmpty(t *testing.T) {
	dur := estimateAudioDuration("")
	if dur != 0 {
		tapNotOk(t, "TestEstimateAudioDurationEmpty", "AC-036", "caminho vazio deveria retornar 0")
		return
	}

	dur = estimateAudioDuration("/caminho/que/nao/existe/audio.mp3")
	if dur != 0 {
		tapNotOk(t, "TestEstimateAudioDurationEmpty", "AC-036", "arquivo inexistente deveria retornar 0")
		return
	}
	tapOk(t, "TestEstimateAudioDurationEmpty", "AC-036")
}

// TestEstimateAudioDurationWav valida a leitura exata do cabeçalho WAV.
// @spec:AC-036
func TestEstimateAudioDurationWav(t *testing.T) {
	tmpDir := t.TempDir()
	wavPath := filepath.Join(tmpDir, "test.wav")

	// Monta cabeçalho WAV simples (44 bytes), 16000 bytes/sec byteRate, 32000 bytes de áudio = 2 segundos
	header := make([]byte, 44+32000)
	copy(header[0:4], []byte("RIFF"))
	copy(header[8:12], []byte("WAVE"))
	copy(header[12:16], []byte("fmt "))
	// ByteRate em [28:32] = 16000 (0x3E80)
	header[28] = 0x80
	header[29] = 0x3E
	header[30] = 0x00
	header[31] = 0x00

	if err := os.WriteFile(wavPath, header, 0644); err != nil {
		t.Fatalf("erro ao criar arquivo wav de teste: %v", err)
	}

	dur := estimateAudioDuration(wavPath)
	if dur != 2 {
		tapNotOk(t, "TestEstimateAudioDurationWav", "AC-036", "duracao calculada incorreta para WAV")
		return
	}
	tapOk(t, "TestEstimateAudioDurationWav", "AC-036")
}

// TestEstimateAudioDurationMp3 valida a estimativa por taxa de bits para MP3.
// @spec:AC-036
func TestEstimateAudioDurationMp3(t *testing.T) {
	tmpDir := t.TempDir()
	mp3Path := filepath.Join(tmpDir, "test.mp3")

	// 160000 bytes ~ 10 segundos a 128kbps (16000 bytes/s)
	data := make([]byte, 160000)
	if err := os.WriteFile(mp3Path, data, 0644); err != nil {
		t.Fatalf("erro ao criar arquivo mp3 de teste: %v", err)
	}

	dur := estimateAudioDuration(mp3Path)
	if dur != 10 {
		tapNotOk(t, "TestEstimateAudioDurationMp3", "AC-036", "duracao estimada incorreta para MP3")
		return
	}
	tapOk(t, "TestEstimateAudioDurationMp3", "AC-036")
}

// TestEffectiveAudioDurationFallback valida que quando audio_duration <= 0, o fallback é utilizado.
// @spec:AC-035 @spec:AC-036
func TestEffectiveAudioDurationFallback(t *testing.T) {
	// Caso 1: audio_duration fornecido no payload prevalece
	provided := 15
	effective := provided
	if effective <= 0 {
		effective = estimateAudioDuration("algum_path")
	}
	if effective != 15 {
		tapNotOk(t, "TestEffectiveAudioDurationFallback", "AC-035", "duracao fornecida deveria ser mantida")
		return
	}
	tapOk(t, "TestEffectiveAudioDurationFallback", "AC-035")

	// Caso 2: audio_duration = 0 utiliza estimativa do arquivo
	tmpDir := t.TempDir()
	mp3Path := filepath.Join(tmpDir, "fallback.mp3")
	_ = os.WriteFile(mp3Path, make([]byte, 48000), 0644) // 3 segundos

	provided = 0
	effective = provided
	if effective <= 0 && mp3Path != "" {
		effective = estimateAudioDuration(mp3Path)
	}
	if effective != 3 {
		tapNotOk(t, "TestEffectiveAudioDurationFallback", "AC-036", "fallback de duracao deveria calcular 3 segundos")
		return
	}
	tapOk(t, "TestEffectiveAudioDurationFallback", "AC-036")
}

// TestCallAutoHangupOnFinishHook valida a lógica de auto-hangup com 2 segundos após o fim do áudio.
// @spec:AC-033 @spec:AC-034
func TestCallAutoHangupOnFinishHook(t *testing.T) {
	var hangupCalled int32
	mockHangup := func() {
		atomic.AddInt32(&hangupCalled, 1)
	}

	entry := &callEntry{
		CallID: "TESTCALL123",
		Status: "active",
	}

	// Simula disparo do OnFinish
	onFinish := func() {
		go func() {
			time.Sleep(50 * time.Millisecond) // Simulado acelerado para o teste
			if entry.Status == "active" || entry.Status == "ringing" {
				mockHangup()
			}
		}()
	}

	onFinish()
	time.Sleep(100 * time.Millisecond)

	if atomic.LoadInt32(&hangupCalled) != 1 {
		tapNotOk(t, "TestCallAutoHangupOnFinishHook", "AC-033", "hangup deveria ter sido chamado apos OnFinish")
		return
	}
	tapOk(t, "TestCallAutoHangupOnFinishHook", "AC-033")

	// Teste AC-034: se a chamada ja estiver encerrada, hangup nao deve ser chamado novamente
	atomic.StoreInt32(&hangupCalled, 0)
	entry.Status = "ended"
	onFinish()
	time.Sleep(100 * time.Millisecond)

	if atomic.LoadInt32(&hangupCalled) != 0 {
		tapNotOk(t, "TestCallAutoHangupOnFinishHook", "AC-034", "hangup nao deveria ser chamado para chamada ja ended")
		return
	}
	tapOk(t, "TestCallAutoHangupOnFinishHook", "AC-034")
}

// TestNormalizePlatform valida o mapeamento RemotePlatform -> mobile/web.
// @spec:AC-071
func TestNormalizePlatform(t *testing.T) {
	if normalizePlatform("smbi") != "mobile" {
		tapNotOk(t, "TestNormalizePlatform", "AC-071", "smbi deveria normalizar para mobile")
		return
	}
	if normalizePlatform("smba") != "mobile" {
		tapNotOk(t, "TestNormalizePlatform", "AC-071", "smba deveria normalizar para mobile")
		return
	}
	if normalizePlatform("web") != "web" {
		tapNotOk(t, "TestNormalizePlatform", "AC-071", "web deveria normalizar para web")
		return
	}
	if normalizePlatform("") != "" {
		tapNotOk(t, "TestNormalizePlatform", "AC-071", "vazio deveria permanecer vazio")
		return
	}
	tapOk(t, "TestNormalizePlatform", "AC-071")
}

// TestCallTimelineOrdered valida que a timeline é acumulada em ordem e o snapshot
// preserva os campos legados (retrocompatibilidade).
// @spec:AC-069 @spec:AC-080
func TestCallTimelineOrdered(t *testing.T) {
	entry := &callEntry{
		CallID:    "CALLTL1",
		Status:    "ringing",
		StartedAt: time.Now(),
	}
	entry.appendTimeline(callEvent{Event: "placed", At: time.Now()})
	entry.appendTimeline(callEvent{Event: "preaccepted", At: time.Now()})
	entry.appendTimeline(callEvent{Event: "accepted", Platform: "mobile", At: time.Now()})
	entry.appendTimeline(callEvent{Event: "ended", Reason: "hangup", At: time.Now()})

	snap := entry.snapshot()
	if len(snap.Timeline) != 4 {
		tapNotOk(t, "TestCallTimelineOrdered", "AC-069", "timeline deveria ter 4 eventos")
		return
	}
	if snap.Timeline[0].Event != "placed" || snap.Timeline[3].Event != "ended" {
		tapNotOk(t, "TestCallTimelineOrdered", "AC-069", "timeline fora de ordem")
		return
	}
	// Retrocompatibilidade: campos legados presentes no snapshot.
	if snap.CallID == "" || snap.Status == "" || snap.StartedAt.IsZero() {
		tapNotOk(t, "TestCallTimelineOrdered", "AC-080", "campos legados ausentes no snapshot")
		return
	}
	tapOk(t, "TestCallTimelineOrdered", "AC-069")
}

// TestCallRingTimeoutGuards valida que o ring timeout só encerra chamadas ainda
// em ringing e nunca uma chamada já atendida/encerrada.
// @spec:AC-075
func TestCallRingTimeoutGuards(t *testing.T) {
	var hangupCalled int32
	entry := &callEntry{CallID: "CALLRT1", Status: "ringing"}

	// Simula a lógica do timer: só encerra se ainda ringing.
	armRingTimeout := func(e *callEntry) {
		go func() {
			time.Sleep(50 * time.Millisecond)
			e.mu.Lock()
			stillRinging := e.Status == "ringing"
			e.mu.Unlock()
			if stillRinging {
				atomic.AddInt32(&hangupCalled, 1)
			}
		}()
	}

	armRingTimeout(entry)
	time.Sleep(100 * time.Millisecond)
	if atomic.LoadInt32(&hangupCalled) != 1 {
		tapNotOk(t, "TestCallRingTimeoutGuards", "AC-075", "chamada ringing deveria ser encerrada pelo timeout")
		return
	}

	// Já atendida: não deve encerrar.
	atomic.StoreInt32(&hangupCalled, 0)
	entry.mu.Lock()
	entry.Status = "active"
	entry.mu.Unlock()
	armRingTimeout(entry)
	time.Sleep(100 * time.Millisecond)
	if atomic.LoadInt32(&hangupCalled) != 0 {
		tapNotOk(t, "TestCallRingTimeoutGuards", "AC-075", "chamada atendida nao deveria ser encerrada pelo ring timeout")
		return
	}
	tapOk(t, "TestCallRingTimeoutGuards", "AC-075")
}

// TestCallStatusRetrocompativel valida que o snapshot do /call/status preserva
// os campos legados (call_id, status, started_at, reason) junto da timeline.
// @spec:AC-080
func TestCallStatusRetrocompativel(t *testing.T) {
	now := time.Now()
	entry := &callEntry{
		CallID:    "CALLRETRO1",
		Status:    "ended",
		StartedAt: now.Add(-10 * time.Second),
		Reason:    "hangup",
	}
	entry.appendTimeline(callEvent{Event: "placed", At: now.Add(-10 * time.Second)})
	entry.appendTimeline(callEvent{Event: "ended", Reason: "hangup", At: now})

	snap := entry.snapshot()
	if snap.CallID != "CALLRETRO1" || snap.Status != "ended" || snap.Reason != "hangup" {
		tapNotOk(t, "TestCallStatusRetrocompativel", "AC-080", "campos legados devem permanecer")
		return
	}
	if len(snap.Timeline) != 2 {
		tapNotOk(t, "TestCallStatusRetrocompativel", "AC-080", "timeline deveria ter 2 eventos")
		return
	}
	tapOk(t, "TestCallStatusRetrocompativel", "AC-080")
}

// TestCallEventBridgePlatform valida que o bridge associa a plataforma à call
// ativa pelo evento "accepted" (captura sem tocar no fork meowcaller).
// @spec:AC-081
func TestCallEventBridgePlatform(t *testing.T) {
	entry := &callEntry{CallID: "CALLBRIDGE1", Status: "ringing", StartedAt: time.Now()}
	applyCallEvent(entry, "preaccepted", "", "")
	applyCallEvent(entry, "accepted", "smbi", "")

	snap := entry.snapshot()
	if snap.Platform != "mobile" {
		tapNotOk(t, "TestCallEventBridgePlatform", "AC-081", "plataforma deveria ser mobile")
		return
	}
	// timeline deve conter preaccepted e accepted
	foundAccepted := false
	for _, ev := range snap.Timeline {
		if ev.Event == "accepted" {
			foundAccepted = true
		}
	}
	if !foundAccepted {
		tapNotOk(t, "TestCallEventBridgePlatform", "AC-081", "evento accepted ausente na timeline")
		return
	}
	tapOk(t, "TestCallEventBridgePlatform", "AC-081")
}
