-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: grad_system
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `registrar_signals`
--

DROP TABLE IF EXISTS `registrar_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_signals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `hold_type` varchar(64) NOT NULL,
  `term_code` varchar(32) DEFAULT NULL,
  `code` varchar(64) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `payload` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_registrar_signals_code` (`code`),
  KEY `idx_registrar_signals_student` (`student_id`),
  KEY `idx_registrar_signals_hold` (`hold_type`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_signals`
--

LOCK TABLES `registrar_signals` WRITE;
/*!40000 ALTER TABLE `registrar_signals` DISABLE KEYS */;
INSERT INTO `registrar_signals` VALUES (4,11,'admission_letter',NULL,'GRAD-BD0A55DC4C3E6FFCA3BD528D',8,'{\"doc_id\":\"5\",\"doc_type\":\"admission_letter\",\"action\":\"approve\"}','2025-12-14 11:23:14',NULL),(5,11,'major_professor',NULL,'GRAD-47420D4FCE2C344B45E96BB1',2,'{\"action\":\"accept\",\"major_professor_id\":\"2\"}','2025-12-14 11:24:24',NULL),(6,11,'major_professor_form',NULL,'GRAD-8B6A3DC95D5BC33CE9A02160',2,'{\"doc_id\":\"6\",\"doc_type\":\"major_professor_form\",\"action\":\"approve\"}','2025-12-14 11:38:20',NULL),(7,11,'major_professor_form',NULL,'GRAD-10139180933EB77E34766372',2,'{\"doc_id\":\"8\",\"doc_type\":\"major_professor_form\",\"action\":\"approve\"}','2025-12-14 11:52:15',NULL),(9,11,'research_method',NULL,'GRAD-9CF36D80A548EBA085A509FB',2,'{\"action\":\"faculty_approve_research_method_proof\",\"doc_id\":\"12\",\"course_code\":\"CS690\"}','2025-12-15 22:27:28',NULL),(10,15,'major_professor',NULL,'GRAD-ACB56AADE25FF3D80347CB9E',2,'{\"action\":\"accept\",\"major_professor_id\":\"2\"}','2025-12-16 09:27:05',NULL),(11,15,'research_method',NULL,'GRAD-802981D792B8B1EB051D54C9',2,'{\"action\":\"faculty_approve_research_method_proof\",\"doc_id\":\"16\",\"course_code\":\"CS690\"}','2025-12-16 09:32:54',NULL),(12,10,'admission_letter','2025FA','GRAD-264A487CFA0238DEAE4B5F21',8,'{\"action\":\"lift_hold\",\"hold_type\":\"admission_letter\"}','2025-12-16 11:34:09',NULL),(13,16,'admission_letter',NULL,'GRAD-5A016052934D24FB54AAA7C6',8,'{\"action\":\"lift_hold\",\"hold_type\":\"admission_letter\",\"source\":\"admin_review_document\"}','2025-12-16 19:48:09',NULL),(14,15,'admission_letter',NULL,'GRAD-F574C382B3766FDA8268644D',8,'{\"action\":\"lift_hold\",\"hold_type\":\"admission_letter\",\"source\":\"admin_review_document\"}','2025-12-16 21:49:13',NULL),(20,20,'admission_letter',NULL,'GRAD-E5A91750FFDB60B618E687CC',8,'{\"action\":\"lift_hold\",\"hold_type\":\"admission_letter\",\"source\":\"admin_review_document\"}','2025-12-16 23:17:05',NULL),(21,20,'major_professor',NULL,'GRAD-E53DAFF0BCB8CA3DC8B6FEE4',2,'{\"action\":\"accept\",\"major_professor_id\":\"2\"}','2025-12-16 23:18:37',NULL),(23,21,'admission_letter',NULL,'GRAD-DE15DBCF89AD281F8AB174B1',8,'{\"action\":\"lift_hold\",\"hold_type\":\"admission_letter\",\"source\":\"admin_review_document\"}','2025-12-19 19:55:46',NULL);
/*!40000 ALTER TABLE `registrar_signals` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-19 21:51:44
