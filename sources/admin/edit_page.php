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

class edit_page extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_edit_page_header'];

    $id = $DB->escape($FORM['id']);
    list($TMPL['id']) = $DB->fetch("SELECT id FROM {$CONF['sql_prefix']}_custom_pages WHERE id = '{$id}'", __FILE__, __LINE__);
    if ($TMPL['id']) {
      if (!isset($FORM['submit'])) {
        $this->form();
      }
      else {
        $this->process();
      }
    }
    else {
      $this->error($LNG['a_del_page_invalid_id'], 'admin');
    }
  }

  function form() {
    global $CONF, $DB, $LNG, $TMPL;

    $TMPL['description'] = '';
    $TMPL['keywords'] = '';

    list($TMPL['title'], $TMPL['content'], $TMPL['description'], $TMPL['keywords']) = $DB->fetch("SELECT title, content, description, keywords FROM {$CONF['sql_prefix']}_custom_pages WHERE id = '{$TMPL['id']}'", __FILE__, __LINE__);

    $TMPL['admin_content'] = <<<EndHTML
<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit_page&amp;id={$TMPL['id']}" method="post">
<fieldset>
<legend>{$LNG['a_edit_page_header']}</legend>

<label for="title">{$LNG['g_title']}</label>
<input type="text" name="title" id="title" size="50" value="{$TMPL['title']}" />

<label for="content">{$LNG['a_edit_page_content']}</label>
<textarea style="width: 100%;" rows="10" name="content" id="content" class="tinymce">{$TMPL['content']}</textarea>

<label for="keywords">Meta Keywords</label>
<input type="text" name="keywords" id="keywords" size="50" value="{$TMPL['keywords']}" />

<label for="description">Meta Description</label>
<input type="text" name="description" id="description" size="50" value="{$TMPL['description']}" />

</fieldset>

<br />
<input name="submit" type="submit" value="{$LNG['a_edit_page_header']}" />

</form>
EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['title'] = $DB->escape($FORM['title']);
    $TMPL['content'] = $DB->escape($FORM['content']);
    $TMPL['keywords'] = $DB->escape($FORM['keywords']);
    $TMPL['description'] = $DB->escape($FORM['description']);

    $DB->query("UPDATE {$CONF['sql_prefix']}_custom_pages SET title = '{$TMPL['title']}', content = '{$TMPL['content']}', keywords = '{$TMPL['keywords']}', description = '{$TMPL['description']}' WHERE id = '{$TMPL['id']}'", __FILE__, __LINE__);
 
    $TMPL['admin_content'] = $LNG['a_edit_page_edited'];
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=edit_page&id={$TMPL['id']}");		
  }
}
