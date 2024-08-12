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

class payment_ipn extends base
{
	/* The payment class */
	private $Payment;
	
	private $payment_providers;
	private $ipn_backup;	
	private $ipn_columns = [
		'txn_id'         => ['type' => 'string',    'required' => true,  'nullable' => false, 'exclude_update' => true,  'default' => false, 'private' => true],
		'provider'       => ['type' => 'string',    'required' => true,  'nullable' => false, 'exclude_update' => true,  'default' => false, 'private' => true], 
		'completed_once' => ['type' => 'int',       'required' => false, 'nullable' => false, 'exclude_update' => false, 'default' => 0], 
		'updated_at'     => ['type' => 'timestamp', 'required' => false, 'nullable' => false, 'exclude_update' => false, 'default' => false], 
		'status'         => ['type' => 'string',    'required' => true,  'nullable' => false, 'exclude_update' => false, 'default' => false], 
		'status_reason'  => ['type' => 'string',    'required' => false, 'nullable' => true,  'exclude_update' => false, 'default' => null], 
		'username'       => ['type' => 'string',    'required' => true,  'nullable' => true,  'exclude_update' => true,  'default' => false], 
		'cheat'          => ['type' => 'int',       'required' => false, 'nullable' => false, 'exclude_update' => false, 'default' => 0], 
		'cheat_reason'   => ['type' => 'string',    'required' => false, 'nullable' => true,  'exclude_update' => false, 'default' => null], 
		'service'        => ['type' => 'string',    'required' => true,  'nullable' => false, 'exclude_update' => true,  'default' => false], 
		'service_info'   => ['type' => 'json',      'required' => true,  'nullable' => false, 'exclude_update' => true,  'default' => ['internal' => '', 'info' => '']], 
		'price'          => ['type' => 'decimal',   'required' => true,  'nullable' => false, 'exclude_update' => true,  'default' => false], 
		'discount'       => ['type' => 'decimal',   'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => '0.00'], 
		'payed'          => ['type' => 'decimal',   'required' => true,  'nullable' => false, 'exclude_update' => true,  'default' => false], 
		'fee'            => ['type' => 'decimal',   'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => '0.00'], 
		'payment_date'   => ['type' => 'timestamp', 'required' => false, 'nullable' => false, 'exclude_update' => false, 'default' => false], 
		'email'          => ['type' => 'string',    'required' => false, 'nullable' => true,  'exclude_update' => true,  'default' => null], 
		'country'        => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'], 
		'country_code'   => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'], 
		'state'          => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'], 
		'city'           => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'], 
		'street'         => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'], 
		'zip'            => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'], 
		'fname'          => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'], 
		'lname'          => ['type' => 'string',    'required' => false, 'nullable' => false, 'exclude_update' => true,  'default' => 'N/A'],
	];
	private $save_method        = 'insert';
	private $initialized        = false;
	private $cheat_codes        = [];
	private $extra_cheat_checks = [];
	
	public function __construct() 
	{
		global $CONF, $DB, $TMPL, $FORM, $LNG;

		require_once("{$CONF['path']}/sources/misc/Payment.php");
		$this->Payment = new Payment();
			
		// Which provider we using?
		$this->payment_providers = $this->Payment->getProviders();
		$provider                = isset($FORM['provider']) ? $FORM['provider'] : '';
	
		if (!isset($this->payment_providers[$provider]))
		{
			// Response code 200 to avoid repeative webhook calls on providers which call every added domain webhook no matter what
			// They repeat usually in intervals unless the response code is 200 to acknowledge receipt of a webhook
			// E.g Stripe and Coinbase Commerce
			http_response_code(200);
			exit;	
		}
		
		// Handles ipn based on passed provider
		// with support for plugins, to load a provider file via plugin path
		// You must follow the control flow as seen inside PayPal for example
		$provider_path = 'sources/payment/ipn';
				
		
		// Plugin hook - overwrite the path to the provider ipn file
		eval(PluginManager::getPluginManager()->pluginHooks('payment_ipn_path'));
		
		
		try 
		{
			// Set our private provider ipn member so it cant be setup in a wrong way
			// IPN file can use this->ipn_columns['provider']['value'] or simply $provider inside their file, at this point it is safe and validated
			// IPN file also has access to $this->payment_providers config variable
			$this->setColumn('provider', $provider, false);
						
			// Core cheat codes, checked when providers pass extra cheat protection
			$this->cheat_codes = [1, 2, 3, 4];
			
			// Call the ipn file, which calls normalized functions from this file
			// It shall not be a class or it not has access to this class methods
			require_once("{$CONF['path']}/{$provider_path}/{$provider}.php");
						
			$response_code = 200;
		}
		catch(Throwable $e) 
		{
			$this->logError($e->getMessage());
			$response_code = 400;
		}
		
		// Do not continue further, no need load html
		http_response_code($response_code);
		exit;
	}
	
	private function logError($error)
	{
		global $CONF, $DB;

		$txn_id = 'NULL';
		if (isset($this->ipn_columns['txn_id']['value']) && $this->ipn_columns['txn_id']['value'] !== false) {
			$txn_id = "'".$DB->escape($this->ipn_columns['txn_id']['value'], 1)."'";		
		}
		
		$provider = $DB->escape($this->ipn_columns['provider']['value'], 1);		
		$error    = $DB->escape($error, 1);

		$DB->query("INSERT INTO `{$CONF['sql_prefix']}_payment_logs_error` (`txn_id`, `provider`, `reason`) VALUES ({$txn_id}, '{$provider}', '{$error}')", __FILE__, __LINE__);
	}	
	
	/**
	 * Initialize our column values with an existing transaction or the default values
	 *
	 * @param string $txn_id
	 * @param string $provider
	 *
	 * @return void
	 */
	private function initColumns($txn_id)
	{
		global $CONF, $DB;

		$this->initialized = true;
		
		// Set our private ipn member, this way users not have to set column for txn_id themself
		$this->setColumn('txn_id', $txn_id, false);

		$txn_id   = $DB->escape($this->ipn_columns['txn_id']['value'], 1);
		$provider = $DB->escape($this->ipn_columns['provider']['value'], 1);

		$row = $DB->fetch("SELECT * FROM `{$CONF['sql_prefix']}_payment_logs` WHERE `txn_id` = '{$txn_id}' AND `provider` = '{$provider}'", __FILE__, __LINE__);

		// Init payment_date and updated_at
		$date = (new DateTime())->format('Y-m-d H:i:s');
			
		if (!empty($row)) 
		{
			$this->save_method = 'update';
			
			foreach ($row as $column => $value)
			{
				if (!isset($this->ipn_columns[$column])) {
					continue;
				}
				
				$this->setColumn($column, $value);
				$this->normalizeColumn($column);
			}
		}
		else
		{
			$this->save_method = 'insert';

			foreach ($this->ipn_columns as $column => $data) 
			{
				$this->setColumn($column, $data['default']);
			}

			$this->setColumn('payment_date', $date);
		}
		
		$this->setColumn('updated_at', $date);		
		
		// Create a backup of columns which gets used in some checks to validate new vs old data after overwriting a ipn column
		$this->ipn_backup = $this->ipn_columns;
	}

	/**
	 * Set a ipn column
	 *
	 * @param string $column
	 * @param mixed $value
	 * @param bool $skip_private Skips provate column members by default as they are setup in a different way
	 *
	 * @return void
	 */
	private function setColumn($column, $value, $skip_private = true) 
	{
		if ($skip_private === false || empty($this->ipn_columns[$column]['private'])) {
			$this->ipn_columns[$column]['value'] = $value;
		}
	}		
	
	/**
	 * Validate column and its values to make sure they in correct format
	 *
	 * @param string $column A valid ipn column from $this->ipn_columns
	 *
     * @throws Exception if the passed value not matches its column format
	 *
	 * @return void
	 */
	private function validateColumn($column)
	{
		global $LNG;

		if ($this->initialized !== true) {
            $msg = "Invalid ipn setup: you have to call \$this->initColumns(\$txn_id); before setting any ipn data via \$this->setColumn(\$column, \$value);";
		}
		elseif (!isset($this->ipn_columns[$column])) {
            $msg = "Invalid column: '{$column}' not exist";
		}
		else
		{			
			$type     = $this->ipn_columns[$column]['type'];
			$value    = $this->ipn_columns[$column]['value'];
			$nullable = $this->ipn_columns[$column]['nullable'];

			if ($nullable === true && is_null($value)) {
				return;
			}
			
			if ($this->ipn_columns[$column]['required'] === true && $value === false) {
				$msg = "Invalid column value: '{$column}' has to be set";
			}	
			elseif ($type === 'int' && !is_int($value)) {
				$msg = "Invalid column value: '{$column}' has to be a integer";
			}		
			elseif ($type === 'string' && !is_string($value)) {
				$msg = "Invalid column value: '{$column}' has to be a string";
			}
			elseif ($type === 'decimal' && (!preg_match('/^\d+\.\d\d$/D', $value) || !is_string($value))) {				
				$msg = "Invalid column value: '{$column}' need to called with number_format(\$value, 2, '.', '') to keep data integrity";
			}
			elseif ($type === 'json') 
			{
				if (!is_array($value)) {
					$msg = "Invalid column value: '{$column}' has to be an array so it can be converted to json";
				}
				elseif (!isset($value['internal']) || !isset($value['info'])) {
					$msg = "Invalid column value: '{$column}' missing array keys 'internal' or 'info'";
				}
			}
			elseif ($type === 'timestamp' && !preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/D', $value)) {
				$msg = "Invalid column value: '{$column}' has to be in mysql timestamp format: Y-M-D H:i:s";
			}
		}
		
		if (!empty($msg)) {
            throw new Exception($msg);
		}
	}
	
	/**
	 * Not every column need to be normalized. This function should be called when you retreive values from database
	 * To make sure the values match the required type when doing insert/update later
	 * Skips private members as they are setup in a different way
	 *
	 * @param string $column 
	 *
	 * @return void
	 */
	private function normalizeColumn($column)
	{
		if (empty($this->ipn_columns[$column]['private'])) 
		{
			$type  = $this->ipn_columns[$column]['type'];
			$value = $this->ipn_columns[$column]['value'];

			if ($type === 'int' && !is_int($value)) {
				$this->setColumn($column, (int)$value, false);
			}
			elseif ($type === 'json') {
				$this->setColumn($column, json_decode($value, true), false);
			}
		}
	}	
	
	private function cheatProtection() 
	{
		global $CONF, $DB, $LNG;

		$run_extended = false;

		if ($this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_completed'] && $this->ipn_backup['status']['value'] === $LNG['payment_ipn_status_completed'] && !empty($this->ipn_columns['completed_once']['value'])) 
		{			
			$this->setColumn('cheat', 1);
			$this->setColumn('cheat_reason', 'Faked / Reposted completed IPN call');
		}
		elseif (!$this->Payment->validService($this->ipn_columns['service']['value'])) 
		{
			$this->setColumn('cheat', 2);
			$this->setColumn('cheat_reason', 'Invalid Service');
		}
		elseif($this->ipn_columns['payed']['value'] !== bcsub("{$this->ipn_columns['price']['value']}", "{$this->ipn_columns['discount']['value']}", 2)) 
		{
			$this->setColumn('cheat', 3);
			$this->setColumn('cheat_reason', 'Altered price');
		}
		elseif ($this->ipn_columns['username']['value'] !== false && !is_null($this->ipn_columns['username']['value']))
		{
			// Vl requires username, external systems linked to this class might skip username by passing null
			$username = $DB->escape($this->ipn_columns['username']['value'], 1);

			list($user_exist) = $DB->fetch("SELECT 1 FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);
			
			if (empty($user_exist))
			{
				$this->setColumn('cheat', 4);
				$this->setColumn('cheat_reason', 'Username not found');
			}
			else {
				$run_extended = true;
			}
		}
		else {
			$run_extended = true;
		}
		
		if ($run_extended === true) {
			$this->getExtendedCheatProtection();
		}
	}	
	
	/**
	 * Lets providers make their own cheat protections
	 * Once it is determined a cheat took place, call this function to have core checks extended
	 *
	 * @param string $cheat_message
	 *
	 * @return void
	 */
	private function extendCheatProtection($cheat_message)
	{
		// get the biggest cheat code and pass the new one
		$max     = max($this->cheat_codes);
		$new_max = $max + 1;
		$this->cheat_codes[] = $new_max;
		
		$this->extra_cheat_checks[] = ['code' => $new_max, 'reason' => $cheat_message];
	}
	
	/**
	 * Sets possible cheat markers, only if the core ones all passed
	 */
	private function getExtendedCheatProtection()
	{
		if (!empty($this->extra_cheat_checks))
		{
			$this->setColumn('cheat', $this->extra_cheat_checks[0]['code']);
			$this->setColumn('cheat_reason', $this->extra_cheat_checks[0]['reason']);
		}
	}
	
	private function save()
	{
		global $CONF, $DB, $LNG;
	
		$update         = [];
		$insert_columns = [];
		$insert_values  = [];
		
		// First thing call cheat protection
		$this->cheatProtection();
		
		// Set the ipn completed once marker, to catch faked/reposted completed IPN calls
		// Since we populate ipn_column with existing transaction, overwrite again status reason so they not hold possible previous data
		// e.g first ipn call was Pending, and we have a pending reason. Next call is completed, need to remove the pending reason
		if ($this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_completed'] && empty($this->ipn_columns['completed_once']['value'])) 
		{
			$this->setColumn('completed_once', 1);
			$this->setColumn('status_reason', null);
		}
		
		foreach ($this->ipn_columns as $column => $data)
		{
			$this->validateColumn($column);
			
			if ($this->save_method === 'update' && $data['exclude_update'] === true) {
				continue;
			}
			
			if ($data['nullable'] === true && is_null($data['value'])) {
				$data['value'] = 'NULL';
			}
			elseif ($data['type'] === 'int') {
				$data['value'] = (int)$data['value'];
			}
			elseif ($data['type'] === 'decimal') {
				// do nothing, its in right format already, checked validate
			}
			elseif ($data['type'] === 'json') 
			{
				$value         = $DB->escape(json_encode($data['value']), 1);
				$data['value'] = "'{$value}'";
			}
			else 
			{
				// All remainging types. string, timestamp
				$value         = $DB->escape($data['value'], 1);
				$data['value'] = "'{$value}'";
			}
			
			if ($this->save_method === 'insert')
			{
				$insert_columns[] = "`{$column}`";
				$insert_values[]  = $data['value'];
			}
			elseif ($this->save_method === 'update')
			{
				$update[] = "`{$column}` = {$data['value']}";
			}
		}

		if ($this->save_method === 'insert')
		{
			$insert_columns = implode(',', $insert_columns);
			$insert_values  = implode(',', $insert_values);
			
			$DB->query("INSERT INTO `{$CONF['sql_prefix']}_payment_logs` ({$insert_columns}) VALUES ({$insert_values})", __FILE__, __LINE__);
		}
		elseif ($this->save_method === 'update')
		{
			$txn_id   = $DB->escape($this->ipn_columns['txn_id']['value'], 1);
			$provider = $DB->escape($this->ipn_columns['provider']['value'], 1);
			$update   = implode(',', $update);
			
			$DB->query("UPDATE `{$CONF['sql_prefix']}_payment_logs` SET {$update} WHERE `txn_id` = '{$txn_id}' AND `provider` = '{$provider}'", __FILE__, __LINE__);
		}
		
		// Hand out rewards
		if (empty($this->ipn_columns['cheat']['value']) && ($this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_completed'] || $this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_failed']) && empty($this->ipn_backup['completed_once']['value'])) 
		{
			$this->reward();
		}
	}
	
	/**
	 * Setup completed or failed email
	 * If completed also hand out service reward/product
	 */
	private function reward()
	{
		global $CONF, $DB, $TMPL, $LNG;
				
		$txn_id   = $DB->escape($this->ipn_columns['txn_id']['value'], 1);
		$provider = $DB->escape($this->ipn_columns['provider']['value'], 1);
		
		// tmpl vars used in email, shared btw providers
		$TMPL['service']            = htmlspecialchars($this->ipn_columns['service']['value'], ENT_QUOTES, 'UTF-8');
		$TMPL['service_info']       = htmlspecialchars($this->ipn_columns['service_info']['value']['info'], ENT_QUOTES, 'UTF-8');
		$TMPL['transaction_id']     = htmlspecialchars($this->ipn_columns['txn_id']['value'], ENT_QUOTES, 'UTF-8');
		$TMPL['transaction_date']   = $this->ipn_columns['payment_date']['value'];
		$TMPL['transaction_status'] = htmlspecialchars($this->ipn_columns['status']['value'], ENT_QUOTES, 'UTF-8');			
		$TMPL['provider']           = htmlspecialchars($this->ipn_columns['provider']['value'], ENT_QUOTES, 'UTF-8');
		
		$TMPL['original_price']     = $this->ipn_columns['price']['value'];
		$TMPL['discount']           = $this->ipn_columns['discount']['value'];
		$TMPL['final_price']        = $this->ipn_columns['payed']['value'];

		// Default email subject, so its never empty, may be overwritten/extended
		$TMPL['subject'] = "{$TMPL['service']} - Transaction details";
		
		// User data
		// rewards user, setup remaining tmpl vars for email based on service
		if ($this->ipn_columns['service']['value'] === 'Premium')
		{
			$username = $DB->escape($this->ipn_columns['username']['value'], 1);
			
			$user_data  = $DB->fetch("SELECT * FROM {$CONF['sql_prefix']}_sites WHERE username = '{$username}'", __FILE__, __LINE__);

			if (!empty($user_data))
			{
				if ($this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_completed'])
				{
					$weeks_buy   = (int)$this->ipn_columns['service_info']['value']['internal']; 
					$total_days  = $weeks_buy * 7; 
					$remain_days = $total_days > 0 ? $total_days + 1 : 0;
					
					$premium_flag    = $CONF['auto_approve_premium'] == 1 ? 1 : 0;
					$premium_request = $premium_flag == 1 ? 0 : 1;

					if ($premium_flag == 1)
					{
						$date_now             = date('Y-m-d', time() + (3600 * $CONF['time_offset']));
						$approve_premium_date = "'".$DB->escape($date_now, 1)."'";
					}
					else {
						$approve_premium_date = 'NULL';
					}

					$DB->query("
						UPDATE {$CONF['sql_prefix']}_sites SET 
							premium_request = {$premium_request},
							premium_flag = {$premium_flag},
							date_start_premium = {$approve_premium_date},
							weeks_buy = weeks_buy + {$weeks_buy},
							total_day = total_day + {$total_days},
							remain_day = remain_day + {$remain_days}
						WHERE username = '{$username}'
					", __FILE__, __LINE__);
				}
				
				// Email data
				$TMPL['account_info'] = "Account: {$this->ipn_columns['username']['value']}";
				
				// Premium auto approve = plugin hook.
				// pending = extra info in email
				if ($premium_flag == 1) {
					eval (PluginManager::getPluginManager ()->pluginHooks ('user_premium_auto_approve'));
				}
				else {
					$TMPL['extra_info'] = $LNG['new_premium_user_info_pending'];
				}
			}
		}
		
		
		// Plugin hook, if you have different services
		// Make sure to call if ($this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_completed'])
		// if you hand out reward
		eval (PluginManager::getPluginManager ()->pluginHooks ('payment_ipn_reward'));
		
		
		// E-Mail ADMIN
		$join_premium_email_admin = new skin('payment_email_admin'); 
		$join_premium_email_admin->send_email($CONF['your_email']); 
		
		// E-Mail User
		if (!empty($user_data['email'])) 
		{
			$join_premium_email_user = new skin('payment_email_user'); 
			$join_premium_email_user->send_email($user_data['email']);
		}
	}
}
