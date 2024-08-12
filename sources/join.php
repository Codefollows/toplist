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

class join extends join_edit {
  public function __construct() {
    global $DB, $FORM, $CONF, $LNG, $TMPL;
	
	if ($CONF['clean_url'] == 1 && preg_match('/\?/', $_SERVER['REQUEST_URI'])) 
	{
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$CONF['list_url']}/join/");
		exit;
	}
			
    $TMPL['header'] = $LNG['join_header'];

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
    $TMPL['error_top'] = '';
    $TMPL['error_style_top'] = '';
    $TMPL['error_captcha'] = '';
    $TMPL['error_style_captcha'] = '';
    $TMPL['error_question'] = '';
    $TMPL['error_style_question'] = '';
    $TMPL['error_terms'] = '';
    $TMPL['error_style_terms'] = '';	

    // custom join fields error initializing
    $result = $DB->query("SELECT field_id FROM {$CONF['sql_prefix']}_join_fields WHERE show_join_edit = 1 AND field_status = 1", __FILE__, __LINE__);
    while(list($error_field_id) = $DB->fetch_array($result)) {
        $TMPL["error_{$error_field_id}"] = '';    
        $TMPL["error_style_{$error_field_id}"] = '';
    }

    // Plugin Hook - Initialize error styles
    eval (PluginManager::getPluginManager ()->pluginHooks ('join_edit_init_error'));


    if (!isset($FORM['submit'])) {
      $this->form();
    }
    else {
      $this->process();
    }
  }

  function form() {
    global $DB, $CONF, $FORM, $LNG, $TMPL;
    
    // Display the CAPTCHA?
    if ($CONF['captcha']) {
      $TMPL['rand'] = rand(1, 1000000);
      $TMPL['join_captcha'] = $this->do_skin('join_captcha');
    }
    else {
      $TMPL['join_captcha'] = '';
    }

    if ($CONF['recaptcha']) {
 	    $TMPL['recaptcha_sitekey'] = $CONF['recaptcha_sitekey'];
		$TMPL['join_recaptcha'] = $this->do_skin('join_recaptcha');
	}
    else {
      $TMPL['join_recaptcha'] = '';
    }

    
	
    // Display the security question?
    if ($CONF['security_question'] != '' && $CONF['security_answer'] != '') {
      $TMPL['security_question'] = $CONF['security_question'];
      if (isset($FORM['security_answer'])) { $TMPL['security_answer'] = strip_tags($FORM['security_answer']); }
      else { $TMPL['security_answer'] = ''; }

      $TMPL['join_question'] = $this->do_skin('join_question');
    }
    else {
      $TMPL['join_question'] = '';
    }

    if (isset($TMPL['category'])) {
        $TMPL['category'] = htmlspecialchars(stripslashes($TMPL['category']), ENT_QUOTES, "UTF-8");
    }
    $TMPL['categories_menu'] = "<select name=\"category\" id=\"join_category\" class=\"form-control\">\n";
    foreach ($CONF['categories'] as $cat => $skin) {
      $cat = htmlspecialchars($cat, ENT_QUOTES, "UTF-8");

      if (isset($TMPL['category']) && $TMPL['category'] == $cat) {
        $TMPL['categories_menu'] .= "<option value=\"{$cat}\" selected=\"selected\">{$cat}</option>\n";
      }
      else {
        $TMPL['categories_menu'] .= "<option value=\"{$cat}\">{$cat}</option>\n";
      }
    }
    $TMPL['categories_menu'] .= "</select>";

    if ($CONF['max_banner_width'] && $CONF['max_banner_height']) {
      $TMPL['join_banner_size'] = sprintf($LNG['join_banner_size'], $CONF['max_banner_width'], $CONF['max_banner_height']);
    }
    else {
      $TMPL['join_banner_size'] = '';
    }

    //initialize plugin variables
    $TMPL['join_security_extra'] = '';
	
	// init/isset -> stripslashes - plugin TMPL tags
    eval (PluginManager::getPluginManager ()->pluginHooks ('join_fields'));

    $TMPL['username']    = isset($TMPL['username'])    ? htmlspecialchars(stripslashes($TMPL['username']), ENT_QUOTES, "UTF-8") : ''; 
    $TMPL['url']         = isset($TMPL['url'])         ? htmlspecialchars(stripslashes($TMPL['url']), ENT_QUOTES, "UTF-8") : ''; 
    $TMPL['title']       = isset($TMPL['title'])       ? htmlspecialchars(stripslashes($TMPL['title']), ENT_QUOTES, "UTF-8") : ''; 
    $TMPL['description'] = isset($TMPL['description']) ? htmlspecialchars(stripslashes($TMPL['description']), ENT_QUOTES, "UTF-8") : ''; 
    $TMPL['banner_url']  = isset($TMPL['banner_url'])  ? htmlspecialchars(stripslashes($TMPL['banner_url']), ENT_QUOTES, "UTF-8") : 'http://'; 
    $TMPL['email']       = isset($TMPL['email'])       ? htmlspecialchars(stripslashes($TMPL['email']), ENT_QUOTES, "UTF-8") : ''; 

    // Grab custom join fields
    $result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_join_fields WHERE show_join_edit = 1 AND field_status = 1 ORDER BY field_sort ASC", __FILE__, __LINE__);
    while($row = $DB->fetch_array($result)) {

        $required = 0;
        if($row['required'] == 1) { $required = 1; }

        $form_tag = "form_{$row['field_id']}";

        if($row['field_type'] == 'checkbox') {
            $TMPL[$row['field_id']] = !empty($TMPL["checked_{$row['field_id']}"]) ? $TMPL["checked_{$row['field_id']}"] : array();	
        }
        else {
            $TMPL[$row['field_id']] = isset($TMPL[$row['field_id']]) ? htmlspecialchars(stripslashes($TMPL[$row['field_id']]), ENT_QUOTES, "UTF-8") : ''; 
        }
 
        switch($row['field_type']) {
            case 'textbox':   
                 $TMPL[$form_tag] = generate_input($row['field_id'], $row['label_text'], $row['field_text_input_size'], $required);
                 break;
			case 'textarea':   
                 $TMPL[$form_tag] = generate_textarea($row['field_id'], $row['label_text'], $required);
                 break;
		    case 'dropdown': 
                 $TMPL[$form_tag] = generate_select($row['field_id'], $row['label_text'], $row['field_choice_value'], $row['field_choice_text']);
                 break;
			case 'checkbox':   
                 $TMPL[$form_tag] = generate_checkbox($row['field_id'], $row['label_text'], $row['field_choice_value'], $row['field_choice_text'], $TMPL[$row['field_id']], $required);
                 break;
			case 'radio': 
                 $TMPL[$form_tag] = generate_radio($row['field_id'], $row['label_text'], $row['field_choice_value'], $row['field_choice_text'], $TMPL[$row['field_id']], $required);			
                 break;
        }

    }

	// Call Plugin Template	
    eval (PluginManager::getPluginManager ()->pluginHooks ('join_build_form'));

	
    if(!isset($altjoin)) {
        $TMPL['content'] = $this->do_skin('join_form');
    }
	 
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['username']    = isset($FORM['u']) ? $DB->escape($FORM['u'], 1) : '';
    $TMPL['url']         = isset($FORM['url']) ? $DB->escape($FORM['url'], 1) : '';
    $TMPL['title']       = isset($FORM['title']) ? $DB->escape($FORM['title'], 1) : '';
    $description_prepare = isset($FORM['description']) ? str_replace(array("\r\n", "\n", "\r"), ' ', $FORM['description']) : '';
    $TMPL['description'] = $DB->escape($description_prepare, 1);
    $TMPL['category']    = isset($FORM['category']) ? $DB->escape($FORM['category'], 1) : '';
    $TMPL['email']       = isset($FORM['email']) ? $DB->escape($FORM['email'], 1) : '';

    $TMPL['title'] = $this->bad_words($TMPL['title']);
    $TMPL['description'] = $this->bad_words($TMPL['description']);

    $TMPL['banner_url'] = '';
    
	
    // Grab custom join fields
    $custom_fields       = '';
    $custom_fields_value = '';
	$form_validate       = array();
    $result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_join_fields WHERE show_join_edit = 1 AND field_status = 1", __FILE__, __LINE__);
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

        // Prepare Insert for custom fields
        $custom_fields .= ', '.$row['field_id'];
        $custom_fields_value .= ", '{$TMPL[$row['field_id']]}'";

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

            $custom_fields .= ", {$row['field_id']}_display";
            $custom_fields_value .= ", '{$field_choice_val}'";
        }

    }
    

    // Plugin Hook - Proccess form data
    eval (PluginManager::getPluginManager ()->pluginHooks ('join_process_form'));


    if ($this->check_ban('join')) {
      if ($this->check_input('join') && !in_array(0, $form_validate)) {
        $password = md5($FORM['password']);

        require_once("{$CONF['path']}/sources/in.php");
        $short_url = in::short_url($TMPL['url']);

        $join_date = date('Y-m-d H:i:s', time() + (3600*$CONF['time_offset']));

        $user_ip = $DB->escape($_SERVER['REMOTE_ADDR'], 1);

        $DB->query("INSERT INTO {$CONF['sql_prefix']}_sites 
                    (username, password, url, short_url, title, description, category, banner_url, email, active, openid, user_ip,owner {$custom_fields})
                    VALUES ('{$TMPL['username']}', '{$password}', '{$TMPL['url']}', '{$short_url}', '{$TMPL['title']}', '{$TMPL['description']}', '{$TMPL['category']}', '{$TMPL['banner_url']}', '{$TMPL['email']}', {$CONF['active_default']}, 0, '{$user_ip}','{$TMPL['username']}' 
                    {$custom_fields_value})", __FILE__, __LINE__);

        $DB->query("INSERT INTO {$CONF['sql_prefix']}_stats (username, join_date) VALUES ('{$TMPL['username']}', '{$join_date}')", __FILE__, __LINE__);


        // Plugin Hook - Insert data
        eval (PluginManager::getPluginManager ()->pluginHooks ('join_insert_data'));
 

 		// Default, non friendly vote code is used
		// Only use friendly code if google_friendly_links is enabled 
		// And list is using https OR list is using http and member is using http
		// As https -> http not pass referrer url
	    $list_scheme          = parse_url($CONF['list_url'], PHP_URL_SCHEME);
	    $user_scheme          = parse_url($TMPL['url'], PHP_URL_SCHEME);
        $TMPL['verbose_link'] = "index.php?a=in&u={$TMPL['username']}";

        if ($CONF['google_friendly_links'] && ($list_scheme == 'https' || $list_scheme == $user_scheme))
		{
			$TMPL['verbose_link'] = "";
        }       
        
        // Link Codes alt text
        include('button_config.php');
        $TMPL['text_link_button_alt'] = $CONF['text_link_button_alt'];

        // Link Code types
	    $TMPL['button_username']       = $TMPL['username'];
        $TMPL['link_code_content']     = '';
        $TMPL['link_code_type_link']   = '';
        $TMPL['link_code_type_static'] = '';
        $TMPL['link_code_type_extra']  = '';
        $TMPL['link_code_type_rank']   = '';
        $TMPL['link_code_type_stats']  = '';

        if($CONF['text_link'] == 1) {
            $TMPL['link_code_type_link'] .= $this->do_skin('link_code_type_link');
        }

        if($CONF['static_button'] == 1) {
            $TMPL['link_code_type_static'] .= $this->do_skin('link_code_type_static');

            $dir = new DirectoryIterator('./images/extra/');
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot()) {
                    $TMPL['button_name'] = $fileinfo->getFilename();

                    $TMPL['link_code_type_extra'] .= $this->do_skin('link_code_type_extra');
                }
            }
        }

        if($CONF['rank_button'] == 1) {
            $TMPL['link_code_type_rank'] .= $this->do_skin('link_code_type_rank');
        }

        if($CONF['stats_button'] == 1) {
            $TMPL['link_code_type_stats'] .= $this->do_skin('link_code_type_stats');
        }    

        // Plugin Hook - More Link codes
        eval (PluginManager::getPluginManager ()->pluginHooks ('join_extra_link_code'));         
        

        $TMPL['link_code'] = $this->do_skin('link_code');


        $LNG['join_welcome'] = sprintf($LNG['join_welcome'], $TMPL['list_name']);

        if ($CONF['email_admin_on_join']) {
          $join_email_admin = new skin('join_email_admin');
          $join_email_admin->send_email($CONF['your_email']);
        }

        if ($CONF['active_default'] == 1) {
          $join_email = new skin('join_email');
          $join_email->send_email($TMPL['email']);
          
          eval (PluginManager::getPluginManager ()->pluginHooks ('join_finish'));

          $TMPL['content'] = $this->do_skin('join_finish');
        }
        else {
          eval (PluginManager::getPluginManager ()->pluginHooks ('join_finish_approve'));

          $TMPL['content'] = $this->do_skin('join_finish_approve');
        }
      }
      else {
        $this->form();
      }
    }
    else {
      $this->form();
    }
  }
}
