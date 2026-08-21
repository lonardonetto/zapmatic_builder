-- Feature: relatorio-ligacao-campanha
-- Aditivo e não-destrutivo. Não altera dados existentes.
-- Tabela de timeline por tentativa de ligação + colunas de desfecho rico em sp_call_leads.

CREATE TABLE IF NOT EXISTS sp_call_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT NOT NULL,
  lead_id INT NOT NULL,
  call_id VARCHAR(100) NOT NULL,
  event VARCHAR(40) NOT NULL,
  platform VARCHAR(16) DEFAULT NULL,
  reason VARCHAR(255) DEFAULT NULL,
  detail TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_call_event (call_id, event),
  INDEX idx_lead (lead_id),
  INDEX idx_campaign (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Colunas aditivas em sp_call_leads (ALTER ADD COLUMN é seguro: não remove nada).
ALTER TABLE sp_call_leads
  ADD COLUMN platform VARCHAR(16) DEFAULT NULL AFTER response_audio,
  ADD COLUMN heard_full_audio TINYINT(1) DEFAULT 0 AFTER platform,
  ADD COLUMN hangup_source VARCHAR(24) DEFAULT NULL AFTER heard_full_audio,
  ADD COLUMN ring_duration_seconds INT DEFAULT 0 AFTER hangup_source,
  ADD COLUMN last_error VARCHAR(255) DEFAULT NULL AFTER ring_duration_seconds;
