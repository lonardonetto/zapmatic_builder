-- WhatsApp Flows - Phase 3.1
-- Extra official metadata pulled from Meta Cloud API

ALTER TABLE `sp_whatsapp_flows`
ADD COLUMN `data_channel_uri` TEXT DEFAULT NULL AFTER `data_api_version`;
