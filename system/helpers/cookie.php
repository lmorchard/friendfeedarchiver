<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cookie helper class.
 *
 * $Id: cookie.php 1970 2008-02-06 21:54:29Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class cookie_Core {

	/**
	 * Sets a cookie with the given parameters.
	 *
	 * @param   string   cookie name or array of config options
	 * @param   string   cookie value
	 * @param   integer  number of seconds before the cookie expires
	 * @param   string   URL path to allow
	 * @param   string   URL domain to allow
	 * @param   boolean  HTTPS only
	 * @param   boolean  HTTP only (requires PHP 5.2 or higher)
	 * @param   string   collision-prevention prefix
	 * @return  boolean
	 */
	public static function set($name, $value = NULL, $expire = NULL, $path = NULL, $domain = NULL, $secure = NULL, $httponly = NULL, $prefix = NULL)
	{
		if (headers_sent())
			return FALSE;

		// If the name param is an array, we import it
		is_array($name) and extract($name, EXTR_OVERWRITE);

		// Fetch default options
		$config = Config::item('cookie');

		foreach (array('value', 'expire', 'domain', 'path', 'prefix', 'secure', 'httponly') as $item)
		{
			if ($$item === NULL AND isset($config[$item]))
			{
				$$item = $config[$item];
			}
		}

		// Expiration timestamp
		$expire = ($expire == 0) ? 0 : time() + (int) $expire;

		// Only set httponly if possible
		return (version_compare(PHP_VERSION, '5.2', '>='))
			? setcookie($prefix.$name, $value, $expire, $path, $domain, $secure, $httponly)
			: setcookie($prefix.$name, $value, $expire, $path, $domain, $secure);
	}

	/**
	 * Fetch a cookie value, using the Input library.
	 *
	 * @param   string   cookie name
	 * @param   string   collision-prevention prefix
	 * @param   boolean  use XSS cleaning on the value
	 * @return  string
	 */
	public static function get($name, $prefix = NULL, $xss_clean = FALSE)
	{
		if ($prefix === NULL)
		{
			$prefix = (string) Config::item('cookie.prefix');
		}

		return Input::instance()->cookie($prefix.$name, $xss_clean);
	}

	/**
	 * Nullify and unset a cookie.
	 *
	 * @param   string   cookie name
	 * @param   string   URL path
	 * @param   string   URL domain
	 * @param   string   collision-prevention prefix
	 * @return  boolean
	 */
	public static function delete($name, $path = NULL, $domain = NULL, $prefix = NULL)
	{
		// Sets the cookie value to an empty string, and the expiration to 24 hours ago
		return cookie::set($name, '', -86400, $path, $domain, FALSE, FALSE, $prefix);
	}

} // End cookie