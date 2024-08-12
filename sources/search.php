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

class search extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

	if ($CONF['clean_url'] == 1 && preg_match('/\?/', $_SERVER['REQUEST_URI'])) 
	{
		$search = 'search/';

		if (isset($FORM['start'])) {
			$search .= $FORM['start'].'/';
		}
		if (isset($FORM['q'])) {
			$search .= preg_replace('/((\&)|(\/)|(\s))+/', '+', $FORM['q']).'/';
		}
			
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$CONF['list_url']}/{$search}");
		exit;
	}
	
    $TMPL['header'] = $LNG['search_header'];
	
	
    eval (PluginManager::getPluginManager ()->pluginHooks ('search_start'));

	
    if (!$CONF['search']) {
      $this->error($LNG['search_off']);
    }
    elseif (!isset($FORM['q']) || !$FORM['q']) {
      $this->form();
    }
    else {
      $this->process();
    }
  }

  function form() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    // Plugin hook - extend the html form
    eval (PluginManager::getPluginManager ()->pluginHooks ('search_build_form'));
 
    $TMPL['content'] = $this->do_skin('search_form');
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

	// On clear urls, certains chars are not allowed,
	// remove them before urlencoding and stuff
	if ($CONF['clean_url'] == 1) {
		$FORM['q'] = trim(preg_replace('/([\&\/]|[\s])+/u', ' ', $FORM['q']));
	}
	
    $TMPL['query'] = htmlentities(strip_tags($FORM['q']), ENT_QUOTES, "UTF-8");
    $query_url     = urlencode(strip_tags($FORM['q']));
	
	$TMPL['header'] .= " - {$TMPL['query']}";

    $words = explode(' ', $DB->escape($FORM['q']));

    // Filter out words that are only 1 or 2 characters
    $filtered_words = array_filter($words, function($word) {
		return mb_strlen($word) > 2;
	});

    if (count($filtered_words) > 0) {
      $words = $filtered_words;
    }

    // Plugin hook - give query more select colums
    $extra_columns = '';
    eval (PluginManager::getPluginManager ()->pluginHooks ('search_search_select'));

    $query = "SELECT * {$extra_columns} FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats WHERE active = 1 AND sites.username = stats.username ";
	
	$query_extend = 'AND (';
    $i = 0;
    foreach ($words as $word) {
      if ($i > 0) {
        $query_extend .= " OR ";
      }
      $query_extend .= "description LIKE '%{$word}%' OR title LIKE '%{$word}%'";

      // Plugin hook - extend inner search 
      eval (PluginManager::getPluginManager ()->pluginHooks ('search_search_query_inner'));

      $i++;
    }
    $query_extend .= ")";
	
	$query .= $query_extend;
	
    $order_by = $this->rank_by()." DESC";
    
    // Plugin hook, extend outer search where clause after above ended
    eval (PluginManager::getPluginManager ()->pluginHooks ('search_search_query'));

	// Pagination data
    list($pagination_rows) = $DB->fetch("SELECT COUNT(*) FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats WHERE active = 1 AND sites.username = stats.username {$query_extend}", __FILE__, __LINE__);
	
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
	
	// Only if old $FORM['start'] exist
	// Redirect page <= 1
	// Redirect page > max page
	// Redirect old start=xx to p=xx
	$pagination_redirect = false;
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
	
    // Figure out what rows we want
	$start = ($page * $CONF['num_list']) - $CONF['num_list'];
	
    if ($page_count > 1) 
	{
		// Previous control
        if ($page > 1) 
		{
			$previous_page  = $page - 1;		

            $TMPL['pagination_rel']  = 'rel="prev"';
            $TMPL['pagination_link'] = $CONF['list_url'];

			// No need start on page 1
			if ($previous_page == 1) 
			{
				$TMPL['pagination_link'] .= "/{$TMPL['url_helper_a']}search{$TMPL['url_helper_q']}{$query_url}";
			}
			elseif ($previous_page > 1) 
			{
				$TMPL['pagination_link'] .= "/{$TMPL['url_helper_a']}search{$TMPL['url_helper_page']}{$previous_page}{$TMPL['url_helper_q']}{$query_url}";	
			}
				
			eval (PluginManager::getPluginManager ()->pluginHooks ('search_pagination_previous'));
				
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
					
					// No need start on page 1
					if ($page_number == 1) 
					{
						$TMPL['pagination_link'] .= "/{$TMPL['url_helper_a']}search{$TMPL['url_helper_q']}{$query_url}";
					}
					elseif ($page_number > 1) 
					{
						$TMPL['pagination_link'] .= "/{$TMPL['url_helper_a']}search{$TMPL['url_helper_page']}{$page_number}{$TMPL['url_helper_q']}{$query_url}";		
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
        if ($next_page < $page_count) 
		{				
            $TMPL['pagination_rel']  = 'rel="next"';
			$TMPL['pagination_link'] = "{$CONF['list_url']}/{$TMPL['url_helper_a']}search{$TMPL['url_helper_page']}{$next_page}{$TMPL['url_helper_q']}{$query_url}";
			
			eval (PluginManager::getPluginManager ()->pluginHooks ('pagination_next'));
			
			$TMPL['pagination_link'] .= $TMPL['url_tail'];
			
			// Header for pagination rel next
			// Parameter false, so prev link header isnt overwritten
			$pagination_rel_next = str_replace('&amp;', '&', $TMPL['pagination_link']);
			header("Link: <{$pagination_rel_next}>; rel=\"next\"", false);
				
			$TMPL['pagination_next'] = $this->do_skin('pagination_next');
		}
    }
	
	$TMPL['search_pagination'] = $this->do_skin('pagination');
	
	// Result set
    $TMPL['num_results'] = $pagination_rows;

    $result = $DB->select_limit("{$query} ORDER BY {$order_by}", $CONF['num_list'], $start, __FILE__, __LINE__);

    $TMPL['results'] = '';
    $TMPL['rank'] = $start + 1;
    while ($row = $DB->fetch_array($result)) {
		
		$row = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $row);
			
		$TMPL = array_merge($TMPL, $row);

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
   
		// Number format only valid stats
		foreach ($row as $key => $value)
		{
			if (strpos($key, 'unq_') === 0 || strpos($key, 'tot_') === 0)
			{
				$TMPL[$key] = number_format($TMPL[$key]);
			}
		}
		
		foreach ($words as $word) 
		{
			$word = preg_quote($word);
			
			$TMPL['description'] = preg_replace_callback("/({$word})/i", function($matches) {
				return "<b>{$matches[1]}</b>";
			}, $TMPL['description']);
			
			$TMPL['title'] = preg_replace_callback("/({$word})/i", function($matches) {
				return "<b>{$matches[1]}</b>";
			}, $TMPL['title']);
		}

		// Prepare Category Url
		$category_raw = htmlspecialchars_decode($TMPL['category'], ENT_QUOTES);
		$TMPL['category_url'] = isset($CONF['categories'][$category_raw]) ? urlencode($CONF['categories'][$category_raw]['cat_slug']) : '';
		
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
		
			  
		// Plugin hook - do something with tmpl vars
		eval (PluginManager::getPluginManager ()->pluginHooks ('rankings_compile_stats'));

		$TMPL['results'] .= $this->do_skin('search_result');

		$TMPL['rank']++;
    }

    if (!$TMPL['results']) {
      $this->error($LNG['search_no_sites']);
    }

    $TMPL['displaying_results'] = sprintf($LNG['search_displaying_results'], ++$start, --$TMPL['rank'], $TMPL['num_results'], $TMPL['query']);

    // Plugin hook - the search page
    eval (PluginManager::getPluginManager ()->pluginHooks ('search_search_results'));

    $TMPL['content'] = $this->do_skin('search_results');
  }
}
