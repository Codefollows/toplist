<?php

// #_sites description field to TEXT
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites CHANGE `description` `description` TEXT NULL DEFAULT NULL", __FILE__, __LINE__);

//Brute Forec Detection
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_ip_log ADD `timestamp` INT NULL", __FILE__, __LINE__);

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_langs` (
			`phrase_id` int(10) NOT NULL auto_increment,
			`language` varchar(150) NOT NULL,
			`definition` text NOT NULL,
			`phrase_name` varchar(100) NOT NULL,
			PRIMARY KEY  (`phrase_id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);