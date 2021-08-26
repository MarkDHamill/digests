<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\core;

use phpbbservices\digests\constants\constants;

class common
{

	/**
	 * Constructor
	*/

	protected $config;
	protected $db;
	protected $filesystem;
	protected $language;
	protected $phpbb_log;
	protected $phpbb_root_path;
	protected $phpEx;
	protected $user;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config 		$config 			The config
	 * @param \phpbb\db\driver\factory 	$db 				The database factory object
	 * @param \phpbb\filesystem		 	$filesystem			Filesystem object
	 * @param \phpbb\language\language 	$language 			Language object
	 * @param \phpbb\log\log 			$phpbb_log 			phpBB log object
	 * @param string					$phpbb_root_path	Relative path to phpBB root
	 * @param string 					$php_ext 			PHP file suffix
	 * @param \phpbb\user 				$user 				The user object
	 *
	 */

	public function __construct(\phpbb\language\language $language, $phpbb_root_path, \phpbb\filesystem\filesystem $filesystem, \phpbb\log\log $phpbb_log, \phpbb\user $user, $php_ext, \phpbb\config\config $config, \phpbb\db\driver\factory $db)
	{
		$this->config = $config;
		$this->db = $db;
		$this->filesystem = $filesystem;
		$this->language = $language;
		$this->phpbb_log = $phpbb_log;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $php_ext;
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

	function notify_subscribers ($digest_notify_list, $email_template = '')
	{

		// This function parses $digest_notify_list, an array of user_ids that represent users that had their digest subscriptions changed, and sends them an email
		// letting them know an action has occurred.

		$emails_sent = 0;

		if (isset($digest_notify_list) && (count($digest_notify_list) > 0))
		{

			if (!class_exists('messenger'))
			{
				include($this->phpbb_root_path . 'includes/functions_messenger.' . $this->phpEx); // Used to send emails
			}

			$sql_array = array(
				'SELECT'	=> 'username, user_email, user_lang, user_digest_type, user_digest_format',

				'FROM'		=> array(
					USERS_TABLE	=> 'u',
				),

				'WHERE'		=> $this->db->sql_in_set('user_id', $digest_notify_list),
			);

			$sql = $this->db->sql_build_query('SELECT', $sql_array);

			$result = $this->db->sql_query($sql);
			$rowset = $this->db->sql_fetchrowset($result);

			// E-mail setup
			$messenger = new \messenger();

			foreach ($rowset as $row)
			{

				switch ($email_template)
				{
					case 'digests_unsubscribe_one_click':
						$digest_notify_template = $email_template;
						$digest_email_subject = $this->language->lang('DIGESTS_UNSUBSCRIBE_SUCCESS');
					break;

					case 'digests_subscription_edited':
						$digest_notify_template = $email_template;
						$digest_email_subject = $this->language->lang('DIGESTS_SUBSCRIBE_EDITED');
					break;

					default:
						// Mass subscribe/unsubscribe
						$digest_notify_template = ($this->config['phpbbservices_digests_subscribe_all']) ? 'digests_subscribe' : 'digests_unsubscribe';
						$digest_email_subject = ($this->config['phpbbservices_digests_subscribe_all']) ? $this->language->lang('DIGESTS_SUBSCRIBE_SUBJECT') : $this->language->lang('DIGESTS_UNSUBSCRIBE_SUBJECT');
					break;
				}

				// Set up associations between digest types as constants and their language equivalents
				switch ($row['user_digest_type'])
				{
					case constants::DIGESTS_WEEKLY_VALUE:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_WEEKLY'));
					break;

					case constants::DIGESTS_MONTHLY_VALUE:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_MONTHLY'));
					break;

					case constants::DIGESTS_NONE_VALUE:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_NONE'));
					break;

					case constants::DIGESTS_DAILY_VALUE:
					default:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_DAILY'));
					break;
				}

				// Set up associations between digest formats as constants and their language equivalents
				switch ($row['user_digest_format'])
				{
					case constants::DIGESTS_HTML_CLASSIC_VALUE:
						$digest_format_text = $this->language->lang('DIGESTS_FORMAT_HTML_CLASSIC');
					break;

					case constants::DIGESTS_PLAIN_VALUE:
						$digest_format_text = $this->language->lang('DIGESTS_FORMAT_PLAIN');
					break;

					case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
						$digest_format_text = $this->language->lang('DIGESTS_FORMAT_PLAIN_CLASSIC');
					break;

					case constants::DIGESTS_TEXT_VALUE:
						$digest_format_text = strtolower($this->language->lang('DIGESTS_FORMAT_TEXT'));
					break;

					case constants::DIGESTS_HTML_VALUE:
					default:
						$digest_format_text = $this->language->lang('DIGESTS_FORMAT_HTML');
					break;
				}

				$messenger->template('@phpbbservices_digests/' . $digest_notify_template, $row['user_lang']);
				$messenger->to($row['user_email']);

				$from_addr = ($this->config['phpbbservices_digests_from_email_address'] == '') ? $this->config['board_email'] : $this->config['phpbbservices_digests_from_email_address'];
				$from_name = ($this->config['phpbbservices_digests_from_email_name'] == '') ? $this->config['board_contact'] : $this->config['phpbbservices_digests_from_email_name'];

				// SMTP delivery must strip text names due to likely bug in messenger class
				if ($this->config['smtp_delivery'])
				{
					$messenger->from($from_addr);
				}
				else
				{
					$messenger->from($from_addr . ' <' . $from_name . '>');
				}

				$messenger->replyto($from_addr);
				$messenger->subject($digest_email_subject);

				$messenger->assign_vars(array(
						'DIGESTS_FORMAT'		=> $digest_format_text,
						'DIGESTS_TYPE'			=> $digest_type_text,
						'DIGESTS_UCP_LINK'		=> generate_board_url() . '/' . 'ucp.' . $this->phpEx,
						'FORUM_NAME'			=> $this->config['sitename'],
						'USERNAME'				=> $row['username'],
					)
				);

				$mail_sent = $messenger->send(NOTIFY_EMAIL, false);

				if (!$mail_sent)
				{
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_NOTIFICATION_ERROR', false, array($row['user_email']));
				}
				else
				{
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_NOTIFICATION_SENT', false, array($row['user_email'], $row['username']));
					$emails_sent++;
				}

				$messenger->reset();

			}

			$messenger->save_queue(); // save queued emails for later delivery, if applicable
			$this->db->sql_freeresult($result); // Query be gone!

		}

		return $emails_sent;

	}

}
