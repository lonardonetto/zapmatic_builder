-- Meta 2026 BSUID Transition Migration
-- Expand columns to support 128-char alphanumeric IDs

-- Update sp_whatsapp_message_status
ALTER TABLE `sp_whatsapp_message_status` MODIFY `to_number` VARCHAR(128) NOT NULL;

-- Update sp_whatsapp_phone_numbers
ALTER TABLE `sp_whatsapp_phone_numbers` MODIFY `phone` VARCHAR(128) NOT NULL;

-- Ensure sp_whatsapp_contacts phone is also expanded if it exists as VARCHAR
-- Some versions use bigint, if so, it MUST be converted to VARCHAR to support BSUID
ALTER TABLE `sp_whatsapp_contacts` MODIFY `phone` VARCHAR(128) NULL;

-- Update autoresponder external_id if present
-- (Optional, based on specific module usage)
-- ALTER TABLE `sp_whatsapp_autoresponder` MODIFY `external_id` VARCHAR(128) NULL;
