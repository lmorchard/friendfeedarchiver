<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE hidden input library.
 *
 * $Id: Form_Hidden.php 2746 2008-06-05 18:02:16Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Form_Hidden_Core extends Form_Input {

	protected $data = array
	(
		'value' => '',
	);

	public function html()
	{
		return form::hidden($this->data['name'], $this->data['value']);
	}

} // End Form Hidden