<?php

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_screens` (
	`screenshot_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`requested_url` VARCHAR( 255 ) NOT NULL,
	`requested_time` DATETIME NOT NULL ,
	`username` VARCHAR( 255 ) NOT NULL,
	`active` tinyint(1) default 1
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);