<?php

// Owner Field
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites ADD `owner` VARCHAR(255) AFTER `username`", __FILE__, __LINE__);
$result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites", __FILE__, __LINE__);
while (list($newowner) = $DB->fetch_array($result)) {

	$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET owner = '{$newowner}' WHERE  username = '{$newowner}'", __FILE__, __LINE__);
	$TMPL['upgrade'] .= "{$LNG['upgrade_added_ownership']} {$newowner}.<br />";
}

// Premium
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites
	ADD `premium_flag` TINYINT(1) NULL DEFAULT 0,
	ADD `premium_request` TINYINT(1) NULL DEFAULT 0,
	ADD `premium_banner_url` VARCHAR(255) NULL,
	ADD `date_start_premium` DATE NULL DEFAULT NULL,
	ADD `weeks_buy` SMALLINT NULL DEFAULT 0,
	ADD `total_day` SMALLINT NULL DEFAULT 0,
	ADD `remain_day` SMALLINT NULL DEFAULT 0
", __FILE__, __LINE__);

// description field to TEXT
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites CHANGE `description` `description` TEXT NULL DEFAULT NULL", __FILE__, __LINE__);

// 2Step Security
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites
	ADD `2step` tinyint(1) unsigned NOT NULL DEFAULT 0,
	ADD `2step_secret` varchar(255) NOT NULL DEFAULT ''
", __FILE__, __LINE__);

// gif/jpg to mp4
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites
	ADD `mp4_url` VARCHAR(255) NULL AFTER `banner_url`,
	ADD `premium_mp4_url` VARCHAR(255) NULL AFTER `premium_banner_url`
", __FILE__, __LINE__);

// Banner/video width height
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites
	ADD `banner_width` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `banner_url`,
	ADD `banner_height` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `banner_width`,
	ADD `premium_banner_width` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `premium_banner_url`,
	ADD `premium_banner_height` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `premium_banner_width`
", __FILE__, __LINE__);

// Indexes
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites
	ADD INDEX `date_start_premium` (`date_start_premium`)
", __FILE__, __LINE__);

// Unsubscribe
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites ADD `unsubscribe` tinyint(1) default 0 AFTER email", __FILE__, __LINE__);

