<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana - The Swift PHP Framework
 *
 *  License:
 *  author    - Kohana Team
 *  copyright - (c) 2007 Kohana Team
 *  license   - <http://kohanaphp.com/license.html>
 */

/**
 * Handles loading of site resources (CSS, JS, images, etc) using Views.
 * By default it is assumed that your media files will be stored in
 * `application/views/media`.
 *
 * Usage:
 *  `http://example.com/index.php/media/css/styles.css`
 */
class Media_Controller extends Controller {

	public function _remap()
	{
		try
		{
			// Find the filename and extension based on the URI
			list ($file, $type) = preg_split('/\.(?=[^.]++$)/', $this->uri->string());

			// Attempt to load the resource using a view
			echo new View($file, NULL, $type);
		}
		catch (Kohana_Exception $e)
		{
			// View file was not found, trigger a 404!
			Event::run('system.404');
		}
	}

} // End Media_Controller