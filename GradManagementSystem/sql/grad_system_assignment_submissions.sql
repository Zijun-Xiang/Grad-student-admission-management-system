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
-- Table structure for table `assignment_submissions`
--

DROP TABLE IF EXISTS `assignment_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignment_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assignment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grade` decimal(6,2) DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `graded_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_submission` (`assignment_id`,`student_id`),
  KEY `idx_submissions_assignment` (`assignment_id`),
  KEY `idx_submissions_student` (`student_id`),
  KEY `idx_submissions_grade` (`grade`),
  CONSTRAINT `fk_assignment_submissions_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignment_submissions`
--

LOCK TABLES `assignment_submissions` WRITE;
/*!40000 ALTER TABLE `assignment_submissions` DISABLE KEYS */;
INSERT INTO `assignment_submissions` VALUES (1,1,7,'assignment_submissions/8a9a21457922d7ff82c85b9904c0df74.pdf','2025-12-14 16:40:09',NULL,NULL,NULL),(2,2,11,'assignment_submissions/f2aeb6c4751f527ab92be910a40b358a.pdf','2025-12-14 16:57:55',95.00,'2025-12-14 17:09:55',2);
/*!40000 ALTER TABLE `assignment_submissions` ENABLE KEYS */;
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
