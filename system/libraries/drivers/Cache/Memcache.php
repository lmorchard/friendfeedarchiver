<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Memcache-based Cache driver.
 *
 * $Id: Memcache.php 2008 2008-02-09 06:42:48Z PugFish $
 *
 * @package    Cache
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Cache_Memcache_Driver implements Cache_Driver {

	// Cache backend object and flags
	protected $backend;
	protected $flags;

	public function __construct()
	{
		if ( ! extension_loaded('memcache'))
			throw new Kohana_Exception('cache.extension_not_loaded', 'memcache');

		$this->backend = new Memcache;
		$this->flags = Config::item('cache_memcache.compression') ? MEMCACHE_COMPRESSED : 0;

		foreach (Config::item('cache_memcache.servers') as $server)
		{
			// Make sure all required keys are set
			$server += array('host' => '127.0.0.1', 'port' => 11211, 'persistent' => FALSE);

			// Add the server to the pool
			$this->backend->addServer($server['host'], $server['port'], (bool) $server['persistent'])
				or Log::add('error', 'Cache: Connection failed: '.$server['host']);
		}
	}

	public function find($tag)
	{
		return FALSE;
	}

	public function get($id)
	{
		return $this->backend->get($id);
	}

	public function set($id, $data, $tags, $expiration)
	{
		count($tags) and Log::add('error', 'Cache: Tags are unsupported by the memcache driver');

		return $this->backend->set($id, $data, $this->flags, $expiration);
	}

	public function delete($id, $tag = FALSE)
	{
		if ($id === TRUE)
		{
			return $this->backend->flush();
		}
		elseif ($tag == FALSE)
		{
			return $this->backend->delete($id);
		}
		else
		{
			return TRUE;
		}
	}

	public function delete_expired()
	{
		return TRUE;
	}

} // End Cache Memcache Driver