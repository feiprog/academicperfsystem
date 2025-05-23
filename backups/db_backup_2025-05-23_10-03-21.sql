-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: academicperfsystem
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_terms`
--

DROP TABLE IF EXISTS `academic_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(9) NOT NULL,
  `term` enum('First Semester','Second Semester','Summer') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('upcoming','active','completed') NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_term` (`academic_year`,`term`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`,`end_date`),
  CONSTRAINT `academic_terms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_terms`
--

LOCK TABLES `academic_terms` WRITE;
/*!40000 ALTER TABLE `academic_terms` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_type` enum('quiz','assignment','exam','project','other') NOT NULL,
  `scheduled_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_scheduled_date` (`scheduled_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`,`subject_id`,`date`),
  KEY `subject_id` (`subject_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  KEY `idx_student_subject_date` (`student_id`,`subject_id`,`date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curriculum`
--

DROP TABLE IF EXISTS `curriculum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `degree_program` varchar(50) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `semester` enum('First Semester','Second Semester') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_curriculum` (`degree_program`,`year_level`,`subject_id`,`semester`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_program_year` (`degree_program`,`year_level`),
  CONSTRAINT `curriculum_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curriculum`
--

LOCK TABLES `curriculum` WRITE;
/*!40000 ALTER TABLE `curriculum` DISABLE KEYS */;
INSERT INTO `curriculum` VALUES (1,'BSIT','1st Year',1,'First Semester','2025-05-23 01:15:20'),(2,'BSIT','1st Year',5,'First Semester','2025-05-23 01:15:20'),(3,'BSIT','1st Year',11,'First Semester','2025-05-23 01:15:20'),(4,'BSIT','1st Year',2,'First Semester','2025-05-23 01:15:20'),(5,'BSIT','1st Year',3,'Second Semester','2025-05-23 01:15:20'),(6,'BSIT','1st Year',12,'Second Semester','2025-05-23 01:15:20'),(7,'BSIT','1st Year',4,'Second Semester','2025-05-23 01:15:20'),(8,'BSIT','1st Year',6,'Second Semester','2025-05-23 01:15:20'),(9,'BSIT','2nd Year',7,'First Semester','2025-05-23 01:15:20'),(10,'BSIT','2nd Year',8,'First Semester','2025-05-23 01:15:20'),(11,'BSIT','2nd Year',10,'First Semester','2025-05-23 01:15:20'),(12,'BSIT','2nd Year',9,'First Semester','2025-05-23 01:15:20'),(13,'BSIT','2nd Year',3,'Second Semester','2025-05-23 01:15:20'),(14,'BSIT','2nd Year',4,'Second Semester','2025-05-23 01:15:20'),(15,'BSIT','2nd Year',11,'Second Semester','2025-05-23 01:15:20'),(16,'BSIT','2nd Year',2,'Second Semester','2025-05-23 01:15:20'),(17,'BSIT','3rd Year',7,'First Semester','2025-05-23 01:15:20'),(18,'BSIT','3rd Year',12,'First Semester','2025-05-23 01:15:20'),(19,'BSIT','3rd Year',10,'First Semester','2025-05-23 01:15:20'),(20,'BSIT','3rd Year',9,'First Semester','2025-05-23 01:15:20'),(21,'BSIT','3rd Year',1,'Second Semester','2025-05-23 01:15:20'),(22,'BSIT','3rd Year',6,'Second Semester','2025-05-23 01:15:20'),(23,'BSIT','3rd Year',8,'Second Semester','2025-05-23 01:15:20'),(24,'BSIT','3rd Year',5,'Second Semester','2025-05-23 01:15:20'),(25,'BSIT','4th Year',3,'First Semester','2025-05-23 01:15:20'),(26,'BSIT','4th Year',4,'First Semester','2025-05-23 01:15:20'),(27,'BSIT','4th Year',10,'First Semester','2025-05-23 01:15:20'),(28,'BSIT','4th Year',9,'First Semester','2025-05-23 01:15:20'),(29,'BSIT','4th Year',7,'Second Semester','2025-05-23 01:15:20'),(30,'BSIT','4th Year',12,'Second Semester','2025-05-23 01:15:20'),(31,'BSIT','4th Year',11,'Second Semester','2025-05-23 01:15:20'),(32,'BSIT','4th Year',2,'Second Semester','2025-05-23 01:15:20'),(33,'BSCS','1st Year',1,'First Semester','2025-05-23 01:15:20'),(34,'BSCS','1st Year',5,'First Semester','2025-05-23 01:15:20'),(35,'BSCS','1st Year',11,'First Semester','2025-05-23 01:15:20'),(36,'BSCS','1st Year',2,'First Semester','2025-05-23 01:15:20'),(37,'BSCS','1st Year',3,'Second Semester','2025-05-23 01:15:20'),(38,'BSCS','1st Year',12,'Second Semester','2025-05-23 01:15:20'),(39,'BSCS','1st Year',4,'Second Semester','2025-05-23 01:15:20'),(40,'BSCS','1st Year',6,'Second Semester','2025-05-23 01:15:20'),(41,'BSCS','2nd Year',7,'First Semester','2025-05-23 01:15:20'),(42,'BSCS','2nd Year',8,'First Semester','2025-05-23 01:15:20'),(43,'BSCS','2nd Year',10,'First Semester','2025-05-23 01:15:20'),(44,'BSCS','2nd Year',9,'First Semester','2025-05-23 01:15:20'),(45,'BSCS','2nd Year',3,'Second Semester','2025-05-23 01:15:20'),(46,'BSCS','2nd Year',4,'Second Semester','2025-05-23 01:15:20'),(47,'BSCS','2nd Year',11,'Second Semester','2025-05-23 01:15:20'),(48,'BSCS','2nd Year',2,'Second Semester','2025-05-23 01:15:20'),(49,'BSCS','3rd Year',7,'First Semester','2025-05-23 01:15:20'),(50,'BSCS','3rd Year',12,'First Semester','2025-05-23 01:15:20'),(51,'BSCS','3rd Year',10,'First Semester','2025-05-23 01:15:20'),(52,'BSCS','3rd Year',9,'First Semester','2025-05-23 01:15:20'),(53,'BSCS','3rd Year',1,'Second Semester','2025-05-23 01:15:20'),(54,'BSCS','3rd Year',6,'Second Semester','2025-05-23 01:15:20'),(55,'BSCS','3rd Year',8,'Second Semester','2025-05-23 01:15:20'),(56,'BSCS','3rd Year',5,'Second Semester','2025-05-23 01:15:20'),(57,'BSCS','4th Year',3,'First Semester','2025-05-23 01:15:20'),(58,'BSCS','4th Year',4,'First Semester','2025-05-23 01:15:20'),(59,'BSCS','4th Year',10,'First Semester','2025-05-23 01:15:20'),(60,'BSCS','4th Year',9,'First Semester','2025-05-23 01:15:20'),(61,'BSCS','4th Year',7,'Second Semester','2025-05-23 01:15:20'),(62,'BSCS','4th Year',12,'Second Semester','2025-05-23 01:15:20'),(63,'BSCS','4th Year',11,'Second Semester','2025-05-23 01:15:20'),(64,'BSCS','4th Year',2,'Second Semester','2025-05-23 01:15:20'),(65,'BSCE','1st Year',1,'First Semester','2025-05-23 01:15:20'),(66,'BSCE','1st Year',5,'First Semester','2025-05-23 01:15:20'),(67,'BSCE','1st Year',11,'First Semester','2025-05-23 01:15:20'),(68,'BSCE','1st Year',2,'First Semester','2025-05-23 01:15:20'),(69,'BSCE','1st Year',3,'Second Semester','2025-05-23 01:15:20'),(70,'BSCE','1st Year',12,'Second Semester','2025-05-23 01:15:20'),(71,'BSCE','1st Year',4,'Second Semester','2025-05-23 01:15:20'),(72,'BSCE','1st Year',6,'Second Semester','2025-05-23 01:15:20'),(73,'BSCE','2nd Year',7,'First Semester','2025-05-23 01:15:20'),(74,'BSCE','2nd Year',8,'First Semester','2025-05-23 01:15:20'),(75,'BSCE','2nd Year',10,'First Semester','2025-05-23 01:15:20'),(76,'BSCE','2nd Year',9,'First Semester','2025-05-23 01:15:20'),(77,'BSCE','2nd Year',3,'Second Semester','2025-05-23 01:15:20'),(78,'BSCE','2nd Year',4,'Second Semester','2025-05-23 01:15:20'),(79,'BSCE','2nd Year',11,'Second Semester','2025-05-23 01:15:20'),(80,'BSCE','2nd Year',2,'Second Semester','2025-05-23 01:15:20'),(81,'BSCE','3rd Year',7,'First Semester','2025-05-23 01:15:20'),(82,'BSCE','3rd Year',12,'First Semester','2025-05-23 01:15:20'),(83,'BSCE','3rd Year',10,'First Semester','2025-05-23 01:15:20'),(84,'BSCE','3rd Year',9,'First Semester','2025-05-23 01:15:20'),(85,'BSCE','3rd Year',1,'Second Semester','2025-05-23 01:15:20'),(86,'BSCE','3rd Year',6,'Second Semester','2025-05-23 01:15:20'),(87,'BSCE','3rd Year',8,'Second Semester','2025-05-23 01:15:20'),(88,'BSCE','3rd Year',5,'Second Semester','2025-05-23 01:15:20'),(89,'BSCE','4th Year',3,'First Semester','2025-05-23 01:15:20'),(90,'BSCE','4th Year',4,'First Semester','2025-05-23 01:15:20'),(91,'BSCE','4th Year',10,'First Semester','2025-05-23 01:15:20'),(92,'BSCE','4th Year',9,'First Semester','2025-05-23 01:15:20'),(93,'BSCE','4th Year',7,'Second Semester','2025-05-23 01:15:20'),(94,'BSCE','4th Year',12,'Second Semester','2025-05-23 01:15:20'),(95,'BSCE','4th Year',11,'Second Semester','2025-05-23 01:15:20'),(96,'BSCE','4th Year',2,'Second Semester','2025-05-23 01:15:20');
/*!40000 ALTER TABLE `curriculum` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_history`
--

DROP TABLE IF EXISTS `grade_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `category` enum('written','performance','exams') NOT NULL,
  `grade_type` varchar(20) NOT NULL,
  `old_score` decimal(5,2) DEFAULT NULL,
  `new_score` decimal(5,2) NOT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  KEY `modified_by` (`modified_by`),
  KEY `idx_grade_id` (`grade_id`),
  KEY `idx_modified_at` (`modified_at`),
  CONSTRAINT `grade_history_ibfk_1` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grade_history_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grade_history_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grade_history_ibfk_4` FOREIGN KEY (`modified_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_history`
--

LOCK TABLES `grade_history` WRITE;
/*!40000 ALTER TABLE `grade_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `category` enum('written','performance','exams') NOT NULL,
  `grade_type` varchar(20) NOT NULL,
  `score` decimal(5,2) NOT NULL,
  `attempt_number` int(11) DEFAULT 1,
  `academic_year` varchar(9) NOT NULL,
  `term` varchar(20) NOT NULL,
  `remarks` text DEFAULT NULL,
  `graded_by` int(11) NOT NULL,
  `graded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade_attempt` (`student_id`,`subject_id`,`category`,`grade_type`,`attempt_number`,`academic_year`,`term`),
  KEY `subject_id` (`subject_id`),
  KEY `graded_by` (`graded_by`),
  KEY `idx_category` (`category`),
  KEY `idx_grade_type` (`grade_type`),
  KEY `idx_academic_year` (`academic_year`),
  KEY `idx_term` (`term`),
  KEY `idx_graded_at` (`graded_at`),
  KEY `idx_student_subject_term` (`student_id`,`subject_id`,`academic_year`,`term`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grades_ibfk_3` FOREIGN KEY (`graded_by`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `check_score` CHECK (`score` >= 0 and `score` <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_username_ip` (`username`,`ip_address`),
  KEY `idx_attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_login_history_user` (`user_id`),
  KEY `idx_login_history_time` (`created_at`),
  CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (1,5,'2025-05-23 09:25:13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:25:13'),(2,5,'2025-05-23 09:25:44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:25:44'),(3,5,'2025-05-23 09:26:15','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:26:15'),(4,5,'2025-05-23 09:26:46','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:26:46'),(5,5,'2025-05-23 09:27:17','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:27:17'),(6,5,'2025-05-23 09:27:48','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:27:48'),(7,5,'2025-05-23 09:28:19','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:28:19'),(8,5,'2025-05-23 09:28:50','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:28:50'),(9,5,'2025-05-23 09:29:21','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:21'),(10,5,'2025-05-23 09:29:30','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:30'),(11,5,'2025-05-23 09:29:32','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:32'),(12,5,'2025-05-23 09:29:32','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:32'),(13,5,'2025-05-23 09:29:32','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:32'),(14,5,'2025-05-23 09:29:33','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:33'),(15,5,'2025-05-23 09:29:42','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:42'),(16,5,'2025-05-23 09:29:44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:44'),(17,5,'2025-05-23 09:29:44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:44'),(18,5,'2025-05-23 09:29:44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','2025-05-23 01:29:44');
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_logs`
--

DROP TABLE IF EXISTS `maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` enum('backup','restore','update','maintenance','other') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('started','completed','failed') NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `performed_by` (`performed_by`),
  CONSTRAINT `maintenance_logs_ibfk_1` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_logs`
--

LOCK TABLES `maintenance_logs` WRITE;
/*!40000 ALTER TABLE `maintenance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `remember_tokens`
--

LOCK TABLES `remember_tokens` WRITE;
/*!40000 ALTER TABLE `remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_requests`
--

DROP TABLE IF EXISTS `report_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `request_type` enum('term','progress','comprehensive','special') NOT NULL,
  `term_period` enum('preliminary','midterm','semi_final','final') DEFAULT NULL,
  `request_reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `response_date` timestamp NULL DEFAULT NULL,
  `response_by` int(11) DEFAULT NULL,
  `response_notes` text DEFAULT NULL,
  `report_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  KEY `response_by` (`response_by`),
  KEY `report_id` (`report_id`),
  KEY `idx_status` (`status`),
  KEY `idx_request_date` (`request_date`),
  CONSTRAINT `report_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_requests_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_requests_ibfk_3` FOREIGN KEY (`response_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `report_requests_ibfk_4` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_requests`
--

LOCK TABLES `report_requests` WRITE;
/*!40000 ALTER TABLE `report_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `report_type` enum('term','progress','comprehensive','special') NOT NULL,
  `term_period` enum('preliminary','midterm','semi_final','final') DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('draft','pending','approved','rejected') DEFAULT 'draft',
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `comments` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_status` (`status`),
  KEY `idx_submission_date` (`submission_date`),
  KEY `idx_status_date` (`status`,`submission_date`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_subjects`
--

DROP TABLE IF EXISTS `student_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('active','dropped','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_status` (`status`),
  KEY `idx_enrollment_date` (`enrollment_date`),
  CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_subjects`
--

LOCK TABLES `student_subjects` WRITE;
/*!40000 ALTER TABLE `student_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `year_level` varchar(20) NOT NULL,
  `degree_program` varchar(50) NOT NULL,
  `semester` enum('First Semester','Second Semester') NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_year_program` (`year_level`,`degree_program`),
  KEY `idx_academic_term` (`semester`,`academic_year`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (2,6,'STU20240001','Test','Student','1st Year','BSIT','First Semester','2023-2024','2025-05-23 00:59:40'),(3,7,'STU20240002','Test','User','1st Year','BSIT','First Semester','2023-2024','2025-05-23 01:01:07'),(4,8,'STU20240003','Student','One','1st Year','BSIT','First Semester','2023-2024','2025-05-23 01:02:40'),(9,18,'STU20250018','Test','Student','2nd Year','BSIT','Second Semester','2024-2025','2025-05-23 01:09:15');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`),
  KEY `idx_subject_code` (`subject_code`),
  KEY `idx_teacher` (`teacher_id`),
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'IM101','Information Management','Fundamentals of Information Management',1,'2025-05-23 01:15:20'),(2,'WSD101','Web System and Development','Web Development and System Design',2,'2025-05-23 01:15:20'),(3,'ADB101','Advanced Database Management','Advanced Database Concepts and Management',3,'2025-05-23 01:15:20'),(4,'NET201','Networking II','Advanced Networking Concepts and Implementation',4,'2025-05-23 01:15:20'),(5,'PROG101','Programming Fundamentals','Introduction to Programming Concepts and Logic',NULL,'2025-05-23 01:15:20'),(6,'OOP201','Object-Oriented Programming','Advanced Programming with OOP Principles',NULL,'2025-05-23 01:15:20'),(7,'DSA201','Data Structures and Algorithms','Core Data Structures and Algorithm Analysis',3,'2025-05-23 01:15:20'),(8,'OS201','Operating Systems','Operating System Concepts and Management',NULL,'2025-05-23 01:15:20'),(9,'SEC201','Information Security','Cybersecurity and Information Protection',4,'2025-05-23 01:15:20'),(10,'PM201','Project Management','IT Project Planning and Management',1,'2025-05-23 01:15:20'),(11,'UIUX101','User Interface Design','Principles of UI/UX Design and Implementation',2,'2025-05-23 01:15:20'),(12,'MOB201','Mobile Development','Mobile Application Development and Design',NULL,'2025-05-23 01:15:20');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'maintenance_mode','false','System maintenance mode status',NULL,'2025-05-23 01:33:54'),(2,'grade_submission_deadline','7','Days allowed for grade submission after term end',NULL,'2025-05-23 01:33:54'),(3,'min_attendance_percentage','75','Minimum required attendance percentage',NULL,'2025-05-23 01:33:54'),(4,'max_units_per_semester','24','Maximum units allowed per semester',NULL,'2025-05-23 01:33:54'),(5,'grading_scale','JSON:{\"A\":95,\"B\":85,\"C\":75,\"D\":65,\"F\":0}','Grade scale configuration',NULL,'2025-05-23 01:33:54'),(6,'academic_year','2025-2026','Current academic year',NULL,'2025-05-23 01:33:54'),(7,'current_term','First Semester','Current academic term',NULL,'2025-05-23 01:33:54'),(8,'system_email','system@school.edu','System email address for notifications',NULL,'2025-05-23 01:33:54'),(9,'backup_retention_days','30','Number of days to retain system backups',NULL,'2025-05-23 01:33:54'),(10,'password_expiry_days','90','Days before password expiration',NULL,'2025-05-23 01:33:54');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_id` (`teacher_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_teacher_id` (`teacher_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
INSERT INTO `teachers` VALUES (1,1,'T2024-001','Information Technology','2025-05-23 00:51:01'),(2,2,'T2024-002','Information Technology','2025-05-23 00:51:01'),(3,3,'T2024-003','Information Technology','2025-05-23 00:51:01'),(4,4,'T2024-004','Information Technology','2025-05-23 00:51:01'),(11,5,'ADMIN-001','System Administration','2025-05-23 01:14:30');
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','admin') NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'mramos','$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO','teacher','Marvin Ramos','marvin.ramos@school.edu','2025-05-23 00:51:01'),(2,'sabina','$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO','teacher','Shane Abina','shane.abina@school.edu','2025-05-23 00:51:01'),(3,'jagudo','$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO','teacher','Jovemer Agudo','jovemer.agudo@school.edu','2025-05-23 00:51:01'),(4,'jsabalo','$2y$10$JzTCD/tsHHVJCB9AAQ6v3OBDWQBaTqph.siy5ePNBJog4Czg7t9cO','teacher','Jonathan Sabalo','jonathan.sabalo@school.edu','2025-05-23 00:51:01'),(5,'admin','$2y$10$UC2vE74ojZfSQGQ3f5hKsuAttDecrapH9zCE1Arbd6EXkSQh343zO','admin','System Administrator','admin@school.edu','2025-05-23 00:53:50'),(6,'test','$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS','student','Test Student','test@example.com','2025-05-23 00:55:29'),(7,'testuser','$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS','student','Test User','testuser@example.com','2025-05-23 01:00:58'),(8,'student1','$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS','student','Student One','student1@example.com','2025-05-23 01:02:29'),(18,'teststudent01','$2y$10$Ea0jZpsbjNNf3U4zgjUq3eywt6m9XadKpblMKPNs5RwUdMR7zaTtS','student','Test Student','teststudent1@gmail.com','2025-05-23 01:09:15');
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

-- Dump completed on 2025-05-23 10:03:22
