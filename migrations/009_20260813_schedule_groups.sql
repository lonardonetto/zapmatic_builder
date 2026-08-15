-- 009: Destinos de grupo do disparo em massa (Whatsapp_bulk)
CREATE TABLE IF NOT EXISTS `sp_whatsapp_schedule_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ids` VARCHAR(32) NOT NULL,
  `team_id` INT UNSIGNED NOT NULL,
  `schedule_id` INT UNSIGNED NOT NULL,
  `account_id` VARCHAR(64) NOT NULL,
  `group_jid` VARCHAR(128) NOT NULL,
  `position` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  `error_log` TEXT NULL,
  `created` INT UNSIGNED NOT NULL,
  `changed` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_schedule_groups_offset` (`schedule_id`, `position`),
  INDEX `idx_schedule_groups_team` (`team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
