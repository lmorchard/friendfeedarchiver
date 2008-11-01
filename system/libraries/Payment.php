<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Provides payment support for credit cards and other providers like PayPal.
 *
 * $Id: Payment.php 1938 2008-02-05 23:47:53Z zombor $
 *
 * @package    Payment
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Payment_Core {

	// Configuration
	protected $config = array
	(
		// The driver string
		'driver'      => NULL,
		// Test mode is set to true by default
		'test_mode'   => TRUE,
	);

	protected $driver = NULL;

	/**
	 * Sets the payment processing fields.
	 * The driver will translate these into the specific format for the provider.
	 * Standard fields are (Providers may have additional or different fields):
	 *
	 * card_num
	 * exp_date
	 * cvv
	 * description
	 * amount
	 * tax
	 * shipping
	 * first_name
	 * last_name
	 * company
	 * address
	 * city
	 * state
	 * zip
	 * email
	 * phone
	 * fax
	 * ship_to_first_name
	 * ship_to_last_name
	 * ship_to_company
	 * ship_to_address
	 * ship_to_city
	 * ship_to_state
	 * ship_to_zip
	 *
	 * @param  array  the driver string
	 */
	public function __construct($config = array())
	{
		if (empty($config))
		{
			// Load the default group
			$config = Config::item('payment.default');
		}
		elseif (is_string($config))
		{
			$this->config['driver'] = $config;
		}

		// Merge the default config with the passed config
		$this->config = array_merge($this->config, $config);

		// Woah! We can't continue like this!
		if ($this->config['driver'] == NULL)
			throw new Kohana_Exception();

		// Get the driver specific settings
		$this->config = array_merge($this->config, Config::item('payment.'.$this->config['driver']));

		// Set driver name
		$driver = 'Payment_'.ucfirst($this->config['driver']).'_Driver';

		// Load the driver
		if ( ! Kohana::auto_load($driver))
			throw new Kohana_Exception('payment.driver_not_supported', $this->config['driver']);

		// Initialize the driver
		$this->driver = new $driver($this->config);

		if ( ! ($this->driver instanceof Payment_Driver))
			throw new Kohana_Exception('payment.driver_not_supported', 'Payment drivers must use the Payment_Driver interface.');
	}

	/**
	 * Sets the credit card processing fields
	 *
	 * @param  string  field name
	 * @param  string  value
	 */
	public function __set($name, $val)
	{
		$this->driver->set_fields(array($name => $val));
	}

	/**
	 * Bulk setting of payment processing fields.
	 *
	 * @param   array   array of values to set
	 * @return  object  this object
	 */
	public function set_fields($fields)
	{
		$this->driver->set_fields((array) $fields);

		return $this;
	}

	/**
	 * Runs the transaction
	 *
	 * @return  TRUE|string  TRUE on successful payment, an error string on failure
	 */
	public function process()
	{
		return $this->driver->process();
	}
}