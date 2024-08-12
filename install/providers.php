<?php

$providers = [
	'PayPal' => [
		'enabled' => [
			'type'  => 'checkbox',
			'label' => 'Enabled?',
			'value' => false,
			'docs'  => 'https://visiolist.com/community/threads/payment-provider-paypal.2380/',
		],
		'sandbox' => [
			'type' => 'checkbox',
			'label' => 'Use sandbox test mode?',
			'value' => false,
			'internal' => 'sandbox.',
		],
		'local_cert' => [
			'type' => 'checkbox',
			'label' => 'Use local ssl certifacte instead of curl built in certificate? Only check this if you have issues with payments',
			'value' => false
		],
		'email' => [
			'type' => 'input',
			'label' => 'Your PayPal E-Mail for Payments',
			'value' => ''
		],
	],
	'Stripe' => [
		'enabled' => [
			'type'  => 'checkbox',
			'label' => 'Enabled?',
			'value' => false,
			'docs'  => 'https://visiolist.com/community/threads/payment-provider-stripe.2377/',
		],
		'api_public_key' => [
			'type' => 'input',
			'label' => 'API public key',
			'value' => '',
		],
		'api_secret_key' => [
			'type' => 'input',
			'label' => 'API secret key',
			'value' => '',
		],
		'webhook_secret_key' => [
			'type' => 'input',
			'label' => 'Webhook signature secret key',
			'value' => '',
		],
	],
	'Paygol' => [
		'enabled' => [
			'type'  => 'checkbox',
			'label' => 'Enabled?',
			'value' => false,
			'docs'  => 'https://visiolist.com/community/threads/payment-provider-paygol.2378/',
		],
		'service_id' => [
			'type' => 'input',
			'label' => 'Service ID',
			'value' => '',
		],
		'secret_key' => [
			'type' => 'input',
			'label' => 'Secret key',
			'value' => '',
		],
	],
	'Coinbase' => [
		'enabled' => [
			'type'  => 'checkbox',
			'label' => 'Enabled?',
			'value' => false,
			'docs'  => 'https://visiolist.com/community/threads/payment-provider-coinbase.2379/',
		],
		'api_key' => [
			'type' => 'input',
			'label' => 'API key',
			'value' => '',
		],
		'webhook_secret_key' => [
			'type' => 'input',
			'label' => 'Webhook secret key',
			'value' => '',
		],
	],
];