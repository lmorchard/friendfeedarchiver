<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Download helper class.
 *
 * $Id: download.php 1725 2008-01-17 16:38:59Z PugFish $
 *
 * @package    Download Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class download_Core {

	/**
	 * Force a download of a file to the user's browser. This function is
	 * binary-safe and will work with any MIME type that Kohana is aware of.
	 *
	 * @param   string  a file path or file name
	 * @param   mixed   data to be sent if the filename does not exist
	 * @return  void
	 */
	public static function force($filename = '', $data = '')
	{
		static $user_agent;

		if ($filename == '')
			return FALSE;

		// Load the user agent
		if ($user_agent === NULL)
		{
			$user_agent = new User_agent();
		}

		if (is_file($filename))
		{
			// Get the real path
			$filepath = str_replace('\\', '/', realpath($filename));

			// Get extension
			$extension = pathinfo($filepath, PATHINFO_EXTENSION);

			// Remove directory path from the filename
			$filename = end(explode('/', $filepath));

			// Set filesize
			$filesize = filesize($filepath);
		}
		else
		{
			// Grab the file extension
			$extension = end(explode('.', $filename));

			// Try to determine if the filename includes a file extension.
			// We need it in order to set the MIME type
			if (empty($data) OR $extension === $filename)
				return FALSE;

			// Set filesize
			$filesize = strlen($data);
		}

		// Set a default mime if we can't find it
		if (($mime = Config::item('mimes.'.$extension)) === NULL)
		{
			$mime = 'application/octet-stream';
		}
		else
		{
			$mime = current((array) $mime);
		}

		// Generate the server headers
		header('Content-Type: '.$mime);
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Content-Length: '.$filesize);

		// IE headers
		if ($user_agent->browser === 'Internet Explorer')
		{
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		}
		else
		{
			header('Pragma: no-cache');
		}

		if (isset($filepath))
		{
			// Open the file
			$handle = fopen($filepath, 'rb');

			// Send the file data
			fpassthru($handle);

			// Close the file
			fclose($handle);
		}
		else
		{
			// Send the file data
			echo $data;
		}
	}

} // End download