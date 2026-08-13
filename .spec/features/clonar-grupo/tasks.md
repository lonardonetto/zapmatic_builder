# Tasks: Clonar grupo

> feature: clonar-grupo

## T-007 — Biblioteca pura do clone (nome, filtro, lotes, progresso) [concluida]
- Refs: US-006, US-007, AC-014, AC-015, AC-016, AC-018, AC-019
- Arquivos: inc/core/Whatsapp_export_participants/Libraries/GroupCloner.php
- Notas: Classe pura, sem banco e sem acoplamento com CodeIgniter. GroupCloner::buildTargetName (padrão "X - cópia", truncado a 25), GroupCloner::filterClone (remove admins + próprio número + deduplica), GroupCloner::chunkParticipants (lotes de 50), GroupCloner::calcProgress. Reaproveita ParticipantFilter/PhoneNormalizer quando possível.

## T-008 — Rota Go para criar grupo e adicionar participantes [concluida]
- Refs: US-009, AC-022, AC-023
- Arquivos: app_zapmatic_whatsmeow_api/internal/http/handler_clone_group.go, app_zapmatic_whatsmeow_api/internal/http/router.go
- Notas: Novo handler POST /groups/clone com instance_id, name e participants (JIDs). Funções puras truncateGroupName (25) e chunkParticipants (lotes de 50) testáveis offline. Cria via client.CreateGroup e adiciona via client.UpdateGroupParticipants(ParticipantChangeAdd) em lotes. Recompilar + backup (P-003/P-004).

## T-009 — Fila de clone (migration + job) [concluida]
- Refs: US-007, US-008, AC-017, AC-018
- Arquivos: migrations/008_20260813_clone_group_queue.sql
- Notas: Migration cria sp_clone_group_queue (ids, team_id, account_id, group_id, group_name, target_name, status, total, done, attempts, max_attempts, error_log, new_group_jid, created, changed). Mesmo padrão de sp_export_participants_queue.

## T-010 — Controller: enfileirar e processar clone [concluida]
- Refs: US-006, US-007, US-008, AC-014, AC-015, AC-016, AC-017, AC-020, AC-021
- Arquivos: inc/core/Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php, inc/core/Whatsapp_export_participants/Config.php
- Notas: Método clone_group($account_id, $group_id) que (1) valida login_type=3, (2) escopa por team_id, (3) monta participantes filtrados (sem admins/self, deduplicados), (4) enfileira em sp_clone_group_queue. Estender cron() para drenar também a fila de clone (lotes de 50 por chamada ao Go). Config.php mantém o cron único existente.

## T-011 — View: botão "Clonar grupo" com nome editável [concluida]
- Refs: US-006, AC-014, AC-020
- Arquivos: inc/core/Whatsapp_export_participants/Views/groups.php
- Notas: Botão "Clonar grupo" no card de cada grupo, visível só para login_type=3. Modal com campo de nome pré-preenchido "X - cópia" (editável) e confirmação. Sem alterar os botões existentes (Exportar CSV / Criar Lista de Contatos).

## T-012 — Testes de aceite (PHP + Go) [concluida]
- Refs: AC-014, AC-015, AC-016, AC-017, AC-018, AC-019, AC-020, AC-021, AC-022, AC-023
- Arquivos: tests/phpunit/GroupClonerTest.php, app_zapmatic_whatsmeow_api/internal/http/clone_group_test.go
- Notas: Cada AC vira um teste com @spec:AC-xxx (PHP via docblock; Go via tap_test.go). Cobre nome/truncamento, filtro de admins/self/duplicados, fatiamento em lotes, progresso, payload do job, validação de login_type e escopo de team.
