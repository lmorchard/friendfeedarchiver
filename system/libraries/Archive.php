<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Archive library.
 *
 * $Id: Archive.php 1924 2008-02-05 16:39:20Z Shadowhand $
 *
 * @package    Archive
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Archive_Core {

	// Files and directories
	protected $paths;

	// Driver instance
	protected $driver;

	/**
	 * Loads the archive driver.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   type of archive to create
	 * @return  void
	 */
	public function __construct($type = NULL)
	{
		$type = empty($type) ? 'zip' : $type;

		// Set driver name
		$driver = 'Archive_'.ucfirst($type).'_Driver';

		// Load the driver
		if ( ! Kohana::auto_load($driver))
			throw new Kohana_Exception('archive.driver_not_supported', $type);

		// Initialize the driver
		$this->driver = new $driver();

		// Validate the driver
		if ( ! ($this->driver instanceof Archive_Driver))
			throw new Kohana_Exception('archive.driver_implements', $type);

		Log::add('debug', 'Archive Library initialized');
	}

	/**
	 * Adds files or directories, recursively, to an archive.
	 *
	 * @param   string   file or directory to add
	 * @param   string   name to use for the given file or directory
	 * @param   bool     add files recursively, used with directories
	 * @return  object
	 */
	public function add($path, $name = NULL, $recursive = NULL)
	{
		// Normalize to forward slashes
		$path = str_replace('\\', '/', $path);

		// Set the name
		($name === NULL) and $name = $path;

		if (file_exists($path) AND is_dir($path))
		{
			// Force directories to end with a slash
			$path = rtrim($path, '/').'/';
			$name = rtrim($name, '/').'/';

			// Add the directory to the paths
			$this->paths[] = array($path, $name);

			if ($recursive == TRUE)
			{
				$dir = opendir($path);
				while (($file = readdir($dir)) !== FALSE)
				{
					// Do not add hidden files or directories
					if (substr($file, 0, 1) === '.')
						continue;

					// Add directory contents
					$this->add($path.$file, $name.$file, TRUE);
				}
				closedir($dir);
			}
		}
		else
		{
			$this->paths[] = array($path, $name);
		}

		return $this;
	}

	/**
	 * Creates an archive and saves it into a file.
	 *
	 * @throws  Kohana_Exception
	 * @param   string   archive filename
	 * @return  boolean
	 */
	public function save($filename)
	{
		// Get the directory name
		$directory = pathinfo($filename, PATHINFO_DIRNAME);

		if ( ! is_writable($directory))
			throw new Kohana_Exception('archive.directory_unwritable', $directory);

		if (file_exists($filename))
		{
			// Unable to write to the file
			if ( ! is_writable($filename))
				throw new Kohana_Exception('archive.filename_conflict', $filename);

			// Remove the file
			unlink($filename);
		}

		return $this->driver->create($this->paths, $filename);
	}

	/**
	 * Creates a raw archive file and returns it.
	 *
	 * @return  string
	 */
	public function create()
	{
		return $this->driver->create($this->paths);
	}

	/**
	 * Forces a download of a created archive.
	 *
	 * @param   string   name of the file that will be downloaded
	 * @return  void
	 */
	public function download($filename)
	{
		download::force($filename, $this->driver->create($this->paths));
	}

} // End Archive