<?php

class PayPal 
{
	/** @var array contains the POST values for IPN **/
	public $ipn_data = [];
	 
    /** @var bool Indicates if the sandbox endpoint is used. */
    private $use_sandbox;

    /** @var bool Indicates if the local certificates are used. */
    private $use_local_certs;
	
    const VERIFY_URI         = 'https://ipnpb.paypal.com/cgi-bin/webscr';
    const SANDBOX_VERIFY_URI = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    const VALID              = 'VERIFIED';
    const INVALID            = 'INVALID'; 
   
	public function __construct($use_sandbox, $use_local_certs) 
	{
		$this->use_sandbox     = (bool)$use_sandbox;
		$this->use_local_certs = (bool)$use_local_certs;
	}
	
    /**
     * Determine endpoint to post the verification data to.
     *
     * @return string
     */
    private function getIpnUri()
    {
        if ($this->use_sandbox) {
            return self::SANDBOX_VERIFY_URI;
        } 
		
		return self::VERIFY_URI;
    }   
   
    /**
     * Verification Function
     * Sends the incoming post data back to PayPal using the cURL library.
     *
     * @return bool
     * @throws Exception
     */
    public function verifyIPN()
    {
		if (!function_exists('curl_init')) {
			throw new Exception('CURL PHP extension is required');
		}
		
        if (!count($_POST)) {
            throw new Exception("Missing POST Data");
        }

		// STEP 1: read POST data
		// Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
		// Instead, read raw POST data from the input stream.
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = [];
        foreach ($raw_post_array as $keyval) 
		{
            $keyval = explode('=', $keyval);
			
            if (count($keyval) == 2) 
			{
				// Since we do not want the plus in the date string to be encode to a space, we manually encode it
				if ($keyval[0] === 'payment_date')
				{
					if (substr_count($keyval[1], '+') === 1) {
						$keyval[1] = str_replace('+', '%2B', $keyval[1]);
					}
				}
				
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }
		
        if (empty($myPost)) {
            throw new Exception("Malformed POST Data");
        }
		
		// read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
		$req = 'cmd=_notify-validate';
		foreach ($myPost as $key => $value) 
		{
			$this->ipn_data[$key] = $value;
			$value                = urlencode($value);
			$req                 .= "&{$key}={$value}";
		}
				
		// Step 2: POST IPN data back to PayPal to validate
		$ch = curl_init($this->getIpnUri());
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		
		// Paypal requires tls version 1.2 support
		curl_setopt($ch, CURLOPT_SSLVERSION, 6);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		
        // This is often required if the server is missing a global cert bundle, or is using an outdated one.
        if ($this->use_local_certs) {
            curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/../cert/cacert.pem");
        }

		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: PHP-IPN-Verification-Script',
            'Connection: Close',
        ));
		
        $res = curl_exec($ch);
		
        if (curl_errno($ch)) 
		{
            $errno  = curl_errno($ch);
            $errstr = curl_error($ch);
			
            curl_close($ch);
			
            throw new Exception("cURL error: [$errno] $errstr");
        }
		
        $info = curl_getinfo($ch);
        $http_code = $info['http_code'];
        if ($http_code != 200) 
		{
			curl_close($ch);
			
            throw new Exception("PayPal responded with http code $http_code");
        }

        curl_close($ch);

        // Check if PayPal verifies the IPN data, and if so, return true.
        if ($res === self::VALID) {
            return true;
        }
		
		return false;
	}
}
