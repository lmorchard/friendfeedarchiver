<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Inflector helper class.
 *
 * $Id: inflector.php 2513 2008-04-16 18:18:29Z JAAulde $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class inflector_Core {

	/**
	 * Checks if a word is defined as uncountable.
	 *
	 * @param   string   word to check
	 * @return  boolean
	 */
	public static function uncountable($str)
	{
		static $uncountables = NULL;

		if ($uncountables === NULL)
		{
			// Makes a mirrored array, eg: foo => foo
			$uncountables = array_flip(Kohana::lang('inflector'));
		}

		return isset($uncountables[$str]);
	}

	/**
	 * Makes a plural word singular.
	 *
	 * @param   string  word to singularize
	 * @return  string
	 */
	public static function singular($str, $count = NULL)
	{
		static $cache;

		$str = trim($str);

		// Cache key name
		$key = $str.$count;

		// We can just return uncountable words
		if (inflector::uncountable($str))
			return $str;

		if (is_string($count) AND ctype_digit($count))
		{
			// Convert to integer when using a digit string
			$count = (int) $count;
		}

		if ($cache === NULL)
		{
			// Initialize the cache
			$cache = array();
		}
		else
		{
			// Already pluralized
			if (isset($cache[$key]))
				return $cache[$key];
		}

		// Do nothing with a single count
		if (is_int($count) AND $count === 0 OR $count > 1)
			return $str;

		if (substr($str, -3) === 'ies')
		{
			$str = substr($str, 0, strlen($str) - 3).'y';
		}
		elseif (substr($str, -4) === 'sses' OR substr($str, -3) === 'xes')
		{
			$str = substr($str, 0, strlen($str) - 2);
		}
		elseif (substr($str, -1) === 's')
		{
			$str = substr($str, 0, strlen($str) - 1);
		}

		return $cache[$key] = $str;
	}

	/**
	 * Makes a singular word plural.
	 *
	 * @param   string  word to pluralize
	 * @return  string
	 */
	public static function plural($str, $count = NULL)
	{
		static $cache;

		$str = trim($str);

		// Cache key name
		$key = $str.$count;

		// We can just return uncountable words
		if (inflector::uncountable($str))
			return $str;

		if (is_string($count) AND ctype_digit($count))
		{
			// Convert to integer when using a digit string
			$count = (int) $count;
		}

		if ($cache === NULL)
		{
			// Initialize the cache
			$cache = array();
		}
		else
		{
			// Already pluralized
			if (isset($cache[$key]))
				return $cache[$key];
		}

		// If the count is one, do not pluralize
		if ($count === 1)
			return $str;

		$end = substr($str, -1);
		$low = (strcmp($end, strtolower($end)) === 0) ? TRUE : FALSE;

		if (preg_match('/[sxz]$/i', $str) OR preg_match('/[^aeioudgkprt]h$/i', $str))
		{
			$end = 'es';
			$str .= ($low == FALSE) ? strtoupper($end) : $end;
		}
		elseif (preg_match('/[^aeiou]y$/i', $str))
		{
			$end = 'ies';
			$end = ($low == FALSE) ? strtoupper($end) : $end;
			$str = substr_replace($str, $end, -1);
		}
		else
		{
			$end = 's';
			$str .= ($low == FALSE) ? strtoupper($end) : $end;
		}

		// Set the cache and return
		return $cache[$key] = $str;
	}

	/**
	 * Makes a phrase camel case.
	 *
	 * @param   string  phrase to camelize
	 * @return  string
	 */
	public static function camelize($str)
	{
		$str = 'x'.strtolower(trim($str));
		$str = ucwords(preg_replace('/[\s_]+/', ' ', $str));

		return substr(str_replace(' ', '', $str), 1);
	}

	/**
	 * Makes a phrase underscored instead of spaced.
	 *
	 * @param   string  phrase to underscore
	 * @return  string
	 */
	public static function underscore($str)
	{
		return preg_replace('/\s+/', '_', trim($str));
	}

	/**
	 * Makes an underscored or dashed phrase human-reable.
	 *
	 * @param   string  phrase to make human-reable
	 * @return  string
	 */
	public static function humanize($str)
	{
		return preg_replace('/[_-]+/', ' ', trim($str));
	}

} // End inflector