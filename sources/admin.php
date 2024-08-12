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

class admin extends base {

  public function __construct() {
    global $CONF, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_header'];

	$TMPL['admin_settings_menu'] = '';
	$TMPL['admin_members_menu'] = '';
	$TMPL['admin_tools_menu'] = '';
	$TMPL['admin_reviews_menu'] = '';
	$TMPL['admin_pages_menu'] = '';
	$TMPL['sweetscore'] = '';
	$TMPL['jplot'] = '';
	$TMPL['slider_perpage'] = '';
	$TMPL['jquery_new_input'] = '';


	eval (PluginManager::getPluginManager ()->pluginHooks ('admin_global_start'));


    if (!isset($_COOKIE['atsphp_sid_admin'])) {
      $this->login();
    }
    else {
      require_once("{$CONF['path']}/sources/misc/session.php");
      $session = new session;
      list($type, $data) = $session->get($_COOKIE['atsphp_sid_admin']);

      if ($type == 'admin') {
        $session->update($_COOKIE['atsphp_sid_admin']);

        // Array containing the valid .php files from the sources/admin directory
        $action = array(
			'add_join_field' => 1,
			'approve' => 1,
			'approve_edited' => 1,
			'approve_premium' => 1,
			'approve_reviews' => 1,
			'backup_database' => 1,
			'create_page' => 1,
			'delete' => 1,
			'delete_bad_word' => 1,
			'delete_ban' => 1,
			'delete_page' => 1,
			'delete_language' => 1,
			'delete_review' => 1,
			'edit' => 1,
			'edit_page' => 1,
			'edit_language' => 1,
			'edit_bad_word' => 1,
			'edit_ban' => 1,
			'edit_join_field' => 1,
			'edit_review' => 1,
			'email' => 1,
			'manage' => 1,
			'search_user' => 1,
			'inactive' => 1,
			'members' => 1,
			'manage_bad_words' => 1,
			'manage_ban' => 1,
			'manage_pages' => 1,
			'manage_languages' => 1,
			'manage_reviews' => 1,
			'manage_menus' => 1,
			'manage_join_fields' => 1,
			'settings' => 1,
			'skins' => 1,
			'manage_skins' => 1,
			'plugins' => 1,
			'manage_premium' => 1,
			'edit_premium_conf' => 1,
			'manage_banners' => 1,
			'add_banner' => 1,
			'ffmpeg_convert' => 1,
		);


        eval (PluginManager::getPluginManager ()->pluginHooks ('admin_action_array'));


        if (isset($FORM['b']) && is_string($FORM['b']) && isset($action[$FORM['b']])) {
          $page_name = $FORM['b'];

          $sources = 'sources/admin';
          // Plugin Hook - Include new admin source file
          eval(PluginManager::getPluginManager()->pluginHooks('admin_include_source'));

          require_once("{$CONF['path']}/{$sources}/{$page_name}.php");
          $page = new $page_name;
          $TMPL['content'] = $this->do_skin('admin');
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

		// Error handled by /admin/
		header("Location: {$TMPL['list_url']}/admin/?bf=1");
		exit;
	}
	else {

		// Show password form only if no 2step cookie exist ( used to keep 2step visible until validated )
		// And no sid in url ( used by email validation link )
		if (!isset($_COOKIE['atsphp_sid_admin_2step']) && !isset($FORM['sid']) && !isset($FORM['password'])) {

			// Form display handled by /admin/
			header("Location: {$TMPL['list_url']}/admin/");
			exit;
		}
		else {

			require_once("{$CONF['path']}/sources/misc/session.php");
			$session = new session;

			// Pre checks
			if (isset($FORM['sid'])) {

				// 2step verification email link, validate from db session only
				list($type, $data) = $session->get($FORM['sid']);

				if ($type === 'admin_2step_email') {
					// Do nothing
				}
				else {

					// Store brute force, if a user tries to bruteforce the sid url param
					$this->brute_force($TMPL['ip'], $TMPL['timestamp']);

					// Error handled by /admin/
					header("Location: {$TMPL['list_url']}/admin/?fail=1");
					exit;
				}
			}
			elseif (isset($FORM['password'])) {

				// Getting pass from login form
				$password = md5($FORM['password']);
				
				if ($CONF['recaptcha'] && $CONF['admin_recaptcha']) 
				{
					require_once("{$CONF['path']}/sources/recaptchalib.php");
					$recaptcha_resp  = null;
					$recaptcha       = new ReCaptcha($CONF['recaptcha_secret']);
					$recaptcha_field = isset($FORM['g-recaptcha-response']) ? $FORM['g-recaptcha-response'] : '';
					$recaptcha_resp  = $recaptcha->verifyResponse($_SERVER["REMOTE_ADDR"], $recaptcha_field);
					
					if ($recaptcha_resp->success === false) 
					{
						// Error handled by /admin/
						header("Location: {$TMPL['list_url']}/admin/?recaptcha=1");
						exit;
					}
				}
			}


			list($admin_password) = $DB->fetch("SELECT admin_password FROM {$CONF['sql_prefix']}_etc", __FILE__, __LINE__);


			if (isset($FORM['sid']) || isset($FORM['2step_validate']) || !isset($FORM['sid']) && isset($password) && $admin_password == $password) {

				$login = true;
			
				// If admin has 2step enabled
				if (!empty($CONF['2step'])) {

					$login = false;

					if (!isset($_COOKIE['atsphp_sid_admin_2step'])) {

						// No cookie yet, means came from pass login form

						// 2step cookie to difference between the normal and 2step user validation
						$session->create('admin_2step', 'admin');

						if ($CONF['2step'] > 0) {

							if ($CONF['2step'] == 1) {

								// email session, no cookie
								$TMPL['sid'] = $session->create('admin_2step_email', 'admin', 0);

								$_2step_email = new skin('admin_login_email');
								$_2step_email->send_email($CONF['your_email']);
							}

							// Message (email) / form (google) display handled by /admin/
							header("Location: {$TMPL['list_url']}/admin/");
							exit;
						}

					}
					elseif (isset($FORM['sid'])) {

						// Email confirm url
						// Proceed to admin panel, sid was validated before already
						$login = true;

						$session->delete($FORM['sid'], 'atsphp_sid_admin_2step_email');
					}

					if ($login === false) {

						if ($CONF['2step'] == 2 && isset($FORM['2step_validate'])) {

							require_once("{$CONF['path']}/sources/misc/GoogleAuthenticator.php");
							$ga = new PHPGangsta_GoogleAuthenticator();

							$_2step_validate = $FORM['2step_validate'];
							$_2step_verified = $ga->verifyCode($CONF['2step_secret'], $_2step_validate, 2);

							if (empty($_2step_verified)) {

								// Store brute force invalid codes
								$this->brute_force($TMPL['ip'], $TMPL['timestamp']);

								// Error handled by /admin/
								header("Location: {$TMPL['list_url']}/admin/?fail=1");
								exit;
							}
							else {
								$login = true;
							}
						}
					}
				}

				if ($login === true) {

					// Delete possible 2step cookies
					if (isset($_COOKIE['atsphp_sid_admin_2step'])) {
						$session->delete($_COOKIE['atsphp_sid_admin_2step'], 'atsphp_sid_admin_2step');
					}

					$session->create('admin', 1);
					header("refresh:0; '{$TMPL['list_url']}/index.php?a=admin'");
					exit;
				}
			}
			else {

				$this->brute_force($TMPL['ip'], $TMPL['timestamp']);

				// Error handled by /admin/
				header("Location: {$TMPL['list_url']}/admin/?fail=1");
				exit;
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
    $session->delete($_COOKIE['atsphp_sid_admin'], 'atsphp_sid_admin');
    $TMPL['content'] = $LNG['a_logout_message'];
    header("Location: {$TMPL['list_url']}/admin/");
	exit;
  }

  function main() {
    global $DB, $CONF, $LNG, $TMPL;

 	list($TMPL['joined_this_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_stats WHERE join_date BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 7 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
 	list($TMPL['joined_last_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_stats WHERE join_date BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 15 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['joined_last_week'] = $TMPL['joined_last_week'] - $TMPL['joined_this_week'];
	list($TMPL['joined_2_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_stats WHERE join_date BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 22 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['joined_2_week'] = $TMPL['joined_2_week'] - $TMPL['joined_last_week'] - $TMPL['joined_this_week'];
 	list($TMPL['joined_3_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_stats WHERE join_date BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 29 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['joined_3_week'] = $TMPL['joined_3_week'] - $TMPL['joined_2_week'] - $TMPL['joined_last_week'] - $TMPL['joined_this_week'];
 	list($TMPL['joined_4_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_stats WHERE join_date BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 36 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['joined_4_week'] = $TMPL['joined_4_week'] - $TMPL['joined_3_week'] - $TMPL['joined_2_week'] - $TMPL['joined_last_week'] - $TMPL['joined_this_week'];

	/////////////////
    //Premium Member counts
 	list($TMPL['premium_this_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites WHERE date_start_premium BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 7 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
 	list($TMPL['premium_last_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites WHERE date_start_premium BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 15 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['premium_last_week'] = $TMPL['premium_last_week'] - $TMPL['premium_this_week'];
 	list($TMPL['premium_2_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites WHERE date_start_premium BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 22 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['premium_2_week'] = $TMPL['premium_2_week'] - $TMPL['premium_last_week'] - $TMPL['premium_this_week'];
 	list($TMPL['premium_3_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites WHERE date_start_premium BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 29 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['premium_3_week'] = $TMPL['premium_3_week'] - $TMPL['premium_2_week'] - $TMPL['premium_last_week'] - $TMPL['premium_this_week'];
 	list($TMPL['premium_4_week']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites WHERE date_start_premium BETWEEN DATE_SUB( CURDATE( ) ,INTERVAL 36 DAY ) AND CURDATE( ) ", __FILE__, __LINE__);
	$TMPL['premium_4_week'] = $TMPL['premium_4_week'] - $TMPL['premium_3_week'] - $TMPL['premium_2_week'] - $TMPL['premium_last_week'] - $TMPL['premium_this_week'];
    $TMPL['admin_content'] = "{$LNG['a_main']}<br /><br />";


    $TMPL['admin_content'] .= $this->do_skin('admin_main_graph');


    $phpversion = phpversion();
    if (ini_get('allow_url_fopen')) {
      $latest_version = file_get_contents('http://visiolist.com/version.txt');
    }
    else {
      $latest_version = '?';
    }

    $TMPL['admin_content'] .= "{$LNG['a_main_your']}: {$TMPL['version']}<br />{$LNG['a_main_latest']}: {$latest_version}<br />\n<a href=\"http://visiolist.com\">{$LNG['a_main_new']}</a><br /><br />";
    list($num_waiting) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites WHERE active = 0", __FILE__, __LINE__);
    if ($num_waiting == 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve\" title=\"{$LNG['a_main_approve']}\" class=\"vistip\">{$num_waiting}</a></div>";
    }
    elseif ($num_waiting > 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve\" title=\"".sprintf($LNG['a_main_approves'], $num_waiting)."\" class=\"vistip\">{$num_waiting}</a></div>";
    }
    list($num_waiting_edited) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites_edited", __FILE__, __LINE__);
    if ($num_waiting_edited == 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_edited\" title=\"{$LNG['a_main_approve_edit']}\" class=\"vistip\">{$num_waiting_edited}</a></div>";
	}
    elseif ($num_waiting_edited > 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_edited\" title=\"".sprintf($LNG['a_main_approve_edits'], $num_waiting_edited)."\" class=\"vistip\">{$num_waiting_edited}</a></div>";
    }
    list($num_waiting_rev) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_reviews WHERE active = 0", __FILE__, __LINE__);
    if ($num_waiting_rev == 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_reviews\" title=\"{$LNG['a_main_approve_rev']}\" class=\"vistip\">{$num_waiting_rev}</a></div>";
    }
    elseif ($num_waiting_rev > 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_reviews\" title=\"".sprintf($LNG['a_main_approve_revs'], $num_waiting_rev)."\" class=\"vistip\">{$num_waiting_rev}</a></div><br /><br />";
    }

	// List if there are some PREMIUM REQUEST
	list($num_premium_req) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites WHERE premium_request = 1", __FILE__, __LINE__);
    if ($num_premium_req == 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_premium\" title=\"".sprintf($LNG['a_main_premium_approve'], $num_premium_req)."\" class=\"vistip\">{$num_premium_req}</a></div>";
    }
    elseif ($num_premium_req > 1) {
      $TMPL['admin_content'] .= "<div class=\"admin_front_approve\"><a href=\"{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_premium\" title=\"".sprintf($LNG['a_main_premium_approves'], $num_premium_req)."\" class=\"vistip\">{$num_premium_req}</a></div>";
    }



    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_build_page'));


    $TMPL['content'] = $this->do_skin('admin');
  }
}
