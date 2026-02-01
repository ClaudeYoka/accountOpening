-- --------------------------------------------------------
-- Hôte:                         127.0.0.1
-- Version du serveur:           8.0.30 - MySQL Community Server - GPL
-- SE du serveur:                Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Listage de la structure de la base pour ecoleaves_db
CREATE DATABASE IF NOT EXISTS `ecoleaves_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `ecoleaves_db`;

-- Listage de la structure de table ecoleaves_db. admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `UserName` varchar(100) NOT NULL,
  `Password` varchar(100) NOT NULL,
  `updationDate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- Listage des données de la table ecoleaves_db.admin : ~0 rows (environ)
INSERT INTO `admin` (`id`, `UserName`, `Password`, `updationDate`) VALUES
	(1, 'admin', '21232f297a57a5a743894a0e4a801fc3', '2020-11-03 05:55:30');

-- Listage de la structure de table ecoleaves_db. tbldepartments
CREATE TABLE IF NOT EXISTS `tbldepartments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `DepartmentName` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DepartmentShortName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `CreationDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

-- Listage des données de la table ecoleaves_db.tbldepartments : ~5 rows (environ)
INSERT INTO `tbldepartments` (`id`, `DepartmentName`, `DepartmentShortName`, `CreationDate`) VALUES
	(2, 'Information Technologies', 'ICT', '2017-11-01 07:19:37'),
	(4, 'Controle interne', 'CI', '2025-07-11 13:23:21'),
	(5, 'Ressources Humaines', 'RH', '2025-07-11 15:38:06'),
	(6, 'Opérations', 'OPS', '2025-07-11 15:45:50'),
	(7, 'Audit Interne', 'AI', '2025-07-11 15:46:09');

-- Listage de la structure de table ecoleaves_db. tblemployees
CREATE TABLE IF NOT EXISTS `tblemployees` (
  `emp_id` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `LastName` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Username` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `EmailId` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Gender` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Dob` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Department` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Av_leave` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Phonenumber` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Phonenumber2` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Status` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `RegDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `location` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `datemb` date DEFAULT NULL,
  `cumulative_days` int DEFAULT '0',
  `twofa_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `twofa_enabled` tinyint(1) DEFAULT '0',
  `password_changed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;

-- Listage des données de la table ecoleaves_db.tblemployees : ~9 rows (environ)
INSERT INTO `tblemployees` (`emp_id`, `FirstName`, `LastName`, `Username`, `Position`, `EmailId`, `Password`, `Gender`, `Dob`, `Department`, `Address`, `Av_leave`, `Phonenumber`, `Phonenumber2`, `Status`, `RegDate`, `role`, `location`, `datemb`, `cumulative_days`, `twofa_secret`, `twofa_enabled`, `password_changed`) VALUES
	(2, 'Yoka', 'Mossa', 'admin', 'Registra', 'admin@gmail.com', '0192023a7bbd73250516f069df18b500', 'Homme', '1992-01-11', 'ICT', '14 rue kombo', '30', '07222288890', '', 'Online', '2017-11-10 13:40:02', 'Admin', 'claude.JPG', '2015-07-08', NULL, NULL, 0, 0),
	(4, 'Nathaniel', 'Nkrumah', 'nkrumah', 'ICT Director', 'rk@gmail.com', '21232f297a57a5a743894a0e4a801fc3', 'Male', '3 February, 1990', 'ICT', 'N NEPO', '30', '1110088165', NULL, 'Offline', '2017-11-10 13:40:02', 'Admin', 'NO-IMAGE-AVAILABLE.jpg', '2024-06-12', NULL, NULL, 0, 0),
	(9, 'Richard', 'Awuni', 'Awuni', 'Head IT', 'knath@gmail.com', '$2y$10$/F1alZEULaMs0YU0e0rCZ.2T7qvp3iV4gEieoIDWDqCH6oCB/QlY.', 'Homme', '1980-02-10', 'ICT', 'Abas Station', '18', '0211988637', '', 'Online', '2022-08-04 18:06:27', 'HOD', 'photo8.jpg', '2021-07-08', 0, 'CMYYXVMISCIZZ4OB', 1, 1),
	(10, 'NKOUSSOU', 'Set', 'Snkoussou', 'IT Application', 'snkoussou@ecobank.com', '202cb962ac59075b964b07152d234b70', 'Femme', '1990-01-10', 'ICT', 'Bacongo', '16', '0102222928', '', 'Offline', '2022-08-04 18:18:50', 'staff', 'photo4.jpg', '2012-04-18', 0, 'WU44744YWAKHE5GP', 1, 1),
	(11, 'Yoka', 'Claude', 'Yclaude', 'IT', 'c@gmail.com', '$2y$10$97ke0HFlZ5m5lsBi10aRces/F7CGTfcNzLACiQpyP0.O3/u9Vokz6', 'Homme', '2000-08-28', 'ICT', '14 rue abolo moungali', '22', '069773075', '', 'Online', '2025-07-08 13:04:13', 'staff', 'claude.JPG', '2024-09-02', 0, 'XQ72GJGCXDL6XLL5', 1, 1),
	(12, 'DEO', 'Gracia', 'dgracia', 'resshource humaine', 'd@gmail.com', '202cb962ac59075b964b07152d234b70', 'Homme', '1996-02-08', 'RH', 'fysgvyvsu', '14', '9773075', '30303030', 'Offline', '2025-07-08 16:25:15', 'RH', 'NO-IMAGE-AVAILABLE.jpg', '2020-01-01', 0, NULL, 0, 1),
	(17, 'KIKOTA', 'Farnise', 'fkikota', 'IT delivery', 'kikota@gmail.com', 'd41e98d1eafa6d6011d3a70f1a5b92f0', 'Homme', '1975-03-10', 'ICT', '66 rue balossa Bacongo', '20', '069773079', '', 'Offline', '2025-07-22 11:40:13', 'staff', 'NO-IMAGE-AVAILABLE.jpg', '2015-01-05', 5, NULL, 0, 0),
	(18, 'MALONGA', 'Gilles', 'gmalonga', 'Controlleur', 'gmalonga@ecobank.com', 'd41e98d1eafa6d6011d3a70f1a5b92f0', 'Homme', '1980-12-02', 'CI', '24 rue kikoula Baconga', '20', '069706700', '054000400', 'Offline', '2025-08-07 13:44:08', 'staff', 'NO-IMAGE-AVAILABLE.jpg', '2020-12-12', 0, NULL, 0, 0),
	(19, 'BOUHOYI NKOUMBA', 'Grace', 'gbouhoyi', 'Head Contrôle Interne', 'gbouhoyi@ecobank.com', '$2y$10$eRTAe.dk9oMgqD/nDIoZe.kgLyfJ68cC83JGOelDeAJwz3kWxCEVy', 'Femme', '1989-02-01', 'CI', '136 rue lenine moungali', '30', '069773070', '069773075', 'Offline', '2025-08-12 14:12:03', 'HOD', 'NO-IMAGE-AVAILABLE.jpg', '2020-01-01', 0, NULL, 0, 1);

-- Listage de la structure de table ecoleaves_db. tblleave
CREATE TABLE IF NOT EXISTS `tblleave` (
  `id` int NOT NULL AUTO_INCREMENT,
  `LeaveType` varchar(110) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `RequestedDays` int NOT NULL,
  `DaysOutstand` int NOT NULL,
  `FromDate` date DEFAULT NULL,
  `ToDate` date DEFAULT NULL,
  `PostingDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `WorkCovered` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `HodRemarks` int NOT NULL DEFAULT '0',
  `HodDate` date DEFAULT NULL,
  `RegRemarks` int NOT NULL DEFAULT '0',
  `RegDate` date DEFAULT NULL,
  `empid` int DEFAULT NULL,
  `num_days` int NOT NULL,
  `RemainingDays` int NOT NULL,
  `IsRead` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `UserEmail` (`empid`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=latin1;

-- Listage des données de la table ecoleaves_db.tblleave : ~7 rows (environ)
INSERT INTO `tblleave` (`id`, `LeaveType`, `RequestedDays`, `DaysOutstand`, `FromDate`, `ToDate`, `PostingDate`, `WorkCovered`, `HodRemarks`, `HodDate`, `RegRemarks`, `RegDate`, `empid`, `num_days`, `RemainingDays`, `IsRead`) VALUES
	(7, 'Congé Maladie', 6, 14, '2025-07-28', '2025-08-04', '2025-07-24 16:07:16', 'KIFILA Set', 0, NULL, 1, '2025-08-12', 12, 6, 14, 1),
	(10, 'Congé annuel', 4, 20, '2025-07-29', '2025-08-01', '2025-07-28 11:13:56', 'Yoka Claude', 1, '2025-07-28', 1, '2025-07-28', 9, 4, 20, 1),
	(11, 'Congé annuel', 3, 17, '2025-08-01', '2025-08-06', '2025-07-30 13:20:54', 'Yoka Claude', 1, '2025-07-30', 1, '2025-07-31', 10, 3, 17, 1),
	(14, 'Congé annuel', 2, 18, '2025-08-01', '2025-08-05', '2025-07-31 11:04:26', 'NKOUSSOU Set', 0, NULL, 1, '2025-07-31', 9, 2, 18, 1),
	(32, 'Congé Maladie', 1, 16, '2025-08-05', '2025-08-06', '2025-08-04 17:50:13', 'Yoka Claude', 1, '2025-08-05', 1, '2025-08-12', 10, 1, 16, 1),
	(33, 'Congé Maladie', 2, 22, '2025-08-06', '2025-08-08', '2025-08-05 09:26:34', 'KIKOTA Farnise', 1, '2025-08-05', 1, '2025-08-05', 11, 2, 22, 1),
	(35, 'Congé Maladie', 2, 22, '2025-08-15', '2025-08-19', '2025-08-12 16:30:42', 'KIKOTA Farnise', 0, NULL, 0, NULL, 11, 2, 20, NULL);

-- Listage de la structure de table ecoleaves_db. tblleavetype
CREATE TABLE IF NOT EXISTS `tblleavetype` (
  `id` int NOT NULL AUTO_INCREMENT,
  `LeaveType` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `number_day` int DEFAULT '0',
  `CreationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- Listage des données de la table ecoleaves_db.tblleavetype : ~3 rows (environ)
INSERT INTO `tblleavetype` (`id`, `LeaveType`, `Description`, `number_day`, `CreationDate`) VALUES
	(1, 'Congé Maladie', 'Congé pour personne malade', 7, '2025-07-11 07:32:58'),
	(2, 'Congé annuel', 'Congé annuel ', 10, '2025-07-14 16:05:13'),
	(3, 'Congé sans solde', 'Congé non payé', 0, '2025-08-07 14:58:56');

-- Listage de la structure de table ecoleaves_db. tblnotifications
CREATE TABLE IF NOT EXISTS `tblnotifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` int NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `notification_type` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emp_id` (`emp_id`),
  CONSTRAINT `tblnotifications_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `tblemployees` (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Listage des données de la table ecoleaves_db.tblnotifications : ~5 rows (environ)
INSERT INTO `tblnotifications` (`id`, `emp_id`, `message`, `is_read`, `created_at`, `notification_type`) VALUES
	(4, 10, ' Congé Maladie de 1 Jours envoyé pour approbation.', 0, '2025-08-04 17:50:13', 'demande'),
	(8, 10, 'Votre demande de congé a été approuvée par votre HEAD', 0, '2025-08-05 13:38:15', 'leave'),
	(10, 11, ' Permission envoyé pour approbation.', 0, '2025-08-13 10:36:02', 'demande'),
	(13, 11, 'Votre permission a été approuvée par votre HEAD', 0, '2025-08-13 14:27:12', 'permission');

-- Listage de la structure de table ecoleaves_db. tblpermission
CREATE TABLE IF NOT EXISTS `tblpermission` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Raison` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `requested_days` int DEFAULT NULL,
  `requested_hours` int DEFAULT NULL,
  `FromDate` datetime DEFAULT NULL,
  `ToDate` datetime DEFAULT NULL,
  `PostingDate` datetime DEFAULT NULL,
  `HodRemarks` int NOT NULL DEFAULT '0',
  `HodDate` date DEFAULT NULL,
  `RegRemarks` int NOT NULL DEFAULT '0',
  `RegDate` date DEFAULT NULL,
  `IsRead` int NOT NULL DEFAULT '0',
  `empid` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `UserEmail` (`empid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Listage des données de la table ecoleaves_db.tblpermission : ~0 rows (environ)
INSERT INTO `tblpermission` (`id`, `Raison`, `requested_days`, `requested_hours`, `FromDate`, `ToDate`, `PostingDate`, `HodRemarks`, `HodDate`, `RegRemarks`, `RegDate`, `IsRead`, `empid`) VALUES
	(1, 'permission pour aller hopital', 0, 3, '2025-08-13 00:00:00', '2025-08-13 00:00:00', '2025-08-13 11:36:02', 1, '2025-08-13', 0, NULL, 1, 11);

-- Listage de la structure de table ecoleaves_db. tbl_logins
CREATE TABLE IF NOT EXISTS `tbl_logins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `emp_id` int NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `emp_id` (`emp_id`),
  CONSTRAINT `tbl_logins_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `tblemployees` (`emp_id`)
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Listage des données de la table ecoleaves_db.tbl_logins : ~83 rows (environ)
INSERT INTO `tbl_logins` (`id`, `emp_id`, `login_time`, `logout_time`) VALUES
	(1, 11, '2025-08-06 15:58:44', '2025-08-06 15:59:26'),
	(2, 11, '2025-08-07 06:40:33', '2025-08-07 06:40:47'),
	(3, 12, '2025-08-07 11:35:02', '2025-08-07 16:36:54'),
	(4, 11, '2025-08-07 16:39:44', '2025-08-07 16:40:35'),
	(5, 11, '2025-08-07 16:40:39', '2025-08-07 16:40:59'),
	(6, 11, '2025-08-07 16:44:41', '2025-08-07 16:47:44'),
	(7, 12, '2025-08-07 16:47:57', '2025-08-07 17:07:42'),
	(8, 12, '2025-08-11 14:58:11', '2025-08-11 15:25:55'),
	(9, 12, '2025-08-11 15:05:23', '2025-08-11 15:25:55'),
	(10, 12, '2025-08-11 15:13:26', '2025-08-11 15:25:55'),
	(11, 12, '2025-08-11 15:16:42', '2025-08-11 15:25:55'),
	(12, 12, '2025-08-11 15:17:07', '2025-08-11 15:25:55'),
	(13, 12, '2025-08-11 15:18:02', '2025-08-11 15:25:55'),
	(14, 12, '2025-08-11 15:18:27', '2025-08-11 15:25:55'),
	(15, 12, '2025-08-11 15:25:33', '2025-08-11 15:25:55'),
	(16, 12, '2025-08-11 15:25:45', '2025-08-11 15:25:55'),
	(17, 11, '2025-08-11 15:26:03', '2025-08-11 15:26:39'),
	(18, 11, '2025-08-11 15:26:15', '2025-08-11 15:26:39'),
	(19, 11, '2025-08-11 15:35:58', '2025-08-11 15:46:37'),
	(20, 11, '2025-08-11 15:36:31', '2025-08-11 15:46:37'),
	(21, 11, '2025-08-11 15:41:24', '2025-08-11 15:46:37'),
	(22, 10, '2025-08-11 15:41:55', '2025-08-11 15:44:53'),
	(23, 10, '2025-08-11 15:42:25', '2025-08-11 15:44:53'),
	(24, 11, '2025-08-11 15:45:00', '2025-08-11 15:46:37'),
	(25, 11, '2025-08-11 15:46:27', '2025-08-11 15:46:37'),
	(26, 11, '2025-08-11 16:00:48', '2025-08-11 16:13:02'),
	(27, 10, '2025-08-11 16:13:15', '2025-08-11 16:13:22'),
	(28, 9, '2025-08-11 16:15:07', '2025-08-11 16:49:17'),
	(29, 9, '2025-08-11 16:16:57', '2025-08-11 16:49:17'),
	(30, 12, '2025-08-11 16:49:32', '2025-08-11 18:36:37'),
	(31, 11, '2025-08-11 18:36:50', '2025-08-11 18:38:06'),
	(32, 11, '2025-08-12 08:16:44', '2025-08-12 10:03:34'),
	(33, 10, '2025-08-12 10:04:34', '2025-08-12 10:34:34'),
	(34, 11, '2025-08-12 10:34:42', '2025-08-12 10:35:25'),
	(35, 12, '2025-08-12 10:35:38', '2025-08-12 10:47:19'),
	(36, 9, '2025-08-12 10:47:24', '2025-08-12 13:47:42'),
	(37, 12, '2025-08-12 13:47:49', '2025-08-12 14:18:32'),
	(38, 19, '2025-08-12 14:18:55', '2025-08-12 14:45:14'),
	(39, 19, '2025-08-12 14:19:34', '2025-08-12 14:45:14'),
	(40, 9, '2025-08-12 14:45:20', '2025-08-12 15:33:40'),
	(41, 11, '2025-08-12 15:36:50', '2025-08-12 15:38:31'),
	(42, 9, '2025-08-12 15:38:41', '2025-08-12 15:39:05'),
	(43, 12, '2025-08-12 15:39:13', '2025-08-12 15:41:48'),
	(44, 12, '2025-08-12 15:41:55', '2025-08-12 15:52:39'),
	(45, 10, '2025-08-12 15:52:49', '2025-08-12 16:01:47'),
	(46, 12, '2025-08-12 16:26:08', '2025-08-12 16:27:08'),
	(47, 11, '2025-08-12 16:27:33', '2025-08-12 16:33:41'),
	(48, 9, '2025-08-12 16:33:52', '2025-08-12 16:53:11'),
	(49, 11, '2025-08-13 08:29:41', '2025-08-13 11:56:38'),
	(50, 9, '2025-08-13 11:56:43', '2025-08-13 14:59:29'),
	(51, 11, '2025-08-13 14:59:33', '2025-08-13 16:55:42'),
	(52, 12, '2025-08-13 16:55:48', '2025-08-13 16:56:32'),
	(53, 9, '2025-08-13 16:56:43', '2025-08-13 16:57:30'),
	(54, 11, '2025-08-13 16:58:22', '2025-08-13 16:58:28'),
	(55, 9, '2025-08-13 16:58:43', '2025-08-13 16:58:52'),
	(56, 2, '2025-08-13 17:10:33', NULL),
	(57, 2, '2025-08-13 17:10:48', NULL),
	(58, 9, '2025-08-14 14:20:39', NULL),
	(59, 9, '2025-08-14 15:10:23', NULL),
	(60, 9, '2025-08-14 15:11:16', NULL),
	(61, 9, '2025-08-14 15:11:28', NULL),
	(62, 9, '2025-08-14 15:11:48', NULL),
	(63, 9, '2025-08-14 15:12:26', NULL),
	(64, 9, '2025-08-14 15:12:56', NULL),
	(65, 9, '2025-08-14 15:13:23', NULL),
	(66, 9, '2025-08-14 15:13:49', NULL),
	(67, 19, '2025-08-14 15:13:57', '2025-08-14 15:56:49'),
	(68, 19, '2025-08-14 15:14:03', '2025-08-14 15:56:49'),
	(69, 19, '2025-08-14 15:14:26', '2025-08-14 15:56:49'),
	(70, 19, '2025-08-14 15:14:45', '2025-08-14 15:56:49'),
	(71, 19, '2025-08-14 15:14:49', '2025-08-14 15:56:49'),
	(72, 19, '2025-08-14 15:15:01', '2025-08-14 15:56:49'),
	(73, 19, '2025-08-14 15:15:18', '2025-08-14 15:56:49'),
	(74, 19, '2025-08-14 15:15:39', '2025-08-14 15:56:49'),
	(75, 19, '2025-08-14 15:16:11', '2025-08-14 15:56:49'),
	(76, 19, '2025-08-14 15:16:51', '2025-08-14 15:56:49'),
	(77, 19, '2025-08-14 15:17:36', '2025-08-14 15:56:49'),
	(78, 19, '2025-08-14 15:18:23', '2025-08-14 15:56:49'),
	(79, 19, '2025-08-14 15:19:40', '2025-08-14 15:56:49'),
	(80, 19, '2025-08-14 15:21:13', '2025-08-14 15:56:49'),
	(81, 19, '2025-08-14 15:21:28', '2025-08-14 15:56:49'),
	(82, 19, '2025-08-14 15:25:36', '2025-08-14 15:56:49'),
	(83, 11, '2025-08-14 18:07:53', '2025-08-14 18:08:23'),
	(84, 11, '2025-08-16 12:20:37', '2025-08-16 12:21:42'),
	(85, 11, '2025-08-16 13:00:31', '2025-08-16 13:03:43'),
	(86, 11, '2025-08-16 13:03:47', NULL);

-- Listage de la structure de table ecoleaves_db. tbl_message
CREATE TABLE IF NOT EXISTS `tbl_message` (
  `msg_id` int NOT NULL AUTO_INCREMENT,
  `incoming_msg_id` text NOT NULL,
  `outgoing_msg_id` text NOT NULL,
  `text_message` text NOT NULL,
  `curr_date` text NOT NULL,
  `curr_time` text NOT NULL,
  PRIMARY KEY (`msg_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- Listage des données de la table ecoleaves_db.tbl_message : ~2 rows (environ)
INSERT INTO `tbl_message` (`msg_id`, `incoming_msg_id`, `outgoing_msg_id`, `text_message`, `curr_date`, `curr_time`) VALUES
	(1, '2', '10', 'hello', 'June 26, 2023 ', '9:12 pm'),
	(2, '4', '10', 'hi', 'June 26, 2023 ', '9:12 pm');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
