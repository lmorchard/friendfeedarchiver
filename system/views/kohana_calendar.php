<?php

$first_day = mktime(1, 0, 0, $month, 1, $year);
$today = (date('Y/m') === date('Y/m', $first_day)) ? date('j') : FALSE;

/**
 * @todo This needs to be moved to an i18n file, or be configured in the library.
 */
$headings = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

/**
 * @todo This needs to be removed and placed in documentation. Inline styles are not allowed.
 */
?><style type="text/css">
table.calendar { text-align: right; }
table.calendar caption { font-size: 1.5em; padding: 0.2em; }
table.calendar th, table.calendar td { padding: 0.2em; background: #fff; border: 0; }
table.calendar td:hover { background: #ddf; }
table.calendar td.prev-next { background: #ccc; color: #999; }
table.calendar td.today { color: #800; }
</style>

<table class="calendar">
<caption><?php echo strftime('%B %Y', $first_day) ?></caption>
<tr>
<?php foreach ($headings as $day): ?>
<th><?php echo $day ?></th>
<?php endforeach ?>
</tr>
<?php foreach ($weeks as $week): ?>
<tr>
<?php foreach ($week as $day): ?>
<?php if ($day[1] === FALSE): ?>
<td class="prev-next"><?php echo $day[0] ?></td>
<?php else: ?>
<?php

if ($today > 0 AND $day[0] == $today)
{
	$class = ' class="today"';
}
else
{
	$class = '';
}
/**
 * @todo Need to add assignable stuff to this. For example, making certain dates into links.
 */
?>
<td<?php echo $class ?>><?php echo $day[0] ?></td>
<?php endif; ?>
<?php endforeach ?>
</tr>
<?php endforeach ?>
</table>
