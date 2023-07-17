-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versione server:              10.3.38-MariaDB - mariadb.org binary distribution
-- S.O. server:                  Win64
-- HeidiSQL Versione:            12.2.0.6576
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dump della struttura di tabella botheory_api_down.bt_job
DROP TABLE IF EXISTS `bt_job`;
CREATE TABLE IF NOT EXISTS `bt_job` (
  `id_bt_job` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_user` bigint(20) unsigned DEFAULT NULL,
  `job_id` varchar(50) NOT NULL,
  `job_status` enum('CREATED','PROGRESS','FAILED','CANCELLED','COMPLETED','EXPIRED') NOT NULL DEFAULT 'CREATED',
  `creation_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `state_change_datetime` timestamp NULL DEFAULT NULL,
  `last_err` text DEFAULT NULL,
  `callback_extra_data` text DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  `callback_url` text DEFAULT NULL,
  `callback_type` enum('POST','GET','PUT') DEFAULT NULL,
  `downloaded_size` bigint(20) NOT NULL DEFAULT 0,
  `file_name` varchar(250) DEFAULT NULL,
  `file_ext` varchar(10) NOT NULL DEFAULT '',
  `downloaded_datetime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_bt_job`),
  UNIQUE KEY `job_id_unique` (`job_id`),
  KEY `id_bt_job` (`id_bt_job`),
  KEY `job_id` (`job_id`),
  KEY `job_status` (`job_status`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dump dei dati della tabella botheory_api_down.bt_job: ~0 rows (circa)

-- Dump della struttura di tabella botheory_api_down.bt_job_downloads
DROP TABLE IF EXISTS `bt_job_downloads`;
CREATE TABLE IF NOT EXISTS `bt_job_downloads` (
  `id_bt_job_downloads` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_bt_job` bigint(20) unsigned NOT NULL,
  `part_index` smallint(5) unsigned NOT NULL DEFAULT 1,
  `download_status` enum('PAUSED','PROGRESS','FAILED','COMPLETED') NOT NULL DEFAULT 'PROGRESS',
  `start_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `downloaded_size` bigint(20) NOT NULL DEFAULT 0,
  `last_progress_datetime` timestamp NULL DEFAULT NULL,
  `state_change_datetime` timestamp NULL DEFAULT NULL,
  `last_err` text DEFAULT NULL,
  PRIMARY KEY (`id_bt_job_downloads`),
  KEY `id_bt_job_downloads` (`id_bt_job_downloads`),
  KEY `id_bt_job` (`id_bt_job`),
  KEY `download_status` (`download_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dump dei dati della tabella botheory_api_down.bt_job_downloads: ~0 rows (circa)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
