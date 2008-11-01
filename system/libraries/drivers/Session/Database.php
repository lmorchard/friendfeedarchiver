<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Session database driver.
 *
 * $Id: Database.php 2001 2008-02-08 21:11:26Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Session_Database_Driver implements Session_Driver {

	/*
	CREATE TABLE kohana_session
	(
		session_id VARCHAR(40) NOT NULL,
		last_activity INT(11) NOT NULL,
		data TEXT NOT NULL,
		PRIMARY KEY (session_id)
	);
	*/

	protected $db;
	protected $encrypt;

	protected $db_group;
	protected $new_session = TRUE;
	protected $old_id;

	// Session has been written?
	protected $written = FALSE;

	public function __construct()
	{
		$this->db_group = Config::item('session.storage');

		// Load Encrypt library
		if (Config::item('session.encryption'))
		{
			$this->encrypt = new Encrypt;
		}

		// Write the session when PHP shuts down, this stops the database
		// being connected to twice when exit is called manually
		register_shutdown_function('session_write_close');

		Log::add('debug', 'Session Database Driver Initialized');
	}

	public function open($path, $name)
	{
		if (Config::item('database.'.$this->db_group) === NULL)
		{
			// There's no defined group, use the default database
			$this->db = new Database;
		}
		else
		{
			// Connect to the database using a database group, defined
			// by the 'session.storage' config item.
			$this->db = new Database($this->db_group);
		}

		return is_object($this->db);
	}

	public function close()
	{
		return TRUE;
	}

	public function read($id)
	{
		$query = $this->db->from($this->db_group)->where('session_id', $id)->get()->result(TRUE);

		if ($query->count() > 0)
		{
			// No new session, this is used when writing the data
			$this->new_session = FALSE;
			return (Config::item('session.encryption')) ? $this->encrypt->decode($query->current()->data) : $query->current()->data;
		}

		// Return value must be string, NOT a boolean
		return '';
	}

	public function write($id, $data)
	{
		// Has the session already been written?
		if ($this->written)
			return TRUE;

		$session = array
		(
			'session_id' => $id,
			'last_activity' => time(),
			'data' => (Config::item('session.encryption')) ? $this->encrypt->encode($data) : $data
		);

		// Existing session, with regenerated session id
		if ( ! empty($this->old_id))
		{
			$query = $this->db->update($this->db_group, $session, array('session_id' => $this->old_id));
		}
		// New session
		elseif ($this->new_session)
		{
			$query = $this->db->insert($this->db_group, $session);
		}
		// Existing session, without regenerated session id
		else
		{
			// No need to update session_id
			unset($session['session_id']);

			$query = $this->db->update($this->db_group, $session, array('session_id' => $id));

			$this->written = TRUE;
		}

		return (bool) $query->count();
	}

	public function destroy($id)
	{
		return (bool) $this->db->delete($this->db_group, array('session_id' => $id))->count();
	}

	public function regenerate()
	{
		// It's wasteful to delete the old session and insert a whole new one so
		// we cache the old id to simply update the db with the new one
		$this->old_id = session_id();

		session_regenerate_id();

		// Return new session id
		return session_id();
	}

	public function gc($maxlifetime)
	{
		$query = $this->db->delete($this->db_group, array('last_activity <' => time() - $maxlifetime));

		Log::add('debug', 'Session garbage collected: '.$query->count().' row(s) deleted.');

		return TRUE;
	}

} // End Session Database Driver
