# Migration: Módulo de Ligação Completo - v8.5.1
# Data: 2026-08-03
# Status: Testado e funcionando no Main, IaClicks, Multiconnecta, AgenciaMCW

## RESUMO DOS PROBLEMAS ENCONTRADOS E CORRIGIDOS

### 1. BANCO DE DADOS — sp_call_campaigns faltavam 14 colunas
### 2. .HTACCESS — call_audio_stream.php bloqueado pelo Rewrite
### 3. call_audio_stream.php — versão antiga quebrada
### 4. Controller — bugs: update(), safeShell(), upload_audio() sem path completo ffmpeg
### 5. ecosystem.config.js — path errado para o app do cliente
### 6. Go binário — compilado sem vendor purpshell/meowcaller → Opus não funcionava
### 7. CallCampaignWorker.php — ausente em alguns servidores

## SERVIDORES APLICADOS

Main ✅ | Multiconnecta ✅ | Renovo ✅ | IaClicks ✅ | Elite ✅ | PlusZap ✅ | Kivozap ✅ | AgenciaMCW ✅ | Chatbut ✅

## ATUALIZAÇÃO 2026-08-06 — PAULO (atualizaleads.app.br)

Aplicada a mesma correção do IaClicks no app do Paulo (porta Go 8091):

### 1. CallCampaignWorker.php — atualizado de getAudioPath() → getAudioInfo()
- Antes: enviava apenas `audio_path` (sem duração)
- Agora: envia `audio_path` + `audio_duration` (mesmo do Zapmatic/IaClicks)

### 2. handler_call.go + binário Go recompilado — auto-hangup adicionado
- Struct agora inclui `AudioDuration` (json: audio_duration)
- Chamadas atendidas encerram automaticamente após a duração do áudio + 2s
- Binário recompilado em 2026-08-06 (com auto-hangup, confirmado via strings: 2 ocorrências "Auto-hangup")
- Backup do binário antigo: zapmatic-whatsmeow.bak_20260806_114550

### 3. Limpeza — 146 chamadas presas removidas
- O callStore do gateway do Paulo acumulava 146 chamadas fantasmas (141 ringing + 2 active) desde 04/08
- Causa: sem auto-hangup, chamadas atendidas ficavam "active" para sempre; ringing sem resposta nunca eram limpos
- Sintoma: gateway a 206% de CPU → áudio falhando em todas as ligações novas
- Após restart do serviço (systemd zapmatic-whatsmeow-paulo): call/list = 0 presas, CPU ~7%

### 4. Validação pós-correção
- "Playing audio to peer" + "Auto-hangup scheduled" + "Auto-hangup: audio finished, ending call" confirmados no gateway.log (call 01BB088BFAA5, 11:50)
- Worker pm2 reiniciado (paulo-call-worker) — "Call answered!" registrado
- Backups: handler_call.go.bak_20260806_114550, CallCampaignWorker.php.bak_20260806_114550
