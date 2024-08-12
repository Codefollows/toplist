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
	
function generate_csrf_token($username, $type, $as_input = true) 
{
	global $CONF;

	require_once("{$CONF['path']}/sources/misc/session.php");
	$session = new session;	
	$sid = $session->create($type, $username, 0);	
	
	if ($as_input === false) {
		return $sid;
	}
	
	$input = '<input type="hidden" name="csrf_token" value="'.$sid.'">';

	return $input;
}

function generate_input($name, $label_text, $size, $required = 0) 
{
	global $DB, $FORM, $CONF, $LNG, $TMPL;

	$TMPL['element_error']       = isset($TMPL["error_{$name}"]) ? $TMPL["error_{$name}"] : '';
	$TMPL['element_error_style'] = isset($TMPL["error_style_{$name}"]) ? $TMPL["error_style_{$name}"] : '';
	$TMPL['element_name']        = $name;
	$TMPL['element_label']       = $label_text;
	$TMPL['element_value']       = $TMPL[$name];

	return base::do_skin('form_elements/input');
}

function generate_textarea($name, $label_text, $required = 0) 
{
	global $DB, $FORM, $CONF, $LNG, $TMPL;

	$TMPL['element_error']       = isset($TMPL["error_{$name}"]) ? $TMPL["error_{$name}"] : '';
	$TMPL['element_error_style'] = isset($TMPL["error_style_{$name}"]) ? $TMPL["error_style_{$name}"] : '';	
	$TMPL['element_name']        = $name;
	$TMPL['element_label']       = $label_text;
	$TMPL['element_value']       = $TMPL[$name];
	
	return base::do_skin('form_elements/textarea');
}

function generate_select($name, $label_text, $option_value, $option_text) 
{
	global $DB, $FORM, $CONF, $LNG, $TMPL;

	$value_array  = explode(', ', $option_value);
	$text_array   = explode(', ', $option_text);
	$count        = count($value_array);
 
	$TMPL['element_error']       = isset($TMPL["error_{$name}"]) ? $TMPL["error_{$name}"] : '';
	$TMPL['element_error_style'] = isset($TMPL["error_style_{$name}"]) ? $TMPL["error_style_{$name}"] : ''; 
	$TMPL['element_name']        = $name;
	$TMPL['element_label']       = $label_text;
	$TMPL['element_options']     = '';
	
	for ($i = 0; $i < $count; $i++) 
	{
		$TMPL['element_option_selected'] = isset($TMPL[$name]) && $TMPL[$name] == $value_array[$i] ? 'selected="selected"' : '';	
		$TMPL['element_option_value']    = $value_array[$i];
		$TMPL['element_option_text']     = $text_array[$i];
		
		$TMPL['element_options']        .= base::do_skin('form_elements/select_option');
	}

	return base::do_skin('form_elements/select');
}

function generate_checkbox($name, $label_text, $option_value, $option_text, $checked_array, $required = 0) 
{
	global $DB, $FORM, $CONF, $LNG, $TMPL;

	$value_array = explode(', ', $option_value);
	$text_array  = explode(', ', $option_text);
	$count       = count($value_array);
	
	$TMPL['element_error']       = isset($TMPL["error_{$name}"]) ? $TMPL["error_{$name}"] : '';
	$TMPL['element_error_style'] = isset($TMPL["error_style_{$name}"]) ? $TMPL["error_style_{$name}"] : '';
	$TMPL['element_name']        = $name;
	$TMPL['element_heading']     = $label_text;
	$TMPL['element_options']     = '';
	
	for ($i = 0; $i < $count; $i++) 
	{
		$TMPL['element_option_checked'] = in_array($value_array[$i], $checked_array) ? 'checked="checked"' : '';
		$TMPL['element_option_value']   = $value_array[$i];
		$TMPL['element_option_label']   = $text_array[$i];
		$TMPL['element_option_id']      = "{$name}-{$i}";
		
		$TMPL['element_options']       .= base::do_skin('form_elements/checkbox_option');
	}
	
	return base::do_skin('form_elements/checkbox');
}

function generate_radio($name, $label_text, $option_value, $option_text, $checked_value, $required = 0) 
{
	global $DB, $FORM, $CONF, $LNG, $TMPL;

	$value_array = explode(', ', $option_value);
	$text_array  = explode(', ', $option_text);
	$count       = count($value_array);

	$TMPL['element_error']       = isset($TMPL["error_{$name}"]) ? $TMPL["error_{$name}"] : '';
	$TMPL['element_error_style'] = isset($TMPL["error_style_{$name}"]) ? $TMPL["error_style_{$name}"] : '';	
	$TMPL['element_name']        = $name;
	$TMPL['element_heading']     = $label_text;
	$TMPL['element_options']     = '';
	
	for ($i = 0; $i < $count; $i++) 
	{
		$TMPL['element_option_checked'] = !empty($checked_value) && $TMPL[$name] == $value_array[$i] ? 'checked="checked"' : '';	
		$TMPL['element_option_value']   = $value_array[$i];
		$TMPL['element_option_label']   = $text_array[$i];
		$TMPL['element_option_id']      = "{$name}-{$i}";
		
		$TMPL['element_options']       .= base::do_skin('form_elements/radio_option');
	}

	return base::do_skin('form_elements/radio');
}
