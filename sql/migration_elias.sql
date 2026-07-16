-- =============================================================
-- SQL: Tabelas e colunas que faltam no banco do Elias
-- Comparado com o banco principal (app_zapmatic_app)
-- Data: 2026-07-16
-- =============================================================

-- =============================================================
-- 1. TABELAS COMPLETAMENTE AUSENTES (3)
-- =============================================================

-- Tabela: sp_ai_settings
CREATE TABLE IF NOT EXISTS `sp_ai_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) DEFAULT NULL,
  `openrouter_key` text,
  `openai_key` text,
  `anthropic_key` text,
  `gemini_key` text,
  `mistral_key` text,
  `groq_key` text,
  `deepseek_key` text,
  `perplexity_key` text,
  `together_key` text,
  `default_provider` varchar(50) DEFAULT NULL,
  `default_model` varchar(150) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: sp_whatsapp_cloud_api_config
CREATE TABLE IF NOT EXISTS `sp_whatsapp_cloud_api_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `instance_id` varchar(100) NOT NULL,
  `phone_number_id` varchar(50) NOT NULL,
  `waba_id` varchar(50) NOT NULL,
  `access_token` text NOT NULL,
  `business_id` varchar(50) NOT NULL,
  `verify_token` varchar(255) DEFAULT NULL,
  `is_coexistence` tinyint(1) DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_instance` (`instance_id`),
  KEY `idx_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: sp_whatsapp_gateways
CREATE TABLE IF NOT EXISTS `sp_whatsapp_gateways` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) DEFAULT NULL,
  `instance_id` varchar(100) NOT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'baileys',
  `base_url` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `capabilities_json` text,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `instance_id` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- 2. COLUNAS QUE FALTAM EM TABELAS EXISTENTES (6)
-- =============================================================

-- sp_bb_sessions: faltando 1 coluna
ALTER TABLE `sp_bb_sessions`
  ADD COLUMN `autorespond_last_at` datetime DEFAULT NULL AFTER `updated_at`;

-- sp_bot_builders: faltando 3 colunas
-- Posicao correta: entre chat_type e start_block_id
ALTER TABLE `sp_bot_builders`
  ADD COLUMN `autorespond` tinyint(1) DEFAULT 0 AFTER `chat_type`,
  ADD COLUMN `autorespond_delay` int(11) DEFAULT 60 AFTER `autorespond`,
  ADD COLUMN `session_timeout` int(11) DEFAULT 60 AFTER `autorespond_delay`;

-- sp_whatsapp_schedules: faltando 2 colunas
-- Posicao correta: entre skip_team_holidays e timezone
ALTER TABLE `sp_whatsapp_schedules`
  ADD COLUMN `gateway_mode` varchar(20) DEFAULT 'auto' AFTER `skip_team_holidays`,
  ADD COLUMN `gateway_overrides` text DEFAULT NULL AFTER `gateway_mode`;
