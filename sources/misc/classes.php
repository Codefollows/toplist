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

class base {
  function error($message, $skin = 0) {
    global $LNG, $TMPL;

    header("HTTP/1.0 404 Not Found");

    $TMPL['header'] = $LNG['g_error'];
    $TMPL['error']  = $message;
    if ($skin) {
      $TMPL["{$skin}_content"] = $this->do_skin('error');
      $TMPL['content'] = $this->do_skin($skin);
    }
    else {
      $TMPL['content'] = $this->do_skin('error');
    }

    $skin = new main_skin('wrapper');
    echo $skin->make();
    exit;
  }

  static public function do_skin($filename) {
    $skin = new skin($filename);
    return $skin->make();
  }

  static public function do_plugin_skin($pluginpath, $filename) {
    $skin = new skin($filename, $pluginpath);
    return $skin->make_plugin_skin();
  }

  static public function rank_by($ranking_method = 0, $ranking_period = 0) {
    global $CONF;

    if (!$ranking_method) {
      $ranking_method = $CONF['ranking_method'];
    }
    if (!$ranking_period) {
      $ranking_period = $CONF['ranking_period'];
    }

    if ($ranking_period == 'overall') {
      $rank_by = "unq_{$ranking_method}_overall";
    }
    elseif (!$CONF['ranking_average']) {
      $rank_by = "unq_{$ranking_method}_0_{$ranking_period}";
    }
    else {
      $rank_by = '(';
      for ($i = 0; $i < 10; $i++) {
        $rank_by .= "unq_{$ranking_method}_{$i}_{$ranking_period} + ";
      }
      $rank_by .= "0) / 10";
    }

    return $rank_by;
  }

  static public function bad_words($text) {
    global $CONF, $DB;

    $result = $DB->query("SELECT word, replacement, matching FROM {$CONF['sql_prefix']}_bad_words", __FILE__, __LINE__);
    while (list($word, $replacement, $matching) = $DB->fetch_array($result)) {
      if ($matching) { // Exact matching
        $word = preg_quote($word);
        $text = preg_replace("/\b{$word}\b/i", $replacement, $text);
      }
      else { // Global matching
        $word = preg_quote($word);
        $text = preg_replace("/{$word}/i", $replacement, $text);

        // str_ireplace() would be faster, but it's only in PHP 5 :(
        // $text = str_ireplace($word, $replacement, $text);
      }
    }

    return $text;
  }

    // Fix for overflowing signed 32 bit integers,
    // works for sizes up to 2^32-1 bytes (4 GiB - 1):
    public function fix_integer_overflow($size) {
        if ($size < 0) {
            $size += 2.0 * (PHP_INT_MAX + 1);
        }
        return $size;
    }

    public function get_file_error($error_code) {
		global $LNG;

		$errors = [
			1 => $LNG['user_cp_banner_error_1'],
			2 => $LNG['user_cp_banner_error_2'],
			3 => $LNG['user_cp_banner_error_3'],
			4 => $LNG['user_cp_banner_no_image'],
			6 => $LNG['user_cp_banner_error_6'],
			7 => $LNG['user_cp_banner_error_7'],
			8 => $LNG['user_cp_banner_error_8'],
		];
/*
				1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
				2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
				3 => 'The uploaded file was only partially uploaded',
				4 => $LNG['user_cp_banner_no_image'],
				6 => 'Missing a temporary folder',
				7 => 'Failed to write file to disk',
				8 => 'A PHP extension stopped the file upload',


*/

		return isset($errors[$error_code]) ? $errors[$error_code] : 'Unknown Error';
    }

    public function get_file_size($file_path, $clear_stat_cache = false) {
        if ($clear_stat_cache) {
            if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
                clearstatcache(true, $file_path);
            } else {
                clearstatcache();
            }
        }
        return $this->fix_integer_overflow(filesize($file_path));
    }

    public function formatBytes($bytes, $force_unit = NULL, $format = NULL, $si = FALSE) {
        // Format string
        $format = ($format === NULL) ? '%01.2f %s' : (string) $format;

        // IEC prefixes (binary) - mostly used
        if ($si == FALSE || strpos($force_unit, 'i') !== FALSE)
        {
            $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
            $mod   = 1024;
        }
        // SI prefixes (decimal), in case we need it.
        else
        {
            $units = array('B', 'kB', 'mB', 'gB', 'tB', 'pB');
            $mod   = 1000;
        }

        // Determine unit to use
        if (($power = array_search((string) $force_unit, $units)) === FALSE)
        {
            $power = ($bytes > 0) ? floor(log($bytes - 1, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    }
  
	public function ffmpeg_convert_image($prefix, $image_url, $video_url_old, $same_dir = false, $premium = false)
	{
		global $CONF;

		// Stop if stuff is disabled
		if (!empty($CONF['disable_mp4']) || !function_exists('shell_exec')) {
			return null;
		}
		
		// Does mp4 or webm already exists? unlink them	
		if (!empty($video_url_old)) 
		{
			$video_path_info_old = pathinfo($video_url_old);

			if (isset($video_path_info_old['dirname']) && isset($video_path_info_old['filename'])) 
			{
				$video_path_old = str_replace($CONF['list_url'], $CONF['path'], $video_path_info_old['dirname']);
				$video_name_old = $video_path_info_old['filename'];
				
				if (file_exists("{$video_path_old}/{$video_name_old}.mp4")) {
					unlink("{$video_path_old}/{$video_name_old}.mp4");
				}
			}
		}
		
		$image_path_info = pathinfo($image_url);
		
		if (!isset($image_path_info['dirname']) || !isset($image_path_info['filename']) || !isset($image_path_info['extension'])) {
			return null;
		}
		
		$image_path       = str_replace($CONF['list_url'], $CONF['path'], $image_path_info['dirname']);
		$image_name       = $image_path_info['filename'];
		$image_extension  = strtolower($image_path_info['extension']);

		// Check if file is still on filesystem and a gif
		if ($image_extension !== 'gif' || !file_exists("{$image_path}/{$image_name}.{$image_extension}")) {
			return null;
		}
		
		// Image width, height is needed for video convert
		$image_size = getimagesize("{$image_path}/{$image_name}.{$image_extension}");	

		if (empty($image_size)) {
			return null;
		}
		
		// For video, we need a new name to bust cache
		$video_path = $image_path . ($same_dir === false ? '/mp4' : '');
		if ($prefix !== false) {
			$video_name = "{$prefix}_" . ($premium === true ? 'premium_' : '') . time();		
		}
		else {
			// e.g settings default banner
			$video_name = $image_name;		
		}
		
		// Force a file path search using "type".
		// "which" may be empty if you not modified $PATH 
		// Returns filepath for ffmpeg, just some safecheck since ffmpeg alone may not always work on every system
		$ffmpeg_path = trim(shell_exec('type -P ffmpeg'));

		if (empty($ffmpeg_path)) {
			return null;
		}
		
		/**
		 * Actual convert command
		 *
		 * =====================
		 * General options
		 * =====================
		 *	(-y)
		 *		- Disable confirm promts when overwriting files 
		 *
		 *	(-i "file path")
		 *		- The input file
		 *
		 * =====================
		 * .mp4 options
		 * =====================
		 *	(-codec:v libx264)
		 *		- The encoder required to encode the video
		 *
		 *	(-crf 23)
		 *		- Enables constant quality mode. Lowering -crf value = better quality but larger file. Default 23, 0-51
		 *
		 *	(-movflags faststart)
		 *		- Progressive downloading under html5 by moving the moov atom to the beginning of the file
		 *		- Results in beginning of playing before completely downloaded. 
		 *		- mp4 only, webm does not need this
		 *
		 *	(-pix_fmt yuv420p)
		 *		- Pixel format. YUV color space with 4:2:0 chroma subsampling, without alpha transparency 
		 *		- Maximizes playback compatibility and hardware acceleration
		 *
		 *	(-preset veryslow)
		 *		- Longer convert time, but better compression
		 *
		 *	(-s {$video_width}x{$video_height})
		 *		- yuv420p pixel format when used with libx264, requires dimensions which are divisable by 2
		 *		- Normally would use the video scale filter: -vf 'scale=trunc(iw/2)*2:trunc(ih/2)*2'
		 *		- But as soon as video filters ( -vf or -filter_complex ) are used, that results in ffmpeg dropping the last frame duration
		 *		- Since we know the dimensions we want, we can calculate that ourself and pass a size manually
		 *
		 * =====================
		 *	Mixed PHP variables
		 * =====================
		 * 	($dev_null)
		 *		- Redirect stdin/stdout/stderr into nothingness and make it a background process
		 * 		- (unix) </dev/null >/dev/null 2>&1 &
		 * 		- (windows) <NUL >NUL 2>&1 &
		 *
		 * 	($new_line)
		 *		- Tells FFmpeg to ignore the carriage return on multi line input and run it as a multi-line command
		 * 		- (unix) \
		 * 		- (windows) ^
		 */	
		$video_width  = floor((int)$image_size[0] / 2) * 2;
		$video_height = floor((int)$image_size[1] / 2) * 2;
		
		if (stripos(PHP_OS, 'WIN') === 0) {
			$dev_null = 'NUL';
			$new_line = '^';
		}
		else {
			$dev_null = '/dev/null';
			// \ character, escaped inside string for php
			$new_line = '\\';
		}		
		
	
		shell_exec("{$ffmpeg_path} -y -i \"{$image_path}/{$image_name}.{$image_extension}\" -codec:v libx264 -crf 23 -movflags faststart -pix_fmt yuv420p -preset veryslow -s {$video_width}x{$video_height} \"{$video_path}/{$video_name}.mp4\" <{$dev_null} >{$dev_null} 2>&1 &");
		
		return [
			'url' => str_replace($CONF['path'], $CONF['list_url'], $video_path) . "/{$video_name}.mp4",
		];
	}  
}



class in_out extends base {
  function record($username, $in_out) {
    global $CONF, $DB, $TMPL, $FORM, $LNG;

    if ($in_out != 'in' && $in_out != 'out') {
      return 0;
    }

    // Is this a unique hit?
    $ip = $DB->escape($_SERVER['REMOTE_ADDR'], 1);
    list($ip_sql, $unq) = $DB->fetch("SELECT ip_address, unq_{$in_out} FROM {$CONF['sql_prefix']}_ip_log WHERE ip_address = '$ip' AND username = '{$username}'", __FILE__, __LINE__);

    $update_ip_log = 0;
    $insert_ip_log = 0;

    if ($ip == $ip_sql && $unq == 0) {
      $is_unq = 1;
      $update_ip_log = 1;
    }
    elseif ($ip != $ip_sql) {
      $is_unq = 1;
      $insert_ip_log = 1;
    }
    else {
      $is_unq = 0;
    }

    // Following vars can be used to extend the log insert/update
    $log_update_extra   = '';
    $log_insert_columns = '';
    $log_insert_values  = '';

    /* Useful to extend validity checks if IP checking is not enough
    ** Modified example of callback plugin:
    **
        if($in_out == 'in')
        {
            // It is a unique hit by IP
            if ($is_unq)
            {
                // Could he have changed ip? Sure, let check against a unique identifier each voter passes which make use of callback plugin
                list($not_valid) = $DB->fetch("SELECT 1 FROM {$CONF['sql_prefix']}_ip_log WHERE username = '{$username}' AND column_name = 'value'", __FILE__, __LINE__);

                // Hey! Above exist, current vote is not valid!
                if ($not_valid)
                {
                    $is_unq = 0;
                }

                // This sets the value to update the log table, which is used in the above check
                if ($update_ip_log)
                {
                    $log_update_extra .= ", column_name = 'value'";
                }
                elseif ($insert_ip_log)
                {
                    $log_insert_columns .= ", column_name";
                    $log_insert_values .= ", 'value'";
                }
            }
        }
    */
    eval (PluginManager::getPluginManager ()->pluginHooks ('classes_record_in'));

    // Update stats / log
    $unique_sql = '';
    if ($is_unq)
    {
        $unique_sql = ", unq_{$in_out}_overall = unq_{$in_out}_overall + 1, unq_{$in_out}_0_daily = unq_{$in_out}_0_daily + 1, unq_{$in_out}_0_weekly = unq_{$in_out}_0_weekly + 1, unq_{$in_out}_0_monthly = unq_{$in_out}_0_monthly + 1";

        if ($update_ip_log)
        {
           $DB->query("UPDATE {$CONF['sql_prefix']}_ip_log SET unq_{$in_out} = 1 {$log_update_extra} WHERE ip_address = '{$ip}' AND username = '{$username}'", __FILE__, __LINE__);
        }
        elseif ($insert_ip_log)
        {
            $DB->query("INSERT INTO {$CONF['sql_prefix']}_ip_log (ip_address, username, unq_{$in_out} {$log_insert_columns}) VALUES ('{$ip}', '{$username}', 1 {$log_insert_values})", __FILE__, __LINE__);
        }
    }
    $DB->query("UPDATE {$CONF['sql_prefix']}_stats SET tot_{$in_out}_overall = tot_{$in_out}_overall + 1, tot_{$in_out}_0_daily = tot_{$in_out}_0_daily + 1, tot_{$in_out}_0_weekly = tot_{$in_out}_0_weekly + 1, tot_{$in_out}_0_monthly = tot_{$in_out}_0_monthly + 1{$unique_sql} WHERE username = '{$username}'", __FILE__, __LINE__);




    return 1;
  }
}

class join_edit extends base {
  function check_input($type) {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $error_username = 0;
    $error_username_duplicate = 0;
    $error_password = 0;
    $error_confirm_password = 0;
    $error_url = 0;
    $error_email = 0;
    $error_title = 0;
    $error_banner_url = 0;
    $error_captcha = 0;
    $error_recaptcha = 0;
    $error_security_question = 0;
    $error_premium_banner_url = 0;
    $error_url_duplicate = 0;

    /* Validate url
    ** If invalid, set error
    ** Actual error display handled by the validator
    */
    if (!validate_url('url', $TMPL['url'], 1)) {
        $error_url = 1;
    }
    $short_url = in::short_url($TMPL['url']);

    // Checks for join and join_existing
    if ($type == 'join') {
      if (mb_strlen($TMPL['username']) == 0 || preg_match('/[^a-zA-Z0-9\-_]+/', $TMPL['username'])) {
        $error_username = 1;
      }

      list($url_check) = $DB->fetch("SELECT 1 FROM {$CONF['sql_prefix']}_sites WHERE short_url LIKE '{$short_url}'", __FILE__, __LINE__);
      if ($url_check) {
        $error_url_duplicate = 1;
      }

      list($username_sql) = $DB->fetch("SELECT username FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
      if ($username_sql && strtolower($username_sql) == strtolower($TMPL['username'])) {
        $error_username_duplicate = 1;
      }
      if (empty($FORM['password']) || empty($FORM['confirm_password']) || $FORM['password'] != $FORM['confirm_password']) {
        $error_password = 1;
        $error_confirm_password = 1;
      }

      if ($CONF['recaptcha']) {

	    require_once("{$CONF['path']}/sources/recaptchalib.php");
        $recaptcha_resp = null;
        $recaptcha      = new ReCaptcha($CONF['recaptcha_secret']);

		$recaptcha_field = isset($FORM['g-recaptcha-response']) ? $FORM['g-recaptcha-response'] : '';
		$recaptcha_resp  = $recaptcha->verifyResponse($_SERVER["REMOTE_ADDR"], $recaptcha_field);

        if ($recaptcha_resp->success === false) {
          $error_recaptcha = 1;
        }
      }

      if ($CONF['captcha']) {
        $ip = $DB->escape($_SERVER['REMOTE_ADDR'], 1);
        list($sid) = $DB->fetch("SELECT sid FROM {$CONF['sql_prefix']}_sessions WHERE type = 'captcha' AND data LIKE '{$ip}|%'", __FILE__, __LINE__);
        require_once("{$CONF['path']}/sources/misc/session.php");
        $session = new session;
        list($type, $data) = $session->get($sid);
        list($ip, $hash) = explode('|', $data);
        if (!isset($FORM['captcha']) || $hash != sha1(')F*RJ@FHR^%X'.$FORM['captcha'].'(*Ht3h7f9&^F'.$ip)) {
          $error_captcha = 1;
        }
        $session->delete($sid);
      }

      if ($CONF['security_question'] && $CONF['security_answer']) {
      	$answer = explode(",", $CONF['security_answer']);
		if (!in_array("{$FORM['security_answer']}", $answer)) {
		$error_security_question = 1;
		}
      }

    }

    // Checks for edit
    if ($type == 'edit') {
      list($url_check) = $DB->fetch("SELECT 1 FROM {$CONF['sql_prefix']}_sites WHERE short_url LIKE '{$short_url}' AND username != '{$TMPL['username_url_check']}'", __FILE__, __LINE__);
      if ($url_check) {
        $error_url_duplicate = 1;
      }
    }

    // Checks for both join and edit
    if (!preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9._-]+)+$/', $TMPL['email'])) {
      $error_email = 1;
    }
    if (!trim($TMPL['title'])) {
      $error_title = 1;
    }
    if (!preg_match('/^https?:\/\/.+/', $TMPL['banner_url'])) {
      $TMPL['banner_url'] = $CONF['default_banner'];
    }
    elseif ($CONF['max_banner_width'] && $CONF['max_banner_height'] && ini_get('allow_url_fopen')) {
	  // Check Normal Banner
      $size = @getimagesize($FORM['banner_url']);
      if ($size[0] > $CONF['max_banner_width'] || $size[1] > $CONF['max_banner_height']) {
        $error_banner_url = 1;
      }
      if (!isset($size[0]) && !isset($size[1])) { $error_banner_url = 1; }
    }
    // If not set Premium Banner, set to null, so it doesnt copy normal banner over
    if (!isset($TMPL['premium_banner_url']) || !preg_match('/^https?:\/\/.+/', $TMPL['premium_banner_url'])) {
      $TMPL['premium_banner_url'] = 'NULL';
    }
    else {
        if($CONF['max_premium_banner_width'] && $CONF['max_premium_banner_height'] && ini_get('allow_url_fopen')) {
            // Retrieve Premium Flag status
            list($premium_flag) = $DB->fetch("SELECT premium_flag FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
            // Check Premium Banner
            if ($premium_flag == 1) {
                $size_premium = @getimagesize($FORM['premium_banner_url']);
                if ($size_premium[0] > $CONF['max_premium_banner_width'] || $size_premium[1] > $CONF['max_premium_banner_height']) {
                    $error_premium_banner_url = 1;
                }
                if (!isset($size_premium[0]) && !isset($size_premium[1])) { $error_premium_banner_url = 1; }
            }
        }
        // No error? Surround the var with quotes, as the update query has none due to NULL submit
        if(!$error_premium_banner_url) { $TMPL['premium_banner_url'] = "'".$TMPL['premium_banner_url']."'"; }
    }

    $good_cat = 0;
    foreach ($CONF['categories'] as $cat => $skin) {
      if (stripslashes($TMPL['category']) == $cat) {
        $good_cat = 1;
      }
    }
    if (!$good_cat) {
      $TMPL['category'] = $DB->escape($cat, 1);
    }


    // Validate plugin join fields
	$error_plugin_vars = '';
    eval (PluginManager::getPluginManager ()->pluginHooks ('classes_check_input'));


    if ($error_username || $error_username_duplicate || $error_password || $error_confirm_password || $error_url || $error_url_duplicate || $error_email || $error_title || $error_banner_url || $error_premium_banner_url || $error_captcha || $error_recaptcha || $error_security_question || $error_plugin_vars) {

      if ($error_username) {
        $TMPL['error_username'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_username']}</div>";
        $TMPL['error_style_username'] = 'join_edit_error is-invalid has-error';
      }
      if ($error_username_duplicate) {
        $TMPL['error_username'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_username_duplicate']}</div>";
        $TMPL['error_style_username'] = 'join_edit_error is-invalid has-error';
      }
      $TMPL['error_password'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_password']}</div>";
      $TMPL['error_style_password'] = 'join_edit_error is-invalid has-error';
      if ($error_confirm_password) {
        $TMPL['error_password'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_confirm_password']}</div>";
      }
      if ($error_url_duplicate) {
        $TMPL['error_url'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_url_duplicate']}</div>";
        $TMPL['error_style_url'] = 'join_edit_error is-invalid has-error';
      }
      if ($error_email) {
        $TMPL['error_email'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_email']}</div>";
        $TMPL['error_style_email'] = 'join_edit_error is-invalid has-error';
      }
      if ($error_title) {
        $TMPL['error_title'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_title']}</div>";
        $TMPL['error_style_title'] = 'join_edit_error is-invalid has-error';
      }
      if ($error_banner_url) {
        $TMPL['error_banner_url'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_urlbanner']}</div>";
        $TMPL['error_style_banner_url'] = 'join_edit_error is-invalid has-error';
      }
	  if ($error_premium_banner_url) {
        $TMPL['error_premium_banner_url'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_premium_urlbanner']}</div>";
        $TMPL['error_style_premium_banner_url'] = 'join_edit_error is-invalid has-error';
      }
      $TMPL['error_style_captcha'] = 'join_edit_error is-invalid has-error';
      if ($error_captcha) {
        $TMPL['error_captcha'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_captcha']}</div>";
      }
      $TMPL['error_style_recaptcha'] = 'join_edit_error is-invalid has-error';
      if ($error_recaptcha) {
        $TMPL['error_recaptcha'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_recaptcha']}</div>";
      }

      if ($error_security_question) {
        $TMPL['error_style_question'] = 'join_edit_error is-invalid has-error';
        $TMPL['error_question'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_error_question']}</div>";
      }


      // Give Plugin the error styling
      eval (PluginManager::getPluginManager ()->pluginHooks ('classes_check_input_style'));


      $TMPL['error_style_top'] = 'join_edit_error text-danger';
      $TMPL['error_top'] = $LNG['join_error_top'];
      return 0;
    }
    else {
      return 1;
    }
  }

  // This should be called before check_input
  function check_ban($type) {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $ban_url = 0;
    $ban_email = 0;
    $ban_username = 0;
    $ban_ip = 0;

   if ($type == 'join') { $fields = array('url', 'email', 'username', 'ip'); }
   elseif ($type == 'edit') { $fields = array('url', 'email'); }
   elseif ($type == 'review' || $type == 'vote') { $fields = array('ip'); }

    $TMPL['ip'] = $DB->escape($_SERVER['REMOTE_ADDR'], 1);

    $result = $DB->query("SELECT id, string, field, matching FROM {$CONF['sql_prefix']}_ban", __FILE__, __LINE__);
    while (list($id, $string, $field, $matching) = $DB->fetch_array($result)) {
      if (in_array($field, $fields)) {
        $string = preg_quote($string);

        if ($matching) { $s = "^{$string}$"; } // Exact matching
        else { $s = $string; } // Global matching

        if (preg_match("|{$s}|i", $TMPL[$field])) {
          ${"ban_{$field}"} = 1;
        }
      }
    }

    if ($ban_url || $ban_email || $ban_username || $ban_ip)
    {
      $TMPL['error_style_top'] = 'join_edit_error';

      if ($ban_ip) {
        $TMPL['error_top'] = "<div class=\"alert alert-danger\">{$LNG['join_ban_top']} {$TMPL['ip']}</div>";
      }
      else {

        $TMPL['error_top'] = "<div class=\"alert alert-danger\">{$LNG['join_error_top']}</div>";

        if ($ban_username) {
            $TMPL['error_username'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_ban_top']}</div>";
            $TMPL['error_style_username'] = 'join_edit_error is-invalid has-error';
        }
        if ($ban_email) {
            $TMPL['error_email'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_ban_top']}</div>";
            $TMPL['error_style_email'] = 'join_edit_error is-invalid has-error';
        }
        if ($ban_url) {
            $TMPL['error_url'] = "<div class=\"invalid-feedback text-danger\">{$LNG['join_ban_top']}</div>";
            $TMPL['error_style_url'] = 'join_edit_error is-invalid has-error';
        }
      }

      return 0;
    }
    else {
      return 1;
    }
  }

}

class timer {
  public $start_time;

  public function __construct () {
    $this->start_time = array_sum(explode(' ', microtime()));
  }

  function get_time () {
    $current_time = array_sum(explode(' ', microtime()));
    return round($current_time - $this->start_time, 5);
  }
}
