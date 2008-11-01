<style type="text/css">
#kohana-profiler
{
	font-family: Monaco, 'Courier New';
	background-color: #F8FFF8;
	margin-top: 20px;
	clear: both;
	padding: 10px 10px 0;
	border: 1px solid #E5EFF8;
	text-align: left;
}
#kohana-profiler pre
{
	margin: 0;
	font: inherit;
}
#kohana-profiler table
{
	font-size: 1.0em;
	color: #4D6171;
	width: 100%;
	border-collapse: collapse;
	border-top: 1px solid #E5EFF8;
	border-right: 1px solid #E5EFF8;
	border-left: 1px solid #E5EFF8;
	margin-bottom: 10px;
}
#kohana-profiler th
{
	text-align: left;
	border-bottom: 1px solid #E5EFF8;
	background-color: #F9FCFE;
	padding: 3px;
	color: #263038;
}
#kohana-profiler td
{
	background-color: #FFFFFF;
	border-bottom: 1px solid #E5EFF8;
	padding: 3px;
}
#kohana-profiler .kp-altrow td
{
	background-color: #F7FBFF;
}
#kohana-profiler .kp-totalrow td
{
	background-color: #FAFAFA;
	border-top: 1px solid #D2DCE5;
	font-weight: bold;
}
#kp-benchmarks th
{
	background-color: #FFE0E0;
}
#kp-queries th
{
	background-color: #E0FFE0;
}
#kp-postdata th
{
	background-color: #E0E0FF;
}
#kp-sessiondata th
{
	background-color: #CCE8FB;
}
#kp-cookiedata th
{
	background-color: #FFF4D7;
}
#kohana-profiler .kp-column
{
	width: 100px;
	border-left: 1px solid #E5EFF8;
	text-align: center;
}
#kohana-profiler .kp-data, #kohana-profiler .kp-name
{
	background-color: #FAFAFB;
	vertical-align: top;
}
#kohana-profiler .kp-name
{
	width: 200px;
	border-right: 1px solid #E5EFF8;
}
#kohana-profiler .kp-altrow .kp-data, #kohana-profiler .kp-altrow .kp-name
{
	background-color: #F6F8FB;
}
</style>
<div id="kohana-profiler">

<?php if (isset($benchmarks)): ?>
	<table id="kp-benchmarks">
		<tr>
			<th><?php echo Kohana::lang('profiler.benchmarks') ?></th>
			<th class="kp-column">Time</th>
			<th class="kp-column">Memory</th>
		</tr>
<?php

// Moves the first benchmark (total execution time) to the end of the array
$benchmarks = array_slice($benchmarks, 1) + array_slice($benchmarks, 0, 1);

foreach ($benchmarks as $name => $benchmark):

	$class = ($name == 'total_execution') ? ' class="kp-totalrow"' : text::alternate('', ' class="kp-altrow"');
	$name = ucwords(str_replace(array('_', '-'), ' ', $name));

?>
		<tr<?php echo $class ?>>
			<td><?php echo $name ?></td>
			<td class="kp-column kp-data"><?php echo number_format($benchmark['time'], 4) ?></td>
			<td class="kp-column kp-data"><?php echo number_format($benchmark['memory'] / 1024 / 1024, 2) ?> MB</td>
		</tr>
<?php

endforeach;

?>
	</table>
<?php endif; ?>

<?php if (isset($queries)): ?>
	<table id="kp-queries">
		<tr>
			<th><?php echo Kohana::lang('profiler.queries') ?></th>
			<th class="kp-column">Time</th>
			<th class="kp-column">Rows</th>
		</tr>
<?php

if ($queries === FALSE):

?>
		<tr><td colspan="3"><?php echo Kohana::lang('profiler.no_database') ?></td></tr>
<?php

else:

	if (count($queries) == 0):

?>
		<tr><td colspan="3"><?php echo Kohana::lang('profiler.no_queries') ?></td></tr>
<?php

	else:
		text::alternate();
		$total_time = 0;
		foreach($queries as $query):
			$total_time += $query['time'];
?>
		<tr<?php echo text::alternate('', ' class="kp-altrow"') ?>>
			<td><?php echo html::specialchars($query['query']) ?></td>
			<td class="kp-column kp-data"><?php echo number_format($query['time'], 4) ?></td>
			<td class="kp-column kp-data"><?php echo $query['rows'] ?></td>
		</tr>
<?php

		endforeach;
?>
		<tr class="kp-totalrow">
			<td>Total: <?php echo count($queries) ?></td>
			<td class="kp-column kp-data"><?php echo number_format($total_time, 4) ?></td>
			<td class="kp-column kp-data">&nbsp;</td>
		</tr>
<?php

	endif;
endif;

?>
	</table>
<?php endif; ?>

<?php if (isset($post)): ?>
	<table id="kp-postdata">
		<tr>
			<th colspan="2"><?php echo Kohana::lang('profiler.post_data') ?></th>
		</tr>
<?php

if (count($_POST) == 0):

?>
		<tr><td colspan="2"><?php echo Kohana::lang('profiler.no_post') ?></td></tr>
<?php

else:
	text::alternate();
	foreach($_POST as $name => $value):

?>
		<tr<?php echo text::alternate('', ' class="kp-altrow"') ?>>
			<td class="kp-name"><?php echo $name ?></td>
			<td>
				<?php echo (is_array($value)) ? '<pre>'.html::specialchars(print_r($value, TRUE)).'</pre>' : html::specialchars($value) ?>
			</td>
		</tr>
<?php

	endforeach;
endif;

?>
	</table>
<?php endif; ?>

<?php if (isset($session)): ?>
	<table id="kp-sessiondata">
		<tr>
			<th colspan="2"><?php echo Kohana::lang('profiler.session_data') ?></th>
		</tr>
<?php

if ( ! isset($_SESSION)):

?>
		<tr><td colspan="2"><?php echo Kohana::lang('profiler.no_session') ?></td></tr>
<?php

else:
	text::alternate();
	foreach($_SESSION as $name => $value):

?>
		<tr<?php echo text::alternate('', ' class="kp-altrow"') ?>>
			<td class="kp-name"><?php echo $name ?></td>
			<td>
				<?php echo (is_array($value) OR is_object($value)) ? '<pre>'.html::specialchars(print_r($value, TRUE)).'</pre>' : html::specialchars($value) ?>
			</td>
		</tr>
<?php

	endforeach;
endif;

?>
	</table>
<?php endif; ?>

<?php if (isset($cookie)): ?>
	<table id="kp-cookiedata">
		<tr>
			<th colspan="2"><?php echo Kohana::lang('profiler.cookie_data') ?></th>
		</tr>
<?php

if (count($_COOKIE) == 0):

?>
		<tr><td colspan="2"><?php echo Kohana::lang('profiler.no_cookie') ?></td></tr>
<?php

else:
	text::alternate();
	foreach($_COOKIE as $name => $value):

?>
		<tr<?php echo text::alternate('', ' class="kp-altrow"') ?>>
			<td class="kp-name"><?php echo $name ?></td>
			<td>
				<?php echo (is_array($value)) ? '<pre>'.html::specialchars(print_r($value, TRUE)).'</pre>' : html::specialchars($value) ?>
			</td>
		</tr>
<?php

	endforeach;
endif;

?>
	</table>
<?php endif; ?>

</div>