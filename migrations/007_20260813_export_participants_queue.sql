-- 007: Fila de criação de listas de contatos (export participants)
CREATE TABLE IF NOT EXISTS `sp_export_participants_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ids` VARCHAR(32) NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `account_id` VARCHAR(64) NOT NULL,
  `group_id` VARCHAR(128) NOT NULL,
  `group_name` VARCHAR(255) NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `total` INT UNSIGNED NOT NULL DEFAULT 0,
  `done` INT UNSIGNED NOT NULL DEFAULT 0,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `error_log` TEXT NULL,
  `created` INT UNSIGNED NOT NULL,
  `changed` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_export_queue_polling` (`status`, `team_id`),
  INDEX `idx_export_queue_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
