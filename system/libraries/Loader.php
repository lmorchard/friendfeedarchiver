<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Loader.
 *
 * $Id: Loader.php 1758 2008-01-21 00:05:34Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Loader_Core {

	/**
	 * Autoloads libraries and models specified in config file.
	 */
	public function __construct()
	{
		foreach(Config::item('core.preload') as $type => $load)
		{
			if ($load == FALSE) continue;

			foreach(explode(',', $load) as $name)
			{
				if (($name = trim($name)) == FALSE) continue;

				switch($type)
				{
					case 'libraries':
						if ($name == 'database')
						{
							$this->database();
						}
						else
						{
							$this->library($name);
						}
					break;
					case 'models':
						$this->model($name);
					break;
				}
			}
		}
	}

	/**
	 * Load library.
	 *
	 * @param   string   library name
	 * @param   array    custom configuration
	 * @param   boolean  return library instance instead of adding to Kohana instance
	 * @return  FALSE|Object  FALSE if library is already loaded, instance of library if return is TRUE
	 */
	public function library($name, $config = array(), $return = FALSE)
	{
		if (isset(Kohana::instance()->$name) AND $return == FALSE)
			return FALSE;

		if ($name == 'database')
		{
			return $this->database($config, $return);
		}
		else
		{
			$class = ucfirst($name);
			$class = new $class($config);

			if ($return == TRUE)
				return $class;

			Kohana::instance()->$name = $class;
		}
	}

	/**
	 * Load database.
	 *
	 * @param   string         Database config group to use
	 * @param   boolean        return database instance instead of adding to Kohana instance
	 * @return  void|Database  database instance if return is TRUE
	 */
	public function database($group = 'default', $return = FALSE)
	{
		$db = new Database($group);

		// Return the new database object
		if ($return == TRUE)
			return $db;

		Kohana::instance()->db = $db;
	}

	/**
	 * Load helper. Deprecated.
	 *
	 * @param  string  helper name
	 */
	public function helper($name)
	{
		// Just don't do this... there's no point.
		Log::add('debug', 'Using $this->load->helper() is deprecated. See Kohana::auto_load().');
	}

	/**
	 * Load model.
	 *
	 * @param   string             model name
	 * @param   string             custom name for accessing model, or TRUE to return instance of model
	 * @return  void|FALSE|Object  FALSE if model is already loaded, instance of model if alias is TRUE
	 */
	public function model($name, $alias = FALSE)
	{
		// The alias is used for Controller->alias
		$alias = ($alias == FALSE) ? $name : $alias;
		$class = ucfirst($name).'_Model';

		if (isset(Kohana::instance()->$alias))
			return FALSE;

		if (strpos($name, '/') !== FALSE)
		{
			// Handle models in subdirectories
			require_once Kohana::find_file('models', $name);

			// Reset the class name
			$class = end(explode('/', $class));
		}

		// Load the model
		$model = new $class();

		// Return the model
		if ($alias === TRUE)
			return $model;

		Kohana::instance()->$alias = $model;
	}

	/**
	 * Load view.
	 *
	 * @param   string  view name
	 * @param   array   data to make accessible within view
	 * @return  View
	 */
	public function view($name, $data = array())
	{
		return new View($name, $data);
	}

} // End Loader Class