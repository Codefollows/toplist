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
$CONF['path']            = __DIR__ .'/..';
$CONF['install_running'] = true;

// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

// Progress = 10
$prog = 10;

$extra_javascript = '';

function getExtension($str)
{
	$i = strrpos($str,".");
	if (!$i) { return ""; }
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return $ext;
}

require_once("{$CONF['path']}/settings_sql.php");

if(isset($CONF['sql_database'])) {
   require_once("{$CONF['path']}/sources/sql/mysql.php");
   $DB = "sql_mysql";
   $DB = new $DB;

   if ($DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database'])) {

      // Settings
      $settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
      $CONF = array_merge($CONF, $settings);

      // Include users current english file
      require_once("{$CONF['path']}/languages/english.php");
	  
	  // Rewrite to be used in importer
	  foreach ($LNG as $import_phrase => $import_definition) {
		  $import_definition = stripslashes($import_definition);
          $import_definition = addslashes($import_definition);
          $IMPORT[''.$import_phrase.''] = ''.$import_definition.'';
	  }
	  
	  // Include possible other language
      if ($CONF['default_language'] != 'english') {
          require_once("{$CONF['path']}/languages/{$CONF['default_language']}.php");
	  }
      $LNG['charset'] = "utf-8";


      if(isset($FORM['upgrade']) && $FORM['upgrade'] == 1) 
	  {
		$TMPL['upgrade'] = "<h3>Performing database updates...</h3><br /><br />";

		// Get VL Version
		list($vl_version) = $DB->fetch("SELECT version FROM {$CONF['sql_prefix']}_etc", __FILE__, __LINE__);

		// Update from which version onwards?
		switch($vl_version) 
		{
            // VL 0.2 -> 0.3
            case '0.2':

				$new_version = '0.3';
				
				require_once("{$CONF['path']}/install/versions/0.3.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // VL 0.3 -> 0.4
            case '0.3':

				$new_version = '0.4';
				
				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // VL 0.4 -> 0.5
            case '0.4':

				$new_version = '0.5';
				
				require_once("{$CONF['path']}/install/versions/0.5.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // VL 0.5 -> 0.6
            case '0.5':

				$new_version = '0.6';
				
				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // VL 0.6 -> 0.7 Beta
            case '0.6':

				$new_version = '0.7 Beta';
				
				require_once("{$CONF['path']}/install/versions/0.7-Beta.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // For ppl running on Beta 1 with wrong version number
            case '0.7':

				$new_version = '0.7 Beta';
				
				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';
				
				
            // VL 0.7 Beta -> 0.7 Beta 2
            case '0.7 Beta':

				$new_version = '0.7 Beta 2';
				
				require_once("{$CONF['path']}/install/versions/0.7-Beta-2.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // VL 0.7 Beta 2 -> 0.7 Final
            case '0.7 Beta 2':

				$new_version = '0.7 Final';
				
				require_once("{$CONF['path']}/install/versions/0.7-Final.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // VL 0.7 Final -> 0.8
            case '0.7 Final':

				$new_version = '0.8';
				
				require_once("{$CONF['path']}/install/versions/0.8.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


            // VL 0.8 -> 0.9
            case '0.8':

				$new_version = '0.9';
				
				require_once("{$CONF['path']}/install/versions/0.9.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 0.9 -> 1.0
            case '0.9':

				$new_version = '1.0';
				
				require_once("{$CONF['path']}/install/versions/1.0.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 1.0 -> 1.1
            case '1.0':

				$new_version = '1.1';
				
				require_once("{$CONF['path']}/install/versions/1.1.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 1.1 -> 1.2
            case '1.1':

				$new_version = '1.2';
				
				require_once("{$CONF['path']}/install/versions/1.2.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 1.2 -> 1.3
            case '1.2':
			
				$new_version = '1.3';
				
				require_once("{$CONF['path']}/install/versions/1.3.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 1.3 -> 1.4
            case '1.3':

				$new_version = '1.4';
				
				require_once("{$CONF['path']}/install/versions/1.4.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';
				

			// VL 1.4 -> 1.5
            case '1.4':

				$new_version = '1.5';
				
				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 1.5 -> 1.6
            case '1.5':

				$new_version = '1.6';
				
				require_once("{$CONF['path']}/install/versions/1.6.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';
				

			// VL 1.6 -> 1.7
            case '1.6':

				$new_version = '1.7';
				
				require_once("{$CONF['path']}/install/versions/1.7.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 1.7 -> 1.8
			case '1.7':

				$new_version = '1.8';
				
				require_once("{$CONF['path']}/install/versions/1.8.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';


			// VL 1.8 -> 1.9
			case '1.8':

				$new_version = '1.9';
				
				require_once("{$CONF['path']}/install/versions/1.9.php");

				$TMPL['upgrade'] .= "<b>VisioList {$new_version}</b><br />";
				$TMPL['upgrade'] .= 'Done<br /><br />';
		}

		// Finally set new version
		if (isset($new_version)) {
			$DB->query("UPDATE {$CONF['sql_prefix']}_etc SET `version` = '{$new_version}'", __FILE__, __LINE__);
		}


		// Rebuild Only english language file. Translated phrases are later imported via admin
		foreach ($IMPORT as $key => $value) {
			$key = $DB->escape("$key",1);
			$value = $DB->escape("$value",1);
			$DB->query("INSERT IGNORE INTO {$CONF['sql_prefix']}_langs SET phrase_name = '{$key}', definition = '{$value}', language = 'english'", __FILE__, __LINE__);
		}
		$output = '';
		$result = $DB->query("SELECT phrase_name, definition FROM {$CONF['sql_prefix']}_langs WHERE language = 'english' ORDER BY phrase_name ASC", __FILE__, __LINE__);
		while (list($phrase_name, $definition) = $DB->fetch_array($result)) {
			$definition = stripslashes($definition);
			$definition = str_replace('\"', '"', addslashes($definition));
			$output .= "\$LNG['{$phrase_name}'] = '$definition';\n";
		}
		$file = "./../languages/english.php";
		if ($fh = @fopen($file, 'w')) {
			$lang_output = <<<EndHTML
<?php

if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

$output
?>
EndHTML;
			fwrite($fh, $lang_output);
			fclose($fh);
		}


		// Upgrade members banner width/height and if supported mp4 conversion via ajax
		// Only of vl smaller 1.6
		if ($vl_version < '1.6')
		{
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

		}


		$prog = 90;

		// For vl under 1.6, hide this success as we have ajax going on in 1.5 -> 1.6 upgrade
		$ajax_hide = $vl_version < '1.6' ? 'style="display: none;"' : '';

		$TMPL['upgrade'] .= '<div id="success" '.$ajax_hide.'>';

		$TMPL['upgrade'] .= '<br /><h3>'.$LNG['upgrade_complete'].'!</h3><br />
							<a href="'.$CONF['list_url'].'/">'.$LNG['install_your'].'</a><br />
							<a href="'.$CONF['list_url'].'/admin/">'.$LNG['install_admin'].'</a><br /><br />';
		$TMPL['upgrade'] .= "<br /><h3>{$LNG['upgrade_complete2']}</h3>";

		$TMPL['upgrade'] .= '</div>';
      }
      else {

		$TMPL['upgrade'] = '<div class="ui-widget">
								<div class="ui-state-highlight ui-corner-all" style="margin: 5px 0; padding: 10px 10px 15px 10px;">
									<p><span class="ui-icon ui-icon-info" style="float: left; margin-right: 0.3em;"></span>
									<h3>'.$LNG['upgrade_update_active_installation'].' <a href="?upgrade=1">'.$LNG['upgrade_proceed'].'</a></h3>
								</div>
							</div>';
      }
   }
}
// Upgrade Code END


// Set the Requirements
$TMPL['requirements'] = '<h2>System Requirements</h2>';
if (function_exists('curl_init')) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/>'." Curl functions are available.<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/>'." Curl not available.<br />\n";
}
if (function_exists('gd_info')) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/>'." GD functions are available.<br />\n";
}
else {
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
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> FreeType Not Installed'."<br />\n";
}
if (phpversion() > 5) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> PHP Version: '.phpversion()."<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> PHP Version: '.phpversion().' is out of date! You must upgrade'."<br />\n";
}
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi' || substr($sapi_type, 0, 3) == 'fpm') {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> CGI PHP Installed - NO problems expected'."<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> CGI PHP NOT detected.  You MAY encounter some file ownership problems when editing files uploaded through the admin interface'."<br />\n";
}
if (is_writable('../settings_sql.php')) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> settings_sql.php is writeable'."<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> settings_sql.php is NOT writeable, make sure it exists and CHMOD 666.  We cannot proceed with the installer until settings_sql is writeable.'."<br />\n";
}
if (is_writable('../button_config.php')) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> button_config.php is writeable'."<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> button_config.php is NOT writeable, make sure it exists and CHMOD 666. '."<br />\n";
}
if (is_writable('../plugins')) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> The plugins directory is writeable'."<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> The plugins directory is NOT writeable, make sure it exists and CHMOD 777.'."<br />\n";
}
if (is_writable('../banners')) {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/yes.png" alt="YES"/> The banners directory is writeable'."<br />\n";
}
else {
   $TMPL['requirements'] .= '<img src="../skins/admin/images/no.png" alt="NO"/> The banners directory is NOT writeable, make sure it exists and CHMOD 777.'."<br />\n";
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


// Start Content
$TMPL['content'] = <<<EndHTML

   {$TMPL['upgrade']}
   {$TMPL['requirements']}
   <br /><br />
EndHTML;
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title>VisioList - <?php echo $LNG['upgrade_header']; ?></title>
<meta http-equiv="Content-Type" content="text/html;charset=<?php echo $LNG['charset']; ?>" />
<link rel="stylesheet" type="text/css" media="screen" href="../skins/admin/default.css" />
<link rel="stylesheet" type="text/css" media="screen" href="../js/jquery-ui-1.10.3.custom.css" />
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.1/css/all.css" integrity="sha384-gfdkjb5BdAXd+lj+gudLWI+BXq4IuLW5IT+brZEZsLFm++aCMlF1V92rMkPaX4PP" crossorigin="anonymous">
<script type="text/javascript" src="../js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="../js/jquery-ui-1.10.3.custom.min.js"></script>
	<script type="text/javascript">
	$(function() {

		$('#wrapper').fadeIn(1000);
		$( "#progressbar" ).progressbar({
			value: <?php echo $prog;?>
		});
        $( "#dialog" ).dialog();

		<?php echo $extra_javascript; ?>

	});
	</script>
<style type="text/css">
.invisible {
    display:none;
}
</style>
</head>

<body>
<div id="wrapper" class="invisible">
	<div id="header" style="text-align:center;"><img src="../skins/admin/images/logo.png" width="" /></div><br />
	<div id="content">    <div id="progressbar"></div><br />
	<div  style="padding: 15px;"><?php echo $TMPL['content']; ?></div>
<br /></div>
</div>
</body>
</html>
