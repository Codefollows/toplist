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
header('Content-Type: application/xml; charset=utf-8');

// Help prevent register_globals injection
define('VISIOLIST', 1);
$CONF = array();
$FORM = array();
$TMPL = array();
date_default_timezone_set('America/Los_Angeles');

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

    if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != $canonical_domain_info['host'] || $visitor_scheme != $canonical_domain_info['scheme']) 
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: '.$canonical_domain_info['scheme'].'://'.$canonical_domain_info['host'].$_SERVER['REQUEST_URI']);
        exit;
    }
}

// Default php date timezone
date_default_timezone_set($CONF['time_zone']);

// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

if($CONF['clean_url'] == 1) {
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
  $TMPL['url_helper_page'] = '/';
} 
else{
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
  $TMPL['url_helper_page'] = '&amp;p=';
}

// The language file
$LNG['charset'] = "utf-8";
require_once("{$CONF['path']}/languages/english.php");
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

//Hook Location
eval(PluginManager::getPluginManager()->pluginHooks('sitemap_global_start'));

$CONF['skins_path'] = "{$CONF['path']}/skins";
$CONF['skins_url'] = "{$CONF['list_url']}/skins";
$TMPL['skins_url'] = $CONF['skins_url'];
$TMPL['skin_name'] = $CONF['default_skin'];
$TMPL['list_name'] = $CONF['list_name'];
$TMPL['list_url'] = $CONF['list_url'];

require_once ("{$CONF['path']}/sources/misc/skin.php");

// Global use date
$TMPL['lastmod'] = date('c');

echo '<?xml version="1.0" encoding="'.$LNG['charset'].'"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

	<url>
		<loc><?php echo $TMPL['list_url']; ?></loc>
		<lastmod><?php echo $TMPL['lastmod']; ?></lastmod>
		<changefreq>daily</changefreq>
		<priority>1.0</priority>
	</url>
<?php
	$result = $DB->query("SELECT category_slug FROM {$CONF['sql_prefix']}_categories", __FILE__, __LINE__);
	while(list($category_slug) = $DB->fetch_array($result)) {

		$category_slug = urlencode($category_slug);
?>
		<url>
			<loc><?php echo "{$TMPL['list_url']}/{$TMPL['url_helper_cat']}{$category_slug}{$TMPL['url_tail']}"; ?></loc>
			<lastmod><?php echo $TMPL['lastmod']; ?></lastmod>
			<changefreq>daily</changefreq>
			<priority>0.9</priority>
		</url>
<?php
	}

	$result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites", __FILE__, __LINE__);
	while(list($TMPL['username']) = $DB->fetch_array($result)) {
?>
		<url>
			<loc><?php echo "{$TMPL['list_url']}/{$TMPL['url_helper_a']}details{$TMPL['url_helper_u']}{$TMPL['username']}{$TMPL['url_tail']}"; ?></loc>
			<lastmod><?php echo $TMPL['lastmod']; ?></lastmod>
			<changefreq>monthly</changefreq>
			<priority>0.7</priority>
		</url>
<?php
	}

	$result = $DB->query("SELECT id FROM {$CONF['sql_prefix']}_custom_pages", __FILE__, __LINE__);
	while(list($TMPL['id']) = $DB->fetch_array($result)) {
?>
		<url>
			<loc><?php echo "{$TMPL['list_url']}/{$TMPL['url_helper_a']}page{$TMPL['url_helper_id']}{$TMPL['id']}{$TMPL['url_tail']}"; ?></loc>
			<lastmod><?php echo $TMPL['lastmod']; ?></lastmod>
			<changefreq>monthly</changefreq>
			<priority>0.7</priority>
		</url>
<?php
	}

	eval(PluginManager::getPluginManager()->pluginHooks('sitemap_urlset'));
?>
</urlset>

<?php

$DB->close();
