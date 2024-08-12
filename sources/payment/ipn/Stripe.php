<?php
require_once("{$CONF['path']}/sources/payment/lib/Stripe/init.php");

try 
{
	$payload   = file_get_contents('php://input');
	$signature = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';

	$event = \Stripe\Webhook::constructEvent(
		$payload, $signature, $this->payment_providers[$provider]['webhook_secret_key']['value']
	);
} 
catch(\UnexpectedValueException $e) {
	// Invalid payload, passed back to parent throwable catch
	throw $e;
} 
catch(\Stripe\Exception\SignatureVerificationException $e) {
	// Invalid signature, passed back to parent throwable catch
	throw $e;
}

/** 
 * Status events we catch
 *	You have to set these up on stripe developer tools webhooks
 *  https://dashboard.stripe.com/webhooks
 * 		1) webhook url: https://domain.com/index.php?a=payment_ipn&provider=Stripe
 *		1.1) There shall be no redirect happen, so make sure it matches your list url.
 *		1.2) Have to use https
 *		2) The hooks needed to add are the array keys below
 *		3) the webhook secret key, you need to copy inside your admin settings
 */
$events = [
	'payment_intent.payment_failed' => $LNG['payment_ipn_status_failed'],
	'payment_intent.processing'     => $LNG['payment_ipn_status_pending'],
	'payment_intent.succeeded'      => $LNG['payment_ipn_status_completed'],
];

// The payment intent object. This holds all the data we need
$intent = $event->data->object;


/**
 * If you have multiple webhooks inside the same Stripe account,
 * they make a request to EACH webhook, no matter from which domain the payment originated. That is cause webhook and api key are not linked together, great....
 *
 * E.g - you use Stripe on domain 1 and 2 and have a webhook for each of those domains added
 * Now payment on domain 1, and Stripe calls both domain webhooks ( bad system in my opinion )
 * If you share the same usernames on both domains, this could result in a user getting free stuff, which we not want obviously...
 *
 * By making use of custom meta data, we can link the webhook call to this script and exit for anything we not want
 * When we exit, we have to return status code 200 to acknowledge receipt of a webhook! Then it not gets recalled for up to 3 days and we reduce useless http requests
 */
if (!isset($intent->metadata->list_url) || $intent->metadata->list_url !== $CONF['list_url']) {
	http_response_code(200);
	exit();	
}


/*
 * The charge data holds 
 * 	Payer billing details like name, email, country etc
 *	A billing transaction id we need to grab the stripe fee
 */
$charge = isset($intent->charges->data[0]) ? $intent->charges->data[0] : null;
$user   = isset($charge->billing_details)  ? $charge->billing_details  : null;

// Some internal variables
$txn_id = isset($intent->id) ? $intent->id : null;
$status = isset($event->type) && isset($events[$event->type]) ? $events[$event->type] : $LNG['payment_ipn_status_unrecognized'];



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
$this->setColumn('status', $status);

if ($this->ipn_columns['status']['value'] === $LNG['payment_ipn_status_failed'] && !empty($intent->last_payment_error)) {
	$this->setColumn('status_reason', $intent->last_payment_error->message);
}

if (isset($intent->created))
{
	$payment_date_obj = new DateTime();								
	$this->setColumn('payment_date', $payment_date_obj->setTimestamp($intent->created)->format('Y-m-d H:i:s'));
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
	$payed = isset($intent->amount) ? (int)$intent->amount : 0;
	$this->setColumn('payed', number_format($payed / 100, 2, '.', ''));

	$fee = 0.00;
	try
	{
		if (!empty($charge->balance_transaction))
		{
			$StripeClient = new \Stripe\StripeClient($this->payment_providers[$provider]['api_secret_key']['value']);
		
			$balance_transaction = $StripeClient->balanceTransactions->retrieve($charge->balance_transaction, []);

			$fee = isset($balance_transaction->fee) ? (int)$balance_transaction->fee : 0;
			$fee = $fee / 100;
		}
	}
	catch (\Stripe\Exception\RateLimitException $e) {
		// Too many requests made to the API too quickly
		$fee = 0.00;
	} 
	catch (\Stripe\Exception\InvalidRequestException $e) {
		// Invalid parameters were supplied to Stripe's API
		$fee = 0.00;
	} 
	catch (\Stripe\Exception\AuthenticationException $e) {
		// Authentication with Stripe's API failed (maybe you changed API keys recently)
		$fee = 0.00;
	} 
	catch (\Stripe\Exception\ApiConnectionException $e) {
		// Network communication with Stripe failed
		$fee = 0.00;
	} 
	catch (\Stripe\Exception\ApiErrorException $e) {
		// Other Stripe API errors
		$fee = 0.00;
	}

	$this->setColumn('fee', number_format($fee, 2, '.', ''));


	if (isset($intent->metadata->service)) {
		$this->setColumn('service', $intent->metadata->service);
	}
	
	$username = isset($intent->metadata->service_username) ? $intent->metadata->service_username : '';
	if (mb_strlen($username) > 0) {
		$this->setColumn('username', $username);
	}
			
	if ($this->ipn_columns['service']['value'] === 'Premium')
	{
		$weeks_buy  = isset($intent->metadata->service_value) ? (int)$intent->metadata->service_value : 0;
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
		
	
	if (!empty($user->email)) {
		$this->setColumn('email', $user->email);
	}	

	if (!empty($user->address->country)) {
		$this->setColumn('country_code', $user->address->country);
	}	

	if (!empty($user->address->state)) {
		$this->setColumn('state', $user->address->state);
	}	

	if (!empty($user->address->city)) {
		$this->setColumn('city', $user->address->city);
	}	

	if (!empty($user->address->postal_code)) {
		$this->setColumn('zip', $user->address->postal_code);
	}	

	if (!empty($user->name)) 
	{
		$full_name = explode(' ', $user->name, 2);

		if (!empty($full_name[0])) {
			$this->setColumn('fname', $full_name[0]);
		}	
		if (!empty($full_name[1])) {
			$this->setColumn('lname', $full_name[1]);
		}	
	}	

	if (!empty($user->address->line1) || !empty($user->address->line2)) 
	{
		$street       = '';
		$street_line1 = !empty($user->address->line1) ? $user->address->line1 : false;
		$street_line2 = !empty($user->address->line2) ? $user->address->line2 : false;
		
		if ($street_line1 !== false) {
			$street .= $street_line1;
		}
		if ($street_line2 !== false) 
		{
			if ($street_line1 !== false) {
				$street .= ' ';
			}

			$street .= $street_line2;
		}
		
		if (!empty($street)) {
			$this->setColumn('street', $street);
		}		
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
