package http

// @spec:AC-022 @spec:AC-023 — clone de grupo no gateway Go: truncamento do
// nome a 25 caracteres e fatiamento de participantes em lotes.
import (
	"strings"
	"testing"

	"go.mau.fi/whatsmeow/types"
)

func TestTruncateGroupNameTo25(t *testing.T) {
	short := truncateGroupName("Vendas")
	if short != "Vendas" {
		tapNotOk(t, "TestTruncateGroupNameTo25", "AC-022", "nome curto alterado")
		return
	}

	long := truncateGroupName("Um grupo de vendas com um nome muito grande demais para o limite")
	runes := []rune(long)
	if len(runes) != 25 {
		tapNotOk(t, "TestTruncateGroupNameTo25", "AC-022", "nome longo não truncado a 25")
		return
	}
	tapOk(t, "TestTruncateGroupNameTo25", "AC-022")
}

func TestTruncateGroupNameEmptyDefaults(t *testing.T) {
	if truncateGroupName("") == "" {
		tapNotOk(t, "TestTruncateGroupNameEmptyDefaults", "AC-022", "nome vazio deveria ter fallback")
		return
	}
	tapOk(t, "TestTruncateGroupNameEmptyDefaults", "AC-022")
}

func TestChunkParticipantsBatches(t *testing.T) {
	var phones []string
	for i := 0; i < 120; i++ {
		phones = append(phones, "5511999999999")
	}
	chunks := chunkParticipants(phones, 50)
	if len(chunks) != 3 {
		tapNotOk(t, "TestChunkParticipantsBatches", "AC-023", "quantidade de lotes inesperada")
		return
	}
	if len(chunks[0]) != 50 || len(chunks[1]) != 50 || len(chunks[2]) != 20 {
		tapNotOk(t, "TestChunkParticipantsBatches", "AC-023", "tamanho dos lotes inesperado")
		return
	}
	tapOk(t, "TestChunkParticipantsBatches", "AC-023")
}

func TestChunkParticipantsSingleBatch(t *testing.T) {
	phones := []string{"5511000000001", "5511000000002"}
	chunks := chunkParticipants(phones, 50)
	if len(chunks) != 1 || len(chunks[0]) != 2 {
		tapNotOk(t, "TestChunkParticipantsSingleBatch", "AC-023", "lote único inesperado")
		return
	}
	tapOk(t, "TestChunkParticipantsSingleBatch", "AC-023")
}

func TestToParticipantJIDs(t *testing.T) {
	jids := toParticipantJIDs([]string{"5511000000001", "", " 5511000000002 "})
	if len(jids) != 2 {
		tapNotOk(t, "TestToParticipantJIDs", "AC-023", "JIDs inválidos deveriam ser ignorados")
		return
	}
	if jids[0].Server != types.DefaultUserServer {
		tapNotOk(t, "TestToParticipantJIDs", "AC-023", "server de JID inesperado")
		return
	}
	if !strings.Contains(jids[0].String(), "5511000000001") {
		tapNotOk(t, "TestToParticipantJIDs", "AC-023", "JID não contém o número")
		return
	}
	tapOk(t, "TestToParticipantJIDs", "AC-023")
}
