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

class create_page extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_create_page_header'];

    if (!isset($FORM['submit'])) {
      $this->form();
    }
    else {
      $this->process();
    }
  }

  function form() {
    global $LNG, $CONF, $DB, $TMPL;

	$menu_path = '';
	$menu_parent_options = '';
      $menu_path = "{$CONF['list_url']}/{$TMPL['url_helper_a']}page{$TMPL['url_helper_id']}";	  

        $result = $DB->query("SELECT menu_id, menu_name FROM {$CONF['sql_prefix']}_menus ORDER BY menu_name ASC",
            __file__, __line__);
        while (list($id, $name) = $DB->fetch_array($result))
        {
            $menu_parent_options .= "<option value=\"$id\">$name </option>";
        }


    $TMPL['admin_content'] = <<<EndHTML
    


    
<form action="index.php?a=admin&amp;b=create_page" method="post">
<fieldset>
<legend>{$LNG['a_create_page_header']}</legend>
<label for="page_id">{$LNG['a_create_page_id']}</label>
<input type="text" id="page_id" name="id" size="50" />

<label for="title">{$LNG['g_title']}</label>
<input type="text" name="title" id="title" size="50" />

<label for="content">{$LNG['a_edit_page_content']}</label>
<textarea style="width: 100%;" rows="10" id="content" name="content" class="tinymce"></textarea>

<script>
    $("#page_id").bind('input propertychange', function() {
      var value = $(this).val();
      $("#idpath").text(value);
    });
</script>

<label for="menu_text">Menu Text</label>
<input type="text" name="menu_text" id="menu_text" size="50" value=""/><br /><br />

Menu Path<br />{$menu_path}<span id="idpath"></span>{$TMPL['url_tail']}

<label for="menu_parent">Select Menu</label>
<select name="menu_parent" id="menu_parent">
{$menu_parent_options}
</select>

<label for="menu_weight">Item Weight</label>
<input type="text" name="menu_weight" id="menu_weight" size="5" />

<label for="keywords">Meta Keywords</label>
<input type="text" name="keywords" id="keywords" size="50" />

<label for="description">Meta Description</label>
<input type="text" name="description" id="description" size="50" />

</fieldset>

<br />
<input name="submit" class="positive" type="submit" value="{$LNG['a_create_page_header']}" />

</form>
EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['id'] = $DB->escape($FORM['id']);
    $TMPL['title'] = $DB->escape($FORM['title']);
    $TMPL['content'] = $DB->escape($FORM['content']);

    $TMPL['menu_text'] = $DB->escape($FORM['menu_text']);
    $TMPL['menu_weight'] = $DB->escape($FORM['menu_weight']);
    $TMPL['menu_parent'] = $DB->escape($FORM['menu_parent']);

    $TMPL['keywords'] = $DB->escape($FORM['keywords']);
    $TMPL['description'] = $DB->escape($FORM['description']);

    $TMPL['menu_path'] = "{$CONF['list_url']}/{$TMPL['url_helper_a']}page{$TMPL['url_helper_id']}{$TMPL['id']}{$TMPL['url_tail']}";	  	  

    list($id_sql) = $DB->fetch("SELECT id FROM {$CONF['sql_prefix']}_custom_pages WHERE id = '{$TMPL['id']}'", __FILE__, __LINE__);
    if ($id_sql && $id_sql == $TMPL['id']) {
      $this->error($LNG['a_create_page_error_id_duplicate'], 'admin');
    }
    elseif (preg_match('/[^a-zA-Z0-9\-_]+/', $TMPL['id'])) {
      $this->error($LNG['a_create_page_error_id'], 'admin');
    }
    else {
      $DB->query("INSERT INTO {$CONF['sql_prefix']}_custom_pages (id, title, content, keywords, description) VALUES ('{$TMPL['id']}', '{$TMPL['title']}', '{$TMPL['content']}', '{$TMPL['keywords']}', '{$TMPL['description']}')", __FILE__, __LINE__);
 

      $DB->query("INSERT INTO {$CONF['sql_prefix']}_menu (menu_id, title, path, sort) VALUES ('{$TMPL['menu_parent']}', '{$TMPL['menu_text']}', '{$TMPL['menu_path']}', '{$TMPL['menu_weight']}')", __FILE__, __LINE__);
      
      

      $TMPL['admin_content'] = $LNG['a_create_page_created'];
    }
  }
}
