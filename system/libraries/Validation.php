<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Validation library.
 *
 * $Id: Validation.php 2111 2008-02-22 01:18:59Z Shadowhand $
 *
 * @package    Validation
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Validation_Core {

	// Instance count
	private static $instances = 0;

	// Currently validating field
	public $current_field = '';

	// Enable or disable safe form errors
	public $form_safe = FALSE;

	// Error message format
	public $error_format = '<p class="error">{message}</p>';
	public $newline_char = "\n";

	// Error messages
	public $messages = array();

	// Field names, rules, and errors
	protected $fields = array();
	protected $rules  = array();
	protected $errors = array();

	// Data to validate
	protected $data = array();

	// Result from validation rules
	protected $result;

	/**
	 * @param array  array to validate
	 */
	public function __construct( & $data = array())
	{
		$this->set_data($data);

		// Load the default error messages
		$this->messages = Kohana::lang('validation');

		// Add one more instance to the count
		self::$instances++;

		Log::add('debug', 'Validation Library Initialized, instance '.self::$instances);
	}

	/**
	 * Magically gets a validation variable. This can be an error string or a
	 * data field, or an array of all field data.
	 *
	 * @param   string        Variable name
	 * @return  string|array  The variable contents or NULL if the variable does not exist
	 */
	public function __get($key)
	{
		if ( ! isset($this->$key))
		{
			if ($key === 'error_string')
			{
				// Complete error message string
				$messages = FALSE;
				foreach(array_keys($this->errors) as $field)
				{
					$messages .= $this->__get($field.'_error');
				}
				return $messages;
			}
			elseif (substr($key, -6) === '_error')
			{
				// Get the field name
				$field = substr($key, 0, -6);

				// Return the error messages for this field
				$messages = FALSE;
				if (isset($this->errors[$field]) AND ! empty($this->errors[$field]))
				{
					foreach($this->errors[$field] as $error)
					{
						// Replace the message with the error in the html error string
						$messages .= str_replace('{message}', $error, $this->error_format).$this->newline_char;
					}
				}
				return $messages;
			}
			elseif (isset($this->data[$key]))
			{
				return $this->data[$key];
			}
			elseif ($key === 'data_array')
			{
				$data = array();
				foreach (array_keys($this->rules) as $key)
				{
					if (isset($this->data[$key]))
					{
						$data[$key] = $this->data[$key];
					}
				}
				return $data;
			}
		}
	}

	/**
	 * This function takes an array of key names, rules, and field names as
	 * input and sets internal field information.
	 *
	 * @param   string|array Key names
	 * @param   string       Rules
	 * @param   string       Field name
	 * @return  void
	 */
	public function set_rules($data, $rules = '', $field = FALSE)
	{
		// Normalize rules to an array
		if ( ! is_array($data))
		{
			if ($rules == '') return FALSE;

			// Make data into an array
			$data = array($data => array($field, $rules));
		}

		// Set the field information
		foreach ($data as $name => $rules)
		{
			if (is_array($rules))
			{
				if (count($rules) > 1)
				{
					$field = current($rules);
					$rules = next($rules);
				}
				else
				{
					$rules = current($rules);
				}
			}

			// Empty field names default to the name of the element
			$this->fields[$name] = empty($field) ? $name : $field;
			$this->rules[$name]  = $rules;

			// Prevent fields from getting the wrong name
			unset($field);
		}
	}

	/**
	 * Lets users set their own error messages on the fly.
	 * Note - The key name has to match the function name that it corresponds to.
	 *
	 * @param   string  Function name
	 * @param   string  Error message
	 * @return  void
	 */
	public function set_message($func, $message = '')
	{
		if ( ! is_array($func))
		{
			$func = array($func => $message);
		}

		foreach($func as $name => $message)
		{
			$this->messages[$name] = $message;
		}
	}

	/**
	 * @param   array  Data to validate
	 * @return  void
	 */
	public function set_data( & $data)
	{
		if ( ! empty($data) AND is_array($data))
		{
			$this->data =& $data;
		}
		else
		{
			$this->data =& $_POST;
		}
	}

	/**
	 * Allows the user to change the error message format. Error formats must
	 * contain the string "{message}" or Kohana_Exception will be triggered.
	 *
	 * @param   string  Error message format  
	 * @return  void
	 */
	public function error_format($string = '')
	{
		if (strpos((string) $string, '{message}') === FALSE)
			throw new Kohana_Exception('validation.error_format');

		$this->error_format = $string;
	}

	/**
	 * @param   string  Function name
	 * @param   string  field name
	 * @return  void
	 */
	public function add_error($func, $field)
	{
		// Set the friendly field name
		$friendly = isset($this->fields[$field]) ? $this->fields[$field] : $field;

		// Fetch the message
		$message = isset($this->messages[$func]) ? $this->messages[$func] : $this->messages['unknown_error'];

		// Replacements in strings
		$replace = array_slice(func_get_args(), 1);

		if ( ! empty($replace) AND $replace[0] === $field)
		{
			// Add the friendly name instead of the field name
			$replace[0] = $friendly;
		}

		// Add the field name into the message, if there is a place for it
		$message = (strpos($message, '%s') !== FALSE) ? vsprintf($message, $replace) : $message;

		$this->errors[$field][] = $message;
	}

	/**
	 * This function does all the work.
	 *
	 * @return  boolean  The validation result
	 */
	public function run()
	{
		// Do we even have any data to process?  Mm?
		if (count($this->data) == 0 OR count($this->rules) == 0)
		{
			return FALSE;
		}

		// Cycle through the rules and test for errors
		foreach ($this->rules as $field => $rules)
		{
			// Set the current field, for other functions to use
			$this->current_field = $field;

			// Insert uploads into the data
			if (strpos($rules, 'upload') !== FALSE AND isset($_FILES[$field]))
			{
				if (is_array($_FILES[$field]['error']))
				{
					foreach($_FILES[$field]['error'] as $error)
					{
						if ($error !== UPLOAD_ERR_NO_FILE)
						{
							$this->data[$field] = $_FILES[$field];
							break;
						}
					}
				}
				elseif ($_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE)
				{
					$this->data[$field] = $_FILES[$field];
				}
			}

			// Process empty fields
			if ( ! isset($this->data[$field]) OR $this->data[$field] == NULL)
			{
				// This field is required
				if (strpos($rules, 'required') !== FALSE)
				{
					$this->add_error('required', $field);
					continue;
				}
			}

			// Loop through the rules and process each one
			foreach(explode('|', $rules) as $rule)
			{
				// To properly handle recursion
				$this->run_rule($rule, $field);

				// Stop validating when there is an error
				if ($this->result === FALSE)
					break;
			}
		}

		// Run validation finished Event and return
		if (count($this->errors) == 0)
		{
			Event::run('validation.success', $this->data);
			return TRUE;
		}
		else
		{
			Event::run('validation.failure', $this->data);
			return FALSE;
		}
	}

	/**
	 * Handles recursively calling rules on arrays of data.
	 *
	 * @param   string  Validation rule to be run on the data
	 * @param   string  Name of field
	 * @return  void
	 */
	protected function run_rule($rule, $field)
	{
		// Use key_string to extract the field data
		$data = Kohana::key_string($field, $this->data);

		// Make sure that data input is not upload data
		if (is_array($data) AND ! (isset($data['tmp_name']) AND isset($data['error'])))
		{
			foreach($data as $key => $value)
			{
				// Recursion is fun!
				$this->run_rule($rule, $field.'.'.$key);

				if ($this->result === FALSE)
					break;
			}
		}
		else
		{
			if (strpos($rule, '=') === 0)
			{
				$rule               = substr($rule, 1);
				$this->data[$field] = $rule($data);
				return;
			}

			// Handle callback rules
			$callback = FALSE;
			if (preg_match('/callback_(.+)/', $rule, $match))
			{
				$callback = $match[1];
			}

			// Handle params
			$params = FALSE;
			if (preg_match('/([^\[]*+)\[(.+)\]/', $rule, $match))
			{
				$rule   = $match[1];
				$params = preg_split('/(?<!\\\\),/', $match[2]);
				$params = str_replace('\,', ',', $params);
			}

			// Process this field with the rule
			if ($callback !== FALSE)
			{
				if ( ! method_exists(Kohana::instance(), $callback))
					throw new Kohana_Exception('validation.invalid_rule', $callback);

				$this->result = Kohana::instance()->$callback($data, $params);
			}
			elseif ($rule === 'matches' OR $rule === 'depends_on')
			{
				$this->result = $this->$rule($field, $params);
			}
			elseif (method_exists($this, $rule))
			{
				$this->result = $this->$rule($data, $params);
			}
			elseif (is_callable($rule))
			{
				if (strpos($rule, '::') !== FALSE)
				{
					$this->result = call_user_func(explode('::', $rule), $data);
				}
				else
				{
					$this->result = $rule($data);
				}
			}
			else
			{
				// Trying to validate with a rule that does not exist? No way!
				throw new Kohana_Exception('validation.invalid_rule', $rule);
			}
		}
	}

	/**
	 * @param   mixed
	 * @param   array
	 * @return  boolean
	 */
	public function in_array($data, $array = FALSE)
	{
		if (empty($array) OR ! is_array($array) OR ! in_array($data, $array))
		{
			$this->add_error('in_array', $this->current_field);
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * @param   mixed         Data to pass to the event
	 * @param   array         Events name
	 * @return  boolean|void
	 */
	public function event($data, $events = FALSE)
	{
		// Validate the events
		if (empty($events) OR ! is_array($events))
		{
			$this->add_error('event', $this->current_field);
			return FALSE;
		}

		// Run the requested events
		foreach($events as $event)
		{
			Event::run('validation.'.$event, $data);
		}
	}

	/**
	 * @param   array
	 * @param   array
	 * @return  boolean|void
	 */
	public function upload($data, $params = FALSE)
	{
		// By default, nothing is allowed
		$allowed = FALSE;

		// Maximum sizes of various attributes
		$fileinfo = array
		(
			'file'   => FALSE,
			'human'  => FALSE,
			'max_width'  => FALSE,
			'max_height' => FALSE,
			'min_width' => FALSE,
			'min_height' => FALSE
		);

		if ($data === $this->data[$this->current_field])
		{
			// Clear the raw upload data, it's internal now
			$this->data[$this->current_field] = NULL;
		}

		if (is_array($data['name']))
		{
			// Handle an array of inputs
			$files = $data;
			$total = count($files['name']);

			for ($i = 0; $i < $total; $i++)
			{
				if (empty($files['name'][$i]))
					continue;

				// Fake a single upload input
				$data = array
				(
					'name'     => $files['name'][$i],
					'type'     => $files['type'][$i],
					'size'     => $files['size'][$i],
					'tmp_name' => $files['tmp_name'][$i],
					'error'    => $files['error'][$i]
				);

				// Recursion
				if ( ! $this->upload($data, $params))
					return FALSE;
			}

			// All files uploaded successfully
			return empty($this->errors);
		}

		// Parse addition parameters
		if (is_array($params) AND ! empty($params))
		{
			// Creates a mirrored array: foo=foo,bar=bar
			$params = array_combine($params, $params);

			foreach($params as $param)
			{
				if (preg_match('/[0-9]+x[0-9]+(?:-[0-9]+x[0-9]+)?/', $param))
				{
 					// Image size, eg: 200x100, 20x10-200x100
					$param = (strpos($param, '-') === FALSE) ? $param.'-'.$param : $param;

					list($min, $max) = explode('-', $param);

					list($fileinfo['max_width'], $fileinfo['max_height']) = explode('x', $max);
					list($fileinfo['min_width'], $fileinfo['min_height']) = explode('x', $min);
				}
				elseif (preg_match('/[0-9]+[BKMG]/i', $param))
				{
					// Maximum file size, eg: 1M
					$fileinfo['human'] = strtoupper($param);

					switch(strtoupper(substr($param, -1)))
					{
						case 'G': $param = intval($param) * pow(1024, 3); break;
						case 'M': $param = intval($param) * pow(1024, 2); break;
						case 'K': $param = intval($param) * pow(1024, 1); break;
						default:  $param = intval($param);                break;
					}

					$fileinfo['file'] = $param;
				}
				else
				{
					$allowed[strtolower($param)] = strtolower($param);
				}
			}
		}

		// Uploads must use a white-list of allowed file types
		if (empty($allowed))
			throw new Kohana_Exception('upload.set_allowed');

		// Make sure that UPLOAD_ERR_EXTENSION is defined
		defined('UPLOAD_ERR_EXTENSION') or define('UPLOAD_ERR_EXTENSION', 8);

		// Fetch the real upload path
		if (($upload_path = str_replace('\\', '/', realpath(Config::item('upload.upload_directory')))) == FALSE)
		{
			$data['error'] = UPLOAD_ERR_NO_TMP_DIR;
		}

		// Validate the upload path
		if ( ! is_dir($upload_path) OR ! is_writable($upload_path))
		{
			$data['error'] = UPLOAD_ERR_CANT_WRITE;
		}

		// Error code definitions available at:
		// http://us.php.net/manual/en/features.file-upload.errors.php
		switch($data['error'])
		{
			// Valid upload
			case UPLOAD_ERR_OK:
			break;
			// Upload to large, based on php.ini settings
			case UPLOAD_ERR_INI_SIZE:
				if ($fileinfo['human'] == FALSE)
				{
					$fileinfo['human'] = ini_get('upload_max_filesize');
				}
				$this->add_error('max_size', $this->current_field, $fileinfo['human']);
				return FALSE;
			break;
			// Kohana does not allow the MAX_FILE_SIZE input to control filesize
			case UPLOAD_ERR_FORM_SIZE:
				throw new Kohana_Exception('upload.max_file_size');
			break;
			// User aborted the upload, or a connection error occurred
			case UPLOAD_ERR_PARTIAL:
				$this->add_error('user_aborted', $this->current_field);
				return FALSE;
			break;
			// No file was uploaded, or an extension blocked the upload
			case UPLOAD_ERR_NO_FILE:
			case UPLOAD_ERR_EXTENSION:
				return FALSE;
			break;
			// No temporary directory set in php.ini
			case UPLOAD_ERR_NO_TMP_DIR:
				throw new Kohana_Exception('upload.no_tmp_dir');
			break;
			// Could not write to the temporary directory
			case UPLOAD_ERR_CANT_WRITE:
				throw new Kohana_Exception('upload.tmp_unwritable', $upload_path);
			break;
		}

		// Validate the uploaded file
		if ( ! isset($data['tmp_name']) OR ! is_uploaded_file($data['tmp_name']))
			return FALSE;

		if ($fileinfo['file'] AND $data['size'] > $fileinfo['file'])
		{
			$this->add_error('max_size', $this->current_field, $fileinfo['human']);
			return FALSE;
		}

		// Find the MIME type of the file. Although the mime type is available
		// in the upload data, it can easily be faked. Instead, we use the
		// server filesystem functions (if possible) to determine the MIME type.

		if (preg_match('/jpe?g|png|[gt]if|bmp/', implode(' ', $allowed)))
		{
			// Use getimagesize() to find the mime type on images
			$mime = @getimagesize($data['tmp_name']);

			// Validate height and width
			if ($fileinfo['max_width'] AND $mime[0] > $fileinfo['max_width'])
			{
				$this->add_error('max_width', $this->current_field, $fileinfo['max_width']);
				return FALSE;
			}
			elseif ($fileinfo['min_width'] AND $mime[0] < $fileinfo['min_width'])
			{
				$this->add_error('min_width', $this->current_field, $fileinfo['min_width']);
				return FALSE;
			}
			elseif ($fileinfo['max_height'] AND $mime[1] > $fileinfo['max_height'])
			{
				$this->add_error('max_height', $this->current_field, $fileinfo['max_height']);
				return FALSE;
			}
			elseif ($fileinfo['min_height'] AND $mime[1] < $fileinfo['min_height'])
			{
				$this->add_error('min_height', $this->current_field, $fileinfo['min_height']);
				return FALSE;
			}

			// Set mime type
			$mime = isset($mime['mime']) ? $mime['mime'] : FALSE;
		}
		elseif (function_exists('finfo_open'))
		{
			// Try using the fileinfo extension
			$finfo = finfo_open(FILEINFO_MIME);
			$mime  = finfo_file($finfo, $data['tmp_name']);
			finfo_close($finfo);
		}
		elseif (ini_get('mime_magic.magicfile') AND function_exists('mime_content_type'))
		{
			// Use mime_content_type(), deprecated by PHP
			$mime = mime_content_type($data['tmp_name']);
		}
		elseif (file_exists($cmd = trim(exec('which file'))))
		{
			// Use the UNIX 'file' command
			$mime = escapeshellarg($data['tmp_name']);
			$mime = trim(exec($cmd.' -bi '.$mime));
		}
		else
		{
			// Trust the browser, as a last resort
			$mime = $data['type'];
		}

		// Find the list of valid mime types by the extension of the file
		$ext = strtolower(end(explode('.', $data['name'])));

		// Validate file mime type based on the extension. Because the mime type
		// is trusted (validated by the server), we check if the mime is in the
		// list of known mime types for the current extension.

		if ($ext == FALSE OR ! in_array($ext, $allowed) OR array_search($mime, Config::item('mimes.'.$ext)) === NULL)
		{
			$this->add_error('invalid_type', $this->current_field);
			return FALSE;
		}

		// Removes spaces from the filename if configured to do so
		$filename = Config::item('upload.remove_spaces') ? preg_replace('/\s+/', '_', $data['name']) : $data['name'];

		// Change the filename to a full path name
		$filename = $upload_path.'/'.$filename;

		// Move the upload file to the new location
		move_uploaded_file($data['tmp_name'], $filename);

		if ( ! empty($this->data[$this->current_field]))
		{
			// Conver the returned data into an array
			$this->data[$this->current_field] = array($this->data[$this->current_field]);
		}

		// Set the data to the current field name
		if (is_array($this->data[$this->current_field]))
		{
			$this->data[$this->current_field][] = $filename;
		}
		else
		{
			$this->data[$this->current_field] = $filename;
		}

		return TRUE;
	}

	/**
	 * @param   string  String to check
	 * @param   array   Length
	 * @return  boolean
	 */
	public function required($str, $length = FALSE)
	{
		if ($str === '' OR $str === FALSE OR (is_array($str) AND empty($str)))
		{
			$this->add_error('required', $this->current_field);
			return FALSE;
		}
		elseif ($length != FALSE AND is_array($length))
		{
			if (count($length) > 1)
			{
				// Get the min and max length
				list ($min, $max) = $length;

				// Change length to the length of the string
				$length = utf8::strlen($str);

				// Test min length
				if ($length < $min)
				{
					$this->add_error('min_length', $this->current_field, (int) $min);
					return FALSE;
				}
				// Test max length
				elseif ($length > $max)
				{
					$this->add_error('max_length', $this->current_field, (int) $max);
					return FALSE;
				}
			}
			elseif (strlen($str) !== (int) current($length))
			{
				// Test exact length
				$this->add_error('exact_length', $this->current_field, (int) current($length));
				return FALSE;
			}
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * Match one field to another.
	 *
	 * @param   string  First field
	 * @param   string  Field to match to first
	 * @return  boolean
	 */
	public function matches($field, $match)
	{
		$match = trim(current($match));

		if ((isset($this->data[$field]) AND $this->data[$field] === $this->data[$match])
		OR ( ! isset($this->data[$field]) AND ! isset($this->data[$match])))
		{
			return TRUE;
		}
		else
		{
			$this->add_error('matches', $field, $match);
			return FALSE;
		}
	}

	/**
	 * Check a string for a minimum length.
	 *
	 * @param   string   String to validate
	 * @param   integer  Minimum length
	 * @return  boolean
	 */
	public function min_length($str, $val)
	{
		$val = is_array($val) ? (string) current($val) : FALSE;

		if (ctype_digit($val))
		{
			if (utf8::strlen($str) >= $val)
				return TRUE;
		}

		$this->add_error('min_length', $this->current_field, (int) $val);
		return FALSE;
	}

	/**
	 * Check a string for a maximum length.
	 *
	 * @param   string   String to validate
	 * @param   integer  Maximum length
	 * @return  boolean
	 */
	public function max_length($str, $val)
	{
		$val = is_array($val) ? (string) current($val) : FALSE;

		if (ctype_digit($val))
		{
			if (utf8::strlen($str) <= $val)
				return TRUE;
		}

		$this->add_error('max_length', $this->current_field, (int) $val);
		return FALSE;
	}

	/**
	 * Check a string for an exact length.
	 *
	 * @param   string   String to validate
	 * @param   integer  Length
	 * @return  boolean
	 */
	public function exact_length($str, $val)
	{
		$val = is_array($val) ? (string) current($val) : FALSE;

		if (ctype_digit($val))
		{
			if (utf8::strlen($str) == $val)
				return TRUE;
		}

		$this->add_error('exact_length', $this->current_field, (int) $val);
		return FALSE;
	}

	/**
	 * Validate URL.
	 *
	 * @param   string  URL
	 * @param   string  Scheme
	 * @return  boolean
	 */
	public function valid_url($url, $scheme = '')
	{
		if (empty($scheme))
		{
			$scheme = 'http';
		}

		if (is_array($scheme))
		{
			$scheme = current($scheme);
		}

		if (valid::url($url, $scheme))
			return TRUE;

		$this->add_error('valid_url', $this->current_field, $url);
		return FALSE;
	}

	/**
	 * Valid Email, Commonly used characters only.
	 *
	 * @param   string  Email address
	 * @return  boolean
	 */
	public function valid_email($email)
	{
		if (valid::email($email))
			return TRUE;

		$this->add_error('valid_email', $this->current_field);
		return FALSE;
	}

	/**
	 * Valid Email, RFC compliant version
	 *
	 * @param   string  Email address
	 * @return  boolean
	 */
	public function valid_email_rfc($email)
	{
		if (valid::email_rfc($email))
			return TRUE;

		$this->add_error('valid_email', $this->current_field);
		return FALSE;
	}

	/**
	 * Validate IP Address.
	 *
	 * @param   string  IP address
	 * @return  boolean
	 */
	public function valid_ip($ip)
	{
		if (valid::ip($ip))
			return TRUE;

		$this->add_error('valid_ip', $this->current_field);
		return FALSE;
	}

	/**
	 * Alphabetic characters only.
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function alpha($str)
	{
		if (valid::alpha($str))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.alpha'));
		return FALSE;
	}

	/**
	 * Alphabetic characters only (UTF-8 compatible).
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function utf8_alpha($str)
	{
		if (valid::alpha($str, TRUE))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.alpha'));
		return FALSE;
	}

	/**
	 * Alphabetic and numeric characters only.
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function alpha_numeric($str)
	{
		if (valid::alpha_numeric($str))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.alpha_numeric'));
		return FALSE;
	}

	/**
	 * Alphabetic and numeric characters only (UTF-8 compatible).
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function utf8_alpha_numeric($str)
	{
		if (valid::alpha_numeric($str, TRUE))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.alpha_numeric'));
		return FALSE;
	}

	/**
	 * Alpha-numeric with underscores and dashes.
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function alpha_dash($str)
	{
		if (valid::alpha_dash($str))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.alpha_dash'));
		return FALSE;
	}

	/**
	 * Alpha-numeric with underscores and dashes (UTF-8 compatible).
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function utf8_alpha_dash($str)
	{
		if (valid::alpha_dash($str, TRUE))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.alpha_dash'));
		return FALSE;
	}

	/**
	 * Digits 0-9, no dots or dashes.
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function digit($str)
	{
		if (valid::digit($str))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.digit'));
		return FALSE;
	}

	/**
	 * Digits 0-9, no dots or dashes (UTF-8 compatible).
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function utf8_digit($str)
	{
		if (valid::digit($str, TRUE))
			return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.digit'));
		return FALSE;
	}

	/**
	 * Digits 0-9 (negative and decimal numbers allowed).
	 *
	 * @param   string  String to validate
	 * @return  boolean
	 */
	public function numeric($str)
	{
		if (valid::numeric($str))
		    return TRUE;

		$this->add_error('valid_type', $this->current_field, Kohana::lang('validation.numeric'));
		return FALSE;
	}

	/**
	 * Test that a field is between a range.
	 *
	 * @param   integer  Number to validate
	 * @param   array    Renges
	 * @return  boolean
	 */
	public function range($num, $ranges)
	{
		if (is_array($ranges) AND ! empty($ranges))
		{
			// Number is always an integer
			$num = (float) $num;

			foreach($ranges as $range)
			{
				list($low, $high) = explode(':', $range, 2);

				if ($low == 'FALSE' AND $num <= (float) $high)
					return TRUE;

				if ($high == 'FALSE' AND $num >= (float) $low)
					return TRUE;

				if ($num >= (float) $low AND $num <= (float) $high)
					return TRUE;
			}
		}

		$this->add_error('range', $this->current_field);
		return FALSE;
	}

	/**
	 * Check dependency between fields.
	 * 
	 * @param   string  First field
	 * @param   string  Field which the first field is depend on it
	 * @return  boolean
	 */
	public function depends_on($field, $depends_on)
	{
		$depends_on = trim(current($depends_on));

		if ($depends_on != NULL AND isset($this->data[$field]) AND isset($this->data[$depends_on]))
		{
			return TRUE;
		}

		$depends_on = isset($this->fields[$depends_on]) ? $this->fields[$depends_on] : $depends_on;

		$this->add_error('depends_on', $field, $depends_on);
		return FALSE;
	}

	/**
	 * Test a field against a regex rule
	 *
	 * @param   string  String to test
	 * @param   string  Regular expression to run
	 * @return  boolean
	 */
	public function regex($str, $regex)
	{
		if ( ! empty($regex))
		{
			// Only one regex validation per field
			$regex = current($regex);

			if (preg_match($regex, $str))
			{
				// Regex matches, return
				return TRUE;
			}
		}

		$this->add_error('regex', $this->current_field);
		return FALSE;
	}

	/**
	 * This function allows HTML to be safely shown in a form.
	 * Special characters are converted.
	 *
	 * @param   string  HTML
	 * @return  string  Prepped HTML
	 */
	public function prep_for_form($str = '')
	{
		if ($this->form_safe == FALSE OR $str == '')
			return $str;

		return html::specialchars($str);
	}

	/**
	 * @param   string  URL
	 * @return  void
	 */
	public function prep_url($str = '')
	{
		if ($str == 'http://' OR $str == '')
		{
			$this->data[$this->current_field] = '';
			return;
		}

		if (substr($str, 0, 7) != 'http://' AND substr($str, 0, 8) != 'https://')
		{
			$str = 'http://'.$str;
		}

		$this->data[$this->current_field] = $str;
	}

	/**
	 * Strip image tags from string.
	 *
	 * @param   string  HTML
	 * @return  void
	 */
	public function strip_image_tags($str)
	{
		$this->data[$this->current_field] = security::strip_image_tags($str);
	}

	/**
	 * XSS clean string.
	 *
	 * @param   string  String to be clean
	 * @return  void
	 */
	public function xss_clean($str)
	{
		$this->data[$this->current_field] = Kohana::instance()->input->xss_clean($str);
	}

	/**
	 * Convert PHP tags to entities.
	 *
	 * @param   string
	 * @return  void
	 */
	public function encode_php_tags($str)
	{
		$this->data[$this->current_field] = security::encode_php_tags($str);
	}

} // End Validation Class
