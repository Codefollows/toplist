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

class inactive extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_man_inactive_header'];

    $num_list = 20;
    $extra_heading = '';

    // Search stuff
    $search_hidden = '';
    $search_name   = '';
	$search_sql    = '';		
    if(isset($FORM['search'])) {
		$search_name = htmlspecialchars($FORM['search'], ENT_QUOTES, "UTF-8");

        $search_name_sql = $DB->escape($FORM['search'], 1);
	    $search_sql = "AND username LIKE '%{$search_name_sql}%' OR title LIKE '%{$search_name_sql}%' OR url LIKE '%{$search_name_sql}%' OR title LIKE '%{$search_name_sql}%'";
		$search_hidden = '<input type="hidden" name="search" value="'.$search_name.'" />';
    }

    // Figure out what rows we want, and SELECT them
    if (isset($FORM['start'])) {
      $start = intval($FORM['start']);
      if ($start > 0) {
        $start--;
      }
    }
    else {
      $start = 0;
    }

    // Filter
    if (!empty($FORM['filter'])) {
        switch ($FORM['filter']) {
            case $LNG['a_man_inactive_days']: $filter = "days_inactive"; break;
            case $LNG['g_username']:          $filter = "username"; break;
            case "Title":                     $filter = "title"; break;
            case "Join Date":                 $filter = "join_date"; break;
            case $LNG['a_man_inactive_in']:   $filter = "unq_in_overall"; break;
            case $LNG['a_man_inactive_pv']:   $filter = "unq_pv_overall"; break;
            default:                          $filter = "days_inactive"; break;
        }
    }
    else {
        $filter = "days_inactive";
    }

    if (!empty($FORM['order'])) {
        switch ($FORM['order']) {
            case "Ascending":  $order = "ASC"; break;
            case "Descending": $order = "DESC"; break;
            default:           $order = "DESC"; break;
        }
    }
    else {
        $order = "DESC";
    }

    // Pagination
    $multiple_pages_p     = '';
    $multiple_pages_n     = '';
    $multiple_pages_links = '';	
	$dots_after           = '';
    $dots_before          = '';
    $pagination_result = $DB->query("SELECT * FROM {$CONF['sql_prefix']}_sites INNER JOIN {$CONF['sql_prefix']}_stats USING (username) WHERE days_inactive > 0 {$search_sql}", __FILE__, __LINE__);
    $pagination_rows = $DB->num_rows($pagination_result);
	
    if ($start < $num_list) {
        $page = "0";
    }
    else {
        $page = $start / $num_list;
    }
    $page_count = ceil($pagination_rows/$num_list);
	
    if ($page_count > 1) {
				
        if (($page + 1) > 1) {
            $previous_page = (($page - 1) * $num_list) + 1;		
			
			// Previous Page			
            $multiple_pages_p .= ' <span class="pag_links"><a href="'.$CONF['list_url'].'/index.php?a=admin&amp;b=inactive&amp;filter=';
            $multiple_pages_p .= isset($FORM['filter']) ? str_replace(' ', '+', $FORM['filter']) : 'Inactive+Days';	
		    $multiple_pages_p .= '&amp;order=';			
            $multiple_pages_p .= isset($FORM['order']) ? $FORM['order'] : 'Descending';
		    $multiple_pages_p .= '&amp;start='.$previous_page;
            $multiple_pages_p .= isset($FORM['search']) ? '&amp;search='.$FORM['search'] : '';
            $multiple_pages_p .= '">&lt;</a></span> ';			
        }
        for ($page_number = 1; $page_number <= $page_count; $page_number++) {
            $start_page = (($page_number - 1) * $num_list) + 1;
            if (($page_number - 1) == $page) {
			    // Current Page
                $multiple_pages_links .= ' <span class="pag_links"><strong>'.$page_number.'</strong></span> ';				
            }
            else {
			
			    if($page_number >= $page_count - 10) { $minus_pages = 9; } else { $minus_pages = 3; }				 		
                if ($page_number <= 11 && $page_number >= $page - 3 || $page_number >= $page - $minus_pages && $page_number <= $page + 5 || $page_number == 1 || $page_number == $page_count) {
				    // Other Pages					
                    $multiple_pages_links .= ' <span class="pag_links"><a href="'.$CONF['list_url'].'/index.php?a=admin&amp;b=inactive&amp;filter=';
                    $multiple_pages_links .= isset($FORM['filter']) ? str_replace(' ', '+', $FORM['filter']) : 'Inactive+Days';	
		            $multiple_pages_links .= '&amp;order=';			
                    $multiple_pages_links .= isset($FORM['order']) ? $FORM['order'] : 'Descending';
		            $multiple_pages_links .= '&amp;start='.$start_page;	
                    $multiple_pages_links .= isset($FORM['search']) ? '&amp;search='.$FORM['search'] : '';
                    $multiple_pages_links .= '">'. $page_number .'</a></span> ';					
                }
                else {				
                    if ($page_number > $page && $dots_after != true) {
                        $multiple_pages_links .= ' ...';
                        $dots_after = true;
                    } 
					elseif ($page_number < $page && $dots_before != true) {
                        $multiple_pages_links .= ' ...';
                        $dots_before = true;
                    }
                }
            }
        }
        if (($page + 1) < $page_count) {
            $next_page = (($page+1) * $num_list) + 1;
		
		    // Next Page
            $multiple_pages_n .= ' <span class="pag_links"><a href="'.$CONF['list_url'].'/index.php?a=admin&amp;b=inactive&amp;filter=';
            $multiple_pages_n .= isset($FORM['filter']) ? str_replace(' ', '+', $FORM['filter']) : 'Inactive+Days';	
		    $multiple_pages_n .= '&amp;order=';			
            $multiple_pages_n .= isset($FORM['order']) ? $FORM['order'] : 'Descending';
		    $multiple_pages_n .= '&amp;start='.$next_page;	
            $multiple_pages_n .= isset($FORM['search']) ? '&amp;search='.$FORM['search'] : '';
            $multiple_pages_n .= '">&gt;</a></span> ';	
        }
    }	

    // Sort By Menu
    $filter_array = array($LNG['a_man_inactive_days'], $LNG['g_username'], 'Title', 'Join Date', $LNG['a_man_inactive_in'], $LNG['a_man_inactive_pv']);
    $filter_menu = "<select name=\"filter\">\n";
    foreach ($filter_array as $item) {
      if (isset($FORM['filter']) && $FORM['filter'] == $item) {
        $filter_menu .= "<option value=\"{$item}\" selected=\"selected\">{$item}</option>\n";
      }
      else {
        $filter_menu .= "<option value=\"{$item}\">{$item}</option>\n";
      }
    }
    $filter_menu .= "</select>";

    // Order By Menu
    $order_array = array('Descending', 'Ascending');
    $order_menu = "<select name=\"order\" id=\"order\">\n";
    foreach ($order_array as $item) {
      if (isset($FORM['order']) && $FORM['order'] == $item) {
        $order_menu .= "<option value=\"{$item}\" selected=\"selected\">{$item}</option>\n";
      }
      else {
        $order_menu .= "<option value=\"{$item}\">{$item}</option>\n";
      }
    }
    $order_menu .= "</select>";
	
	
    // Plugin Hook
    eval (PluginManager::getPluginManager ()->pluginHooks ('admin_manage_inactive_build_page'));


    // Always visible content
    $TMPL['admin_content'] = <<<EndHTML

    <script language="javascript">
        var count = 0;
        function popup(id) {
            count = count + 1;
            elem = document.getElementById(id);
            elem.style.zIndex = count;
            if (elem.style.display == "none") { elem.style.display = "block"; }
            else { elem.style.display = "none"; }
        }
    </script>

    <br />
    <div style="float: right;">
        <form action="index.php" method="get">
            <input type="hidden" name="a" value="admin" />
            <input type="hidden" name="b" value="inactive" />
            <input type="text" name="search" size="18" value="{$search_name}" placeholder="{$LNG['a_s_search']}" />
            <input type="submit"  class="positive" value="{$LNG['g_form_submit_short']}" />
        </form>
    </div>
    <div style="float: left;">
        <form action="index.php" method="get">	
            <input type="hidden" name="a" value="admin" />
            <input type="hidden" name="b" value="inactive" />
            Order By
            {$filter_menu}
            {$order_menu}
            {$search_hidden}
            <input type="submit"  class="positive" value="{$LNG['g_form_submit_short']}" />
        </form>
    </div>
    <div class="pagination">{$multiple_pages_p}{$multiple_pages_links}{$multiple_pages_n}</div>
    <br style="clear: both;"/><br />

    <form action="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete" method="post" name="manage">
        <table cellpadding="1" cellspacing="1" width="100%">
          <thead>
            <tr class="mediumbg">
              <th align="center" width="1%">{$LNG['a_man_delete']}?</th>
              <th align="center" width="1%">{$LNG['g_username']}</th>
              <th align="center" width="1%">{$LNG['a_man_inactive_days']}</th>
              <th width="90%">{$LNG['table_title']}</th>
              <th align="center" width="1%"{$TMPL['pv_hide']}>{$LNG['a_man_inactive_pv']}</th>
              <th align="center" width="1%">{$LNG['a_man_inactive_in']}</th>
              {$extra_heading}
              <th align="center" colspan="4">{$LNG['a_man_actions']}</th>
            </tr>
          </thead>
          <tbody>
EndHTML;

    $alt = '';
    $num = 0;
    $result = $DB->select_limit("SELECT username, title, url, email, user_ip, days_inactive, unq_pv_overall, unq_in_overall FROM {$CONF['sql_prefix']}_sites INNER JOIN {$CONF['sql_prefix']}_stats USING (username) WHERE days_inactive > 0 {$search_sql} ORDER BY {$filter} {$order}", $num_list, $start, __FILE__, __LINE__);
    while (list($username, $title, $url, $email, $user_ip, $days_inactive, $pv_overall, $in_overall) = $DB->fetch_array($result)) {
      $url_url = urlencode($url);
      $user_ip_url = urlencode($user_ip);
      $username_url = urlencode($username);
      $email_url = urlencode($email);
	  $title = htmlspecialchars($title, ENT_QUOTES, "UTF-8");
	  $url = htmlspecialchars($url, ENT_QUOTES, "UTF-8");

      $extra           = '';
      $blacklist_extra = '';

      // Plugin Hook
      eval (PluginManager::getPluginManager ()->pluginHooks ('admin_manage_inactive_member_loop'));


      $TMPL['admin_content'] .= <<<EndHTML
      <tr class="lightbg{$alt}">
        <td><input type="checkbox" name="u[]" value="{$username}" id="checkbox_{$num}" class="check_selectall_none" /></td>
        <td align="center">{$username}</td>
        <td align="center">{$days_inactive}</td>
        <td><a href="{$url}" onclick="out(this,'{$username}');" target="_blank">{$title}</a></td>
        <td align="center"{$TMPL['pv_hide']}>{$pv_overall}</td>
        <td align="center">{$in_overall}</td>
        {$extra}
        <td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=edit&amp;u={$username}">{$LNG['a_man_edit']}</a></td>
        <td align="center"><a href="mailto:{$email}">{$LNG['a_man_email']}</a></td>
        <td align="center"><a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=delete&amp;u={$username}">{$LNG['a_man_delete']}</a></td>
        <td align="center">
          <a href="javascript:void(0);" onclick="popup('ban_{$num}')">{$LNG['a_menu_manage_ban']}</a>
          <div id="ban_{$num}" class="lightbg{$alt}" style="display: none; border: 1px solid #000; position: absolute; padding: 2px; text-align: left;">
            <a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$url_url}&amp;field=url&amp;matching=1">URL</a><br />
            <a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$user_ip_url}&amp;field=ip&amp;matching=1">User IP</a><br />
            <a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$username_url}&amp;field=username&amp;matching=1">Username</a><br />
            <a href="{$TMPL['list_url']}/index.php?a=admin&amp;b=manage_ban&amp;string={$email_url}&amp;field=email&amp;matching=1">Email</a>
            {$blacklist_extra}
          </div>
        </td>
      </tr>
EndHTML;

      if ($alt) { $alt = ''; }
      else { $alt = 'alt'; }
      $num++;
    }

    $TMPL['admin_content'] .= <<<EndHTML
    </tbody></table><br /><br />

    <span id="selectall">{$LNG['a_man_all']}</span> | 
    <span id="selectnone">{$LNG['a_man_none']}</span><br /><br />
    <input type="submit"  class="positive" value="{$LNG['a_man_del_sel']}" />

</form>
EndHTML;
  }
}
