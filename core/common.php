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
		// This function translates a text timezone (like America/New_York) to an hour offset from UTC, doing magic like figuring out if DST applies
		if (!$this->validate_date($tz_text))
		{
			// Date string is invalid so assume UTC
			$timeOffset = 0;
		}
		else
		{
			$tz = new \DateTimeZone($tz_text);
			$datetime_tz = new \DateTime('now', $tz);
			$timeOffset = $tz->getOffset($datetime_tz) / 3600;
		}
		return ($show_sign && $timeOffset >= 0) ? '+' . $timeOffset : $timeOffset;
	}

	public function validate_date($date)
	{
		// This functions checks to see if a date format (like America/New_York) is valid. If not, it returns false.
		$d = \DateTime::createFromFormat('e', $date);
		return $d && $d->format('e') === $date;
	}

	public function check_send_hour($hour)
	{
		// Ensures an hour falls between 0 and 24, adjusts if outside the range.
		if ($hour >= 24)
		{
			return (float) ($hour - 24);
		}
		else if ($hour < 0)
		{
			return (float) ($hour + 24);
		}
		else
		{
			return (float) $hour;
		}
	}

}
