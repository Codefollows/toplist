<?php
//===========================================================================\\
// VISIOLIST is a proud derivative work of Aardvark Topsites                 \\
// Copyright (c) 2000-2007 Jeremy Scheff.  All rights reserved.              \\
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

class user_premium extends join_edit 
{
	public function __construct() 
	{
		global $FORM, $LNG, $TMPL;

		$TMPL['header'] = $LNG['user_cp_premium_header'];
	
		if (isset($FORM['check'])) {
			$this->check();
		}
		elseif (isset($FORM['success']) || isset($FORM['cancel'])) {
			$this->payment_return();
		}
		elseif (!isset($FORM['submit'])) {
			$this->form();
		}
		else {
			$this->process();
		}
	}
	
	public function check()
	{
		global $CONF;
		
		header("Content-type: application/json; charset=utf-8");
		
		$weeks = isset($_POST['weeks']) ? (int)$_POST['weeks'] : 0;
		

		$regular_price = bcmul("{$weeks}", "{$CONF['one_w_price']}", 2);

		//Apply discounts
		$line_discount = 0;
		if ($CONF['discount_qty_03'] > 0 && $weeks >= $CONF['discount_qty_03']) {
			$line_discount = $CONF['discount_value_03'];
		}
		elseif ($CONF['discount_qty_02'] > 0 && $weeks >= $CONF['discount_qty_02']) {
			$line_discount = $CONF['discount_value_02'];
		}
		elseif ($CONF['discount_qty_01'] > 0 && $weeks >= $CONF['discount_qty_01']) {
			$line_discount = $CONF['discount_value_01'];
		}
		
		$discount_multiplier = bcdiv("{$line_discount}", '100', 2);
		$discount            = bcmul("{$regular_price}", "{$discount_multiplier}", 2);
					
		$final_price = bcsub("{$regular_price}", "{$discount}", 2);
		
		$response = [
			'days'          => $weeks * 7,
			'regular_price' => $regular_price,
			'discount'      => $line_discount,
			'final_price'   => $final_price,
		];
		
		echo json_encode($response);
		exit;
	}
	
	public function payment_return() 
	{
		global $CONF, $FORM, $DB, $LNG, $TMPL;
    
		if (isset($FORM['success'])) 
		{
			if ($CONF['auto_approve_premium'] == 1) {
				$TMPL['payment_return_success_message'] = 'Your Premium should activate within a few minutes. If it does not, please contact us for investigation.';
			}
			else {
				$TMPL['payment_return_success_message'] = $LNG['new_premium_user_info_pending'];
			}
			
			$TMPL['user_cp_content'] = $this->do_skin('payment_return_success');
		}
		elseif (isset($FORM['cancel'])) 
		{
			$TMPL['payment_return_cancel_message'] = 'Your Premium could not be activated because the payment got canceled or failed.';

			$TMPL['user_cp_content'] = $this->do_skin('payment_return_cancel');		
		}
	}

	public function form() 
	{
		global $CONF, $FORM, $DB, $LNG, $TMPL;

		// MultiSite check
		if(!isset($FORM['u'])) 
		{
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
				 $TMPL['user_cp_content'] .= '<li style="background: url('.$ourl.'/favicon.ico) 15px no-repeat; background-size: 16px auto;"><a href="index.php?a=user_cpl&b=user_premium&u='.$ousername.'">'.$ourl.'</a></li>';
			}

			//End LIST
			$TMPL['user_cp_content'] .= '</ul>';

			if($count == 1) {
				header("Location: index.php?a=user_cpl&b=user_premium&u={$TMPL['username']}");
			}
		}
		elseif(isset($FORM['u']))
		{
			list($TMPL['myowner']) = $DB->fetch("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);

			$TMPL['myusername'] = $DB->escape($FORM['u'], 1);

			$row = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['myusername']}' AND owner = '{$TMPL['myowner']}' ", __FILE__, __LINE__);
			
			if (empty($row)) {
				header("Location: index.php?a=user_cpl&b=user_premium");
				exit;
			}
			
			$row = array_map(function($value) {
				return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
			}, $row);

			$TMPL = array_merge($TMPL, $row);


			// check if we have at least 1 enabled provider			
			require_once("{$CONF['path']}/sources/misc/Payment.php");
			$Payment = new Payment();
				
			$payment_providers = $Payment->getProviders();
			
			if (empty($payment_providers) || empty($CONF['one_w_price'])) {
				$TMPL['user_cp_content'] = $LNG['user_cp_premium_not_active'];
			}
			else 
			{
				if ($TMPL['premium_flag'] == 1 || $TMPL['premium_request'] == 1) 
				{
					if ($TMPL['premium_request'] == 1 ) {
						$TMPL['premium_request_pending'] = $LNG['user_cp_premium_pending_msg'];
					}
					else {
						$TMPL['premium_request_pending'] = $LNG['user_cp_premium_already_msg'];
					}
			
					$timestamp_now = strtotime(date('Y-m-d'));

					// If exist ADMIN DATE APPROVATION
					if ($TMPL['date_start_premium']){
						$TMPL['date_end_premium'] = date('Y-m-d',strtotime ("+" . $TMPL['remain_day'] . "day",$timestamp_now));
					}
					else {
						$TMPL['date_end_premium'] = "";
					}
			
			
					// Plugin Hook
					eval(PluginManager::getPluginManager()->pluginHooks('user_cp_premium_already_form'));
					
					
					$TMPL['user_cp_content'] = $this->do_skin('premium_already_form');
				}
				else
				{
					$TMPL['one_w_price'] = $CONF['one_w_price'];
					
					$TMPL['discounts'] = '';
					if ($CONF['discount_qty_01'] > 0 && $CONF['discount_value_01'] > 0) 
					{
						$TMPL['discount_qty']   = $CONF['discount_qty_01'];
						$TMPL['discount_value'] = $CONF['discount_value_01'];
						
						$TMPL['discounts'] .= $this->do_skin('premium_form_discount');
					}
					
					if ($CONF['discount_qty_02'] > 0 && $CONF['discount_value_02'] > 0) 
					{
						$TMPL['discount_qty']   = $CONF['discount_qty_02'];
						$TMPL['discount_value'] = $CONF['discount_value_02'];
						
						$TMPL['discounts'] .= $this->do_skin('premium_form_discount');
					}
					
					if ($CONF['discount_qty_03'] > 0 && $CONF['discount_value_03'] > 0) 
					{
						$TMPL['discount_qty']   = $CONF['discount_qty_03'];
						$TMPL['discount_value'] = $CONF['discount_value_03'];
						
						$TMPL['discounts'] .= $this->do_skin('premium_form_discount');
					}
					
					
					// Plugin Hook
					eval(PluginManager::getPluginManager()->pluginHooks('user_cp_premium_form'));
			
			
					// csrf token at the end so plugins can not meddle with the template tag
					$TMPL['csrf_token'] = generate_csrf_token($TMPL['myusername'], 'user_cp_premium_csrf');
				
			
					$TMPL['user_cp_content'] = $this->do_skin('premium_form');
				}
			}
		}
	}

	public function process() 
	{
		global $CONF, $DB, $FORM, $LNG, $TMPL;

		// Validate csrf token
		// Before anything other gets defined or executed
		if (!isset($FORM['csrf_token']) || validate_csrf_token($FORM['csrf_token'], 'user_cp_premium_csrf') === false) {
			$this->error($LNG['g_session_expired'], 'user_cp');
		}
		
		list($TMPL['myowner']) = $DB->fetch("SELECT owner FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['username']}'", __FILE__, __LINE__);

		$TMPL['myusername'] = isset($FORM['u']) ? $DB->escape($FORM['u'], 1) : '';

		$row = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$TMPL['myusername']}' AND owner = '{$TMPL['myowner']}' ", __FILE__, __LINE__);
		
		if (empty($row) || $row['premium_flag'] == 1 || $row['premium_request'] == 1) {
			header("Location: index.php?a=user_cpl&b=user_premium");
			exit;
		}
				
		$row = array_map(function($value) {
			return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
		}, $row);
			
		$TMPL = array_merge($TMPL, $row);
		
		$TMPL['weeks_buy'] = isset($FORM['total_weeks']) ? (int)$FORM['total_weeks'] : 0;
		$TMPL['total_day'] = $TMPL['weeks_buy'] * 7;
		
		//First lets compare the values from the users form results to our own internal calculation
		$price = bcmul("{$TMPL['weeks_buy']}", "{$CONF['one_w_price']}", 2);

		//Apply discounts
		$line_discount = 0;
		if ($CONF['discount_qty_03'] > 0 && $TMPL['weeks_buy'] >= $CONF['discount_qty_03']) {
			$line_discount = $CONF['discount_value_03'];
		}
		elseif ($CONF['discount_qty_02'] > 0 && $TMPL['weeks_buy'] >= $CONF['discount_qty_02']) {
			$line_discount = $CONF['discount_value_02'];
		}
		elseif ($CONF['discount_qty_01'] > 0 && $TMPL['weeks_buy'] >= $CONF['discount_qty_01']) {
			$line_discount = $CONF['discount_value_01'];
		}
		
		$discount_multiplier = bcdiv("{$line_discount}", '100', 2);
		$discount            = bcmul("{$price}", "{$discount_multiplier}", 2);
					
		$valid_price = bcsub("{$price}", "{$discount}", 2);
		
		// if he meddled with prices, who cares, we overwrite with correct value
		$TMPL['line_discount']       = $line_discount;
		$TMPL['final_price_display'] = number_format($valid_price, 2, '.', ',');
		
		// Set to false via plugin to prevent base layout loading
		$load_default_layout = true;
		
		
		// Plugin Hook - extend review form
		eval(PluginManager::getPluginManager()->pluginHooks('user_cp_premium_review'));

			
		if ($load_default_layout === true)
		{
			require_once("{$CONF['path']}/sources/misc/Payment.php");
			$Payment = new Payment();
			
			// Variables used accross all form providers, these has to be set!
			// The js providers use something similar!
			$Payment->setServiceConfig('Premium', [
				'item_name' => "Premium {$TMPL['weeks_buy']} weeks - {$TMPL['myusername']}",
				'value'     => $TMPL['weeks_buy'],
				'price'     => $valid_price,
				'currency'  => $TMPL['currency_code'],
				'username'  => $TMPL['myusername'],
			]);
			
			$TMPL['payment_providers'] = $Payment->loadProviderHtml();
					
			$TMPL['user_cp_content'] = $this->do_skin('premium_review');
		}
	}
}
