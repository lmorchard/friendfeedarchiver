<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * @package  Core
 *
 * Supported Shortcuts:
 *  :any - matches any non-blank string
 *  :num - matches any number
 */

/**
 * Permitted URI characters. Note that "?", "#", and "=" are URL characters, and
 * should not be added here.
 */
$config['_allowed'] = '-a-z 0-9~%.,:_';

/**
 * Default route to use when no URI segments are available.
 */
$config['_default'] = 'welcome';