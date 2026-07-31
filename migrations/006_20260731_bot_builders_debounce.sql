ALTER TABLE `sp_bot_builders` ADD COLUMN IF NOT EXISTS `debounce_seconds` INT DEFAULT 1;
ALTER TABLE `sp_bot_builders` ADD COLUMN IF NOT EXISTS `debounce_max_seconds` INT DEFAULT 5;
