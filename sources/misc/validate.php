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

/* Global - if($required && empty($value))
** $value is considered empty on cases below
** "" (an empty string)
** 0 (0 as an integer)
** 0.0 (0 as a float)
** "0" (0 as a string)
** NULL
** FALSE
** array() (an empty array)
** $var; (a variable declared, but without a value)
*/

/*
** Validate csrf field
*/
function validate_csrf_token($sid, $type) { 
    global $CONF;

	if(empty($sid) || empty($type)) {
		return false;
	}
	
	require_once("{$CONF['path']}/sources/misc/session.php");
	$session = new session;
	
	list($csrf_type, $csrf_user) = $session->get($sid);
			
	if(empty($csrf_type) || $csrf_type !== $type) {
		return false;
	}

	$session->delete($sid, "atsphp_sid_{$csrf_type}");
	
    return true;
}

/*
** Validate $value against required, but no other checks
*/
function validate_only_required($name, $value) {
    global $LNG, $TMPL;

	if(mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}	
    return 1;
}

/*
** Validate $value against preg_match if validators below dont fit your needs
** Example $rule = '/^[a-zA-Z0-9\-_]+$/D' ( only allow a-z, A-Z, 0-9, - and _ )
** Default: not required
*/
function validate_preg_match($name, $value, $rule, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;
	
    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_preg_match'];

	if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}
	elseif(mb_strlen($value) > 0 && !preg_match($rule, $value)) {
        error_display($name, $error_msg);
		return 0;
    }			
	return 1;
}  

/*
** Validate $value against a Number (int, decimal[including dot, comma]) > 0
** Default: not required
*/
function validate_number($name, $value, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;

    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_number'];

	if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}
	elseif(mb_strlen($value) > 0 && !preg_match('/^\d+(([\.,]\d+)+)?$/D', $value)) {
        error_display($name, $error_msg);
		return 0;
    }	
    return 1;
}

/*
** Validate $value against a digit (int)
** Default: not required
*/
function validate_int($name, $value, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;

    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_int'];

	if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}
	elseif(mb_strlen($value) > 0 && !preg_match('/^\d+$/D', $value)) {
        error_display($name, $error_msg);
		return 0;
    }	
    return 1;
}

/*
** Validate $value minimum number of characters
** Default: not required, min chars: 3
*/
function validate_min_chars($name, $value, $min = 3, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;

    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_min_chars'];

    if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}
    elseif(mb_strlen($value) > 0 && mb_strlen($value) < $min) {
        error_display($name, sprintf($error_msg, $min));
		return 0;
	}
    return 1;
}

/*
** Validate $value maximum number of characters
** Default: not required, max chars: 255
*/
function validate_max_chars($name, $value, $max = 255, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;

    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_max_chars'];

    if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}	
    elseif(mb_strlen($value) > $max) {
        error_display($name, sprintf($error_msg, $max));
		return 0;
	}
    return 1;
}

/*
** Validate $value against range between min to max characters
** Default: not required, char range: 3 - 255
*/
function validate_range_chars($name, $value, $min = 3, $max = 255, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;

    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_range_chars'];

    if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}
    elseif(mb_strlen($value) < $min || mb_strlen($value) > $max ) {
	    error_display($name, sprintf($error_msg, $min, $max));
		return 0;
	}
    return 1;
}

/*
** Validate $value against url(domain/ip'ish url) / image
** Default: not required
*/
function validate_url($name, $value, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;	
	
    $error_msg = !empty($error_msg) ? $error_msg : $LNG['join_error_url'];

	if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}	
	elseif(mb_strlen($value) > 0 && !preg_match('_^(?:https?://)(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS', $value)) {
        error_display($name, $error_msg);
		return 0;
    }	
	return 1;
}  

/*
** Validate $value against IP only
** Default: not required
*/
function validate_ip($name, $value, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;	
	
    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_ip'];

	if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}	
	elseif(mb_strlen($value) > 0 && !filter_var($value, FILTER_VALIDATE_IP)) {
        error_display($name, $error_msg);
		return 0;
    }	
	return 1;
} 

/*
** Validate $value against email
** Default: not required
*/
function validate_email($name, $value, $required = 0, $error_msg = '') {
    global $LNG, $TMPL;
	
    $error_msg = !empty($error_msg) ? $error_msg : $LNG['join_error_email'];

	if($required && mb_strlen($value) == 0) {
        error_display($name, $LNG['validate_required']);
		return 0;
	}
	elseif(mb_strlen($value) > 0 && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        error_display($name, $error_msg);
		return 0;
    }			
	return 1;
}  

/*
** Validate $value against db field to avoid duplicates
** Default: not required
*/
function validate_db_duplicate($name, $value, $columns = array(), $table = 'sites', $error_msg = '') {
    global $DB, $CONF, $LNG, $TMPL;	
	
    $error_msg = !empty($error_msg) ? $error_msg : $LNG['validate_db_duplicate'];

	if(mb_strlen($value) > 0) {
        $value = mb_strtolower($value);
	    foreach($columns as $column => $column_name) {
            list($column_value) = $DB->fetch("SELECT LOWER({$column_name}) FROM {$CONF['sql_prefix']}_{$table} WHERE LOWER({$column_name}) LIKE '{$value}'", __FILE__, __LINE__);
            if ($column_value && $column_value == $value) {
                error_display($name, $error_msg);
		        return 0;
            }
		}
    }	
	return 1;
}  

/*
** Gets called by functions to generate the error tmpl tags
*/
function error_display($name, $message) {
    global $LNG, $TMPL;

    $TMPL['error_top'] = $LNG['join_error_top'];
    $TMPL['error_style_top'] = 'join_edit_error text-danger';

    $TMPL["error_{$name}"] = "<div class=\"invalid-feedback text-danger\">{$message}</div>";  
    $TMPL["error_style_{$name}"] = 'join_edit_error is-invalid has-error';	 	
}
