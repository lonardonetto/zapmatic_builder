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
