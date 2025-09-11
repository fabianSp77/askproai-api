/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.11-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: askproai_db
-- ------------------------------------------------------
-- Server version	10.11.11-MariaDB-0+deb12u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'super_admin','web','2025-05-14 14:49:56','2025-05-14 14:49:56');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'view_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(2,'view_any_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(3,'create_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(4,'update_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(5,'restore_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(6,'restore_any_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(7,'replicate_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(8,'reorder_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(9,'delete_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(10,'delete_any_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(11,'force_delete_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(12,'force_delete_any_appointment','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(13,'view_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(14,'view_any_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(15,'create_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(16,'update_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(17,'restore_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(18,'restore_any_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(19,'replicate_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(20,'reorder_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(21,'delete_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(22,'delete_any_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(23,'force_delete_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(24,'force_delete_any_booking','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(25,'view_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(26,'view_any_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(27,'create_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(28,'update_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(29,'restore_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(30,'restore_any_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(31,'replicate_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(32,'reorder_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(33,'delete_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(34,'delete_any_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(35,'force_delete_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(36,'force_delete_any_branch','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(37,'view_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(38,'view_any_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(39,'create_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(40,'update_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(41,'restore_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(42,'restore_any_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(43,'replicate_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(44,'reorder_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(45,'delete_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(46,'delete_any_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(47,'force_delete_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(48,'force_delete_any_call','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(49,'view_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(50,'view_any_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(51,'create_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(52,'update_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(53,'restore_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(54,'restore_any_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(55,'replicate_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(56,'reorder_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(57,'delete_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(58,'delete_any_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(59,'force_delete_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(60,'force_delete_any_company','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(61,'view_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(62,'view_any_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(63,'create_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(64,'update_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(65,'restore_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(66,'restore_any_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(67,'replicate_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(68,'reorder_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(69,'delete_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(70,'delete_any_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(71,'force_delete_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(72,'force_delete_any_customer','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(73,'view_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(74,'view_any_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(75,'create_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(76,'update_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(77,'restore_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(78,'restore_any_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(79,'replicate_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(80,'reorder_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(81,'delete_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(82,'delete_any_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(83,'force_delete_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(84,'force_delete_any_integration','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(85,'view_role','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(86,'view_any_role','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(87,'create_role','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(88,'update_role','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(89,'delete_role','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(90,'delete_any_role','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(91,'view_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(92,'view_any_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(93,'create_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(94,'update_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(95,'restore_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(96,'restore_any_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(97,'replicate_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(98,'reorder_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(99,'delete_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(100,'delete_any_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(101,'force_delete_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(102,'force_delete_any_service','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(103,'view_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(104,'view_any_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(105,'create_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(106,'update_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(107,'restore_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(108,'restore_any_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(109,'replicate_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(110,'reorder_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(111,'delete_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(112,'delete_any_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(113,'force_delete_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(114,'force_delete_any_staff','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(115,'view_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(116,'view_any_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(117,'create_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(118,'update_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(119,'restore_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(120,'restore_any_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(121,'replicate_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(122,'reorder_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(123,'delete_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(124,'delete_any_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(125,'force_delete_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(126,'force_delete_any_user','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(127,'view_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(128,'view_any_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(129,'create_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(130,'update_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(131,'restore_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(132,'restore_any_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(133,'replicate_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(134,'reorder_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(135,'delete_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(136,'delete_any_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(137,'force_delete_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(138,'force_delete_any_working::hour','web','2025-05-14 14:49:56','2025-05-14 14:49:56'),
(139,'view_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(140,'view_any_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(141,'create_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(142,'update_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(143,'restore_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(144,'restore_any_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(145,'replicate_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(146,'reorder_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(147,'delete_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(148,'delete_any_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(149,'force_delete_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(150,'force_delete_any_appointment','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(151,'view_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(152,'view_any_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(153,'create_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(154,'update_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(155,'restore_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(156,'restore_any_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(157,'replicate_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(158,'reorder_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(159,'delete_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(160,'delete_any_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(161,'force_delete_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(162,'force_delete_any_booking','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(163,'view_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(164,'view_any_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(165,'create_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(166,'update_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(167,'restore_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(168,'restore_any_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(169,'replicate_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(170,'reorder_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(171,'delete_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(172,'delete_any_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(173,'force_delete_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(174,'force_delete_any_branch','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(175,'view_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(176,'view_any_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(177,'create_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(178,'update_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(179,'restore_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(180,'restore_any_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(181,'replicate_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(182,'reorder_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(183,'delete_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(184,'delete_any_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(185,'force_delete_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(186,'force_delete_any_call','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(187,'view_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(188,'view_any_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(189,'create_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(190,'update_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(191,'restore_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(192,'restore_any_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(193,'replicate_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(194,'reorder_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(195,'delete_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(196,'delete_any_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(197,'force_delete_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(198,'force_delete_any_company','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(199,'view_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(200,'view_any_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(201,'create_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(202,'update_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(203,'restore_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(204,'restore_any_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(205,'replicate_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(206,'reorder_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(207,'delete_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(208,'delete_any_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(209,'force_delete_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(210,'force_delete_any_customer','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(211,'view_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(212,'view_any_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(213,'create_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(214,'update_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(215,'restore_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(216,'restore_any_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(217,'replicate_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(218,'reorder_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(219,'delete_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(220,'delete_any_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(221,'force_delete_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(222,'force_delete_any_integration','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(223,'view_role','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(224,'view_any_role','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(225,'create_role','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(226,'update_role','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(227,'delete_role','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(228,'delete_any_role','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(229,'view_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(230,'view_any_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(231,'create_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(232,'update_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(233,'restore_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(234,'restore_any_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(235,'replicate_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(236,'reorder_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(237,'delete_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(238,'delete_any_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(239,'force_delete_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(240,'force_delete_any_service','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(241,'view_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(242,'view_any_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(243,'create_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(244,'update_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(245,'restore_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(246,'restore_any_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(247,'replicate_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(248,'reorder_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(249,'delete_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(250,'delete_any_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(251,'force_delete_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(252,'force_delete_any_staff','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(253,'view_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(254,'view_any_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(255,'create_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(256,'update_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(257,'restore_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(258,'restore_any_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(259,'replicate_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(260,'reorder_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(261,'delete_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(262,'delete_any_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(263,'force_delete_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(264,'force_delete_any_user','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(265,'view_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(266,'view_any_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(267,'create_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(268,'update_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(269,'restore_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(270,'restore_any_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(271,'replicate_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(272,'reorder_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(273,'delete_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(274,'delete_any_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(275,'force_delete_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49'),
(276,'force_delete_any_working::hour','filament','2025-05-14 14:53:49','2025-05-14 14:53:49');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-18 14:21:03
