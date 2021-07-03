<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\core;

class common
{

	/**
	 * Constructor
	*/

	protected $filesystem;
	protected $language;
	protected $phpbb_log;
	protected $phpbb_root_path;
	protected $user;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\language\language 	$language 			Language object
	 * @param string					$phpbb_root_path	Relative path to phpBB root
	 * @param \phpbb\filesystem		 	$filesystem			Filesystem object
	 * @param \phpbb\log\log 			$phpbb_log 			phpBB log object
	 * @param \phpbb\user 				$user 				The user object
	 */

	public function __construct(\phpbb\language\language $language, $phpbb_root_path, \phpbb\filesystem\filesystem $filesystem, \phpbb\log\log $phpbb_log, \phpbb\user $user)
	{
		$this->filesystem = $filesystem;
		$this->language = $language;
		$this->phpbb_log = $phpbb_log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->user = $user;
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

	public function validate_iso_date($date)
	{

		// Validates an ISO-8601 date string. Found here: https://stackoverflow.com/questions/8003446/php-validate-iso-8601-date-string.
		// This version does not require Zulu or use gmmktime, or require T. Example: 2018-12-19 14:00:00
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $date, $parts) == true)
		{
			$time = mktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);

			$input_time = strtotime($date);
			if ($input_time === false)
			{
				return false;
			}
			return $input_time == $time;
		}
		else
		{
			return false;
		}

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

	public function make_directories()
	{
		// Makes the store/phpbbservices/digest directory. If they are successfully created, returns true. If they
		// cannot be created (likely due to permission issues), returns false.

		if (!$this->filesystem->exists($this->phpbb_root_path . 'store/phpbbservices'))
		{
			try
			{
				$this->filesystem->mkdir($this->phpbb_root_path . 'store/phpbbservices', '0777');
			}
			catch (\Exception $e)
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_EXCEPTION_ERROR', false, array($e->getMessage()));
				return false;
			}
		}

		if (!$this->filesystem->exists($this->phpbb_root_path . 'store/phpbbservices/digests'))
		{
			try
			{
				$this->filesystem->mkdir($this->phpbb_root_path . 'store/phpbbservices/digests', '0777');
			}
			catch (\Exception $e)
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_EXCEPTION_ERROR', false, array($e->getMessage()));
				return false;
			}
		}

		return true;

	}

}
