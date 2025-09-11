/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT 'default',
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `log_name` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_type` varchar(100) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` bigint(20) unsigned DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `event_type` enum('authentication','authorization','crud','api','webhook','export','import','security','system','compliance','financial','integration','notification','backup') NOT NULL DEFAULT 'crud',
  `severity` enum('emergency','alert','critical','error','warning','notice','info','debug') NOT NULL DEFAULT 'info',
  `risk_level` enum('critical','high','medium','low') NOT NULL DEFAULT 'low',
  `before_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`before_state`)),
  `after_state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`after_state`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` text DEFAULT NULL,
  `request_params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_params`)),
  `response_code` int(11) DEFAULT NULL,
  `execution_time` decimal(8,3) DEFAULT NULL,
  `memory_usage` int(10) unsigned DEFAULT NULL,
  `is_suspicious` tinyint(1) NOT NULL DEFAULT 0,
  `compliance_flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compliance_flags`)),
  `batch_id` varchar(36) DEFAULT NULL,
  `parent_log_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `activity_logs_company_id_user_id_created_at_index` (`company_id`,`user_id`,`created_at`),
  KEY `activity_logs_company_id_event_type_created_at_index` (`company_id`,`event_type`,`created_at`),
  KEY `activity_logs_company_id_risk_level_created_at_index` (`company_id`,`risk_level`,`created_at`),
  KEY `activity_logs_company_id_action_created_at_index` (`company_id`,`action`,`created_at`),
  KEY `activity_logs_is_suspicious_created_at_index` (`is_suspicious`,`created_at`),
  KEY `activity_logs_event_type_severity_created_at_index` (`event_type`,`severity`,`created_at`),
  KEY `activity_logs_company_id_index` (`company_id`),
  KEY `activity_logs_user_id_index` (`user_id`),
  KEY `activity_logs_ip_address_index` (`ip_address`),
  KEY `activity_logs_session_id_index` (`session_id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_resource_type_index` (`resource_type`),
  KEY `activity_logs_resource_id_index` (`resource_id`),
  KEY `activity_logs_module_index` (`module`),
  KEY `activity_logs_event_type_index` (`event_type`),
  KEY `activity_logs_severity_index` (`severity`),
  KEY `activity_logs_risk_level_index` (`risk_level`),
  KEY `activity_logs_response_code_index` (`response_code`),
  KEY `activity_logs_is_suspicious_index` (`is_suspicious`),
  KEY `activity_logs_batch_id_index` (`batch_id`),
  KEY `activity_logs_parent_log_id_index` (`parent_log_id`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_logs_parent_log_id_foreign` FOREIGN KEY (`parent_log_id`) REFERENCES `activity_logs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `retell_agent_id` bigint(20) unsigned NOT NULL,
  `assignment_type` varchar(255) NOT NULL,
  `criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`criteria`)),
  `priority` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `days_of_week` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`days_of_week`)),
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT 0,
  `traffic_percentage` int(11) DEFAULT NULL,
  `test_start_date` datetime DEFAULT NULL,
  `test_end_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_assignments_retell_agent_id_foreign` (`retell_agent_id`),
  KEY `agent_assignments_service_id_foreign` (`service_id`),
  KEY `agent_assignments_branch_id_foreign` (`branch_id`),
  KEY `agent_assignments_company_id_is_active_priority_index` (`company_id`,`is_active`,`priority`),
  KEY `agent_assignments_company_id_assignment_type_is_active_index` (`company_id`,`assignment_type`,`is_active`),
  CONSTRAINT `agent_assignments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agent_assignments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agent_assignments_retell_agent_id_foreign` FOREIGN KEY (`retell_agent_id`) REFERENCES `retell_agents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agent_assignments_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_performance_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `total_calls` int(11) NOT NULL DEFAULT 0,
  `avg_sentiment_score` decimal(3,2) DEFAULT NULL,
  `avg_satisfaction_score` decimal(3,2) DEFAULT NULL,
  `conversion_rate` decimal(5,2) DEFAULT NULL COMMENT 'percentage',
  `avg_call_duration_sec` int(11) DEFAULT NULL,
  `positive_calls` int(11) NOT NULL DEFAULT 0,
  `neutral_calls` int(11) NOT NULL DEFAULT 0,
  `negative_calls` int(11) NOT NULL DEFAULT 0,
  `converted_calls` int(11) NOT NULL DEFAULT 0,
  `hourly_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{hour: {calls, sentiment, conversion}}' CHECK (json_valid(`hourly_metrics`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agent_performance_metrics_agent_id_date_unique` (`agent_id`,`date`),
  KEY `agent_performance_metrics_date_avg_sentiment_score_index` (`date`,`avg_sentiment_score`),
  KEY `agent_performance_metrics_company_id_foreign` (`company_id`),
  CONSTRAINT `agent_performance_metrics_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` char(36) DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agents_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `triggered_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_history_alert_id_triggered_at_index` (`alert_id`,`triggered_at`),
  KEY `alert_history_alert_id_index` (`alert_id`),
  CONSTRAINT `alert_history_alert_id_foreign` FOREIGN KEY (`alert_id`) REFERENCES `monitoring_alerts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `anomaly_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `anomaly_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `anomaly_type` varchar(50) NOT NULL,
  `severity` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `current_value` decimal(10,2) NOT NULL,
  `expected_min` decimal(10,2) DEFAULT NULL,
  `expected_max` decimal(10,2) DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `anomaly_logs_company_id_foreign` (`company_id`),
  KEY `anomaly_logs_branch_id_foreign` (`branch_id`),
  CONSTRAINT `anomaly_logs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `anomaly_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_call_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service` varchar(255) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `request_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_headers`)),
  `request_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_body`)),
  `response_status` int(11) DEFAULT NULL,
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `response_body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_body`)),
  `duration_ms` double DEFAULT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `threat_score` int(11) NOT NULL DEFAULT 0,
  `blocked` tinyint(1) NOT NULL DEFAULT 0,
  `block_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_call_logs_service_endpoint_index` (`service`,`endpoint`),
  KEY `api_call_logs_response_status_index` (`response_status`),
  KEY `api_call_logs_correlation_id_index` (`correlation_id`),
  KEY `api_call_logs_company_id_index` (`company_id`),
  KEY `api_call_logs_requested_at_index` (`requested_at`),
  KEY `api_call_logs_service_requested_at_index` (`service`,`requested_at`),
  KEY `api_call_logs_service_response_status_requested_at_index` (`service`,`response_status`,`requested_at`),
  KEY `api_call_logs_user_id_index` (`user_id`),
  CONSTRAINT `api_call_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_credentials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credentialable_type` varchar(255) NOT NULL,
  `credentialable_id` bigint(20) unsigned NOT NULL,
  `service` varchar(255) NOT NULL,
  `key_type` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `is_inherited` tinyint(1) NOT NULL DEFAULT 0,
  `inherited_from_id` bigint(20) unsigned DEFAULT NULL,
  `inherited_from_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_credentials_credentialable_type_credentialable_id_index` (`credentialable_type`,`credentialable_id`),
  KEY `api_credentials_service_key_type_index` (`service`,`key_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_endpoint_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_endpoint_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `status_code` int(11) NOT NULL,
  `response_time` decimal(10,2) NOT NULL,
  `memory_usage` int(11) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `api_endpoint_metrics_endpoint_created_at_index` (`endpoint`,`created_at`),
  KEY `api_endpoint_metrics_endpoint_status_code_created_at_index` (`endpoint`,`status_code`,`created_at`),
  KEY `api_endpoint_metrics_endpoint_index` (`endpoint`),
  KEY `api_endpoint_metrics_user_id_index` (`user_id`),
  KEY `api_endpoint_metrics_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_health_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_health_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `response_time` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_health_logs_company_id_foreign` (`company_id`),
  CONSTRAINT `api_health_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `request` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request`)),
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response`)),
  `status_code` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointment_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_locks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lock_token` varchar(64) NOT NULL,
  `branch_id` char(36) NOT NULL,
  `staff_id` char(36) DEFAULT NULL,
  `event_type_id` bigint(20) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `holder_type` varchar(50) NOT NULL DEFAULT 'system',
  `holder_id` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_locks_lock_token_unique` (`lock_token`),
  KEY `idx_appointment_locks_branch_time` (`branch_id`,`starts_at`,`ends_at`),
  KEY `idx_appointment_locks_staff_time` (`staff_id`,`starts_at`,`ends_at`),
  KEY `appointment_locks_expires_at_index` (`expires_at`),
  KEY `appointment_locks_lock_token_index` (`lock_token`),
  CONSTRAINT `appointment_locks_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_locks_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointment_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_series` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `series_id` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `recurrence_type` enum('daily','weekly','biweekly','monthly','custom') NOT NULL,
  `recurrence_pattern` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recurrence_pattern`)),
  `recurrence_interval` int(11) NOT NULL DEFAULT 1,
  `series_start_date` date NOT NULL,
  `series_end_date` date DEFAULT NULL,
  `occurrences_count` int(11) DEFAULT NULL,
  `appointment_time` time NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `total_appointments` int(11) NOT NULL DEFAULT 0,
  `completed_appointments` int(11) NOT NULL DEFAULT 0,
  `cancelled_appointments` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
  `exceptions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exceptions`)),
  `modifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`modifications`)),
  `price_per_session` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `auto_confirm` tinyint(1) NOT NULL DEFAULT 0,
  `send_reminders` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `notes` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `cancelled_by` varchar(255) DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointment_series_company_id_foreign` (`company_id`),
  KEY `appointment_series_customer_id_foreign` (`customer_id`),
  CONSTRAINT `appointment_series_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointment_series_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_appointment_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `series_id` varchar(255) DEFAULT NULL,
  `group_booking_id` varchar(255) DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `source` varchar(255) NOT NULL DEFAULT 'phone',
  `booking_type` enum('single','recurring','group','package') NOT NULL DEFAULT 'single',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `call_id` bigint(20) unsigned DEFAULT NULL,
  `staff_id` char(36) DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `calcom_v2_booking_id` varchar(255) DEFAULT NULL,
  `calcom_event_type_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `calcom_booking_id` bigint(20) unsigned DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 0,
  `lock_expires_at` timestamp NULL DEFAULT NULL,
  `lock_token` varchar(255) DEFAULT NULL,
  `reminder_24h_sent_at` timestamp NULL DEFAULT NULL,
  `booking_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Booking context (search criteria, alternatives shown, etc)' CHECK (json_valid(`booking_metadata`)),
  `travel_time_minutes` int(11) DEFAULT NULL COMMENT 'Estimated customer travel time',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `recurrence_rule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurrence_rule`)),
  `package_sessions_total` int(11) DEFAULT NULL,
  `package_sessions_used` int(11) NOT NULL DEFAULT 0,
  `package_expires_at` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `appointments_external_id_index` (`external_id`),
  KEY `appointments_call_id_index` (`call_id`),
  KEY `appointments_staff_id_index` (`staff_id`),
  KEY `appointments_calcom_event_type_id_index` (`calcom_event_type_id`),
  KEY `appointments_version_index` (`version`),
  KEY `appointments_lock_expires_at_lock_token_index` (`lock_expires_at`,`lock_token`),
  KEY `appointments_company_id_index` (`company_id`),
  KEY `appointments_starts_at_index` (`starts_at`),
  KEY `appointments_ends_at_index` (`ends_at`),
  KEY `appointments_status_index` (`status`),
  KEY `appointments_customer_id_index` (`customer_id`),
  KEY `appointments_branch_id_index` (`branch_id`),
  KEY `appointments_service_id_index` (`service_id`),
  KEY `appointments_calcom_booking_id_index` (`calcom_booking_id`),
  KEY `appointments_calcom_v2_booking_id_index` (`calcom_v2_booking_id`),
  KEY `appointments_company_starts_at_index` (`company_id`,`starts_at`),
  KEY `appointments_company_status_index` (`company_id`,`status`),
  KEY `appointments_status_starts_at_index` (`status`,`starts_at`),
  KEY `appointments_branch_starts_at_index` (`branch_id`,`starts_at`),
  KEY `appointments_staff_starts_at_index` (`staff_id`,`starts_at`),
  KEY `appointments_reminder_24h_sent_at_index` (`reminder_24h_sent_at`),
  KEY `idx_appointments_revenue_calc` (`company_id`,`status`,`starts_at`,`service_id`),
  KEY `idx_appointments_conversion_track` (`company_id`,`call_id`,`created_at`),
  KEY `idx_appointments_branch_date` (`company_id`,`branch_id`,`starts_at`),
  KEY `idx_appointments_reminder_status` (`company_id`,`status`,`reminder_24h_sent_at`),
  KEY `idx_appointments_company_created` (`company_id`,`created_at`),
  KEY `idx_appointments_company_status` (`company_id`,`status`),
  KEY `idx_appointments_company_id` (`company_id`),
  KEY `idx_appointments_branch_id` (`branch_id`),
  KEY `idx_appointments_staff_id` (`staff_id`),
  KEY `idx_appointments_customer_id` (`customer_id`),
  KEY `idx_appointments_service_id` (`service_id`),
  KEY `idx_appointments_starts_status` (`starts_at`,`status`),
  KEY `idx_branch_appointments_time` (`branch_id`,`starts_at`,`status`),
  KEY `idx_customer_appointments` (`customer_id`,`starts_at`,`status`),
  KEY `idx_staff_schedule` (`staff_id`,`starts_at`,`ends_at`),
  KEY `idx_appointments_branch_starts` (`branch_id`,`starts_at`),
  KEY `idx_appointments_staff_date` (`staff_id`,`starts_at`),
  KEY `idx_appointments_status` (`status`),
  KEY `appointments_parent_appointment_id_foreign` (`parent_appointment_id`),
  KEY `appointments_series_id_index` (`series_id`),
  KEY `appointments_group_booking_id_index` (`group_booking_id`),
  KEY `appointments_booking_type_starts_at_index` (`booking_type`,`starts_at`),
  KEY `appointments_customer_id_series_id_index` (`customer_id`,`series_id`),
  KEY `idx_appointments_call_id` (`call_id`),
  KEY `appointments_source_index` (`source`),
  KEY `idx_appointments_company_customer_date` (`company_id`,`customer_id`,`starts_at`),
  KEY `idx_company_status_date` (`company_id`,`status`,`starts_at`),
  KEY `idx_branch_starts_ends` (`branch_id`,`starts_at`,`ends_at`),
  KEY `idx_appointments_calendar` (`branch_id`,`starts_at`,`ends_at`,`status`),
  KEY `idx_appointments_staff_starts` (`staff_id`,`starts_at`),
  KEY `idx_appointments_customer_starts` (`customer_id`,`starts_at`),
  KEY `idx_appointments_status_starts` (`status`,`starts_at`),
  KEY `idx_appointments_event_status` (`calcom_event_type_id`,`status`),
  KEY `idx_appointments_created_starts` (`created_at`,`starts_at`),
  KEY `idx_appointments_company_starts` (`company_id`,`starts_at`),
  CONSTRAINT `appointments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_calcom_event_type_id_foreign` FOREIGN KEY (`calcom_event_type_id`) REFERENCES `calcom_event_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `appointments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_parent_appointment_id_foreign` FOREIGN KEY (`parent_appointment_id`) REFERENCES `appointments` (`id`),
  CONSTRAINT `appointments_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_appointments_call` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `user_type` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `risk_level` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_trail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(255) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(255) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_trail_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `audit_trail_user_id_created_at_index` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backup_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `size` bigint(20) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL,
  `error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `backup_logs_type_status_created_at_index` (`type`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `balance_topups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `balance_topups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `status` enum('pending','processing','succeeded','failed','cancelled') NOT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `stripe_checkout_session_id` varchar(255) DEFAULT NULL,
  `stripe_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stripe_response`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `initiated_by` bigint(20) unsigned DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `stripe_invoice_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `balance_topups_stripe_payment_intent_id_unique` (`stripe_payment_intent_id`),
  UNIQUE KEY `balance_topups_stripe_checkout_session_id_unique` (`stripe_checkout_session_id`),
  KEY `balance_topups_company_id_status_index` (`company_id`,`status`),
  KEY `balance_topups_stripe_payment_intent_id_index` (`stripe_payment_intent_id`),
  KEY `balance_topups_initiated_by_foreign` (`initiated_by`),
  KEY `balance_topups_invoice_id_index` (`invoice_id`),
  KEY `balance_topups_stripe_invoice_id_index` (`stripe_invoice_id`),
  CONSTRAINT `balance_topups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `balance_topups_initiated_by_foreign` FOREIGN KEY (`initiated_by`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `balance_topups_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `balance_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `balance_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` enum('topup','charge','refund','adjustment','reservation','release','bonus','withdrawal') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `affects_bonus` tinyint(1) NOT NULL DEFAULT 0,
  `bonus_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `balance_transactions_created_by_foreign` (`created_by`),
  KEY `balance_transactions_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `balance_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  CONSTRAINT `balance_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `balance_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `portal_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_alert_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_alert_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `alert_type` enum('usage_limit','payment_reminder','subscription_renewal','overage_warning','payment_failed','budget_exceeded','low_balance','invoice_generated') NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`thresholds`)),
  `notification_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '["email"]' CHECK (json_valid(`notification_channels`)),
  `advance_days` int(11) DEFAULT NULL,
  `amount_threshold` decimal(10,2) DEFAULT NULL,
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recipients`)),
  `notify_primary_contact` tinyint(1) NOT NULL DEFAULT 1,
  `notify_billing_contact` tinyint(1) NOT NULL DEFAULT 1,
  `preferred_time` time DEFAULT NULL,
  `quiet_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`quiet_hours`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_alert_configs_company_id_alert_type_unique` (`company_id`,`alert_type`),
  KEY `billing_alert_configs_is_enabled_index` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_alert_suppressions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_alert_suppressions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `alert_type` enum('usage_limit','payment_reminder','subscription_renewal','overage_warning','payment_failed','budget_exceeded','low_balance','invoice_generated','all') NOT NULL,
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_alert_suppressions_company_id_alert_type_index` (`company_id`,`alert_type`),
  KEY `billing_alert_suppressions_starts_at_ends_at_index` (`starts_at`,`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `config_id` bigint(20) unsigned NOT NULL,
  `alert_type` enum('usage_limit','payment_reminder','subscription_renewal','overage_warning','payment_failed','budget_exceeded','low_balance','invoice_generated') NOT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `threshold_value` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','sent','failed','acknowledged') NOT NULL DEFAULT 'pending',
  `delivery_attempts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`delivery_attempts`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` char(36) DEFAULT NULL,
  `channels_used` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channels_used`)),
  `channel_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channel_results`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_alerts_config_id_foreign` (`config_id`),
  KEY `billing_alerts_company_id_status_index` (`company_id`,`status`),
  KEY `billing_alerts_alert_type_created_at_index` (`alert_type`,`created_at`),
  KEY `billing_alerts_sent_at_index` (`sent_at`),
  CONSTRAINT `billing_alerts_config_id_foreign` FOREIGN KEY (`config_id`) REFERENCES `billing_alert_configs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_bonus_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_bonus_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `min_amount` decimal(10,2) NOT NULL,
  `max_amount` decimal(10,2) DEFAULT NULL,
  `bonus_percentage` decimal(5,2) NOT NULL,
  `max_bonus_amount` decimal(10,2) DEFAULT NULL,
  `is_first_time_only` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 0,
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_until` timestamp NULL DEFAULT NULL,
  `times_used` int(11) NOT NULL DEFAULT 0,
  `total_bonus_given` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_bonus_rules_company_id_is_active_priority_index` (`company_id`,`is_active`,`priority`),
  KEY `billing_bonus_rules_valid_from_valid_until_index` (`valid_from`,`valid_until`),
  KEY `billing_bonus_rules_min_amount_index` (`min_amount`),
  CONSTRAINT `billing_bonus_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_line_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_line_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `billing_snapshot_id` bigint(20) unsigned NOT NULL,
  `branch_id` varchar(36) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_line_items_billing_snapshot_id_branch_id_index` (`billing_snapshot_id`,`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_periods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `subscription_id` char(36) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `total_minutes` decimal(10,2) NOT NULL DEFAULT 0.00,
  `used_minutes` decimal(10,2) NOT NULL DEFAULT 0.00,
  `included_minutes` int(11) NOT NULL DEFAULT 0,
  `overage_minutes` int(11) NOT NULL DEFAULT 0,
  `price_per_minute` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `base_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overage_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `margin` decimal(10,2) NOT NULL DEFAULT 0.00,
  `margin_percentage` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `is_prorated` tinyint(1) NOT NULL DEFAULT 0,
  `proration_factor` decimal(5,4) DEFAULT NULL,
  `is_invoiced` tinyint(1) NOT NULL DEFAULT 0,
  `invoiced_at` timestamp NULL DEFAULT NULL,
  `stripe_invoice_id` varchar(255) DEFAULT NULL,
  `stripe_invoice_created_at` timestamp NULL DEFAULT NULL,
  `invoice_id` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_periods_company_id_start_date_index` (`company_id`,`start_date`),
  KEY `billing_periods_subscription_id_index` (`subscription_id`),
  KEY `billing_periods_status_index` (`status`),
  KEY `billing_periods_stripe_invoice_id_index` (`stripe_invoice_id`),
  KEY `billing_periods_company_id_index` (`company_id`),
  KEY `billing_periods_branch_id_index` (`branch_id`),
  KEY `billing_periods_is_invoiced_index` (`is_invoiced`),
  KEY `billing_periods_start_date_end_date_index` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `rate_per_minute` decimal(10,4) NOT NULL DEFAULT 0.4200,
  `base_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `package_minutes` int(11) NOT NULL DEFAULT 0,
  `package_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overage_rate_per_minute` decimal(10,4) DEFAULT NULL,
  `billing_type` enum('prepaid','package','hybrid') NOT NULL DEFAULT 'prepaid',
  `billing_increment` int(11) NOT NULL DEFAULT 1,
  `minimum_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `valid_from` timestamp NULL DEFAULT NULL,
  `valid_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_rates_company_id_unique` (`company_id`),
  KEY `billing_rates_company_id_index` (`company_id`),
  KEY `billing_rates_company_id_valid_from_valid_until_index` (`company_id`,`valid_from`,`valid_until`),
  CONSTRAINT `billing_rates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `period` varchar(7) NOT NULL,
  `snapshot_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot_data`)),
  `checksum` varchar(64) NOT NULL,
  `is_finalized` tinyint(1) NOT NULL DEFAULT 0,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_snapshots_company_id_period_unique` (`company_id`,`period`),
  KEY `billing_snapshots_is_finalized_index` (`is_finalized`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_snapshots_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_snapshots_archive` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_id` bigint(20) unsigned NOT NULL,
  `snapshot_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`snapshot_data`)),
  `checksum` varchar(64) NOT NULL,
  `archived_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_snapshots_archive_original_id_index` (`original_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_spending_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_spending_limits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `daily_limit` decimal(15,2) DEFAULT NULL,
  `weekly_limit` decimal(15,2) DEFAULT NULL,
  `monthly_limit` decimal(15,2) DEFAULT NULL,
  `alert_thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`alert_thresholds`)),
  `current_day_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_week_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_month_spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `last_daily_alert_level` int(11) NOT NULL DEFAULT 0,
  `last_weekly_alert_level` int(11) NOT NULL DEFAULT 0,
  `last_monthly_alert_level` int(11) NOT NULL DEFAULT 0,
  `current_day_date` date NOT NULL DEFAULT '2025-07-05',
  `current_week_start` date NOT NULL DEFAULT '2025-06-30',
  `current_month_start` date NOT NULL DEFAULT '2025-07-01',
  `send_alerts` tinyint(1) NOT NULL DEFAULT 1,
  `last_alert_sent_at` timestamp NULL DEFAULT NULL,
  `hard_limit` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_spending_limits_company_id_unique` (`company_id`),
  KEY `billing_spending_limits_company_id_index` (`company_id`),
  KEY `idx_spending_limits_dates` (`current_day_date`,`current_week_start`,`current_month_start`),
  CONSTRAINT `billing_spending_limits_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_event_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` char(36) NOT NULL,
  `event_type_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_event_types_branch_id_event_type_id_unique` (`branch_id`,`event_type_id`),
  KEY `branch_event_types_branch_id_event_type_id_index` (`branch_id`,`event_type_id`),
  KEY `branch_event_types_branch_id_is_primary_index` (`branch_id`,`is_primary`),
  KEY `branch_event_types_event_type_id_foreign` (`event_type_id`),
  CONSTRAINT `branch_event_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_event_types_event_type_id_foreign` FOREIGN KEY (`event_type_id`) REFERENCES `calcom_event_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_service` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` char(36) NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_service_branch_id_service_id_unique` (`branch_id`,`service_id`),
  KEY `branch_service_branch_id_index` (`branch_id`),
  KEY `branch_service_service_id_index` (`service_id`),
  CONSTRAINT `branch_service_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_service_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `notification_email` varchar(255) DEFAULT NULL,
  `send_call_summaries` tinyint(1) DEFAULT NULL COMMENT 'Override company setting',
  `call_summary_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Branch-specific recipients' CHECK (json_valid(`call_summary_recipients`)),
  `include_transcript_in_summary` tinyint(1) DEFAULT NULL,
  `include_csv_export` tinyint(1) DEFAULT NULL,
  `summary_email_frequency` enum('immediate','hourly','daily') DEFAULT NULL,
  `call_notification_overrides` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`call_notification_overrides`)),
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `invoice_recipient` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_name` varchar(255) DEFAULT NULL,
  `invoice_email` varchar(255) DEFAULT NULL,
  `invoice_address` varchar(255) DEFAULT NULL,
  `invoice_phone` varchar(255) DEFAULT NULL,
  `calcom_event_type_id` varchar(255) DEFAULT NULL COMMENT 'DEPRECATED - Use branch_event_types table instead',
  `calcom_api_key` text DEFAULT NULL,
  `retell_agent_id` varchar(255) DEFAULT NULL,
  `integration_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`integration_status`)),
  `calendar_mode` enum('inherit','override') NOT NULL DEFAULT 'inherit',
  `integrations_tested_at` timestamp NULL DEFAULT NULL,
  `calcom_user_id` varchar(255) DEFAULT NULL,
  `retell_agent_cache` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`retell_agent_cache`)),
  `retell_last_sync` timestamp NULL DEFAULT NULL,
  `configuration_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration_status`)),
  `parent_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parent_settings`)),
  `address` varchar(255) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `services_override` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services_override`)),
  `country` varchar(255) NOT NULL DEFAULT 'Deutschland',
  `uuid` varchar(255) NOT NULL DEFAULT uuid(),
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Latitude and longitude' CHECK (json_valid(`coordinates`)),
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `transport_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transport_info`)),
  `service_radius_km` int(11) DEFAULT 0 COMMENT 'Service area radius',
  `accepts_walkins` tinyint(1) NOT NULL DEFAULT 0,
  `parking_available` tinyint(1) NOT NULL DEFAULT 0,
  `public_transport_access` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_slug_unique` (`slug`),
  KEY `branches_customer_id_index` (`customer_id`),
  KEY `branches_uuid_index` (`uuid`),
  KEY `branches_company_id_index` (`company_id`),
  KEY `branches_active_index` (`active`),
  KEY `branches_company_active_index` (`company_id`,`active`),
  KEY `idx_branches_company_name` (`company_id`,`name`),
  KEY `idx_branches_company_active` (`company_id`,`is_active`),
  KEY `idx_branches_company_id` (`company_id`),
  KEY `idx_branches_is_active` (`is_active`),
  KEY `idx_company_active_branches` (`company_id`,`is_active`,`id`),
  KEY `idx_calcom_event_lookup` (`calcom_event_type_id`,`is_active`),
  KEY `idx_branches_active` (`is_active`,`company_id`),
  KEY `idx_company_active` (`company_id`,`is_active`),
  CONSTRAINT `branches_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branches_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches_numeric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches_numeric` (
  `id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `invoice_recipient` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_name` varchar(255) DEFAULT NULL,
  `invoice_email` varchar(255) DEFAULT NULL,
  `invoice_address` varchar(255) DEFAULT NULL,
  `invoice_phone` varchar(255) DEFAULT NULL,
  `calcom_event_type_id` varchar(255) DEFAULT NULL COMMENT 'DEPRECATED - Use branch_event_types table instead',
  `calcom_api_key` text DEFAULT NULL,
  `retell_agent_id` varchar(255) DEFAULT NULL,
  `integration_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`integration_status`)),
  `calendar_mode` enum('inherit','override') NOT NULL DEFAULT 'inherit',
  `integrations_tested_at` timestamp NULL DEFAULT NULL,
  `calcom_user_id` varchar(255) DEFAULT NULL,
  `retell_agent_cache` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`retell_agent_cache`)),
  `retell_last_sync` timestamp NULL DEFAULT NULL,
  `configuration_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration_status`)),
  `parent_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parent_settings`)),
  `address` varchar(255) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `business_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`business_hours`)),
  `services_override` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services_override`)),
  `country` varchar(255) NOT NULL DEFAULT 'Deutschland',
  `uuid` varchar(255) NOT NULL DEFAULT uuid(),
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Latitude and longitude' CHECK (json_valid(`coordinates`)),
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `transport_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transport_info`)),
  `service_radius_km` int(11) DEFAULT 0 COMMENT 'Service area radius',
  `accepts_walkins` tinyint(1) NOT NULL DEFAULT 0,
  `parking_available` tinyint(1) NOT NULL DEFAULT 0,
  `public_transport_access` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_slug_unique` (`slug`),
  KEY `branches_customer_id_index` (`customer_id`),
  KEY `branches_uuid_index` (`uuid`),
  KEY `branches_company_id_index` (`company_id`),
  KEY `branches_active_index` (`active`),
  KEY `branches_company_active_index` (`company_id`,`active`),
  KEY `idx_branches_company_name` (`company_id`,`name`),
  KEY `idx_branches_company_active` (`company_id`,`is_active`),
  KEY `idx_branches_company_id` (`company_id`),
  KEY `idx_branches_is_active` (`is_active`),
  KEY `idx_company_active_branches` (`company_id`,`is_active`,`id`),
  KEY `idx_calcom_event_lookup` (`calcom_event_type_id`,`is_active`),
  KEY `idx_branches_active` (`is_active`,`company_id`),
  KEY `idx_company_active` (`company_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext DEFAULT NULL,
  `expiration` int(11) DEFAULT NULL,
  PRIMARY KEY (`key`),
  KEY `idx_expiration` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calcom_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calcom_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `calcom_uid` varchar(255) DEFAULT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `event_type_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calcom_bookings_status_index` (`status`),
  KEY `calcom_bookings_appointment_id_status_index` (`appointment_id`,`status`),
  KEY `calcom_bookings_calcom_uid_index` (`calcom_uid`),
  KEY `idx_event_type_id` (`event_type_id`),
  CONSTRAINT `calcom_bookings_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calcom_bookings_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calcom_bookings_backup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `calcom_booking_id` bigint(20) unsigned NOT NULL,
  `booking_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`booking_data`)),
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NOT NULL,
  `status` varchar(50) NOT NULL,
  `attendee_email` varchar(255) DEFAULT NULL,
  `synced_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calcom_bookings_backup_calcom_booking_id_unique` (`calcom_booking_id`),
  KEY `calcom_bookings_backup_company_id_starts_at_index` (`company_id`,`starts_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calcom_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calcom_event_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` char(36) NOT NULL,
  `staff_id` char(36) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `calcom_numeric_event_type_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Die numerische Event Type ID von Cal.com (z.B. 12345)',
  `team_id` int(11) DEFAULT NULL COMMENT 'Cal.com Team ID for team event types',
  `is_team_event` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this is a team event type',
  `requires_confirmation` tinyint(1) NOT NULL DEFAULT 0,
  `duration_minutes` int(11) DEFAULT NULL COMMENT 'Dauer der Dienstleistung in Minuten',
  `description` text DEFAULT NULL COMMENT 'Beschreibung der Dienstleistung/des Event Typs',
  `price` decimal(8,2) DEFAULT NULL COMMENT 'Preis der Dienstleistung',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Ist dieser Event Type aktiv und buchbar?',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `minimum_booking_notice` int(11) DEFAULT NULL COMMENT 'Minimum Vorlaufzeit in Minuten',
  `booking_future_limit` int(11) DEFAULT NULL COMMENT 'Maximale Buchungsreichweite in Tagen',
  `time_slot_interval` int(11) DEFAULT NULL COMMENT 'Zeitschritte in Minuten (z.B. 15, 30)',
  `buffer_before` int(11) DEFAULT NULL COMMENT 'Pufferzeit vor dem Termin in Minuten',
  `buffer_after` int(11) DEFAULT NULL COMMENT 'Pufferzeit nach dem Termin in Minuten',
  `locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Verfügbare Locations [{type, value, displayName}]' CHECK (json_valid(`locations`)),
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Custom Fields Konfiguration' CHECK (json_valid(`custom_fields`)),
  `max_bookings_per_day` int(11) DEFAULT NULL COMMENT 'Max Buchungen pro Tag',
  `seats_per_time_slot` int(11) DEFAULT NULL COMMENT 'Plätze pro Zeitslot für Gruppenbuchungen',
  `schedule_id` varchar(255) DEFAULT NULL COMMENT 'Cal.com Schedule ID für Verfügbarkeiten',
  `recurring_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Wiederkehrende Event Konfiguration' CHECK (json_valid(`recurring_config`)),
  `setup_status` enum('incomplete','partial','complete') NOT NULL DEFAULT 'incomplete' COMMENT 'Setup-Status des Event Types',
  `setup_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Checklist was noch konfiguriert werden muss' CHECK (json_valid(`setup_checklist`)),
  `webhook_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Webhook-spezifische Einstellungen' CHECK (json_valid(`webhook_settings`)),
  `calcom_url` varchar(255) DEFAULT NULL COMMENT 'Direkt-Link zum Event Type in Cal.com',
  `booking_limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`booking_limits`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `calcom_event_type_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_branch_event_name` (`branch_id`,`name`),
  KEY `calcom_event_types_staff_id_index` (`staff_id`),
  KEY `calcom_event_types_calcom_numeric_event_type_id_index` (`calcom_numeric_event_type_id`),
  KEY `calcom_event_types_company_id_index` (`company_id`),
  KEY `calcom_event_types_setup_status_index` (`setup_status`),
  KEY `calcom_event_types_company_id_setup_status_index` (`company_id`,`setup_status`),
  KEY `calcom_event_types_calcom_numeric_event_type_id_team_id_index` (`calcom_numeric_event_type_id`,`team_id`),
  KEY `calcom_event_types_branch_id_index` (`branch_id`),
  KEY `calcom_event_types_slug_index` (`slug`),
  KEY `idx_event_types_company_active` (`company_id`,`is_active`),
  KEY `idx_event_types_branch_active` (`branch_id`,`is_active`),
  KEY `idx_event_types_duration` (`duration_minutes`),
  KEY `idx_event_types_price` (`price`),
  CONSTRAINT `calcom_event_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calcom_event_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calcom_event_types_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calcom_event_types_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calcom_event_types_backup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `calcom_event_type_id` bigint(20) unsigned NOT NULL,
  `event_type_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`event_type_data`)),
  `synced_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calcom_event_types_backup_calcom_event_type_id_unique` (`calcom_event_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_event_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` char(36) NOT NULL,
  `provider` varchar(255) NOT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 30,
  `price` decimal(10,2) DEFAULT NULL,
  `provider_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_data`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_event_types_branch_id_provider_external_id_unique` (`branch_id`,`provider`,`external_id`),
  KEY `calendar_event_types_branch_id_provider_index` (`branch_id`,`provider`),
  CONSTRAINT `calendar_event_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendar_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` char(36) NOT NULL,
  `staff_id` char(36) NOT NULL,
  `calendar_type` enum('company','branch','personal') NOT NULL,
  `calendar_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calendar_details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendar_mappings_staff_id_foreign` (`staff_id`),
  KEY `calendar_mappings_branch_id_staff_id_index` (`branch_id`,`staff_id`),
  CONSTRAINT `calendar_mappings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calendar_mappings_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendars` (
  `id` char(36) NOT NULL,
  `staff_id` char(36) NOT NULL,
  `provider` enum('calcom','google') NOT NULL DEFAULT 'calcom',
  `api_key` text DEFAULT NULL,
  `event_type_id` varchar(255) DEFAULT NULL,
  `external_user_id` varchar(255) DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendars_staff_id_index` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `call_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `activity_type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `call_activities_user_id_foreign` (`user_id`),
  KEY `call_activities_call_id_created_at_index` (`call_id`,`created_at`),
  KEY `call_activities_company_id_index` (`company_id`),
  KEY `call_activities_activity_type_index` (`activity_type`),
  CONSTRAINT `call_activities_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_activities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `call_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) unsigned NOT NULL,
  `assigned_by` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned NOT NULL,
  `previous_assignee` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `call_assignments_previous_assignee_foreign` (`previous_assignee`),
  KEY `call_assignments_call_id_created_at_index` (`call_id`,`created_at`),
  KEY `call_assignments_assigned_to_index` (`assigned_to`),
  KEY `call_assignments_assigned_by_index` (`assigned_by`),
  CONSTRAINT `call_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_assignments_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_assignments_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_assignments_previous_assignee_foreign` FOREIGN KEY (`previous_assignee`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `call_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_charges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `duration_seconds` int(11) NOT NULL,
  `rate_per_minute` decimal(10,4) NOT NULL,
  `amount_charged` decimal(15,2) NOT NULL,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refund_status` enum('none','partial','full') NOT NULL DEFAULT 'none',
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_reason` varchar(255) DEFAULT NULL,
  `refund_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `balance_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `charged_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_charges_call_id_unique` (`call_id`),
  KEY `call_charges_balance_transaction_id_foreign` (`balance_transaction_id`),
  KEY `call_charges_company_id_charged_at_index` (`company_id`,`charged_at`),
  KEY `call_charges_call_id_index` (`call_id`),
  KEY `call_charges_refund_status_index` (`refund_status`),
  KEY `call_charges_refunded_at_index` (`refunded_at`),
  CONSTRAINT `call_charges_balance_transaction_id_foreign` FOREIGN KEY (`balance_transaction_id`) REFERENCES `balance_transactions` (`id`),
  CONSTRAINT `call_charges_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_charges_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `call_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'general',
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `call_notes_user_id_foreign` (`user_id`),
  KEY `call_notes_call_id_created_at_index` (`call_id`,`created_at`),
  KEY `call_notes_type_index` (`type`),
  CONSTRAINT `call_notes_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_notes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `call_portal_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_portal_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) unsigned NOT NULL,
  `status` enum('new','in_progress','callback_scheduled','not_reached_1','not_reached_2','not_reached_3','completed','abandoned','requires_action') NOT NULL DEFAULT 'new',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `priority` enum('high','medium','low') NOT NULL DEFAULT 'medium',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `next_action_date` datetime DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `follow_up_count` int(11) NOT NULL DEFAULT 0,
  `resolution_notes` text DEFAULT NULL,
  `callback_scheduled_at` datetime DEFAULT NULL,
  `callback_scheduled_by` bigint(20) unsigned DEFAULT NULL,
  `callback_notes` text DEFAULT NULL,
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `assigned_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `call_portal_data_call_id_foreign` (`call_id`),
  KEY `call_portal_data_assigned_to_foreign` (`assigned_to`),
  KEY `call_portal_data_callback_scheduled_by_foreign` (`callback_scheduled_by`),
  KEY `call_portal_data_status_assigned_to_index` (`status`,`assigned_to`),
  KEY `call_portal_data_next_action_date_index` (`next_action_date`),
  KEY `call_portal_data_callback_scheduled_at_index` (`callback_scheduled_at`),
  CONSTRAINT `call_portal_data_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `call_portal_data_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `call_portal_data_callback_scheduled_by_foreign` FOREIGN KEY (`callback_scheduled_by`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `callback_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `callback_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `call_id` bigint(20) unsigned NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `requested_service` text DEFAULT NULL,
  `requested_date` date DEFAULT NULL,
  `requested_time` time DEFAULT NULL,
  `reason` enum('calcom_error','no_availability','technical_error','customer_request') NOT NULL,
  `error_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`error_details`)),
  `call_summary` text DEFAULT NULL,
  `priority` enum('urgent','high','normal','low') NOT NULL DEFAULT 'normal',
  `status` enum('pending','in_progress','completed','auto_closed','cancelled') NOT NULL DEFAULT 'pending',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `auto_close_after_hours` int(11) NOT NULL DEFAULT 24,
  `processed_at` timestamp NULL DEFAULT NULL,
  `auto_closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_company` (`status`,`company_id`,`created_at`),
  KEY `idx_phone` (`customer_phone`),
  KEY `idx_priority` (`priority`,`status`),
  KEY `callback_requests_company_id_foreign` (`company_id`),
  KEY `callback_requests_call_id_foreign` (`call_id`),
  KEY `callback_requests_branch_id_foreign` (`branch_id`),
  KEY `callback_requests_assigned_to_foreign` (`assigned_to`),
  KEY `callback_requests_completed_by_foreign` (`completed_by`),
  CONSTRAINT `callback_requests_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `callback_requests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `callback_requests_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `callback_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `callback_requests_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(255) DEFAULT NULL,
  `transcript` text DEFAULT NULL,
  `raw` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `kunde_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `consent_given` tinyint(1) NOT NULL DEFAULT 0,
  `data_forwarded` tinyint(1) NOT NULL DEFAULT 0,
  `consent_at` timestamp NULL DEFAULT NULL,
  `forwarded_at` timestamp NULL DEFAULT NULL,
  `retell_call_id` varchar(255) NOT NULL,
  `from_number` varchar(255) DEFAULT NULL,
  `to_number` varchar(255) DEFAULT NULL,
  `duration_sec` int(10) unsigned DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL COMMENT 'Duration in milliseconds from Retell',
  `wait_time_sec` int(11) DEFAULT NULL,
  `tmp_call_id` char(36) DEFAULT NULL,
  `phone_number_id` bigint(20) unsigned DEFAULT NULL,
  `agent_id` varchar(255) DEFAULT NULL,
  `cost_cents` int(10) unsigned DEFAULT NULL,
  `sentiment_score` double DEFAULT NULL,
  `call_status` varchar(255) DEFAULT NULL,
  `session_outcome` varchar(50) DEFAULT NULL,
  `call_successful` tinyint(1) DEFAULT NULL,
  `analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analysis`)),
  `conversation_id` char(36) DEFAULT NULL,
  `call_id` varchar(255) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `audio_url` varchar(255) DEFAULT NULL,
  `recording_url` varchar(255) DEFAULT NULL,
  `disconnection_reason` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `sentiment` varchar(255) DEFAULT NULL,
  `detected_language` varchar(5) DEFAULT NULL,
  `language_confidence` decimal(3,2) DEFAULT NULL,
  `language_mismatch` tinyint(1) NOT NULL DEFAULT 0,
  `public_log_url` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `health_insurance_company` varchar(255) DEFAULT NULL,
  `datum_termin` date DEFAULT NULL,
  `uhrzeit_termin` time DEFAULT NULL,
  `dienstleistung` varchar(255) DEFAULT NULL,
  `reason_for_visit` text DEFAULT NULL,
  `telefonnummer` varchar(255) DEFAULT NULL,
  `grund` text DEFAULT NULL,
  `calcom_booking_id` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `call_time` timestamp NULL DEFAULT NULL,
  `call_duration` int(11) DEFAULT NULL,
  `disconnect_reason` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT 'inbound',
  `cost` decimal(10,2) DEFAULT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT 1,
  `user_sentiment` varchar(255) DEFAULT NULL,
  `raw_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_data`)),
  `behandlung_dauer` varchar(255) DEFAULT NULL,
  `rezeptstatus` varchar(255) DEFAULT NULL,
  `versicherungsstatus` varchar(255) DEFAULT NULL,
  `haustiere_name` varchar(255) DEFAULT NULL,
  `notiz` text DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `appointment_made` tinyint(1) NOT NULL DEFAULT 0,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `start_timestamp` timestamp NULL DEFAULT NULL,
  `end_timestamp` timestamp NULL DEFAULT NULL,
  `call_type` varchar(20) DEFAULT NULL,
  `direction` varchar(20) DEFAULT NULL,
  `transcript_object` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transcript_object`)),
  `transcript_with_tools` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transcript_with_tools`)),
  `latency_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`latency_metrics`)),
  `end_to_end_latency` int(11) DEFAULT NULL,
  `cost_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cost_breakdown`)),
  `llm_usage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`llm_usage`)),
  `retell_dynamic_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`retell_dynamic_variables`)),
  `opt_out_sensitive_data` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `duration_minutes` decimal(10,2) DEFAULT NULL,
  `webhook_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`webhook_data`)),
  `agent_version` varchar(50) DEFAULT NULL,
  `retell_cost` decimal(10,4) DEFAULT NULL,
  `custom_sip_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_sip_headers`)),
  `appointment_requested` tinyint(1) NOT NULL DEFAULT 0,
  `extracted_date` varchar(255) DEFAULT NULL,
  `extracted_time` varchar(255) DEFAULT NULL,
  `extracted_name` varchar(255) DEFAULT NULL,
  `extracted_email` varchar(255) DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 0,
  `duration` int(11) DEFAULT NULL COMMENT 'Call duration in seconds',
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `retell_agent_id` varchar(255) DEFAULT NULL,
  `transcription_id` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `caller` varchar(255) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL COMMENT 'Full name of the AI agent',
  `urgency_level` varchar(255) DEFAULT NULL COMMENT 'Call urgency: high/medium/low',
  `no_show_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Previous no-shows',
  `reschedule_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of reschedules',
  `first_visit` tinyint(1) DEFAULT NULL COMMENT 'Is first visit',
  `insurance_type` varchar(255) DEFAULT NULL COMMENT 'Type of insurance',
  `insurance_company` varchar(255) DEFAULT NULL COMMENT 'Insurance provider',
  `custom_analysis_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'All custom analysis fields' CHECK (json_valid(`custom_analysis_data`)),
  `customer_data_backup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`customer_data_backup`)),
  `customer_data_collected_at` timestamp NULL DEFAULT NULL,
  `call_summary` text DEFAULT NULL COMMENT 'AI-generated call summary',
  `llm_token_usage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Token usage statistics' CHECK (json_valid(`llm_token_usage`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `calls_retell_call_id_unique` (`retell_call_id`),
  UNIQUE KEY `calls_retell_call_id_index` (`retell_call_id`),
  KEY `calls_external_id_index` (`external_id`),
  KEY `calls_kunde_id_foreign` (`kunde_id`),
  KEY `calls_conversation_id_index` (`conversation_id`),
  KEY `calls_call_id_index` (`call_id`),
  KEY `calls_appointment_id_index` (`appointment_id`),
  KEY `calls_direction_index` (`direction`),
  KEY `calls_disconnection_reason_index` (`disconnection_reason`),
  KEY `calls_start_timestamp_end_timestamp_index` (`start_timestamp`,`end_timestamp`),
  KEY `calls_version_index` (`version`),
  KEY `calls_company_id_index` (`company_id`),
  KEY `calls_created_at_index` (`created_at`),
  KEY `calls_start_timestamp_index` (`start_timestamp`),
  KEY `calls_from_number_index` (`from_number`),
  KEY `calls_to_number_index` (`to_number`),
  KEY `calls_call_status_index` (`call_status`),
  KEY `calls_customer_id_index` (`customer_id`),
  KEY `calls_company_created_at_index` (`company_id`,`created_at`),
  KEY `calls_customer_created_at_index` (`customer_id`,`created_at`),
  KEY `calls_company_call_status_index` (`company_id`,`call_status`),
  KEY `calls_duration_sec_index` (`duration_sec`),
  KEY `calls_cost_index` (`cost`),
  KEY `idx_calls_company_date` (`company_id`,`created_at`),
  KEY `idx_calls_status_duration` (`company_id`,`call_status`,`duration_sec`),
  KEY `idx_calls_phone_normalized` (`company_id`,`from_number`),
  KEY `idx_calls_sentiment_date` (`company_id`,`created_at`),
  KEY `idx_calls_created_company` (`created_at`,`company_id`),
  KEY `idx_calls_retell_call_id` (`retell_call_id`),
  KEY `idx_calls_company_id` (`company_id`),
  KEY `idx_calls_appointment_id` (`appointment_id`),
  KEY `idx_calls_call_id` (`call_id`),
  KEY `idx_calls_created_at` (`created_at`),
  KEY `idx_company_recent_calls` (`company_id`,`created_at`,`call_status`),
  KEY `idx_phone_call_history` (`from_number`,`created_at`),
  KEY `idx_retell_call_status` (`retell_call_id`,`call_status`),
  KEY `calls_status_index` (`status`),
  KEY `calls_company_id_status_index` (`company_id`,`status`),
  KEY `idx_calls_company_created` (`company_id`,`created_at`),
  KEY `idx_calls_branch_status` (`status`),
  KEY `idx_calls_customer_id` (`customer_id`),
  KEY `idx_calls_company_agent` (`company_id`,`agent_id`),
  KEY `idx_calls_company_phone` (`company_id`,`to_number`),
  KEY `idx_calls_company_status_time` (`company_id`,`call_status`,`start_timestamp`),
  KEY `idx_calls_company_time` (`company_id`,`start_timestamp`),
  KEY `idx_company_timestamp` (`company_id`,`start_timestamp`),
  KEY `idx_company_status` (`company_id`,`call_status`),
  KEY `idx_company_customer` (`company_id`,`customer_id`),
  KEY `idx_company_appointment` (`company_id`,`appointment_id`),
  KEY `idx_from_number` (`from_number`),
  KEY `idx_company_created` (`company_id`,`created_at`),
  KEY `idx_calls_conversation_id` (`conversation_id`),
  KEY `idx_calls_customer` (`customer_id`),
  KEY `idx_status_created` (`status`,`created_at`),
  KEY `idx_calls_phone_number` (`phone_number`),
  KEY `idx_calls_company_start_timestamp` (`company_id`,`start_timestamp`),
  KEY `idx_calls_to_number` (`to_number`),
  KEY `idx_calls_status_recent` (`call_status`,`created_at`),
  KEY `calls_recording_url_index` (`recording_url`),
  KEY `calls_retell_agent_id_index` (`retell_agent_id`),
  KEY `calls_session_outcome_index` (`session_outcome`),
  KEY `calls_appointment_made_index` (`appointment_made`),
  KEY `calls_branch_id_index` (`branch_id`),
  CONSTRAINT `calls_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `calls_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `calls_kunde_id_foreign` FOREIGN KEY (`kunde_id`) REFERENCES `kunden` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) NOT NULL,
  `custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Custom data for this target' CHECK (json_valid(`custom_data`)),
  `status` enum('pending','calling','completed','failed','skipped') NOT NULL DEFAULT 'pending',
  `attempt_count` int(11) NOT NULL DEFAULT 0,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `call_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_targets_call_id_foreign` (`call_id`),
  KEY `campaign_targets_campaign_id_status_index` (`campaign_id`,`status`),
  KEY `campaign_targets_phone_number_index` (`phone_number`),
  KEY `idx_campaign_status_date` (`campaign_id`,`status`,`created_at`),
  KEY `idx_target_phone_campaign` (`phone_number`,`campaign_id`),
  CONSTRAINT `campaign_targets_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `campaign_targets_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `retell_ai_call_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `circuit_breaker_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `circuit_breaker_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service` varchar(50) NOT NULL,
  `status` enum('success','failure') NOT NULL,
  `state` enum('closed','open','half_open') NOT NULL,
  `duration_ms` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `circuit_breaker_metrics_service_status_created_at_index` (`service`,`status`,`created_at`),
  KEY `circuit_breaker_metrics_service_state_created_at_index` (`service`,`state`,`created_at`),
  KEY `circuit_breaker_metrics_created_at_index` (`created_at`),
  KEY `circuit_breaker_metrics_service_index` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `command_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `command_executions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `command_template_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `workflow_execution_id` bigint(20) unsigned DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `status` enum('pending','running','success','failed','cancelled') NOT NULL DEFAULT 'pending',
  `progress` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `execution_time_ms` int(11) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `output` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`output`)),
  `error_message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `command_executions_status_index` (`status`),
  KEY `command_executions_user_id_index` (`user_id`),
  KEY `command_executions_company_id_index` (`company_id`),
  KEY `command_executions_command_template_id_index` (`command_template_id`),
  KEY `command_executions_workflow_execution_id_index` (`workflow_execution_id`),
  KEY `command_executions_correlation_id_index` (`correlation_id`),
  KEY `command_executions_created_at_index` (`created_at`),
  CONSTRAINT `command_executions_command_template_id_foreign` FOREIGN KEY (`command_template_id`) REFERENCES `command_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `command_executions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `command_executions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `command_executions_workflow_execution_id_foreign` FOREIGN KEY (`workflow_execution_id`) REFERENCES `workflow_executions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `command_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `command_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `command_template_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `command_favorites_user_id_command_template_id_unique` (`user_id`,`command_template_id`),
  KEY `command_favorites_command_template_id_foreign` (`command_template_id`),
  CONSTRAINT `command_favorites_command_template_id_foreign` FOREIGN KEY (`command_template_id`) REFERENCES `command_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `command_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `command_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `command_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `description` text DEFAULT NULL,
  `command_template` text NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `nlp_keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`nlp_keywords`)),
  `shortcut` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_premium` tinyint(1) NOT NULL DEFAULT 0,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `success_rate` double NOT NULL DEFAULT 100,
  `avg_execution_time` double NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `command_templates_name_unique` (`name`),
  KEY `command_templates_category_index` (`category`),
  KEY `command_templates_is_public_index` (`is_public`),
  KEY `command_templates_company_id_index` (`company_id`),
  KEY `command_templates_created_by_index` (`created_by`),
  KEY `command_templates_usage_count_index` (`usage_count`),
  CONSTRAINT `command_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `command_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `command_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `command_workflows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `avg_execution_time` double NOT NULL DEFAULT 0,
  `success_rate` double NOT NULL DEFAULT 100,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `schedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`schedule`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `command_workflows_company_id_index` (`company_id`),
  KEY `command_workflows_created_by_index` (`created_by`),
  KEY `command_workflows_is_public_index` (`is_public`),
  KEY `command_workflows_is_active_index` (`is_active`),
  CONSTRAINT `command_workflows_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `command_workflows_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `billing_contact_email` varchar(255) DEFAULT NULL,
  `billing_contact_phone` varchar(255) DEFAULT NULL,
  `usage_budget` decimal(10,2) DEFAULT NULL,
  `alerts_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `opening_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opening_hours`)),
  `calcom_api_key` text DEFAULT NULL,
  `calcom_team_slug` varchar(255) DEFAULT NULL,
  `calcom_user_id` varchar(255) DEFAULT NULL,
  `calcom_team_id` int(11) DEFAULT NULL,
  `retell_api_key` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `send_call_summaries` tinyint(1) NOT NULL DEFAULT 1,
  `call_summary_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`call_summary_recipients`)),
  `include_transcript_in_summary` tinyint(1) NOT NULL DEFAULT 0,
  `include_csv_export` tinyint(1) NOT NULL DEFAULT 1,
  `summary_email_frequency` enum('immediate','hourly','daily') NOT NULL DEFAULT 'immediate',
  `call_notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional notification settings' CHECK (json_valid(`call_notification_settings`)),
  `notification_provider` enum('calcom','twilio','none') NOT NULL DEFAULT 'calcom' COMMENT 'Which provider to use for SMS/WhatsApp notifications',
  `calcom_handles_notifications` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Let Cal.com handle all appointment notifications',
  `email_notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `balance_warning_sent_at` timestamp NULL DEFAULT NULL,
  `calcom_event_type_id` varchar(255) DEFAULT NULL,
  `api_test_errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`api_test_errors`)),
  `send_booking_confirmations` tinyint(1) NOT NULL DEFAULT 1,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `retell_webhook_url` varchar(255) DEFAULT 'https://api.askproai.de/api/retell/webhook',
  `retell_agent_id` varchar(255) DEFAULT NULL,
  `retell_voice` varchar(50) DEFAULT 'nova',
  `retell_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `retell_default_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`retell_default_settings`)),
  `calcom_calendar_mode` enum('zentral','filiale','mitarbeiter') NOT NULL DEFAULT 'zentral',
  `billing_status` enum('active','inactive','trial','suspended') NOT NULL DEFAULT 'trial',
  `billing_type` enum('prepaid','postpaid') NOT NULL DEFAULT 'postpaid',
  `credit_balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Guthaben für Prepaid-Kunden',
  `low_credit_threshold` decimal(10,2) NOT NULL DEFAULT 10.00,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `alert_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`alert_preferences`)),
  `industry` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `subscription_status` varchar(50) DEFAULT NULL,
  `subscription_plan` varchar(50) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'DE',
  `timezone` varchar(50) NOT NULL DEFAULT 'Europe/Berlin',
  `default_language` varchar(5) NOT NULL DEFAULT 'de',
  `supported_languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supported_languages`)),
  `auto_translate` tinyint(1) NOT NULL DEFAULT 1,
  `translation_provider` varchar(255) NOT NULL DEFAULT 'deepl',
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `google_calendar_credentials` text DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `security_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_settings`)),
  `allowed_ip_addresses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_ip_addresses`)),
  `webhook_signing_secret` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(20) NOT NULL DEFAULT 'net30',
  `small_business_threshold_date` date DEFAULT NULL,
  `default_event_type_id` bigint(20) unsigned DEFAULT NULL,
  `prepaid_billing_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_slug_unique` (`slug`),
  KEY `companies_industry_index` (`industry`),
  KEY `companies_subscription_status_index` (`subscription_status`),
  KEY `companies_slug_index` (`slug`),
  KEY `companies_country_index` (`country`),
  KEY `companies_is_active_index` (`is_active`),
  KEY `companies_deleted_at_index` (`deleted_at`),
  KEY `companies_default_event_type_id_foreign` (`default_event_type_id`),
  KEY `idx_companies_active` (`is_active`),
  KEY `companies_default_language_index` (`default_language`),
  CONSTRAINT `companies_default_event_type_id_foreign` FOREIGN KEY (`default_event_type_id`) REFERENCES `calcom_event_types` (`id`),
  CONSTRAINT `chk_retell_api_key_encrypted` CHECK (`retell_api_key` is null or `retell_api_key` like 'eyJ%'),
  CONSTRAINT `chk_calcom_api_key_encrypted` CHECK (`calcom_api_key` is null or `calcom_api_key` like 'eyJ%')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_goals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_goals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `template_type` varchar(255) DEFAULT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_goals_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `company_goals_start_date_end_date_index` (`start_date`,`end_date`),
  CONSTRAINT `company_goals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_pricing` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `price_per_minute` decimal(10,4) NOT NULL COMMENT 'Preis pro Minute in EUR',
  `setup_fee` decimal(10,2) DEFAULT NULL COMMENT 'Einrichtungsgebühr',
  `monthly_base_fee` decimal(10,2) DEFAULT NULL COMMENT 'Monatliche Grundgebühr',
  `included_minutes` int(11) NOT NULL DEFAULT 0 COMMENT 'Inkludierte Minuten pro Monat',
  `overage_price_per_minute` decimal(10,4) DEFAULT NULL COMMENT 'Preis für Minuten über Kontingent',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `valid_from` date NOT NULL DEFAULT '2025-06-26',
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_pricing_company_id_is_active_valid_from_index` (`company_id`,`is_active`,`valid_from`),
  CONSTRAINT `company_pricing_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_pricing_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_pricing_tiers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `child_company_id` bigint(20) unsigned DEFAULT NULL,
  `pricing_type` enum('inbound','outbound','sms','monthly','setup') NOT NULL,
  `cost_price` decimal(10,4) NOT NULL COMMENT 'What the reseller pays',
  `sell_price` decimal(10,4) NOT NULL COMMENT 'What the end customer pays',
  `setup_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `included_minutes` int(11) NOT NULL DEFAULT 0,
  `overage_rate` decimal(10,4) DEFAULT NULL COMMENT 'Rate for minutes over included amount',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional pricing rules or conditions' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_pricing_unique` (`company_id`,`child_company_id`,`pricing_type`),
  KEY `company_pricing_tiers_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `company_pricing_tiers_child_company_id_index` (`child_company_id`),
  KEY `idx_company_pricing_optimal` (`company_id`,`child_company_id`,`pricing_type`,`is_active`),
  KEY `idx_child_pricing_lookup` (`child_company_id`,`pricing_type`,`is_active`),
  KEY `idx_pricing_date_company` (`created_at`,`company_id`),
  CONSTRAINT `company_pricing_tiers_child_company_id_foreign` FOREIGN KEY (`child_company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `company_pricing_tiers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `critical_errors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `critical_errors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trace_id` char(36) NOT NULL,
  `error_class` varchar(255) NOT NULL,
  `error_message` text NOT NULL,
  `error_code` int(11) DEFAULT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`context`)),
  `created_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `critical_errors_error_class_created_at_index` (`error_class`,`created_at`),
  KEY `critical_errors_error_code_created_at_index` (`error_code`,`created_at`),
  KEY `critical_errors_created_at_resolved_at_index` (`created_at`,`resolved_at`),
  KEY `critical_errors_trace_id_index` (`trace_id`),
  KEY `critical_errors_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`schema`)),
  `description` text DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_events_name_unique` (`name`),
  KEY `custom_events_category_index` (`category`),
  KEY `custom_events_company_id_index` (`company_id`),
  CONSTRAINT `custom_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_interactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `interaction_type` enum('phone_call','appointment_booking','appointment_cancellation','appointment_reschedule','inquiry','complaint','feedback','no_show','walk_in','online_booking','sms','email','whatsapp') NOT NULL,
  `channel` varchar(255) NOT NULL DEFAULT 'phone',
  `interaction_at` timestamp NOT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `call_id` varchar(255) DEFAULT NULL,
  `from_phone` varchar(255) DEFAULT NULL,
  `to_phone` varchar(255) DEFAULT NULL,
  `call_outcome` enum('appointment_booked','appointment_cancelled','appointment_rescheduled','information_provided','transferred','voicemail','hung_up','technical_issue') DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extracted_data`)),
  `sentiment_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sentiment_analysis`)),
  `transcript` text DEFAULT NULL,
  `intent_classification` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`intent_classification`)),
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `staff_id` bigint(20) unsigned DEFAULT NULL,
  `handled_by` varchar(255) DEFAULT NULL,
  `customer_mood` enum('happy','neutral','frustrated','angry') DEFAULT NULL,
  `issue_resolved` tinyint(1) DEFAULT NULL,
  `satisfaction_score` int(11) DEFAULT NULL,
  `requires_follow_up` tinyint(1) NOT NULL DEFAULT 0,
  `follow_up_at` timestamp NULL DEFAULT NULL,
  `follow_up_notes` text DEFAULT NULL,
  `follow_up_completed` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_interactions_customer_id_foreign` (`customer_id`),
  KEY `customer_interactions_company_id_foreign` (`company_id`),
  CONSTRAINT `customer_interactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_interactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_journey_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_journey_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `from_status` varchar(255) DEFAULT NULL,
  `to_status` varchar(255) DEFAULT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `triggered_by` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `related_type` varchar(255) DEFAULT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_journey_events_user_id_foreign` (`user_id`),
  KEY `customer_journey_events_customer_id_created_at_index` (`customer_id`,`created_at`),
  KEY `customer_journey_events_company_id_event_type_index` (`company_id`,`event_type`),
  KEY `customer_journey_events_related_type_related_id_index` (`related_type`,`related_id`),
  CONSTRAINT `customer_journey_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_journey_events_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_journey_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_journey_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_journey_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `color` varchar(255) NOT NULL DEFAULT '#6B7280',
  `icon` varchar(255) NOT NULL DEFAULT 'heroicon-o-user',
  `next_stages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`next_stages`)),
  `automation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`automation_rules`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_journey_stages_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_notes_created_by_foreign` (`created_by`),
  KEY `customer_notes_customer_id_created_at_index` (`customer_id`,`created_at`),
  KEY `customer_notes_category_index` (`category`),
  KEY `customer_notes_is_important_index` (`is_important`),
  CONSTRAINT `customer_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`),
  KEY `customer_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `preferred_days_of_week` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_days_of_week`)),
  `preferred_time_slots` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_time_slots`)),
  `earliest_booking_time` time DEFAULT NULL,
  `latest_booking_time` time DEFAULT NULL,
  `preferred_duration_minutes` int(11) DEFAULT NULL,
  `advance_booking_days` int(11) NOT NULL DEFAULT 7,
  `preferred_services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_services`)),
  `avoided_services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`avoided_services`)),
  `preferred_staff_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_staff_ids`)),
  `avoided_staff_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`avoided_staff_ids`)),
  `preferred_branch_id` bigint(20) unsigned DEFAULT NULL,
  `reminder_24h` tinyint(1) NOT NULL DEFAULT 1,
  `reminder_2h` tinyint(1) NOT NULL DEFAULT 1,
  `reminder_sms` tinyint(1) NOT NULL DEFAULT 0,
  `reminder_whatsapp` tinyint(1) NOT NULL DEFAULT 0,
  `marketing_consent` tinyint(1) NOT NULL DEFAULT 0,
  `birthday_greetings` tinyint(1) NOT NULL DEFAULT 1,
  `communication_blackout_times` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`communication_blackout_times`)),
  `accessibility_needs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accessibility_needs`)),
  `health_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`health_conditions`)),
  `allergies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allergies`)),
  `special_instructions` text DEFAULT NULL,
  `booking_patterns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`booking_patterns`)),
  `cancellation_patterns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cancellation_patterns`)),
  `punctuality_score` double NOT NULL DEFAULT 1,
  `reliability_score` double NOT NULL DEFAULT 1,
  `service_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`service_history`)),
  `price_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `average_spend` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preferred_payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_payment_methods`)),
  `auto_charge_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_preferences_customer_id_company_id_unique` (`customer_id`,`company_id`),
  KEY `customer_preferences_preferred_branch_id_index` (`preferred_branch_id`),
  KEY `customer_preferences_company_id_foreign` (`company_id`),
  CONSTRAINT `customer_preferences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_preferences_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_relationships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `related_customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `relationship_type` enum('same_person','same_company','phone_match','possible_match') NOT NULL,
  `confidence_score` int(11) NOT NULL DEFAULT 50,
  `matching_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`matching_details`)),
  `status` enum('auto_detected','user_confirmed','user_rejected','merged') NOT NULL DEFAULT 'auto_detected',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_relationships_customer_id_related_customer_id_unique` (`customer_id`,`related_customer_id`),
  KEY `customer_relationships_related_customer_id_foreign` (`related_customer_id`),
  KEY `customer_relationships_created_by_foreign` (`created_by`),
  KEY `customer_relationships_confirmed_by_foreign` (`confirmed_by`),
  KEY `customer_relationships_customer_id_related_customer_id_index` (`customer_id`,`related_customer_id`),
  KEY `customer_relationships_company_id_relationship_type_index` (`company_id`,`relationship_type`),
  KEY `customer_relationships_confidence_score_status_index` (`confidence_score`,`status`),
  CONSTRAINT `customer_relationships_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_relationships_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_relationships_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customer_relationships_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_relationships_related_customer_id_foreign` FOREIGN KEY (`related_customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_touchpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_touchpoints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `channel` varchar(255) DEFAULT NULL,
  `direction` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `occurred_at` timestamp NOT NULL,
  `touchpointable_type` varchar(255) DEFAULT NULL,
  `touchpointable_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_touchpoints_customer_id_occurred_at_index` (`customer_id`,`occurred_at`),
  KEY `customer_touchpoints_company_id_type_index` (`company_id`,`type`),
  KEY `customer_touchpoints_touchpointable_type_touchpointable_id_index` (`touchpointable_type`,`touchpointable_id`),
  CONSTRAINT `customer_touchpoints_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_touchpoints_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `customer_type` varchar(255) DEFAULT 'private',
  `name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `customer_number` varchar(255) DEFAULT NULL,
  `journey_status` varchar(255) NOT NULL DEFAULT 'initial_contact',
  `journey_status_updated_at` timestamp NULL DEFAULT NULL,
  `journey_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`journey_history`)),
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `portal_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `portal_access_token` varchar(255) DEFAULT NULL,
  `portal_token_expires_at` timestamp NULL DEFAULT NULL,
  `last_portal_login_at` timestamp NULL DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `last_call_at` timestamp NULL DEFAULT NULL,
  `preferred_language` varchar(5) NOT NULL DEFAULT 'de',
  `preferred_contact_method` varchar(255) NOT NULL DEFAULT 'phone',
  `preferred_appointment_time` varchar(255) DEFAULT NULL,
  `privacy_consent_at` timestamp NULL DEFAULT NULL,
  `marketing_consent_at` timestamp NULL DEFAULT NULL,
  `deletion_requested_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `phone_variants` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phone_variants`)),
  `matching_confidence` int(11) NOT NULL DEFAULT 100,
  `notes` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `internal_notes` text DEFAULT NULL,
  `no_show_count` int(11) NOT NULL DEFAULT 0,
  `cancelled_count` int(11) NOT NULL DEFAULT 0,
  `first_appointment_date` date DEFAULT NULL,
  `last_appointment_date` date DEFAULT NULL,
  `appointment_count` int(11) NOT NULL DEFAULT 0,
  `last_appointment_at` timestamp NULL DEFAULT NULL,
  `completed_appointments` int(11) NOT NULL DEFAULT 0,
  `cancelled_appointments` int(11) NOT NULL DEFAULT 0,
  `no_show_appointments` int(11) NOT NULL DEFAULT 0,
  `total_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `call_count` int(11) NOT NULL DEFAULT 0,
  `loyalty_points` int(11) NOT NULL DEFAULT 0,
  `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `average_booking_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_vip` tinyint(1) NOT NULL DEFAULT 0,
  `loyalty_tier` varchar(255) NOT NULL DEFAULT 'standard',
  `vip_since` timestamp NULL DEFAULT NULL,
  `special_requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`special_requirements`)),
  `birthday` date DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `preferred_branch_id` char(36) DEFAULT NULL,
  `preferred_staff_id` char(36) DEFAULT NULL,
  `booking_history_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`booking_history_summary`)),
  `location_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Customer location info (city, postal code, coordinates)' CHECK (json_valid(`location_data`)),
  `preference_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preference_data`)),
  `custom_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_attributes`)),
  `communication_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`communication_preferences`)),
  `last_security_check` timestamp NULL DEFAULT NULL,
  `security_flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_flags`)),
  `failed_verification_attempts` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `customers_phone_index` (`phone`),
  KEY `customers_email_index` (`email`),
  KEY `customers_company_id_index` (`company_id`),
  KEY `customers_company_phone_index` (`company_id`,`phone`),
  KEY `customers_company_email_index` (`company_id`,`email`),
  KEY `customers_name_index` (`name`),
  KEY `customers_portal_access_token_index` (`portal_access_token`),
  KEY `idx_customers_company_phone` (`company_id`,`phone`),
  KEY `idx_customers_name_company` (`company_id`,`name`),
  KEY `idx_customers_company_created` (`company_id`,`created_at`),
  KEY `customers_preferred_branch_id_foreign` (`preferred_branch_id`),
  KEY `customers_preferred_staff_id_foreign` (`preferred_staff_id`),
  KEY `idx_customers_phone_company` (`phone`,`company_id`),
  KEY `idx_customers_phone` (`phone`),
  KEY `idx_customers_email` (`email`),
  KEY `idx_customer_phone_lookup` (`phone`,`company_id`),
  KEY `idx_customer_email_lookup` (`email`,`company_id`),
  KEY `idx_company_recent_customers` (`company_id`,`created_at`),
  KEY `customers_status_index` (`status`),
  KEY `customers_customer_type_index` (`customer_type`),
  KEY `customers_company_id_status_index` (`company_id`,`status`),
  KEY `customers_loyalty_points_loyalty_tier_index` (`loyalty_points`,`loyalty_tier`),
  KEY `customers_last_seen_at_index` (`last_seen_at`),
  KEY `customers_is_vip_loyalty_tier_index` (`is_vip`,`loyalty_tier`),
  KEY `idx_company_phone` (`company_id`,`phone`),
  KEY `idx_customers_duplicate_check` (`company_id`,`phone`,`email`),
  KEY `customers_preferred_language_index` (`preferred_language`),
  KEY `customers_company_name_index` (`company_name`),
  KEY `customers_customer_number_index` (`customer_number`),
  KEY `customers_company_id_company_name_index` (`company_id`,`company_name`),
  KEY `customers_company_id_phone_index` (`company_id`,`phone`),
  KEY `customers_company_id_customer_number_index` (`company_id`,`customer_number`),
  KEY `customers_journey_status_index` (`journey_status`),
  KEY `customers_company_id_journey_status_index` (`company_id`,`journey_status`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_preferred_branch_id_foreign` FOREIGN KEY (`preferred_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_preferred_staff_id_foreign` FOREIGN KEY (`preferred_staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `widget_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`widget_settings`)),
  `layout_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`layout_settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dashboard_configurations_user_id_unique` (`user_id`),
  CONSTRAINT `dashboard_configurations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_widget_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widget_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(36) NOT NULL,
  `dashboard_type` varchar(50) NOT NULL,
  `widget_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`widget_order`)),
  `widget_visibility` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`widget_visibility`)),
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_flow_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_flow_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `correlation_id` char(36) NOT NULL,
  `parent_correlation_id` char(36) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `source` varchar(50) NOT NULL,
  `destination` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'started',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`steps`)),
  `statistics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`statistics`)),
  `summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_ms` decimal(10,2) DEFAULT NULL,
  `sequence_diagram` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_flow_logs_correlation_id_unique` (`correlation_id`),
  KEY `data_flow_logs_source_destination_created_at_index` (`source`,`destination`,`created_at`),
  KEY `data_flow_logs_type_status_created_at_index` (`type`,`status`,`created_at`),
  KEY `data_flow_logs_correlation_id_status_index` (`correlation_id`,`status`),
  KEY `data_flow_logs_parent_correlation_id_index` (`parent_correlation_id`),
  KEY `data_flow_logs_type_index` (`type`),
  KEY `data_flow_logs_source_index` (`source`),
  KEY `data_flow_logs_destination_index` (`destination`),
  KEY `data_flow_logs_status_index` (`status`),
  KEY `data_flow_logs_started_at_index` (`started_at`),
  KEY `data_flow_logs_duration_ms_index` (`duration_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `datev_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `datev_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `consultant_number` varchar(7) DEFAULT NULL,
  `client_number` varchar(5) DEFAULT NULL,
  `export_format` varchar(10) NOT NULL DEFAULT 'EXTF',
  `account_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`account_mapping`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `datev_configurations_company_id_unique` (`company_id`),
  CONSTRAINT `datev_configurations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doc_ai_queries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_ai_queries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `context_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context_documents`)),
  `confidence_score` decimal(3,2) DEFAULT NULL,
  `was_helpful` tinyint(1) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_ai_queries_user_id_index` (`user_id`),
  KEY `doc_ai_queries_created_at_index` (`created_at`),
  CONSTRAINT `doc_ai_queries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doc_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(255) NOT NULL,
  `document_id` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `session_id` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_analytics_event_type_occurred_at_index` (`event_type`,`occurred_at`),
  KEY `doc_analytics_user_id_index` (`user_id`),
  KEY `doc_analytics_document_id_index` (`document_id`),
  CONSTRAINT `doc_analytics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doc_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `comment` text NOT NULL,
  `is_question` tinyint(1) NOT NULL DEFAULT 0,
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `upvotes` int(11) NOT NULL DEFAULT 0,
  `downvotes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_comments_parent_id_foreign` (`parent_id`),
  KEY `doc_comments_document_id_created_at_index` (`document_id`,`created_at`),
  KEY `doc_comments_user_id_index` (`user_id`),
  CONSTRAINT `doc_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `doc_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doc_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doc_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_ratings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `document_id` varchar(255) NOT NULL,
  `rating` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_ratings_user_id_document_id_unique` (`user_id`,`document_id`),
  KEY `doc_ratings_document_id_index` (`document_id`),
  CONSTRAINT `doc_ratings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doc_search_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_search_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `query` varchar(255) NOT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `clicked_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clicked_results`)),
  `session_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_search_logs_user_id_index` (`user_id`),
  KEY `doc_search_logs_created_at_index` (`created_at`),
  CONSTRAINT `doc_search_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doc_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` varchar(255) NOT NULL,
  `version` varchar(255) NOT NULL,
  `changelog` text DEFAULT NULL,
  `diff` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`diff`)),
  `updated_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_versions_updated_by_foreign` (`updated_by`),
  KEY `doc_versions_document_id_version_index` (`document_id`,`version`),
  CONSTRAINT `doc_versions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `doc_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doc_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `document_id` varchar(255) NOT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `time_spent` int(11) NOT NULL DEFAULT 0,
  `scroll_depth` int(11) NOT NULL DEFAULT 0,
  `user_agent` varchar(255) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `viewed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doc_views_user_id_document_id_index` (`user_id`,`document_id`),
  KEY `doc_views_viewed_at_index` (`viewed_at`),
  CONSTRAINT `doc_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documentation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `content` longtext DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `difficulty` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `estimated_reading_time` int(11) NOT NULL DEFAULT 5,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `prerequisites` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prerequisites`)),
  `related_documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_documents`)),
  `view_count` int(11) NOT NULL DEFAULT 0,
  `rating` decimal(2,1) DEFAULT NULL,
  `version` varchar(255) NOT NULL DEFAULT '1.0',
  `is_outdated` tinyint(1) NOT NULL DEFAULT 0,
  `is_interactive` tinyint(1) NOT NULL DEFAULT 0,
  `has_video` tinyint(1) NOT NULL DEFAULT 0,
  `ai_summary` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `icon` varchar(255) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documentation_items_slug_unique` (`slug`),
  KEY `documentation_items_category_difficulty_index` (`category`,`difficulty`),
  KEY `documentation_items_slug_index` (`slug`),
  FULLTEXT KEY `documentation_items_title_description_ai_summary_fulltext` (`title`,`description`,`ai_summary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dummy_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dummy_companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dunning_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dunning_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dunning_process_id` bigint(20) unsigned NOT NULL,
  `company_id` char(36) NOT NULL,
  `type` enum('retry_scheduled','retry_attempted','retry_succeeded','retry_failed','email_sent','service_paused','service_resumed','manual_review_requested','manually_resolved','escalated','cancelled') NOT NULL,
  `description` varchar(255) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `performed_by` varchar(255) DEFAULT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dunning_activities_dunning_process_id_type_index` (`dunning_process_id`,`type`),
  KEY `dunning_activities_company_id_index` (`company_id`),
  CONSTRAINT `dunning_activities_dunning_process_id_foreign` FOREIGN KEY (`dunning_process_id`) REFERENCES `dunning_processes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dunning_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dunning_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `max_retry_attempts` int(11) NOT NULL DEFAULT 3,
  `retry_delays` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"1": 3, "2": 5, "3": 7}' CHECK (json_valid(`retry_delays`)),
  `grace_period_days` int(11) NOT NULL DEFAULT 3,
  `pause_service_on_failure` tinyint(1) NOT NULL DEFAULT 0,
  `pause_after_days` int(11) NOT NULL DEFAULT 14,
  `send_payment_failed_email` tinyint(1) NOT NULL DEFAULT 1,
  `send_retry_warning_email` tinyint(1) NOT NULL DEFAULT 1,
  `send_service_paused_email` tinyint(1) NOT NULL DEFAULT 1,
  `send_payment_recovered_email` tinyint(1) NOT NULL DEFAULT 1,
  `enable_manual_review` tinyint(1) NOT NULL DEFAULT 1,
  `manual_review_after_attempts` int(11) NOT NULL DEFAULT 2,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dunning_configurations_company_id_unique` (`company_id`),
  KEY `dunning_configurations_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dunning_processes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dunning_processes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `invoice_id` char(36) NOT NULL,
  `stripe_invoice_id` varchar(255) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `status` enum('active','resolved','failed','paused','cancelled') NOT NULL,
  `started_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `max_retries` int(11) NOT NULL DEFAULT 3,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `original_amount` decimal(10,2) NOT NULL,
  `remaining_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `failure_code` varchar(255) DEFAULT NULL,
  `failure_message` text DEFAULT NULL,
  `service_paused` tinyint(1) NOT NULL DEFAULT 0,
  `service_paused_at` timestamp NULL DEFAULT NULL,
  `manual_review_requested` tinyint(1) NOT NULL DEFAULT 0,
  `manual_review_requested_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dunning_processes_company_id_status_index` (`company_id`,`status`),
  KEY `dunning_processes_status_next_retry_at_index` (`status`,`next_retry_at`),
  KEY `dunning_processes_stripe_invoice_id_index` (`stripe_invoice_id`),
  KEY `dunning_processes_stripe_subscription_id_index` (`stripe_subscription_id`),
  KEY `dunning_processes_invoice_id_index` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `to` varchar(255) NOT NULL,
  `from` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `content` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_logs_customer_id_sent_at_index` (`customer_id`,`sent_at`),
  KEY `email_logs_company_id_type_index` (`company_id`,`type`),
  KEY `email_logs_status_index` (`status`),
  KEY `email_logs_sent_at_index` (`sent_at`),
  CONSTRAINT `email_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `email_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_catalog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_code` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `service` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `symptoms` text DEFAULT NULL,
  `stack_pattern` text DEFAULT NULL,
  `root_causes` text NOT NULL,
  `severity` enum('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `auto_detectable` tinyint(1) NOT NULL DEFAULT 0,
  `occurrence_count` int(11) NOT NULL DEFAULT 0,
  `last_occurred_at` timestamp NULL DEFAULT NULL,
  `avg_resolution_time` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `error_catalog_error_code_unique` (`error_code`),
  KEY `error_catalog_category_severity_index` (`category`,`severity`),
  KEY `error_catalog_category_index` (`category`),
  KEY `error_catalog_service_index` (`service`),
  FULLTEXT KEY `error_catalog_title_description_symptoms_fulltext` (`title`,`description`,`symptoms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `severity` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `request_id` varchar(36) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `error_logs_severity_created_at_index` (`severity`,`created_at`),
  KEY `error_logs_type_created_at_index` (`type`,`created_at`),
  KEY `error_logs_severity_index` (`severity`),
  KEY `error_logs_type_index` (`type`),
  KEY `error_logs_user_id_index` (`user_id`),
  KEY `error_logs_request_id_index` (`request_id`),
  KEY `error_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_occurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_occurrences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_catalog_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `environment` varchar(20) NOT NULL DEFAULT 'production',
  `context` text DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `request_url` varchar(255) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `was_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_time` int(11) DEFAULT NULL,
  `solution_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_occurrences_user_id_foreign` (`user_id`),
  KEY `error_occurrences_solution_id_foreign` (`solution_id`),
  KEY `error_occurrences_error_catalog_id_created_at_index` (`error_catalog_id`,`created_at`),
  KEY `error_occurrences_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `error_occurrences_environment_index` (`environment`),
  CONSTRAINT `error_occurrences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `error_occurrences_error_catalog_id_foreign` FOREIGN KEY (`error_catalog_id`) REFERENCES `error_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `error_occurrences_solution_id_foreign` FOREIGN KEY (`solution_id`) REFERENCES `error_solutions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `error_occurrences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_prevention_tips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_prevention_tips` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_catalog_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT 1,
  `tip` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_prevention_tips_error_catalog_id_order_index` (`error_catalog_id`,`order`),
  CONSTRAINT `error_prevention_tips_error_catalog_id_foreign` FOREIGN KEY (`error_catalog_id`) REFERENCES `error_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_relationships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_id` bigint(20) unsigned NOT NULL,
  `related_error_id` bigint(20) unsigned NOT NULL,
  `relationship_type` varchar(50) NOT NULL,
  `relevance_score` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `error_relationships_error_id_related_error_id_unique` (`error_id`,`related_error_id`),
  KEY `error_relationships_related_error_id_foreign` (`related_error_id`),
  KEY `error_relationships_error_id_relevance_score_index` (`error_id`,`relevance_score`),
  CONSTRAINT `error_relationships_error_id_foreign` FOREIGN KEY (`error_id`) REFERENCES `error_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `error_relationships_related_error_id_foreign` FOREIGN KEY (`related_error_id`) REFERENCES `error_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_solution_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_solution_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `solution_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `was_helpful` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_solution_feedback_user_id_foreign` (`user_id`),
  KEY `error_solution_feedback_solution_id_was_helpful_index` (`solution_id`,`was_helpful`),
  CONSTRAINT `error_solution_feedback_solution_id_foreign` FOREIGN KEY (`solution_id`) REFERENCES `error_solutions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `error_solution_feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_solutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_solutions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_catalog_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT 1,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `steps` text NOT NULL,
  `code_snippet` text DEFAULT NULL,
  `is_automated` tinyint(1) NOT NULL DEFAULT 0,
  `automation_script` varchar(255) DEFAULT NULL,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `success_rate` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_solutions_error_catalog_id_order_index` (`error_catalog_id`,`order`),
  CONSTRAINT `error_solutions_error_catalog_id_foreign` FOREIGN KEY (`error_catalog_id`) REFERENCES `error_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_tag_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_tag_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `error_catalog_id` bigint(20) unsigned NOT NULL,
  `error_tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `error_tag_assignments_error_catalog_id_error_tag_id_unique` (`error_catalog_id`,`error_tag_id`),
  KEY `error_tag_assignments_error_tag_id_foreign` (`error_tag_id`),
  CONSTRAINT `error_tag_assignments_error_catalog_id_foreign` FOREIGN KEY (`error_catalog_id`) REFERENCES `error_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `error_tag_assignments_error_tag_id_foreign` FOREIGN KEY (`error_tag_id`) REFERENCES `error_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6B7280',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `error_tags_name_unique` (`name`),
  UNIQUE KEY `error_tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_audit_trail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_log_id` bigint(20) unsigned NOT NULL,
  `action` varchar(255) NOT NULL,
  `performed_by` bigint(20) unsigned NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_audit_trail_event_log_id_foreign` (`event_log_id`),
  CONSTRAINT `event_audit_trail_event_log_id_foreign` FOREIGN KEY (`event_log_id`) REFERENCES `event_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_name` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_logs_event_name_index` (`event_name`),
  KEY `event_logs_company_id_index` (`company_id`),
  KEY `event_logs_user_id_index` (`user_id`),
  KEY `event_logs_created_at_index` (`created_at`),
  KEY `event_logs_event_name_created_at_index` (`event_name`,`created_at`),
  KEY `event_logs_company_id_created_at_index` (`company_id`,`created_at`),
  CONSTRAINT `event_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `event_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_names` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`event_names`)),
  `webhook_url` varchar(255) NOT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_subscriptions_active_index` (`active`),
  KEY `event_subscriptions_company_id_index` (`company_id`),
  CONSTRAINT `event_subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_type_import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_type_import_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `total_event_types` int(11) NOT NULL DEFAULT 0,
  `imported_count` int(11) NOT NULL DEFAULT 0,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `total_errors` int(11) NOT NULL DEFAULT 0,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `error_message` text DEFAULT NULL,
  `error_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`error_details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_type_import_logs_company_id_index` (`company_id`),
  KEY `event_type_import_logs_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `external_sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `external_sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sync_type` varchar(50) NOT NULL,
  `report` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`report`)),
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `external_sync_logs_sync_type_created_at_index` (`sync_type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_flag_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flag_overrides` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feature_key` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flag_overrides_feature_key_company_id_unique` (`feature_key`,`company_id`),
  KEY `feature_flag_overrides_feature_key_index` (`feature_key`),
  KEY `feature_flag_overrides_company_id_index` (`company_id`),
  CONSTRAINT `feature_flag_overrides_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feature_flag_overrides_feature_key_foreign` FOREIGN KEY (`feature_key`) REFERENCES `feature_flags` (`key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_flag_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flag_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feature_key` varchar(255) NOT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `result` tinyint(1) NOT NULL,
  `evaluation_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feature_flag_usage_feature_key_index` (`feature_key`),
  KEY `feature_flag_usage_created_at_index` (`created_at`),
  KEY `feature_flag_usage_feature_key_created_at_index` (`feature_key`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `rollout_percentage` varchar(255) NOT NULL DEFAULT '0',
  `enabled_at` timestamp NULL DEFAULT NULL,
  `disabled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flags_key_unique` (`key`),
  KEY `feature_flags_key_index` (`key`),
  KEY `feature_flags_enabled_index` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('bug','feature','improvement','question','complaint') NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `first_response_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_company_id_status_index` (`company_id`,`status`),
  KEY `feedback_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `feedback_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedback_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_responses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feedback_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_responses_user_id_foreign` (`user_id`),
  KEY `feedback_responses_feedback_id_created_at_index` (`feedback_id`,`created_at`),
  CONSTRAINT `feedback_responses_feedback_id_foreign` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_responses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gdpr_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gdpr_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` enum('export','deletion') NOT NULL,
  `status` enum('pending_confirmation','completed','expired','cancelled') NOT NULL DEFAULT 'pending_confirmation',
  `token` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `downloaded_at` timestamp NULL DEFAULT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gdpr_requests_token_unique` (`token`),
  KEY `gdpr_requests_company_id_foreign` (`company_id`),
  KEY `gdpr_requests_customer_id_type_index` (`customer_id`,`type`),
  KEY `gdpr_requests_token_index` (`token`),
  KEY `gdpr_requests_status_index` (`status`),
  KEY `gdpr_requests_expires_at_index` (`expires_at`),
  CONSTRAINT `gdpr_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gdpr_requests_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goal_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `goal_achievements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_goal_id` bigint(20) unsigned NOT NULL,
  `goal_metric_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` bigint(20) unsigned DEFAULT NULL,
  `period_start` datetime NOT NULL,
  `period_end` datetime NOT NULL,
  `period_type` enum('hourly','daily','weekly','monthly','quarterly','yearly','custom') NOT NULL,
  `achieved_value` decimal(10,2) NOT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `achievement_percentage` decimal(5,2) NOT NULL,
  `breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breakdown`)),
  `funnel_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`funnel_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goal_achievements_company_goal_id_period_start_period_end_index` (`company_goal_id`,`period_start`,`period_end`),
  KEY `goal_achievements_goal_metric_id_period_type_index` (`goal_metric_id`,`period_type`),
  KEY `goal_achievements_achievement_percentage_index` (`achievement_percentage`),
  KEY `goal_achievements_branch_id_index` (`branch_id`),
  CONSTRAINT `goal_achievements_company_goal_id_foreign` FOREIGN KEY (`company_goal_id`) REFERENCES `company_goals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goal_achievements_goal_metric_id_foreign` FOREIGN KEY (`goal_metric_id`) REFERENCES `goal_metrics` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goal_funnel_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `goal_funnel_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_goal_id` bigint(20) unsigned NOT NULL,
  `step_order` int(11) NOT NULL,
  `step_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `step_type` enum('call_received','call_answered','data_captured','email_captured','phone_captured','address_captured','appointment_requested','appointment_scheduled','appointment_confirmed','appointment_completed','payment_received','custom') NOT NULL,
  `required_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`required_fields`)),
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `expected_conversion_rate` decimal(5,2) DEFAULT NULL,
  `is_optional` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `goal_funnel_steps_company_goal_id_step_order_unique` (`company_goal_id`,`step_order`),
  KEY `goal_funnel_steps_company_goal_id_step_order_index` (`company_goal_id`,`step_order`),
  CONSTRAINT `goal_funnel_steps_company_goal_id_foreign` FOREIGN KEY (`company_goal_id`) REFERENCES `company_goals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `goal_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `goal_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_goal_id` bigint(20) unsigned NOT NULL,
  `metric_type` enum('calls_received','calls_answered','data_collected','appointments_booked','appointments_completed','revenue_generated','customer_satisfaction','average_call_duration','conversion_rate','custom') NOT NULL,
  `metric_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `target_unit` enum('count','percentage','currency','seconds','score') NOT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `calculation_method` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculation_method`)),
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conditions`)),
  `comparison_operator` varchar(255) NOT NULL DEFAULT 'gte',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goal_metrics_company_goal_id_metric_type_index` (`company_goal_id`,`metric_type`),
  CONSTRAINT `goal_metrics_company_goal_id_foreign` FOREIGN KEY (`company_goal_id`) REFERENCES `company_goals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `guest_access_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guest_access_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `call_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `reason` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guest_access_requests_token_unique` (`token`),
  KEY `guest_access_requests_call_id_foreign` (`call_id`),
  KEY `guest_access_requests_approved_by_foreign` (`approved_by`),
  KEY `guest_access_requests_rejected_by_foreign` (`rejected_by`),
  KEY `guest_access_requests_company_id_email_index` (`company_id`,`email`),
  KEY `guest_access_requests_token_index` (`token`),
  KEY `guest_access_requests_expires_at_index` (`expires_at`),
  CONSTRAINT `guest_access_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guest_access_requests_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guest_access_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guest_access_requests_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `health_check_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `health_check_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `component` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `response_time` decimal(10,2) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `health_check_results_component_status_created_at_index` (`component`,`status`,`created_at`),
  KEY `health_check_results_component_index` (`component`),
  KEY `health_check_results_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `help_article_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `help_article_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `topic` varchar(100) NOT NULL,
  `helpful` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `portal_user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `help_article_feedback_category_topic_index` (`category`,`topic`),
  KEY `help_article_feedback_helpful_index` (`helpful`),
  KEY `help_article_feedback_created_at_index` (`created_at`),
  KEY `help_article_feedback_portal_user_id_index` (`portal_user_id`),
  CONSTRAINT `help_article_feedback_portal_user_id_foreign` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `help_article_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `help_article_views` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `topic` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `portal_user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `help_article_views_category_topic_index` (`category`,`topic`),
  KEY `help_article_views_created_at_index` (`created_at`),
  KEY `help_article_views_portal_user_id_index` (`portal_user_id`),
  KEY `help_article_views_session_id_index` (`session_id`),
  CONSTRAINT `help_article_views_portal_user_id_foreign` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `help_search_queries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `help_search_queries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(255) NOT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `clicked_result` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `portal_user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `help_search_queries_query_index` (`query`),
  KEY `help_search_queries_created_at_index` (`created_at`),
  KEY `help_search_queries_portal_user_id_index` (`portal_user_id`),
  CONSTRAINT `help_search_queries_portal_user_id_foreign` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `industry_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `industry_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `name_en` varchar(255) NOT NULL,
  `icon` varchar(255) NOT NULL DEFAULT 'heroicon-o-building-office',
  `description` text NOT NULL,
  `default_services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`default_services`)),
  `default_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`default_hours`)),
  `ai_personality` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`ai_personality`)),
  `common_questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`common_questions`)),
  `booking_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`booking_rules`)),
  `setup_time_estimate` int(11) NOT NULL DEFAULT 300,
  `popularity_score` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `industry_templates_slug_unique` (`slug`),
  KEY `industry_templates_slug_index` (`slug`),
  KEY `industry_templates_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`credentials`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `integrations_company_id_index` (`company_id`),
  KEY `integrations_type_index` (`type`),
  KEY `integrations_status_index` (`status`),
  KEY `idx_integrations_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `accepted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invitations_token_hash_unique` (`token_hash`),
  KEY `invitations_email_index` (`email`),
  KEY `invitations_tenant_id_index` (`tenant_id`),
  KEY `invitations_expires_at_index` (`expires_at`),
  KEY `invitations_email_tenant_id_index` (`email`,`tenant_id`),
  KEY `invitations_invited_by_foreign` (`invited_by`),
  KEY `invitations_accepted_by_foreign` (`accepted_by`),
  CONSTRAINT `invitations_accepted_by_foreign` FOREIGN KEY (`accepted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invitations_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'service',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_items_invoice_id_index` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `number` varchar(255) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 19.00,
  `tax_amount` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `stripe_invoice_id` varchar(255) DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_number_unique` (`number`),
  KEY `invoices_company_id_status_index` (`company_id`,`status`),
  KEY `invoices_due_date_index` (`due_date`),
  KEY `invoices_stripe_invoice_id_index` (`stripe_invoice_id`),
  CONSTRAINT `invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` text NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `event_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`event_data`)),
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_analytics_document_id_index` (`document_id`),
  KEY `knowledge_analytics_user_id_index` (`user_id`),
  KEY `knowledge_analytics_event_type_index` (`event_type`),
  KEY `knowledge_analytics_created_at_index` (`created_at`),
  CONSTRAINT `knowledge_analytics_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_analytics_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_code_snippets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_code_snippets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `language` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `code` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_executable` tinyint(1) NOT NULL DEFAULT 0,
  `execution_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`execution_config`)),
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_code_snippets_document_id_index` (`document_id`),
  KEY `knowledge_code_snippets_language_index` (`language`),
  KEY `knowledge_code_snippets_is_executable_index` (`is_executable`),
  CONSTRAINT `knowledge_code_snippets_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `position` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`position`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_comments_document_id_index` (`document_id`),
  KEY `knowledge_comments_parent_id_index` (`parent_id`),
  KEY `knowledge_comments_status_index` (`status`),
  KEY `knowledge_comments_user_id_foreign` (`user_id`),
  CONSTRAINT `knowledge_comments_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `knowledge_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_document_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_document_tag` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledge_document_tag_document_id_tag_id_unique` (`document_id`,`tag_id`),
  KEY `knowledge_document_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `knowledge_document_tag_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_document_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `knowledge_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_document_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_document_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledge_doc_tag_unique` (`document_id`,`tag_id`),
  KEY `knowledge_document_tags_tag_id_foreign` (`tag_id`),
  CONSTRAINT `knowledge_document_tags_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_document_tags_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `knowledge_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `user_id` mediumint(8) unsigned DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_feedback_document_id_foreign` (`document_id`),
  CONSTRAINT `knowledge_feedback_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_notebook_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_notebook_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notebook_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_notebook_entries_notebook_id_index` (`notebook_id`),
  CONSTRAINT `knowledge_notebook_entries_notebook_id_foreign` FOREIGN KEY (`notebook_id`) REFERENCES `knowledge_notebooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_notebooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_notebooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledge_notebooks_user_id_slug_unique` (`user_id`,`slug`),
  KEY `knowledge_notebooks_is_public_index` (`is_public`),
  CONSTRAINT `knowledge_notebooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_related_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_related_documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `related_document_id` bigint(20) unsigned NOT NULL,
  `relevance_score` double NOT NULL DEFAULT 1,
  `relation_type` varchar(255) NOT NULL DEFAULT 'similar',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledge_related_docs_unique` (`document_id`,`related_document_id`),
  KEY `knowledge_related_documents_related_document_id_foreign` (`related_document_id`),
  KEY `knowledge_related_documents_document_id_relevance_score_index` (`document_id`,`relevance_score`),
  CONSTRAINT `knowledge_related_documents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_related_documents_related_document_id_foreign` FOREIGN KEY (`related_document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_relationships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_document_id` bigint(20) unsigned NOT NULL,
  `target_document_id` bigint(20) unsigned NOT NULL,
  `relationship_type` varchar(255) NOT NULL,
  `strength` double NOT NULL DEFAULT 1,
  `is_auto_detected` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relationship` (`source_document_id`,`target_document_id`,`relationship_type`),
  KEY `knowledge_relationships_relationship_type_index` (`relationship_type`),
  KEY `knowledge_relationships_target_document_id_foreign` (`target_document_id`),
  CONSTRAINT `knowledge_relationships_source_document_id_foreign` FOREIGN KEY (`source_document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `knowledge_relationships_target_document_id_foreign` FOREIGN KEY (`target_document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_search_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_search_index` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `section_title` varchar(255) DEFAULT NULL,
  `content_chunk` text NOT NULL,
  `embedding` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`embedding`)),
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`keywords`)),
  `relevance_score` double NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_search_index_document_id_index` (`document_id`),
  KEY `knowledge_search_index_relevance_score_index` (`relevance_score`),
  FULLTEXT KEY `knowledge_search_index_content_chunk_fulltext` (`content_chunk`),
  CONSTRAINT `knowledge_search_index_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL DEFAULT '#6B7280',
  `description` text DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `knowledge_tags_slug_unique` (`slug`),
  KEY `knowledge_tags_slug_index` (`slug`),
  KEY `knowledge_tags_usage_count_index` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `knowledge_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) unsigned NOT NULL,
  `version_number` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `diff` longtext DEFAULT NULL,
  `commit_message` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `knowledge_versions_document_id_version_number_index` (`document_id`,`version_number`),
  KEY `knowledge_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `knowledge_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `knowledge_versions_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `knowledge_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kunden`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kunden` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `channel` varchar(255) NOT NULL DEFAULT 'stack',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logs_created_at_index` (`created_at`),
  KEY `logs_level_created_at_index` (`level`,`created_at`),
  KEY `logs_level_index` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mcp_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mcp_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `task` text NOT NULL,
  `correlation_id` char(36) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `mcp_logs_service_type_created_at_index` (`service`,`type`,`created_at`),
  KEY `mcp_logs_correlation_id_created_at_index` (`correlation_id`,`created_at`),
  KEY `mcp_logs_correlation_id_index` (`correlation_id`),
  KEY `mcp_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mcp_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mcp_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service` varchar(50) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `tenant_id` int(11) DEFAULT NULL,
  `operation` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'success',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL,
  `response_time` decimal(10,3) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mcp_metrics_service_created_at_index` (`service`,`created_at`),
  KEY `mcp_metrics_service_success_created_at_index` (`service`,`success`,`created_at`),
  KEY `mcp_metrics_tenant_id_created_at_index` (`tenant_id`,`created_at`),
  KEY `mcp_metrics_service_index` (`service`),
  KEY `mcp_metrics_success_index` (`success`),
  KEY `mcp_metrics_tenant_id_index` (`tenant_id`),
  KEY `mcp_metrics_created_at_index` (`created_at`),
  KEY `idx_mcp_metrics_status` (`status`),
  KEY `idx_mcp_metrics_service_status_created` (`service`,`status`,`created_at`),
  KEY `idx_mcp_metrics_service_operation_created` (`service`,`operation`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `metric_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `metric_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `metric_type` varchar(50) NOT NULL,
  `period` varchar(20) NOT NULL,
  `metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metrics`)),
  `snapshot_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `metric_snapshots_company_id_foreign` (`company_id`),
  KEY `metric_snapshots_branch_id_foreign` (`branch_id`),
  CONSTRAINT `metric_snapshots_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `metric_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ml_call_predictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ml_call_predictions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) unsigned NOT NULL,
  `model_version` varchar(50) NOT NULL,
  `sentiment_score` decimal(3,2) DEFAULT NULL COMMENT '-1.0 to 1.0',
  `satisfaction_score` decimal(3,2) DEFAULT NULL COMMENT '0.0 to 1.0',
  `goal_achievement_score` decimal(3,2) DEFAULT NULL COMMENT '0.0 to 1.0',
  `sentence_sentiments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '[{text, start_time, end_time, sentiment, score}]' CHECK (json_valid(`sentence_sentiments`)),
  `feature_contributions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`feature_contributions`)),
  `prediction_confidence` decimal(3,2) DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ml_call_predictions_call_id_sentiment_score_index` (`call_id`,`sentiment_score`),
  CONSTRAINT `ml_call_predictions_call_id_foreign` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ml_job_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ml_job_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(255) NOT NULL,
  `job_type` varchar(255) NOT NULL,
  `status` enum('pending','running','completed','failed') DEFAULT 'pending',
  `total_items` int(11) DEFAULT 0,
  `processed_items` int(11) DEFAULT 0,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `current_step` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_id` (`job_id`),
  KEY `idx_status` (`status`),
  KEY `idx_job_type` (`job_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ml_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ml_models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(50) NOT NULL,
  `version` varchar(20) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `training_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`training_metrics`)),
  `feature_importance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`feature_importance`)),
  `training_samples` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ml_models_model_type_is_active_index` (`model_type`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `monitoring_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `monitoring_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `metric` varchar(255) NOT NULL,
  `condition` varchar(10) NOT NULL,
  `threshold` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 5,
  `actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`actions`)),
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `trigger_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monitoring_alerts_enabled_metric_index` (`enabled`,`metric`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'general',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notes_customer_id_is_pinned_index` (`customer_id`,`is_pinned`),
  KEY `notes_type_index` (`type`),
  KEY `notes_created_at_index` (`created_at`),
  KEY `notes_user_id_index` (`user_id`),
  CONSTRAINT `notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`channels`)),
  `successful_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`successful_channels`)),
  `failed_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`failed_channels`)),
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `status` enum('sent','failed','partial') NOT NULL DEFAULT 'sent',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_logs_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `notification_logs_customer_id_type_index` (`customer_id`,`type`),
  KEY `notification_logs_status_created_at_index` (`status`,`created_at`),
  KEY `notification_logs_appointment_id_index` (`appointment_id`),
  KEY `notification_logs_customer_id_index` (`customer_id`),
  KEY `notification_logs_company_id_index` (`company_id`),
  CONSTRAINT `notification_logs_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notification_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `key` varchar(100) NOT NULL,
  `channel` varchar(20) NOT NULL,
  `translations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`translations`)),
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_template_unique` (`company_id`,`key`,`channel`),
  KEY `notification_templates_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `notification_templates_key_index` (`key`),
  KEY `notification_templates_channel_index` (`channel`),
  CONSTRAINT `notification_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `action_text` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_notifiable_type_notifiable_id_read_at_index` (`notifiable_type`,`notifiable_id`,`read_at`),
  KEY `notifications_category_created_at_index` (`category`,`created_at`),
  KEY `notifications_priority_index` (`priority`),
  KEY `notifications_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `onboarding_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `current_step` varchar(255) NOT NULL,
  `completed_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_steps`)),
  `step_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`step_data`)),
  `progress_percentage` int(11) NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `onboarding_progress_company_id_index` (`company_id`),
  KEY `onboarding_progress_user_id_index` (`user_id`),
  KEY `onboarding_progress_company_id_is_completed_index` (`company_id`,`is_completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `onboarding_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_states` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `current_step` int(11) NOT NULL DEFAULT 1,
  `completed_steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`completed_steps`)),
  `state_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`state_data`)),
  `time_elapsed` int(11) NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `industry_template` varchar(255) DEFAULT NULL,
  `completion_percentage` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `onboarding_states_company_id_is_completed_index` (`company_id`,`is_completed`),
  CONSTRAINT `onboarding_states_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `outbound_call_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `outbound_call_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `template_type` enum('sales','appointment_reminder','follow_up','survey','custom') NOT NULL,
  `script_template` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dynamic variables that can be used in the script' CHECK (json_valid(`variables`)),
  `success_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'What constitutes a successful call' CHECK (json_valid(`success_criteria`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `outbound_call_templates_created_by_foreign` (`created_by`),
  KEY `outbound_call_templates_company_id_template_type_is_active_index` (`company_id`,`template_type`,`is_active`),
  KEY `idx_template_company_type` (`company_id`,`template_type`,`is_active`),
  CONSTRAINT `outbound_call_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `outbound_call_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_reset_tokens_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_failures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_failures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_method` varchar(50) NOT NULL,
  `error_code` varchar(100) NOT NULL,
  `error_message` text NOT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_failures_created_at_index` (`created_at`),
  KEY `payment_failures_customer_id_index` (`customer_id`),
  KEY `payment_failures_payment_method_created_at_index` (`payment_method`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` char(36) DEFAULT NULL,
  `type` varchar(100) NOT NULL,
  `severity` enum('low','medium','high') DEFAULT 'medium',
  `message` text NOT NULL,
  `metric_name` varchar(100) DEFAULT NULL,
  `metric_value` decimal(10,3) DEFAULT NULL,
  `context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context_data`)),
  `resolved` tinyint(1) DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tenant_resolved_severity` (`tenant_id`,`resolved`,`severity`),
  KEY `idx_type_resolved_created` (`type`,`resolved`,`created_at`),
  KEY `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` char(36) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `type` enum('frontend','backend') NOT NULL DEFAULT 'frontend',
  `url` varchar(500) DEFAULT NULL,
  `user_agent` varchar(1000) DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `metrics_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`metrics_data`)),
  `performance_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_metrics_tenant_id_type_created_at_index` (`tenant_id`,`type`,`created_at`),
  KEY `performance_metrics_performance_score_created_at_index` (`performance_score`,`created_at`),
  KEY `performance_metrics_session_id_index` (`session_id`),
  KEY `performance_metrics_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` char(36) DEFAULT NULL,
  `session_id` varchar(100) NOT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `user_agent` varchar(1000) DEFAULT NULL,
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `avg_performance_score` decimal(5,2) DEFAULT NULL,
  `page_views` int(11) DEFAULT 1,
  `total_interactions` int(11) DEFAULT 0,
  `session_duration` decimal(8,2) DEFAULT NULL,
  `first_seen_at` timestamp NULL DEFAULT current_timestamp(),
  `last_seen_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `idx_tenant_first_seen` (`tenant_id`,`first_seen_at`),
  KEY `idx_avg_score` (`avg_performance_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cpu_usage` decimal(5,2) NOT NULL,
  `memory_usage` decimal(5,2) NOT NULL,
  `memory_used_bytes` bigint(20) NOT NULL,
  `disk_usage` decimal(5,2) NOT NULL,
  `active_connections` int(11) NOT NULL,
  `queue_size` int(11) NOT NULL,
  `cache_hit_rate` int(11) NOT NULL,
  `additional_metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_metrics`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `performance_snapshots_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_thresholds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_thresholds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` char(36) DEFAULT NULL,
  `metric_name` varchar(100) NOT NULL,
  `warning_threshold` decimal(10,3) NOT NULL,
  `critical_threshold` decimal(10,3) NOT NULL,
  `unit` varchar(20) DEFAULT 'ms',
  `description` text DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant_metric` (`tenant_id`,`metric_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `phone_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `phone_numbers` (
  `id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `number` varchar(255) NOT NULL,
  `retell_phone_id` varchar(255) DEFAULT NULL,
  `retell_agent_id` varchar(255) DEFAULT NULL,
  `retell_agent_version` varchar(255) DEFAULT NULL,
  `type` enum('direct','hotline') NOT NULL DEFAULT 'direct',
  `capabilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`capabilities`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `routing_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`routing_config`)),
  `agent_id` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `whatsapp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_numbers_number_unique` (`number`),
  KEY `phone_numbers_branch_id_index` (`branch_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `phone_numbers_company_id_index` (`company_id`),
  KEY `phone_numbers_type_index` (`type`),
  KEY `phone_numbers_retell_phone_id_index` (`retell_phone_id`),
  KEY `phone_numbers_retell_agent_id_index` (`retell_agent_id`),
  KEY `phone_numbers_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `idx_phone_branch_lookup` (`number`,`branch_id`,`is_active`),
  KEY `idx_phone_numbers_active` (`number`,`is_active`),
  KEY `idx_phone_numbers_number` (`number`),
  KEY `idx_phone_numbers_active_number` (`is_active`,`number`),
  KEY `idx_number_type` (`number`,`type`),
  CONSTRAINT `fk_phone_numbers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_phone_numbers_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `entity_type` enum('call','appointment','feature','general') NOT NULL,
  `entity_id` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text NOT NULL,
  `category` enum('bug','idea','complaint','praise') NOT NULL,
  `status` enum('new','reviewed','in_progress','resolved','closed') NOT NULL DEFAULT 'new',
  `admin_response` text DEFAULT NULL,
  `responded_by` bigint(20) unsigned DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portal_feedback_user_id_foreign` (`user_id`),
  KEY `portal_feedback_responded_by_foreign` (`responded_by`),
  KEY `portal_feedback_company_id_status_index` (`company_id`,`status`),
  KEY `portal_feedback_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `portal_feedback_category_index` (`category`),
  CONSTRAINT `portal_feedback_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `portal_feedback_responded_by_foreign` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `portal_feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `portal_password_resets_email_token_index` (`email`,`token`),
  KEY `portal_password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `module` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `is_critical` tinyint(1) NOT NULL DEFAULT 0,
  `admin_only` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portal_permissions_name_unique` (`name`),
  KEY `portal_permissions_module_index` (`module`),
  KEY `portal_permissions_is_critical_index` (`is_critical`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `portal_sessions_user_id_index` (`user_id`),
  KEY `portal_sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `portal_user_id` bigint(20) unsigned NOT NULL,
  `portal_permission_id` bigint(20) unsigned NOT NULL,
  `granted_at` timestamp NOT NULL,
  `granted_by_user_id` char(36) DEFAULT NULL,
  `granted_by_user_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portal_user_permissions_portal_user_id_foreign` (`portal_user_id`),
  KEY `portal_user_permissions_portal_permission_id_foreign` (`portal_permission_id`),
  CONSTRAINT `portal_user_permissions_portal_permission_id_foreign` FOREIGN KEY (`portal_permission_id`) REFERENCES `portal_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `portal_user_permissions_portal_user_id_foreign` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portal_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `portal_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` enum('owner','admin','manager','staff') NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_recovery_codes` varchar(1000) DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `two_factor_enforced` tinyint(1) NOT NULL DEFAULT 0,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_preferences`)),
  `call_notification_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`call_notification_preferences`)),
  `preferred_language` varchar(5) NOT NULL DEFAULT 'de',
  `timezone` varchar(255) NOT NULL DEFAULT 'Europe/Berlin',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `portal_users_email_unique` (`email`),
  KEY `portal_users_company_id_email_index` (`company_id`,`email`),
  KEY `portal_users_role_index` (`role`),
  CONSTRAINT `portal_users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prepaid_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prepaid_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reserved_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `low_balance_threshold` decimal(15,2) NOT NULL DEFAULT 20.00,
  `last_warning_sent_at` timestamp NULL DEFAULT NULL,
  `auto_topup_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `auto_topup_threshold` decimal(15,2) DEFAULT NULL,
  `auto_topup_amount` decimal(15,2) DEFAULT NULL,
  `stripe_payment_method_id` varchar(255) DEFAULT NULL,
  `last_auto_topup_at` timestamp NULL DEFAULT NULL,
  `auto_topup_daily_count` int(11) NOT NULL DEFAULT 0,
  `auto_topup_monthly_limit` decimal(15,2) NOT NULL DEFAULT 5000.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prepaid_balances_company_id_unique` (`company_id`),
  KEY `prepaid_balances_company_id_index` (`company_id`),
  KEY `prepaid_balances_company_id_auto_topup_enabled_index` (`company_id`,`auto_topup_enabled`),
  KEY `prepaid_balances_bonus_balance_index` (`bonus_balance`),
  CONSTRAINT `prepaid_balances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prepaid_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prepaid_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `prepaid_balance_id` bigint(20) unsigned NOT NULL,
  `type` enum('topup','deduction','refund','adjustment','bonus') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prepaid_transactions_user_id_foreign` (`user_id`),
  KEY `prepaid_transactions_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `prepaid_transactions_prepaid_balance_id_created_at_index` (`prepaid_balance_id`,`created_at`),
  KEY `prepaid_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `prepaid_transactions_type_index` (`type`),
  CONSTRAINT `prepaid_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prepaid_transactions_prepaid_balance_id_foreign` FOREIGN KEY (`prepaid_balance_id`) REFERENCES `prepaid_balances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prepaid_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `price_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `pricing_plan_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `modification_type` varchar(255) NOT NULL,
  `modification_value` decimal(10,2) NOT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `priority` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `price_rules_pricing_plan_id_foreign` (`pricing_plan_id`),
  KEY `price_rules_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `price_rules_valid_from_valid_until_index` (`valid_from`,`valid_until`),
  CONSTRAINT `price_rules_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `price_rules_pricing_plan_id_foreign` FOREIGN KEY (`pricing_plan_id`) REFERENCES `pricing_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pricing_margins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_margins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_pricing_tier_id` bigint(20) unsigned NOT NULL,
  `margin_amount` decimal(10,4) NOT NULL,
  `margin_percentage` decimal(5,2) NOT NULL,
  `calculated_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pricing_margins_company_pricing_tier_id_calculated_date_index` (`company_pricing_tier_id`,`calculated_date`),
  KEY `idx_margin_date_tier` (`calculated_date`,`company_pricing_tier_id`),
  CONSTRAINT `pricing_margins_company_pricing_tier_id_foreign` FOREIGN KEY (`company_pricing_tier_id`) REFERENCES `company_pricing_tiers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pricing_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'package',
  `billing_interval` varchar(255) NOT NULL DEFAULT 'monthly',
  `interval_count` int(11) NOT NULL DEFAULT 1,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `included_minutes` int(11) NOT NULL DEFAULT 0,
  `included_appointments` int(11) NOT NULL DEFAULT 0,
  `included_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`included_features`)),
  `overage_price_per_minute` decimal(10,4) DEFAULT NULL,
  `overage_price_per_appointment` decimal(10,2) DEFAULT NULL,
  `volume_discounts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`volume_discounts`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `trial_days` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pricing_plans_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `pricing_plans_type_index` (`type`),
  CONSTRAINT `pricing_plans_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promo_code_uses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `promo_code_uses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `price_rule_id` bigint(20) unsigned NOT NULL,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `applied_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `promo_code_uses_subscription_id_foreign` (`subscription_id`),
  KEY `promo_code_uses_price_rule_id_subscription_id_index` (`price_rule_id`,`subscription_id`),
  CONSTRAINT `promo_code_uses_price_rule_id_foreign` FOREIGN KEY (`price_rule_id`) REFERENCES `price_rules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `promo_code_uses_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prompt_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prompt_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'general',
  `version` varchar(255) NOT NULL DEFAULT '1.0.0',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prompt_templates_slug_unique` (`slug`),
  KEY `prompt_templates_category_is_active_index` (`category`,`is_active`),
  KEY `prompt_templates_parent_id_index` (`parent_id`),
  CONSTRAINT `prompt_templates_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `prompt_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rate_limit_violations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limit_violations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `route` varchar(255) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `limit` int(11) NOT NULL,
  `attempts` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `reset_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rate_limit_violations_created_at_key_index` (`created_at`,`key`),
  KEY `rate_limit_violations_key_index` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reading_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reading_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `document_id` varchar(255) NOT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `completed_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`completed_sections`)),
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reading_progress_user_id_document_id_unique` (`user_id`,`document_id`),
  KEY `reading_progress_user_id_index` (`user_id`),
  CONSTRAINT `reading_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reseller_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reseller_company_id` bigint(20) unsigned NOT NULL,
  `permission` varchar(255) NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reseller_permissions_reseller_company_id_permission_unique` (`reseller_company_id`,`permission`),
  KEY `reseller_permissions_reseller_company_id_index` (`reseller_company_id`),
  CONSTRAINT `reseller_permissions_reseller_company_id_foreign` FOREIGN KEY (`reseller_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retell_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retell_agents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `phone_number_id` char(36) DEFAULT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `version` int(11) DEFAULT NULL,
  `version_title` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_active` tinyint(1) DEFAULT 1,
  `active` tinyint(1) DEFAULT 1,
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `sync_status` enum('pending','synced','error') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agent_id` (`agent_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_agent_id` (`agent_id`),
  KEY `fk_retell_agents_phone_number` (`phone_number_id`),
  CONSTRAINT `fk_retell_agents_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_retell_agents_phone_number` FOREIGN KEY (`phone_number_id`) REFERENCES `phone_numbers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retell_agents_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retell_agents_backup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `retell_agent_id` varchar(255) NOT NULL,
  `agent_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`agent_data`)),
  `synced_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retell_agents_backup_retell_agent_id_unique` (`retell_agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retell_ai_call_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retell_ai_call_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `agent_id` varchar(255) NOT NULL,
  `target_type` enum('all_customers','inactive_customers','custom_list') NOT NULL,
  `target_criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_criteria`)),
  `schedule_type` enum('immediate','scheduled','recurring') NOT NULL DEFAULT 'immediate',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `dynamic_variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dynamic_variables`)),
  `status` enum('draft','scheduled','running','paused','completed','failed') NOT NULL DEFAULT 'draft',
  `total_targets` int(11) NOT NULL DEFAULT 0,
  `calls_completed` int(11) NOT NULL DEFAULT 0,
  `calls_failed` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`results`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retell_ai_call_campaigns_created_by_foreign` (`created_by`),
  KEY `retell_ai_call_campaigns_company_id_status_index` (`company_id`,`status`),
  KEY `retell_ai_call_campaigns_scheduled_at_index` (`scheduled_at`),
  KEY `retell_ai_call_campaigns_created_at_index` (`created_at`),
  CONSTRAINT `retell_ai_call_campaigns_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `retell_ai_call_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retell_calls_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retell_calls_backup` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `retell_call_id` varchar(255) NOT NULL,
  `call_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`call_data`)),
  `transcript` text DEFAULT NULL,
  `recording_url` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) NOT NULL,
  `from_number` varchar(255) NOT NULL,
  `to_number` varchar(255) NOT NULL,
  `synced_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retell_calls_backup_retell_call_id_unique` (`retell_call_id`),
  KEY `retell_calls_backup_company_id_synced_at_index` (`company_id`,`synced_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retell_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retell_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `webhook_url` varchar(255) NOT NULL,
  `webhook_secret` text DEFAULT NULL,
  `webhook_events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`webhook_events`)),
  `custom_functions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_functions`)),
  `last_tested_at` timestamp NULL DEFAULT NULL,
  `test_status` enum('success','failed','pending') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `retell_configurations_company_id_unique` (`company_id`),
  KEY `retell_configurations_company_id_test_status_index` (`company_id`,`test_status`),
  CONSTRAINT `retell_configurations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `retell_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retell_webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(255) NOT NULL,
  `call_id` varchar(255) DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `retell_webhooks_event_type_index` (`event_type`),
  KEY `retell_webhooks_call_id_index` (`call_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `search_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `query` varchar(255) NOT NULL,
  `selected_type` varchar(255) DEFAULT NULL,
  `selected_id` varchar(255) DEFAULT NULL,
  `context` varchar(255) DEFAULT NULL,
  `results_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `search_history_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `search_history_query_index` (`query`),
  CONSTRAINT `search_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `search_indices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_indices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `searchable_type` varchar(255) NOT NULL,
  `searchable_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `category` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `weight` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `company_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `search_indices_searchable_type_searchable_id_index` (`searchable_type`,`searchable_id`),
  KEY `search_indices_company_id_index` (`company_id`),
  KEY `search_indices_category_index` (`category`),
  FULLTEXT KEY `search_indices_title_content_fulltext` (`title`,`content`),
  CONSTRAINT `search_indices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `severity` varchar(20) NOT NULL DEFAULT 'info',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_type` varchar(255) DEFAULT NULL,
  `resource_type` varchar(255) DEFAULT NULL,
  `resource_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `threat_indicators` text DEFAULT NULL,
  `correlation_id` varchar(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_audit_logs_created_at_event_type_index` (`created_at`,`event_type`),
  KEY `security_audit_logs_user_id_user_type_index` (`user_id`,`user_type`),
  KEY `security_audit_logs_resource_type_resource_id_index` (`resource_type`,`resource_id`),
  KEY `security_audit_logs_event_type_index` (`event_type`),
  KEY `security_audit_logs_correlation_id_index` (`correlation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `method` varchar(255) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_addons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'recurring',
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `billing_interval` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_metered` tinyint(1) NOT NULL DEFAULT 0,
  `meter_unit` varchar(255) DEFAULT NULL,
  `meter_unit_price` decimal(10,4) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `requirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirements`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_addons_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `service_addons_type_index` (`type`),
  CONSTRAINT `service_addons_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_event_type_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_event_type_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `calcom_event_type_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `keywords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Alternative names/keywords for matching' CHECK (json_valid(`keywords`)),
  `priority` int(11) NOT NULL DEFAULT 0 COMMENT 'Higher priority = preferred match',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_service_event_branch` (`service_id`,`calcom_event_type_id`,`branch_id`),
  KEY `idx_service_event` (`service_id`,`calcom_event_type_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_branch` (`branch_id`),
  KEY `idx_active` (`is_active`),
  KEY `service_event_type_mappings_calcom_event_type_id_foreign` (`calcom_event_type_id`),
  CONSTRAINT `service_event_type_mappings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_event_type_mappings_calcom_event_type_id_foreign` FOREIGN KEY (`calcom_event_type_id`) REFERENCES `calcom_event_types` (`calcom_numeric_event_type_id`) ON DELETE CASCADE,
  CONSTRAINT `service_event_type_mappings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_event_type_mappings_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_staff` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint(20) unsigned NOT NULL,
  `staff_id` char(36) NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_staff_service_id_staff_id_unique` (`service_id`,`staff_id`),
  KEY `service_staff_staff_id_index` (`staff_id`),
  KEY `service_staff_service_id_index` (`service_id`),
  CONSTRAINT `service_staff_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_staff_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `default_duration_minutes` int(11) NOT NULL DEFAULT 30,
  `is_online_bookable` tinyint(1) NOT NULL DEFAULT 1,
  `min_staff_required` int(11) NOT NULL DEFAULT 1,
  `buffer_time_minutes` int(11) NOT NULL DEFAULT 0,
  `calcom_event_type_id` varchar(255) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` char(36) DEFAULT NULL,
  `tenant_id` char(36) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `max_bookings_per_day` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `required_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Skills required to perform this service' CHECK (json_valid(`required_skills`)),
  `required_certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Certifications required' CHECK (json_valid(`required_certifications`)),
  `complexity_level` enum('basic','intermediate','advanced','expert') NOT NULL DEFAULT 'basic',
  PRIMARY KEY (`id`),
  KEY `services_calcom_event_type_id_index` (`calcom_event_type_id`),
  KEY `services_company_id_index` (`company_id`),
  KEY `services_deleted_at_index` (`deleted_at`),
  KEY `services_company_deleted_index` (`company_id`,`deleted_at`),
  KEY `idx_services_company_name` (`company_id`,`name`),
  KEY `idx_services_company_active` (`company_id`,`active`),
  KEY `idx_services_company_id` (`company_id`),
  KEY `idx_services_active` (`active`),
  KEY `idx_company_services` (`company_id`,`id`),
  KEY `services_branch_id_index` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`),
  KEY `idx_sessions_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `small_business_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `small_business_monitoring` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `year` year(4) NOT NULL,
  `revenue_current` decimal(12,2) NOT NULL DEFAULT 0.00,
  `revenue_previous_year` decimal(12,2) NOT NULL DEFAULT 0.00,
  `revenue_projected` decimal(12,2) NOT NULL DEFAULT 0.00,
  `threshold_percentage` decimal(5,2) NOT NULL COMMENT 'Prozent vom Schwellenwert',
  `alert_sent` tinyint(1) NOT NULL DEFAULT 0,
  `alert_sent_at` timestamp NULL DEFAULT NULL,
  `status` enum('safe','warning','critical','exceeded') NOT NULL DEFAULT 'safe',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `small_business_monitoring_company_id_year_unique` (`company_id`,`year`),
  KEY `small_business_monitoring_status_index` (`status`),
  CONSTRAINT `small_business_monitoring_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_message_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_message_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `channel` enum('sms','whatsapp') NOT NULL,
  `to` varchar(255) NOT NULL,
  `from` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `twilio_sid` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `price` decimal(10,4) DEFAULT NULL,
  `price_unit` varchar(3) DEFAULT NULL,
  `segments` int(11) NOT NULL DEFAULT 1,
  `error_code` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_message_logs_twilio_sid_unique` (`twilio_sid`),
  KEY `sms_message_logs_appointment_id_foreign` (`appointment_id`),
  KEY `sms_message_logs_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `sms_message_logs_customer_id_channel_index` (`customer_id`,`channel`),
  KEY `sms_message_logs_status_created_at_index` (`status`,`created_at`),
  KEY `sms_message_logs_twilio_sid_index` (`twilio_sid`),
  KEY `sms_message_logs_channel_index` (`channel`),
  CONSTRAINT `sms_message_logs_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sms_message_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sms_message_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `home_branch_id` char(36) DEFAULT NULL,
  `calcom_user_id` varchar(255) DEFAULT NULL,
  `calcom_calendar_link` varchar(255) DEFAULT NULL,
  `is_bookable` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `external_calendar_id` varchar(255) DEFAULT NULL,
  `calendar_provider` varchar(255) DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of skill identifiers' CHECK (json_valid(`skills`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '["de"]' COMMENT 'Array of language codes' CHECK (json_valid(`languages`)),
  `mobility_radius_km` int(11) DEFAULT NULL COMMENT 'How far staff can travel between branches',
  `specializations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Special certifications or expertise' CHECK (json_valid(`specializations`)),
  `average_rating` decimal(3,2) DEFAULT NULL COMMENT 'Average customer rating',
  `certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Professional certifications' CHECK (json_valid(`certifications`)),
  `experience_level` int(11) NOT NULL DEFAULT 1,
  `working_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_hours`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `calcom_username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_company_email_unique` (`company_id`,`email`),
  KEY `staff_branch_id_index` (`branch_id`),
  KEY `staff_company_id_index` (`company_id`),
  KEY `staff_email_index` (`email`),
  KEY `staff_company_branch_index` (`company_id`,`branch_id`),
  KEY `idx_staff_company_name` (`company_id`,`name`),
  KEY `idx_staff_company_branch` (`company_id`,`branch_id`),
  KEY `idx_staff_home_branch_id` (`home_branch_id`),
  KEY `idx_branch_staff` (`branch_id`,`id`),
  KEY `idx_company_staff_simple` (`company_id`),
  KEY `idx_staff_branch` (`branch_id`),
  KEY `idx_staff_company_active` (`company_id`,`active`),
  KEY `idx_branch_active` (`branch_id`,`active`),
  KEY `idx_staff_availability` (`branch_id`,`active`,`created_at`),
  KEY `staff_is_active_index` (`is_active`),
  KEY `idx_staff_company_bookable` (`company_id`,`is_bookable`),
  CONSTRAINT `staff_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_home_branch_id_foreign` FOREIGN KEY (`home_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_branches` (
  `staff_id` char(36) NOT NULL,
  `branch_id` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`staff_id`,`branch_id`),
  KEY `staff_branches_staff_id_index` (`staff_id`),
  KEY `staff_branches_branch_id_index` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_branches_and_staff_services_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_branches_and_staff_services_tables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_event_type_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_event_type_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` bigint(20) unsigned NOT NULL,
  `event_type_id` bigint(20) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_event_type_assignments_staff_id_event_type_id_unique` (`staff_id`,`event_type_id`),
  KEY `staff_event_type_assignments_staff_id_index` (`staff_id`),
  KEY `staff_event_type_assignments_event_type_id_index` (`event_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_event_types` (
  `id` char(36) NOT NULL,
  `staff_id` char(36) NOT NULL,
  `calcom_event_type_id` bigint(20) unsigned NOT NULL,
  `calcom_user_id` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `custom_duration` int(11) DEFAULT NULL,
  `custom_price` decimal(10,2) DEFAULT NULL,
  `availability_override` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`availability_override`)),
  `max_bookings_per_day` int(11) DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_event_types_staff_id_calcom_event_type_id_unique` (`staff_id`,`calcom_event_type_id`),
  KEY `staff_event_types_staff_id_index` (`staff_id`),
  KEY `staff_event_types_calcom_event_type_id_index` (`calcom_event_type_id`),
  KEY `idx_staff_event_types_staff` (`staff_id`),
  CONSTRAINT `staff_event_types_calcom_event_type_id_foreign` FOREIGN KEY (`calcom_event_type_id`) REFERENCES `calcom_event_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_event_types_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `staff_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` char(36) NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_service_staff_id_service_id_unique` (`staff_id`,`service_id`),
  KEY `staff_service_service_id_foreign` (`service_id`),
  CONSTRAINT `staff_service_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_service_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_addons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `service_addon_id` bigint(20) unsigned NOT NULL,
  `price_override` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_addons_subscription_id_service_addon_id_unique` (`subscription_id`,`service_addon_id`),
  KEY `subscription_addons_service_addon_id_foreign` (`service_addon_id`),
  KEY `subscription_addons_status_index` (`status`),
  CONSTRAINT `subscription_addons_service_addon_id_foreign` FOREIGN KEY (`service_addon_id`) REFERENCES `service_addons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscription_addons_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `stripe_subscription_item_id` varchar(255) NOT NULL,
  `stripe_price_id` varchar(255) NOT NULL,
  `stripe_product_id` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_items_stripe_subscription_item_id_unique` (`stripe_subscription_item_id`),
  KEY `subscription_items_subscription_id_index` (`subscription_id`),
  CONSTRAINT `subscription_items_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `pricing_plan_id` bigint(20) unsigned DEFAULT NULL,
  `custom_price` decimal(10,2) DEFAULT NULL,
  `custom_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_features`)),
  `stripe_subscription_id` varchar(255) NOT NULL,
  `stripe_customer_id` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `stripe_status` varchar(255) NOT NULL,
  `stripe_price_id` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `next_billing_date` date DEFAULT NULL,
  `billing_interval` varchar(255) DEFAULT NULL,
  `billing_interval_count` int(11) NOT NULL DEFAULT 1,
  `current_period_start` timestamp NULL DEFAULT NULL,
  `current_period_end` timestamp NULL DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_subscription_id_unique` (`stripe_subscription_id`),
  KEY `subscriptions_company_id_stripe_status_index` (`company_id`,`stripe_status`),
  KEY `subscriptions_current_period_end_index` (`current_period_end`),
  KEY `subscriptions_stripe_customer_id_index` (`stripe_customer_id`),
  KEY `subscriptions_pricing_plan_id_foreign` (`pricing_plan_id`),
  CONSTRAINT `subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_pricing_plan_id_foreign` FOREIGN KEY (`pricing_plan_id`) REFERENCES `pricing_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `system_metrics_type_created_at_index` (`type`,`created_at`),
  KEY `system_metrics_type_index` (`type`),
  KEY `system_metrics_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'System-wide tax rates',
  `description` text DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `stripe_tax_rate_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_rates_company_id_is_default_index` (`company_id`,`is_default`),
  KEY `tax_rates_is_system_index` (`is_system`),
  CONSTRAINT `tax_rates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tax_threshold_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_threshold_monitoring` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `year` year(4) NOT NULL,
  `annual_revenue` decimal(12,2) NOT NULL DEFAULT 0.00,
  `threshold_exceeded` tinyint(1) NOT NULL DEFAULT 0,
  `notification_sent_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tax_threshold_monitoring_company_id_year_unique` (`company_id`,`year`),
  CONSTRAINT `tax_threshold_monitoring_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `balance_cents` bigint(20) unsigned NOT NULL DEFAULT 0,
  `slug` varchar(255) NOT NULL,
  `api_key_hash` varchar(255) DEFAULT NULL,
  `calcom_team_slug` varchar(255) DEFAULT NULL COMMENT 'Der Team-Slug des Mandanten bei Cal.com',
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_slug_unique` (`slug`),
  KEY `tenants_calcom_team_slug_index` (`calcom_team_slug`),
  KEY `fk_tenants_company_id` (`company_id`),
  CONSTRAINT `fk_tenants_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `theme_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `theme_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_id` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_by` varchar(255) NOT NULL,
  `git_commit` varchar(255) DEFAULT NULL,
  `git_branch` varchar(255) DEFAULT NULL,
  `file_count` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `theme_snapshots_snapshot_id_unique` (`snapshot_id`),
  KEY `theme_snapshots_created_at_index` (`created_at`),
  KEY `theme_snapshots_created_by_index` (`created_by`),
  KEY `theme_snapshots_git_branch_index` (`git_branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `two_factor_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `two_factor_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token` varchar(6) NOT NULL,
  `type` enum('login','confirmation') NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `two_factor_sessions_user_id_expires_at_index` (`user_id`,`expires_at`),
  KEY `two_factor_sessions_token_index` (`token`),
  CONSTRAINT `two_factor_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unified_event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unified_event_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` char(36) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `provider` varchar(255) NOT NULL,
  `external_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 30,
  `price` decimal(10,2) DEFAULT NULL,
  `provider_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_data`)),
  `conflict_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conflict_data`)),
  `imported_at` timestamp NULL DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `assignment_status` enum('assigned','unassigned') DEFAULT 'unassigned',
  `import_status` enum('success','duplicate','error','pending_review') DEFAULT 'success',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_provider_external` (`provider`,`external_id`),
  KEY `idx_branch_provider` (`branch_id`,`provider`),
  KEY `idx_assignment_status` (`assignment_status`),
  KEY `idx_import_status` (`import_status`),
  KEY `idx_imported_at` (`imported_at`),
  KEY `idx_slug` (`slug`),
  CONSTRAINT `fk_unified_event_types_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unified_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unified_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` enum('api','error','audit','security','webhook','email','sms','whatsapp','notification','search','sync','mcp','data_flow','anomaly') NOT NULL,
  `level` enum('debug','info','warning','error','critical') DEFAULT 'info',
  `message` text DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_log_type_created` (`log_type`,`created_at`),
  KEY `idx_company_created` (`company_id`,`created_at`),
  KEY `idx_level_created` (`level`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_doc_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_doc_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `document_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_doc_favorites_user_id_document_id_unique` (`user_id`,`document_id`),
  KEY `user_doc_favorites_user_id_index` (`user_id`),
  CONSTRAINT `user_doc_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_type` varchar(255) NOT NULL,
  `preference_key` varchar(255) NOT NULL,
  `preference_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`preference_value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_preferences_user_id_user_type_preference_key_unique` (`user_id`,`user_type`,`preference_key`),
  KEY `user_preferences_user_id_user_type_index` (`user_id`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status_title` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `two_factor_enforced` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_method` enum('authenticator','sms') DEFAULT 'authenticator',
  `two_factor_phone_number` varchar(255) DEFAULT NULL COMMENT 'Phone number for SMS 2FA',
  `two_factor_phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `remember_token` varchar(100) DEFAULT NULL,
  `interface_language` varchar(5) NOT NULL DEFAULT 'de',
  `content_language` varchar(5) NOT NULL DEFAULT 'de',
  `auto_translate_content` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `kunde_id` bigint(20) unsigned DEFAULT NULL,
  `tenant_id` bigint(20) unsigned DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_kunde_id_foreign` (`kunde_id`),
  KEY `users_tenant_id_foreign` (`tenant_id`),
  KEY `users_two_factor_enforced_index` (`two_factor_enforced`),
  KEY `users_two_factor_confirmed_at_index` (`two_factor_confirmed_at`),
  KEY `idx_company_email` (`company_id`,`email`),
  CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_kunde_id_foreign` FOREIGN KEY (`kunde_id`) REFERENCES `kunden` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `validation_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `validation_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `validatable_type` varchar(255) DEFAULT NULL,
  `validatable_id` bigint(20) unsigned DEFAULT NULL,
  `validation_type` varchar(50) DEFAULT NULL,
  `entity_type` enum('company','branch','staff') NOT NULL,
  `entity_id` varchar(36) NOT NULL,
  `test_type` varchar(50) NOT NULL,
  `status` enum('pending','success','warning','error') NOT NULL,
  `message` text DEFAULT NULL,
  `results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`results`)),
  `tested_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_entity_expires` (`entity_type`,`entity_id`,`expires_at`),
  KEY `validation_results_test_type_status_index` (`test_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webauthn_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webauthn_credentials` (
  `id` varchar(510) NOT NULL,
  `authenticatable_type` varchar(255) NOT NULL,
  `authenticatable_id` bigint(20) unsigned NOT NULL,
  `user_id` char(36) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `counter` bigint(20) unsigned DEFAULT NULL,
  `rp_id` varchar(255) NOT NULL,
  `origin` varchar(255) NOT NULL,
  `transports` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transports`)),
  `aaguid` char(36) DEFAULT NULL,
  `public_key` text NOT NULL,
  `attestation_format` varchar(255) NOT NULL DEFAULT 'none',
  `certificates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certificates`)),
  `disabled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tenant_id` varchar(36) DEFAULT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webauthn_user_index` (`authenticatable_type`,`authenticatable_id`),
  KEY `webauthn_credentials_tenant_id_authenticatable_id_index` (`tenant_id`,`authenticatable_id`),
  KEY `idx_webauthn_tenant_user` (`tenant_id`,`user_id`),
  KEY `idx_webauthn_user_tenant` (`user_id`,`tenant_id`),
  KEY `idx_webauthn_last_used` (`last_used_at`),
  KEY `idx_webauthn_tenant_device` (`tenant_id`,`device_type`),
  KEY `idx_webauthn_tenant_created` (`tenant_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_dead_letter_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_dead_letter_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `correlation_id` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`headers`)),
  `error` text NOT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `failed_at` timestamp NOT NULL,
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_dead_letter_queue_event_type_failed_at_index` (`event_type`,`failed_at`),
  KEY `webhook_dead_letter_queue_resolved_failed_at_index` (`resolved`,`failed_at`),
  KEY `webhook_dead_letter_queue_correlation_id_index` (`correlation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_deduplication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_deduplication` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` varchar(255) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response`)),
  `response_status` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_duplicate` tinyint(1) NOT NULL DEFAULT 0,
  `is_replay_attack` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webhook_deduplication_webhook_id_unique` (`webhook_id`),
  KEY `webhook_deduplication_created_at_provider_index` (`created_at`,`provider`),
  KEY `webhook_deduplication_event_type_index` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `event_id` varchar(255) NOT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `correlation_id` varchar(255) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webhook_events_event_id_unique` (`event_id`),
  UNIQUE KEY `webhook_events_idempotency_key_unique` (`idempotency_key`),
  KEY `webhook_events_provider_event_type_index` (`provider`,`event_type`),
  KEY `webhook_events_status_index` (`status`),
  KEY `webhook_events_created_at_index` (`created_at`),
  KEY `webhook_events_correlation_id_index` (`correlation_id`),
  KEY `webhook_events_company_id_index` (`company_id`),
  KEY `webhook_events_provider_index` (`provider`),
  KEY `idx_webhook_events_status` (`status`,`created_at`),
  KEY `idx_webhook_events_provider_created` (`provider`,`created_at`),
  KEY `idx_provider_event_created` (`provider`,`event_id`,`created_at`),
  KEY `idx_webhook_dedup` (`provider`,`event_id`,`status`),
  KEY `idx_webhook_retry` (`status`,`retry_count`,`created_at`),
  CONSTRAINT `webhook_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `event_type` varchar(100) NOT NULL,
  `webhook_id` varchar(255) NOT NULL,
  `correlation_id` varchar(255) DEFAULT NULL,
  `status` enum('success','error','duplicate') NOT NULL DEFAULT 'success',
  `payload` text DEFAULT NULL,
  `headers` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `processing_time_ms` int(11) DEFAULT NULL,
  `is_duplicate` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `webhook_logs_webhook_id_unique` (`webhook_id`),
  KEY `webhook_logs_created_at_provider_index` (`created_at`,`provider`),
  KEY `webhook_logs_status_created_at_index` (`status`,`created_at`),
  KEY `webhook_logs_provider_event_type_created_at_index` (`provider`,`event_type`,`created_at`),
  KEY `webhook_logs_provider_index` (`provider`),
  KEY `webhook_logs_event_type_index` (`event_type`),
  KEY `webhook_logs_correlation_id_index` (`correlation_id`),
  KEY `webhook_logs_status_index` (`status`),
  KEY `webhook_logs_company_id_index` (`company_id`),
  KEY `webhook_logs_created_at_index` (`created_at`),
  KEY `webhook_logs_is_duplicate_index` (`is_duplicate`),
  CONSTRAINT `webhook_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_payloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_payloads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_raw_payloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_raw_payloads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_event_id` bigint(20) unsigned NOT NULL,
  `payload` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_webhook_event` (`webhook_event_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `whatsapp_message_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_message_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `appointment_id` bigint(20) unsigned DEFAULT NULL,
  `to` varchar(255) NOT NULL,
  `from` varchar(255) DEFAULT NULL,
  `type` enum('text','template','media','location') NOT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `template_parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_parameters`)),
  `message_body` text DEFAULT NULL,
  `status` enum('queued','sent','delivered','read','failed') NOT NULL,
  `whatsapp_status` varchar(255) DEFAULT NULL,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `cost` decimal(10,4) DEFAULT NULL,
  `conversation_id` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_message_logs_message_id_unique` (`message_id`),
  KEY `whatsapp_message_logs_company_id_index` (`company_id`),
  KEY `whatsapp_message_logs_customer_id_index` (`customer_id`),
  KEY `whatsapp_message_logs_appointment_id_index` (`appointment_id`),
  KEY `whatsapp_message_logs_status_index` (`status`),
  KEY `whatsapp_message_logs_to_created_at_index` (`to`,`created_at`),
  KEY `whatsapp_message_logs_conversation_id_index` (`conversation_id`),
  CONSTRAINT `whatsapp_message_logs_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_message_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `whatsapp_message_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_commands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_commands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `command_workflow_id` bigint(20) unsigned NOT NULL,
  `command_template_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config`)),
  `condition` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_commands_command_workflow_id_order_unique` (`command_workflow_id`,`order`),
  KEY `workflow_commands_command_template_id_foreign` (`command_template_id`),
  KEY `workflow_commands_command_workflow_id_command_template_id_index` (`command_workflow_id`,`command_template_id`),
  CONSTRAINT `workflow_commands_command_template_id_foreign` FOREIGN KEY (`command_template_id`) REFERENCES `command_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_commands_command_workflow_id_foreign` FOREIGN KEY (`command_workflow_id`) REFERENCES `command_workflows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_executions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `command_workflow_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','running','success','failed','cancelled','paused') NOT NULL DEFAULT 'pending',
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `current_step` int(11) NOT NULL DEFAULT 0,
  `current_command_index` int(11) NOT NULL DEFAULT 0,
  `total_steps` int(11) NOT NULL DEFAULT 0,
  `output` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`output`)),
  `error_message` text DEFAULT NULL,
  `execution_time_ms` int(11) DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_executions_status_index` (`status`),
  KEY `workflow_executions_user_id_index` (`user_id`),
  KEY `workflow_executions_company_id_index` (`company_id`),
  KEY `workflow_executions_command_workflow_id_index` (`command_workflow_id`),
  KEY `workflow_executions_created_at_index` (`created_at`),
  CONSTRAINT `workflow_executions_command_workflow_id_foreign` FOREIGN KEY (`command_workflow_id`) REFERENCES `command_workflows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_executions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_executions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `command_workflow_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_favorites_user_id_command_workflow_id_unique` (`user_id`,`command_workflow_id`),
  KEY `workflow_favorites_command_workflow_id_foreign` (`command_workflow_id`),
  CONSTRAINT `workflow_favorites_command_workflow_id_foreign` FOREIGN KEY (`command_workflow_id`) REFERENCES `command_workflows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `workflow_favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `working_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `working_hours` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` char(36) NOT NULL,
  `weekday` tinyint(4) NOT NULL,
  `start` time NOT NULL,
  `end` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `day_of_week` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday, 7=Sunday',
  PRIMARY KEY (`id`),
  KEY `working_hours_day_of_week_index` (`day_of_week`),
  KEY `working_hours_staff_id_index` (`staff_id`),
  KEY `working_hours_staff_day_index` (`staff_id`,`day_of_week`),
  KEY `idx_working_hours_staff_day` (`staff_id`,`day_of_week`),
  CONSTRAINT `working_hours_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'*_fix_staff_branch_id_field',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2025_01_13_add_capacity_fields_to_staff',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_01_13_add_mobile_app_fields_to_customers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_01_13_add_reminder_fields_to_appointments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_03_19_150030_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_03_19_150040_create_kunden_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_03_19_150056_add_kunde_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_03_19_150110_create_integrations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_03_19_160234_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_03_20_181810_ensure_calls_table_exists',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_03_20_181815_align_calls_table_with_model',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_03_20_181820_add_kunde_id_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_03_21_150758_create_oauth_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_03_21_150759_create_oauth_personal_access_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_03_22_085045_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_03_22_085045_create_phone_numbers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_03_22_085045_create_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_03_22_112544_create_oauth_auth_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_03_22_112545_create_oauth_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_03_22_112546_create_oauth_refresh_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_03_27_151813_create_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_03_27_151814_create_api_health_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_03_27_151814_create_retell_agents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_04_04_142048_add_tenant_id_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_04_29_134517_create_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_04_29_134518_create_calendars_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_04_29_152000_create_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_04_29_152815_create_staff_service_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_04_29_153012_create_working_hours_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_04_30_072730_alter_branches_customer_id_back_to_bigint',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_04_30_072859_add_branch_customer_fk',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_04_30_143324_update_integrations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_04_30_164923_fix_integrations_fk',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_04_30_165307_fix_integrations_fk',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_05_01_083530_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_05_01_083530_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_05_01_085108_ensure_appointments_table_exists',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_05_01_091735_alter_calls_raw_to_json',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_05_01_091740_add_customer_fk_to_calls',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_05_02_135535_alter_calls_add_retell_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_05_02_142413_alter_calls_retell_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_05_02_145708_alter_calls_add_details',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_05_02_150542_alter_calls_add_details',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_05_03_070914_add_duration_sec_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_05_03_071357_add_duration_sec_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_05_03_075425_add_relations_and_cost_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_05_03_075425_create_agents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_05_04_091545_calls_add_retell_missing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_05_04_111306_add_conversation_id_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_05_04_112000_calls_add_conversation_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_05_04_145240_add_call_id_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_05_05_162425_create_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_05_05_162426_add_balance_cents_to_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_05_05_163431_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_05_06_170852_add_slug_to_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_05_06_171214_add_slug_and_api_key_to_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_05_06_192113_alter_tenants_for_uuid',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_05_07_113058_add_birthdate_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_05_08_120000_create_retell_webhooks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_05_09_200001_make_staff_id_nullable_on_calcom_event_types',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_05_10_173530_create_password_reset_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_05_11_183656_add_details_to_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_05_11_190001_create_branch_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_05_11_190002_create_branch_service_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_05_11_190003_add_home_branch_to_staff',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_05_11_200000_create_calcom_event_types_and_bookings_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_05_13_000000_add_fields_to_calls',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_05_13_065443_recreate_integrations_if_missing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_05_13_071116_2025_05_10_090000_recreate_staff_if_missing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_05_16_184107_add_calcom_fields_to_calcom_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_05_16_184230_add_calcom_team_slug_to_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_05_18_142705_rename_start_column_on_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_05_18_143853_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_05_18_143854_add_event_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_05_18_143855_add_batch_uuid_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_05_19_000000_create_dashboard_configurations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_05_26_111052_add_tenant_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_05_26_111100_add_email_to_tenants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_05_27_102550_add_calendar_fields_to_staff',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_05_27_102550_add_integration_fields_to_companies',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_05_27_102550_add_invoice_fields_to_branches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_05_27_103419_add_invoice_fields_to_branches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_05_27_121711_add_company_id_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_05_27_122940_add_company_id_to_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_05_28_064807_add_retell_columns_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_05_28_065040_add_retell_columns_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_05_28_133327_add_api_keys_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_05_28_134510_add_event_type_id_to_companies',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_05_28_162041_add_api_test_errors_to_companies',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_05_28_183048_create_dummy_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_05_29_155901_create_api_credentials_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_05_29_172036_add_booking_fields_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_05_29_192155_change_agent_id_to_string_in_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_05_30_073853_add_email_notifications_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_05_30_101653_add_calcom_event_type_id_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_05_30_103812_add_call_id_to_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_05_30_110254_add_missing_columns_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_05_30_131221_add_phone_number_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_05_31_081003_add_is_active_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_05_31_091427_add_deleted_at_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_05_31_091553_add_deleted_at_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_05_31_095101_create_service_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_05_31_101218_add_deleted_at_to_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_05_31_115921_create_staff_branches_and_staff_services_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_05_31_120334_fix_staff_tables_naming_issue',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_05_31_120649_create_missing_staff_pivot_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_05_31_120852_add_day_of_week_to_working_hours_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_05_31_121353_add_staff_id_to_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_05_31_130000_add_missing_columns_simple',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_05_31_162146_add_calendar_fields_to_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_05_31_162146_add_calendar_mode_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_05_31_163334_fix_calendar_mappings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_05_31_163846_add_calendar_mode_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_05_31_163902_add_calendar_fields_to_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_05_31_183913_add_integrations_tested_at_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_06_01_163240_add_api_version_to_calcom_bookings',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_06_01_163240_add_calcom_v2_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_06_02_095925_add_calcom_event_type_id_to_branches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_06_02_100639_add_calcom_user_id_to_branches',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_06_02_104018_add_calcom_event_type_id_to_services',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_06_02_160459_update_companies_retell_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_06_04_065055_create_master_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_06_04_065100_create_branch_service_overrides_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_06_04_065106_create_staff_service_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_06_04_065110_create_validation_results_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_06_04_144741_update_branches_table_structure',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_06_04_154728_add_active_to_retell_agents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_06_04_155952_ensure_branch_fields_exist',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_06_04_161144_create_business_hours_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_06_04_161624_add_business_hours_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_06_04_165451_create_business_hours_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_06_04_170653_add_business_hours_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_06_05_000000_add_fields_to_branch_service_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_06_05_103513_create_calendar_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_06_05_103818_create_unified_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_06_05_121854_create_event_type_mappings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_06_05_122613_add_assignment_status_to_unified_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_06_05_123433_make_branch_id_nullable_safely',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_06_05_131334_add_import_timestamps_to_unified_event_types',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2025_06_05_133248_add_uuid_to_branches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2025_06_06_105538_add_calcom_booking_id_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2025_06_06_110615_add_calcom_booking_id_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2025_06_09_182203_add_missing_columns_to_staff_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2025_06_10_183716_create_staff_service_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2025_06_12_120001_create_staff_event_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2025_06_12_210202_fix_staff_service_assignments_id_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2025_06_13_090153_add_calcom_event_type_id_to_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2025_06_13_092851_migrate_staff_service_assignments_to_staff_event_types',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2025_06_13_093142_add_event_management_permissions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2025_06_13_110000_create_notification_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2025_06_13_134431_create_slow_query_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2025_06_13_162722_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2025_06_14_070316_add_appointment_id_to_calls_table_for_compatibility',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2025_06_14_100000_create_onboarding_progress_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2025_06_14_101110_add_security_dashboard_permission',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2025_06_14_135628_add_missing_fields_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2025_06_14_151005_increase_api_key_column_sizes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2025_06_14_221704_add_missing_fields_to_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2025_06_14_223007_add_company_id_to_users_table_if_missing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2025_06_14_add_tags_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2025_06_15_163036_add_notes_to_appointments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2025_06_15_193601_add_retell_advanced_fields_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2025_06_15_add_email_verified_at_to_laravel_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2025_06_16_095555_add_duration_minutes_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2025_06_16_095900_add_missing_retell_fields_to_calls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2025_06_16_164937_fix_call_costs_from_cents_to_euros',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2025_06_16_170100_create_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2025_06_16_170110_create_invoice_items_flexible_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2025_06_16_170147_create_pricing_models_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2025_06_16_fix_missing_call_columns',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2025_06_17_093617_fix_company_json_fields_defaults',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2025_06_17_094102_rename_active_to_is_active_in_companies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2025_06_17_111220_add_retell_agent_provisioning_fields_to_branches_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2025_06_17_114712_create_system_alerts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2025_06_17_115232_fix_business_hours_templates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2025_06_17_144828_add_version_field_for_optimistic_locking',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2025_06_17_145013_create_appointment_locks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2025_06_17_145304_create_webhook_events_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2025_06_17_150711_create_availability_cache_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2025_06_17_150716_create_api_call_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2025_06_17_152433_add_branch_id_to_appointment_locks_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2025_06_17_155555_create_booking_flow_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2025_06_17_add_industry_to_companies',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2025_06_17_add_missing_columns_to_companies',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2025_06_27_create_ml_sentiment_tables',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2025_06_16_000000_add_company_id_to_appointments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2025_06_16_000001_add_company_id_to_customers_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2025_06_16_000002_normalize_company_relationships',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2025_06_17_add_performance_critical_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2025_06_17_cleanup_redundant_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2025_06_17_create_circuit_breaker_metrics_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2025_06_17_create_critical_errors_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2025_06_17_create_notes_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2025_06_17_create_notification_logs_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2025_06_17_create_webhook_logs_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2025_06_17_fix_branches_uuid',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2025_06_17_fix_missing_master_services_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2025_06_17_restore_critical_pivot_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2025_06_17_restore_critical_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2025_06_18_add_company_id_to_staff_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2025_06_18_add_privacy_consent_to_customers',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2025_06_18_add_sentiment_to_calls',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2025_06_18_create_advanced_backup_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2025_06_18_create_backup_logs_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2025_06_19_add_authentication_to_customers_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2025_06_19_194242_create_gdpr_requests_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2025_06_27_075116_fix_tenant_company_consistency',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2025_06_27_080659_add_company_id_to_webhook_events_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2025_06_27_081523_add_company_id_to_calcom_event_types_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2025_03_19_150031_add_two_factor_columns_to_users_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2025_06_27_add_two_factor_columns_to_users_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2025_06_27_create_two_factor_sessions_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2025_06_18_create_dashboard_metrics_tables',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2025_06_18_create_dashboard_metrics_tables_simple',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2025_06_18_create_dashboard_performance_indexes',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2025_06_18_create_logs_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2025_06_18_create_unified_event_types_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2025_06_18_create_validation_results_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2025_06_18_fix_missing_agents_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2025_06_19_100502_add_multi_location_support_fields',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2025_06_19_182915_add_security_fields_to_tables',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2025_06_19_add_is_active_to_branches',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2025_06_19_185445_add_critical_performance_indexes_v2',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2025_06_19_194225_create_cookie_consents_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2025_06_19_add_creation_mode_to_invoices',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2025_06_19_add_critical_performance_indexes',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2025_06_19_add_extracted_fields_to_calls_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2025_06_19_add_payment_terms_to_invoices',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2025_06_19_add_skills_and_preferences_to_staff_and_customers',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2025_06_19_add_sms_whatsapp_to_phone_numbers',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2025_06_19_create_customer_password_resets_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2025_06_19_create_phone_numbers_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2025_06_19_create_security_logs_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2025_06_19_create_tax_compliance_tables_safe',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2025_06_19_make_branch_id_nullable_in_phone_numbers',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2025_06_19_update_phone_numbers_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2025_06_20_084131_create_knowledge_base_tables',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2025_06_20_092324_create_knowledge_management_tables',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2025_06_20_104429_add_order_column_to_knowledge_documents',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2025_06_20_add_missing_company_columns',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2025_06_20_create_mcp_metrics_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2025_06_20_create_missing_tax_tables',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2025_06_20_create_notifications_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2025_06_20_create_webhook_dead_letter_queue_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2025_06_21_090808_update_mcp_metrics_table_structure',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2025_06_21_121430_add_extended_settings_to_calcom_event_types_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2025_06_21_200800_create_mcp_metrics_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2025_06_21_203000_create_logs_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2025_06_21_203500_add_processing_time_to_webhook_logs',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2025_06_21_204000_add_is_duplicate_to_webhook_logs',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2025_06_21_add_retell_fields_to_phone_numbers_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2025_06_21_add_team_id_to_calcom_event_types',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2025_06_21_create_circuit_breaker_metrics_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2025_06_21_create_event_type_import_logs_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2025_06_21_create_performance_optimization_indexes_final',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2025_06_22_001513_add_calcom_team_slug_to_companies_table_if_missing',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2025_06_22_002350_add_missing_columns_to_branch_service_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2025_06_22_090808_move_retell_agent_id_from_branches_to_phone_numbers',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2025_06_22_114709_create_branch_event_types_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2025_06_22_120000_add_retell_agent_version_to_phone_numbers',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2025_06_22_120000_prepare_remove_calcom_event_type_id_from_branches',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2025_06_22_124704_add_missing_columns_to_event_type_import_logs',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2025_06_22_124748_add_branch_id_to_calcom_event_types',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2025_06_22_125358_add_missing_columns_to_calcom_event_types',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2025_06_22_130838_create_service_event_type_mappings_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2025_06_22_131755_add_calcom_event_type_id_to_appointments',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2025_06_22_140000_update_staff_event_types_for_uuid',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2025_06_22_154500_add_provider_to_webhook_events',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2025_06_22_193132_create_callback_requests_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2025_06_22_add_missing_foreign_keys',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2025_06_23_000255_add_trial_ends_at_to_companies_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2025_06_23_002006_create_service_usage_logs_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2025_06_23_002227_create_feature_flags_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2025_06_23_070735_drop_unused_empty_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2025_06_23_070805_migrate_kunden_to_customers',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2025_06_23_172607_add_missing_columns_to_calls_table_fix',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2025_06_23_220055_create_retell_configurations_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2025_06_23_221000_add_missing_columns_to_webhook_events',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2025_06_23_add_missing_columns_to_webhook_events',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2025_06_23_add_status_to_customers_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2025_06_23_fix_mcp_metrics_status_column',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2025_06_23_fix_missing_columns',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2025_06_24_080000_encrypt_api_keys',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2025_06_24_080500_expand_webhook_secret_column',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2025_06_24_090000_add_performance_indexes',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2025_06_24_100000_create_appointment_locks_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2025_06_24_add_metadata_column_to_appointments',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2025_06_24_make_calcom_event_type_optional',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2025_06_25_132946_add_retell_default_settings_to_companies_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2025_06_25_143717_add_sync_fields_to_retell_agents_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2025_06_25_200000_add_multi_booking_fields_to_appointments',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2025_06_25_200001_add_preference_fields_to_customers',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2025_06_25_200002_create_appointment_series_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2025_06_25_200003_create_customer_preferences_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2025_06_25_200004_create_customer_interactions_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2025_06_26_001500_create_model_has_permissions_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2025_06_26_010000_add_performance_indexes_retell_control_center',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2025_06_26_122221_add_source_to_appointments_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2025_06_26_172057_populate_missing_phone_numbers',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2025_06_26_173406_add_performance_indexes_to_calls_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2025_06_26_225928_create_security_logs_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2025_06_26_add_company_id_to_tenants_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2025_06_26_create_ml_job_progress_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2025_06_27_add_enhanced_two_factor_fields_to_users_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2025_06_27_create_ml_job_progress_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2025_12_06_120000_enhance_calcom_event_types_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2025_12_06_120002_add_default_event_type_to_companies',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2025_12_06_120003_enhance_staff_table_for_calcom',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2025_12_06_140000_update_calcom_event_types_branch_requirement',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'_*_create_calendar_event_types_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2025_06_27_123621_create_whatsapp_message_logs_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2025_06_27_create_documentation_tables',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2025_06_27_add_tags_to_customers_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2025_06_27_120000_encrypt_tenant_api_keys',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2025_06_27_121000_encrypt_all_sensitive_fields',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2025_06_27_140000_add_critical_performance_indexes',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2025_06_27_170000_create_subscriptions_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2025_06_27_add_critical_performance_indexes',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2025_06_28_064349_add_missing_performance_indexes_2025_06_28',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2025_06_28_065833_enforce_api_key_encryption',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2025_06_28_add_recording_url_to_calls_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2025_06_28_create_data_flow_logs_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2025_06_28_083014_create_command_templates_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2025_06_28_083020_create_command_workflows_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2025_06_28_083030_create_workflow_executions_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2025_06_28_083034_create_command_executions_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (330,'2025_06_28_083151_create_workflow_commands_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2025_06_28_083210_create_command_favorites_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2025_06_28_083227_create_workflow_favorites_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2025_06_28_110209_create_circuit_breaker_metrics_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2025_06_28_112114_add_missing_columns_to_webhook_events_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2025_06_28_090000_add_websocket_fields_to_executions',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2025_06_28_143525_create_sms_message_logs_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2025_06_28_172500_create_gdpr_requests_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2025_06_28_create_payment_failures_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2025_06_28_180000_add_notification_provider_to_companies',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2025_06_29_add_retell_missing_fields_to_calls_table_safe',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2025_06_29_transform_retell_agent_configurations',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2025_06_29_fix_calls_branch_id_to_uuid',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2025_06_30_081627_create_billing_periods_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2025_06_30_080000_create_invoices_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2025_06_30_080001_create_invoice_items_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2025_06_30_130000_create_pricing_models_tables',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2025_06_30_131000_create_promo_code_uses_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2025_06_30_110000_create_billing_alerts_tables',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2025_07_01_fix_legacy_branch_id',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2025_06_30_090000_create_dunning_tables',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2025_06_30_100000_create_company_pricings_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2025_06_30_add_notification_email_to_branches',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2025_07_01_fix_branch_id_columns_to_uuid',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2025_07_02_184932_add_retell_data_fields_to_calls_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2025_07_03_120221_create_portal_users_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (357,'2025_07_03_120335_create_call_portal_data_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (358,'2025_07_03_120356_create_portal_feedback_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (359,'2025_07_03_131000_create_call_notes_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (360,'2025_07_03_131100_create_call_assignments_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (362,'2025_07_03_130326_create_prepaid_billing_tables',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (363,'2025_07_03_140000_create_portal_password_resets_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (364,'2025_07_04_111915_add_customer_data_backup_fields_to_calls_table',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (365,'2025_07_04_150000_add_language_settings_to_companies',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (366,'2025_07_04_150001_add_language_tracking_to_calls',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (367,'2025_07_04_150002_create_notification_templates_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (368,'2025_07_04_150003_clean_up_customer_language_fields',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (369,'2025_07_04_160000_add_language_preferences_to_users',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (370,'2025_07_04_175914_create_user_preferences_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (371,'2025_01_07_create_feedback_tables',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (372,'2025_01_07_enhance_notifications_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (373,'2025_07_05_create_audit_logs_table',999);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (374,'2025_07_05_create_portal_permissions_table',999);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (375,'2025_07_04_224217_add_portal_performance_indexes',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (376,'2025_07_05_111717_extend_prepaid_balances_for_bonus_and_auto_topup',1000);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (377,'2025_07_05_111813_extend_billing_rates_for_packages_and_base_fee',1000);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (378,'2025_07_05_111851_create_billing_bonus_rules_table',1000);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (379,'2025_07_05_111930_create_billing_spending_limits_table',1001);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (380,'2025_07_05_133452_create_company_goals_table',1002);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (381,'2025_07_05_133513_create_goal_metrics_table',1002);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (382,'2025_07_05_133536_create_goal_funnel_steps_table',1002);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (383,'2025_07_05_133557_create_goal_achievements_table',1003);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (384,'2025_07_05_220033_add_consent_and_forwarding_to_calls_table',1004);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (385,'2025_07_05_make_end_date_nullable_in_company_goals',1005);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (386,'2025_07_06_115925_add_call_notification_preferences_to_companies_table',1006);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (387,'2025_07_06_120949_add_call_notification_preferences_to_companies_and_branches_table',1007);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (388,'2025_07_07_create_portal_sessions_table',1008);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (389,'2025_07_07_add_metadata_to_balance_topups_table',1009);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (390,'2025_07_07_make_initiated_by_nullable_in_balance_topups',1010);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (391,'2025_07_07_113436_add_invoice_fields_to_balance_topups_table',1011);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (392,'2025_07_07_180724_add_refund_tracking_to_call_charges_table',1012);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (393,'2025_07_07_231617_create_call_activities_table',1013);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (394,'2025_01_08_create_guest_access_requests_table',1014);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (395,'2025_07_08_141954_add_company_tracking_fields_to_customers_table',1015);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (396,'2025_07_08_142029_create_customer_relationships_table',1015);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (397,'2025_07_08_create_customer_journey_status_system',1016);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (398,'2025_01_08_add_information_gathering_journey_stages',1017);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (399,'2025_07_08_202203_fix_appointments_foreign_key_types',1018);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (400,'2025_07_09_recreate_calcom_bookings_table',1019);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (401,'2025_07_09_085211_add_unique_constraint_to_staff_table',1020);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (402,'2025_07_10_181117_create_help_article_views_table',1021);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (403,'2025_07_10_181136_create_help_search_queries_table',1021);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (404,'2025_07_10_181154_create_help_article_feedback_table',1021);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (405,'2025_07_10_183416_create_error_catalog_tables',1022);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (406,'2025_01_10_create_prompt_templates_table',1023);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (407,'2025_01_10_create_industry_templates_table',1024);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (408,'2025_01_10_create_onboarding_states_table',1024);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (409,'2025_01_10_create_search_history_table',1025);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (410,'2025_01_10_create_search_indices_table',1025);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (411,'2025_01_10_fix_search_history_selected_id_column',1026);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (412,'2025_07_10_create_customer_notes_table',1027);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (413,'2025_07_10_create_email_logs_table',1027);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (414,'2025_07_11_add_columns_to_staff_services_table',1028);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (415,'2025_01_06_create_notifications_table',1029);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (416,'2025_07_09_add_information_gathering_journey_stages',1030);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (417,'2025_07_14_fix_branches_customer_id_nullable',1031);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (418,'2025_07_14_fix_branches_schema_comprehensive',1032);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (419,'2025_01_16_add_is_active_to_staff_table',1033);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (420,'2025_08_25_add_event_type_id_to_calcom_bookings',1034);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (421,'2025_08_14_000001_secure_api_keys_migration',1035);
