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

class search_user extends base {
  public function __construct() {
    global $CONF, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_search_header'];

    if (!isset($FORM['q']) || !$FORM['q']) {
      $this->process();
    }
    else {
      $this->process();
    }
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['query'] = '';
    $TMPL['admin_content'] = '';
    $TMPL['query'] = strip_tags($FORM['q']);

    $TMPL['admin_content'] .= <<<EndHTML

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

<form action="index.php" method="get">
<input type="hidden" name="a" value="admin" />
<input type="hidden" name="b" value="search_user" />
<input type="text" name="q" size="18" value="" />
<input type="submit" value="{$LNG['g_form_submit_short']}" />
</form><br />

<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete" method="post" name="manage">
<table cellpadding="1" cellspacing="1" width="100%" id="man">
<thead>
<tr class="mediumbg">
<th>{$LNG['a_man_delete']}?</th>
<th align="center" width="1%">{$LNG['g_username']}</th>
<th width="90%">{$LNG['table_title']}</th>
<th align="center" colspan="6">{$LNG['a_man_actions']}</th>
</tr>
</thead>
<tbody>
EndHTML;

    $alt = '';
    $num = 0;
    $query = $DB->query("SELECT username, title, url, email, user_ip FROM {$CONF['sql_prefix']}_sites WHERE active = 1 AND username LIKE '%{$TMPL['query']}%' OR title LIKE '%{$TMPL['query']}%' OR url LIKE '%{$TMPL['query']}%'", __FILE__, __LINE__);
    while (list($username, $title, $url, $email, $user_ip) = $DB->fetch_array($query)) {
      $url_url = urlencode($url);
      $user_ip_url = urlencode($user_ip);
      $username_url = urlencode($username);
      $email_url = urlencode($email);
	  $title = htmlspecialchars($title, ENT_QUOTES, "UTF-8");

      $blacklist_extra = '';
      $screenshot = '';

      if (!empty($CONF['visio_screen_api'])) {

        $screenshot = "<a href=\"{$TMPL['list_url']}/screenshots.php?url={$url}&generate=1\" onclick=\"return popitup('screenshots.php?url={$url}&generate=1')\">Screenshot</a>";

      }


eval (PluginManager::getPluginManager ()->pluginHooks ('admin_manage_member_loop'));


      $TMPL['admin_content'] .= <<<EndHTML
<tr class="lightbg{$alt}">
<td><input type="checkbox" name="u[]" value="{$username}" id="checkbox_{$num}" class="check_selectall_none" /></td>
<td align="center">{$username}</td>
<td width="100%"><a href="{$url}" onclick="out('{$username}');" title="{$url}" class="vistip">{$title}</a></td>
<td align="center">{$screenshot}</td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit&amp;u={$username}">{$LNG['a_man_edit']}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete&amp;u={$username}">{$LNG['a_man_delete']}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_reviews&amp;u={$username}">{$LNG['a_header_reviews']}</a></td>
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

    if(mysql_num_rows($query) == '0'){
      $TMPL['admin_content'] = $LNG['a_search_no_user']."<br />";
    }


    $TMPL['admin_content'] .= <<<EndHTML
</table><br />
<span id="selectall">{$LNG['a_man_all']}</span> | 
<span id="selectnone">{$LNG['a_man_none']}</span><br /><br />
<input type="submit" value="{$LNG['a_man_del_sel']}" />
</form>
EndHTML;

  }
}
