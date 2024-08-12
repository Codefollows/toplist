<?php

// Brute Forec Detection
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_ip_log ADD `timestamp` INT NULL", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_ip_log DROP PRIMARY KEY", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_ip_log
	ADD INDEX `brute_force_check` (`ip_address`, `timestamp`),
	ADD INDEX `vote_check` (`ip_address`, `username`)
", __FILE__, __LINE__);
