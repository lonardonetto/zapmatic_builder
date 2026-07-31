CREATE TABLE IF NOT EXISTS `sp_bot_builders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ids` VARCHAR(255) DEFAULT NULL,
  `team_id` INT(11) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `trigger_keywords` TEXT DEFAULT NULL,
  `enable_keyword` TEXT DEFAULT NULL,
  `stop_keyword` TEXT DEFAULT NULL,
  `bot_enabled` TINYINT(1) DEFAULT 1,
  `keyword_match_type` VARCHAR(20) DEFAULT 'contains',
  `chat_type` VARCHAR(20) DEFAULT 'all',
  `start_block_id` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_status` (`status`),
  KEY `idx_team_status` (`team_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_blocks` (
  `id` VARCHAR(255) NOT NULL,
  `bot_id` INT(11) DEFAULT NULL,
  `type` VARCHAR(50) DEFAULT NULL,
  `data` LONGTEXT DEFAULT NULL,
  `pos_x` INT(11) DEFAULT 0,
  `pos_y` INT(11) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_edges` (
  `id` VARCHAR(255) NOT NULL,
  `bot_id` INT(11) DEFAULT NULL,
  `from_block_id` VARCHAR(255) DEFAULT NULL,
  `to_block_id` VARCHAR(255) DEFAULT NULL,
  `condition_type` VARCHAR(50) DEFAULT NULL,
  `condition_value` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_from` (`from_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_sessions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bot_id` INT(11) DEFAULT NULL,
  `instance_id` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(255) DEFAULT NULL,
  `current_block_id` VARCHAR(255) DEFAULT NULL,
  `context` LONGTEXT DEFAULT NULL,
  `is_completed` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_instance_phone` (`instance_id`, `phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_versions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bot_id` INT(11) DEFAULT NULL,
  `version` INT(11) DEFAULT 1,
  `snapshot` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_templates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `icon` VARCHAR(100) DEFAULT 'fad fa-robot',
  `icon_color` VARCHAR(30) DEFAULT '#25d366',
  `schema_json` LONGTEXT DEFAULT NULL,
  `is_premium` TINYINT(1) DEFAULT 0,
  `price` DECIMAL(10,2) DEFAULT 0,
  `use_count` INT(11) DEFAULT 0,
  `status` TINYINT(1) DEFAULT 1,
  `seed_version` INT(11) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_category` (`status`, `category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_template_usage` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_id` INT(11) DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `bot_id` INT(11) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sp_bb_integrations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bot_id` INT(11) NOT NULL,
  `instance_id` INT(11) NOT NULL,
  `account_ids` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bot_instance` (`bot_id`, `instance_id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_instance` (`instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
