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

class lost_pw extends base {
  public function __construct() {
    global $FORM, $LNG, $TMPL, $CONF;

    $TMPL['header'] = $LNG['lost_pw_header'];

    if (!isset($FORM['submit']) && !isset($FORM['sid'])) {

		if ($CONF['clean_url'] == 1 && preg_match('/\?/', $_SERVER['REQUEST_URI']))
		{
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: {$CONF['list_url']}/lost_pw/");
			exit;
		}

		if ($CONF['recaptcha'] && $CONF['lostpw_recaptcha']) {
			$TMPL['recaptcha_sitekey'] = $CONF['recaptcha_sitekey'];
			$TMPL['join_recaptcha'] = $this->do_skin('join_recaptcha');
		}
		else {
			$TMPL['join_recaptcha'] = '';
		}

		$TMPL['content'] = $this->do_skin('lost_pw_form');
    }
    elseif (isset($FORM['submit']) && !isset($FORM['password'])) {
		$this->email();
    }
    elseif (isset($FORM['sid']) && !isset($FORM['password'])) {
		$this->form();
    }
    elseif (isset($FORM['sid']) && isset($FORM['password'])) {
		$this->new_password();
    }
  }

  function email() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;


	if ($CONF['recaptcha'] && $CONF['lostpw_recaptcha']) 
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


    $username = isset($FORM['u']) ? $DB->escape($FORM['u']) : '';
    list($email) = $DB->fetch("SELECT email FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);
    if ($email) {
		require_once("{$CONF['path']}/sources/misc/session.php");
		$session = new session;
		$TMPL['sid'] = $session->create('lost_pw', $username, 0);

		$lost_pw_email = new skin('lost_pw_email');
		$lost_pw_email->send_email($email);

		eval (PluginManager::getPluginManager ()->pluginHooks ('lost_pw_email_finish'));

		$TMPL['content'] = $this->do_skin('lost_pw_finish');
    }
    else {
		$this->error($LNG['g_invalid_u']);
    }

  }

  function form() {
    global $CONF, $FORM, $LNG, $TMPL;

    require_once("{$CONF['path']}/sources/misc/session.php");
    $session = new session;
    list($type, $data) = $session->get($FORM['sid']);

    if ($type == 'lost_pw') {
		$TMPL['sid'] = htmlentities(strip_tags($FORM['sid']), ENT_QUOTES, "UTF-8");

		eval (PluginManager::getPluginManager ()->pluginHooks ('lost_pw_form'));

		$TMPL['content'] = $this->do_skin('lost_pw_form_2');
    }
    else {
		$this->error($LNG['g_session_expired']);
    }
  }

  function new_password() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

	require_once("{$CONF['path']}/sources/misc/session.php");
	$session = new session;
	list($type, $data) = $session->get($FORM['sid']);
	$TMPL['username'] = $DB->escape($data);
	$password = md5($FORM['password']);

	if ($type == 'lost_pw') {
		$session->delete($FORM['sid'], 'atsphp_sid_lost_pw');

		$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET password = '{$password}' WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);

		// Invalidate all possible logins on this user
		$DB->query("DELETE FROM {$CONF['sql_prefix']}_sessions WHERE type = 'user_cp' AND data = '{$TMPL['username']}'", __FILE__, __LINE__);

		eval (PluginManager::getPluginManager ()->pluginHooks ('lost_pw_new_password'));

		$TMPL['content'] = $this->do_skin('lost_pw_finish_2');
    }
    else {
		$this->error($LNG['g_session_expired']);
    }
  }
}
