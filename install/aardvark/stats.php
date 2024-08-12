<?php

$DB->query("ALTER TABLE {$CONF['sql_prefix']}_stats ADD `join_date` DATETIME NOT NULL AFTER username", __FILE__, __LINE__);
$DB->query("UPDATE {$CONF['sql_prefix']}_stats stats, {$CONF['sql_prefix']}_sites sites SET stats.join_date = sites.join_date WHERE stats.username = sites.username", __FILE__, __LINE__);

// We have this sites edit here, in case include order changes, so it not fucks up the edit above!
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_sites DROP `join_date`", __FILE__, __LINE__);


// Rankings indexes
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_stats
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