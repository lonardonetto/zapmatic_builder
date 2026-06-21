-- AUTO-GENERATED INCREMENTAL SCHEMA UPDATE
-- Review carefully before execution
-- Found 10 missing tables and 4 missing columns


-- ==========================================
-- MISSING TABLES
-- ==========================================

DROP TABLE IF EXISTS `sp_bb_blocks`;
CREATE TABLE `sp_bb_blocks` (
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


DROP TABLE IF EXISTS `sp_bb_edges`;
CREATE TABLE `sp_bb_edges` (
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


DROP TABLE IF EXISTS `sp_bb_integrations`;
CREATE TABLE `sp_bb_integrations` (
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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sp_bb_sessions`;
CREATE TABLE `sp_bb_sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) DEFAULT NULL,
  `instance_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context` longtext COLLATE utf8mb4_unicode_ci,
  `is_completed` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_instance_phone` (`instance_id`,`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=292 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sp_bb_template_usage`;
CREATE TABLE `sp_bb_template_usage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sp_bb_templates`;
CREATE TABLE `sp_bb_templates` (
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sp_bb_versions`;
CREATE TABLE `sp_bb_versions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) DEFAULT NULL,
  `version` int(11) DEFAULT '1',
  `snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=652 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sp_bot_builders`;
CREATE TABLE `sp_bot_builders` (
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
  `start_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_status` (`status`),
  KEY `idx_team_status` (`team_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sp_whatsapp_cloud_dispatches`;
CREATE TABLE `sp_whatsapp_cloud_dispatches` (
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sp_whatsapp_team_holidays`;
CREATE TABLE `sp_whatsapp_team_holidays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` int(11) NOT NULL,
  `changed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_holiday_date` (`team_id`,`holiday_date`),
  KEY `idx_team_holiday_created` (`team_id`,`created`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ==========================================
-- MISSING COLUMNS
-- ==========================================

ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `schedule_weekdays` text;
ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `skip_team_holidays` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `cloud_parallel_enabled` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `cloud_parallel_level` smallint(6) NOT NULL DEFAULT '0';
