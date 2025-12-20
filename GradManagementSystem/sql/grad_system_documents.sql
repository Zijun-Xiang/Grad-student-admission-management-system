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
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `doc_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `doc_type` varchar(64) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_comment` text,
  PRIMARY KEY (`doc_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (5,11,'admission_letter','1df3235816a99e68ea4a50596d439e49.pdf','2025-12-14 19:22:46','approved','Approved'),(6,11,'major_professor_form','0601472beee1fb5eeecd549889f9560b.pdf','2025-12-14 19:37:12','approved','Approved'),(7,11,'major_professor_form','8383cc10b55d53c1a8fb7d86bb6b8ef8.jpg','2025-12-14 19:49:26','rejected',''),(8,11,'major_professor_form','6628bd48d5850016b461179ebddcf954.jpg','2025-12-14 19:51:52','approved','Approved'),(9,11,'thesis_project','f8f9c5faad713d43435b7b2b2faa7240.pdf','2025-12-15 00:27:37','approved','Approved'),(10,15,'research_method_proof','812881882fcda96604ab219a41d90f61.jpg','2025-12-15 10:15:22','pending',NULL),(12,11,'research_method_proof','f52cd85484f059113d043efb804a1084.jpg','2025-12-15 10:23:32','approved',NULL),(13,10,'admission_letter','3a0aaa87cb56a0a4592dbe0ad46b7f53.pdf','2025-12-16 17:22:26','approved','Approved'),(14,10,'admission_letter','b9ac3e9401f9a6b509d61a7e24f3588c.pdf','2025-12-16 17:23:38','approved','Approved'),(16,15,'research_method_proof','5d84267723e55d5ba4c2a258e71ac439.pdf','2025-12-16 17:29:10','approved',NULL),(18,16,'admission_letter','32958b592f0f5f9bdb03dd19b6547479.pdf','2025-12-17 03:47:49','approved','Approved'),(19,15,'admission_letter','bac12c7a14e3abb7efba71c3929e92e4.pdf','2025-12-17 05:49:02','approved','Approved'),(26,20,'admission_letter','9457f1e3dabe4435f1055e88600792d4.pdf','2025-12-17 07:16:51','approved','Approved'),(27,20,'major_professor_form','010cf4b53a28a6b0f90f28d58eecac39.jpg','2025-12-17 07:19:08','approved','Approved'),(29,21,'admission_letter','ac7a19cda190b8a8255192873d303a70.pdf','2025-12-20 03:55:13','approved','Approved');
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
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
