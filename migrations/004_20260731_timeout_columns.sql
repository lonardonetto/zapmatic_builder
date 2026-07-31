-- 004: Colunas de timeout/reenvio do Bot Builder
ALTER TABLE `sp_bb_sessions`
  ADD COLUMN `timeout_at` BIGINT DEFAULT NULL AFTER `autorespond_last_at`,
  ADD COLUMN `timeout_retries_done` INT DEFAULT 0 AFTER `timeout_at`,
  ADD COLUMN `timeout_max_retries` INT DEFAULT 3 AFTER `timeout_retries_done`,
  ADD COLUMN `timeout_retry_msg` LONGTEXT DEFAULT NULL AFTER `timeout_max_retries`,
  ADD COLUMN `timeout_exit_msg` LONGTEXT DEFAULT NULL AFTER `timeout_retry_msg`,
  ADD COLUMN `timeout_instance_id` VARCHAR(100) DEFAULT NULL AFTER `timeout_exit_msg`,
  ADD COLUMN `reply_phone` VARCHAR(100) DEFAULT NULL AFTER `timeout_instance_id`;
