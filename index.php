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
session_start();

header("Content-type: text/html; charset=utf-8");

// Help prevent register_globals injection
define('VISIOLIST', 1);
$CONF = array();
$FORM = array();
$TMPL = array();
date_default_timezone_set('America/Los_Angeles');


// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

// Enable when coding
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ALL);

// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Change the path to your full path if necessary
$CONF['path'] = __DIR__;
$TMPL['version'] = '1.9';

//If you set a cron job manually, set this to 1
$CONF['cron'] = '0';

// Set to 1 to display SQL queries and GET/POST/COOKIE data
$CONF['debug'] = 0;

// Provide a user ip fix in case site uses cloudflare - previously a plugin
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];

// Require some classes and start the timer
require_once ("{$CONF['path']}/sources/misc/classes.php");
require_once ("{$CONF['path']}/sources/misc/form.php");
require_once ("{$CONF['path']}/sources/misc/validate.php");
$TIMER = new timer;

// Connect to the database
// Set the last argument of $DB->connect to 1 to enable debug mode
require_once ("{$CONF['path']}/settings_sql.php");
require_once ("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");
require_once ("{$CONF['path']}/button_config.php");
$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database'], $CONF['debug']);

$TMPL['rand'] = rand(1, 1000);

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

// Make sure the site not uses site.com/index.php - Should redirect to domain only
if (preg_match('~/index.php$~D', $_SERVER['REQUEST_URI']))
{
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: {$CONF['list_url']}/");
    exit;
}

// Redirect old filenames
if (preg_match('/\?/', $_SERVER['REQUEST_URI']))
{
	if (isset($FORM['a']) && $FORM['a'] == 'stats')
	{
		$old_details_redirect = '?a=details';

		if (isset($FORM['u'])) { $old_details_redirect .= '&u='.$FORM['u']; }
		if (isset($FORM['all_reviews']) && $FORM['all_reviews'] == 1) { $old_details_redirect .= '&all_reviews='.$FORM['all_reviews']; }

		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$CONF['list_url']}/{$old_details_redirect}");
		exit;
	}
}
elseif (preg_match('~/stats/$~D', $_SERVER['REQUEST_URI']))
{
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: {$CONF['list_url']}/overall-stats/");
    exit;
}
elseif (preg_match('~/stats/([^/]*)/$~D', $_SERVER['REQUEST_URI'], $matches))
{
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: {$CONF['list_url']}/details/{$matches[1]}/");
    exit;
}

// Default php date timezone
date_default_timezone_set($CONF['time_zone']);

// Hide PV Data if not enabled
$TMPL['pv_hide'] = '';
if ($CONF['count_pv'] != 1) {
    $TMPL['pv_hide'] = ' style="display: none;"';
}

//  Provide template tag for current year
$TMPL['current_year'] = date('Y', time() + (3600 * $CONF['time_offset']));

// The language file
$LNG['charset'] = "utf-8";
require_once ("{$CONF['path']}/languages/english.php");
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
    $TMPL['url_helper_page'] = '/';
} else {
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

// Ad Breaks
$ad_breaks = explode(',', $CONF['ad_breaks']);
$CONF['ad_breaks'] = array();
foreach ($ad_breaks as $key => $value) {
    $CONF['ad_breaks'][$value] = $value;
}

// Header and footer javascript files
$TMPL['header_js_files'] = '';
$TMPL['footer_js_files'] = '';

//Initialize some vars for skin plugins, these are globally in template, so init here
$TMPL['header'] = '';
$TMPL['css_styles'] = '';
$TMPL['head_extra'] = '';
$TMPL['front_page_top'] = '';
$TMPL['front_page_after_content'] = '';
$TMPL['sidebar_1_top'] = '';
$TMPL['sidebar_1_bottom'] = '';
$TMPL['footer_content'] = '';
$TMPL['footer_1'] = '';
$TMPL['footer_2'] = '';
$TMPL['extra_javascripts'] = '';
$TMPL['wrapper_welcome'] = '';
$TMPL['wrapper_username'] = '';
$TMPL['category_welcome'] = '';
$TMPL['user_cp_main_menu'] = '';
$TMPL['user_cp_links'] = '';
$TMPL['user_cp_score'] = '';
$TMPL['table_wrap_before_content'] = '';
$TMPL['table_wrap_after_content'] = '';

$TMPL['currency_code'] = $CONF['currency_code'];
$TMPL['currency_symbol'] = $CONF['currency_symbol'];
$TMPL['payment_checkout_url']  = "{$CONF['list_url']}/index.php?a=payment_checkout";

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
eval(PluginManager::getPluginManager()->pluginHooks('global_start'));


$CONF['skins_path'] = "{$CONF['path']}/skins";
$CONF['skins_url'] = "{$CONF['list_url']}/skins";
$TMPL['skins_url'] = $CONF['skins_url'];
$TMPL['list_name'] = $CONF['list_name'];
$TMPL['list_url'] = $CONF['list_url'];

$result = $DB->query("SELECT category, category_slug, old_slugs, skin, cat_description, cat_keywords FROM {$CONF['sql_prefix']}_categories ORDER BY category", __FILE__, __LINE__);
while (list($category, $category_slug, $old_slugs, $skin, $cat_description, $cat_keywords) = $DB->fetch_array($result))
{
    $CONF['categories'][$category]['skin']      = $skin;
    $CONF['categories'][$category]['cat_desc']  = $cat_description;
    $CONF['categories'][$category]['cat_key']   = $cat_keywords;
    $CONF['categories'][$category]['cat_slug']  = $category_slug;
    $CONF['categories'][$category]['old_slugs'] = (array) json_decode($old_slugs, true);
}

// Does FORM['cat'] exist?
foreach ($CONF['categories'] as $cat => $skin)
{
	$form_cat      = isset($FORM['cat']) ? $FORM['cat'] : '';
	$category_slug = $CONF['categories'][$cat]['cat_slug'];

    /*
	 * See if $FORM cat match stored cat slug (case sensitive)
     * Use tag below to validate category in url instead of isset
	 */
    $TMPL['cat_exist'] = $form_cat === $category_slug ? $cat : false;

    /* Stop loop if slug matches case sentitive, no need to go further */
    if ($TMPL['cat_exist'] !== false) { 
		break; 
	}
	
	/* The following checks/redirects we not want in admin, so just continue */
	if (isset($FORM['a']) && $FORM['a'] == 'admin') {
		continue;
	}
		
    /*
	 * If the form value is correct, but case insentive
	 *	Redirect to proper case sentive slug. 
	 *	This is to avoid canonical headers in rankings.php and possibly plugins, which make use of $TMPL['cat_exist']
	 *	This is easier to avoid duplicate content
	 *
	 * If the form value match any of the old slugs (case insentive)
	 *	Redirect to proper case senitive slug
     *
     * Any other char in url beside our allowed ones? utf8 letters, number, minus.
     *    Setup new slug and redirect
     *    Either that redirect will turn into a valid slug or not and trigger category not found error
	 */
	$is_case_insentive = mb_strtolower($form_cat) === mb_strtolower($category_slug) ? true : false;
	$is_old_slug       = in_array(mb_strtolower($form_cat), array_map('mb_strtolower', $CONF['categories'][$cat]['old_slugs'])) ? true : false;
	
    if ($is_case_insentive || $is_old_slug || preg_match('/[^\p{L}\p{N}\-]/u', $form_cat))
	{
		if ($is_case_insentive || $is_old_slug) {
			$cat_slug = $category_slug;
		}
		else {
			$cat_slug_pre = preg_replace('/([^\p{L}\p{N}]|[\-])+/u', '-', $form_cat);
			$cat_slug     = trim($cat_slug_pre, '-');
		}
		
        $new_cat_url = "{$CONF['list_url']}/";

        if (isset($FORM['method']))
        {
            if($CONF['clean_url'] == 1)
            {
                $new_cat_url .= $TMPL['url_helper_method'];

                if (isset($FORM['p'])) {
                   $new_cat_url .= intval($FORM['p']).'/';
                }

                $new_cat_url .= $FORM['method'];
            }
            else
            {
                $new_cat_url .= $TMPL['url_helper_method'];
                $new_cat_url .= $FORM['method'];

                if (isset($FORM['p'])) {
                   $url_helper_page = str_replace('&amp;', '&', $TMPL['url_helper_page']);
                   $new_cat_url .= $url_helper_page.intval($FORM['p']);
                }
            }
            $url_helper_cat2 = str_replace('&amp;', '&', $TMPL['url_helper_cat2']);
            $new_cat_url .= "{$url_helper_cat2}{$cat_slug}{$TMPL['url_tail']}";
        }
        else {
            $new_cat_url .= "{$TMPL['url_helper_cat']}{$cat_slug}{$TMPL['url_tail']}";
        }

        Header("HTTP/1.1 301 Moved Permanently");
        Header('Location: ' . $new_cat_url);
        exit;
    }
}


// Determine the category skin and meta data
if ($TMPL['cat_exist']) {
    $TMPL['skin_name'] = $CONF['categories'][$TMPL['cat_exist']]['skin'];
    $TMPL['meta_description'] = $CONF['categories'][$TMPL['cat_exist']]['cat_desc'];
    $TMPL['meta_keywords'] = $CONF['categories'][$TMPL['cat_exist']]['cat_key'];
    $TMPL['cat_desc'] = $CONF['categories'][$TMPL['cat_exist']]['cat_desc'];
} else {
    $TMPL['skin_name'] = $CONF['default_skin'];
}

if (!is_dir("{$CONF['path']}/skins/{$TMPL['skin_name']}/") || !$TMPL['skin_name']) {
    $TMPL['skin_name'] = $CONF['default_skin'];
}
if (!is_dir("{$CONF['path']}/skins/{$CONF['default_skin']}/")) {
    $TMPL['skin_name'] = 'parabola';
}
require_once ("{$CONF['path']}/sources/misc/skin.php");

if (isset($FORM['a'])) {
    if ($FORM['a'] == 'admin') {
        $TMPL['skin_name'] = 'admin';
    }
}elseif(empty($FORM['a']) && empty($FORM['method']) && empty($FORM['cat'])){
	$isfront = 1;
}


if(empty($CONF['cron'])) {
    // Is it a new day/week/month?
    list($last_new_day, $last_new_week, $last_new_month) = $DB->fetch("SELECT last_new_day, last_new_week, last_new_month FROM {$CONF['sql_prefix']}_etc", __FILE__, __LINE__);
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
}



// Adjust the output text based on days, weeks, or months
if ($CONF['ranking_period'] == 'weekly') {
    $LNG['g_this_period'] = $LNG['g_this_week'];
    $LNG['g_last_period'] = $LNG['g_last_week'];
} elseif ($CONF['ranking_period'] == 'monthly') {
    $LNG['g_this_period'] = $LNG['g_this_month'];
    $LNG['g_last_period'] = $LNG['g_last_month'];
} else {
    $LNG['g_this_period'] = $LNG['g_today'];
    $LNG['g_last_period'] = $LNG['g_yesterday'];
}

// Check if installer is there
if (file_exists("{$CONF['path']}/install/")) {
    $TMPL['header'] = $LNG['g_error'];
    $base = new base;
    $base->error($LNG['g_delete_install']);
}


// Begin detect user_cp or login move html to templates before release
if(isset($FORM['a']) && $FORM['a'] == 'admin') {
    // Do nothing :)
}
else {
  if (isset($_COOKIE['atsphp_sid_user_cp'])) {
    require_once ("{$CONF['path']}/sources/misc/session.php");
    $session = new session;
    list($type, $data) = $session->get($_COOKIE['atsphp_sid_user_cp']);
    $TMPL['wrapper_username'] = $DB->escape($data);
  }
  if (isset($TMPL['wrapper_username']) && mb_strlen($TMPL['wrapper_username']) > 0) 
  {
	eval(PluginManager::getPluginManager()->pluginHooks('user_cp_menu'));
	  
    $TMPL['user_cp_main_menu_top'] = base::do_skin('user_cp_main_menu');
    $TMPL['user_cp_main_menu'] = base::do_skin('user_cp_main_menu');
    $TMPL['wrapper_welcome'] = base::do_skin('welcome_logged_in');
  } else {
    $TMPL['wrapper_username'] = '';
    $TMPL['wrapper_welcome'] = base::do_skin('welcome_not_logged_in');
  }
}


// Check for hits in
require_once ("{$CONF['path']}/sources/in.php");
$in = new in;

// Array containing the valid .php files from the sources directory
$action = array(
    'admin' => 1,
    'in' => 1,
    'join' => 1,
    'lost_pw' => 1,
    'out' => 1,
    'page' => 1,
    'rankings' => 1,
    'rate' => 1,
    'search' => 1,
    'details' => 1,
    'sendmessage' => 1,
    'user_cpl' => 1,
    'payment_ipn' => 1,
	'payment_checkout' => 1,
);


eval(PluginManager::getPluginManager()->pluginHooks('action_array'));


// Require the appropriate file
if (isset($FORM['a']) && is_string($FORM['a']) && isset($action[$FORM['a']])) {
    $page_name = $FORM['a'];
    $page_name_path = $FORM['a'];
} else {
    $page_name = 'rankings';
    $page_name_path = 'rankings';
}

$sources = 'sources';
eval(PluginManager::getPluginManager()->pluginHooks('include_source'));

require_once ("{$CONF['path']}/{$sources}/{$page_name_path}.php");
$page = new $page_name;



// Set main skin file
$wrapper_template = 'wrapper';


//Plugin Hook - Overwrite wrapper template
eval(PluginManager::getPluginManager()->pluginHooks('main_skin'));

// Maintenance mode
if ($CONF['maintenance_mode'] && $page_name != 'admin') {

    if (isset($_COOKIE['atsphp_sid_admin'])) {

        require_once("{$CONF['path']}/sources/misc/session.php");
        $session = new session;
        list($type, $data) = $session->get($_COOKIE['atsphp_sid_admin']);

        if ($type == 'admin') {
            $session->update($_COOKIE['atsphp_sid_admin']);
		}
        else {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 3600'); // 1 hour

            $wrapper_template = 'wrapper_maintenance';
		}

    }
    else {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 3600'); // 1 hour

        $wrapper_template = 'wrapper_maintenance';
	}

}

// Set main skin class
$skin = new main_skin($wrapper_template);

// Echo main skin file
echo $skin->make();

$DB->close();

// Print out debugging info, if necessary
if ($CONF['debug'] == 1) {

    ini_set('display_errors', 1);
    error_reporting(E_ALL | E_STRICT);

    echo '<div style="clear: both;">';
    foreach ($DB->queries as $value) {
        echo "<hr /><pre>{$value}</pre>";
    }
    echo '<hr /><pre>';
    print_r($_REQUEST);
    echo '</pre>';
    echo '</div>';
} else {
    //error_reporting(0);
}
