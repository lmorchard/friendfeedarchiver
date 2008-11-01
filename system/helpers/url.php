<?php defined('SYSPATH') or die('No direct script access.');
/**
 * URL helper class.
 *
 * $Id: url.php 2020 2008-02-10 10:41:19Z Geert $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class url_Core {

	/**
	 * Base URL, with or without the index page.
	 *
	 * @param   boolean  include the index page
	 * @param   boolean  non-default protocol
	 * @return  string
	 */
	public static function base($index = FALSE, $protocol = FALSE)
	{
		$protocol = ($protocol == FALSE) ? Config::item('core.site_protocol') : strtolower($protocol);

		$base_url = $protocol.'://'.Config::item('core.site_domain', TRUE);

		if ($index == TRUE AND $index = Config::item('core.index_page'))
		{
			$base_url = $base_url.$index.'/';
		}

		return $base_url;
	}

	/**
	 * Fetches a site URL based on a URI segment.
	 *
	 * @param   string  site URI to convert
	 * @param   string  non-default protocol
	 * @return  string
	 */
	public static function site($uri = '', $protocol = FALSE)
	{
		$uri = trim($uri, '/');

		$qs = ''; // anchor?query=string
		$id = ''; // anchor#id

		if (strpos($uri, '?') !== FALSE)
		{
			list ($uri, $qs) = explode('?', $uri, 2);
			$qs = '?'.$qs;
		}

		if (strpos($uri, '#') !== FALSE)
		{
			list ($uri, $id) = explode('#', $uri, 2);
			$id = '#'.$id;
		}

		$index_page = Config::item('core.index_page', TRUE);
		$url_suffix = ($uri != '') ? Config::item('core.url_suffix') : '';

		return url::base(FALSE, $protocol).$index_page.$uri.$url_suffix.$qs.$id;
	}

	/**
	 * Fetches the current URI.
	 *
	 * @param   boolean  include the query string
	 * @return  string
	 */
	public static function current($qs = FALSE)
	{
		return Router::$current_uri.($qs === TRUE ? Router::$query_string : '');
	}

	/**
	 * Convert a phrase to a URL-safe title.
	 *
	 * @param   string  phrase to convert
	 * @param   string  word separator (- or _)
	 * @return  string
	 */
	public static function title($title, $separator = '-')
	{
		$separator = ($separator === '-') ? '-' : '_';

		// Replace accented characters by their unaccented equivalents
		$title = utf8::transliterate_to_ascii($title);

		// Remove all characters that are not the separator, a-z, 0-9, or whitespace
		$title = preg_replace('/[^'.$separator.'a-z0-9\s]+/', '', strtolower($title));

		// Replace all separator characters and whitespace by a single separator
		$title = preg_replace('/['.$separator.'\s]+/', $separator, $title);

		// Trim separators from the beginning and end
		return trim($title, $separator);
	}

	/**
	 * Sends a page redirect header.
	 *
	 * @param  string  site URI or URL to redirect to
	 * @param  string  HTTP method of redirect
	 * @return A HTML anchor, but sends HTTP headers. The anchor should never be seen
	 *         by the user, unless their browser does not understand the headers sent.
	 */
	public static function redirect($uri = '', $method = '302')
	{
		if (Event::has_run('system.send_headers'))
			return;

		if (strpos($uri, '://') === FALSE)
		{
			$uri = url::site($uri);
		}

		if ($method == 'refresh')
		{
			header('Refresh: 0; url='.$uri);
		}
		else
		{
			$codes = array
			(
				'300' => 'Multiple Choices',
				'301' => 'Moved Permanently',
				'302' => 'Found',
				'303' => 'See Other',
				'304' => 'Not Modified',
				'305' => 'Use Proxy',
				'307' => 'Temporary Redirect'
			);

			$method = isset($codes[$method]) ? $method : '302';

			header('HTTP/1.1 '.$method.' '.$codes[$method]);
			header('Location: '.$uri);
		}

		// Last resort, exit and display the URL
		exit('<a href="'.$uri.'">'.$uri.'</a>');
	}

} // End url