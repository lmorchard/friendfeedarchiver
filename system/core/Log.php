<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Message file logging class.
 * 
 * $Id: Log.php 1930 2008-02-05 22:35:57Z armen $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Log {

	private static $log_directory;

	private static $types = array(1 => 'error', 2 => 'debug', 3 => 'info');
	private static $messages = array();

	/**
	 * Set the the log directory. The log directory is determined by Kohana::setup.
	 *
	 * @param   string   full log directory path
	 * @return  void
	 */
	public static function directory($directory)
	{
		if (self::$log_directory === NULL)
		{
			// Set the log directory if it has not already been set
			self::$log_directory = rtrim($directory, '/').'/';
		}
	}

	/**
	 * Add a log message.
	 *
	 * @param   string  info, debug, or error
	 * @param   string  message to be logged
	 * @return  void
	 */
	public static function add($type, $message)
	{
		self::$messages[strtolower($type)][] = array
		(
			date(Config::item('log.format')),
			strip_tags($message)
		);
	}

	/**
	 * Write the current log to a file.
	 *
	 * @return  void
	 */
	public static function write()
	{
		// Set the log threshold
		$threshold = Config::item('log.threshold');

		// Don't log if there is nothing to log to
		if ($threshold < 1 OR count(self::$messages) === 0) return;

		// Set the log filename
		$filename = self::$log_directory.date('Y-m-d').'.log'.EXT;

		// Compile the messages
		$messages = '';
		foreach(self::$messages as $type => $data)
		{
			if (array_search($type, self::$types) > $threshold)
				continue;

			foreach($data as $date => $text)
			{
				list($date, $message) = $text;
				$messages .= $date.' -- '.$type.': '.$message."\r\n";
			}
		}

		// No point in logging nothing
		if ($messages == '')
			return;

		// Create the log file if it doesn't exist yet
		if ( ! file_exists($filename))
		{
			touch($filename);
			chmod($filename, 0644);

			// Add our PHP header to the log file to prevent URL access
			$messages = "<?php defined('SYSPATH') or die('No direct script access.'); ?>\r\n\r\n".$messages;
		}

		// Append the messages to the log
		file_put_contents($filename, $messages, FILE_APPEND) or trigger_error
		(
			'The log file could not be written to. Please correct the permissions and refresh the page.',
			E_USER_ERROR
		);
	}

} // End Log
