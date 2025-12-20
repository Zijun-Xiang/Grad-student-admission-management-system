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
-- Table structure for table `core_courses`
--

DROP TABLE IF EXISTS `core_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `core_courses` (
  `course_code` varchar(10) NOT NULL,
  `major_code` varchar(16) DEFAULT NULL,
  `course_name` varchar(100) NOT NULL,
  `credits` int DEFAULT '3',
  `status` varchar(20) DEFAULT 'Open',
  `level` varchar(16) NOT NULL DEFAULT 'GRAD',
  `is_required` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`course_code`),
  KEY `idx_core_courses_level` (`level`),
  KEY `idx_core_courses_required` (`is_required`),
  KEY `idx_core_courses_major` (`major_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `core_courses`
--

LOCK TABLES `core_courses` WRITE;
/*!40000 ALTER TABLE `core_courses` DISABLE KEYS */;
INSERT INTO `core_courses` VALUES ('CS 445','CS','Compiler Design',4,'Open','GRAD',0),('CS 452','CS','Real-Time Operating Systems',3,'Open','GRAD',0),('CS 460','CS','Database Management Systems Design',3,'Open','GRAD',0),('CS 470','CS','Artificial Intelligence',3,'Open','GRAD',0),('CS 472','CS','Evolutionary Computation',3,'Open','GRAD',0),('CS 475','CS','Machine Learning',3,'Open','GRAD',0),('CS 507','CS','Research Methods in CS',3,'Open','GRAD',0),('CS 511','CS','Parallel Programming',3,'Open','GRAD',0),('CS200','CS','Programming Fundamentals',3,'Open','UG',1),('CS210','CS','Discrete Structures',3,'Open','UG',1),('CS220','CS','Data Structures',3,'Open','UG',1),('CS230','CS','Computer Organization & Architecture',3,'Open','UG',1),('CS240','CS','Algorithms',3,'Open','UG',1),('CS250','CS','Operating Systems',3,'Open','UG',1),('CS260','CS','Database Systems',3,'Open','UG',1),('CS270','CS','Software Engineering',3,'Open','UG',1),('CS280','CS','Computer Networks',3,'Open','UG',1),('CS290','CS','Theory of Computation',3,'Open','UG',1),('CS690','CS','Research Methods in Computer Science',3,'Open','GRAD',0);
/*!40000 ALTER TABLE `core_courses` ENABLE KEYS */;
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
