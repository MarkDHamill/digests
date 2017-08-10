<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\cron\task;

use phpbbservices\digests\constants\constants;

use phpbbservices\digests\includes\html_messenger;

class digests extends \phpbb\cron\task\base
{

	protected $auth;
	protected $config;
	protected $db;
	protected $helper;
	protected $language;
	protected $phpbb_log;
	protected $phpbb_root_path;
	protected $phpEx;
	protected $request;
	protected $template;
	protected $table_prefix;
	protected $user;

	// Most of these private variables are needed because the create_content function does much of the assembly work and it needs a lot of common information
	
	private $board_url;					// Digests need an absolute URL to the forum to embed links to topic, posts, forum and private messages
	private $cache_path;				// Relative path to the cache directory
	private $date_limit;				// A logical range of dates that posts must be within
	private $email_address_override;	// Used if admin wants manual mailer to send him/her a digest at an email address specified for this run
	private $email_templates_path;		// Relative path to where the language specific email templates are located
	private $forum_hierarchy;			// Provides a list of forums, their forum_ids and their parent_ids.
	private $layout_with_html_tables;	// Layout posts in the email as HTML tables, similar to the phpBB2 digests mod
	private $list_id;					// Used in determining forum access privileges for a subscriber
	private $max_posts;					// Maximum number of posts in a digest
	private $path_prefix;				// Appended to paths to find files in the correct location
	private $posts_in_digest;			// # of posts in a digest for a particular subscriber
	private $read_id;					// Used in determining forum access privileges for a subscriber
	private $requested_forums_names;	// If user specifies forums for posts wanted, this will contain the forum names
	private $run_mode;					// phpBB (regular) cron, system cron or manual
	private $server_timezone;			// Offset in hours from UTC for server
	private $time;						// Current time (or requested start time if running an out of cycle digest)
	private $toc;						// Table of contents array
	private $toc_pm_count;				// Table of contents private message count
	private $toc_post_count;			// Table of contents post count
	private $utc_time;					// UTC time when digest mailer run
	private $utc_month_lastday_end;		// The last day of the month when a monthly digest is wanted

	/**
	* Constructor.
	*
	* @param \phpbb\config\config 		$config 			The config
	* @param \phpbb\request\request 	$request 			The request object
	* @param \phpbb\user 				$user 				The user object
	* @param \phpbb\db\driver\factory 	$db 				The database factory object
	* @param $php_ext 										PHP file suffix
	* @param $phpbb_root_path								Relative path to phpBB root
	* @param \phpbb\template\template 	$template 			The template engine object
	* @param phpbb\auth\auth 			$auth 				The auth object
	* @param $table_prefix 									Prefix for phpbb's database tables
	* @param \phpbb\log\log 			$phpbb_log 			phpBB log object
	* @param \phpbbservices\digests\core\common $helper		Extension's helper object
	* @param \phpbb\language\language 	$language 			Language object
	*/

	public function __construct(\phpbb\config\config $config, \phpbb\request\request $request, \phpbb\user $user, \phpbb\db\driver\factory $db, $php_ext, $phpbb_root_path, \phpbb\template\template $template, \phpbb\auth\auth $auth, $table_prefix, \phpbb\log\log $phpbb_log, \phpbbservices\digests\core\common $helper, \phpbb\language\language $language)
	{
		$this->config = $config;
		$this->request = $request;
		$this->user = $user;
		$this->db = $db;
		$this->phpEx = $php_ext;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->template = $template;
		$this->auth = $auth;
		$this->table_prefix = $table_prefix;
		$this->phpbb_log = $phpbb_log;
		$this->helper = $helper;
		$this->language = $language;

		$this->forum_hierarchy = array();
		$this->run_mode = constants::DIGESTS_RUN_REGULAR;

		// Populate the forum hierarchy array. This is used when the full path to a forum is requested to be shown in digests
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

		// In system cron (CLI) mode, the $user object may not have an IP assigned. If so, use the server's IP. This will
		// allow logging to succeed since the IP is written to the log.
		if (is_null($this->user->ip))
		{
			$this->user->ip = $this->request->server('SERVER_ADDR');
		}
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
		$top_of_hour_ts = $this->top_of_hour_timestamp($this->config['phpbbservices_digests_cron_task_last_gc']);

		// Run this cron only if the current time is at or before the date and hour digests were last run, plus 1 hour.
		return (bool) ($top_of_hour_ts + $this->config['phpbbservices_digests_cron_task_gc'] <= time());
	}

	/**
	* Runs this cron task.
	*
	* @return true if successful, false if an error occurred
	*/
	public function run()
	{
		
		$now = time();

		// Need a board URL since URLs in the digest pointing to the board need to be absolute URLs
		$this->board_url = generate_board_url() . '/';
	
		$this->server_timezone = (float) date('O')/100;	// Server timezone offset from UTC, in hours. Digests are mailed based on UTC time, so rehosting is unaffected.
		
		// Determine how this is program is being executed. Options are:
		//   - DEFAULT: Regular cron (via invocation of cron.php as part of loading a web page in a browser) - constant::DIGESTS_RUN_REGULAR
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

		$this->path_prefix = ($this->run_mode == constants::DIGESTS_RUN_MANUAL) ? './../' : './';	// Because in manual mode you are in the adm folder
		
		$this->email_templates_path = $this->path_prefix . 'ext/phpbbservices/digests/language/en/email/';	// Note: the email templates (except subscribe/unsubscribe templates not used here) are language independent, so it's okay to use British English as it is always supported and the subscribe/unsubscribe templates are not used here.
		$this->cache_path = $this->path_prefix . 'cache/phpbbservices/digests/';

		// We need enough style information to keep get_user_style() from complaining that bbcode.html cannot be found. Ideally the default style
		// should be used to find templates but since it is looking for bbcode.html, styles always have prosilver in the inheritance tree and bbcode.html
		// is not a template that should ever be customized, it's safe to instruct the templating engine to use prosilver. In some run modes this information
		// can get lost, so it's best to add it explicitly.
		$this->user->style['style_path'] = 'prosilver';
		$this->user->style['style_parent_id'] = 0;
		$this->user->style['bbcode_bitfield'] = 'kNg=';

		if ($this->run_mode != constants::DIGESTS_RUN_MANUAL)
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
				$day_last_ran = (int) date('z', $this->config['phpbbservices_digests_cron_task_last_gc']);	// 0 thru 365/366
				$hour_last_ran = (int) date('g', $this->config['phpbbservices_digests_cron_task_last_gc']);
				$minute_last_ran = (int) date('i', $this->config['phpbbservices_digests_cron_task_last_gc']);
				$second_last_ran = (int) date('s', $this->config['phpbbservices_digests_cron_task_last_gc']);

				$year_now = (int) date('Y', $now);
				$day_now = (int) date('z', $now);	// 0 thru 365/366
				$hour_now = (int) date('g', $now);
				$minute_now = (int) date('i', $now);
				$second_now = (int) date('s', $now);

				// If the year or day differs from when digests was last run, or if these are the same but the hour differs, we look at the minute last ran
				// and compare it  with the minute now. If the minute now is less than the minute last run we have to increment $hours_to_do to capture the missing hour.
				if ($year_now != $year_last_ran || $day_now != $day_last_ran ||
					($year_now == $year_last_ran && $day_now == $day_last_ran && $hour_now != $hour_last_ran))
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
			$this->email_address_override = (trim($this->config['phpbbservices_digests_test_email_address']) != '') ? $this->config['phpbbservices_digests_test_email_address'] : $this->config['board_contact'];
		}

		// Display a digest mail start processing message. It is captured in a log.
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
		if ($hours_to_do >= 1)
		{
			for ($i=(1 - $hours_to_do); ($i <= 0); $i++)
			{
				$success = $this->mail_digests($now, $i);
				if (!$success)
				{
					// To do: Need logic to notify or email admins when mailing digests fails. There are probably emailing issues needing fixing.
					return false;
				}
				else if ($this->run_mode !== constants::DIGESTS_RUN_MANUAL)
				{
					// Note that the hour was processed successfully. If run manually, we don't want to mess with the configuration variable.
					$this->config->set('phpbbservices_digests_cron_task_last_gc', $now + ($i * 60 * 60));
				}
			}
		}
		else
		{
			// This condition should never occur as it suggests a programmatic bug, but if it does let's at least be aware of it.
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_INCONSISTENT_DATES', false, array($this->config['phpbbservices_digests_cron_task_last_gc'], $now));
		}

		// Display a digest mail end processing message. It is captured in a log.
		if ($this->config['phpbbservices_digests_enable_log'])
		{
			$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
		}

		return true;
			
	}

	private function mail_digests($now, $hour)
	{
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//                                                                                                                  //
		// This method is what used to be mail_digests.php. It will mail all the digests for the given year, date and hour  //
		// offset by the $hour parameter.                                                                                   //
		//                                                                                                                  //
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		static $daily_digest_sql, $weekly_digest_sql, $now_info;

		// Reset to a maximum execution time for this function, since we don't know how many digests
		// must be processed for a particular hour or how long it may take. Other PHP settings may overrule this.
		@set_time_limit(0);

		if (!isset($now_info))
		{
			$now_info = getdate($now);
		}

		// We track the last language used in the digest. It's possible a forum will support multiple languages.
		// If so we'll change the language files to accommodate the subscriber.
		$last_language = '';
		
		// If it was requested, get the year, month, date and hour of the digests to recreate. If it was not requested, simply use the current time. Note:
		// if used it must be as a result of a manual run of the mailer.
		if (($this->run_mode == constants::DIGESTS_RUN_MANUAL) && ($this->config['phpbbservices_digests_test_time_use']))
		{
			$this->time = mktime($this->config['phpbbservices_digests_test_hour'], 0, 0, $this->config['phpbbservices_digests_test_month'], $this->config['phpbbservices_digests_test_day'], $this->config['phpbbservices_digests_test_year']);
			if ($this->config['phpbbservices_digests_enable_log'])
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_SIMULATION_DATE_TIME', false, array(str_pad($this->config['phpbbservices_digests_test_year'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($this->config['phpbbservices_digests_test_month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($this->config['phpbbservices_digests_test_day'], 2, '0', STR_PAD_LEFT), $this->config['phpbbservices_digests_test_hour']));
			}
		}
		else
		{
			$this->time = $now + ($hour * (60 * 60));	// Timestamp for hour to be processed
		}

		$this->utc_time = $this->time - (int) ($this->server_timezone * 60 * 60);	// Convert server time (or requested run date) into UTC

		// Get the current hour in UTC, so applicable digests can be sent out for this hour
		$current_hour_utc = date('G', $this->utc_time); // 0 thru 23
		$current_hour_utc_plus_30 = date('G', $this->utc_time) + .5;
		if ($current_hour_utc_plus_30 >= 24)
		{
			$current_hour_utc_plus_30 = $current_hour_utc_plus_30 - 24;	// A very unlikely situation
		}
		
		// Create SQL fragment to fetch users wanting a daily digest
		if (!(isset($daily_digest_sql)))
		{
			$daily_digest_sql = '(' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_DAILY_VALUE)) . ')';
		}
		
		// Create SQL fragment to also fetch users wanting a weekly digest, if today is the day weekly digests should go out
		if (!(isset($weekly_digest_sql)))
		{
			$weekly_digest_sql = (date('w', $this->utc_time) == $this->config['phpbbservices_digests_weekly_digest_day']) ? ' OR (' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_WEEKLY_VALUE)) . ')': '';
		}
		
		// Create SQL fragment to also fetch users wanting a monthly digest. This only happens if the current UTC day is the first of the month.
		$utc_year = (int) date('Y', $this->utc_time);
		$utc_month = (int) date('m', $this->utc_time);	// Two-digit month, with leading zeroes
		$utc_day = (int) date('d', $this->utc_time);		// Two-digit day of month, with leading zeroes
		$utc_hour = (int) date('H', $this->utc_time);	// Two digit 24 hour, with leading zeroes
		
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

		$formatted_date = date('Y-m-d', $this->utc_time);	// Format is YYYY-MM-DD with 2 digit months and days
		$formatted_hour = date('H', $this->utc_time);	// Use a two-digit 24 hour.

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
			
				'WHERE'		=> 's.style_id = ' . $this->config['default_style'] . ' 
								AND (' . 
									$daily_digest_sql . $weekly_digest_sql . $monthly_digest_sql . 
								") 
								AND (user_digest_send_hour_gmt = $current_hour_utc OR user_digest_send_hour_gmt = $current_hour_utc_plus_30) 
								AND user_inactive_reason = 0 " . $allowed_user_types . "
								AND user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "'",
			
				'ORDER_BY'	=> ' user_lang',
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
								AND (user_digest_send_hour_gmt = $current_hour_utc OR user_digest_send_hour_gmt = $current_hour_utc_plus_30) 
								AND user_inactive_reason = 0 " . $allowed_user_types . "
								AND user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "'",
			
				'ORDER_BY'	=> 'user_lang',
			);
		}
		
		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);	// Gets users and their metadata that are receiving digests for this hour

		// Fetch all the posts (no private messages) but do it just once for efficiency. These will be filtered later 
		// to remove those posts a particular user should not see.

		// First, determine a maximum date range fetched: daily, weekly or monthly
		if ($monthly_digest_sql != '')
		{
			// In the case of monthly digests, it's important to include posts that support daily and weekly digests as well, hence dates of posts
			// retrieved may exceed post dates for the previous month. Logic to exclude posts past the end of the previous month in the case of 
			// monthly digests must be handled in the create_content function to skip these.
			$date_limit_sql = ' AND p.post_time >= ' . $utc_month_1st_begin . ' AND p.post_time <= ' . max($this->utc_month_lastday_end, $this->utc_time);
		}
		else if ($weekly_digest_sql != '')	// Weekly
		{
			$this->date_limit = $this->time - (7 * 24 * 60 * 60);
			$date_limit_sql = ' AND p.post_time >= ' . $this->date_limit . ' AND p.post_time < ' . $this->time;
		}
		else	// Daily
		{
			$this->date_limit = $this->time - (24 * 60 * 60);
			$date_limit_sql = ' AND p.post_time >= ' . $this->date_limit. ' AND p.post_time < ' . $this->time;
		}

		// Now get all potential posts and related data for all users and place them in an array for parsing. Later the create_content function will filter out the stuff
		// that should not go in a particular digest, based on permissions and options the user selected.
		
		// Prepare SQL
		$sql_array = array(
			'SELECT'	=> 'f.*, t.*, p.*, u.*',
		
			'FROM'		=> array(
				POSTS_TABLE 	=> 'p',
				USERS_TABLE 	=> 'u',
				TOPICS_TABLE 	=> 't',
				FORUMS_TABLE 	=> 'f'),
		
			'WHERE'		=> "f.forum_id = t.forum_id
								AND p.topic_id = t.topic_id 
								AND p.poster_id = u.user_id
								$date_limit_sql
								AND p.post_visibility = 1
								AND forum_password = ''",
		
			'ORDER_BY'	=> 'f.left_id, f.right_id'
		);
		
		// Build query
		$sql_posts = $this->db->sql_build_query('SELECT', $sql_array);
		
		$result_posts = $this->db->sql_query($sql_posts);	// Fetch the data
		$posts_rowset = $this->db->sql_fetchrowset($result_posts); // Get all the posts as a set
		
		// Now that we have all the posts, time to send one digest at a time
		
		foreach ($rowset as $row)
		{

			// Each traverse through this loop sends out exactly one digest

			// While it shouldn't happen, we need a circuit breaker to prevent duplicate digests from going out
			// for the same hour for the same subscriber. So if the day and hour for the last time the digest was sent
			// for this user is the same as the current day and hour, presumably the subscriber has already received a
			// digest so skip sending it.

			$user_digest_last_sent = getdate($row['user_digest_last_sent']);
			if ($this->run_mode != constants::DIGESTS_RUN_MANUAL)
			{
				if ($user_digest_last_sent['year'] == $now_info['year'] && $user_digest_last_sent['yday'] == $now_info['yday'] && $user_digest_last_sent['hours'] == $now_info['hours'])
				{
					// Note the inconsistency in the log.
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_DUPLICATE_PREVENTED', false, array($row['username'], $row['user_email'], $utc_year . '-' . $utc_month . '-' . $utc_day, $current_hour_utc));
					continue;
				}
			}

			// Skip sending this digest if a full "cycle" has not elapsed since when the digest was last sent out. For example, if the user has 
			// subscribed to a daily digest, 24 hours needs to have elapsed since the last digest went out. The digest last sent time is recorded
			// in the database when sent out.

			$top_of_hour_ts = $this->top_of_hour_timestamp($row['user_digest_last_sent']);
			switch ($row['user_digest_type'])
			{
				case constants::DIGESTS_WEEKLY_VALUE:
					if ($top_of_hour_ts + (7 * 60 * 60 * 24) > $now)
					{
						continue;
					}
				break;
				
				case constants::DIGESTS_MONTHLY_VALUE:
					// Calculate seconds in previous month, which depends on number of days in that month
					$use_year = date('Y', $now);
					$use_month = date('n', $now) - 1;
					if ($use_month == 0)
					{
						$use_month = 12;
						$use_year--;
					}
					$use_days_in_month = cal_days_in_month (CAL_GREGORIAN, $use_month, $use_year);
					if ($top_of_hour_ts + ($use_days_in_month * 60 * 60 * 24) > $now)
					{
						continue;
					}
				break;

				case constants::DIGESTS_DAILY_VALUE:
				default:
					if ($top_of_hour_ts + (60 * 60 * 24) > $now)
					{
						continue;
					}
				break;
			}

			// Load the appropriate language files based on the user's preferred language. The board default language
			// is probably English, which may not be what we want since phpBB supports multiple languages depending on
			// the language packs installed and which language the user chooses.
			if ($row['user_lang'] != $last_language)
			{
				$this->language->set_user_language($row['user_lang'], true);
				$this->language->add_lang('common');
				$this->language->add_lang(array('common', 'acp/common'), 'phpbbservices/digests');
				$last_language = $row['user_lang'];
			}

			$this->toc = array();		// Create or empty the array containing table of contents information
			$this->toc_post_count = 0; 	// # of posts in the table of contents for subscriber
			$this->toc_pm_count = 0; 	// # of private messages in the table of contents for subscriber

			// The extended messenger class is used to send the digests. It is extended to allow HTML emails to be sent.
			$html_messenger = new \phpbbservices\digests\includes\html_messenger();

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
					$digest_type = '';	// Make PhpStorm happy
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_BAD_DIGEST_TYPE', false, array($row['user_digest_type'], $row['username']));
					continue;
				break;
			}

			$digest_type = ($this->config['phpbbservices_digests_lowercase_digest_type']) ? strtolower($digest_type) : $digest_type;
			$email_subject = $this->language->lang('DIGESTS_SUBJECT_TITLE', $this->config['sitename'], $digest_type);
		
			// Set various variables and flags based on the requested digest format.
			
			switch($row['user_digest_format'])
			{
			
				case constants::DIGESTS_TEXT_VALUE:
					$format = $this->language->lang('DIGESTS_FORMAT_TEXT');
					$html_messenger->template('digests_text', '', $this->email_templates_path);
					$is_html = false;
					$disclaimer = str_replace('&rsquo;', "'", strip_tags($this->language->lang('DIGESTS_DISCLAIMER', $this->board_url, $this->config['sitename'], $this->phpEx, $this->config['board_contact'])));
					$powered_by = $this->config['phpbbservices_digests_host'];
					$this->layout_with_html_tables = false;
				break;
				
				case constants::DIGESTS_PLAIN_VALUE:
					$format = $this->language->lang('DIGESTS_FORMAT_PLAIN');
					$html_messenger->template('digests_plain_html', '', $this->email_templates_path);
					$is_html = true;
					$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER', $this->board_url, $this->config['sitename'], $this->phpEx, $this->config['board_contact']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['phpbbservices_digests_page_url'], $this->config['phpbbservices_digests_host']);
					$this->layout_with_html_tables = false;
				break;
				
				case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
					$format = $this->language->lang('DIGESTS_FORMAT_PLAIN_CLASSIC');
					$html_messenger->template('digests_plain_html', '', $this->email_templates_path);
					$is_html = true;
					$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER', $this->board_url, $this->config['sitename'], $this->phpEx, $this->config['board_contact']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['phpbbservices_digests_page_url'], $this->config['phpbbservices_digests_host']);
					$this->layout_with_html_tables = true;
				break;
				
				case constants::DIGESTS_HTML_VALUE:
					$format = $this->language->lang('DIGESTS_FORMAT_HTML');
					$html_messenger->template('digests_html', '', $this->email_templates_path);
					$is_html = true;
					$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER', $this->board_url, $this->config['sitename'], $this->phpEx, $this->config['board_contact']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['phpbbservices_digests_page_url'], $this->config['phpbbservices_digests_host']);
					$this->layout_with_html_tables = false;
				break;
				
				case constants::DIGESTS_HTML_CLASSIC_VALUE:
					$format = $this->language->lang('DIGESTS_FORMAT_HTML_CLASSIC');
					$html_messenger->template('digests_html', '', $this->email_templates_path);
					$is_html = true;
					$disclaimer = $this->language->lang('DIGESTS_DISCLAIMER', $this->board_url, $this->config['sitename'], $this->phpEx, $this->config['board_contact']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['phpbbservices_digests_page_url'], $this->config['phpbbservices_digests_host']);
					$this->layout_with_html_tables = true;
				break;
				
				default:
					// The database may be corrupted if the digest format for a subscriber is invalid. 
					// Write an error to the log and continue to the next subscriber.
					$format = '';		// Keep PhpStorm happy
					$is_html = false;	// Keep PhpStorm happy
					$disclaimer = '';	// Keep PhpStorm happy
					$powered_by = '';	// Keep PhpStorm happy
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FORMAT_ERROR', false, array($row['user_digest_type'], $row['username']));
					continue;
				break;
				
			}
	
			// Set email header information
			$from_field_email = (isset($this->config['phpbbservices_digests_from_email_address']) && (strlen($this->config['phpbbservices_digests_from_email_address']) > 0)) ? $this->config['phpbbservices_digests_from_email_address'] : $this->config['board_email'];
			$from_field_name = (isset($this->config['phpbbservices_digests_from_email_name']) && (strlen($this->config['phpbbservices_digests_from_email_name']) > 0)) ? $this->config['phpbbservices_digests_from_email_name'] : $this->config['sitename'] . ' ' . $this->language->lang('DIGESTS_ROBOT');
			$reply_to_field_email = (isset($this->config['phpbbservices_digests_reply_to_email_address']) && (strlen($this->config['phpbbservices_digests_reply_to_email_address']) > 0)) ? $this->config['phpbbservices_digests_reply_to_email_address'] : $this->config['board_email'];
		
			// Admin may override where email is sent in manual mode. This won't apply if digests are stored to the cache/phpbbservices/digests folder instead.
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

			// Change the filter type into something a human can read
			switch($row['user_digest_filter_type'])
			{
			
				case constants::DIGESTS_ALL:
					$post_types = $this->language->lang('DIGESTS_POSTS_TYPE_ANY');
				break;
				
				case constants::DIGESTS_FIRST:
					$post_types = $this->language->lang('DIGESTS_POSTS_TYPE_FIRST');
				break;
				
				case constants::DIGESTS_BOOKMARKS:
					$post_types = $this->language->lang('DIGESTS_USE_BOOKMARKS');
				break;
				
				default:
					// The database may be corrupted if the filter type for a subscriber is incorrect. 
					// Write an error to the log and continue to the next subscriber.
					$post_types = '';	// Keep PhpStorm happy
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FILTER_ERROR', false, array($row['user_digest_filter_type'], $row['username']));
					continue;
				break;
					
			}
			
			// Change the sort by into something a human can read
			switch ($row['user_digest_sortby'])
			{
			
				case constants::DIGESTS_SORTBY_BOARD:
					$sort_by = $this->language->lang('DIGESTS_SORT_USER_ORDER');
				break;
					
				case constants::DIGESTS_SORTBY_STANDARD:
					$sort_by = $this->language->lang('DIGESTS_SORT_FORUM_TOPIC');
				break;
					
				case constants::DIGESTS_SORTBY_STANDARD_DESC:
					$sort_by = $this->language->lang('DIGESTS_SORT_FORUM_TOPIC_DESC');
				break;
					
				case constants::DIGESTS_SORTBY_POSTDATE:
					$sort_by = $this->language->lang('DIGESTS_SORT_POST_DATE');
				break;
					
				case constants::DIGESTS_SORTBY_POSTDATE_DESC:
					$sort_by = $this->language->lang('DIGESTS_SORT_POST_DATE_DESC');
				break;
					
				default:
					// The database may be corrupted if the digest sort by for a subscriber is incorrect. 
					// Write an error to the log and continue to the next subscriber.
					$sort_by = '';	// Make PhpStorm happy
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_SORT_BY_ERROR', false, array($row['user_digest_sortby'], $row['username']));
					continue;
				break;
					
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
				$this->max_posts = 0;	// 0 means no limit
				$max_posts_msg = $this->language->lang('DIGESTS_NO_LIMIT');
			}
			else if (($this->config['phpbbservices_digests_max_items'] != 0) && $this->config['phpbbservices_digests_max_items'] < $row['user_digest_max_posts'])
			{
				$this->max_posts = (int) $row['phpbbservices_digests_max_items'];
				$max_posts_msg = $this->language->lang('DIGESTS_BOARD_LIMIT', $this->config['phpbbservices_digests_max_items']);
			}
			else
			{
				$this->max_posts = (int) $row['user_digest_max_posts'];
				$max_posts_msg = $row['user_digest_max_posts'];
			}

			$recipient_time = $this->utc_time + (float) ($this->helper->make_tz_offset($row['user_timezone']) * 60 * 60);

			// Identify the language translator, if one exists and they choose to identify his/herself
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

			// Print the non-post and non-private message information in the digest. The actual posts and private messages require the full templating system,
			// and is handled in the create_content function.
			$html_messenger->assign_vars(array(
				'S_CONTENT_DIRECTION'					=> $this->language->lang('DIRECTION'),
				'S_DIGESTS_BLOCK_IMAGES'				=> $row['user_digest_block_images'],
				'S_DIGESTS_COUNT_LIMIT'					=> $max_posts_msg,
				'S_DIGESTS_DISCLAIMER'					=> $disclaimer,
				'S_DIGESTS_FILTER_FOES'					=> $row['user_digest_remove_foes'],
				'S_DIGESTS_FILTER_TYPE'					=> $post_types,
				'S_DIGESTS_FORMAT_FOOTER'				=> $format,
				'S_DIGESTS_INTRODUCTION'				=> ($is_html) ? $this->language->lang('DIGESTS_INTRODUCTION', $this->config['sitename']) : strip_tags($this->language->lang('DIGESTS_INTRODUCTION', $this->config['sitename'])),
				'S_DIGESTS_LASTVISIT_RESET'				=> $row['user_digest_reset_lastvisit'],
				'S_DIGESTS_MAIL_FREQUENCY'				=> $digest_type,
				'S_DIGESTS_MAX_SIZE'					=> $row['user_digest_no_post_text'] ? $this->language->lang('DIGESTS_NO_POST_TEXT') : (($row['user_digest_max_display_words'] == 0) ? $this->language->lang('DIGESTS_NO_LIMIT') : $row['user_digest_max_display_words']),
				'S_DIGESTS_MIN_SIZE'					=> !$row['user_digest_min_words'] ? $this->language->lang('DIGESTS_NO_CONSTRAINT') : $row['user_digest_min_words'],
				'S_DIGESTS_PMS_MARK_READ'				=> $row['user_digest_pm_mark_read'] ? true : false,
				'S_DIGESTS_POWERED_BY'					=> $powered_by,
				'S_DIGESTS_PUBLISH_DATE'				=> $publish_date,
				'S_DIGESTS_REMOVE_YOURS'				=> !$row['user_digest_show_mine'] ? true : false,
				'S_DIGESTS_SALUTATION_BLURB'			=> $row['username'] . $this->language->lang('DIGESTS_COMMA'),
				'S_DIGESTS_SEND_HOUR'					=> (trim($row['user_dateformat'] == '')) ? $this->helper->make_hour_string($local_send_hour, $this->config['board_timezone']) : $this->helper->make_hour_string($local_send_hour, $row['user_dateformat']),
				'S_DIGESTS_SEND_IF_NO_NEW_MESSAGES'		=> $row['user_digest_send_on_no_posts'],
				'S_DIGESTS_SHOW_ATTACHMENTS'			=> $row['user_digest_attachments'],
				'S_DIGESTS_SHOW_NEW_POSTS_ONLY'			=> $row['user_digest_new_posts_only'] ? true : false,
				'S_DIGESTS_SHOW_PMS'					=> $row['user_digest_show_pms'] ? true : false,
				'S_DIGESTS_SORT_BY'						=> $sort_by,
				'S_DIGESTS_TITLE'						=> $email_subject,
				'S_DIGESTS_TOC'							=> $row['user_digest_toc'] ? true : false,
				'S_DIGESTS_TRANSLATOR'					=> ($is_html) ? $translator : strip_tags($translator),
				'S_DIGESTS_YOUR_DIGEST_OPTIONS'			=> ($is_html) ? $this->language->lang('DIGESTS_YOUR_DIGEST_OPTIONS', $row['username']) : str_replace('&rsquo;', "'", $this->language->lang('DIGESTS_YOUR_DIGEST_OPTIONS', $row['username'])),
				'S_USER_LANG'							=> $user_lang,
				'T_STYLESHEET_LINK'						=> ($this->config['phpbbservices_digests_enable_custom_stylesheets']) ? "{$this->board_url}styles/" . $this->config['phpbbservices_digests_custom_stylesheet_path'] : "{$this->board_url}styles/" . $row['style_path'] . '/theme/stylesheet.css',
				'T_THEME_PATH'							=> "{$this->board_url}styles/" . $row['style_path'] . '/theme',
			));

			// Get any private messages for this user
			
			// Count # of unread and new for this user. Counts may need to be reduced later.
			$total_pm_unread = 0;
			$total_pm_new = 0;

			if ($row['user_digest_show_pms'])
			{
			
				$sql_array = array(
					'SELECT'	=> '*',
				
					'FROM'		=> array(
						PRIVMSGS_TO_TABLE	=> 'pt',
						PRIVMSGS_TABLE		=> 'pm',
						USERS_TABLE			=> 'u',
					),
				
					'WHERE'		=> 'pt.msg_id = pm.msg_id
										AND pt.author_id = u.user_id
										AND pt.user_id = ' . $row['user_id'] . '
										AND (pm_unread = 1 OR pm_new = 1)',
				
					'ORDER_BY'	=> 'message_time',
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
				}
				$this->db->sql_freeresult($pm_result);

			}
			else
			{
				// Avoid some PHP Notices...
				$pm_result = NULL;
				$pm_rowset = NULL;
			}

			// Construct the body of the digest. We use the templating system because of the advanced features missing in the 
			// email templating system, e.g. loops and switches.
			$digest_content = $this->create_content($posts_rowset, $pm_rowset, $row, $is_html);
		
			// List the subscribed forums, if any
			if ($row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS)
			{
				$subscribed_forums = $this->language->lang('DIGESTS_USE_BOOKMARKS');
			}
			else if (sizeof($this->requested_forums_names) > 0)
			{
				$subscribed_forums = implode(', ', $this->requested_forums_names);
			}
			else
			{
				// Show that all forums were selected
				$subscribed_forums = $this->language->lang('DIGESTS_ALL_FORUMS');
			}
			
			// Assemble a digest table of contents
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
							$this->language->lang('DIGESTS_JUMP_TO_MSG') , $this->language->lang('DIGESTS_PM_SUBJECT'), $this->language->lang('DIGESTS_SENDER'), $this->language->lang('DIGESTS_DATE'));
					}
					
					// Add a table row for each private message
					if ($this->toc_pm_count > 0)
					{
						for ($i=0; $i <= $this->toc_pm_count; $i++)
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
				else
				{
					$digest_toc = null;	// Avoid a PHP Notice
				}
				
				// Create Table of Contents header for posts
				if ($is_html)
				{
					// For HTML digests, the table of contents always appears in a HTML table
					$digest_toc .= sprintf("<table>\n<tbody>\n<tr>\n<th id=\"h1\">%s</th><th id=\"h2\">%s</th><th id=\"h3\">%s</th><th id=\"h4\">%s</th><th id=\"h5\">%s</th>\n</tr>\n",
						$this->language->lang('DIGESTS_JUMP_TO_POST') , $this->language->lang('FORUM'), $this->language->lang('TOPIC'), $this->language->lang('AUTHOR'), $this->language->lang('DIGESTS_DATE'));
				}
				
				// Add a table row for each post
				if ($this->toc_post_count > 0)
				{
					for ($i=0; $i <= $this->toc_post_count; $i++)
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
					'DIGESTS_TOC'			=> $digest_toc,	
				));
			
			}
			else
			{
				$digest_toc = null;	// Avoid a PHP Notice
			}
			
			if (!$is_html)
			{
				// This reduces extra lines in the text digests. Apparently the phpBB template engine leaves
				// blank lines where a template contains templates commands.
				$digest_content = str_replace("\n\n", "\n", $digest_content);
			}
		
			// Publish the digest content, assembled elsewhere and a list of the forums subscribed to.
			$html_messenger->assign_vars(array(
				'DIGESTS_CONTENT'					=> $digest_content,	
				'S_DIGESTS_FORUMS_WANTED'			=> $subscribed_forums,
			));
			
			// Mark private messages in the digest as read, if so instructed
			if ((sizeof($pm_rowset) != 0) && ($row['user_digest_show_pms'] == 1) && ($row['user_digest_pm_mark_read'] == 1))
			{
				
				$sql_ary = array(
					'pm_new'		=> 0,
					'pm_unread'		=> 0,
					'folder_id'		=> 0,
				);
				
				$pm_read_sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . '
					SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . $row['user_id'] . '
						AND (pm_unread = 1 OR pm_new = 1)';
						
				$this->db->sql_query($pm_read_sql);

				// Reduce the user_unread_privmsg and user_new_privmsg count by the amount of PMs in the digest.
				// Be careful not to store a negative number in case the database is inconsistent. Note: the nature
				// of this SQL is that using $db->sql_build_array won't generate the desired SQL, so we go rogue.

				$pm_read_sql = 'UPDATE ' . USERS_TABLE . '
					SET user_unread_privmsg = user_unread_privmsg - ' . min($total_pm_unread, $row['user_unread_privmsg']) . ', 
						user_new_privmsg = user_new_privmsg - ' . min($total_pm_new, $row['user_new_privmsg']).

				$this->db->sql_query($pm_read_sql);

				$this->db->sql_freeresult($result_posts);

			}

			if (($this->run_mode == constants::DIGESTS_RUN_MANUAL) && ($this->config['phpbbservices_digests_test_spool']))
			{
				
				// To grab the content of the email (less mail headers) first run the mailer with the $break parameter set to true. This will keep 
				// the mail from being sent out. The function won't fail since nothing is being sent out.
				$html_messenger->send(NOTIFY_EMAIL, true, $is_html, true);
				$email_content = $html_messenger->msg;

				// Save digests as file in the cache/phpbbservices/digests folder instead of emailing.
				$suffix = ($is_html) ? '.html' : '.txt';
				$file_name = $row['username'] . '-' . $utc_year . '-' . str_pad($utc_month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($utc_day, 2, '0', STR_PAD_LEFT) . '-' . str_pad($utc_hour, 2, '0', STR_PAD_LEFT) . $suffix;
				
				$handle = @fopen($this->cache_path . $file_name, "w");
				if ($handle === false)
				{
					// Since this indicates a major problem, let's abort now. It's likely a global write error.
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FILE_OPEN_ERROR', false, array($this->cache_path));
					if ($this->config['phpbbservices_digests_enable_log'])
					{
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
					}
					return false;
				}
				
				$success = @fwrite($handle, htmlspecialchars_decode($email_content));
				if ($success === false)
				{
					// Since this indicates a major problem, let's abort now.  It's likely a global write error.
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FILE_WRITE_ERROR', false, array($this->cache_path . $file_name));
					if ($this->config['phpbbservices_digests_enable_log'])
					{
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
					}
					return false;
				}
				
				$success = @fclose($handle);
				if ($success === false)
				{
					// Since this indicates a major problem, let's abort now
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_FILE_CLOSE_ERROR', false, array($this->cache_path . $file_name));
					if ($this->config['phpbbservices_digests_enable_log'])
					{
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_END');
					}
					return false;
				}
				
				// Note in the log that a digest was written to the cache folder
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
				if ($row['user_digest_send_on_no_posts'] || $this->toc_post_count > 0 || ((sizeof($pm_rowset) > 0) && $row['user_digest_show_pms']))
				{
					
					$mail_sent = $html_messenger->send(NOTIFY_EMAIL, false, $is_html, true);	 // digest mailed
					
					if (!$mail_sent)
					{
						// Something went wrong when sending the digest. Errors always go in the log.
						if ($this->config['phpbbservices_digests_show_email'])
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD', false, array($row['username'], $row['user_email']));
						}
						else
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD_NO_EMAIL', false, array($row['username']));
						}
					}
					else
					{
						// Digest should have been mailed successfully
						if ($this->config['phpbbservices_digests_enable_log'])
						{
							if ($this->config['phpbbservices_digests_show_email'])
							{
								$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD', false, array($this->language->lang('DIGESTS_SENT_TO'), $row['username'], $row['user_email'], $utc_year . '-' . $utc_month . '-' . $utc_day, $current_hour_utc, $this->posts_in_digest, sizeof($pm_rowset)));
							}
							else
							{
								$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_NO_EMAIL', false, array($this->language->lang('DIGESTS_SENT_TO'), $row['username'], $utc_year . '-' . $utc_month . '-' . $utc_day, $current_hour_utc, $this->posts_in_digest, sizeof($pm_rowset)));
							}
						}

						// Record the moment the digest was successfully sent to the subscriber
						$sql_ary = array(
							'user_digest_last_sent'		=> time(),
						);
						
						$sql2 = 'UPDATE ' . USERS_TABLE . '
							SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . $row['user_id'];
						$this->db->sql_query($sql2);
			
						// If requested, update user_lastvisit
						if ($row['user_digest_reset_lastvisit'] == 1)
						{
							$sql_ary = array(
								'user_lastvisit'		=> time(),
							);
							
							$sql2 = 'UPDATE ' . USERS_TABLE . '
								SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
								WHERE user_id = ' . $row['user_id'];
							$this->db->sql_query($sql2);
						}
					}

				}
				else
				{
					// Don't send a digest -- the user doesn't want one because there are no qualifying posts
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
					
			}

			// Reset messenger object for the next subscriber, bug fix provided by robdocmagic
			$html_messenger->reset();

		}	// foreach
		
		return true;	// Successful run if all digests were processed for the requested hour.
		
	}
	
	private function create_content(&$posts_rowset, &$pm_rowset, &$user_row, $is_html)
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

		$topic_first_poster_name = array();	// Keep PhpStorm happy
		$username_clean = array();			// Keep PhpStorm happy
		$post_time = array();				// Keep PhpStorm happy
		$post_subject = array();			// Keep PhpStorm happy
		$topic_last_post_time = array();	// Keep PhpStorm happy
		$topic_replies = array();			// Keep PhpStorm happy
		$topic_title = array();				// Keep PhpStorm happy
		$topic_views = array();				// Keep PhpStorm happy

		// Choose a template based on whether a HTML or text digest will be sent
		$mail_template = ($is_html) ? 'mail_digests_html.html' : 'mail_digests_text.html';
		$this->template->set_filenames(array(
		   'mail_digests'      => $mail_template,
		));
			
		$show_post_text = ($user_row['user_digest_no_post_text'] == 0);
		
		$this->posts_in_digest = 0;

		// Process private messages (if any) first since they appear before posts in the digest
		
		if ((sizeof($pm_rowset) != 0) && ($user_row['user_digest_show_pms'] == 1))
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
				$user_sig = ($pm_row['enable_sig'] && $pm_row['user_sig'] != '' && $this->config['allow_sig']) ? censor_text($pm_row['user_sig']) : '';
				if ($user_sig != '')
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
				$pm_text = ($user_sig != '') ? $pm_text . "\n" . $this->language->lang('DIGESTS_POST_SIGNATURE_DELIMITER') . "\n" . $user_sig : $pm_text . "\n";
	
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
	
		if (sizeof($posts_rowset) != 0)
		{
			
			unset($bookmarked_topics);
			unset($fetched_forums);
			$fetched_forums = array();
			
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
						AND b.user_id = ' . $user_row['user_id'],
				);
				
				$sql3 = $this->db->sql_build_query('SELECT', $sql_array);
				$result3 = $this->db->sql_query($sql3);
				
				while ($row3 = $this->db->sql_fetchrow($result3))
				{
					$bookmarked_topics[] = (int) $row3['topic_id'];
				}
				$this->db->sql_freeresult($result3);
				
				if (sizeof($bookmarked_topics) == 0)
				{
					// Logically, if there are no bookmarked topics for this user_id then there will be nothing in the digest. Flag an exception and
					// make a note in the log about this inconsistency. Subscriber should still get a digest with a no bookmarked posts message.
					$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGEST_NO_BOOKMARKS', false, array($user_row['username']));
				}
				
			}
			
			else
			
			{
				
				// Determine the forums allowed this subscriber is allowed to read
				
				// Get forum read permissions for this user. They are also usually stored in the user_permissions column, but sometimes the field is empty. This always works.
				unset($allowed_forums);
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
			
				if (sizeof($allowed_forums) == 0)
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
				$this->requested_forums_names = array();
				
				$sql_array = array(
					'SELECT'	=> 's.forum_id, forum_name',
				
					'FROM'		=> array(
						$this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE	=> 's',
						FORUMS_TABLE														=> 'f',
					),
				
					'WHERE'		=> 's.forum_id = f.forum_id 
										AND user_id = ' . $user_row['user_id'],
				);
				
				$sql3 = $this->db->sql_build_query('SELECT', $sql_array);

				$result3 = $this->db->sql_query($sql3);
				while ($row3 = $this->db->sql_fetchrow($result3))
				{
					$requested_forums[] = $row3['forum_id'];
					$this->requested_forums_names[] = $row3['forum_name'];
				}
				$this->db->sql_freeresult($result3);
				$requested_forums[] = 0;	// Add in global announcements forum
				
				// Ensure there are no duplicates
				$requested_forums = array_unique($requested_forums);
				
				// The forums that will be fetched is the array intersection of the requested and allowed forums. There should be at least one forum
				// allowed because the global announcements pseudo forum is common to both. However, if the user did not specify any forums then the allowed 
				// forums become the ones fetched.
				$fetched_forums = (sizeof($requested_forums) > 1) ? array_intersect($allowed_forums, $requested_forums) : $allowed_forums;
				asort($fetched_forums);
				
				// Add in any required forums
				$required_forums = (isset($this->config['phpbbservices_digests_include_forums'])) ? explode(',',$this->config['phpbbservices_digests_include_forums']) : array();
				if (sizeof($required_forums) > 0)
				{
					$fetched_forums = array_merge($fetched_forums, $required_forums);
				}
				
				// Remove any prohibited forums
				$excluded_forums = (isset($this->config['phpbbservices_digests_exclude_forums'])) ? explode(',',$this->config['phpbbservices_digests_exclude_forums']) : array();
				if (sizeof($excluded_forums) > 0)
				{
					$fetched_forums = array_diff($fetched_forums, $excluded_forums);
				}
				
				// Tidy up the forum list
				$fetched_forums = array_unique($fetched_forums);
				
			}

			// Sort posts by the user's preference.

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
			
			// Fetch foes, if any but only if they want foes filtered out
			unset($foes);
			if ($user_row['user_digest_remove_foes'] == 1)
			{
			
				// Fetch your foes
				$sql_array = array(
					'SELECT'	=> 'zebra_id',
				
					'FROM'		=> array(
						ZEBRA_TABLE	=> 'z',
					),
				
					'WHERE'		=> 'user_id = ' . $user_row['user_id'] . ' AND foe = 1',
				);
			
				$sql3 = $this->db->sql_build_query('SELECT', $sql_array);
				$result3 = $this->db->sql_query($sql3);
				while ($row3 = $this->db->sql_fetchrow($result3))
				{
					$foes[] = (int) $row3['zebra_id'];
				}
				$this->db->sql_freeresult($result3);
						
			}
		
			// Put posts in the digests, assuming they should not be filtered out
			
			foreach ($posts_rowset as $post_row)
			{

				// If we've hit the limit of the maximum number of posts in a digest, it's time to exit the loop. If $this->max_posts == 0 there is no limit.
				if (($this->max_posts !== 0) && ($this->posts_in_digest >= $this->max_posts))
				{
					break;
				}
				
				// Skip posts if new posts only logic applies
				if (($user_row['user_digest_new_posts_only']) && ($post_row['post_time'] < min($this->date_limit, $user_row['user_lastvisit'])))
				{
					continue;
				}
				
				// Exclude post if from a foe
				if (isset($foes) && sizeof($foes) > 0)
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
				
				// Skip post if not a bookmarked topic
				if ($user_row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS)
				{
					if ((isset($bookmarked_topics)) && !in_array($post_row['topic_id'], $bookmarked_topics))
					{
						continue;
					}
				}
				else
				{
					// Skip post if post is not in an allowed forum
					if (!in_array($post_row['forum_id'], $fetched_forums))
					{
						continue;
					}
				}
			
				// Skip posts if first post logic applies and not a first post
				if (($user_row['user_digest_filter_type'] == constants::DIGESTS_FIRST) && ($post_row['topic_first_post_id'] != $post_row['post_id']))
				{
					continue;
				}
				
				// Skip posts if remove my posts logic applies
				if (($user_row['user_digest_show_mine'] == 0) && ($post_row['poster_id'] == $user_row['user_id']))
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
				$this->toc['posts'][$this->toc_post_count]['post_id'] = html_entity_decode($post_row['post_id']);
				$this->toc['posts'][$this->toc_post_count]['forum'] = $this->get_forum_path($post_row['forum_id']);
				$this->toc['posts'][$this->toc_post_count]['topic'] = html_entity_decode($post_row['topic_title']);
				$this->toc['posts'][$this->toc_post_count]['author'] = html_entity_decode($post_row['username']);
				$this->toc['posts'][$this->toc_post_count]['datetime'] = $post_datetime;
				$this->toc_post_count++;

				// Need BBCode flags to translate BBCode into HTML
				$flags = (($post_row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
					(($post_row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
					(($post_row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
				
				if ($this->run_mode == constants::DIGESTS_RUN_SYSTEM)
				{
					// Hack that is needed for system crons to show smilies
					$post_text = str_replace('{SMILIES_PATH}', $this->board_url . 'images/smilies', $post_text);
				}
				$post_text = generate_text_for_display($post_text, $post_row['bbcode_uid'], $post_row['bbcode_bitfield'], $flags);

				// Logic to show attachments
				$post_text .= $this->create_attachment_markup($post_row, $is_post = true);

				// User signature wanted?
				$user_sig = ($post_row['enable_sig'] && $post_row['user_sig'] != '' && $this->config['allow_sig'] ) ? censor_text($post_row['user_sig']) : '';
				if ($user_sig != '')
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
				$post_text = ($user_sig != '') ? trim($post_text . "\n" . $this->language->lang('DIGESTS_POST_SIGNATURE_DELIMITER') . "\n" . $user_sig) : trim($post_text . "\n");
	
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
				}
				else
				{
					// Board URLs must be absolute in the digests, so substitute board URL for relative URL. Smilies are marked up differently after converted
					// to HTML so a special pass must be made for them.
					$post_text = str_replace('<img src="' . $this->phpbb_root_path, '<img src="' . $this->board_url, $post_text);
					$post_text = str_replace('<img class="smilies" src="' . $this->phpbb_root_path, '<img class="smilies" src="' . $this->board_url, $post_text);

					// For HTML digests, remove problematic HTML tags if the board administrator has specified any.
					if (trim($this->config['phpbbservices_digests_strip_tags']) !== '')
					{
						$tags = explode(',',str_replace(' ', '', trim($this->config['phpbbservices_digests_strip_tags'])));
						foreach ($tags as $tag)
						{

							$dom = new \DOMDocument();
							$dom->loadHTML($post_text);

							$script = $dom->getElementsByTagName($tag);

							foreach($script as $item)
							{
								$substitute = $dom->createElement('p', $this->language->lang('DIGESTS_TAG_REPLACED'));
								$item->parentNode->replaceChild($substitute, $item);	// Replace offending tag with substitute
							}

							$post_text = $dom->saveHTML();

						}
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
						
				if ($last_topic_id != (int) $post_row['topic_id'])
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
				
				$this->posts_in_digest++;
				
			}

		}

		// General template variables are set here. Many are inherited from language variables.
		$this->template->assign_vars(array(
			'DIGESTS_TOTAL_PMS'				=> sizeof($pm_rowset),
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
					// Danger Will Robinson! No list permission exists for a parent of the requested forum, so this forum should not be shown
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
		
		if (sizeof($word_array) <= $max_words)
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
				WHERE post_msg_id = ' . $row['post_id'] . ' AND in_message = 0  
				ORDER BY attach_id';
		}
		else
		{
			$sql = 'SELECT *
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE post_msg_id = ' . $row['msg_id'] . ' AND in_message = 1  
				ORDER BY attach_id';
		}
		$result = $this->db->sql_query($sql);

		$attachments_found = 0;
		while ($attachment_row = $this->db->sql_fetchrow($result))
		{
			$attachments_found++;
			if ($attachments_found == 1)
			{
				$markup_text = sprintf("<div class=\"box\">\n<p>%s</p>\n", $this->language->lang('ATTACHMENTS'));
			}
			$file_size = round(($attachment_row['filesize']/1024),2);
			// Show images, link to other attachments
			if (substr($attachment_row['mimetype'],0,6) == 'image/')
			{
				$anchor_begin = '';
				$anchor_end = '';
				$image_text = '';
				$thumbnail_parameter = '';
				$is_thumbnail = ($attachment_row['thumbnail'] == 1) ? true : false;
				// Logic to resize the image, if needed
				if ($is_thumbnail)
				{
					$anchor_begin = sprintf("<a href=\"%s\">", $this->board_url . "download/file.$this->phpEx?id=" . $attachment_row['attach_id']);
					$anchor_end = '</a>';
					$image_text = $this->language->lang('DIGESTS_POST_IMAGE_TEXT');
					$thumbnail_parameter = '&t=1';
				}
				$markup_text .= sprintf("%s<br><em>%s</em> (%s %s)<br>%s<img src=\"%s\" alt=\"%s\" title=\"%s\" />%s\n<br>%s", censor_text($attachment_row['attach_comment']), $attachment_row['real_filename'], $file_size, $this->language->lang('KIB'), $anchor_begin, $this->board_url . "download/file.$this->phpEx?id=" . $attachment_row['attach_id'] . $thumbnail_parameter, censor_text($attachment_row['attach_comment']), censor_text($attachment_row['attach_comment']), $anchor_end, $image_text);
			}
			else
			{
				$my_styles = $this->template->get_user_style();
				$markup_text .= ($attachment_row['attach_comment'] == '') ? '' : '<em>' . censor_text($attachment_row['attach_comment']) . '</em><br>';
				$markup_text .=
					sprintf("<img src=\"%s\" title=\"\" alt=\"\" /> ",
						$this->board_url . 'styles/' . $my_styles[sizeof($my_styles) - 1] . '/theme/images/icon_topic_attach.gif') .
					sprintf("<b><a href=\"%s\">%s</a></b> (%s %s)<br>",
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

}
