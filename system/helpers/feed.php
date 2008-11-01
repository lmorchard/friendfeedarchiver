<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Feed helper class.
 *
 * $Id: feed.php 1776 2008-01-21 17:19:13Z Shadowhand $
 *
 * @package    Feed Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class feed_Core {

	/**
	 * Parses a remote feed into an array.
	 *
	 * @param   string   remote feed URL
	 * @param   integer  item limit to fetch
	 * @return  array
	 */
	public static function parse($feed, $limit = 0)
	{
		// Make limit an integer
		$limit = (int) $limit;

		// Disable error reporting while opening the feed
		$ER = error_reporting(0);

		// Allow loading by filename or raw XML string.
		$feed = (is_file($feed) OR valid::url($feed)) ? simplexml_load_file($feed) : simplexml_load_string($feed);

		// Restore error reporting
		error_reporting($ER);

		// Feed could not be loaded
		if ($feed === FALSE)
			return array();

		// Detect the feed type. RSS 1.0/2.0 and Atom 1.0 are supported.
		$feed = isset($feed->channel) ? $feed->xpath('//item') : $feed->entry;

		$i = 0;
		$items = array();

		foreach ($feed as $item)
		{
			if ($limit > 0 AND $i++ === $limit)
				break;

			$items[] = (array) $item;
		}

		return $items;
	}

} // End rss