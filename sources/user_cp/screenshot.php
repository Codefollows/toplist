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

class screenshot extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['plugin_screenshots_user_cp_header'];

	// MultiSite check
	if(!isset($FORM['site'])) {
	
        //GET OWNER AND SITE LIST
	    $result = $DB->query("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
	    while (list($myowner) = $DB->fetch_array($result)) {
		    $TMPL['myowner'] = $myowner;
		}
		    
	    $result = $DB->query("SELECT title, url, username FROM {$CONF['sql_prefix']}_sites WHERE owner = '{$TMPL['myowner']}' AND (active = 1 OR active = 3)", __FILE__, __LINE__);

        $TMPL['subtext'] = $LNG['user_cp_choose_domain'];

		//START LIST
		$TMPL['user_cp_content'] .= '<ul class="site-list">';
		
		$count = 0;
	    while (list($otitle, $ourl, $ousername) = $DB->fetch_array($result)) {
	    	 $count++;
		     $TMPL['user_cp_content'] .= '<li style="background: url('.$ourl.'/favicon.ico) 15px no-repeat; background-size: 16px auto;"><a href="index.php?a=user_cpl&b=screenshot&site='.$ousername.'">'.$ourl.'</a></li>';
		}
		
		//End LIST
		$TMPL['user_cp_content'] .= '</ul>';
		    
		if($count == 1) {
            header("Location: index.php?a=user_cpl&b=screenshot&site={$TMPL['username']}");
        }
	}
	elseif(isset($FORM['site'])){

	    $result = $DB->query("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
	    while (list($myowner) = $DB->fetch_array($result)) {
		    $TMPL['myowner'] = $myowner;
	    }

	    //VALIDATE THIS
	    $TMPL['myusername'] = $DB->escape($FORM['site']);
	    list($valid) = $DB->fetch("SELECT title FROM {$CONF['sql_prefix']}_sites WHERE username = \"{$TMPL['myusername']}\" AND owner = \"{$TMPL['myowner']}\"", __FILE__, __LINE__);
	
	    if (!$valid) {
	        header("Location: index.php?a=user_cpl&b=screenshot");
	        exit;
	    }  

	    $FORM['site'] = $DB->escape($FORM['site']);

        $row = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$FORM['site']}'", __FILE__, __LINE__);
        $TMPL = array_merge($TMPL, $row);     
	   
        $screen = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_screens WHERE username = '{$FORM['site']}'", __FILE__, __LINE__);
      
        eval (PluginManager::getPluginManager ()->pluginHooks ('user_cp_screenshot_build_page'));

	    if(!isset($screen['requested_url'])){
	  	
	  	    $requested_time = date('Y-m-d h:i:s', time() + (3600*$CONF['time_offset']));  

            $DB->query("INSERT INTO {$CONF['sql_prefix']}_screens (requested_url,requested_time,username) VALUES ('{$TMPL['url']}', '{$requested_time}', '{$FORM['site']}')", __FILE__, __LINE__);

            $TMPL['user_cp_content'] = '<p>'.$TMPL['url']. $LNG['plugin_screenshots_user_cp_added'].'</p>';
      
        }
        else {
            $TMPL['user_cp_content'] = '<p>'.$LNG['plugin_screenshots_user_cp_already'].'</p>';     	
        }

    }
    
  }
}
