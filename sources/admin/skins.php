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

class skins extends base {
  public function __construct() {
    global $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_skins_header'];

    if (!isset($FORM['submit']) && !isset($FORM['submit_categories']) && !isset($FORM['c'])) {
      $this->form();
    }
    elseif (isset($FORM['default_skin'])) {
      $this->process_default();
    }
    elseif (isset($FORM['submit_categories'])) {
      $this->process_categories();
    }
    elseif (isset($FORM['cat']) && !isset($FORM['c'])) {
      $this->process_new_category();
    }
    elseif (isset($FORM['c']) && $FORM['c'] == 'delete') {
      $this->delete_category();
    }
    elseif (isset($FORM['c']) && $FORM['c'] == 'edit') {
      $this->edit_category();
    }
  }

  function form() {
    global $CONF, $DB, $LNG, $TMPL;

    $default_skin_menu = '';
    $dir = opendir("{$CONF['path']}/skins/");
    while (false !== ($subdir = readdir($dir))) {
        if ($subdir != '.' && $subdir != '..' && $subdir != 'index.htm' && file_exists("{$CONF['path']}/skins/{$subdir}/info.php")) {
        unset($name, $author, $email, $url);

        // Load child info.php if exist
        if(file_exists("{$CONF['path']}/skins/{$subdir}/child/info.php"))
        {
            include "{$CONF['path']}/skins/{$subdir}/child/info.php";
        }
        else
        {
            include "{$CONF['path']}/skins/{$subdir}/info.php";
        }
        // Load child skin preview img if exist
        if(file_exists("{$CONF['path']}/skins/{$subdir}/child/{$name}.jpg"))
        {
          $skin_preview_img = "skins/{$subdir}/child/{$name}.jpg";
        }
        else
        {
          $skin_preview_img = "skins/{$subdir}/{$name}.jpg";
        }

        if ($CONF['default_skin'] == $subdir) {
          $checked = ' checked="checked"';
        }
        else {
          $checked = '';
        }

        if (!isset($author) || !$author) {
          $author = $LNG['a_skins_anon'];
        }
        if (isset($email) && $email) {
          $author_link = "<a href=\"mailto:{$email}\">{$author}</a>";
        }
        else {
          $author_link = $author;
        }
        if (isset($url) && $url) {
          $url_link = "<br /><a href=\"{$url}\">{$url}</a>";
        }
        else {
          $url_link = '';
        }

        $default_skin_menu .= <<<EndHTML

<div style="width: 240px; padding: 10px; height: 240px;float:left;margin: 10px;background: #fcfbf9;text-align: center;border-radius: 15px; border: 2px solid #fff;box-shadow: 0 0 4px #666">
<input type="radio" name="default_skin" value="{$subdir}"{$checked} />{$LNG['a_skins_default']}<br />
<img src="{$skin_preview_img}" style="border: 2px solid #fff; margin: 0 0 5px;" width="200px" height="140px" alt="{$name}"><br />
<a href="index.php?a=admin&b=manage_skins&s={$subdir}&t=wrapper.html"><span style="background: url(skins/admin/images/edit_sm.png) no-repeat; padding: 3px 3px 3px 25px;">{$LNG['a_man_edit']}</span></a>
<a href="index.php?a=admin&b=manage_skins&s={$subdir}&child=1&t=wrapper.html"><span style="background: url(skins/admin/images/edit_sm.png) no-repeat; padding: 3px 3px 3px 25px;">{$LNG['a_skins_edit_child']}</span></a><br />
<b>{$name}</b> by {$author_link}{$url_link}
</div>

EndHTML;
      }
    }

    $categories_menu = '<table cellspacing="0" cellpadding="0" width="100%">';
    foreach ($CONF['categories'] as $cat => $skin) {
      $cat_sql = $DB->escape($cat, 1);
      list($cat_skin) = $DB->fetch("SELECT skin FROM {$CONF['sql_prefix']}_categories WHERE category = '{$cat_sql}'", __FILE__, __LINE__);

      $skins_menu = '';
      $dir = opendir("{$CONF['path']}/skins/");
      while (false !== ($subdir = readdir($dir))) {
      if ($subdir != '.' && $subdir != '..' && $subdir != 'index.htm' && file_exists("{$CONF['path']}/skins/{$subdir}/info.php")) {
	  unset($name, $author, $email, $url);

          require "{$CONF['path']}/skins/{$subdir}/info.php";
          if ($cat_skin == $subdir) {
            $skins_menu .= "<option value=\"{$subdir}\" selected=\"selected\">{$name}</option>\n";
          }
          else {
            $skins_menu .= "<option value=\"{$subdir}\">{$name}</option>\n";
          }
        }
      }

      $cat_url = urlencode($cat);
      $cat_form = $cat_url;

      $categories_menu .= <<<EndHTML
<tr>
<td valign="top" width="20%">{$cat}</td>
<td valign="top" width="30%"><a href="index.php?a=admin&amp;b=skins&amp;c=edit&amp;cat={$cat_url}">{$LNG['a_man_edit']}</a>
<a href="index.php?a=admin&amp;b=skins&amp;c=delete&amp;cat={$cat_url}">{$LNG['a_man_delete']}</a></td>
<td valign="top" width="50%">
<select name="skin_{$cat_form}">
<option value="">{$LNG['a_skins_default']}</option>
{$skins_menu}</select><br /><br />
</td>
</tr>
EndHTML;
    }
    $categories_menu .= '</table>';

    $TMPL['admin_content'] = <<<EndHTML
<form action="index.php?a=admin&amp;b=skins" method="post">
<fieldset>
<legend>{$LNG['a_skins_default']}</legend>
{$default_skin_menu}
<br style="clear: left;"/>
<button type="submit" name="submit" class="positive">{$LNG['a_skins_set_default']}</button>
</fieldset>
</form>
<form action="index.php?a=admin&amp;b=skins" method="post">
<fieldset>
<legend>{$LNG['a_skins_categories']}</legend>
<form action="index.php?a=admin&amp;b=skins" method="post">
    <label for="cat">{$LNG['a_skins_category_name']}</label>
    <input type="text" name="cat" id="cat" size="20" />

    <label for="category_slug">{$LNG['a_skins_category_url']}</label>
    <input type="text" name="category_slug" id="category_slug" size="20" />

    <br /><br />
    <input type="submit" name="submit" value="{$LNG['a_skins_new_category']}" /><br /><br /><br />
</form>
<form action="index.php?a=admin&amp;b=skins" method="post">
{$LNG['a_skins_diff_skins']}<br /><br />
{$categories_menu}
<input type="submit" name="submit_categories" class="positive" value="{$LNG['a_skins_set_skins']}" />
</fieldset>


</form>
EndHTML;
  }

  function process_default() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    if (file_exists("{$CONF['path']}/skins/{$FORM['default_skin']}/info.php")) {
      $default_skin = $DB->escape($FORM['default_skin']);

      $DB->query("UPDATE {$CONF['sql_prefix']}_settings SET default_skin = '{$default_skin}'", __FILE__, __LINE__);

      $TMPL['admin_content'] = $LNG['a_skins_default_done'];
      header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=skins");
    }
    else {
      $this->error(sprintf($LNG['a_skins_invalid_skin'], $FORM['default_skin']), 'admin');
    }
  }

  function process_categories() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    foreach ($CONF['categories'] as $cat => $skin) {

      $cat_form = urlencode($cat);
      if (isset($FORM['skin_'.$cat_form]) && file_exists("{$CONF['path']}/skins/{$FORM['skin_'.$cat_form]}/info.php")) {
        $new_skin = $DB->escape($FORM['skin_'.$cat_form]);
      }
      else {
        $new_skin = '';
      }

      $DB->query("UPDATE {$CONF['sql_prefix']}_categories SET skin = '{$new_skin}' WHERE category = '{$cat}'", __FILE__, __LINE__);
    }
    $TMPL['admin_content'] = $LNG['a_skins_categories_done'];
	header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=skins");
  }

  function process_new_category() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $category = $DB->escape($FORM['cat']);
    $cat_slug = preg_replace('/([^\p{L}\p{N}]|[\-])+/u', '-', $FORM['cat']);
    $cat_slug = trim($cat_slug, '-');
    $cat_slug = $DB->escape($cat_slug);

    $DB->query("INSERT INTO {$CONF['sql_prefix']}_categories (category, category_slug) VALUES ('{$category}', '{$cat_slug}')", __FILE__, __LINE__);

    $TMPL['admin_content'] = $LNG['a_skins_new_category_done'];
    header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=skins");
  }

  function delete_category() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    list($num_cats) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_categories", __FILE__, __LINE__);
    if ($num_cats > 1) {
      $category = $DB->escape($FORM['cat']);
      $DB->query("DELETE FROM {$CONF['sql_prefix']}_categories WHERE category = '{$category}'", __FILE__, __LINE__);

	  // Set users to a random existing category
      list($new_cat) = $DB->fetch("SELECT category FROM {$CONF['sql_prefix']}_categories ORDER BY RAND() LIMIT 1", __FILE__, __LINE__);
      $new_category = $DB->escape($new_cat);
      $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET category = '{$new_category}' WHERE category = '{$category}'", __FILE__, __LINE__);

      $TMPL['admin_content'] = $LNG['a_skins_delete_done'];
      header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=skins");
    }
    else {
      $this->error($LNG['a_skins_delete_error'], 'admin');
    }
  }

  function edit_category() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    // We already have config setup, no need a query category data
    $old_category = $FORM['cat'];

    if (!isset($FORM['submit']))
    {
        $cat_url = urlencode($old_category);

        $new_category    = htmlspecialchars($old_category, ENT_QUOTES, "UTF-8");
        $category_slug   = htmlspecialchars($CONF['categories'][$old_category]['cat_slug'], ENT_QUOTES, "UTF-8");
        $cat_description = htmlspecialchars($CONF['categories'][$old_category]['cat_desc'], ENT_QUOTES, "UTF-8");
        $cat_keywords    = htmlspecialchars($CONF['categories'][$old_category]['cat_key'], ENT_QUOTES, "UTF-8");

        $TMPL['admin_content'] = <<<EndHTML
          <form action="index.php?a=admin&amp;b=skins&amp;c=edit&amp;cat={$cat_url}" method="post">
            <fieldset>
              <legend>{$LNG['a_skins_edit_category']}</legend>

              <label for="new_cat">{$LNG['a_skins_category_name']}</label>
              <input type="text" name="new_cat" id="new_cat" size="20" value="{$new_category}" />

              <label for="category_slug">{$LNG['a_skins_category_url']}</label>
              <input type="text" name="category_slug" id="category_slug" size="20" value="{$category_slug}" />

              <label for="cat_description">{$LNG['a_skins_category_description']}</label>
              <textarea name="cat_description" id="cat_description">{$cat_description}</textarea>

              <label for="cat_keywords">{$LNG['a_skins_category_keywords']}</label>
              <textarea name="cat_keywords" id="cat_keywords">{$cat_keywords}</textarea>

            </fieldset>

            <br />
            <input type="submit" name="submit" class="positive" value="{$LNG['a_skins_edit_category']}" />

          </form>
EndHTML;

    }
    else
    {
        $new_category = $FORM['new_cat'];
        $cat_slug     = mb_strlen($FORM['category_slug']) > 0 ? $FORM['category_slug'] : $new_category;
        $cat_slug     = preg_replace('/([^\p{L}\p{N}]|[\-])+/u', '-', $cat_slug);
        $cat_slug     = trim($cat_slug, '-');
        $old_slugs    = $CONF['categories'][$old_category]['old_slugs'];

        // Is the new slug in old_slugs? then we have to remove it from there to avoid endless loops
        if (in_array($cat_slug, $old_slugs))
        {
            $old_slug_key = array_search($cat_slug, $old_slugs);

            if ($old_slug_key !== false) {
                unset($old_slugs[$old_slug_key]);
            }
        }

        // Did the slug change? put the old slug into old_slugs
        if ($cat_slug !== $CONF['categories'][$old_category]['cat_slug']) {
            $old_slugs[] = $CONF['categories'][$old_category]['cat_slug'];
        }

        $old_category_sql    = $DB->escape($old_category);
        $new_category_sql    = $DB->escape($new_category);
        $cat_description_sql = $DB->escape($FORM['cat_description']);
        $cat_keywords_sql    = $DB->escape($FORM['cat_keywords']);
        $cat_slug_sql        = $DB->escape($cat_slug);
        $old_slugs_sql       = $DB->escape(json_encode($old_slugs));

        $DB->query("UPDATE {$CONF['sql_prefix']}_categories SET category = '{$new_category_sql}', category_slug = '{$cat_slug_sql}', old_slugs = '{$old_slugs_sql}', cat_description = '{$cat_description_sql}', cat_keywords = '{$cat_keywords_sql}' WHERE category = '{$old_category_sql}'", __FILE__, __LINE__);

        // Only update users if category name changed
        if ($new_category !== $old_category) {
            $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET category = '{$new_category_sql}' WHERE category = '{$old_category_sql}'", __FILE__, __LINE__);
        }

        $TMPL['admin_content'] = $LNG['a_skins_edit_done'];
        header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=skins");
    }
  }
}
