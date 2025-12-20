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
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('student','faculty','admin') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'prof_jamil','$2y$10$OkYR3XOpLZylQLZpmcbBwungIH/rd8AWRalhghxcJm5xqKdQKGTPq','jamil@uidaho.edu','faculty','2025-12-14 02:18:04'),(3,'admin_user','123456','admin@uidaho.edu','admin','2025-12-14 02:18:04'),(4,'prof_alves','123456','alves@uidaho.edu','faculty','2025-12-14 04:39:30'),(5,'prof_shou','123456','shou@uidaho.edu','faculty','2025-12-14 04:39:30'),(6,'prof_heckendorn','123456','heckendorn@uidaho.edu','faculty','2025-12-14 04:39:30'),(8,'admin','$2y$10$5e0FH00VNumTe.YFXlupFOJtxxH157uo8BR1AFwN0stC0.8uHmi0W','admin@example.com','admin','2025-12-14 07:41:01'),(10,'Zijun Xiang','$2y$10$A7TKOEHP7djbP6zVJ0kpm.zAvJxN5I7XsI6PItYB40lsTMqbS7Gx6','','student','2025-12-14 08:47:06'),(11,'student1','$2y$10$cbIabJOhHaxQxgjGjG/CQuMwSbXPcFiQbQYyfMv/mntmHg7jwECSy','123@gmail.com','student','2025-12-14 19:22:20'),(15,'Jason Liang','$2y$10$Gtz/ZaHTKDouJ4yn4Gfm2O9FGCeTT2m2bxOtTen0.kefswi9F6XBG','','student','2025-12-15 10:02:12'),(16,'Mengna Cheng','$2y$10$dc0BWP9F.mDz9NPy/NEsieZ2ntHn0/hgzBYxqJmbVH47haFkIGqpa','','student','2025-12-17 03:47:29'),(20,'Qiwei Liang','$2y$10$7cHjCQj6Jw/fQWcmy6.SFeyprYMclnqncQdbQ4I51G/YVHYv6gcne','2908375382@qq.com','student','2025-12-17 07:16:41'),(21,'abcd','$2y$10$0x9Z0k48bWc2llhzlYhlCu1v15.CIQQThxgmo8JmlrFkelPbI2YC2','','student','2025-12-20 03:28:43');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
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
