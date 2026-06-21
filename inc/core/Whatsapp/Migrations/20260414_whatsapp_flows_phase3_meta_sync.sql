-- WhatsApp Flows - Phase 3
-- Meta sync state for categories and preview links

ALTER TABLE `sp_whatsapp_flows`
ADD COLUMN `categories_json` LONGTEXT DEFAULT NULL AFTER `data_api_version`,
ADD COLUMN `preview_url` TEXT DEFAULT NULL AFTER `health_status`,
ADD COLUMN `preview_expires_at` INT(11) DEFAULT NULL AFTER `preview_url`;
