<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Provides Kohana-specific helper functions. This is where the magic happens!
 *
 * $Id: Kohana.php 1899 2008-02-02 00:29:35Z Shadowhand $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Kohana {

	// The singleton instance of the controller
	public static $instance;

	// Output buffering level
	private static $buffer_level = 0;

	// Will be set to TRUE when an exception is caught
	public static $has_error = FALSE;

	// The final output that will displayed by Kohana
	public static $output = '';

	// The current user agent
	public static $user_agent = '';

	/**
	 * Sets up the PHP environment. Adds error/exception handling, output
	 * buffering, and adds an auto-loading method for loading classes.
	 *
	 * This method is run immediately when this file is loaded, and is
	 * benchmarked as environment_setup.
	 *
	 * For security, this function also destroys the $_REQUEST global variable.
	 * Using the proper global (GET, POST, COOKIE, etc) is inherently more secure.
	 * The recommended way to fetch a global variable is using the Input library.
	 * @see http://www.php.net/globals
	 *
	 * @return  void
	 */
	final public static function setup()
	{
		static $run;

		// This function can only be run once
		if ($run === TRUE)
			return;

		// Start the environment setup benchmark
		Benchmark::start(SYSTEM_BENCHMARK.'_environment_setup');

		// Disable error reporting
		$ER = error_reporting(0);

		// Set the user agent
		self::$user_agent = trim($_SERVER['HTTP_USER_AGENT']);

		if (function_exists('date_default_timezone_set'))
		{
			$timezone = Config::item('locale.timezone');

			// Set default timezone, due to increased validation of date settings
			// which cause massive amounts of E_NOTICEs to be generated in PHP 5.2+
			date_default_timezone_set(empty($timezone) ? date_default_timezone_get() : $timezone);
		}

		// Restore error reporting
		error_reporting($ER);

		// Start output buffering
		ob_start(array('Kohana', 'output_buffer'));

		// Save buffering level
		self::$buffer_level = ob_get_level();

		// Set autoloader
		spl_autoload_register(array('Kohana', 'auto_load'));

		// Set error handler
		set_error_handler(array('Kohana', 'exception_handler'));

		// Set exception handler
		set_exception_handler(array('Kohana', 'exception_handler'));

		// Disable magic_quotes_runtime. The Input library takes care of
		// magic_quotes_gpc later.
		set_magic_quotes_runtime(0);

		// Send default text/html UTF-8 header
		header('Content-type: text/html; charset=UTF-8');

		// Set locale information
		setlocale(LC_ALL, Config::item('locale.language').'.UTF-8');

		if (Config::item('log.threshold') > 0)
		{
			// Get the configured log directory
			$log_dir = Config::item('log.directory');

			// Two possible locations
			$app_log = APPPATH.$log_dir;
			$log_dir = realpath($log_dir);

			// If the log directory does not exist, log inside of application/
			is_dir($log_dir) or $log_dir = $app_log;

			// Log directory must be writable
			if ( ! is_dir($log_dir) OR ! is_writable($log_dir))
				throw new Kohana_Exception('core.cannot_write_log');

			// Set the log directory
			Log::directory($log_dir);

			// Enable log writing if the log threshold is above 0
			register_shutdown_function(array('Log', 'write'));
		}

		// Enable Kohana routing
		Event::add('system.routing', array('Router', 'find_uri'));
		Event::add('system.routing', array('Router', 'setup'));

		// Enable Kohana controller initialization
		Event::add('system.execute', array('Kohana', 'instance'));

		// Enable Kohana 404 pages
		Event::add('system.404', array('Kohana', 'show_404'));

		// Enable Kohana output handling
		Event::add('system.shutdown', array('Kohana', 'shutdown'));

		if ($config = Config::item('hooks.enable'))
		{
			$hooks = array();

			if ( ! is_array($config))
			{
				// All of the hooks are enabled, so we use list_files
				$hooks = Kohana::list_files('hooks', TRUE);
			}
			else
			{
				// Individual hooks need to be found
				foreach($config as $name)
				{
					if ($hook = Kohana::find_file('hooks', $name, FALSE))
					{
						// Hook was found, add it to loaded hooks
						$hooks[] = $hook;
					}
					else
					{
						// This should never happen
						Log::add('error', 'Hook not found: '.$name);
					}
				}
			}

			// To validate the filename extension
			$ext = -(strlen(EXT));

			foreach($hooks as $hook)
			{
				if (substr($hook, $ext) === EXT)
				{
					// Hook was found, include it
					include_once $hook;
				}
				else
				{
					// This should never happen
					Log::add('error', 'Hook not found: '.$hook);
				}
			}
		}

		// Setup is complete, prevent it from being run again
		$run = TRUE;

		// Stop the environment setup routine
		Benchmark::stop(SYSTEM_BENCHMARK.'_environment_setup');
	}

	/**
	 * Loads the controller and initializes it. Runs the pre_controller,
	 * post_controller_constructor, and post_controller events. Triggers
	 * a system.404 event when the route cannot be mapped to a controller.
	 *
	 * This method is benchmarked as controller_setup and controller_execution.
	 *
	 * @return  object  instance of controller
	 */
	final public static function & instance()
	{
		if (self::$instance === NULL)
		{
			Benchmark::start(SYSTEM_BENCHMARK.'_controller_setup');

			// Include the Controller file
			require Router::$directory.Router::$controller.EXT;

			// Run system.pre_controller
			Event::run('system.pre_controller');

			// Set controller class name
			$controller = ucfirst(Router::$controller).'_Controller';

			// Make sure the controller class exists
			class_exists($controller, FALSE) or Event::run('system.404');

			// Find the unique controller methods
			$methods = array_diff(get_class_methods($controller), get_class_methods('Controller_Core'));

			// If there are no methods in the controller, it's invalid
			empty($methods) and Event::run('system.404');

			// Combine the methods
			$methods = array_combine($methods, $methods);

			if (isset($methods['_remap']))
			{
				// Change arguments to be $method, $arguments.
				// This makes _remap capable of being a much more effecient dispatcher
				Router::$arguments = array(Router::$method, Router::$arguments);

				// Set the method to _remap
				Router::$method = '_remap';
			}
			elseif (isset($methods[Router::$method]) AND substr(Router::$method, 0, 1) != '_')
			{
				// A valid route has been found, and nothing needs to be done.
				// Amazing that having nothing inside the statement still works.
			}
			elseif (method_exists($controller, '_default'))
			{
				// Change arguments to be $method, $arguments.
				// This makes _default a much more effecient 404 handler
				Router::$arguments = array(Router::$method, Router::$arguments);

				// Set the method to _default
				Router::$method = '_default';
			}
			else
			{
				// Method was not found, run the system.404 event
				Event::run('system.404');
			}

			// Initialize the controller
			$controller = new $controller;

			// Run system.post_controller_constructor
			Event::run('system.post_controller_constructor');

			// Stop the controller setup benchmark
			Benchmark::stop(SYSTEM_BENCHMARK.'_controller_setup');

			// Start the controller execution benchmark
			Benchmark::start(SYSTEM_BENCHMARK.'_controller_execution');

			// Controller method name, used for calling
			$method = Router::$method;

			if (empty(Router::$arguments))
			{
				// Call the controller method with no arguments
				$controller->$method();
			}
			else
			{
				// Manually call the controller for up to 4 arguments. Why? Because
				// call_user_func_array is ~3 times slower than direct method calls.
				switch(count(Router::$arguments))
				{
					case 1:
						$controller->$method(Router::$arguments[0]);
					break;
					case 2:
						$controller->$method(Router::$arguments[0], Router::$arguments[1]);
					break;
					case 3:
						$controller->$method(Router::$arguments[0], Router::$arguments[1], Router::$arguments[2]);
					break;
					case 4:
						$controller->$method(Router::$arguments[0], Router::$arguments[1], Router::$arguments[2], Router::$arguments[3]);
					break;
					default:
						// Resort to using call_user_func_array for many segments
						call_user_func_array(array($controller, $method), Router::$arguments);
					break;
				}
			}

			// Run system.post_controller
			Event::run('system.post_controller');

			// Stop the controller execution benchmark
			Benchmark::stop(SYSTEM_BENCHMARK.'_controller_execution');
		}

		return self::$instance;
	}

	/**
	 * Kohana output handler.
	 *
	 * @param   string  current output buffer
	 * @return  string
	 */
	final public static function output_buffer($output)
	{
		// Run the send_headers event, specifically for cookies being set
		Event::has_run('system.send_headers') or Event::run('system.send_headers');

		// Set final output
		self::$output = $output;

		// Set and return the final output
		return $output;
	}

	/**
	 * Triggers the shutdown of Kohana by closing the output buffer, runs the system.display event.
	 *
	 * @return  void
	 */
	public static function shutdown()
	{
		while (ob_get_level() > self::$buffer_level)
		{
			// Flush all open output buffers above the internal buffer
			ob_end_flush();
		}

		// This will flush the Kohana buffer, which sets self::$output
		(ob_get_level() === self::$buffer_level) and ob_end_clean();

		// Run the output event
		Event::run('system.display', self::$output);

		// Render the final output
		self::render(self::$output);
	}

	/**
	 * Inserts global Kohana variables into the generated output and prints it.
	 *
	 * @param   string  final output that will displayed
	 * @return  void
	 */
	public static function render($output)
	{
		// Fetch memory usage in MB
		$memory = function_exists('memory_get_usage') ? (memory_get_usage() / 1024 / 1024) : 0;

		// Fetch benchmark for page execution time
		$benchmark = Benchmark::get(SYSTEM_BENCHMARK.'_total_execution');

		// Replace the global template variables
		$output = str_replace(
			array
			(
				'{kohana_version}',
				'{kohana_codename}',
				'{execution_time}',
				'{memory_usage}'
			),
			array
			(
				KOHANA_VERSION,
				KOHANA_CODENAME,
				$benchmark['time'],
				number_format($memory, 2).'MB'
			),
			$output
		);

		if (ini_get('output_handler') != 'ob_gzhandler' AND ini_get('zlib.output_compression') == 0 AND $level = Config::item('core.output_compression'))
		{
			if ($level < 1 OR $level > 9)
			{
				// Normalize the level to be an integer between 1 and 9. This
				// step must be done to prevent gzencode from triggering an error
				$level = max(1, min($level, 9));
			}

			if (stripos(@$_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
			{
				$compress = 'gzip';
			}
			elseif (stripos(@$_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== FALSE)
			{
				$compress = 'deflate';
			}
		}

		if (isset($compress) AND $level > 0)
		{
			switch($compress)
			{
				case 'gzip':
					// Compress output using gzip
					$output = gzencode($output, $level);
				break;
				case 'deflate':
					// Compress output using zlib (HTTP deflate)
					$output = gzdeflate($output, $level);
				break;
			}

			// This header must be sent with compressed content to prevent
			// browser caches from breaking
			header('Vary: Accept-Encoding');

			// Send the content encoding header
			header('Content-Encoding: '.$compress);

			// Sending Content-Length in CGI can result in unexpected behavior
			if (stripos(PHP_SAPI, 'cgi') === FALSE)
			{
				header('Content-Length: '.strlen($output));
			}
		}

		echo $output;
	}

	/**
	 * Dual-purpose PHP error and exception handler. Uses the kohana_error_page
	 * view to display the message.
	 *
	 * @param   integer|object  exception object or error code
	 * @param   string          error message
	 * @param   string          filename
	 * @param   integer         line number
	 * @return  void
	 */
	public static function exception_handler($exception, $message = NULL, $file = NULL, $line = NULL)
	{
		// PHP errors have 5 args, always
		$PHP_ERROR = (func_num_args() === 5);

		// Test to see if errors should be displayed
		if ($PHP_ERROR AND (error_reporting() & $exception) === 0)
			return;

		// This is useful for hooks to determine if a page has an error
		self::$has_error = TRUE;

		// Error handling will use exactly 5 args, every time
		if ($PHP_ERROR)
		{
			$code     = $exception;
			$type     = 'PHP Error';
			$template = 'kohana_error_page';
		}
		else
		{
			$code     = $exception->getCode();
			$type     = get_class($exception);
			$message  = $exception->getMessage();
			$file     = $exception->getFile();
			$line     = $exception->getLine();
			$template = ($exception instanceof Kohana_Exception) ? $exception->getTemplate() : 'kohana_error_page';
		}

		if (is_numeric($code))
		{
			$codes = Kohana::lang('errors');

			if ( ! empty($codes[$code]))
			{
				list($level, $error, $description) = $codes[$code];
			}
			else
			{
				$level = 1;
				$error = $PHP_ERROR ? 'Unknown Error' : get_class($exception);
				$description = '';
			}
		}
		else
		{
			// Custom error message, this will never be logged
			$level = 5;
			$error = $code;
			$description = '';
		}

		// Remove the DOCROOT from the path, as a security precaution
		$file = str_replace('\\', '/', realpath($file));
		$file = preg_replace('|^'.preg_quote(DOCROOT).'|', '', $file);

		if (Config::item('log.threshold') >= $level)
		{
			// Log the error
			Log::add('error', Kohana::lang('core.uncaught_exception', $type, $message, $file, $line));
		}

		if ($PHP_ERROR)
		{
			$description = Kohana::lang('errors.'.E_RECOVERABLE_ERROR);
			$description = $description[2];
		}
		else
		{
			if (method_exists($exception, 'sendHeaders'))
			{
				// Send the headers if they have not already been sent
				headers_sent() or $exception->sendHeaders();
			}
		}

		while (ob_get_level() > self::$buffer_level)
		{
			// Clean all active output buffers
			ob_end_clean();
		}

		// Clear the current buffer
		(ob_get_level() === self::$buffer_level) and ob_clean();

		// Test if display_errors is on
		if (Config::item('core.display_errors'))
		{
			if ($line != FALSE)
			{
				// Remove the first entry of debug_backtrace(), it is the exception_handler call
				$trace = $PHP_ERROR ? array_slice(debug_backtrace(), 1) : $exception->getTrace();

				// Beautify backtrace
				$trace = self::backtrace($trace);
			}

			// Load the error
			include self::find_file('views', empty($template) ? 'kohana_error_page' : $template);
		}
		else
		{
			// Get the i18n messages
			$error = Kohana::lang('core.generic_error');
			$message = sprintf(Kohana::lang('core.errors_disabled'), url::site(''), url::site(Router::$current_uri));

			// Load the errors_disabled view
			include self::find_file('views', 'kohana_error_disabled');
		}

		// Run the system.shutdown event
		Event::has_run('system.shutdown') or Event::run('system.shutdown');

		// Turn off error reporting
		error_reporting(0);
		exit;
	}

	/**
	 * Displays a 404 page.
	 *
	 * @throws  Kohana_404_Exception
	 * @param   string  URI of page
	 * @param   string  custom template
	 * @return  void
	 */
	public static function show_404($page = FALSE, $template = FALSE)
	{
		throw new Kohana_404_Exception($page, $template);
	}

	/**
	 * Show a custom error message.
	 *
	 * @throws  Kohana_User_Exception
	 * @param   string  error title
	 * @param   string  error message
	 * @param   string  custom template
	 * @return  void
	 */
	public static function show_error($title, $message, $template = FALSE)
	{
		throw new Kohana_User_Exception($title, $message, $template);
	}

	/**
	 * Provides class auto-loading.
	 *
	 * @throws  Kohana_Exception
	 * @param   string  name of class
	 * @return  bool
	 */
	public static function auto_load($class)
	{
		static $prefix;

		// Set the extension prefix
		empty($prefix) and $prefix = Config::item('core.extension_prefix');

		if (class_exists($class, FALSE))
			return TRUE;

		if (($type = strrpos($class, '_')) !== FALSE)
		{
			// Find the class suffix
			$type = substr($class, $type + 1);
		}

		switch($type)
		{
			case 'Core':
				$type = 'libraries';
				$file = substr($class, 0, -5);
			break;
			case 'Controller':
				$type = 'controllers';
				// Lowercase filename
				$file = strtolower(substr($class, 0, -11));
			break;
			case 'Model':
				$type = 'models';
				// Lowercase filename
				$file = strtolower(substr($class, 0, -6));
			break;
			case 'Driver':
				$type = 'libraries/drivers';
				$file = str_replace('_', '/', substr($class, 0, -7));
			break;
			default:
				// This can mean either a library or a helper, but libraries must
				// always be capitalized, so we check if the first character is
				// lowercase. If it is, we are loading a helper, not a library.
				$type = (ord($class[0]) > 96) ? 'helpers' : 'libraries';
				$file = $class;
			break;
		}

		// If the file doesn't exist, just return
		if (($filepath = self::find_file($type, $file)) === FALSE)
			return FALSE;

		// Load the requested file
		require_once $filepath;

		if ($type === 'libraries' OR $type === 'helpers')
		{
			if ($extension = self::find_file($type, $prefix.$class))
			{
				// Load the class extension
				require_once $extension;
			}
			elseif (substr($class, -5) !== '_Core' AND class_exists($class.'_Core', FALSE))
			{
				// Transparent class extensions are handled using eval. This is
				// a disgusting hack, but it works very well.
				eval('class '.$class.' extends '.$class.'_Core { }');
			}
		}

		return class_exists($class, FALSE);
	}

	/**
	 * Find a resource file in a given directory.
	 *
	 * @throws  Kohana_Exception  if file is required and not found
	 * @param   string   directory to search in
	 * @param   string   filename to look for (including extension only if 4th parameter is TRUE)
	 * @param   boolean  is the file required?
	 * @param   boolean  use custom file extension?
	 * @return  array    if the type is i18n or config
	 * @return  string   if the file is found
	 * @return  FALSE    if the file is not found
	 */
	public static function find_file($directory, $filename, $required = FALSE, $ext = FALSE)
	{
		static $found = array();

		$search = $directory.'/'.$filename;
		$hash   = sha1($search);

		if (isset($found[$hash]))
			return $found[$hash];

		if ($directory == 'config' OR $directory == 'i18n')
		{
			$fnd = array();

			// Search from SYSPATH up
			foreach(array_reverse(Config::include_paths()) as $path)
			{
				if (is_file($path.$search.EXT)) $fnd[] = $path.$search.EXT;
			}

			// If required and nothing was found, throw an exception
			if ($required == TRUE AND $fnd === array())
				throw new Kohana_Exception('core.resource_not_found', Kohana::lang('core.'.inflector::singular($directory)), $filename);

			return $found[$hash] = $fnd;
		}
		else
		{
			// Users can define their own extensions, .css, etc
			$ext = ($ext == FALSE) ? EXT : '';

			// Find the file and return its filename
			foreach (Config::include_paths() as $path)
			{
				if (is_file($path.$search.$ext))
					return $found[$hash] = $path.$search.$ext;
			}

			// If the file is required, throw an exception
			if ($required == TRUE)
				throw new Kohana_Exception('core.resource_not_found', Kohana::lang('core.'.inflector::singular($directory)), $filename);

			return $found[$hash] = FALSE;
		}
	}

	/**
	 * Lists all files and directories in a resource path.
	 *
	 * @param   string   directory to search
	 * @param   boolean  list all files to the maximum depth?
	 * @param   string   full path to search (used for recursion, *never* set this manually)
	 * @return  array    filenames and directories
	 */
	public static function list_files($directory, $recursive = FALSE, $path = FALSE)
	{
		$files = array();

		if ($path === FALSE)
		{
			foreach(Config::include_paths() as $path)
			{
				$files = array_merge($files, self::list_files($directory, $recursive, $path.$directory));
			}
		}
		else
		{
			$path = rtrim($path, '/').'/';

			if (is_readable($path))
			{
				foreach(glob($path.'*') as $index => $item)
				{
					$files[] = $item = str_replace('\\', '/', $item);

					// Handle recursion
					if (is_dir($item) AND $recursive == TRUE)
					{
						// Filename should only be the basename
						$item = pathinfo($item, PATHINFO_BASENAME);

						// Append sub-directory search
						$files = array_merge($files, self::list_files($directory, TRUE, $path.$item));
					}
				}
			}
		}

		return $files;
	}

	/**
	 * Fetch an i18n language item.
	 *
	 * @param   string  language key to fetch
	 * @param   array   additional information to insert into the line
	 * @return  string  i18n language string, or the requested key if the i18n item is not found
	 */
	public static function lang($key, $args = array())
	{
		static $language = array();

		$group = current(explode('.', $key));

		if ( ! isset($language[$group]))
		{
			// Messages from this file
			$messages = array();

			// The name of the file to search for
			$filename = Config::item('locale.language').'/'.$group;

			// Loop through the files and include each one, so SYSPATH files
			// can be overloaded by more localized files
			foreach(self::find_file('i18n', $filename) as $filename)
			{
				include $filename;

				// Merge in configuration
				if ( ! empty($lang) AND is_array($lang))
				{
					foreach($lang as $k => $v)
					{
						$messages[$k] = $v;
					}
				}
			}

			// Cache the type
			$language[$group] = $messages;
		}

		$line = self::key_string($key, $language);

		// Return the key string as fallback
		if ($line === NULL)
		{
			Log::add('debug', 'Missing i18n entry '.$key.' for language '.Config::item('locale.language'));
			return $key;
		}

		if (is_string($line) AND func_num_args() > 1)
		{
			$args = array_slice(func_get_args(), 1);

			// Add the arguments into the line
			$line = vsprintf($line, is_array($args[0]) ? $args[0] : $args);
		}

		return $line;
	}

	/**
	 * Returns the value of a key, defined by a 'dot-noted' string, from an array.
	 *
	 * @param   string  dot-noted string: foo.bar.baz
	 * @param   array   array to search
	 * @return  string  if the key is found
	 * @return  void    if the key is not found
	 */
	public static function key_string($keys, $array)
	{
		// No array to search
		if ((empty($keys) AND is_string($keys)) OR (empty($array) AND is_array($array)))
			return;

		// Prepare for loop
		$keys = explode('.', $keys);

		// Loop down and find the key
		do
		{
			// Get the current key
			$key = array_shift($keys);

			// Value is set, dig deeper or return
			if (isset($array[$key]))
			{
				// If the key is an array, and we haven't hit bottom, prepare
				// for the next loop by re-referencing to the next child
				if (is_array($array[$key]) AND ! empty($keys))
				{
					$array =& $array[$key];
				}
				else
				{
					// Requested key was found
					return $array[$key];
				}
			}
			else
			{
				// Requested key is not set
				break;
			}
		}
		while ( ! empty($keys));

		// We return NULL, because it's less common than FALSE
		return;
	}

	/**
	 * Quick debugging of any variable. Any number of parameters can be set.
	 *
	 * @return  string
	 */
	public static function debug()
	{
		if (func_num_args() === 0)
			return;

		// Get params
		$params = func_get_args();
		$output = array();

		foreach($params as $var)
		{
			$output[] = '<pre>'.html::specialchars(print_r($var, TRUE)).'</pre>';
		}

		return implode("\n", $output);
	}

	/**
	 * Displays nice backtrace information.
	 * @see http://php.net/debug_backtrace
	 *
	 * @param   array   backtrace generated by an exception or debug_backtrace
	 * @return  string
	 */
	public static function backtrace($trace)
	{
		if ( ! is_array($trace))
			return;

		// Final output
		$output = array();

		foreach($trace as $entry)
		{
			$temp = '<li>';

			if (isset($entry['file']))
			{
				// Add file (without docroot)
				$temp .= '<strong>'.preg_replace('!^'.preg_quote(DOCROOT).'!', '', $entry['file']);
				// Add line
				$temp .= ' ['.$entry['line'].']:</strong>';
			}

			$temp .= '<pre>';

			if (isset($entry['class']))
			{
				// Add class and call type
				$temp .= $entry['class'].$entry['type'];
			}

			// Add function
			$temp .= $entry['function'].'( ';

			// Add function args
			if (isset($entry['args']) AND is_array($entry['args']))
			{
				// Separator starts as nothing
				$sep = '';

				while ($arg = array_shift($entry['args']))
				{
					if (is_string($arg) AND is_file($arg))
					{
						// Remove docroot from filename
						$arg = preg_replace('!^'.preg_quote(DOCROOT).'!', '', $arg);
					}

					$temp .= $sep.print_r($arg, TRUE);

					// Change separator to a comma
					$sep = ', ';
				}
			}

			$temp .= ' )</pre></li>';

			$output[] = $temp;
		}

		return '<ul class="backtrace">'.implode("\n", $output).'</ul>';
	}

} // End Kohana

/**
 * Creates a generic i18n exception.
 */
class Kohana_Exception extends Exception {

	// Template file
	protected $template = 'kohana_error_page';

	// Message
	protected $message = 'Unknown Exception: ';

	// Header
	protected $header = FALSE;

	// Error code, filename, line number
	protected $code = E_KOHANA;
	protected $file = FALSE;
	protected $line = FALSE;

	/**
	 * Set exception message.
	 *
	 * @param  string  i18n language key for the message
	 * @param  array   addition line parameters
	 */
	function __construct($error)
	{
		$args = array_slice(func_get_args(), 1);

		// Fetch the error message
		$message = Kohana::lang($error, $args);

		// Handle error messages that are not set
		if ($message == $error)
		{
			$this->message .= $error;
		}
		else
		{
			$this->message = $message;
		}
	}

	/**
	 * Magic method for converting an object to a string.
	 *
	 * @return  string  i18n message
	 */
	public function __toString()
	{
		return (string) $this->message;
	}

	/**
	 * Fetch the template name.
	 *
	 * @return  string
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * Sends an Internal Server Error header.
	 *
	 * @return  void
	 */
	public function sendHeaders()
	{
		// Send the 500 header
		header('HTTP/1.1 500 Internal Server Error');
	}

} // End Kohana Exception

/**
 * Creates a custom exception.
 */
class Kohana_User_Exception extends Kohana_Exception {

	/**
	 * Set exception title and message.
	 *
	 * @param   string  exception title string
	 * @param   string  exception message string
	 * @param   string  custom error template
	 */
	public function __construct($title, $message, $template = FALSE)
	{
		$this->code     = $title;
		$this->message  = $message;

		if ($template != FALSE)
		{
			$this->template = $template;
		}
	}

} // End Kohana PHP Exception

/**
 * Creates a Page Not Found exception.
 */
class Kohana_404_Exception extends Kohana_Exception {

	protected $code = E_PAGE_NOT_FOUND;

	/**
	 * Set internal properties.
	 *
	 * @param  string  URL of page
	 * @param  string  custom error template
	 */
	public function __construct($page = FALSE, $template = FALSE)
	{
		if ($page === FALSE)
		{
			// Construct the page URI using Router properties
			$page = Router::$current_uri.Router::$url_suffix.Router::$query_string;
		}

		$this->message = Kohana::lang('core.page_not_found', $page);
		$this->file    = FALSE;
		$this->line    = FALSE;

		$this->template = $template;
	}

	/**
	 * Sends "File Not Found" headers, to emulate server behavior.
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
		// Send the 404 header
		header('HTTP/1.1 404 File Not Found');
	}

} // End Kohana 404 Exception

/**
 * Run Kohana setup to prepare the environment.
 */
Kohana::setup();
