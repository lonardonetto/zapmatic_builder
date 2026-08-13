# Tasks: Extrair contatos de grupos

> feature: extrair-contatos-grupos

## T-001 — Bibliotecas puras de paginação, filtro e fila (PHP) [concluida]
- Refs: US-001, US-003, US-004, AC-001, AC-002, AC-003, AC-008, AC-009, AC-010, AC-011
- Arquivos: inc/core/Whatsapp_export_participants/Libraries/GroupPaginator.php, inc/core/Whatsapp_export_participants/Libraries/ParticipantFilter.php, inc/core/Whatsapp_export_participants/Libraries/ExportQueue.php, inc/core/Whatsapp_export_participants/Libraries/AccountScope.php
- Notas: Classes puras, sem acoplamento com CodeIgniter (mesma filosofia de PhoneNormalizer/PhoneValidator). GroupPaginator::paginate, ParticipantFilter::apply, ExportQueue::createJob/tenantOwns/calcProgress, AccountScope::withTeam.

## T-002 — Nome do participante quando existir (PHP) [concluida]
- Refs: US-005, AC-012
- Arquivos: inc/core/Whatsapp_export_participants/Libraries/LeadExtractor.php, inc/core/Whatsapp_export_participants/Libraries/PhoneNormalizer.php, inc/core/Whatsapp_export_participants/Libraries/PhoneValidator.php
- Notas: LeadExtractor::extract passa a capturar ->name (ou ['name']) quando presente, repassando ao CSV e à lista de contatos. PhoneNormalizer/PhoneValidator são as dependências puras já existentes (mantidas/mapeadas aqui). Sem mudança no gateway Go.

## T-003 — Paginação no gateway Go [concluida]
- Refs: US-001, AC-004, AC-013
- Arquivos: app_zapmatic_whatsmeow_api/internal/http/handler_groups.go
- Notas: Extrai a lógica de paginação para função pura (paginateGroups + selectGroups) e aplica page/limit/total no handleListGroups. Sem `page`, mantém o comportamento legado (retorna todos). A lógica pura é testada com tap_test.go. Recompilar + backup (P-003/P-004).

## T-004 — Integração no controller (fila + filtros + team_id) [concluida]
- Refs: US-002, US-003, US-004, US-005, AC-005, AC-008, AC-009, AC-011
- Arquivos: inc/core/Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php, inc/core/Whatsapp_export_participants/Config.php, inc/core/Whatsapp_export_participants/Views/content.php, inc/core/Whatsapp_export_participants/Views/groups.php, migrations/007_20260813_export_participants_queue.sql
- Notas: create_contact_list passa a enfileirar via ExportQueue (em vez de inserir síncrono); export_group aplica ParticipantFilter; Config.php ganha bloco cron. A migration cria sp_export_participants_queue.

## T-005 — Worker do cron para processar a fila [concluida]
- Refs: US-002, US-004, AC-005, AC-006, AC-007
- Arquivos: inc/core/Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php
- Notas: Método cron() público que puxa jobs pendentes do time, processa em lotes de 200 e atualiza done/status. Chama LeadExtractor::saveAsContactList.

## T-006 — Testes de aceite (PHP + Go) [concluida]
- Refs: AC-001, AC-002, AC-003, AC-004, AC-005, AC-006, AC-007, AC-008, AC-009, AC-010, AC-011, AC-012, AC-013
- Arquivos: tests/phpunit/GroupPaginatorTest.php, tests/phpunit/ParticipantFilterTest.php, tests/phpunit/ExportQueueTest.php, tests/phpunit/LeadExtractorNameTest.php, app_zapmatic_whatsmeow_api/internal/http/groups_paginate_test.go
- Notas: Cada AC vira um teste com @spec:AC-xxx (PHP via docblock; Go via tap_test.go). Também remove o SmokeTest placeholder (@spec:AC-999).
