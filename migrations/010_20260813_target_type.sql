-- 010: Coluna target_type separa DESTINO (contacts|groups) do TIPO de mensagem
ALTER TABLE `sp_whatsapp_schedules`
  ADD COLUMN `target_type` VARCHAR(16) NOT NULL DEFAULT 'contacts' AFTER `contact_id`;
