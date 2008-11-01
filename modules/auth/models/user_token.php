<?php defined('SYSPATH') or die('No direct script access.');

class User_Token_Model extends ORM {

	// Relationships
	protected $belongs_to = array('user');

	// Current timestamp
	protected $now;

	/**
	 * Handles garbage collection and deleting of expired objects.
	 */
	public function __construct($id = FALSE)
	{
		parent::__construct($id);

		// Set the now, we use this a lot
		$this->now = time();

		if (mt_rand(1, 100) === 1)
		{
			// Do garbage collection
			$this->delete_expired();
		}

		if ($this->object->id != 0 AND $this->object->expires < $this->now)
		{
			// This object has expired
			$this->delete();
		}
	}

	/**
	 * Overload saving to set the created time and to create a new token
	 * when the object is saved.
	 */
	public function save()
	{
		if ($this->object->id == 0)
		{
			// Set the created time, token, and hash of the user agent
			$this->created = $this->now;
			$this->user_agent = sha1(Kohana::$user_agent);
		}

		// Create a new token each time the token is saved
		$this->token = $this->create_token();

		return parent::save();
	}

	/**
	 * Deletes all expired tokens.
	 *
	 * @return  void
	 */
	public function delete_expired()
	{
		// Delete all expired tokens
		self::$db->where('expires <', $this->now)->delete($this->table);
	}

	/**
	 * Allows loading by token string.
	 */
	protected function where_key($id)
	{
		if ( ! empty($id) AND is_string($id) AND ! ctype_digit($id))
		{
			return 'token';
		}

		return parent::where_key($id);
	}

	/**
	 * Finds a new unique token, using a loop to make sure that the token does
	 * not already exist in the database. This could potentially become an
	 * infinite loop, but the chances of that happening are very unlikely.
	 *
	 * @return  string
	 */
	protected function create_token()
	{
		while (TRUE)
		{
			// Create a random token
			$token = text::random('alnum', 32);

			// Make sure the token does not already exist
			if (count(self::$db->select('id')->where('token', $token)->get($this->table)) === 0)
			{
				// A unique token has been found
				return $token;
			}
		}
	}

} // End User Token