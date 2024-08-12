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

class details extends base {

  public function __construct() {
    global $FORM, $LNG;

    if (isset($FORM['u'])) {
        $stats = new stats_site;
    }
    else {
        if (class_exists('stats_overall')) {
            $stats = new stats_overall;
        }
        else {
            $this->error($LNG['g_404']);
        }
    }
  }

  function averages() {
    global $TMPL;

    $ranking_periods = array('daily', 'weekly', 'monthly');
    $ranking_methods = array('unq_pv', 'tot_pv', 'unq_in', 'tot_in', 'unq_out', 'tot_out');
    foreach ($ranking_periods as $ranking_period) {
      foreach ($ranking_methods as $ranking_method) {
        $TMPL["{$ranking_method}_avg_{$ranking_period}"] = 0;
        for ($i = 0; $i < 10; $i++) {
          $TMPL["{$ranking_method}_avg_{$ranking_period}"] = $TMPL["{$ranking_method}_avg_{$ranking_period}"] + $TMPL["{$ranking_method}_{$i}_{$ranking_period}"];
        }
        $TMPL["{$ranking_method}_avg_{$ranking_period}"] = number_format($TMPL["{$ranking_method}_avg_{$ranking_period}"] / 10, 1);
      }
    }
  }

  function locale() {
    global $CONF, $LNG, $TMPL;

    setlocale(LC_ALL, $CONF['default_language']);
    for ($i = 2; $i < 10; $i++) {
      $TMPL["{$i}_daily"] = iconv('', 'UTF-8', strftime('%B %d', time()-3600*24*$i + (3600*$CONF['time_offset'])));
    }
    for ($i = 2; $i < 10; $i++) {
      $TMPL["{$i}_weekly"] = "{$LNG['stats_week']} ".date('W', time()-3600*24*7*$i + (3600*$CONF['time_offset']));
    }
    for ($i = 2; $i < 10; $i++) {
      $TMPL["{$i}_monthly"] = iconv('', 'UTF-8', strftime('%B %y', mktime(0, 0, 0, date('m')-$i, 1)));
    }
  }
}

class stats_site extends details {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

	if ($CONF['clean_url'] == 1 && preg_match('/\?/', $_SERVER['REQUEST_URI']))
	{
		$redirect_details = 'details';
		if (isset($FORM['all_reviews']) && $FORM['all_reviews'] == 1) {
			$redirect_details = 'reviews';
		}
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: {$CONF['list_url']}/{$redirect_details}/{$FORM['u']}/");
		exit;
	}

    $TMPL['header'] = $LNG['stats_header'];

    $TMPL['username'] = $DB->escape($FORM['u'], 1);
	$TMPL['stats_top'] = '';
	$TMPL['stats_social_pages'] = '';
	$TMPL['stats_under_title'] = '';
	$TMPL['stats_details'] = '';
	$TMPL['stats_before_stats'] = '';

    $stats = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_stats WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
    unset($stats['username']);
    $sites = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);
    if ($stats && $sites['active'] > 0) {

		$sites = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $sites);
		$stats = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $stats);

		$TMPL = array_merge($TMPL, $stats, $sites);

		// Canonical header real username
		$canonical_details = str_replace('&amp;', '&', "{$TMPL['list_url']}/{$TMPL['url_helper_a']}details{$TMPL['url_helper_u']}{$TMPL['username']}{$TMPL['url_tail']}");
		header("Link: <{$canonical_details}>; rel=\"canonical\"");

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

		// Build averages
		$this->averages();
		$TMPL['average_rating'] = $TMPL['num_ratings'] > 0 ? round($TMPL['total_rating'] / $TMPL['num_ratings'], 0) : 0;

		// Number format only valid stats, after averages have been built
		// As average needs ints
		foreach ($stats as $key => $value)
		{
			if (strpos($key, 'unq_') === 0 || strpos($key, 'tot_') === 0)
			{
				$TMPL[$key] = number_format($TMPL[$key]);
			}
		}


		// Plugin hook - Compile stats
		eval (PluginManager::getPluginManager ()->pluginHooks ('details_compile_details'));


		$this->locale();

		$TMPL['header']          .= " - {$TMPL['title']}";
		$TMPL['meta_description'] = $TMPL['description'];

		// Prepare Category Url
		$category_raw = htmlspecialchars_decode($TMPL['category'], ENT_QUOTES);
		$TMPL['category_url'] = isset($CONF['categories'][$category_raw]) ? urlencode($CONF['categories'][$category_raw]['cat_slug']) : '';

		////////////
		//REVIEW SECTION TO BE UPDATED
		////////////
		$reviews_on = 0;

		if($reviews_on == 1) {

			$query = "SELECT id, date, review FROM {$CONF['sql_prefix']}_reviews WHERE username = '{$TMPL['username']}' AND active = 1";
			if (isset($FORM['all_reviews']) && $FORM['all_reviews']) {
			  $result = $DB->query("{$query} ORDER BY date DESC", __FILE__, __LINE__);
			}
			else {
			  $result = $DB->select_limit("{$query} ORDER BY RAND()", 2, 0, __FILE__, __LINE__);
			}
			$TMPL['reviews'] = '';
			while (list($TMPL['id'], $TMPL['date'], $TMPL['review']) = $DB->fetch_array($result)) {

				$TMPL['review'] = htmlspecialchars($TMPL['review'], ENT_QUOTES, "UTF-8");
				$TMPL['reviews'] .= $this->do_skin('stats_review');
			}

		} else {
			//HIDE Review info in stats template
			$TMPL['reviews'] = '';
			$TMPL['stats_review_hide'] = ' style="display: none;"';
		}


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


		// Plugin Hook - add page elements
		eval (PluginManager::getPluginManager ()->pluginHooks ('details_build_page'));


		$TMPL['content'] = $this->do_skin('stats');
    }
    else {
      $this->error($LNG['g_invalid_u']);
    }
  }
}


// Plugin Hook - Can be used for a new class, like overall stats plugin
eval (PluginManager::getPluginManager ()->pluginHooks ('details_build_overall_page'));
