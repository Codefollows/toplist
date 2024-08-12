<?php

// New language
$IMPORT['a_skins_category_url'] = 'Category URL (a-zA-Z, 0-9, and - only) - Auto generated if left empty';
$IMPORT['g_404'] = '404 - Page not found';
$IMPORT['payment_ipn_status_canceled'] = 'Canceled';
$IMPORT['payment_ipn_status_canceled_reversal'] = 'Canceled Reversal';
$IMPORT['payment_ipn_status_completed'] = 'Completed';
$IMPORT['payment_ipn_status_created'] = 'Created';
$IMPORT['payment_ipn_status_denied'] = 'Denied';
$IMPORT['payment_ipn_status_expired'] = 'Expired';
$IMPORT['payment_ipn_status_failed'] = 'Failed';
$IMPORT['payment_ipn_status_pending'] = 'Pending';
$IMPORT['payment_ipn_status_pending_refund'] = 'Pending refund';
$IMPORT['payment_ipn_status_processed'] = 'Processed';
$IMPORT['payment_ipn_status_refunded'] = 'Refunded';
$IMPORT['payment_ipn_status_rejected'] = 'Rejected';
$IMPORT['payment_ipn_status_reversed'] = 'Reversed';
$IMPORT['payment_ipn_status_unrecognized'] = 'Unrecognized';
$IMPORT['payment_ipn_status_voided'] = 'Voided';

// Delete lng phrases
// Also unset the auto generated imports so they not inserted again!
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_s_adscaptcha'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_s_adscaptcha_clickhere'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_s_adscaptcha_id'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_s_adscaptcha_info'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_s_adscaptcha_priv_key'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'a_s_adscaptcha_pub_key'", __FILE__, __LINE__);
$DB->query("DELETE FROM {$CONF['sql_prefix']}_langs WHERE phrase_name = 'upgrade_adscaptcha'", __FILE__, __LINE__);

if (isset($IMPORT['a_s_adscaptcha'])) { unset($IMPORT['a_s_adscaptcha']); }
if (isset($IMPORT['a_s_adscaptcha_clickhere'])) { unset($IMPORT['a_s_adscaptcha_clickhere']); }
if (isset($IMPORT['a_s_adscaptcha_id'])) { unset($IMPORT['a_s_adscaptcha_id']); }
if (isset($IMPORT['a_s_adscaptcha_info'])) { unset($IMPORT['a_s_adscaptcha_info']); }
if (isset($IMPORT['a_s_adscaptcha_priv_key'])) { unset($IMPORT['a_s_adscaptcha_priv_key']); }
if (isset($IMPORT['a_s_adscaptcha_pub_key'])) { unset($IMPORT['a_s_adscaptcha_pub_key']); }
if (isset($IMPORT['upgrade_adscaptcha'])) { unset($IMPORT['upgrade_adscaptcha']); }


// Google meta description update
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_custom_pages` CHANGE `description` `description` varchar(320) default ''", __FILE__, __LINE__);
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_categories` CHANGE `cat_description` `cat_description` varchar(320)", __FILE__, __LINE__);

// allow users to set own category urls, for this need to store old category slugs
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_categories` ADD `old_slugs` TEXT NULL DEFAULT NULL AFTER `category_slug`", __FILE__, __LINE__);

// Old description was default '' triggering strict mode error
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites CHANGE `description` `description` TEXT NULL DEFAULT NULL", __FILE__, __LINE__);


// Payment system
$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings` ADD `payment_providers` TEXT NULL DEFAULT NULL", __FILE__, __LINE__);

require_once("{$CONF['path']}/sources/misc/Payment.php");
require_once("{$CONF['path']}/install/providers.php");

$Payment = new Payment();
$Payment->insertProviders($providers);


$DB->query("ALTER TABLE `{$CONF['sql_prefix']}_settings`
	DROP `email_pay`,
	DROP `pp_item_description`,
	DROP `paypal_sandbox`,
	DROP `tk_pp`,
	DROP `acct2co`,
	DROP `adscaptcha`,
	DROP `adscaptcha_id`,
	DROP `adscaptcha_public`,
	DROP `adscaptcha_private`
", __FILE__, __LINE__);


$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_payment_logs` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`txn_id` varchar(255) NOT NULL,
	`provider` varchar(255) NOT NULL,
	`completed_once` tinyint(3) unsigned NOT NULL DEFAULT 0,
	`updated_at` timestamp NULL DEFAULT NULL,
	`status` varchar(255) NOT NULL,
	`status_reason` text DEFAULT NULL,
	`username` varchar(255) DEFAULT NULL,
	`cheat` tinyint(3) unsigned NOT NULL DEFAULT 0,
	`cheat_reason` varchar(255) DEFAULT NULL,
	`service` varchar(255) NOT NULL,
	`service_info` text DEFAULT NULL,
	`price` decimal(10,2) NOT NULL DEFAULT 0.00,
	`discount` decimal(10,2) NOT NULL DEFAULT 0.00,
	`payed` decimal(10,2) NOT NULL DEFAULT 0.00,
	`fee` decimal(10,2) NOT NULL DEFAULT 0.00,
	`payment_date` timestamp NULL DEFAULT NULL,
	`email` varchar(255) DEFAULT NULL,
	`country` varchar(255) NOT NULL DEFAULT 'N/A',
	`country_code` varchar(255) NOT NULL DEFAULT 'N/A',
	`state` varchar(255) NOT NULL DEFAULT 'N/A',
	`city` varchar(255) NOT NULL DEFAULT 'N/A',
	`street` varchar(255) NOT NULL DEFAULT 'N/A',
	`zip` varchar(255) NOT NULL DEFAULT 'N/A',
	`fname` varchar(255) NOT NULL DEFAULT 'N/A',
	`lname` varchar(255) NOT NULL DEFAULT 'N/A',
	PRIMARY KEY (`id`),
	INDEX `username` (`username`),
	INDEX `txn_id` (`txn_id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_payment_logs_error` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`txn_id` varchar(255) DEFAULT NULL,
	`provider` varchar(255) NOT NULL,
	`reason` text DEFAULT NULL,
	PRIMARY KEY (`id`),
	INDEX `txn_id` (`txn_id`)
)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);
