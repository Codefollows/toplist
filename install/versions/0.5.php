<?php

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners` (
	`id` BIGINT NOT NULL AUTO_INCREMENT,
	`code` TEXT,
	`name` TEXT,
	`display_zone` TEXT,
	`active` SMALLINT DEFAULT 1,
	`views` INT,
	`max_views` INT,
	PRIMARY KEY (`id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);