<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
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

// Help prevent register_globals injection
define('VISIOLIST', 1);
$CONF = array();
$FORM = array();
$TMPL = array();

// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Change the path to your full path if necessary
$CONF['path'] = __DIR__;

// Connect to the database
require_once("{$CONF['path']}/settings_sql.php");
require_once("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");
$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database']);

// Settings
$settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
$CONF = array_merge($CONF, $settings);

/* Make sure the site uses 
** www. or non-www domain 
** http or https
** As set in settings
*/
if ($CONF['list_url'] != 'http://localhost')
{
    $canonical_domain_info = parse_url($CONF['list_url']);
    $visitor_scheme        = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';

    if ($_SERVER['HTTP_HOST'] != $canonical_domain_info['host'] || $visitor_scheme != $canonical_domain_info['scheme']) 
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: '.$canonical_domain_info['scheme'].'://'.$canonical_domain_info['host'].$_SERVER['REQUEST_URI']);
        exit;
    }
}

$CONF['skins_path'] = "{$CONF['path']}/skins";
$CONF['skins_url'] = "{$CONF['list_url']}/skins";
$TMPL['skins_url'] = $CONF['skins_url'];
$TMPL['list_name'] = $CONF['list_name'];
$TMPL['list_url'] = $CONF['list_url'];

// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

// The language file
$LNG['charset'] = "utf-8";
require_once("{$CONF['path']}/languages/english.php");
if ($CONF['default_language'] != 'english') {
	require_once ("{$CONF['path']}/languages/{$CONF['default_language']}.php");
}

// URL Helpers
if ($CONF['clean_url'] == 1) {
    $TMPL['url_tail'] = '/';
    $TMPL['url_helper_a'] = '';
    $TMPL['url_helper_u'] = '/';
    $TMPL['url_helper_cat'] = 'category/';
    $TMPL['url_helper_cat2'] = '/category/';
    $TMPL['url_helper_rate'] = 'review';
    $TMPL['url_helper_b'] = '/';
    $TMPL['url_helper_id'] = '/';
    $TMPL['url_helper_method'] = 'rank/';
    $TMPL['url_helper_q'] = '/';
    $TMPL['url_helper_start'] = '/';
}
else {
    $TMPL['url_tail'] = '';
    $TMPL['url_helper_a'] = '?a=';
    $TMPL['url_helper_u'] = '&amp;u=';
    $TMPL['url_helper_cat'] = '?cat=';
    $TMPL['url_helper_cat2'] = '&amp;cat=';
    $TMPL['url_helper_rate'] = 'rate';
    $TMPL['url_helper_b'] = '&amp;b=';
    $TMPL['url_helper_id'] = '&amp;id=';
    $TMPL['url_helper_method'] = '?method=';
    $TMPL['url_helper_q'] = '&amp;q=';
    $TMPL['url_helper_start'] = '&amp;start=';
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

//Hook Location
eval(PluginManager::getPluginManager()->pluginHooks('ssi_global_start'));


// The skin
$TMPL['skin_name'] = $CONF['default_skin'];
require_once("{$CONF['path']}/sources/misc/skin.php");

// Top
if (!isset($FORM['a']) || isset($FORM['a']) && $FORM['a'] == 'top') {
	
  if (isset($FORM['num'])) {
    $TMPL['num'] = intval($FORM['num']);
  }
  if (!isset($TMPL['num']) || !$TMPL['num']) {
    $TMPL['num'] = 5;
  }

  $TMPL['sites'] = '';

  require_once("{$CONF['path']}/sources/misc/classes.php");
  $order_by = base::rank_by()." DESC";

  $result = $DB->select_limit("SELECT *
                               FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats
                               WHERE sites.username = stats.username AND active = 1
                               ORDER BY {$order_by}
                              ", $TMPL['num'], 0, __FILE__, __LINE__);

  $page_rank = 1;
  while ($row = $DB->fetch_array($result)) {
	  
	$row = array_map(function($value) {
		return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
	}, $row);
		
    $TMPL = array_merge($TMPL, $row);

    eval (PluginManager::getPluginManager ()->pluginHooks ('ssi_top_compile_stats'));

    $skin = new skin('ssi_top_row');
    $TMPL['sites'] .= $skin->make();

    $page_rank++;
  }

  $LNG['ssi_top'] = sprintf($LNG['ssi_top'], $TMPL['num']);

  $skin = new skin('ssi_top');
}

// New
if (isset($FORM['a']) && $FORM['a'] == 'new') {
  if (isset($FORM['num'])) {
    $TMPL['num'] = intval($FORM['num']);
  }
  if (!isset($TMPL['num']) || !$TMPL['num']) {
    $TMPL['num'] = 5;
  }

  $TMPL['sites'] = '';

  $result = $DB->select_limit("SELECT *
                               FROM {$CONF['sql_prefix']}_sites
                               WHERE active = 1
                               ORDER BY join_date DESC
                              ", $TMPL['num'], 0, __FILE__, __LINE__);

  $page_rank = 1;
  while ($row = $DB->fetch_array($result)) {
	  
	$row = array_map(function($value) {
		return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
	}, $row);
	
    $TMPL = array_merge($TMPL, $row);

    eval (PluginManager::getPluginManager ()->pluginHooks ('ssi_new_compile_stats'));

    $skin = new skin('ssi_new_row');
    $TMPL['sites'] .= $skin->make();

    $page_rank++;
  }

  $LNG['ssi_new'] = sprintf($LNG['ssi_new'], $TMPL['num']);

  $skin = new skin('ssi_new');
}


//Hook Location, can be used to overwrite above completley or extend with new options for FORM['a']
eval(PluginManager::getPluginManager()->pluginHooks('ssi_global_end'));

echo $skin->make();

$DB->close();
