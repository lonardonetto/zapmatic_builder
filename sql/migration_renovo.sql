-- =============================================================
-- SQL: Tabelas e colunas que faltam no banco do Renovo
-- Comparado com o banco principal (app_zapmatic_app)
-- =============================================================
-- Renovo ja tem as 3 tabelas + sp_bb_sessions + sp_bot_builders
-- So falta sp_whatsapp_schedules (2 colunas)

-- =============================================================
-- 1. COLUNAS QUE FALTAM (2)
-- =============================================================

-- sp_whatsapp_schedules: entre skip_team_holidays e timezone
ALTER TABLE `sp_whatsapp_schedules`
  ADD COLUMN `gateway_mode` varchar(20) DEFAULT 'auto' AFTER `skip_team_holidays`,
  ADD COLUMN `gateway_overrides` text DEFAULT NULL AFTER `gateway_mode`;
