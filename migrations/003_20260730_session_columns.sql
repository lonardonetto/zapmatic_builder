-- 003: Colunas de sessao (handoff humano + timeout)
ALTER TABLE `sp_bb_sessions`
  ADD COLUMN `bot_status` ENUM('active', 'human_handoff') NOT NULL DEFAULT 'active' AFTER `is_completed`,
  ADD INDEX `idx_session_timeout` (`is_completed`, `bot_status`, `updated_at`);
