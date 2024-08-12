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

class in extends in_out {
  public function __construct() {
    global $CONF, $DB, $LNG, $FORM, $TMPL;

    if (isset($_SERVER['HTTP_REFERER'])) {
      $referer = $DB->escape($_SERVER['HTTP_REFERER'], 1);
    }

    $go_to_rankings = 0;
    if (isset($FORM['a']) && $FORM['a'] == 'in' && isset($FORM['u'])) {
      $go_to_rankings = 1;
      $username = $DB->escape($FORM['u']);
    }
    else {
      // Get user by referer?
      $good_referer = 0;
      if (isset($referer) && !isset($FORM['a']) && mb_strpos($referer, $CONF['list_url']) === FALSE) {
        // Make sure it's not a search engine
        if (mb_strpos($referer, 'google.com') === FALSE && mb_strpos($referer, 'yahoo.com') === FALSE && mb_strpos($referer, 'bing.com') === FALSE) {
          $good_referer = 1;
        }
      }

      if ($good_referer) {
        $username = $this->get_username($referer);
      }
      else {
        $username = '';
      }

    }

    if ($username) {

      list($username_sql, $username_active) = $DB->fetch("SELECT username, active FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);
      if ($username_sql) {
        if ($CONF['gateway'] && !isset($FORM['sid'])) {
          $this->gateway($username);
        }
        else {
          $not_blacklisted = new join_edit;

          if ($CONF['gateway']) {
            $valid = $this->check($username);
          }
          else {
            $valid = 1;
          }


          if ($CONF['recaptcha'] && $CONF['gateway_recaptcha']) 
		  {
			require_once("{$CONF['path']}/sources/recaptchalib.php");
			$recaptcha_resp = null;
			$recaptcha      = new ReCaptcha($CONF['recaptcha_secret']);

			$recaptcha_field = isset($FORM['g-recaptcha-response']) ? $FORM['g-recaptcha-response'] : '';
			$recaptcha_resp  = $recaptcha->verifyResponse($_SERVER["REMOTE_ADDR"], $recaptcha_field);

			if ($recaptcha_resp->success === false) {
				$TMPL['error_recaptcha'] = '<br>'. $LNG['join_error_recaptcha'];
				$this->gateway($username);
			}
		  }


          // Plugin hook - Might be used for captcha validation
          eval (PluginManager::getPluginManager ()->pluginHooks ('in_before_valid'));


          if ($valid && $not_blacklisted->check_ban('vote')) {
            // Site made inactive due inactivity? Make it active again
            if ($username_active == 3) {
                $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET active = 1 WHERE username = '{$username}'", __FILE__, __LINE__);
            }
            $this->record($username, 'in');

            eval (PluginManager::getPluginManager ()->pluginHooks ('in_valid'));
          }
        }
      }
    }

    if ($go_to_rankings) {
      $vote_url = "{$CONF['list_url']}/";

      // Plugin hook, redirect after voting. simply call $vote_url via a plugin
      eval (PluginManager::getPluginManager ()->pluginHooks ('in_redirect'));

      header("HTTP/1.1 301 Moved Permanently");
      header("Location: {$vote_url}");
      exit;
    }
  }


  static public function check($username) {
    global $CONF, $FORM;

    require_once("{$CONF['path']}/sources/misc/session.php");
    $session = new session;
    list($type, $data) = $session->get($FORM['sid']);
    $session->delete($FORM['sid']);

    if ($type == 'gateway' && $data == $username) {
      return 1;
    }
    else {
      return 0;
    }
  }

  static public function gateway($username) {
    global $DB, $LNG, $CONF, $FORM, $TMPL;

    if (isset($FORM['a']) && $FORM['a'] == 'in') {
        header('X-Robots-Tag: noindex');
    }
    
    $gateway_template = 'gateway';

    eval (PluginManager::getPluginManager ()->pluginHooks ('in_gateway_start'));

    require_once("{$CONF['path']}/sources/misc/session.php");
    $session = new session;
    $TMPL['sid'] = $session->create('gateway', $username);

    $TMPL['username'] = $username;
    $TMPL['gateway_top'] = '';
    $TMPL['gateway_bottom'] = '';

    $result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);
    while ($row = $DB->fetch_array($result)) {

		$row = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $row);

        $TMPL = array_merge($TMPL, $row);
    }

	// Prepare Category Url
	$category_raw = htmlspecialchars_decode($TMPL['category'], ENT_QUOTES);
	$TMPL['category_url'] = isset($CONF['categories'][$category_raw]) ? urlencode($CONF['categories'][$category_raw]['cat_slug']) : '';

	// Banner image/mp4 management - Order 1: Premium overwrite normal values
	if ($TMPL['premium_flag'] == 1)
	{
		if(!empty($TMPL['premium_banner_url']))
		{
			$TMPL['banner_url']    = $TMPL['premium_banner_url'];
			$TMPL['mp4_url']       = $TMPL['premium_mp4_url'];
			$TMPL['banner_width']  = $TMPL['premium_banner_width'];
			$TMPL['banner_height'] = $TMPL['premium_banner_height'];
		}

		if(empty($CONF['disable_mp4']) && !empty($TMPL['premium_mp4_url']))
		{
			$TMPL['mp4_url'] = $TMPL['premium_mp4_url'];
		}
	}

	// Banner image/mp4 management - Order 2: If banner equals default, use button_config values
	if ($TMPL['banner_url'] == $CONF['default_banner'])
	{
		if(empty($CONF['disable_mp4']) && !empty($TMPL['mp4_url']))
		{
			$TMPL['mp4_url'] = $CONF['default_banner_mp4'];
		}

		$TMPL['banner_width']  = $CONF['default_banner_width'];
		$TMPL['banner_height'] = $CONF['default_banner_height'];
	}

	// Banner image/mp4 management - Order 3: width/height 
	// Only include these if width/height > 0 ( to avoid hidden banners/videos if getimagesize() failed when saving images )
	$TMPL['banner_aspect_base']  = '';
	$TMPL['banner_aspect_ratio'] = '';
	$TMPL['banner_width_height'] = '';
	if ($TMPL['banner_width'] > 0 && $TMPL['banner_height'] > 0)
	{
		$TMPL['banner_aspect_base']  = "--site-image-aspect-base: {$TMPL['banner_width']}px;";
		$TMPL['banner_aspect_ratio'] = "--site-image-aspect-ratio: {$TMPL['banner_width']}/{$TMPL['banner_height']};";
		$TMPL['banner_width_height'] = 'width="'.$TMPL['banner_width'].'" height="'.$TMPL['banner_height'].'"';
	}

	// Banner image/mp4 management - Order 4: Layout switcher
	if(empty($CONF['disable_mp4']) && !empty($TMPL['mp4_url']))
	{
		$TMPL['banner'] = base::do_skin('banner_mp4');
	}
	else
	{
		$TMPL['banner'] = base::do_skin('banner');
	}
	
	
	if ($CONF['recaptcha'] && $CONF['gateway_recaptcha']) {
		$TMPL['recaptcha_sitekey'] = $CONF['recaptcha_sitekey'];
		$TMPL['join_recaptcha'] = base::do_skin('join_recaptcha');
	}
	else {
		$TMPL['join_recaptcha'] = '';
	}
	  

    eval (PluginManager::getPluginManager ()->pluginHooks ('in_gateway'));

    // Maintenance mode
    if ($CONF['maintenance_mode'])
    {
        if (isset($_COOKIE['atsphp_sid_admin']))
        {
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

                $gateway_template = 'wrapper_maintenance';
            }
        }
        else {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 3600'); // 1 hour

            $gateway_template = 'wrapper_maintenance';
        }
    }

    echo base::do_skin($gateway_template);
    exit;
  }

  static public function get_username($url) {
    global $CONF, $DB;

    $url = in::short_url($url);
    $count = 0;

    $username = '';
    while (!$username) {
      list($username) = $DB->fetch("SELECT username FROM {$CONF['sql_prefix']}_sites WHERE short_url = '{$url}'", __FILE__, __LINE__);

      if (!$username) {
        $url = in::short_url("{$url}.");
      }

      $count++;
      if ($count >= 10) {
        $username = 0;
        break;
      }
    }

    return $username;
  }

  static public function short_url($url) {
    // Lowercase
    $url = mb_strtolower($url);

    // Get rid of www.
    $url = preg_replace('/\/\/www./', '//', $url);

    // Get rid of trailing slash
    $url = preg_replace('/\/$/', '', $url);

    // Get rid of page after the last slash
    preg_match('/^(https?:\/\/.+)\/(.+)/', $url, $matches);
    if (!isset($matches[0])) {
      // Just a domain with a slash at the end
      $url = preg_replace('/^(https?:\/\/.+)\//', '\\1', $url);
    }
    else {
      // All other URLs
      // Check to see if after the trailing slash is a file or a directory
      if (mb_strpos($matches[2], '.')) { $url = $matches[1]; }
    }

    return $url;
  }
}
