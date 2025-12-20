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
-- Table structure for table `student_deficiencies`
--

DROP TABLE IF EXISTS `student_deficiencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_deficiencies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `status` enum('assigned','completed','waived') DEFAULT 'assigned',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_code` (`course_code`),
  CONSTRAINT `student_deficiencies_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `student_deficiencies_ibfk_2` FOREIGN KEY (`course_code`) REFERENCES `core_courses` (`course_code`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_deficiencies`
--

LOCK TABLES `student_deficiencies` WRITE;
/*!40000 ALTER TABLE `student_deficiencies` DISABLE KEYS */;
INSERT INTO `student_deficiencies` VALUES (20,10,'CS270','assigned'),(21,10,'CS290','assigned'),(22,16,'CS270','assigned'),(23,16,'CS280','assigned'),(24,16,'CS290','assigned'),(25,15,'CS280','assigned'),(26,15,'CS290','assigned'),(32,20,'CS290','assigned'),(33,21,'CS270','assigned'),(34,21,'CS290','assigned');
/*!40000 ALTER TABLE `student_deficiencies` ENABLE KEYS */;
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
