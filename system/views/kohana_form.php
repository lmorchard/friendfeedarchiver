<?php echo form::open($action) ?>

<h4><?php echo $title ?></h4>

<ul>
<?php

foreach($inputs as $name => $data):

	// Generate label
	$label = empty($data['label']) ? '' : form::label($name, arr::remove('label', $data))."\n";

	// Generate error
	$error = arr::remove('error', $data);

	// Set input name and id
	$data['name'] = $name;

	if ( ! empty($data['options']))
	{
		// Get options and selected
		$options  = arr::remove('options', $data);
		$selected = arr::remove('selected', $data);
		// Generate dropdown
		$input = form::dropdown($data, $options, $selected);
	}
	else
	{
		switch(@$data['type'])
		{
			case 'textarea':
				// Remove the type, textarea doesn't need it
				arr::remove('type', $data);
				// Generate a textarea
				$input = form::textarea($data);
			break;
			case 'submit':
				// Generate a submit button
				$input = form::button($data);
			break;
			default:
				// Generate a generic input
				$input = form::input($data);
			break;
		}
	}
?>
<li>
<?php echo $label.$input.$error; ?>
</li>
<?php endforeach; ?>
</ul>
<?php echo form::close() ?>
