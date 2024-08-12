<?php

require_once("{$CONF['path']}/sources/payment/lib/PayPal.php"); 

try 
{
	$use_sandbox     = $this->payment_providers[$provider]['sandbox']['value'];
	$use_local_certs = $this->payment_providers[$provider]['local_cert']['value'];

	$p = new PayPal($use_sandbox, $use_local_certs);
	
	$verified = $p->verifyIPN();
	
	if ($verified) 
	{
		$status_names = [
			'Unrecognized'      => $LNG['payment_ipn_status_unrecognized'],
			'Canceled_Reversal' => $LNG['payment_ipn_status_canceled_reversal'],
			'Completed'         => $LNG['payment_ipn_status_completed'],
			'Created'           => $LNG['payment_ipn_status_created'],
			'Denied'            => $LNG['payment_ipn_status_denied'],
			'Expired'           => $LNG['payment_ipn_status_expired'],
			'Failed'            => $LNG['payment_ipn_status_failed'],
			'Pending'           => $LNG['payment_ipn_status_pending'],
			'Refunded'          => $LNG['payment_ipn_status_refunded'],
			'Reversed'          => $LNG['payment_ipn_status_reversed'],
			'Processed'         => $LNG['payment_ipn_status_processed'],
			'Voided'            => $LNG['payment_ipn_status_voided'],
		];
		
		// Several possible reasons for pending, canceled, refund etc...
		$pending_reason = [
			'address' => 'Your Payment Receiving Preferences are set so that if a customer does not include a confirmed shipping address, you must manually accept or deny the payment. To change your preference, go to the Preferences section of your Profile.',
			'authorization' => 'You set the payment action to Authorization and have not yet captured funds.',
			'delayed_disbursement' => 'The transaction has been approved and is currently awaiting funding from the bank. This typically takes less than 48 hrs.',
			'echeck' => 'The payment is pending because it was made by an eCheck that has not yet cleared.',
			'intl' => 'The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview.',
			'multi_currency' => 'You do not have a balance in the currency sent, and you do not have your profile\'s Payment Receiving Preferences option set to automatically convert and accept this payment. As a result, you must manually accept or deny this payment.',
			'order' => 'You set the payment action to Order and have not yet captured funds.',
			'paymentreview' => 'The payment is pending while it is reviewed by PayPal for risk.',
			'regulatory_review' => 'The payment is pending because PayPal is reviewing it for compliance with government regulations. PayPal will complete this review within 72 hours. When the review is complete, you will receive a second IPN message whose payment_status/reason code variables indicate the result.',
			'unilateral' => 'The payment is pending because it was made to an email address that is not yet registered or confirmed.',
			'upgrade' => 'The payment is pending because it was made via credit card and you must upgrade your account to Business or Premier status before you can receive the funds. upgrade can also mean that you have reached the monthly limit for transactions on your account.',
			'verify' => 'The payment is pending because you are not yet verified. You must verify your account before you can accept this payment.',
			'other' => 'The payment is pending for an unknown reason. For more information, contact PayPal Customer Service.',
			// According to docs, there are no additional codes, but just to make sure we catch everthing. Notify to contact support in that case 
			'unknown' => 'Pending reason received is not listed on paypal docs. Therefore please contact PayPal support and ask for the \'pending_reason\' explanation:',
		];
		$reason_code = [
			// Reversed, Refunded, Canceled_Reversal, Denied general reason
			'adjustment_reversal' => 'Reversal of an adjustment.',
			'admin_fraud_reversal' => 'The transaction has been reversed due to fraud detected by PayPal administrators.',
			'admin_reversal' => 'The transaction has been reversed by PayPal administrators.',
			'buyer-complaint' => 'The transaction has been reversed due to a complaint from your customer.',
			'buyer_complaint' => 'The transaction has been reversed due to a complaint from your customer.',
			'chargeback' => 'The transaction has been reversed due to a chargeback by your customer.',
			'chargeback_reimbursement' => 'Reimbursement for a chargeback.',
			'chargeback_settlement' => 'Settlement of a chargeback.',
			'guarantee' => 'The transaction has been reversed because your customer exercised a money-back guarantee.',
			'other' => 'Unspecified reason.',
			'refund' => 'The transaction has been reversed because you gave the customer a refund.',
			'regulatory_block' => 'PayPal blocked the transaction due to a violation of a government regulation.',
			'regulatory_reject' => 'PayPal rejected the transaction due to a violation of a government regulation and returned the funds to the buyer.',
			'regulatory_review_exceeding_sla' => 'PayPal did not complete the review for compliance with government regulations within 72 hours, as required. Consequently, PayPal auto-reversed the transaction and returned the funds to the buyer. Note that `sla` stands for `service level agreement`.',
			'unauthorized_claim' => 'The transaction has been reversed because it was not authorized by the buyer.',
			'unauthorized_spoof' => 'The transaction has been reversed due to a customer dispute in which an unauthorized spoof is suspected.',
			// If a dispute is opened
			'case_type' => [
				'complaint' => [
					'non_receipt' => 'Buyer claims that he did not receive goods or service.',
					'not_as_described' => 'Buyer claims that the goods or service received differ from merchant\'s description of the goods or service.',
					'unauthorized_claim' => 'Buyer claims that an unauthorized payment was made for this particular transaction.',
				],
				'chargeback' => [
					'unauthorized' => 'The buyer claims that they didn\'t authorize the transaction.',
					'adjustment_reimburse' => 'A case that has been resolved and closed requires a reimbursement.',
					'non_receipt' => 'Buyer claims that he did not receive goods or service.',
					'duplicate' => 'Buyer claims that a possible duplicate payment was made to the merchant.',
					'merchandise' => 'Buyer claims that the received merchandise is unsatisfactory, defective, or damaged.',
					'billing' => 'Buyer claims that the received merchandise is unsatisfactory, defective, or damaged.',
					'special' => 'Some other reason. Usually, special indicates a credit card processing error for which the merchant is not responsible and for which no debit to the merchant will result. PayPal must review the documentation from the credit card company to determine the nature of the dispute and possibly contact the merchant to resolve it.',
				],
			],
			// According to docs, there might be additional codes, notify to contact support in that case 
			'unknown' => 'Reason code received is not listed on paypal docs. Therefore please contact PayPal support and ask for the \'reason_code\' explanation:',
		];
		
		// Some internal variables
		// parent_txn_id exist? Overwrite, we not need the new txn id in case of refund, reversal, or canceled reversal
		$txn_id      = isset($p->ipn_data['txn_id'])        ? $p->ipn_data['txn_id']        : null;
		$txn_id      = isset($p->ipn_data['parent_txn_id']) ? $p->ipn_data['parent_txn_id'] : $txn_id;
		$status_name = isset($p->ipn_data['payment_status']) && isset($status_names[$p->ipn_data['payment_status']]) ? $p->ipn_data['payment_status'] : 'Unrecognized';
		$custom      = isset($p->ipn_data['custom']) ? explode('|', $p->ipn_data['custom']) : [];

		/**
		 * Populate ipn array with default values or existing transaction
		 * This is an automated process and has to be the first ipn action
		 */
		$this->initColumns($txn_id);		
		
		
		/**
		 * Very edge case for "Canceled_Reversal"
		 * 	-> We start with "Created" or "Pending" status
		 *	-> We get "Reversed" next, which could have several reason. See $reason_code
		 *	-> Customer cancel or paypal resolve in your favor and we get "Canceled_Reversal"
		 *
		 * "Completed" status code never ran in this case and premium never handed out, but it should
		 * Overwrite the status to "Completed" 
		 */
		if ($status_name === 'Canceled_Reversal' && empty($this->ipn_columns['completed_once']['value'])) {
			$status_name = 'Completed';
		}
		
		/**
		 * Set ipn data, include these on every type of ipn status if they are required
		 *
		 * status        - required
		 * status_reason - optional - default null
		 * payment_date  - optional - default current datetime upon first insert
		 *
		 */
		 
		$this->setColumn('status', $status_names[$status_name]);
		

		if (isset($p->ipn_data['pending_reason']))
		{
			$status_key = $p->ipn_data['pending_reason'];
			$this->setColumn('status_reason', "{$pending_reason['unknown']} '{$status_key}'");
			
			if (isset($pending_reason[$status_key])) {			
				$this->setColumn('status_reason', $pending_reason[$status_key]);
			}
		}

		if (isset($p->ipn_data['reason_code']))
		{
			$status_key    = $p->ipn_data['reason_code'];
			$this->setColumn('status_reason', "{$reason_code['unknown']} '{$status_key}'");

			// First normal reason, then check if possible dispute values
			if (isset($reason_code[$status_key])) {
				$this->setColumn('status_reason', $reason_code[$status_key]);
			}

			if (isset($p->ipn_data['case_type']))
			{
				$case_type = $p->ipn_data['case_type'];

				// Reset reason
				$this->setColumn('status_reason', "{$reason_code['unknown']} '{$status_key}' - And 'case_type' explanation: '{$case_type}'");

				if (isset($reason_code['case_type'][$case_type][$status_key])) {			
					$this->setColumn('status_reason', $reason_code['case_type'][$case_type][$status_key]);
				}
			}
		}

		if (isset($p->ipn_data['payment_date']))
		{
			$payment_date_obj = new DateTime($p->ipn_data['payment_date']);
			$this->setColumn('payment_date', $payment_date_obj->format('Y-m-d H:i:s'));			
		}
		

		/**
		 * Set ipn data, include these only on the first ipn call, weather its completed payment or not
		 * If you pass these anyway, they will be ignored and only useful if you do some compare in this file with it
		 *
		 * payed        - required - decimal number with 2 precision
		 * fee          - optional - decimal number with 2 precision - default 0.00 
		 * service      - required - the service the user payed for. e.g Premium
		 * service_info - required - array in the format ['internal' => '', 'info' => ''] - internal is a value used internal for calculation, info is used for email and payment history
		 * price        - required - decimal number with 2 precision - price of the service
		 * discount     - optional - decimal number with 2 precision - default 0.00
		 * username     - required - the VisioList user who purchased this service
		 * email        - optional - the email the user payed with - default null
		 * country      - optional - default N/A
		 * country_code - optional - default N/A
		 * state        - optional - default N/A
		 * city         - optional - default N/A
		 * zip          - optional - default N/A
		 * street       - optional - default N/A
		 * fname        - optional - default N/A
		 * lname        - optional - default N/A
		 */
		if ($this->save_method === 'insert')
		{
			/**
			 * In all cases cast mc_gross and mc_fee to positive
			 *
			 * Status
			 *	Completed, Created, Denied, Expired, Failed, Pending, Processed, Voided
			 *		mc_gross = positive full amount
			 *		mc_fee   = positive	
			 *	Refunded 
			 *		mc_gross = Negative full amount
			 *		mc_fee   = negative
			 *	Reversed
			 *		mc_gross = negative without fee
			 *		mc_fee   = negative
			 *	Canceled_Reversal
			 *		mc_gross = positive without fee
			 *		mc_fee   = positive
			 */
			$payed = number_format(abs($p->ipn_data['mc_gross']), 2, '.', '');
			$fee   = number_format(abs($p->ipn_data['mc_fee']), 2, '.', '');
			if ($this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_reversed'] || $this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_canceled_reversal']) {
				$payed = bcadd("{$payed}", "{$fee}", 2);
			}
			
			$this->setColumn('payed', $payed);
			$this->setColumn('fee', $fee);	
	
			if (!empty($custom[0])) {
				$this->setColumn('service', $custom[0]);
			}	
			
			$username = isset($custom[2]) ? $custom[2] : '';
			if (mb_strlen($username) > 0) {
				$this->setColumn('username', $username);
			}
	
			if ($this->ipn_columns['service']['value'] === 'Premium')
			{
				$weeks_buy  = isset($custom[1]) ? (int)$custom[1] : 0;
				$total_days = $weeks_buy * 7;
				$price      = bcmul("{$weeks_buy}", "{$CONF['one_w_price']}", 2);

				// calculate discount
				$line_discount = 0;
				if ($CONF['discount_qty_03'] > 0 && $weeks_buy >= $CONF['discount_qty_03']) {
					$line_discount = $CONF['discount_value_03'];
				}
				elseif ($CONF['discount_qty_02'] > 0 && $weeks_buy >= $CONF['discount_qty_02']) {
					$line_discount = $CONF['discount_value_02'];
				}
				elseif ($CONF['discount_qty_01'] > 0 && $weeks_buy >= $CONF['discount_qty_01']) {
					$line_discount = $CONF['discount_value_01'];
				}

				$discount_multiplier = bcdiv("{$line_discount}", '100', 2);
				$discount            = bcmul("{$price}", "{$discount_multiplier}", 2);
				
				$service_info             = [];
				$service_info['internal'] = $weeks_buy;
				$service_info['info']     = "Duration: {$weeks_buy} Weeks ({$total_days} days)";
				$service_info['info']    .= "\nUsername: {$username}";

				
				$this->setColumn('price', $price);
				$this->setColumn('discount', $discount);
				$this->setColumn('service_info', $service_info);
			}
		

			if (!empty($p->ipn_data['payer_email'])) {
				$this->setColumn('email', $p->ipn_data['payer_email']);
			}	

			if (!empty($p->ipn_data['address_country'])) {
				$this->setColumn('country', $p->ipn_data['address_country']);
			}
			
			if (!empty($p->ipn_data['address_country_code'])) {
				$this->setColumn('country_code', $p->ipn_data['address_country_code']);
			}	

			if (!empty($p->ipn_data['address_state'])) {
				$this->setColumn('state', $p->ipn_data['address_state']);
			}	

			if (!empty($p->ipn_data['address_city'])) {
				$this->setColumn('city', $p->ipn_data['address_city']);
			}	

			if (!empty($p->ipn_data['address_zip'])) {
				$this->setColumn('zip', $p->ipn_data['address_zip']);
			}	
			
			if (!empty($p->ipn_data['address_street'])) {
				$this->setColumn('street', $p->ipn_data['address_street']);
			}

			if (!empty($p->ipn_data['first_name'])) {
				$this->setColumn('fname', $p->ipn_data['first_name']);
			}	
			
			if (!empty($p->ipn_data['last_name'])) {
				$this->setColumn('lname', $p->ipn_data['last_name']);
			}	
		}
	


		/** 
		 * You might pass more cheat protection to the save handler
		 * Example
		 *
		 *	if ($cheat_detected) {
		 *		$this->extendCheatProtection('Message which explains what cheat took place');
		 *	} 
		*/
		$business_email = isset($p->ipn_data['business']) ? $p->ipn_data['business'] : '';
		if ($business_email !== $this->payment_providers[$provider]['email']['value']) {
			$this->extendCheatProtection('IPN call not for your paypal email. Most likely faked ipn call');
		}



		/**
		 * Plugin hook, if you need to do more checks, or extend in any way
		 *
		 * Make sure to surround your code in provider specific if clause in case you only want to execute it for a specific provider
		 * if ($provider === 'PayPal') { do my stuff }
		 *
		 */
		eval(PluginManager::getPluginManager()->pluginHooks('payment_ipn_before_save'));



		/**
		 * Save the ipn
		 *
		 * This has to be last call ( except the plugin hook ) inside each provider file!
		 *
		 * Any invalid data type, wrong columns will produce the ipn to fail and insert into ipn log database with details
		 */
		$this->save();



		/**
		 * Plugin hook, if you need to execute code after successfull insert/update IPN
		 *
		 * Make sure to surround your code in provider specific if clause in case you only want to execute it for a specific provider
		 * if ($provider === 'PayPal') { do my stuff }
		 *
		 */
		eval(PluginManager::getPluginManager()->pluginHooks('payment_ipn_after_save'));
	}
}
catch(Exception $e) 
{
	// pass back exceptions ( missing post, curl error ) to parent throwable catch
	throw $e;
}
