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

if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

//error_reporting(0);

class banner extends base {
  public function __construct() {
    global $CONF, $FORM, $LNG, $DB, $TMPL;

    $upload_mode = 1;
    if(isset($FORM['mode']) && is_numeric($FORM['mode'])) {
        $upload_mode = intval($FORM['mode']);
    }
	
	// With this we allow users to upload to a different location
	$banner_folder = 'banners';

    switch ($upload_mode) {
        case "3":
 		    $TMPL['user_cp_upload_max_size'] = 100; //Premium Banner
 		    $TMPL['user_cp_upload_max_width'] = $CONF['max_premium_banner_width']; 
 		    $TMPL['user_cp_upload_max_height'] = $CONF['max_premium_banner_height'];
 		    $bannertype = $LNG['premium']; 
            break;
        case "1":
        default:
 		    $TMPL['user_cp_upload_max_size'] = 100; //Regular Banner
 		    $TMPL['user_cp_upload_max_width'] = $CONF['max_banner_width']; 
 		    $TMPL['user_cp_upload_max_height'] = $CONF['max_banner_height'];
 		    $bannertype = $LNG['user_cp_banner_regular'];   
		    break;
    }

    $TMPL['header'] = $LNG['user_cp_banner_upload_your'].' '.$bannertype;

		
    //GET OWNER AND SITE LIST
	$result = $DB->query("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
    while (list($myowner) = $DB->fetch_array($result)) 
    {
		$TMPL['myowner'] = $myowner;
    }

    $row = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
	$row = array_map(function($value) {
		return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
	}, $row);
		
    $TMPL = array_merge($TMPL, $row);

	
	eval (PluginManager::getPluginManager ()->pluginHooks ('usercp_banner_upload_start'));	

	
    if($upload_mode == 3) 
    {
        // Premium mode
	    $result = $DB->query("SELECT username, url FROM {$CONF['sql_prefix']}_sites WHERE owner = '{$TMPL['myowner']}' AND (active = 1 OR active = 3) AND premium_flag > 0", __FILE__, __LINE__);

        eval (PluginManager::getPluginManager ()->pluginHooks ('usercp_banner_extend_upload_mode_premium'));	
	}
    else 
    {
        // Whatever form "mode" is else, use normal mode
	    $result = $DB->query("SELECT username, url FROM {$CONF['sql_prefix']}_sites WHERE owner = '{$TMPL['myowner']}' AND (active = 1 OR active = 3)", __FILE__, __LINE__);

        eval (PluginManager::getPluginManager ()->pluginHooks ('usercp_banner_extend_upload_mode_regular'));	
	}

    $TMPL['user_cp_upload_sites'] = '';
    $i = 0;
    while (list($sitelist, $sitelist_url) = $DB->fetch_array($result)) 
    {
	    $i++;
		$TMPL['user_cp_upload_sites'] .= '<option value="'.$sitelist.'">'.$sitelist_url.'</option>';
    }

    // Stop here if while loop is empty
    if(empty($i)) 
    {
        header("Location: {$TMPL['list_url']}/index.php?a=user_cpl&b=user_premium"); 
        exit;
    }
	

	// csrf token at the end so plugins can not meddle with the template tag
	$TMPL['csrf_token'] = generate_csrf_token($TMPL['username'], 'user_cp_banner_csrf');
		
    // On submit
    if(isset($FORM['submit']))
    {
		// Validate csrf token
		// Before anything other gets defined or executed
		if (!isset($FORM['csrf_token']) || validate_csrf_token($FORM['csrf_token'], 'user_cp_banner_csrf') === false) {
			$this->error($LNG['g_session_expired'], 'user_cp');
		}
		
		
        $TMPL['myusername'] = isset($FORM['site']) ? $DB->escape($FORM['site'], 1) : '';
        $errors = 0;

 	    if (isset($_FILES['image']['name']))  
 	    {
			// File errors can be 0-8, 0 means no base error
			if (!empty($_FILES['image']['error']))
			{
				$file_error = $this->get_file_error($_FILES['image']['error']);
				
				$TMPL['user_cp_upload_errors'] = "<h1 class=\"user_cp_upload_error\">{$file_error}</h1>";
				$errors = 1;
			}
			else
			{
				$filename = stripslashes($_FILES['image']['name']);
				$extension = $this->getExtension($filename);
				$extension = strtolower($extension);
				if (($extension != "jpg") && ($extension != "jpeg") && ($extension != "png") && ($extension != "gif")) 
				{
					$TMPL['user_cp_upload_errors'] = "<h1 class=\"user_cp_upload_error\">{$LNG['user_cp_banner_unknown_extension']}</h1>";
					$errors = 1;
				}
				else
				{
					//File size check
					$size = $this->get_file_size($_FILES['image']['tmp_name']);
					if ($size > $TMPL['user_cp_upload_max_size'] * 1024)
					{
						$TMPL['user_cp_upload_errors'] = "<h1 class=\"user_cp_upload_error\">{$LNG['user_cp_banner_error_max_filesize']}</h1>";
						$errors = 1;
					}

					//Check Dimensions
					if($CONF['max_banner_height'] > 0 && empty($errors)) 
					{
						list($width, $height, $type, $attr) = getimagesize("{$_FILES['image']['tmp_name']}");
						if ($width > $TMPL['user_cp_upload_max_width'] || $height > $TMPL['user_cp_upload_max_height'])
						{
							$TMPL['user_cp_upload_errors'] = "<h1 class=\"user_cp_upload_error\">{$LNG['user_cp_banner_error_max_dimensions']}</h1>";
							$errors = 1;
						}
					}

					//Check MIME
					if(empty($errors)) 
					{
						$size = getimagesize($_FILES['image']['tmp_name']);
						$fp = fopen($_FILES['image']['tmp_name'], "rb");
						if ($size && $fp) 
						{
							// All OK
							$banner_width  = (int)$size[0];
							$banner_height = (int)$size[1];

							// Plugin Hook - image data
							eval (PluginManager::getPluginManager ()->pluginHooks ('usercp_banner_upload_image_data'));	
						} 
						else 
						{
							$TMPL['user_cp_upload_errors'] = "<h1 class=\"user_cp_upload_error\">{$LNG['user_cp_banner_invalid_file']}</h1>";
							$errors = 1;
						}
					}

					// Check for correct username. Some may alter select options to fuck up other members
					if(empty($errors)) 
					{
						$result = $DB->query("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);
						while (list($myowner_check) = $DB->fetch_array($result)) 
						{
							$TMPL['myowner_check'] = $myowner_check;
						}

						if($TMPL['myowner_check'] != $TMPL['myowner'])
						{
							exit;
						}
					}

					// The image time to bust cache
					$image_time = time() + (3600 * $CONF['time_offset']);

					if($upload_mode == 3)
					{
						$newname = "{$banner_folder}/{$TMPL['myusername']}_premium_{$image_time}.{$extension}";
					}
					else 
					{
						$newname = "{$banner_folder}/{$TMPL['myusername']}_{$image_time}.{$extension}";
					}

					// move image to upload path
					if(empty($errors)) 
					{
						if (!move_uploaded_file($_FILES['image']['tmp_name'], "{$CONF['path']}/{$newname}"))
						{
							$TMPL['user_cp_upload_errors'] = "<h1 class=\"user_cp_upload_error\">{$LNG['user_cp_banner_copy_fail']}</h1>";
							$errors = 1;
						}
					}
				}
			}
        }
        else {
	        $TMPL['user_cp_upload_errors'] = "<h1 class=\"user_cp_upload_error\">{$LNG['user_cp_banner_no_image']}</h1>";
	        $errors = 1;
        }

        // If no errors registered, handle old and new banner/mp4
        if(empty($errors)) 
        {
			list($old_banner_url, $old_mp4_url, $old_premium_banner_url, $old_premium_mp4_url) = $DB->fetch("SELECT banner_url, mp4_url, premium_banner_url, premium_mp4_url FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);

 	        $fullnewname = $CONF['list_url'].'/'.$newname;

			// Remove old normal/premium banner
            if($upload_mode == 3) 
			{
				// Only if not default banner
				if (!empty($old_premium_banner_url) && $old_premium_banner_url != $CONF['default_banner']) 
				{
					// Only if local image
					if (stripos($old_premium_banner_url, $CONF['list_url']) === 0) 
					{	
						$old_premium_banner = str_replace($CONF['list_url'], $CONF['path'], $old_premium_banner_url);

						if (file_exists($old_premium_banner)) {
							unlink($old_premium_banner);
						}
					}
				}
				
                $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET premium_banner_url = '{$fullnewname}', premium_banner_width = {$banner_width}, premium_banner_height = {$banner_height} WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);
            }
            else 
			{
				// Only if not default banner
				if (!empty($old_banner_url) && $old_banner_url != $CONF['default_banner']) 
				{
					// Only if local image
					if (stripos($old_banner_url, $CONF['list_url']) === 0) 
					{	
						$old_banner = str_replace($CONF['list_url'], $CONF['path'], $old_banner_url);

						if (file_exists($old_banner)) {
							unlink($old_banner);
						}
					}
				}
				
                $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET banner_url = '{$fullnewname}', banner_width = {$banner_width}, banner_height = {$banner_height} WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);
            } 
	
	
            $TMPL['user_cp_upload_success'] = "<h1>{$LNG['user_cp_banner_success']}</h1> <p><img src=\"{$fullnewname}\" width=\"{$banner_width}\" height=\"{$banner_height}\" /></p>";

            // final filesize
            $finalsize = $this->formatBytes($this->get_file_size($newname, true));
            $allowedfilesize = $this->formatBytes($TMPL['user_cp_upload_max_size'] * 1024);
            $TMPL['user_cp_upload_filesize'] = "<h2>{$LNG['user_cp_banner_file_size']}: {$finalsize} / {$allowedfilesize}</h2>";


			// gif to video conversion using ffmpeg for smaller filesizes
			// params - username, image url, old video url, save in same dir, premium
			$ffmpeg_is_premium = $upload_mode === 3 ? true : false;
			$video_url_old     = $ffmpeg_is_premium ? $old_premium_mp4_url : $old_mp4_url;
			$video  = $this->ffmpeg_convert_image($TMPL['myusername'], $fullnewname, $video_url_old, false, $ffmpeg_is_premium);
			
			// Reset video column if anything went wrong
			if (!empty($video))
			{
				$video_url_sql = $DB->escape($video['url'], 1);
				
				if($upload_mode === 3) {
					$DB->query("UPDATE `{$CONF['sql_prefix']}_sites` SET `premium_mp4_url` = '{$video_url_sql}' WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);
				}
				else {
					$DB->query("UPDATE `{$CONF['sql_prefix']}_sites` SET `mp4_url` = '{$video_url_sql}' WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);
				}
			}
			else
			{
				if($upload_mode === 3) {
					$DB->query("UPDATE `{$CONF['sql_prefix']}_sites` SET `premium_mp4_url` = NULL WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);
				}
				else {
					$DB->query("UPDATE `{$CONF['sql_prefix']}_sites` SET `mp4_url` = NULL WHERE username = '{$TMPL['myusername']}'", __FILE__, __LINE__);
				}
			}

            // Plugin Hook - extend update query or set new tmpl vars
            eval (PluginManager::getPluginManager ()->pluginHooks ('usercp_banner_upload_finish'));
        }
    }

    $TMPL['user_cp_content'] = $this->do_skin('user_cp_upload_banner');

  }

  function getExtension($str) {
    $i = strrpos($str,".");
    if (!$i) { return ""; }
    $l = strlen($str) - $i;
    $ext = substr($str,$i+1,$l);
    return $ext;
  }

}
