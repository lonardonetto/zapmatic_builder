SET @schedule_weekdays_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sp_whatsapp_schedules'
      AND COLUMN_NAME = 'schedule_weekdays'
);

SET @schedule_weekdays_sql := IF(
    @schedule_weekdays_exists = 0,
    'ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `schedule_weekdays` TEXT NULL AFTER `schedule_time`',
    'SELECT 1'
);

PREPARE schedule_weekdays_stmt FROM @schedule_weekdays_sql;
EXECUTE schedule_weekdays_stmt;
DEALLOCATE PREPARE schedule_weekdays_stmt;

SET @skip_team_holidays_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sp_whatsapp_schedules'
      AND COLUMN_NAME = 'skip_team_holidays'
);

SET @skip_team_holidays_sql := IF(
    @skip_team_holidays_exists = 0,
    'ALTER TABLE `sp_whatsapp_schedules` ADD COLUMN `skip_team_holidays` TINYINT(1) NOT NULL DEFAULT 0 AFTER `schedule_weekdays`',
    'SELECT 1'
);

PREPARE skip_team_holidays_stmt FROM @skip_team_holidays_sql;
EXECUTE skip_team_holidays_stmt;
DEALLOCATE PREPARE skip_team_holidays_stmt;

CREATE TABLE IF NOT EXISTS `sp_whatsapp_team_holidays` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_id` INT NOT NULL,
  `holiday_date` DATE NOT NULL,
  `name` VARCHAR(191) NOT NULL,
  `created` INT NOT NULL,
  `changed` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_holiday_date` (`team_id`, `holiday_date`),
  KEY `idx_team_holiday_created` (`team_id`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
