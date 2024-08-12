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

class add_join_field extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = 'Add New Join Field';

    $TMPL['error_top'] = '';
    $TMPL['error_style_top'] = '';
    $TMPL['error_field_id'] = '';
    $TMPL['error_style_field_id'] = '';
    $TMPL['error_label_text'] = '';
    $TMPL['error_style_label_text'] = '';
    $TMPL['error_field_text_length'] = '';
    $TMPL['error_style_field_text_length'] = '';
    $TMPL['error_field_text_input_size'] = '';
    $TMPL['error_style_field_text_input_size'] = '';
    $TMPL['error_field_choice_value_0'] = '';
    $TMPL['error_style_field_choice_value_0'] = '';

    if (!isset($FORM['submit'])) {
      $this->form();
    }
    else {
      $this->process();
    }
  }

  function form() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['jquery_new_input'] = "
                                var hide = $('#min, #max, #range');
                                $(hide).hide();
                                $('input[name^=\"field_text_enable_length\"]').change(function() {
                                    var selected = $(this).val();
                                    $('#field_text_length').val('');
                                    if(selected == 'none') {
                                        $(hide).hide();
                                    }
                                    else {
                                        $('#' + selected).show();
                                        $('#' + selected).siblings().hide();
                                    }
                                });

                                $('#add_input').on('click', function(e) {
                                    $('<p><input type=\"text\" size=\"50\" name=\"field_choice_value[]\" value=\"\" placeholder=\"{$LNG['a_man_jf_value']}\" /> <input type=\"text\" size=\"50\" name=\"field_choice_text[]\" value=\"\" placeholder=\"{$LNG['a_man_jf_displaytext']}\" /> <a href=\"#\" class=\"remove_input\">{$LNG['a_man_jf_remove']}</a></p>').appendTo('#input_area');

                                    e.preventDefault();
                                });

                                $('#tabs').on('click', '.remove_input', function(e) {
                                    $(this).parents('p').remove();
                                    e.preventDefault();
                                });
    ";

    // Field type radio buttons
    $a = 0;
	$field_type_menu = '';
    $field_type = array(
	                    'textbox' => $LNG['a_man_jf_textbox'],
						'textarea' => $LNG['a_man_jf_textarea'],
						'dropdown' => $LNG['a_man_jf_dropdown'],
						'checkbox' => $LNG['a_man_jf_checkbox'],
						'radio' => $LNG['a_man_jf_radio']
				        );
    foreach ($field_type as $key => $value)
	{
        $checked = (empty($TMPL['field_type']) && $a == 0 || isset($TMPL['field_type']) && $TMPL['field_type'] == $key) ? 'checked="checked"' : '';

        $field_type_menu .= "<input type=\"radio\" name=\"field_type\" value=\"{$key}\" {$checked} />{$value}<br />\n";
        $a++;
    }

    // Required Checkbox
    if (isset($TMPL['required']) && $TMPL['required'] == 1) {
       $required_menu = '<input type="checkbox" name="required" id="required" checked="checked" />';
    }
	else {
       $required_menu = '<input type="checkbox" name="required" id="required" />';
    }

    // Textbox requirements radio buttons
    $b = 0;
	$requirements_menu = '';
    $field_text_requirements = array(
		'none' => $LNG['a_man_jf_none'],
		'url' => $LNG['a_man_jf_url'],
		'email' => $LNG['g_email'],
		'number' => $LNG['a_man_jf_number'],
		'int' => $LNG['a_man_jf_int'],
		'az_09' => $LNG['a_man_jf_az_09']
	);
    foreach ($field_text_requirements as $key => $value)
	{
        $checked = (empty($TMPL['field_text_requirements']) && $b == 0 || isset($TMPL['field_text_requirements']) && $TMPL['field_text_requirements'] == $key) ? 'checked="checked"' : '';

        $requirements_menu .= "<input type=\"radio\" name=\"field_text_requirements\" value=\"{$key}\" {$checked} />{$value}<br />\n";
        $b++;
    }

    // Textbox limit characters radio buttons
	$c = 0;
	$text_length_menu = '';
    $text_length = array(
	                     'none' => $LNG['a_man_jf_none1'],
						 'min' => $LNG['a_man_jf_min'],
					     'max' => $LNG['a_man_jf_max'],
					     'range' => $LNG['a_man_jf_range']
				         );
    foreach ($text_length as $key => $value)
	{
        $checked = (empty($TMPL['field_text_enable_length']) && $c == 0 || isset($TMPL['field_text_enable_length']) && $TMPL['field_text_enable_length'] == $key) ? 'checked="checked"' : '';

        $text_length_menu .= "<input type=\"radio\" name=\"field_text_enable_length\" value=\"{$key}\" {$checked} />{$value}<br />\n";
        $c++;
    }

    // Display location radio buttons
	$d = 0;
	$location_menu = '';
    $location = array('user' => $LNG['a_man_jf_user'], 'website' => $LNG['a_man_jf_website']);
    foreach ($location as $key => $value)
	{
        $checked = empty($TMPL['display_location']) && $d == 1 || isset($TMPL['display_location']) && $TMPL['display_location'] == $key ? 'checked="checked"' : '';

        $location_menu .= "<input type=\"radio\" name=\"display_location\" value=\"{$key}\" {$checked} />{$value}<br />\n";
        $d++;
    }

    // Show on register and edit form checkbox
	$show_on_join_menu = '';
    $show_on_join_options = array(1 => $LNG['a_man_jf_show_on_join_option1'], 0 => $LNG['a_man_jf_show_on_join_option2']);
    foreach ($show_on_join_options as $key => $value)
	{
        $checked = !isset($TMPL['show_join_edit']) && $key == 1 || isset($TMPL['show_join_edit']) && $TMPL['show_join_edit'] == $key ? 'checked="checked"' : '';

        $show_on_join_menu .= "<input type=\"radio\" name=\"show_join_edit\" value=\"{$key}\" {$checked} />{$value}<br />\n";
    }


    $TMPL['field_id']              = isset($TMPL['field_id']) ? stripslashes($TMPL['field_id']) : '';
    $TMPL['label_text']            = isset($TMPL['label_text']) ? stripslashes($TMPL['label_text']) : '';
    $TMPL['description']           = isset($TMPL['description']) ? stripslashes($TMPL['description']) : '';
    $TMPL['field_text_length']     = isset($TMPL['field_text_length']) ? stripslashes($TMPL['field_text_length']) : '';
    $TMPL['field_text_input_size'] = isset($TMPL['field_text_input_size']) ? stripslashes($TMPL['field_text_input_size']) : '';
    $TMPL['field_choice_value']    = isset($TMPL['field_choice_value']) ? stripslashes($TMPL['field_choice_value']) : '';
    $TMPL['field_choice_text']     = isset($TMPL['field_choice_text']) ? stripslashes($TMPL['field_choice_text']) : '';

    $choice_value_array            = !empty($TMPL['field_choice_value']) ? explode(', ', $TMPL['field_choice_value']) : '';
    $choice_text_array             = !empty($TMPL['field_choice_text']) ? explode(', ', $TMPL['field_choice_text']) : '';
    $choice_value                  = !empty($TMPL['field_choice_value']) ? $choice_value_array[0] : '';
    $choice_text                   = !empty($TMPL['field_choice_text']) ? $choice_text_array[0] : '';
    $TMPL['count']                 = isset($TMPL['count']) ? $TMPL['count'] : '0';

    // Extra choice field inputs
    // In case of errors so user see same amount as before hitting submit
    $extra_choices = '';
    for ($i = 1; $i < $TMPL['count']; $i++) {
      $TMPL['error_field_choice_value_'.$i] = '';
      $TMPL['error_style_field_choice_value_'.$i] = '';

        $extra_choices .= '<p class="'.$TMPL['error_style_field_choice_value_'.$i].'">
                             <input type="text" size="50" name="field_choice_value[]" value="'.$choice_value_array[$i].'" placeholder="'.$LNG['a_man_jf_value'].'" />
                             <input type="text" size="50" name="field_choice_text[]" value="'.$choice_text_array[$i].'" placeholder="'.$LNG['a_man_jf_displaytext'].'" />
                             <a href="#" class="remove_input">'.$LNG['a_man_jf_remove'].'</a>
                             '.$TMPL['error_field_choice_value_'.$i].'
                          </p>';
    }


    // Html Output
    $TMPL['admin_content'] = <<<EndHTML

<br />
<p class="{$TMPL['error_style_top']}">{$TMPL['error_top']}</p>

<form action="index.php?a=admin&amp;b=add_join_field" method="post">

    <div id="tabs">
	    <ul>
		    <li><a href="#tabs-1">{$LNG['a_man_jf_tab1']}</a></li>
		    <li><a href="#tabs-2">{$LNG['a_man_jf_tab2']}</a></li>
		    <li><a href="#tabs-3">{$LNG['a_man_jf_tab3']}</a></li>
		    <li><a href="#tabs-4">{$LNG['a_man_jf_tab4']}</a></li>
	    </ul>

        <div id="tabs-1">
            <div class="{$TMPL['error_style_field_id']}"><label for="field_id">{$LNG['a_man_jf_field_id']}</label>
                <input type="text" name="field_id" id="field_id" size="50" value="{$TMPL['field_id']}" /><br />
	            {$LNG['a_man_jf_field_id_info']}
				{$TMPL['error_field_id']}
	        </div>

            <div class="{$TMPL['error_style_label_text']}"><label for="label_text">{$LNG['a_man_jf_label_text']}</label>
                <input type="text" name="label_text" id="label_text" size="50" value="{$TMPL['label_text']}" /><br />
				{$LNG['a_man_jf_label_text_info']}
				{$TMPL['error_label_text']}
            </div>

            <label for="description">{$LNG['g_description']}</label>
                <textarea name="description" id="description" rows="3" cols="50">{$TMPL['description']}</textarea><br />
				{$LNG['a_man_jf_label_desc_info']}
            <br /><br />

            {$LNG['a_man_jf_field_type']}<br />
            {$field_type_menu}

            <label for="required">{$LNG['a_man_jf_required']}</label> {$required_menu}
	    </div>

		<div id="tabs-2">
		    <h3>{$LNG['a_man_jf_requirements']}</h3>
            {$requirements_menu}
            <br /><br />

		    <h3>{$LNG['a_man_jf_char_requirements']}</h3>
            {$text_length_menu}<br />
			<div>
                <div id="min">{$LNG['a_man_jf_char_min']}</div>
                <div id="max">{$LNG['a_man_jf_char_max']}</div>
                <div id="range">{$LNG['a_man_jf_char_range']}</div>
			</div>
            <div class="{$TMPL['error_style_field_text_length']}">
				<input type="text" name="field_text_length" id="field_text_length" size="10" value="{$TMPL['field_text_length']}" placeholder="{$LNG['a_man_jf_not_set']}" />
				{$TMPL['error_field_text_length']}
		    </div>

            <div class="{$TMPL['error_style_field_text_input_size']}"><label for="field_text_input_size">{$LNG['a_man_jf_input_size']}</label>
                <input type="text" name="field_text_input_size" id="field_text_input_size" size="20" value="{$TMPL['field_text_input_size']}" placeholder="50" />
				{$TMPL['error_field_text_input_size']}
            </div>
		</div>

		<div id="tabs-3">
		    <h3>{$LNG['a_man_jf_add_choice_h3']}</h3><br />
		    <div id="input_area">
		        {$LNG['a_man_jf_add_choice_info']}<br />
				  <p class="{$TMPL['error_style_field_choice_value_0']}">
				    <input type="text" name="field_choice_value[]" size="50" value="{$choice_value}" placeholder="{$LNG['a_man_jf_value']}" />
                    <input type="text" name="field_choice_text[]" size="50" value="{$choice_text}" placeholder="{$LNG['a_man_jf_displaytext']}" />
					{$TMPL['error_field_choice_value_0']}
				  </p>
				  {$extra_choices}
			</div>
			<br /><br />
            <div class="buttons">
                <a href="#" id="add_input" class="positive">{$LNG['a_man_jf_add_choice']}</a>
            </div><br /><br />
		</div>

		<div id="tabs-4">
            {$LNG['a_man_jf_display_info']}<br />
            {$location_menu}

            <label for="show_join_edit">{$LNG['a_man_jf_show_on_join']}</label> {$show_on_join_menu}

		</div>

    </div>

    <div class="buttons">
        <button type="submit" name="submit" class="positive">{$LNG['a_man_jf_addnew']}</button>
    </div>

</form>


EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['field_id'] = $DB->escape($FORM['field_id'], 1);
	$TMPL['label_text'] = $DB->escape($FORM['label_text'], 1);
	$TMPL['description'] = $DB->escape($FORM['description'], 1);
	$TMPL['field_type'] = $DB->escape($FORM['field_type'], 1);
	if(isset($FORM['required']) && $FORM['required'] == 'on') {$TMPL['required'] = 1;} else {$TMPL['required'] = 0;}
	$TMPL['field_text_requirements'] = $DB->escape($FORM['field_text_requirements'], 1);
	$TMPL['field_text_enable_length'] = $DB->escape($FORM['field_text_enable_length'], 1);
	$TMPL['field_text_length'] = $DB->escape($FORM['field_text_length'], 1);
	$TMPL['field_text_input_size'] = !empty($FORM['field_text_input_size']) ? intval($FORM['field_text_input_size']) : 50;
    $TMPL['count'] = count($FORM['field_choice_value']);
	$TMPL['field_choice_value'] = $DB->escape(implode(', ', $FORM['field_choice_value']), 1);
    $FORM['field_choice_text'] = str_replace(',', '', $FORM['field_choice_text']); // Remove commas, so nothing gets fu**** up
	$TMPL['field_choice_text'] = $DB->escape(implode(', ', $FORM['field_choice_text']), 1);
	$TMPL['display_location'] = $DB->escape($FORM['display_location'], 1);
	$TMPL['show_join_edit'] = !empty($FORM['show_join_edit']) ? 1 : 0;

	// Call different validation methods, returns error on fail
	// See sources/misc/validate.php for possible choices
	$form_validate = array(
                           validate_preg_match('field_id', $TMPL['field_id'], '/^[a-z0-9_]+$/D', 1),
                           validate_max_chars('field_id', $TMPL['field_id'], 30, 1),
                           validate_db_duplicate('field_id', 'acf_'.$TMPL['field_id'], array('field_id'), 'join_fields'),
                           validate_number('field_text_input_size', $TMPL['field_text_input_size']),
                           validate_preg_match('field_text_length', $TMPL['field_text_length'], '/^[0-9\-]+$/D'),
					      );
    // Push additional validation into $form_validate
    foreach ($FORM['field_choice_value'] as $key => $value)
    {
      if(!empty($value))
      {
        array_push($form_validate, validate_preg_match('field_choice_value_'.$key, $value, '/^[a-zA-Z0-9\-_]+$/D'));
      }
    }

    if(!in_array(0, $form_validate))
    {
        $DB->query("INSERT INTO {$CONF['sql_prefix']}_join_fields (field_id, label_text, description, field_type, required, field_text_requirements, field_text_enable_length, field_text_length, field_text_input_size, field_choice_value, field_choice_text, display_location, show_join_edit, field_sort)
                    VALUES ('acf_{$TMPL['field_id']}', '{$TMPL['label_text']}', '{$TMPL['description']}', '{$TMPL['field_type']}', {$TMPL['required']}, '{$TMPL['field_text_requirements']}', '{$TMPL['field_text_enable_length']}', '{$TMPL['field_text_length']}', {$TMPL['field_text_input_size']}, '{$TMPL['field_choice_value']}', '{$TMPL['field_choice_text']}', '{$TMPL['display_location']}', {$TMPL['show_join_edit']}, 255)", __FILE__, __LINE__);

        switch($TMPL['field_type']) {
            case 'textarea': $column_type = "TEXT default '' NOT NULL";
                             $extra_column = "";
                             break;
            case 'dropdown': $column_type = "VARCHAR(255) default '' NOT NULL";
                             $extra_column = ", ADD `acf_{$TMPL['field_id']}_display` {$column_type}";
                             break;
            case 'checkbox': $column_type = "VARCHAR(255) default '' NOT NULL";
                             $extra_column = ", ADD `acf_{$TMPL['field_id']}_display` {$column_type}";
                             break;
            case 'radio':    $column_type = "VARCHAR(255) default '' NOT NULL";
                             $extra_column = ", ADD `acf_{$TMPL['field_id']}_display` {$column_type}";
                             break;
            default        : $column_type = "VARCHAR(255) default '' NOT NULL";
                             $extra_column = "";
        }

        $DB->query("ALTER TABLE `{$CONF['sql_prefix']}_sites` ADD `acf_{$TMPL['field_id']}` {$column_type} {$extra_column}", __FILE__, __LINE__);

        $TMPL['admin_content'] = $LNG['a_man_jf_add_success'];
        header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=manage_join_fields");
	}
    else {
	    $this->form();
	}

  }

}
