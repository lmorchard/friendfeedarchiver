<?php defined('SYSPATH') or die('No direct script access.');
/**
 * User authorization library. Handles user login and logout, as well as secure
 * password hashing.
 *
 * @package    User Management
 * @depends    ORM
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Auth_Core {

	// Session instance
	protected $session;

	// Configuration
	protected $config;

	/**
	 * Create an instance of Auth.
	 *
	 * @return  object
	 */
	public static function factory($config = array())
	{
		return new Auth($config);
	}

	/**
	 * Return a static instance of Auth.
	 *
	 * @return  object
	 */
	public static function instance($config = array())
	{
		static $instance;

		// Load the Auth instance
		empty($instance) and $instance = new Auth($config);

		return $instance;
	}

	/**
	 * Loads Session and configuration options.
	 */
	public function __construct($config = array())
	{
		// Load libraries
		$this->session = Session::instance();

		// Append default auth configuration
		$config += Config::item('auth');

		// Clean up the salt pattern and split it into an array
		$config['salt_pattern'] = preg_split('/, ?/', Config::item('auth.salt_pattern'));

		// Save the config in the object
		$this->config = $config;

		Log::add('debug', 'Auth Library loaded');
	}

	/**
	 * Attempt to log in a user by using an ORM object and plain-text password.
	 *
	 * @param   object  user model object
	 * @param   string  plain-text password to check against
	 * @param   bool    to allow auto-login, or "remember me" feature
	 * @return  bool
	 */
	public function login(User_Model $user, $password, $remember = FALSE)
	{
		if (empty($password))
			return FALSE;

		// Create a hashed password using the salt from the stored password
		$password = $this->hash_password($password,  $this->find_salt($user->password));

		// If the user has the "login" role and the passwords match, perform a login
		if ($user->has_role('login') AND $user->password === $password)
		{
			if ($remember == TRUE)
			{
				// Create a new autologin token
				$token = new User_Token_Model;

				// Set token data
				$token->user_id = $user->id;
				$token->expires = time() + $this->config['lifetime'];
				$token->save();

				// Set the autologin cookie
				cookie::set('autologin', $token->token, $this->config['lifetime']);
			}

			// Finish the login
			$this->complete_login($user);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Attempt to automatically log a user in by using tokens.
	 *
	 * @return  bool
	 */
	public function auto_login()
	{
		if ($token = cookie::get('autologin'))
		{
			// Load the token and user
			$token = new User_Token_Model($token);
			$user = new User_Model($token->user_id);

			if ($token->id != 0 AND $user->id != 0)
			{
				if ($token->user_agent === sha1(Kohana::$user_agent))
				{
					// Save the token to create a new unique token
					$token->save();

					// Set the new token
					cookie::set('autologin', $token->token, $token->expires - time());

					// Complete the login with the found data
					$this->complete_login($user);

					// Automatic login was successful
					return TRUE;
				}

				// Token is invalid
				$token->delete();
			}
		}

		return FALSE;
	}

	/**
	 * Log out a user by removing the related session variables.
	 *
	 * @param   bool   completely destroy the session
	 * @return  bool
	 */
	public function logout($destroy = FALSE)
	{
		// Delete the autologin cookie if it exists
		cookie::get('autologin') and cookie::delete('autologin');

		if ($destroy == TRUE)
		{
			$this->session->destroy();
		}
		else
		{
			$this->session->del('user_id', 'username', 'roles');
		}

		return TRUE;
	}

	/**
	 * Creates a hashed password from a plaintext password, inserting salt
	 * based on the configured salt pattern.
	 *
	 * Parameters:
	 *  password - plaintext password
	 *
	 * Returns:
	 *  Hashed password string
	 */
	public function hash_password($password, $salt = FALSE)
	{
		if ($salt == FALSE)
		{
			// Create a salt string, same length as the number of offsets in the pattern
			$salt = substr($this->hash(uniqid(NULL, TRUE)), 0, count($this->config['salt_pattern']));
		}

		// Password hash that the salt will be inserted into
		$hash = $this->hash($salt.$password);

		// Change salt to an array
		$salt = str_split($salt, 1);

		// Returned password
		$password = '';

		// Used to calculate the length of splits
		$last_offset = 0;

		foreach($this->config['salt_pattern'] as $offset)
		{
			// Split a new part of the hash off
			$part = substr($hash, 0, $offset - $last_offset);

			// Cut the current part out of the hash
			$hash = substr($hash, $offset - $last_offset);

			// Add the part to the password, appending the salt character
			$password .= $part.array_shift($salt);

			// Set the last offset to the current offset
			$last_offset = $offset;
		}

		// Return the password, with the remaining hash appended
		return $password.$hash;
	}

	/**
	 * Perform a hash, using the configured method.
	 *
	 * Parameters:
	 *  str - string to be hashed
	 *
	 * Returns:
	 *  Hashed string.
	 */
	protected function hash($str)
	{
		return hash($this->config['hash_method'], $str);
	}

	/**
	 * Finds the salt from a password, based on the configured salt pattern.
	 *
	 * Parameters:
	 *  password - hashed password
	 *
	 * Returns:
	 *  Salt string
	 */
	protected function find_salt($password)
	{
		$salt = '';

		foreach($this->config['salt_pattern'] as $i => $offset)
		{
			// Find salt characters... take a good long look..
			$salt .= substr($password, $offset + $i, 1);
		}

		return $salt;
	}

	/**
	 * Complete the login for a user by incrementing the logins and setting
	 * session data: user_id, username, roles
	 *
	 * @param   object   user model object
	 * @return  void
	 */
	protected function complete_login(User_Model $user)
	{
		// Update the number of logins
		$user->logins += 1;

		// Save the user
		$user->save();

		// Store session data
		$this->session->set(array
		(
			'user_id'  => $user->id,
			'username' => $user->username,
			'roles'    => $user->roles
		));
	}

} // End Auth