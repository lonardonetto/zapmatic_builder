SET @cloud_parallel_enabled_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sp_whatsapp_schedules'
      AND COLUMN_NAME = 'cloud_parallel_enabled'
);

SET @cloud_parallel_enabled_sql := IF(
    @cloud_parallel_enabled_exists = 0,
    'ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `cloud_parallel_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `max_delay`',
    'SELECT 1'
);

PREPARE cloud_parallel_enabled_stmt FROM @cloud_parallel_enabled_sql;
EXECUTE cloud_parallel_enabled_stmt;
DEALLOCATE PREPARE cloud_parallel_enabled_stmt;

SET @cloud_parallel_level_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sp_whatsapp_schedules'
      AND COLUMN_NAME = 'cloud_parallel_level'
);

SET @cloud_parallel_level_sql := IF(
    @cloud_parallel_level_exists = 0,
    'ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `cloud_parallel_level` SMALLINT NOT NULL DEFAULT 0 AFTER `cloud_parallel_enabled`',
    'SELECT 1'
);

PREPARE cloud_parallel_level_stmt FROM @cloud_parallel_level_sql;
EXECUTE cloud_parallel_level_stmt;
DEALLOCATE PREPARE cloud_parallel_level_stmt;

CREATE TABLE IF NOT EXISTS `sp_whatsapp_cloud_dispatches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  `account_id` INT DEFAULT NULL,
  `phone_number_id` VARCHAR(64) DEFAULT NULL,
  `contact_phone_id` INT DEFAULT NULL,
  `raw_phone` VARCHAR(128) NOT NULL,
  `normalized_phone` VARCHAR(128) NOT NULL,
  `batch_no` INT NOT NULL DEFAULT 0,
  `status` ENUM('queued','processing','retry_wait','sent','failed') NOT NULL DEFAULT 'queued',
  `wa_message_id` VARCHAR(255) DEFAULT NULL,
  `attempt_count` INT NOT NULL DEFAULT 0,
  `error_code` INT DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `last_attempt_at` INT DEFAULT NULL,
  `next_attempt_at` INT DEFAULT NULL,
  `created` INT NOT NULL,
  `updated` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedule_normalized_phone` (`schedule_id`, `normalized_phone`),
  KEY `idx_schedule_status` (`schedule_id`, `status`),
  KEY `idx_account_created` (`account_id`, `created`),
  KEY `idx_schedule_next_attempt` (`schedule_id`, `next_attempt_at`),
  KEY `idx_contact_phone_id` (`contact_phone_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
