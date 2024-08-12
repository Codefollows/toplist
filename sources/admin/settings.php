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
error_reporting(E_ALL);
class settings extends base {
  public function __construct() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

    $TMPL['header'] = $LNG['a_s_header'];

	$TMPL['error_top'] = '';

    if (!isset($FORM['submit'])) {
      $this->form();
    }
    else {
      $this->process();
    }
  }

  function form() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;



	$TMPL['num_list'] = $CONF['num_list'];

	$TMPL['slider_perpage'] = '

		$( "#slider-range-min" ).slider({
			range: "min",
			value: '.$CONF['num_list'].',
			min: 1,
			max: 250,
			slide: function( event, ui ) {
	       		$( "#amount" ).val( "" + ui.value );
			}
        });

		$( "#amount" ).val( "" + $( "#slider-range-min" ).slider( "value" ) );

	';

    $languages_menu = '';
    $languages = array();
    $dir = opendir("{$CONF['path']}/languages/");
    while (false !== ($file = readdir($dir))) {
      $file = str_replace('.php', '', $file);
      if (is_file("{$CONF['path']}/languages/{$file}.php")) {
        $languages[$file] = $file;
      }
    }
    ksort($languages);
    foreach ($languages as $file => $translation) {
      if($file != 'importer'){
      if ($file == $CONF['default_language']) {
        $languages_menu .= "<option value=\"{$file}\" selected=\"selected\">{$file}</option>\n";
      }
      else {
        $languages_menu .= "<option value=\"{$file}\">{$file}</option>\n";
      }
      }
    }

    $ranking_period_menu = '';
    $ranking_periods = array('daily', 'weekly', 'monthly');
    foreach ($ranking_periods as $ranking_period) {
      if ($ranking_period == $CONF['ranking_period']) { $ranking_period_menu .= "<option value=\"{$ranking_period}\" selected=\"selected\">{$LNG["g_{$ranking_period}"]}</option>\n"; }
      else { $ranking_period_menu .= "<option value=\"{$ranking_period}\">{$LNG["g_{$ranking_period}"]}</option>\n"; }
    }

    $ranking_method_menu = '';
    $ranking_methods = array('pv', 'in', 'out');
    foreach ($ranking_methods as $ranking_method) {
      if ($ranking_method == $CONF['ranking_method']) { $ranking_method_menu .= "<option value=\"$ranking_method\" selected=\"selected\">{$LNG["g_{$ranking_method}"]}</option>\n"; }
      else { $ranking_method_menu .= "<option value=\"$ranking_method\">{$LNG["g_{$ranking_method}"]}</option>\n"; }
    }

    if ($CONF['ranking_average']) {
      $ranking_average_menu = "<option value=\"1\" selected=\"selected\">{$LNG['g_average']}</option>\n<option value=\"0\">{$LNG['g_this_period']}</option>\n";
    }
    else {
      $ranking_average_menu = "<option value=\"1\">{$LNG['g_average']}</option>\n<option value=\"0\" selected=\"selected\">{$LNG['g_this_period']}</option>\n";
    }
    $LNG['a_s_ranking_average'] = sprintf($LNG['a_s_ranking_average'], strtolower($LNG['g_this_period']));

	if ($CONF['maintenance_mode']) {
      $maintenance_mode_menu = '<div class="checksize"><input type="checkbox" name="maintenance_mode" id="maintenance_mode" checked="yes"/></div>';
    }
    else {
      $maintenance_mode_menu = '<div class="checksize"><input type="checkbox" name="maintenance_mode" id="maintenance_mode" /></div>';
    }

    if ($CONF['search']) {
      $search_menu = "<option value=\"1\" selected=\"selected\">{$LNG['a_s_on']}</option>\n<option value=\"0\">{$LNG['a_s_off']}</option>\n";
    }
    else {
      $search_menu = "<option value=\"1\">{$LNG['a_s_on']}</option>\n<option value=\"0\" selected=\"selected\">{$LNG['a_s_off']}</option>\n";
    }

    if ($CONF['featured_member'] == 1) {
      $featured_member_menu = "<div class=\"checksize\"><input type=\"checkbox\" id=\"featured_member_box\" name=\"featured_member\" checked=\"yes\"/> </div>";
    }
    else {
      $featured_member_menu = "<div class=\"checksize\"><input type=\"checkbox\" name=\"featured_member\" id=\"featured_member_box\"/> </div>";
    }

    if ($CONF['fill_blank_rows']) {
      $fill_blank_rows_menu = '<div class="checksize"><input type="checkbox" name="fill_blank_rows" id="fill_blank_rows" checked="yes"/></div>';
    }
    else {
      $fill_blank_rows_menu = '<div class="checksize"><input type="checkbox" name="fill_blank_rows" id="fill_blank_rows" /></div>';
    }

    if ($CONF['active_default']) {
      $active_default_menu = '<div class="checksize"><input type="checkbox" name="active_default"/></div>';
    }
    else {
      $active_default_menu = '<div class="checksize"><input type="checkbox" name="active_default"  checked="yes"/></div>';
    }

    if ($CONF['active_default_review']) {
      $active_default_review_menu = '<div class="checksize"><input type="checkbox" name="active_default_review"/></div>';
    }
    else {
      $active_default_review_menu = '<div class="checksize"><input type="checkbox" name="active_default_review" checked="yes"/></div>';
    }

    if ($CONF['email_admin_on_join']) {
      $email_admin_on_join_menu = '<div class="checksize"><input type="checkbox" name="email_admin_on_join" id="email_admin_on_join" checked="yes"/></div>';
    }
    else {
      $email_admin_on_join_menu = '<div class="checksize"><input type="checkbox" name="email_admin_on_join" id="email_admin_on_join" /></div>';
    }

    if ($CONF['email_admin_on_review']) {
      $email_admin_on_review_menu = '<div class="checksize"><input type="checkbox" name="email_admin_on_review" id="email_admin_on_review" checked="yes"/></div>';
    }
    else {
      $email_admin_on_review_menu = '<div class="checksize"><input type="checkbox" name="email_admin_on_review" id="email_admin_on_review" /></div>';
    }

    if ($CONF['gateway']) {
      $gateway_menu = '<div class="checksize"><input type="checkbox" name="gateway" checked="yes"/></div>';
    }
    else {
      $gateway_menu = '<div class="checksize"><input type="checkbox" name="gateway"/></div>';
    }

    if ($CONF['captcha']) {
      $captcha_menu = '<div class="checksize"><input type="checkbox" name="captcha" checked="yes"/></div>';
    }
    else {
      $captcha_menu = '<div class="checksize"><input type="checkbox" name="captcha"/></div>';
    }


    if ($CONF['recaptcha']) {
      $recaptcha_menu = '<div class="checksize"><input type="checkbox" name="recaptcha" checked="yes"/></div>';
    }
    else {
      $recaptcha_menu = '<div class="checksize"><input type="checkbox" name="recaptcha"/></div>';
    }

    if ($CONF['lostpw_recaptcha']) {
      $lostpw_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="lostpw_recaptcha" checked="yes"/></div>';
    }
    else {
      $lostpw_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="lostpw_recaptcha"/></div>';
    }

    if ($CONF['admin_recaptcha']) {
      $admin_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="admin_recaptcha" checked="yes"/></div>';
    }
    else {
      $admin_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="admin_recaptcha"/></div>';
    }

    if ($CONF['usercp_recaptcha']) {
      $usercp_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="usercp_recaptcha" checked="yes"/></div>';
    }
    else {
      $usercp_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="usercp_recaptcha"/></div>';
    }
    if ($CONF['gateway_recaptcha']) {
      $gateway_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="gateway_recaptcha" checked="yes"/></div>';
    }
    else {
      $gateway_recaptcha_menu = '<div class="checksize"><input type="checkbox" name="gateway_recaptcha"/></div>';
    }


    if ($CONF['google_friendly_links']) {
      $google_friendly_links_menu = '<div class="checksize"><input type="checkbox" name="google_friendly_links" id="google_friendly_links" checked="yes"/></div>';
    }
    else {
      $google_friendly_links_menu = '<div class="checksize"><input type="checkbox" name="google_friendly_links" id="google_friendly_links" /></div>';
    }

	if ($CONF['auto_approve_premium']) {
      $auto_approve_premium_menu = '<div class="checksize"><input type="checkbox" name="auto_approve_premium" id="auto_approve_premium" checked="yes"/></div>';
    }
    else {
      $auto_approve_premium_menu = '<div class="checksize"><input type="checkbox" name="auto_approve_premium" id="auto_approve_premium" /></div>';
    }

    if ($CONF['premium_order_by']) {
      $premium_order_by_menu = "<option value=\"1\" selected=\"selected\">{$LNG['a_s_premium_start_date']}</option>\n<option value=\"0\">{$LNG['g_random']}</option>\n";
    }
    else {
      $premium_order_by_menu = "<option value=\"1\">{$LNG['a_s_premium_start_date']}</option>\n<option value=\"0\" selected=\"selected\">{$LNG['g_random']}</option>\n";
    }

    if ($CONF['clean_url']) {
      $clean_url_menu = '<div class="checksize"><input type="checkbox" name="clean_url" id="clean_url" checked="yes"/></div>';
    }
    else {
      $clean_url_menu = '<div class="checksize"><input type="checkbox" name="clean_url" id="clean_url" /></div>';
    }

    $ad_breaks = implode(',', $CONF['ad_breaks']);

	// 2step security select
	$TMPL['2step']      = !empty($TMPL['2step']) ? (int)$TMPL['2step'] : $CONF['2step'];
	$TMPL['form_2step'] = generate_select('2step', $LNG['a_2step'], '0, 1, 2', "{$LNG['2step_none']}, {$LNG['2step_email']}, {$LNG['2step_google']}");

	require_once("{$CONF['path']}/sources/misc/GoogleAuthenticator.php");
	$ga = new PHPGangsta_GoogleAuthenticator();

	$TMPL['2step_secret'] = $CONF['2step_secret'];
	if (empty($TMPL['2step_secret'])) {

		$TMPL['2step_secret'] = $ga->createSecret();
		$TMPL['2step_secret'] = $DB->escape($TMPL['2step_secret'], 1);

		$DB->query("UPDATE {$CONF['sql_prefix']}_settings SET 2step_secret = '{$TMPL['2step_secret']}'", __FILE__, __LINE__);
	}

	// 2step QR code for google
	$TMPL['2step_qr_code'] = $ga->getQRCodeGoogleUrl("{$TMPL['list_url']}/ - admin", $TMPL['2step_secret']);

	// 2step google validation input
	// Not required to fill, but user can use it to validate the app setup, upon save validates the code
	$TMPL['2step_validate']         = isset($TMPL['2step_validate']) ? htmlspecialchars(stripslashes($TMPL['2step_validate']), ENT_QUOTES, "UTF-8") : '';
	$TMPL['form_2step_validate']    = generate_input('2step_validate', $LNG['2step_google_label'], 50, 0);

	// 2step google information layout
	$TMPL['2step_google_info_hide'] = $TMPL['2step'] == 2 ? '' : 'style="display: none;"';
	$TMPL['2step_google_info']      = $this->do_skin('settings_form_qr_code');


    //initialize plugin variables
    $admin_general_settings = '';
    $admin_ranking_settings = '';
    $admin_member_settings = '';
    $admin_button_settings = '';
    $admin_security_settings = '';
    $admin_premium_banner_settings = '';
    $admin_other_settings = '';
    $admin_new_tab_settings = '';


	eval (PluginManager::getPluginManager ()->pluginHooks ('admin_settings_build_page'));


	// for each payment provider, construct settings
	require_once("{$CONF['path']}/sources/misc/Payment.php");
	$Payment = new Payment();
	
	$payment_providers = $Payment->getProviders([], true);
	foreach ($payment_providers as $provider => $provider_config)
	{
		$admin_new_tab_settings .= "<h3>Payment Provider: {$provider}</h3><div>";
		
		foreach ($provider_config as $setting => $setting_data)
		{
			$label = $setting_data['label'];
			
			// Enabled setting can have documention link
			if ($setting === 'enabled' && isset($setting_data['docs']))
			{
				$label .= ' <a href="'.$setting_data['docs'].'" rel="noopener" target="_blank">View Documentation</a>';
			}
			
			$admin_new_tab_settings .= "<label for=\"{$provider}_{$setting}\">{$label}</label>";

			if ($setting_data['type'] == 'checkbox')
			{
				if ($setting_data['value'] === true) {
					$admin_new_tab_settings .= '<div class="checksize"><input type="checkbox" name="payment_providers['.$provider.']['.$setting.']" id="'.$provider.'_'.$setting.'" checked="yes"/></div>';
				}
				else {
					$admin_new_tab_settings .= '<div class="checksize"><input type="checkbox" name="payment_providers['.$provider.']['.$setting.']" id="'.$provider.'_'.$setting.'" /></div>';
				}
			}
			elseif ($setting_data['type'] == 'input') 
			{
				$setting_value           = htmlspecialchars(stripslashes($setting_data['value']), ENT_QUOTES, "UTF-8");
				$admin_new_tab_settings .= '<input type="text" name="payment_providers['.$provider.']['.$setting.']" id="'.$provider.'_'.$setting.'" value="'.$setting_value.'" class="default_input" />';
			}
			elseif ($setting_data['type'] == 'textarea') 
			{
				$setting_value           = htmlspecialchars(stripslashes($setting_data['value']), ENT_QUOTES, "UTF-8");
				$admin_new_tab_settings .= '<textarea name="payment_providers['.$provider.']['.$setting.']" id="'.$provider.'_'.$setting.'" class="default_input" row="5">'.$setting_value.'</textarea>';
			}
		}
		
		$admin_new_tab_settings .= '</div>';
	}
	
	
    include('button_config.php');
    if($CONF['count_pv'] == 1) { $count_pv_checked = ' CHECKED'; } else { $count_pv_checked = ''; }
    if($CONF['text_link'] == 1) { $text_link_checked = ' CHECKED'; } else { $text_link_checked = ''; }
    if($CONF['static_button'] == 1) { $static_button_checked = ' CHECKED'; } else { $static_button_checked = ''; }
    if($CONF['rank_button'] == 1) { $rank_button_checked = ' CHECKED'; } else { $rank_button_checked = ''; }
    if($CONF['stats_button'] == 1) { $stats_button_checked = ' CHECKED'; } else { $stats_button_checked = ''; }
    if(empty($CONF['text_link_button_alt'])) { $CONF['text_link_button_alt'] = $CONF['list_name']; }

	// Button Preview check
    if(empty($CONF['static_button_url'])) {
	    $static_button_preview = "{$CONF['list_url']}/images/button.png";
	} else { $static_button_preview = $CONF['static_button_url']; }
    if(empty($CONF['button_dir']) && empty($CONF['button_ext'])) {
	    $rank_button_preview = "{$CONF['list_url']}/images/1.gif";
	} else { $rank_button_preview = "{$CONF['button_dir']}/1.{$CONF['button_ext']}"; }


    $current_date = date("Y-m-d H:i:s");

    $TMPL['admin_content'] = <<<EndHTML

<form action="index.php?a=admin&amp;b=settings" method="post">
<div id="accordion">
{$TMPL['error_top']}

<h3>{$LNG['a_s_general']}</h3>
<div>
<label for="list_name">{$LNG['a_s_list_name']}</label>
<input type="text" name="list_name" id="list_name" value="{$CONF['list_name']}" class="default_input" />

<label for="list_url">{$LNG['a_s_list_url']}</label>
<input type="text" name="list_url" id="list_url" value="{$CONF['list_url']}" class="default_input" />

<label for="default_language">{$LNG['a_s_default_language']}</label>
<select name="default_language" id="default_language">
{$languages_menu}</select>

<label for="your_email">{$LNG['a_s_your_email']}</label>
<input type="text" name="your_email" id="your_email" value="{$CONF['your_email']}" class="default_input" />

<label for="your_email">{$LNG['a_s_admin_password']}</label>
<input type="text" name="admin_password" id="admin_password" value="" class="default_input" />


<label for="clean_url">{$LNG['a_s_clean_urls']}</label>
{$clean_url_menu}

<label for="maintenance_mode">{$LNG['a_s_maintenance_mode']}</label>
{$maintenance_mode_menu}

{$admin_general_settings}

</div>




<h3>{$LNG['a_s_ranking']}</h3>
<div>
<label for="amount">{$LNG['a_s_num_list']}</label>
<div id="slider-range-min" style="margin: 5px 0;"></div>
<input type="text" name="num_list" size="5" id="amount" value="{$CONF['num_list']}" />


<label for="ranking_period">{$LNG['a_s_ranking_period']}</label>
<select name="ranking_period" id="ranking_period">
{$ranking_period_menu}</select>

<label for="ranking_method">{$LNG['a_s_ranking_method']}</label>
<select name="ranking_method" id="ranking_method">
{$ranking_method_menu}</select>

<label for="ranking_average">{$LNG['a_s_ranking_average']}</label>
<select name="ranking_average" id="ranking_average">
{$ranking_average_menu}</select>

<label for="featured_member_box">{$LNG['a_s_featured_member']}</label>
{$featured_member_menu}

<label for="top_skin_num">{$LNG['a_s_top_skin_num']}</label>
<input type="text" name="top_skin_num" id="top_skin_num" size="5" value="{$CONF['top_skin_num']}" />

<label id="ad_breaks">{$LNG['a_s_ad_breaks']}</label>
<input type="text" name="ad_breaks" id="ad_breaks" size="20" value="{$ad_breaks}" />

<label for="fill_blank_rows">{$LNG['a_s_fill_blank_rows']}</label>
{$fill_blank_rows_menu}


{$admin_ranking_settings}

</div>

<h3>{$LNG['a_s_member']}</h3>
<div>
{$active_default_menu} {$LNG['a_s_active_default']}

<br /><br />

{$active_default_review_menu} {$LNG['a_s_active_default_review']}


<label for="inactive_after">{$LNG['a_s_inactive_after']}</label>
<input type="text" name="inactive_after" id="inactive_after" size="5" value="{$CONF['inactive_after']}" />

<label for="email_admin_on_join">{$LNG['a_s_email_admin_on_join']}</label>
{$email_admin_on_join_menu}

<label for="email_admin_on_review">{$LNG['a_s_email_admin_on_review']}</label>
{$email_admin_on_review_menu}

<label for="max_banner_width">{$LNG['a_s_max_banner_width']}</label>
<input type="text" name="max_banner_width" id="max_banner_width" size="5" value="{$CONF['max_banner_width']}" />

<label for="max_banner_height">{$LNG['a_s_max_banner_height']}</label>
<input type="text" name="max_banner_height" id="max_banner_height" size="5" value="{$CONF['max_banner_height']}" />

<label for="default_banner">{$LNG['a_s_default_banner']}</label>
<input type="text" name="default_banner" id="default_banner" value="{$CONF['default_banner']}" class="default_input" />

{$admin_member_settings}

</div>

<h3>{$LNG['a_s_button']}</h3>

<div>
<h2>{$LNG['a_s_button_backlink_choices']}</h2>


<div class="option-box">
  <label for="text_link_button_alt">{$LNG['a_s_text_link_button_alt']}</label>
  <input type="text" name="text_link_button_alt" id="text_link_button_alt" value="{$CONF['text_link_button_alt']}" class="default_input" />
</div>

<div class="option-box">
<div class="preview-box">{$LNG['a_s_button_preview']}: <a href="{$CONF['list_url']}">{$CONF['text_link_button_alt']}</a></div>
<input type="checkbox" name="text_link"{$text_link_checked}> {$LNG['a_s_button_o_textlink']}
</div>


<div class="option-box">
<div class="preview-box">{$LNG['a_s_button_preview']}: <a href="{$CONF['list_url']}"><img src="{$static_button_preview}" id="preview_button_url" alt="{$CONF['text_link_button_alt']}"></a></div>
<input type="checkbox" name="static_button"{$static_button_checked}> {$LNG['a_s_button_o_static']} <br /><br />


		<div id="static_options" title="Button Settings">

            <label for="static_button_url">{$LNG['a_s_button_url_static']}</label>
            <input type="text" name="static_button_url" id="static_button_url" class="default_input" value="{$CONF['static_button_url']}" />

       </div>


</div>


<div class="option-box">
<div class="preview-box">{$LNG['a_s_button_preview']}: <a href="{$CONF['list_url']}"><img src="{$rank_button_preview}" alt="{$CONF['text_link_button_alt']}"></a></div>
<input type="checkbox" name="rank_button"{$rank_button_checked}> {$LNG['a_s_button_o_rank']}  <br /><br />



		<div id="dialog" title="Button Settings">

<label for="default_rank_button">{$LNG['a_s_button_url_rank']}</label>
<input type="text" name="default_rank_button" id="default_rank_button" class="default_input" value="{$CONF['default_rank_button']}" />

<label for="button_dir">{$LNG['a_s_button_dir']}</label>
<input type="text" name="button_dir" id="button_dir" class="default_input" value="{$CONF['button_dir']}" />

<label for="button_ext">{$LNG['a_s_button_ext']}</label>
<select name="button_ext" id="button_ext">
<option>{$CONF['button_ext']}</option>
<option>gif</option>
<option>jpg</option>
<option>jpeg</option>
<option>png</option>
</select>


<label for="button_num">{$LNG['a_s_button_num']}</label>
<input type="text" name="button_num" id="button_num" size="5" value="{$CONF['button_num']}" />


 </div>
</div>




<div class="option-box">
<div class="preview-box">{$LNG['a_s_button_preview']}: <a href="{$CONF['list_url']}"><img src="{$CONF['list_url']}/images/ranking.png" alt="{$CONF['text_link_button_alt']}"></a></div>
<input type="checkbox" name="stats_button"{$stats_button_checked}> {$LNG['a_s_button_o_dynamic']} <br /><br class="cb"/>
</div>

<h2>More Button Settings</h2>
<div class="option-box">
<label for="google_friendly_links">{$LNG['a_s_google_friendly_links']}</label>
{$google_friendly_links_menu}


<p>{$LNG['a_s_button_info']}
<input type="checkbox" name="count_pv"{$count_pv_checked}>
<i>{$LNG['a_s_button_info2']}</i></p>
</div>


{$admin_button_settings}

</div>

<h3>{$LNG['join_security']}</h3>
<div>

<div>{$gateway_menu} {$LNG['a_s_gateway']}</div><br />
<div>{$captcha_menu} {$LNG['a_s_captcha']}</div><br />

<div>{$recaptcha_menu} {$LNG['a_s_recaptcha']}</div>
<div>{$lostpw_recaptcha_menu} {$LNG['a_s_lostpw_recaptcha']}</div>
<div>{$admin_recaptcha_menu} {$LNG['a_s_admin_recaptcha']}</div>
<div>{$usercp_recaptcha_menu} {$LNG['a_s_usercp_recaptcha']}</div>
<div>{$gateway_recaptcha_menu} {$LNG['a_s_gateway_recaptcha']}</div>
<p>{$LNG['a_s_recaptcha_info']} - <a href="https://www.google.com/recaptcha/admin/create" target="_blank">{$LNG['a_s_recaptcha_clickhere']}</a></p>

{$LNG['a_s_recaptcha_sitekey']}: <input type="text" name="recaptcha_sitekey" class="default_input" value="{$CONF['recaptcha_sitekey']}" /><br />
{$LNG['a_s_recaptcha_secret']}: <input type="text" name="recaptcha_secret" class="default_input" value="{$CONF['recaptcha_secret']}" /><br />
<br />

<div>{$TMPL['form_2step']}</div>
<div>{$TMPL['2step_google_info']}</div>
<br />
<hr />


<label for="security_question">{$LNG['a_s_security_question']}</label>
<input type="text" name="security_question" id="security_question" class="default_input" value="{$CONF['security_question']}" />

<label for="security_answer">{$LNG['a_s_answer']}</label>
<input type="text" name="security_answer" id="security_answer" size="25" value="{$CONF['security_answer']}" />


{$admin_security_settings}

</div>





<h3>{$LNG['a_s_premium_banner_settings']}</h3>
<div>
<div style="width:400px;display:inline-block;border-right: 1px solid #ccc;margin-right: 10px;">
<label for="bn_pr_width">{$LNG['a_s_premium_banner_width']}</label>
<input type="text" name="bn_pr_width" id="bn_pr_width" size="5" value="{$CONF['max_premium_banner_width']}" />&nbsp;px

<label for="bn_pr_height">{$LNG['a_s_premium_banner_height']}</label>
<input type="text" name="bn_pr_height" id="bn_pr_height" size="5" value="{$CONF['max_premium_banner_height']}" />&nbsp;px<br /><br />
{$LNG['a_s_premium_banner_alert']}
<br />

<label for="currency_code">{$LNG['a_s_premium_currency_code']}</label>
<input type="text" name="currency_code" id="currency_code" size="5" value="{$CONF['currency_code']}" />

<label for="currency_symbol">{$LNG['a_s_premium_currency_symbol']}</label>
<input type="text" name="currency_symbol" id="currency_symbol" size="5" value="{$CONF['currency_symbol']}" />

<table>
    <tr>
        <td>
            <label for="new_day_boost">{$LNG['a_s_premium_new_day_boost']}</label>
            <input type="text" name="new_day_boost" id="new_day_boost" size="5" value="{$CONF['new_day_boost']}" />
        </td>
        <td>
            <label for="new_week_boost">{$LNG['a_s_premium_new_week_boost']}</label>
            <input type="text" name="new_week_boost" id="new_week_boost" size="5" value="{$CONF['new_week_boost']}" />
        </td>
        <td>
            <label for="new_month_boost">{$LNG['a_s_premium_new_month_boost']}</label>
            <input type="text" name="new_month_boost" id="new_month_boost" size="5" value="{$CONF['new_month_boost']}" />
        </td>
    </tr>
</table>

<br /><br />

<h3>{$LNG['a_s_premium_sidebar']}</h3>
<label for="premium_number">{$LNG['a_s_premium_number']}</label>
<input type="text" name="premium_number" id="premium_number" size="5" value="{$CONF['premium_number']}" />

<label for="premium_order_by">{$LNG['a_s_premium_order_by']}</label>
<select name="premium_order_by" id="premium_order_by">
    {$premium_order_by_menu}
</select>


{$admin_premium_banner_settings}

</div>



<div style="width:400px;display:inline-block;vertical-align:top;">
<div class="highlight">
<label>
<strong>{$LNG['a_s_premium_price_one_week']}:</strong> <input type="text" name="one_w_price" size="5" value="{$CONF['one_w_price']}" />
</label>
</div>


<label for="auto_approve_premium">{$LNG['a_s_premium']}</label>
{$auto_approve_premium_menu}




</div>


</div>


<h3>{$LNG['a_s_premium_discount_fieldset']}</h3>
<div>

<div class="quarter">
<label for="discount_qty_01">{$LNG['a_s_premium_disc_qty_01']}</label>
</div>

<div class="quarter">
    <input type="text" name="discount_qty_01" size="5" value="{$CONF['discount_qty_01']}" id="discount_qty_01" />
</div>

<div class="quarter">
<label for="discount_value_01">{$LNG['a_s_premium_disc_value']}</label>
</div>

<div class="quarter">
    <input type="text" name="discount_value_01" size="3" value="{$CONF['discount_value_01']}" id="discount_value_01" />&nbsp;(%)
</div>

<div class="quarter">
<label for="discount_qty_02">{$LNG['a_s_premium_disc_qty_02']}</label>
</div>
<div class="quarter">
    <input type="text" name="discount_qty_02" size="5" value="{$CONF['discount_qty_02']}" id="discount_qty_02" />
</div>

<div class="quarter">
<label for="discount_value_02">{$LNG['a_s_premium_disc_value']}</label>
</div>

<div class="quarter">
    <input type="text" name="discount_value_02" size="3" value="{$CONF['discount_value_02']}" id="discount_value_02" />&nbsp;(%)
</div>
<div class="quarter">
<label for="discount_qty_03">{$LNG['a_s_premium_disc_qty_03']}</label>
</div>

<div class="quarter">
    <input type="text" name="discount_qty_03" size="5" value="{$CONF['discount_qty_03']}" id="discount_qty_03" />
</div>
<div class="quarter">
<label for="discount_value_03">{$LNG['a_s_premium_disc_value']}</label>
</div>
<div class="quarter">
    <input type="text" name="discount_value_03" size="3" value="{$CONF['discount_value_03']}" id="discount_value_03" />&nbsp;(%)
</div>

</div>



<h3>{$LNG['a_s_other']}</h3>
<div>
<label for="search1">{$LNG['a_s_search']}</label>
<select name="search" id="search1">
{$search_menu}
</select>

<label for="time_zone">{$LNG['a_s_time_zone']} <a href="http://php.net/manual/en/timezones.php" target="_blank">?</a></label>
<input type="text" name="time_zone" id="time_zone"  value="{$CONF['time_zone']}" /> {$current_date}

<label for="time_offset">{$LNG['a_s_time_offset']}</label>
<input type="text" name="time_offset" id="time_offset" size="5" value="{$CONF['time_offset']}" />

<label for="smtp_host">{$LNG['a_s_smtp_host']}</label>
<input type="text" name="smtp_host" id="smtp_host" value="{$CONF['smtp_host']}" />

<label for="smtp_user">{$LNG['a_s_smtp_user']}</label>
<input type="text" name="smtp_user" id="smtp_user" value="{$CONF['smtp_user']}" />

<label for="smtp_password">{$LNG['a_s_smtp_password']}</label>
<input type="password" name="smtp_password" id="smtp_password" value="{$CONF['smtp_password']}" />

<label for="smtp_port">{$LNG['a_s_smtp_port']}</label>
<input type="text" name="smtp_port" id="smtp_port" size="5" value="{$CONF['smtp_port']}" /><br /><br />


{$admin_other_settings}

</div>

{$admin_new_tab_settings}

</div>

<br />


<div class="buttons">
    <button type="submit" name="submit" class="positive">
        {$LNG['a_s_header']}
    </button>
</div>


</form>

EndHTML;
  }

  function process() {
    global $CONF, $DB, $FORM, $LNG, $TMPL;

	$form_validate = array();

    $list_name = $DB->escape($FORM['list_name']);
    $list_url = $DB->escape(rtrim($FORM['list_url'], '/'));
    $default_language = $DB->escape($FORM['default_language']);
    $your_email = $DB->escape($FORM['your_email']);

	if(isset($FORM['clean_url']) && $FORM['clean_url'] == 'on') {$clean_url = 1;} else {$clean_url = 0;}

    if(isset($FORM['maintenance_mode']) && $FORM['maintenance_mode'] == 'on') {$maintenance_mode = 1;} else {$maintenance_mode = 0;}

    $num_list = intval($FORM['num_list']);
    if ($num_list < 1) { $num_list = 1; } // Things get messed up if num_list is not a positive integer
    $ranking_period = $DB->escape($FORM['ranking_period']);
    $ranking_method = $DB->escape($FORM['ranking_method']);
    $ranking_average = intval($FORM['ranking_average']);
    $featured_member = '';
    if(isset($FORM['featured_member']) && $FORM['featured_member'] == 'on') {$featured_member = 1;} else {$featured_member = 0;}

    $top_skin_num = intval($FORM['top_skin_num']);
    $ad_breaks = $DB->escape($FORM['ad_breaks']);
    $ad_breaks = preg_replace('/[^0-9,]/m', '', $ad_breaks);

    if(isset($FORM['fill_blank_rows']) && $FORM['fill_blank_rows'] == 'on') {$fill_blank_rows = 1;} else {$fill_blank_rows = 0;}
    if(isset($FORM['recaptcha']) && $FORM['recaptcha'] == 'on') {$recaptcha = 1;} else {$recaptcha = 0;}
    if(isset($FORM['lostpw_recaptcha']) && $FORM['lostpw_recaptcha'] == 'on') {$lostpw_recaptcha = 1;} else {$lostpw_recaptcha = 0;}    //ADDED 1.8
    if(isset($FORM['admin_recaptcha']) && $FORM['admin_recaptcha'] == 'on') {$admin_recaptcha = 1;} else {$admin_recaptcha = 0;}    //ADDED 1.8
    if(isset($FORM['usercp_recaptcha']) && $FORM['usercp_recaptcha'] == 'on') {$usercp_recaptcha = 1;} else {$usercp_recaptcha = 0;}    //ADDED 1.8
    if(isset($FORM['gateway_recaptcha']) && $FORM['gateway_recaptcha'] == 'on') {$gateway_recaptcha = 1;} else {$gateway_recaptcha = 0;}    //ADDED 1.8
    $recaptcha_sitekey = $DB->escape($FORM['recaptcha_sitekey']);
    $recaptcha_secret = $DB->escape($FORM['recaptcha_secret']);

    $smtp_host = $DB->escape($FORM['smtp_host']);
    $smtp_user = $DB->escape($FORM['smtp_user']);
    $smtp_password = $DB->escape($FORM['smtp_password']);
    $smtp_port = $DB->escape($FORM['smtp_port']);

    if(isset($FORM['active_default']) && $FORM['active_default'] == 'on') {$active_default = 0;} else {$active_default = 1;}

    if(isset($FORM['active_default_review']) && $FORM['active_default_review'] == 'on') {$active_default_review = 0;} else {$active_default_review = 1;}

    $inactive_after = intval($FORM['inactive_after']);

    if(isset($FORM['email_admin_on_join']) && $FORM['email_admin_on_join'] == 'on') {$email_admin_on_join = 1;} else {$email_admin_on_join = 0;}

    if(isset($FORM['email_admin_on_review']) && $FORM['email_admin_on_review'] == 'on') {$email_admin_on_review = 1;} else {$email_admin_on_review = 0;}

    $max_banner_width = intval($FORM['max_banner_width']);
    $max_banner_height = intval($FORM['max_banner_height']);

    $default_banner = $DB->escape($FORM['default_banner']);

	$default_banner_width  = 0;
	$default_banner_height = 0;
	if (!empty($default_banner))
	{
		$default_banner_extension = $this->getExtension($default_banner);
		$default_banner_extension = strtolower($default_banner_extension);

		// Hosted on list url or external sourse
		if (stripos($default_banner, $list_url) === 0)
		{
			$default_banner_path = str_replace($list_url, $CONF['path'], $default_banner);
			$default_banner_size = getimagesize($default_banner_path);

			if (!empty($default_banner_size))
			{
				$default_banner_width  = (int)$default_banner_size[0];
				$default_banner_height = (int)$default_banner_size[1];
			}
		}
		elseif (ini_get('allow_url_fopen'))
		{
			$default_banner_size = @getimagesize($default_banner);

			if (!empty($default_banner_size))
			{
				$default_banner_width  = (int)$default_banner_size[0];
				$default_banner_height = (int)$default_banner_size[1];
			}
		}
	}

    if(isset($FORM['count_pv']) && $FORM['count_pv'] == 'on') {$count_pv = 1;} else {$count_pv = 0;}

    if($ranking_method == 'pv') {
		$count_pv = 1;
	}

    if(isset($FORM['text_link']) && $FORM['text_link'] == 'on') {$text_link = 1;} else {$text_link = 0;}
    if(isset($FORM['static_button']) && $FORM['static_button'] == 'on') {$static_button = 1;} else {$static_button = 0;}
    if(isset($FORM['rank_button']) && $FORM['rank_button'] == 'on') {$rank_button = 1;} else {$rank_button = 0;}
    if(isset($FORM['stats_button']) && $FORM['stats_button'] == 'on') {$stats_button = 1;} else {$stats_button = 0;}
    $text_link_button_alt = $DB->escape($FORM['text_link_button_alt']);
    $static_button_url = $DB->escape($FORM['static_button_url']);
    $default_rank_button = $DB->escape($FORM['default_rank_button']);
    $button_dir = $DB->escape($FORM['button_dir']);
    $button_ext = $DB->escape($FORM['button_ext']);
    $button_num = intval($FORM['button_num']);


	if(isset($FORM['google_friendly_links']) && $FORM['google_friendly_links'] == 'on') {$google_friendly_links = 1;} else {$google_friendly_links = 0;}

    if(isset($FORM['gateway']) && $FORM['gateway'] == 'on') {$gateway = 1;} else {$gateway = 0;}
    if(isset($FORM['captcha']) && $FORM['captcha'] == 'on') {$captcha = 1;} else {$captcha = 0;}

    $security_question = $DB->escape($FORM['security_question']);
    $security_answer = $DB->escape($FORM['security_answer']);



	$one_w_price = $DB->escape($FORM['one_w_price']);

	$discount_qty_01 = $DB->escape($FORM['discount_qty_01']);
	$discount_value_01 = $DB->escape($FORM['discount_value_01']);
	$discount_qty_02 = $DB->escape($FORM['discount_qty_02']);
	$discount_value_02 = $DB->escape($FORM['discount_value_02']);
	$discount_qty_03 = $DB->escape($FORM['discount_qty_03']);
	$discount_value_03 = $DB->escape($FORM['discount_value_03']);

	$max_premium_banner_width = $DB->escape($FORM['bn_pr_width']);
	$max_premium_banner_height = $DB->escape($FORM['bn_pr_height']);
	$premium_number = $DB->escape($FORM['premium_number']);
    $premium_order_by = intval($FORM['premium_order_by']);

	$currency_symbol = $DB->escape($FORM['currency_symbol']);
	$currency_code = $DB->escape($FORM['currency_code']);


	$new_day_boost = intval($FORM['new_day_boost']);
	$new_week_boost = intval($FORM['new_week_boost']);
	$new_month_boost = intval($FORM['new_month_boost']);

	if(isset($FORM['auto_approve_premium']) && $FORM['auto_approve_premium'] == 'on') {$approve_premium_method = 1;} else {$approve_premium_method = 0;}


    $search = intval($FORM['search']);
    $time_offset = intval($FORM['time_offset']);
    $time_zone = $DB->escape($FORM['time_zone']);

    $TMPL['2step'] = !empty($FORM['2step']) ? (int)$FORM['2step'] : 0;

	// Validate 2step google
	if (!empty($FORM['2step_validate']))
	{
		require_once("{$CONF['path']}/sources/misc/GoogleAuthenticator.php");
		$ga = new PHPGangsta_GoogleAuthenticator();

		$TMPL['2step_validate'] = !empty($FORM['2step_validate']) ? $FORM['2step_validate'] : '';
		$_2step_verified        = $ga->verifyCode($CONF['2step_secret'], $TMPL['2step_validate'], 2);

		if (empty($_2step_verified))
		{
		    array_push($form_validate, 0);
			error_display('2step_validate', $LNG['2step_google_invalid']);
		}
	}



	// Put existing payment providers back to json
	require_once("{$CONF['path']}/sources/misc/Payment.php");
	$Payment = new Payment();
	
	$payment_providers = $Payment->getProviders([], true);
	foreach ($payment_providers as $provider => $provider_config)
	{
		foreach ($provider_config as $setting => $setting_data)
		{
			if ($setting_data['type'] == 'checkbox') {
				$payment_providers[$provider][$setting]['value'] = isset($FORM['payment_providers'][$provider][$setting]) ? true : false;
			}
			else {
				$payment_providers[$provider][$setting]['value'] = isset($FORM['payment_providers'][$provider][$setting]) ? $FORM['payment_providers'][$provider][$setting] : '';
			}
		}
	}
	$payment_providers = $DB->escape(json_encode($payment_providers));
	
	
	eval (PluginManager::getPluginManager ()->pluginHooks ('admin_settings_process_data'));


    if (!in_array(0, $form_validate))
	{
		$DB->query("UPDATE {$CONF['sql_prefix']}_settings SET
			list_name = '{$list_name}',
			list_url = '{$list_url}',
			default_language = '{$default_language}',
			your_email = '{$your_email}',
			clean_url = {$clean_url},
			maintenance_mode = '{$maintenance_mode}',
			num_list = {$num_list},
			ranking_period = '{$ranking_period}',
			ranking_method = '{$ranking_method}',
			ranking_average = {$ranking_average},
			featured_member = {$featured_member},
			top_skin_num = {$top_skin_num},
			ad_breaks = '{$ad_breaks}',
			fill_blank_rows = {$fill_blank_rows},
			active_default = {$active_default},
			active_default_review = {$active_default_review},
			inactive_after = {$inactive_after},
			email_admin_on_join = {$email_admin_on_join},
			email_admin_on_review = {$email_admin_on_review},
			max_banner_width = {$max_banner_width},
			max_banner_height = {$max_banner_height},
			default_banner = '{$default_banner}',
			google_friendly_links = {$google_friendly_links},
			search = {$search},
			time_offset = {$time_offset},
			gateway = {$gateway},
			captcha = {$captcha},
			recaptcha = {$recaptcha},
			recaptcha_sitekey = '{$recaptcha_sitekey}',
			recaptcha_secret = '{$recaptcha_secret}',
			lostpw_recaptcha = '{$lostpw_recaptcha}',
			admin_recaptcha = '{$admin_recaptcha}',
			usercp_recaptcha = '{$usercp_recaptcha}',
			gateway_recaptcha = '{$gateway_recaptcha}',
			security_question = '{$security_question}',
			security_answer = '{$security_answer}',
			smtp_host = '{$smtp_host}',
			smtp_user = '{$smtp_user}',
			smtp_password = '{$smtp_password}',
			smtp_port = '{$smtp_port}',
			time_zone = '{$time_zone}',
			2step = {$TMPL['2step']}
		", __FILE__, __LINE__);

		$DB->query("UPDATE {$CONF['sql_prefix']}_settings SET
			payment_providers = '{$payment_providers}',
			one_w_price = '{$one_w_price}',
			discount_qty_01 = '{$discount_qty_01}',
			discount_value_01 = '{$discount_value_01}',
			discount_qty_02 = '{$discount_qty_02}',
			discount_value_02 = '{$discount_value_02}',
			discount_qty_03 = '{$discount_qty_03}',
			discount_value_03 = '{$discount_value_03}',
			max_premium_banner_width = '{$max_premium_banner_width}',
			max_premium_banner_height = '{$max_premium_banner_height}',
			auto_approve_premium = '{$approve_premium_method}',
			premium_number = '{$premium_number}',
			premium_order_by = {$premium_order_by},
			currency_symbol = '{$currency_symbol}',
			currency_code = '{$currency_code}',
			new_day_boost = '{$new_day_boost}',
			new_week_boost = '{$new_week_boost}',
			new_month_boost = '{$new_month_boost}'
		", __FILE__, __LINE__);

		if (!empty($CONF['inactive_after']) && empty($inactive_after)) {
			$DB->query("UPDATE {$CONF['sql_prefix']}_sites SET active = 1 WHERE active = 3", __FILE__, __LINE__);
		}


    if (!empty($FORM['admin_password'])) {
      $admin_pass = md5($FORM['admin_password']);
			$DB->query("UPDATE {$CONF['sql_prefix']}_etc SET admin_password = '{$admin_pass}'", __FILE__, __LINE__);
		}


		// Reset video for png /jpg
		$default_banner_mp4 = '';
						
		// gif to mp4 conversion using ffmpeg for smaller filesizes			
		// Extra check if default banner is filled and on this domain		
		if (!empty($default_banner) && stripos($default_banner, $list_url) === 0) 
		{
			// gif to video conversion using ffmpeg for smaller filesizes
			// params - username, image url, old video url, save in same dir, premium
			$video = $this->ffmpeg_convert_image(false, $default_banner, $default_banner_mp4, true, false);

			if (!empty($video)) {				
				$default_banner_mp4 = $video['url'];
			}
		}


		$file = "{$CONF['path']}/button_config.php";
		if ($fh = @fopen($file, 'w')) {
			$button_config = <<<EndHTML
<?php

\$CONF['count_pv'] = {$count_pv}; //Count pageviews

\$CONF['text_link_button_alt'] = '{$text_link_button_alt}'; // Text Link Anchor, Alt for Buttons
\$CONF['text_link'] = {$text_link}; //Enable Text Link
\$CONF['static_button'] = {$static_button}; // Show Only Static Button
\$CONF['static_button_url'] = '{$static_button_url}';

\$CONF['rank_button'] = {$rank_button}; //Show buttons with rank 1.gif, 2.gif etc
\$CONF['default_rank_button'] = '{$default_rank_button}';
\$CONF['button_dir'] = '{$button_dir}';
\$CONF['button_ext'] = '{$button_ext}';
\$CONF['button_num'] = {$button_num};

\$CONF['stats_button'] = {$stats_button}; //Show Dynamic Stats Button

\$CONF['hidden_button_url'] = '{$list_url}/images/clear.png';

\$CONF['default_banner_mp4'] = '{$default_banner_mp4}';
\$CONF['default_banner_width'] = {$default_banner_width};
\$CONF['default_banner_height'] = {$default_banner_height};

?>
EndHTML;
			fwrite($fh, $button_config);
			fclose($fh);
		}

		eval (PluginManager::getPluginManager ()->pluginHooks ('admin_settings_update_data'));

		$TMPL['admin_content'] = $LNG['a_s_updated'];
		header("refresh:1; url={$list_url}/index.php?a=admin&b=settings");
	}
	else {
		$this->form();
	}
  }

  function getExtension($str)
  {
	$i = strrpos($str,".");
	if (!$i) { return ""; }
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return $ext;
  }
}
