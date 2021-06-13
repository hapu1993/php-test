-- MySQL dump 10.13  Distrib 5.7.26, for Win64 (x86_64)
--
-- Host: localhost    Database: php_test
-- ------------------------------------------------------
-- Server version	5.7.26-log

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

--
-- Table structure for table `admissions`
--

DROP TABLE IF EXISTS `admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admission_id` varchar(255) DEFAULT NULL,
  `patient_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `bed` varchar(32) DEFAULT NULL,
  `room` varchar(32) DEFAULT NULL,
  `physician` varchar(150) DEFAULT NULL,
  `comment` text,
  `discharged` tinyint(1) DEFAULT NULL,
  `discharge_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_admission_patient_id` (`patient_id`),
  KEY `FK_admission_facility_id` (`facility_id`),
  KEY `FK_admission_ward_id` (`ward_id`),
  KEY `IX_admission_date` (`date`),
  KEY `IX_admission_discharge_date` (`discharge_date`),
  KEY `IX_id_date` (`id`,`date`),
  KEY `IX_patient_id_date` (`patient_id`,`date`),
  KEY `IX_created_time` (`created_time`),
  KEY `IX_modified_time` (`modified_time`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`),
  CONSTRAINT `FK_admission_facility_id` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `FK_admission_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`),
  CONSTRAINT `FK_admission_ward_id` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`),
  CONSTRAINT `mcap_encounters_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `mcap_encounters_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `mcap_encounters_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `mcap_encounters_ibfk_5` FOREIGN KEY (`modified_by`) REFERENCES `system_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admissions`
--

LOCK TABLES `admissions` WRITE;
/*!40000 ALTER TABLE `admissions` DISABLE KEYS */;
INSERT INTO `admissions` VALUES (6,NULL,9,'2021-01-01',1,1,'1','R2','Ajith Kumara','Chest Pain',1,'2021-02-03',182,'2021-05-03 16:56:24',182,'2021-05-03 16:56:42'),(7,NULL,9,'2021-05-01',1,2,'1','R1','Ajith Kumara','Chest Pain',NULL,NULL,182,'2021-05-03 16:57:45',NULL,NULL);
/*!40000 ALTER TABLE `admissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_elements`
--

DROP TABLE IF EXISTS `cms_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(200) DEFAULT NULL,
  `name` varchar(250) NOT NULL,
  `comment` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `public` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_elements`
--

LOCK TABLES `cms_elements` WRITE;
/*!40000 ALTER TABLE `cms_elements` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_elements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_main_menu_items`
--

DROP TABLE IF EXISTS `cms_main_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_main_menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `cms_main_menu_items_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_main_menu_items`
--

LOCK TABLES `cms_main_menu_items` WRITE;
/*!40000 ALTER TABLE `cms_main_menu_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_main_menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_page_elements`
--

DROP TABLE IF EXISTS `cms_page_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_page_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(255) DEFAULT NULL,
  `page_id` int(11) NOT NULL,
  `element_id` int(11) NOT NULL,
  `column` varchar(255) DEFAULT NULL,
  `hint` text,
  `required` tinyint(1) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '99',
  `public` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_page_elements`
--

LOCK TABLES `cms_page_elements` WRITE;
/*!40000 ALTER TABLE `cms_page_elements` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_page_elements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_page_sub_element_values`
--

DROP TABLE IF EXISTS `cms_page_sub_element_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_page_sub_element_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `page_element_id` int(11) NOT NULL,
  `sub_element_id` int(11) NOT NULL,
  `value` text,
  `lib_value` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_page_sub_element_values`
--

LOCK TABLES `cms_page_sub_element_values` WRITE;
/*!40000 ALTER TABLE `cms_page_sub_element_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_page_sub_element_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_pages`
--

DROP TABLE IF EXISTS `cms_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT NULL,
  `template` varchar(200) DEFAULT NULL,
  `name` varchar(250) NOT NULL,
  `title` varchar(250) DEFAULT NULL,
  `seo_title` varchar(250) DEFAULT NULL,
  `image` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `linked` tinyint(1) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_pages`
--

LOCK TABLES `cms_pages` WRITE;
/*!40000 ALTER TABLE `cms_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_sub_elements`
--

DROP TABLE IF EXISTS `cms_sub_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_sub_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `element_id` int(11) NOT NULL,
  `type` varchar(200) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `required` tinyint(1) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_sub_elements`
--

LOCK TABLES `cms_sub_elements` WRITE;
/*!40000 ALTER TABLE `cms_sub_elements` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_sub_elements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_sub_submenu_items`
--

DROP TABLE IF EXISTS `cms_sub_submenu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_sub_submenu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `submenu_item_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `sort_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submenu_item_id` (`submenu_item_id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `cms_sub_submenu_items_ibfk_1` FOREIGN KEY (`submenu_item_id`) REFERENCES `cms_submenu_items` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `cms_sub_submenu_items_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_sub_submenu_items`
--

LOCK TABLES `cms_sub_submenu_items` WRITE;
/*!40000 ALTER TABLE `cms_sub_submenu_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_sub_submenu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cms_submenu_items`
--

DROP TABLE IF EXISTS `cms_submenu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cms_submenu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_item_id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `sort_order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_item_id` (`menu_item_id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `cms_submenu_items_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `cms_main_menu_items` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `cms_submenu_items_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cms_submenu_items`
--

LOCK TABLES `cms_submenu_items` WRITE;
/*!40000 ALTER TABLE `cms_submenu_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cms_submenu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hl7_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hospital_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cquin_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `active` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_facilities_hospital_id` (`hospital_id`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`),
  CONSTRAINT `FK_facilities_hospital_id` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`),
  CONSTRAINT `facilities_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`),
  CONSTRAINT `facilities_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`),
  CONSTRAINT `facilities_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `facilities_ibfk_4` FOREIGN KEY (`modified_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `facilities_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `facilities_ibfk_6` FOREIGN KEY (`modified_by`) REFERENCES `system_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
INSERT INTO `facilities` VALUES (1,'Fa001',1,'Cardiology Unit',NULL,NULL,1,182,'2021-05-03 06:03:39',NULL,NULL),(2,'Fa002',1,'ICU',NULL,NULL,1,182,'2021-05-03 06:07:29',NULL,NULL);
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hospitals`
--

DROP TABLE IF EXISTS `hospitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hl7_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `active` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`),
  CONSTRAINT `hospitals_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `hospitals_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `system_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospitals`
--

LOCK TABLES `hospitals` WRITE;
/*!40000 ALTER TABLE `hospitals` DISABLE KEYS */;
INSERT INTO `hospitals` VALUES (1,'Hos01','Nawaloka Hospitals PLC','General Line : +94 (0) 115577111\r\nChanneling Hotline : +94 (0) 115777888\r\nFax : +94 (0) 11 2430393\r\nEmail : nawaloka@slt.lk',1,182,'2021-05-03 05:59:54',NULL,NULL);
/*!40000 ALTER TABLE `hospitals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location_security_groups_permissions`
--

DROP TABLE IF EXISTS `location_security_groups_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_security_groups_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `location_id_group_id` (`location_id`,`group_id`),
  KEY `UQ_location_group` (`location_id`,`group_id`),
  KEY `UQ_group_location` (`group_id`,`location_id`),
  CONSTRAINT `FK_location_security_groups_permissions_group_id` FOREIGN KEY (`group_id`) REFERENCES `ward_security_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_location_security_groups_permissions_ward_id` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location_security_groups_permissions`
--

LOCK TABLES `location_security_groups_permissions` WRITE;
/*!40000 ALTER TABLE `location_security_groups_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `location_security_groups_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hl7_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `facility_id` (`facility_id`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`),
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `locations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `locations_ibfk_3` FOREIGN KEY (`modified_by`) REFERENCES `system_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hl7_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `surname` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `gender` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `postcode` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `normalised_postcode` varchar(8) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `address` text COLLATE utf8_unicode_ci,
  `deceased_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_surname_name` (`surname`,`name`),
  KEY `created_by` (`created_by`),
  KEY `modified_by` (`modified_by`),
  CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `system_users` (`id`),
  CONSTRAINT `patients_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `system_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (9,'P001','Mr','Kamal','Fonseka','1981-05-21','Male','10200','10200','No:512,\r\nSaman Mawatha\r\nHomagama',NULL,182,'2021-05-03 15:48:27',NULL,NULL);
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_apps`
--

DROP TABLE IF EXISTS `system_apps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `tooltip` varchar(255) DEFAULT NULL,
  `front_end_app` tinyint(1) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_apps`
--

LOCK TABLES `system_apps` WRITE;
/*!40000 ALTER TABLE `system_apps` DISABLE KEYS */;
INSERT INTO `system_apps` VALUES (1,'Admin Panel','','cogs',NULL,NULL,1),(13,'Application','app_application/','heartbeat',NULL,0,1);
/*!40000 ALTER TABLE `system_apps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_config`
--

DROP TABLE IF EXISTS `system_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `constant` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_config`
--

LOCK TABLES `system_config` WRITE;
/*!40000 ALTER TABLE `system_config` DISABLE KEYS */;
INSERT INTO `system_config` VALUES (1,'landpage','',0),(12,'pagination','20',0),(13,'dateformat','d M Y',0),(14,'width','960',0),(15,'height','400',0),(16,'font','Verdana',0),(17,'fsize','12',0),(18,'login_cookie_expiry','86400',0),(19,'MAX_FILE_SIZE','10000000',0),(20,'crypt_algorithm','MCRYPT_BLOWFISH',1),(21,'crypt_mode','MCRYPT_MODE_CBC',1),(22,'crypt_random_source','MCRYPT_DEV_URANDOM',1),(23,'popup_size','Dynamic',0),(25,'timezone','Europe/London',0),(26,'shortcuts_menu','0',0),(28,'show_side_search','1',0),(29,'disable_rc_menu','1',0);
/*!40000 ALTER TABLE `system_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_intrusions`
--

DROP TABLE IF EXISTS `system_intrusions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_intrusions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` text NOT NULL,
  `domain` varchar(255) NOT NULL,
  `page` text NOT NULL,
  `tags` varchar(128) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `impact` int(11) unsigned NOT NULL,
  `origin` varchar(15) NOT NULL,
  `created` datetime NOT NULL,
  `filter_id` int(11) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_intrusions`
--

LOCK TABLES `system_intrusions` WRITE;
/*!40000 ALTER TABLE `system_intrusions` DISABLE KEYS */;
INSERT INTO `system_intrusions` VALUES (1,0,'GET','decrypt','php_test','/utilities.php?x=ODQ5ZTQwZDY4ZjBlNThiYjhjMGIzNmMzYTg2MmFhYzDtFGp8QX9gGZVK1dztgUpIL2hYOXgzNU9vdEFiUEdYZDA1bEsvZz09','mangle','::1',999,'::1','2021-05-03 14:49:59',0,'Detects mangling of encrypted string'),(2,0,'GET','decrypt','php_test','/utilities.php?x=ODQ5ZTQwZDY4ZjBlNThiYjhjMGIzNmMzYTg2MmFhYzDtFGp8QX9gGZVK1dztgUpIL2hYOXgzNU9vdEFiUEdYZDA1bEsvZz09','mangle','::1',999,'::1','2021-05-03 14:49:59',0,'Detects mangling of encrypted string'),(3,0,'GET','http://php_test:9090//app_application/patient_details.php?x=MmM2NTlmYmYwNTdhMTFhNTZlZDBjNTNhNjc0YmQ5N2ObLnR31eWe4rZvV97oUvgocElwOFdJSHJqS0drVnEwaFBqaHRzZz09','php_test','/utilities.php?x=MTJmMTU1NzFkNTE3MGZiNjJjY2IwNWYyMWYxOWQ2MzOO4kSRvaGiPrV_dPbk1C1BQzlmK0poTUJTVGNPODJFckFvaXJQNUFZZVVkV0g1dERYbDNQV09zWkU2MVBab3JqU2R1V0xJOXJ5V3hYblJtakErWCtZdXRld3pMN0ZQTWwyQVRQQmxCNEZUdjhIZUdmTEY3elVSWnFaaTRueWMzd1U2VFBhSCtITWhUTStRbUt1TW9rWnBNSGZyTWRPcGlvVUtFTnd1Vy9PY00xQnUxQUtJVnFEQWpQQ2hVd2h0WGtNbkxUbUoyeExmcEFFd2sreUV0NWxRMEJSWUw3aFJnMHhXNStjWFpLdnBYcXFRZWxXZGxXdWlRRjVpc3kzb2FGbDk5S2dKaFM3eWJXS2d1NjdwZHk3dnNhT0RHbmEwVG9jbmxiT2c9PQ','mangle','::1',999,'::1','2021-05-03 14:49:59',0,'Detects mangling of encrypted string'),(4,0,'GET','decrypt','php_test','/utilities.php?x=MTJmMTU1NzFkNTE3MGZiNjJjY2IwNWYyMWYxOWQ2MzOO4kSRvaGiPrV_dPbk1C1BQzlmK0poTUJTVGNPODJFckFvaXJQNUFZZVVkV0g1dERYbDNQV09zWkU2MVBab3JqU2R1V0xJOXJ5V3hYblJtakErWCtZdXRld3pMN0ZQTWwyQVRQQmxCNEZUdjhIZUdmTEY3elVSWnFaaTRueWMzd1U2VFBhSCtITWhUTStRbUt1TW9rWnBNSGZyTWRPcGlvVUtFTnd1Vy9PY00xQnUxQUtJVnFEQWpQQ2hVd2h0WGtNbkxUbUoyeExmcEFFd2sreUV0NWxRMEJSWUw3aFJnMHhXNStjWFpLdnBYcXFRZWxXZGxXdWlRRjVpc3kzb2FGbDk5S2dKaFM3eWJXS2d1NjdwZHk3dnNhT0RHbmEwVG9jbmxiT2c9PQ','mangle','::1',999,'::1','2021-05-03 14:50:07',0,'Detects mangling of encrypted string'),(5,0,'GET','decrypt','php_test','/utilities.php?x=MTJmMTU1NzFkNTE3MGZiNjJjY2IwNWYyMWYxOWQ2MzOO4kSRvaGiPrV_dPbk1C1BQzlmK0poTUJTVGNPODJFckFvaXJQNUFZZVVkV0g1dERYbDNQV09zWkU2MVBab3JqU2R1V0xJOXJ5V3hYblJtakErWCtZdXRld3pMN0ZQTWwyQVRQQmxCNEZUdjhIZUdmTEY3elVSWnFaaTRueWMzd1U2VFBhSCtITWhUTStRbUt1TW9rWnBNSGZyTWRPcGlvVUtFTnd1Vy9PY00xQnUxQUtJVnFEQWpQQ2hVd2h0WGtNbkxUbUoyeExmcEFFd2sreUV0NWxRMEJSWUw3aFJnMHhXNStjWFpLdnBYcXFRZWxXZGxXdWlRRjVpc3kzb2FGbDk5S2dKaFM3eWJXS2d1NjdwZHk3dnNhT0RHbmEwVG9jbmxiT2c9PQ','mangle','::1',999,'::1','2021-05-03 14:50:07',0,'Detects mangling of encrypted string'),(6,0,'GET','decrypt','php_test','/utilities.php?x=MjEyYzRmOTY4ZmZmNjdmZmY5NGU5YjMzNDM3OTlmMDfzPrOsSJ4cWH1xldB4WE9aZFZKVDR0dFVHd1Q5QVM3bGtzaGYxZz09','mangle','::1',999,'::1','2021-05-03 15:12:39',0,'Detects mangling of encrypted string'),(7,0,'GET','decrypt','php_test','/utilities.php?x=MjEyYzRmOTY4ZmZmNjdmZmY5NGU5YjMzNDM3OTlmMDfzPrOsSJ4cWH1xldB4WE9aZFZKVDR0dFVHd1Q5QVM3bGtzaGYxZz09','mangle','::1',999,'::1','2021-05-03 15:12:39',0,'Detects mangling of encrypted string'),(8,0,'GET','http://php_test:9090/app_application/patient_details.php?x=Mzk3ZDU2MWZkNzQ2MDk0NDY3OGMzYTBlMWQwMzA3YTUJ7wXi73uLIMRgyQwMvZD4NlFtU2djVlBINm5BaGNRT1JkN1Zvdz09','php_test','/utilities.php?x=Y2NlYTY0OTgxYmVkYmE4ZjIzZjhmZGVjZWZhMWUzZWbdhsgKla4LeVrAc8o-6rkZckVYUGh0TkJRL21Qd0pUMWJTL0ZDeGJtbENCRjRPUXNkZGMrREtWRTc2RjgzVkhRdFdFMWd0eEQ2SjNtdkVjbEo3R2ZwZXRhMXpBeVNMd045YTNsMFA0UG5ZV3NYVkUwZXZOeUQ1Zkl0dGxkanVyU0JGOWQwREF5UUNFTWNzMmVMekxkcFBnSTFsVUlOQ25JdVVGUzd4eE9HdnZTUmVKZDU0L2ZYaGZBVEgxTDh5OGM1RURtS1hlOXJNRmhIWkg1SFFtRm5nY3NJdm1mNWtYTHFwSmV3OFFLek9ZeDI1a21jVS9JNjlpQnpCUE5hRWxyR1R1OUNwZ1ZwRlRBR2pDQVA5ajlQNWZrUkVGNjZ3M3FsZ0xpMVE9PQ','mangle','::1',999,'::1','2021-05-03 15:12:40',0,'Detects mangling of encrypted string'),(9,0,'GET','decrypt','php_test','/utilities.php?x=Y2NlYTY0OTgxYmVkYmE4ZjIzZjhmZGVjZWZhMWUzZWbdhsgKla4LeVrAc8o-6rkZckVYUGh0TkJRL21Qd0pUMWJTL0ZDeGJtbENCRjRPUXNkZGMrREtWRTc2RjgzVkhRdFdFMWd0eEQ2SjNtdkVjbEo3R2ZwZXRhMXpBeVNMd045YTNsMFA0UG5ZV3NYVkUwZXZOeUQ1Zkl0dGxkanVyU0JGOWQwREF5UUNFTWNzMmVMekxkcFBnSTFsVUlOQ25JdVVGUzd4eE9HdnZTUmVKZDU0L2ZYaGZBVEgxTDh5OGM1RURtS1hlOXJNRmhIWkg1SFFtRm5nY3NJdm1mNWtYTHFwSmV3OFFLek9ZeDI1a21jVS9JNjlpQnpCUE5hRWxyR1R1OUNwZ1ZwRlRBR2pDQVA5ajlQNWZrUkVGNjZ3M3FsZ0xpMVE9PQ','mangle','::1',999,'::1','2021-05-03 15:12:47',0,'Detects mangling of encrypted string'),(10,0,'GET','decrypt','php_test','/utilities.php?x=Y2NlYTY0OTgxYmVkYmE4ZjIzZjhmZGVjZWZhMWUzZWbdhsgKla4LeVrAc8o-6rkZckVYUGh0TkJRL21Qd0pUMWJTL0ZDeGJtbENCRjRPUXNkZGMrREtWRTc2RjgzVkhRdFdFMWd0eEQ2SjNtdkVjbEo3R2ZwZXRhMXpBeVNMd045YTNsMFA0UG5ZV3NYVkUwZXZOeUQ1Zkl0dGxkanVyU0JGOWQwREF5UUNFTWNzMmVMekxkcFBnSTFsVUlOQ25JdVVGUzd4eE9HdnZTUmVKZDU0L2ZYaGZBVEgxTDh5OGM1RURtS1hlOXJNRmhIWkg1SFFtRm5nY3NJdm1mNWtYTHFwSmV3OFFLek9ZeDI1a21jVS9JNjlpQnpCUE5hRWxyR1R1OUNwZ1ZwRlRBR2pDQVA5ajlQNWZrUkVGNjZ3M3FsZ0xpMVE9PQ','mangle','::1',999,'::1','2021-05-03 15:12:47',0,'Detects mangling of encrypted string'),(11,0,'GET','decrypt','php_test','/utilities.php','mangle','::1',999,'::1','2021-05-03 15:15:05',0,'Detects mangling of encrypted string'),(12,0,'GET','decrypt','php_test','/utilities.php','mangle','::1',999,'::1','2021-05-03 15:15:05',0,'Detects mangling of encrypted string'),(13,0,'GET','decrypt','php_test','/utilities.php?x=OTM3MjMwNWE2NDRiZDRmN2IwY2UzNThhM2IyNTM5NWQRb1wy-h2eJfH1IJIR54VkR1NNaEE4ZUE5djR0Umh4bTd4TVRlUT09','mangle','::1',999,'::1','2021-05-03 15:20:38',0,'Detects mangling of encrypted string'),(14,0,'GET','decrypt','php_test','/utilities.php?x=OTM3MjMwNWE2NDRiZDRmN2IwY2UzNThhM2IyNTM5NWQRb1wy-h2eJfH1IJIR54VkR1NNaEE4ZUE5djR0Umh4bTd4TVRlUT09','mangle','::1',999,'::1','2021-05-03 15:20:38',0,'Detects mangling of encrypted string'),(15,0,'GET','decrypt','php_test','/utilities.php?x=YzllNDM5ZGI4ZDAxZGViNmQ5MjEwMzM1MTdiNWZmNjEty-reJ9KjI9P_fJETDjMiNEwvSUFUZ28zUllvVXl3NGlQVE1SRzIxb0pPMk1jcVRwbmNyZmNMUnpITVVzNEVCOEk2Q3ZqRVErVjgvWm9kY1k0RVBoaUN2MGhFT3FGejBSU1NVYWhVcENOUUZqTDBYdEI4RlJ3L0phaEd2Qkp6VklFci9SWFNmanFTb0NPazF4S3UxV3RPNCsyUnRmUzd6UHpKdGkwUStFaVlvbXU4Y2kvTXliUEhScmZzZWVROXlyRTNSYWROQlRHa1RHakhOanZIaHlWWEdveEx1MlpqNUlVeEs5dFRBdkg2MjNIUmpIVlFWY3A5QXVlQT0','mangle','::1',999,'::1','2021-05-03 15:20:49',0,'Detects mangling of encrypted string'),(16,0,'GET','decrypt','php_test','/utilities.php?x=YzllNDM5ZGI4ZDAxZGViNmQ5MjEwMzM1MTdiNWZmNjEty-reJ9KjI9P_fJETDjMiNEwvSUFUZ28zUllvVXl3NGlQVE1SRzIxb0pPMk1jcVRwbmNyZmNMUnpITVVzNEVCOEk2Q3ZqRVErVjgvWm9kY1k0RVBoaUN2MGhFT3FGejBSU1NVYWhVcENOUUZqTDBYdEI4RlJ3L0phaEd2Qkp6VklFci9SWFNmanFTb0NPazF4S3UxV3RPNCsyUnRmUzd6UHpKdGkwUStFaVlvbXU4Y2kvTXliUEhScmZzZWVROXlyRTNSYWROQlRHa1RHakhOanZIaHlWWEdveEx1MlpqNUlVeEs5dFRBdkg2MjNIUmpIVlFWY3A5QXVlQT0','mangle','::1',999,'::1','2021-05-03 15:20:49',0,'Detects mangling of encrypted string'),(17,0,'GET','decrypt','php_test','/utilities.php?x=MzA3MDQyN2ZlMGQ1Yzk3YmNiMjcwZTM1Yjc4NjliZjNfrODA_cb_-Xk4NmQo7PC6aXd4dmtKTUk1bEdkRkJvTjZoaHQxbFhjeGtOYjJOeDhjd1VxOVcyQWw1YzVIcEhlM3NmSnBKRitKdU0vQ0JoamR6QVlJcWUreGh2Zzl3MHMybzFFOHJpNUgvUGRzaGtiejJvS0hLSFA1MVJnVU43RGg3V1cyckpvKytSWEUwUTF2czVBanRmek04T0dqVlB1TzUzaDlaWWFQMnV4VnI1UUhnN01YdHdDeEs0bEE3YlNtK1BuM3U2c01wN0MzbVJKOVM3eWJDenBIU2JjM0xFL0djT0xlb1NtUEVGNUJ4a3RDSVRYNEh5bEtUdz0','mangle','::1',999,'::1','2021-05-03 15:21:07',0,'Detects mangling of encrypted string'),(18,0,'GET','decrypt','php_test','/utilities.php?x=MzA3MDQyN2ZlMGQ1Yzk3YmNiMjcwZTM1Yjc4NjliZjNfrODA_cb_-Xk4NmQo7PC6aXd4dmtKTUk1bEdkRkJvTjZoaHQxbFhjeGtOYjJOeDhjd1VxOVcyQWw1YzVIcEhlM3NmSnBKRitKdU0vQ0JoamR6QVlJcWUreGh2Zzl3MHMybzFFOHJpNUgvUGRzaGtiejJvS0hLSFA1MVJnVU43RGg3V1cyckpvKytSWEUwUTF2czVBanRmek04T0dqVlB1TzUzaDlaWWFQMnV4VnI1UUhnN01YdHdDeEs0bEE3YlNtK1BuM3U2c01wN0MzbVJKOVM3eWJDenBIU2JjM0xFL0djT0xlb1NtUEVGNUJ4a3RDSVRYNEh5bEtUdz0','mangle','::1',999,'::1','2021-05-03 15:21:07',0,'Detects mangling of encrypted string'),(19,0,'GET','decrypt','php_test','/utilities.php?x=MmZlZjZhOWI5NWMwMTQxYzdlYzFmMGZkODY1ZTUyZDDTvM4UKMQJ8KGr2EHeQP2ASzI2cWtpN2FjVy8rMG9iTDBXSWxINEUxSmo4dHN0T0RWa1JMTEE1SHlndEovVHJIWXRVamxsdG5IdGQ5Y05ldlNLRkd0dmN4QldmWkZsNStFY1RSUkJWVkhUMmxWN2dWdk04QlBwbklnL2p4NzF1Tk5LU3pBSmpwT3FNSGpGTDFtejRxZ1NzN1dlTzh5WFlycGJwVWRMZlhpZUhwRmt3Y2dVcjdLZVl6MDhmN290RHVtcnZRWlRCYXp0aUpHdWQ5','mangle','::1',999,'::1','2021-05-03 15:21:14',0,'Detects mangling of encrypted string'),(20,0,'GET','decrypt','php_test','/utilities.php?x=MmZlZjZhOWI5NWMwMTQxYzdlYzFmMGZkODY1ZTUyZDDTvM4UKMQJ8KGr2EHeQP2ASzI2cWtpN2FjVy8rMG9iTDBXSWxINEUxSmo4dHN0T0RWa1JMTEE1SHlndEovVHJIWXRVamxsdG5IdGQ5Y05ldlNLRkd0dmN4QldmWkZsNStFY1RSUkJWVkhUMmxWN2dWdk04QlBwbklnL2p4NzF1Tk5LU3pBSmpwT3FNSGpGTDFtejRxZ1NzN1dlTzh5WFlycGJwVWRMZlhpZUhwRmt3Y2dVcjdLZVl6MDhmN290RHVtcnZRWlRCYXp0aUpHdWQ5','mangle','::1',999,'::1','2021-05-03 15:21:14',0,'Detects mangling of encrypted string'),(21,0,'GET','decrypt','php_test','/utilities.php?x=MjE5YzE4NDIwOTNjMWY2ZjBjMjRjMDRmZTcyYjJlZmQDseVb-skmKVm0PZ_IhaDZeVNpS3N2QXMvWFJkOHVtMzZNSnUwMHlURnJ1QUdCcG1IbFJkbjJkaDFVeVQ2WGEweHpmYVowU1Nid3h1Kzh5TTlQUEIrZmpoNjJPa0toMjFGS0hZQ1JBakt3OTlHYkhHb2w3TmNQck1XUG5tTzlVMzR3OWFCYTNZWHovc0lmMHB5d3VNTWxtQWtWRUc5UytJU1B0QWJ5SlE4L3RUSEdibWRQQlhhVmM3VlJrPQ','mangle','::1',999,'::1','2021-05-03 15:21:23',0,'Detects mangling of encrypted string'),(22,0,'GET','decrypt','php_test','/utilities.php?x=MjE5YzE4NDIwOTNjMWY2ZjBjMjRjMDRmZTcyYjJlZmQDseVb-skmKVm0PZ_IhaDZeVNpS3N2QXMvWFJkOHVtMzZNSnUwMHlURnJ1QUdCcG1IbFJkbjJkaDFVeVQ2WGEweHpmYVowU1Nid3h1Kzh5TTlQUEIrZmpoNjJPa0toMjFGS0hZQ1JBakt3OTlHYkhHb2w3TmNQck1XUG5tTzlVMzR3OWFCYTNZWHovc0lmMHB5d3VNTWxtQWtWRUc5UytJU1B0QWJ5SlE4L3RUSEdibWRQQlhhVmM3VlJrPQ','mangle','::1',999,'::1','2021-05-03 15:21:23',0,'Detects mangling of encrypted string'),(23,0,'GET','encrypt','php_test','/utilities.php?x=MzAyYTUwYzNmN2Q0MTFkMzc5MGM3MjFiYTQyZDdhNzRZRREAB1ZAJFPQGxLLAxwDVlBsVmdTVU1tbDU0Y1lLaVhwYzNCdz09','mangle','::1',999,'::1','2021-05-03 15:21:25',0,'Detects mangling of encrypted string'),(24,0,'GET','encrypt','php_test','/utilities.php?x=MzAyYTUwYzNmN2Q0MTFkMzc5MGM3MjFiYTQyZDdhNzRZRREAB1ZAJFPQGxLLAxwDVlBsVmdTVU1tbDU0Y1lLaVhwYzNCdz09','mangle','::1',999,'::1','2021-05-03 15:21:25',0,'Detects mangling of encrypted string'),(25,0,'GET','encrypt','php_test','/utilities.php?x=MmZlODc2OWI5ZTg3ZGMzZDdkNzQ1YzFlZWFlM2U2MjMMn3ItTDjhfIoGziTu1K8_MytIcFhGcy9iN1k5S1ZaaUZYTzlzQT09','mangle','::1',999,'::1','2021-05-03 15:21:28',0,'Detects mangling of encrypted string'),(26,0,'GET','encrypt','php_test','/utilities.php?x=MmZlODc2OWI5ZTg3ZGMzZDdkNzQ1YzFlZWFlM2U2MjMMn3ItTDjhfIoGziTu1K8_MytIcFhGcy9iN1k5S1ZaaUZYTzlzQT09','mangle','::1',999,'::1','2021-05-03 15:21:28',0,'Detects mangling of encrypted string'),(27,0,'GET','encrypt','php_test','/utilities.php?x=MDY3NzU2YzMxZWQ3NzBmMDBmN2E0MTg4MWE5ZTE3MTFrOmVWk-oPduGuUjG4Ot3vY2g3d0puR1phb09vMzRJV1d3cDcyZz09','mangle','::1',999,'::1','2021-05-03 15:22:14',0,'Detects mangling of encrypted string'),(28,0,'GET','encrypt','php_test','/utilities.php?x=MDY3NzU2YzMxZWQ3NzBmMDBmN2E0MTg4MWE5ZTE3MTFrOmVWk-oPduGuUjG4Ot3vY2g3d0puR1phb09vMzRJV1d3cDcyZz09','mangle','::1',999,'::1','2021-05-03 15:22:14',0,'Detects mangling of encrypted string'),(29,0,'GET','decrypt','php_test','/utilities.php?x=ZjUzOTFmNGY4OGExYzQzNzIzMjUzZTE1MGY3MGM1YjEg8akhk_R-6tiocJiq3kvrbFlJbC9aNmVlUFNPdmVFazYrdnZJU1ptQWFKekg1WWJOR2k5MjV3c1RobG5LSU1idTRVRDdmRGJTUjd1a0F0V25ncEo5VjhKemtjc0FCMmE1YkVaOVFKN0pCemp5aGR0Qkt2d1RESUV6NGdMU2dxanNxZmR2TkVzSldva2lDdmpkTytqVDVrNVlVQXdBZ2F6SU5hczlKTHpKSFo2R3VCejYyT2Urb1RkUGhVPQ','mangle','::1',999,'::1','2021-05-03 15:22:22',0,'Detects mangling of encrypted string'),(30,0,'GET','decrypt','php_test','/utilities.php?x=ZjUzOTFmNGY4OGExYzQzNzIzMjUzZTE1MGY3MGM1YjEg8akhk_R-6tiocJiq3kvrbFlJbC9aNmVlUFNPdmVFazYrdnZJU1ptQWFKekg1WWJOR2k5MjV3c1RobG5LSU1idTRVRDdmRGJTUjd1a0F0V25ncEo5VjhKemtjc0FCMmE1YkVaOVFKN0pCemp5aGR0Qkt2d1RESUV6NGdMU2dxanNxZmR2TkVzSldva2lDdmpkTytqVDVrNVlVQXdBZ2F6SU5hczlKTHpKSFo2R3VCejYyT2Urb1RkUGhVPQ','mangle','::1',999,'::1','2021-05-03 15:22:22',0,'Detects mangling of encrypted string');
/*!40000 ALTER TABLE `system_intrusions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_ldap_group_mappings`
--

DROP TABLE IF EXISTS `system_ldap_group_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_ldap_group_mappings` (
  `ldap_group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`ldap_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_ldap_group_mappings`
--

LOCK TABLES `system_ldap_group_mappings` WRITE;
/*!40000 ALTER TABLE `system_ldap_group_mappings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_ldap_group_mappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_ldap_security_list`
--

DROP TABLE IF EXISTS `system_ldap_security_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_ldap_security_list` (
  `id` int(11) NOT NULL,
  `level_name` varchar(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_ldap_security_list`
--

LOCK TABLES `system_ldap_security_list` WRITE;
/*!40000 ALTER TABLE `system_ldap_security_list` DISABLE KEYS */;
INSERT INTO `system_ldap_security_list` VALUES (0,'None'),(1,'TLS'),(2,'SSL');
/*!40000 ALTER TABLE `system_ldap_security_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_ldap_settings`
--

DROP TABLE IF EXISTS `system_ldap_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_ldap_settings` (
  `id` int(11) NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `domain_controller` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `security_id` int(11) NOT NULL,
  `base_dn` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_system_ldap_settings_security_id` (`security_id`),
  CONSTRAINT `FK_system_ldap_settings_security_id` FOREIGN KEY (`security_id`) REFERENCES `system_ldap_security_list` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_ldap_settings`
--

LOCK TABLES `system_ldap_settings` WRITE;
/*!40000 ALTER TABLE `system_ldap_settings` DISABLE KEYS */;
INSERT INTO `system_ldap_settings` VALUES (1,0,'domain',1,0,'base','firstnamelastname',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `system_ldap_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_local_text`
--

DROP TABLE IF EXISTS `system_local_text`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_local_text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `local_group` varchar(255) NOT NULL,
  `local_key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_local_text`
--

LOCK TABLES `system_local_text` WRITE;
/*!40000 ALTER TABLE `system_local_text` DISABLE KEYS */;
INSERT INTO `system_local_text` VALUES (1,'Global','long_mrn','MRN',0,'2020-07-13 10:03:11',184,'2020-07-13 07:17:20'),(2,'Global','short_mrn','MRN!!!',0,'2020-07-13 10:03:11',184,'2020-07-13 07:15:42'),(3,'Global','long_nhs_number','NHS Number!!!',0,'2020-07-13 10:03:11',184,'2020-07-13 07:15:48'),(4,'Global','short_nhs_number','NHS No!!!',0,'2020-07-13 10:03:11',184,'2020-07-13 07:15:53'),(5,'Global','Location','Team',0,'2020-09-30 15:46:05',184,'2020-09-30 12:18:02'),(6,'Global','Ward','Ward',0,'2020-09-30 15:46:05',184,'2020-09-30 12:18:02'),(7,'Global','Facility','Facility',0,'2020-09-30 15:46:05',184,'2020-09-30 12:18:02'),(8,'Global','Hospital','Hospital',0,'2020-09-30 15:46:05',184,'2020-09-30 12:18:02');
/*!40000 ALTER TABLE `system_local_text` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_log`
--

DROP TABLE IF EXISTS `system_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `object` varchar(64) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `action` varchar(64) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `object_id` varchar(160) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `object_id` (`object_id`),
  KEY `object` (`object`)
) ENGINE=InnoDB AUTO_INCREMENT=1230 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `system_objects_tags`
--

DROP TABLE IF EXISTS `system_objects_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_objects_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `related_object_name` varchar(255) NOT NULL,
  `related_sub_object` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `object_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `system_objects_tags_ibfk_1` FOREIGN KEY (`tag_id`) REFERENCES `system_tags` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_objects_tags`
--

LOCK TABLES `system_objects_tags` WRITE;
/*!40000 ALTER TABLE `system_objects_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_objects_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_openid_login`
--

DROP TABLE IF EXISTS `system_openid_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_openid_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hybridauth_provider_uid` varchar(255) NOT NULL,
  `hybridauth_provider_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_openid_login`
--

LOCK TABLES `system_openid_login` WRITE;
/*!40000 ALTER TABLE `system_openid_login` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_openid_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_pages`
--

DROP TABLE IF EXISTS `system_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page` varchar(255) NOT NULL,
  `comment` text,
  `type` int(11) DEFAULT NULL,
  `core` tinyint(1) DEFAULT NULL,
  `front_end_app` tinyint(1) DEFAULT NULL,
  `front_end` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2579 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_pages`
--

LOCK TABLES `system_pages` WRITE;
/*!40000 ALTER TABLE `system_pages` DISABLE KEYS */;
INSERT INTO `system_pages` VALUES (1,'index.php',NULL,1,1,NULL,0),(3,'users.php',NULL,1,0,NULL,0),(4,'system_log.php',NULL,1,0,NULL,0),(5,'user_comments.php',NULL,1,0,NULL,0),(6,'wastebasket.php',NULL,1,0,NULL,0),(7,'server.php',NULL,1,0,NULL,0),(9,'utilities.php',NULL,1,0,NULL,0),(11,'license.php',NULL,1,0,NULL,0),(12,'404.php',NULL,2,1,NULL,0),(13,'logout.php',NULL,2,1,NULL,0),(18,'user_details.php',NULL,2,1,NULL,0),(21,'delete_logs.php',NULL,2,0,NULL,0),(24,'admin_panel.php',NULL,2,0,NULL,0),(25,'forgotten_password.php',NULL,2,1,NULL,0),(27,'add_user.php',NULL,3,0,NULL,0),(28,'delete_user.php',NULL,3,0,NULL,0),(29,'edit_user.php',NULL,3,0,NULL,0),(30,'add_user_comment.php',NULL,3,0,NULL,0),(31,'delete_user_comment.php',NULL,3,0,NULL,0),(32,'edit_user_comment.php',NULL,3,0,NULL,0),(34,'send_user_email.php',NULL,3,0,NULL,0),(35,'add_user_group.php',NULL,3,0,NULL,0),(36,'delete_user_group.php',NULL,3,0,NULL,0),(37,'edit_user_group.php',NULL,3,0,NULL,0),(38,'delete_user_session.php',NULL,3,0,NULL,0),(39,'delete_wastebasket.php',NULL,3,0,NULL,0),(40,'empty_wastebasket.php',NULL,3,0,NULL,0),(41,'restore_wastebasket.php',NULL,3,0,NULL,0),(193,'delete_intrusion_logs.php',NULL,0,0,NULL,0),(195,'system_monitor.php',NULL,0,0,NULL,0),(245,'exception.php',NULL,0,0,NULL,0),(460,'add_bookmark.php',NULL,NULL,1,NULL,0),(461,'edit_page_comment.php',NULL,NULL,NULL,NULL,0),(462,'export_user_comments.php',NULL,NULL,NULL,NULL,0),(463,'add_system_app.php',NULL,NULL,NULL,NULL,0),(464,'delete_system_app.php',NULL,NULL,NULL,NULL,0),(465,'edit_system_app.php',NULL,NULL,NULL,NULL,0),(481,'send_single_user_email.php',NULL,NULL,NULL,NULL,0),(501,'set_email_alert.php',NULL,NULL,NULL,NULL,0),(536,'edit_preferences.php',NULL,NULL,1,NULL,0),(537,'404_popup.php',NULL,NULL,NULL,NULL,0),(2162,'index.php',NULL,NULL,NULL,NULL,1),(2163,'logout.php',NULL,NULL,NULL,NULL,1),(2166,'forgotten_password.php',NULL,NULL,NULL,NULL,1),(2245,'show_captcha.php',NULL,NULL,NULL,0,0),(2246,'delete_system_tag.php',NULL,NULL,NULL,0,0),(2247,'edit_system_tag.php',NULL,NULL,NULL,0,0),(2249,'app_application/help.php',NULL,NULL,NULL,0,0),(2250,'app_application/index.php',NULL,NULL,NULL,0,0),(2258,'app_application/page_details.php',NULL,NULL,NULL,0,0),(2259,'app_application/add_facility.php',NULL,NULL,NULL,0,0),(2260,'app_application/delete_facility.php',NULL,NULL,NULL,0,0),(2261,'app_application/edit_facility.php',NULL,NULL,NULL,0,0),(2262,'app_application/custom_fields.php',NULL,NULL,NULL,0,0),(2269,'app_application/add_hospital.php',NULL,NULL,NULL,0,0),(2270,'app_application/delete_hospital.php',NULL,NULL,NULL,0,0),(2271,'app_application/edit_hospital.php',NULL,NULL,NULL,0,0),(2272,'app_application/built_in_fields.php',NULL,NULL,NULL,0,0),(2273,'app_application/add_location.php',NULL,NULL,NULL,0,0),(2274,'app_application/delete_location.php',NULL,NULL,NULL,0,0),(2275,'app_application/edit_location.php',NULL,NULL,NULL,0,0),(2276,'app_application/add_page.php',NULL,NULL,NULL,0,0),(2277,'app_application/delete_page.php',NULL,NULL,NULL,0,0),(2278,'app_application/edit_page.php',NULL,NULL,NULL,0,0),(2279,'app_application/add_page_element.php',NULL,NULL,NULL,0,0),(2280,'app_application/delete_page_element.php',NULL,NULL,NULL,0,0),(2281,'app_application/edit_page_element.php',NULL,NULL,NULL,0,0),(2282,'app_application/add_page_menu_item.php',NULL,NULL,NULL,0,0),(2283,'app_application/delete_page_menu_item.php',NULL,NULL,NULL,0,0),(2284,'app_application/edit_page_menu_item.php',NULL,NULL,NULL,0,0),(2292,'app_application/add_setting.php',NULL,NULL,NULL,0,0),(2293,'app_application/add_ward.php',NULL,NULL,NULL,0,0),(2294,'app_application/delete_ward.php',NULL,NULL,NULL,0,0),(2295,'app_application/edit_ward.php',NULL,NULL,NULL,0,0),(2296,'app_config/index.php',NULL,NULL,NULL,0,0),(2298,'app_config/edit_setting.php',NULL,NULL,NULL,0,0),(2299,'app_config/system_settings.php',NULL,NULL,NULL,0,0),(2362,'app_application/users.php',NULL,NULL,NULL,0,0),(2363,'app_application/add_user.php',NULL,NULL,NULL,0,0),(2364,'app_application/delete_user.php',NULL,NULL,NULL,0,0),(2365,'app_application/edit_user.php',NULL,NULL,NULL,0,0),(2380,'expired_password.php',NULL,NULL,NULL,0,0),(2384,'app_application/user_details.php',NULL,NULL,NULL,NULL,0),(2388,'app_application/import_users.php',NULL,NULL,NULL,NULL,0),(2462,'new_user_setup.php','',1,1,NULL,0),(2463,'resend-password-link_user.php','',3,1,NULL,0),(2464,'activate_user.php','',3,0,NULL,0),(2465,'deactivate_user.php','',3,0,NULL,0),(2552,'add_ward_security_group.php',NULL,3,0,0,0),(2553,'app_application/add_ward_security_group.php',NULL,3,0,0,0),(2554,'edit_ward_security_group.php',NULL,3,0,0,0),(2555,'app_application/edit_ward_security_group.php',NULL,3,0,0,0),(2556,'delete_ward_security_group.php',NULL,3,0,0,0),(2557,'app_application/delete_ward_security_group.php',NULL,3,0,0,0),(2559,'app_application/ward_security_group_details.php',NULL,NULL,0,0,0),(2561,'app_application/multiedit_user.php',NULL,NULL,NULL,0,0),(2562,'multiedit_user.php',NULL,NULL,NULL,0,0),(2563,'ward_security_group_details.php',NULL,NULL,0,0,0),(2564,'import_group_permissions.php',NULL,NULL,NULL,0,0),(2567,'search.php',NULL,NULL,NULL,0,0),(2568,'system_tags.php',NULL,NULL,NULL,0,0),(2569,'app_application/patients_and_admissions.php',NULL,NULL,NULL,0,0),(2570,'app_application/patient.php',NULL,NULL,NULL,0,0),(2571,'app_application/patient_details.php',NULL,NULL,NULL,0,0),(2572,'app_application/delete_patient.php',NULL,NULL,NULL,0,0),(2573,'app_application/add_patient.php',NULL,NULL,NULL,0,0),(2574,'app_application/delete_admission.php',NULL,NULL,NULL,0,0),(2575,'app_application/edit_admission.php',NULL,NULL,NULL,0,0),(2576,'app_application/new_admission.php',NULL,NULL,NULL,0,0),(2577,'app_application/discharge_patient.php',NULL,NULL,NULL,0,0),(2578,'app_application/edit_patient.php',NULL,NULL,NULL,0,0);
/*!40000 ALTER TABLE `system_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_pings`
--

DROP TABLE IF EXISTS `system_pings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_pings` (
  `id` varchar(128) NOT NULL,
  `time` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_pings`
--

LOCK TABLES `system_pings` WRITE;
/*!40000 ALTER TABLE `system_pings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_pings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_session`
--

DROP TABLE IF EXISTS `system_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_session` (
  `id` varchar(128) NOT NULL,
  `access` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `data` varchar(60000) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_session`
--

LOCK TABLES `system_session` WRITE;
/*!40000 ALTER TABLE `system_session` DISABLE KEYS */;
INSERT INTO `system_session` VALUES ('vFd9cuaWOX1A5Bc7EPu5Q3',1620056133,182,'MmMwMDRlMDRmMmMyODM4ZDliYmY4YWVhOTIyYmNlMjKiGkTds8tq9HIffDsLpSdjN2dSM0lOeSt6d0QwdGFhcVlHTW4rWUZ1RlZYNXVQcHA0aFBZWVEwRURzQmN0c3Y4dWZvWlhIcGh1T3ZFVlZoQ0QwUTFFYS9pUkVSeUZ0SmJRbnAxcGFsWHQ5anhHaUdWQUU3UnovcUV5WVMvUFZDNWhiUHBSQzBpMWJ5emE3UmxBazBNV3FVcTNwVGkrOThDbVB1R1JKTmlZUzNIWE1QRkJkV1RYN0wrTkRObVVUZ0tZUnM3MEZvRHVjVHV2b3JGVW5XSkxQYTBMSVN4bHRoRUo0eXBQZmt2bFlJUVppVFdXQ0xxUlpSc2NtblliS2IxbExpNHVncVJZc0xSN3preVBTWER5MXFXdDY3SmxUa1VxZkVremFITisrL2kreDRtOG5hYVU0NzV2NUxSRDdQODBwOWx0NXRGNFNCa2Nack1kMkQvbGhvMGtjdGRwOFQ5Z29TKzUzNzlES2RpTkRLckFHNk9lUmlCZEp2S2hydkplYzNPLzllUkRBZjFiR0psRUN6NDhwM2ZKNnRSMi9nMkxJVURVUXZpdUE1MVNIZ293Tk95Z1JoN3NFTXc4VXJCcm04RGc1eHF2SjhvSjBNRUdZdWJ2N1dCTlA3ZzVvT3FiSTZRU2pOZTY2SkZ0bUloaTJQQWFKQ2JSN3p3SkIxQ0dMVnA3YkQrWVA2TXlxMXJjb2tHY0VJbUxaVDQ4cC9FTWNTQXV6aDRPL3BCTnFaMit0VWJFSVp2T2hsUHVoRm5JcmNFS3lRa0dCY0VaNFViTUFydW1mTk9hdmIrK0xyWENJWmpVOXNrV3J0VDBJRU15WlFzak5lanlHSlRiRVNRUEcyZmxwU2ZNTmxKc010ZUVienRLbGZ3a3pKWVY1UE95VkE0OGRXL05ONzM5SWJHOC90MVVvQk13dHRJTFIvNFpEWVI0dmVXWTltL0hUdzRhUzdLdWlDNnZmWDYzMnkzb3hEYnE2K041Z2dRZ3FPREorWnVxM016eUdLTTRIZFNKeE9JbmtqYlNTMG8rdk1DanhnbTJuUExFdnFWUUxKaGpwVFJ6NjFnOHQ0V3VGR2NJMDQ3MVJGYVZxOFovNUluZlA3dHFWWGwyMHFkOERReTBXSnlOZ0VkOG9BLzBLOUR1cnNRNWdobmU1ZGl6MXZHMVFDVnJNM1FtWWsrRXNjNFhmclNiOUp6UDNod3pMSG5DZGFBcXRac3B5aW53Kzd2Vm9RM2dOdURBZXNwaFFDSzVlZXlqd2Q0RFR2N1BzV3pLMlhaT2VudXhPNkFtUG96Sm5ydHBOQmNMcE8rVlFtSlFva3VzTTBablM5S2JNSEdDaXFFUFpyY09zbjJkMWlpb3h0eUNKR0JzTHltTXFYNmJrTUxGUDQ4QUIwenBEQXB6QW1CVHZuTVMrc01sS1lZbGhGeVV2dVd1TCtXdUhWV2V3QWdyaXJtSWQ2ZmdTOHE1d1pUdzlZNnM0YXFmS2dlR1UwNXNzVUxPRlI4MGlyazZoN0FYY0dmcVVRc1Q4VnFBTUpJc2Z3N0tNSUNwY09mZ3VVTkFnTnhYQUhUd0VhcXVHRURpUFlUV3VtL1gxUDQrVHZCU3lmNWtOYmcwSHpzTndjdmRhb2VuRU5SN1FCUVdmUHowK2w2L1RyQzI5R2NFRndwRytlYzZucjR5L3VURzNacGlKSEs2RVdPSjhOSVF2d1RNQXRUOHhlVlJ3VU85aTl1RE5JdVVEa0Y4L1dCWFdOWGx2YVlEalk3YnVaa29nSVh0T1lLc1F5VnNwTytwM1BFRHU3dTd5WGlHL2lBa3pqa0tuTkNLNndXdEdyTFlHTDdIUDJmTC94d05NdkFMZ0oreHV2M2o2K1NGQ2ZwMmkxZXl6UkwxMW5aSW1rS0piSWpnUWpzWkdidXhsLzd0NHl5c0ltTjBHWTZmdlUvaWNjdDR0K0tBNmJ6K1dWdU5ZNmhvTjAzQWF6WHhOQ1JiaVZWWnFnOW9jT241SHIxb2ROQm1PN3RkMnRCWmZTWjN4aDl6VjRQeVFkTnJ0bDBra0dtdFRVNFh5Tjl6SFZmc2oyQ2xQQ3M0UVFBZzhqZUJFK2d1OCtjUFQ4OTZoQzdGOWhLZ3dMOU9oMmFrTHViQUZ4b2x4Vi9NUlZBL0daeWxjV3dmV0RjTTVCSlhuMHUzKzZVcGx1OHNoMXMzMVJPTks2UEFlT1hUYzRLOTNleWFScVlFWUJFMjVSd3JNR2F5dXQ3NlhiWUlvTGRpTUViUmxyUnhhNGxpbjk5WXY0TWNYY2pMczVNSmQ2Q2ZsdjBGb21KUE9YY2lUNjltM1BuZzJKME5QdzNMdFRxU0Q2T05RMU96TGJERzM0c2lhTzh4bnBINVl0S0RmaWp2WVFmK25SNnlBUnRNU051UEx5V1NBeXo0WTc1YTJzTzhJY0d3UStzUzFoYlRiZk9Kd2ZPbTd0dUpsdHBVOU4zTDZFbXhMN3gwVS9jZHNKSG1HQ2hQamJyeTFydTZNUjVHNVF2ZllhY0dpTXN0QldWb2hLYmk0RjlLdEJiSkkyZnd0UXA4NlljRk80NU5BSVl3WDVmMjBKTU4wNVZ2clh4NXdRc0Z1UVFvdUFVeElvK2l0bllQVHBvb3RpL3hjTEtkSXZWKzZacXEzZXJwbko1UHZaeUNtL01pK0ZWdng5OU5Dc1pwbXZCU25BcGtLTHVwYXNvME84cXg1azVSVnE3R1ozdVpsbnNNOTZaZnZlSHF0dzFadW41TzBubkx1bGlCWldIWDZVdjJzYnQrYTBMU2dZQkovb1E5UzA4d2pzdTdkK09WVWQ2YStaYzRnSVZLYllNM2NNMGN2Vi8zQTJmRm0xWkgzZ1NTbnY4a3ltR0FvZEtXelNUWk8rWk5HSzJlc2xRQVNQSDFqdGo0WXZHM2FhcFZpaDBBT3JZTmNnZ2ZjanRPQllkL0MzdFhrYTNmZVYxcitKNC8xM0N1TFh2UURIeG9QT1h0M2tQaG12N3hGd29kQnJCaDgzZlhiSndxVzRoQ1NBNkQwMHB6QitVL0ZsbWF0Rk82UThXZlh1blVEOE8zQmN2MVBEbXk3MU0xN0Y4YnJYRzhPbDlYOWpobVUySkZyYW5Ec2dzV1dRM29VbHhlVVQyWUhmVGVXMUt1V05xU1QxMkFjZy8rV1pyaW85eldpcU5SZHExU1J1SjJLNjFBcm9wL2p5YUJFa3BLbmYwbzFkeW9NclJjajNVN1A0dUZ2Q291RGhpMm4yRmY0MUhaQ1E3T1N6aEh0YkV6b0ppLzFBRE9TYnR6YkhoeWVhK2VvTmFBQWNUUlBCTjh6L0pCSlZLY1pndXp2M1Y2MEtNVFRpQldSTUtVWWlIT2hKdXFEQUVoS21zbUJtU2pZbHhmWTJ3MUlLTUo0b2xqQ2ZWUUdEVXZoOFFtRmI5bHI2dC9SeWdBVlNodm9DYkNMWDhnS3pxQ0lJTjYvUFZDRm8xZDBjNi9qRlFOa2hOS1dDSi82RERPbm9vZGZCODRScXRqU3lTUHU3Wng5T3BpTkFSbVlvelgrbEZrM3lpRGQyemVoWUg4eCtuNVRDcTIvaitJZWJybTJEblVSMmE2RVgra0l2Z2s5eU14THRYcjVCYzNQeVR1R3dvOW9IcFBSVGFrVFY4eVMya01WZDVYRjZLdFJLRkFZQ1VuWjBJZmQzaElWVGFFUk0vYjg0bmZFclJnUzhQREtVVFVlNFpzYzdRempSVU5rT2dVMjlZM3Z1MUozVVlUTHF5TGFXNmlnUmVXL0tzOU52Z1N3SWZIUDVTMUNuaFJjSHdsYkVWVnBkNVd0dkV5NU5TenFmRlNqa1B1TXgxUmU4dlBkcTZOOTUyOHJJSzNPOVM3QWRBWXhQTDJyK1IxUm4rNHB6QndBYUk0VlZuR1hWRFFzYXR0aXBsUUFkblU1RGJvbU54R3c0aVRpUTkyU20zWk40MHJ0cW9rNHRuamtsVHRpaFgvQStWNVZCMTBuUUVXYnRZVk5tbEVMYXVRbDU2SjQvc0UxeFJWMitKNVpKZmRDeThYclJTQzAvNWFzdHFrZXpJMmgwVU1ueW1LTmF5UmZqNmc4Ky96NitrbUVsL3NibHJKNkRhZzlaRklMOGJOVDNSeTdmeEp4bE1ybk5GemJCTUVKNkZQeVU2R3RSdmw1ZzdTUUNmcklmU1E5QUN6U3ZFdkhWdzJpU0JaVE8zdkZuL0Z3OWJRb2ZEUUFhTUlka0lEWGU1QkNYUlhnenBHbGtEUE9oRG5FZzJ3S0VsQjBZUmdNRmxZTW1hZGRFaE96MUZaellXcFpkc2NsWjhmdnRpNjJWOVdKTzlISU9QOXE2bVZvaW1VeGR2bzEzUC9YTCtSL1dEcjFubjl3MnFHdXpYMm0vYTBkVHJPSmt2WnhsMEsremtqblZUei90WXJtYVZXSmdJVy81c0J4SHNRd3RMdnZ0SCtHOEVKenZLZ0VGdUlEMDU2ZHlaK1UraUE4Snh4b2kwQk5WVzdCOVpBQUl5UThMUHBXU0ttR056YWYySUNISmZ6Q1lJb29tclJuT1lVTXFIR0hpTXZ4M2FDME1la2JQczQvaVZXcVlheDlyQUdobDVta2p1RklNcm5ObUVpSUoyVFU5S280QVpWSXhFZ0pDT2tVbWhEMjR3ajJvdG9WNzdWSG5EK29ZV09ERjArbnJQUmtPMXRIMjFQRGtKRTdyOVhVTTduK3NtQWdmOHR6SkFLRDBxUWhOb0R5d1NsbDRjdGhETzRSS0RxTElNaTVnUTk4MEZlSmsxTzUzQ1diQkhCcEdveXVqUWdDbVliSVNxZHdXbEI2N3JKYkVHQmdSOHZFRnBGTTFrOFdLZzl6V2s4ZVVnZllDcHRJdThWRmRiWkg5cWM0ZlFqZUNENm9QYzlmb2RPYStHZ0o3ZThoelVNakJUZXp1d0FmTTdVeTlKL2VsQndRMllZSmV5aG9sS0d1MDdHdW96V3FRYVR5MmREMXNpYk1YNzJ4OThib21EK2RudzFreWk5OTltdmZTTm9PbjRQdlZaK0JpQUF0SDlUeWVEdmZaa0luaDN3aHIvOGVhZXJXUWpNYXFIM0VFeEYvTGtLK3pNYXR1czg0VngzeU1XUGRJNGU1K2theUVUVVdXZXg3bmdTVTlEYVE2ekxkbVZ4Y2lZUXRNOEVoM0RGZHJkN0pJSTh2bm5XMXFXVGE5NFNvSzJHT2VMeFprSnBlNStpZ2ZVd1NVTCswY0ZXNnlwejVReEF4WkZhSmEwRnNpOCtpRlQ5QktUczcwOUZ6enZrZEhGamhmNTNPTUxqOGFRMXJGZzMvYmZpbnVqWGROUjRNNDR4UkR0MzZrdHJlR2NHUjUzaW1RanRSdVFhVnppZnU3ejVqOWpoTU51am44WXFWejBrS2pkdzZCLzBtOXE4c0d1ZnZWbzBMalhDcFdGQ1VralNDdmJhc1Y4OFRKWDh5YzdWRlJaZC9IWWY4NllFR2FYa0ViUkFueG9LRVEvdXhVcmpMNTQ3NzZ0NWxaK0QzTEt0U1dMelFMTFU1ZG1LMVFtcE9EK3VtVGxtT2NEV1VjZE5mTkc0L21TWFV4S0RMbGc5bWhqNjFpWnJsMmVxSnRqaXovd2lSZkpRK1hZRmFBK01tNHIwOXdIczRsM3JrS1lwMlJTQ3I0aDJrNUo1Ulc0amdKSWhCaGZFRXJncmhlWEFUZkZPZEFkTko0TW1idjhMcXhOL09rQ0JzOG4vaEdOSmx0VDhONkhhMTVFRXc1U3pPZVB6MEZaS0FwTWF6NzFmZHV5cnNrM1UvUDJtQWdtOHVYaUlnYllnZEZ0ZlVYTjJteVZXRkVJekZSK1k4RmN0Ris5UDliZW1ySzEzOTNmTU1NaUVrd0kzeW53T3pUdkYxOW56aEJXNS9GRHpvcmhlZGxlS0NTT2Q0cThwSVZVVUk3WGpYV00rWnUwRzMvMUF4elJGRFFmK3VobzRKTURyU1p3WjJxRXZNeWZHOTEvR1pORDBkSFdSTU40NTkwaUpTdVNFRENnNnNSY2ZyV2EvOU9NRFZocXc5cW91cG9RazJhb1VJcXJuc1hUODFTbmJwbWd5Sk9FcGRVdVNPL0E0Q3ZndElmR0NNQjVpNU5TZzZ5dHdZUGVuNWtZZVg5amhuNjVnU1RXem9aMmxYcjh6bUR1QU4rSVk5NFR6a3IrQ0hpR2ZVek8rV2dMWjNHdFFwQWxHQk9vK0F6dmF4a3pDbUZQcmU4b3FWWVpQY2o1emMxWVlzemdxUlc4OHZKR2lPcHo4MkVWbDhiOTlnNnpYbmY1OHlaZm8vVVRDS21XeXN6cXRZL2hsK1Z4UmZnQ2xMcVl1N2JnRUdacHJNc2lmMUtZQU1rSWNjSmpPbmZYbEN4TGpLczZjQWNkWEYvc294b3ZmTk5scFcvVmNGenJ5Zk16ME8wZWlLS1R4WXZmSU1YYks0aUtJRDRDRWhRVkFrOEt2R1crWnRBNnp1a29oQ3R5UEVrWlJXSXA4cFFFaTZwUGhVYW1aZUpkeFJCall0aU85YTY3c251TDFVMHRuVDVPMzhucVhvSnFzWDdJNk5LZFBHcG85Z2FSSktBK01qMUhpeVlYd1FMVGFKcEQyN3Z3VW9rZWVlNmFBc1VtbFd5ZHBtRmZyOTZRZFJRM0dSdEpjWXpRM09XdkdQRENvRlVFWUlMWnQyenhzMUxvL1RTTDlPalpjY3dFQmlYVENKWkdDQ0Q5TTJhQU43ZERUd0w5cjlRd1VXRmhWMlFOL2svS2lqUTJjc29ZM1N2WWFNekY0QTZUdkVldDArTjQ5U05wNGl3U2JkMWVoMlpEbzEzYWRqNmt3TjZOQWRwUlRQa05rY1I3ZUhxYitJVXplZENZTy9SYUdYd2dMTUlucStqVE9EWTF0N1B1dElNR0lpK2kzRnRPVEE5L1FDeXJnb2o3Z2UvMFdVc0thcEtlK0tZRDBUZEd4cHl5VkhHN25mNzdhbmhDbGZOVzROdzVlZStiV3dKcGZEQU1Bd2xsSEFGQy9ZaDhLQkxFYjBMV1RjN0RDU0NNRFlKT0RKemVQY0Zra0dKeHJUU05PTTc0MDV3NWlPT0FKbVBSZC9VZTdqVVgzcVhGWUNTMGl2RlNUdlJmMEhOa3hYMEhMMEJQRWhROTBLQmh1MytxaVhsS2VsMlBCOEx6MkFhSlBDbFdjSGdKVllSYXRKdzlqQWJ2OXV5OTdteTdnMmpVcnRUa0toOW9NVDA0YWJHNElZa0x2MzNFTFpYRzhnNlBTTGptRTBQLzJSVXJtellZWEJqaFdqK1NoS1Q3VXJsZjNtZGMyVmhOWmw3Q3FrVlQ0SXdIbWJiRENtOUZsTExUQkRvTVQwWnQxQzhRaGZRQUtkc1Q5WENVRnJZblRwdEZlTkU3dWZlTWt4cWpDLzBIb3lXL3RJdUJiUWRZblVjK2ZoWkVnQzBzRllkdVovdXp4MnR1WkY5ZjZ3amF0UmhOQ2JpcFJBZ01ocFVKeG1WeDY2UUFETXN1Z1VyVGlXMHN0QWhwUWpUUG02eFhmVXoyTUR4RXhZMTJwTXFIT0Z0OG5zQmpEeHNSYVZINDY0THpZUllPNkwvbXJFZ3JJZW1neVUwejNucm5ySjI4ZjlLTVEvREpHbllqYnRoREpRZGsrQlZRejY3UDRWTHdrcE03ZHdkVzdxTXVVRGpJQzZwV3dsRDlDcU1aYWpmckVYT25TbUlxR3BqdVZ5TDd2VXhIckpNZlRqMkR3TVp6T1FUZzFaOGNuODFiamFXQ2ZzNDgzcVZFWWs0WE5wVitJRkZDa1lrcjR0TTlBSG02dmx2SlZvSFR3c254SXBRSDNmRldtTFduL2pjcm9VcHFteFI3TzlPaytGb21mcWNzdzJSaDhJUWx6N21uTzM2NTNsUGYwOGMxTytxc0tQN0dhVWRXZ05WMHdDNWdiSm1LcU9tekNySm5RcFp3WnR2YXlGVHFRYkFRR1YzeGNoY1pFazZhcUIyWi8zNHYyVjI3RGs3VFBXb1JOMVd4U21pdWVUZEVLOTM5dHlIUEFNTnFPZ0lmZTRLZEQxbTFRZTArem1tQ1A0UCs0U3h4cm1udGpOTWdNc1MxN2syVUdlbnhFWGFmQnZlV3RmMFp1SWJ5Tng3blI0c01ta2FFM3RyTkZYaEVHN1ZGTmlKUVJuRTJXKzluQ05hNHVOUElWSFRwREdacmIrMDJLMlJLV2Iwa0RhK0xkeXlMZHNZZUpRNy9Vc3o2RVFFNmtrL1lxMXUyNEM5RlBoTWpNTG5jQXliblFxOTY4ZnhqMEJ0cUprRjV0RWx4Y25YeXlsbXJEUHlJTFdHR0xRMlVZL0tGczJGNTAvTDF6VVlrSmw0YjRMR3FJNEZZcDVIaUdWWW42RlJQd0J4Z3FVK1AwZEVML01EYU1BeWs0UmovUEJpNzF4UkJucjFQV3B1SjhxcnE5NHdRYk5WaXRLMm9jdnUrZjRZWW4xZkdrZz09','2021-05-03 16:48:17','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36','::1');
/*!40000 ALTER TABLE `system_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_session_key_value`
--

DROP TABLE IF EXISTS `system_session_key_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_session_key_value` (
  `id` varchar(128) NOT NULL,
  `session_key` varchar(255) NOT NULL,
  `session_value` varchar(64000) NOT NULL,
  `type` varchar(1) NOT NULL,
  `length` int(11) NOT NULL,
  KEY `id` (`id`),
  KEY `session_key` (`session_key`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_session_key_value`
--

LOCK TABLES `system_session_key_value` WRITE;
/*!40000 ALTER TABLE `system_session_key_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_session_key_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` varchar(20) NOT NULL,
  `values` text,
  `value` text,
  `comment` text,
  `module` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_tags`
--

DROP TABLE IF EXISTS `system_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `related_object_name` varchar(255) DEFAULT NULL,
  `related_sub_object` varchar(255) NOT NULL DEFAULT '',
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_tags`
--

LOCK TABLES `system_tags` WRITE;
/*!40000 ALTER TABLE `system_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_user_alerts`
--

DROP TABLE IF EXISTS `system_user_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `object` varchar(255) NOT NULL,
  `object_table` varchar(255) NOT NULL,
  `action` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `FK_system_user_alerts_user_id` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_alerts`
--

LOCK TABLES `system_user_alerts` WRITE;
/*!40000 ALTER TABLE `system_user_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_user_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_user_bookmarks`
--

DROP TABLE IF EXISTS `system_user_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `url` text NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `FK_system_user_bookmarks_user_id` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_bookmarks`
--

LOCK TABLES `system_user_bookmarks` WRITE;
/*!40000 ALTER TABLE `system_user_bookmarks` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_user_bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_user_comments`
--

DROP TABLE IF EXISTS `system_user_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `page` varchar(255) NOT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `status` enum('New','Reviewed','Resolved') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_comments`
--

LOCK TABLES `system_user_comments` WRITE;
/*!40000 ALTER TABLE `system_user_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_user_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_user_group_links`
--

DROP TABLE IF EXISTS `system_user_group_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_group_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_group_user` (`group_id`,`user_id`),
  UNIQUE KEY `UQ_user_group` (`user_id`,`group_id`),
  CONSTRAINT `FK_system_user_group_links_group_id` FOREIGN KEY (`group_id`) REFERENCES `system_user_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_system_user_group_links_user_id` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `system_user_group_links_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `system_user_group_links_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `system_user_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1607 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_group_links`
--

LOCK TABLES `system_user_group_links` WRITE;
/*!40000 ALTER TABLE `system_user_group_links` DISABLE KEYS */;
INSERT INTO `system_user_group_links` VALUES (33,0,0),(1606,182,1),(1602,184,1),(1601,182,2),(1604,184,2),(1554,861,2),(1557,862,2),(1560,863,2),(1563,864,2),(1566,865,2),(1569,866,2),(1572,867,2),(1575,868,2),(1578,869,2),(1581,870,2),(1584,871,2),(1587,872,2),(1590,873,2),(1553,861,3),(1556,862,3),(1559,863,3),(1562,864,3),(1565,865,3),(1568,866,3),(1571,867,3),(1574,868,3),(1577,869,3),(1580,870,3),(1583,871,3),(1586,872,3),(1589,873,3),(1593,874,3),(1605,184,4),(1555,861,4),(1558,862,4),(1561,863,4),(1564,864,4),(1567,865,4),(1570,866,4),(1573,867,4),(1576,868,4),(1579,869,4),(1582,870,4),(1585,871,4),(1588,872,4),(1591,873,4),(1594,874,4),(1603,184,5),(1592,874,5);
/*!40000 ALTER TABLE `system_user_group_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_user_group_permissions`
--

DROP TABLE IF EXISTS `system_user_group_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_id_group_id` (`page_id`,`group_id`),
  KEY `UQ_page_group` (`page_id`,`group_id`),
  KEY `UQ_group_page` (`group_id`,`page_id`),
  CONSTRAINT `FK_system_user_group_permissions_group_id` FOREIGN KEY (`group_id`) REFERENCES `system_user_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_system_user_group_permissions_page_id` FOREIGN KEY (`page_id`) REFERENCES `system_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3260 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_group_permissions`
--

LOCK TABLES `system_user_group_permissions` WRITE;
/*!40000 ALTER TABLE `system_user_group_permissions` DISABLE KEYS */;
INSERT INTO `system_user_group_permissions` VALUES (2027,1,1),(2044,3,1),(2381,3,5),(2039,4,1),(2330,4,4),(2376,4,5),(2684,6,5),(3221,7,1),(3222,9,1),(1999,12,1),(2029,13,1),(3225,18,1),(2497,18,4),(2682,18,5),(2006,24,1),(2388,24,5),(2024,25,1),(2285,27,1),(2328,27,4),(2364,27,5),(3230,28,1),(2366,28,5),(2286,29,1),(2327,29,4),(2368,29,5),(2004,30,1),(2392,30,5),(3231,34,1),(3232,35,1),(2370,35,5),(3233,36,1),(3234,37,1),(2374,37,5),(3235,38,1),(2375,38,5),(2683,41,5),(3224,195,1),(2022,245,1),(2384,245,5),(2001,460,1),(3228,501,1),(2016,536,1),(2000,537,1),(2391,537,5),(2379,2162,5),(2058,2163,2),(2112,2163,3),(2332,2163,4),(2383,2163,5),(2090,2166,2),(2108,2166,3),(2390,2166,5),(2238,2245,1),(2386,2245,5),(2233,2249,1),(2445,2249,5),(2234,2250,1),(2526,2250,4),(2446,2250,5),(2235,2258,1),(2680,2258,4),(2447,2258,5),(2193,2259,1),(2543,2259,4),(2396,2259,5),(2208,2260,1),(2415,2260,5),(2220,2261,1),(2545,2261,4),(2430,2261,5),(3248,2262,1),(2528,2262,4),(2412,2262,5),(2196,2269,1),(2399,2269,5),(2211,2270,1),(2418,2270,5),(2223,2271,1),(2433,2271,5),(3249,2272,1),(2529,2272,4),(2411,2272,5),(2197,2273,1),(2400,2273,5),(2212,2274,1),(2419,2274,5),(2224,2275,1),(2434,2275,5),(2198,2276,1),(2401,2276,5),(2213,2277,1),(2420,2277,5),(2225,2278,1),(2435,2278,5),(2199,2279,1),(2402,2279,5),(2214,2280,1),(2421,2280,5),(2226,2281,1),(2436,2281,5),(2200,2282,1),(2403,2282,5),(2215,2283,1),(2422,2283,5),(2227,2284,1),(2437,2284,5),(2409,2292,5),(2203,2293,1),(2410,2293,5),(2217,2294,1),(2427,2294,5),(2232,2295,1),(2631,2295,4),(2444,2295,5),(2189,2296,1),(2452,2296,5),(2188,2298,1),(2451,2298,5),(2190,2299,1),(2453,2299,5),(3236,2362,1),(2626,2362,4),(2685,2362,5),(3239,2363,1),(2630,2363,4),(2687,2363,5),(3240,2364,1),(2688,2364,5),(3241,2365,1),(2628,2365,4),(2689,2365,5),(3226,2380,1),(2604,2380,5),(2610,2384,1),(2633,2384,4),(2686,2384,5),(2614,2388,1),(3000,2464,1),(3001,2464,4),(3003,2464,5),(3007,2465,1),(3008,2465,4),(3010,2465,5),(3206,2552,1),(3243,2553,1),(3210,2553,4),(3208,2554,1),(3245,2555,1),(3212,2555,4),(3207,2556,1),(3244,2557,1),(3211,2557,4),(3238,2559,1),(3209,2559,4),(3242,2561,1),(3215,2561,4),(3214,2562,1),(3213,2563,1),(3250,2569,1),(3252,2570,1),(3251,2571,1),(3254,2572,1),(3253,2573,1),(3255,2574,1),(3256,2575,1),(3257,2576,1),(3258,2577,1),(3259,2578,1);
/*!40000 ALTER TABLE `system_user_group_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_user_groups`
--

DROP TABLE IF EXISTS `system_user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_groups`
--

LOCK TABLES `system_user_groups` WRITE;
/*!40000 ALTER TABLE `system_user_groups` DISABLE KEYS */;
INSERT INTO `system_user_groups` VALUES (0,'Master Admin',''),(1,'Admin','');
/*!40000 ALTER TABLE `system_user_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `system_user_groups_view`
--

DROP TABLE IF EXISTS `system_user_groups_view`;
/*!50001 DROP VIEW IF EXISTS `system_user_groups_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `system_user_groups_view` AS SELECT 
 1 AS `user_id`,
 1 AS `group_ids`,
 1 AS `group_names`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `system_user_historical_passwords`
--

DROP TABLE IF EXISTS `system_user_historical_passwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_historical_passwords` (
  `user_id` int(11) NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `created_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`created_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_historical_passwords`
--

LOCK TABLES `system_user_historical_passwords` WRITE;
/*!40000 ALTER TABLE `system_user_historical_passwords` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_user_historical_passwords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_user_table_views`
--

DROP TABLE IF EXISTS `system_user_table_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_user_table_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `view_table` varchar(255) NOT NULL,
  `view` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `FK_system_user_table_views_user_id` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_user_table_views`
--

LOCK TABLES `system_user_table_views` WRITE;
/*!40000 ALTER TABLE `system_user_table_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_user_table_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_users`
--

DROP TABLE IF EXISTS `system_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_changed` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(64) NOT NULL DEFAULT '',
  `last_login` datetime DEFAULT NULL,
  `failed_login` int(11) NOT NULL DEFAULT '0',
  `allowed_ip` varchar(20) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `end_login` datetime DEFAULT NULL,
  `current_ip` varchar(20) DEFAULT NULL,
  `unique_login` tinyint(1) NOT NULL DEFAULT '0',
  `preferences` text,
  `bookmarks` text,
  `person_id` int(11) DEFAULT NULL,
  `two_factor_auth_type` int(11) DEFAULT NULL,
  `two_factor_auth_token` varchar(64) DEFAULT NULL,
  `country_code` varchar(5) DEFAULT NULL,
  `mobile` varchar(10) DEFAULT NULL,
  `is_sa` tinyint(4) DEFAULT '0',
  `created_time` datetime DEFAULT NULL,
  `password_link_time` datetime DEFAULT NULL,
  `deactivation_time` datetime DEFAULT NULL,
  `deactivation_reason` varchar(255) DEFAULT NULL,
  `auth_type` varchar(9) NOT NULL DEFAULT 'Native',
  `password_changed_by_sa` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `person_id` (`person_id`),
  KEY `system_users_auth_type` (`auth_type`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_users`
--

LOCK TABLES `system_users` WRITE;
/*!40000 ALTER TABLE `system_users` DISABLE KEYS */;
INSERT INTO `system_users` VALUES (0,'admin','$2a$14$9SWEtKjwspTVMjjgOpCb1eguR/nEHpRbfkjEXpBdM82C3zUdd3f6q',NULL,'System Administrator','php.test@vitalhub.com','2021-05-03 15:19:06',0,'',1,'2010-12-20 17:39:42','',0,'{\"id\":\"1\",\"pagination\":\"20\",\"patient_type\":\"Inpatients\",\"filter_by\":\"None\",\"review_status_filter\":null,\"inpatients_facility\":\"25\",\"inpatients_specialty\":\"1\",\"inpatients_area\":\"2\",\"inpatients_level_of_care\":\"8\",\"outpatients_specialty\":\"1\",\"outpatients_area\":\"2\",\"outpatients_level_of_care\":\"19\",\"encounter_view\":\"No encounters expanded\",\"nq_review_offset\":\"1 day\",\"q_review_offset\":\"1 day\"}','',0,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'Native',NULL),(182,'test_user','$2a$14$9SWEtKjwspTVMjjgOpCb1eguR/nEHpRbfkjEXpBdM82C3zUdd3f6q','2021-04-28 16:52:27','Test User','test.user@vitalhub.com','2021-05-03 16:49:40',0,NULL,1,NULL,NULL,0,'{\"landing_page\":\"patient_search\",\"patient_type\":\"Inpatients\",\"filter_by\":\"None\",\"pagination\":\"100\",\"review_status_filter\":\"Current Tasks\",\"encounter_view\":\"No Encounters Expanded\",\"id\":\"1\",\"inpatients_facility\":null,\"inpatients_specialty\":null,\"inpatients_area\":\"2\",\"inpatients_level_of_care\":\"8\",\"outpatients_specialty\":\"1\",\"outpatients_area\":\"2\",\"outpatients_level_of_care\":\"19\",\"nq_review_offset\":\"1 day\",\"q_review_offset\":\"1 day\"}',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,'Native',NULL);
/*!40000 ALTER TABLE `system_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_users_dashboards`
--

DROP TABLE IF EXISTS `system_users_dashboards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_users_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dashboard_id` varchar(255) NOT NULL,
  `dashboard_layout` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_users_dashboards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_users_dashboards`
--

LOCK TABLES `system_users_dashboards` WRITE;
/*!40000 ALTER TABLE `system_users_dashboards` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_users_dashboards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_wastebasket`
--

DROP TABLE IF EXISTS `system_wastebasket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_wastebasket` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `deletion_date` datetime NOT NULL,
  `object` varchar(255) NOT NULL,
  `object_key` varchar(160) NOT NULL,
  `information` text,
  `content` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_wastebasket`
--

LOCK TABLES `system_wastebasket` WRITE;
/*!40000 ALTER TABLE `system_wastebasket` DISABLE KEYS */;
INSERT INTO `system_wastebasket` VALUES (146,0,'2021-04-28 19:56:27','system_app','15','\r\n            <table class=\"action_form separator\" style=\"width: 600px;\"><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">App Name</th>\r\n\r\n                <td class=\"\">Front End</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Active</th>\r\n\r\n                <td class=\"\">\r\n            \r\n            \r\n        </td>\r\n              </tr></table>','%7B%22table%22%3A%22system_apps%22%2C%22orderby%22%3A%22t.id%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22System_App%22%2C%22table_fields%22%3A%5B%22id%22%2C%22name%22%2C%22path%22%2C%22image%22%2C%22tooltip%22%2C%22front_end_app%22%2C%22active%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22system_app%22%2C%22field_selection%22%3A%22t.id%2Ct.name%2Ct.path%2Ct.image%2Ct.tooltip%2Ct.front_end_app%2Ct.active%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%2220%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%2220%22%7D%2C%22id%22%3A%2215%22%2C%22name%22%3A%22Front+End%22%2C%22path%22%3A%22%22%2C%22image%22%3A%22tasks%22%2C%22tooltip%22%3A%22%22%2C%22front_end_app%22%3A%221%22%2C%22active%22%3A%221%22%2C%22sql_where%22%3A%5B%22WHERE+t.system_app_id+%3D+%3F%22%2C%7B%22system_app_id%22%3A%2215%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22%22%7D'),(147,0,'2021-04-28 19:56:31','system_app','16','\r\n            <table class=\"action_form separator\" style=\"width: 600px;\"><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">App Name</th>\r\n\r\n                <td class=\"\">HL7</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Path</th>\r\n\r\n                <td class=\"\">app_hl7/</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Active</th>\r\n\r\n                <td class=\"\">\r\n            \r\n            \r\n        </td>\r\n              </tr></table>','%7B%22table%22%3A%22system_apps%22%2C%22orderby%22%3A%22t.id%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22System_App%22%2C%22table_fields%22%3A%5B%22id%22%2C%22name%22%2C%22path%22%2C%22image%22%2C%22tooltip%22%2C%22front_end_app%22%2C%22active%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22system_app%22%2C%22field_selection%22%3A%22t.id%2Ct.name%2Ct.path%2Ct.image%2Ct.tooltip%2Ct.front_end_app%2Ct.active%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%2220%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%2220%22%7D%2C%22id%22%3A%2216%22%2C%22name%22%3A%22HL7%22%2C%22path%22%3A%22app_hl7%5C%2F%22%2C%22image%22%3A%22envelope-o%22%2C%22tooltip%22%3A%22%22%2C%22front_end_app%22%3A%220%22%2C%22active%22%3A%221%22%2C%22sql_where%22%3A%5B%22WHERE+t.system_app_id+%3D+%3F%22%2C%7B%22system_app_id%22%3A%2216%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22%22%7D'),(148,0,'2021-04-28 19:56:36','system_app','17','\r\n            <table class=\"action_form separator\" style=\"width: 600px;\"><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">App Name</th>\r\n\r\n                <td class=\"\">Data</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Path</th>\r\n\r\n                <td class=\"\">app_data/</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Active</th>\r\n\r\n                <td class=\"\">\r\n            \r\n            \r\n        </td>\r\n              </tr></table>','%7B%22table%22%3A%22system_apps%22%2C%22orderby%22%3A%22t.id%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22System_App%22%2C%22table_fields%22%3A%5B%22id%22%2C%22name%22%2C%22path%22%2C%22image%22%2C%22tooltip%22%2C%22front_end_app%22%2C%22active%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22system_app%22%2C%22field_selection%22%3A%22t.id%2Ct.name%2Ct.path%2Ct.image%2Ct.tooltip%2Ct.front_end_app%2Ct.active%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%2220%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%2220%22%7D%2C%22id%22%3A%2217%22%2C%22name%22%3A%22Data%22%2C%22path%22%3A%22app_data%5C%2F%22%2C%22image%22%3A%22bed%22%2C%22tooltip%22%3A%22%22%2C%22front_end_app%22%3A%220%22%2C%22active%22%3A%221%22%2C%22sql_where%22%3A%5B%22WHERE+t.system_app_id+%3D+%3F%22%2C%7B%22system_app_id%22%3A%2217%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22%22%7D'),(149,0,'2021-04-28 19:56:45','system_app','18','\r\n            <table class=\"action_form separator\" style=\"width: 600px;\"><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">App Name</th>\r\n\r\n                <td class=\"\">TOG Admin</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Path</th>\r\n\r\n                <td class=\"\">app_tog_admin/</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Active</th>\r\n\r\n                <td class=\"\">\r\n            \r\n            \r\n        </td>\r\n              </tr></table>','%7B%22table%22%3A%22system_apps%22%2C%22orderby%22%3A%22t.id%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22System_App%22%2C%22table_fields%22%3A%5B%22id%22%2C%22name%22%2C%22path%22%2C%22image%22%2C%22tooltip%22%2C%22front_end_app%22%2C%22active%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22system_app%22%2C%22field_selection%22%3A%22t.id%2Ct.name%2Ct.path%2Ct.image%2Ct.tooltip%2Ct.front_end_app%2Ct.active%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%2220%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%2220%22%7D%2C%22id%22%3A%2218%22%2C%22name%22%3A%22TOG+Admin%22%2C%22path%22%3A%22app_tog_admin%5C%2F%22%2C%22image%22%3A%22hospital-o%22%2C%22tooltip%22%3A%22%22%2C%22front_end_app%22%3A%220%22%2C%22active%22%3A%221%22%2C%22sql_where%22%3A%5B%22WHERE+t.system_app_id+%3D+%3F%22%2C%7B%22system_app_id%22%3A%2218%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22%22%7D'),(150,0,'2021-04-28 20:00:15','system_app','14','\r\n            <table class=\"action_form separator\" style=\"width: 600px;\"><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">App Name</th>\r\n\r\n                <td class=\"\">Configuration</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Path</th>\r\n\r\n                <td class=\"\">app_config/</td>\r\n              </tr><tr>\r\n                <th style=\"min-width: 200px; width: 200px; text-align: right;\">Active</th>\r\n\r\n                <td class=\"\">\r\n            \r\n            \r\n        </td>\r\n              </tr></table>','%7B%22table%22%3A%22system_apps%22%2C%22orderby%22%3A%22t.id%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22System_App%22%2C%22table_fields%22%3A%5B%22id%22%2C%22name%22%2C%22path%22%2C%22image%22%2C%22tooltip%22%2C%22front_end_app%22%2C%22active%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22system_app%22%2C%22field_selection%22%3A%22t.id%2Ct.name%2Ct.path%2Ct.image%2Ct.tooltip%2Ct.front_end_app%2Ct.active%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%2220%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%2220%22%7D%2C%22id%22%3A%2214%22%2C%22name%22%3A%22Configuration%22%2C%22path%22%3A%22app_config%5C%2F%22%2C%22image%22%3A%22sliders%22%2C%22tooltip%22%3A%22%22%2C%22front_end_app%22%3A%220%22%2C%22active%22%3A%221%22%2C%22sql_where%22%3A%5B%22WHERE+t.system_app_id+%3D+%3F%22%2C%7B%22system_app_id%22%3A%2214%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22%22%7D'),(151,182,'2021-05-03 15:43:45','admission','2',NULL,'%7B%22table%22%3A%22admissions%22%2C%22orderby%22%3A%22t.date%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22Admission%22%2C%22table_fields%22%3A%5B%22id%22%2C%22admission_id%22%2C%22patient_id%22%2C%22date%22%2C%22facility_id%22%2C%22ward_id%22%2C%22bed%22%2C%22room%22%2C%22physician%22%2C%22comment%22%2C%22discharged%22%2C%22discharge_date%22%2C%22created_by%22%2C%22created_time%22%2C%22modified_by%22%2C%22modified_time%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22admission%22%2C%22field_selection%22%3A%22t.id%2Ct.admission_id%2Ct.patient_id%2Ct.date%2Ct.facility_id%2Ct.ward_id%2Ct.bed%2Ct.room%2Ct.physician%2Ct.comment%2Ct.discharged%2Ct.discharge_date%2Ct.created_by%2Ct.created_time%2Ct.modified_by%2Ct.modified_time%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%22100%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%22100%22%7D%2C%22id%22%3A%222%22%2C%22admission_id%22%3A%22%22%2C%22patient_id%22%3A%228%22%2C%22date%22%3A%222021-05-03%22%2C%22facility_id%22%3A%221%22%2C%22ward_id%22%3A%221%22%2C%22bed%22%3A%223%22%2C%22room%22%3A%22A%22%2C%22physician%22%3A%22%22%2C%22comment%22%3A%22%22%2C%22discharged%22%3A%22%22%2C%22discharge_date%22%3A%22%22%2C%22created_by%22%3A%22182%22%2C%22created_time%22%3A%222021-05-03+15%3A42%3A41%22%2C%22modified_by%22%3A%22%22%2C%22modified_time%22%3A%22%22%2C%22fullname%22%3A%22sss%2C+ssss%22%2C%22dob%22%3A%222011-05-03%22%2C%22deceased_date%22%3A%22%22%2C%22facility_name%22%3A%22Fa001+-+Cardiology+Unit%22%2C%22ward_name%22%3A%22W001+-+East+Wing%22%2C%22created_by_name%22%3A%22Test+User%22%2C%22modified_by_name%22%3A%22%22%2C%22sql_where%22%3A%5B%22WHERE+t.admission_id+%3D+%3F%22%2C%7B%22admission_id%22%3A%222%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22app_application%5C%2F%22%7D'),(152,182,'2021-05-03 15:53:05','admission','3',NULL,'%7B%22table%22%3A%22admissions%22%2C%22orderby%22%3A%22t.date%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22Admission%22%2C%22table_fields%22%3A%5B%22id%22%2C%22admission_id%22%2C%22patient_id%22%2C%22date%22%2C%22facility_id%22%2C%22ward_id%22%2C%22bed%22%2C%22room%22%2C%22physician%22%2C%22comment%22%2C%22discharged%22%2C%22discharge_date%22%2C%22created_by%22%2C%22created_time%22%2C%22modified_by%22%2C%22modified_time%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22admission%22%2C%22field_selection%22%3A%22t.id%2Ct.admission_id%2Ct.patient_id%2Ct.date%2Ct.facility_id%2Ct.ward_id%2Ct.bed%2Ct.room%2Ct.physician%2Ct.comment%2Ct.discharged%2Ct.discharge_date%2Ct.created_by%2Ct.created_time%2Ct.modified_by%2Ct.modified_time%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%22100%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%22100%22%7D%2C%22id%22%3A%223%22%2C%22admission_id%22%3A%22%22%2C%22patient_id%22%3A%229%22%2C%22date%22%3A%222021-05-03%22%2C%22facility_id%22%3A%221%22%2C%22ward_id%22%3A%221%22%2C%22bed%22%3A%222%22%2C%22room%22%3A%22R1%22%2C%22physician%22%3A%22Agith+Kumara%22%2C%22comment%22%3A%22Chest+Pain%22%2C%22discharged%22%3A%22%22%2C%22discharge_date%22%3A%22%22%2C%22created_by%22%3A%22182%22%2C%22created_time%22%3A%222021-05-03+15%3A49%3A23%22%2C%22modified_by%22%3A%22%22%2C%22modified_time%22%3A%22%22%2C%22fullname%22%3A%22Fonseka%2C+Kamal%22%2C%22dob%22%3A%221981-05-21%22%2C%22deceased_date%22%3A%22%22%2C%22facility_name%22%3A%22Fa001+-+Cardiology+Unit%22%2C%22ward_name%22%3A%22W001+-+East+Wing%22%2C%22created_by_name%22%3A%22Test+User%22%2C%22modified_by_name%22%3A%22%22%2C%22sql_where%22%3A%5B%22WHERE+t.admission_id+%3D+%3F%22%2C%7B%22admission_id%22%3A%223%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22app_application%5C%2F%22%7D'),(153,182,'2021-05-03 16:55:09','admission','4',NULL,'%7B%22table%22%3A%22admissions%22%2C%22orderby%22%3A%22t.date%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22Admission%22%2C%22table_fields%22%3A%5B%22id%22%2C%22admission_id%22%2C%22patient_id%22%2C%22date%22%2C%22facility_id%22%2C%22ward_id%22%2C%22bed%22%2C%22room%22%2C%22physician%22%2C%22comment%22%2C%22discharged%22%2C%22discharge_date%22%2C%22created_by%22%2C%22created_time%22%2C%22modified_by%22%2C%22modified_time%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22admission%22%2C%22field_selection%22%3A%22t.id%2Ct.admission_id%2Ct.patient_id%2Ct.date%2Ct.facility_id%2Ct.ward_id%2Ct.bed%2Ct.room%2Ct.physician%2Ct.comment%2Ct.discharged%2Ct.discharge_date%2Ct.created_by%2Ct.created_time%2Ct.modified_by%2Ct.modified_time%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%22100%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%22100%22%7D%2C%22id%22%3A%224%22%2C%22admission_id%22%3A%22%22%2C%22patient_id%22%3A%229%22%2C%22date%22%3A%222021-05-03%22%2C%22facility_id%22%3A%221%22%2C%22ward_id%22%3A%221%22%2C%22bed%22%3A%221%22%2C%22room%22%3A%22R1%22%2C%22physician%22%3A%22Agith+Kumara%22%2C%22comment%22%3A%22Chest+Pain.%22%2C%22discharged%22%3A%221%22%2C%22discharge_date%22%3A%222021-05-03%22%2C%22created_by%22%3A%22182%22%2C%22created_time%22%3A%222021-05-03+15%3A53%3A43%22%2C%22modified_by%22%3A%22182%22%2C%22modified_time%22%3A%222021-05-03+16%3A50%3A04%22%2C%22fullname%22%3A%22Fonseka%2C+Kamal%22%2C%22dob%22%3A%221981-05-21%22%2C%22deceased_date%22%3A%22%22%2C%22facility_name%22%3A%22Fa001+-+Cardiology+Unit%22%2C%22ward_name%22%3A%22W001+-+East+Wing%22%2C%22created_by_name%22%3A%22Test+User%22%2C%22modified_by_name%22%3A%22Test+User%22%2C%22sql_where%22%3A%5B%22WHERE+t.admission_id+%3D+%3F%22%2C%7B%22admission_id%22%3A%224%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22app_application%5C%2F%22%7D'),(154,182,'2021-05-03 16:55:53','admission','5',NULL,'%7B%22table%22%3A%22admissions%22%2C%22orderby%22%3A%22t.date%22%2C%22allow_pk_insert%22%3Afalse%2C%22system_log%22%3A%7B%7D%2C%22system_wastebasket%22%3A%7B%7D%2C%22class_name%22%3A%22Admission%22%2C%22table_fields%22%3A%5B%22id%22%2C%22admission_id%22%2C%22patient_id%22%2C%22date%22%2C%22facility_id%22%2C%22ward_id%22%2C%22bed%22%2C%22room%22%2C%22physician%22%2C%22comment%22%2C%22discharged%22%2C%22discharge_date%22%2C%22created_by%22%2C%22created_time%22%2C%22modified_by%22%2C%22modified_time%22%5D%2C%22database%22%3Anull%2C%22object_pk%22%3A%22id%22%2C%22object_name%22%3A%22admission%22%2C%22field_selection%22%3A%22t.id%2Ct.admission_id%2Ct.patient_id%2Ct.date%2Ct.facility_id%2Ct.ward_id%2Ct.bed%2Ct.room%2Ct.physician%2Ct.comment%2Ct.discharged%2Ct.discharge_date%2Ct.created_by%2Ct.created_time%2Ct.modified_by%2Ct.modified_time%22%2C%22groupby%22%3A%22%22%2C%22page%22%3A1%2C%22num_on_page%22%3A%22100%22%2C%22allowed_delete_from_tables%22%3A%5B%5D%2C%22offset%22%3A0%2C%22limit%22%3A%7B%22offset%22%3A0%2C%22num_on_page%22%3A%22100%22%7D%2C%22id%22%3A%225%22%2C%22admission_id%22%3A%22%22%2C%22patient_id%22%3A%229%22%2C%22date%22%3A%222021-05-04%22%2C%22facility_id%22%3A%221%22%2C%22ward_id%22%3A%221%22%2C%22bed%22%3A%221%22%2C%22room%22%3A%22R1%22%2C%22physician%22%3A%22%22%2C%22comment%22%3A%22%22%2C%22discharged%22%3A%22%22%2C%22discharge_date%22%3A%22%22%2C%22created_by%22%3A%22182%22%2C%22created_time%22%3A%222021-05-03+16%3A54%3A54%22%2C%22modified_by%22%3A%22%22%2C%22modified_time%22%3A%22%22%2C%22fullname%22%3A%22Fonseka%2C+Kamal%22%2C%22dob%22%3A%221981-05-21%22%2C%22deceased_date%22%3A%22%22%2C%22facility_name%22%3A%22Fa001+-+Cardiology+Unit%22%2C%22ward_name%22%3A%22W001+-+East+Wing%22%2C%22created_by_name%22%3A%22Test+User%22%2C%22modified_by_name%22%3A%22%22%2C%22sql_where%22%3A%5B%22WHERE+t.admission_id+%3D+%3F%22%2C%7B%22admission_id%22%3A%225%22%7D%2C%5B%22integer%22%5D%5D%2C%22class_app%22%3A%22app_application%5C%2F%22%7D');
/*!40000 ALTER TABLE `system_wastebasket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `test_app_system_settings`
--

DROP TABLE IF EXISTS `test_app_system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `test_app_system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `type` varchar(20) NOT NULL,
  `values` text,
  `value` text,
  `comment` text,
  `module` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_app_system_settings`
--

LOCK TABLES `test_app_system_settings` WRITE;
/*!40000 ALTER TABLE `test_app_system_settings` DISABLE KEYS */;
INSERT INTO `test_app_system_settings` VALUES (1,'Use Delay Codes?','Selection','Optional, Mandatory, No','Optional',NULL,NULL,0,'2015-01-08 11:43:12',0,'2015-06-16 18:01:26'),(2,'Titles','Text',NULL,'Dr, Mr, Mrs, Ms, Mstr',NULL,NULL,0,'2015-01-08 11:50:19',0,'2015-01-08 11:52:46'),(3,'Physician Types','Text',NULL,'A, B, C',NULL,NULL,0,'2015-01-08 11:55:27',NULL,NULL),(5,'Enable \"All met\" shortcut','Yes/No',NULL,'1',NULL,NULL,0,'2015-06-01 17:36:16',0,'2015-06-19 18:32:10'),(6,'Maximum number of reasons or delays','Number',NULL,'3',NULL,NULL,0,'2015-06-02 13:22:15',NULL,NULL),(10,'Delete untouched reviews on discharge?','Selection','Yes, Ask, No','Yes',NULL,NULL,0,'2015-06-02 13:27:24',0,'2015-06-02 13:27:42'),(11,'Delete incomplete reviews on discharge?','Selection','Yes, Ask, No','Yes',NULL,NULL,0,'2015-06-02 13:28:43',NULL,NULL),(14,'Criterion\'s sub elements are required','Yes/No',NULL,'1',NULL,NULL,0,'2015-06-09 14:53:07',0,'2015-06-19 18:25:11'),(15,'Auto-create next review on review creation?','Yes/No',NULL,'1',NULL,NULL,0,'2015-06-16 17:33:22',0,'2015-06-17 16:01:02'),(16,'Custom fields should inherit values?','Yes/No',NULL,'0','',NULL,0,'2015-07-29 17:00:25',NULL,NULL),(17,'Delete incomplete reviews on discharge via A03 message?','Yes/No',NULL,'1',NULL,NULL,0,'2015-12-08 18:08:26',NULL,NULL),(18,'Delete untouched reviews on discharge via A03 message?','Yes/No',NULL,'1',NULL,NULL,0,'2015-12-08 18:09:01',NULL,NULL),(19,'Allow Mark as Deceased','Yes/No',NULL,NULL,NULL,NULL,0,'2016-04-19 00:00:00',NULL,NULL),(20,'Allow Create New Patient','Yes/No',NULL,'1',NULL,NULL,0,'2016-05-16 19:56:01',184,'2020-09-14 17:07:37'),(21,'Allow Create New Encounter','Yes/No',NULL,'1',NULL,NULL,0,'2016-05-16 19:56:01',184,'2020-09-14 17:07:31'),(22,'Output A08 message on Review completion','Yes/No',NULL,'0',NULL,NULL,0,'2016-08-17 02:00:54',NULL,NULL),(23,'Output A08 message - Z segment name extension','String',NULL,'CU',NULL,NULL,0,'2016-08-17 02:00:54',NULL,NULL),(24,'Update past incomplete reviews when HL7 is updating Encounter','Yes/No',NULL,'1',NULL,NULL,0,'2016-08-17 02:03:07',0,'2017-03-13 14:45:30'),(25,'Assign Consultant with Facility on creation','Yes/No',NULL,'0',NULL,NULL,0,'2016-08-17 02:03:26',184,'2018-06-19 12:40:25'),(26,'Password Expiry: Enable','Yes/No',NULL,'0','Enable password expiring',NULL,0,'2016-08-17 02:03:26',NULL,NULL),(27,'Password Expiry: Validity','Selection','+1 day, +1 week, +1 month, +2 months, +3 months, +6 months','+6 months','Maximum valididy for a password',NULL,0,'2016-08-17 02:03:26',NULL,NULL),(28,'Password Expiry: Previous Passwords','Number',NULL,'5','Number of previous passwords prevented (can also take zero to not check previous passwords, or any negative value to check all)',NULL,0,'2016-08-17 02:03:26',NULL,NULL),(29,'Enable pings','Yes/No',NULL,'0','Switching on starts regular server pings from open browser pages. If delete session cron job is running this will logout users after browser close',NULL,0,'2016-12-09 02:26:35',NULL,NULL),(30,'Ping interval','Number',NULL,'30','Ping interval in seconds',NULL,0,'2016-12-09 02:26:35',NULL,NULL),(31,'Discharge Screening','Yes/No',NULL,NULL,NULL,'TOG Admin',0,'2018-09-27 20:43:49',NULL,NULL),(32,'Assign Clinical Service with Facility on creation','Yes/No',NULL,'1',NULL,NULL,0,'2019-08-01 20:41:04',NULL,NULL),(33,'Enable \"None of these apply\" Criteria option','Yes/No',NULL,'1',NULL,'TOG Admin',0,'2020-07-13 09:59:47',NULL,NULL),(34,'Force test Criteria in NHSE/I','Yes/No',NULL,'1',NULL,'TOG Admin',0,'2020-07-13 09:59:47',NULL,NULL),(35,'Login failure message','Text',NULL,NULL,NULL,'TOG Admin',0,'2020-07-13 10:00:19',NULL,NULL),(36,'Show NHS Number','Yes/No',NULL,'1',NULL,NULL,0,'2020-07-13 10:03:11',NULL,NULL),(37,'Show CCG','Yes/No',NULL,'1',NULL,NULL,0,'2020-07-13 10:03:11',NULL,NULL),(38,'Show Room','Yes/No',NULL,'1',NULL,NULL,0,'2020-07-13 10:03:11',NULL,NULL),(39,'Show Bed','Yes/No',NULL,'1',NULL,NULL,0,'2020-07-13 10:03:11',NULL,NULL),(40,'Show Local Authority','Yes/No',NULL,'1',NULL,NULL,0,'2020-07-13 10:03:11',NULL,NULL),(41,'Landing Page','Selection','patient_search,dashboard','patient_search',NULL,NULL,0,'2020-07-13 10:03:11',NULL,NULL),(42,'Default ward security','Selection','All, None','All',NULL,'TOG Admin',0,'2020-09-23 11:00:39',184,'2020-09-29 14:59:08'),(43,'Restrict NHSE/I Discharge Report to NHSE/I Criteria','Yes/No',NULL,'1',NULL,'TOG Admin',0,'2020-09-28 16:18:35',NULL,NULL),(44,'Allow Next Review Date Selection for Inpatient Reviews','Yes/No',NULL,'1',NULL,'TOG Admin',0,'2020-10-02 13:59:49',NULL,NULL),(45,'Allow Next Review Date Selection for Outpatient Reviews','Yes/No',NULL,'1',NULL,'TOG Admin',0,'2020-10-02 13:59:49',NULL,NULL),(46,'Maximum Days between Review Dates for Inpatients','Number',NULL,'1',NULL,'TOG Admin',0,'2020-10-02 13:59:49',NULL,NULL),(47,'Maximum Days between Review Dates for Outpatients','Number',NULL,'1',NULL,'TOG Admin',0,'2020-10-02 13:59:49',NULL,NULL);
/*!40000 ALTER TABLE `test_app_system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `user_accessible_wards_and_locations`
--

DROP TABLE IF EXISTS `user_accessible_wards_and_locations`;
/*!50001 DROP VIEW IF EXISTS `user_accessible_wards_and_locations`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `user_accessible_wards_and_locations` AS SELECT 
 1 AS `user_id`,
 1 AS `hospital_id`,
 1 AS `facility_id`,
 1 AS `ward_id`,
 1 AS `location_id`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `user_ward_security_groups_links`
--

DROP TABLE IF EXISTS `user_ward_security_groups_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_ward_security_groups_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UQ_ward_security_group_user` (`group_id`,`user_id`),
  UNIQUE KEY `UQ_user_group_ward_security` (`user_id`,`group_id`),
  CONSTRAINT `FK_user_ward_security_groups_links_group_id` FOREIGN KEY (`group_id`) REFERENCES `ward_security_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_user_ward_security_groups_links_user_id` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_ward_security_groups_links_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_ward_security_groups_links_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `ward_security_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_ward_security_groups_links`
--

LOCK TABLES `user_ward_security_groups_links` WRITE;
/*!40000 ALTER TABLE `user_ward_security_groups_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_ward_security_groups_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `view_audit`
--

DROP TABLE IF EXISTS `view_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `view_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `encounter_id` int(11) DEFAULT NULL,
  `review_id` int(11) DEFAULT NULL,
  `date_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_view_audit_source_id` (`source_id`),
  KEY `FK_view_audit_user_id` (`user_id`),
  CONSTRAINT `FK_view_audit_source_id` FOREIGN KEY (`source_id`) REFERENCES `view_audit_sources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_view_audit_user_id` FOREIGN KEY (`user_id`) REFERENCES `system_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `view_audit`
--

LOCK TABLES `view_audit` WRITE;
/*!40000 ALTER TABLE `view_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `view_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `view_audit_sources`
--

DROP TABLE IF EXISTS `view_audit_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `view_audit_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` int(5) NOT NULL,
  `class_name` varchar(200) NOT NULL,
  `method_name` varchar(200) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_UNIQUE` (`code`),
  KEY `UQ_id_code` (`id`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `view_audit_sources`
--

LOCK TABLES `view_audit_sources` WRITE;
/*!40000 ALTER TABLE `view_audit_sources` DISABLE KEYS */;
/*!40000 ALTER TABLE `view_audit_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ward_security_groups`
--

DROP TABLE IF EXISTS `ward_security_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ward_security_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ward_security_groups`
--

LOCK TABLES `ward_security_groups` WRITE;
/*!40000 ALTER TABLE `ward_security_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `ward_security_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ward_security_groups_permissions`
--

DROP TABLE IF EXISTS `ward_security_groups_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ward_security_groups_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ward_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ward_id_group_id` (`ward_id`,`group_id`),
  KEY `UQ_ward_group` (`ward_id`,`group_id`),
  KEY `UQ_group_ward` (`group_id`,`ward_id`),
  CONSTRAINT `FK_ward_security_groups_permissions_group_id` FOREIGN KEY (`group_id`) REFERENCES `ward_security_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_ward_security_groups_permissions_ward_id` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ward_security_groups_permissions`
--

LOCK TABLES `ward_security_groups_permissions` WRITE;
/*!40000 ALTER TABLE `ward_security_groups_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ward_security_groups_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wards`
--

DROP TABLE IF EXISTS `wards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hl7_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facility_id` int(11) NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cquin_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nhsi_report` tinyint(4) DEFAULT NULL,
  `comment` text COLLATE utf8_unicode_ci,
  `active` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_time` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_wards_facility_id` (`facility_id`),
  CONSTRAINT `FK_wards_facility_id` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `wards_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`),
  CONSTRAINT `wards_ibfk_2` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wards`
--

LOCK TABLES `wards` WRITE;
/*!40000 ALTER TABLE `wards` DISABLE KEYS */;
INSERT INTO `wards` VALUES (1,'W001',1,'East Wing',NULL,NULL,NULL,1,182,'2021-05-03 06:06:49',NULL,NULL),(2,'W002',1,'West Wing',NULL,NULL,NULL,1,182,'2021-05-03 06:07:08',NULL,NULL);
/*!40000 ALTER TABLE `wards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `system_user_groups_view`
--

/*!50001 DROP VIEW IF EXISTS `system_user_groups_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `system_user_groups_view` AS select 1 AS `user_id`,1 AS `group_ids`,1 AS `group_names` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `user_accessible_wards_and_locations`
--

/*!50001 DROP VIEW IF EXISTS `user_accessible_wards_and_locations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `user_accessible_wards_and_locations` AS select `u`.`user_id` AS `user_id`,`h`.`id` AS `hospital_id`,`f`.`id` AS `facility_id`,`w`.`id` AS `ward_id`,NULL AS `location_id` from (((((`php_test`.`ward_security_groups` `t` join `php_test`.`ward_security_groups_permissions` `p` on((`p`.`group_id` = `t`.`id`))) join `php_test`.`user_ward_security_groups_links` `u` on((`u`.`group_id` = `t`.`id`))) join `php_test`.`wards` `w` on(((`w`.`id` = `p`.`ward_id`) and (`w`.`active` = 1)))) join `php_test`.`facilities` `f` on(((`w`.`facility_id` = `f`.`id`) and (`f`.`active` = 1)))) join `php_test`.`hospitals` `h` on(((`h`.`id` = `f`.`hospital_id`) and (`h`.`active` = 1)))) union select `u`.`user_id` AS `user_id`,`h`.`id` AS `hospital_id`,`f`.`id` AS `facility_id`,NULL AS `ward_id`,`l`.`location_id` AS `location_id` from (((((`php_test`.`ward_security_groups` `t` join `php_test`.`location_security_groups_permissions` `l` on((`l`.`group_id` = `t`.`id`))) join `php_test`.`user_ward_security_groups_links` `u` on((`u`.`group_id` = `t`.`id`))) join `php_test`.`locations` `w` on(((`l`.`location_id` = `w`.`id`) and (`w`.`active` = 1)))) join `php_test`.`facilities` `f` on(((`w`.`facility_id` = `f`.`id`) and (`f`.`active` = 1)))) join `php_test`.`hospitals` `h` on(((`h`.`id` = `f`.`hospital_id`) and (`h`.`active` = 1)))) union select `u`.`id` AS `user_id`,`h`.`id` AS `hospital_id`,`f`.`id` AS `facility_id`,`w`.`id` AS `ward_id`,NULL AS `location_id` from ((((`php_test`.`system_users` `u` left join `php_test`.`user_ward_security_groups_links` `l` on((`u`.`id` = `l`.`user_id`))) join (select `b`.`id` AS `id`,`b`.`hl7_id` AS `hl7_id`,`b`.`facility_id` AS `facility_id`,`b`.`name` AS `name`,`b`.`cquin_type` AS `cquin_type`,`b`.`nhsi_report` AS `nhsi_report`,`b`.`comment` AS `comment`,`b`.`active` AS `active`,`b`.`created_by` AS `created_by`,`b`.`created_time` AS `created_time`,`b`.`modified_by` AS `modified_by`,`b`.`modified_time` AS `modified_time`,`c`.`value` AS `default_security` from (`php_test`.`wards` `b` join `php_test`.`test_app_system_settings` `c` on(((`c`.`name` = 'Default ward security') and (`c`.`value` = 'All'))))) `w`) join `php_test`.`facilities` `f` on(((`w`.`facility_id` = `f`.`id`) and (`f`.`active` = 1)))) join `php_test`.`hospitals` `h` on(((`h`.`id` = `f`.`hospital_id`) and (`h`.`active` = 1)))) where (isnull(`l`.`id`) and (`u`.`id` <> 0) and (`w`.`active` = 1) and (`w`.`default_security` = 'All')) union select `u`.`id` AS `user_id`,`h`.`id` AS `hospital_id`,`f`.`id` AS `facility_id`,NULL AS `ward_id`,`w`.`id` AS `location_id` from ((((`php_test`.`system_users` `u` left join `php_test`.`user_ward_security_groups_links` `l` on((`u`.`id` = `l`.`user_id`))) join (select `b`.`id` AS `id`,`b`.`hl7_id` AS `hl7_id`,`b`.`name` AS `name`,`b`.`facility_id` AS `facility_id`,`b`.`active` AS `active`,`b`.`created_by` AS `created_by`,`b`.`created_time` AS `created_time`,`b`.`modified_by` AS `modified_by`,`b`.`modified_time` AS `modified_time`,`c`.`value` AS `default_security` from (`php_test`.`locations` `b` join `php_test`.`test_app_system_settings` `c` on(((`c`.`name` = 'Default ward security') and (`c`.`value` = 'All'))))) `w`) join `php_test`.`facilities` `f` on(((`w`.`facility_id` = `f`.`id`) and (`f`.`active` = 1)))) join `php_test`.`hospitals` `h` on(((`h`.`id` = `f`.`hospital_id`) and (`h`.`active` = 1)))) where (isnull(`l`.`id`) and (`u`.`id` <> 0) and (`w`.`active` = 1) and (`w`.`default_security` = 'All')) */;
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

-- Dump completed on 2021-05-03 21:10:28
