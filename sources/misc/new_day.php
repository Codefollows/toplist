<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2007 Jeremy Scheff.  All rights reserved.              \\
//---------------------------------------------------------------------------\\
// http://www.aardvarktopsitesphp.com/                http://www.avatic.com/ \\
//---------------------------------------------------------------------------\\
// This program is free software; you can redistribute it and/or modify it   \\
// under the terms of the GNU General Public License as published by the     \\
// Free Software Foundation; either version 2 of the License, or (at your    \\
// option) any later version.                                                \\
//                                                                           \\
// This program is distributed in the hope that it will be useful, but       \\
// WITHOUT ANY WARRANTY; without even the implied warranty of                \\
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General \\
// Public License for more details.                                          \\
//===========================================================================\\

if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

function new_day($current_day) {
	global $CONF, $DB, $TMPL;

	$startdate = date('Y-m-d');

	$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET premium_flag = 1 WHERE date_start_premium = \"$startdate\" ", __FILE__, __LINE__);

	// Routine to Manage Premium Profile every day
	$result = $DB->query("SELECT username, email, url, remain_day, date_start_premium FROM {$CONF['sql_prefix']}_sites WHERE premium_flag = 1", __FILE__, __LINE__);
  
	while ($row = $DB->fetch_array($result)) {
		
		$TMPL = array_merge($TMPL, $row);
  
		$new_remain_day = 0;
		$user_name = '';
		$user_name = $TMPL['username'];
		$new_remain_day = $TMPL['remain_day'] - 1;
    
		//Disabled Expired premium
		if ($new_remain_day < 1) {
			
			$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET remain_day = 0, premium_flag = 0, premium_request = 0, date_start_premium = NULL, total_day = 0, weeks_buy = 0 WHERE username = '{$user_name}'", __FILE__, __LINE__);
			
			// Here Send Alert E-Mail to User for his info
			$premium_end_email = new skin('premium_end_email');
			$premium_end_email->send_email($TMPL['email']);
		}
		else {
			$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET remain_day = {$new_remain_day} WHERE username = '{$user_name}'", __FILE__, __LINE__); 
		}
	}
	

	$DB->query("UPDATE {$CONF['sql_prefix']}_etc SET last_new_day = {$current_day}", __FILE__, __LINE__);
	$DB->query("TRUNCATE TABLE {$CONF['sql_prefix']}_ip_log", __FILE__, __LINE__);

	$DB->query("UPDATE {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats SET days_inactive = days_inactive + 1 WHERE tot_pv_0_daily = 0 AND tot_in_0_daily = 0 AND active = 1 AND sites.username = stats.username", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET days_inactive = 0 WHERE tot_pv_0_daily > 0 OR tot_in_0_daily > 0", __FILE__, __LINE__);

	if ($CONF['inactive_after'] > 0) {
		
		$result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_stats WHERE days_inactive >= {$CONF['inactive_after']}", __FILE__, __LINE__);
		for ($i = 0; list($username) = $DB->fetch_array($result); $i++) {
			
			if ($i > 0) {
				$inactive_usernames .= ', ';
			}
			else {
				$inactive_usernames = '';
			}
			$inactive_usernames .= "'{$username}'";
		}

		if ($i != 0) {
			$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET active = 3 WHERE active = 1 AND username IN({$inactive_usernames})", __FILE__, __LINE__);
		}
	}

	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_pv_max_daily = unq_pv_0_daily WHERE unq_pv_0_daily > unq_pv_max_daily", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_pv_max_daily = tot_pv_0_daily WHERE tot_pv_0_daily > tot_pv_max_daily", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_in_max_daily = unq_in_0_daily WHERE unq_in_0_daily > unq_in_max_daily", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_in_max_daily = tot_in_0_daily WHERE tot_in_0_daily > tot_in_max_daily", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_out_max_daily = unq_out_0_daily WHERE unq_out_0_daily > unq_out_max_daily", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_out_max_daily = tot_out_0_daily WHERE tot_out_0_daily > tot_out_max_daily", __FILE__, __LINE__);

	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET 
		unq_pv_9_daily = unq_pv_8_daily, unq_pv_8_daily = unq_pv_7_daily, unq_pv_7_daily = unq_pv_6_daily, unq_pv_6_daily = unq_pv_5_daily, unq_pv_5_daily = unq_pv_4_daily, unq_pv_4_daily = unq_pv_3_daily, unq_pv_3_daily = unq_pv_2_daily, unq_pv_2_daily = unq_pv_1_daily, unq_pv_1_daily = unq_pv_0_daily, unq_pv_0_daily = 0,
		tot_pv_9_daily = tot_pv_8_daily, tot_pv_8_daily = tot_pv_7_daily, tot_pv_7_daily = tot_pv_6_daily, tot_pv_6_daily = tot_pv_5_daily, tot_pv_5_daily = tot_pv_4_daily, tot_pv_4_daily = tot_pv_3_daily, tot_pv_3_daily = tot_pv_2_daily, tot_pv_2_daily = tot_pv_1_daily, tot_pv_1_daily = tot_pv_0_daily, tot_pv_0_daily = 0,
		unq_in_9_daily = unq_in_8_daily, unq_in_8_daily = unq_in_7_daily, unq_in_7_daily = unq_in_6_daily, unq_in_6_daily = unq_in_5_daily, unq_in_5_daily = unq_in_4_daily, unq_in_4_daily = unq_in_3_daily, unq_in_3_daily = unq_in_2_daily, unq_in_2_daily = unq_in_1_daily, unq_in_1_daily = unq_in_0_daily, unq_in_0_daily = 0,
		tot_in_9_daily = tot_in_8_daily, tot_in_8_daily = tot_in_7_daily, tot_in_7_daily = tot_in_6_daily, tot_in_6_daily = tot_in_5_daily, tot_in_5_daily = tot_in_4_daily, tot_in_4_daily = tot_in_3_daily, tot_in_3_daily = tot_in_2_daily, tot_in_2_daily = tot_in_1_daily, tot_in_1_daily = tot_in_0_daily, tot_in_0_daily = 0,
		unq_out_9_daily = unq_out_8_daily, unq_out_8_daily = unq_out_7_daily, unq_out_7_daily = unq_out_6_daily, unq_out_6_daily = unq_out_5_daily, unq_out_5_daily = unq_out_4_daily, unq_out_4_daily = unq_out_3_daily, unq_out_3_daily = unq_out_2_daily, unq_out_2_daily = unq_out_1_daily, unq_out_1_daily = unq_out_0_daily, unq_out_0_daily = 0,
		tot_out_9_daily = tot_out_8_daily, tot_out_8_daily = tot_out_7_daily, tot_out_7_daily = tot_out_6_daily, tot_out_6_daily = tot_out_5_daily, tot_out_5_daily = tot_out_4_daily, tot_out_4_daily = tot_out_3_daily, tot_out_3_daily = tot_out_2_daily, tot_out_2_daily = tot_out_1_daily, tot_out_1_daily = tot_out_0_daily, tot_out_0_daily = 0,
		old_rank = rank_cache
	", __FILE__, __LINE__);

	// PREMIUM HITS IN BOOST 
	$result2 = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites WHERE premium_flag = 1", __FILE__, __LINE__);
	while ($row2 = $DB->fetch_array($result2)) {
		
		$TMPL = array_merge($TMPL, $row2);
		$user_name = '';
		$user_name = $TMPL['username'];
     
		if($CONF['new_day_boost'] > 0) {
			
			$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET 
				unq_in_0_daily = unq_in_0_daily + '{$CONF['new_day_boost']}', tot_in_0_daily = tot_in_0_daily + '{$CONF['new_day_boost']}',
				unq_in_0_weekly = unq_in_0_weekly + '{$CONF['new_day_boost']}', tot_in_0_weekly = tot_in_0_weekly + '{$CONF['new_day_boost']}',
				unq_in_0_monthly = unq_in_0_monthly + '{$CONF['new_day_boost']}', tot_in_0_monthly = tot_in_0_monthly + '{$CONF['new_day_boost']}',
				unq_in_overall = unq_in_overall + '{$CONF['new_day_boost']}', tot_in_overall = tot_in_overall + '{$CONF['new_day_boost']}'
			WHERE username = '{$user_name}'", __FILE__, __LINE__);
		}
	}

	// Plugin Hook :: Execute stuff daily
	eval (PluginManager::getPluginManager ()->pluginHooks ('new_day'));
}

function new_week($current_week) {
	global $CONF, $DB, $TMPL;
  
	$DB->query("UPDATE {$CONF['sql_prefix']}_etc SET last_new_week = {$current_week}", __FILE__, __LINE__);

	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_pv_max_weekly = unq_pv_0_weekly WHERE unq_pv_0_weekly > unq_pv_max_weekly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_pv_max_weekly = tot_pv_0_weekly WHERE tot_pv_0_weekly > tot_pv_max_weekly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_in_max_weekly = unq_in_0_weekly WHERE unq_in_0_weekly > unq_in_max_weekly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_in_max_weekly = tot_in_0_weekly WHERE tot_in_0_weekly > tot_in_max_weekly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_out_max_weekly = unq_out_0_weekly WHERE unq_out_0_weekly > unq_out_max_weekly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_out_max_weekly = tot_out_0_weekly WHERE tot_out_0_weekly > tot_out_max_weekly", __FILE__, __LINE__);

	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET 
		unq_pv_9_weekly = unq_pv_8_weekly, unq_pv_8_weekly = unq_pv_7_weekly, unq_pv_7_weekly = unq_pv_6_weekly, unq_pv_6_weekly = unq_pv_5_weekly, unq_pv_5_weekly = unq_pv_4_weekly, unq_pv_4_weekly = unq_pv_3_weekly, unq_pv_3_weekly = unq_pv_2_weekly, unq_pv_2_weekly = unq_pv_1_weekly, unq_pv_1_weekly = unq_pv_0_weekly, unq_pv_0_weekly = 0,
		tot_pv_9_weekly = tot_pv_8_weekly, tot_pv_8_weekly = tot_pv_7_weekly, tot_pv_7_weekly = tot_pv_6_weekly, tot_pv_6_weekly = tot_pv_5_weekly, tot_pv_5_weekly = tot_pv_4_weekly, tot_pv_4_weekly = tot_pv_3_weekly, tot_pv_3_weekly = tot_pv_2_weekly, tot_pv_2_weekly = tot_pv_1_weekly, tot_pv_1_weekly = tot_pv_0_weekly, tot_pv_0_weekly = 0,
		unq_in_9_weekly = unq_in_8_weekly, unq_in_8_weekly = unq_in_7_weekly, unq_in_7_weekly = unq_in_6_weekly, unq_in_6_weekly = unq_in_5_weekly, unq_in_5_weekly = unq_in_4_weekly, unq_in_4_weekly = unq_in_3_weekly, unq_in_3_weekly = unq_in_2_weekly, unq_in_2_weekly = unq_in_1_weekly, unq_in_1_weekly = unq_in_0_weekly, unq_in_0_weekly = 0,
		tot_in_9_weekly = tot_in_8_weekly, tot_in_8_weekly = tot_in_7_weekly, tot_in_7_weekly = tot_in_6_weekly, tot_in_6_weekly = tot_in_5_weekly, tot_in_5_weekly = tot_in_4_weekly, tot_in_4_weekly = tot_in_3_weekly, tot_in_3_weekly = tot_in_2_weekly, tot_in_2_weekly = tot_in_1_weekly, tot_in_1_weekly = tot_in_0_weekly, tot_in_0_weekly = 0,
		unq_out_9_weekly = unq_out_8_weekly, unq_out_8_weekly = unq_out_7_weekly, unq_out_7_weekly = unq_out_6_weekly, unq_out_6_weekly = unq_out_5_weekly, unq_out_5_weekly = unq_out_4_weekly, unq_out_4_weekly = unq_out_3_weekly, unq_out_3_weekly = unq_out_2_weekly, unq_out_2_weekly = unq_out_1_weekly, unq_out_1_weekly = unq_out_0_weekly, unq_out_0_weekly = 0,
		tot_out_9_weekly = tot_out_8_weekly, tot_out_8_weekly = tot_out_7_weekly, tot_out_7_weekly = tot_out_6_weekly, tot_out_6_weekly = tot_out_5_weekly, tot_out_5_weekly = tot_out_4_weekly, tot_out_4_weekly = tot_out_3_weekly, tot_out_3_weekly = tot_out_2_weekly, tot_out_2_weekly = tot_out_1_weekly, tot_out_1_weekly = tot_out_0_weekly, tot_out_0_weekly = 0
	", __FILE__, __LINE__);

	$result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites WHERE premium_flag = 1", __FILE__, __LINE__);
	while ($row = $DB->fetch_array($result)) {
		
		$TMPL = array_merge($TMPL, $row);
		$user_name = '';
		$user_name = $TMPL['username'];
		
		//HITS IN BOOST
		if($CONF['new_week_boost'] > 0) {
			
			$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET 
				unq_in_0_daily = unq_in_0_daily + '{$CONF['new_week_boost']}', tot_in_0_daily = tot_in_0_daily + '{$CONF['new_week_boost']}',
				unq_in_0_weekly = unq_in_0_weekly + '{$CONF['new_week_boost']}', tot_in_0_weekly = tot_in_0_weekly + '{$CONF['new_week_boost']}',
				unq_in_0_monthly = unq_in_0_monthly + '{$CONF['new_week_boost']}', tot_in_0_monthly = tot_in_0_monthly + '{$CONF['new_week_boost']}',
				unq_in_overall = unq_in_overall + '{$CONF['new_week_boost']}', tot_in_overall = tot_in_overall + '{$CONF['new_week_boost']}'
			WHERE username = '{$user_name}'", __FILE__, __LINE__);
		}
	}

	// Plugin Hook :: Execute stuff weekly
	eval (PluginManager::getPluginManager ()->pluginHooks ('new_week'));
}

function new_month($current_month) {
	global $CONF, $DB, $TMPL;

	$DB->query("UPDATE {$CONF['sql_prefix']}_etc SET last_new_month = {$current_month}", __FILE__, __LINE__);

	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_pv_max_monthly = unq_pv_0_monthly WHERE unq_pv_0_monthly > unq_pv_max_monthly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_pv_max_monthly = tot_pv_0_monthly WHERE tot_pv_0_monthly > tot_pv_max_monthly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_in_max_monthly = unq_in_0_monthly WHERE unq_in_0_monthly > unq_in_max_monthly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_in_max_monthly = tot_in_0_monthly WHERE tot_in_0_monthly > tot_in_max_monthly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET unq_out_max_monthly = unq_out_0_monthly WHERE unq_out_0_monthly > unq_out_max_monthly", __FILE__, __LINE__);
	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_out_max_monthly = tot_out_0_monthly WHERE tot_out_0_monthly > tot_out_max_monthly", __FILE__, __LINE__);

	$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET 
		unq_pv_9_monthly = unq_pv_8_monthly, unq_pv_8_monthly = unq_pv_7_monthly, unq_pv_7_monthly = unq_pv_6_monthly, unq_pv_6_monthly = unq_pv_5_monthly, unq_pv_5_monthly = unq_pv_4_monthly, unq_pv_4_monthly = unq_pv_3_monthly, unq_pv_3_monthly = unq_pv_2_monthly, unq_pv_2_monthly = unq_pv_1_monthly, unq_pv_1_monthly = unq_pv_0_monthly, unq_pv_0_monthly = 0,
		tot_pv_9_monthly = tot_pv_8_monthly, tot_pv_8_monthly = tot_pv_7_monthly, tot_pv_7_monthly = tot_pv_6_monthly, tot_pv_6_monthly = tot_pv_5_monthly, tot_pv_5_monthly = tot_pv_4_monthly, tot_pv_4_monthly = tot_pv_3_monthly, tot_pv_3_monthly = tot_pv_2_monthly, tot_pv_2_monthly = tot_pv_1_monthly, tot_pv_1_monthly = tot_pv_0_monthly, tot_pv_0_monthly = 0,
		unq_in_9_monthly = unq_in_8_monthly, unq_in_8_monthly = unq_in_7_monthly, unq_in_7_monthly = unq_in_6_monthly, unq_in_6_monthly = unq_in_5_monthly, unq_in_5_monthly = unq_in_4_monthly, unq_in_4_monthly = unq_in_3_monthly, unq_in_3_monthly = unq_in_2_monthly, unq_in_2_monthly = unq_in_1_monthly, unq_in_1_monthly = unq_in_0_monthly, unq_in_0_monthly = 0,
		tot_in_9_monthly = tot_in_8_monthly, tot_in_8_monthly = tot_in_7_monthly, tot_in_7_monthly = tot_in_6_monthly, tot_in_6_monthly = tot_in_5_monthly, tot_in_5_monthly = tot_in_4_monthly, tot_in_4_monthly = tot_in_3_monthly, tot_in_3_monthly = tot_in_2_monthly, tot_in_2_monthly = tot_in_1_monthly, tot_in_1_monthly = tot_in_0_monthly, tot_in_0_monthly = 0,
		unq_out_9_monthly = unq_out_8_monthly, unq_out_8_monthly = unq_out_7_monthly, unq_out_7_monthly = unq_out_6_monthly, unq_out_6_monthly = unq_out_5_monthly, unq_out_5_monthly = unq_out_4_monthly, unq_out_4_monthly = unq_out_3_monthly, unq_out_3_monthly = unq_out_2_monthly, unq_out_2_monthly = unq_out_1_monthly, unq_out_1_monthly = unq_out_0_monthly, unq_out_0_monthly = 0,
		tot_out_9_monthly = tot_out_8_monthly, tot_out_8_monthly = tot_out_7_monthly, tot_out_7_monthly = tot_out_6_monthly, tot_out_6_monthly = tot_out_5_monthly, tot_out_5_monthly = tot_out_4_monthly, tot_out_4_monthly = tot_out_3_monthly, tot_out_3_monthly = tot_out_2_monthly, tot_out_2_monthly = tot_out_1_monthly, tot_out_1_monthly = tot_out_0_monthly, tot_out_0_monthly = 0
	", __FILE__, __LINE__);


	$result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites WHERE premium_flag = 1", __FILE__, __LINE__);
	while ($row = $DB->fetch_array($result)) {
		
		$TMPL = array_merge($TMPL, $row);
		$user_name = '';
		$user_name = $TMPL['username'];
		
		//HITS IN BOOST
		if($CONF['new_month_boost'] > 0) {
			
			$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET 
				unq_in_0_daily = unq_in_0_daily + '{$CONF['new_month_boost']}', tot_in_0_daily = tot_in_0_daily + '{$CONF['new_month_boost']}',
				unq_in_0_weekly = unq_in_0_weekly + '{$CONF['new_month_boost']}', tot_in_0_weekly = tot_in_0_weekly + '{$CONF['new_month_boost']}',
				unq_in_0_monthly = unq_in_0_monthly + '{$CONF['new_month_boost']}', tot_in_0_monthly = tot_in_0_monthly + '{$CONF['new_month_boost']}',
				unq_in_overall = unq_in_overall + '{$CONF['new_month_boost']}', tot_in_overall = tot_in_overall + '{$CONF['new_month_boost']}'
			WHERE username = '{$user_name}'", __FILE__, __LINE__);
		}
	}

	// Plugin Hook :: Execute stuff monthly
	eval (PluginManager::getPluginManager ()->pluginHooks ('new_month'));  
}
