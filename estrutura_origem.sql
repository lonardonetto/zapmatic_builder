/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.22-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: 127.0.0.1    Database: db_zapmatic_sql
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `sp_accounts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_accounts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(255) DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  `social_network` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `login_type` int(11) DEFAULT NULL,
  `can_post` int(1) DEFAULT NULL,
  `pid` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `token` text,
  `avatar` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `tmp` text,
  `data` mediumtext,
  `proxy` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_ai_prompt_categories`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_prompt_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `desc` varchar(500) DEFAULT NULL,
  `icon` varchar(150) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_ai_prompt_templates`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_ai_prompt_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `content` text,
  `status` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1106 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_blocks`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=389 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bb_template_usage`
--

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

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `desc` text,
  `content` longtext,
  `tags` text,
  `img` varchar(255) DEFAULT NULL,
  `status` int(1) DEFAULT '1',
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `internal` int(11) DEFAULT '0',
  `show_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_bot_builders`
--

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

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_captions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(255) NOT NULL,
  `team_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `changed` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_coinpayments_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_coinpayments_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `plan_by` int(11) DEFAULT NULL,
  `txn_id` varchar(255) DEFAULT NULL,
  `coin_amount` float DEFAULT NULL,
  `amount` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_coupons`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_coupons` (
  `id` int(11) NOT NULL,
  `ids` varchar(32) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(32) DEFAULT NULL,
  `by` int(11) DEFAULT '1',
  `price` float DEFAULT NULL,
  `expiration_date` int(11) DEFAULT NULL,
  `plans` text,
  `status` int(11) DEFAULT '1',
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_faqs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` longtext,
  `status` int(1) DEFAULT '1',
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_files`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` mediumtext,
  `is_folder` int(1) NOT NULL DEFAULT '0',
  `pid` int(11) DEFAULT '0',
  `team_id` int(11) DEFAULT NULL,
  `name` mediumtext,
  `file` mediumtext,
  `type` mediumtext,
  `extension` mediumtext,
  `detect` text,
  `size` float DEFAULT NULL,
  `is_image` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `note` mediumtext,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1035 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_groups`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `data` longtext,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_language`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `slug` varchar(32) DEFAULT NULL,
  `text` text,
  `custom` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20501 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_language_category`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_language_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `dir` varchar(3) NOT NULL,
  `is_default` int(1) DEFAULT NULL,
  `auto_translate` varchar(32) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_license_billings`
--

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

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_midtrans_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `user` int(11) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `plan_by` int(11) DEFAULT NULL,
  `txn_id` varchar(255) DEFAULT NULL,
  `amount` text,
  `status` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_options`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` longtext NOT NULL,
  `value` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=314 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_payment_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_payment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `plan` int(11) DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `by` int(1) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=125 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_payment_subscriptions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_payment_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` text,
  `uid` int(11) DEFAULT NULL,
  `plan` int(11) DEFAULT NULL,
  `by` int(1) DEFAULT NULL,
  `type` text,
  `subscription_id` text,
  `customer_id` text,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_plans`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_plans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  `type` int(11) DEFAULT NULL,
  `price_monthly` float DEFAULT NULL,
  `price_annually` float DEFAULT NULL,
  `plan_type` int(1) DEFAULT NULL,
  `number_accounts` int(11) DEFAULT NULL,
  `cloud_api_enabled` int(1) DEFAULT '1',
  `cloud_api_accounts` int(11) DEFAULT '-1',
  `trial_day` float DEFAULT NULL,
  `featured` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `permissions` mediumtext,
  `data` mediumtext,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_proxies`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_proxies` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT '0',
  `is_system` int(11) DEFAULT NULL,
  `proxy` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `limit` float DEFAULT NULL,
  `plans` varchar(255) DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_purchases`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `item_id` varchar(32) DEFAULT NULL,
  `is_main` int(11) DEFAULT NULL,
  `purchase_code` varchar(64) DEFAULT NULL,
  `version` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_smtp`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_smtp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `server` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `encryption` varchar(32) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_team`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` mediumtext,
  `owner` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `permissions` longtext,
  `data` longtext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_team_member`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_team_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` mediumtext,
  `uid` int(11) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `permissions` longtext,
  `pending` text,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_user_roles`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_user_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `permissions` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` mediumtext,
  `pid` text,
  `is_admin` int(1) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `plan` int(11) DEFAULT NULL,
  `expiration_date` int(11) DEFAULT NULL,
  `timezone` mediumtext,
  `language` varchar(30) DEFAULT NULL,
  `login_type` mediumtext,
  `avatar` varchar(255) DEFAULT NULL,
  `data` mediumtext,
  `status` int(11) DEFAULT NULL,
  `last_login` int(11) DEFAULT NULL,
  `recovery_key` varchar(32) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_ai`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_ai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `instance_id` text NOT NULL,
  `status` int(11) NOT NULL,
  `apikey` text,
  `temperature` text,
  `model` text,
  `key_disable` text,
  `key_enable` text,
  `max_tokens` int(11) NOT NULL,
  `api_status` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_ar_responses`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_ar_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `whatsapp` varchar(255) NOT NULL,
  `instance_id` varchar(255) NOT NULL,
  `last_response` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_whatsapp_instance_id` (`whatsapp`,`instance_id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_autoresponder`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_autoresponder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` text,
  `team_id` int(11) DEFAULT NULL,
  `instance_id` text,
  `type` int(1) DEFAULT NULL,
  `template` int(11) DEFAULT NULL,
  `caption` text,
  `media` longtext,
  `except` longtext,
  `path` text,
  `delay` int(11) DEFAULT NULL,
  `result` text,
  `sent` int(11) DEFAULT NULL,
  `failed` int(11) DEFAULT NULL,
  `send_to` int(1) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_callresponder`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_callresponder` (
  `id` int(11) NOT NULL,
  `ids` text,
  `team_id` int(11) DEFAULT NULL,
  `instance_id` text,
  `type` int(1) DEFAULT NULL,
  `template` int(11) DEFAULT NULL,
  `caption` text,
  `media` longtext,
  `except` longtext,
  `path` text,
  `delay` int(11) DEFAULT NULL,
  `result` text,
  `sent` int(11) DEFAULT NULL,
  `failed` int(11) DEFAULT NULL,
  `send_to` int(1) DEFAULT NULL,
  `auto_reject` tinyint(1) NOT NULL DEFAULT '0',
  `status` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `caption2` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_chatbot`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_chatbot` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` text,
  `name` text,
  `keywords` text,
  `instance_id` text,
  `team_id` int(11) DEFAULT NULL,
  `type_search` int(11) DEFAULT '1',
  `template` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `caption` text,
  `media` text,
  `except` text,
  `run` int(1) DEFAULT '1',
  `sent` int(11) DEFAULT NULL,
  `failed` int(11) DEFAULT NULL,
  `send_to` int(1) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `presenceTime` int(11) NOT NULL DEFAULT '0',
  `presenceType` int(11) NOT NULL DEFAULT '0',
  `nextBot` text,
  `description` text,
  `use_ai` int(11) DEFAULT NULL,
  `is_default` int(11) DEFAULT NULL,
  `save_data` int(11) NOT NULL DEFAULT '0',
  `inputname` text,
  `api_config` longtext,
  `api_url` text,
  `get_api_data` int(11) NOT NULL DEFAULT '1',
  `send_as_voicenotes` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=248 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_cloud_dispatches`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_cloud_dispatches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `phone_number_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone_id` int(11) DEFAULT NULL,
  `raw_phone` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `normalized_phone` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_no` int(11) NOT NULL DEFAULT '0',
  `status` enum('queued','processing','retry_wait','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `wa_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempt_count` int(11) NOT NULL DEFAULT '0',
  `error_code` int(11) DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `last_attempt_at` int(11) DEFAULT NULL,
  `next_attempt_at` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schedule_normalized_phone` (`schedule_id`,`normalized_phone`),
  KEY `idx_schedule_status` (`schedule_id`,`status`),
  KEY `idx_account_created` (`account_id`,`created`),
  KEY `idx_schedule_next_attempt` (`schedule_id`,`next_attempt_at`),
  KEY `idx_contact_phone_id` (`contact_phone_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_contacts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_contacts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_delivery_reports`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_delivery_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `error_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_id` (`message_id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_schedule_id` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_flow_assets`
--

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

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_funnels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `desc` text NOT NULL,
  `order` int(11) NOT NULL,
  `color` varchar(20) NOT NULL,
  `instance_id` text NOT NULL,
  `team_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_history`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` mediumtext COLLATE utf8mb4_unicode_ci,
  `team_id` int(11) NOT NULL,
  `phone` mediumtext COLLATE utf8mb4_unicode_ci,
  `type` mediumtext COLLATE utf8mb4_unicode_ci,
  `message` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int(11) NOT NULL,
  `time_post` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2900 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_livechat`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_livechat` (
  `id` varchar(50) NOT NULL,
  `instance_id` text NOT NULL,
  `pushName` text NOT NULL,
  `messageTimestamp` text,
  `message_type` text,
  `remoteJid` text,
  `text` text NOT NULL,
  `imagePath` text,
  `media` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_message_status`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_message_status` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `campaign_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schedule_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `to_number` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wa_message_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('sent','delivered','read','failed','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `last_status_at` int(11) NOT NULL,
  `meta_error_code` int(11) DEFAULT NULL,
  `meta_error_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_error_details` text COLLATE utf8mb4_unicode_ci,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule` (`schedule_id`,`team_id`),
  KEY `idx_wa_message_id` (`wa_message_id`),
  KEY `idx_status` (`status`,`last_status_at`)
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_messages`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_messages` (
  `id` varchar(50) NOT NULL,
  `instance_id` text NOT NULL,
  `remoteJid` text NOT NULL,
  `contactId` text,
  `participant` text,
  `ack` text,
  `read` tinyint(1) NOT NULL DEFAULT '0',
  `fromMe` tinyint(1) NOT NULL DEFAULT '0',
  `body` text NOT NULL,
  `mediaUrl` text,
  `mediaType` text NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0',
  `createdAt` int(11) NOT NULL,
  `updatedAt` int(11) NOT NULL,
  `dataJson` longtext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_phone_numbers`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_phone_numbers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(15) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `phone` varchar(128) NOT NULL,
  `params` text,
  `is_valid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=116086 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_quick_replies`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_quick_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) NOT NULL,
  `team_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `shortcut` varchar(50) DEFAULT NULL,
  `type` enum('text','file','image','video','audio','pix') NOT NULL DEFAULT 'text',
  `content` text,
  `media_url` varchar(500) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created` int(11) NOT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `shortcut` (`shortcut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_schedules`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_schedules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `accounts` text,
  `next_account` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `type` int(11) DEFAULT '1',
  `template` int(11) DEFAULT NULL,
  `time_post` int(11) DEFAULT NULL,
  `min_delay` int(11) DEFAULT NULL,
  `schedule_time` varchar(255) DEFAULT NULL,
  `schedule_weekdays` text,
  `skip_team_holidays` tinyint(1) NOT NULL DEFAULT '0',
  `timezone` varchar(100) DEFAULT NULL,
  `max_delay` int(11) DEFAULT NULL,
  `cloud_parallel_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `cloud_parallel_level` smallint(6) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `caption` text,
  `media` text,
  `sent` int(11) DEFAULT '0',
  `failed` int(11) DEFAULT '0',
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci,
  `run` int(11) DEFAULT '0',
  `status` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_sessions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `instance_id` varchar(255) DEFAULT NULL,
  `data` longtext,
  `status` int(11) DEFAULT NULL,
  `creds` longtext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1207 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_stats`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `wa_total_sent_by_month` int(11) DEFAULT NULL,
  `wa_total_sent` int(11) DEFAULT NULL,
  `wa_chatbot_count` int(11) DEFAULT NULL,
  `wa_autoresponder_count` int(11) DEFAULT NULL,
  `wa_api_count` int(11) DEFAULT NULL,
  `wa_bulk_total_count` int(11) DEFAULT NULL,
  `wa_bulk_sent_count` int(11) DEFAULT NULL,
  `wa_bulk_failed_count` int(11) DEFAULT NULL,
  `wa_time_reset` int(11) DEFAULT NULL,
  `next_update` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_subscriber`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_subscriber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `instance_id` text NOT NULL,
  `chatid` text NOT NULL,
  `data` longtext,
  `tags` text,
  `kanban_group` text,
  `last_chatbot_id` int(11) DEFAULT NULL,
  `last_response` longtext,
  `last_response_time` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `enabled_chatbot` int(11) NOT NULL DEFAULT '1',
  `kanban_order` int(11) DEFAULT '0',
  `contact_data` longtext,
  `unreadMessages` int(11) DEFAULT '0',
  `lastMessage` text,
  `lastMessageTime` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4417 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_team_holidays`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_team_holidays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `holiday_date` date NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` int(11) NOT NULL,
  `changed` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_team_holiday_date` (`team_id`,`holiday_date`),
  KEY `idx_team_holiday_created` (`team_id`,`created`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_template`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` varchar(32) DEFAULT NULL,
  `team_id` int(11) DEFAULT NULL,
  `type` int(1) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `data` longtext,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1191 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sp_whatsapp_webhook`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sp_whatsapp_webhook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ids` text,
  `team_id` int(11) DEFAULT NULL,
  `instance_id` text,
  `webhook_url` text,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-19 14:31:24
