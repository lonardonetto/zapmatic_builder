# Tasks: Encerramento Automático de Chamada (Auto-Hangup)

> feature: auto-hangup-ligacao

## T-019 — Hook OnFinish e Auto-Hangup no Handler Go [concluida]
- Refs: US-013, US-014, AC-033, AC-034, AC-035, AC-036
- Arquivos: app_zapmatic_whatsmeow_api/internal/http/handler_call.go
- Notas: Capturar o retorno `*Player` de `call.Play(audioSrc)` e vincular callback `player.OnFinish()` com delay de 2 segundos antes de chamar `call.Hangup()`. Implementar leitor nativo de duração de arquivos de áudio e timer de segurança de fallback.

## T-020 — Testes Unitários e Integração Go [concluida]
- Refs: AC-033, AC-034, AC-035, AC-036
- Arquivos: app_zapmatic_whatsmeow_api/internal/http/handler_call_test.go
- Notas: Testes em Go anotados com `@spec:AC-033`, `@spec:AC-034`, `@spec:AC-035`, `@spec:AC-036` validando a lógica de auto-hangup, o callback `OnFinish`, a leitura de duração e a resiliência a chamadas já encerradas.

## T-021 — Leitor Nativo de Duração de Áudio no PHP [concluida]
- Refs: AC-035, AC-036
- Arquivos: inc/core/Whatsapp_call_campaign/Controllers/Whatsapp_call_campaign.php, app/Commands/CallCampaignWorker.php
- Notas: Adicionar função em PHP puro para ler cabeçalhos MP3/WAV e obter duração precisa sem depender de funções desabilitadas de shell (`shell_exec`/`ffprobe`). Recalcular duração no worker se estiver zerada no banco.
