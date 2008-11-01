<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package  Encrypt
 *
 * Encryption key used to do encryption and decryption. The default option
 * should never be used for a production website.
 *
 * For best security, your encryption key should be at least 16 characters
 * long and contain letters, numbers, and symbols.
 * @note Do not use a hash as your key. This significantly lowers encryption entropy.
 */
$config['key'] = 'K0H@NA+PHP_7hE-SW!FtFraM3w0R|<';

/**
 * MCrypt encryption mode. By default, MCRYPT_MODE_NOFB is used. This mode
 * offers initialization vector support, is suited to short strings, and
 * produces the shortest encrypted output.
 * @see http://php.net/mcrypt
 */
$config['mode'] = MCRYPT_MODE_NOFB;

/**
 * MCrypt encryption cipher. By default, the MCRYPT_RIJNDAEL_128 cipher is used.
 * This is also known as 128-bit AES.
 * @see http://php.net/mcrypt
 */
$config['cipher'] = MCRYPT_RIJNDAEL_128;