<?php

$IMPORT['a_man_jf_intro1'] = 'You can drag and drop fields between active and inactive columns without saving';
$IMPORT['a_man_jf_intro2'] = 'To make use of these form fields, collapse a field and add the tags displayed into the specified html files. Failing to add the form tags into their respective files will result in invisible errors';
$IMPORT['a_man_jf_tag_form'] = 'Template Tags: Form';
$IMPORT['a_man_jf_tag_user'] = 'Template Tags: User selected value';
$IMPORT['a_man_jf_tag_user_sub'] = 'For example';
$IMPORT['a_man_jf_show_on_join_option1'] = 'Join and edit form';
$IMPORT['a_man_jf_show_on_join_option2'] = 'Edit form only';

$IMPORT['user_cp_banner_error_1'] = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
$IMPORT['user_cp_banner_error_2'] = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
$IMPORT['user_cp_banner_error_3'] = 'The uploaded file was only partially uploaded';
$IMPORT['user_cp_banner_error_6'] = 'Missing a temporary folder';
$IMPORT['user_cp_banner_error_7'] = 'Failed to write file to disk';
$IMPORT['user_cp_banner_error_8'] = 'A PHP extension stopped the file upload';
$IMPORT['user_cp_unsubscribe'] = 'Do not send me admin news and notification emails';


$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'Internal value' WHERE phrase_name = 'a_man_jf_internal' AND language = 'english'", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'Display value' WHERE phrase_name = 'a_man_jf_display_value' AND language = 'english'", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'Display location admin edit user form' WHERE phrase_name = 'a_man_jf_display_info' AND language = 'english'", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'On which forms to display? No effect on admin edit user' WHERE phrase_name = 'a_man_jf_show_on_join' AND language = 'english'", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_langs SET definition = 'Form Placement' WHERE phrase_name = 'a_man_jf_tab4' AND language = 'english'", __FILE__, __LINE__);


//add unsubscribe option
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites ADD `unsubscribe` tinyint(1) default 0 AFTER email", __FILE__, __LINE__);


// join_date move into stats table
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_stats ADD `join_date` DATETIME NOT NULL AFTER username", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_stats stats, {$CONF['sql_prefix']}_sites sites SET stats.join_date = sites.join_date WHERE stats.username = sites.username", __FILE__, __LINE__);
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites DROP `join_date`", __FILE__, __LINE__);

// rework indexes, since mysql 5.6 is still a thing, we have to drop and re-add
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_stats
	DROP INDEX `rank_in_daily`,
	DROP INDEX `rank_in_weekly`,
	DROP INDEX `rank_in_monthly`,
	DROP INDEX `rank_out_daily`,
	DROP INDEX `rank_out_weekly`,
	DROP INDEX `rank_out_monthly`,
	DROP INDEX `rank_pv_daily`,
	DROP INDEX `rank_pv_weekly`,
	DROP INDEX `rank_pv_monthly`,
	ADD INDEX `rank_join_date` (`join_date`),
	ADD INDEX `rank_in_daily` (`unq_in_0_daily`, `unq_in_overall`, `join_date`),
	ADD INDEX `rank_in_weekly` (`unq_in_0_weekly`, `unq_in_overall`, `join_date`),
	ADD INDEX `rank_in_monthly` (`unq_in_0_monthly`, `unq_in_overall`, `join_date`),
	ADD INDEX `rank_out_daily` (`unq_out_0_daily`, `unq_out_overall`, `join_date`),
	ADD INDEX `rank_out_weekly` (`unq_out_0_weekly`, `unq_out_overall`, `join_date`),
	ADD INDEX `rank_out_monthly` (`unq_out_0_monthly`, `unq_out_overall`, `join_date`),
	ADD INDEX `rank_pv_daily` (`unq_pv_0_daily`, `unq_pv_overall`, `join_date`),
	ADD INDEX `rank_pv_weekly` (`unq_pv_0_weekly`, `unq_pv_overall`, `join_date`),
	ADD INDEX `rank_pv_monthly` (`unq_pv_0_monthly`, `unq_pv_overall`, `join_date`)
", __FILE__, __LINE__);
