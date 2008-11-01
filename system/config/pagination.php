<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * @package  Pagination
 *
 * Views folder in which your pagination style templates reside.
 */
$config['directory'] = 'pagination';

/**
 * Style name (matches template filename).
 */
$config['style'] = 'classic';

/**
 * URI segment (or 'label') in which the current page number can be found.
 */
$config['uri_segment'] = 3;

/**
 * Number of items in a page of results.
 */
$config['items_per_page'] = 20;