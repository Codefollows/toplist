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

class manage_join_fields extends base{
  public function __construct(){
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_man_jf_header'];

    //Initialize a couple vars    
    $TMPL['admin_content']  = "<p><i>{$LNG['a_man_jf_intro1']}</i></p>";
    $TMPL['admin_content'] .= "<p><i>{$LNG['a_man_jf_intro2']}</i></p>";
		
    if (isset($_POST['sort']))
    {
        $sort = $_POST['sort'];
        for ($i = 0; $i < count($sort); $i++)
        {
            list($item_id, $status) = explode('|', $sort[$i]);
            $item_id = intval($item_id);
            $status  = intval($status);

            $DB->query("UPDATE {$CONF['sql_prefix']}_join_fields SET field_sort = {$i}, field_status = {$status} WHERE id = {$item_id}", __file__, __line__);
        }
    }

    if (isset($FORM['delete']))
    {
        $item_id = intval($FORM['delete']);

        list($delete_id, $delete_field_id, $delete_field_type) = $DB->fetch("SELECT id, field_id, field_type FROM {$CONF['sql_prefix']}_join_fields WHERE id = '{$item_id}'",__file__, __line__);
        $DB->query("DELETE FROM {$CONF['sql_prefix']}_join_fields WHERE id = {$delete_id}",__file__, __line__);
        $DB->query("ALTER TABLE `{$CONF['sql_prefix']}_sites` DROP `{$delete_field_id}`", __FILE__, __LINE__);
        if($delete_field_type == 'dropdown' || $delete_field_type == 'radio' || $delete_field_type == 'checkbox') {
             $DB->query("ALTER TABLE `{$CONF['sql_prefix']}_sites` DROP `{$delete_field_id}_display`", __FILE__, __LINE__);
        }

        $TMPL['admin_content'] .= "<h3>{$LNG['a_man_jf_delete_msg']}</h3><br />";
        header("refresh: 3; url={$TMPL['list_url']}/index.php?a=admin&b=manage_join_fields");
    }
        

    $TMPL['admin_content'] .= <<< EndHTML

    <style>
    .custom-column { width: 50%; float: left; padding: 5px 0; }
    .field_row { margin: 0 1em 1em 0; }
    .field_header { margin: 0.3em; padding-bottom: 4px; padding-left: 0.2em; cursor: move; }
    .field_header .ui-icon { float: right; }
    .field_content { padding: 0.4em; display: none; }
    .ui-sortable-placeholder { border: 1px dotted black; visibility: visible !important; }
    .ui-sortable-placeholder * { visibility: hidden; }
    .ui-icon-minusthick, .ui-icon-plusthick { cursor: pointer; }
    .field_active, .field_inactive { margin-bottom: 10px; }
    </style>
    <script>
    $(function() {

        $('#custom-active').sortable({
            revert: true,
            forcePlaceholderSize: true,
			connectWith: '#custom-inactive',
            handle: '.field_header',
            update : function () {

                // Set status 1 ( active ) from all div UNTIL inactive block
                $('#custom-active > .field_row').each(function() {
                    var old_id = $(this).attr('id').split('|')[0];
                    $(this).attr('id', old_id+'|1');
                });

	            serial = $('#custom-active').sortable('serialize');

                $.ajax({
	                url: 'index.php?a=admin&b=manage_join_fields',
		            type: 'post',
		            data: serial,
		            error: function(){
		                alert('there was an error with the sort');
                    }
	            });
	        }
        });
		
        $('#custom-inactive').sortable({
            revert: true,
            forcePlaceholderSize: true,
			connectWith: '#custom-active',
            handle: '.field_header',
            update : function () {

                // Set status 0 ( inactive ) from all div AFTER inactive block
                $('#custom-inactive > .field_row').each(function() {
                    var old_id = $(this).attr('id').split('|')[0];
                    $(this).attr('id', old_id+'|0');
                });

	            serial = $('#custom-inactive').sortable('serialize');

                $.ajax({
	                url: 'index.php?a=admin&b=manage_join_fields',
		            type: 'post',
		            data: serial,
		            error: function(){
		                alert('there was an error with the sort');
                    }
	            });
	        }
        });

        $('#custom-fields .field_row').slice(0,1)
            .find('.ui-icon').toggleClass('ui-icon-plusthick').toggleClass('ui-icon-minusthick')
            .end()
            .find('.field_content').toggle('slow', function() {
                $('#custom-active, #custom-inactive').css('min-height', $('#custom-fields').height());
            });

        $('.field_header').on('click', '.ui-icon', function() {
            var icon = $(this);
            var content = $(this).parent().next('.field_content');
            var outer = content.parent().parent();

            icon.toggleClass('ui-icon-plusthick').toggleClass('ui-icon-minusthick');
            content.slideToggle('slow', function() {
                if($(icon).hasClass('ui-icon-minusthick')) {
                    outer.css('min-height', outer.height());
                }
                else {
                    outer.animate({'min-height': outer.height() - content.height()}, 400);
                }
            });

        });
 
    });
    </script>

    <br /><a href="{$TMPL['list_url']}/index.php?a=admin&b=add_join_field" class="positive">{$LNG['a_man_jf_addnew']}</a><br /><br />

    <div id="custom-fields">
	
		<div class="custom-column">
			<h2 class="field_active">Active</h2>
		</div>
		<div class="custom-column">
			<h2 class="field_active">Inactive</h2>
		</div>
		
		<div style="clear:both;"></div>

		<div id="custom-active" class="custom-column">

EndHTML;

    $result = $DB->query("SELECT id, field_id, field_type, description, field_status, show_join_edit FROM {$CONF['sql_prefix']}_join_fields WHERE field_status = 1 ORDER BY field_sort ASC", __file__, __line__);
    while (list($id, $field_id, $field_type, $description, $status, $show_join_edit) = $DB->fetch_array($result))
    {
	    $description = !empty($description) ? htmlspecialchars($description, ENT_QUOTES, "UTF-8") .'<br /><br />' : '';
		
        switch($field_type) {
            case 'textbox':  $field_type = $LNG['a_man_jf_textbox']; 
                             $internal   = '';
                             $display    = ''; 
                             break;
			case 'textarea': $field_type = $LNG['a_man_jf_textarea'];
                             $internal   = '';
                             $display    = ''; 
                             break;
		    case 'dropdown': $field_type = $LNG['a_man_jf_dropdown'];
                             $internal   = " - {$LNG['a_man_jf_internal']}";
                             $display    = "<p><span class=\"ui-state-default\">{\${$field_id}_display}</span> - {$LNG['a_man_jf_display_value']}</p>"; 
                             break;
			case 'checkbox': $field_type = $LNG['a_man_jf_checkbox']; 
                             $internal   = " - {$LNG['a_man_jf_internal']}";
                             $display    = "<p><span class=\"ui-state-default\">{\${$field_id}_display}</span> - {$LNG['a_man_jf_display_value']}</p>"; 
                             break;
			case 'radio':    $field_type = $LNG['a_man_jf_radio'];
                             $internal   = " - {$LNG['a_man_jf_internal']}";
                             $display    = "<p><span class=\"ui-state-default\">{\${$field_id}_display}</span> - {$LNG['a_man_jf_display_value']}</p>"; 
                             break;
        }
		
		$form_tag_files = '';
		if ($show_join_edit == 1) {
			$form_tag_files = ', edit_form.html';
		}

        $TMPL['admin_content'] .= <<< EndHTML

			<div class="field_row ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" id="sort_{$id}|{$status}">
				<div class="field_header ui-widget-header ui-corner-all">
					<span class="ui-icon ui-icon-plusthick"></span>
					{$field_id}
				</div>
				<div class="field_content">
					<div class="buttons" style="float: right; padding-left: 10px;">
						<a href="{$CONF['list_url']}/index.php?a=admin&b=edit_join_field&id={$id}" class="positive">{$LNG['a_man_edit']}</a>
						<a href="{$CONF['list_url']}/index.php?a=admin&b=manage_join_fields&delete={$id}" class="negative" onclick="return confirmSubmit()"><img src="{$CONF['list_url']}/skins/admin/images/sm_delete.png" width="10px">{$LNG['a_man_delete']}</a>
					</div>
					{$description}
					{$LNG['g_type']}: {$field_type}<br /><br />
					
					<h3>Template Tags: Form</h3>
					<i>join_form.html, join_form_existing.html{$form_tag_files}</i>
					<p><span class="ui-state-default">{\$form_{$field_id}}</span></p>
					
					<h3>Template Tags: User selected value</h3>
					<i>For example stats.html, table_top_row.html</i>
					<p><span class="ui-state-default">{\${$field_id}}</span> {$internal}
					{$display}

				</div>
			</div>

EndHTML;

    }

    $TMPL['admin_content'] .= <<< EndHTML

		</div>

		<div id="custom-inactive" class="custom-column">

EndHTML;

    $result = $DB->query("SELECT id, field_id, field_type, description, field_status, show_join_edit FROM {$CONF['sql_prefix']}_join_fields WHERE field_status = 0 ORDER BY field_sort ASC", __file__, __line__);
    while (list($id, $field_id, $field_type, $description, $status, $show_join_edit) = $DB->fetch_array($result))
    {
	    $description = !empty($description) ? htmlspecialchars($description, ENT_QUOTES, "UTF-8") .'<br /><br />' : '';
		
        switch($field_type) {
            case 'textbox':  $field_type = $LNG['a_man_jf_textbox']; 
                             $internal   = '';
                             $display    = ''; 
                             break;
			case 'textarea': $field_type = $LNG['a_man_jf_textarea'];
                             $internal   = '';
                             $display    = ''; 
                             break;
		    case 'dropdown': $field_type = $LNG['a_man_jf_dropdown'];
                             $internal   = " - {$LNG['a_man_jf_internal']}";
                             $display    = "<p><span class=\"ui-state-default\">{\${$field_id}_display}</span> - {$LNG['a_man_jf_display_value']}</p>"; 
                             break;
			case 'checkbox': $field_type = $LNG['a_man_jf_checkbox']; 
                             $internal   = " - {$LNG['a_man_jf_internal']}";
                             $display    = "<p><span class=\"ui-state-default\">{\${$field_id}_display}</span> - {$LNG['a_man_jf_display_value']}</p>"; 
                             break;
			case 'radio':    $field_type = $LNG['a_man_jf_radio'];
                             $internal   = " - {$LNG['a_man_jf_internal']}";
                             $display    = "<p><span class=\"ui-state-default\">{\${$field_id}_display}</span> - {$LNG['a_man_jf_display_value']}</p>"; 
                             break;
        }
		
		$form_tag_files = '';
		if ($show_join_edit == 1) {
			$form_tag_files = ', edit_form.html';
		}

        $TMPL['admin_content'] .= <<< EndHTML

			<div class="field_row ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" id="sort_{$id}|{$status}">
				<div class="field_header ui-widget-header ui-corner-all">
					<span class="ui-icon ui-icon-plusthick"></span>
					{$field_id}
				</div>
				<div class="field_content">
					<div class="buttons" style="float: right; padding-left: 10px;">
						<a href="{$CONF['list_url']}/index.php?a=admin&b=edit_join_field&id={$id}" class="positive">{$LNG['a_man_edit']}</a>
						<a href="{$CONF['list_url']}/index.php?a=admin&b=manage_join_fields&delete={$id}" class="negative"><img src="{$CONF['list_url']}/skins/admin/images/sm_delete.png" width="10px">{$LNG['a_man_delete']}</a>
					</div>
					{$description}
					{$LNG['g_type']}: {$field_type}<br /><br />
					
					<h3>{$LNG['a_man_jf_tag_form']}</h3>
					<i>join_form.html, join_form_existing.html{$form_tag_files}</i>
					<p><span class="ui-state-default">{\$form_{$field_id}}</span></p>
					
					<h3>{$LNG['a_man_jf_tag_user']}</h3>
					<i>{$LNG['a_man_jf_tag_user_sub']} stats.html, table_top_row.html</i>
					<p><span class="ui-state-default">{\${$field_id}}</span> {$internal}
					{$display}

				</div>
			</div>

EndHTML;

    }

    $TMPL['admin_content'] .= <<< EndHTML

		</div>
	
		<div style="clear:both;"></div>
	
	</div>
EndHTML;


  }
}
