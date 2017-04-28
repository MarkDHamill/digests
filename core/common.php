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

	public function dateFormatToStrftime($dateFormat, $lang="en") {

		/*
		* Convert a date format to a strftime format
		*
		* Timezone conversion is done for unix. Windows users must exchange %z and %Z.
		*
		* Unsupported date formats : n, t, L, B, G, u, e, I, P, Z, c, r
		* Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
		*
		* @param string $dateFormat a date format
		* @param strong $lang a language, like en or fr_FR
		* @return string
		*/
		$caracs = array(
			// Day - no strf eq : S
			'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j', 'S' => '',
			// Week - no date eq : %U, %W
			'W' => '%V',
			// Month - no strf eq : n, t
			'F' => '%B', 'm' => '%m', 'M' => '%b',
			// Year - no strf eq : L; no date eq : %C, %g
			'o' => '%G', 'Y' => '%Y', 'y' => '%y',
			// Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
			'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S',
			// Timezone - no strf eq : e, I, P, Z
			'O' => '%z', 'T' => '%Z',
			// Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
			'U' => '%s'
		);

		if (strlen($lang) == 2)
		{
			$locale = trim($lang) . '_' . strtoupper($lang);
		}
		else
		{
			$locale = $lang;
		}
		setlocale(LC_ALL, $locale);
		return strtr((string)$dateFormat, $caracs);
	}

}
