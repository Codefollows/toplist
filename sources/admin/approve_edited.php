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

class approve_edited extends base {
  public function __construct() {
    global $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_approve_edited_header'];

    if (!isset($FORM['u'])) {
      $this->form();
    }
    elseif ($FORM['c'] == 'approve') {
      $this->approve();
    }
    elseif ($FORM['c'] == 'reject') {
      $this->reject();
    }
  }

  function form() {
    global $CONF, $DB, $LNG, $TMPL;

    $alt = '';
    $num = 0;
    $result = $DB->query("SELECT username, url, title FROM {$CONF['sql_prefix']}_sites_edited ORDER BY username ASC", __FILE__, __LINE__);
    if ($DB->num_rows($result)) {
      $TMPL['admin_content'] = <<<EndHTML

<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_edited" method="post" name="approve">
<table class="darkbg" cellpadding="1" cellspacing="1" width="100%">
<tr class="mediumbg">
<td>{$LNG['a_approve']}?</td>
<td align="center" width="1%">{$LNG['g_username']}</td>
<td>{$LNG['a_approve_edited_old']}</td>
<td>${LNG['a_approve_edited_new']}</td>
<td align="center" colspan="2">{$LNG['a_man_actions']}</td>
</tr>
EndHTML;

      while (list($username, $url_new, $title_new) = $DB->fetch_array($result)) {
	$username_sql = $DB->escape($username);
        list($url, $title) = $DB->fetch("SELECT url, title FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username_sql}'", __FILE__, __LINE__);

		$url       = htmlspecialchars($url, ENT_QUOTES, "UTF-8");
		$url_new   = htmlspecialchars($url_new, ENT_QUOTES, "UTF-8");
		$title     = htmlspecialchars($title, ENT_QUOTES, "UTF-8");
		$title_new = htmlspecialchars($title_new, ENT_QUOTES, "UTF-8");

        $TMPL['admin_content'] .= <<<EndHTML
<tr class="lightbg{$alt}">
<td><input type="checkbox" name="u[]" value="{$username}" id="checkbox_{$num}" class="check_selectall_none" /></td>
<td align="center">$username</td>
<td>{$title}<br /><a href="{$url}">{$url}</a></td>
<td>{$title_new}<br /><a href="{$url_new}">{$url_new}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_edited&amp;c=approve&amp;u={$username}">{$LNG['a_approve']}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=approve_edited&amp;c=reject&amp;u={$username}">{$LNG['a_approve_edited_reject']}</a></td>
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
<select name="c">
<option value="approve">{$LNG['a_approve']}</option>
<option value="reject">{$LNG['a_approve_edited_reject']}</option>
</select>
<input type="submit" value="{$LNG['g_form_submit_short']}" />
</form>
EndHTML;
    }
    else {
      $TMPL['admin_content'] = $this->error($LNG['a_approve_edited_none'], 'admin');
    }
  }

  function approve() {
    global $DB, $FORM, $LNG, $TMPL;

    if (is_array($FORM['u']) && count($FORM['u']) > 1) {
      foreach ($FORM['u'] as $username) {
        $this->do_approve($DB->escape($username));
      }
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

    $TMPL['admin_content'] = $LNG['a_approve_edited_done'];
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=approve_edited");		
  }

  function do_approve($username) {
    global $CONF, $DB, $LNG, $TMPL;

    list($url, $title) = $DB->fetch("SELECT url, title FROM {$CONF['sql_prefix']}_sites_edited WHERE username = '{$username}'", __FILE__, __LINE__);
	
    $url_sql = $DB->escape($url, 1);
    $title_sql = $DB->escape($title, 1);
	
	
    // Plugin Hook	     
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_approve_edited_do_approve'));
	

    $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET url = '{$url_sql}', title = '{$title_sql}' WHERE username = '{$username}'", __FILE__, __LINE__);
    $DB->query("DELETE FROM {$CONF['sql_prefix']}_sites_edited WHERE username = '{$username}'", __FILE__, __LINE__);
  }

  function reject() {
    global $DB, $FORM, $LNG, $TMPL;

    if (is_array($FORM['u']) && count($FORM['u']) > 1) {
      foreach ($FORM['u'] as $username) {
        $this->do_reject($username);
      }
    }
    else {
      if (is_array($FORM['u']) && count($FORM['u']) == 1) {
        $username = $DB->escape($FORM['u'][0]);
      }
      else {
        $username = $DB->escape($FORM['u']);
      }

      $this->do_reject($username);
    }

    $TMPL['admin_content'] = $LNG['a_approve_rejected_done'];
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=approve_edited");
  }

  function do_reject($username) {
    global $CONF, $DB, $LNG, $TMPL;

    $DB->query("DELETE FROM {$CONF['sql_prefix']}_sites_edited WHERE username = '{$username}'", __FILE__, __LINE__);
  }
}
