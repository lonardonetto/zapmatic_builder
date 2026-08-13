-- 006: Colunas de debounce em sp_bot_builders (portavel)
SET @col1 := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_bot_builders' AND column_name='debounce_seconds');
SET @sql1 := IF(@col1 = 0, 'ALTER TABLE sp_bot_builders ADD COLUMN debounce_seconds INT DEFAULT 1', 'SELECT 1');
PREPARE stmt1 FROM @sql1; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @col2 := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='sp_bot_builders' AND column_name='debounce_max_seconds');
SET @sql2 := IF(@col2 = 0, 'ALTER TABLE sp_bot_builders ADD COLUMN debounce_max_seconds INT DEFAULT 5', 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;
