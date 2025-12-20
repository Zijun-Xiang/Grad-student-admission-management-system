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
-- Table structure for table `advisee_course_actions`
--

DROP TABLE IF EXISTS `advisee_course_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `advisee_course_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `faculty_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `action_type` enum('add','drop') NOT NULL,
  `course_code` varchar(32) NOT NULL,
  `comment` text,
  `status` enum('pending','applied','cancelled','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `applied_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `student_comment` text,
  PRIMARY KEY (`id`),
  KEY `idx_aca_student_status` (`student_id`,`status`,`created_at`),
  KEY `idx_aca_faculty_status` (`faculty_id`,`status`,`created_at`),
  KEY `idx_aca_course` (`course_code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `advisee_course_actions`
--

LOCK TABLES `advisee_course_actions` WRITE;
/*!40000 ALTER TABLE `advisee_course_actions` DISABLE KEYS */;
INSERT INTO `advisee_course_actions` VALUES (1,2,7,'add','CS 472',NULL,'applied','2025-12-16 20:33:24','2025-12-16 20:33:55',NULL,NULL,NULL),(2,2,7,'add','CS 460',NULL,'cancelled','2025-12-16 20:34:36',NULL,'2025-12-16 20:42:06',NULL,NULL),(3,2,9,'add','CS 445',NULL,'rejected','2025-12-16 20:43:44',NULL,NULL,'2025-12-16 20:59:54',NULL),(4,2,9,'add','CS 452',NULL,'cancelled','2025-12-16 21:00:49',NULL,'2025-12-16 21:01:31',NULL,NULL),(5,2,9,'add','CS 475',NULL,'pending','2025-12-16 21:01:59',NULL,NULL,NULL,NULL),(6,2,17,'add','CS 472',NULL,'applied','2025-12-16 22:29:10','2025-12-16 22:29:22',NULL,NULL,NULL),(7,2,18,'add','CS 445',NULL,'applied','2025-12-16 22:58:15','2025-12-16 22:58:29',NULL,NULL,NULL),(8,2,20,'add','CS690',NULL,'pending','2025-12-19 19:58:22',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `advisee_course_actions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-19 21:51:45
