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

## SERVIDORES APLICADOS — TODOS OS 9
Main ✅ | Multiconnecta ✅ | Renovo ✅ | IaClicks ✅ | Elite ✅ | PlusZap ✅ | Kivozap ✅ | AgenciaMCW ✅ | Chatbut ✅
