<?php

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners` (
	`id` BIGINT unsigned NOT NULL AUTO_INCREMENT,
	`code` TEXT,
	`name` TEXT,
	`display_zone` TEXT,
	`active` tinyint(1) unsigned default 1 NOT NULL,
	`views` int(10) unsigned default 0 NOT NULL,
	`max_views` int(10) unsigned default 0 NOT NULL,
	`type` varchar(255) default 'global' NOT NULL,
	PRIMARY KEY (`id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners_zones` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`zone` VARCHAR(255) default '' NOT NULL,
	`type` VARCHAR(255) default 'global' NOT NULL,
	PRIMARY KEY (`id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

// Defaults
$zones = array(
	'a' => 'global|Global',
	'b' => 'global|Global',
	'c' => 'global|Global',
	'd' => 'details|Details Page'
);
foreach ($zones as $zone => $type) {
	$DB->query("INSERT INTO `{$CONF['sql_prefix']}_osbanners_zones` (`zone`, `type`) VALUES ('{$zone}', '{$type}')", __FILE__, __LINE__);
}