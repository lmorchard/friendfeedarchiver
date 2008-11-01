<?php defined('SYSPATH') or die('No direct script access.');
/**
 * URI library.
 *
 * $Id: URI.php 1911 2008-02-04 16:13:16Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class URI_Core extends Router {

	/**
	 * Returns a singleton instance of URI.
	 *
	 * @return  object
	 */
	public static function instance()
	{
		static $instance;

		// Initialize the URI instance
		empty($instance) and $instance = new URI;

		return $instance;
	}

	/**
	 * Retrieve a specific URI segment.
	 *
	 * @param   integer|string  segment number or label
	 * @param   mixed           default value returned if segment does not exist
	 * @return  string
	 */
	public function segment($index = 1, $default = FALSE)
	{
		if (is_string($index))
		{
			if (($key = array_search($index, self::$segments)) === FALSE)
				return $default;

			$index = $key + 2;
		}

		$index = (int) $index - 1;

		return isset(self::$segments[$index]) ? self::$segments[$index] : $default;
	}

	/**
	 * Retrieve a specific routed URI segment.
	 *
	 * @param   integer|string  rsegment number or label
	 * @param   mixed           default value returned if segment does not exist
	 * @return  string
	 */
	public function rsegment($index = 1, $default = FALSE)
	{
		if (is_string($index))
		{
			if (($key = array_search($index, self::$rsegments)) === FALSE)
				return $default;

			$index = $key + 2;
		}

		$index = (int) $index - 1;

		return isset(self::$rsegments[$index]) ? self::$rsegments[$index] : $default;
	}

	/**
	 * Retrieve a specific URI argument.
	 * This is the part of the segments that does not indicate controller or method
	 *
	 * @param   integer|string  argument number or label
	 * @param   mixed           default value returned if segment does not exist
	 * @return  string
	 */
	public function argument($index = 1, $default = FALSE)
	{
		if (is_string($index))
		{
			if (($key = array_search($index, self::$arguments)) === FALSE)
				return $default;

			$index = $key + 2;
		}

		$index = (int) $index - 1;

		return isset(self::$arguments[$index]) ? self::$arguments[$index] : $default;
	}

	/**
	 * Returns an array containing all the URI segments.
	 *
	 * @param   integer  segment offset
	 * @param   boolean  return an associative array
	 * @return  array
	 */
	public function segment_array($offset = 0, $associative = FALSE)
	{
		$segment_array = self::$segments;
		array_unshift($segment_array, 0);
		$segment_array = array_slice($segment_array, $offset + 1, $this->total_segments(), TRUE);

		if ( ! $associative)
			return $segment_array;

		$segment_array_assoc = array();

		foreach (array_chunk($segment_array, 2) as $pair)
		{
			$segment_array_assoc[$pair[0]] = isset($pair[1]) ? $pair[1] : '';
		}

		return $segment_array_assoc;
	}

	/**
	 * Returns an array containing all the re-routed URI segments.
	 *
	 * @param   integer  rsegment offset
	 * @param   boolean  return an associative array
	 * @return  array
	 */
	public function rsegment_array($offset = 0, $associative = FALSE)
	{
		$segment_array = self::$rsegments;
		array_unshift($segment_array, 0);
		$segment_array = array_slice($segment_array, $offset + 1, $this->total_segments(), TRUE);

		if ( ! $associative)
			return $segment_array;

		$segment_array_assoc = array();

		foreach (array_chunk($segment_array, 2) as $pair)
		{
			$segment_array_assoc[$pair[0]] = isset($pair[1]) ? $pair[1] : '';
		}

		return $segment_array_assoc;
	}

	/**
	 * Returns an array containing all the URI arguments.
	 *
	 * @param   integer  segment offset
	 * @param   boolean  return an associative array
	 * @return  array
	 */
	public function argument_array($offset = 0, $associative = FALSE)
	{
		$argument_array = self::$arguments;
		array_unshift($argument_array, 0);
		$argument_array = array_slice($argument_array, $offset + 1, $this->total_arguments(), TRUE);

		if ( ! $associative)
			return $argument_array;

		$argument_array_assoc = array();

		foreach (array_chunk($argument_array, 2) as $pair)
		{
			$argument_array_assoc[$pair[0]] = isset($pair[1]) ? $pair[1] : '';
		}

		return $argument_array_assoc;
	}

	/**
	 * Returns the complete URI as a string.
	 *
	 * @return  string
	 */
	public function string()
	{
		return self::$current_uri;
	}

	/**
	 * Magic method for converting an object to a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->string();
	}

	/**
	 * Returns the total number of URI segments.
	 *
	 * @return  integer
	 */
	public function total_segments()
	{
		return count(self::$segments);
	}

	/**
	 * Returns the total number of re-routed URI segments.
	 *
	 * @return  integer
	 */
	public function total_rsegments()
	{
		return count(self::$rsegments);
	}

	/**
	 * Returns the total number of URI arguments.
	 *
	 * @return  integer
	 */
	public function total_arguments()
	{
		return count(self::$arguments);
	}

	/**
	 * Returns the last URI segment.
	 *
	 * @param   mixed   default value returned if segment does not exist
	 * @return  string
	 */
	public function last_segment($default = FALSE)
	{
		if ($this->total_segments() < 1)
			return $default;

		return end(self::$segments);
	}

	/**
	 * Returns the last re-routed URI segment.
	 *
	 * @param   mixed   default value returned if segment does not exist
	 * @return  string
	 */
	public function last_rsegment($default = FALSE)
	{
		if ($this->total_rsegments() < 1)
			return $default;

		return end(self::$rsegments);
	}

} // End URI Class