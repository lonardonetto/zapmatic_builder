CREATE TABLE IF NOT EXISTS `sp_whatsapp_message_status` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `schedule_id` INT NOT NULL,
  `account_id` INT NOT NULL,
  `to_number` VARCHAR(20) NOT NULL,
  `wa_message_id` VARCHAR(255) NOT NULL,
  `status` ENUM('sent','delivered','read','failed','deleted') NOT NULL DEFAULT 'sent',
  `last_status_at` INT NOT NULL,
  `meta_error_code` INT DEFAULT NULL,
  `meta_error_title` VARCHAR(255) DEFAULT NULL,
  `meta_error_details` TEXT DEFAULT NULL,
  `created` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule` (`schedule_id`,`team_id`),
  KEY `idx_wa_message_id` (`wa_message_id`),
  KEY `idx_status` (`status`,`last_status_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

