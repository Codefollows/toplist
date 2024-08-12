<?php

class Coinbase 
{
	const API_URL             = 'https://api.commerce.coinbase.com/';
	const API_VERSION         = '2018-03-22';
	const API_CONNECT_TIMEOUT = 5;
	const API_TOTAL_TIMEOUT   = 10;
	
	private $apiKey;
   
    /**
     * @param string $apiKey
     */   
	public function __construct($apiKey = '') 
	{
		$this->apiKey = $apiKey;
	}	

    /**
     * @param string $payload
     * @param string $signature
     * @param string $secret
	 * @return object
     */
    public function getEvent($payload, $signature, $secret)
    {		
		$obj = $this->decodeBody($payload);

		if (!isset($obj->event)) {
			throw new Exception('Invalid event data received.');
		}
		
		$this->verifySignature($payload, $signature, $secret);

		return $obj->event;
    }			
	
    /**
     * @param array $body
     * @return object
     */
    public function createCharge($body)
    {		
		if (!function_exists('curl_init')) {
			throw new Exception('CURL PHP extension is required');
		}
		
        if (empty($this->apiKey)) {
            throw new \Exception('Missing Api key');
        }		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::API_URL . 'charges');
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::API_CONNECT_TIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::API_TOTAL_TIMEOUT);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Accept: application/json',
			'User-Agent: Coinbase ',
			'X-CC-Api-Key: ' . $this->apiKey,
			'X-CC-Version: '. self::API_VERSION,
		]);
		
		$response = curl_exec($ch);
		
		if (curl_errno($ch)) 
		{
			$errno  = curl_errno($ch);
			$errstr = curl_error($ch);
			
			curl_close($ch);
			
			throw new Exception("cURL error: [{$errno}] {$errstr}");
		}
		
		$info  = curl_getinfo($ch);
		curl_close($ch);
		
		$obj = $this->decodeBody($response);
		$this->checkForError($obj, $info['http_code']);
		
		if (!isset($obj->data)) {
			throw new Exception('Invalid api response data received.');
		}
		
		return $obj->data;
    }	

    /**
     * @param object $obj
     * @param int $http_code
     */
    private function checkForError($obj, $http_code)
    {
		$error = false;
		if (isset($obj->error->message)) {
			$error = $obj->error->message;
		}
		elseif (isset($obj->error->type)) {
			$error = $this->getErrorByType($obj->error->type);
		}
		elseif (isset($obj->warnings))
		{
			$error = 'Undefined warning';
			foreach ($obj->warnings as $warning) {
				$error .= is_array($warning) ? implode(', ', $warning) : $warning;
			}
		}
		else {
			$error = $this->getErrorByCode($http_code);
		}
		
		if ($error !== false) {			
			throw new Exception($error);
		}
    }
	
    /**
     * @param $type
     * @return string|false
     */
    private function getErrorByType($type)
    {
		$types = [
			'not_found'             => 'Endpoint not found. Please contact us.',
			'param_required'        => 'Invalid setup. Please contact us.',
			'validation_error'      => 'Data Validation failed. Please contact us.',
			'invalid_request'       => 'Invalid request. Please try again in a few moments or contact us if the error persists.',
			'authentication_error'  => 'Authentication with Coinbase failed. Please contact us.',
			'rate_limit_exceeded'   => 'Too many requests made to Coinbase too quickly. Please try again in a few moments',
			'internal_server_error' => 'Internal server error on Coinbase. Please try again in a few moments.',
		];

		return isset($types[$type]) ? $types[$type] : false;
    }
	
    /**
     * @param int $code
     * @return string|false
     */
    private function getErrorByCode($code)
    {
		$errors = [
			400 => 'Invalid request. Please try again in a few moments or contact us if the error persists.',
			401 => 'Authentication with Coinbase failed. Please contact us.',
			404 => 'Endpoint not found. Please contact us.',
			429 => 'Too many requests made to Coinbase too quickly. Please try again in a few moments',
			500 => 'Internal server error on Coinbase. Please try again in a few moments.',
			503 => 'Service unavailable. Please try again in a few moments.',
		];

		return isset($errors[$code]) ? $errors[$code] : false;
    }

    /**
     * @param string $json
	 * @return object
     */	
	private function decodeBody($json)
	{
		$obj = json_decode($json);

		if (json_last_error()) {
			throw new Exception('Invalid body data. No JSON object could be decoded.');
		}

		return $obj;
	}

    /**
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private function hashEqual($str1, $str2)
    {
		if (function_exists('hash_equals')) {
			return hash_equals($str1, $str2);
		}

		if (strlen($str1) != strlen($str2)) {
			return false;
		} 
		else 
		{
			$res = $str1 ^ $str2;
			$ret = 0;

			for ($i = strlen($res) - 1; $i >= 0; $i--) {
				$ret |= ord($res[$i]);
			}
			return !$ret;
		}
    }

    /**
     * @param string $payload
     * @param string $signature
     * @param string $secret
     */
    private function verifySignature($payload, $signature, $secret)
    {
		$computedSignature = hash_hmac('sha256', $payload, $secret);

		if (!$this->hashEqual($signature, $computedSignature)) {
			throw new Exception('Invalid Signature');
		}
    }	
}
