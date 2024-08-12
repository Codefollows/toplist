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

// feed.php originally by Matt Wells <cerberus@users.berlios.de>

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

// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

// The language file
$LNG['charset'] = "utf-8";
require_once("{$CONF['path']}/languages/english.php");
if ($CONF['default_language'] != 'english') {
	require_once ("{$CONF['path']}/languages/{$CONF['default_language']}.php");
}

if($CONF['clean_url'] == 1) {
$TMPL['url_tail'] = '/';
$TMPL['url_helper_a'] = '/';
$TMPL['url_helper_u'] = '/';
$TMPL['url_helper_cat'] = '/category/';
}else{
$TMPL['url_tail'] = '';
$TMPL['url_helper_a'] = '/?a=';
$TMPL['url_helper_u'] = '&amp;u=';
$TMPL['url_helper_cat'] = '/?cat=';

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

// Plugin Hook
eval(PluginManager::getPluginManager()->pluginHooks('feed_start'));


$result = $DB->query("SELECT category, category_slug, skin FROM {$CONF['sql_prefix']}_categories ORDER BY category", __FILE__, __LINE__);
while (list($category, $category_slug, $skin) = $DB->fetch_array($result)) {

    $CONF['categories'][$category]['skin'] = $skin;
    $CONF['categories'][$category]['cat_slug'] = $category_slug;

}

// Does FORM['cat'] exist?
foreach ($CONF['categories'] as $cat => $skin) {

	$FORM['cat'] = isset($FORM['cat']) ? $FORM['cat'] : '';
	$category_slug = $CONF['categories'][$cat]['cat_slug'];

    // See if $FORM cat match stored cat slug
    // Use tag below to validate category in url instead of isset
    $TMPL['cat_exist'] = ($FORM['cat'] == $category_slug) ? $cat : '';
    
    // Stop loop if slug matches, no need to go further
    if (!empty($TMPL['cat_exist'])) { break; }

    // Any other char in url beside our allowed ones? utf8 letters, number, minus
    // Prepare to redirect to trimmed url version and stop loop
    if (preg_match('/[^\p{L}\p{N}\-]/u', $FORM['cat'])) {
        $cat_slug_pre = preg_replace('/([^\p{L}\p{N}]|[\-])+/u', '-', $FORM['cat']);
        $cat_slug     = trim($cat_slug_pre, '-');

        $new_cat_url = "{$CONF['list_url']}/feed.php?cat={$cat_slug}";

        Header("HTTP/1.1 301 Moved Permanently");
        Header('Location: ' . $new_cat_url);
        exit;

        break;
    }

}


// Get the category, default to no category
if ($TMPL['cat_exist']) {
   $TMPL['category'] = $TMPL['cat_exist'];
   $category_escaped = $DB->escape($TMPL['category']);
   $category_sql = "AND category = '{$category_escaped}'";
   $category_url = $TMPL['url_helper_cat'].$CONF['categories'][$TMPL['category']]['cat_slug'].$TMPL['url_tail'];
}
else {
  $TMPL['category'] = $LNG['main_all'];
  $category_sql = '';
  $category_url = '';
}

$TMPL['category']  = htmlspecialchars($TMPL['category'], ENT_QUOTES, "UTF-8");
$CONF['list_url']  = htmlspecialchars($CONF['list_url'], ENT_QUOTES, "UTF-8");
$CONF['list_name'] = htmlspecialchars($CONF['list_name'], ENT_QUOTES, "UTF-8");

// Make ORDER BY clause
require_once("{$CONF['path']}/sources/misc/classes.php");
$order_by = base::rank_by()." DESC";

header('Content-Type: application/xml');
echo "<?xml version=\"1.0\" encoding=\"{$LNG['charset']}\"?>";

$result = $DB->select_limit("SELECT *
                             FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats
                             WHERE sites.username = stats.username AND active = 1 {$category_sql}
                             ORDER BY {$order_by}
                            ", 10, 0, __FILE__, __LINE__);
?>

<rss version="2.0">
	<channel>
		<title><?php echo "{$CONF['list_name']} - {$TMPL['category']}"; ?></title>
		<link><?php echo $CONF['list_url'].$category_url; ?></link>
		<description></description>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<generator>Visiolist Topsites</generator>

		<item>
			<title><?php echo "{$CONF['list_name']} - {$TMPL['category']}"; ?></title>
			<link><?php echo $CONF['list_url'].$category_url; ?></link>
			<description></description>
			<guid><?php echo $CONF['list_url']; ?>/</guid>
			<pubDate><?php echo date(DATE_RFC822); ?></pubDate>
		</item>


<?php
for($rank = 1; $row = $DB->fetch_array($result); $rank++) {
  $row['title'] = htmlspecialchars($row['title'],ENT_QUOTES, "UTF-8");
  $row['description'] = htmlspecialchars($row['description'],ENT_QUOTES, "UTF-8");
?>

		<item>
			<title><?php echo $rank . ' - ' . $row['title']; ?></title>
			<link><?php echo $CONF['list_url']; ?>/index.php?a=out&amp;u=<?php echo $row['username']; ?>&amp;go=1</link>
			<description><?php echo $row['description']; ?></description>
			<guid><?php echo $CONF['list_url'].$TMPL['url_helper_a'].'details'.$TMPL['url_helper_u'].$row['username'].$TMPL['url_tail']; ?></guid>
		</item>

<?php
}
?>
	</channel>
</rss>