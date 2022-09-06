<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\cron\task;

use phpbbservices\digests\constants\constants;

class digests extends \phpbb\cron\task\base
{

	protected $auth;
	protected $config;
	protected $cpfs;
	protected $db;
	protected $helper;
	protected $language;
	protected $phpbb_dispatcher;
	protected $phpbb_log;
	protected $phpbb_notifications;
	protected $phpbb_root_path;
	protected $phpEx;
	protected $request;
	protected $report_details_table;
	protected $report_table;
	protected $subscribed_forums_table;
	protected $template;
	protected $user;

	// Most of these private variables are needed because the create_content function does much of the assembly work and it needs a lot of common information

	private $board_url;					// Digests need an absolute URL to the forum to embed links to topic, posts, forum and private messages
	private $debug;						// Let's us know if we are in debug mode
	private $date_limit;				// A logical range of dates that posts must be within
	private $email_address_override;	// Used if admin wants manual mailer to send him/her a digest at an email address specified for this run
	private $email_templates_path;		// Relative path to where the language specific email templates are located
	private $forum_hierarchy;			// An array of forum_ids and their parent forum_ids.
	private $layout_with_html_tables;	// Layout posts in the email as HTML tables, similar to the phpBB2 digests mod
	private $list_id;					// Used in determining forum access privileges for a subscriber
	private $max_posts;					// Maximum number of posts in a digest
	private $path_prefix;				// Appended to paths to find files in the correct location
	private $read_id;					// Used in determining forum access privileges for a subscriber
	private $run_mode;					// phpBB (regular) cron, system cron or manual
	private $salutation_fields;			// Contains fields to be used in the salutation, as an array
	private $server_timezone;			// Offset in hours from UTC for server
	private $store_path;				// Relative path to the store directory
	private $time;						// Current time (or requested start time if running an out of cycle digest)
	private $toc;						// Table of contents array
	private $toc_pm_count;				// Table of contents private message count
	private $utc_month_lastday_end;		// The last day of the month when a monthly digest is wanted

	/**
	* Constructor.
	*
	* @param \phpbb\auth\auth 					$auth 					The auth object
	* @param \phpbb\config\config 				$config 				The config
	* @param \phpbb\profilefields\manager		$cpfs					Custom profile fields manager
	* @param \phpbb\db\driver\factory 			$db 					The database factory object
	* @param \phpbbservices\digests\core\common $helper 				Digests helper object
	* @param \phpbb\language\language 			$language 				Language object
	* @param \phpbb\notification\manager 		$notification_manager 	Notifications manager
	* @param string								$php_ext 				PHP file suffix
	* @param \phpbb\event\dispatcher			$phpbb_dispatcher		Dispatcher object
	* @param \phpbb\log\log 					$phpbb_log 				phpBB log object
	* @param string								$phpbb_root_path		Relative path to phpBB root
	* @param string								$report_details_table	Extension's digests report details table
	* @param string								$report_table			Extension's digests report table
	* @param string								$subscribed_forums_table	Extension's subscribed forums table
	* @param \phpbb\request\request 			$request 				The request object
	* @param \phpbb\template\template 			$template 				The template engine object
	* @param \phpbb\user 						$user 					The user object
	*/

	public function __construct(\phpbb\config\config $config, \phpbb\request\request $request, \phpbb\user $user, \phpbb\db\driver\factory $db, string $php_ext, string $phpbb_root_path, \phpbb\template\template $template, \phpbb\auth\auth $auth, \phpbb\log\log $phpbb_log, \phpbb\language\language $language, \phpbb\notification\manager $notification_manager, \phpbbservices\digests\core\common $helper, \phpbb\profilefields\manager $cpfs, \phpbb\event\dispatcher $phpbb_dispatcher, string $subscribed_forums_table, string $report_table, string $report_details_table)
	{

 	 	$this->auth = $auth;
		$this->config = $config;
		$this->cpfs = $cpfs;
		$this->db = $db;
		$this->helper = $helper;
		$this->language = $language;
		$this->phpbb_dispatcher = $phpbb_dispatcher;
		$this->phpbb_log = $phpbb_log;
		$this->phpbb_notifications = $notification_manager;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $php_ext;
		$this->request = $request;
		$this->report_details_table = $report_details_table;
		$this->report_table = $report_table;
		$this->subscribed_forums_table = $subscribed_forums_table;
		$this->template = $template;
		$this->user = $user;

		$this->debug = (bool) $this->config['phpbbservices_digests_debug'];
		$this->digests_last_run = $this->config['phpbbservices_digests_cron_task_last_gc'];
		$this->forum_hierarchy = array();
		$this->run_mode = constants::DIGESTS_RUN_REGULAR;

	}

	/**
	* Indicates to phpBB's cron utility if this task should be run.
	*
	* @return true if it should be run, false if it should not be run.
	*/
	public function should_run()
	{
		// If the board is currently disabled, digests should be disabled.
		if ($this->config['board_disable'])
		{
			if ($this->config['phpbbservices_digests_enable_log'])
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_BOARD_DISABLED');
			}
			return false;
		}

		// Get date and hour digests were last run, as a Unix timestamp
		$top_of_hour_ts = (int) $this->top_of_hour_timestamp($this->config['phpbbservices_digests_cron_task_last_gc']);
		$should_run = $top_of_hour_ts + (int) $this->config['phpbbservices_digests_cron_task_gc'] <= time();

		if ($this->debug)
		{
			$should_run_str = ($should_run) ? $this->language->lang('YES') : $this->language->lang('NO');
			$run_after_str = date('c', $top_of_hour_ts + $this->config['phpbbservices_digests_cron_task_gc']);	// ISO-8601 date
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_DEBUG_SHOULD_RUN', false, array($should_run_str, $run_after_str));
		}

		// Run this cron only if the current time is at or before the date and hour digests were last run, plus 1 hour.
		return $should_run;
	}

	/**
	* Runs this cron task.
	*
	* @return true if successful, false if an error occurred
	*/
	public function run()
	{

		$now = time();

		$max_execution_time = (function_exists('ini_get')) ? (float) @ini_get('max_execution_time') : (float) @get_cfg_var('max_execution_time');

		// This array keeps statistics on the work accomplished in this mailer run
		$run_statistics = array(
			'success'	=> true,
			'digests_mailed' => 0,
			'digests_skipped' => 0);

		// Populate the forum hierarchy array. This is used when the full path to a forum is requested to be shown in digests.
		$this->create_forums_hierarchy();

		// In system cron (CLI) mode, the $user object may not have an IP assigned. If so, use the server's IP. This will
		// allow logging to succeed since the IP is written to the log.
		if (is_null($this->user->ip))
		{
			$this->user->ip = $this->request->server('SERVER_ADDR');
		}

		// Need a board URL since URLs in the digest pointing to the board need to be absolute URLs
		$this->board_url = generate_board_url() . '/';
	
		$this->server_timezone = (float) date('O')/100;	// Server timezone offset from UTC, in hours. Digests are mailed based on UTC time, so rehosting is unaffected.
		
		// Determine how this is program is being executed. Options are:
		//   - DEFAULT: Regular cron (via invocation of cron.php as part of loading a web page in a browser) - constants::DIGESTS_RUN_REGULAR
		//   - System cron (via an actual cron/scheduled task from the operating system) -  constants::DIGESTS_RUN_SYSTEM
		//   - Manual mode (via the ACP Digests "Manually run the mailer" option) - constants::DIGESTS_RUN_MANUAL
		if (defined('IN_DIGESTS_TEST'))
		{
			$this->run_mode = constants::DIGESTS_RUN_MANUAL;
		}
		else if (php_sapi_name() == 'cli')
		{
			$this->run_mode = constants::DIGESTS_RUN_SYSTEM;
		}

		$this->path_prefix = ($this->run_mode == constants::DIGESTS_RUN_MANUAL) ? './../' : './';	// Because in manual mode you are executing this from the adm folder
		
		$this->email_templates_path = $this->path_prefix . 'ext/phpbbservices/digests/language/en/email/';	// Note: the email templates (except subscribe/unsubscribe templates not used here) are language independent, so it's okay to use British English as it is always supported and the subscribe/unsubscribe templates are not used here.
		$this->store_path = $this->path_prefix . 'store/phpbbservices/digests/';	// Used to write digests to files so they can be analyzed

		// We need enough style information to keep get_user_style() from complaining that bbcode.html cannot be found. Ideally the default style
		// should be used to find templates but since it is looking for bbcode.html, styles always have prosilver in the inheritance tree and bbcode.html
		// is not a template that should ever be customized, it's safe to instruct the templating engine to use prosilver. In some run modes this information
		// can get lost, so it's best to add it explicitly.
		$this->user->style['style_path'] = 'prosilver';
		$this->user->style['style_parent_id'] = 0;
		$this->user->style['bbcode_bitfield'] = 'kNg=';

		// Get the fields to be used in the salutations
		$this->salutation_fields = $this->get_salutation_field_names(); // returns array of custom field names, or false if none are to be used.

		if ($this->run_mode !== constants::DIGESTS_RUN_MANUAL)
		{

			// phpBB cron and a system cron assume an interface where styles won't be needed, so it must be told where to find them. We need styles because we
			// need the templating system to format digests.
			$this->template->set_style(array($this->path_prefix . 'ext/phpbbservices/digests/styles', 'styles'));

			// How many hours of digests are wanted? We want to do it for the number of hours between now and when digests were last ran successfully.
			if ($this->config['phpbbservices_digests_cron_task_last_gc'] == 0)
			{
				$hours_to_do = 1;	// First time run, or digest mailer was reset
			}
			else
			{
				$hours_to_do = floor(($now - $this->config['phpbbservices_digests_cron_task_last_gc']) / (60 * 60));

				// $this->config['phpbbservices_digests_max_cron_hrs'] may override $hours_to_do if it is not zero
				if ($this->config['phpbbservices_digests_max_cron_hrs'] != 0)
				{
					$hours_to_do = min($hours_to_do, (int) $this->config['phpbbservices_digests_max_cron_hrs']);
				}

				// Care must be taken not to miss an hour. For example, if a phpBB cron was run at 11:29 and next at 13:06 then digests must be sent for
				// hours 12 and 13, not just 13. The following algorithm should handle these cases by adding 1 to $hours_to_do.
				$year_last_ran = (int) date('Y', $this->config['phpbbservices_digests_cron_task_last_gc']);
				$day_last_ran = (int) date('z', $this->config['phpbbservices_digests_cron_task_last_gc']);	// 0 thru 364/365
				$hour_last_ran = (int) date('g', $this->config['phpbbservices_digests_cron_task_last_gc']);
				$minute_last_ran = (int) date('i', $this->config['phpbbservices_digests_cron_task_last_gc']);
				$second_last_ran = (int) date('s', $this->config['phpbbservices_digests_cron_task_last_gc']);

				$year_now = (int) date('Y', $now);
				$day_now = (int) date('z', $now);	// 0 thru 364/365
				$hour_now = (int) date('g', $now);
				$minute_now = (int) date('i', $now);
				$second_now = (int) date('s', $now);

				// If the year or day differs from when digests was last run, or if these are the same but the hour differs, we look at the minute last ran
				// and compare it with the minute now. If the minute now is less than the minute last run we have to increment $hours_to_do to capture the missing hour.
				if ($year_now !== $year_last_ran || $day_now !== $day_last_ran ||
					($year_now == $year_last_ran && $day_now == $day_last_ran && $hour_now !== $hour_last_ran))
				{
					if ($minute_now < $minute_last_ran)
					{
						$hours_to_do++;
					}
					else if ($minute_now == $minute_last_ran)
					{
						if ($second_now < $second_last_ran)
						{
							$hours_to_do++;
						}
					}
				}
			}

			if ($hours_to_do <= 0)
			{
				// Error. An hour has not elapsed since digests were last run. This shouldn't happen because should_run() should capture this.
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_RUN_TOO_SOON');
				return false;
			}
			
		}
		else
		{
			$hours_to_do = 1;	// When running manually, the mailer always processes exactly 1 hour

			// Send all digests to a specified email address if this feature is enabled.
			$this->email_address_override = (trim($this->config['phpbbservices_digests_test_email_address']) !== '') ? $this->config['phpbbservices_digests_test_email_address'] : $this->config['board_contact'];
		}

		// Display a digest mail start processing message. It is captured in the admin log.
		if ($this->config['phpbbservices_digests_enable_log'])
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_START');
			// Annotate the log with the type of run
			switch ($this->run_mode)
			{
				case constants::DIGESTS_RUN_SYSTEM:
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_SYSTEM_CRON_RUN');
				break;
				case constants::DIGESTS_RUN_MANUAL:
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_MANUAL_RUN');
				break;
				case constants::DIGESTS_RUN_REGULAR:
				default:
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_REGULAR_CRON_RUN');
				break;
			}
		}

		// Include functions_content.php if it's not already included. If censor_text() missing, we need to load it.
		if (!function_exists('censor_text'))
		{
			include($this->path_prefix . 'includes/functions_content.' . $this->phpEx);
		}

		// Process digests for each hour. For example, to do three hours, start with -2 hours from now and end after 0 hours from now (current hour).
		$total_digests_processed = 0;
		if ($hours_to_do >= 1)
		{
			for ($i=(1 - $hours_to_do); ($i <= 0); $i++)
			{
				$start_time = microtime(true); // returns float Unix timestamp to at least 2 decimal places, ex: 1641160711.04

				// The mail_digests function returns an array which reports on what it mailed and if all went well
				$hourly_report = $this->mail_digests($now, $i);

				// Update the run statistics
				$run_statistics['success'] = $hourly_report['success'];
				$run_statistics['digests_mailed'] = $run_statistics['digests_mailed'] + $hourly_report['digests_mailed'];
				$run_statistics['digests_skipped'] = $run_statistics['digests_skipped'] + $hourly_report['digests_skipped'];
				$total_digests_processed += $hourly_report['digests_mailed'] + $hourly_report['digests_skipped'];

				$end_time = microtime(true);
				$execution_time = $end_time - $start_time;
				$execution_time = round($execution_time,2);
				$execution_time = number_format($execution_time, 2);

				$memory_used_mb = number_format((memory_get_usage() / (1024 * 1024)), 2); // Memory used in MB to process digests for this hour
				$utc_time = $this->time - (int) ($this->server_timezone * 60 * 60);	// Convert server time (or requested run date) into UTC

				if ($run_statistics['success'] == false)
				{
					// Reset the phpBB digests cron since it was not run successfully
					$this->config->set('phpbbservices_digests_cron_task_last_gc', $this->digests_last_run);

					// Notify admins when mailing digests fails through an error log entry
					$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_EMAILING_FAILURE', false, array(date('Y-m-d', $utc_time), date('H', $utc_time), $execution_time, $max_execution_time, $memory_used_mb, $hourly_report['digests_mailed'], $hourly_report['digests_skipped'], $hours_to_do));
					return false;
				}
				else
				{
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_EMAILING_SUCCESS', false, array(date('Y-m-d', $utc_time), date('H', $utc_time), $execution_time, $max_execution_time, $memory_used_mb, $hourly_report['digests_mailed'], $hourly_report['digests_skipped'], $hours_to_do));
					if ($this->run_mode !== constants::DIGESTS_RUN_MANUAL)
					{
						$last_completion_time = $now + ($i * 60 * 60);
						// Note that the hour was processed successfully. If run manually, we don't want to mess with the configuration variable.
						$this->config->set('phpbbservices_digests_cron_task_last_gc', $last_completion_time);
						// Since an hour was completed successfully, change when digests last ran an hour successfully in case of a subsequent crash.
						$this->digests_last_run = $last_completion_time;
					}
				}
				// Save report statistics for this hour for later analysis
				$this->save_report_statistics($hourly_report['utc_time'], $start_time, $end_time, $hourly_report['digests_mailed'], $hourly_report['digests_skipped'], $execution_time, $memory_used_mb, $this->run_mode, $hourly_report['details']);
			}
			// To keep the digests report table at a reasonable size, remove any rows from the table that represent data older than the configuration setting.
			$this->remove_old_report_statistics($hourly_report['utc_time']);
		}
		else
		{
			// This condition should never occur as it suggests a programmatic bug, but if it does let's at least be aware of it.
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_INCONSISTENT_DATES', false, array($this->config['phpbbservices_digests_cron_task_last_gc'], $now));
			return false;
		}

		// Display a digest mail end processing message. It is captured in a log.
		if ($this->config['phpbbservices_digests_enable_log'])
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END', false, array($run_statistics['digests_mailed'], $run_statistics['digests_skipped']));
		}

		return $total_digests_processed;
			
	}

	private function mail_digests($now, $hour)
	{
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//                                                                                                           //
		// This method is what used to be mail_digests.php. It will mail all the digests for the given day and hour  //
		// offset by the $hour parameter.                                                                            //
		//                                                                                                           //
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		static $daily_digest_sql, $weekly_digest_sql;

		// This array is returned by the function and reports on the work done for the hour.
		$hourly_report = array(
			'utc_time'	=> 0,
			'success'	=> true,
			'digests_mailed' => 0,
			'digests_skipped' => 0);

		$report_details = array();

		// Reset to a maximum execution time for this function, since we don't know how many digests
		// must be processed for a particular hour or how long it may take. Other PHP settings may overrule this.
		@set_time_limit(0);

		// We track the last language used in the digest. It's possible a forum will support multiple languages.
		// If so we'll change the language files to accommodate the subscriber.
		$last_language = '';
		
		// If it was requested, get the year, month, date and hour of the digests to recreate. If it was not requested, simply use the current time. Note:
		// if used it must be as a result of a manual run of the mailer.
		if (($this->run_mode == constants::DIGESTS_RUN_MANUAL) && (trim($this->config['phpbbservices_digests_test_date_hour'])) !== '')
		{
			$this->time = mktime(substr($this->config['phpbbservices_digests_test_date_hour'], 11,2),
				substr($this->config['phpbbservices_digests_test_date_hour'],14,2),
				substr($this->config['phpbbservices_digests_test_date_hour'],17,2),
				substr($this->config['phpbbservices_digests_test_date_hour'],5,2),
				substr($this->config['phpbbservices_digests_test_date_hour'],8,2),
				substr($this->config['phpbbservices_digests_test_date_hour'],0,4));
			// To determine UTC in manual mode, we need to use the administrator's timezone and offset from it.
			$hour_offset = $this->helper->make_tz_offset ($this->user->data['user_timezone']);
			$utc_time = $this->time - (int) ($hour_offset * 60 * 60);	// Convert server time (or requested run date) into UTC
		}
		else
		{
			$this->time = $now + ($hour * (60 * 60));	// Timestamp for hour to be processed
			$utc_time = $this->time - (int) ($this->server_timezone * 60 * 60);	// Convert server time (or requested run date) into UTC
		}
		$hourly_report['utc_time'] = $this->top_of_hour_timestamp($utc_time); // This value needs to always be for the top of the hour so hourly data can always be found correctly

		// Get the current hour in UTC, so applicable digests can be sent out for this hour
		$current_hour_utc = date('G', $utc_time); // 0 thru 23
		$current_hour_utc_plus_30 = date('G', $utc_time) + .5;
		if ($current_hour_utc_plus_30 >= 24)
		{
			$current_hour_utc_plus_30 = $current_hour_utc_plus_30 - 24;	// A very unlikely situation
		}
		$current_date_utc = date('Y-m-d' , $utc_time);

		// Create SQL fragment to fetch users wanting a daily digest
		if (!(isset($daily_digest_sql)))
		{
			$daily_digest_sql = '(' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_DAILY_VALUE)) . ')';
		}
		
		// Create SQL fragment to also fetch users wanting a weekly digest, if today is the day weekly digests should go out
		if (!(isset($weekly_digest_sql)))
		{
			$weekly_digest_sql = (date('w', $utc_time) == $this->config['phpbbservices_digests_weekly_digest_day']) ? ' OR (' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_WEEKLY_VALUE)) . ')': '';
		}
		
		// Create SQL fragment to also fetch users wanting a monthly digest. This only happens if the current UTC day is the first of the month.
		$utc_year = (int) date('Y', $utc_time);
		$utc_month = (int) date('m', $utc_time);	// Two-digit month, with leading zeroes
		$utc_day = (int) date('d', $utc_time);	// Two-digit day of month, with leading zeroes
		$utc_hour = (int) date('H', $utc_time);	// Two digit 24 hour, with leading zeroes
		
		if ($utc_day == 1) // Since it's the first day of the month in UTC, monthly digests are run too
		{
			
			if ($utc_month == 1)	// If January, the monthly digests are for December of the previous year
			{
				$utc_month = 12;
				$utc_year--;
			}
			else
			{
				$utc_month--;	// Otherwise monthly digests are run for the previous month for the year
			}
			
			// Create a Unix timestamp that represents a time range for monthly digests, based on the current hour
			$utc_month_last_day = date('t', mktime(0, 0, 0, $utc_month, $utc_day, $utc_year));
			$utc_month_1st_begin = mktime(0, 0, 0, $utc_month, $utc_day, $utc_year);
			$this->utc_month_lastday_end = mktime(23, 59, 59, $utc_month, $utc_month_last_day, $utc_year);	// timestamp for last second of month
			$monthly_digest_sql = ' OR (' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_MONTHLY_VALUE)) . ')';
			
		}
		else
		{
			$monthly_digest_sql = '';
			$utc_month_1st_begin = 0; 	// Make PhpStorm happy
		}

		$formatted_date = date('Y-m-d', $utc_time);	// Format is YYYY-MM-DD with 2 digit months and days
		$formatted_hour = date('H', $utc_time);	// Use a two-digit 24 hour.

		if ($this->config['phpbbservices_digests_enable_log'])
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_HOUR_RUN', false, array($formatted_date, $formatted_hour));
		}

		// We need to know which auth_option_id corresponds to the forum read privilege (f_read) and forum list (f_list) privilege. Why not use $this->auth->acl_get?
		// Because this program must get permissions for different users, so forum authentication will need to be done outside of the regular authentication 
		// mechanism.
		$auth_options = array('f_read', 'f_list');
		$sql = 'SELECT auth_option, auth_option_id
				FROM ' . ACL_OPTIONS_TABLE . '
				WHERE ' . $this->db->sql_in_set('auth_option', $auth_options);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			if ($row['auth_option'] == 'f_read')
			{
				$this->read_id = $row['auth_option_id'];
			}
			if ($row['auth_option'] == 'f_list')
			{
				$this->list_id = $row['auth_option_id'];
			}
		}
		$this->db->sql_freeresult($result); // Query be gone!

		// Get users requesting digests for the current UTC hour. Also, grab the user's style, so the digest will have a familiar look.
		$allowed_user_types = 'AND ' . $this->db->sql_in_set('user_type', array(USER_FOUNDER, USER_NORMAL));	// No bots or inactive users should get digests

		if ($this->config['override_user_style'])
		{

			$sql_array = array(
				'SELECT'	=> 'u.*, s.*',
			
				'FROM'		=> array(
					USERS_TABLE		=> 'u',
					STYLES_TABLE	=> 's',
				),
			
				'WHERE'		=> 's.style_id = ' . (int) $this->config['default_style'] . ' 
								AND (' . 
									$daily_digest_sql . $weekly_digest_sql . $monthly_digest_sql . 
								") 
								AND (user_digest_send_hour_gmt = " . (int) $current_hour_utc . " OR user_digest_send_hour_gmt = " . (float) $current_hour_utc_plus_30 . ") 
								AND user_inactive_reason = 0 " . $allowed_user_types . "
								AND user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' 
								AND user_digest_last_sent_for <> " . $hourly_report['utc_time'],
			
				'ORDER_BY'	=> 'user_lang',
			);

		}
		else
		{

			$sql_array = array(
				'SELECT'	=> 'u.*, s.*',
			
				'FROM'		=> array(
								USERS_TABLE		=> 'u',
								STYLES_TABLE	=> 's',
				),
			
				'WHERE'		=> 'u.user_style = s.style_id
								AND (' . 
									$daily_digest_sql . $weekly_digest_sql . $monthly_digest_sql . 
								") 
								AND (user_digest_send_hour_gmt = " . (int) $current_hour_utc . " OR user_digest_send_hour_gmt = " . (float) $current_hour_utc_plus_30 . ") 
								AND user_inactive_reason = 0 " . $allowed_user_types . "
								AND user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' 
								AND user_digest_last_sent_for <> " . $hourly_report['utc_time'],

				'ORDER_BY'	=> 'user_lang',
			);
		}

		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		if ($this->debug)
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_DEBUG_SQL_CURRENT_HOUR', false, array($current_date_utc, $current_hour_utc, $sql));
		}

		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);	// Gets users and their metadata that are receiving digests for this hour

		if (count($rowset) > 0)

		{

			// Fetch all the posts (no private messages) but do it just once for efficiency. These will be filtered later
			// to remove those posts a particular user should not see.

			// First, determine a maximum date range fetched: daily, weekly or monthly
			if ($monthly_digest_sql !== '')
			{
				// In the case of monthly digests, it's important to include posts that support daily and weekly digests as well, hence dates of posts
				// retrieved may exceed post dates for the previous month. Logic to exclude posts past the end of the previous month in the case of
				// monthly digests must be handled in the create_content function to skip these.
				$date_limit_sql = ' AND p.post_time >= ' . $utc_month_1st_begin . ' AND p.post_time <= ' . max($this->utc_month_lastday_end, $utc_time);
			}
			else if ($weekly_digest_sql !== '')    // Weekly
			{
				$this->date_limit = $this->time - (7 * 24 * 60 * 60);
				$date_limit_sql = ' AND p.post_time >= ' . $this->date_limit . ' AND p.post_time < ' . $this->time;
			}
			else    // Daily
			{
				$this->date_limit = $this->time - (24 * 60 * 60);
				$date_limit_sql = ' AND p.post_time >= ' . $this->date_limit . ' AND p.post_time < ' . $this->time;
			}

			// Now get all potential posts and related data for all users and place them in an array for parsing. Later the create_content function will filter out the stuff
			// that should not go in a particular digest, based on permissions and options the user selected.

			// Prepare SQL
			$sql_array = array(
				'SELECT' => 'f.*, t.*, p.*, u.*',

				'FROM' => array(
					POSTS_TABLE  => 'p',
					USERS_TABLE  => 'u',
					TOPICS_TABLE => 't',
					FORUMS_TABLE => 'f'),

				'WHERE' => "f.forum_id = t.forum_id
								AND p.topic_id = t.topic_id 
								AND p.poster_id = u.user_id
								$date_limit_sql
								AND p.post_visibility = 1
								AND topic_status <> " . ITEM_MOVED . "
								AND forum_password = ''",

				'ORDER_BY' => 'f.left_id, f.right_id'
			);

			// Build query
			$sql_posts = $this->db->sql_build_query('SELECT', $sql_array);
			if ($this->debug)
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_DEBUG_POSTS_CURRENT_HOUR', false, array($current_date_utc, $current_hour_utc, $sql_posts));
			}

			$result_posts = $this->db->sql_query($sql_posts);    // Fetch the data
			$posts_rowset = $this->db->sql_fetchrowset($result_posts); // Get all the posts as a set

			// Now that we have all the posts, time to send one digest at a time

			foreach ($rowset as $row)
			{

				// Each traverse through this loop sends out exactly one digest

				// It's possible to run out of resources while running digests. This will happen mostly on shared hosting.
				// In this event, we want to try to gracefully exit this function and call attention to it as a critical issue
				// by placing it in the phpBB error log.
				if (!still_on_time())
				{
					$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_NO_RESOURCES', false, array($row['username'], $current_hour_utc));
					$hourly_report['success'] = false;
					return $hourly_report;
				}

				// Load the appropriate language files based on the user's preferred language. The board default language
				// is probably British English, which may not be what we want since phpBB supports multiple languages depending on
				// the language packs installed and which language the user chooses.
				if ($row['user_lang'] !== $last_language)
				{
					$this->language->set_user_language($row['user_lang'], true);
					$this->language->add_lang('common');
					$this->language->add_lang(array('common', 'acp/common'), 'phpbbservices/digests');
					$last_language = $row['user_lang'];
				}

				$this->toc = array();        // Create or empty the array containing table of contents information
				$this->toc_pm_count = 0;    // # of private messages in the table of contents for subscriber

				// The extended messenger class is used to send the digests. It is extended to allow HTML emails to be sent.
				if (!class_exists('messenger'))
				{
					include($this->phpbb_root_path . 'includes/functions_messenger.' . $this->phpEx);
				}
				$html_messenger = new \phpbbservices\digests\includes\html_messenger($this->user, $this->phpbb_dispatcher, $this->language, (bool) $this->config['email_package_size']);

				// Set the text showing the digest type
				switch ($row['user_digest_type'])
				{
					case constants::DIGESTS_DAILY_VALUE:
						$digest_type = $this->language->lang('DIGESTS_DAILY');
					break;

					case constants::DIGESTS_WEEKLY_VALUE:
						$digest_type = $this->language->lang('DIGESTS_WEEKLY');
					break;

					case constants::DIGESTS_MONTHLY_VALUE:
						$digest_type = $this->language->lang('DIGESTS_MONTHLY');
					break;

					default:
						// The database may be corrupted if the digest type for a subscriber is invalid.
						// Write an error to the log and continue to the next subscriber.
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_BAD_DIGEST_TYPE', false, array($row['user_digest_type'], $row['username']));
						continue 2;
				}

				$digest_type = ($this->config['phpbbservices_digests_lowercase_digest_type']) ? strtolower($digest_type) : $digest_type;
				$email_subject = html_entity_decode($this->language->lang('DIGESTS_SUBJECT_TITLE', $this->config['sitename'], $digest_type));

				// Set various variables and flags based on the requested digest format.

				$unsubscribe_link = sprintf($this->board_url . "app.{$this->phpEx}/digests/unsubscribe?u=%s&amp;s=%s", $row['user_id'], $row['user_form_salt']);
				switch ($row['user_digest_format'])
				{

					case constants::DIGESTS_TEXT_VALUE:
						$html_messenger->template('digests_text', '', $this->email_templates_path);
						$is_html = false;
						$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER_TEXT', $this->board_url, $this->config['sitename'], $this->phpEx, $this->config['board_contact'], $unsubscribe_link);
						$this->layout_with_html_tables = false;
					break;

					case constants::DIGESTS_PLAIN_VALUE:
						$html_messenger->template('digests_plain_html', '', $this->email_templates_path);
						$is_html = true;
						$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER_HTML', $this->board_url, $this->config['sitename'], $this->phpEx . '?i=-phpbbservices-digests-ucp-main_module&mode=basics', $this->config['board_contact'], $unsubscribe_link);
						$this->layout_with_html_tables = false;
					break;

					case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
						$html_messenger->template('digests_plain_html', '', $this->email_templates_path);
						$is_html = true;
						$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER_HTML', $this->board_url, $this->config['sitename'], $this->phpEx . '?i=-phpbbservices-digests-ucp-main_module&mode=basics', $this->config['board_contact'], $unsubscribe_link);
						$this->layout_with_html_tables = true;
					break;

					case constants::DIGESTS_HTML_VALUE:
						$html_messenger->template('digests_html', '', $this->email_templates_path);
						$is_html = true;
						$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER_HTML', $this->board_url, $this->config['sitename'], $this->phpEx . '?i=-phpbbservices-digests-ucp-main_module&mode=basics', $this->config['board_contact'], $unsubscribe_link);
						$this->layout_with_html_tables = false;
					break;

					case constants::DIGESTS_HTML_CLASSIC_VALUE:
						$html_messenger->template('digests_html', '', $this->email_templates_path);
						$is_html = true;
						$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER_HTML', $this->board_url, $this->config['sitename'], $this->phpEx . '?i=-phpbbservices-digests-ucp-main_module&mode=basics', $this->config['board_contact'], $unsubscribe_link);
						$this->layout_with_html_tables = true;
					break;

					default:
						// The database may be corrupted if the digest format for a subscriber is invalid.
						// Write an error to the log and continue to the next subscriber.
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FORMAT_ERROR', false, array($row['user_digest_type'], $row['username']));
						continue 2;

				}

				// Set email header information
				$from_field_email = (isset($this->config['phpbbservices_digests_from_email_address']) && (strlen($this->config['phpbbservices_digests_from_email_address']) > 0)) ? $this->config['phpbbservices_digests_from_email_address'] : $this->config['board_email'];
				$from_field_name = (isset($this->config['phpbbservices_digests_from_email_name']) && (strlen($this->config['phpbbservices_digests_from_email_name']) > 0)) ? $this->config['phpbbservices_digests_from_email_name'] : $this->config['sitename'] . ' ' . $this->language->lang('DIGESTS_ROBOT');
				$reply_to_field_email = (isset($this->config['phpbbservices_digests_reply_to_email_address']) && (strlen($this->config['phpbbservices_digests_reply_to_email_address']) > 0)) ? $this->config['phpbbservices_digests_reply_to_email_address'] : $this->config['board_email'];

				// Admin may override where email is sent in manual mode. This won't apply if digests are stored to the store/phpbbservices/digests folder instead.
				if ($this->run_mode == constants::DIGESTS_RUN_MANUAL && $this->config['phpbbservices_digests_test_send_to_admin'])
				{
					$html_messenger->to($this->email_address_override);
				}
				else
				{
					$html_messenger->to($row['user_email']);
				}

				if (trim($from_field_name) !== '')
				{
					$html_messenger->from('"' . mail_encode(htmlspecialchars_decode($from_field_name)) . '" <' . $from_field_email . '>');
				}
				else
				{
					$html_messenger->from($from_field_email);
				}

				$html_messenger->replyto($reply_to_field_email);
				$html_messenger->subject($email_subject);

				// Transform user_digest_send_hour_gmt to the subscriber's local time
				$local_send_hour = $row['user_digest_send_hour_gmt'] + (float) $this->helper->make_tz_offset($row['user_timezone']);
				$local_send_hour = $this->helper->check_send_hour($local_send_hour);

				if (($local_send_hour >= 24) || ($local_send_hour < 0))
				{
					// The database may be corrupted if the local send hour for a subscriber is still not between 0 and 23.
					// Write an error to the log and continue to the next subscriber.
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_BAD_SEND_HOUR', false, array($row['user_digest_type'], $row['user_digest_send_hour_gmt']));
					continue;
				}

				// Send a proper content-language to the output
				$user_lang = $row['user_lang'];
				if (strpos($user_lang, '-x-') !== false)
				{
					$user_lang = substr($user_lang, 0, strpos($user_lang, '-x-'));
				}

				// Create a proper message indicating the number of posts allowed in digest and set a value for the maximum posts allowed in this digest
				if (($row['user_digest_max_posts'] == 0) && ($this->config['phpbbservices_digests_max_items'] == 0))
				{
					$this->max_posts = 0;    // 0 means no limit
				}
				else if (($this->config['phpbbservices_digests_max_items'] != 0) && $this->config['phpbbservices_digests_max_items'] < $row['user_digest_max_posts'])
				{
					$this->max_posts = (int) $row['phpbbservices_digests_max_items'];
				}
				else
				{
					$this->max_posts = (int) $row['user_digest_max_posts'];
				}

				$recipient_time = $utc_time + (float) ($this->helper->make_tz_offset($row['user_timezone']) * 60 * 60);

				// Identify the language translator, if one exists and they choose to identify him/herself
				if (trim($this->language->lang('DIGESTS_TRANSLATOR_NAME') == ''))
				{
					$translator = '';
				}
				else
				{
					$translator = $this->language->lang('DIGESTS_COMMA') . strtolower($this->language->lang('DIGESTS_TRANSLATED_BY')) . ' ';
					$translator .= ($this->language->lang('DIGESTS_TRANSLATOR_CONTACT') == '') ? $this->language->lang('DIGESTS_TRANSLATOR_NAME') : '<a href="' . $this->language->lang('DIGESTS_TRANSLATOR_CONTACT') . '" class="postlink">' . $this->language->lang('DIGESTS_TRANSLATOR_NAME') . '</a>';
				}

				// When running a phpBB cron as "Anonymous", the timezone object may not exist or get destroyed. Recreate it if needed so format_date() will work.
				if (!is_object($this->user->timezone))
				{
					$this->create_timezone_object($row['user_timezone'], $row['user_dateformat']);
				}
				$publish_date = $this->language->lang('DIGESTS_PUBLISH_DATE', $row['username'], $this->user->format_date($recipient_time, $row['user_dateformat']));

				// Get the values to appear in the salutation for this subscriber, which is typically the username
				if (is_array($this->salutation_fields))
				{
					$user_cp_fields = $this->cpfs->grab_profile_fields_data($row['user_id']); // Get custom profile fields for this subscriber
					$salutation_name = '';
					foreach ($this->salutation_fields as $this_salutation)
					{
						$salutation_name .= $user_cp_fields[$row['user_id']][$this_salutation]['value'] . ' ';
					}
					$salutation_name = trim($salutation_name);
					if (trim($salutation_name) == '')
					{
						// The custom profile fields to use are blank for this subscriber, so default to the username.
						$salutation_name = $row['username'];
					}
				}
				else
				{
					// The salutation field is blank, so use the username in the salutation.
					$salutation_name = $row['username'];
				}

				// Print the non-post and non-private message information in the digest. The actual posts and private messages require the full templating system,
				// and is handled in the create_content function.

				$html_messenger->assign_vars(array(
					'S_CONTENT_DIRECTION'        => $this->language->lang('DIRECTION'),
					'S_DIGESTS_DISCLAIMER'       => $disclaimer,
					'S_DIGESTS_INTRODUCTION'     => ($is_html) ? $this->language->lang('DIGESTS_INTRODUCTION', $this->config['sitename']) : strip_tags($this->language->lang('DIGESTS_INTRODUCTION', $this->config['sitename'])),
					'S_DIGESTS_PUBLISH_DATE'     => $publish_date,
					'S_DIGESTS_SALUTATION_BLURB' => $salutation_name . $this->language->lang('DIGESTS_COMMA'),
					'S_DIGESTS_TITLE'            => $email_subject,
					'S_DIGESTS_TRANSLATOR'       => ($is_html) ? $translator : strip_tags($translator),
					'S_USER_LANG'                => $user_lang,
					'T_STYLESHEET_LINK'          => ($this->config['phpbbservices_digests_enable_custom_stylesheets']) ? "{$this->board_url}styles/" . $this->config['phpbbservices_digests_custom_stylesheet_path'] : "{$this->board_url}styles/" . $row['style_path'] . '/theme/stylesheet.css',
					'T_THEME_PATH'               => "{$this->board_url}styles/" . $row['style_path'] . '/theme',
				));

				// Get any private messages for this user

				// Count # of unread and new for this user. Counts may need to be reduced later.
				$total_pm_unread = 0;
				$total_pm_new = 0;
				unset($msg_ids);
				$msg_ids = array();

				if ($row['user_digest_show_pms'])
				{

					$sql_array = array(
						'SELECT' => '*',

						'FROM' => array(
							PRIVMSGS_TO_TABLE => 'pt',
							PRIVMSGS_TABLE    => 'pm',
							USERS_TABLE       => 'u',
						),

						'WHERE' => 'pt.msg_id = pm.msg_id
										AND pt.author_id = u.user_id
										AND pt.user_id = ' . (int) $row['user_id'] . '
										AND ((pm_unread = 1 AND folder_id <> ' . PRIVMSGS_OUTBOX .') OR (pm_new = 1 AND ' . $this->db->sql_in_set('folder_id', array(PRIVMSGS_NO_BOX, PRIVMSGS_HOLD_BOX)) . '))',	// Logic used by function update_pm_counts() in /includes/functions_privmsgs.php

						'ORDER_BY' => 'message_time',
					);

					$pm_sql = $this->db->sql_build_query('SELECT', $sql_array);

					$pm_result = $this->db->sql_query($pm_sql);
					$pm_rowset = $this->db->sql_fetchrowset($pm_result);

					foreach ($pm_rowset as $pm_row)
					{
						if ($pm_row['pm_unread'] == 1)
						{
							$total_pm_unread++;
						}
						if ($pm_row['pm_new'] == 1)
						{
							$total_pm_new++;
						}
						$msg_ids[] = $pm_row['msg_id'];
					}
					$this->db->sql_freeresult($pm_result);

				}
				else
				{
					// Avoid some PHP Notices...
					$pm_result = null;
					$pm_rowset = null;
				}

				// Construct the body of the digest. We use the templating system because of the advanced features missing in the
				// email templating system, e.g. loops and switches.
				$digest_content = $this->create_content($posts_rowset, $pm_rowset, $row, $is_html, $utc_month_1st_begin);

				// Create the table of contents. We move this to a function to make this more readable.
				$this->create_table_of_contents($row, $is_html, $html_messenger);

				if (!$is_html)
				{
					// This reduces extra lines in the text digests. Apparently the phpBB template engine leaves
					// blank lines where a template contains templates commands.
					$digest_content = str_replace("\n\n", "\n", $digest_content);
				}

				// Publish the digest content, assembled elsewhere and a list of the forums subscribed to.
				$html_messenger->assign_vars(array(
					'DIGESTS_CONTENT' => $digest_content,
				));

				$mail_sent = true;	// Assume a digest mailing will be sent successfully.

				// Email the digest or save it locally.
				if (($this->run_mode == constants::DIGESTS_RUN_MANUAL) && ($this->config['phpbbservices_digests_test_spool']))
				{

					// To grab the content of the email (less mail headers) first run the mailer with the $break parameter set to true. This will keep
					// the mail from being sent out. The function won't fail since nothing is being sent out.
					$html_messenger->send(NOTIFY_EMAIL, true, $is_html, true);
					$email_content = $html_messenger->msg;

					// Make the store/phpbbservices/digests directory if it does not exist. This should be created in
					// acp/main_module.php. So this acts mostly as a safety switch.
					if (!$this->helper->make_directories())
					{
						// If unable to create these directories, it's likely a permissions issue, so flag it and terminate abnormally.
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_CREATE_DIRECTORY_ERROR', false, array($this->store_path));
						if ($this->config['phpbbservices_digests_enable_log'])
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
						}
						$hourly_report['success'] = false;
						return $hourly_report;
					}

					// Save digests as file in the store/phpbbservices/digests folder instead of emailing.
					$suffix = ($is_html) ? '.html' : '.txt';
					$file_name = $row['username'] . '-' . $utc_year . '-' . str_pad($utc_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($utc_day, 2, '0', STR_PAD_LEFT) . '-' . str_pad($utc_hour, 2, '0', STR_PAD_LEFT) . $suffix;

					$handle = @fopen($this->store_path . $file_name, "w");
					if ($handle === false)
					{
						// Since this indicates a major problem, let's abort now. It's likely a global write error.
						$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FILE_OPEN_ERROR', false, array($this->store_path));
						if ($this->config['phpbbservices_digests_enable_log'])
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
						}
						$hourly_report['success'] = false;
						return $hourly_report;
					}

					$success = @fwrite($handle, htmlspecialchars_decode($email_content));
					if ($success === false)
					{
						// Since this indicates a major problem, let's abort now.  It's likely a global write error.
						$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FILE_WRITE_ERROR', false, array($this->store_path . $file_name));
						if ($this->config['phpbbservices_digests_enable_log'])
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
						}
						$hourly_report['success'] = false;
						return $hourly_report;
					}

					$success = @fclose($handle);
					if ($success === false)
					{
						// Since this indicates a major problem, let's abort now
						$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FILE_CLOSE_ERROR', false, array($this->store_path . $file_name));
						if ($this->config['phpbbservices_digests_enable_log'])
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
						}
						$hourly_report['success'] = false;
						return $hourly_report;
					}

					// Note in the log that a digest was written to the store folder
					if ($this->config['phpbbservices_digests_enable_log'])
					{
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_DISK', false, array($file_name));
					}

				}

				else

				{

					// Send the digest out only if there are new qualifying posts OR the user requests a digest to be sent if there are no posts OR
					// if there are unread private messages AND the user wants to see private messages in the digest.

					// Try to send this digest
					$okay_to_send = $row['user_digest_send_on_no_posts'] || $this->posts_in_digest > 0 || (is_array($pm_rowset) && count($pm_rowset) > 0) && $row['user_digest_show_pms'];
					if ($okay_to_send)
					{

						$mail_sent = $html_messenger->send(NOTIFY_EMAIL, false, $is_html, true);     // digest mailed?

						if (!$mail_sent)
						{
							$hourly_report['success'] = false;
							// Something went wrong when sending the digest. Errors now go into the error log for more visibility.
							if ($this->config['phpbbservices_digests_show_email'])
							{
								$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD', false, array($row['username'], $row['user_email']));
							}
							else
							{
								$this->phpbb_log->add('critical', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD_NO_EMAIL', false, array($row['username']));
							}
						}
						else
						{
							// save queue for later delivery (if applicable)
							$html_messenger->save_queue();
							$hourly_report['digests_mailed']++;

							// Digest should have been mailed successfully
							if ($this->config['phpbbservices_digests_enable_log'])
							{
								if ($this->config['phpbbservices_digests_show_email'])
								{
									$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD', false, array(lcfirst($digest_type), $this->language->lang('DIGESTS_SENT_TO'), $row['username'], $row['user_email'], $utc_year . '-' . str_pad($utc_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($utc_day, 2, '0', STR_PAD_LEFT), $current_hour_utc, $this->posts_in_digest, (is_array($pm_rowset)) ? count($pm_rowset) : 0));
								}
								else
								{
									$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_NO_EMAIL', false, array(lcfirst($digest_type), $this->language->lang('DIGESTS_SENT_TO'),	$row['username'],$utc_year . '-' . str_pad($utc_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($utc_day, 2, '0', STR_PAD_LEFT), $current_hour_utc, $this->posts_in_digest, (is_array($pm_rowset)) ? count($pm_rowset) : 0));
								}
							}
						}

						if (!($this->run_mode == constants::DIGESTS_RUN_MANUAL))
						{

							// Record the moment the digest was successfully sent to the subscriber
							$sql_ary = array(
								'user_digest_last_sent' => time(),
							);

							$sql2 = 'UPDATE ' . USERS_TABLE . '
								SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
								WHERE user_id = ' . (int) $row['user_id'];
							$this->db->sql_query($sql2);

							// If requested, update user_lastvisit, which also marks all forums read and clears
							// any notifications
							if ($row['user_digest_reset_lastvisit'] == 1)
							{
								$sql_ary = array(
									'user_lastvisit' => time(),
								);

								$sql2 = 'UPDATE ' . USERS_TABLE . '
									SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
									WHERE user_id = ' . (int) $row['user_id'];
								$this->db->sql_query($sql2);

								if (!function_exists('markread'))
								{
									include($this->path_prefix . 'includes/functions.' . $this->phpEx);
								}

								// In order to mark all forums read and to clear all forum-related notifications, we must
								// temporarily set the value of $user->data['user_id'] to the current subscriber because
								// markread() assumes the subscriber is logged in and uses this value.

								$saved_user_id = $this->user->data['user_id'];

								$this->user->data['user_id'] = $row['user_id'];
								$this->user->data['is_registered'] = 1;
								markread('all', false, false, $now);    // Perform mark all forums read through the current hour for this subscriber

								$this->user->data['user_id'] = $saved_user_id;
							}

							// Mark private messages in the digest as read, if so instructed
							if (!empty($pm_rowset) && $row['user_digest_show_pms'] == 1 && $row['user_digest_pm_mark_read'] == 1)
							{

								$sql_ary = array(
									'pm_new'    => 0,
									'pm_unread' => 0,
									'folder_id' => PRIVMSGS_INBOX,
								);

								$this->db->sql_transaction('begin');

								$pm_read_sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . '
									SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
									WHERE ' . $this->db->sql_in_set('msg_id', $msg_ids);

								$this->db->sql_query($pm_read_sql);

								// Since any unread and new private messages will be in the digest, it's safe to set these values to zero.

								$update_users_sql = 'UPDATE ' . USERS_TABLE . '
									SET user_unread_privmsg = 0, 
										user_new_privmsg = 0
									WHERE user_id = ' . (int) $row['user_id'];

								$this->db->sql_query($update_users_sql);

								$this->db->sql_transaction('commit');

								// Next, mark all private message notification for the subscriber as read
								$this->phpbb_notifications->mark_notifications('notification.type.pm', false, $row['user_id'], false);

							}
						}

					}
					else
					{
						// Don't send a digest -- the user doesn't want one because there are no qualifying posts
						$hourly_report['digests_skipped']++;
						if ($this->config['phpbbservices_digests_enable_log'])
						{
							if ($this->config['phpbbservices_digests_show_email'])
							{
								$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE', false, array($row['username'], $row['user_email']));
							}
							else
							{
								$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE_NO_EMAIL', false, array($row['username']));
							}
						}
					}

					// Collect report detail information for this subscriber
					$report_details[$row['user_id']] = array(
						'digest_type' => $row['user_digest_type'],
						'posts_in_digest' => $this->posts_in_digest,
						'msgs_in_digest' => is_array($pm_rowset) ? count($pm_rowset) : 0,
						'creation_time' => time(),
						'status' => $okay_to_send,
						'sent' => ($okay_to_send && $mail_sent) ? true : false,
					);
				}

				if ($mail_sent && $this->run_mode !== constants::DIGESTS_RUN_MANUAL)
				{
					// Except for a manual mailing, record the current top of hour timestamp so we know what date and
					// hour this digest was mailed for. In the event of a resource error occurring, this should keep
					// digests from being sent more than once to the subscriber. This also captures the case where the
					// user does not want a digest if some criteria were not met.
					$sql_ary = array(
						'user_digest_last_sent_for' => $hourly_report['utc_time'],
					);

					$sql2 = 'UPDATE ' . USERS_TABLE . '
								SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
								WHERE user_id = ' . (int) $row['user_id'];
					$this->db->sql_query($sql2);
				}

				// Reset messenger object for the next subscriber, bug fix provided by robdocmagic
				$html_messenger->reset();

			}   // foreach

			$this->db->sql_freeresult($result_posts);

		}

		// Append $report_details array to $hourly_report array
		$hourly_report['details'] = $report_details;

		unset($rowset, $posts_rowset, $report_details);

		return $hourly_report;
		
	}
	
	private function create_content($posts_rowset, $pm_rowset, $user_row, $is_html, $utc_month_1st_begin)
	{

		// This function creates most of the content for an individual digests and is handled by the main templating system, NOT the email templating system
		// because at least under phpBB 3.0 the messenger class was not sophisticated enough to do loops and such. The function will return a string with this
		// content all nicely marked up, usually in HTML but possibly in plain text if a text digest is requested. The messenger class simply assigns it to a
		// template variable for inclusion in an email.
		//
		// $posts_rowset is an array of all possible posts for the digest time period
		// $pm_roswet is an array of private messages, if any, for the user getting this digest
		// $user_row is a simple array of mostly data from the users table for the user getting this digest
		// $is_html is true if the user getting this digest wants a HTML digest
		// $utc_month_1st_begin is a UNIX timestamp that represents the beginning date/time for a monthly digest for this day and hour, if applicable.

		$popular_topics = array();			// Keep PhpStorm happy
		$post_subject = array();			// Keep PhpStorm happy
		$post_time = array();				// Keep PhpStorm happy
		$topic_first_poster_name = array();	// Keep PhpStorm happy
		$topic_last_post_time = array();	// Keep PhpStorm happy
		$topic_replies = array();			// Keep PhpStorm happy
		$topic_title = array();				// Keep PhpStorm happy
		$topic_views = array();				// Keep PhpStorm happy
		$username_clean = array();			// Keep PhpStorm happy

		// Get popular topics for this user, if needed.
		if ((int) $user_row['user_digest_popular'] == 1 && !($user_row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS))
		{
			switch ($user_row['user_digest_type'])
			{
				case constants::DIGESTS_MONTHLY_VALUE:
					$start_ts = $utc_month_1st_begin;
					$end_ts = $this->utc_month_lastday_end;
				break;
				case constants::DIGESTS_DAILY_VALUE:
					$start_ts = $this->time - (24 * 60 * 60);
					$end_ts = $this->time;
				break;
				case constants::DIGESTS_WEEKLY_VALUE:
				default:
					$start_ts = $this->time - (7 * 24 * 60 * 60);
					$end_ts = $this->time;
				break;
			}
			$popular_topics = $this->get_popular_topics($user_row['user_digest_type'], $start_ts, $end_ts, $user_row['user_digest_popularity_size']);
		}

		// Choose a template based on whether a HTML or text digest will be sent
		$mail_template = ($is_html) ? 'mail_digests_html.html' : 'mail_digests_text.html';
		$this->template->set_filenames(array(
		   'mail_digests'      => $mail_template,
		));
			
		$show_post_text = ($user_row['user_digest_no_post_text'] == 0);
		
		$this->posts_in_digest = 0;

		// Process private messages (if any) first since they appear before posts in the digest
		
		if (($user_row['user_digest_show_pms'] == 1) && (is_array($pm_rowset) && count($pm_rowset) > 0))
		{
		
			// There are private messages and the user wants to see them in the digest
			$this->template->assign_vars(array(
				'L_FROM'						=> ($is_html) ? $this->language->lang('FROM') : ucfirst($this->language->lang('FROM')),
				'L_YOU_HAVE_PRIVATE_MESSAGES'	=> $this->language->lang('DIGESTS_YOU_HAVE_PRIVATE_MESSAGES', $user_row['username']),
				'S_SHOW_PMS'					=> true,
			));
			
			foreach ($pm_rowset as $pm_row)
			{
			
				// If there are inline attachments, remove them otherwise they will show up twice. Getting the styling right
				// in these cases is probably a lost cause due to the complexity to be addressed due to various styling issues.
				$pm_row['message_text'] = preg_replace('#\[attachment=.*?\].*?\[/attachment:.*?]#', '', censor_text($pm_row['message_text']));
	
				// Now adjust message time to digest recipient's local time
				$pm_time_offset = ((float) $this->helper->make_tz_offset($user_row['user_timezone']) - (int) $this->server_timezone) * 60 * 60;
				$recipient_time = $pm_row['message_time'] + $pm_time_offset;

				// When running a phpBB cron as "Anonymous", the timezone object may not exist or get destroyed. Recreate it if needed so format_date() will work.
				if (!is_object($this->user->timezone))
				{
					$this->create_timezone_object($user_row['user_timezone'], $user_row['user_dateformat']);
				}

				$message_datetime = $this->user->format_date($recipient_time, $user_row['user_dateformat']);

				// Add to table of contents array
				$this->toc['pms'][$this->toc_pm_count]['message_id'] = html_entity_decode($pm_row['msg_id']);
				$this->toc['pms'][$this->toc_pm_count]['message_subject'] = html_entity_decode(censor_text($pm_row['message_subject']));
				$this->toc['pms'][$this->toc_pm_count]['author'] = html_entity_decode($pm_row['username']);
				$this->toc['pms'][$this->toc_pm_count]['datetime'] = $message_datetime;
				$this->toc_pm_count++;
	
				$flags = (($pm_row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
					(($pm_row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
					(($pm_row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
				
				if ($this->run_mode == constants::DIGESTS_RUN_SYSTEM)
				{
					// Hack that is needed for system crons to show smilies
					$pm_row['message_text'] = str_replace('{SMILIES_PATH}', $this->board_url . 'images/smilies', $pm_row['message_text']);
				}
				$pm_text = generate_text_for_display(censor_text($pm_row['message_text']), $pm_row['bbcode_uid'], $pm_row['bbcode_bitfield'], $flags);
				
				// User signature wanted? If so append it to the private message.
				$user_sig = ($pm_row['enable_sig'] && $pm_row['user_sig'] !== '' && $this->config['allow_sig']) ? censor_text($pm_row['user_sig']) : '';
				if ($user_sig !== '')
				{
					if ($this->run_mode == constants::DIGESTS_RUN_SYSTEM)
					{
						// Hack that is needed for system crons to show smilies
						$user_sig = str_replace('{SMILIES_PATH}', $this->board_url . 'images/smilies', $user_sig);
					}
					// Format the signature for display
					$user_sig = generate_text_for_display(censor_text($user_sig), $user_row['user_sig_bbcode_uid'], $user_row['user_sig_bbcode_bitfield'], $flags);
				}
			
				// Handle logic to display attachments in private messages
				if ($pm_row['message_attachment'] > 0 && $user_row['user_digest_attachments'])
				{
					// Logic to show attachments
					$pm_text .= $this->create_attachment_markup($pm_row, false);
				}
					
				// Add signature to bottom of private message
				$pm_text = ($user_sig !== '') ? $pm_text . "\n" . $this->language->lang('DIGESTS_POST_SIGNATURE_DELIMITER') . "\n" . $user_sig : $pm_text . "\n";
	
				// If required or requested, remove all images
				if ($this->config['phpbbservices_digests_block_images'] || $user_row['user_digest_block_images'])
				{
					$pm_text = preg_replace('/(<)([img])(\w+)([^>]*>)/', '', $pm_text);
				}
					
				// If a text digest is desired, this is a good point to strip tags, after first replacing <br> with two carriage returns, since text digests 
				// must have no tags
				if (!$is_html)
				{
					$pm_text = str_replace('<br>', "\n\n", $pm_text);
					$pm_text = html_entity_decode(strip_tags($pm_text));
				}
				else
				{
					// Board URLs must be absolute in the digests, so substitute board URL for relative URL. Smilies are marked up differently after converted
					// to HTML so a special pass must be made for them.
					$pm_text = str_replace('<img src="' . $this->phpbb_root_path, '<img src="' . $this->board_url, $pm_text);
					$pm_text = str_replace('<img class="smilies" src="' . $this->phpbb_root_path, '<img class="smilies" src="' . $this->board_url, $pm_text);
				}

				// Publish the private messages in the digest
				$this->template->assign_block_vars('pm', array(
					'ANCHOR'					=> 'm' . $pm_row['msg_id'],
					'CONTENT'					=> $pm_text,
					'DATE'						=> $message_datetime . "\n",
					'FROM'						=> ($is_html) ? sprintf("<a href=\"%s?mode=viewprofile&amp;u=%s\">%s</a>", $this->board_url . 'memberlist.' . $this->phpEx, $pm_row['author_id'], $pm_row['username']) : $pm_row['username'],
					'NEW_UNREAD'				=> ($pm_row['pm_new'] == 1) ? $this->language->lang('DIGESTS_NEW') . ' ' : $this->language->lang('DIGESTS_UNREAD') . ' ',
					'PRIVATE_MESSAGE_LINK'		=> ($is_html) ? sprintf('<a href="%s?i=pm&amp;mode=view&amp;f=0&amp;p=%s">%s</a>', $this->board_url . 'ucp.' . $this->phpEx, $pm_row['msg_id'], $pm_row['msg_id']) . "\n" : html_entity_decode(censor_text($pm_row['message_subject'])) . "\n",
					'PRIVATE_MESSAGE_SUBJECT'	=> ($is_html) ? sprintf('<a href="%s?i=pm&amp;mode=view&amp;f=0&amp;p=%s">%s</a>', $this->board_url . 'ucp.' . $this->phpEx, $pm_row['msg_id'], html_entity_decode(censor_text($pm_row['message_subject']))) . "\n" : html_entity_decode(censor_text($pm_row['message_subject'])) . "\n",
					'S_USE_CLASSIC_TEMPLATE'	=> $this->layout_with_html_tables,
				));
			}
	
		}
		else
		{
			// Turn off switch that would indicate there are private messages
			$this->template->assign_vars(array(
				'S_SHOW_PMS'	=> false,
			));
		}

		// Process posts next
		
		$last_forum_id = -1;
		$last_topic_id = -1;
	
		if (count($posts_rowset) !== 0)
		{

			$bookmarked_topics = $this->get_bookmarked_topics($user_row);
			$fetched_forums = $this->get_fetched_forums($user_row);

			// Sort posts by the user's preference.
			$left_id = array();
			$right_id = array();

			switch($user_row['user_digest_sortby'])
			{
			
				case constants::DIGESTS_SORTBY_BOARD:
				
					$topic_asc_desc = ($user_row['user_topic_sortby_dir'] == 'd') ? SORT_DESC : SORT_ASC;
					$post_asc_desc = ($user_row['user_post_sortby_dir'] == 'd') ? SORT_DESC : SORT_ASC;

					switch($user_row['user_topic_sortby_type'])
					
					{
						
						case 'a':
							// Sort by topic author
							foreach ($posts_rowset as $key => $row)
							{
								$left_id[$key]  = $row['left_id'];
								$right_id[$key] = $row['right_id'];
								$topic_first_poster_name[$key] = $row['topic_first_poster_name'];
								switch($user_row['user_post_sortby_type'])
								{
									case 'a':
										$username_clean[$key] = $row['username_clean'];
									break;
									case 't':
										$post_time[$key] = $row['post_time'];
									break;
									case 's':
										$post_subject[$key] = censor_text($row['post_subject']);
									break;
								}
							}
							switch($user_row['user_post_sortby_type'])
							{
								case 'a':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_first_poster_name, $topic_asc_desc, $username_clean, $post_asc_desc, $posts_rowset);
								break;
								case 't':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_first_poster_name, $topic_asc_desc, $post_time, $post_asc_desc, $posts_rowset);
								break;
								case 's':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_first_poster_name, $topic_asc_desc, $post_subject, $post_asc_desc, $posts_rowset);
								break;
							}
						break;
						
						case 't':
							// Sort by topic last post time
							foreach ($posts_rowset as $key => $row)
							{
								$left_id[$key]  = $row['left_id'];
								$right_id[$key] = $row['right_id'];
								$topic_last_post_time[$key] = $row['topic_last_post_time'];
								switch($user_row['user_post_sortby_type'])
								{
									case 'a':
										$username_clean[$key] = $row['username_clean'];
									break;
									case 't':
										$post_time[$key] = $row['post_time'];
									break;
									case 's':
										$post_subject[$key] = censor_text($row['post_subject']);
									break;
								}
							}
							switch($user_row['user_post_sortby_type'])
							{
								case 'a':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_last_post_time, $topic_asc_desc, $username_clean, $post_asc_desc, $posts_rowset);
								break;
								case 't':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_last_post_time, $topic_asc_desc, $post_time, $post_asc_desc, $posts_rowset);
								break;
								case 's':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_last_post_time, $topic_asc_desc, $post_subject, $post_asc_desc, $posts_rowset);
								break;
							}
						break;
						
						case 'r':
							// Sort by topic replies
							foreach ($posts_rowset as $key => $row)
							{
								$left_id[$key]  = $row['left_id'];
								$right_id[$key] = $row['right_id'];
								$topic_replies[$key] = $row['topic_replies'];
								switch($user_row['user_post_sortby_type'])
								{
									case 'a':
										$username_clean[$key] = $row['username_clean'];
									break;
									case 't':
										$post_time[$key] = $row['post_time'];
									break;
									case 's':
										$post_subject[$key] = censor_text($row['post_subject']);
									break;
								}
							}
							switch($user_row['user_post_sortby_type'])
							{
								case 'a':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_replies, $topic_asc_desc, $username_clean, $post_asc_desc, $posts_rowset);
								break;
								case 't':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_replies, $topic_asc_desc, $post_time, $post_asc_desc, $posts_rowset);
								break;
								case 's':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_replies, $topic_asc_desc, $post_subject, $post_asc_desc, $posts_rowset);
								break;
							}
						break;
						
						case 's':
							// Sort by topic title
							foreach ($posts_rowset as $key => $row)
							{
								$left_id[$key]  = $row['left_id'];
								$right_id[$key] = $row['right_id'];
								$topic_title[$key] = censor_text($row['topic_title']);
								switch($user_row['user_post_sortby_type'])
								{
									case 'a':
										$username_clean[$key] = $row['username_clean'];
									break;
									case 't':
										$post_time[$key] = $row['post_time'];
									break;
									case 's':
										$post_subject[$key] = censor_text($row['post_subject']);
									break;
								}
							}
							switch($user_row['user_post_sortby_type'])
							{
								case 'a':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_title, $topic_asc_desc, $username_clean, $post_asc_desc, $posts_rowset);
								break;
								case 't':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_title, $topic_asc_desc, $post_time, $post_asc_desc, $posts_rowset);
								break;
								case 's':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_title, $topic_asc_desc, $post_subject, $post_asc_desc, $posts_rowset);
								break;
							}
						break;
						
						case 'v':
							// Sort by topic views
							foreach ($posts_rowset as $key => $row)
							{
								$left_id[$key]  = $row['left_id'];
								$right_id[$key] = $row['right_id'];
								$topic_views[$key] = $row['topic_views'];
								switch($user_row['user_post_sortby_type'])
								{
									case 'a':
										$username_clean[$key] = $row['username_clean'];
									break;
									case 't':
										$post_time[$key] = $row['post_time'];
									break;
									case 's':
										$post_subject[$key] = censor_text($row['post_subject']);
									break;
								}
							}
							switch($user_row['user_post_sortby_type'])
							{
								case 'a':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_views, $topic_asc_desc, $username_clean, $post_asc_desc, $posts_rowset);
								break;
								case 't':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_views, $topic_asc_desc, $post_time, $post_asc_desc, $posts_rowset);
								break;
								case 's':
									array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_views, $topic_asc_desc, $post_subject, $post_asc_desc, $posts_rowset);
								break;
							}
						break;
					
						default:
						break;
					
					}
					
				break;
				
				case constants::DIGESTS_SORTBY_STANDARD_DESC:
					// Sort by traditional order, newest post first
					foreach ($posts_rowset as $key => $row)
					{
						$left_id[$key]  = $row['left_id'];
						$right_id[$key] = $row['right_id'];
						$topic_last_post_time[$key] = $row['topic_last_post_time'];
						$post_time[$key] = $row['post_time'];
					}
					array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_last_post_time, SORT_DESC, $post_time, SORT_DESC, $posts_rowset);
				break;
				
				case constants::DIGESTS_SORTBY_POSTDATE:
					// Sort by post date
					foreach ($posts_rowset as $key => $row)
					{
						$left_id[$key]  = $row['left_id'];
						$right_id[$key] = $row['right_id'];
						$post_time[$key] = $row['post_time'];
					}
					array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $post_time, SORT_ASC, $posts_rowset);
				break;
				
				case constants::DIGESTS_SORTBY_POSTDATE_DESC:
					// Sort by post date, newest first
					foreach ($posts_rowset as $key => $row)
					{
						$left_id[$key]  = $row['left_id'];
						$right_id[$key] = $row['right_id'];
						$post_time[$key] = $row['post_time'];
					}
					array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $post_time, SORT_DESC, $posts_rowset);
				break;

				case constants::DIGESTS_SORTBY_STANDARD:
				default:
					// Sort by traditional order
					foreach ($posts_rowset as $key => $row)
					{
						$left_id[$key]  = $row['left_id'];
						$right_id[$key] = $row['right_id'];
						$topic_last_post_time[$key] = $row['topic_last_post_time'];
						$post_time[$key] = $row['post_time'];
					}
					array_multisort($left_id, SORT_ASC, $right_id, SORT_ASC, $topic_last_post_time, SORT_DESC, $post_time, SORT_ASC, $posts_rowset);
				break;

			}
			
			// Fetch foes, if any, of the subscriber. These posts could be filtered out.
			$foes = $this->get_foes($user_row);

			// Put posts in the digests, assuming they should not be filtered out
			
			foreach ($posts_rowset as $post_row)
			{

				// If we've hit the limit of the maximum number of posts in a digest, it's time to exit the loop. If $this->max_posts == 0 there is no limit.
				if (($this->max_posts !== 0) && ($this->posts_in_digest >= $this->max_posts))
				{
					break;
				}

				// Skip post if post is not in an allowed forum
				if (!in_array($post_row['forum_id'], $fetched_forums))
				{
					continue;
				}

				// Skip post if new posts only logic applies, or if for some reason its timestamp is before the allowed daily, weekly or monthly digest range.
				if (($user_row['user_digest_new_posts_only']) && ($post_row['post_time'] < max($this->date_limit, $user_row['user_lastvisit'])))
				{
					continue;
				}
				
				// Exclude post if from a foe
				if (isset($foes) && count($foes) > 0)
				{
					if ($user_row['user_digest_remove_foes'] == 1 && in_array($post_row['poster_id'], $foes))
					{
						continue;
					}
				}
	
				// Exclude posts that are before the needed start date/time
				if (($user_row['user_digest_type'] == constants::DIGESTS_WEEKLY_VALUE) && ($post_row['post_time'] < $this->time - (7 * 24 * 60 * 60)))
				{
					continue;
				}
				if (($user_row['user_digest_type'] == constants::DIGESTS_DAILY_VALUE) && ($post_row['post_time'] < $this->time - (24 * 60 * 60)))
				{
					continue;
				}
				
				// Exclude posts from monthly digests that occur after the end of the previous month.
				if (($user_row['user_digest_type'] == constants::DIGESTS_MONTHLY_VALUE) && ($post_row['post_time'] > $this->utc_month_lastday_end))
				{
					continue;
				}
				
				// Skip if post has less than minimum words wanted.
				if (($user_row['user_digest_min_words'] > 0) && (($this->truncate_words(censor_text($post_row['post_text']), $user_row['user_digest_min_words'], true) < $user_row['user_digest_min_words']))) 
				{
					continue;
				}
				
				// Skip post if not a bookmarked topic and bookmarked topics only are wanted.
				if ($user_row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS)
				{
					if ((isset($bookmarked_topics)) && !in_array($post_row['topic_id'], $bookmarked_topics))
					{
						continue;
					}
				}

				// Skip post if first post logic applies and not a first post
				if (($user_row['user_digest_filter_type'] == constants::DIGESTS_FIRST) && ($post_row['topic_first_post_id'] != $post_row['post_id']))
				{
					continue;
				}
				
				// Skip post if remove my posts logic applies
				if (($user_row['user_digest_show_mine'] == 0) && ($post_row['poster_id'] == $user_row['user_id']))
				{
					continue;
				}

				// Skip posts in topics that are not popular if that option was selected. Does not apply if bookmarked topics only are wanted.
				if (!($user_row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS) &&
					$user_row['user_digest_popular'] == 1 &&
					!in_array($post_row['topic_id'], $popular_topics))
				{
					continue;
				}

				// If there are inline attachments, remove them otherwise they will show up twice. Getting the styling right
				// in these cases is probably a lost cause due to the complexity to be addressed due to various styling issues.
				$post_text = preg_replace('#\[attachment=.*?\].*?\[/attachment:.*?]#', '', $post_row['post_text']);
		
				// Now adjust post time to digest recipient's local time
				$post_time_offset = ((float) $this->helper->make_tz_offset($user_row['user_timezone']) - (int) $this->server_timezone) * 60 * 60;
				$recipient_time = $post_row['post_time'] + $post_time_offset;

				// When running a phpBB cron as "Anonymous", the timezone object may not exist or get destroyed. Recreate it if needed so format_date() will work.
				if (!is_object($this->user->timezone))
				{
					$this->create_timezone_object($user_row['user_timezone'], $user_row['user_dateformat']);
				}
				$post_datetime = $this->user->format_date($recipient_time, $user_row['user_dateformat']);

				// Add to table of contents array
				$this->toc['posts'][$this->posts_in_digest]['post_id'] = html_entity_decode($post_row['post_id']);
				$this->toc['posts'][$this->posts_in_digest]['forum'] = $this->get_forum_path($post_row['forum_id']);
				$this->toc['posts'][$this->posts_in_digest]['topic'] = html_entity_decode($post_row['topic_title']);
				$this->toc['posts'][$this->posts_in_digest]['author'] = html_entity_decode($post_row['username']);
				$this->toc['posts'][$this->posts_in_digest]['datetime'] = $post_datetime;
				$this->posts_in_digest++;

				// Need BBCode flags to translate BBCode into HTML
				$flags = (($post_row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
					(($post_row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
					(($post_row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
				
				if ($this->run_mode == constants::DIGESTS_RUN_SYSTEM)
				{
					// Hack that is needed for system crons to show smilies
					$post_text = str_replace('{SMILIES_PATH}', $this->board_url . 'images/smilies', $post_text);
				}
				$post_text = '<div>' . generate_text_for_display($post_text, $post_row['bbcode_uid'], $post_row['bbcode_bitfield'], $flags) . '</div>';

				// Logic to show attachments
				$post_text .= $this->create_attachment_markup($post_row, true);

				// User signature wanted?
				$user_sig = ($post_row['enable_sig'] && $post_row['user_sig'] !== '' && $this->config['allow_sig'] ) ? censor_text($post_row['user_sig']) : '';
				if ($user_sig !== '')
				{
					// Format the signature for display
					// Fix by phpBB user EAM to handle when post and signature BBCode settings differ
					$sigflags = (($post_row['enable_sig']) ? OPTION_FLAG_BBCODE : 0) +
									(($post_row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
									(($post_row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
					if ($this->run_mode == constants::DIGESTS_RUN_SYSTEM)
					{
						// Hack that is needed for system crons to show smilies
						$user_sig = str_replace('{SMILIES_PATH}', $this->board_url . 'images/smilies', $user_sig);
					}
					$user_sig = generate_text_for_display($user_sig, $post_row['user_sig_bbcode_uid'], $post_row['user_sig_bbcode_bitfield'], $sigflags);
				}
				
				// Add signature to bottom of post
				$post_text = ($user_sig !== '') ? trim($post_text . "\n" . $this->language->lang('DIGESTS_POST_SIGNATURE_DELIMITER') . "\n" . $user_sig) : trim($post_text . "\n");

				// If required or requested, remove all images
				if ($this->config['phpbbservices_digests_block_images'] || $user_row['user_digest_block_images'])
				{
					$post_text = preg_replace('/(<)([img])(\w+)([^>]*>)/', '', $post_text);
				}
				
				// If a text digest is desired, this is a good point to strip tags
				if (!$is_html)
				{
					$post_text = str_replace('<br>', "\n\n", $post_text);
					$post_text = html_entity_decode(strip_tags($post_text));

					if ((bool) $this->config['phpbbservices_digests_foreign_urls'])
					{
						// Find all domain names within the post text that are not for the board's domain and substitute the
						// foreign domain name with link removed text. This is to reduce the likelihood a given digest
						// will be flagged as spam because it contains foreign domain links.
						$matches_found = preg_match('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', $post_text, $matches);
						if ((bool) $matches_found)
						{
							foreach ($matches as $match)
							{
								if (trim($match) !== trim($this->config['server_name']))
								{
									$post_text = str_replace($match, $this->language->lang('DIGESTS_FOREIGN_LINK_REMOVED_TEXT'), $post_text);
								}
							}
						}
					}
				}
				else
				{
					// Board URLs must be absolute in the digests, so substitute board URL for relative URL. Smilies are marked up differently after converted
					// to HTML so a special pass must be made for them. Also replace & with entity to avoid parser error.
					$post_text = str_replace('<img src="' . $this->phpbb_root_path, '<img src="' . $this->board_url, $post_text);
					$post_text = str_replace('<img class="smilies" src="' . $this->phpbb_root_path, '<img class="smilies" src="' . $this->board_url, $post_text);

					// For HTML digests, remove problematic HTML tags if the board administrator has specified any.
					if (trim($this->config['phpbbservices_digests_strip_tags']) !== '')
					{
						$tags = explode(',',str_replace(' ', '', trim($this->config['phpbbservices_digests_strip_tags'])));
						if (count($tags) > 0)
						{
							$dom = new \DOMDocument();
							$dom->loadHTML($post_text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Fix provided by whocarez
							$substitute = $dom->createElement('p', $this->language->lang('DIGESTS_TAG_REPLACED'));

							foreach ($tags as $tag)
							{
								$script = $dom->getElementsByTagName(trim($tag));

								foreach($script as $item)
								{
									$item->parentNode->replaceChild($substitute, $item);	// Replace unwanted tag with substitute
								}
							}

							$post_text = utf8_decode($dom->saveHTML($dom->documentElement)); // Fix provided by whocarez
						}
					}

					// Remove foreign links, if this option is enabled. Links outside of the domain will have text substituted. This will
					// make it less likely that digests will get flagged as spam by email systems.
					if ((bool) $this->config['phpbbservices_digests_foreign_urls'])
					{
						$dom = new \DOMDocument();
						$dom->loadHTML($post_text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD); // Fix provided by whocarez

						$anchors = $dom->getElementsByTagName('a');

						for ($i=$anchors->length-1;$i>=0;$i--)
						{
							$item = $anchors->item($i);
							$url = $item->attributes->getNamedItem('href')->nodeValue;
							if (!strstr($url, trim($this->config['server_name'])))
							{
								$substitute = $dom->createElement('span', $this->language->lang('DIGESTS_FOREIGN_LINK_REMOVED'));
								$item->parentNode->replaceChild($substitute, $item);	// Replace anchor tag with foreign link removed notice.
							}
						}
						$post_text = utf8_decode($dom->saveHTML($dom->documentElement));
					}

				}
	
				if ($last_forum_id != (int) $post_row['forum_id'])
				{
					// Process a forum break

					// Now expand the array of forums to show the hierarchy from the index down, if needed. Otherwise
					// just publish the forum name containing subsequent posts.
					$forum_path = $this->get_forum_path($post_row['forum_id']);

					$this->template->assign_block_vars('forum', array(
						'FORUM'			=> ($is_html) ? sprintf("<a href=\"%sviewforum.%s?f=%s\">%s</a>", $this->board_url, $this->phpEx, $post_row['forum_id'], html_entity_decode($forum_path)) : $forum_path,
					));
					$last_forum_id = (int) $post_row['forum_id'];
				}
						
				if ($last_topic_id !== (int) $post_row['topic_id'])
				{
					// Process a topic break
					$this->template->assign_block_vars('forum.topic', array(
						'S_USE_CLASSIC_TEMPLATE'	=> $this->layout_with_html_tables,
						'TOPIC'						=> ($is_html) ? sprintf("<a href=\"%sviewtopic.%s?f=%s&amp;t=%s\">%s</a>", $this->board_url, $this->phpEx, $post_row['forum_id'], $post_row['topic_id'], html_entity_decode($post_row['topic_title'])) : html_entity_decode($post_row['topic_title']),
					));
					$last_topic_id = (int) $post_row['topic_id'];
				}
			
				// Handle max display words logic
				if ($user_row['user_digest_max_display_words'] > 0)
				{
					$post_text = $this->truncate_words($post_text, $user_row['user_digest_max_display_words']);
				}
				
				// Create a link to the profile of the poster
				$from_url = sprintf("<a href=\"%s?mode=viewprofile&amp;u=%s\">%s</a>%s", $this->board_url . 'memberlist.' . $this->phpEx, $post_row['user_id'], html_entity_decode($post_row['username']), "\n");
			
				$this->template->assign_block_vars('forum.topic.post', array(
					'ANCHOR'		=> 'p' . $post_row['post_id'],
					'CONTENT'		=> $post_text,
					'DATE'			=> $post_datetime . "\n",
					'FROM'			=> ($is_html) ? $from_url : html_entity_decode($post_row['username']),
					'POST_LINK'		=> ($is_html) ? sprintf("<a href=\"%sviewtopic.$this->phpEx?f=%s&amp;t=%s#p%s\">%s</a>%s", $this->board_url, $post_row['forum_id'], $post_row['topic_id'], $post_row['post_id'], html_entity_decode(censor_text($post_row['post_subject'])), "\n") : html_entity_decode(censor_text($post_row['post_subject'])),
					'SUBJECT'		=> ($is_html) ? sprintf("<a href=\"%sviewtopic.$this->phpEx?f=%s&amp;t=%s#p%s\">%s</a>%s", $this->board_url, $post_row['forum_id'], $post_row['topic_id'], $post_row['post_id'], html_entity_decode(censor_text($post_row['post_subject'])), "\n") : html_entity_decode(censor_text($post_row['post_subject'])),
					'S_FIRST_POST' 	=> ($post_row['topic_first_post_id'] == $post_row['post_id']), // Hide subject if first post, as it is the same as topic title
				));
				
			}

		}

		// General template variables are set here. Many are inherited from language variables.
		$this->template->assign_vars(array(
			'DIGESTS_TOTAL_PMS'				=> is_array($pm_rowset) ? count($pm_rowset) : 0,
			'DIGESTS_TOTAL_POSTS'			=> $this->posts_in_digest,
			'L_DIGESTS_NO_PRIVATE_MESSAGES'	=> $this->language->lang('DIGESTS_NO_PRIVATE_MESSAGES') . "\n",
			'L_PRIVATE_MESSAGE'				=> strtolower($this->language->lang('PRIVATE_MESSAGE')) . "\n",
			'L_PRIVATE_MESSAGE_2'			=> ucwords($this->language->lang('PRIVATE_MESSAGE')) . "\n",
			'L_YOU_HAVE_PRIVATE_MESSAGES'	=> $this->language->lang('DIGESTS_YOU_HAVE_PRIVATE_MESSAGES', $user_row['username']) . "\n",
			'S_SHOW_POST_TEXT'				=> $show_post_text,
		));

		if ($user_row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS)
		{
			// Substitute a no bookmarked posts error message if needed.
			$this->template->assign_vars(array(
				'L_DIGESTS_NO_POSTS'			=> $this->language->lang('DIGESTS_NO_BOOKMARKED_POSTS'),
			));
		}
		
		$digest_body = $this->template->assign_display('mail_digests');
		
		$this->template->destroy();	

		return $digest_body;
		
	}

	private function check_all_parents($forum_array, $forum_id)
	{
	
		// This function checks all parents for a given forum_id. If any of them do not have the f_list permission
		// the function returns false, meaning the forum should not be displayed because it has a parent that should
		// not be listed. Otherwise it returns true, indicating the forum can be listed.
		
		$there_are_parents = true;
		$current_forum_id = $forum_id;
		$include_this_forum = true;
		
		static $parents_loaded = false;
		static $parent_array = array();
		
		if (!$parents_loaded)
		{
			// Get a list of parent_ids for each forum and put them in an array.

			$sql_array = array(
				'SELECT'	=> 'forum_id, parent_id',
			
				'FROM'		=> array(
					FORUMS_TABLE	=> 'f',
				),
			
				'ORDER_BY'	=> '1',
			);
			
			$sql = $this->db->sql_build_query('SELECT', $sql_array);

			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$parent_array[$row['forum_id']] = $row['parent_id'];
			}
			$parents_loaded = true;
			$this->db->sql_freeresult($result);
		}
		
		while ($there_are_parents)
		{
		
			if ($parent_array[$current_forum_id] == 0) 	// No parent
			{
				$there_are_parents = false;
			}
			else
			{
				if (isset($forum_array[$parent_array[$current_forum_id]][$this->list_id]) && $forum_array[$parent_array[$current_forum_id]][$this->list_id] == 1)
				{
					// So far so good
					$current_forum_id = $parent_array[$current_forum_id];
				}
				else
				{
					// Danger Will Robinson! No list permission exists for a parent of the requested forum, so this forum should not be shown.
					$there_are_parents = false;
					$include_this_forum = false;
				}
			}
			
		}
	
		return $include_this_forum;
		
	}
	
	private function truncate_words($text, $max_words, $just_count_words = false)
	{
	
		// This function returns the first $max_words from the supplied $text. If $just_count_words === true, a word count is returned. Note:
		// for consistency, HTML is stripped. This can be annoying, but otherwise HTML rendered in the digest may not be valid.
		
		if ($just_count_words)
		{
			return str_word_count(strip_tags($text));
		}
		
		$word_array = preg_split("/[\s]+/", $text);
		
		if (count($word_array) <= $max_words)
		{
			return rtrim($text);
		}
		else
		{
			$truncated_text = '';
			for ($i=0; $i < $max_words; $i++) 
			{
				$truncated_text .= $word_array[$i] . ' ';
			}
			return rtrim($truncated_text) . $this->language->lang('DIGESTS_MAX_WORDS_NOTIFIER');
		}
		
	}

	private function create_forums_hierarchy()
	{

		// Populates an internal array used to show the full path to a forum when it is requested to be shown in digests.
		// get_forum_path makes it readable.

		$sql_array = array(
			'SELECT'	=> 'forum_id, forum_name, parent_id',

			'FROM'		=> array(
				FORUMS_TABLE	=> 'f',
			),
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->forum_hierarchy[$row['forum_id']] = array ('forum_name' => $row['forum_name'], 'parent_id' => $row['parent_id']);
		}
		$this->db->sql_freeresult($result); // Query be gone!

	}

	private function get_forum_path ($forum_id)
	{

		// When called this function returns a string with the complete forum path, if that option is enabled. Otherwise it returns the forum
		// name from the forum hierarchy array.

		if (!$this->config['phpbbservices_digests_show_forum_path'])
		{
			return html_entity_decode($this->forum_hierarchy[$forum_id]['forum_name']);
		}
		else
		{
			$temp_forum_array = array();
			$current_forum_id = (int) $forum_id;
			while ($current_forum_id !== 0)
			{
				// Place the current forum found at the top of the stack
				array_unshift($temp_forum_array, $this->forum_hierarchy[$current_forum_id]['forum_name']);
				$current_forum_id = (int) $this->forum_hierarchy[$current_forum_id]['parent_id'];
			}
			return html_entity_decode(implode($this->language->lang('DIGESTS_DELIMITER'), $temp_forum_array));
		}

	}

	private function top_of_hour_timestamp($unix_timestamp)
	{

		// Returns a UNIX timestamp that represents the time at the top of the hour for the timestamp
		$date_info = getdate($unix_timestamp);
		return mktime($date_info['hours'], 0, 0, $date_info['mon'], $date_info['mday'], $date_info['year']);

	}

	private function create_timezone_object($user_timezone, $user_dateformat)
	{

		// When the run function is run as a cron, it's possible the $user->timezone object won't exist. This will
		// create the object in memory, principally so that dates can be formatted in the user's language.
		if ($this->helper->validate_date($user_timezone))
		{
			$timezone = $user_timezone;
			$this->user->data['user_timezone'] = $user_timezone;
			$this->user->data['user_dateformat'] = $user_dateformat;
		}
		else
		{
			// User's timezone is not valid, so default to board timezone
			$timezone = $this->config['board_timezone'];
			$this->user->data['user_timezone'] = $this->config['board_timezone'];
			$this->user->data['user_dateformat'] = $this->config['default_dateformat'];
		}
		$this->user->timezone = new \DateTimeZone($timezone);
		return true;

	}

	private function create_attachment_markup ($row, $is_post = true)
	{

		// This function creates HTML markup for showing attached images or linking to them whether in a
		// post or private message. $row contains either the row in the posts table or the private messages
		// table. If for a post, $is_post == true.

		$markup_text = '';

		// Get all attachments
		if ($is_post)
		{
			$sql = 'SELECT *
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE post_msg_id = ' . (int) $row['post_id'] . ' AND in_message = 0  
				ORDER BY attach_id';
		}
		else
		{
			$sql = 'SELECT *
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE post_msg_id = ' . (int) $row['msg_id'] . ' AND in_message = 1  
				ORDER BY attach_id';
		}
		$result = $this->db->sql_query($sql);

		$attachments_found = 0;
		while ($attachment_row = $this->db->sql_fetchrow($result))
		{
			$attachments_found++;
			if ($attachments_found == 1)
			{
				$markup_text = sprintf("<div class=\"box\"><div>%s</div>", $this->language->lang('ATTACHMENTS'));
			}
			$file_size = round(($attachment_row['filesize']/1024),2);
			// Show images, link to other attachments
			if (substr($attachment_row['mimetype'],0,6) == 'image/')
			{
				$anchor_begin = '';
				$anchor_end = '';
				$image_text = '';
				$thumbnail_parameter = '';
				$is_thumbnail = ($attachment_row['thumbnail'] == 1);
				// Logic to resize the image, if needed
				if ($is_thumbnail)
				{
					$anchor_begin = sprintf("<a href=\"%s\">", $this->board_url . "download/file.$this->phpEx?id=" . $attachment_row['attach_id']);
					$anchor_end = '</a>';
					$image_text = $this->language->lang('DIGESTS_POST_IMAGE_TEXT');
					$thumbnail_parameter = '&t=1';
				}
				$markup_text .= sprintf("<div>%s<br><em>%s</em> (%s %s)<br>%s<img src=\"%s\" alt=\"%s\" title=\"%s\" />%s\n<br>%s</div>", censor_text($attachment_row['attach_comment']), $attachment_row['real_filename'], $file_size, $this->language->lang('KIB'), $anchor_begin, $this->board_url . "download/file.$this->phpEx?id=" . $attachment_row['attach_id'] . $thumbnail_parameter, censor_text($attachment_row['attach_comment']), censor_text($attachment_row['attach_comment']), $anchor_end, $image_text);
			}
			else
			{
				$markup_text .=
					sprintf("<div>%s<br><a href=\"%s\">%s</a><br>(%s %s)</div>",
						($attachment_row['attach_comment'] == '') ? '' : '<em>' . censor_text($attachment_row['attach_comment']) . '</em>',
						$this->board_url . "download/file.$this->phpEx?id=" . $attachment_row['attach_id'],
						$attachment_row['real_filename'],
						$file_size,
						$this->language->lang('KIB'));
			}
		}
		$this->db->sql_freeresult($result);

		if ($attachments_found > 0)
		{
			$markup_text .= '</div>';
		}
		return $markup_text;

	}

	private function get_popular_topics ($user_digest_type, $start_ts, $end_ts, $popularity_size)
	{

		// This function determines topics that are popular for a given subscriber. The number of topic replies for the
		// time period (day, week or month) is used. Parameters:
		//
		//	$user_digest_type: DAY, WEEK or MNTH, same as phpbb_users.user_digest_type
		//	$start_ts: UNIX timestamp for start of post_time range
		//	$end_ts: UNIX timestamp for end of post_time range
		//	$popularity_size: phpbb_users.user_digest_popularity_size value for the subscriber

		// Figure out the date range to use for this user
		switch ($user_digest_type)
		{
			case constants::DIGESTS_MONTHLY_VALUE:
				$day_range = round ((($end_ts - $start_ts) / (24 * 60 * 60 * 1000)), 0); // Days in last month
			break;
			case constants::DIGESTS_WEEKLY_VALUE:
				$day_range = 7;
			break;
			case constants::DIGESTS_DAILY_VALUE:
			default:
				$day_range = 1;
			break;
		}

		$popular_topics = array();

		$sql = 'SELECT topic_id, count(*)/' . $day_range . ' AS popularity
				FROM ' . POSTS_TABLE . '
				WHERE post_time >= ' . (int) $start_ts . ' AND post_time <= ' . (int) $end_ts . '
				GROUP BY topic_id
				HAVING popularity >= ' . (int) $popularity_size;

		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$popular_topics[] = $row['topic_id'];
		}
		$this->db->sql_freeresult($result);

		return $popular_topics;

	}

	private function get_salutation_field_names()
	{

		// Returns a list of custom profile fields to be used in a digest salutation. If none were specified, returns false.

		// Substitute custom profile field values in the salutation, if they exist and are single-text fields
		$salutation_fields = explode(',', trim($this->config['phpbbservices_digests_saluation_fields']));
		if ($salutation_fields[0] == '')	// Nothing was entered in this field
		{
			return false;
		}

		// Remove any padding around the salutation fields that may have been added during data entry. Lower case
		// too because profile fields are stored in the database in lower case.
		for ($i=0; $i < count($salutation_fields); $i++)
		{
			$salutation_fields[$i] = strtolower(trim($salutation_fields[$i]));
		}

		return $salutation_fields;

	}

	private function create_table_of_contents($row, $is_html, $html_messenger)
	{

		// Assemble a digest table of contents as a HTML table or as text.

		if ($row['user_digest_toc'] == 1)
		{

			// Create Table of Contents header for private messages first
			if ($is_html)
			{
				// For HTML digests, the table of contents always appears in a HTML table
				$digest_toc = "<h2 style=\"color:#000000\">" . $this->language->lang('DIGESTS_TOC') . "</h2>\n";
				$digest_toc .= "<p><a href=\"#skip\">" . $this->language->lang('DIGESTS_SKIP') . "</a></p>\n";
			}
			else
			{
				$digest_toc = "____________________________________________________________\n\n" . $this->language->lang('DIGESTS_TOC') . "\n\n";
			}

			if ($row['user_digest_show_pms'] == 1)
			{

				// Heading for table of contents
				if ($is_html)
				{
					$digest_toc .= sprintf("<div class=\"toc\"><table>\n<tbody>\n<tr>\n<th id=\"j1\">%s</th><th id=\"j2\">%s</th><th id=\"j3\">%s</th><th id=\"j4\">%s</th>\n</tr>\n",
						$this->language->lang('DIGESTS_JUMP_TO_MSG'), $this->language->lang('DIGESTS_PM_SUBJECT'), $this->language->lang('DIGESTS_SENDER'), $this->language->lang('DIGESTS_DATE'));
				}

				// Add a table row for each private message
				if ($this->toc_pm_count > 0)
				{
					for ($i = 0; $i <= $this->toc_pm_count; $i++)
					{
						if ($is_html)
						{
							$digest_toc .= (isset($this->toc['pms'][$i])) ? "<tr>\n<td headers=\"j1\" style=\"text-align: center;\"><a href=\"#m" . $this->toc['pms'][$i]['message_id'] . '">' . $this->toc['pms'][$i]['message_id'] . '</a></td><td headers="j2">' . $this->toc['pms'][$i]['message_subject'] . '</td><td headers="j3">' . $this->toc['pms'][$i]['author'] . '</td><td headers="j4">' . $this->toc['pms'][$i]['datetime'] . "</td>\n</tr>\n" : '';
						}
						else
						{
							$digest_toc .= (isset($this->toc['pms'][$i])) ? $this->toc['pms'][$i]['author'] . ' ' . $this->language->lang('DIGESTS_SENT_YOU_A_MESSAGE') . ' ' . $this->language->lang('DIGESTS_OPEN_QUOTE_TEXT') . $this->toc['pms'][$i]['message_subject'] . $this->language->lang('DIGESTS_CLOSED_QUOTE_TEXT') . ' ' . $this->language->lang('DIGESTS_ON') . ' ' . $this->toc['pms'][$i]['datetime'] . "\n" : '';
						}
					}
				}
				else
				{
					$digest_toc .= ($is_html) ? '<tr><td colspan="4">' . $this->language->lang('DIGESTS_NO_PRIVATE_MESSAGES') . "</td></tr>" : $this->language->lang('DIGESTS_NO_PRIVATE_MESSAGES') . "\n";
				}

				// Create Table of Contents footer for private messages
				$digest_toc .= ($is_html) ? "</tbody></table>\n<br>" : "\n";

			}

			// Create Table of Contents header for posts
			if ($is_html)
			{
				// For HTML digests, the table of contents always appears in a HTML table
				$digest_toc .= sprintf("<table>\n<tbody>\n<tr>\n<th id=\"h1\">%s</th><th id=\"h2\">%s</th><th id=\"h3\">%s</th><th id=\"h4\">%s</th><th id=\"h5\">%s</th>\n</tr>\n",
					$this->language->lang('DIGESTS_JUMP_TO_POST'), $this->language->lang('FORUM'), $this->language->lang('TOPIC'), $this->language->lang('AUTHOR'), $this->language->lang('DIGESTS_DATE'));
			}

			// Add a table row for each post
			if ($this->posts_in_digest > 0)
			{
				for ($i = 0; $i <= $this->posts_in_digest; $i++)
				{
					if ($is_html)
					{
						$digest_toc .= (isset($this->toc['posts'][$i])) ? "<tr>\n<td headers=\"h1\" style=\"text-align: center;\"><a href=\"#p" . $this->toc['posts'][$i]['post_id'] . '">' . $this->toc['posts'][$i]['post_id'] . '</a></td><td headers="h2">' . $this->toc['posts'][$i]['forum'] . '</td><td headers="h3">' . $this->toc['posts'][$i]['topic'] . '</td><td headers="h4">' . $this->toc['posts'][$i]['author'] . '</td><td headers="h5">' . $this->toc['posts'][$i]['datetime'] . "</td>\n</tr>\n" : '';
					}
					else
					{
						$digest_toc .= (isset($this->toc['posts'][$i])) ? $this->toc['posts'][$i]['author'] . ' ' . $this->language->lang('DIGESTS_POSTED_TO_THE_TOPIC') . ' ' . $this->language->lang('DIGESTS_OPEN_QUOTE_TEXT') . $this->toc['posts'][$i]['topic'] . $this->language->lang('DIGESTS_CLOSED_QUOTE_TEXT') . ' ' . $this->language->lang('IN') . ' ' . $this->language->lang('DIGESTS_OPEN_QUOTE_TEXT') . $this->toc['posts'][$i]['forum'] . $this->language->lang('DIGESTS_CLOSED_QUOTE_TEXT') . ' ' . $this->language->lang('DIGESTS_ON') . ' ' . $this->toc['posts'][$i]['datetime'] . "\n" : '';
					}
				}
			}
			else
			{
				$no_posts_msg = ($row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS) ? $this->language->lang('DIGESTS_NO_BOOKMARKED_POSTS') : $this->language->lang('DIGESTS_NO_POSTS');
				$digest_toc .= ($is_html) ? '<tr><td colspan="5">' . $no_posts_msg . "</td></tr>" : $no_posts_msg;
			}

			// Create Table of Contents footer for posts
			$digest_toc .= ($is_html) ? "</tbody>\n</table></div>\n<br>" : '';

			// Publish the table of contents
			$html_messenger->assign_vars(array(
				'DIGESTS_TOC' => $digest_toc,
			));
		}

	}

	private function get_bookmarked_topics($user_row)
	{

		// If bookmarked topics only is checked, returns an array of bookmarked topic_ids. Otherwise
		// returns a null array.

		$bookmarked_topics = array();

		// Determine bookmarked topics, if any
		if ($user_row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS) // Bookmarked topics only
		{

			// When selecting bookmarked topics only, we can safely ignore the logic constraining the user to read only
			// from certain forums. Instead we will create the SQL to get the bookmarked topics only.

			$bookmarked_topics = array();

			$sql_array = array(
				'SELECT'	=> 't.topic_id',

				'FROM'		=> array(
					USERS_TABLE			=> 'u',
					BOOKMARKS_TABLE		=> 'b',
					TOPICS_TABLE		=> 't',
				),

				'WHERE'		=> 'u.user_id = b.user_id AND b.topic_id = t.topic_id 
						AND b.user_id = ' . (int) $user_row['user_id'],
			);

			$sql = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$bookmarked_topics[] = (int) $row['topic_id'];
			}
			$this->db->sql_freeresult($result);

			if (count($bookmarked_topics) == 0)
			{
				// Logically, if there are no bookmarked topics for this user_id then there will be nothing in the digest. Flag an exception and
				// make a note in the log about this inconsistency. Subscriber should still get a digest with a no bookmarked posts message.
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGEST_NO_BOOKMARKS', false, array($user_row['username']));
			}

		}
		return $bookmarked_topics;

	}

	private function get_fetched_forums($user_row)
	{

		// Returns an array of forum_ids that the user is allowed to read.

		// Get forum read permissions for this user. They are also usually stored in the user_permissions column, but sometimes the field is empty. This always works.
		$allowed_forums = array();

		$forum_array = $this->auth->acl_raw_data_single_user($user_row['user_id']);
		foreach ($forum_array as $key => $value)
		{
			foreach ($value as $auth_option_id => $auth_setting)
			{
				if ($auth_option_id == $this->read_id)
				{
					if (($auth_setting == 1) && $this->check_all_parents($forum_array, $key))
					{
						$allowed_forums[] = $key;
					}
				}
			}
		}

		if (count($allowed_forums) == 0)
		{
			// If this user cannot retrieve ANY forums, in most cases no digest will be produced. However, there may be forums that the admin
			// requires be presented, so we don't do an exception, but we do note it in the log.
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_NO_ALLOWED_FORUMS', false, array($user_row['username']));
		}
		$allowed_forums[] = 0;	// Add in global announcements forum

		// Ensure there are no duplicates
		$allowed_forums = array_unique($allowed_forums);

		// Get the requested forums and their names. If none are specified in the phpbb_digests_subscribed_forums table, then all allowed forums are assumed
		$requested_forums = array();

		$sql_array = array(
			'SELECT'	=> 's.forum_id, forum_name',

			'FROM'		=> array(
				$this->subscribed_forums_table	=> 's',
				FORUMS_TABLE					=> 'f',
			),

			'WHERE'		=> 's.forum_id = f.forum_id 
										AND user_id = ' . (int) $user_row['user_id'],
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$requested_forums[] = $row['forum_id'];
		}
		$this->db->sql_freeresult($result);
		$requested_forums[] = 0;	// Add in global announcements forum

		// Ensure there are no duplicates
		$requested_forums = array_unique($requested_forums);

		// The forums that will be fetched is the array intersection of the requested and allowed forums. There should be at least one forum
		// allowed because the global announcements pseudo forum is common to both. However, if the user did not specify any forums then the allowed
		// forums become the ones fetched.
		$fetched_forums = (count($requested_forums) > 1) ? array_intersect($allowed_forums, $requested_forums) : $allowed_forums;
		asort($fetched_forums);

		// Add in any required forums
		$required_forums = (isset($this->config['phpbbservices_digests_include_forums'])) ? explode(',',$this->config['phpbbservices_digests_include_forums']) : array();
		if (count($required_forums) > 0)
		{
			$fetched_forums = array_merge($fetched_forums, $required_forums);
		}

		// Remove any prohibited forums
		$excluded_forums = (isset($this->config['phpbbservices_digests_exclude_forums'])) ? explode(',',$this->config['phpbbservices_digests_exclude_forums']) : array();
		if (count($excluded_forums) > 0)
		{
			$fetched_forums = array_diff($fetched_forums, $excluded_forums);
		}

		// Tidy up the forum list
		$fetched_forums = array_unique($fetched_forums);

		return $fetched_forums;

	}

	private function get_foes($user_row)
	{
		// Returns an array of foes for the subscriber, if any. If none, an empty array is returned.
		$foes = array();

		if ($user_row['user_digest_remove_foes'] == 1)
		{

			// Fetch your foes
			$sql_array = array(
				'SELECT'	=> 'zebra_id',

				'FROM'		=> array(
					ZEBRA_TABLE	=> 'z',
				),

				'WHERE'		=> 'user_id = ' . (int) $user_row['user_id'] . ' AND foe = 1',
			);

			$sql = $this->db->sql_build_query('SELECT', $sql_array);
			$result = $this->db->sql_query($sql);
			while ($row = $this->db->sql_fetchrow($result))
			{
				$foes[] = (int) $row['zebra_id'];
			}
			$this->db->sql_freeresult($result);

		}

		return $foes;
	}

	private function save_report_statistics($utc_date_hour, $started, $ended, $mailed, $skipped, $exec_time, $memory_used, $cron_type, $details)
	{

		// This function saves an hour's statistics to the phpbb_digests_report table.
		//
		// $utc_date_hour - UNIX timestamp for the date and hour digests were sent. This should always be set to evaluate to the top of the hour.
		// $started - UNIX timestamp for when digests started processing for a particular day + hour
		// $ended - UNIX timestamp for when digests started processing for a particular day + hour
		// $mailed - number of digests mailed
		// $skipped - number of digests that skipped being mailed due to a user's digest criteria
		// $exec_time - execution time to create the digests for this particular day + hour
		// $memory_used - memory used to create the digests for this particular day + hour, in MB
		// $cron_type - see constants.php: indicates if system cron, phpBB cron or manual mailer was used (1, 2 or 3)
		// $details - array of user data on digests sent for the hour, used to populate the phpbb_digests_report_details_table

		$data = [
			'date_hour_sent_utc'	=> (int) $utc_date_hour,
			'started'	=> (int) number_format($started,0, '.',''), // Timestamp, but trim two decimal places
			'ended'	=> (int) number_format($ended,0, '.',''), // Timestamp, but trim two decimal places
			'mailed'	=> (int) $mailed,
			'skipped'	=> (int) $skipped,
			'execution_time_secs'	=> (float) number_format($exec_time, 2, '.', ''), // Make sure not to lose 2 decimal digits from string value
			'memory_used_mb'	=> (float) number_format($memory_used, 2, '.', ''), // Make sure not to lose 2 decimal digits from string value
			'cron_type'	=> $cron_type,	// See the constants file: 1=system cron, 2=phpBB cron
		];

		// If the row is already in the table (unlikely), update it, otherwise insert the row
		$sql = 'SELECT * FROM ' . $this->report_table . ' WHERE ' .
				$this->db->sql_build_array('SELECT',	array('date_hour_sent_utc'	=> $utc_date_hour));
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		if (count($rowset) == 1)
		{
			$data['mailed'] = $data['mailed'] + $rowset[0]['mailed'];
			$data['skipped'] = $data['skipped'] + $rowset[0]['skipped'];

			$digests_report_id = $rowset[0]['digests_report_id'];
			$sql2 = 'UPDATE ' . $this->report_table . ' SET ' .
				$this->db->sql_build_array('UPDATE',	$data) . '
				WHERE digests_report_id = ' . (int) $digests_report_id;
			$this->db->sql_query($sql2);
		}
		else
		{
			$sql2 = 'INSERT INTO ' . $this->report_table . ' ' . $this->db->sql_build_array('INSERT', $data);
			$this->db->sql_query($sql2);
			$digests_report_id = $this->db->sql_nextid();
		}
		$this->db->sql_freeresult($result);

		// Save the report details data
		foreach ($details as $detail)
		{
			// We need the key from the report table row that was just inserted, as it's a foreign key that is needed here
			$detail['digests_report_id'] = (int) $digests_report_id;
			// We also need the user_id in the $detail array so we can use sql_build_array()
			$detail['user_id'] = key($details);

			// If row to insert already exists (should only be a manual mailer issue), delete it first.
			$sql = 'DELETE FROM ' . $this->report_details_table . ' WHERE ' . $this->db->sql_build_array('DELETE', array('digests_report_id' => (int) $digests_report_id, 'user_id' => key($details)));
			$this->db->sql_query($sql);

			// Insert row of fresh data
			$sql = 'INSERT INTO ' . $this->report_details_table . ' ' . $this->db->sql_build_array('INSERT', $detail);
			$this->db->sql_query($sql);
		}

	}

	private function remove_old_report_statistics($now)
	{

		// Removes report statistics older than the report statistics configuration setting
		//
		// $now = UNIX timestamp, usually for the current hour, but could be offset by a number of hours. It should
		//		  represent a top of hour timestamp only.

		$reporting_days = (int) $this->config['phpbbservices_digests_reporting_days'];
		$date_limit = max(0, $now - ($reporting_days * 24 * 60 * 60));

		if ($reporting_days > 0)
		{
			// Remove rows from report details table first to maintain referential integrity
			$sql = 'SELECT digests_report_id FROM ' . $this->report_table . ' WHERE date_hour_sent_utc <= ' . (int) $date_limit;
			$result = $this->db->sql_query($sql);
			$rowset = $this->db->sql_fetchrowset($result);
			$digests_report_ids = array();
			foreach ($rowset as $row)
			{
				$digests_report_ids[] = (int) $row['digests_report_id'];
			}

			if (count($rowset) > 0)
			{
				$sql2 = 'DELETE FROM ' . $this->report_details_table . ' WHERE ' . $this->db->sql_in_set('digests_report_id', $digests_report_ids);
				$this->db->sql_query($sql2);
			}

			// Now remove from the primary reports table
			$sql2 = 'DELETE FROM ' . $this->report_table . ' WHERE date_hour_sent_utc <= ' . (int) $date_limit;
			$this->db->sql_query($sql2);

			$this->db->sql_freeresult($result);
		}

	}

}