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

class manage_pages extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_man_pages_header'];

    $num_list = 20;

    if (isset($FORM['start'])) {
      $start = $DB->escape($FORM['start']);
    }
    else {
      $start = '';
    }

    $ids_menu = '';
    $result = $DB->select_limit("SELECT id FROM {$CONF['sql_prefix']}_custom_pages ORDER BY id ASC", 1, 0, __FILE__, __LINE__);
    list($id_start) = $DB->fetch_array($result);
    while ($id_start) {
      $result = $DB->select_limit("SELECT id FROM {$CONF['sql_prefix']}_custom_pages WHERE id > '{$id_start}' ORDER BY id ASC", 2, $num_list - 2, __FILE__, __LINE__);
      list($id_end) = $DB->fetch_array($result);
      if (!$id_end) {
        $result = $DB->select_limit("SELECT id FROM {$CONF['sql_prefix']}_custom_pages ORDER BY id DESC", 1, 0, __FILE__, __LINE__);
        list($id_end) = $DB->fetch_array($result);
      }

      if ($id_start == $start) { $ids_menu .= "<option value=\"{$id_start}\" selected=\"selected\">{$id_start} - {$id_end}</option>"; }
      else { $ids_menu .= "<option value=\"{$id_start}\">{$id_start} - {$id_end}</option>"; }

      list($id_start) = $DB->fetch_array($result);
    }

    list($num_pages) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_custom_pages", __FILE__, __LINE__);
    if ($num_pages > $num_list) {
      $TMPL['admin_content'] = <<<EndHTML
<form action="index.php" method="get">
<input type="hidden" name="a" value="admin" />
<input type="hidden" name="b" value="manage_pages" />
<select name="start">
{$ids_menu}
</select>
<input type="submit" value="{$LNG['g_form_submit_short']}" class="positive" />
</form><br />
EndHTML;
    }
    else {
      $TMPL['admin_content'] = '';
    }

    $TMPL['admin_content'] .= <<<EndHTML

<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete_page" method="post" name="manage">
<table class="darkbg" cellpadding="1" cellspacing="1" width="100%">
<tr class="mediumbg">
<td>{$LNG['a_man_delete']}?</td>
<td align="center" width="1%">{$LNG['a_man_rev_id']}</td>
<td width="100%">{$LNG['table_title']}</td>
<td align="center" colspan="2">{$LNG['a_man_actions']}</td>
</tr>
EndHTML;

    $alt = '';
    $num = 0;
    $result = $DB->select_limit("SELECT id, title, content FROM {$CONF['sql_prefix']}_custom_pages WHERE id >= '{$start}' ORDER BY id ASC", $num_list, 0, __FILE__, __LINE__);
    while (list($id, $title, $content) = $DB->fetch_array($result)) {
      $TMPL['admin_content'] .= <<<EndHTML
<tr class="lightbg{$alt}">
<td><input type="checkbox" name="id[]" value="{$id}" id="checkbox_{$num}" class="check_selectall_none" /></td>
<td align="center">{$id}</td>
<td width="100%"><a href="{$TMPL['list_url']}/{$TMPL['url_helper_a']}page{$TMPL['url_helper_id']}{$id}{$TMPL['url_tail']}">{$title}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit_page&amp;id={$id}">{$LNG['a_man_edit']}</a></td>
<td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete_page&amp;id={$id}">{$LNG['a_man_delete']}</a></td>
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
<input type="submit" value="{$LNG['a_man_del_sel']}" class="positive" />
</form>
EndHTML;
  }
}
