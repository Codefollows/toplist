<?php

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_join_fields` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`field_id` varchar(150) NOT NULL,
	`label_text` varchar(255) NOT NULL,
	`description` text NOT NULL,
	`field_type` varchar(10) NOT NULL DEFAULT 'textbox',
	`required` tinyint(1) unsigned NOT NULL DEFAULT 0,
	`field_text_requirements` varchar(6) NOT NULL DEFAULT 'none',
	`field_text_enable_length` varchar(5) NOT NULL DEFAULT 'none',
	`field_text_length` varchar(100) NOT NULL DEFAULT '',
	`field_text_input_size` tinyint(1) unsigned NOT NULL DEFAULT 50,
	`field_choice_value` varchar(255) NOT NULL,
	`field_choice_text` varchar(255) NOT NULL,
	`display_location` varchar(7) NOT NULL DEFAULT 'website',
	`show_join_edit` tinyint(1) NOT NULL DEFAULT 1,
	`field_sort` tinyint(1) unsigned NOT NULL DEFAULT 255,
	`field_status` tinyint(1) unsigned NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);