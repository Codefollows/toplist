<?php

$IMPORT['a_vote'] = 'Vote';
$IMPORT['vote'] = 'Vote';
$IMPORT['a_s_lostpw_recaptcha'] = 'Add Recaptcha to Lost Password Form';
$IMPORT['a_s_admin_recaptcha'] = 'Add Recaptcha to Admin Login Form';
$IMPORT['a_s_usercp_recaptcha'] = 'Add Recaptcha to User Login Form';
$IMPORT['a_s_gateway_recaptcha'] = 'Add Recaptcha to Vote Gateway';

$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sessions ADD INDEX `delete_query` (`time`, `keep_alive`)", FILE, __LINE__);

$DB->query("ALTER TABLE {$CONF['sql_prefix']}_ip_log
		ADD INDEX `brute_force_check` (`ip_address`, `timestamp`),
		ADD INDEX `vote_check` (`ip_address`, `username`)
", __FILE__, __LINE__);


$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `gateway_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `usercp_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `admin_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_settings ADD `lostpw_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER recaptcha_sitekey", __FILE__, __LINE__);