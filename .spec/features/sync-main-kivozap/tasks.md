# Tasks: Sincronizar Main → Kivozap

> feature: sync-main-kivozap

## T-001 — Backup completo do kivozap antes da sincronização [concluida]
- Refs: US-016, US-017
- Arquivos: (execução remota via SSH)
- Notas: Fazer dump do banco `db_abner_sql`, backup do diretório `/www/wwwroot/app_abner_app/` (tar.gz), backup do `.env` separadamente, verificar integridade dos backups.

## T-002 — Remover tabelas legadas do kivozap [concluida]
- Refs: US-016, AC-039
- Arquivos: (execução remota via SSH — MySQL)
- Notas: Confirmar que `sp_whatsapp_autoresponder` e `sp_whatsapp_chatbot` não são referenciadas no código. Dropar ambas. Verificar com `SHOW TABLES`.

## T-003 — Criar tabelas faltantes no kivozap [concluida]
- Refs: US-016, AC-038
- Arquivos: (execução remota via SSH — MySQL)
- Notas: Criar `sp_clone_group_queue`, `sp_export_participants_queue`, `sp_whatsapp_schedule_groups` com schema do main. Verificar com `SHOW TABLES`.

## T-004 — Alinhar tipos de colunas em sp_bb_sessions [concluida]
- Refs: US-016, AC-040
- Arquivos: (execução remota via SSH — MySQL)
- Notas: ALTER reply_phone varchar(100)→varchar(255), timeout_instance_id varchar(100)→varchar(255), timeout_at bigint→int(11), timeout_retry_msg longtext→text, timeout_exit_msg longtext→text.

## T-005 — Sincronizar código: Bot_builder.php (correção já aplicada) [concluida]
- Refs: US-017, AC-041
- Arquivos: inc/core/Bot_builder/Controllers/Bot_builder.php
- Notas: Confirmar que o Bot_builder.php do kivozap já está correto (feito na auditoria). Rodar `php -l`.

## T-006 — Sincronizar código: Whatsapp_bulk [concluida]
- Refs: US-017, AC-042
- Arquivos: inc/core/Whatsapp_bulk/Controllers/Whatsapp_bulk.php
- Notas: Copiar do main para kivozap via SCP. Verificar diff = 0. Rodar `php -l`.

## T-007 — Sincronizar código: Whatsapp_export_participants [concluida]
- Refs: US-017, AC-042
- Arquivos: inc/core/Whatsapp_export_participants/Controllers/Whatsapp_export_participants.php
- Notas: Copiar do main para kivozap via SCP. Verificar diff = 0. Rodar `php -l`.

## T-008 — Sincronizar código: Todos os controllers restantes [concluida]
- Refs: US-017, AC-043
- Arquivos: inc/core/*/Controllers/*.php
- Notas: Para cada módulo, comparar controller do main vs kivozap. Copiar arquivos com diferenças. Verificar diff = 0. Rodar `php -l`.

## T-009 — Verificar isolamento: sem reencaminhamento entre plataformas [concluida]
- Refs: US-018, AC-044
- Arquivos: inc/core/Whatsapp_webhook/Controllers/Whatsapp_webhook.php
- Notas: Verificar Forwarding DISABLED. Verificar ausência de child_endpoints e URLs de outros sistemas.

## T-010 — Verificar isolamento: credenciais próprias preservadas [concluida]
- Refs: US-018, AC-045
- Arquivos: .env
- Notas: Verificar que .env mantém db_abner_sql, kivozap.com.br. Sem referências a db_zapmatic_sql ou zapmatic.tec.br.

## T-011 — Verificar isolamento: processos independentes [concluida]
- Refs: US-018, AC-046
- Arquivos: (execução remota via SSH)
- Notas: Verificar conexões ESTABLISHED/CLOSE-WAIT para 10.0.0.14 e 168.75.102.17. Verificar crontab. Apache/PHP-FPM independentes.

## T-012 — Reiniciar serviços do kivozap [concluida]
- Refs: US-017, AC-041
- Arquivos: (execução remota via SSH)
- Notas: Reiniciar PHP-FPM e Apache. Verificar que subiram sem erros.

## T-013 — Teste: Flow builder funcional [concluida]
- Refs: US-019, AC-047
- Arquivos: inc/core/Bot_builder/
- Notas: GET bot-builder, bot-builder/1/editor, bot-builder/create → HTTP 200. Sem parse errors no log.

## T-014 — Teste: Webhook funcional [concluida]
- Refs: US-019, AC-048
- Arquivos: inc/core/Whatsapp_webhook/
- Notas: POST whatsapp_webhook com payload vazio e válido → HTTP 200. Tempo < 5s.

## T-015 — Teste: Bulk/mass messaging [concluida]
- Refs: US-019, AC-049
- Arquivos: inc/core/Whatsapp_bulk/
- Notas: GET whatsapp-bulk → HTTP 200. Página de disparo carrega com opções de contatos e grupos.

## T-016 — Teste: Exportação de participantes [concluida]
- Refs: US-019, AC-050
- Arquivos: inc/core/Whatsapp_export_participants/
- Notas: GET whatsapp-export-participants → HTTP 200. Filtros (self, admins) disponíveis.

## T-017 — Teste: Logs sem erros PHP [concluida]
- Refs: US-019, AC-051
- Arquivos: writable/logs/
- Notas: Verificar writable/logs/*.log. Ausência de erros de sintaxe, fatal errors, warnings.

## T-018 — Teste: Cron jobs independentes [concluida]
- Refs: US-019, AC-052
- Arquivos: (execução remota via SSH)
- Notas: Verificar crontab. Sem referências a zapmatic.tec.br ou 10.0.0.14.

## T-019 — Auditoria final: onp-spec audit [concluida]
- Refs: US-016, US-017, US-018, US-019
- Arquivos: .spec/verification/sync-main-kivozap.json
- Notas: Rodar onp-spec verify e onp-spec audit --ci. Colar saída.
