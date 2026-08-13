CREATE TABLE IF NOT EXISTS `sp_bb_message_buffer` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `instance_id` VARCHAR(100) NOT NULL,
  `account_id` INT DEFAULT NULL,
  `bot_id` INT DEFAULT NULL,
  `phone` VARCHAR(100) NOT NULL,
  `reply_phone` VARCHAR(100) DEFAULT NULL,
  `messages` LONGTEXT DEFAULT NULL,
  `first_message` LONGTEXT DEFAULT NULL,
  `first_at` DATETIME DEFAULT NULL,
  `last_at` DATETIME DEFAULT NULL,
  `debounce_seconds` INT DEFAULT 1,
  `debounce_max_seconds` INT DEFAULT 5,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_phone` (`phone`),
  INDEX `idx_last_at` (`last_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
