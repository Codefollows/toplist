<?php
//===========================================================================\\
// VisioList is a proud derivative work of:                                  \\
// Aardvark Topsites PHP                                                     \\
// Copyright (c) 2000-2009 Jeremy Scheff.  All rights reserved.              \\
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
header("Content-type: text/html; charset=utf-8");

// Help prevent register_globals injection
define('ATSPHP', 1); //REMOVE ONCE ALL PLUGINS ARE UPDATED
define('VISIOLIST', 1);
$CONF = array();
$FORM = array();
$TMPL = array();

// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Change the path to your full path if necessary
$CONF['path'] = __DIR__;

// Provide a user ip fix in case site uses cloudflare - previously a plugin
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];

// Require some classes and start the timer
require_once ("{$CONF['path']}/sources/misc/classes.php");
$TIMER = new timer;

// Connect to the database
// Set the last argument of $DB->connect to 1 to enable debug mode
require_once ("{$CONF['path']}/settings_sql.php");
require_once ("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");
require_once ("{$CONF['path']}/button_config.php");
$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database']);

$TMPL['rand'] = rand(1, 1000);

// Settings
$settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
$CONF = array_merge($CONF, $settings);


// The language file
$LNG['charset'] = "utf-8";
require_once ("{$CONF['path']}/languages/english.php");
if ($CONF['default_language'] != 'english') {
	require_once ("{$CONF['path']}/languages/{$CONF['default_language']}.php");
}

//Start Up The Plugin manager
include ("{$CONF['path']}/plugins.php");
pluginManager::getPluginManager();
 
// Lets load The Plugin Language Files
$plugin_dir  = "{$CONF['path']}/plugins/";
$plugin_list = scandir($plugin_dir);
foreach ($plugin_list as $plugin)
{
	if ($plugin != '.' && $plugin != '..') 
	{
		// Look for folders of plugins
		$plugin_path = $plugin_dir . $plugin;
		
		if (is_dir($plugin_path) && strpos($plugin, '0_') === FALSE) 
		{									
			if (file_exists("{$plugin_path}/languages/english.php")) {
				include ("{$plugin_path}/languages/english.php");
			}
			if ($CONF['default_language'] != 'english' && file_exists("{$plugin_path}/languages/{$CONF['default_language']}.php")) {
				include ("{$plugin_path}/languages/{$CONF['default_language']}.php");
			}
		}
	}
}

$CONF['skins_path'] = "{$CONF['path']}/skins";
$CONF['skins_url'] = "{$CONF['list_url']}/skins";
$TMPL['skins_url'] = $CONF['skins_url'];
$TMPL['skin_name'] = $CONF['default_skin'];
$TMPL['list_name'] = $CONF['list_name'];
$TMPL['list_url'] = $CONF['list_url'];

require_once ("{$CONF['path']}/sources/misc/skin.php");

// Is it a new day/week/month?
list($last_new_day, $last_new_week, $last_new_month) =
    $DB->fetch("SELECT last_new_day, last_new_week, last_new_month FROM {$CONF['sql_prefix']}_etc", __file__, __line__);
$time = time() + (3600 * $CONF['time_offset']);
$current_day = date('d', $time);
$current_week = date('W', $time);
$current_month = date('m', $time);
if ($last_new_month != $current_month) {
    require_once ("{$CONF['path']}/sources/misc/new_day.php");
    new_month($current_month);
}
if ($last_new_week != $current_week) {
    require_once ("{$CONF['path']}/sources/misc/new_day.php");
    new_week($current_week);
}
if ($last_new_day != $current_day) {
    require_once ("{$CONF['path']}/sources/misc/new_day.php");
    new_day($current_day);
}

$DB->close();
