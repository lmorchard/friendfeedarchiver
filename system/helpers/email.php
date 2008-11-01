<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Email helper class.
 *
 * $Id: email.php 2001 2008-02-08 21:11:26Z PugFish $
 *
 * @package    Email Helper
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class email_Core {

	// SwiftMailer instance
	protected static $mail;

	/**
	 * Creates a SwiftMailer instance.
	 *
	 * @param   string  DSN connection string
	 * @return  object  Swift object
	 */
	public static function connect($config = NULL)
	{
		// Load default configuration
		($config === NULL) and $config = Config::item('email');

		if ( ! class_exists('Swift', FALSE))
		{
			// Load SwiftMailer
			require_once Kohana::find_file('vendor', 'swift/Swift');

			// Register the Swift ClassLoader as an autoload
			spl_autoload_register(array('Swift_ClassLoader', 'load'));
		}

		switch ($config['driver'])
		{
			case 'smtp':
				// Create a SMTP connection
				$connection = new Swift_Connection_SMTP
				(
					$config['options']['hostname'],
					empty($config['options']['port']) ? 25 : $config['options']['port']
				);

				// Do authentication, if part of the DSN
				empty($config['options']['username']) or $connection->setUsername($config['options']['username']);
				empty($config['options']['password']) or $connection->setPassword($config['options']['password']);

				// Set the timeout to 5 seconds
				$connection->setTimeout(5);
			break;
			case 'sendmail':
				// Create a sendmail connection
				$connection = new Swift_Connection_Sendmail
				(
					empty($config['options']) ? Swift_Connection_Sendmail::AUTO_DETECT : $config['options']
				);

				// Set the timeout to 5 seconds
				$connection->setTimeout(5);
			break;
			default:
				// Use the native connection
				$connection = new Swift_Connection_NativeMail;
			break;
		}

		// Create the SwiftMailer instance
		return self::$mail = new Swift($connection);
	}

	/**
	 * Send an email message.
	 *
	 * @param   string|array  recipient email (and name)
	 * @param   string|array  sender email (and name)
	 * @param   string        message subject
	 * @param   string        message body
	 * @param   boolean       send email as HTML
	 * @return  integer       number of emails sent
	 */
	public static function send($to, $from, $subject, $message, $html = FALSE)
	{
		// Connect to SwiftMailer
		(self::$mail === NULL) and email::connect();

		// Determine the message type
		$html = ($html === TRUE) ? 'text/html' : 'text/plain';

		// Create the message
		$message = new Swift_Message($subject, $message, $html, '8bit', 'utf-8');

		// Make a personalized To: address
		$to = is_array($to) ? new Swift_Address($to[0], $to[1]) : new Swift_Address($to);

		// Make a personalized From: address
		$from = is_array($from) ? new Swift_Address($from[0], $from[1]) : new Swift_Address($from);

		return self::$mail->send($message, $to, $from);
	}

} // End email