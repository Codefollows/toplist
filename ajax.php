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
header("Content-type: application/json; charset=utf-8");
define('VISIOLIST', 1);

// Drop no ajax requests
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {}
else {
    die("No Ajax request.");
}

// Set encoding for multi-byte string functions
mb_internal_encoding("UTF-8");

// Change the path to your full path if necessary
$CONF['path'] = __DIR__;

// Provide a user ip fix in case site uses cloudflare - previously a plugin
$_SERVER['REMOTE_ADDR'] = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];

// Require some classes
require_once ("{$CONF['path']}/sources/misc/classes.php");

// Change the path to your full path if necessary
require_once ("{$CONF['path']}/settings_sql.php");
require_once ("{$CONF['path']}/sources/sql/{$CONF['sql']}.php");

$DB = "sql_{$CONF['sql']}";
$DB = new $DB;
$DB->connect($CONF['sql_host'], $CONF['sql_username'], $CONF['sql_password'], $CONF['sql_database'], 0);

// Settings
$settings = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_settings", __FILE__, __LINE__);
$CONF = array_merge($CONF, $settings);

// Default php date timezone
date_default_timezone_set($CONF['time_zone']);

// Combine the GET and POST input
$FORM = array_merge($_GET, $_POST);

// Drop if no validated admin
if (!isset($_COOKIE['atsphp_sid_admin'])) 
{
    die("You are not allowed to few this page.");
}
elseif (isset($_COOKIE['atsphp_sid_admin'])) 
{
    require_once("{$CONF['path']}/sources/misc/session.php");
    $session = new session;
	
	list($type, $data) = $session->get($_COOKIE['atsphp_sid_admin']);

	if ($type == 'admin') {
        $session->update($_COOKIE['atsphp_sid_admin']);
	}
    else {	  
        die("You are not allowed to few this page.");
    }
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

$result = $DB->query("SELECT category, category_slug, skin, cat_description, cat_keywords FROM {$CONF['sql_prefix']}_categories ORDER BY category", __FILE__, __LINE__);
while (list($category, $category_slug, $skin, $cat_description, $cat_keywords) = $DB->fetch_array($result)) {
    $CONF['categories'][$category]['skin'] = $skin;
    $CONF['categories'][$category]['cat_desc'] = $cat_description;
    $CONF['categories'][$category]['cat_key'] = $cat_keywords;
    $CONF['categories'][$category]['cat_slug'] = $category_slug;
}

// Here we define, what action we take
if(isset($FORM['action']) && !empty($FORM['action']))
{
    switch($FORM['action'])
    {
        case 'username_list'  : $inactive_days = isset($FORM['inactive_days']) ? intval($FORM['inactive_days']) : 0;
                                ajax_username_list($inactive_days);
                                break;
        case 'send_mail'      : ajax_send_mail(); break;
        case 'ffmpeg_convert' : ffmpeg_convert(); break;

    }
}

// Plugin hook
eval(PluginManager::getPluginManager()->pluginHooks('ajax_global_start'));

// Update admin exportable mail list depending on inactive days
function ajax_username_list($inactive_days) {
    global $DB, $CONF, $FORM;

	if($inactive_days > 0)
	{
		$where_extend = '';

		switch($FORM['inactive_method'])
		{
			case 'gt' : $where_extend .= "AND days_inactive > {$inactive_days}";  break;
			case 'gte': $where_extend .= "AND days_inactive >= {$inactive_days}"; break;
			case 'lt' : $where_extend .= "AND days_inactive < {$inactive_days}";  break;
			case 'lte': $where_extend .= "AND days_inactive <= {$inactive_days}"; break;
			case 'eq' : $where_extend .= "AND days_inactive = {$inactive_days}";  break;
		}

		$result = $DB->query("SELECT sites.username FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats WHERE sites.username = stats.username {$where_extend}", __FILE__, __LINE__);
	}
	else
	{
		$result = $DB->query("SELECT username FROM {$CONF['sql_prefix']}_sites", __FILE__, __LINE__);
	}
	
	// Plugin hook
	eval(PluginManager::getPluginManager()->pluginHooks('ajax_username_list_query'));

	$username_list = '';
	while (list($username) = $DB->fetch_array($result))
	{
		$username_list .= $username.',';
	}
	$username_list = explode(",", rtrim($username_list, ','));
	$username_list = array_unique($username_list);
	$username_list = implode(", ", $username_list);

	$response = array('username_list' => $username_list);

	echo json_encode($response);
}

// Send mails in batches
function ajax_send_mail() {
    global $DB, $CONF, $FORM;

    // Take care of some vars so they are valid
    $seconds   = (isset($FORM['seconds']) && $FORM['seconds'] > 0) ? (int)$FORM['seconds'] : 0;
    $sent      = (isset($FORM['sent_count']) && $FORM['sent_count'] > 0) ? (int)$FORM['sent_count'] : 0;
    $fail      = (isset($FORM['fail_count']) && $FORM['fail_count'] > 0) ? (int)$FORM['fail_count'] : 0;
    $response  = array();
    $mail_sent = 0;

    // Recieved user list, remove possible leading and ending whitespace and comma
	// Remove empty strings ( but keeps 0 - zero )
    $username_list = explode(",", trim($FORM['username_list'], ', '));
    $username_list = array_filter(array_map('trim', $username_list), 'strlen');

    // Stop if 0 users in list
    if(empty($username_list)) { return 0; }

    // Save mail for later use
	$username_sql = $DB->escape($username_list[0], 1);
	$user_data = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats WHERE sites.username = stats.username AND sites.username = '{$username_sql}'", __FILE__, __LINE__);

    // Remove current user for next batch
    unset($username_list[0]);
	
	if (!empty($user_data))
	{
		$user_data = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $user_data);
		
		// Number format only valid stats, after averages have been built
		// As average needs ints
		foreach ($user_data as $key => $value)
		{
			if (strpos($key, 'unq_') === 0 || strpos($key, 'tot_') === 0)
			{
				$user_data[$key] = number_format($user_data[$key]);
			}
		}
		
		// Prepare Category Url
		$category_raw = htmlspecialchars_decode($user_data['category'], ENT_QUOTES);
		$user_data['category_url'] = isset($CONF['categories'][$category_raw]) ? urlencode($CONF['categories'][$category_raw]['cat_slug']) : '';
		
		
		// Plugin hook - Push new data into $user_data
		eval(PluginManager::getPluginManager()->pluginHooks('ajax_send_mail_start'));
		
		
		// No valid mail? Mark this item as not sent!
		if(filter_var($user_data['email'], FILTER_VALIDATE_EMAIL))
		{
			$orig_message = $FORM['message'];
			$orig_subject = $FORM['subject'];
			
			// Template tags + optional limit text output
			$message = preg_replace_callback('/{\$(.+?)((?:,\s?length=)([0-9]+?))?}/i', function($matches) use ($user_data) {
				
				if(isset($matches[3])) {
					$limit = $matches[3];
					if (mb_strlen($user_data[$matches[1]]) > $limit) {
						$user_data[$matches[1]] = mb_substr($user_data[$matches[1]], 0, mb_strrpos(mb_substr($user_data[$matches[1]], 0, $limit), " ")) . "...";
					}
				}

				return isset($user_data[$matches[1]]) ? $user_data[$matches[1]] : "";
			}, $orig_message);

			$subject = preg_replace_callback('/{\$(.+?)((?:,\s?length=)([0-9]+?))?}/i', function($matches) use ($user_data) {

				if(isset($matches[3])) {
					$limit = $matches[3];
					if (mb_strlen($user_data[$matches[1]]) > $limit) {
						$user_data[$matches[1]] = mb_substr($user_data[$matches[1]], 0, mb_strrpos(mb_substr($user_data[$matches[1]], 0, $limit), " ")) . "...";
					}
				}

				return isset($user_data[$matches[1]]) ? $user_data[$matches[1]] : "";
			}, $orig_subject);


			// Plugin hook - do something with above defined stuff
			eval(PluginManager::getPluginManager()->pluginHooks('ajax_send_mail_details'));


			// Send mail
			require_once("{$CONF['path']}/sources/misc/class.phpmailer.php");
			$mail = new PHPMailer;

			//USE SMTP OR MAIL?
			if(!empty($CONF['smtp_host']) && !empty($CONF['smtp_password']))
			{
				$mail->IsSMTP();
				$mail->Host = $CONF['smtp_host'];
				$mail->SMTPAuth = true;
				$mail->Port = $CONF['smtp_port'];
				$mail->Username = $CONF['smtp_user'];
				$mail->Password = $CONF['smtp_password'];
				$mail->SMTPSecure = 'tls';
				$mail->CharSet = 'UTF-8';
			}
			else
			{
				$mail->IsMail();
			}
			$mail->From = $CONF['your_email'];
			$mail->FromName = $CONF['list_name'];
			$mail->AddReplyTo($CONF['your_email'], $CONF['list_name']);

			$mail->AddAddress($user_data['email']);

			$mail->WordWrap = 50;
			$mail->IsHTML(true);

			$mail->Subject = $subject;
			$mail->Body    = $message;

			// mail sent, mark item
			if($mail->Send()) {
				$mail_sent = 1;
			}
		}
	}

    // Prepare response for next run
    // Note: if next_set is empty, it indicate end of repeat
    $response = array(
        'username' => $user_data['username'],
        'subject' => $orig_subject,
        'message' => $orig_message,
        'mail_sent' => $mail_sent,
        'sent_count' => ($mail_sent == 1 ? ++$sent : $sent),
        'fail_count' => ($mail_sent == 1 ? $fail : ++$fail),
        'next_set' => implode(',', $username_list)
    );

    // lets sleep btw each mail if set
    sleep($seconds);

    // Return to ajax
    echo json_encode($response);
}


// Member mass gif to mp4
function ffmpeg_convert() 
{
    global $DB, $CONF, $FORM;

	$total     = (int)$FORM['total'];
	$checked   = (int)$FORM['checked'];
	$converted = (int)$FORM['converted'];

	// local image not found
	$failed      = (int)$FORM['failed'];
	$failed_urls = '';
	
	$remaining = $total - $checked;
	$start     = $total - $remaining;

	$response = array();

	if ($remaining > 0)
	{
		// Have a little nap, so we dont overload CPU
		sleep(1);
		
		$result = $DB->select_limit("SELECT `username`, `banner_url`, `premium_banner_url`, `mp4_url`, `premium_mp4_url` FROM `{$CONF['sql_prefix']}_sites` ORDER BY `username`", 5, $start, __FILE__, __LINE__);
		
		if ($DB->num_rows($result)) 
		{
			$base = new base;
			
			while ($row = $DB->fetch_array($result)) 
			{	
				$update_set = '';
		
				// Normal banner
				// If like $CONF['default_banner'] skip, system ( rankings etc ) will read width/height and if exist mp4 of button_config.php
				if (!empty($row['banner_url']) && $row['banner_url'] !== $CONF['default_banner']) 
				{	
					// Hosted on list url or external image?
					$local_image = stripos($row['banner_url'], $CONF['list_url']) === 0 ? true : false; 

					if ($local_image === true) 
					{
						// Replace list url with path
						$image_path = str_replace($CONF['list_url'], $CONF['path'], $row['banner_url']);
						
						// Check if file is still on filesystem
						if (file_exists($image_path)) 
						{
							// gif to video conversion using ffmpeg for smaller filesizes
							// params - username, image url, old video url, save in same dir, premium
							$video = $base->ffmpeg_convert_image($row['username'], $row['banner_url'], $row['mp4_url'], false, false);
				
							if (!empty($video))
							{
								$video_url_sql = $DB->escape($video['url'], 1);
								
								$update_set .= "mp4_url = '{$video_url_sql}',";
								
								$converted++;
							}
						}
						else {
							$failed++;
							$failed_urls .= "{$row['username']} - {$row['banner_url']}<br />";
						}
					}
				}
				
				// Premium banner
				// If like $CONF['default_banner'] skip, system ( rankings etc ) will read width/height and if exist mp4 of button_config.php
				if (!empty($row['premium_banner_url']) && $row['premium_banner_url'] !== $CONF['default_banner']) 
				{	
					// Hosted on list url or external image?
					$local_image = stripos($row['premium_banner_url'], $CONF['list_url']) === 0 ? true : false; 

					if ($local_image === true) 
					{
						// Replace list url with path
						$image_path = str_replace($CONF['list_url'], $CONF['path'], $row['premium_banner_url']);
						
						// Check if file is still on filesystem
						if (file_exists($image_path)) 
						{
							// gif to video conversion using ffmpeg for smaller filesizes
							// params - username, image url, old video url, save in same dir, premium
							$video = $base->ffmpeg_convert_image($row['username'], $row['premium_banner_url'], $row['premium_mp4_url'], false, true);
				
							if (!empty($video))
							{
								$video_url_sql = $DB->escape($video['url'], 1);
								
								$update_set .= "premium_mp4_url = '{$video_url_sql}',";
								
								$converted++;
							}
						}
						else {
							$failed++;
							$failed_urls .= "{$row['username']} - {$row['premium_banner_url']}<br />";
						}
					}
				}

				if (!empty($update_set))
				{
					$update_set = rtrim($update_set, ',');
					$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET {$update_set} WHERE username = '{$row['username']}'", __FILE__, __LINE__);
				}
				
				$checked++;
			}
		}
	}

	$response['total']       = $total;
	$response['checked']     = $checked;
	$response['converted']   = $converted;
	$response['failed']      = $failed;
	$response['failed_urls'] = $failed_urls;

	echo json_encode($response);
}

function getExtension($str)
{
	$i = strrpos($str,".");
	if (!$i) { return ""; }
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return $ext;
}

$DB->close();
