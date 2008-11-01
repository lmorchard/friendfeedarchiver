<?php defined('SYSPATH') or die('No direct script access.');
/**
 * HTML helper class.
 *
 * $Id: html.php 1971 2008-02-06 21:55:46Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class html_Core {

	/**
	 * Convert special characters to HTML entities
	 *
	 * @param   string   string to convert
	 * @param   boolean  encode existing entities
	 * @return  string
	 */
	public static function specialchars($str, $double_encode = TRUE)
	{
		// Do encode existing HTML entities (default)
		if ($double_encode == TRUE)
		{
			$str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
		}
		else
		{
			// Do not encode existing HTML entities
			// From PHP 5.2.3 this functionality is built-in, otherwise use a regex
			if (version_compare(PHP_VERSION, '5.2.3', '>='))
			{
				$str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8', FALSE);
			}
			else
			{
				$str = preg_replace('/&(?!(?:#\d+|[a-z]+);)/i', '&amp;', $str);
				$str = str_replace(array('<', '>', '\'', '"'), array('&lt;', '&gt;', '&#39;', '&quot;'), $str);
			}
		}

		return $str;
	}

	/**
	 * Creates an HTTP query string from an array. Deprecated.
	 *
	 * @param   array   array of data to convert to string
	 * @return  string
	 */
	public static function query_string($array)
	{
		return http_build_query($array);
	}

	/**
	 * Create HTML link anchors.
	 *
	 * @param   string  URL or URI string
	 * @param   string  link text
	 * @param   array   HTML anchor attributes
	 * @param   string  non-default protocol, eg: https
	 * @return  string
	 */
	public static function anchor($uri, $title = FALSE, $attributes = FALSE, $protocol = FALSE)
	{
		if ($uri === '')
		{
			$site_url = url::base(FALSE);
		}
		elseif (strpos($uri, '://') === FALSE)
		{
			$site_url = url::site($uri, $protocol);
		}
		else
		{
			$site_url = $uri;
		}

		return
		// Parsed URL
		'<a href="'.html::specialchars($site_url, FALSE).'"'
		// Attributes empty? Use an empty string
		.(empty($attributes) ? '' : html::attributes($attributes)).'>'
		// Title empty? Use the parsed URL
		.(empty($title) ? $site_url : $title).'</a>';
	}

	/**
	 * Creates an HTML anchor to a file.
	 *
	 * @param   string  name of file to link to
	 * @param   string  link text
	 * @param   array   HTML anchor attributes
	 * @param   string  non-default protocol, eg: ftp
	 * @return  string
	 */
	public static function file_anchor($file, $title = FALSE, $attributes = FALSE, $protocol = FALSE)
	{
		return
		// Base URL + URI = full URL
		'<a href="'.html::specialchars(url::base(FALSE, $protocol).$file, FALSE).'"'
		// Attributes empty? Use an empty string
		.(empty($attributes) ? '' : html::attributes($attributes)).'>'
		// Title empty? Use the filename part of the URI
		.(empty($title) ? end(explode('/', $file)) : $title) .'</a>';
	}

	/**
	 * Similar to anchor, but with the protocol parameter first.
	 *
	 * @param   string  link protocol
	 * @param   string  URI or URL to link to
	 * @param   string  link text
	 * @param   array   HTML anchor attributes
	 * @return  string
	 */
	public static function panchor($protocol, $uri, $title = FALSE, $attributes = FALSE)
	{
		return html::anchor($uri, $title, $attributes, $protocol);
	}

	/**
	 * Creates a email anchor.
	 *
	 * @param   string  email address to send to
	 * @param   string  link text
	 * @param   array   HTML anchor attributes
	 * @return  string
	 */
	public static function mailto($email, $title = FALSE, $attributes = FALSE)
	{
		// Remove the subject or other parameters that do not need to be encoded
		$subject = FALSE;
		if (strpos($email, '?') !== FALSE)
		{
			list ($email, $subject) = explode('?', $email);
		}

		$safe = '';
		foreach(str_split($email) as $i => $letter)
		{
			switch (($letter == '@') ? rand(1, 2) : rand(1, 3))
			{
				// HTML entity code
				case 1: $safe .= '&#'.ord($letter).';'; break;
				// Hex character code
				case 2: $safe .= '&#x'.dechex(ord($letter)).';'; break;
				// Raw (no) encoding
				case 3: $safe .= $letter;
			}
		}

		// Title defaults to the encoded email address
		$title = ($title == FALSE) ? $safe : $title;

		// URL encode the subject line
		$subject = ($subject == TRUE) ? '?'.rawurlencode($subject) : '';

		// Parse attributes
		$attributes = ($attributes == TRUE) ? html::attributes($attributes) : '';

		// Encoded start of the href="" is a static encoded version of 'mailto:'
		return '<a href="&#109;&#097;&#105;&#108;&#116;&#111;&#058;'.$safe.$subject.'"'.$attributes.'>'.$title.'</a>';
	}

	/**
	 * Generate a "breadcrumb" list of anchors representing the URI.
	 *
	 * @param   array   segments to use as breadcrumbs, defaults to using Router::$segments
	 * @return  string
	 */
	public static function breadcrumb($segments = NULL)
	{
		empty($segments) and $segments = Router::$segments;

		$array = array();
		while ($segment = array_pop($segments))
		{
			$array[] = html::anchor
			(
				// Complete URI for the URL
				implode('/', $segments).'/'.$segment,
				// Title for the current segment
				ucwords(inflector::humanize($segment))
			);
		}

		// Retrun the array of all the segments
		return array_reverse($array);
	}

	/**
	 * Creates a stylesheet link.
	 *
	 * @param   string|array  filename, or array of filenames to match to array of medias
	 * @param   string|array  media type of stylesheet, or array to match filenames
	 * @param   boolean       include the index_page in the link
	 * @return  string
	 */
	public static function stylesheet($style, $media = FALSE, $index = FALSE)
	{
		return html::link($style, 'stylesheet', 'text/css', '.css', $media, $index);
	}

	/**
	 * Creates a link tag.
	 *
	 * @param   string|array  filename
	 * @param   string|array  relationship
	 * @param   string|array  mimetype
	 * @param   string        specifies suffix of the file
	 * @param   string|array  specifies on what device the document will be displayed
	 * @param   boolean       include the index_page in the link
	 * @return  string
	 */
	public static function link($href, $rel, $type, $suffix = FALSE, $media = FALSE, $index = FALSE)
	{
		$compiled = '';

		if (is_array($href))
		{
			foreach($href as $_href)
			{
				$_rel   = is_array($rel) ? array_shift($rel) : $rel;
				$_type  = is_array($type) ? array_shift($type) : $type;
				$_media = is_array($media) ? array_shift($media) : $media;

				$compiled .= html::link($_href, $_rel, $_type, $suffix, $_media, $index);
			}
		}
		else
		{
			// Add the suffix only when it's not already present
			$suffix   = ( ! empty($suffix) AND strpos($href, $suffix) === FALSE) ? $suffix : '';
			$media    = empty($media) ? '' : ' media="'.$media.'"';
			$compiled = '<link rel="'.$rel.'" type="'.$type.'" href="'.url::base((bool) $index).$href.$suffix.'"'.$media.' />';
		}

		return $compiled."\n";
	}

	/**
	 * Creates a script link.
	 *
	 * @param   string|array  filename
	 * @param   boolean       include the index_page in the link
	 * @return  string
	 */
	public static function script($script, $index = FALSE)
	{
		$compiled = '';

		if (is_array($script))
		{
			foreach($script as $name)
			{
				$compiled .= html::script($name, $index);
			}
		}
		else
		{
			// Add the suffix only when it's not already present
			$suffix   = (strpos($script, '.js') === FALSE) ? '.js' : '';
			$compiled = '<script type="text/javascript" src="'.url::base((bool) $index).$script.$suffix.'"></script>';
		}

		return $compiled."\n";
	}

	/**
	 * Creates a image link.
	 *
	 * @param   string|array  array of html attributes, or an image name
	 * @param   boolean       include the index_page in the link
	 * @return  string
	 */
	public static function image($attr = NULL, $index = FALSE)
	{
		if ( ! is_array($attr))
		{
			$attr = array('src' => $attr);
		}

		if (strpos($attr['src'], '://') === FALSE)
		{
			// Make the src attribute into an absolute URL
			$attr['src'] = url::base($index).$attr['src'];
		}

		return '<img'.html::attributes($attr).' />';
	}

	/**
	 * Compiles an array of HTML attributes into an attribute string.
	 *
	 * @param   string|array  array of attributes
	 * @return  string
	 */
	public static function attributes($attrs)
	{
		if (empty($attrs))
			return '';

		if (is_string($attrs))
			return ' '.$attrs;

		$compiled = '';
		foreach($attrs as $key => $val)
		{
			$compiled .= ' '.$key.'="'.$val.'"';
		}

		return $compiled;
	}

} // End html
