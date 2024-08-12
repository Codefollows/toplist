<?php

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_menu` (
	`id` int(10) NOT NULL auto_increment,
	`menu_id` int(11) NOT NULL,
	`title` varchar(150) NOT NULL,
	`path` varchar(150) NOT NULL,
	`target` varchar(80) NULL DEFAULT NULL,
	`sort` int(2) NOT NULL,
	PRIMARY KEY (`id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

$DB->query("INSERT INTO {$CONF['sql_prefix']}_menu (menu_id, title, path, sort) VALUES ('1', '{$LNG['main_menu_rankings']}', '{$CONF['list_url']}/', 1)", __FILE__, __LINE__);
$DB->query("INSERT INTO {$CONF['sql_prefix']}_menu (menu_id, title, path, sort) VALUES ('1', '{$LNG['main_menu_join']}', '{$CONF['list_url']}/?a=join', 1)", __FILE__, __LINE__);
$DB->query("INSERT INTO {$CONF['sql_prefix']}_menu (menu_id, title, path, sort) VALUES ('1', '{$LNG['main_menu_user_cp']}', '{$CONF['list_url']}/?a=user_cpl', 1)", __FILE__, __LINE__);


$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_menus` (
	`menu_id` int(10) NOT NULL auto_increment,
	`menu_name` varchar(255),
	`menu_weight` int(11),
	`menu_parent` int(11),
	PRIMARY KEY (`menu_id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

$DB->query("INSERT INTO {$CONF['sql_prefix']}_menus (menu_id, menu_name, menu_weight, menu_parent) VALUES ('1', 'Header', 1, 0)", __FILE__, __LINE__);
