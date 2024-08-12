<?php

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_payment_logs_error` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`txn_id` varchar(255) DEFAULT NULL,
	`provider` varchar(255) NOT NULL,
	`reason` text DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `txn_id` (`txn_id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);
