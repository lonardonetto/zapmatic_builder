-- 008: Fila de clone de grupos (Whatsapp_export_participants)
CREATE TABLE IF NOT EXISTS `sp_clone_group_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ids` VARCHAR(32) NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `account_id` VARCHAR(64) NOT NULL,
  `group_id` VARCHAR(128) NOT NULL,
  `group_name` VARCHAR(255) NULL,
  `target_name` VARCHAR(64) NOT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `total` INT UNSIGNED NOT NULL DEFAULT 0,
  `done` INT UNSIGNED NOT NULL DEFAULT 0,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `error_log` TEXT NULL,
  `new_group_jid` VARCHAR(128) NULL,
  `created` INT UNSIGNED NOT NULL,
  `changed` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_clone_queue_polling` (`status`, `team_id`),
  INDEX `idx_clone_queue_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
