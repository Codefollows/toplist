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

class approve extends base {
  public function __construct() {
    global $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_approve_header'];

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
    $result = $DB->query("SELECT username, url, title, email, user_ip FROM {$CONF['sql_prefix']}_sites WHERE active = 0 ORDER BY username ASC", __FILE__, __LINE__);
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

<form action="{$TMPL['list_url']}/index.php?a=admin" method="post" name="approve">
<table class="darkbg" cellpadding="1" cellspacing="1" width="100%">
<tr class="mediumbg">
<td>{$LNG['a_approve']}?</td>
<td align="center" width="1%">{$LNG['g_username']}</td>
<td width="100%">${LNG['table_title']}</td>
<td align="center" colspan="5">{$LNG['a_man_actions']}</td>
</tr>
EndHTML;

      while (list($username, $url, $title, $email, $user_ip) = $DB->fetch_array($result)) {
        $url_url = urlencode($url);
        $user_ip_url = urlencode($user_ip);
        $username_url = urlencode($username);
        $email_url = urlencode($email);
		$title = htmlspecialchars($title, ENT_QUOTES, "UTF-8");
		$url = htmlspecialchars($url, ENT_QUOTES, "UTF-8");

$blacklist_extra = '';

eval (PluginManager::getPluginManager ()->pluginHooks ('admin_approve_member_loop'));

        $TMPL['admin_content'] .= <<<EndHTML
<tr class="lightbg{$alt}">
<td><input type="checkbox" name="u[]" value="{$username}" id="checkbox_{$num}" class="check_selectall_none" /></td>
<td align="center">{$username}</td>
<td width="100%"><a href="{$url}" onclick="out('{$username}');" target="_blank">{$title}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=approve&amp;u={$username}">{$LNG['a_approve']}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit&amp;u={$username}">{$LNG['a_man_edit']}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete&amp;u={$username}">{$LNG['a_man_delete']}</a></td>
<td align="center"><a href="mailto:{$email}">{$LNG['a_man_email']}</a></td>
<td align="center"><a href="javascript:void(0);" onclick="popup('ban_{$num}')">{$LNG['a_menu_manage_ban']}</a>
<div id="ban_{$num}" class="lightbg{$alt}" style="display: none; border: 1px solid #000; position: absolute; padding: 2px; text-align: left;">
<a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$url_url}&amp;field=url&amp;matching=1">URL</a><br />
<a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$user_ip_url}&amp;field=ip&amp;matching=1">User IP</a><br />
<a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$username_url}&amp;field=username&amp;matching=1">Username</a><br />
<a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$email_url}&amp;field=email&amp;matching=1">Email</a>
{$blacklist_extra}
</div>
</td>
</tr>
EndHTML;
        if ($alt) { $alt = ''; }
        else { $alt = 'alt'; }
        $num++;
      }

      $TMPL['admin_content'] .= <<<EndHTML
</table><br />
<span id="selectall">{$LNG['a_man_all']}</span> |
<span id="selectnone">{$LNG['a_man_none']}</span><br /><br />
{$LNG['a_approve_sel']}<br />
<select name="b">
<option value="approve">{$LNG['a_approve']}</option>
<option value="delete">{$LNG['a_man_delete']}</option>
</select>
<input type="submit" value="{$LNG['g_form_submit_short']}" />
</form>
EndHTML;
    }
    else {
      //$TMPL['admin_content'] = $this->error($LNG['a_approve_none'], 'admin');
      $TMPL['admin_content'] = $LNG['a_approve_none'];
      $TMPL['admin_content'] .= '<meta http-equiv="refresh" content="2; url=./?a=admin">';
    }
  }

  function process() {
    global $DB, $FORM, $LNG, $TMPL;

    if (is_array($FORM['u']) && count($FORM['u']) > 1) {
      foreach ($FORM['u'] as $username) {
        $this->do_approve($DB->escape($username));
      }

      $LNG['a_approve_done'] = $LNG['a_approve_dones'];
    }
    else {
      if (is_array($FORM['u']) && count($FORM['u']) == 1) {
        $username = $DB->escape($FORM['u'][0]);
      }
      else {
        $username = $DB->escape($FORM['u']);
      }

      $this->do_approve($username);
    }

    $TMPL['admin_content'] = $LNG['a_approve_done'];
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=approve");
  }

  function do_approve($username) {
    global $CONF, $DB, $LNG, $TMPL;

    $join_date = date('Y-m-d H:i:s', time() + (3600*$CONF['time_offset']));

    $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET active = 1 WHERE username = '{$username}'", __FILE__, __LINE__);
    $DB->query("UPDATE {$CONF['sql_prefix']}_stats SET join_date = '{$join_date}' WHERE username = '{$username}'", __FILE__, __LINE__);


    list($TMPL['username'], $TMPL['url'], $TMPL['title'], $TMPL['description'], $TMPL['category'], $TMPL['banner_url'], $TMPL['email'],  $TMPL['owner']) = $DB->fetch("SELECT username, url, title, description, category, banner_url, email,  owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);

    // Plugin hook - approve members
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_approve_do_approve'));

    if ($CONF['google_friendly_links']) {
      $TMPL['verbose_link'] = "";
    }
    else {
      $TMPL['verbose_link'] = "index.php?a=in&u={$TMPL['username']}";
    }
    $TMPL['link_code'] = $this->do_skin('link_code');

    $LNG['join_welcome'] = sprintf($LNG['join_welcome'], $TMPL['list_name']);

    $TMPL['username'] = $TMPL['owner'];

    $join_email = new skin('join_email');
    $join_email->send_email($TMPL['email']);

  }
}
