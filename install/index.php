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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Change the path to your full path if necessary
$CONF['path']            = __DIR__ .'/..';
$CONF['install_running'] = true;
$new_version             = '1.9';

// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

$extra_javascript = '';

function getExtension($str)
{
	$i = strrpos($str,".");
	if (!$i) { return ""; }
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return $ext;
}

$TMPL['requirements'] = '';
$FORM['upgrade'] = '';
$TMPL['upgrade'] = '';
$fail = '';
$translation = '';


if (!isset($FORM['l'])) {

  $prog = 10;

  ////////////////////////////////////////////////////////////////////////////
  // ####################### Aardvark Upgrade ############################# //
  ////////////////////////////////////////////////////////////////////////////

  require_once("{$CONF['path']}/settings_sql.php");

  if(isset($CONF['sql_database'])) {

     if($FORM['upgrade'] != 2) {
        $fail = ' class="invisible"';
     }

     require_once("{$CONF['path']}/sources/sql/mysql.php");
     $DB = "sql_mysql";
     $DB = new $DB;

     if ($DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database']))
	 {
        // Settings
        $settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
        $CONF = array_merge($CONF, $settings);

        // The language file
        $LNG['charset'] = "utf-8";
        require_once("{$CONF['path']}/languages/import/english.php");

        // Causes issues with aardvark language file, script dies cos ATSPHP not defined
        //require_once("{$CONF['path']}/languages/{$CONF['default_language']}.php");

        if(isset($_GET['upgrade']) && $_GET['upgrade'] == 1)
		{
			$TMPL['upgrade'] = "{$LNG['upgrade_run_queries']}...<br />";

			//////////////////////
			// Aardvark backup bug, missing sites_edited table
			//////////////////////
			$result = $DB->query("SHOW TABLES LIKE '{$CONF['sql_prefix']}_sites_edited'", __FILE__, __LINE__);
			if(!$DB->num_rows($result)) {
				$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_sites_edited` (
					`username` varchar(255) default '' NOT NULL,
					`url` varchar(255) default '',
					`title` varchar(255) default '',
					PRIMARY KEY (`username`)
				)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);
			}


			/////////////////////
			// #old Tables to utf-8
			//////////////////////
			$result = $DB->query("SHOW TABLES", __FILE__, __LINE__);
			while($tables = $DB->fetch_array($result)) {
				foreach ($tables as $key => $value) {
					if(strpos($value, $CONF['sql_prefix']) !== false) {
						$DB->query("ALTER TABLE {$value} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);
					}
				}
			}


			/////////////////////
			// #_etc Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/etc.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_etc {$LNG['upgrade_table_updated']}...<br />";


			/////////////////////
			// #_stats Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/stats.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_stats {$LNG['upgrade_table_updated']}.<br />";


			/////////////////////
			// #_sites Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/sites.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_sites {$LNG['upgrade_table_updated']}.<br />";


			/////////////////////
			// #_settings Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/settings.php");

			$TMPL['upgrade'] .= "{$LNG['upgrade_button_settings_removed']}<br />";
			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_settings {$LNG['upgrade_table_updated']}...<br />";


			/////////////////////
			// #_menu, _menus Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/menu.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_menu {$LNG['upgrade_table_created']}...<br />";
			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_menus {$LNG['upgrade_table_created']}...<br />";


			/////////////////////
			// #_categories Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/categories.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_categories {$LNG['upgrade_table_updated']}...<br />";


			/////////////////////
			// #_reviews Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/reviews.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_reviews {$LNG['upgrade_table_updated']}...<br />";


			/////////////////////
			// #_langs Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/langs.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_langs {$LNG['upgrade_table_created']}...<br />";


			/////////////////////
			// #_osbanners Table (Ads plugin)
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/osbanners.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_osbanners {$LNG['upgrade_table_created']}...<br />";


			//////////////////////
			// #_ip_log Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/ip_log.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_ip_log {$LNG['upgrade_table_updated']}.<br />";


			//////////////////////
			// #_custom_pages Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/custom_pages.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_custom_pages {$LNG['upgrade_table_updated']}.<br />";


			/////////////////////
			// #_screens Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/screens.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_screens {$LNG['upgrade_table_created']}...<br />";


			/////////////////////
			// #_join_fields Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/join_fields.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_join_fields {$LNG['upgrade_table_created']}...<br />";


			/////////////////////
			// #_sessions Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/sessions.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_sessions {$LNG['upgrade_table_updated']}.<br />";


			/////////////////////
			// #_payment_logs Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/payment_logs.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_payment_logs {$LNG['upgrade_table_created']}.<br />";


			/////////////////////
			// #_payment_logs_error Table
			//////////////////////
			require_once("{$CONF['path']}/install/aardvark/payment_logs_error.php");

			$TMPL['upgrade'] .= "{$CONF['sql_prefix']}_payment_logs_error {$LNG['upgrade_table_created']}.<br />";




			// Upgrade members banner width/height and if supported mp4 conversion via ajax
			list($total_users) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites", __FILE__, __LINE__);

			$TMPL['upgrade'] .= '<br /><h3 id="users"><i class="fas fa-spinner fa-spin"></i> Checking users <span>0</span>/'.$total_users.'</h3><br />';

			$TMPL['upgrade'] .= '<div id="banners">
									<i class="fas fa-spinner fa-spin"></i> <b>Saving width/height for normal/premium banners</b><br />
									<div id="updated">Saved: <span>0</span></div>
									<div id="failed1" style="display: none;">Failed - Image not found: <span>0</span></div>
									<div id="failed2" style="display: none;">Failed - External image - allow_url_fopen is disabled: <span>0</span></div>
								</div><br />';

			$TMPL['upgrade'] .= '<div id="mp4">
									<i class="fas fa-spinner fa-spin"></i> <b>Converting gif banners to mp4 for reduced filesizes</b>
									<div id="converted">Converted: <span>0</span></div>
									<div id="failed3" style="display: none;">Failed - Image not found: <span>0</span></div>
								</div><br />';

			$extra_javascript .= "

					var dataObj = {
						total: {$total_users},
						checked: 0,
						updated: 0,
						failed1: 0,
						failed2: 0,
						converted: 0
					};

					banner_updates(dataObj);

					function banner_updates(dataObj) {

						$.ajax({
							type: 'POST',
							url: '{$CONF['list_url']}/install/versions/1.6-ajax.php',
							data: dataObj,
							cache: false,
							dataType: 'json'
						}).success(function(response) {

							var checked   = response.checked,
								updated   = response.updated,
								converted = response.converted,
								failed1   = response.failed1,
								failed2   = response.failed2;

							$('#users span').text(checked);
							$('#banners #updated span').text(updated);

							if (failed1 > 0) {
								$('#banners #failed1').find('span').text(failed1).end().slideDown('slow');
							}
							if (failed2 > 0) {
								$('#banners #failed2').find('span').text(failed2).end().slideDown('slow');
							}

							if (response.ffmpeg_support == 1) {
								$('#mp4 #converted span').text(converted);
							}
							else {
								$('#mp4 #converted').text('Feature not supported');
								$('#mp4 i').remove();
							}


							if (checked < {$total_users}) {

								dataObj = {
									ffmpeg_support: response.ffmpeg_support,
									total: {$total_users},
									checked: checked,
									updated: updated,
									converted: converted,
									failed1: failed1,
									failed2: failed2
								};

								banner_updates(dataObj);
							}
							else {

								$('#users i').remove();
								$('#banners i').remove();
								$('#mp4 i').remove();

								$('#success').slideDown('slow');
							}

						}).error(function(jqXHR, textStatus, errorThrown) {


						});

					}
			";

			/////////////////////
			// UPGRADE DONE
			//////////////////////
			$TMPL['upgrade'] .= '<div id="success" style="display: none;">';

			$TMPL['upgrade'] .= "<h2>{$LNG['upgrade_complete']}!</h2><p>{$LNG['upgrade_complete2']}.</p><br />";

			$TMPL['upgrade'] .= '</div>';
        }





////////////////////////////////////////////////////////////////////////////
// ##################### UPGRADE Aardvark or Fresh Install ?? ########### //
////////////////////////////////////////////////////////////////////////////
        else {
           $TMPL['upgrade'] = '<div class="ui-widget">
			        <div class="ui-state-highlight ui-corner-all" style="margin: 5px 0; padding: 10px 10px 15px 10px;">
				  <p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>
				     <h3>'.$LNG['upgrade_active_installation'].' <a href="?upgrade=1">'.$LNG['upgrade_yes'].'</a> | <a href="?upgrade=2">'.$LNG['upgrade_no'].'</a></h3>
                          </div>
                       </div>';
        }

     }
  }
  ////////////////////////////////////////////////////////////////////////////
  // ##################### END Aardvark Upgrade ########################### //
  ////////////////////////////////////////////////////////////////////////////


    $TMPL['requirements'] .= '<h2>System Requirements</h2>';
  if (function_exists('curl_init')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/>'." Curl functions are available.<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/>'." Curl not available.<br />\n";
}
  if (function_exists('gd_info')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/>'." GD functions are available.<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/>'." GD not available.<br />\n";
}
  if (function_exists('iconv')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/>'." iconv functions are available.<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/>'." iconv not available.<br />\n";
}
  if (function_exists('mb_strlen')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/>'." MB_String functions are available.<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/>'." MB_String PHP functions are not available.<br />\n";
}
$gdinfo = gd_info();
  if ($gdinfo['FreeType Support']) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> FreeType Installed'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> FreeType Not Installed'."<br />\n";
}
  if (phpversion() > 5) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> PHP Version: '.phpversion()."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> PHP Version: '.phpversion().' is out of date! You must upgrade'."<br />\n";
}
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi' || substr($sapi_type, 0, 3) == 'fpm') {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> CGI PHP Installed - NO problems expected'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> CGI PHP NOT detected. You MAY encounter some file ownership problems when editing files uploaded through the admin interface'."<br />\n";
}
if (is_writable('../settings_sql.php')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> settings_sql.php is writeable'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> settings_sql.php is NOT writeable, make sure it exists and CHMOD 666.  We cannot proceed with the installer until settings_sql is writeable.'."<br />\n";
    $fail = ' class="invisible"';
}
if (is_writable('../button_config.php')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> button_config.php is writeable'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> button_config.php is NOT writeable, make sure it exists and CHMOD 666. '."<br />\n";
    $fail = ' class="invisible"';
}
if (is_writable('../banners')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> The banners directory is writeable'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> The banners directory is NOT writeable, make sure it exists and CHMOD 777.'."<br />\n";
    $fail = ' class="invisible"';
}
if (is_writable('../languages')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> The languages directory is writeable'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> The languages directory is NOT writeable, make sure it exists and CHMOD 777.'."<br />\n";
    $fail = ' class="invisible"';
}
if (is_writable('../plugins')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> The plugins directory is writeable'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> The plugins directory is NOT writeable, make sure it exists and CHMOD 777.'."<br />\n";
    $fail = ' class="invisible"';
}
if (is_writable('../screens')) {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> The screens directory is writeable'."<br />\n";
} else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> The screens directory is NOT writeable, make sure it exists and CHMOD 777.'."<br />\n";
    $fail = ' class="invisible"';
}

// gif/jpg to mp4 conversion using ffmpeg for smaller filesizes
if (function_exists('shell_exec')) {

	// Force a file path search using "type".
	// "which" may be empty if you not modified $PATH
	// Returns filepath for ffmpeg
	$ffmpeg_path = trim(shell_exec('type -P ffmpeg'));

	if (!empty($ffmpeg_path)) {
		$TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> The system will convert gif member banners on upload to mp4 for smaller filesize'."<br />\n";
	}
	else {
		$TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> Server lib "ffmpeg" not found on the system. Member banner upload cant convert gif to mp4 for smaller filesizes.'."<br />\n";
	}
}
else {
    $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> Function "shell_exec" is disabled. Member banner upload cant convert gif to mp4 for smaller filesizes.'."<br />\n";
}

if (extension_loaded('bcmath')) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> BCMATH module is installed'."<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> BCMATH moodule is not installed. Payment system will not work.'."<br />\n";
}


if(isset($FORM['upgrade']) && $FORM['upgrade'] == 1) {
	$TMPL['requirements'] = '';
}



////////////////////////////////////////////////////////////////////////////
// ################### Fresh VisioList Install ########################## //
////////////////////////////////////////////////////////////////////////////

  $TMPL['content'] = <<<EndHTML

    {$TMPL['upgrade']}

    <div style="width: 400px; float:left;">
       {$TMPL['requirements']}
    </div>

  <div style="width: 400px; float:right;">
  <div{$fail}>
Please select your language.<br /><br />
<form action="index.php" method="get">
<select name="l">
EndHTML;
  $languages = array();
  $dir = opendir("{$CONF['path']}/languages/import/");
  while (false !== ($file = readdir($dir)) ) {
    $file = str_replace('.php', '', $file);
    if (is_file("{$CONF['path']}/languages/import/{$file}.php")) {
      require "{$CONF['path']}/languages/import/{$file}.php";
      $languages[$file] = $translation;
    }
  }
  natcasesort($languages);
  foreach ($languages as $file => $translation) {
    if ($file == 'english') {
      $TMPL['content'] .= "<option value=\"{$file}\" selected=\"selected\">{$file}</option>\n";
    }
    else {
      $TMPL['content'] .= "<option value=\"{$file}\">{$file}</option>\n";
    }
  }
  $LNG['charset'] = "utf-8";
  require "{$CONF['path']}/languages/import/english.php";
  $TMPL['content'] .= <<<EndHTML
</select>
<input type="submit" value="Next" />
</div>
</form>
</div>
EndHTML;
}
elseif (!isset($FORM['submit'])) {

  $LNG['charset'] = "utf-8";
  require_once("{$CONF['path']}/languages/import/english.php");
  require_once("{$CONF['path']}/languages/import/{$FORM['l']}.php");

  $path = str_replace('/install/index.php', '', $_SERVER['PHP_SELF']);
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){$protocol = 'https://';}else{$protocol = 'http://';}
	$list_url = "{$protocol}{$_SERVER['HTTP_HOST']}{$path}";



  $sql_menu = '';
  $dir = opendir("{$CONF['path']}/sources/sql/");
  while (false !== ($file = readdir($dir))) {
    if ($file != '.' && $file != '..' && $file != 'index.htm' && !is_dir("{$CONF['path']}/sources/sql/{$file}")) {
      $file = str_replace('.php', '', $file);
      require "{$CONF['path']}/sources/sql/{$file}.php";
      $sql_menu .= "<option value=\"{$file}\">{$database}</option>\n";
    }
  }

  $prog = 50;


  $TMPL['content'] = <<<EndHTML
{$LNG['install_welcome']}<br /><br />
<form action="index.php?import=1" method="post">
<input name="l" type="hidden" value="{$FORM['l']}" />
<fieldset>
<legend>{$LNG['a_s_general']}</legend>
<label>{$LNG['a_s_admin_password']}</label>
<input name="admin_password" type="password" size="20" /><br /><br />

<label>{$LNG['a_s_list_url']}</label>
<input name="list_url" type="text" size="50" value="{$list_url}" /><br /><br />

<label>{$LNG['a_s_your_email']}</label>
<input name="your_email" type="text" size="50" />

</fieldset>
<fieldset>
<legend>{$LNG['a_s_sql']}</legend>
<label>{$LNG['a_s_sql_type']}</label>
<select name="sql">
$sql_menu</select><br /><br />

<label>{$LNG['a_s_sql_host']}</label>
<input name="sql_host" type="text" size="20" value="localhost" /><br /><br />

<label>{$LNG['a_s_sql_database']}</label>
<input name="sql_database" type="text" size="20" /><br /><br />

<label>{$LNG['a_s_sql_username']}</label>
<input name="sql_username" type="text" size="20" /><br /><br />

<label>{$LNG['a_s_sql_password']}</label>
<input name="sql_password" type="password" size="20" /><br /><br />

<label>{$LNG['install_sql_prefix']}</label><br />
<input name="sql_prefix" type="text" size="20" value="VL" /><br /><br />

<div class="buttons">
<button class="positive" name="submit" type="submit">{$LNG['install_header']}</button>
</div>
</fieldset>
</form>
EndHTML;
}
elseif (isset($FORM['submit']) && isset($FORM['l']) && !isset($FORM['done'])) {
  $LNG['charset'] = "utf-8";
  require_once("{$CONF['path']}/languages/import/english.php");
  require_once("{$CONF['path']}/languages/import/{$FORM['l']}.php");

  require_once("{$CONF['path']}/sources/sql/{$FORM['sql']}.php");
  $DB = "sql_{$FORM['sql']}";
  $DB = new $DB;

  if ($DB->connect($FORM['sql_host'], $FORM['sql_username'], $FORM['sql_password'], $FORM['sql_database'])) {

    $default_language = $DB->escape($FORM['l']);
    $admin_password = md5($FORM['admin_password']);
    $list_url = $DB->escape($FORM['list_url']);
    $CONF['list_url'] = $list_url;

    $your_email = $DB->escape($FORM['your_email']);

    // Write the button_config.php
    $button_file = "{$CONF['path']}/button_config.php";
    if ($fh = @fopen($button_file, 'w')) {
      $button_config = <<<EndHTML
<?php

\$CONF['count_pv'] = 0; //Count pageviews

\$CONF['text_link_button_alt'] = 'My VisioList'; // Text Link Anchor, Alt for Buttons
\$CONF['text_link'] = 1; //Enable Text Link
\$CONF['static_button'] = 0; // Show Only Static Button
\$CONF['static_button_url'] = '';

\$CONF['rank_button'] = 1; //Show buttons with rank 1.gif, 2.gif etc
\$CONF['default_rank_button'] = '{$CONF['list_url']}/images/button.png';
\$CONF['button_dir'] = '{$CONF['list_url']}/images';
\$CONF['button_ext'] = 'gif';
\$CONF['button_num'] = 5;

\$CONF['stats_button'] = 1; //Show Dynamic Stats Button

\$CONF['hidden_button_url'] = '{$CONF['list_url']}/images/clear.png';

\$CONF['default_banner_mp4'] = '';
\$CONF['default_banner_width'] = 88;
\$CONF['default_banner_height'] = 31;

?>
EndHTML;
      fwrite($fh, $button_config);
      fclose($fh);
    }

    // Write settings_sql.php file and create database tables
    $file = "{$CONF['path']}/settings_sql.php";
    if ($fh = @fopen($file, 'w'))
	{
		$settings_sql = <<<EndHTML
<?php
\$CONF['sql'] = '{$FORM['sql']}';
\$CONF['sql_host'] = '{$FORM['sql_host']}';
\$CONF['sql_database'] = '{$FORM['sql_database']}';
\$CONF['sql_username'] = '{$FORM['sql_username']}';
\$CONF['sql_password'] = '{$FORM['sql_password']}';
\$CONF['sql_prefix'] = '{$FORM['sql_prefix']}';
?>
EndHTML;
		fwrite($fh, $settings_sql);
		fclose($fh);

		require_once("{$CONF['path']}/settings_sql.php");

		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_settings` (
			`list_name` varchar(255) default 'My VisioList',
			`list_url` varchar(255) default '',
			`default_language` varchar(255) default '',
			`default_skin` varchar(255) default 'default',
			`your_email` varchar(255) default '',
			`clean_url` TINYINT(1) NOT NULL DEFAULT 0,
			`maintenance_mode` TINYINT(1) NULL DEFAULT 0,
			`num_list` int(5) default 10,
			`ranking_period` varchar(7) default 'daily',
			`ranking_method` varchar(255) default 'in',
			`ranking_average` tinyint(1) default 0,
			`featured_member` tinyint(1) default 1,
			`top_skin_num` int(5) default 2,
			`ad_breaks` varchar(255) default '',
			`fill_blank_rows` tinyint(1) default 1,
			`active_default` tinyint(1) default 0,
			`active_default_review` tinyint(1) default 1,
			`inactive_after` int(5) default 14,
			`email_admin_on_join` tinyint(1) default 0,
			`email_admin_on_review` tinyint(1) default 0,
			`max_banner_width` int(4) default 0,
			`max_banner_height` int(4) default 0,
			`default_banner` varchar(255) default '',
			`google_friendly_links` tinyint(1) default '1',
			`search` tinyint(1) default 1,
			`time_offset` int(2) default 0,
			`time_zone` varchar(85) DEFAULT 'America/Los_Angeles',
			`gateway` tinyint(1) default 1,
			`captcha` tinyint(1) default 1,
			`security_question` text,
			`security_answer` varchar(255),
			`visio_screen_api` VARCHAR(255) NULL,
			`premium_number` INT(10) NOT NULL DEFAULT 5,
			`premium_order_by` tinyint(1) default 1,
			`currency_code` varchar(55) NOT NULL DEFAULT '',
			`currency_symbol` varchar(55) NOT NULL DEFAULT '',
			`one_w_price` DECIMAL(5, 2) NULL DEFAULT 0,
			`discount_qty_01` SMALLINT NULL DEFAULT 0,
			`discount_value_01` SMALLINT NULL DEFAULT 0,
			`discount_qty_02` SMALLINT NULL DEFAULT 0,
			`discount_value_02` SMALLINT NULL DEFAULT 0,
			`discount_qty_03` SMALLINT NULL DEFAULT 0,
			`discount_value_03` SMALLINT NULL DEFAULT 0,
			`max_premium_banner_width` INT(4) NULL DEFAULT 0,
			`max_premium_banner_height` INT(4) NULL DEFAULT 0,
			`auto_approve_premium` TINYINT(1) NULL DEFAULT 0,
			`new_day_boost` INT(11) NOT NULL DEFAULT 0,
			`new_week_boost` INT(11) NOT NULL DEFAULT 0,
			`new_month_boost` INT(11) NOT NULL DEFAULT 0,
			`recaptcha` TINYINT(1) NULL DEFAULT 0,
			`gateway_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`usercp_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`admin_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`lostpw_recaptcha` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`recaptcha_sitekey` VARCHAR(255) NULL DEFAULT '',
			`recaptcha_secret` VARCHAR(255) NULL DEFAULT '',
			`new_member_num` tinyint(1) unsigned default 5 NOT NULL,
			`new_member_screen` tinyint(1) unsigned default 1 NOT NULL,
			`smtp_host` VARCHAR(255) NOT NULL DEFAULT '',
			`smtp_user` VARCHAR(255) NOT NULL DEFAULT '',
			`smtp_password` VARCHAR(255) NOT NULL DEFAULT '',
			`smtp_port` VARCHAR(50) NOT NULL  DEFAULT '',
			`2step` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`2step_secret` varchar(255) NOT NULL DEFAULT '',
			`payment_providers` TEXT NULL DEFAULT NULL
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		$DB->query("INSERT INTO {$CONF['sql_prefix']}_settings (list_url, default_language, your_email, default_banner)
                  VALUES ('{$list_url}', '{$default_language}', '{$your_email}', '{$list_url}/images/button.png')", __FILE__, __LINE__);

		require_once("{$CONF['path']}/sources/misc/Payment.php");
		require_once("{$CONF['path']}/install/providers.php");

		$Payment = new Payment();
		$Payment->insertProviders($providers);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_bad_words` (
			`id` int(10) unsigned NOT NULL,
			`word` varchar(255),
			`replacement` varchar(255),
			`matching` tinyint(1),
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_menu` (
			`id` int(10) NOT NULL auto_increment,
			`menu_id` int(11) NOT NULL,
			`title` varchar(150) NOT NULL,
			`path` varchar(150) NOT NULL,
			`target` varchar(80) NULL DEFAULT NULL,
			`sort` int(2) NOT NULL,
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		$DB->query("INSERT INTO {$CONF['sql_prefix']}_menu (menu_id, title, path, sort) VALUES ('1', '{$LNG['main_menu_rankings']}', '{$CONF['list_url']}/', 1)", __FILE__, __LINE__);
		$DB->query("INSERT INTO {$CONF['sql_prefix']}_menu (menu_id, title, path, sort) VALUES ('1', '{$LNG['main_menu_join']}', '{$CONF['list_url']}/?a=join',1)", __FILE__, __LINE__);
		$DB->query("INSERT INTO {$CONF['sql_prefix']}_menu (menu_id, title, path, sort) VALUES ('1', '{$LNG['main_menu_user_cp']}', '{$CONF['list_url']}/?a=user_cpl', 1)", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_menus` (
			`menu_id` int(10) unsigned NOT NULL auto_increment,
			`menu_name` varchar(255),
			`menu_weight` int(11),
			`menu_parent` int(11),
			PRIMARY KEY (`menu_id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		$DB->query("INSERT INTO {$CONF['sql_prefix']}_menus (menu_id, menu_name, menu_weight, menu_parent) VALUES ('1', \"Header\", 1, 0)", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_ban` (
			`id` int(10) unsigned NOT NULL,
			`string` varchar(255) NOT NULL,
			`field` varchar(255) NOT NULL,
			`matching` tinyint(1) NOT NULL,
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_custom_pages` (
			`id` varchar(255) default '' NOT NULL,
			`title` varchar(255) default '',
			`content` text,
			`keywords` varchar(255) default '',
			`description` varchar(320) default '',
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_etc` (
			`admin_password` varchar(32) default '',
			`last_new_day` tinyint(4) default 0,
			`last_new_week` tinyint(4) default 0,
			`last_new_month` tinyint(4) default 0,
			`version` varchar(255) default 0
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		$DB->query("INSERT INTO {$CONF['sql_prefix']}_etc (admin_password, version) VALUES ('{$admin_password}', '{$new_version}')", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_categories` (
			`category` varchar(255) default '' NOT NULL,
			`category_slug` VARCHAR(255) default '' NOT NULL,
			`old_slugs` TEXT NULL DEFAULT NULL,
			`skin` varchar(255) default '',
			`cat_description` varchar(320),
			`cat_keywords` varchar(155),
			PRIMARY KEY (`category`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		$DB->query("INSERT INTO {$CONF['sql_prefix']}_categories (category, category_slug) VALUES ('Category', 'Category')", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_ip_log` (
			`ip_address` varchar(78) default '' NOT NULL,
			`username` varchar(255) default '' NOT NULL,
			`unq_pv` tinyint(1) default 0,
			`unq_in` tinyint(1) default 0,
			`unq_out` tinyint(1) default 0,
			`rate` tinyint(1) default 0,
			`timestamp` INT NULL,
			INDEX `brute_force_check` (`ip_address`, `timestamp`),
			INDEX `vote_check` (`ip_address`, `username`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_join_fields` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`field_id` varchar(150) NOT NULL,
			`label_text` varchar(255) NOT NULL,
			`description` text NOT NULL,
			`field_type` varchar(10) NOT NULL DEFAULT 'textbox',
			`required` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`field_text_requirements` varchar(6) NOT NULL DEFAULT 'none',
			`field_text_enable_length` varchar(5) NOT NULL DEFAULT 'none',
			`field_text_length` varchar(100) NOT NULL DEFAULT '',
			`field_text_input_size` tinyint(1) unsigned NOT NULL DEFAULT 50,
			`field_choice_value` text NOT NULL,
			`field_choice_text` text NOT NULL,
			`display_location` varchar(7) NOT NULL DEFAULT 'website',
			`show_join_edit` tinyint(1) NOT NULL DEFAULT 1,
			`field_sort` tinyint(1) unsigned NOT NULL DEFAULT 255,
			`field_status` tinyint(1) unsigned NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners` (
			`id` BIGINT unsigned NOT NULL AUTO_INCREMENT,
			`code` TEXT,
			`name` TEXT,
			`display_zone` TEXT,
			`active` tinyint(1) unsigned default 1 NOT NULL,
			`views` int(10) unsigned default 0 NOT NULL,
			`max_views` int(10) unsigned default 0 NOT NULL,
			`type` varchar(255) default 'global' NOT NULL,
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_osbanners_zones` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`zone` VARCHAR(255) default '' NOT NULL,
			`type` VARCHAR(255) default 'global' NOT NULL,
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);

		// Defaults
		$zones = array(
			'a' => 'global|Global',
			'b' => 'global|Global',
			'c' => 'global|Global',
			'd' => 'details|Details Page'
		);
		foreach ($zones as $zone => $type) {
			$DB->query("INSERT INTO `{$CONF['sql_prefix']}_osbanners_zones` (`zone`, `type`) VALUES ('{$zone}', '{$type}')", __FILE__, __LINE__);
		}

		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_screens` (
			`screenshot_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`requested_url` VARCHAR( 255 ) NOT NULL,
			`requested_time` DATETIME NOT NULL,
			`username` VARCHAR( 255 ) NOT NULL,
			`active` tinyint(1) default 1
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_reviews` (
			`username` varchar(255) default '',
			`id` bigint(20) unsigned default 0 NOT NULL,
			`date` datetime NOT NULL,
			`review` text,
			`active` tinyint(1) default 1,
			PRIMARY KEY (`id`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_sessions` (
			`type` varchar(255) NOT NULL default '',
			`sid` varchar(32) default '' NOT NULL,
			`time` int(10) unsigned default 0,
			`data` varchar(255) default '',
			`keep_alive` tinyint(1) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY (`sid`),
			INDEX `delete_query` (`time`, `keep_alive`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_sites` (
			`username` varchar(255) default '' NOT NULL,
			`owner` varchar(255) default '' NOT NULL,
			`password` varchar(32) default '',
			`url` varchar(255) default '',
			`short_url` varchar(255) default '',
			`title` varchar(255) default '',
			`description` TEXT NULL DEFAULT NULL,
			`category` varchar(255) default '',
			`banner_url` varchar(255) default '',
			`banner_width` INT(10) UNSIGNED NOT NULL DEFAULT 0,
			`banner_height` INT(10) UNSIGNED NOT NULL DEFAULT 0,
			`mp4_url` VARCHAR(255) NULL,
			`email` varchar(255) default '',
			`unsubscribe` tinyint(1) default 0,
			`active` tinyint(1) default 1,
			`openid` tinyint(1) default 0,
			`user_ip` varchar(255) default '',
			`premium_flag` TINYINT( 1 ) NULL DEFAULT 0,
			`premium_request` TINYINT( 1 ) NULL DEFAULT 0,
			`premium_banner_url` VARCHAR( 255 ) NULL,
			`premium_banner_width` INT(10) UNSIGNED NOT NULL DEFAULT 0,
			`premium_banner_height` INT(10) UNSIGNED NOT NULL DEFAULT 0,
			`premium_mp4_url` VARCHAR(255) NULL,
			`date_start_premium` DATE NULL DEFAULT NULL,
			`weeks_buy` SMALLINT NULL DEFAULT 0,
			`total_day` SMALLINT NULL DEFAULT 0,
			`remain_day` SMALLINT NULL DEFAULT 0,
			`2step` tinyint(1) unsigned NOT NULL DEFAULT 0,
			`2step_secret` varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY (`username`),
			INDEX `date_start_premium` (`date_start_premium`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_sites_edited` (
			`username` varchar(255) default '' NOT NULL,
			`url` varchar(255) default '',
			`title` varchar(255) default '',
			PRIMARY KEY (`username`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_stats` (
			`username` varchar(255) default '' NOT NULL,
			`join_date` DATETIME NOT NULL,
			`rank_cache` bigint(20) unsigned default 0,
			`rank_cache_time` int(10) unsigned default 0,
			`old_rank` bigint(20) unsigned default 0,
			`days_inactive` int(10) unsigned default 0,
			`total_rating` bigint(20) unsigned default 0,
			`num_ratings` bigint(20) unsigned default 0,
			`unq_pv_overall` bigint(20) unsigned default 0,
			`tot_pv_overall` bigint(20) unsigned default 0,
			`unq_in_overall` bigint(20) unsigned default 0,
			`tot_in_overall` bigint(20) unsigned default 0,
			`unq_out_overall` bigint(20) unsigned default 0,
			`tot_out_overall` bigint(20) unsigned default 0,
			`unq_pv_0_daily` bigint(20) unsigned default 0,
			`unq_pv_1_daily` bigint(20) unsigned default 0,
			`unq_pv_2_daily` bigint(20) unsigned default 0,
			`unq_pv_3_daily` bigint(20) unsigned default 0,
			`unq_pv_4_daily` bigint(20) unsigned default 0,
			`unq_pv_5_daily` bigint(20) unsigned default 0,
			`unq_pv_6_daily` bigint(20) unsigned default 0,
			`unq_pv_7_daily` bigint(20) unsigned default 0,
			`unq_pv_8_daily` bigint(20) unsigned default 0,
			`unq_pv_9_daily` bigint(20) unsigned default 0,
			`unq_pv_max_daily` bigint(20) unsigned default 0,
			`tot_pv_0_daily` bigint(20) unsigned default 0,
			`tot_pv_1_daily` bigint(20) unsigned default 0,
			`tot_pv_2_daily` bigint(20) unsigned default 0,
			`tot_pv_3_daily` bigint(20) unsigned default 0,
			`tot_pv_4_daily` bigint(20) unsigned default 0,
			`tot_pv_5_daily` bigint(20) unsigned default 0,
			`tot_pv_6_daily` bigint(20) unsigned default 0,
			`tot_pv_7_daily` bigint(20) unsigned default 0,
			`tot_pv_8_daily` bigint(20) unsigned default 0,
			`tot_pv_9_daily` bigint(20) unsigned default 0,
			`tot_pv_max_daily` bigint(20) unsigned default 0,
			`unq_in_0_daily` bigint(20) unsigned default 0,
			`unq_in_1_daily` bigint(20) unsigned default 0,
			`unq_in_2_daily` bigint(20) unsigned default 0,
			`unq_in_3_daily` bigint(20) unsigned default 0,
			`unq_in_4_daily` bigint(20) unsigned default 0,
			`unq_in_5_daily` bigint(20) unsigned default 0,
			`unq_in_6_daily` bigint(20) unsigned default 0,
			`unq_in_7_daily` bigint(20) unsigned default 0,
			`unq_in_8_daily` bigint(20) unsigned default 0,
			`unq_in_9_daily` bigint(20) unsigned default 0,
			`unq_in_max_daily` bigint(20) unsigned default 0,
			`tot_in_0_daily` bigint(20) unsigned default 0,
			`tot_in_1_daily` bigint(20) unsigned default 0,
			`tot_in_2_daily` bigint(20) unsigned default 0,
			`tot_in_3_daily` bigint(20) unsigned default 0,
			`tot_in_4_daily` bigint(20) unsigned default 0,
			`tot_in_5_daily` bigint(20) unsigned default 0,
			`tot_in_6_daily` bigint(20) unsigned default 0,
			`tot_in_7_daily` bigint(20) unsigned default 0,
			`tot_in_8_daily` bigint(20) unsigned default 0,
			`tot_in_9_daily` bigint(20) unsigned default 0,
			`tot_in_max_daily` bigint(20) unsigned default 0,
			`unq_out_0_daily` bigint(20) unsigned default 0,
			`unq_out_1_daily` bigint(20) unsigned default 0,
			`unq_out_2_daily` bigint(20) unsigned default 0,
			`unq_out_3_daily` bigint(20) unsigned default 0,
			`unq_out_4_daily` bigint(20) unsigned default 0,
			`unq_out_5_daily` bigint(20) unsigned default 0,
			`unq_out_6_daily` bigint(20) unsigned default 0,
			`unq_out_7_daily` bigint(20) unsigned default 0,
			`unq_out_8_daily` bigint(20) unsigned default 0,
			`unq_out_9_daily` bigint(20) unsigned default 0,
			`unq_out_max_daily` bigint(20) unsigned default 0,
			`tot_out_0_daily` bigint(20) unsigned default 0,
			`tot_out_1_daily` bigint(20) unsigned default 0,
			`tot_out_2_daily` bigint(20) unsigned default 0,
			`tot_out_3_daily` bigint(20) unsigned default 0,
			`tot_out_4_daily` bigint(20) unsigned default 0,
			`tot_out_5_daily` bigint(20) unsigned default 0,
			`tot_out_6_daily` bigint(20) unsigned default 0,
			`tot_out_7_daily` bigint(20) unsigned default 0,
			`tot_out_8_daily` bigint(20) unsigned default 0,
			`tot_out_9_daily` bigint(20) unsigned default 0,
			`tot_out_max_daily` bigint(20) unsigned default 0,
			`unq_pv_0_weekly` bigint(20) unsigned default 0,
			`unq_pv_1_weekly` bigint(20) unsigned default 0,
			`unq_pv_2_weekly` bigint(20) unsigned default 0,
			`unq_pv_3_weekly` bigint(20) unsigned default 0,
			`unq_pv_4_weekly` bigint(20) unsigned default 0,
			`unq_pv_5_weekly` bigint(20) unsigned default 0,
			`unq_pv_6_weekly` bigint(20) unsigned default 0,
			`unq_pv_7_weekly` bigint(20) unsigned default 0,
			`unq_pv_8_weekly` bigint(20) unsigned default 0,
			`unq_pv_9_weekly` bigint(20) unsigned default 0,
			`unq_pv_max_weekly` bigint(20) unsigned default 0,
			`tot_pv_0_weekly` bigint(20) unsigned default 0,
			`tot_pv_1_weekly` bigint(20) unsigned default 0,
			`tot_pv_2_weekly` bigint(20) unsigned default 0,
			`tot_pv_3_weekly` bigint(20) unsigned default 0,
			`tot_pv_4_weekly` bigint(20) unsigned default 0,
			`tot_pv_5_weekly` bigint(20) unsigned default 0,
			`tot_pv_6_weekly` bigint(20) unsigned default 0,
			`tot_pv_7_weekly` bigint(20) unsigned default 0,
			`tot_pv_8_weekly` bigint(20) unsigned default 0,
			`tot_pv_9_weekly` bigint(20) unsigned default 0,
			`tot_pv_max_weekly` bigint(20) unsigned default 0,
			`unq_in_0_weekly` bigint(20) unsigned default 0,
			`unq_in_1_weekly` bigint(20) unsigned default 0,
			`unq_in_2_weekly` bigint(20) unsigned default 0,
			`unq_in_3_weekly` bigint(20) unsigned default 0,
			`unq_in_4_weekly` bigint(20) unsigned default 0,
			`unq_in_5_weekly` bigint(20) unsigned default 0,
			`unq_in_6_weekly` bigint(20) unsigned default 0,
			`unq_in_7_weekly` bigint(20) unsigned default 0,
			`unq_in_8_weekly` bigint(20) unsigned default 0,
			`unq_in_9_weekly` bigint(20) unsigned default 0,
			`unq_in_max_weekly` bigint(20) unsigned default 0,
			`tot_in_0_weekly` bigint(20) unsigned default 0,
			`tot_in_1_weekly` bigint(20) unsigned default 0,
			`tot_in_2_weekly` bigint(20) unsigned default 0,
			`tot_in_3_weekly` bigint(20) unsigned default 0,
			`tot_in_4_weekly` bigint(20) unsigned default 0,
			`tot_in_5_weekly` bigint(20) unsigned default 0,
			`tot_in_6_weekly` bigint(20) unsigned default 0,
			`tot_in_7_weekly` bigint(20) unsigned default 0,
			`tot_in_8_weekly` bigint(20) unsigned default 0,
			`tot_in_9_weekly` bigint(20) unsigned default 0,
			`tot_in_max_weekly` bigint(20) unsigned default 0,
			`unq_out_0_weekly` bigint(20) unsigned default 0,
			`unq_out_1_weekly` bigint(20) unsigned default 0,
			`unq_out_2_weekly` bigint(20) unsigned default 0,
			`unq_out_3_weekly` bigint(20) unsigned default 0,
			`unq_out_4_weekly` bigint(20) unsigned default 0,
			`unq_out_5_weekly` bigint(20) unsigned default 0,
			`unq_out_6_weekly` bigint(20) unsigned default 0,
			`unq_out_7_weekly` bigint(20) unsigned default 0,
			`unq_out_8_weekly` bigint(20) unsigned default 0,
			`unq_out_9_weekly` bigint(20) unsigned default 0,
			`unq_out_max_weekly` bigint(20) unsigned default 0,
			`tot_out_0_weekly` bigint(20) unsigned default 0,
			`tot_out_1_weekly` bigint(20) unsigned default 0,
			`tot_out_2_weekly` bigint(20) unsigned default 0,
			`tot_out_3_weekly` bigint(20) unsigned default 0,
			`tot_out_4_weekly` bigint(20) unsigned default 0,
			`tot_out_5_weekly` bigint(20) unsigned default 0,
			`tot_out_6_weekly` bigint(20) unsigned default 0,
			`tot_out_7_weekly` bigint(20) unsigned default 0,
			`tot_out_8_weekly` bigint(20) unsigned default 0,
			`tot_out_9_weekly` bigint(20) unsigned default 0,
			`tot_out_max_weekly` bigint(20) unsigned default 0,
			`unq_pv_0_monthly` bigint(20) unsigned default 0,
			`unq_pv_1_monthly` bigint(20) unsigned default 0,
			`unq_pv_2_monthly` bigint(20) unsigned default 0,
			`unq_pv_3_monthly` bigint(20) unsigned default 0,
			`unq_pv_4_monthly` bigint(20) unsigned default 0,
			`unq_pv_5_monthly` bigint(20) unsigned default 0,
			`unq_pv_6_monthly` bigint(20) unsigned default 0,
			`unq_pv_7_monthly` bigint(20) unsigned default 0,
			`unq_pv_8_monthly` bigint(20) unsigned default 0,
			`unq_pv_9_monthly` bigint(20) unsigned default 0,
			`unq_pv_max_monthly` bigint(20) unsigned default 0,
			`tot_pv_0_monthly` bigint(20) unsigned default 0,
			`tot_pv_1_monthly` bigint(20) unsigned default 0,
			`tot_pv_2_monthly` bigint(20) unsigned default 0,
			`tot_pv_3_monthly` bigint(20) unsigned default 0,
			`tot_pv_4_monthly` bigint(20) unsigned default 0,
			`tot_pv_5_monthly` bigint(20) unsigned default 0,
			`tot_pv_6_monthly` bigint(20) unsigned default 0,
			`tot_pv_7_monthly` bigint(20) unsigned default 0,
			`tot_pv_8_monthly` bigint(20) unsigned default 0,
			`tot_pv_9_monthly` bigint(20) unsigned default 0,
			`tot_pv_max_monthly` bigint(20) unsigned default 0,
			`unq_in_0_monthly` bigint(20) unsigned default 0,
			`unq_in_1_monthly` bigint(20) unsigned default 0,
			`unq_in_2_monthly` bigint(20) unsigned default 0,
			`unq_in_3_monthly` bigint(20) unsigned default 0,
			`unq_in_4_monthly` bigint(20) unsigned default 0,
			`unq_in_5_monthly` bigint(20) unsigned default 0,
			`unq_in_6_monthly` bigint(20) unsigned default 0,
			`unq_in_7_monthly` bigint(20) unsigned default 0,
			`unq_in_8_monthly` bigint(20) unsigned default 0,
			`unq_in_9_monthly` bigint(20) unsigned default 0,
			`unq_in_max_monthly` bigint(20) unsigned default 0,
			`tot_in_0_monthly` bigint(20) unsigned default 0,
			`tot_in_1_monthly` bigint(20) unsigned default 0,
			`tot_in_2_monthly` bigint(20) unsigned default 0,
			`tot_in_3_monthly` bigint(20) unsigned default 0,
			`tot_in_4_monthly` bigint(20) unsigned default 0,
			`tot_in_5_monthly` bigint(20) unsigned default 0,
			`tot_in_6_monthly` bigint(20) unsigned default 0,
			`tot_in_7_monthly` bigint(20) unsigned default 0,
			`tot_in_8_monthly` bigint(20) unsigned default 0,
			`tot_in_9_monthly` bigint(20) unsigned default 0,
			`tot_in_max_monthly` bigint(20) unsigned default 0,
			`unq_out_0_monthly` bigint(20) unsigned default 0,
			`unq_out_1_monthly` bigint(20) unsigned default 0,
			`unq_out_2_monthly` bigint(20) unsigned default 0,
			`unq_out_3_monthly` bigint(20) unsigned default 0,
			`unq_out_4_monthly` bigint(20) unsigned default 0,
			`unq_out_5_monthly` bigint(20) unsigned default 0,
			`unq_out_6_monthly` bigint(20) unsigned default 0,
			`unq_out_7_monthly` bigint(20) unsigned default 0,
			`unq_out_8_monthly` bigint(20) unsigned default 0,
			`unq_out_9_monthly` bigint(20) unsigned default 0,
			`unq_out_max_monthly` bigint(20) unsigned default 0,
			`tot_out_0_monthly` bigint(20) unsigned default 0,
			`tot_out_1_monthly` bigint(20) unsigned default 0,
			`tot_out_2_monthly` bigint(20) unsigned default 0,
			`tot_out_3_monthly` bigint(20) unsigned default 0,
			`tot_out_4_monthly` bigint(20) unsigned default 0,
			`tot_out_5_monthly` bigint(20) unsigned default 0,
			`tot_out_6_monthly` bigint(20) unsigned default 0,
			`tot_out_7_monthly` bigint(20) unsigned default 0,
			`tot_out_8_monthly` bigint(20) unsigned default 0,
			`tot_out_9_monthly` bigint(20) unsigned default 0,
			`tot_out_max_monthly` bigint(20) unsigned default 0,
			PRIMARY KEY (`username`),
			INDEX `rank_join_date` (`join_date`),
			INDEX `rank_in_daily` (`unq_in_0_daily`, `unq_in_overall`, `join_date`),
			INDEX `rank_in_weekly` (`unq_in_0_weekly`, `unq_in_overall`, `join_date`),
			INDEX `rank_in_monthly` (`unq_in_0_monthly`, `unq_in_overall`, `join_date`),
			INDEX `rank_out_daily` (`unq_out_0_daily`, `unq_out_overall`, `join_date`),
			INDEX `rank_out_weekly` (`unq_out_0_weekly`, `unq_out_overall`, `join_date`),
			INDEX `rank_out_monthly` (`unq_out_0_monthly`, `unq_out_overall`, `join_date`),
			INDEX `rank_pv_daily` (`unq_pv_0_daily`, `unq_pv_overall`, `join_date`),
			INDEX `rank_pv_weekly` (`unq_pv_0_weekly`, `unq_pv_overall`, `join_date`),
			INDEX `rank_pv_monthly` (`unq_pv_0_monthly`, `unq_pv_overall`, `join_date`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


		$DB->query("CREATE TABLE `{$CONF['sql_prefix']}_langs` (
			`phrase_id` int(10) NOT NULL auto_increment,
			`language` varchar(150) NOT NULL,
			`definition` text NOT NULL,
			`phrase_name` varchar(100) NOT NULL,
			PRIMARY KEY (`phrase_id`),
			UNIQUE (`language`, `phrase_name`)
		)CHARACTER SET utf8 COLLATE utf8_unicode_ci", __FILE__, __LINE__);


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



		$prog = 70;

		// Set Cookie
		if (!isset($_COOKIE['vl_install'])) {
			setcookie("vl_install", 1, time() + 3600, "/");
		}

		$language_english = '';
		$language_list = '';

		if ($handle = opendir('../languages/import/')) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != ".." && $entry != "index.html") {
					$entry_short = str_replace(".php",'',$entry);
					if ($entry_short == 'english') {
						$language_english .= '<div class="buttons"><a href="../languages/importer.php?l='.$entry_short.'" class="lang_name positive" style="padding: 5px;" title="'.$entry_short.'" target="_blank">Import '.$entry_short.'</a><br /></div>'."\n";
					}
					else {
						$language_list .= '<a href="../languages/importer.php?l='.$entry_short.'" class="lang_name" title="'.$entry_short.'" target="_blank">Import '.$entry_short.'</a><br />'."\n";
					}
				}
			}
			closedir($handle);
		}

		$TMPL['content'] = <<<EndHTML

			<h1>{$LNG['install_import']}</h1>
			{$language_english}<br />

			{$language_list}
			<br />

			<form action="index.php" method="post">
				<input name="l" type="hidden" value="{$FORM['l']}" />
				<input name="done" type="hidden" value="1" />
				<div class="buttons">
					 <button class="positive" name="submit" type="submit">{$LNG['search_next']}</button>
				</div>
			</form>
EndHTML;

    }
    else {
      $TMPL['content'] = "<h3>{$LNG['g_error']}</h3><br />{$LNG['install_error_chmod']}<br />";
    }
  }
  else {
    $TMPL['content'] = "<h3>{$LNG['g_error']}</h3><br />{$LNG['install_error_sql']}<br />";
  }
}
else {

	// Delete cookie
	if ($FORM['done'] == 1) {
	  if (isset($_COOKIE['vl_install'])) {
		  setcookie("vl_install", "", time() - 3600, "/");
	  }
	}

	$LNG['charset'] = "utf-8";
	require_once("{$CONF['path']}/languages/import/english.php");
	require_once("{$CONF['path']}/languages/import/{$FORM['l']}.php");

	$path = str_replace('/install/index.php', '', $_SERVER['PHP_SELF']);

	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){
		$protocol = 'https://';}else{$protocol = 'http://';
	}

	$list_url = "{$protocol}{$_SERVER['HTTP_HOST']}{$path}";

	$prog = 90;

	$TMPL['content'] = <<<EndHTML

{$LNG['install_done']}<br /><br />
<a href="{$list_url}/">{$LNG['install_your']}</a><br />
<a href="{$list_url}/admin/">{$LNG['install_admin']}</a><br />

EndHTML;

}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>VisioList - <?php echo $LNG['install_header']; ?></title>
<meta http-equiv="Content-Type" content="text/html;charset=<?php echo $LNG['charset']; ?>" />
<link rel="stylesheet" type="text/css" media="screen" href="../skins/admin/default.css" />
<link rel="stylesheet" type="text/css" media="screen" href="../js/jquery-ui-1.10.3.custom.css" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
<script type="text/javascript" src="../js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.10.3.custom.min.js"></script>

	<script type="text/javascript">
	$(function() {
	    $('#wrapper').fadeIn(2000);

		$( "#progressbar" ).progressbar({
			value: <?php echo $prog;?>
		});

        $( "#dialog" ).dialog();

		$(".lang_name").click(function() {
            var lang_name = $(this).attr("title");
			var url = '../languages/importer.php?l='+lang_name;
			importwindow = window.open(url,'name','height=600,width=850,scrollbars=yes');
	        if (window.focus) {
				importwindow.focus();
			}
	        return false;
        });

		<?php echo $extra_javascript; ?>

	});
	</script>

<style type="text/css">
.invisible {
    display:none;
}

legend {
    font-weight:bold;
    border-bottom: 2px solid #8f8768;
    width: 900px;
    padding: 10px 0;
    color: #000;
    text-shadow: 1px -1px 1px #fff;
}

fieldset {border: 0;}

label {
    display: block;
    width: 150px;
    float: left;
}
</style>

</head>

<body>
<div id="wrapper" class="invisible">
	<div id="header" style="text-align:center;"><img src="../skins/admin/images/logo.png" width="" /></div><br />
	<div id="content">    <div style="background:  #fff;height:14px;" id="progressbar"></div><br />
	<div  style="padding: 15px;"><?php echo $TMPL['content']; ?></div>
<br /></div>
</div>



</body>
</html>
