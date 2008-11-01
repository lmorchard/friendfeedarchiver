<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Controller class.
 *
 * $Id: Controller.php 1762 2008-01-21 10:59:41Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Controller_Core {

	/**
	 * Loads Loader, URI, and Input into this controller.
	 *
	 * @return  void
	 */
	public function __construct()
	{
		if (Kohana::$instance === NULL)
		{
			// Set the instance to the first controller loaded
			Kohana::$instance = $this;

			// Loader should always be available
			$this->load = new Loader;

			// Loader should always be available
			$this->uri = new URI;

			// Input should always be available
			$this->input = new Input;
		}
		else
		{
			// Loader should always be available
			$this->load = Kohana::$instance->load;

			// Loader should always be available
			$this->uri = Kohana::$instance->uri;

			// Input should always be available
			$this->input = Kohana::$instance->input;
		}
	}

	/**
	 * Includes a View within the controller scope.
	 *
	 * @param   string  view filename
	 * @param   array   array of view variables
	 * @return  string
	 */
	public function _kohana_load_view($kohana_view_filename, $kohana_input_data)
	{
		if ($kohana_view_filename == '')
			return;

		// Buffering on
		ob_start();

		// Import the view variables to local namespace
		extract($kohana_input_data, EXTR_SKIP);

		// Views are straight HTML pages with embedded PHP, so importing them
		// this way insures that $this can be accessed as if the user was in
		// the controller, which gives the easiest access to libraries in views
		include $kohana_view_filename;

		// Fetch the output and close the buffer
		return ob_get_clean();
	}

} // End Controller Class