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

class edit extends join_edit {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_edit_header'];

    $TMPL['error_username'] = '';
    $TMPL['error_style_username'] = '';
    $TMPL['error_password'] = '';
    $TMPL['error_style_password'] = '';
    $TMPL['error_url'] = '';
    $TMPL['error_style_url'] = '';
    $TMPL['error_email'] = '';
    $TMPL['error_style_email'] = '';
    $TMPL['error_title'] = '';
    $TMPL['error_style_title'] = '';
    $TMPL['error_banner_url'] = '';
    $TMPL['error_style_banner_url'] = '';
    $TMPL['error_premium_banner_url'] = '';
    $TMPL['error_style_premium_banner_url'] = '';
    $TMPL['error_captcha'] = '';
    $TMPL['error_style_captcha'] = '';
	$TMPL['error_description'] = '';
	$TMPL['error_style_description'] = '';

	

    // custom join fields error initializing
    $result = $DB->query("SELECT field_id FROM {$CONF['sql_prefix']}_join_fields WHERE field_status = 1", __FILE__, __LINE__);
    while(list($error_field_id) = $DB->fetch_array($result)) {
        $TMPL["error_{$error_field_id}"] = '';
        $TMPL["error_style_{$error_field_id}"] = '';
    }

    // Plugin Hook - Initialize error styles
    eval (PluginManager::getPluginManager ()->pluginHooks ('join_edit_init_error'));


    $TMPL['username'] = $DB->escape($FORM['u']);
    list($check) = $DB->fetch("SELECT 1 FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
    if ($check) {
      if (!isset($FORM['submit'])) {
        $this->form();
      }
      else {
        $this->process();
      }
    }
    else {
      $this->error($LNG['g_invalid_u'], 'admin');
    }
  }

  function form() {
    global $CONF, $DB, $LNG, $TMPL;

    if (!isset($TMPL['url'])) {
      $row = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
      $TMPL = array_merge($TMPL, $row);
    }
    else {
      if (isset($TMPL['url'])) { $TMPL['url'] = stripslashes($TMPL['url']); }
      if (isset($TMPL['title'])) { $TMPL['title'] = stripslashes($TMPL['title']); }
      if (isset($TMPL['description'])) { $TMPL['description'] = stripslashes($TMPL['description']); }
      if (isset($TMPL['category'])) { $TMPL['category'] = stripslashes($TMPL['category']); }
      if (isset($TMPL['banner_url'])) { $TMPL['banner_url'] = stripslashes($TMPL['banner_url']); }
      if (isset($TMPL['premium_banner_url']) && $TMPL['premium_banner_url'] == 'NULL') {
          $TMPL['premium_banner_url'] = '';
      }
      elseif (isset($TMPL['premium_banner_url'])) {
          $TMPL['premium_banner_url'] = str_replace("'", '', stripslashes($TMPL['premium_banner_url']));
      }

	  

      if (isset($TMPL['date_start_premium'])) { 

	      $TMPL['date_start_premium'] = $TMPL['date_start_premium'] == 'NULL' ? '' : str_replace("'", '', stripslashes($TMPL['date_start_premium']));
      }

      if (isset($TMPL['email'])) { $TMPL['email'] = stripslashes($TMPL['email']); }
    }

    $TMPL['category'] = htmlspecialchars($TMPL['category'], ENT_QUOTES, "UTF-8");
    $TMPL['categories_menu'] = "<select name=\"category\" id=\"category\">\n";
    foreach ($CONF['categories'] as $cat => $skin) {
      $cat = htmlspecialchars($cat, ENT_QUOTES, "UTF-8");

      if ($TMPL['category'] == $cat) {
        $TMPL['categories_menu'] .= "<option value=\"{$cat}\" selected=\"selected\">{$cat}</option>\n";
      }
      else {
        $TMPL['categories_menu'] .= "<option value=\"{$cat}\">{$cat}</option>\n";
      }
    }
    $TMPL['categories_menu'] .= '</select>';

	

	// 2step security
	// Only if the user already has a google 2step secret key shall we display the google option.
	// Else he may be blocked out of his account, as not setup his app yet
	$TMPL['2step'] = !empty($TMPL['2step']) ? (int)$TMPL['2step'] : 0;
	if (empty($TMPL['2step_secret'])) {
		$TMPL['form_2step'] = generate_select('2step', $LNG['2step'], '0, 1', "{$LNG['2step_none']}, {$LNG['2step_email']}");
	}
	else {
		$TMPL['form_2step'] = generate_select('2step', $LNG['2step'], '0, 1, 2', "{$LNG['2step_none']}, {$LNG['2step_email']}, {$LNG['2step_google']}");
	}

    $TMPL['active_menu'] = "<select name=\"active\" id=\"active\">\n";
    $active_array = array(
        0 => $LNG['a_edit_pending'],
        1 => $LNG['a_edit_active'],
        2 => $LNG['a_edit_inactive'],
        3 => 'Temporary Inactive ( No Pv/Hits in )'
    );
    foreach ($active_array as $key => $value) {
        if ($TMPL['active'] == $key) {
            $TMPL['active_menu'] .= "<option value=\"{$key}\" selected=\"selected\">{$value}</option>\n";
        }
        else {
            $TMPL['active_menu'] .= "<option value=\"{$key}\">{$value}</option>\n";
        }
    }
    $TMPL['active_menu'] .= '</select>';

    if ($CONF['max_banner_width'] && $CONF['max_banner_height']) {
      $TMPL['join_banner_size'] = sprintf($LNG['join_banner_size'], $CONF['max_banner_width'], $CONF['max_banner_height']);
    }
    else {
      $TMPL['join_banner_size'] = '';
    }

    if ($CONF['max_premium_banner_width'] && $CONF['max_premium_banner_height']) {
      $TMPL['join_premium_banner_size'] = sprintf($LNG['join_banner_size'], $CONF['max_premium_banner_width'], $CONF['max_premium_banner_height']);
    }
    else {
      $TMPL['join_premium_banner_size'] = '';
    }

    

    if($TMPL['premium_flag'] == 1) {
    	$TMPL['premium_flag_options'] = "<option value=\"1\" selected=\"selected\">Active</option><option value=\"0\">Not Premium</option> ";
    } else {
    	$TMPL['premium_flag_options'] = "<option value=\"1\">Active</option><option value=\"0\" selected=\"selected\">Not Premium</option> ";
    }

    //initialize plugin variables
    $admin_edit_website_extra     = '';
    $admin_edit_user_extra        = '';
    $admin_edit_build_form_fields = '';


	// init/isset -> htmlspecialchars - plugin TMPL tags
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_edit_build_form'));


    $TMPL['url'] = htmlspecialchars($TMPL['url'],ENT_QUOTES, "UTF-8");
    $TMPL['title'] = htmlspecialchars($TMPL['title'],ENT_QUOTES, "UTF-8");
    $TMPL['description'] = htmlspecialchars($TMPL['description'],ENT_QUOTES, "UTF-8");
    $TMPL['banner_url'] = htmlspecialchars($TMPL['banner_url'],ENT_QUOTES, "UTF-8");
    $TMPL['premium_banner_url'] = htmlspecialchars($TMPL['premium_banner_url'],ENT_QUOTES, "UTF-8");
	$TMPL['date_start_premium'] = htmlspecialchars($TMPL['date_start_premium'],ENT_QUOTES, "UTF-8");
    $TMPL['email'] = htmlspecialchars($TMPL['email'],ENT_QUOTES, "UTF-8");

    $TMPL['owner'] = htmlspecialchars($TMPL['owner'],ENT_QUOTES, "UTF-8");

    

	// Call Plugin Template
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_edit_fields'));


    // Grab custom join fields
    $result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_join_fields WHERE field_status = 1 ORDER BY field_sort ASC", __FILE__, __LINE__);
    while($row = $DB->fetch_array($result)) {

        $display_zone = ($row['display_location'] == 'user') ? 'admin_edit_user_extra' : 'admin_edit_website_extra';

        if($row['field_type'] == 'checkbox') {
            $TMPL[$row['field_id']] = !empty($TMPL["checked_{$row['field_id']}"]) ? $TMPL["checked_{$row['field_id']}"] : explode(', ', $TMPL[$row['field_id']]);
        }
        else {
            if (isset($TMPL[$row['field_id']])) { $TMPL[$row['field_id']] = stripslashes($TMPL[$row['field_id']]); }
            $TMPL[$row['field_id']] = htmlspecialchars($TMPL[$row['field_id']], ENT_QUOTES, "UTF-8");
        }

        switch($row['field_type']) {
            case 'textbox':
                 ${$display_zone} .= generate_input($row['field_id'], $row['label_text'], $row['field_text_input_size']);
                 break;
			case 'textarea':
                 ${$display_zone} .= generate_textarea($row['field_id'], $row['label_text']);
                 break;
		    case 'dropdown':
                 ${$display_zone} .= generate_select($row['field_id'], $row['label_text'], $row['field_choice_value'], $row['field_choice_text']);
                 break;
			case 'checkbox':
                 ${$display_zone} .= generate_checkbox($row['field_id'], $row['label_text'], $row['field_choice_value'], $row['field_choice_text'], $TMPL[$row['field_id']]);
                 break;
			case 'radio':
                 ${$display_zone} .= generate_radio($row['field_id'], $row['label_text'], $row['field_choice_value'], $row['field_choice_text'], $TMPL[$row['field_id']]);
                 break;
        }

    }

    $TMPL['admin_content'] = <<<EndHTML
<form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit&amp;u={$TMPL['username']}" method="post" enctype="multipart/form-data">
<fieldset>
<legend>{$LNG['join_website']}</legend>

{$TMPL['screenshot']}

<div class="{$TMPL['error_style_url']}">
<label for="url">{$LNG['g_url']}</label>
<input type="text" name="url" id="url" size="50" value="{$TMPL['url']}" />
{$TMPL['error_url']}
</div>

<div class="{$TMPL['error_style_title']}">
<label for="title">{$LNG['g_title']}</label>
<input type="text" name="title" id="title" size="50" value="{$TMPL['title']}" />
{$TMPL['error_title']}
</div>

<div class="{$TMPL['error_style_description']}">
<label for="description">{$LNG['g_description']}</label>
<textarea cols="40" rows="5" name="description" id="description">{$TMPL['description']}</textarea>
{$TMPL['error_description']}
</div>

<label for="category">{$LNG['g_category']}</label>
{$TMPL['categories_menu']}

<div class="{$TMPL['error_style_banner_url']}">
<label for="banner_url">{$LNG['g_banner_url']} {$TMPL['join_banner_size']}</label>
<input type="text" name="banner_url" id="banner_url" size="50" value="{$TMPL['banner_url']}" />
{$TMPL['error_banner_url']}
</div>

<div class="{$TMPL['error_style_premium_banner_url']}">
<label for="premium_banner_url">{$LNG['a_edit_premium_banner_url']} {$TMPL['join_premium_banner_size']}</label>
<input type="text" name="premium_banner_url" id="premium_banner_url" size="50" value="{$TMPL['premium_banner_url']}" />
{$TMPL['error_premium_banner_url']}
</div>


<div class="{$TMPL['error_style_email']}">
<label for="email">{$LNG['g_email']}</label>
<input type="text" name="email" id="email" size="50" value="{$TMPL['email']}" />
{$TMPL['error_email']}
</div>


{$admin_edit_website_extra}


</fieldset>


<fieldset>
<legend>{$LNG['a_edit_premium_data']}</legend>

<label for="premium_flag">{$LNG['a_edit_premium_set_status']}</label>
<select name="premium_flag" id="premium_flag">
{$TMPL['premium_flag_options']}
</select>

<label for="datepicker">{$LNG['a_edit_premium_set_startdate']}</label>
<input type="text" name="date_start_premium" value="{$TMPL['date_start_premium']}" id="datepicker" />

<label for="total_day">{$LNG['a_edit_premium_set_days']}</label>
<input type="text" name="total_day" id="total_day" value="{$TMPL['total_day']}" />

<label for="remain_day">{$LNG['a_edit_premium_remain_day']}</label>
<input type="text" name="remain_day" id="remain_day" value="{$TMPL['remain_day']}" />

</fieldset>

{$admin_edit_build_form_fields}

<fieldset>
<legend>{$LNG['join_user']}</legend>

<label for="owner">{$LNG['g_owner']}</label>
<input type="text" name="owner" id="owner" size="20" value="{$TMPL['owner']}" />

{$admin_edit_user_extra}

<label for="password">{$LNG['g_password']} - {$LNG['edit_password_blank']}</label>
<input type="password" name="password" id="password" size="20" autocomplete="new-password"/>

<label for="active">{$LNG['a_edit_site_is']}</label>
{$TMPL['active_menu']}

{$TMPL['form_2step']}

<input type="hidden" name="approve" value="{$TMPL['active']}" />

</fieldset>

<br />
<input name="submit" type="submit" class="positive" value="{$LNG['a_edit_header']}" />

</form>
EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['url'] = $DB->escape($FORM['url']);
    $TMPL['title'] = $DB->escape($FORM['title']);
    $description_prepare = str_replace(array("\r\n", "\n", "\r"), ' ', $FORM['description']);
    $TMPL['description'] = $DB->escape($description_prepare);
    $TMPL['category'] = $DB->escape($FORM['category']);
    $TMPL['banner_url'] = $DB->escape($FORM['banner_url']);

	

	// Keep the duplicated variable, needed for the gif to mp4 as classes.php returns the tmpl var surrounded by quotes
    $TMPL['premium_banner_url'] = $DB->escape($FORM['premium_banner_url']);
    $premium_banner_url = $DB->escape($FORM['premium_banner_url']);

	

    $TMPL['email'] = $DB->escape($FORM['email']);
    $TMPL['active'] = intval($FORM['active']);

    $TMPL['remain_day']         = intval($FORM['remain_day']);
    $TMPL['total_day']          = intval($FORM['total_day']);
    $TMPL['date_start_premium'] = empty($FORM['date_start_premium']) ? 'NULL' : "'".$DB->escape($FORM['date_start_premium'])."'";
    $TMPL['premium_flag']       = intval($FORM['premium_flag']);

    $TMPL['2step'] = !empty($FORM['2step']) ? (int)$FORM['2step'] : 0;

    $TMPL['owner'] = $DB->escape($FORM['owner']);

    // Grab custom join fields
    $custom_fields_value = '';
	$form_validate       = array();
    $result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_join_fields WHERE field_status = 1", __FILE__, __LINE__);
    while($row = $DB->fetch_array($result)) {

        $required = 0;
        if($row['required'] == 1) { $required = 1; }

        // Prepare the TMPL vars
        if($row['field_type'] == 'checkbox') {
            $TMPL["checked_{$row['field_id']}"] = isset($FORM[$row['field_id']]) ? $FORM[$row['field_id']] : array();
            $TMPL[$row['field_id']]             = implode(', ', $TMPL["checked_{$row['field_id']}"]);
        }
        else {
            $TMPL[$row['field_id']] = isset($FORM[$row['field_id']]) ? $DB->escape($FORM[$row['field_id']], 1) : '';

            if ($row['field_type'] == 'textarea') {
                $TMPL[$row['field_id']] = str_replace(array("\r\n", "\n", "\r"), ' ', $TMPL[$row['field_id']]);
            }
        }

        // 1 line textbox validate checks
        switch($row['field_text_requirements']) {
            case 'url':
                 // Validate as url if a letter is found
                 if(preg_match('/\p{L}/u', $TMPL[$row['field_id']])) {
                     array_push($form_validate, validate_url($row['field_id'], $TMPL[$row['field_id']], $required));
                 }
                 // Else Validate as normal IP
                 else {
                     array_push($form_validate, validate_ip($row['field_id'], $TMPL[$row['field_id']], $required));
                 }
                 break;
            case 'email':
                 array_push($form_validate, validate_email($row['field_id'], $TMPL[$row['field_id']], $required));
                 break;
            case 'number':
                 array_push($form_validate, validate_number($row['field_id'], $TMPL[$row['field_id']], $required));
                 break;
            case 'int':
                 array_push($form_validate, validate_int($row['field_id'], $TMPL[$row['field_id']], $required));
                 break;
            case 'az_09':
                 array_push($form_validate, validate_preg_match($row['field_id'], $TMPL[$row['field_id']], '/^[a-zA-Z0-9\-_]+$/D', $required));
                 break;
            default:
                 if($required) {
                     array_push($form_validate, validate_only_required($row['field_id'], $TMPL[$row['field_id']]));
                 }
        }

        // text field num chars check ( textbox, textarea )
        switch($row['field_text_enable_length']) {
            case 'min':
                 array_push($form_validate, validate_min_chars($row['field_id'], $TMPL[$row['field_id']], $row['field_text_length'], $required));
                 break;
            case 'max':
                 array_push($form_validate, validate_max_chars($row['field_id'], $TMPL[$row['field_id']], $row['field_text_length'], $required));
                 break;
            case 'range':
                 list($min_chars, $max_chars) = explode('-', $row['field_text_length']);
                 array_push($form_validate, validate_range_chars($row['field_id'], $TMPL[$row['field_id']], $min_chars, $max_chars, $required));
                 break;
        }

        // Prepare Update for custom fields
        $custom_fields_value .= ", {$row['field_id']} = '{$TMPL[$row['field_id']]}'";

        // Extra Update for choice field options
        // We not only want to submit "field_choice_value" ( internal value used in <option> value for example )
        // but also the "field_choice_text", which is the visible text in dropdown box for example
        if($row['field_type'] == 'dropdown' || $row['field_type'] == 'radio' || $row['field_type'] == 'checkbox') {

            // Create array out of the comma lists
            $field_choice_value_array  = explode(', ', $row['field_choice_value']);
            $field_choice_text_array   = explode(', ', $row['field_choice_text']);

            // get key number from selected value of the internal value array $field_choice_value_array
            $field_choice_key = array_search($TMPL[$row['field_id']], $field_choice_value_array);

            // Get the display text value using $field_choice_key for $field_choice_text_array
            $field_choice_val = $field_choice_key !== false ? $DB->escape($field_choice_text_array[$field_choice_key], 1) : '';

            // For checkboxes iterate over the existing checked_array ( $TMPL["checked_{$row['field_id']}"] )
            if($row['field_type'] == 'checkbox') {
                $field_choice_temp = array();
                foreach($TMPL["checked_{$row['field_id']}"] as $checked) {
                    $field_choice_key = array_search($checked, $field_choice_value_array);
                    $field_choice_temp[] = $field_choice_text_array[$field_choice_key];
                }
                $field_choice_val = $DB->escape(implode(', ', $field_choice_temp), 1);
            }

            $custom_fields_value .= ", `{$row['field_id']}_display` = '{$field_choice_val}'";
        }

    }


    // Plugin Hook - Proccess form data
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_edit_process_form'));

	$TMPL['username_url_check'] = $TMPL['username'];

    if ($this->check_input('edit') && !in_array(0, $form_validate)) {

	  if (isset($FORM['password']) && mb_strlen($FORM['password']) > 0)
	  {
		$password = md5($FORM['password']);
        $password_sql = ", password = '{$password}'";

		// Invalidate all but logged in user sid sessions
		$DB->query("DELETE FROM {$CONF['sql_prefix']}_sessions WHERE type = 'user_cp' AND data = '{$TMPL['username']}'", __FILE__, __LINE__);
      }
      else {
        $password_sql = '';
      }

      require_once("{$CONF['path']}/sources/in.php");
      $short_url = in::short_url($TMPL['url']);


	  // Banner width/height
	  // mp4 to gif
	  $banner_width_height         = '';
	  $premium_banner_width_height = '';
	  $mp4_url_sql                 = '';
	  $premium_mp4_url_sql         = '';

	  // Normal banner
	  if (!empty($TMPL['banner_url']) && $TMPL['banner_url'] != $CONF['default_banner'])
	  {
		list($old_banner_url, $old_video_url) = $DB->fetch("SELECT banner_url, mp4_url FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);

		if ($TMPL['banner_url'] != $old_banner_url)
		{
			// Reset mp4
			$mp4_url_sql = "mp4_url = 'NULL',";

			// Hosted on list url or external image?
			$local_image = stripos($TMPL['banner_url'], $CONF['list_url']) === 0 ? true : false;

			if ($local_image === true)
			{
				// Replace list url with path
				$image_path = str_replace($CONF['list_url'], $CONF['path'], $TMPL['banner_url']);

				// Check if file is still on filesystem
				if (file_exists($image_path))
				{
					$banner_size = getimagesize($image_path);

					if (!empty($banner_size))
					{
						$banner_width  = (int)$banner_size[0];
						$banner_height = (int)$banner_size[1];

						$banner_width_height = "banner_width = {$banner_width}, banner_height = {$banner_height},";

						// gif to video conversion using ffmpeg for smaller filesizes
						// params - username, image url, old video url, save in same dir, premium
						$video = $this->ffmpeg_convert_image($TMPL['username'], $TMPL['banner_url'], $old_video_url, false, false);
			
						if (!empty($video))
						{
							$video_url_sql = $DB->escape($video['url'], 1);
							
							$mp4_url_sql = "mp4_url = '{$video_url_sql}',";
						}
					}
				}
			}
			else
			{
				// External file, mostly aardvark upgraders
				if (ini_get('allow_url_fopen'))
				{
					$banner_size = @getimagesize($TMPL['banner_url']);

					if (!empty($banner_size))
					{
						$banner_width  = (int)$banner_size[0];
						$banner_height = (int)$banner_size[1];

						$banner_width_height = "banner_width = {$banner_width}, banner_height = {$banner_height},";
					}
				}
			}
		}
	  }

	  // Premium banner
	  // classes.php returns premium banner as string in quotes ( due null possibility ), so use a duplicated variable instead which was definded at top of this function instead the $TMPL var
	  if (!empty($premium_banner_url) && $premium_banner_url != $CONF['default_banner'])
	  {
		list($premium_old_banner_url, $premium_old_video_url) = $DB->fetch("SELECT premium_banner_url, premium_mp4_url FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);

		if ($premium_banner_url != $premium_old_banner_url)
		{
			// Reset mp4
			$premium_mp4_url_sql = "premium_mp4_url = 'NULL',";

			// Hosted on list url or external image?
			$premium_local_image = stripos($premium_banner_url, $CONF['list_url']) === 0 ? true : false;

			if ($premium_local_image === true)
			{
				// Replace list url with path
				$premium_image_path = str_replace($CONF['list_url'], $CONF['path'], $premium_banner_url);

				// Check if file is still on filesystem
				if (file_exists($premium_image_path))
				{
					$premium_banner_size = getimagesize($premium_image_path);

					if (!empty($premium_banner_size))
					{
						$premium_banner_width  = (int)$premium_banner_size[0];
						$premium_banner_height = (int)$premium_banner_size[1];

						$premium_banner_width_height = "premium_banner_width = {$premium_banner_width}, premium_banner_height = {$premium_banner_height},";

						// gif to video conversion using ffmpeg for smaller filesizes
						// params - username, image url, old video url, save in same dir, premium
						$video = $this->ffmpeg_convert_image($TMPL['username'], $TMPL['premium_banner_url'], $premium_old_video_url, false, true);
			
						if (!empty($video))
						{
							$video_url_sql = $DB->escape($video['url'], 1);
							
							$premium_mp4_url_sql = "premium_mp4_url = '{$video_url_sql}',";
						}
					}
				}
			}
			else
			{
				// External file, mostly aardvark upgraders
				if (ini_get('allow_url_fopen'))
				{
					$premium_banner_size = @getimagesize($premium_banner_url);

					if (!empty($premium_banner_size))
					{
						$premium_banner_width  = (int)$premium_banner_size[0];
						$premium_banner_height = (int)$premium_banner_size[1];

						$premium_banner_width_height = "premium_banner_width = {$premium_banner_width}, premium_banner_height = {$premium_banner_height},";
					}
				}
			}
		}
	  }


	  // Update member
      $DB->query("UPDATE {$CONF['sql_prefix']}_sites SET
			url = '{$TMPL['url']}',
			short_url = '{$short_url}',
			title = '{$TMPL['title']}',
			description = '{$TMPL['description']}',
			category = '{$TMPL['category']}',
			banner_url = '{$TMPL['banner_url']}',
			{$banner_width_height}
			{$mp4_url_sql}
			premium_banner_url = {$TMPL['premium_banner_url']},
			{$premium_banner_width_height}
			{$premium_mp4_url_sql}
			email = '{$TMPL['email']}',
			remain_day = '{$TMPL['remain_day']}',
			total_day = '{$TMPL['total_day']}',
			premium_flag = '{$TMPL['premium_flag']}',
			date_start_premium = {$TMPL['date_start_premium']},
			owner = '{$TMPL['owner']}',
			active = {$TMPL['active']},
			2step = {$TMPL['2step']}
			{$custom_fields_value}
			{$password_sql}
	  WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);


      // Plugin Hook - Update data
      eval (PluginManager::getPluginManager ()->pluginHooks ('admin_edit_update_data'));


      $TMPL['admin_content'] = $LNG['a_edit_edited'];

      if (isset($FORM['approve']) && $FORM['approve'] == 0) {

          // Member was pending and were switched to active
          if ($TMPL['active'] > 0) {

              if ($CONF['google_friendly_links']) {
                  $TMPL['verbose_link'] = "";
              }
              else {
                  $TMPL['verbose_link'] = "index.php?a=in&u={$TMPL['username']}";
              }
              $TMPL['link_code'] = $this->do_skin('link_code');

              $LNG['join_welcome'] = sprintf($LNG['join_welcome'], $TMPL['list_name']);

              $join_email = new skin('join_email');
              $join_email->send_email($TMPL['email']);

          }

          // Member was listed as pending, redirect back to approve page
          header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=approve");
      }
      else {
          header("refresh:1; url={$TMPL['list_url']}/index.php?a=admin&b=edit&u={$TMPL['username']}");
      }

    }
    else {
      $this->form();
    }
  }

  function getExtension($str) {
    $i = strrpos($str,".");
    if (!$i) { return ""; }
    $l = strlen($str) - $i;
    $ext = substr($str,$i+1,$l);
    return $ext;
  }

}
