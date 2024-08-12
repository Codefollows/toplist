<?php 
require_once("{$CONF['path']}/sources/payment/lib/Paygol_autoload.php");

use Paygol\API;
use Paygol\Notification;

$ipn = new Notification($this->payment_providers[$provider]['service_id']['value'], $this->payment_providers[$provider]['secret_key']['value']);

try 
{
	$status_names = [
		'unrecognized' => $LNG['payment_ipn_status_unrecognized'],
		'created'      => $LNG['payment_ipn_status_pending'],
		'completed'    => $LNG['payment_ipn_status_completed'],
		'failed'       => $LNG['payment_ipn_status_failed'],
		'rejected'     => $LNG['payment_ipn_status_rejected'],
	];
	
	$status_reasons = [
		'unrecognized' => 'Payment status can not be recognized. Please contact us to investigate.',
		'created'      => 'Payment never completed the payment process, or the payment has not yet been registered by the payment method provider.',
		'failed'       => 'Payment method provider did not accept the payment.',
		'rejected'     => 'Paygol has rejected the payment, likely due to a notification from the payment method provider.',
	];

    $ipn->validate();
	
    $params = $ipn->getParams();
	    
	// Some internal variables
	$txn_id      = isset($params['transaction_id']) ? $params['transaction_id'] : null;
	$status_name = isset($params['status']) && isset($status_names[$params['status']]) && isset($status_names[$params['status']]) ? $params['status'] : 'unrecognized';
	$custom      = isset($params['custom']) ? explode('|', $params['custom']) : [];



	/**
	 * Populate ipn array with default values or existing transaction
	 * This is an automated process and has to be the first ipn action
	 */
	$this->initColumns($txn_id);



	/**
	 * Set ipn data, include these on every type of ipn status if they are required
	 *
	 * status        - required
	 * status_reason - optional - default null
	 * payment_date  - optional - default current datetime upon first insert
	 *
	 */
	$this->setColumn('status', $status_names[$status_name]);

	if (isset($status_reasons[$status_name])) {
		$this->setColumn('status_reason', $status_reasons[$status_name]);
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
		$payed = isset($params['price']) ? $params['price'] : 0;
		$this->setColumn('payed', number_format($payed, 2, '.', ''));

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
		
		
		// We dont need the status, but this call includes customer data
		$Paygol_API = new API($this->payment_providers[$provider]['service_id']['value'], $this->payment_providers[$provider]['secret_key']['value']);
		$extra      = $Paygol_API->getPaymentStatus($this->ipn_columns['txn_id']['value']);
				
		if (!empty($extra->payment->customer->email)) {
			$this->setColumn('email', $extra->payment->customer->email);
		}	
		
		if (!empty($extra->payment->customer->country)) {
			$this->setColumn('country_code', $extra->payment->customer->country);
		}	
		
		if (!empty($extra->payment->customer->first_name)) {
			$this->setColumn('fname', $extra->payment->customer->first_name);
		}

		if (!empty($extra->payment->customer->last_name)) {
			$this->setColumn('lname', $extra->payment->customer->last_name);
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
		
		
		
	// Paygol required the text OK
	$ipn->sendResponse(['OK'], 200);
} 
catch (\Exception $e) 
{
	$ipn->sendResponse(['error' => 'Validation error'], 400);
	
	throw $e;	
}