-- WhatsApp Flows - Phase 2.2
-- Additive column for rich visual builder persistence

ALTER TABLE `sp_whatsapp_flows`
ADD COLUMN `builder_state` LONGTEXT DEFAULT NULL AFTER `preview_data`;
