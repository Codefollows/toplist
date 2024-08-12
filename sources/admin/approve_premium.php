<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2007 Jeremy Scheff.  All rights reserved.              \\
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

class approve_premium extends base {
  public function __construct() {
    global $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_approve_premium_header'];

    if (!isset($FORM['u'])) {
      $this->form();
    }
    else {
      $this->process();
    }
  }

  function form() {
    global $CONF, $DB, $LNG, $TMPL;

    $alt = '';
    $num = 0;
    $result = $DB->query("SELECT username, url, title, email, user_ip, total_day, remain_day, weeks_buy FROM {$CONF['sql_prefix']}_sites WHERE active = 1 AND premium_request = 1 ORDER BY username ASC", __FILE__, __LINE__);
    if ($DB->num_rows($result)) {
      $TMPL['admin_content'] = <<<EndHTML
<script language="javascript">
var count = 0;
function popup(id)
{
  count = count + 1;
  elem = document.getElementById(id);
  elem.style.zIndex = count;
  if (elem.style.display == "none") { elem.style.display = "block"; }
  else { elem.style.display = "none"; }
}
</script>

<table class="darkbg" cellpadding="1" cellspacing="1" width="100%">
<tr class="mediumbg">
<td align="center" width="1%" colspan="2">{$LNG['g_username']}</td>
<td width="50%">${LNG['table_title']}</td>
<td align="center" colspan="5">{$LNG['a_man_actions']}</td>
</tr>
EndHTML;

      while (list($username, $url, $title, $email, $user_ip, $total_day, $remain_day, $weeks_buy) = $DB->fetch_array($result)) {
        $url_url = urlencode($url);
        $user_ip_url = urlencode($user_ip);
        $username_url = urlencode($username);
        $email_url = urlencode($email);
		$title = htmlspecialchars($title, ENT_QUOTES, "UTF-8");
		$url = htmlspecialchars($url, ENT_QUOTES, "UTF-8");

        $TMPL['admin_content'] .= <<<EndHTML
<tr class="lightbg{$alt}">
<td align="center" colspan="2">$username</td>
<td width="20%"><a href="{$url}" onclick="out('{$username}');">{$title}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_premium&amp;u={$username}">{$LNG['a_approve_premium']}</a></td>
<td align="center" colspan="2"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_premium&amp;u={$username}&amp;c=reject">{$LNG['a_reject_premium']}</a></td>
</tr>
<tr class="mediumbg">
<td colspan="6" align="center">{$LNG['a_approve_premium_profile']}</td>
</tr>
<tr class="lightbg{$alt}">
<td width="10%" align="center" colspan="2">{$LNG['a_approve_premium_total_days']}: $total_day</td>
<td width="20%" align="center">{$LNG['a_approve_premium_remain_days']}: $remain_day</td>
<td width="50%" align="center" colspan="3">{$LNG['a_approve_premium_weeks_buy']}: $weeks_buy <a href=</td>
</tr>

EndHTML;
        if ($alt) { $alt = ''; }
        else { $alt = 'alt'; }
        $num++;
      }

      $TMPL['admin_content'] .= <<<EndHTML
</table><br />
<br />
EndHTML;
    }
    else {
      $TMPL['admin_content'] = $this->error($LNG['a_approve_none'], 'admin');
    }
  }

  function process() {
    global $DB, $FORM, $LNG, $TMPL;

	$username = $DB->escape($FORM['u']);
 	if (isset($FORM['c'])) {
      $action_plus = $DB->escape($FORM['c']);
      if ($action_plus == 'reject') {
		$this->do_reject($username);
	    $TMPL['admin_content'] = $LNG['a_reject_premium_done'];
        header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=approve_premium");
	  }  
	} 
	else {
		$this->do_approve($username);
	    $TMPL['admin_content'] = $LNG['a_approve_done'];
        header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=approve_premium");		
	}
  }

  function do_approve($username) {
    global $CONF, $DB, $LNG, $TMPL;
	

	$approve_premium_date = date('Y-m-d', time() + (3600*$CONF['time_offset']));

    $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET premium_flag = 1, premium_request = 0, date_start_premium = '{$approve_premium_date}' WHERE username = '{$username}'", __FILE__, __LINE__);

    list($TMPL['username'], $TMPL['email']) = $DB->fetch("SELECT username, email, url FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);

    // Plugin hook, when premium approved by admin
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_approve_premium_do_approve'));

    $premium_app_email = new skin('premium_app_email');
    $premium_app_email->send_email($TMPL['email']);
  }
  
  function do_reject($username) {
    global $CONF, $DB, $LNG, $TMPL;
	

    $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET premium_flag = 0, premium_request = 0, total_day = '', remain_day = '', weeks_buy = '' WHERE username = '{$username}'", __FILE__, __LINE__);

    list($TMPL['username'], $TMPL['email']) = $DB->fetch("SELECT username, email, url FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);

    $premium_rej_email = new skin('premium_rej_email');
    $premium_rej_email->send_email($TMPL['email']);
  }
}
