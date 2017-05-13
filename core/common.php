<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\core;

class common
{

	/**
	 * Constructor
	*/

	public function __construct()
	{
		global $phpbb_container;

		$this->language = $phpbb_container->get('language');
	}

	public function make_hour_string($hour, $user_dateformat)
	{

		// This function returns a string representing an hour (0-23) for display. It attempts to be smart by looking at
		// the user's date format and determining whether it supports AM/PM or not.

		static $display_hour_array_am_pm = array(12,1,2,3,4,5,6,7,8,9,10,11,12,1,2,3,4,5,6,7,8,9,10,11);

		// Is AM/PM expected?
		$use_lowercase_am_pm = strstr($user_dateformat,'a');
		$use_uppercase_am_pm = strstr($user_dateformat,'A');
		$twenty_four_hour_time = !($use_lowercase_am_pm || $use_uppercase_am_pm);

		if ($twenty_four_hour_time)
		{
			return $hour . $this->language->lang('DIGESTS_HOURS_ABBREVIATION');
		}
		else
		{
			$am_pm = ($hour < 12) ? $this->language->lang('DIGESTS_AM') : $this->language->lang('DIGESTS_PM');
			if ($use_uppercase_am_pm)
			{
				return $display_hour_array_am_pm[$hour] . strtoupper($am_pm);
			}
			else
			{
				return $display_hour_array_am_pm[$hour] . strtolower($am_pm);
			}
		}

	}

	public function make_tz_offset ($tz_text, $show_sign = false)
	{
		// This function translates a text timezone (like America/New_York) to an hour offset from GMT, doing magic like figuring out DST
		$tz = new \DateTimeZone($tz_text);
		$datetime_tz = new \DateTime('now', $tz);
		$timeOffset = $tz->getOffset($datetime_tz) / 3600;
		return ($show_sign && $timeOffset >= 0) ? '+' . $timeOffset : $timeOffset;
	}

	public function date_loc($fmt, $datetime = null)
	{
		// no datetime given - use current time
		if ($datetime === null)
		{
			$datetime = time();
		}

		// prepare localized names of weekdays and months
		$weekdays_long = explode(',', $this->language->lang('DIGESTS_DAYS_LONG'));
		$weekdays_short = explode(',', $this->language->lang('DIGESTS_DAYS_SHORT'));
		$months_long = explode(',', $this->language->lang('DIGESTS_MONTHS_LONG'));
		$months_short = explode(',', $this->language->lang('DIGESTS_MONTHS_SHORT'));

		// get current day of week, prepare its escaped string - long and short version
		$weekday = date('N', $datetime);
		$weekday_long = preg_replace('/(.)/', '\\\$1', $weekdays_long[$weekday-1]);
		$weekday_short = preg_replace('/(.)/', '\\\$1', $weekdays_short[$weekday-1]);

		// get current day of month, prepare its string - long and short version4
		// the string is escaped by a backslash to avoid expanding its letters as placeholders
		$month = date('n', $datetime);
		$month_long = preg_replace('/(.)/', '\\\$1', $months_long[$month-1]);
		$month_short = preg_replace('/(.)/', '\\\$1', $months_short[$month-1]);

		// in the formatting string, replace placeholders for localized weekdays and months
		// avoid formating for characters preceded by a backslash - these are supposed to be printed as text
		$fmt = preg_replace('/(?<!\\\)(l)/', $weekday_long, $fmt);
		$fmt = preg_replace('/(?<!\\\)(D)/', $weekday_short, $fmt);
		$fmt = preg_replace('/(?<!\\\)(F)/', $month_long, $fmt);
		$fmt = preg_replace('/(?<!\\\)(M)/', $month_short, $fmt);

		return date($fmt, $datetime);
	}


}
