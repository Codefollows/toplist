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

class rankings extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;
	
    // Get the category, default to no category
    if ($TMPL['cat_exist']) {
        $TMPL['category'] = $TMPL['cat_exist'];
        $category_escaped = $DB->escape($TMPL['category']);
        $category_sql = "AND category = '{$category_escaped}'";
    }
    elseif(isset($FORM['cat'])) { 
        // Form cat exist, empty or whatever value, but no valid category, since first if block not ran
        $this->error($LNG['g_invalid_category']); 
    }
    else {
        $TMPL['category'] = $LNG['main_all'];
        $category_sql = '';
    }

    $TMPL['header'] = "{$LNG['main_header']} - {$TMPL['category']}";

	// Init some vars
    $TMPL['rankings_custom_fields'] = ''; 
	
    // Get the ranking method, default to pageviews
    $ranking_method = isset($FORM['method']) ? $FORM['method'] : $CONF['ranking_method'];
    if (($ranking_method != 'pv') && ($ranking_method != 'in') && ($ranking_method != 'out')) {
      $ranking_method = 'pv';
    }

    // This is needed in case someone provide more ranking methods, e.g order by newest
    // So $TMPL['this_period'] , $TMPL['average'] stays correct and not turn to unq_newest_0_daily
    $ranking_method_alt = $ranking_method;

    // Make ORDER BY clause
    $order_by = $this->rank_by($ranking_method)." DESC, unq_{$ranking_method}_overall DESC, join_date DESC";

    // Plugin hook, overwrite or extend ORDER BY clause
    eval (PluginManager::getPluginManager ()->pluginHooks ('rankings_order_by'));

    $where_extra  = '';
    $where_active = 'AND active = 1';
    // Plugin hook, extend the WHERE clause.
    // e.g $where_extra .= " AND column_name = 'text here'";
    eval (PluginManager::getPluginManager ()->pluginHooks ('rankings_extend_where'));

	
	// Pagination data
    list($pagination_rows) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats WHERE sites.username = stats.username {$where_active} {$category_sql} {$where_extra}", __FILE__, __LINE__);
	
	// Repeately used checks
	$is_friendly_url = mb_strpos($_SERVER['REQUEST_URI'], '?') === false ? true : false;
	$has_start_param = isset($FORM['start']) ? true : false;

	// Handles not updated htaccess file, set during first redirect
	if ($has_start_param && isset($_SESSION['VL_OLD_START_AS_PAGE']) && $is_friendly_url) {
		$FORM['p'] = $FORM['start'];
	}

	$has_page_param   = isset($FORM['p']) ? true : false;	
	$page             = $has_page_param ? (int)$FORM['p'] : 1;
    $page_count       = $pagination_rows > 0 ? ceil($pagination_rows / $CONF['num_list']) : 1;
    $pag_category_url = $TMPL['cat_exist'] ? urlencode($CONF['categories'][$TMPL['cat_exist']]['cat_slug']) : '';		
		
	// Only if $FORM['method'] or old $FORM['start'] exist
	// Redirect page <= 1
	// Redirect default method if page <= 1
	// Redirect page > max page
	// Redirect old start=xx to p=xx
	$pagination_redirect = false;
	if (isset($FORM['method']) || $has_start_param)
	{	
		if ($page <= 1 || $page > $page_count)
		{			
			// Handle old start=xx urls
			// Also handles friendly urls if htaccess not updated, thus redirecting to proper page number
			if ($has_start_param) 
			{
				$page                = ceil((int)$FORM['start'] / $CONF['num_list']);
				$has_page_param      = true;
				$pagination_redirect = true;
			}
			
			if ($is_friendly_url) 
			{
				$ranking_params = $_SERVER['REQUEST_URI'];

				// REQUEST_URI includes sub folder of domain, strip it in order to normalize REQUEST_URI
				$list_url_parse = parse_url($CONF['list_url']);
				if (isset($list_url_parse['path'])) {
					$ranking_params = mb_substr($ranking_params, mb_strlen($list_url_parse['path']));
				}
				
				// Dont include starting and ending slash to avoid blank array item
				$ranking_params = explode('/', trim($ranking_params, '/'));
				
				// Overwrite page param if htaccess not updated
				if ($has_start_param) 
				{
					$ranking_params[1] = $page;
					
					// Set session to overwrite $FORM['p'] with $FORM['start'] after redirect
					// If not done, a pagination click would result in many redirects crawling down to page 1 again
					$_SESSION['VL_OLD_START_AS_PAGE'] = 1;
				}
			}
			else 
			{
				parse_str($_SERVER['QUERY_STRING'], $ranking_params);
				
				// Handle old start=xx urls
				if ($has_start_param) 
				{
					// Add page param
					$ranking_params = array('p' => $page) + $ranking_params;
					
					// Drop start param
					unset($ranking_params['start']);
				}
			}
				
			// Set page to max page
			if ($page > $page_count)
			{
				if ($is_friendly_url) {
					$ranking_params[1] = $page_count;
				}
				else {
					$ranking_params['p'] = $page_count;
				}
				
				$pagination_redirect = true;
			}
				
			// No need page on page <= 1
			// No need method if default and on page <= 1
			if ($page <= 1) 
			{
				if ($has_page_param) 
				{
					if ($is_friendly_url) {
						unset($ranking_params[1]);
					}
					else {
						unset($ranking_params['p']);
					}
					
					$pagination_redirect = true;
				}
				
				if ($ranking_method == $CONF['ranking_method']) 
				{
					if ($is_friendly_url) 
					{
						// string 'rank'
						unset($ranking_params[0]);

						// string method '$ranking_method'
						if ($has_page_param) {
							unset($ranking_params[2]);
						}
						else {
							unset($ranking_params[1]);
						}
					}
					else{
						unset($ranking_params['method']);
					}
					
					$pagination_redirect = true;
				}
			}
			
			if ($pagination_redirect === true) 
			{
				if ($is_friendly_url) {
					$ranking_params = !empty($ranking_params) ? implode('/', $ranking_params).'/' : '';
				}
				else {
					$ranking_params = !empty($ranking_params) ? '?'.http_build_query($ranking_params) : '';			
				}

				header("HTTP/1.1 301 Moved Permanently");
				header("Location: {$CONF['list_url']}/{$ranking_params}");
				exit;
			}
		}
	}
	
    // Figure out what rows we want
	$start = ($page * $CONF['num_list']) - $CONF['num_list'];	
	
    $query_columns = '*';
    // Plugin hook, overwrite what columns get selected.
    // e.g $query_columns = " sites.username, sites.title etc";
    eval (PluginManager::getPluginManager ()->pluginHooks ('rankings_select_columns'));
	
	// User data
    $result = $DB->select_limit("SELECT {$query_columns}
		FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats
		WHERE sites.username = stats.username {$where_active} {$category_sql} {$where_extra}
		ORDER BY {$order_by}
	", $CONF['num_list'], $start, __FILE__, __LINE__);
				
				
    eval (PluginManager::getPluginManager ()->pluginHooks ('rankings_query'));
	

    if ($page_count > 1) 
	{
		// Previous control
        if ($page > 1) 
		{
			$previous_page  = $page - 1;		

            $TMPL['pagination_rel']  = 'rel="prev"';
            $TMPL['pagination_link'] = $CONF['list_url'];

			// No need start on page 1 and method only if not default
			if ($previous_page == 1 && $ranking_method != $CONF['ranking_method']) 
			{
				$TMPL['pagination_link'] .= '/'.$TMPL['url_helper_method'].$ranking_method;
			}
			elseif ($previous_page > 1) 
			{
				$TMPL['pagination_link'] .= "/{$TMPL['url_helper_method']}";	
				if($CONF['clean_url'] == 1) { 
					$TMPL['pagination_link'] .= "{$previous_page}/{$ranking_method}"; 
				}
				else { 
					$TMPL['pagination_link'] .= $ranking_method.$TMPL['url_helper_page'].$previous_page; 
				}
			}
			
			if($TMPL['cat_exist']) 
			{ 
				// Category url helper differs when page 1 not include method
				if ($previous_page == 1 && $ranking_method == $CONF['ranking_method']) {
					$TMPL['pagination_link'] .= '/'.$TMPL['url_helper_cat'].$pag_category_url; 
				}
				else {
					$TMPL['pagination_link'] .= $TMPL['url_helper_cat2'].$pag_category_url; 
				}
			}
				
			eval (PluginManager::getPluginManager ()->pluginHooks ('pagination_previous'));
				
			$TMPL['pagination_link'] .= $TMPL['url_tail'];
			
			// Header for pagination rel prev
			$pagination_rel_prev = str_replace('&amp;', '&', $TMPL['pagination_link']);
			header("Link: <{$pagination_rel_prev}>; rel=\"prev\"");
				
			$TMPL['pagination_prev'] = $this->do_skin('pagination_prev');
		}
		
		// Page numbers
		$pagination_dots = true;
		$TMPL['pagination_items'] = '';

        for ($page_number = 1; $page_number <= $page_count; $page_number++) 
		{
			// If first or last page or the page number falls within the pagination limit, generate the links for these pages
			if($page_number == 1 || $page_number == $page_count || ($page_number >= $page - 4 && $page_number <= $page + 4))
			{
				// Set to true again for possible second dots block
				$pagination_dots = true;

				// Current Page, default state
				$TMPL['pagination_state'] = 'disabled active';
				$TMPL['pagination_link']  = '#';
					
				// All other page number links
				if ($page_number != $page) 
				{
					$TMPL['pagination_state'] = '';

                    $TMPL['pagination_link'] = $CONF['list_url'];
					
					// No need start on page 1 and method only if not default
					if ($page_number == 1 && $ranking_method != $CONF['ranking_method']) 
					{
						$TMPL['pagination_link'] .= '/'.$TMPL['url_helper_method'].$ranking_method;
					}
					elseif ($page_number > 1) 
					{
						$TMPL['pagination_link'] .= "/{$TMPL['url_helper_method']}";	
						if($CONF['clean_url'] == 1) { 
							$TMPL['pagination_link'] .= "{$page_number}/{$ranking_method}"; 
						}
						else { 
							$TMPL['pagination_link'] .= $ranking_method.$TMPL['url_helper_page'].$page_number; 
						}
					}
					
					if($TMPL['cat_exist']) 
					{ 
						// Category url helper differs when page 1 not include method
						if ($page_number == 1 && $ranking_method == $CONF['ranking_method']) {
							$TMPL['pagination_link'] .= '/'.$TMPL['url_helper_cat'].$pag_category_url; 
						}
						else {
							$TMPL['pagination_link'] .= $TMPL['url_helper_cat2'].$pag_category_url; 
						}
					}

                    eval (PluginManager::getPluginManager ()->pluginHooks ('pagination_number'));
					
                    $TMPL['pagination_link'] .= $TMPL['url_tail'];	
				}
				
				$TMPL['pagination_page']   = $page_number;	
				$TMPL['pagination_items'] .= $this->do_skin('pagination_item');
			}
			elseif ($pagination_dots == true)
			{
				// set it to false, until needed again
				$pagination_dots = false;
				
				// The dots 
				$TMPL['pagination_state'] = 'disabled';
				$TMPL['pagination_rel']  = '';
				$TMPL['pagination_link']  = '#';
				$TMPL['pagination_page']  = '...';	
				
				$TMPL['pagination_items'] .= $this->do_skin('pagination_item');				   
			}
		}
					
		// Next control
		$next_page = $page + 1;
        if ($next_page <= $page_count) 
		{				
            $TMPL['pagination_rel']  = 'rel="next"';
			$TMPL['pagination_link'] = "{$CONF['list_url']}/{$TMPL['url_helper_method']}";
			if($CONF['clean_url'] == 1) { 
				$TMPL['pagination_link'] .= "{$next_page}/{$ranking_method}"; 
			}
			else { 
				$TMPL['pagination_link'] .= $ranking_method.$TMPL['url_helper_page'].$next_page; 
			}		
			if($TMPL['cat_exist']) { 
				$TMPL['pagination_link'] .= $TMPL['url_helper_cat2'].$pag_category_url; 
			}
			
			eval (PluginManager::getPluginManager ()->pluginHooks ('pagination_next'));
			
			$TMPL['pagination_link'] .= $TMPL['url_tail'];
			
			// Header for pagination rel next
			// Parameter false, so prev link header isnt overwritten
			$pagination_rel_next = str_replace('&amp;', '&', $TMPL['pagination_link']);
			header("Link: <{$pagination_rel_next}>; rel=\"next\"", false);
				
			$TMPL['pagination_next'] = $this->do_skin('pagination_next');
		}
    }
	
	$TMPL['pagination'] = $this->do_skin('pagination');
    // Pagination End


    $ranking_period = $CONF['ranking_period'];

    if ($TMPL['category'] == $LNG['main_all']) {
      $is_main = 1;
    }
    else {
      $is_main = 0;
    }
    $TMPL['rank'] = $start + 1;
    $page_rank = 1;
    $top_done = 0;
    $do_table_open = 0;
    $TMPL['alt'] = 'alt';

    if ($DB->num_rows($result)) {
		
		// Start the output with table_top_open if we're on the first page
		if ($CONF['top_skin_num'] > 0 && $page == 1) {
			$TMPL['content'] = $this->do_skin('table_top_open');
		}
		else {
			$TMPL['content'] = $this->do_skin('table_open');
		}

		// All this $TMPL_original stuff is a hack to avoid doing an array_merge
		// on large arrays with conflicting keys, because that is very slow
		$TMPL_original = $TMPL;

		while ($row = $DB->fetch_array($result)) {

			$TMPL_original['content'] = $TMPL['content'];
			$TMPL_original['alt'] = $TMPL['alt'];
			$TMPL_original['rank'] = $TMPL['rank'];
			
			$row = array_map(function($value) {
				return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
			}, $row);
		
			$TMPL = array_merge($TMPL_original, $row);

			if ($CONF['ranking_method'] == $ranking_method && $is_main) {
			  if (!$TMPL['old_rank']) {
				$TMPL['old_rank'] = $TMPL['rank'];
				$DB->query("UPDATE {$CONF['sql_prefix']}_stats SET old_rank = {$TMPL['old_rank']} WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
			  }
			  if ($TMPL['old_rank'] > $TMPL['rank']) { $TMPL['up_down'] = 'up'; $LNG['up_down'] = $LNG['table_up']; }
			  elseif ($TMPL['old_rank'] < $TMPL['rank']) { $TMPL['up_down'] = 'down'; $LNG['up_down'] = $LNG['table_down']; }
			  else { $TMPL['up_down'] = 'neutral'; $LNG['up_down'] = $LNG['table_neutral']; }
			}
			else { $TMPL['up_down'] = 'neutral'; $LNG['up_down'] = $LNG['table_neutral']; }
			if ($TMPL['alt']) { $TMPL['alt'] = ''; }
			else { $TMPL['alt'] = 'alt'; }

			$TMPL['average_rating'] = $TMPL['num_ratings'] > 0 ? round($TMPL['total_rating'] / $TMPL['num_ratings'], 0) : 0;

			$ranking_periods = array('daily', 'weekly', 'monthly');
			$ranking_methods = array('unq_pv', 'tot_pv', 'unq_in', 'tot_in', 'unq_out', 'tot_out');
			foreach ($ranking_periods as $ranking_period2) {
			  foreach ($ranking_methods as $ranking_method2) {
				$TMPL["{$ranking_method2}_avg_{$ranking_period2}"] = 0;
				for ($i = 0; $i < 10; $i++) {
				  $TMPL["{$ranking_method2}_avg_{$ranking_period2}"] = $TMPL["{$ranking_method2}_avg_{$ranking_period2}"] + $TMPL["{$ranking_method2}_{$i}_{$ranking_period2}"];
				}
				$TMPL["{$ranking_method2}_avg_{$ranking_period2}"] = number_format($TMPL["{$ranking_method2}_avg_{$ranking_period2}"] / 10, 1);
			  }
			}

			$TMPL['this_period'] = number_format($TMPL["unq_{$ranking_method_alt}_0_{$ranking_period}"]);
			$TMPL['average'] = 0;
			for ($i = 0; $i < 10; $i++) {
			  $TMPL['average'] = $TMPL['average'] + $TMPL["unq_{$ranking_method_alt}_{$i}_{$ranking_period}"];
			}
			$TMPL['average'] = number_format($TMPL['average'] / 10, 1);

			$TMPL['unq_pv_max_daily'] = $TMPL['unq_pv_0_daily'] > $TMPL['unq_pv_max_daily'] ? $TMPL['unq_pv_0_daily'] : $TMPL['unq_pv_max_daily'];
			$TMPL['tot_pv_max_daily'] = $TMPL['tot_pv_0_daily'] > $TMPL['tot_pv_max_daily'] ? $TMPL['tot_pv_0_daily'] : $TMPL['tot_pv_max_daily'];
			$TMPL['unq_in_max_daily'] = $TMPL['unq_in_0_daily'] > $TMPL['unq_in_max_daily'] ? $TMPL['unq_in_0_daily'] : $TMPL['unq_in_max_daily'];
			$TMPL['tot_in_max_daily'] = $TMPL['tot_in_0_daily'] > $TMPL['tot_in_max_daily'] ? $TMPL['tot_in_0_daily'] : $TMPL['tot_in_max_daily'];
			$TMPL['unq_out_max_daily'] = $TMPL['unq_out_0_daily'] > $TMPL['unq_out_max_daily'] ? $TMPL['unq_out_0_daily'] : $TMPL['unq_out_max_daily'];
			$TMPL['tot_out_max_daily'] = $TMPL['tot_out_0_daily'] > $TMPL['tot_out_max_daily'] ? $TMPL['tot_out_0_daily'] : $TMPL['tot_out_max_daily'];
			$TMPL['unq_pv_max_weekly'] = $TMPL['unq_pv_0_weekly'] > $TMPL['unq_pv_max_weekly'] ? $TMPL['unq_pv_0_weekly'] : $TMPL['unq_pv_max_weekly'];
			$TMPL['tot_pv_max_weekly'] = $TMPL['tot_pv_0_weekly'] > $TMPL['tot_pv_max_weekly'] ? $TMPL['tot_pv_0_weekly'] : $TMPL['tot_pv_max_weekly'];
			$TMPL['unq_in_max_weekly'] = $TMPL['unq_in_0_weekly'] > $TMPL['unq_in_max_weekly'] ? $TMPL['unq_in_0_weekly'] : $TMPL['unq_in_max_weekly'];
			$TMPL['tot_in_max_weekly'] = $TMPL['tot_in_0_weekly'] > $TMPL['tot_in_max_weekly'] ? $TMPL['tot_in_0_weekly'] : $TMPL['tot_in_max_weekly'];
			$TMPL['unq_out_max_weekly'] = $TMPL['unq_out_0_weekly'] > $TMPL['unq_out_max_weekly'] ? $TMPL['unq_out_0_weekly'] : $TMPL['unq_out_max_weekly'];
			$TMPL['tot_out_max_weekly'] = $TMPL['tot_out_0_weekly'] > $TMPL['tot_out_max_weekly'] ? $TMPL['tot_out_0_weekly'] : $TMPL['tot_out_max_weekly'];
			$TMPL['unq_pv_max_monthly'] = $TMPL['unq_pv_0_monthly'] > $TMPL['unq_pv_max_monthly'] ? $TMPL['unq_pv_0_monthly'] : $TMPL['unq_pv_max_monthly'];
			$TMPL['tot_pv_max_monthly'] = $TMPL['tot_pv_0_monthly'] > $TMPL['tot_pv_max_monthly'] ? $TMPL['tot_pv_0_monthly'] : $TMPL['tot_pv_max_monthly'];
			$TMPL['unq_in_max_monthly'] = $TMPL['unq_in_0_monthly'] > $TMPL['unq_in_max_monthly'] ? $TMPL['unq_in_0_monthly'] : $TMPL['unq_in_max_monthly'];
			$TMPL['tot_in_max_monthly'] = $TMPL['tot_in_0_monthly'] > $TMPL['tot_in_max_monthly'] ? $TMPL['tot_in_0_monthly'] : $TMPL['tot_in_max_monthly'];
			$TMPL['unq_out_max_monthly'] = $TMPL['unq_out_0_monthly'] > $TMPL['unq_out_max_monthly'] ? $TMPL['unq_out_0_monthly'] : $TMPL['unq_out_max_monthly'];
			$TMPL['tot_out_max_monthly'] = $TMPL['tot_out_0_monthly'] > $TMPL['tot_out_max_monthly'] ? $TMPL['tot_out_0_monthly'] : $TMPL['tot_out_max_monthly'];
	   
			// Number format only valid stats, after averages have been built
			// As average needs ints
			foreach ($row as $key => $value)
			{
				if (strpos($key, 'unq_') === 0 || strpos($key, 'tot_') === 0)
				{
					$TMPL[$key] = number_format($TMPL[$key]);
				}
			}
			
			// Prepare Category Url
			$category_raw = htmlspecialchars_decode($TMPL['category'], ENT_QUOTES);
			$TMPL['category_url'] = isset($CONF['categories'][$category_raw]) ? urlencode($CONF['categories'][$category_raw]['cat_slug']) : '';
		  
			//Get domain for use in templates
			$parse = parse_url($TMPL['url']);
			$TMPL['domain'] = $parse['host'];

			// Banner image/mp4 management - Order 1: Premium overwrite normal values
			if ($TMPL['premium_flag'] == 1) 
			{
				if(!empty($TMPL['premium_banner_url'])) 
				{
					$TMPL['banner_url']    = $TMPL['premium_banner_url'];
					$TMPL['mp4_url']       = $TMPL['premium_mp4_url'];
					$TMPL['banner_width']  = $TMPL['premium_banner_width'];
					$TMPL['banner_height'] = $TMPL['premium_banner_height'];
				}
				
				if(empty($CONF['disable_mp4']) && !empty($TMPL['premium_mp4_url'])) 
				{
					$TMPL['mp4_url'] = $TMPL['premium_mp4_url'];
				}
			}
			
			// Banner image/mp4 management - Order 2: If banner equals default, use button_config values
			if ($TMPL['banner_url'] == $CONF['default_banner'])
			{
				if(empty($CONF['disable_mp4']) && !empty($TMPL['mp4_url'])) 
				{
					$TMPL['mp4_url'] = $CONF['default_banner_mp4'];
				}
				
				$TMPL['banner_width']  = $CONF['default_banner_width'];
				$TMPL['banner_height'] = $CONF['default_banner_height'];
			}
			
			// Banner image/mp4 management - Order 3: width/height 
			// Only include these if width/height > 0 ( to avoid hidden banners/videos if getimagesize() failed when saving images )
			$TMPL['banner_aspect_base']  = '';
			$TMPL['banner_aspect_ratio'] = '';
			$TMPL['banner_width_height'] = '';
			if ($TMPL['banner_width'] > 0 && $TMPL['banner_height'] > 0)
			{
				$TMPL['banner_aspect_base']  = "--site-image-aspect-base: {$TMPL['banner_width']}px;";
				$TMPL['banner_aspect_ratio'] = "--site-image-aspect-ratio: {$TMPL['banner_width']}/{$TMPL['banner_height']};";
				$TMPL['banner_width_height'] = 'width="'.$TMPL['banner_width'].'" height="'.$TMPL['banner_height'].'"';
			}
			
			// Banner image/mp4 management - Order 4: Layout switcher
			if(empty($CONF['disable_mp4']) && !empty($TMPL['mp4_url'])) 
			{
				$TMPL['banner'] = $this->do_skin('banner_mp4');
			}
			else 
			{
				$TMPL['banner'] = $this->do_skin('banner');
			}


			// Plugin Hook - Ranking table
			eval (PluginManager::getPluginManager ()->pluginHooks ('rankings_compile_stats'));


			// Only use _top skin on the first page
			if ($page_rank <= $CONF['top_skin_num'] && $page == 1) {

				if ($TMPL['premium_flag'] == 1) {
					$TMPL['content'] .= $this->do_skin('table_top_row_premium');
				}
				else {
					$TMPL['content'] .= $this->do_skin('table_top_row');
				} 
				$is_top = 1;
			}
			else {
				// This sees if $do_table_open had been set during the last loop.  If so,
				// a new table_open is printed.  This keeps a table_open form being the
				// last thing on the page when there is an ad break at the end.
				if ($do_table_open) {
					$TMPL['content'] .= $this->do_skin('table_open');
					$do_table_open = 0;
				}

				if ($TMPL['premium_flag'] == 1) {
					$TMPL['content'] .= $this->do_skin('table_row_premium');
				}
				else {
					$TMPL['content'] .= $this->do_skin('table_row');
				}
				$top_done = 1;
				$is_top = 0;
			}

			if ($page_rank == $CONF['top_skin_num'] && $is_top) {
				$TMPL['content'] .= $this->do_skin('table_top_close');
				$do_table_open = 1;
			}

			if (isset($CONF['ad_breaks'][$page_rank])) {

				if ($is_top) {
					// Close top table if it is still open
					if (!$do_table_open) {
						$TMPL['content'] .= $this->do_skin('table_top_close');
					}

					// Plugin Hook - Used by Ad plugin for example
					eval (PluginManager::getPluginManager ()->pluginHooks ('ad_break_top'));

					$TMPL['content'] .= $this->do_skin('ad_break_top');
					
					if ($page_rank < $CONF['top_skin_num']) {
						$TMPL['content'] .= $this->do_skin('table_top_open');
					}
				}
				else {
					$TMPL['content'] .= $this->do_skin('table_close');

					// Plugin Hook - Used by Ad plugin for example
					eval (PluginManager::getPluginManager ()->pluginHooks ('ad_break'));

					$TMPL['content'] .= $this->do_skin('ad_break');
					$do_table_open = 1;
				}
			}

			$TMPL['rank']++;
			$page_rank++;
		}

		// If an ad break is directly after the last row, then there is no need to close the table
		if (!isset($CONF['ad_breaks'][--$page_rank]) || $CONF['fill_blank_rows']) {
			if ($top_done) {
				$do_table_close = 1;
			}
			elseif (!$do_table_open) {
				$do_table_top_close = 1;
				$TMPL['content'] .= $this->do_skin('table_top_close');
			}
		}
    }

	if ($CONF['fill_blank_rows'] && $page_rank < $CONF['num_list']) {
		if (!isset($TMPL['content'])) {
			$page_rank = 0;
			$TMPL['content'] = $this->do_skin('table_open');
		}
		if ((isset($do_table_top_close) && $do_table_top_close) || $do_table_open) {
			$TMPL['content'] .= $this->do_skin('table_open');
		}

		while ($page_rank < $CONF['num_list']) {
			$page_rank++;
			$TMPL['content'] .= $this->do_skin('table_filler');
			$TMPL['rank']++;
		}

		$TMPL['content'] .= $this->do_skin('table_close');
	}
	elseif (isset($do_table_close) && $do_table_close) {
		$TMPL['content'] .= $this->do_skin('table_close');
	}

   
	if (!isset($TMPL['content'])) { $TMPL['content'] = ''; }

    
	eval (PluginManager::getPluginManager ()->pluginHooks ('rankings_table_wrapper_extra'));


	$TMPL['content'] = $this->do_skin('table_wrapper');
  }
}
