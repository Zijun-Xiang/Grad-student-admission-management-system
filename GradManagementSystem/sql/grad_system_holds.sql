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
-- Table structure for table `holds`
--

DROP TABLE IF EXISTS `holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holds` (
  `hold_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `hold_type` enum('admission_letter','major_professor','research_method','other') NOT NULL,
  `term_code` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`hold_id`),
  KEY `student_id` (`student_id`),
  KEY `idx_holds_term` (`term_code`),
  CONSTRAINT `holds_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holds`
--

LOCK TABLES `holds` WRITE;
/*!40000 ALTER TABLE `holds` DISABLE KEYS */;
INSERT INTO `holds` VALUES (4,10,'admission_letter','2025FA',0,'2025-12-16 11:34:09','2025-12-14 08:47:06'),(5,11,'admission_letter','2024FA',0,'2025-12-14 11:23:14','2025-12-14 19:22:20'),(9,15,'admission_letter','2025SP',0,'2025-12-16 21:49:13','2025-12-15 10:02:12'),(10,11,'research_method','2025FA',0,'2025-12-15 22:27:28','2025-12-15 10:23:32'),(11,15,'research_method','2025FA',0,'2025-12-16 09:32:54','2025-12-16 17:27:48'),(12,16,'admission_letter','2025FA',0,'2025-12-16 19:48:09','2025-12-17 03:47:29'),(17,20,'admission_letter','2024FA',0,'2025-12-16 23:17:05','2025-12-17 07:16:41'),(18,21,'admission_letter','2025FA',0,'2025-12-19 19:55:46','2025-12-20 03:28:43');
/*!40000 ALTER TABLE `holds` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-19 21:51:43
