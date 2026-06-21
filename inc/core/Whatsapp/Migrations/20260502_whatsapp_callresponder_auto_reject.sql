SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sp_whatsapp_callresponder'
      AND COLUMN_NAME = 'auto_reject'
);

SET @migration_sql := IF(
    @column_exists = 0,
    'ALTER TABLE `sp_whatsapp_callresponder` ADD COLUMN `auto_reject` TINYINT(1) NOT NULL DEFAULT 0 AFTER `send_to`',
    'SELECT 1'
);

PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;
