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

class link_code extends base {
  public function __construct() {
    global $CONF, $FORM, $DB, $LNG, $TMPL;

    $TMPL['header'] = $LNG['link_code_header'];

	// MultiSite check
	if(!isset($FORM['site'])) {
	
        //GET OWNER AND SITE LIST
	    $result = $DB->query("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
	    while (list($myowner) = $DB->fetch_array($result)) {
		    $TMPL['myowner'] = $myowner;
		}

	    $result = $DB->query("SELECT title, url, username, category FROM {$CONF['sql_prefix']}_sites WHERE owner = '{$TMPL['myowner']}' AND (active = 1 OR active = 3)", __FILE__, __LINE__);

        $TMPL['subtext'] = $LNG['user_cp_choose_domain'];

		//START LIST
		$TMPL['user_cp_content'] .= '<ul class="site-list">';
		
		$count = 0;
	    while (list($otitle, $ourl, $ousername, $category) = $DB->fetch_array($result)) {
	    	 $count++;
		     $TMPL['user_cp_content'] .= '<li style="background: url('.$ourl.'/favicon.ico) 15px no-repeat; background-size: 16px auto;"><a href="index.php?a=user_cpl&b=link_code&site='.$ousername.'">'.$ourl.'</a></li>';
		}
		
		//End LIST
		$TMPL['user_cp_content'] .= '</ul>';
		    
		if($count == 1) {
            header("Location: index.php?a=user_cpl&b=link_code&site={$TMPL['username']}");
        }
	}
	elseif(isset($FORM['site'])){

	    $result = $DB->query("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
	    while (list($myowner) = $DB->fetch_array($result)) {
		    $TMPL['myowner'] = $myowner;
	    }

	    //VALIDATE THIS
	    $TMPL['myusername'] = $DB->escape($FORM['site']);
	    list($valid, $category, $url) = $DB->fetch("SELECT title, category, url FROM {$CONF['sql_prefix']}_sites WHERE username = \"{$TMPL['myusername']}\" AND owner = \"{$TMPL['myowner']}\"", __FILE__, __LINE__);
	
        $TMPL['category'] = $category;
    
	    if (!$valid) {
	        header("Location: index.php?a=user_cpl&b=link_code");
	        exit;
	    }

	    $TMPL['site'] = $DB->escape($FORM['site']);
	
		// Default, non friendly vote code is used
		// Only use friendly code if google_friendly_links is enabled 
		// And list is using https OR list is using http and member is using http
		// As https -> http not pass referrer url
	    $list_scheme          = parse_url($CONF['list_url'], PHP_URL_SCHEME);
	    $user_scheme          = parse_url($url, PHP_URL_SCHEME);
        $TMPL['verbose_link'] = "index.php?a=in&u={$TMPL['site']}";

        if ($CONF['google_friendly_links'] && ($list_scheme == 'https' || $list_scheme == $user_scheme))
		{
			$TMPL['verbose_link'] = "";
        }

        // Link Codes alt text
        include('button_config.php');
        $TMPL['text_link_button_alt'] = $CONF['text_link_button_alt'];

        // Link Code types
	    $TMPL['button_username']       = $TMPL['site'];
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
        eval (PluginManager::getPluginManager ()->pluginHooks ('user_cp_extra_link_code'));


    }
 
    $TMPL['user_cp_content'] .= $this->do_skin('link_code');
    
    
  }
}
