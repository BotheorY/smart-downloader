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
  `job_status` enum('CREATED','DOWNLOADING','FAILED','CANCELLED','COMPLETED','EXPIRED','JOINING','DOWNLOADED') NOT NULL DEFAULT 'CREATED',
  `creation_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `state_change_datetime` timestamp NULL DEFAULT NULL,
  `last_err` text DEFAULT NULL,
  `callback_extra_data` text DEFAULT NULL,
  `file_url` text DEFAULT NULL,
  `callback_url` text DEFAULT NULL,
  `callback_type` enum('POST','GET','PUT') DEFAULT NULL,
  `downloaded_size` bigint(20) NOT NULL DEFAULT 0,
  `file_name` varchar(250) DEFAULT NULL,
  `file_ext` varchar(10) DEFAULT NULL,
  `downloaded_datetime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_bt_job`),
  UNIQUE KEY `job_id_unique` (`job_id`),
  KEY `id_bt_job` (`id_bt_job`),
  KEY `job_id` (`job_id`),
  KEY `job_status` (`job_status`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dump dei dati della tabella botheory_api_down.bt_job: ~1 rows (circa)
INSERT INTO `bt_job` (`id_bt_job`, `id_user`, `job_id`, `job_status`, `creation_datetime`, `state_change_datetime`, `last_err`, `callback_extra_data`, `file_url`, `callback_url`, `callback_type`, `downloaded_size`, `file_name`, `file_ext`, `downloaded_datetime`) VALUES
	(2, 1, '80DD260AE4A2385D108DDF2DFC8103D9', 'COMPLETED', '2023-07-26 11:33:36', '2023-07-26 11:34:46', NULL, NULL, 'https://rr1---sn-uxaxpu5ap5-ju5e.googlevideo.com/videoplayback?expire=1690392771&ei=YwTBZP6OEsPXx_AP3fWK6AM&ip=79.8.157.250&id=o-APJ9luGMpEN6albcJ8rzAgqTDeTJ0B2rRNKyvgWRiAUL&itag=18&source=youtube&requiressl=yes&mh=dV&mm=31%2C29&mn=sn-uxaxpu5ap5-ju5e%2Csn-hpa7kn7d&ms=au%2Crdu&mv=u&mvi=1&pl=27&spc=Ul2Sq-thz5eV3dgHTd_aSmPDlOgH-MZu6nQFUdNF8w&vprv=1&svpuc=1&mime=video%2Fmp4&ns=Qi2Y80nL2FbnGbeEhmC3eo0O&gir=yes&clen=67637756&ratebypass=yes&dur=1154.333&lmt=1679574147217224&mt=1690370598&fvip=1&fexp=24007246&beids=24350018&c=WEB&txp=6319224&n=kCoTT2M9qkaB6_yvdV&sparams=expire%2Cei%2Cip%2Cid%2Citag%2Csource%2Crequiressl%2Cspc%2Cvprv%2Csvpuc%2Cmime%2Cns%2Cgir%2Cclen%2Cratebypass%2Cdur%2Clmt&sig=AOq0QJ8wRAIgWUgh2QD_jmsdlvaDVZCvCLHlt89BBvVJAWeIpQdzRA8CIH4imiV1JpezjFBQf97acgccOIPFHmZYPREm-I7Olc3b&lsparams=mh%2Cmm%2Cmn%2Cms%2Cmv%2Cmvi%2Cpl&lsig=AG3C_xAwRgIhAKyWDt-hZvCy5Cv-tUMrQXjGAqEk-K-tzUjXcNuxVrDZAiEA_nn08Dr9jj5nJSrQTbhXKt0UIRw9U4K9q7F0Ug4yFaw%3D', NULL, NULL, 67637756, 'videoplayback', NULL, '2023-07-26 11:34:40');

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
  `last_one` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_bt_job_downloads`),
  KEY `id_bt_job_downloads` (`id_bt_job_downloads`),
  KEY `id_bt_job` (`id_bt_job`),
  KEY `download_status` (`download_status`)
) ENGINE=InnoDB AUTO_INCREMENT=2762 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Dump dei dati della tabella botheory_api_down.bt_job_downloads: ~0 rows (circa)

-- Dump della struttura di tabella botheory_api_down.bt_log
DROP TABLE IF EXISTS `bt_log`;
CREATE TABLE IF NOT EXISTS `bt_log` (
  `id_bt_log` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `id_bt_job` bigint(20) unsigned DEFAULT NULL,
  `id_bt_job_downloads` bigint(20) unsigned DEFAULT NULL,
  `log_msg` text NOT NULL,
  `start_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_bt_log`) USING BTREE,
  KEY `id_bt_job` (`id_bt_job`) USING BTREE,
  KEY `id_bt_job_downloads` (`id_bt_job_downloads`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dump dei dati della tabella botheory_api_down.bt_log: ~0 rows (circa)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
