<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Paypal Payment Driver
 *
 * You have to set payerid after authorizing with paypal:
 * $this->paypment->payerid = $this->input->get('payerid');
 *
 * $Id: Paypal.php 1938 2008-02-05 23:47:53Z zombor $
 *
 * @package    Payment
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Payment_Paypal_Driver implements Payment_Driver {

	private $required_fields = array
	(
		'API_UserName'  => FALSE,
		'API_Password'  => FALSE,
		'API_Signature' => FALSE,
		'API_Endpoint'  => TRUE,
		'version'       => TRUE,
		'Amt'           => FALSE,
		'PAYMENTACTION' => TRUE,
		'ReturnUrl'     => FALSE,
		'CANCELURL'     => FALSE,
		'CURRENCYCODE'  => TRUE
	);

	private $paypal_values = array
	(
		'API_UserName'  => '',
		'API_Password'  => '',
		'API_Signature' => '',
		'API_Endpoint'  => 'https://api-3t.paypal.com/nvp',
		'version'       => '3.0',
		'Amt'           => 0,
		'PAYMENTACTION' => 'Sale',
		'ReturnUrl'     => '',
		'CANCELURL'     => '',
		'error_url'     => '',
		'CURRENCYCODE'  => 'USD',
		'payerid'       => ''
	);

	private $paypal_url = '';

	/**
	 * Sets the config for the class.
	 *
	 * @param  array  config passed from the library
	 */
	public function __construct($config)
	{
		$this->paypal_values['API_UserName']  = $config['API_UserName'];
		$this->paypal_values['API_Password']  = $config['API_Password'];
		$this->paypal_values['API_Signature'] = $config['API_Signature'];
		$this->paypal_values['ReturnUrl']     = $config['ReturnUrl'];
		$this->paypal_values['CANCELURL']     = $config['CANCELURL'];
		$this->paypal_values['CURRENCYCODE']  = $config['CURRENCYCODE'];
		$this->paypal_values['API_Endpoint']  = ($config['test_mode']) ? 'https://api.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';

		$this->paypal_url = ($config['test_mode'])
		                  ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='
		                  : 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';

		$this->required_fields['API_UserName']  = !empty($config['API_UserName']);
		$this->required_fields['API_Password']  = !empty($config['API_Password']);
		$this->required_fields['API_Signature'] = !empty($config['API_Signature']);
		$this->required_fields['ReturnUrl']     = !empty($config['ReturnUrl']);
		$this->required_fields['CANCELURL']     = !empty($config['CANCELURL']);
		$this->required_fields['CURRENCYCODE']  = !empty($config['CURRENCYCODE']);

		$this->curl_config = $config['curl_config'];

		$this->session = new Session();
		$this->input   = new Input();

		Log::add('debug', 'PayPal Payment Driver Initialized');
	}

	public function set_fields($fields)
	{
		foreach ((array) $fields as $key => $value)
		{
			// Do variable translation
			switch($key)
			{
				case 'amount':
					$key = 'Amt';
				break;
				default:
				break;
			}

			$this->paypal_values[$key] = $value;

			if (array_key_exists($key, $this->required_fields) AND !empty($value))
			{
				$this->required_fields[$key] = TRUE;
			}
		}
	}

	public function process()
	{
		// Make sure the payer ID is set. We do it here because it's not required the first time around.
		if ($this->session->get('paypal_token') AND isset($this->paypal_values['payerid']))
		{
			$this->required_fields['payerid'] = TRUE;
		}
		elseif ($this->session->get('paypal_token'))
		{
			$this->required_fields['payerid'] = FALSE;
		}

		// Check for required fields
		if (in_array(FALSE, $this->required_fields))
		{
			$fields = array();
			foreach ($this->required_fields as $key => $field)
			{
				if ( ! $field)
				{
					$fields[] = $key;
				}
			}

			throw new Kohana_Exception('payment.required', implode(', ', $fields));
		}

		if ( ! $this->session->get('paypal_token'))
		{
			$this->paypal_login();
			return FALSE;
		}

		// Post data for submitting to server
		$data = '&TOKEN='.$this->session->get('paypal_token').
		        '&PAYERID='.$this->paypal_values['payerid'].
		        '&IPADDRESS='.urlencode($_SERVER['SERVER_NAME']).
		        '&Amt='.$this->paypal_values['Amt'].
		        '&PAYMENTACTION='.$this->paypal_values['PAYMENTACTION'].
		        '&ReturnUrl='.$this->paypal_values['ReturnUrl'].
		        '&CANCELURL='.$this->paypal_values['CANCELURL'] .
		        '&CURRENCYCODE='.$this->paypal_values['CURRENCYCODE'].'&COUNTRYCODE=US';

		$response    = $this->contact_paypal('DoExpressCheckoutPayment', $data);
		$nvpResArray = $this->deformatNVP($response);

		return ($nvpResArray['ACK'] == TRUE);
	}

	/**
	 * Runs paypal authentication.
	 */
	protected function paypal_login()
	{
		$data = '&Amt='.$this->paypal_values['Amt'].
		        '&PAYMENTACTION='.$this->paypal_values['PAYMENTACTION'].
		        '&ReturnURL='.$this->paypal_values['ReturnUrl'].
		        '&CancelURL='.$this->paypal_values['CANCELURL'];

		$reply = $this->contact_paypal('SetExpressCheckout', $data);
		$this->session->set(array('reshash' => $reply));

		$reply = $this->deformatNVP($reply);
		$ack   = strtoupper($reply['ACK']);

		if ($ack == 'SUCCESS')
		{
			$paypal_token = urldecode($reply['TOKEN']);

			// Redirect to paypal.com here
			$this->session->set(array('paypal_token' => $paypal_token));

			// We are off to paypal to login!
			url::redirect($this->paypal_url.$paypal_token);
		}
		else // Something went terribly wrong...
		{
			Log::add('error', Kohana::debug($reply));
			url::redirect($this->paypal_values['error_url']);
		}
	}

	/**
	 * Runs the CURL methods to communicate with paypal.
	 *
	 * @param   string  paypal API call to run
	 * @param   string  any additional query string data to send to paypal
	 * @return  mixed
	 */
	protected function contact_paypal($method, $data)
	{
		$final_data = 'METHOD='.urlencode($method).
		              '&VERSION='.urlencode($this->paypal_values['version']).
		              '&PWD='.urlencode($this->paypal_values['API_Password']).
		              '&USER='.urlencode($this->paypal_values['API_UserName']).
		              'SIGNATURE='.urlencode($this->paypal_values['API_Signature']).$data;

		Log::add('debug', 'Connecting to '.$this->paypal_values['API_Endpoint']);
		$ch = curl_init($this->paypal_values['API_Endpoint']);

		// Set custom curl options
		curl_setopt_array($ch, $this->curl_config);
		curl_setopt($ch, CURLOPT_POST, 1);

		// Setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $final_data);

		// Getting response from server
		$response = curl_exec($ch);

		if (curl_errno($ch))
		{
			// Moving to error page to display curl errors
			$this->session->set_flash(array('curl_error_no' => curl_errno($ch), 'curl_error_msg' => curl_error($ch)));
			url::redirect($this->error_url);
		}
		else
		{
			curl_close($ch);
		}

		return $response;
	}

	/**
	 * This is from paypal. It decodes their return string and converts it into an array.
	 * We can probably rewrite this better, but it works, so its going in for now.
	 *
	 * @param   string  query string
	 * @return  array
	 */
	protected function deformatNVP($nvpstr)
	{
		$intial   = 0;
		$nvpArray = array();

		while (strlen($nvpstr))
		{
			// Postion of Key
			$keypos = strpos($nvpstr, '=');

			// Position of value
			$valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);

			// Getting the Key and Value values and storing in a Associative Array
			$keyval = substr($nvpstr, $intial, $keypos);
			$valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);

			// Decoding the respose
			$nvpArray[urldecode($keyval)] = urldecode( $valval);

			$nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
		}

		return $nvpArray;
	}
} // End Payment_Paypal_Driver Class