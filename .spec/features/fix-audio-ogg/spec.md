# Spec: Correção de Envio de Áudio OGG — Todos os Fluxos

> **Status:** em análise  
> **Data:** 2026-08-28  
> **Problema:** Áudios OGG não chegam como mensagem de voz (PTT) no WhatsApp. Chegam como MP3 com visual de "áudio encaminhado" (bolha roxa).

---

## 1. Contexto

O WhatsApp diferencia dois tipos de áudio:
- **Mensagem de voz (PTT):** áudio OGG/Opus com `PTT=true` — aparece como gravação direta (bolha verde com ondas)
- **Arquivo de áudio:** MP3/M4A/etc — aparece como arquivo enviado (bolha roxa com player)

Para que um áudio chegue como mensagem de voz, o Go gateway precisa:
1. Receber o arquivo como OGG/Opus
2. Definir `PTT=true` na mensagem protobuf
3. Usar MIME type `audio/ogg; codecs=opus`

---

## 2. Fluxos de Envio de Áudio Identificados

### 2.1 Bot Builder — Bloco `audio` (linha 1805)

```php
case 'audio':
    $url = $this->replace_vars($bData->url ?? '', $context);
    $this->send_whatsapp($instance_id, $session->phone, 'audio', ['url' => $url]);
```

**Fluxo:** URL direta → `send_whatsapp()` → `WhatsAppGatewayService::send()` → Go gateway `/send/media`

**Status:** ✅ DEVERIA funcionar se a URL apontar para um arquivo OGG. O Go gateway já normaliza o MIME type (fix v8.5.26).

**Possível problema:** Se a URL aponta para um arquivo `.mp3` (ex: ElevenLabs TTS), o Go gateway detecta como MP3 e envia como arquivo, não como voz.

### 2.2 Bot Builder — ElevenLabs TTS (linha 2611)

```php
case 'intg_elevenlabs':
    // ...
    $fileName = 'audio_' . time() . '_' . mt_rand(1000,9999) . '.mp3';  // ❌ SEMPRE .mp3
    $audioPath = FCPATH . 'uploads/audio/' . $fileName;
    file_put_contents($audioPath, $audioData);
    $context[$varName] = base_url('uploads/audio/' . $fileName);
    $this->send_whatsapp($instance_id, $session->phone, 'audio', ['url' => $context[$varName]]);
```

**Fluxo:** ElevenLabs API → download como MP3 → salva como `.mp3` → envia como áudio

**Status:** ❌ **PROBLEMA IDENTIFICADO.** O ElevenLabs retorna MP3 por padrão. O arquivo é salvo como `.mp3`. O Go gateway detecta como MP3 e envia como arquivo (bolha roxa).

**Correção necessária:** 
- Opção A: Usar modelo ElevenLabs que retorne OGG/Opus (se disponível)
- Opção B: Converter MP3→OGG com ffmpeg antes de enviar
- Opção C: Aceitar que TTS será enviado como arquivo MP3 (não como voz)

### 2.3 Single Message (Whatsapp_send_message, linha 218)

```php
$ext = strtolower(pathinfo(parse_url($media, PHP_URL_PATH), PATHINFO_EXTENSION));
$mediaType = match($ext) {
    'ogg', 'oga', 'opus', 'mp3', 'wav', 'm4a', 'aac', 'flac' => 'audio',
    // ...
};
$result = \App\Services\WhatsAppGatewayService::send($account->token, $send_to, $mediaType, ['url' => $media, ...]);
```

**Fluxo:** URL com extensão → detecção por extensão → `WhatsAppGatewayService::send()` → Go gateway

**Status:** ✅ DEVERIA funcionar. Se a URL termina em `.ogg`, detecta como 'audio' e o Go gateway faz o resto.

**Possível problema:** Se o arquivo foi salvo como `.mp3` (ex: upload de arquivo OGG que foi renomeado), a extensão será `.mp3` e o Go gateway tratará como MP3.

### 2.4 Bulk Campaign (Whatsapp_bulk, linha 1079)

```php
$media = $medias[0]; // URL do arquivo
// ... salva no schedule ...
```

**Fluxo:** URL do arquivo → salva em `sp_whatsapp_schedules.media` → worker despacha → `WhatsAppGatewayService::send()` → Go gateway

**Status:** ✅ DEVERIA funcionar se a URL apontar para OGG.

**Possível problema:** Mesmo que o arquivo original seja OGG, se foi salvo no servidor como `.mp3` (ex: upload via file manager que renomeia), a URL terminará em `.mp3`.

### 2.5 Go Gateway — media.go (linha 131)

```go
// Normalizar MIME type para áudio
if mediaType == "audio" {
    isOgg := mimeType == "application/ogg" || mimeType == "application/octet-stream" ||
        (len(mediaBytes) >= 4 && string(mediaBytes[:4]) == "OggS")
    if isOgg {
        mimeType = "audio/ogg; codecs=opus"
    }
}
```

**Status:** ✅ CORRIGIDO em v8.5.26. Normaliza `application/ogg` → `audio/ogg; codecs=opus`.

**Nota:** Se o arquivo é MP3 (magic bytes `ID3` ou `ÿû`), NÃO será normalizado para OGG. O Go gateway enviará como MP3.

---

## 3. Diagnóstico

O problema NÃO está no Go gateway (já corrigido). O problema está nos **pontos de entrada** do áudio:

| Fluxo | Arquivo salvo como | Go gateway detecta | Resultado |
|-------|-------------------|-------------------|-----------|
| Bot Builder audio block (URL externa OGG) | N/A (URL direta) | OGG ✅ | Voz ✅ |
| Bot Builder audio block (URL local .mp3) | .mp3 | MP3 ❌ | Arquivo ❌ |
| ElevenLabs TTS | .mp3 | MP3 ❌ | Arquivo ❌ |
| Single Message (URL .ogg) | N/A | OGG ✅ | Voz ✅ |
| Single Message (URL .mp3) | N/A | MP3 ❌ | Arquivo ❌ |
| Bulk Campaign (URL .ogg) | N/A | OGG ✅ | Voz ✅ |
| Bulk Campaign (URL .mp3) | N/A | MP3 ❌ | Arquivo ❌ |
| File upload (arquivo.ogg) | .ogg | OGG ✅ | Voz ✅ |
| File upload (arquivo.mp3) | .mp3 | MP3 ❌ | Arquivo ❌ |

**Conclusão:** O sistema funciona corretamente quando o arquivo de origem é OGG/Opus. O problema acontece quando:
1. O arquivo é MP3 (extensão ou conteúdo)
2. O ElevenLabs gera MP3
3. O file manager salva como MP3

---

## 4. Correções Propostas

### 4.1 Prioridade ALTA — ElevenLabs TTS

**Problema:** ElevenLabs retorna MP3. Salva como `.mp3`. Envia como arquivo.

**Correção:** Adicionar conversão MP3→OGG com ffmpeg no Bot Builder, OU usar modelo ElevenLabs que retorne OGG/Opus.

**Arquivo:** `inc/core/Bot_builder/Controllers/Bot_builder.php` linha 2631

### 4.2 Prioridade MÉDIA — File Upload

**Problema:** Se o usuário faz upload de um arquivo OGG, ele deve ser salvo preservando a extensão `.ogg`.

**Verificar:** O file manager preserva extensões de upload?

### 4.3 Prioridade BAIXA — Documentação

**Problema:** O usuário não sabe que para enviar como "mensagem de voz" o arquivo precisa ser OGG/Opus.

**Correção:** Adicionar nota na interface do Bot Builder explicando que áudios OGG/Opus são enviados como mensagem de voz.

---

## 5. Testes Necessários

| # | Teste | Comando | Critério de sucesso |
|---|-------|---------|-------------------|
| 1 | Go gateway envia OGG como voz | `curl -X POST http://127.0.0.1:8090/send/media -d '{"instance_id":"...","chat_id":"...","type":"audio","payload":{"url":"https://upload.wikimedia.org/wikipedia/commons/c/c8/Example.ogg"}}'` | `status: success` + MIME `audio/ogg; codecs=opus` no log |
| 2 | Go gateway envia MP3 como arquivo | Mesmo comando com URL `.mp3` | `status: success` + MIME `audio/mpeg` no log |
| 3 | Bot Builder audio block com OGG | Criar bloco audio com URL OGG e executar flow | Mensagem de voz no WhatsApp |
| 4 | Bot Builder audio block com MP3 | Criar bloco audio com URL MP3 e executar flow | Arquivo de áudio no WhatsApp |
| 5 | Single Message com OGG | Enviar áudio OGG via Single Message | Mensagem de voz no WhatsApp |
| 6 | Bulk Campaign com OGG | Criar campanha com mídia OGG | Mensagem de voz no WhatsApp |

---

## 6. Arquivos Envolvidos

| Arquivo | Função |
|---------|--------|
| `app_zapmatic_whatsmeow_api/internal/sender/media.go` | Go gateway — upload e envio de mídia |
| `inc/core/Bot_builder/Controllers/Bot_builder.php` | Bot Builder — blocos audio e ElevenLabs TTS |
| `inc/core/Whatsapp_send_message/Controllers/Whatsapp_send_message.php` | Single Message — envio de mídia |
| `inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php` | Bulk Campaign — agendamento de mídia |
| `app/Commands/BotWorkerAll.php` | Worker — despacho de campanhas |
| `app/Services/WhatsAppGatewayService.php` | Serviço de gateway — roteamento |
