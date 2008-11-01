<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Number helper class.
 *
 * $Id: num.php 2100 2008-02-22 00:30:59Z Shadowhand $
 *
 * @package    Number Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class num_Core {

	/**
	 * Round a number to the nearest nth
	 *
	 * @param   integer  number to round
	 * @param   integer  number to round to
	 * @return  integer
	 */
	public static function round($number, $nearest = 5)
	{
		return round($number / $nearest) * $nearest;
	}

}