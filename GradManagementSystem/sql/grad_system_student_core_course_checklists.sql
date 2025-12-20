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
-- Table structure for table `student_core_course_checklists`
--

DROP TABLE IF EXISTS `student_core_course_checklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_core_course_checklists` (
  `student_id` bigint unsigned NOT NULL,
  `completed_codes` text,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_core_course_checklists`
--

LOCK TABLES `student_core_course_checklists` WRITE;
/*!40000 ALTER TABLE `student_core_course_checklists` DISABLE KEYS */;
INSERT INTO `student_core_course_checklists` VALUES (1,'[]','2025-12-14 01:18:35'),(7,'[\"CS200\",\"CS210\",\"CS220\",\"CS230\",\"CS240\",\"CS260\",\"CS270\"]','2025-12-14 01:47:33'),(9,'[\"CS200\",\"CS210\",\"CS220\",\"CS230\",\"CS240\",\"CS250\",\"CS260\",\"CS270\",\"CS280\"]','2025-12-14 01:35:54'),(10,'[\"CS200\",\"CS210\",\"CS220\",\"CS240\",\"CS230\",\"CS250\",\"CS260\",\"CS280\"]','2025-12-16 11:34:44'),(11,'[\"CS200\",\"CS210\",\"CS220\",\"CS230\",\"CS240\",\"CS250\",\"CS260\",\"CS270\",\"CS290\",\"CS280\"]','2025-12-14 11:23:30'),(15,'[\"CS200\",\"CS210\",\"CS230\",\"CS220\",\"CS240\",\"CS250\",\"CS260\",\"CS270\"]','2025-12-16 21:49:28'),(16,'[\"CS200\",\"CS220\",\"CS210\",\"CS250\",\"CS240\",\"CS230\",\"CS260\"]','2025-12-16 19:48:43'),(17,'[\"CS200\",\"CS210\",\"CS220\",\"CS230\",\"CS240\",\"CS250\",\"CS270\"]','2025-12-16 22:21:53'),(18,'[\"CS200\",\"CS210\",\"CS220\",\"CS230\",\"CS260\",\"CS270\",\"CS280\",\"CS290\"]','2025-12-16 22:56:24'),(20,'[\"CS200\",\"CS210\",\"CS220\",\"CS230\",\"CS240\",\"CS250\",\"CS260\",\"CS270\",\"CS280\"]','2025-12-16 23:17:21'),(21,'[\"CS200\",\"CS210\",\"CS220\",\"CS230\",\"CS240\",\"CS250\",\"CS260\",\"CS280\"]','2025-12-19 19:56:20');
/*!40000 ALTER TABLE `student_core_course_checklists` ENABLE KEYS */;
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
