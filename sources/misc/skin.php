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

class skin {
  public $filename;
  public $path;
  
  function __construct($filename, $path = null) {
 	global $CONF, $TMPL;
    $this->filename = $filename;
    $this->path     = is_null($path) ? "{$CONF['skins_path']}/{$TMPL['skin_name']}" : $path;
  }
    
  function make() {
    global $CONF, $TMPL;
    
	$file = "{$this->path}/{$this->filename}.html";
    
    //Check for child
    $child = "{$this->path}/child/{$this->filename}.html";
    if (file_exists($child)) {
   	    $file = $child;
    }
    //end child

    $skin = '';
    if ($fh_skin = fopen($file, 'r')) {
        $filesize = filesize($file);
        $skin = $filesize > 0 ? fread($fh_skin, $filesize) : '';
        fclose($fh_skin);
    }
    
    $parse = 1;
    
    if ($this->filename == 'wrapper') {
      $powered_by_check = mb_strpos($skin, '{$powered_by}') ? 1 : 0;
      
      if ($powered_by_check) {
        $return = $skin;
      }
      else {
        $return = 'You cannot delete {$powered_by} from wrapper.html.';
        $parse = 0;
      }
    }
    elseif ($this->filename == 'admin' || $this->filename == 'ssi_top' || $this->filename == 'ssi_members') {
      $return = $skin;
    }
    else {
      $return = "\n{$skin}\n\n";
    }
    
    if ($parse) {
      return $this->parse($return);
    }
    else {
      return $return;
    }
  }
  
  function make_plugin_skin() {
    global $CONF, $TMPL;
    
	$file = "{$this->path}/{$this->filename}.html";
    
    //Check for child
    $child = "{$this->path}/child/{$this->filename}.html";
    if (file_exists($child)) {
   	    $file = $child;
    }
    //end child

    $skin = '';
    if ($fh_skin = fopen($file, 'r')) {
        $filesize = filesize($file);
        $skin = $filesize > 0 ? fread($fh_skin, $filesize) : '';
        fclose($fh_skin);
    }

    $parse = 1;
    
	$return = "\n{$skin}\n";
    
    if ($parse) {
      return $this->parse($return);
    }
    else {
      return $return;
    }
  }
  
  function send_email($email) {
    global $CONF, $TMPL;

    $file = "{$this->path}/{$this->filename}.html";

    //Check for child
    $child = "{$this->path}/child/{$this->filename}.html";
    if (file_exists($child)) {
   	    $file = $child;
    }
    //end child

    $skin = '';
    if ($fh_skin = fopen($file, 'r')) {
        $filesize = filesize($file);
        $skin = $filesize > 0 ? fread($fh_skin, $filesize) : '';
        fclose($fh_skin);
    }

    $skin_array = explode("\n", $skin);

    $subject = array_shift($skin_array);
    $subject = str_replace('Subject: ', '', $subject);
    $body = implode("\n", $skin_array);

    $subject = $this->parse($subject);
    $body = $this->parse($body);

    require_once("{$CONF['path']}/sources/misc/class.phpmailer.php");
    $mail = new PHPMailer;

    //USE SMTP OR MAIL?
    if(!empty($CONF['smtp_host']) && !empty($CONF['smtp_password'])) {
        $mail->IsSMTP();                                      
        $mail->Host = $CONF['smtp_host'];                    
        $mail->SMTPAuth = true;                               
        $mail->Port = $CONF['smtp_port'];                     
        $mail->Username = $CONF['smtp_user'];                 
        $mail->Password = $CONF['smtp_password'];             
        $mail->SMTPSecure = 'tls';                            
        $mail->CharSet = 'UTF-8';
    }
    else {
        $mail->IsMail();                                      
    }
    $mail->From = $CONF['your_email'];
    $mail->FromName = $CONF['list_name'];
    $mail->AddReplyTo($CONF['your_email'], $CONF['list_name']);

    $mail->AddAddress($email);                        

    $mail->WordWrap = 50;                                 
    $mail->IsHTML(true);

    $mail->Subject = $subject;
    $mail->Body    = nl2br($body);
      
    $mail->Send();
  }
  
  function parse($skin) {
    global $LNG, $TMPL, $n, $parse_time;
    
	if(!empty($_GET['a']) || !empty($_GET['cat'])) {$TMPL['front_page_top'] = '';}

    // Include tag
    $skin = preg_replace_callback('/{include \"(.+?)\"}/i', function($matches) {
		
		return file_get_contents($matches[1]);
	}, $skin);
    
    // Language tags
    $skin = preg_replace_callback('/{\$lng->(.+?)}/i', function($matches) {
		global $LNG; 
		
		return $LNG[$matches[1]];
	}, $skin);	

    // Template tags + optional limit text output
    $skin = preg_replace_callback('/{\$(.+?)((?:,\s?length=)([0-9]+?))?}/i', function($matches) {
		global $TMPL; 
		
	    if(isset($matches[3])) {
            $limit = $matches[3];
            if (mb_strlen($TMPL[$matches[1]]) > $limit) {
                $TMPL[$matches[1]] = mb_substr($TMPL[$matches[1]], 0, mb_strrpos(mb_substr($TMPL[$matches[1]], 0, $limit), " ")) . "...";
            }	
	    }
		
	    return isset($TMPL[$matches[1]]) ? $TMPL[$matches[1]] : "";
	}, $skin);


    // Front page (page 1) only tag
    $skin = preg_replace_callback('/{isfront}(.+?){\/isfront}/is', function($matches) {
		global $FORM;
		
        if(empty($FORM["a"]) && empty($FORM["method"]) && empty($FORM["cat"])) {
            return $matches[1];
        } 
		else {}
    }, $skin);	

    //Ampersand Validation purpose only, does not affect any URLs
    $skin = str_replace(' & ', ' &amp; ', $skin);
    	
    return $skin;
  }
  
  function callback($matches) {
    return $matches[1];
  }
}


class main_skin extends skin {
  function __construct($filename) {
    global $CONF, $DB, $FORM, $LNG, $TIMER, $TMPL;
    
    $this->filename = $filename;
    $this->path     = "{$CONF['skins_path']}/{$TMPL['skin_name']}";

    // Number of members
    list($TMPL['num_members']) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats WHERE sites.username = stats.username AND active = 1", __FILE__, __LINE__);

    //BUILD MENUS
    $res = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_menu ORDER BY sort ASC ", __FILE__, __LINE__);
    $list = array();
    while ($r = mysqli_fetch_object($res)) {
        $list[$r->menu_id][$r->id]['title'] = $r->title;
        $list[$r->menu_id][$r->id]['path'] = $r->path;
        $list[$r->menu_id][$r->id]['target'] = $r->target;
    }

    foreach ($list as $menu_id => $menu_items) {
	    $TMPL["menu-$menu_id"] = "<ul class=\"nav menu-$menu_id\">\n";
	    foreach ($menu_items as $menu_item => $menu_content) {
			
			$url = preg_replace_callback('/{\$(.+?)}/i', function($matches) {
				global $TMPL; 
				
				return isset($TMPL[$matches[1]]) ? $TMPL[$matches[1]] : "";
			}, $menu_content['path']);

            $targets = '';
            $targets = !empty($menu_content['target']) ? ' '.stripslashes($menu_content['target']) : '';
		    $TMPL["menu-$menu_id"] .= '<li><a href="'.$url.'"'.$targets.'>'.$menu_content['title'].'</a></li>'."\n";
        }		
        $TMPL["menu-$menu_id"] .= '</ul>'."\n";
    }

    // Build the ranking method menu
    $ranking_method = isset($FORM['method']) ? $FORM['method'] : $CONF['ranking_method'];
    $TMPL['ranking_methods_menu'] = '';
    $TMPL['method_views'] = '';	
	$TMPL['method_in'] = ''; 	
	$TMPL['method_out'] = ''; 	
	
    $TMPL['ranking_methods_menu'] = '<select name="method">'."\n";
    if ($ranking_method == 'pv') {
    	$TMPL['method_views'] = '<span class="method_sort"> </span>'; 
	    $TMPL['ranking_methods_menu'] .= "<option value=\"pv\" selected=\"selected\">{$LNG['g_pv']}</option>\n";
	}
    else {
	    $TMPL['ranking_methods_menu'] .= "<option value=\"pv\">{$LNG['g_pv']}</option>\n";
	}
    if ($ranking_method == 'in') {
	    $TMPL['method_in'] = '<span class="method_sort"> </span>'; 
	    $TMPL['ranking_methods_menu'] .= "<option value=\"in\" selected=\"selected\">{$LNG['g_in']}</option>\n";
	}
    else {
	    $TMPL['ranking_methods_menu'] .= "<option value=\"in\">{$LNG['g_in']}</option>\n"; 
	}
    if ($ranking_method == 'out') {
	    $TMPL['method_out'] = '<span class="method_sort"> </span>'; 
    	$TMPL['ranking_methods_menu'] .= "<option value=\"out\" selected=\"selected\">{$LNG['g_out']}</option>\n"; 
	}
    else {
	    $TMPL['ranking_methods_menu'] .= "<option value=\"out\">{$LNG['g_out']}</option>\n";
	}
    $TMPL['ranking_methods_menu'] .= '</select>';
    
    // Build the categories menu and feed.php link
    $TMPL['feed'] = 'feed.php';
      $TMPL['categories_menu'] = "<select name=\"cat\" class=\"op form-control\">\n";
      if (isset($TMPL['category']) && $TMPL['category'] == $LNG['main_all']) {
         $TMPL['categories_menu'] .= "<option value=\"\" selected=\"selected\">{$LNG['main_all']}</option>\n";
      }
      else {
         $TMPL['categories_menu'] .= "<option value=\"\">{$LNG['main_all']}</option>\n";
      }
      
    $TMPL['category_menu'] = ''; 	
    $TMPL['cat_sort'] = '';			
    $TMPL['category_menu'] = '<ul class="category_menu">'."\n"; 
    
    foreach ($CONF['categories'] as $cat => $skin) {
        
      $cat_url = urlencode($CONF['categories'][$cat]['cat_slug']);    
      
      if ($TMPL['cat_exist'] == $cat) {
        $TMPL['categories_menu'] .= "<option value=\"{$cat}\" selected=\"selected\">{$cat}</option>\n";

        // Category Feed Link
        $TMPL['feed'] = "feed.php?cat={$cat_url}";
        
        // Category Order by Ranking Method
        $TMPL['cat_sort'] = '';		
        $TMPL['cat_sort'] = $TMPL['url_helper_cat2'].$cat_url;
        
        // Green tick icon on selected category
        $cat_selected = ' <span class="method_sort"> </span>';
      }      
      else {
        $TMPL['categories_menu'] .= "<option value=\"{$cat}\">{$cat}</option>\n";
        $cat_selected = '';
      }      
      
      $TMPL['category_menu'] .= '<li><a href="'.$CONF['list_url'].'/'.$TMPL['url_helper_cat'].$cat_url.$TMPL['url_tail'].'">'.$cat.'</a>'.$cat_selected.'</li>'."\n";
    }
    
    $TMPL['categories_menu'] .= '</select>';
    $TMPL['category_menu'] .= '</ul>';
    
    
    // Premium Member List Sidebar
	if (!empty($CONF['premium_number'])) 
	{
		$TMPL['premium_list'] = '';
		$TMPL['premium_list_row'] = '';
		$c = 0;
		$num_premium = $CONF['premium_number'];
		
		if ($CONF['premium_order_by'] == 1) {
			$premium_order_by = 'date_start_premium DESC';
		}
		else {
			$premium_order_by = 'RAND()';
		}
		
		$result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE premium_flag = 1 ORDER BY {$premium_order_by} LIMIT {$num_premium}", __FILE__, __LINE__);
		while ($row = $DB->fetch_array($result)) {
			
			$row = array_map(function($value) {
				return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
			}, $row);
			
			$TMPL = array_merge($TMPL, $row);
			$c++;
			
			if (!empty($CONF['visio_screen_api'])) {
				
				$domain_string   = filter_var($TMPL['url'],FILTER_SANITIZE_URL);
				$screenshot_url  = trim($domain_string, '/');
				$screenshot_url  = preg_replace('/https?:\/\//', '', $screenshot_url);
				$screenshot_url  = preg_replace('/(\/)|(\?)|(#)/', '-', $screenshot_url);
				$screenshot_path = $screenshot_url;
				$screenshot_url  = $screenshot_url.'_small.jpg';
			  
				if (file_exists("screens/{$screenshot_url}")) {
					$TMPL['screenshot'] = "<img src=\"screens/{$screenshot_url}\" alt=\"{$TMPL['title']}\" />";
					$TMPL['screenshot_path'] = 'screens/'.$screenshot_path;
				} 
				else {
				   $TMPL['screenshot'] = '';
				   $TMPL['screenshot_path'] =  'screens/none'; 
				}
			}
			
			// Prepare Category Url
			$category_raw = htmlspecialchars_decode($TMPL['category'], ENT_QUOTES);
			$TMPL['category_url'] = isset($CONF['categories'][$category_raw]) ? urlencode($CONF['categories'][$category_raw]['cat_slug']) : '';
			
			// Plugin Hook - Extend or modify premium list
			eval (PluginManager::getPluginManager ()->pluginHooks ('skin_premium_list'));

			$TMPL['premium_list_row'] .= base::do_skin('premium_list_row');
		}
		
		if($c > 0) {
			$TMPL['premium_list'] = base::do_skin('premium_list');
		}
	}
    
    
    // Plugin Hook > Global Useage
    eval (PluginManager::getPluginManager ()->pluginHooks ('skin_global'));
    
    
    // Featured member
	$TMPL['featured_member'] = '';
    if ($CONF['featured_member'] && $TMPL['num_members']) {
        $result = $DB->select_limit("SELECT username, url, title, description, banner_url FROM {$CONF['sql_prefix']}_sites WHERE active = 1 ORDER BY RAND()", 3, 0, __FILE__, __LINE__);
        $row = $DB->fetch_array($result);
		$row = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $row);
		
        $TMPL = array_merge($TMPL, $row);
      
        //Show Screenshots? (VisioList API Access required)
        if (!empty($CONF['visio_screen_api'])) {

	        $domain_string   = filter_var($TMPL['url'],FILTER_SANITIZE_URL);
  	        $screenshot_url  = trim($domain_string, '/');
            $screenshot_url  = preg_replace('/https?:\/\//', '', $screenshot_url);
            $screenshot_url  = preg_replace('/(\/)|(\?)/', '-', $screenshot_url);
            $screenshot_path = $screenshot_url;
	        $screenshot_url  = $screenshot_url.'_small.jpg';

            if (file_exists("screens/{$screenshot_url}")) {
	            $TMPL['featured_member_screenshot'] = "<img src=\"screens/{$screenshot_url}\" alt=\"{$TMPL['title']}\" title=\"{$TMPL['title']}\" class=\"screenshot\" /><br />";
                $TMPL['screenshot_path'] = 'screens/'.$screenshot_path;
            } 
            else {
                $TMPL['featured_member_screenshot'] = '';
                $TMPL['screenshot_path'] =  'screens/none'; 
            } 
        }   
        $TMPL['featured_member'] = base::do_skin('featured_member');
    }
    
    $TMPL['query'] = isset($TMPL['query']) ? $TMPL['query'] : '';
    
    $TMPL['powered_by'] = $LNG['main_powered'].' <a href="http://visiolist.com/"><b>VisioList</b></a> '.$TMPL['version'];


    eval (PluginManager::getPluginManager ()->pluginHooks ('skin_end'));
    
    
    if (!isset($TMPL['content'])) { $TMPL['content'] = ''; }
    
    $TMPL['num_queries'] = $DB->num_queries;
    $TMPL['execution_time'] = $TIMER->get_time();
  }
}
