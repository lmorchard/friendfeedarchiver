<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * @package  Core
 *
 * Supported Shortcuts:
 *  :any - matches any non-blank string
 *  :num - matches any number
 */

$config['update/(.*)'] =
    'main/update/$1';

$config['update'] =
    'main/update';

$config['(.*)/(.*)/(.*)/(.*)'] = 
    'main/entries_date/$1/$2/$3/$4';

$config['(.*)/dates'] = 
    'main/entries_index/$1';

$config['(.*)'] = 
    'main/entries_date/$1';

/**
 * Permitted URI characters. Note that "?", "#", and "=" are URL characters, and
 * should not be added here.
 */
$config['_allowed'] = '-a-z 0-9~%.,:_';

/**
 * Default route to use when no URI segments are available.
 */
$config['_default'] = 'main';
