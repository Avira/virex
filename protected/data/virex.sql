
CREATE TABLE bogus_archives_bga (
  id_bga int(10) unsigned NOT NULL AUTO_INCREMENT,
  name_bga varchar(120) NOT NULL,
  detection_bga varchar(32) NOT NULL,
  type_bga enum('daily','monthly','urls') NOT NULL,
  date_add_bga date NOT NULL,
  pending_action_bga enum('delete','rescan') DEFAULT NULL,
  error_message_bga varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_bga`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `external_users_history_euh` (
  `id_euh` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action_euh` varchar(350) NOT NULL,
  `idusr_euh` int(11) NOT NULL,
  `time_euh` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_euh`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `external_users_usr` (
  `id_usr` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_usr` varchar(60) NOT NULL,
  `company_usr` varchar(80) NOT NULL,
  `email_usr` varchar(80) NOT NULL,
  `password_usr` char(32) NOT NULL,
  `public_pgp_key_usr` text NOT NULL,
  `email_code_usr` char(32) DEFAULT NULL,
  `status_usr` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:new, 1:email_valid, 2:admin_valid, 3:disabled / can login if is admin_valid (2)',
  `rights_daily_usr` int(1) unsigned NOT NULL DEFAULT '1',
  `rights_monthly_usr` int(1) unsigned NOT NULL DEFAULT '1',
  `rights_clean_usr` int(1) unsigned NOT NULL DEFAULT '0',
  `register_date_usr` datetime DEFAULT NULL,
  `last_login_date_usr` datetime DEFAULT NULL,
  `limitation_date_usr` date DEFAULT NULL,
  `ip_usr` varchar(20) DEFAULT NULL,
  `pgp_key_name_usr` varchar(80) DEFAULT NULL,
  `rights_url_usr` int(1) NOT NULL DEFAULT '0',
  `second_public_gpg_key_text_usr` text DEFAULT NULL,
  `second_public_gpg_key_name_usr` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`id_usr`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `internal_users_uin` (
  `id_uin` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fname_uin` varchar(50) NOT NULL,
  `lname_uin` varchar(50) NOT NULL,
  `email_uin` varchar(50) NOT NULL,
  `enabled_uin` int(1) unsigned NOT NULL DEFAULT '0',
  `password_uin` char(32) NOT NULL,
  `register_date_uin` datetime NOT NULL,
  `register_by_uin` int(10) unsigned DEFAULT NULL,
  `last_login_date_uin` datetime DEFAULT NULL,
  `notification_pgp_error_uin` int(1) unsigned NOT NULL DEFAULT '0',
  `notification_undetected_samples_uin` int(1) unsigned NOT NULL DEFAULT '0',
  `notification_new_account_request_uin` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_uin`),
  UNIQUE KEY `email_uin` (`email_uin`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `permanent_statistics_ftp_psf` (
  `date_psf` date NOT NULL,
  `hour_psf` int(3) unsigned NOT NULL,
  `files_number_psf` int(10) unsigned DEFAULT NULL,
  `files_size_psf` int(10) unsigned DEFAULT NULL,
  `archives_number_psf` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`date_psf`,`hour_psf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `permanent_statistics_user_psu` (
  `date_psu` date NOT NULL,
  `hour_psu` int(3) unsigned NOT NULL,
  `idusr_psu` int(10) unsigned NOT NULL,
  `files_number_psu` int(10) unsigned DEFAULT NULL,
  `files_size_psu` int(10) unsigned DEFAULT NULL,
  `files_in_list_count_psu` int(10) unsigned DEFAULT NULL,
  `files_unique_number_psu` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`date_psu`,`hour_psu`,`idusr_psu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `samples_clean_scl` (
  `id_scl` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `md5_scl` char(32) NOT NULL,
  `sha256_scl` char(64) DEFAULT NULL,
  `added_when_scl` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `file_size_scl` int(10) unsigned NOT NULL,
  `type_scl` enum('daily', 'monthly') NOT NULL,
  `pending_action_scl` enum('delete') DEFAULT NULL,
  `enabled_scl` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_scl`),
  UNIQUE KEY `NewIndex1` (`md5_scl`,`type_scl`),
  UNIQUE KEY `NewIndex2` (`sha256_scl`,`type_scl`),
  KEY `date` (`added_when_scl`),
  KEY `type` (`type_scl`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `samples_detected_sde` (
  `id_sde` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `md5_sde` char(32) NOT NULL,
  `sha256_sde` char(64) DEFAULT NULL,
  `detection_sde` varchar(40) DEFAULT NULL,
  `file_size_sde` int(10) unsigned NOT NULL,
  `added_when_sde` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type_sde` enum('daily', 'monthly') NOT NULL,
  `pending_action_sde` enum('delete') DEFAULT NULL,
  `enabled_sde` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_sde`),
  UNIQUE KEY `NewIndex1` (`md5_sde`,`type_sde`),
  UNIQUE KEY `NewIndex2` (`sha256_sde`,`type_sde`),
  KEY `date` (`added_when_sde`),
  KEY `type` (`type_sde`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `urls_url` (
  `id_url` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `md5_url` char(32) NOT NULL,
  `sha256_url` char(64) DEFAULT NULL,
  `url_url` varchar(210) NOT NULL,
  `added_when_url` date NOT NULL,
  `enabled_url` int(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_url`),
  UNIQUE KEY `md5` (`md5_url`),
  UNIQUE KEY `sha256` (`sha256_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `user_files_usf` (
  `id_usf` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `idusl_usf` int(11) unsigned DEFAULT NULL,
  `md5_usf` char(32) NOT NULL,
  `sha256_usf` char(64) DEFAULT NULL,
  `date_usf` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `count_usf` int(2) unsigned NOT NULL,
  `idusr_usf` int(10) unsigned NOT NULL,
  `file_size_usf` bigint(11) unsigned NOT NULL,
  PRIMARY KEY (`id_usf`),
  KEY `ListId` (`idusl_usf`),
  KEY `idusr_usf` (`idusr_usf`),
  KEY `md5_usf` (`md5_usf`),
  KEY `sha256_usf` (`sha256_usf`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user_lists_usl` (
  `id_usl` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_usl` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `idusr_usl` int(11) NOT NULL,
  `text_usl` varchar(50) NOT NULL,
  `number_of_files_usl` int(10) unsigned NOT NULL,
  `start_interval_usl` date DEFAULT NULL,
  `end_interval_usl` date DEFAULT NULL,
  `list_type_usl` enum('Detected', 'Clean', 'Urls') NOT NULL DEFAULT 'Detected',
  PRIMARY KEY (`id_usl`),
  KEY `User` (`idusr_usl`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `variables_vrb` (
  `name_vrb` varchar(35) NOT NULL,
  `value_vrb` varchar(20) NOT NULL,
  PRIMARY KEY (`name_vrb`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;