-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server Version:               5.7.14 - MySQL Community Server (GPL)
-- Server Betriebssystem:        Win64
-- HeidiSQL Version:             9.4.0.5125
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Exportiere Struktur von Tabelle devrant_docs.dd_answers
CREATE TABLE IF NOT EXISTS `dd_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `questionid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `body` text NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='All answers for all the questions';

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle devrant_docs.dd_questions
CREATE TABLE IF NOT EXISTS `dd_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `body` text NOT NULL,
  `category` varchar(20) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='All questions';

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle devrant_docs.dd_tokens
CREATE TABLE IF NOT EXISTS `dd_tokens` (
  `token_id` varchar(30) NOT NULL,
  `token_key` varchar(40) NOT NULL,
  `userid` int(11) NOT NULL,
  UNIQUE KEY `token_id` (`token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Auth Tokens';

-- Daten Export vom Benutzer nicht ausgewählt
-- Exportiere Struktur von Tabelle devrant_docs.dd_users
CREATE TABLE IF NOT EXISTS `dd_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Password Hash',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Bool',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='All users';

-- Daten Export vom Benutzer nicht ausgewählt
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
