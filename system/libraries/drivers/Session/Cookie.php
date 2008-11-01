<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Session cookie driver.
 *
 * $Id: Cookie.php 1928 2008-02-05 21:00:14Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Session_Cookie_Driver implements Session_Driver {

	protected $cookie_name;
	protected $encrypt; // Library

	public function __construct()
	{
		$this->cookie_name = Config::item('session.name').'_data';

		if (Config::item('session.encryption'))
		{
			$this->encrypt = new Encrypt;
		}

		Log::add('debug', 'Session Cookie Driver Initialized');
	}

	public function open($path, $name)
	{
		return TRUE;
	}

	public function close()
	{
		return TRUE;
	}

	public function read($id)
	{
		$data = (string) cookie::get($this->cookie_name);

		if ($data == '')
			return $data;

		return (Config::item('session.encryption')) ? $this->encrypt->decode($data) : base64_decode($data);
	}

	public function write($id, $data)
	{
		$data = (Config::item('session.encryption')) ? $this->encrypt->encode($data) : base64_encode($data);

		if (strlen($data) > 4048)
		{
			Log::add('error', 'Session data exceeds the 4kB limit, ignoring write.');
			return FALSE;
		}

		return cookie::set($this->cookie_name, $data, Config::item('session.expiration'));
	}

	public function destroy($id)
	{
		unset($_COOKIE[$this->cookie_name]);

		return cookie::delete($this->cookie_name);
	}

	public function regenerate()
	{
		session_regenerate_id(TRUE);

		// Return new id
		return session_id();
	}

	public function gc($maxlifetime)
	{
		return TRUE;
	}

} // End Session Cookie Driver Class