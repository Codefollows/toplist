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

class Payment
{	
	public $checkout_require_login = true;

	private $service_config  = [];
	private $current_service = false;
	
	public function __construct() 
	{
		global $CONF, $FORM, $DB, $LNG, $TMPL;
		
		// Dont run this during install/update of VL
		if (!isset($CONF['install_running']))
		{
			// Default service config for Premium
			$this->enableService('Premium', [
				'return_url_success' => "{$CONF['list_url']}/index.php?a=user_cpl&b=user_premium&success",
				'return_url_cancel'  => "{$CONF['list_url']}/index.php?a=user_cpl&b=user_premium&cancel",
			]);
		
			/**
			 * Plugin hook - enable a new service or disable an existing one, this hook enables/disables passed service across all payment functions
			 *
			 * enable new service you coded
			 * 	$this->enableService('My Service', [
			 *		'return_url_success' => "return success url",
			 *		'return_url_cancel'  => "return cancel url",
			 *	]);
			 *
			 * disable a existing service
			 * 	$this->disableService('My Service');
			 */
			eval(PluginManager::getPluginManager()->pluginHooks('payment_set_service'));
		}
	}
	
	/**
	 * @param string $name
	 * @param array $config
	 */
	public function enableService($name, $config)
	{
		global $CONF;

		if (!is_array($config)) {
			$config = [];
		}
		
		$this->setServiceConfig($name, $config);
	}
	
	/**
	 * @param string $name
	 */
	public function disableService($name)
	{
		if (isset($this->service_config[$name])) {
			unset($this->service_config[$name]);
		}
	}	
	
	/**
	 * @param string $name
	 * @return bool
	 */
	public function validService($name)
	{
		if (isset($this->service_config[$name])) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param string $name
	 * @param array $config
	 *	Used before you load provider HTML, so we can construct some config variables
	 */
	public function setServiceConfig($name, $config)
	{
		global $CONF;
		
		$this->current_service = $name;
		
		$domain = str_replace('www.', '', parse_url($CONF['list_url'], PHP_URL_HOST));
		
		foreach ($config as $key => $value) 
		{
			if ($key === 'item_name') {
				$value = "{$value} - {$domain}";
			}
			
			$this->service_config[$name][$key] = $value;
		}
	}
	
	/**
	 * @param array $names
	 * @param bool $ignore_enabled
	 * @return array 
	 */
	public function getProviders($names = [], $ignore_enabled = false)
	{
		global $CONF;

		// On install, the CONF value doesnt exist, so just make it empty
		$all_providers = isset($CONF['payment_providers']) ? json_decode($CONF['payment_providers'], true) : [];	

		if (json_last_error() || empty($all_providers)) {
			return [];
		}
		
		$providers = [];
		foreach ($all_providers as $provider => $config)
		{
			if ($ignore_enabled === false && (!isset($config['enabled']) || $config['enabled']['value'] === false)) {
				continue;
			}
			
			if (empty($names) || in_array($provider, $names)) {
				$providers[$provider] = $config;
			}
		}

		return $providers;
	}
	
	/**
	 * @param array $providers
	 *	Manually update the DB stored provider config, e.g changing keys, adding more keys
	 *	Just pass the proper structure
	 * @return bool
	 */	
	public function updateProviders($providers)
	{
		global $CONF, $DB;
		
		if (!is_array($providers)) {
			return false;
		}
		
		$old_providers = $this->getProviders([], true);

		foreach ($providers as $provider => $config)
		{
			if (!isset($old_providers[$provider]) || empty($config) || !is_array($config)) {
				continue;
			}

			foreach ($config as $setting => $data)
			{
				foreach ($data as $key => $value)
				{
					// Make sure that the enabled setting, we only edit the docs and label key!
					// Nothing should ever enable/disable on behalf of the admin or change the type from checkbox to something else!
					if ($setting === 'enabled' && ($key === 'type' || $key === 'value')) 
					{
						continue;
					}
					
					$old_providers[$provider][$setting][$key] = $value;
				}
			}
		}
		
		$new_providers = $DB->escape(json_encode($old_providers));
		
		$DB->query("UPDATE `{$CONF['sql_prefix']}_settings` SET `payment_providers` = '{$new_providers}'", __FILE__, __LINE__);
		
		return true;
	}
	
	/**
	 * @param array $providers
	 * @return bool
	 */	
	public function insertProviders($providers)
	{
		global $CONF, $DB, $LNG;
		
		if (!is_array($providers)) {
			return false;
		}
		
		$old_providers = $this->getProviders([], true);

		$new_providers = [];
		foreach ($providers as $provider => $config)
		{
			if (isset($old_providers[$provider]) || empty($config) || !is_array($config)) {
				continue;
			}
			
			foreach ($config as $setting => $data)
			{
				$new_providers[$provider][$setting] = $data;
			}
			
			// Check if required enabled config is there
			if (!isset($new_providers[$provider]['enabled']))
			{
				$new_providers[$provider]['enabled'] = [
					'type'  => 'checkbox',
					'label' => 'Enabled?',
					'value' => false,
				];
			}
			else 
			{
				// Enabled must be a checkbox
				if (!isset($new_providers[$provider]['enabled']['type']) || $new_providers[$provider]['enabled']['type'] !== 'checkbox') {
					$new_providers[$provider]['enabled']['type'] = 'checkbox';
				}
				
				// We need enabled text
				if (!isset($new_providers[$provider]['enabled']['label'])) {
					$new_providers[$provider]['enabled']['type'] = 'Enabled?';
				}
				
				// Never should a new provider be auto enabled
				if (!isset($new_providers[$provider]['enabled']['value']) || $new_providers[$provider]['enabled']['value'] !== false) {
					$new_providers[$provider]['enabled']['value'] = false;
				}
			}
		}
		
		if (empty($new_providers)) {
			return false;
		}
						
		$new_providers = array_merge($old_providers, $new_providers);
		$new_providers = $DB->escape(json_encode($new_providers));
		
		$DB->query("UPDATE `{$CONF['sql_prefix']}_settings` SET `payment_providers` = '{$new_providers}'", __FILE__, __LINE__);
		
		return true;
	}
	
	/**
	 * @param array $names
	 * @return bool
	 */	
	public function deleteProviders($names)
	{
		global $CONF, $DB;
		
		if (!is_array($names)) {
			return false;
		}
		
		$old_providers = $this->getProviders([], true);
		
		$removed = 0;
		foreach ($names as $name)
		{
			if (!isset($old_providers[$name])) {
				continue;
			}
			
			unset($old_providers[$name]);
			
			$removed++;
		}
		
		if ($removed === 0) {
			return false;
		}
		
		$new_providers = $DB->escape(json_encode($old_providers));
		$DB->query("UPDATE `{$CONF['sql_prefix']}_settings` SET `payment_providers` = '{$new_providers}'", __FILE__, __LINE__);
		
		return true;
	}
	
	/**
	 * @param array $names
	 * @return layout
	 *
	 * This function should be called on the actual payment confirm page to load the payment providers html
	 * Additionally you need to setup the config required, as shown below
	 * Failing to do so, will result in no payment providers loading!
	 *
	 *		$Payment->setServiceConfig('Premium', [
	 *			'item_name' => 'item name',
	 *			'value'     => 'The service value, in case of premium, the weeks selection',
	 *			'price'     => 'The price of the product',
	 *			'currency'  => 'Currency code',
	 *			'username'  => 'The user purchasing the service, if required. Else empty string',
	 *		]);
	 */	
	public function loadProviderHtml($names = [])
	{
		global $CONF, $FORM, $DB, $LNG, $TMPL;

		if (!$this->validServiceConfig()) {
			return '';
		}
		
		$TMPL['service'] = $this->current_service;
		foreach ($this->service_config[$this->current_service] as $setting => $value) {
			$TMPL["service_{$setting}"] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		}
		
		// Metadata we construct ourself out of required config, PayPal and Paygol use this for example and it will be used in IPN
		$TMPL['service_metadata']  = $TMPL['service'];
		$TMPL['service_metadata'] .= "|{$TMPL['service_value']}";
		$TMPL['service_metadata'] .= "|{$TMPL['service_username']}";
		
		
		$provider_names = [];


		// Plugin hook - pass only specific enabled providers to load on a per need basis
		// $provider_names = ['PayPal', 'Stripe'];
		eval (PluginManager::getPluginManager ()->pluginHooks ('payment_providers_html_start'));
		
		
		// Gets enabled providers
		$providers = $this->getProviders($provider_names);

		
		// The js which handles provider selection
		// That button click calls index.php?a=payment_checkout which calls checkoutValidate() to validate and setup provider stuff ( e.g stripe customer session )
		// On the ajax response we have a js event hook, which can be used for provider specific stuff ( see payment_form_Stripe_javascript.html )
		$TMPL['extra_javascripts'] .= base::do_skin("payment_form_provider_javascript");
		
		
		$provider_html = '';
		foreach ($providers as $provider => $config)
		{
			$TMPL['provider'] = $provider;
			
			// Setup tmpl variables based on config
			foreach ($config as $setting => $data)
			{
				$TMPL["{$provider}_{$setting}"] = htmlspecialchars($data['value'], ENT_QUOTES, 'UTF-8');
				
				// for enabled checkbox settings, a internal value is possible ( e.g paypal sandbox )
				// so setup a var for it
				if (isset($data['internal']) && $data['type'] === 'checkbox' && $data['value'] === true)
				{
					$TMPL["{$provider}_{$setting}_internal"] = htmlspecialchars($data['internal'], ENT_QUOTES, 'UTF-8');
				}
			}
			
			$provider_js = false;
			if ($provider === 'Stripe' || $provider === 'Coinbase') {
				$provider_js = true;
			}
			
			/**
			 * Set to plugin path if you load a provider form via plugin.
			 * Filename and possible provider js must follow the same naming as core files
			 *
			 * $plugin_path = "{$CONF['path']}/plugins/MyPluginName";
			 */
			$plugin_path = false;
			
			
			// Plugin Hook
			// if you need to init more provider config
			// Can also be used to overwrite previously setup tmpl variables for provider config ( foreach above )
			eval(PluginManager::getPluginManager()->pluginHooks('payment_providers_html_loop'));
			
			
			// load plugin file vs normal skin file
			if ($plugin_path === false) 
			{
				if ($provider_js === true) {
					$TMPL['extra_javascripts'] .= base::do_skin("payment_form_{$provider}_javascript");
				}
			
				$provider_html .= base::do_skin("payment_form_{$provider}");
			}
			else 
			{
				if ($provider_js === true) {
					$TMPL['extra_javascripts'] .= base::do_plugin_skin($plugin_path, "payment_form_{$provider}_javascript");
				}
				
				$provider_html .= base::do_plugin_skin($plugin_path, "payment_form_{$provider}");
			}
		}
		
		return $provider_html;
	}
	
	/**
	 * @returns json
	 */
	public function checkoutValidate() 
	{
		global $CONF, $FORM, $DB, $LNG, $TMPL;
		
		if ($this->checkout_require_login === true && (!isset($TMPL['wrapper_username']) || mb_strlen($TMPL['wrapper_username']) === 0)) 
		{
			return $this->checkoutResponse([], 'Access denied.');
		}
		
		$response      = [];
		$providers     = $this->getProviders();
		$provider      = isset($FORM['provider'])      ? $FORM['provider']      : '';
		$service       = isset($FORM['service'])       ? $FORM['service']       : '';
		$service_value = isset($FORM['service_value']) ? $FORM['service_value'] : '';
		
		if (!isset($providers[$provider])) {
			return $this->checkoutResponse([], 'Invalid payment provider.');
		}
		
		if (!$this->validService($service)) {
			return $this->checkoutResponse([], 'Invalid service.');
		}
		
		// Might be blank, as maybe not be required by every service
		$service_username     = isset($FORM['service_username']) ? $FORM['service_username'] : '';
		$service_username_sql = $DB->escape($service_username, 1);
		$service_owner_sql    = $DB->escape($TMPL['wrapper_username'], 1);

		// Check if passed username belongs to owner if login is required for checkout
		if ($this->checkout_require_login === true)
		{
			list($valid_username) = $DB->fetch("SELECT 1 FROM `{$CONF['sql_prefix']}_sites` WHERE `username` = '{$service_username_sql}' AND `owner` = '{$service_owner_sql}'", __FILE__, __LINE__);
			
			if (empty($valid_username)) {
				return $this->checkoutResponse([], 'Access denied.');
			}
		}
		
		$extra_required_keys = [];
		if ($service === 'Premium')
		{
			// In case of premium, service value should be an int! and represents amount of weeks selected
			$service_value = (int)$service_value;
		
			if ($service_value <= 0) {
				return $this->checkoutResponse([], 'Invalid selection.');
			}

			// We dont need any price validation, as we work with whatever weeks are passed!
			$price = bcmul("{$service_value}", "{$CONF['one_w_price']}", 2);

			// Apply discounts
			$line_discount = 0;
			if ($service_value >= $CONF['discount_qty_03']) {
				$line_discount = $CONF['discount_value_03'];
			}
			elseif ($service_value >= $CONF['discount_qty_02']) {
				$line_discount = $CONF['discount_value_02'];
			}
			elseif ($service_value >= $CONF['discount_qty_01']) {
				$line_discount = $CONF['discount_value_01'];
			}
			
			$discount_multiplier = bcdiv("{$line_discount}", '100', 2);
			$discount            = bcmul("{$price}", "{$discount_multiplier}", 2);
			
			
			// Set more service config
			list($email) = $DB->fetch("SELECT `email` FROM `{$CONF['sql_prefix']}_sites` WHERE `username` = '{$service_username_sql}' AND `owner` = '{$service_owner_sql}'", __FILE__, __LINE__);

			$this->setServiceConfig($service, [
				'item_name'    => "{$service} {$service_value} weeks - {$service_username}",
				'value'        => $service_value,
				'price'        => bcsub("{$price}", "{$discount}", 2),
				'currency'     => $CONF['currency_code'],
				'username'     => $service_username,
				'stripe_email' => $email,
			]);
		}
		
		
		// Plugin Hook - New service validations or overwrites
		eval(PluginManager::getPluginManager()->pluginHooks('payment_checkout_validate'));
		
		
		// Validate proper base service config and potential extra keys
		if (!$this->validServiceConfig($extra_required_keys)) {
			return $this->checkoutResponse([], 'Invalid service setup.');
		}
				
		// Set up provider specific config, usually providers which are no form and require some sort of init
		// We are working with the service config instead of normal variables to avoid possible issues with plugins doing something wrong
		// E.g they overwrite $service_value in anyway prior to adding the correct value inside service config. This makes sure we really work with the values they intend to use
		$config = $this->service_config[$this->current_service];

		if ($provider === 'Stripe')
		{
			if (empty($config['stripe_email'])) {
				return $this->checkoutResponse([], 'Invalid Stripe setup.');
			}
		
			require_once("{$CONF['path']}/sources/payment/lib/Stripe/init.php");

			// start customer session, used to redirect to checkout
			// The list_url metadata is there to work around odd behavour in that it calls each webhook ( even other domains )
			// I make a pay on domain 1, they sends webhook to both domain 1 and 2, so we need a way to stop execution on domain 2 and vise versa
			// Thus the list url to validate inside the ipn and exit if not matching
			try 
			{
				\Stripe\Stripe::setApiKey($providers[$provider]['api_secret_key']['value']);

				$customers = \Stripe\Customer::all(['email' => $config['stripe_email'], 'limit' => 1]);
				
				$customer_id = !empty($customers->data[0]->id) ? $customers->data[0]->id : false;
				
				if ($customer_id === false)
				{
					$customer = \Stripe\Customer::create([
						'email' => $config['stripe_email'],
					]);
					
					$customer_id = $customer->id;
				}
					
				$customer_session = \Stripe\Checkout\Session::create([
					'customer' => $customer_id,
					'payment_method_types' => ['card'],
					'payment_intent_data' => [
						'metadata' => [
							'service'          => $this->current_service,
							'service_value'    => $config['value'],
							'service_username' => $config['username'],
							'list_url'         => $CONF['list_url'],
						],
					],
					'line_items' => [
						[
							'price_data' => [
								'currency' => mb_strtolower($config['currency']),
								'product_data' => [
									'name' => $config['item_name'],
								],
								'unit_amount' => (int) bcmul("{$config['price']}", "100", 0),
							],
							'quantity' => 1,
						]
					],
					'mode' => 'payment',
					'success_url' => $config['return_url_success'],
					'cancel_url' => $config['return_url_cancel'],
				]);
				
				$response['session_id'] = $customer_session->id;
			} 
			catch (\Stripe\Exception\RateLimitException $e) {
				return $this->checkoutResponse([], $e->getMessage());
			} 
			catch (\Stripe\Exception\InvalidRequestException $e) {
				return $this->checkoutResponse([], $e->getMessage());
			} 
			catch (\Stripe\Exception\AuthenticationException $e) {
				return $this->checkoutResponse([], $e->getMessage());
			} 
			catch (\Stripe\Exception\ApiConnectionException $e) {
				return $this->checkoutResponse([], $e->getMessage());
			} 
			catch (\Stripe\Exception\ApiErrorException $e) {
				return $this->checkoutResponse([], $e->getMessage());
			} 
			catch (Exception $e) {
				return $this->checkoutResponse([], $e->getMessage());
			}
		}
		elseif ($provider === 'Coinbase')
		{
			require_once("{$CONF['path']}/sources/payment/lib/Coinbase.php");

			// create a charge, used to redirect to checkout
			// The list_url metadata is there to work around odd behavour in that it calls each webhook ( even other domains )
			// I make a pay on domain 1, they sends webhook to both domain 1 and 2, so we need a way to stop execution on domain 2 and vise versa
			// Thus the list url to validate inside the ipn and exit if not matching
			try 
			{
				$Coinbase = new Coinbase($providers[$provider]['api_key']['value']);

				$charge = $Coinbase->createCharge([
					'name' => $this->current_service,
					'description' => $config['item_name'],
					'local_price' => [
						'amount'   => $config['price'],
						'currency' => mb_strtoupper($config['currency']),
					],
					'pricing_type' => 'fixed_price',
					'metadata' => [
						'service'          => $this->current_service,
						'service_value'    => $config['value'],
						'service_username' => $config['username'],
						'list_url'         => $CONF['list_url'],
					],
					'redirect_url' => $config['return_url_success'],
					'cancel_url' => $config['return_url_cancel'],
				]);
				
				
				$response['redirect'] = $charge->hosted_url;
			} 
			catch (Exception $e) {
				return $this->checkoutResponse([], $e->getMessage());
			}
		}		
		
		
		// Plugin Hook - Set up a new js type provider like Stripe
		eval(PluginManager::getPluginManager()->pluginHooks('payment_checkout_provider'));
		
		
		return $this->checkoutResponse($response);
	}
	
	/**
	 * @param array $response
	 * @param bool $error
	 * @return json
	 */
	private function checkoutResponse($response = [], $error = false)
	{
		global $CONF, $FORM, $DB, $LNG, $TMPL;
		
		header("Content-type: application/json; charset=utf-8");
		
		if ($error !== false)
		{
			if (!is_string($error)) {
				$error = 'Undefined error.';
			}
			
			$response['error'] = $error;
		}
		
		
		// Plugin hook - Do something with response if needed
		eval(PluginManager::getPluginManager()->pluginHooks('payment_checkout_response'));
		
		
		echo json_encode($response);
		exit;
	}
	
	/**
	 * @param array $extra_keys
	 * @return bool
	 */
	private function validServiceConfig($extra_keys = [])
	{
		if (empty($this->service_config[$this->current_service])) {
			return false;
		}
		
		if (!is_array($extra_keys)) {
			$extra_keys = [];
		}
		
		$base_keys     = ['item_name', 'value', 'price', 'currency', 'username', 'return_url_success', 'return_url_cancel'];
		$required_keys = array_merge($base_keys, $extra_keys);
		$service_keys  = array_keys($this->service_config[$this->current_service]);
		
		foreach ($required_keys as $key)
		{
			if (!in_array($key, $service_keys)) {
				return false;
			}
		}
		
		return true;
	}
}