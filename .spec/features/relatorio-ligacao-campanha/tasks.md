# Tasks: Relatório Completo e Confiabilidade das Campanhas de Ligação

> feature: relatorio-ligacao-campanha

## T-035 — Migração: tabela sp_call_events + colunas sp_call_leads [concluida]
- Refs: US-030, AC-082
- Arquivos: migrations/2026_08_21_001_call_events.sql, migration_db_zapmatic_full.sql
- Notas: Criar `sp_call_events` e ALTER `sp_call_leads` (platform, heard_full_audio, hangup_source, ring_duration_seconds, last_error). Sincronizar o dump completo `migration_db_zapmatic_full.sql` com o mesmo DDL.

## T-036 — Gateway: capturar plataforma e eventos do peer (CallAccept) [concluida]
- Refs: US-030, AC-081
- Arquivos: app_zapmatic_whatsmeow_api/internal/session/manager.go, app_zapmatic_whatsmeow_api/internal/http/handler_call.go
- Notas: Registrar handler de events.CallPreAccept/CallAccept/CallReject/CallTerminate no gateway (via inst.Client()) correlacionando por CallID, e expor um hook para o handler_call gravar a plataforma na callEntry. NÃO toca no fork meowcaller.

## T-037 — Gateway: timeline + ring timeout + retrocompatibilidade do status [concluida]
- Refs: US-026, US-028, US-030, AC-069, AC-071, AC-072, AC-073, AC-074, AC-075, AC-080
- Arquivos: app_zapmatic_whatsmeow_api/internal/http/handler_call.go
- Notas: Adicionar timeline em callEntry, timer de ring timeout (armado no start, cancelado em ready/end), gravação de plataforma no accepted, motivo rico no ended. Manter campos antigos no /call/status. Depende de T-036 (hook de plataforma).

## T-038 — Gateway: testes de ring timeout, timeline e status [concluida]
- Refs: AC-069, AC-071, AC-072, AC-073, AC-074, AC-075, AC-080
- Arquivos: app_zapmatic_whatsmeow_api/internal/http/handler_call_test.go
- Notas: Testes Go anotados @spec:AC-xxx: timer de ring timeout, ordenação da timeline, mapeamento platform→mobile/web, retrocompatibilidade do status (campos antigos presentes).

## T-039 — Worker: reconciliação assíncrona uniforme nos 3 modos [concluida]
- Refs: US-029, AC-077, AC-078, AC-079
- Arquivos: app/Commands/CallCampaignWorker.php
- Notas: Substituir pollCallResult bloqueante por reconcileRinging() no ciclo principal; modo simultaneo passa a reconciliar; alternado mantém rodízio de instância. Persistir eventos no MySQL (sp_call_events) e atualizar contadores.

## T-040 — Worker: persistência da timeline e classificação do desfecho [concluida]
- Refs: US-026, US-027, AC-070, AC-072, AC-073, AC-074
- Arquivos: app/Commands/CallCampaignWorker.php
- Notas: Ler timeline do /call/status e gravar em sp_call_events; classificar desfecho (ring_timeout × peer_disconnect × hangup), plataforma, heard_full_audio, hangup_source.

## T-041 — Worker: cancelamento explícito no timeout [concluida]
- Refs: US-028, AC-076
- Arquivos: app/Commands/CallCampaignWorker.php
- Notas: Ao estourar timeout de espera, chamar POST /call/cancel antes de marcar failed; garantir que não reste chamada ativa no gateway.

## T-042 — Relatório: exibir timeline e desfecho rico [concluida]
- Refs: US-026, US-027, AC-070
- Arquivos: inc/core/Whatsapp_call_campaign/Views/results.php, inc/core/Whatsapp_call_campaign/Controllers/Whatsapp_call_campaign.php
- Notas: Na tela de resultados, carregar sp_call_events por lead e renderizar a timeline (etapas, horário, plataforma, motivo) + novos campos (platform, heard_full_audio, hangup_source).

## T-043 — Testes PHP: reconciliação, classificação e persistência [concluida]
- Refs: AC-077, AC-078, AC-079, AC-072, AC-073, AC-074
- Arquivos: tests/phpunit/CallReconcileTest.php, tests/phpunit/CallOutcomeTest.php
- Notas: Testes PHPUnit anotados @spec:AC-xxx para a lógica pura de reconciliação/classificação (sem depender do gateway). Extrair a lógica para Library pura se necessário (inc/core/Whatsapp_call_campaign/Libraries/).
