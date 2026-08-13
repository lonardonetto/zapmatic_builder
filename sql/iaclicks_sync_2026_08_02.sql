-- =============================================================================
-- Migration: Sync IaClicks DB (sql_iaclicks_db) with Main DB (db_zapmatic_sql)
-- Date: 2026-08-02
-- Source: schema_completo.sql (2026-07-24) + migrations/001-006 + module migrations
-- Safe: All statements use IF NOT EXISTS or information_schema checks
-- =============================================================================

-- -------------------------------------------------------
-- 1. DROP obsolete AI tables (migration 001)
-- -------------------------------------------------------
DROP TABLE IF EXISTS `sp_ai_prompt_categories`;
DROP TABLE IF EXISTS `sp_ai_prompt_templates`;
DROP TABLE IF EXISTS `sp_ai_settings`;
DROP TABLE IF EXISTS `sp_whatsapp_ai`;

-- -------------------------------------------------------
-- 2. CREATE Bot Builder tables (migration 20260605_bot_builder)
--    Includes autorespond, session_timeout, debounce columns
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_bot_builders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `trigger_keywords` text COLLATE utf8mb4_unicode_ci,
  `enable_keyword` text COLLATE utf8mb4_unicode_ci,
  `stop_keyword` text COLLATE utf8mb4_unicode_ci,
  `bot_enabled` tinyint(1) DEFAULT '1',
  `keyword_match_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'contains',
  `chat_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `autorespond` tinyint(1) DEFAULT '0',
  `autorespond_delay` int(11) DEFAULT '60',
  `session_timeout` int(11) DEFAULT '60',
  `start_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debounce_seconds` int(11) DEFAULT '1',
  `debounce_max_seconds` int(11) DEFAULT '5',
  `status` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_status` (`status`),
  KEY `idx_team_status` (`team_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_blocks` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci,
  `pos_x` int(11) DEFAULT '0',
  `pos_y` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_edges` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `from_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_from` (`from_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_integrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bot_instance` (`bot_id`,`instance_id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_instance` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) DEFAULT NULL,
  `instance_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context` longtext COLLATE utf8mb4_unicode_ci,
  `is_completed` tinyint(1) DEFAULT '0',
  `bot_status` enum('active','human_handoff') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `autorespond_last_at` datetime DEFAULT NULL,
  `timeout_at` bigint(20) DEFAULT NULL,
  `timeout_retries_done` int(11) DEFAULT '0',
  `timeout_max_retries` int(11) DEFAULT '3',
  `timeout_retry_msg` longtext,
  `timeout_exit_msg` longtext,
  `timeout_instance_id` varchar(100) DEFAULT NULL,
  `reply_phone` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_instance_phone` (`instance_id`,`phone`),
  KEY `idx_session_timeout` (`is_completed`,`bot_status`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_template_usage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fad fa-robot',
  `icon_color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '#25d366',
  `schema_json` longtext COLLATE utf8mb4_unicode_ci,
  `is_premium` tinyint(1) DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `use_count` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `seed_version` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_category` (`status`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_versions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) DEFAULT NULL,
  `version` int(11) DEFAULT '1',
  `snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. CREATE sp_bb_message_buffer (migration 005)
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_bb_message_buffer` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `instance_id` varchar(100) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `phone` varchar(100) NOT NULL,
  `reply_phone` varchar(100) DEFAULT NULL,
  `messages` longtext,
  `first_message` longtext,
  `first_at` datetime DEFAULT NULL,
  `last_at` datetime DEFAULT NULL,
  `debounce_seconds` int(11) DEFAULT '1',
  `debounce_max_seconds` int(11) DEFAULT '5',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_last_at` (`last_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 4. CREATE WhatsApp Flows tables (phase 1+2+3 combined)
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_whatsapp_flow_endpoints` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waba_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint_uri` text COLLATE utf8mb4_unicode_ci,
  `endpoint_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_configured',
  `public_key_fingerprint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_key_uploaded` tinyint(1) NOT NULL DEFAULT '0',
  `private_key_path` text COLLATE utf8mb4_unicode_ci,
  `app_secret_verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_meta_error` text COLLATE utf8mb4_unicode_ci,
  `last_sync_at` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_team_account` (`team_id`,`account_id`),
  KEY `idx_account_ids` (`account_ids`),
  KEY `idx_phone_number_id` (`phone_number_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_whatsapp_flows` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waba_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_flow_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cloud_api',
  `status_local` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `status_meta` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `json_version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_api_version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_channel_uri` text COLLATE utf8mb4_unicode_ci,
  `categories_json` longtext COLLATE utf8mb4_unicode_ci,
  `flow_json` longtext COLLATE utf8mb4_unicode_ci,
  `preview_data` longtext COLLATE utf8mb4_unicode_ci,
  `builder_state` longtext COLLATE utf8mb4_unicode_ci,
  `health_status` longtext COLLATE utf8mb4_unicode_ci,
  `preview_url` text COLLATE utf8mb4_unicode_ci,
  `preview_expires_at` int(11) DEFAULT NULL,
  `last_meta_error` text COLLATE utf8mb4_unicode_ci,
  `published_at` int(11) DEFAULT NULL,
  `last_sync_at` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_team_ids` (`team_id`,`ids`),
  KEY `idx_team_account` (`team_id`,`account_id`),
  KEY `idx_account_ids` (`account_ids`),
  KEY `idx_meta_flow` (`meta_flow_id`),
  KEY `idx_status` (`team_id`,`status_local`,`status_meta`),
  KEY `idx_endpoint` (`endpoint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_whatsapp_flow_assets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `flow_id` int(11) DEFAULT NULL,
  `meta_flow_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_asset_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_asset_handle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `mime_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_path` text COLLATE utf8mb4_unicode_ci,
  `public_url` text COLLATE utf8mb4_unicode_ci,
  `checksum` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `last_meta_error` text COLLATE utf8mb4_unicode_ci,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_flow` (`flow_id`,`team_id`),
  KEY `idx_meta_flow` (`meta_flow_id`),
  KEY `idx_asset_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_whatsapp_flow_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) DEFAULT NULL,
  `flow_id` int(11) DEFAULT NULL,
  `endpoint_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instance_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direction` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chat_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flow_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci,
  `response` longtext COLLATE utf8mb4_unicode_ci,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_team_event` (`team_id`,`event_type`,`created`),
  KEY `idx_flow` (`flow_id`,`created`),
  KEY `idx_account` (`account_id`,`account_ids`),
  KEY `idx_message` (`message_id`),
  KEY `idx_token` (`flow_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. ALTER sp_whatsapp_schedules - add missing columns
-- -------------------------------------------------------

SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_whatsapp_schedules' AND column_name='schedule_weekdays');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE sp_whatsapp_schedules ADD COLUMN schedule_weekdays TEXT NULL AFTER schedule_time', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_whatsapp_schedules' AND column_name='skip_team_holidays');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE sp_whatsapp_schedules ADD COLUMN skip_team_holidays TINYINT(1) NOT NULL DEFAULT 0 AFTER schedule_weekdays', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_whatsapp_schedules' AND column_name='gateway_mode');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE sp_whatsapp_schedules ADD COLUMN gateway_mode VARCHAR(20) DEFAULT ''auto'' AFTER skip_team_holidays', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_whatsapp_schedules' AND column_name='gateway_overrides');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE sp_whatsapp_schedules ADD COLUMN gateway_overrides TEXT NULL AFTER gateway_mode', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_whatsapp_schedules' AND column_name='cloud_parallel_enabled');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE sp_whatsapp_schedules ADD COLUMN cloud_parallel_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER max_delay', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_whatsapp_schedules' AND column_name='cloud_parallel_level');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE sp_whatsapp_schedules ADD COLUMN cloud_parallel_level SMALLINT NOT NULL DEFAULT 0 AFTER cloud_parallel_enabled', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------
-- 6. CREATE sp_whatsapp_team_holidays
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_whatsapp_team_holidays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` int(11) NOT NULL,
  `changed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_holiday_date` (`team_id`,`holiday_date`),
  KEY `idx_team_holiday_created` (`team_id`,`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 7. CREATE sp_whatsapp_cloud_api_config
-- -------------------------------------------------------

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

-- -------------------------------------------------------
-- 8. CREATE sp_whatsapp_cloud_dispatches
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_whatsapp_cloud_dispatches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `phone_number_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone_id` int(11) DEFAULT NULL,
  `raw_phone` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_phone` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_no` int(11) NOT NULL DEFAULT '0',
  `status` enum('queued','processing','retry_wait','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `wa_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT '0',
  `error_code` int(11) DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `last_attempt_at` int(11) DEFAULT NULL,
  `next_attempt_at` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedule_normalized_phone` (`schedule_id`,`normalized_phone`),
  KEY `idx_schedule_status` (`schedule_id`,`status`),
  KEY `idx_account_created` (`account_id`,`created`),
  KEY `idx_schedule_next_attempt` (`schedule_id`,`next_attempt_at`),
  KEY `idx_contact_phone_id` (`contact_phone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 9. CREATE sp_whatsapp_gateways
-- -------------------------------------------------------

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

-- -------------------------------------------------------
-- 10. ALTER sp_whatsapp_callresponder - add auto_reject
-- -------------------------------------------------------

SET @col_exists := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_whatsapp_callresponder' AND column_name='auto_reject');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE sp_whatsapp_callresponder ADD COLUMN auto_reject TINYINT(1) NOT NULL DEFAULT 0 AFTER send_to', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -------------------------------------------------------
-- 11. CREATE Call Campaign tables
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_call_audios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `team_id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `original_name` varchar(255) DEFAULT NULL,
    `file_path` varchar(500) NOT NULL,
    `duration_seconds` int(11) DEFAULT '0',
    `format` varchar(10) DEFAULT 'mp3',
    `file_size_bytes` bigint(20) DEFAULT '0',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sp_call_campaigns` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `team_id` int(11) NOT NULL,
    `instance_id` varchar(50) NOT NULL,
    `audio_id` int(11) DEFAULT NULL,
    `name` varchar(200) NOT NULL,
    `status` enum('draft','scheduled','running','paused','completed','failed') DEFAULT 'draft',
    `max_concurrent` int(11) DEFAULT '1',
    `delay_between_calls` int(11) DEFAULT '30',
    `delay_min` int(11) DEFAULT '10',
    `delay_max` int(11) DEFAULT '60',
    `timeout_ring` int(11) DEFAULT '30',
    `record_response` tinyint(1) DEFAULT '0',
    `schedule_start` datetime DEFAULT NULL,
    `schedule_end` datetime DEFAULT NULL,
    `total_leads` int(11) DEFAULT '0',
    `calls_made` int(11) DEFAULT '0',
    `calls_answered` int(11) DEFAULT '0',
    `calls_no_answer` int(11) DEFAULT '0',
    `calls_busy` int(11) DEFAULT '0',
    `calls_failed` int(11) DEFAULT '0',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `schedule_time` varchar(255) DEFAULT NULL,
    `schedule_weekdays` varchar(255) DEFAULT NULL,
    `skip_team_holidays` tinyint(1) DEFAULT '0',
    `timezone` varchar(100) DEFAULT NULL,
    `call_mode` varchar(20) DEFAULT 'fila',
    `instance_ids` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_team_status` (`team_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sp_call_leads` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `phone` varchar(30) NOT NULL,
    `name` varchar(100) DEFAULT '',
    `status` enum('pending','ringing','answered','no_answer','busy','failed','cancelled') DEFAULT 'pending',
    `call_id` varchar(100) DEFAULT NULL,
    `started_at` datetime DEFAULT NULL,
    `answered_at` datetime DEFAULT NULL,
    `ended_at` datetime DEFAULT NULL,
    `duration_seconds` int(11) DEFAULT '0',
    `response_audio` varchar(500) DEFAULT NULL,
    `error_message` varchar(500) DEFAULT NULL,
    `retry_count` int(11) DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_campaign_status` (`campaign_id`,`status`),
    KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 12. CREATE sp_connection_links
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_connection_links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `team_id` int(11) NOT NULL,
    `instance_id` varchar(50) NOT NULL,
    `token` char(36) NOT NULL,
    `client_name` varchar(100) DEFAULT '',
    `status` enum('pending','used','expired') DEFAULT 'pending',
    `expires_at` datetime NOT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `used_at` datetime DEFAULT NULL,
    `connected_phone` varchar(30) DEFAULT '',
    `connected_name` varchar(100) DEFAULT '',
    `connected_avatar` varchar(500) DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_token` (`token`),
    KEY `idx_team_status` (`team_id`,`status`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 13. CREATE async engine tables
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_message_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `bot_id` int(10) unsigned NOT NULL,
  `action_type` enum('send_message','api_call','delay_resume') NOT NULL,
  `payload` json NOT NULL,
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `send_at` int(10) unsigned NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT '3',
  `error_log` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_queue_polling` (`status`,`send_at`),
  KEY `idx_queue_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_campaign_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) unsigned NOT NULL,
  `instance_id` int(10) unsigned NOT NULL,
  `recipient_phone` varchar(30) NOT NULL,
  `payload` json NOT NULL,
  `status` enum('pending','processing','sent','failed','rate_limited') NOT NULL DEFAULT 'pending',
  `send_at` int(10) unsigned NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `max_attempts` tinyint(3) unsigned NOT NULL DEFAULT '3',
  `error_log` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_polling` (`status`,`instance_id`,`send_at`),
  KEY `idx_campaign_reference` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 14. CREATE Google Maps scraper tables
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_gmscraper_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `target_phonebook` varchar(32) DEFAULT NULL,
  `limit_leads` int(11) DEFAULT '100',
  `delay_seconds` int(11) DEFAULT '30',
  `status` int(1) DEFAULT '0' COMMENT '0=pending, 1=running, 2=completed, 3=error, 4=paused',
  `current_count` int(11) DEFAULT '0',
  `error_msg` text,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `ddi` varchar(5) DEFAULT '55',
  `proxy` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sp_gmscraper_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `rating` varchar(10) DEFAULT NULL,
  `reviews` varchar(20) DEFAULT NULL,
  `address` text,
  `website` varchar(255) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_idx` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 15. CREATE Landing Pages tables
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_landing_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) NOT NULL,
  `team_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `page_type` varchar(50) NOT NULL DEFAULT 'custom',
  `is_home` tinyint(1) DEFAULT '0',
  `is_published` tinyint(1) DEFAULT '1',
  `theme` varchar(50) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_slug` (`slug`),
  KEY `team_id` (`team_id`),
  KEY `slug` (`slug`),
  KEY `page_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sp_landing_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `block_type` varchar(50) NOT NULL,
  `sort_order` int(11) DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `data` longtext,
  `settings` longtext,
  `is_active` tinyint(1) DEFAULT '1',
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 16. CREATE System infrastructure tables
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_system_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sp_system_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `description` text,
  `applied_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 17. CREATE WhatsApp message status (if missing)
-- -------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sp_whatsapp_message_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `campaign_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schedule_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `to_number` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wa_message_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('sent','delivered','read','failed','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `last_status_at` int(11) NOT NULL,
  `meta_error_code` int(11) DEFAULT NULL,
  `meta_error_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_error_details` text COLLATE utf8mb4_unicode_ci,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule` (`schedule_id`,`team_id`),
  KEY `idx_wa_message_id` (`wa_message_id`),
  KEY `idx_status` (`status`,`last_status_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- DONE.
-- Tabelas criadas: 30
-- Colunas adicionadas: 7
-- Tabelas removidas: 4
-- -------------------------------------------------------
