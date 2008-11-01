<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Calendar creation library.
 *
 * $Id: Calendar.php 1911 2008-02-04 16:13:16Z PugFish $
 *
 * @package    Calendar
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Calendar_Core {

	// Month and year to use for calendaring
	protected $month;
	protected $year;

	// Start the calendar on Sunday by default
	public $week_start = 0;

	// Event data
	public $events;

	/**
	 * Create a new Calendar instance. A month and year can be specified.
	 * By default, the current month and year are used.
	 *
	 * @param   integer  month number
	 * @param   integer  year number
	 * @return  void
	 */
	public function __construct($month = NULL, $year = NULL, $start_monday = NULL)
	{
		empty($month) and $month = date('n'); // Current month
		empty($year)  and $year  = date('Y'); // Current year

		// Set the month and year
		$this->month = (int) $month;
		$this->year  = (int) $year;

		// Some locales start the week on Monday, not Sunday.
		($start_monday === TRUE) and $this->week_start = 1;
	}

	public function events()
	{
		if (func_num_args() === 0)
			return;

		$this->events = func_get_args();
	}

	/**
	 * Returns an array for use with a view. The array contains an array for
	 * each week. Each week contains 7 arrays, with a day number and status:
	 * TRUE if the day is in the month, FALSE if it is padding.
	 *
	 * @return  array
	 */
	public function weeks()
	{
		// First day of the month as a timestamp
		$first = mktime(1, 0, 0, $this->month, 1, $this->year);

		// Total number of days in this month
		$total = (int) date('t', $first);

		// Last day of the month as a timestamp
		$last  = mktime(1, 0, 0, $this->month, $total, $this->year);

		// Make the month and week empty arrays
		$month = $week = array();

		// Number of days added. When this reaches 7, start a new month
		$days = 0;

		if (($w = (int) date('w', $first)) > $this->week_start)
		{
			// Number of days in the previous month
			$n = (int) date('t', mktime(1, 0, $this->month - 1, 1, $this->year));

			// i = number of day, t = number of days to pad
			for($i = $n - $w, $t = $w - $this->week_start; $t > 0; $t--, $i++)
			{
				// Add previous month padding days
				$week[] = array($i, FALSE);
				$days++;
			}
		}

		// i = number of day
		for ($i = 1; $i <= $total; $i++)
		{
			if ($days % 7 === 0)
			{
				// Start a new week
				$month[] = $week;
				$week = array();
			}

			// Add days to this month
			$week[] = array($i, TRUE);
			$days++;
		}

		if (($w = (int) date('w', $last) - $this->week_start) < 6 )
		{
			// i = number of day, t = number of days to pad
			for ($i = 1, $t = 6 - $w; $t > 0; $t--, $i++)
			{
				// Add next month padding days
				$week[] = array($i, FALSE);
			}

			$month[] = $week;
		}

		return $month;
	}

	/**
	 * Convert the calendar to HTML using the kohana_calendar view.
	 *
	 * @return  string
	 */
	public function render()
	{
		$view =  new View('kohana_calendar', array
		(
			'month'  => $this->month,
			'year'   => $this->year,
			'weeks'  => $this->weeks(),
			'events' => $this->events,
		));

		return $view->render();
	}

	/**
	 * Magically convert this object to a string, the rendered calendar.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->render();
	}

} // End Calendar