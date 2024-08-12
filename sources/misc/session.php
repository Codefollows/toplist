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

class session {
  public function __construct() {
    global $CONF, $DB;

    // Delete session older 30 days where keep_alive is set
	$check_time_ka = time() - (60 * 60 * 24 * 30);
    
    // No keep_alive or other session types ( e.g lost_pw ), delete session older 1 hour
	$check_time = time() - (60 * 60);
	
    $DB->query("DELETE FROM {$CONF['sql_prefix']}_sessions WHERE (time < {$check_time} AND keep_alive = 0) OR (time < {$check_time_ka} AND keep_alive = 1)", __FILE__, __LINE__);
  }

  function create($type, $data, $cookie = 1, $keep_alive = 0) {
    global $CONF, $DB;

    $sid  = $this->make_sid(32);
    $time = time();

	if (!empty($keep_alive)) {
		$expire = $time + (60 * 60 * 24 * 30);
	}
	else {
		$expire = $time + (60 * 60);
	}
			
    $DB->query("INSERT INTO {$CONF['sql_prefix']}_sessions (type, sid, time, data, keep_alive) VALUES ('{$type}', '{$sid}', {$time}, '{$data}', {$keep_alive})", __FILE__, __LINE__);
	
    if ($cookie) {
		
		$domain_info = parse_url($CONF['list_url']);
		$domain      = str_replace('www.', '', $domain_info['host']);
		$secure      = $domain_info['scheme'] == 'https' ? true : false;
		
		// First delete old cookies, if one has an cookie already, which we previously defined without path, secure etc
		// He would end up with 2 cookies, old and new, because cookie refresh need to be called with the same parametters as it was created with
		// php seems to grab the old one, because of that, cookie sid and db sid never match and we end up not being able to login
		// Maybe remove in a later VL version
		setcookie("atsphp_sid_{$type}", '', $time - 3600);

		// Now create the new cookie
		setcookie("atsphp_sid_{$type}", $sid, $expire, '/', $domain, $secure, true);
    }

    return $sid;
  }

  function delete($sid, $name = 0) {
    global $CONF, $DB;

    if ($this->check_sid($sid)) {
		if (!$name) {
			list($type, $data) = $this->get($sid);
			$name = "atsphp_sid_{$type}";
		}

		$domain_info = parse_url($CONF['list_url']);
		$domain      = str_replace('www.', '', $domain_info['host']);
		$secure      = $domain_info['scheme'] == 'https' ? true : false;
				
		setcookie($name, '', time() - 3600, '/', $domain, $secure, true);
			 		
		$DB->query("DELETE FROM {$CONF['sql_prefix']}_sessions WHERE sid = '{$sid}'", __FILE__, __LINE__);

		return 1;
    }

	return 0;
  }

  function get($sid) {
    global $CONF, $DB;

    if ($this->check_sid($sid)) {
      $session = $DB->fetch("SELECT type, data FROM {$CONF['sql_prefix']}_sessions WHERE sid = '{$sid}'", __FILE__, __LINE__);
      return $session;
    }

	return 0;
  }

  function update($sid) {
    global $CONF, $DB;

	if ($this->check_sid($sid)) {
		
		list($type, $keep_alive) = $DB->fetch("SELECT type, keep_alive FROM {$CONF['sql_prefix']}_sessions WHERE sid = '{$sid}'", __FILE__, __LINE__);

		$time = time();
		$name = "atsphp_sid_{$type}";
		
		// Update Cookie expire only if the session uses cookies
		if (isset($_COOKIE[$name]))
		{
			if (!empty($keep_alive)) {
				$expire = $time + (60 * 60 * 24 * 30);
			}
			else {
				$expire = $time + (60 * 60);
			}
		
			$domain_info = parse_url($CONF['list_url']);
			$domain      = str_replace('www.', '', $domain_info['host']);
			$secure      = $domain_info['scheme'] == 'https' ? true : false;
			
			setcookie("atsphp_sid_{$type}", $sid, $expire, '/', $domain, $secure, true);
		}
		
		$DB->query("UPDATE {$CONF['sql_prefix']}_sessions SET time = {$time} WHERE sid = '{$sid}'", __FILE__, __LINE__);
		
		return 1;
	}

	return 0;
  }

  function check_sid($sid) {
	  
    if (!is_string($sid) || preg_match('/[^a-zA-Z0-9]+/', $sid)) {
      return 0;
    }
	
    return 1;
  }

  function make_sid($length) 
  {
	if (function_exists('random_bytes')) 
	{
		// bin2hex doubles the length, so we pass a desired length, we need to divide by 2 to get correct output
		// also make sure we not make a string longer than db allowed of 32
		$byte_length = floor($length / 2);
		if ($byte_length < 1 || $byte_length > 16) {
			$byte_length = 16;
		}
		
		return bin2hex(random_bytes($byte_length));
	}
	
    $sid = '';
    for ($i = 1; $i <= $length; $i++) {
      $random = mt_rand(1, 30);
      if ($random <= 10) {
        $sid .= chr(mt_rand(65, 90));
      }
      elseif ($random <= 20) {
        $sid .= mt_rand(0, 9);
      }
      else {
        $sid .= chr(mt_rand(97, 122));
      }
    }

    return $sid;
  }
}
