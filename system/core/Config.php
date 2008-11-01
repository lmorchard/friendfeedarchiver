<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Loads configuration files and retrieves keys. This class is declared as final.
 * 
 * $Id: Config.php 2108 2008-02-22 01:10:55Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Config {

	// Entire configuration
	private static $conf;

	// Include paths
	private static $include_paths;

	/**
	 * Get a config item or group.
	 *
	 * @param   string                item name
	 * @param   boolean               force a forward slash (/) at the end of the item
	 * @param   boolean               is the item required?
	 * @return  string|array|boolean
	 */
	public static function item($key, $slash = FALSE, $required = TRUE)
	{
		// Configuration autoloading
		if (self::$conf === NULL)
		{
			// Load the application configuration file
			require APPPATH.'config/config'.EXT;

			// Invalid config file
			(isset($config) AND is_array($config)) or die
			(
				'Your Kohana application configuration file is not valid.'
			);

			// Load config into self
			self::$conf['core'] = $config;

			// Re-parse the include paths
			self::include_paths(TRUE);
		}

		// Requested group
		$group = current(explode('.', $key));

		// Load the group if not already loaded
		if ( ! isset(self::$conf[$group]))
		{
			self::$conf[$group] = self::load($group, $required);
		}

		// Get the value of the key string
		$value = Kohana::key_string($key, self::$conf);

		return
		// If the value is not an array, and the value should end with /
		( ! is_array($value) AND $slash == TRUE AND $value != '')
		// Trim the string and force a slash on the end
		? rtrim((string) $value, '/').'/'
		// Otherwise, just return the value
		: $value;
	}

	/**
	 * Sets a configuration item, if allowed.
	 *
	 * @param   string   config key string
	 * @param   string   config value
	 * @return  boolean
	 */
	public static function set($key, $value)
	{
		// Config setting must be enabled
		if (Config::item('core.allow_config_set') == FALSE)
		{
			Log::add('debug', 'Config::set was called, but your configuration file does not allow setting.');
			return FALSE;
		}

		// Empty keys and core.allow_set cannot be set
		if (empty($key) OR $key == 'core.allow_config_set')
			return FALSE;

		// Do this to make sure that the config array is already loaded
		Config::item($key);

		// Clean key
		$key = rtrim($key, '.');

		if (substr($key, 0, 7) === 'routes.')
		{
			// Routes cannot contain sub keys due to possible dots in regex
			$keys = explode('.', $key, 2);
		}
		else
		{
			// Convert dot-noted key string to an array
			$keys = explode('.', $key);
		}

		// Used for recursion
		$conf =& self::$conf;
		$last = count($keys) - 1;

		foreach($keys as $i => $k)
		{
			if ($i === $last)
			{
				$conf[$k] = $value;
			}
			else
			{
				$conf =& $conf[$k];
			}
		}

		if ($key == 'core.modules')
		{
			// Reprocess the include paths
			self::include_paths(TRUE);
		}

		return TRUE;
	}

	/**
	 * Get all include paths.
	 *
	 * @param   boolean  re-process the include paths
	 * @return  array    include paths, APPPATH first
	 */
	public static function include_paths($process = FALSE)
	{
		if ($process == TRUE)
		{
			// Start setting include paths, APPPATH first
			self::$include_paths = array(APPPATH);

			// Normalize all paths to be absolute and have a trailing slash
			foreach(self::item('core.modules') as $path)
			{
				if (($path = str_replace('\\', '/', realpath($path))) == '')
					continue;

				self::$include_paths[] = $path.'/';
			}

			// Finish setting include paths by adding SYSPATH
			self::$include_paths[] = SYSPATH;
		}

		return self::$include_paths;
	}

	/**
	 * Load a config file.
	 *
	 * @param   string   config filename, without extension
	 * @param   boolean  is the file required?
	 * @return  array    config items in file
	 */
	public static function load($name, $required = TRUE)
	{
		$configuration = array();

		// Find all the configuartion files matching the name
		foreach(Kohana::find_file('config', $name, $required) as $filename)
		{
			// Import the config
			include $filename;

			if (isset($config) AND is_array($config))
			{
				// Merge in configuration
				$configuration = array_merge($configuration, $config);
			}
		}

		return $configuration;
	}

} // End Config