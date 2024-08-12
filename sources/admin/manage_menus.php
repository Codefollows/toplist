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

if (!defined('VISIOLIST'))
{
    die("This file cannot be accessed directly.");
}

class manage_menus extends base{
    public function __construct(){
        global $CONF, $DB, $FORM, $LNG, $TMPL;

        $TMPL['header'] = $LNG['a_custom_menu_header'];
		
        $TMPL['admin_content_msg'] = '';


        if (isset($_POST['menu']))
        {
            $menu = $_POST['menu'];
            for ($i = 0; $i < count($menu); $i++)
            {
                $DB->query("UPDATE {$CONF['sql_prefix']}_menu SET sort = " .
                    mysql_real_escape_string($i) . " WHERE id = '" . mysql_real_escape_string($menu[$i]) .
                    "'",__file__, __line__);

            }
        }


        if (isset($_POST['menu_name']))
        {
            $menu_name = $DB->escape($_POST['menu_name']);
                $DB->query("INSERT INTO {$CONF['sql_prefix']}_menus 
				(menu_name, menu_weight, menu_parent) VALUES (\"$menu_name\",1,0)
				",
                __file__, __line__);

            $TMPL['admin_content_msg'] = "<h3>{$LNG['a_custom_menu_created']}</h3><br />";
            header("refresh: 1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_menus");

        }
        
        if (isset($_POST['new_menu_id']))
        {
            $title = $DB->escape($_POST['title']);
            $path = $DB->escape($_POST['path']);
            $menu_id = intval($_POST['new_menu_id']);
			            
                $DB->query("INSERT INTO {$CONF['sql_prefix']}_menu 
				(title, path, menu_id, sort) VALUES ('{$title}', '{$path}', '{$menu_id}', 1)
				",
                __file__, __line__);

            $TMPL['admin_content_msg'] = "<h3>{$LNG['a_custom_menu_create_item_info']}</h3><br />";
            header("refresh: 1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_menus&menu_choice={$menu_id}");

        }
        
        if (isset($FORM['menu_delete']))
        {
            $menu_id = intval($FORM['menu_delete']);                
			
			$DB->query("DELETE FROM {$CONF['sql_prefix']}_menu WHERE menu_id = $menu_id
				",
                __file__, __line__);
                $DB->query("DELETE FROM {$CONF['sql_prefix']}_menus WHERE menu_id = $menu_id
				",
                __file__, __line__);


            $TMPL['admin_content_msg'] = "<h3>{$LNG['a_custom_menu_delete_info']}</h3><br />";
            header("refresh: 1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_menus");

        }


        if (isset($FORM['menu_item_delete']))
        {
            $menu_item_id = intval($FORM['menu_item_delete']);

            list($menu_id) = $DB->fetch("SELECT menu_id FROM {$CONF['sql_prefix']}_menu WHERE id = '{$menu_item_id}'",__file__, __line__);
            $DB->query("DELETE FROM {$CONF['sql_prefix']}_menu WHERE id = $menu_item_id",__file__, __line__);

            $TMPL['admin_content_msg'] = "<h3>{$LNG['a_custom_menu_delete_item1']} {$menu_item_id} {$LNG['a_custom_menu_delete_item2']}</h3><br />";
            header("refresh: 1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_menus&menu_choice={$menu_id}");

        }
        

        if (isset($FORM['update_menu_id']))
        {
            $menu_id = intval($FORM['update_menu_id']);
            $menu_text = $DB->escape($FORM['menu_text']);
            $menu_parent = $DB->escape($FORM['parent']);

            $DB->query("UPDATE {$CONF['sql_prefix']}_menus SET menu_name = \"$menu_text\", menu_parent = \"$menu_parent\" WHERE menu_id = \"$menu_id\"",__file__, __line__);

            $TMPL['admin_content_msg'] = "<h3>{$LNG['a_custom_menu_updated']}</h3><br />";
            header("refresh: 1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_menus&menu_choice={$menu_id}");
        }


        if (isset($FORM['update_item_id']))
        {
            $update_item_id = intval($FORM['update_item_id']);
            $new_title = $DB->escape($FORM['new_title']);
            $new_path = $DB->escape($FORM['new_path']);
            $new_target = addslashes($FORM['new_target']);

            $DB->query("UPDATE {$CONF['sql_prefix']}_menu SET title = \"$new_title\", path = \"$new_path\", target = \"$new_target\" WHERE id = \"$update_item_id\"",__file__, __line__);

            list($menu_id) = $DB->fetch("SELECT menu_id FROM {$CONF['sql_prefix']}_menu WHERE id = '{$update_item_id}'",__file__, __line__);

            $TMPL['admin_content_msg'] = "<h3>{$LNG['a_custom_menu_item_updated']}</h3><br />";
            header("refresh: 1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_menus&menu_choice={$menu_id}");
        }

        $num_list = 50;

        $result = $DB->query("SELECT menu_id, menu_name FROM {$CONF['sql_prefix']}_menus ORDER BY menu_name ASC",
            __file__, __line__);
        
		//Initialize a couple vars    
        $ids_menu = '';
        $TMPL['admin_content'] = '';
         
        while (list($id, $name) = $DB->fetch_array($result))
        {
            $ids_menu .= "<option value=\"$id\">$name </option>";
        }

        $TMPL['admin_content'] .= <<< EndHTML
        
<style>
label { width:130px; display: block; float: left; padding: 10px 0; clear: left; margin: 0;}


</style>

<br />{$TMPL['admin_content_msg']}

<div id="tabs">
	<ul>
		<li><a href="#tabs-1">{$LNG['a_custom_menu_edit']}</a></li>
		<li><a href="#tabs-2">{$LNG['a_custom_menu_delete']}</a></li>
		<li><a href="#tabs-3">{$LNG['a_custom_menu_create']}</a></li>
		<li><a href="#tabs-4">{$LNG['a_custom_menu_create_item']}</a></li>
	</ul>

<div id="tabs-1">
<p>{$LNG['a_custom_menu_info']}</p>
 <form action="index.php" method="get">
 <input type="hidden" name="a" value="admin" />
 <input type="hidden" name="b" value="manage_menus" />
 <select name="menu_choice">
 {$ids_menu}
 </select>
 <input type="submit" class="positive" value="{$LNG['g_form_submit_short']}" />
 </form>
</div>


<div id="tabs-2">
<form action="index.php?a=admin&b=manage_menus" method="post">
<p>{$LNG['a_custom_menu_delete_choose']}</p>
<input type="hidden" name="a" value="admin" />
<input type="hidden" name="b" value="manage_menus" />
<select name="menu_delete">
{$ids_menu}
</select>
<input type="submit" class="positive" value="{$LNG['a_custom_menu_delete1']}" />
</form>
</div>


<div id="tabs-3">
<h3>{$LNG['a_custom_menu_create']}</h3>
<p>{$LNG['a_custom_menu_create_info']}</p>
<form action="index.php?a=admin&b=manage_menus" method="post">
<input type="hidden" name="a" value="admin" />
<input type="hidden" name="b" value="manage_menus" />
<input type="text" name="menu_name" />
<input type="submit" class="positive" value="{$LNG['g_form_submit_short']}" />
</form>
</div>

<div id="tabs-4">
<h3>{$LNG['a_custom_menu_create_item']}</h3>
<form action="index.php?a=admin&b=manage_menus" method="post" id="menu_create_item">
<input type="hidden" name="a" value="admin" />
<input type="hidden" name="b" value="manage_menus" />
<p>
<label>{$LNG['a_custom_menu']}:</label>
<select name="new_menu_id">
{$ids_menu}
</select>
</p>

<p><label>{$LNG['a_custom_menu_link_text']}:</label> <input type="text" name="title" /></p>
<p><label>{$LNG['a_custom_menu_path']}:</label> <input type="text" name="path" id="path" size="50" value="" /> <br /></p>
<p>
<b>{$LNG['a_custom_menu_create_vl_core_links']}</b>: {$LNG['a_custom_menu_create_clickadd']}<br />
<a class="vl_core_links" href="#" title="{\$list_url}/">{$LNG['main_menu_rankings']}</a><br />
<a class="vl_core_links" href="#" title="{\$list_url}/{\$url_helper_a}join{\$url_tail}">{$LNG['main_menu_join']}</a><br />
<a class="vl_core_links" href="#" title="{\$list_url}/{\$url_helper_a}user_cpl{\$url_tail}">{$LNG['main_menu_user_cp']}</a><br />
</p>

<input type="submit" class="positive" value="{$LNG['g_form_submit_short']}" />

</form>
</div>

</div>

EndHTML;


        if (isset($FORM['menu_choice']))
        {
            $menu_id = intval($FORM['menu_choice']);

            $TMPL['admin_content'] .= <<< EndHTML

<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_menus&update_menu_id={$menu_id}" method="post" name="manage">
<p style="margin-top: 35px;"> </p>
<h2>{$LNG['a_custom_menu_details']}</h2>
<table class="darkbg" cellpadding="1" cellspacing="1" width="100%">
<tr class="mediumbg">
<td width="50%">{$LNG['a_custom_menu_text']}</td>
<td width="30%">{$LNG['a_custom_menu_templatetag']} <a href="#" class="vistip" title="{$LNG['a_custom_menu_templatetag_info']}">?</a></td>
<td width="10%">{$LNG['a_custom_menu_parent']} <a href="#" class="vistip" title="{$LNG['a_custom_menu_parent_info']}">?</a></td>
<td align="center">{$LNG['a_man_actions']}</td>
</tr>


EndHTML;
            $alt = '';
            $num = 0;

            $result = $DB->query("SELECT menu_id, menu_name, menu_parent, menu_weight FROM {$CONF['sql_prefix']}_menus WHERE menu_id = $menu_id ",
                __file__, __line__);
            while (list($id, $title, $parent, $weight) = $DB->fetch_array($result))
            {
                $TMPL['admin_content'] .= <<< EndHTML
<tr class="lightbg{$alt}">
<td width="50%"><input type="text" name="menu_text" size= "50" value="{$title}"/></td>
<td><input type="text" size= "15" value="{\$menu-{$id}}" readonly="readonly" /></td>
<td><input type="text" name="parent" size= "3" value="{$parent}" /></td>
<td align="center">

<div class="buttons">
    <button type="submit" name="submit" class="">
        {$LNG['a_custom_menu_save']}
    </button></form>
</div>

<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_menus&amp;menu_delete={$id}">
    <button type="submit" name="submit" class="">
        {$LNG['a_custom_menu_delete1']}
    </button>
</form>

</td>
</tr>





<script>
$(document).ready(function() {
     $("#sortme_{$id}").sortable({
          update : function () {
	         serial = $('#sortme_{$id}').sortable('serialize');

	         $.ajax({
	              url: "index.php?a=admin&b=manage_menus",
		        type: "post",
		        data: serial,
		        error: function(){
		             alert("there was an error with the sort");}
	         });
	    }
     });
});
</script>

<style>
ul#sortme_{$id} {

	margin: 0 0 10px 0px;
	padding: 0px;	
	list-style: none;
}

</style>


EndHTML;


                $result = $DB->query("SELECT id, title, path, target, sort FROM {$CONF['sql_prefix']}_menu WHERE menu_id = '{$id}' ORDER BY sort ASC",
                    __file__, __line__);
                $TMPL['admin_content'] .= '<tr><td colspan="4"><ul id="sortme_' . $id . '">';
                while (list($itemid, $title, $path, $target, $sort) = $DB->fetch_array($result))
                {
	              $title = htmlspecialchars($title,ENT_QUOTES, "UTF-8");
	              $target = stripslashes(htmlspecialchars($target,ENT_QUOTES, "UTF-8"));

                    $TMPL['admin_content'] .= <<< EndHTML


<script>
  $(document).ready(function() {

    $("#but_{$itemid}").removeClass().addClass("positive");

  });
</script>

<style>
  table#menu_{$itemid} {
	background: url(skins/admin/images/grip.png) no-repeat;
      background-position: 1% 70%;
	cursor: move;
	padding: 0px 0 0px 20px;
	margin: 0 0 10px 0px;
	border-bottom: 0px solid #000;
  }

</style>



<table id="menu_{$itemid}" width="100%">
     <form action="{$CONF['list_url']}/index.php?a=admin&b=manage_menus&update_item_id={$itemid}" method="post"> 
     

     <tr>
     <th class="tiny">Link Text</td>
     <th class="tiny">URL Path</td>
     <th class="tiny">Extra a Attributes</td>
     <th class="tiny">Update Item </td>
	 </tr>

     
     <tr>
     <td class="menu_item"><input type="text" name="new_title" value="{$title}" /></td>
     <td class="menu_item"><input type="text" name="new_path" size="50" value="{$path}" id="new_path_{$itemid}" /></td>
     <td class="menu_item"><input type="text" name="new_target" size="20" value="{$target}" /></td>
     <td class="menu_item">

    <button type="submit" name="submit" id="but_{$itemid}" class="">
        {$LNG['a_custom_menu_save']}
    </button>


		  <a href="?a=admin&b=manage_menus&menu_item_delete={$itemid}" class="negative">{$LNG['a_custom_menu_delete1']}</a>
		  </td></tr>
    </form>
</table>


EndHTML;

                }
                $TMPL['admin_content'] .= '</ul></td></tr>';


                if ($alt)
                {
                    $alt = '';
                } else
                {
                    $alt = 'alt';
                }
                $num++;
            }

            $TMPL['admin_content'] .= <<< EndHTML
</table><br />


</form>

EndHTML;

        } //ENDS BIG IF TO AVOID TABLE HEADER


    }
}
