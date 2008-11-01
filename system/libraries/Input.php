<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Input library.
 *
 * $Id: Input.php 1911 2008-02-04 16:13:16Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Input_Core {

	// Singleton instance
	protected static $instance;

	protected $use_xss_clean = FALSE;

	public $ip_address = FALSE;
	public $user_agent = FALSE;

	/**
	 * Retrieve a singleton instance of Input. This will always be the first
	 * created instance of this class.
	 *
	 * @return  object
	 */
	public static function instance()
	{
		// Create an instance if none exists
		empty(self::$instance) and new Input;

		return self::$instance;
	}

	/**
	 * Sets whether to globally enable the XSS processing.
	 */
	public function __construct()
	{
		// Use XSS clean?
		$this->use_xss_clean = (bool) Config::item('core.global_xss_filtering');

		if (self::$instance === NULL)
		{
			$this->user_agent = Kohana::$user_agent;

			if (ini_get('register_globals'))
			{
				// Prevent GLOBALS override attacks
				isset($_REQUEST['GLOBALS']) and exit('Global variable overload attack.');

				// Destroy the REQUEST global
				$_REQUEST = array();

				// These globals are standard and should not be removed
				$preserve = array('GLOBALS', '_REQUEST', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER', '_ENV', '_SESSION');

				// This loop has the same effect as disabling register_globals
				foreach ($GLOBALS as $key => $val)
				{
					if ( ! in_array($key, $preserve))
					{
						// NULL-ify the global variable
						global $$key;
						$$key = NULL;
						// Unset the global variable
						unset($GLOBALS[$key]);
						unset($$key);
					}
				}

				// Warn the developer about register globals
				Log::add('debug', 'Register globals is enabled. To save resources, disable register_globals in php.ini');
			}

			if (is_array($_GET) AND count($_GET) > 0)
			{
				foreach ($_GET as $key => $val)
				{
					// Sanitize $_GET
					$_GET[$this->clean_input_keys($key)] = $this->clean_input_data($val);
				}
			}
			else
			{
				$_GET = array();
			}

			if (is_array($_POST) AND count($_POST) > 0)
			{
				foreach ($_POST as $key => $val)
				{
					// Sanitize $_POST
					$_POST[$this->clean_input_keys($key)] = $this->clean_input_data($val);
				}
			}
			else
			{
				$_POST = array();
			}

			if (is_array($_COOKIE) AND count($_COOKIE) > 0)
			{
				foreach ($_COOKIE as $key => $val)
				{
					// Sanitize $_COOKIE
					$_COOKIE[$this->clean_input_keys($key)] = $this->clean_input_data($val);
				}
			}
			else
			{
				$_COOKIE = array();
			}

			// Create a singleton
			self::$instance = $this;

			Log::add('debug', 'Global GET, POST and COOKIE data sanitized');
		}

		Log::add('debug', 'Input Library initialized');
	}

	/**
	 * Fetch an item from a global array.
	 *
	 * @param   string  array to access (get, post, cookie or server)
	 * @param   array   arguments (array key, xss_clean)
	 * @return  mixed
	 */
	public function __call($global, $args = array())
	{
		// Array to be searched, assigned by reference
		$array = array();
		switch (strtolower($global))
		{
			case 'get':    $array =& $_GET;    break;
			case 'post':   $array =& $_POST;   break;
			case 'cookie': $array =& $_COOKIE; break;
			case 'server': $array =& $_SERVER; break;
			default:
				throw new Kohana_Exception('core.invalid_method', $global, get_class($this));
		}

		if (count($args) === 0)
			return $array;

		// If the last argument is a boolean, it's the XSS clean flag
		$xss_clean = (is_bool(end($args))) ? array_pop($args) : FALSE;

		// Reset the array pointer
		reset($args);

		// Multiple inputs require us to return an array
		$return_array = (count($args) > 1);

		// Compose the data to return
		$data = array();
		while ($key = array_shift($args))
		{
			if (isset($array[$key]))
			{
				// XSS clean if the data has not already been cleaned
				$data[$key] = ($this->use_xss_clean == FALSE AND $xss_clean == TRUE) ? $this->xss_clean($array[$key]) : $array[$key];
			}
			else
			{
				$data[$key] = NULL;
			}
		}

		// Return the global value
		return ($return_array) ? $data : current($data);
	}

	/**
	 * This is a helper function. It escapes data and standardizes newline characters to '\n'.
	 *
	 * @param   unknown_type  string to clean
	 * @return  string
	 */
	protected function clean_input_data($str)
	{
		if (is_array($str))
		{
			$new_array = array();
			foreach ($str as $key => $val)
			{
				$new_array[$this->clean_input_keys($key)] = $this->clean_input_data($val);
			}
			return $new_array;
		}

		if (get_magic_quotes_gpc())
		{
			$str = stripslashes($str);
		}

		if ($this->use_xss_clean === TRUE)
		{
			$str = $this->xss_clean($str);
		}

		// Standardize newlines
		return str_replace(array("\r\n", "\r"), "\n", $str);
	}

	/**
	 * This is a helper function. To prevent malicious users
	 * from trying to exploit keys we make sure that keys are
	 * only named with alpha-numeric text and a few other items.
	 *
	 * @param   string  string to clean
	 * @return  string
	 */
	protected function clean_input_keys($str)
	{
		$chars = (PCRE_UNICODE_PROPERTIES) ? '\pL' : 'a-zA-Z';

		if ( ! preg_match('#^['.$chars.'0-9:_/-]+$#uD', $str))
		{
			exit('Disallowed key characters in global data.');
		}

		return $str;
	}

	/**
	 * Fetch the IP Address.
	 *
	 * @return string
	 */
	public function ip_address()
	{
		if ($this->ip_address !== FALSE)
			return $this->ip_address;

		if ($this->server('REMOTE_ADDR') AND $this->server('HTTP_CLIENT_IP'))
		{
			 $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif ($this->server('REMOTE_ADDR'))
		{
			 $this->ip_address = $_SERVER['REMOTE_ADDR'];
		}
		elseif ($this->server('HTTP_CLIENT_IP'))
		{
			 $this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif ($this->server('HTTP_X_FORWARDED_FOR'))
		{
			 $this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		if ($this->ip_address === FALSE)
		{
			$this->ip_address = '0.0.0.0';
			return $this->ip_address;
		}

		if (strstr($this->ip_address, ','))
		{
			$x = explode(',', $this->ip_address);
			$this->ip_address = end($x);
		}

		if ( ! $this->valid_ip($this->ip_address))
		{
			$this->ip_address = '0.0.0.0';
		}

		return $this->ip_address;
	}

	/**
	 * Validates an IPv4 address based on RFC specifications.
	 *
	 * @param   string  IP to validate
	 * @return  boolean
	 */
	public function valid_ip($ip)
	{
		return valid::ip($ip);
	}

	/**
	 * Get the user agent of the current request.
	 *
	 * @return string
	 */
	public function user_agent()
	{
		return $this->user_agent;
	}

	/**
	 * Clean cross site scripting exploits from string.
	 * HTMLPurifier may be used if installed, otherwise defaults to built in method.
	 * Note - This function should only be used to deal with data upon submission.
	 * It's not something that should be used for general runtime processing
	 * since it requires a fair amount of processing overhead.
	 *
	 * @param   string  data to clean
	 * @param   string  xss_clean method to use ('htmlpurifier' or defaults to built in method)
	 * @return  string
	 */
	public function xss_clean($data, $tool = NULL)
	{
		if (is_array($data))
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $this->xss_clean($val, $tool);
			}
			return $data;
		}

		// It is a string
		$string = $data;

		// Do not clean empty strings
		if (trim($string) == '')
			return $string;

		if ( ! is_string($tool))
		{
			// Fetch the configured tool
			if (is_bool($tool = Config::item('core.global_xss_filtering')))
			{
				// Make sure that the default tool is used
				$tool = 'default';
			}
		}

		switch ($tool)
		{
			case 'htmlpurifier':
				/**
				 * @todo License should go here, http://htmlpurifier.org/
				 */
				require_once Kohana::find_file('vendor', 'htmlpurifier/HTMLPurifier.auto');
				require_once 'HTMLPurifier.func.php';

				// Set configuration
				$config = HTMLPurifier_Config::createDefault();
				$config->set('HTML', 'TidyLevel', 'none'); // Only XSS cleaning now

				// Run HTMLPurifier
				$string = HTMLPurifier($string, $config);
			break;
			default:
				// http://svn.bitflux.ch/repos/public/popoon/trunk/classes/externalinput.php
				// +----------------------------------------------------------------------+
				// | Copyright (c) 2001-2006 Bitflux GmbH                                 |
				// +----------------------------------------------------------------------+
				// | Licensed under the Apache License, Version 2.0 (the "License");      |
				// | you may not use this file except in compliance with the License.     |
				// | You may obtain a copy of the License at                              |
				// | http://www.apache.org/licenses/LICENSE-2.0                           |
				// | Unless required by applicable law or agreed to in writing, software  |
				// | distributed under the License is distributed on an "AS IS" BASIS,    |
				// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
				// | implied. See the License for the specific language governing         |
				// | permissions and limitations under the License.                       |
				// +----------------------------------------------------------------------+
				// | Author: Christian Stocker <chregu@bitflux.ch>                        |
				// +----------------------------------------------------------------------+
				//
				// Kohana Modifications:
				// * Changed double quotes to single quotes, changed indenting and spacing
				// * Removed magic_quotes stuff
				// * Increased regex readability:
				//   * Used delimeters that aren't found in the pattern
				//   * Removed all unneeded escapes
				//   * Deleted U modifiers and swapped greediness where needed
				// * Increased regex speed:
				//   * Made capturing parentheses non-capturing where possible
				//   * Removed parentheses where possible
				//   * Split up alternation alternatives
				//

				$string = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $string);
				// fix &entitiy\n;

				$string = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $string);
				$string = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $string);
				$string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');

				// remove any attribute starting with "on" or xmlns
				$string = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*>#iu', '$1>', $string);
				// remove javascript: and vbscript: protocol
				$string = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $string);
				$string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $string);
				$string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $string);
				//<span style="width: expression(alert('Ping!'));"></span>
				// only works in ie...
				$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*>#i', '$1>', $string);
				$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*>#i', '$1>', $string);
				$string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*>#iu', '$1>', $string);
				//remove namespaced elements (we do not need them...)
				$string = preg_replace('#</*\w+:\w[^>]*>#i', '',$string);
				//remove really unwanted tags

				do {
					$oldstring = $string;
					$string = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*>#i', '', $string);
				} while ($oldstring != $string);
			break;
		}

		return $string;
	}

} // End Input Class
