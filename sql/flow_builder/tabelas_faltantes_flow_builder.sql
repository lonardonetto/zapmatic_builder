DROP TABLE IF EXISTS `sp_bb_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bb_blocks` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci,
  `pos_x` int(11) DEFAULT '0',
  `pos_y` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_edges`
--


DROP TABLE IF EXISTS `sp_bb_edges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bb_edges` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `from_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_from` (`from_block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_integrations`
--


DROP TABLE IF EXISTS `sp_bb_integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bb_integrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) NOT NULL,
  `instance_id` int(11) NOT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bot_instance` (`bot_id`,`instance_id`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_instance` (`instance_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_sessions`
--


DROP TABLE IF EXISTS `sp_bb_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bb_sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) DEFAULT NULL,
  `instance_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context` longtext COLLATE utf8mb4_unicode_ci,
  `is_completed` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone`),
  KEY `idx_bot` (`bot_id`),
  KEY `idx_instance_phone` (`instance_id`,`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=406 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_template_usage`
--


DROP TABLE IF EXISTS `sp_bb_template_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bb_template_usage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `bot_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_templates`
--


DROP TABLE IF EXISTS `sp_bb_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bb_templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fad fa-robot',
  `icon_color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '#25d366',
  `schema_json` longtext COLLATE utf8mb4_unicode_ci,
  `is_premium` tinyint(1) DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `use_count` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `seed_version` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_category` (`status`,`category`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_versions`
--


DROP TABLE IF EXISTS `sp_bb_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bb_versions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(11) DEFAULT NULL,
  `version` int(11) DEFAULT '1',
  `snapshot` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bot` (`bot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=935 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_blogs`
--


DROP TABLE IF EXISTS `sp_bot_builders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_bot_builders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `trigger_keywords` text COLLATE utf8mb4_unicode_ci,
  `enable_keyword` text COLLATE utf8mb4_unicode_ci,
  `stop_keyword` text COLLATE utf8mb4_unicode_ci,
  `bot_enabled` tinyint(1) DEFAULT '1',
  `keyword_match_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'contains',
  `chat_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `start_block_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_status` (`status`),
  KEY `idx_team_status` (`team_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_captions`
--


DROP TABLE IF EXISTS `sp_license_billings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_license_billings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` int(10) unsigned NOT NULL,
  `invoice_code` varchar(64) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(8) NOT NULL DEFAULT 'BRL',
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `due_at` int(11) NOT NULL DEFAULT '0',
  `paid_at` int(11) NOT NULL DEFAULT '0',
  `payment_method` varchar(64) DEFAULT NULL,
  `external_reference` varchar(128) DEFAULT NULL,
  `apply_renewal` tinyint(1) NOT NULL DEFAULT '1',
  `renewal_days` int(11) NOT NULL DEFAULT '30',
  `payload` longtext,
  `changed` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_invoice_code_unique` (`invoice_code`),
  KEY `license_billing_status_idx` (`status`),
  KEY `license_billing_license_idx` (`license_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_license_events`
--


DROP TABLE IF EXISTS `sp_license_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_license_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` int(10) unsigned DEFAULT NULL,
  `installation_id` varchar(128) DEFAULT NULL,
  `event_type` varchar(64) NOT NULL,
  `level` varchar(16) NOT NULL DEFAULT 'info',
  `message` text,
  `payload` longtext,
  `created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `license_event_type_idx` (`event_type`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_license_installations`
--


DROP TABLE IF EXISTS `sp_license_installations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_license_installations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license_id` int(10) unsigned NOT NULL,
  `installation_id` varchar(128) NOT NULL,
  `installation_hash` char(64) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `ip` varchar(128) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `machine_hash` char(64) DEFAULT NULL,
  `db_fingerprint` char(64) DEFAULT NULL,
  `token_hash` char(64) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `current_users` int(11) NOT NULL DEFAULT '0',
  `current_team_members` int(11) NOT NULL DEFAULT '0',
  `current_accounts` int(11) NOT NULL DEFAULT '0',
  `usage_payload` longtext,
  `last_usage_at` int(11) NOT NULL DEFAULT '0',
  `first_seen_at` int(11) NOT NULL DEFAULT '0',
  `last_seen_at` int(11) NOT NULL DEFAULT '0',
  `last_error` text,
  `meta` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_installation_unique` (`license_id`,`installation_id`),
  KEY `license_installation_status_idx` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_license_licenses`
--


DROP TABLE IF EXISTS `sp_license_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_license_licenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(64) NOT NULL,
  `activation_code` varchar(120) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `product_name` varchar(120) DEFAULT 'Zapmatic',
  `allowed_domain` varchar(255) DEFAULT NULL,
  `allowed_ip` varchar(128) DEFAULT NULL,
  `allowed_hostname` varchar(255) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'active',
  `max_activations` int(11) NOT NULL DEFAULT '1',
  `heartbeat_interval` int(11) NOT NULL DEFAULT '300',
  `grace_period_minutes` int(11) NOT NULL DEFAULT '1440',
  `expires_at` int(11) NOT NULL DEFAULT '0',
  `warn_days` int(11) NOT NULL DEFAULT '7',
  `max_users` int(11) NOT NULL DEFAULT '0',
  `max_team_members` int(11) NOT NULL DEFAULT '0',
  `max_accounts` int(11) NOT NULL DEFAULT '0',
  `notice_message` text,
  `admin_api_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `admin_api_allowed_ips` text,
  `admin_api_url` text,
  `admin_api_key` varchar(255) DEFAULT NULL,
  `admin_api_hmac_mode` varchar(16) NOT NULL DEFAULT 'optional',
  `admin_api_hmac_secret` varchar(128) DEFAULT NULL,
  `billing_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `billing_cycle` varchar(16) NOT NULL DEFAULT 'monthly',
  `billing_currency` varchar(8) NOT NULL DEFAULT 'BRL',
  `billing_status` varchar(16) NOT NULL DEFAULT 'active',
  `next_due_at` int(11) NOT NULL DEFAULT '0',
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `billing_webhook_secret` varchar(128) DEFAULT NULL,
  `notes` text,
  `meta` longtext,
  `changed` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_ids_unique` (`ids`),
  KEY `license_activation_code_idx` (`activation_code`),
  KEY `license_status_idx` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_license_local`
--


DROP TABLE IF EXISTS `sp_license_local`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_license_local` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `installation_id` varchar(128) DEFAULT NULL,
  `license_ids` varchar(128) DEFAULT NULL,
  `license_token` longtext,
  `token_hash` char(64) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'inactive',
  `domain` varchar(255) DEFAULT NULL,
  `ip` varchar(128) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `machine_hash` char(64) DEFAULT NULL,
  `db_fingerprint` char(64) DEFAULT NULL,
  `app_url` text,
  `heartbeat_interval` int(11) NOT NULL DEFAULT '300',
  `grace_period_minutes` int(11) NOT NULL DEFAULT '1440',
  `last_activated_at` int(11) NOT NULL DEFAULT '0',
  `last_heartbeat_at` int(11) NOT NULL DEFAULT '0',
  `last_valid_at` int(11) NOT NULL DEFAULT '0',
  `blocked_at` int(11) NOT NULL DEFAULT '0',
  `last_error` text,
  `payload` longtext,
  `changed` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_midtrans_history`
--


DROP TABLE IF EXISTS `sp_whatsapp_flow_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_flow_assets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `flow_id` int(11) DEFAULT NULL,
  `meta_flow_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_asset_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_asset_handle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `mime_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_path` text COLLATE utf8mb4_unicode_ci,
  `public_url` text COLLATE utf8mb4_unicode_ci,
  `checksum` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `last_meta_error` text COLLATE utf8mb4_unicode_ci,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_flow` (`flow_id`,`team_id`),
  KEY `idx_meta_flow` (`meta_flow_id`),
  KEY `idx_asset_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_flow_endpoints`
--


DROP TABLE IF EXISTS `sp_whatsapp_flow_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_flow_endpoints` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waba_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint_uri` text COLLATE utf8mb4_unicode_ci,
  `endpoint_status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_configured',
  `public_key_fingerprint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_key_uploaded` tinyint(1) NOT NULL DEFAULT '0',
  `private_key_path` text COLLATE utf8mb4_unicode_ci,
  `app_secret_verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_meta_error` text COLLATE utf8mb4_unicode_ci,
  `last_sync_at` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_team_account` (`team_id`,`account_id`),
  KEY `idx_account_ids` (`account_ids`),
  KEY `idx_phone_number_id` (`phone_number_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_flow_events`
--


DROP TABLE IF EXISTS `sp_whatsapp_flow_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_flow_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) DEFAULT NULL,
  `flow_id` int(11) DEFAULT NULL,
  `endpoint_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instance_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direction` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chat_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flow_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci,
  `response` longtext COLLATE utf8mb4_unicode_ci,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_team_event` (`team_id`,`event_type`,`created`),
  KEY `idx_flow` (`flow_id`,`created`),
  KEY `idx_account` (`account_id`,`account_ids`),
  KEY `idx_message` (`message_id`),
  KEY `idx_token` (`flow_token`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_flows`
--


DROP TABLE IF EXISTS `sp_whatsapp_flows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_flows` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `account_ids` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `waba_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_flow_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endpoint_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cloud_api',
  `status_local` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `status_meta` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `json_version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_api_version` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_channel_uri` text COLLATE utf8mb4_unicode_ci,
  `categories_json` longtext COLLATE utf8mb4_unicode_ci,
  `flow_json` longtext COLLATE utf8mb4_unicode_ci,
  `preview_data` longtext COLLATE utf8mb4_unicode_ci,
  `builder_state` longtext COLLATE utf8mb4_unicode_ci,
  `health_status` longtext COLLATE utf8mb4_unicode_ci,
  `preview_url` text COLLATE utf8mb4_unicode_ci,
  `preview_expires_at` int(11) DEFAULT NULL,
  `last_meta_error` text COLLATE utf8mb4_unicode_ci,
  `published_at` int(11) DEFAULT NULL,
  `last_sync_at` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_team_ids` (`team_id`,`ids`),
  KEY `idx_team_account` (`team_id`,`account_id`),
  KEY `idx_account_ids` (`account_ids`),
  KEY `idx_meta_flow` (`meta_flow_id`),
  KEY `idx_status` (`team_id`,`status_local`,`status_meta`),
  KEY `idx_endpoint` (`endpoint_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_funnels`
--


