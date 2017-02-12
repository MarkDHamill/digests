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
	}
	
	public function make_hour_string($hour, $user_dateformat)
	{
		
		// This function returns a string representing an hour (0-23) for display. It attempts to be smart by looking at 
		// the user's date format and determining whether they support AM/PM or not. Some countries (like France) display
		// 24 hour time.
		
		static $display_hour_array_am_pm = array(12,1,2,3,4,5,6,7,8,9,10,11,12,1,2,3,4,5,6,7,8,9,10,11);
		
		// Is AM/PM expected?
		$use_lowercase_am_pm = strstr($user_dateformat,'a');
		$use_uppercase_am_pm = strstr($user_dateformat,'A');
		if ($use_lowercase_am_pm)
		{
			$am = ' am';
			$pm = ' pm';
		}
		else if ($use_uppercase_am_pm)
		{
			$am = ' AM';
			$pm = ' PM';
		}
		else // 24 hour time wanted
		{
			$am = '';
			$pm = '';
		}
		
		$suffix = ($hour < 12) ? $am : $pm;
		$display_hour = ($use_lowercase_am_pm || $use_uppercase_am_pm) ? $display_hour_array_am_pm[$hour] : $hour;
		
		return $display_hour . $suffix;
		
	}

	public function make_tz_offset ($tz_text)
	{
		// This function translates a text timezone (like America/New York) to an hour offset from GMT, doing magic like figuring out DST
		$tz = new \DateTimeZone($tz_text);
		$datetime_tz = new \DateTime('now', $tz);
		$timeOffset = $tz->getOffset($datetime_tz) / 3600;
		return $timeOffset;
	}
	
}
