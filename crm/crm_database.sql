
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE TABLE IF NOT EXISTS `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('call','email','meeting','task','note','demo','follow_up') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text,
  `company_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `opportunity_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','overdue') DEFAULT 'scheduled',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `duration_minutes` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `outcome` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `contact_id` (`contact_id`),
  KEY `opportunity_id` (`opportunity_id`),
  KEY `idx_activities_assigned_to` (`assigned_to`),
  KEY `idx_activities_due_date` (`due_date`),
  KEY `idx_activities_status` (`status`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activities_ibfk_3` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activities_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `customer_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_user_id` (`user_id`),
  KEY `idx_activity_logs_created_at` (`created_at`),
  KEY `idx_activity_customer` (`customer_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activity_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `agent_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(50) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(11) NOT NULL,
  `data` json DEFAULT NULL,
  `status` enum('pending','approved','rejected','executed','failed') DEFAULT 'pending',
  `result` json DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agent_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `input_data` json DEFAULT NULL,
  `output_data` json DEFAULT NULL,
  `status` enum('success','error','warning') DEFAULT 'success',
  `error_message` text,
  `customer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agent_name` (`agent_name`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agent_memory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(50) NOT NULL,
  `context_type` varchar(50) NOT NULL,
  `context_id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text,
  `customer_id` int(11) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_agent_context` (`agent_name`,`context_type`,`context_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `agent_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `agent_name` varchar(50) NOT NULL,
  `can_read` tinyint(1) DEFAULT '1',
  `can_suggest` tinyint(1) DEFAULT '1',
  `can_execute` tinyint(1) DEFAULT '0',
  `automation_mode` enum('assisted','semi-auto','autonomous') DEFAULT 'assisted',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_agent` (`customer_id`,`agent_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*!50001 DROP VIEW IF EXISTS `agent_stats`*/;
/*!50001 CREATE VIEW `agent_stats` AS SELECT 
 1 AS `agent_name`,
 1 AS `customer_id`,
 1 AS `date`,
 1 AS `total_actions`,
 1 AS `successful`,
 1 AS `errors`,
 1 AS `success_rate`*/;

CREATE TABLE IF NOT EXISTS `analytics_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(15,2) NOT NULL,
  `metric_date` date NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `opportunity_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `company_id` (`company_id`),
  KEY `opportunity_id` (`opportunity_id`),
  KEY `idx_analytics_metric_date` (`metric_date`),
  CONSTRAINT `analytics_metrics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `analytics_metrics_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `analytics_metrics_ibfk_3` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `related_type` enum('email','activity','quote','company','contact','opportunity') NOT NULL,
  `related_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `attachments_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `automation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `automation_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('sent','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'sent',
  `response` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `automation_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `automation_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL,
  `action_type` enum('email','wait','sms') COLLATE utf8mb4_unicode_ci DEFAULT 'email',
  `content` text COLLATE utf8mb4_unicode_ci,
  `delay_hours` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `automation_id` (`automation_id`),
  CONSTRAINT `automation_steps_ibfk_1` FOREIGN KEY (`automation_id`) REFERENCES `automations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `automations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trigger_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `audience_filter` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `status` enum('active','paused') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `open_rate` float DEFAULT '0',
  `click_rate` float DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cahier_des_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) NOT NULL,
  `project_goals` text NOT NULL,
  `target_audience` text NOT NULL,
  `features` text NOT NULL,
  `platform` varchar(50) NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `deadline` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_user_id` (`user_id`),
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `call_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_type` enum('lead','customer','prospect','other') COLLATE utf8mb4_unicode_ci DEFAULT 'lead',
  `contact_id` int(11) DEFAULT NULL,
  `scheduled_time` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT '30',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `call_type` enum('follow_up','demo','support','sales','other') COLLATE utf8mb4_unicode_ci DEFAULT 'follow_up',
  `status` enum('pending','completed','cancelled','missed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `reminder_sent` tinyint(1) DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `outcome` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_scheduled` (`scheduled_time`),
  KEY `idx_status` (`status`),
  KEY `idx_contact` (`contact_type`,`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `campaign_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `event_type` enum('open','click') NOT NULL,
  `identifier` varchar(255) DEFAULT NULL,
  `url` text,
  `ip` varbinary(16) DEFAULT NULL,
  `ua` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `customer_id` (`customer_id`),
  KEY `event_type` (`event_type`),
  KEY `identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `automation_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `sender_name` varchar(150) DEFAULT NULL,
  `sender_email` varchar(150) DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `email_config_id` int(11) DEFAULT NULL COMMENT 'ID de la configuration email à utiliser',
  `whatsapp_config_id` int(11) DEFAULT NULL COMMENT 'ID de la configuration WhatsApp à utiliser',
  `channel` varchar(20) DEFAULT 'email' COMMENT 'Canal de communication: email, whatsapp, sms',
  `status` varchar(50) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `recipients_emails` text,
  `recipients_count` int(11) DEFAULT '0',
  `recipients` int(11) DEFAULT '0',
  `open_rate` float DEFAULT '0',
  `click_rate` float DEFAULT '0',
  `opens_count` int(11) DEFAULT '0',
  `clicks_count` int(11) DEFAULT '0',
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_automation_id` (`automation_id`),
  KEY `idx_campaigns_email_config` (`email_config_id`),
  KEY `idx_campaigns_whatsapp_config` (`whatsapp_config_id`),
  KEY `idx_campaigns_channel` (`channel`),
  CONSTRAINT `fk_campaigns_automation` FOREIGN KEY (`automation_id`) REFERENCES `automations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_campaigns_email_config` FOREIGN KEY (`email_config_id`) REFERENCES `email_configurations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_campaigns_whatsapp_config` FOREIGN KEY (`whatsapp_config_id`) REFERENCES `whatsapp_configurations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `response_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `response` varchar(255) DEFAULT NULL,
  `conversation_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `response_to` (`response_to`),
  KEY `fk_conversation_id` (`conversation_id`),
  CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`response_to`) REFERENCES `chat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conversation_id` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `industry` varchar(50) DEFAULT NULL,
  `segment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `siret` varchar(14) DEFAULT NULL,
  `vat_number` varchar(20) DEFAULT NULL,
  `employee_count` varchar(20) DEFAULT NULL,
  `annual_revenue` varchar(50) DEFAULT NULL,
  `capital` decimal(15,2) DEFAULT NULL,
  `address` text,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'France',
  `status` enum('prospect','client','partner','inactive') DEFAULT 'prospect',
  `satisfaction` decimal(3,1) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  `satisfaction_score` decimal(3,1) DEFAULT NULL,
  `employees_count` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `interne_customer` tinyint(1) NOT NULL DEFAULT '0',
  `validation_code` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_companies_status` (`status`),
  KEY `idx_companies_assigned_to` (`assigned_to`),
  KEY `idx_companies_segment` (`segment`),
  KEY `idx_companies_customer_id` (`customer_id`),
  CONSTRAINT `companies_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `contact_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `topic` varchar(100) NOT NULL,
  `conversation_id` int(11) DEFAULT NULL,
  `request_type` enum('contact','chat_contact','callback') DEFAULT 'contact',
  `status` enum('pending','contacted','completed','cancelled') DEFAULT 'pending',
  `notes` text,
  `contacted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_id` (`request_id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `contact_requests_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timeline` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_preference` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'email',
  `status` enum('nouveau','en_cours','traite','archive') COLLATE utf8mb4_unicode_ci DEFAULT 'nouveau',
  `is_primary` tinyint(1) DEFAULT '0',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submission_time` int(11) NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_contacts_company_id` (`company_id`),
  KEY `fk_contacts_assigned_to` (`assigned_to`),
  CONSTRAINT `fk_contacts_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_contacts_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) NOT NULL,
  `is_closed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `topic` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `crm_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-bell',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'primary',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `city` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `siren` varchar(20) DEFAULT NULL,
  `siret` varchar(14) DEFAULT NULL,
  `vat_number` varchar(20) DEFAULT NULL,
  `capital` decimal(15,2) DEFAULT NULL,
  `naf` varchar(10) DEFAULT NULL,
  `role` enum('admin','manager','client','partner') DEFAULT 'client',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `postal_code` varchar(20) DEFAULT NULL,
  `validation_code` varchar(20) DEFAULT NULL,
  `powerbi_token` text,
  `powerbi_token_expiry` datetime DEFAULT NULL,
  `powerbi_client_secret` text,
  `powerbi_tenant_id` varchar(255) DEFAULT NULL,
  `powerbi_workspace_id` varchar(255) DEFAULT NULL,
  `powerbi_report_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `daily_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_by_agent` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dns_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'A, AAAA, CNAME, MX, TXT, etc.',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'subdomain ou @',
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ttl` int(10) unsigned DEFAULT '3600',
  `priority` int(10) unsigned DEFAULT NULL COMMENT 'For MX records',
  `synced` tinyint(1) DEFAULT '0',
  `last_sync` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `domain_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'purchase, renew, transfer, dns_update, ssl_install, etc.',
  `status` enum('success','failed','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `details` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON with additional info',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_domain_id` (`domain_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `domain_ssl` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `domain_id` int(10) unsigned NOT NULL,
  `provider` enum('letsencrypt','cloudflare','custom') COLLATE utf8mb4_unicode_ci DEFAULT 'letsencrypt',
  `certificate` text COLLATE utf8mb4_unicode_ci,
  `private_key` text COLLATE utf8mb4_unicode_ci,
  `chain` text COLLATE utf8mb4_unicode_ci,
  `issued_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT '1',
  `status` enum('pending','active','expired','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `last_renewal_attempt` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `domain_id` (`domain_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `domain_verifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_domain_id` int(10) unsigned NOT NULL,
  `method` enum('dns_txt','dns_cname','meta_tag','file_upload') COLLATE utf8mb4_unicode_ci DEFAULT 'dns_txt',
  `token` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci COMMENT 'Expected value for verification',
  `status` enum('pending','verified','failed','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `verified_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `attempts` int(10) unsigned DEFAULT '0',
  `last_check` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_domain_id` (`project_domain_id`),
  KEY `idx_status` (`status`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `registrar` varchar(50) NOT NULL DEFAULT 'godaddy',
  `domain` varchar(255) NOT NULL,
  `status` enum('pending','active','suspended','expired','owned') DEFAULT 'pending',
  `auto_renew` tinyint(1) DEFAULT '0',
  `purchase_date` datetime DEFAULT NULL,
  `expiry_date` datetime DEFAULT NULL,
  `nameservers` text COMMENT 'JSON array of nameservers',
  `dns_managed` tinyint(1) DEFAULT '1',
  `ssl_enabled` tinyint(1) DEFAULT '0',
  `ssl_provider` varchar(50) DEFAULT NULL,
  `ssl_expiry` datetime DEFAULT NULL,
  `purchased_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `whois` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_domain` (`user_id`,`domain`),
  KEY `idx_domain` (`domain`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expiry_date` (`expiry_date`),
  CONSTRAINT `domains_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `email_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filesize` int(11) NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_inline` tinyint(1) DEFAULT '0',
  `content_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_id` (`email_id`),
  CONSTRAINT `email_attachments_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_campaign_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `status` enum('pending','sent','opened','clicked','bounced','unsubscribed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `bounced_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `email_campaign_recipients_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `template_id` int(11) DEFAULT NULL,
  `status` enum('draft','scheduled','sending','sent','paused','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `recipients_count` int(11) DEFAULT '0',
  `opened_count` int(11) DEFAULT '0',
  `clicked_count` int(11) DEFAULT '0',
  `bounced_count` int(11) DEFAULT '0',
  `unsubscribed_count` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  CONSTRAINT `email_campaigns_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'gmail, outlook, hostinger, custom',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Encrypted password (NULL if OAuth)',
  `imap_server` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imap_port` int(11) DEFAULT '993',
  `use_ssl` tinyint(1) NOT NULL DEFAULT '1',
  `mailbox` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_server` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_port` int(11) DEFAULT '587',
  `sync_leads` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `last_sync` datetime DEFAULT NULL,
  `last_successful_sync_at` datetime DEFAULT NULL,
  `last_imap_uid` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `oauth_provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'google, microsoft, none',
  `oauth_access_token` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Encrypted OAuth access token',
  `oauth_refresh_token` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Encrypted OAuth refresh token',
  `oauth_token_expires_at` datetime DEFAULT NULL COMMENT 'When the access token expires',
  `oauth_scope` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Granted OAuth scopes',
  `token_encrypted` tinyint(1) DEFAULT '1' COMMENT 'Whether tokens are encrypted',
  `last_error` text COLLATE utf8mb4_unicode_ci COMMENT 'Last authentication/sync error',
  `error_count` int(11) DEFAULT '0' COMMENT 'Consecutive error count for backoff',
  `last_error_at` datetime DEFAULT NULL COMMENT 'When last error occurred',
  `webhook_channel` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Webhook channel ID (Gmail Pub/Sub or Microsoft subscription)',
  `labels_to_sync` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retention_days` int(11) DEFAULT '0',
  `webhook_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_expires_at` datetime DEFAULT NULL COMMENT 'When webhook subscription expires',
  `sync_enabled` tinyint(1) DEFAULT '1' COMMENT 'Enable/disable sync for this account',
  `connection_method` enum('oauth','password','app_password') COLLATE utf8mb4_unicode_ci DEFAULT 'password' COMMENT 'How user authenticated',
  `smtp_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_pass` text COLLATE utf8mb4_unicode_ci,
  `smtp_from` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`customer_id`,`email`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_provider` (`provider`),
  KEY `idx_oauth_provider` (`oauth_provider`),
  KEY `idx_token_expires` (`oauth_token_expires_at`),
  KEY `idx_webhook_expires` (`webhook_expires_at`),
  KEY `idx_sync_enabled` (`sync_enabled`),
  KEY `idx_last_imap_uid` (`last_imap_uid`),
  KEY `idx_oauth_token_expires_at` (`oauth_token_expires_at`),
  CONSTRAINT `email_configurations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Email account configurations with OAuth2 support for Gmail/Microsoft and IMAP/SMTP for others';

CREATE TABLE IF NOT EXISTS `email_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API endpoint or action',
  `request_count` int(11) DEFAULT '0',
  `window_start` datetime NOT NULL,
  `window_end` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_rate_limit` (`config_id`,`endpoint`,`window_start`),
  KEY `idx_window` (`window_end`),
  CONSTRAINT `email_rate_limits_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `email_configurations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_sync_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `status` enum('pending','running','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `emails_fetched` int(11) DEFAULT '0',
  `leads_extracted` int(11) DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_config_id` (`config_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `email_sync_logs_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `email_configurations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_sync_logs_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_sync_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `priority` enum('high','normal','low') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `retry_count` int(11) DEFAULT '0',
  `max_retries` int(11) DEFAULT '3',
  `scheduled_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_scheduled` (`scheduled_at`),
  KEY `idx_config` (`config_id`),
  CONSTRAINT `email_sync_queue_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `email_configurations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_sync_queue_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_category` (`category`),
  CONSTRAINT `email_templates_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) DEFAULT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'google, microsoft',
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'message_received, etc.',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Raw webhook payload (JSON)',
  `processed` tinyint(1) DEFAULT '0',
  `processed_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `config_id` (`config_id`),
  KEY `idx_processed` (`processed`),
  KEY `idx_provider` (`provider`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `email_webhook_logs_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `email_configurations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `email_id` varchar(255) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `imap_uid` bigint(20) DEFAULT NULL,
  `message_id_header` varchar(512) DEFAULT NULL,
  `in_reply_to` varchar(512) DEFAULT NULL,
  `references_header` text,
  `mailbox` varchar(255) DEFAULT 'INBOX',
  `activity_id` int(11) DEFAULT NULL,
  `from_email` varchar(255) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `cc_email` text,
  `bcc_email` text,
  `subject` varchar(500) NOT NULL,
  `body_html` text,
  `body_text` text,
  `message_id` varchar(255) DEFAULT NULL,
  `thread_id` varchar(255) DEFAULT NULL,
  `is_outbound` tinyint(1) DEFAULT '1',
  `is_read` tinyint(1) DEFAULT '0',
  `is_replied` tinyint(1) DEFAULT '0',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('new','inbox','archive','trash','spam') DEFAULT 'inbox',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ai_analyzed` tinyint(1) DEFAULT '0',
  `ai_summary` text,
  `ai_type` varchar(50) DEFAULT NULL,
  `ai_priority` varchar(20) DEFAULT NULL,
  `ai_sentiment` varchar(20) DEFAULT NULL,
  `ai_action_id` int(11) DEFAULT NULL,
  `email_date` datetime DEFAULT NULL,
  `has_attachments` tinyint(1) DEFAULT '0',
  `from_address` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `to_address` varchar(255) DEFAULT NULL,
  `body` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_id` (`email_id`),
  KEY `activity_id` (`activity_id`),
  KEY `idx_imap_uid` (`imap_uid`),
  KEY `idx_message_id_header` (`message_id_header`),
  KEY `idx_emails_customer_id` (`customer_id`),
  KEY `idx_ai_analyzed` (`ai_analyzed`),
  KEY `idx_emails_priority` (`priority`),
  KEY `idx_emails_status` (`status`),
  KEY `idx_emails_user_id` (`user_id`),
  KEY `idx_emails_email_id` (`email_id`),
  CONSTRAINT `emails_ibfk_1` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_emails_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `emails_sent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `config_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `to_address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cc_address` text COLLATE utf8mb4_unicode_ci,
  `bcc_address` text COLLATE utf8mb4_unicode_ci,
  `subject` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime NOT NULL,
  `status` enum('sent','failed','bounced') COLLATE utf8mb4_unicode_ci DEFAULT 'sent',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `message_id_header` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `in_reply_to` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `config_id` (`config_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_status` (`status`),
  CONSTRAINT `emails_sent_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emails_sent_ibfk_2` FOREIGN KEY (`config_id`) REFERENCES `email_configurations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_accounting_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de la catégorie',
  `category_type` enum('income','expense','asset','liability','equity') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type comptable',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code comptable (ex: 60110)',
  `parent_id` int(11) DEFAULT NULL COMMENT 'Catégorie parente pour hiérarchie',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description de la catégorie',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Catégorie active',
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Couleur hex pour affichage',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_type` (`category_type`),
  KEY `idx_active` (`is_active`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `erp_accounting_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `erp_accounting_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!50001 DROP VIEW IF EXISTS `erp_accounting_revenue`*/;
/*!50001 CREATE VIEW `erp_accounting_revenue` AS SELECT 
 1 AS `customer_id`,
 1 AS `period`,
 1 AS `fiscal_year`,
 1 AS `fiscal_quarter`,
 1 AS `invoice_count`,
 1 AS `total_revenue_ht`,
 1 AS `total_vat_collected`,
 1 AS `total_revenue_ttc`,
 1 AS `average_invoice_amount`,
 1 AS `invoice_numbers`*/;

CREATE TABLE IF NOT EXISTS `erp_bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de la banque',
  `account_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Numéro de compte (IBAN)',
  `account_type` enum('checking','savings','business') COLLATE utf8mb4_unicode_ci DEFAULT 'checking' COMMENT 'Type de compte',
  `balance` decimal(15,2) DEFAULT '0.00' COMMENT 'Solde actuel',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'EUR' COMMENT 'Devise',
  `connection_status` enum('connected','disconnected','error') COLLATE utf8mb4_unicode_ci DEFAULT 'disconnected' COMMENT 'Statut de connexion API',
  `api_credentials` text COLLATE utf8mb4_unicode_ci COMMENT 'Credentials chiffrés pour API bancaire',
  `last_sync` datetime DEFAULT NULL COMMENT 'Dernière synchronisation',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`connection_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_bank_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL COMMENT 'Référence au compte bancaire',
  `transaction_date` date NOT NULL COMMENT 'Date de la transaction',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Libellé de la transaction',
  `amount` decimal(15,2) NOT NULL COMMENT 'Montant (positif = crédit, négatif = débit)',
  `balance_after` decimal(15,2) DEFAULT NULL COMMENT 'Solde après transaction',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Catégorie comptable',
  `transaction_type` enum('debit','credit','transfer') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type de transaction',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro de référence bancaire',
  `vendor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom du fournisseur/client',
  `is_reconciled` tinyint(1) DEFAULT '0' COMMENT 'Transaction rapprochée',
  `metadata` json DEFAULT NULL COMMENT 'Données supplémentaires',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_account_date` (`account_id`,`transaction_date`),
  KEY `idx_category` (`category`),
  KEY `idx_date` (`transaction_date`),
  KEY `idx_reconciled` (`is_reconciled`),
  CONSTRAINT `erp_bank_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `erp_bank_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `siret` varchar(20) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT 'France',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `naf` varchar(20) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_erp_companies_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `first_name` varchar(120) NOT NULL,
  `last_name` varchar(120) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `base_salary` decimal(10,2) DEFAULT '0.00',
  `job_title` varchar(180) DEFAULT NULL,
  `department` varchar(180) DEFAULT NULL,
  `contract_type` enum('CDI','CDD','Freelance','Stage','Alternance') DEFAULT 'CDI',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_emp_company` (`company_id`),
  KEY `idx_erp_employees_customer_id` (`customer_id`),
  CONSTRAINT `fk_emp_company` FOREIGN KEY (`company_id`) REFERENCES `erp_companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_financial_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `report_type` enum('balance_sheet','income_statement','cash_flow','trial_balance','custom') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type de bilan',
  `period_start` date NOT NULL COMMENT 'Début de période',
  `period_end` date NOT NULL COMMENT 'Fin de période',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Titre du rapport',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chemin du PDF généré',
  `data` json DEFAULT NULL COMMENT 'Données du rapport',
  `total_income` decimal(15,2) DEFAULT NULL COMMENT 'Total revenus',
  `total_expenses` decimal(15,2) DEFAULT NULL COMMENT 'Total dépenses',
  `net_result` decimal(15,2) DEFAULT NULL COMMENT 'Résultat net',
  `generated_by` int(11) DEFAULT NULL COMMENT 'ID utilisateur',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_type` (`report_type`),
  KEY `idx_period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `location` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `description` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_inventory_customer` (`customer_id`),
  CONSTRAINT `fk_inventory_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_invoice_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `action` enum('created','updated','sent','viewed','paid','cancelled','archived') COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'Utilisateur ayant effectué l''action',
  `old_values` json DEFAULT NULL COMMENT 'Anciennes valeurs modifiées',
  `new_values` json DEFAULT NULL COMMENT 'Nouvelles valeurs',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse IP',
  `user_agent` text COLLATE utf8mb4_unicode_ci COMMENT 'Navigateur/Client',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail pour conformité (conservation 10 ans)';

CREATE TABLE IF NOT EXISTS `erp_invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL COMMENT 'Référence à la facture',
  `item_order` int(11) DEFAULT '0' COMMENT 'Ordre d''affichage',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description du produit/service',
  `product_id` int(11) DEFAULT NULL COMMENT 'Référence produit si applicable',
  `quantity` decimal(10,3) NOT NULL DEFAULT '1.000' COMMENT 'Quantité',
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unité' COMMENT 'Unité (pièce, heure, jour, kg, etc.)',
  `unit_price_ht` decimal(15,2) NOT NULL COMMENT 'Prix unitaire HT',
  `discount_rate` decimal(5,2) DEFAULT '0.00' COMMENT 'Taux de remise (%)',
  `discount_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Montant de remise',
  `total_ht` decimal(15,2) NOT NULL COMMENT 'Total HT de la ligne',
  `vat_rate` decimal(5,2) NOT NULL COMMENT 'Taux de TVA (20, 10, 5.5, 2.1, 0)',
  `vat_amount` decimal(15,2) NOT NULL COMMENT 'Montant TVA',
  `total_ttc` decimal(15,2) NOT NULL COMMENT 'Total TTC',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lignes de facture détaillées';
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER after_invoice_item_insert
AFTER INSERT ON erp_invoice_items
FOR EACH ROW
BEGIN
    UPDATE erp_invoices
    SET 
        total_ht = (SELECT COALESCE(SUM(total_ht), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_tva = (SELECT COALESCE(SUM(vat_amount), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_ttc = (SELECT COALESCE(SUM(total_ttc), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id)
    WHERE id = NEW.invoice_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER after_invoice_item_update
AFTER UPDATE ON erp_invoice_items
FOR EACH ROW
BEGIN
    UPDATE erp_invoices
    SET 
        total_ht = (SELECT COALESCE(SUM(total_ht), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_tva = (SELECT COALESCE(SUM(vat_amount), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_ttc = (SELECT COALESCE(SUM(total_ttc), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id)
    WHERE id = NEW.invoice_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER after_invoice_item_delete
AFTER DELETE ON erp_invoice_items
FOR EACH ROW
BEGIN
    UPDATE erp_invoices
    SET 
        total_ht = (SELECT COALESCE(SUM(total_ht), 0) FROM erp_invoice_items WHERE invoice_id = OLD.invoice_id),
        total_tva = (SELECT COALESCE(SUM(vat_amount), 0) FROM erp_invoice_items WHERE invoice_id = OLD.invoice_id),
        total_ttc = (SELECT COALESCE(SUM(total_ttc), 0) FROM erp_invoice_items WHERE invoice_id = OLD.invoice_id)
    WHERE id = OLD.invoice_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

CREATE TABLE IF NOT EXISTS `erp_invoice_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `payment_date` date NOT NULL COMMENT 'Date du paiement',
  `amount` decimal(15,2) NOT NULL COMMENT 'Montant payé',
  `payment_method` enum('transfer','check','cash','card','direct_debit','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro de transaction/chèque',
  `bank_account_id` int(11) DEFAULT NULL COMMENT 'Compte bancaire de réception',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes sur le paiement',
  `recorded_by` int(11) DEFAULT NULL COMMENT 'Utilisateur ayant enregistré',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_bank_account` (`bank_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des paiements de factures';
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER after_payment_insert
AFTER INSERT ON erp_invoice_payments
FOR EACH ROW
BEGIN
    -- Mettre à jour le montant payé sur la facture
    UPDATE erp_invoices
    SET 
        paid_amount = (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id),
        payment_status = CASE
            WHEN (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id) >= total_ttc THEN 'paid'
            WHEN (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id) > 0 THEN 'partial'
            ELSE payment_status
        END,
        paid_date = CASE
            WHEN (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id) >= total_ttc THEN NEW.payment_date
            ELSE paid_date
        END
    WHERE id = NEW.invoice_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

CREATE TABLE IF NOT EXISTS `erp_invoice_sequences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT 'Client propriétaire de la séquence',
  `invoice_year` int(11) NOT NULL COMMENT 'Année de la séquence',
  `prefix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'FA' COMMENT 'Préfixe (ex: FA, FACT, INV)',
  `current_number` int(11) NOT NULL DEFAULT '0' COMMENT 'Dernier numéro utilisé',
  `number_format` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '{PREFIX}{YEAR}-{NUMBER}' COMMENT 'Format: FA2026-0001',
  `padding` int(11) DEFAULT '4' COMMENT 'Nombre de zéros (0001, 00001, etc.)',
  `separator_char` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT '-' COMMENT 'Séparateur',
  `last_invoice_id` int(11) DEFAULT NULL COMMENT 'Dernière facture créée',
  `last_invoice_date` date DEFAULT NULL COMMENT 'Date dernière facture',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_year` (`customer_id`,`invoice_year`),
  KEY `idx_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Numérotation séquentielle obligatoire sans rupture';

CREATE TABLE IF NOT EXISTS `erp_invoice_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `template_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'modern',
  `primary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#667eea',
  `secondary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#764ba2',
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `header_text` text COLLATE utf8mb4_unicode_ci,
  `footer_text` text COLLATE utf8mb4_unicode_ci,
  `show_logo` tinyint(1) DEFAULT '1',
  `show_qr_code` tinyint(1) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer` (`customer_id`),
  KEY `idx_template_name` (`template_name`),
  KEY `idx_customer_template` (`customer_id`,`template_name`),
  CONSTRAINT `erp_invoice_settings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT 'Client émetteur de la facture',
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Numéro séquentiel unique sans rupture',
  `invoice_type` enum('sale','credit_note','advance','proforma') COLLATE utf8mb4_unicode_ci DEFAULT 'sale' COMMENT 'Type de facture',
  `issue_date` date NOT NULL COMMENT 'Date d''émission (ne peut être antérieure)',
  `due_date` date DEFAULT NULL COMMENT 'Date d''échéance de paiement',
  `delivery_date` date DEFAULT NULL COMMENT 'Date de livraison/prestation',
  `client_type` enum('company','individual') COLLATE utf8mb4_unicode_ci DEFAULT 'company',
  `client_company_id` int(11) DEFAULT NULL COMMENT 'Référence à companies si entreprise',
  `client_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du client',
  `client_address` text COLLATE utf8mb4_unicode_ci COMMENT 'Adresse complète',
  `client_postal_code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'France',
  `client_siret` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SIRET obligatoire si entreprise française',
  `client_vat_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro TVA intracommunautaire',
  `client_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_ht` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total HT',
  `total_tva` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total TVA',
  `total_ttc` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total TTC',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'EUR',
  `vat_details` json DEFAULT NULL COMMENT 'Détail par taux: [{rate: 20, base_ht: 1000, amount: 200}]',
  `payment_terms` text COLLATE utf8mb4_unicode_ci COMMENT 'Conditions de paiement',
  `payment_method` enum('transfer','check','cash','card','direct_debit','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Moyen de paiement',
  `payment_status` enum('unpaid','partial','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `paid_date` date DEFAULT NULL COMMENT 'Date de paiement effectif',
  `late_fee_rate` decimal(5,2) DEFAULT '10.00' COMMENT 'Taux de pénalités de retard (%)',
  `recovery_indemnity` decimal(10,2) DEFAULT '40.00' COMMENT 'Indemnité forfaitaire de recouvrement (min 40€)',
  `discount_terms` text COLLATE utf8mb4_unicode_ci COMMENT 'Conditions d''escompte si paiement anticipé',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes internes',
  `client_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes pour le client (conditions générales)',
  `attached_documents` text COLLATE utf8mb4_unicode_ci COMMENT 'Documents joints (devis, bon de commande, etc.)',
  `electronic_format` enum('pdf','facturx','xml') COLLATE utf8mb4_unicode_ci DEFAULT 'pdf' COMMENT 'Format de la facture',
  `xml_data` text COLLATE utf8mb4_unicode_ci COMMENT 'Données XML EN 16931 pour Factur-X',
  `pdf_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chemin du PDF généré',
  `status` enum('draft','sent','viewed','paid','cancelled','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `sent_at` datetime DEFAULT NULL COMMENT 'Date d''envoi au client',
  `viewed_at` datetime DEFAULT NULL COMMENT 'Date de consultation par le client',
  `cancelled_at` datetime DEFAULT NULL COMMENT 'Date d''annulation',
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci COMMENT 'Motif d''annulation',
  `sale_id` int(11) DEFAULT NULL COMMENT 'Référence à erp_sales si vente directe',
  `folder_id` int(11) DEFAULT NULL COMMENT 'Référence à folders si missions',
  `created_by` int(11) DEFAULT NULL COMMENT 'Utilisateur créateur',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_issue_date` (`issue_date`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_client_company` (`client_company_id`),
  KEY `idx_sale_id` (`sale_id`),
  KEY `idx_folder` (`folder_id`),
  CONSTRAINT `fk_invoice_client_company` FOREIGN KEY (`client_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_invoice_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Factures conformes législation française 2026';
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER before_invoice_insert 
BEFORE INSERT ON erp_invoices
FOR EACH ROW
BEGIN
    -- Anti-backdating: la date d'émission ne peut pas être antérieure à aujourd'hui
    IF NEW.issue_date < CURDATE() AND NEW.status != 'draft' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'La date d\'émission ne peut pas être antérieure à la date du jour (anti-backdating obligatoire)';
    END IF;
    
    -- Calcul de la date d'échéance si non fournie (30 jours par défaut)
    IF NEW.due_date IS NULL THEN
        SET NEW.due_date = DATE_ADD(NEW.issue_date, INTERVAL 30 DAY);
    END IF;
    
    -- Définir le statut de paiement selon les montants
    IF NEW.paid_amount >= NEW.total_ttc THEN
        SET NEW.payment_status = 'paid';
        SET NEW.paid_date = CURDATE();
    ELSEIF NEW.paid_amount > 0 THEN
        SET NEW.payment_status = 'partial';
    ELSEIF NEW.due_date < CURDATE() THEN
        SET NEW.payment_status = 'overdue';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER after_invoice_paid
AFTER UPDATE ON erp_invoices
FOR EACH ROW
BEGIN
    -- Si le statut de paiement passe à 'paid'
    IF NEW.payment_status = 'paid' AND OLD.payment_status != 'paid' THEN
        
        -- Créer une transaction bancaire de crédit (entrée d'argent)
        INSERT INTO erp_bank_transactions (
            account_id,
            transaction_date,
            description,
            amount,
            category,
            transaction_type,
            reference_number,
            vendor_name,
            is_reconciled,
            metadata
        )
        SELECT 
            ba.id as account_id,
            NEW.paid_date as transaction_date,
            CONCAT('Facture ', NEW.invoice_number, ' - ', NEW.client_name) as description,
            NEW.total_ttc as amount,
            'Ventes de produits' as category,
            'credit' as transaction_type,
            NEW.invoice_number as reference_number,
            NEW.client_name as vendor_name,
            TRUE as is_reconciled,
            JSON_OBJECT(
                'invoice_id', NEW.id,
                'invoice_number', NEW.invoice_number,
                'total_ht', NEW.total_ht,
                'total_tva', NEW.total_tva,
                'total_ttc', NEW.total_ttc,
                'payment_method', NEW.payment_method
            ) as metadata
        FROM erp_bank_accounts ba
        WHERE ba.customer_id = NEW.customer_id
          AND ba.account_type = 'business'
          AND ba.is_active = TRUE
        LIMIT 1;
        
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

/*!50001 DROP VIEW IF EXISTS `erp_invoices_dashboard`*/;
/*!50001 CREATE VIEW `erp_invoices_dashboard` AS SELECT 
 1 AS `customer_id`,
 1 AS `period`,
 1 AS `total_invoices`,
 1 AS `paid_count`,
 1 AS `unpaid_count`,
 1 AS `overdue_count`,
 1 AS `total_amount`,
 1 AS `paid_amount`,
 1 AS `outstanding_amount`,
 1 AS `avg_payment_delay_days`*/;

CREATE TABLE IF NOT EXISTS `erp_payrolls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `period` char(7) NOT NULL COMMENT 'YYYY-MM',
  `gross_salary` decimal(10,2) NOT NULL,
  `bonus` decimal(10,2) NOT NULL DEFAULT '0.00',
  `overtime` decimal(10,2) NOT NULL DEFAULT '0.00',
  `deductions` decimal(10,2) NOT NULL DEFAULT '0.00',
  `employee_contrib` decimal(10,2) NOT NULL,
  `employer_contrib` decimal(10,2) NOT NULL,
  `net_pay` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_emp_period` (`employee_id`,`period`),
  KEY `idx_erp_payrolls_customer_id` (`customer_id`),
  CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `erp_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sales_product` (`product_id`),
  KEY `idx_sales_customer` (`customer_id`),
  KEY `idx_sales_employee` (`employee_id`),
  KEY `idx_invoice` (`invoice_id`),
  CONSTRAINT `fk_sales_customer_uniq` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_employee_uniq` FOREIGN KEY (`employee_id`) REFERENCES `erp_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_product_uniq` FOREIGN KEY (`product_id`) REFERENCES `erp_stock` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_scanned_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du fichier original',
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Chemin de stockage',
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Extension (pdf, jpg, xlsx, etc.)',
  `file_size` int(11) DEFAULT NULL COMMENT 'Taille en octets',
  `document_type` enum('invoice','receipt','bank_statement','payslip','contract','other') COLLATE utf8mb4_unicode_ci DEFAULT 'other' COMMENT 'Type de document',
  `document_date` date DEFAULT NULL COMMENT 'Date du document',
  `amount` decimal(15,2) DEFAULT NULL COMMENT 'Montant extrait',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'EUR',
  `vendor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Fournisseur/Client extrait',
  `invoice_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro de facture extrait',
  `extracted_data` json DEFAULT NULL COMMENT 'Toutes les données extraites par OCR/AI',
  `status` enum('pending','processing','processed','error') COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'Statut du traitement',
  `confidence_score` decimal(5,2) DEFAULT NULL COMMENT 'Score de confiance OCR (0-100)',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT 'Message d''erreur si échec',
  `processed_at` datetime DEFAULT NULL COMMENT 'Date de traitement',
  `linked_transaction_id` int(11) DEFAULT NULL COMMENT 'Transaction bancaire liée',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`document_type`),
  KEY `idx_date` (`document_date`),
  KEY `idx_vendor` (`vendor_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `role` varchar(150) DEFAULT NULL,
  `notes` text,
  `company_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shift_employee` (`employee_id`),
  KEY `idx_shift_customer` (`customer_id`),
  KEY `idx_shift_company` (`company_id`),
  CONSTRAINT `fk_shifts_company_uniq` FOREIGN KEY (`company_id`) REFERENCES `erp_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_shifts_customer_uniq` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_shifts_employee_uniq` FOREIGN KEY (`employee_id`) REFERENCES `erp_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_stock` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `category` varchar(100) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_stock_customer` (`customer_id`),
  CONSTRAINT `fk_stock_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_vat_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rate` decimal(5,2) NOT NULL COMMENT 'Taux de TVA',
  `label` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Libellé (Normal, Intermédiaire, Réduit, Super-réduit)',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description et cas d''application',
  `country` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT 'FR' COMMENT 'Code pays',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Taux en vigueur',
  `effective_from` date DEFAULT NULL COMMENT 'Date d''entrée en vigueur',
  `effective_to` date DEFAULT NULL COMMENT 'Date de fin (si modifié)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_country` (`country`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Taux de TVA applicables';

CREATE TABLE IF NOT EXISTS `folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `fk_folder_status` (`status_id`),
  CONSTRAINT `fk_folder_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`),
  CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `folders_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) DEFAULT NULL,
  `form_data` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `date_upload` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `integration_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `integration_id` int(11) NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','error','warning') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_integration` (`integration_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `integration_logs_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `integrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `integrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `integration_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-plug',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'primary',
  `is_active` tinyint(1) DEFAULT '0',
  `config` json DEFAULT NULL,
  `api_key` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_secret` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync` timestamp NULL DEFAULT NULL,
  `sync_status` enum('never','success','error','syncing') COLLATE utf8mb4_unicode_ci DEFAULT 'never',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_integration` (`customer_id`,`integration_type`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_type` (`integration_type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folder_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('en_attente','envoyée','payée','annulée') DEFAULT 'en_attente',
  `issued_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `folder_id` (`folder_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `status` enum('new','contacted','qualified','unqualified','converted','customer','lost') DEFAULT 'new',
  `source` enum('website','social_media','referral','direct','event') DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `interest` text,
  `notes` text,
  `stage` enum('lead','contacted','qualified','proposal','converted','unqualified') NOT NULL DEFAULT 'lead',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ai_score` float DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT '50',
  `score_category` enum('chaud','tiede','froid','mort') DEFAULT 'tiede',
  `score_reasoning` text,
  `score_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `fk_contact_customer` (`customer_id`),
  KEY `idx_leads_stage` (`stage`),
  KEY `idx_leads_source` (`source`),
  KEY `idx_score` (`score`),
  KEY `idx_score_category` (`score_category`),
  CONSTRAINT `fk_contact_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leads_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_time` (`email`,`attempted_at`),
  KEY `idx_ip_time` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `is_deleted_sender` tinyint(1) DEFAULT '0',
  `is_deleted_receiver` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `migration_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(150) NOT NULL,
  `executed_at` datetime NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_name` (`migration_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `missions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folder_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `departure` varchar(255) DEFAULT NULL,
  `arrival` varchar(255) DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `driver` varchar(255) DEFAULT NULL,
  `vehicle` varchar(255) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT '0.00',
  `status_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'vtc',
  `project` varchar(255) DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `responsible` varchar(100) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `folder_id` (`folder_id`),
  KEY `fk_mission_status` (`status_id`),
  KEY `fk_missions_assigned_to` (`assigned_to`),
  CONSTRAINT `fk_mission_status` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`),
  CONSTRAINT `fk_missions_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `missions_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `msg_conversations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `type` enum('direct','group','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `msg_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `sender_type` enum('user','company') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conv_id` (`conversation_id`,`id`),
  CONSTRAINT `fk_mm_conv` FOREIGN KEY (`conversation_id`) REFERENCES `msg_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `msg_participants` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) unsigned NOT NULL,
  `participant_type` enum('user','company') NOT NULL,
  `participant_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_participant` (`conversation_id`,`participant_type`,`participant_id`),
  KEY `idx_conv` (`conversation_id`),
  CONSTRAINT `fk_mp_conv` FOREIGN KEY (`conversation_id`) REFERENCES `msg_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `msg_receipts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg` (`message_id`),
  CONSTRAINT `fk_mr_msg` FOREIGN KEY (`message_id`) REFERENCES `msg_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `related_type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_is_read` (`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `opportunities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` text,
  `customer_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `status_id` int(11) DEFAULT NULL,
  `stage` enum('prospecting','qualification','needs_analysis','proposal','negotiation','closed_won','closed_lost') NOT NULL DEFAULT 'prospecting',
  `probability` int(11) DEFAULT '0',
  `amount` decimal(15,2) DEFAULT '0.00',
  `expected_close_date` date DEFAULT NULL,
  `actual_close_date` date DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `competitor` varchar(100) DEFAULT NULL,
  `loss_reason` text,
  `next_action` text,
  `next_action_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `contact_id` (`contact_id`),
  KEY `idx_opportunities_stage` (`stage`),
  KEY `idx_opportunities_assigned_to` (`assigned_to`),
  KEY `idx_opportunities_close_date` (`expected_close_date`),
  KEY `idx_opportunities_customer_id` (`customer_id`),
  KEY `idx_opportunities_status_id` (`status_id`),
  CONSTRAINT `opportunities_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_ibfk_2` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `opportunities_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `participants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pseudo` varchar(100) NOT NULL,
  `img` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `pipeline_boards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` enum('opportunity','mission','lead','campaign') NOT NULL,
  `name` varchar(120) NOT NULL,
  `is_default` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_entity_type` (`entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pipeline_entity_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` enum('opportunity','mission','lead','campaign') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `stage_id` int(11) NOT NULL,
  `position` bigint(20) DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_entity` (`entity_type`,`entity_id`),
  KEY `idx_stage` (`stage_id`),
  CONSTRAINT `fk_pipeline_stage` FOREIGN KEY (`stage_id`) REFERENCES `pipeline_stages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pipeline_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(64) DEFAULT NULL,
  `entity_type` enum('opportunity','mission','lead','campaign') NOT NULL DEFAULT 'opportunity',
  `board_id` int(11) DEFAULT NULL,
  `order_position` int(11) NOT NULL,
  `probability_default` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `color_code` varchar(7) DEFAULT '#007bff',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_board_slug` (`board_id`,`slug`),
  KEY `idx_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` enum('crm','creator','devis','dev') NOT NULL,
  `description` text,
  `price` decimal(10,2) DEFAULT '0.00',
  `features` text,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text,
  `content` longtext,
  `author_id` int(11) DEFAULT '1',
  `status` varchar(20) DEFAULT 'published',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `powerbi_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `report_id` varchar(255) NOT NULL,
  `workspace_id` varchar(255) NOT NULL,
  `embed_url` text NOT NULL,
  `access_token` text,
  `token_expires_at` datetime DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `allowed_roles` json DEFAULT NULL,
  `refresh_schedule` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('website','ecommerce','portfolio','blog','landing','organigramme','uml','mindmap','flowchart','charte') COLLATE utf8mb4_unicode_ci DEFAULT 'website',
  `status` enum('active','completed','paused','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `progress` int(11) DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `published_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pages_metadata` text COLLATE utf8mb4_unicode_ci,
  `data` longtext COLLATE utf8mb4_unicode_ci,
  `global_styles` json DEFAULT NULL COMMENT 'Styles globaux du projet (police, couleurs, bordures, etc.)',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quote_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `unit_price` decimal(10,2) DEFAULT '0.00',
  `discount_percent` decimal(5,2) DEFAULT '0.00',
  `total_price` decimal(15,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `quote_id` (`quote_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `quote_items_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quote_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_number` varchar(50) NOT NULL,
  `opportunity_id` int(11) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `status` enum('draft','sent','accepted','rejected','expired') DEFAULT 'draft',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `valid_until` date DEFAULT NULL,
  `notes` text,
  `terms_conditions` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_number` (`quote_number`),
  KEY `opportunity_id` (`opportunity_id`),
  KEY `company_id` (`company_id`),
  KEY `contact_id` (`contact_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotes_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotes_ibfk_3` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotes_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `rt_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `topic` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer_topic_id` (`customer_id`,`topic`,`id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*!50001 DROP VIEW IF EXISTS `sales_dashboard`*/;
/*!50001 CREATE VIEW `sales_dashboard` AS SELECT 
 1 AS `date`,
 1 AS `total_opportunities`,
 1 AS `revenue`,
 1 AS `deals_won`,
 1 AS `deals_lost`,
 1 AS `avg_deal_size`,
 1 AS `first_name`,
 1 AS `last_name`*/;

CREATE TABLE IF NOT EXISTS `sites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `site_data` json DEFAULT NULL,
  `style_data` json DEFAULT NULL,
  `date_modif` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `type` enum('mission','dossier','global') NOT NULL DEFAULT 'global',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `stores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `category` varchar(100) DEFAULT NULL,
  `admin_response` text,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `support_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text,
  `is_public` tinyint(1) DEFAULT '0',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('revenue','deals','calls','meetings') NOT NULL,
  `period` enum('monthly','quarterly','yearly') NOT NULL,
  `target_value` decimal(15,2) NOT NULL,
  `achieved_value` decimal(15,2) DEFAULT '0.00',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `targets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `due_date` datetime DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `status` enum('todo','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'todo',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `related_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'lead, customer, deal, etc.',
  `related_id` int(11) DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `checklist` json DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_assigned` (`assigned_to`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_related` (`related_type`,`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT '1',
  `security_alerts` tinyint(1) DEFAULT '1',
  `newsletter` tinyint(1) DEFAULT '0',
  `theme` enum('light','dark','auto') COLLATE utf8mb4_unicode_ci DEFAULT 'light',
  `language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'fr',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Europe/Paris',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`),
  KEY `idx_user_settings_user_id` (`user_id`),
  CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `pending_plan_id` int(11) DEFAULT NULL,
  `pending_plan_change_date` date DEFAULT NULL,
  `last_plan_change_date` datetime DEFAULT NULL,
  `prorated_amount` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `current_period_end` date DEFAULT NULL,
  `project_category` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','trial','trialing','essai_gratuit','free_trial','canceled','past_due','unpaid') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `subscription_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `idx_stripe_subscription_id` (`stripe_subscription_id`),
  KEY `idx_stripe_customer_id` (`stripe_customer_id`),
  KEY `fk_pending_plan` (`pending_plan_id`),
  CONSTRAINT `fk_pending_plan` FOREIGN KEY (`pending_plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `user_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `onboarding_completed` tinyint(1) DEFAULT '0',
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT '',
  `last_name` varchar(50) DEFAULT '',
  `role` enum('admin','manager','sales','support') DEFAULT 'sales',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `email_verified` tinyint(1) DEFAULT '0',
  `email_verify_token` varchar(64) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `twofa_enabled` tinyint(1) DEFAULT '0',
  `twofa_secret` varchar(64) DEFAULT NULL,
  `can_access_analytics` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_pipeline` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_tasks` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_calls` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_clients` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_leads` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_folders` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_missions` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_billing` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_mail` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_ai_agents` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_ai_actions` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_campaigns` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_whatsapp` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_email` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_invoices` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_quotes` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_sales` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_planning` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_hr` tinyint(1) NOT NULL DEFAULT '1',
  `can_access_payroll` tinyint(1) NOT NULL DEFAULT '1',
  `can_generate_payroll` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_customer_id` (`customer_id`),
  KEY `idx_stripe_customer_id` (`stripe_customer_id`),
  CONSTRAINT `fk_users_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `vcard_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `qr_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_campaign_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `variables` json DEFAULT NULL COMMENT 'Variables pour template: {"1":"John","2":"Paris"}',
  `status` enum('pending','sent','delivered','read','failed','replied') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Meta message ID',
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `error_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `cost` decimal(10,4) DEFAULT '0.0000',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `idx_campaign_id` (`campaign_id`),
  KEY `idx_status` (`status`),
  KEY `idx_phone` (`phone_number`),
  CONSTRAINT `whatsapp_campaign_recipients_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `whatsapp_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_campaign_recipients_ibfk_2` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `config_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','scheduled','sending','sent','paused','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `recipients_count` int(11) DEFAULT '0',
  `sent_count` int(11) DEFAULT '0',
  `delivered_count` int(11) DEFAULT '0',
  `read_count` int(11) DEFAULT '0',
  `failed_count` int(11) DEFAULT '0',
  `replied_count` int(11) DEFAULT '0',
  `total_cost` decimal(10,2) DEFAULT '0.00' COMMENT 'Coût total Meta en USD',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `config_id` (`config_id`),
  KEY `template_id` (`template_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled` (`scheduled_at`),
  CONSTRAINT `whatsapp_campaigns_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_campaigns_ibfk_2` FOREIGN KEY (`config_id`) REFERENCES `whatsapp_configurations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_campaigns_ibfk_3` FOREIGN KEY (`template_id`) REFERENCES `whatsapp_templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WhatsApp marketing campaigns sent to multiple recipients';

CREATE TABLE IF NOT EXISTS `whatsapp_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `phone_number_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Meta Phone Number ID',
  `business_account_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Meta Business Account ID',
  `display_phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Format international: +33612345678',
  `verified` tinyint(1) DEFAULT '0' COMMENT 'Numéro vérifié par Meta',
  `access_token` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Encrypted Meta access token (permanent)',
  `webhook_verify_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token pour vérifier webhooks',
  `quality_rating` enum('GREEN','YELLOW','RED','UNKNOWN') COLLATE utf8mb4_unicode_ci DEFAULT 'UNKNOWN',
  `messaging_limit` enum('TIER_50','TIER_250','TIER_1K','TIER_10K','TIER_100K','TIER_UNLIMITED') COLLATE utf8mb4_unicode_ci DEFAULT 'TIER_50',
  `is_active` tinyint(1) DEFAULT '1',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `error_count` int(11) DEFAULT '0',
  `last_error_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_phone` (`customer_id`,`phone_number_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_phone_number_id` (`phone_number_id`),
  KEY `idx_quality_rating` (`quality_rating`),
  CONSTRAINT `whatsapp_configurations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='WhatsApp Business configurations per customer using Meta Cloud API';

CREATE TABLE IF NOT EXISTS `whatsapp_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `config_id` int(11) NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `last_message_direction` enum('inbound','outbound') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_count` int(11) DEFAULT '0',
  `is_archived` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `config_id` (`config_id`),
  KEY `lead_id` (`lead_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_phone` (`phone_number`),
  KEY `idx_last_message` (`last_message_at`),
  CONSTRAINT `whatsapp_conversations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_conversations_ibfk_2` FOREIGN KEY (`config_id`) REFERENCES `whatsapp_configurations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_conversations_ibfk_3` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `config_id` int(11) NOT NULL,
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Meta message ID (wamid.xxx)',
  `conversation_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pour grouper les messages',
  `direction` enum('inbound','outbound') COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL COMMENT 'Lead associé',
  `message_type` enum('text','image','document','audio','video','location','template','interactive') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `content` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Message text or JSON payload',
  `media_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL du média si applicable',
  `template_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom du template Meta si utilisé',
  `status` enum('sent','delivered','read','failed','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `is_read` tinyint(1) DEFAULT '0',
  `is_replied` tinyint(1) DEFAULT '0',
  `status_timestamp` datetime DEFAULT NULL,
  `error_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `cost` decimal(10,4) DEFAULT '0.0000' COMMENT 'Coût Meta en USD',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_id` (`message_id`),
  KEY `config_id` (`config_id`),
  KEY `lead_id` (`lead_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_conversation` (`conversation_id`),
  KEY `idx_direction` (`direction`),
  KEY `idx_status` (`status`),
  KEY `idx_from_phone` (`from_phone`),
  KEY `idx_created` (`created_at`),
  KEY `idx_whatsapp_is_read` (`is_read`),
  FULLTEXT KEY `ft_content` (`content`),
  CONSTRAINT `whatsapp_messages_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_messages_ibfk_2` FOREIGN KEY (`config_id`) REFERENCES `whatsapp_configurations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_messages_ibfk_3` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='All WhatsApp messages sent and received with full conversation history';

CREATE TABLE IF NOT EXISTS `whatsapp_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `config_id` int(11) NOT NULL,
  `template_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom unique du template',
  `language` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'fr' COMMENT 'Code langue ISO (fr, en, es...)',
  `category` enum('MARKETING','UTILITY','AUTHENTICATION') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED','DISABLED') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
  `header_type` enum('NONE','TEXT','IMAGE','VIDEO','DOCUMENT') COLLATE utf8mb4_unicode_ci DEFAULT 'NONE',
  `header_content` text COLLATE utf8mb4_unicode_ci COMMENT 'Texte ou URL média header',
  `body_text` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Corps du message avec {{1}} variables',
  `footer_text` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Texte footer optionnel',
  `buttons` json DEFAULT NULL COMMENT 'Boutons: [{"type":"QUICK_REPLY","text":"Oui"}]',
  `meta_template_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID Meta du template approuvé',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template` (`customer_id`,`template_name`,`language`),
  KEY `config_id` (`config_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  CONSTRAINT `whatsapp_templates_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_templates_ibfk_2` FOREIGN KEY (`config_id`) REFERENCES `whatsapp_configurations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Meta-approved message templates required for outbound messages';

CREATE TABLE IF NOT EXISTS `whatsapp_webhook_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) DEFAULT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'messages, message_status, account_update',
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON brut Meta',
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processed` tinyint(1) DEFAULT '0',
  `processed_at` datetime DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `config_id` (`config_id`),
  KEY `idx_processed` (`processed`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `whatsapp_webhook_logs_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `whatsapp_configurations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `winner` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pseudo` varchar(100) NOT NULL,
  `img` mediumtext NOT NULL,
  `tirage_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!50001 DROP VIEW IF EXISTS `agent_stats`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;


/*!50001 VIEW `agent_stats` AS select `agent_logs`.`agent_name` AS `agent_name`,`agent_logs`.`customer_id` AS `customer_id`,cast(`agent_logs`.`created_at` as date) AS `date`,count(0) AS `total_actions`,sum((case when (`agent_logs`.`status` = 'success') then 1 else 0 end)) AS `successful`,sum((case when (`agent_logs`.`status` = 'error') then 1 else 0 end)) AS `errors`,(avg((case when (`agent_logs`.`status` = 'success') then 1 else 0 end)) * 100) AS `success_rate` from `agent_logs` group by `agent_logs`.`agent_name`,`agent_logs`.`customer_id`,cast(`agent_logs`.`created_at` as date) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `erp_accounting_revenue`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;


/*!50001 VIEW `erp_accounting_revenue` AS select `i`.`customer_id` AS `customer_id`,date_format(`i`.`paid_date`,'%Y-%m') AS `period`,date_format(`i`.`paid_date`,'%Y') AS `fiscal_year`,quarter(`i`.`paid_date`) AS `fiscal_quarter`,count(0) AS `invoice_count`,sum(`i`.`total_ht`) AS `total_revenue_ht`,sum(`i`.`total_tva`) AS `total_vat_collected`,sum(`i`.`total_ttc`) AS `total_revenue_ttc`,avg(`i`.`total_ttc`) AS `average_invoice_amount`,group_concat(distinct `i`.`invoice_number` separator ', ') AS `invoice_numbers` from `erp_invoices` `i` where ((`i`.`payment_status` = 'paid') and (`i`.`status` <> 'cancelled')) group by `i`.`customer_id`,date_format(`i`.`paid_date`,'%Y-%m'),date_format(`i`.`paid_date`,'%Y'),quarter(`i`.`paid_date`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `erp_invoices_dashboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;


/*!50001 VIEW `erp_invoices_dashboard` AS select `i`.`customer_id` AS `customer_id`,date_format(`i`.`issue_date`,'%Y-%m') AS `period`,count(0) AS `total_invoices`,sum((case when (`i`.`payment_status` = 'paid') then 1 else 0 end)) AS `paid_count`,sum((case when (`i`.`payment_status` = 'unpaid') then 1 else 0 end)) AS `unpaid_count`,sum((case when (`i`.`payment_status` = 'overdue') then 1 else 0 end)) AS `overdue_count`,sum(`i`.`total_ttc`) AS `total_amount`,sum((case when (`i`.`payment_status` = 'paid') then `i`.`total_ttc` else 0 end)) AS `paid_amount`,sum((case when (`i`.`payment_status` <> 'paid') then `i`.`total_ttc` else 0 end)) AS `outstanding_amount`,avg((to_days(`i`.`paid_date`) - to_days(`i`.`issue_date`))) AS `avg_payment_delay_days` from `erp_invoices` `i` where (`i`.`status` <> 'cancelled') group by `i`.`customer_id`,date_format(`i`.`issue_date`,'%Y-%m') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `sales_dashboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;


/*!50001 VIEW `sales_dashboard` AS select cast(`o`.`created_at` as date) AS `date`,count(0) AS `total_opportunities`,sum((case when (`o`.`stage` = 'closed_won') then `o`.`amount` else 0 end)) AS `revenue`,sum((case when (`o`.`stage` = 'closed_won') then 1 else 0 end)) AS `deals_won`,sum((case when (`o`.`stage` = 'closed_lost') then 1 else 0 end)) AS `deals_lost`,avg(`o`.`amount`) AS `avg_deal_size`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name` from (`opportunities` `o` left join `users` `u` on((`o`.`assigned_to` = `u`.`id`))) group by cast(`o`.`created_at` as date),`o`.`assigned_to` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
