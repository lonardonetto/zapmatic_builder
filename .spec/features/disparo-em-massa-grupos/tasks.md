# Tasks: Disparo em massa grupos

> feature: disparo-em-massa-grupos

## T-013 — Biblioteca pura de destinos de grupo (PHP) [concluida]
- Refs: US-010, AC-024, AC-025, AC-026, AC-028
- Arquivos: inc/core/Whatsapp_bulk/Libraries/GroupTarget.php
- Notas: Classe pura, sem banco e sem acoplamento com CodeIgniter. GroupTarget::normalizeTargets (deduplica por account_id+group_jid, descarta vazios), GroupTarget::ensureGroupJid (sufixo @g.us), GroupTarget::resolveChat (nunca @s.whatsapp.net). Reaproveita a filosofia das Libraries de Whatsapp_export_participants.

## T-014 — Destinos de grupo no motor Go (offset persistente + envio) [concluida]
- Refs: US-012, AC-026, AC-029, AC-030, AC-031, AC-032
- Arquivos: app_zapmatic_whatsmeow_api/internal/bulk/processor.go, app_zapmatic_whatsmeow_api/internal/bulk/campaign.go, app_zapmatic_whatsmeow_api/internal/bulk/group_target.go
- Notas: Campanha de grupo busca o próximo destino em sp_whatsapp_schedule_groups por offset sent+failed (padrão de GetNextPhone), monta chat como @g.us e envia via SendText/SendMedia (mesmo caminho, sem duplicar sender). Envio SEMPRE pela conta dona do grupo (resolveGroupInstance por account_id), não pelo rotador cego. Reusa CalculateDelay. target_type separa destino do tipo de mensagem. Recompilar + backup (P-003/P-004).

## T-015 — Migration da tabela de destinos de grupo [concluida]
- Refs: US-010, AC-024, AC-032
- Arquivos: migrations/009_20260813_schedule_groups.sql, migrations/010_20260813_target_type.sql
- Notas: 009 cria sp_whatsapp_schedule_groups (ids, team_id, schedule_id, account_id, group_jid, position, status, error_log, created, changed). Índice em schedule_id+position. 010 adiciona target_type em sp_whatsapp_schedules. Mesmo padrão das migrations 007/008.

## T-016 — Controller e validação (escopo, login_type, save) [concluida]
- Refs: US-010, US-011, AC-027, AC-028, AC-032
- Arquivos: inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php, inc/core/Whatsapp_bulk/Models/Whatsapp_bulkModel.php
- Notas: save() aceita target_type (contacts|groups) + group_targets, valida login_type=3 (Go) e escopo por team_id; persiste em sp_whatsapp_schedule_groups. get_account_items já escopa por team_id. Não altera o fluxo 1 a 1 existente. Carrega group_targets_json ao editar.

## T-017 — View: seletor de grupos como destino [concluida]
- Refs: US-010, AC-027, AC-032
- Arquivos: inc/core/Whatsapp_bulk/Views/update.php
- Notas: Seletor de DESTINO (Lista de contatos vs Grupos do WhatsApp) independente do tipo de mensagem. Quando destino=grupos, mostra o seletor de grupos (reusa /groups/list do gateway Go) e esconde o "Contact group". Sem tocar no seletor de lista de contatos existente.

## T-018 — Testes de aceite (PHP + Go) [concluida]
- Refs: AC-024, AC-025, AC-026, AC-027, AC-028, AC-029, AC-030, AC-031, AC-032
- Arquivos: tests/phpunit/GroupTargetTest.php, app_zapmatic_whatsmeow_api/internal/bulk/group_target_test.go, app_zapmatic_whatsmeow_api/internal/bulk/tap_test.go
- Notas: Cada AC vira um teste com @spec:AC-xxx (PHP via docblock; Go via tap_test.go). Cobre normalização/deduplicação, sufixo @g.us, resolução de chat de grupo, validação de login_type, escopo de team, delay, offset persistente, envio pela conta dona do grupo e destino independente do tipo.
