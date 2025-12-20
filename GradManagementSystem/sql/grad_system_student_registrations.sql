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
-- Table structure for table `student_registrations`
--

DROP TABLE IF EXISTS `student_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT 'Fall 2025',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `student_registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_registrations`
--

LOCK TABLES `student_registrations` WRITE;
/*!40000 ALTER TABLE `student_registrations` DISABLE KEYS */;
INSERT INTO `student_registrations` VALUES (5,11,'CS 452','Fall 2025'),(6,11,'CS 460','Fall 2025'),(7,11,'CS 470','Fall 2025'),(8,11,'CS 472','Fall 2025'),(9,11,'CS 475','Fall 2025'),(20,11,'CS 445','Fall 2025'),(21,10,'CS270','Fall 2025'),(22,10,'CS290','Fall 2025'),(23,10,'CS 445','Fall 2025'),(24,10,'CS 452','Fall 2025'),(25,10,'CS 460','Fall 2025'),(26,16,'CS270','Fall 2025'),(27,16,'CS280','Fall 2025'),(28,16,'CS290','Fall 2025'),(29,16,'CS 511','Fall 2025'),(30,16,'CS 507','Fall 2025'),(36,15,'CS280','Fall 2025'),(37,15,'CS290','Fall 2025'),(38,15,'CS 507','Fall 2025'),(50,20,'CS290','Fall 2025'),(51,20,'CS 511','Fall 2025'),(52,20,'CS 507','Fall 2025'),(53,20,'CS 475','Fall 2025'),(54,21,'CS270','Fall 2025'),(55,21,'CS290','Fall 2025'),(56,21,'CS 511','Fall 2025'),(57,21,'CS 507','Fall 2025'),(58,21,'CS 475','Fall 2025'),(59,21,'CS 445','Fall 2025');
/*!40000 ALTER TABLE `student_registrations` ENABLE KEYS */;
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
