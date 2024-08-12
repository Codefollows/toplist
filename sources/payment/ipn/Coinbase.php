<?php
require_once("{$CONF['path']}/sources/payment/lib/Coinbase.php");

try 
{
	$payload   = file_get_contents('php://input');
	$signature = isset($_SERVER['HTTP_X_CC_WEBHOOK_SIGNATURE']) ? $_SERVER['HTTP_X_CC_WEBHOOK_SIGNATURE'] : '';
		
	$Coinbase = new Coinbase();
	$event    = $Coinbase->getEvent($payload, $signature, $this->payment_providers[$provider]['webhook_secret_key']['value']);
} 
catch(Exception $e) {
	// Failed to get event payload, wrong signature, code error etc
	throw $e;
}

/**
 * If you have multiple webhooks inside the same Coinbase Commerce account,
 * they make a request to EACH webhook, no matter from which domain the payment originated. That is cause webhook and api key are not linked together, great....
 *
 * E.g - you use Coinbase on domain 1 and 2 and have a webhook for each of those domains added
 * Now payment on domain 1, and Coinbase calls both domain webhooks ( bad system in my opinion )
 * If you share the same usernames on both domains, this could result in a user getting free stuff, which we not want obviously...
 *
 * By making use of custom meta data, we can link the webhook call to this script and exit for anything we not want
 * When we exit, we have to return status code 200 to acknowledge receipt of a webhook! Then it not gets recalled for up to 3 days and we reduce useless http requests
 */
if (!isset($event->data->metadata->list_url) || $event->data->metadata->list_url !== $CONF['list_url']) 
{
	http_response_code(200);
	exit;	
}

	
/** 
 * Status events we catch 
 *	You have to set these up on Coinbase
 *  https://commerce.coinbase.com/dashboard/settings
 * 		1) webhook url: https://domain.com/index.php?a=payment_ipn&provider=Coinbase
 *		1.1) There shall be no redirect happen, so make sure it matches your list url.
 *		1.2) Have to use https
 *		2) The hooks needed to add are the array keys below. By default all a checked, but make sure they really are
 *		3) the webhook secret key, you need to copy inside your admin settings
 */
$events = [
	'charge:created'   => 'created',
	'charge:pending'   => 'pending',
	'charge:failed'    => 'pending_resolve',
	'charge:confirmed' => 'completed',
	'charge:resolved'  => 'completed',
	'charge:delayed'   => 'expired',
];

$timeline = [
	'NEW'            => 'created',
	'PENDING'        => 'pending',
	'UNRESOLVED'     => 'pending_resolve',
	'REFUND PENDING' => 'pending_refund',
	'COMPLETED'      => 'completed',
	'RESOLVED'       => 'completed',
	'REFUNDED'       => 'refunded',
	'EXPIRED'        => 'expired',
	'CANCELED'       => 'canceled',
];

$status_names = [
	'unrecognized'    => $LNG['payment_ipn_status_unrecognized'],
	'created'         => $LNG['payment_ipn_status_created'],
	'pending'         => $LNG['payment_ipn_status_pending'],
	'pending_resolve' => $LNG['payment_ipn_status_pending'],
	'pending_refund'  => $LNG['payment_ipn_status_pending_refund'],
	'completed'       => $LNG['payment_ipn_status_completed'],
	'refunded'        => $LNG['payment_ipn_status_refunded'],
	'expired'         => $LNG['payment_ipn_status_expired'],
	'canceled'        => $LNG['payment_ipn_status_canceled'],
];

$status_reasons = [
	'unrecognized'   => 'Payment status can not be recognized. Please contact us to investigate.',
	'created'        => 'The payment request has been created and awaits a transaction.',
	'pending'        => 'The transaction has been detected and awaits confirmation by the blockchain network.',
	'pending_refund' => 'A refund has been issued by the merchant and awaits confirmation by the blockchain network.',
	'refunded'       => 'The transaction has been refunded.',	
	'expired'        => 'The payment request has expired (requests expire after 60 minutes if no payment has been detected).',		
	'canceled'       => 'The payment request has been canceled.',	
];


// Some internal variables
$txn_id      = isset($event->data->code) ? $event->data->code : null;
$status_name = isset($event->type) && isset($events[$event->type]) && isset($status_names[$events[$event->type]]) ? $events[$event->type] : 'unrecognized';

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
 
// Overwrite status and possibly reason if we have a status timeline ( should always be there )
if (!empty($event->data->timeline) && is_array($event->data->timeline))
{
	$latest_timeline = end($event->data->timeline);
	reset($event->data->timeline);
		
	if (isset($latest_timeline->status) && isset($timeline[$latest_timeline->status])) 
	{
		$status_name = $timeline[$latest_timeline->status];
		$this->setColumn('status', $status_names[$status_name]);

		if (isset($status_reasons[$status_name])) {
			$this->setColumn('status_reason', $status_reasons[$status_name]);
		}

		// Context when unresolved
		$timeline_context = [
			'UNDERPAID' => 'The payment has to be manually verified, as it was underpaid. Mostly caused by your wallet using a significantly different exchange rate than Coinbase Commerce. Please contact us if this does not happen within a reasonable timeframe.', 
			'OVERPAID'  => 'The payment has to be manually verified, as it was overpaid. Mostly caused by your wallet using a significantly different exchange rate than Coinbase Commerce. Please contact us if this does not happen within a reasonable timeframe.', 
			'DELAYED'   => 'The payment has to be manually verified, as it was completed after the payment request expired. Please contact us if this does not happen within a reasonable timeframe.', 
			'MULTIPLE'  => 'The payment has to be manually verified, as multiple transaction were detected for the same order. Please contact us to verify the payment manually to resolve the issue.', 
			'MANUAL'    => 'The payment has to be manually verified. Please contact us if this does not happen within a reasonable timeframe.', 
			'OTHER'     => 'The payment has to be manually verified, as the transaction was of unknown type. Please contact us if this does not happen within a reasonable timeframe.',
		];
	
		if (isset($latest_timeline->context) && isset($timeline_context[$latest_timeline->context])) {
			$this->setColumn('status_reason', $timeline_context[$latest_timeline->context]);
		}
	}
}

if (isset($event->created_at))
{
	$payment_date_obj = new DateTime($event->created_at);								
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
	// Payments can be underpaid or overpaid due live exchange rates, so its possible the amount paid is not 100% the same as what we requested
	// Certain events also not include payment data, such as created, canceled and possibly others we cant test cause coinbase has no real sandbox mode
	// So we just set the payed amount manually on the first received event ( created ), to make sure we not trigger cheat protection
	if (isset($event->data->pricing->local->amount)) {
		$this->setColumn('payed', number_format($event->data->pricing->local->amount, 2, '.', ''));
	}
	
	if (isset($event->data->metadata->service)) {
		$this->setColumn('service', $event->data->metadata->service);
	}
	
	$username = isset($event->data->metadata->service_username) ? $event->data->metadata->service_username : '';
	if (mb_strlen($username) > 0) {
		$this->setColumn('username', $username);
	}
	
	if ($this->ipn_columns['service']['value'] === 'Premium')
	{
		$weeks_buy  = isset($event->data->metadata->service_value) ? (int)$event->data->metadata->service_value : 0;
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
