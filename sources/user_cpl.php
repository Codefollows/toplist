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

class user_cpl extends base {

  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['user_cp_header'];

	$TMPL['user_cp_content'] = '';
	$TMPL['subtext'] = '';
	$TMPL['join_premium_banner_size'] = '';
	$TMPL['enable_input_banner_premium'] = '';

    if (!isset($_COOKIE['atsphp_sid_user_cp'])) {
	  $this->login();
    }
    else {
      require_once("{$CONF['path']}/sources/misc/session.php");
      $session = new session;
      list($type, $data) = $session->get($_COOKIE['atsphp_sid_user_cp']);

      $TMPL['username'] = $DB->escape($data);

      if ($type == 'user_cp') {
        $session->update($_COOKIE['atsphp_sid_user_cp']);


        $SITE = array();
        $siteinfo = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
        $SITE = array_merge($SITE, $siteinfo);
		$TMPL['user_cp_links'] = '';

        // Plugin hook - Add extra control panel links to menu
        eval (PluginManager::getPluginManager ()->pluginHooks ('user_cp_global_start'));


        // Array containing the valid .php files from the sources/user_cp directory
        $action = array(
			'edit' => 1,
			'link_code' => 1,
			'banner' => 1,
			'join_existing' => 1,
			'user_premium' => 1,
        	'payment_history' => 1
        );

        // Plugin Hook, to extend the action array
        eval (PluginManager::getPluginManager ()->pluginHooks ('user_cp_action_array'));


        //Payment options
        $TMPL['hide_2co'] = '';
        $TMPL['hide_paypal'] = '';
        if(mb_strlen($CONF['acct2co']) < 5) {
           $TMPL['hide_2co'] = ' style="display: none;"';
        }
        if(mb_strlen($CONF['email_pay']) < 5) {
           $TMPL['hide_paypal'] = ' style="display: none;"';
        }

        //1000 MAX
        $profile_points = '';
        $max_points = '';
        //Points For Description
        if(mb_strlen($SITE['description']) > 150 ) {$profile_points = 100;} else {$profile_points = 50;}
        $max_points = '100';
        //give 100 points for premium
        if($SITE['premium_flag'] == 1){$profile_points = $profile_points + 100;}
        $max_points = $max_points + 100;
        //give 100 points for unique banner
        if("{$SITE['banner_url']}" != "{$CONF['default_banner']}") {$profile_points = $profile_points + 100;}
        $max_points = $max_points + 100;

        eval (PluginManager::getPluginManager ()->pluginHooks ('user_cp_profile_points'));

        $TMPL['user_cp_score'] = round($profile_points / $max_points * 100);


        if (isset($FORM['b']) && is_string($FORM['b']) && isset($action[$FORM['b']])) {
          $page_name = $FORM['b'];

          $sources = 'sources/user_cp';
          // Plugin Hook - Include new user_cp source file
          eval(PluginManager::getPluginManager()->pluginHooks('user_cp_include_source'));

          require_once("{$CONF['path']}/{$sources}/{$page_name}.php");
          $page = new $page_name;
          $TMPL['content'] = $this->do_skin('user_cp');
        }
        elseif (isset($FORM['b']) && $FORM['b'] == 'logout') {
          $this->logout();
        }
        else {
          $this->main();
        }
      }
      else {
        $this->login();
      }
    }
  }

  function login() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

	$TMPL['timestamp'] = time();
	$timestamp_cutoff  = $TMPL['timestamp'] - 3600;
	$TMPL['ip']        = $_SERVER['REMOTE_ADDR'];


	//Delete Old timestamps
	$DB->query("DELETE FROM {$CONF['sql_prefix']}_ip_log WHERE timestamp < {$timestamp_cutoff}", __FILE__, __LINE__);

	//count instances of this IP in IP_log for BruteForce
	$ipcount = 0;
	$result = $DB->query("SELECT ip_address FROM {$CONF['sql_prefix']}_ip_log WHERE ip_address = '{$TMPL['ip']}' AND timestamp > {$timestamp_cutoff}", __FILE__, __LINE__);
	while ($row = $DB->fetch_array($result)) {
		$ipcount++;
	}

	if($ipcount > 10) {
		$this->error($LNG['g_invalid_u_or_p_bfd']);
	}
	else {

		// Show login form only if no 2step cookie exist ( used to keep 2step visible until validated )
		// And no sid in url ( used by email validation link )
		if (!isset($_COOKIE['atsphp_sid_2step']) && !isset($FORM['sid']) && (!isset($FORM['u']) || !isset($FORM['password']) || !$FORM['u'] || !$FORM['password'])) {

			if ($CONF['clean_url'] == 1 && !isset($FORM['b']) && preg_match('/\?/', $_SERVER['REQUEST_URI']))
			{
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: {$CONF['list_url']}/user_cpl/");
				exit;
			}

			// Canonical header login page
			$canonical_page = str_replace('&amp;', '&', "{$TMPL['list_url']}/{$TMPL['url_helper_a']}user_cpl{$TMPL['url_tail']}");
			header("Link: <{$canonical_page}>; rel=\"canonical\"");

			if ($CONF['recaptcha'] && $CONF['usercp_recaptcha']) {
				$TMPL['recaptcha_sitekey'] = $CONF['recaptcha_sitekey'];
				$TMPL['join_recaptcha'] = $this->do_skin('join_recaptcha');
			}
			else {
				$TMPL['join_recaptcha'] = '';
			}
  
			// Plugin Hook - user login form
			eval(PluginManager::getPluginManager()->pluginHooks('user_cp_login_form'));

			$TMPL['content'] = $this->do_skin('user_cp_login');
		}
		else {

			require_once("{$CONF['path']}/sources/misc/session.php");
			$session = new session;

			$password_sql = '';

			// Remember me, session check here to avoid reset of 2step
			$_SESSION['user_cp_keep_alive'] = !empty($_SESSION['user_cp_keep_alive']) ? 1 : 0;

			if (isset($FORM['sid'])) {

				// 2step verification email link, getting username from db session only
				list($type, $data) = $session->get($FORM['sid']);

				if ($type === '2step_email') {
					$TMPL['username'] = $DB->escape($data);
				}
				else {

					// Store brute force, if a user tries to bruteforce the sid url param
					$this->brute_force($TMPL['ip'], $TMPL['timestamp']);

					$this->error($LNG['g_session_expired']);
				}
			}
			elseif (isset($_COOKIE['atsphp_sid_2step'])) {

				// 2step verification, getting username from session
				// This step is just here so the default user/pass part isn't triggered
				list($type, $data) = $session->get($_COOKIE['atsphp_sid_2step']);

				if ($type === '2step') {
					$TMPL['username'] = $DB->escape($data);
				}
				else {

					// Store brute force
					$this->brute_force($TMPL['ip'], $TMPL['timestamp']);

					// Delete db session + cookie
					$session->delete($_COOKIE['atsphp_sid_2step'], 'atsphp_sid_2step');
					$this->error($LNG['g_session_expired']);
				}
			}
			else {

				// Getting username, pass from login form
				$TMPL['username'] = $DB->escape($FORM['u']);
				$password         = md5($FORM['password']);
				$password_sql     = "AND password = '{$password}'";

				if ($CONF['recaptcha']  && $CONF['usercp_recaptcha']) 
				{
					require_once("{$CONF['path']}/sources/recaptchalib.php");
					$recaptcha_resp = null;
					$recaptcha      = new ReCaptcha($CONF['recaptcha_secret']);
					$recaptcha_field = isset($FORM['g-recaptcha-response']) ? $FORM['g-recaptcha-response'] : '';
					$recaptcha_resp  = $recaptcha->verifyResponse($_SERVER["REMOTE_ADDR"], $recaptcha_field);
					
					if ($recaptcha_resp->success === false) {
						$this->error($LNG['join_error_recaptcha']);
					}
				}

				// Remember me
				$_SESSION['user_cp_keep_alive'] = !empty($FORM['keep_alive']) ? 1 : 0;
			}


			// Gets users data either from login form or 2step session data
			// Not really needed for the 2step, but this way we avoid some if/else
			list($username, $email, $active, $_2step, $_2step_secret) = $DB->fetch("SELECT username, email, active, 2step, 2step_secret FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}' {$password_sql}", __FILE__, __LINE__);


			if (strtolower($TMPL['username']) == strtolower($username)) {

				if ($active == 1 || $active == 3) {

					$login = true;

					// If user has 2step enabled
					if (!empty($_2step)) {

						$login = false;

						if (!isset($_COOKIE['atsphp_sid_2step'])) {

							// No cookie yet, means came from user, pass login form

							// 2step cookie to difference between the normal and 2step user validation
							$session->create('2step', $username);

							if ($_2step == 1) {

								// email session, no cookie
								$TMPL['sid'] = $session->create('2step_email', $username, 0);

								$_2step_email = new skin('user_cp_login_email');
								$_2step_email->send_email($email);
							}
						}
						elseif (isset($FORM['sid'])) {

							// Email confirm url
							// Proceed to user panel, sid was validated before by 2step
							$login = true;

							$session->delete($FORM['sid'], 'atsphp_sid_2step_email');
						}

						if ($login === false) {

							if ($_2step == 1) {
								$TMPL['content'] = $this->do_skin('user_cp_login_2step_email');
							}
							elseif ($_2step == 2) {

								if (isset($FORM['2step_validate'])) {

									require_once("{$CONF['path']}/sources/misc/GoogleAuthenticator.php");
									$ga = new PHPGangsta_GoogleAuthenticator();

									$TMPL['2step_validate'] = $FORM['2step_validate'];
									$_2step_verified        = $ga->verifyCode($_2step_secret, $TMPL['2step_validate'], 2);

									if (empty($_2step_verified)) {

										// Store brute force invalid codes
										$this->brute_force($TMPL['ip'], $TMPL['timestamp']);

										if(mb_strlen($TMPL['2step_validate']) == 0) {
											error_display('2step_validate', $LNG['validate_required']);
										}
										else {
											error_display('2step_validate', $LNG['2step_google_invalid']);
										}
									}
									else {
										$login = true;
									}
								}

								if (empty($_2step_verified)) {

									$TMPL['2step_validate']      = isset($TMPL['2step_validate']) ? htmlspecialchars(stripslashes($TMPL['2step_validate']), ENT_QUOTES, "UTF-8") : '';
									$TMPL['form_2step_validate'] = generate_input('2step_validate', $LNG['2step_google_label'], 50, 0);

									$TMPL['content'] = $this->do_skin('user_cp_login_2step_google');
								}
							}
						}
					}


					if ($login === true) {

						// Delete possible 2step cookies
						if (isset($_COOKIE['atsphp_sid_2step'])) {
							$session->delete($_COOKIE['atsphp_sid_2step'], 'atsphp_sid_2step');
						}

						$session->create('user_cp', $username, 1, $_SESSION['user_cp_keep_alive']);
						header("refresh:0; '{$TMPL['list_url']}/{$TMPL['url_helper_a']}user_cpl{$TMPL['url_tail']}'");
						exit;
					}
				}
				else {
					$this->error($LNG['user_cp_inactive']);
				}
			}
			else {

				$this->brute_force($TMPL['ip'], $TMPL['timestamp']);
				$this->error($LNG['g_invalid_u_or_p']);
			}
		}
	}
  }

  function brute_force($ip, $timestamp) {
    global $CONF, $DB;

	$DB->query("INSERT INTO {$CONF['sql_prefix']}_ip_log (ip_address, timestamp) VALUES ('{$ip}', '{$timestamp}')", __FILE__, __LINE__);
  }

  function logout() {
    global $CONF, $LNG, $TMPL;

    require_once("{$CONF['path']}/sources/misc/session.php");
    $session = new session;
    $session->delete($_COOKIE['atsphp_sid_user_cp'], 'atsphp_sid_user_cp');
    header("refresh:1; '{$TMPL['list_url']}/'");
    $TMPL['content'] = $LNG['user_cp_logout_message'];
  }

  function main() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['user_cp_content'] = $LNG['user_cp_welcome'];

    //GET OWNER AND SITE LIST
	$result = $DB->query("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
	while (list($myowner) = $DB->fetch_array($result)) {
	    $TMPL['myowner'] = $myowner;
	}

    $stats = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_stats WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
    unset($stats['username']);
    $sites = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
    if ($stats) {

		$sites = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $sites);
		$stats = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $stats);

		$TMPL = array_merge($TMPL, $stats, $sites);

		$TMPL['unq_pv_max_daily'] = $TMPL['unq_pv_0_daily'] > $TMPL['unq_pv_max_daily'] ? $TMPL['unq_pv_0_daily'] : $TMPL['unq_pv_max_daily'];
		$TMPL['tot_pv_max_daily'] = $TMPL['tot_pv_0_daily'] > $TMPL['tot_pv_max_daily'] ? $TMPL['tot_pv_0_daily'] : $TMPL['tot_pv_max_daily'];
		$TMPL['unq_in_max_daily'] = $TMPL['unq_in_0_daily'] > $TMPL['unq_in_max_daily'] ? $TMPL['unq_in_0_daily'] : $TMPL['unq_in_max_daily'];
		$TMPL['tot_in_max_daily'] = $TMPL['tot_in_0_daily'] > $TMPL['tot_in_max_daily'] ? $TMPL['tot_in_0_daily'] : $TMPL['tot_in_max_daily'];
		$TMPL['unq_out_max_daily'] = $TMPL['unq_out_0_daily'] > $TMPL['unq_out_max_daily'] ? $TMPL['unq_out_0_daily'] : $TMPL['unq_out_max_daily'];
		$TMPL['tot_out_max_daily'] = $TMPL['tot_out_0_daily'] > $TMPL['tot_out_max_daily'] ? $TMPL['tot_out_0_daily'] : $TMPL['tot_out_max_daily'];
		$TMPL['unq_pv_max_weekly'] = $TMPL['unq_pv_0_weekly'] > $TMPL['unq_pv_max_weekly'] ? $TMPL['unq_pv_0_weekly'] : $TMPL['unq_pv_max_weekly'];
		$TMPL['tot_pv_max_weekly'] = $TMPL['tot_pv_0_weekly'] > $TMPL['tot_pv_max_weekly'] ? $TMPL['tot_pv_0_weekly'] : $TMPL['tot_pv_max_weekly'];
		$TMPL['unq_in_max_weekly'] = $TMPL['unq_in_0_weekly'] > $TMPL['unq_in_max_weekly'] ? $TMPL['unq_in_0_weekly'] : $TMPL['unq_in_max_weekly'];
		$TMPL['tot_in_max_weekly'] = $TMPL['tot_in_0_weekly'] > $TMPL['tot_in_max_weekly'] ? $TMPL['tot_in_0_weekly'] : $TMPL['tot_in_max_weekly'];
		$TMPL['unq_out_max_weekly'] = $TMPL['unq_out_0_weekly'] > $TMPL['unq_out_max_weekly'] ? $TMPL['unq_out_0_weekly'] : $TMPL['unq_out_max_weekly'];
		$TMPL['tot_out_max_weekly'] = $TMPL['tot_out_0_weekly'] > $TMPL['tot_out_max_weekly'] ? $TMPL['tot_out_0_weekly'] : $TMPL['tot_out_max_weekly'];
		$TMPL['unq_pv_max_monthly'] = $TMPL['unq_pv_0_monthly'] > $TMPL['unq_pv_max_monthly'] ? $TMPL['unq_pv_0_monthly'] : $TMPL['unq_pv_max_monthly'];
		$TMPL['tot_pv_max_monthly'] = $TMPL['tot_pv_0_monthly'] > $TMPL['tot_pv_max_monthly'] ? $TMPL['tot_pv_0_monthly'] : $TMPL['tot_pv_max_monthly'];
		$TMPL['unq_in_max_monthly'] = $TMPL['unq_in_0_monthly'] > $TMPL['unq_in_max_monthly'] ? $TMPL['unq_in_0_monthly'] : $TMPL['unq_in_max_monthly'];
		$TMPL['tot_in_max_monthly'] = $TMPL['tot_in_0_monthly'] > $TMPL['tot_in_max_monthly'] ? $TMPL['tot_in_0_monthly'] : $TMPL['tot_in_max_monthly'];
		$TMPL['unq_out_max_monthly'] = $TMPL['unq_out_0_monthly'] > $TMPL['unq_out_max_monthly'] ? $TMPL['unq_out_0_monthly'] : $TMPL['unq_out_max_monthly'];
		$TMPL['tot_out_max_monthly'] = $TMPL['tot_out_0_monthly'] > $TMPL['tot_out_max_monthly'] ? $TMPL['tot_out_0_monthly'] : $TMPL['tot_out_max_monthly'];

		$TMPL['average_rating'] = $TMPL['num_ratings'] > 0 ? round($TMPL['total_rating'] / $TMPL['num_ratings'], 0) : 0;

		// Number format only valid stats
		foreach ($stats as $key => $value)
		{
			if (strpos($key, 'unq_') === 0 || strpos($key, 'tot_') === 0)
			{
				$TMPL[$key] = number_format($TMPL[$key]);
			}
		}

		if ($TMPL['premium_flag'] == 1 && $TMPL['remain_day'] >= 0) {
			$TMPL['remain_day_premium'] = "{$TMPL['remain_day']} {$LNG['a_s_days']}";
		}
		elseif ($TMPL['premium_request'] == 1) {
			$TMPL['remain_day_premium'] = $LNG['user_cp_premium_pending_msg'];
		}
		else {
			$TMPL['remain_day_premium'] = "0 {$LNG['a_s_days']} - <a href=\"{$TMPL['list_url']}/index.php?a=user_cpl&amp;b=user_premium\">{$LNG['user_cp_premium_buy_menu']}</a>";
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
			$TMPL['banner'] = $this->do_skin('banner_mp4');
		}
		else
		{
			$TMPL['banner'] = $this->do_skin('banner');
		}
    }


    // Plugin Hook, user cp main page
    eval (PluginManager::getPluginManager ()->pluginHooks ('user_cp_main'));

    $TMPL['user_cp_content'] .= $this->do_skin('user_cp_start');
    $TMPL['content'] = $this->do_skin('user_cp');
  }

}
