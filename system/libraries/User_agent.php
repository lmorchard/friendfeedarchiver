<?php defined('SYSPATH') or die('No direct script access.');
/**
 * User agent library.
 *
 * $Id: User_agent.php 2030 2008-02-11 16:16:19Z Geert $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class User_agent_Core {

	public static $agent;

	protected static $referrer  = '';
	protected static $languages = array();
	protected static $charsets  = array();

	protected $platform = '';
	protected $browser  = '';
	protected $version  = '';
	protected $mobile   = '';
	protected $robot    = '';

	/**
	 * Loads user agent data.
	 */
	public function __construct()
	{
		// Make sure the user agent is set
		if (empty(self::$agent) AND (self::$agent = Kohana::$user_agent) === '')
		{
			Log::add('debug', 'Could not determine user agent type.');
			return;
		}

		// Set the user agent data
		foreach(Config::item('user_agents') as $type => $data)
		{
			if (isset($this->$type))
			{
				foreach($data as $agent => $name)
				{
					if (stripos(self::$agent, $agent) !== FALSE)
					{
						if ($type == 'browser' AND preg_match('|'.preg_quote($agent).'[^0-9.]*([0-9.]+)|i', self::$agent, $match))
						{
							$this->version = $match[1];
							unset($match);
						}
						$this->$type = $name;
						break;
					}
				}
			}
		}

		// Set the accepted languages
		if (empty(self::$languages) AND ! empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			self::$languages = (preg_match_all('/[-a-z]{2,}/', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])), $matches)) ? $matches[0] : array();
		}

		// Set the accepted charsets
		if (empty(self::$charsets) AND ! empty($_SERVER['HTTP_ACCEPT_CHARSET']))
		{
			self::$charsets = (preg_match_all('/[-a-z0-9]{2,}/', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])), $matches)) ? $matches[0] : array();
		}

		// Set the referrer
		if (empty(self::$referrer) AND ! empty($_SERVER['HTTP_REFERER']))
		{
			self::$referrer = trim($_SERVER['HTTP_REFERER']);
		}

		Log::add('debug', 'User Agent Library initialized');
	}

	/**
	 * Fetch information about the user agent, examples:
	 * is_browser, is_mobile, is_robot
	 * agent, browser, mobile, version, referrer
	 *
	 * @param   string  key name
	 * @return  string
	 */
	public function __get($key)
	{
		if (empty($key))
		{
			return;
		}
		elseif (strpos($key, 'is_') === 0)
		{
			$key = substr($key, 3);
			return isset($this->$key) ? (bool) $this->$key : FALSE;
		}
		elseif (isset($this->$key))
		{
			return $this->$key;
		}
		elseif (isset(self::$$key))
		{
			return self::$$key;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * So that users can use $user_agent->is_robot() or $user_agent->is_robot.
	 *
	 * @param   string  function name
	 * @return  string
	 */
	public function __call($func, $args = FALSE)
	{
		return $this->__get($func);
	}

	/**
	 * Returns the full user agent string when the object is turned into a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return self::$agent;
	}

	/**
	 * Test for a particular language.
	 *
	 * @param   string   language to test for
	 * @return  boolean
	 */
	public function accept_lang($lang = 'en')
	{
		if (empty($lang) OR ! is_string($lang))
			return FALSE;

		return in_array(strtolower($lang), self::$languages);
	}

	/**
	 * Test for a particular character set.
	 *
	 * @param   string   character set to test for
	 * @return  boolean
	 */
	public function accept_charset($charset = 'utf-8')
	{
		if (empty($charset) OR ! is_string($charset))
			return FALSE;

		return in_array(strtolower($charset), $this->charsets());
	}

} // End User_Agent Class