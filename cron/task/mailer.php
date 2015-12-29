<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\cron\task;

use phpbbservices\digests\constants\constants;

use includes\functions\functions_messenger;

//use phpbb\template\template;

class mailer extends \phpbb\cron\task\base
{
	
	protected $config;
	protected $request;
	protected $user;
	protected $db;
	protected $phpEx;
	protected $phpbb_root_path;
	protected $template;
	
	private $server_timezone;
	private $toc;				// Table of contents
	private $toc_pm_count;		// Table of contents private message count
	private $toc_post_count;	// Table of contents post count
	private $board_url;
	
	/**
	* Constructor.
	*
	* @param \phpbb\config\config $config The config
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\request\request $request, \phpbb\user $user, \phpbb\db\driver\factory $db, $php_ext, $phpbb_root_path, \phpbb\template\template $template)
	{
		$this->config = $config;
		$this->request = $request;
		$this->user = $user;
		$this->db = $db;
		$this->phpEx = $php_ext;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->template = $template;
	}
	
	/**
	* Runs this cron task.
	*
	* @return true if successful, false if an error occurred
	*/
	public function run()
	{
		
		// This method is what used to be mail_digests.php. It will mail all the digests for the given year, date and hour.
		
		include($this->phpbb_root_path . 'includes/functions_messenger.' . $this->phpEx); // Used to send emails
		
		$run_successful = true;	// Assume a successful run

		// We need to distinguish if the request is being made from a system cron (typical) or by the ACP's manual mailer (atypical). We can do this
		// by looking at the referer and verifying the call was from the ACP for the correct extension and module mode.
		
		$referer = $this->request->server('HTTP_REFERER');
		$this->manual_mode = (strstr($referer, 'adm/index.php') && strstr($referer, 'i=-phpbbservices-digests-acp-main_module') && strstr($referer, 'mode=digests_test')) ? true : false;

		if (!$this->manual_mode)
		{
			$this->user->add_lang_ext('phpbbservices/digests', array('info_acp_common','common'));	// Language strings are already loaded if in manual mode
		}
		
		// If the board is currently disabled, digests should also be disabled too, don't ya think?
		if ($this->config['board_disable'])
		{
			add_log('admin', 'LOG_CONFIG_DIGESTS_BOARD_DISABLED');
			return false;
		}

		$digests_sent = 0;
		
		// Set an indefinite execution time for this program, since we don't know how many digests
		// must be processed for a particular hour or how long it may take. The set_time_limit function
		// only works if PHP's safe mode is off.
		set_time_limit(0);
		
		// Display a digest mail start processing message. It may get captured in a log.
		add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_START');
		
		// Need a board URL since URLs in the digest pointing to the board need to be absolute URLs
		$this->board_url = generate_board_url() . '/';
	
		// If it was requested, get the year, month, date and hour of the digests to create. If it was not requested, it's derived from the current time. Note:
		// these cannot be acquired as URL key/value pairs anymore like in the mod. If used it must be as a result of a manual run of the mailer.
		if (($this->manual_mode) && ($this->config['phpbbservices_digests_test_time_use']))
		{
			$time = mktime($this->config['phpbbservices_digests_test_hour'], 0, 0, $this->config['phpbbservices_digests_test_month'], $this->config['phpbbservices_digests_test_day'], $this->config['phpbbservices_digests_test_year']);
		}
		else
		{
			$time = time();
		}

		$this->server_timezone = floatval(date('O')/100);	// Offset from GMT in hours
		
		$gmt_time = $time - ($this->server_timezone * 60 * 60);	// Convert server time into GMT time
		
		// Get the current hour in GMT, so applicable digests can be sent out for this hour
		$current_hour_gmt = date('G', $gmt_time); // 0 thru 23
		$current_hour_gmt_plus_30 = date('G', $gmt_time) + .5;
		if ($current_hour_gmt_plus_30 >= 24)
		{
			$current_hour_gmt_plus_30 = $current_hour_gmt_plus_30 - 24;	// A very unlikely situation
		}
		
		// Create SQL fragment to fetch users wanting a daily digest
		$daily_digest_sql = '(' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_DAILY_VALUE)) . ')';
		
		// Create SQL fragment to also fetch users wanting a weekly digest, if today is the day weekly digests should go out
		$weekly_digest_sql = (date('w', $gmt_time) == $this->config['digests_weekly_digest_day']) ? ' OR (' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_WEEKLY_VALUE)) . ')': '';
		
		// Create SQL fragment to also fetch users wanting a monthly digest. This only happens if the current GMT day is the first of the month.
		$gmt_year = (int) date('Y', $gmt_time);
		$gmt_month = (int) date('n', $gmt_time);
		$gmt_day = (int) date('j', $gmt_time);
		$gmt_hour = (int) date('G', $gmt_time);
		
		if ($gmt_day == 1) // Since it's the first day of the month in GMT, monthly digests are run too
		{
			
			if ($gmt_month == 1)	// If January, the monthly digests are for December of the previous year
			{
				$gmt_month = 12;
				$gmt_year--;
			}
			else
			{
				$gmt_month--;	// Otherwise monthly digests are run for the previous month for the year
			}
			
			$gmt_month_last_day = date('t', mktime(0, 0, 0, $gmt_month, $gmt_day, $gmt_year));
			$gmt_month_1st_begin = mktime(0, 0, 0, $gmt_month, $gmt_day, $gmt_year);
			$gmt_month_lastday_end = mktime(23, 59, 59, $gmt_month, $gmt_month_last_day, $gmt_year);
			$monthly_digest_sql = ' OR (' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_MONTHLY_VALUE)) . ')';
		}
		else
		{
			$monthly_digest_sql = '';
		}
		
		// We need to know which auth_option_id corresponds to the forum read privilege (f_read) and forum list (f_list) privilege. Why not use $auth->acl_get?
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
				$read_id = $row['auth_option_id'];
			}
			if ($row['auth_option'] == 'f_list')
			{
				$list_id = $row['auth_option_id'];
			}
		}
		$this->db->sql_freeresult($result); // Query be gone!

		// Get users requesting digests for the current hour. Also, grab the user's style, so the digest will have a familiar look.
		if ($this->config['override_user_style'])
		{
			$sql = 'SELECT u.*, s.* 
				FROM ' . USERS_TABLE . ' u, ' . STYLES_TABLE . ' s
				WHERE s.style_id = ' . $this->config['default_style'] . ' AND (' . 
					$daily_digest_sql . $weekly_digest_sql . $monthly_digest_sql . 
					") AND (user_digest_send_hour_gmt = $current_hour_gmt OR user_digest_send_hour_gmt = $current_hour_gmt_plus_30) 
					AND user_inactive_reason = 0
					AND user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . 
					"' ORDER BY user_lang";
		}
		else
		{
			$sql = 'SELECT u.*, s.* 
				FROM ' . USERS_TABLE . ' u, ' . STYLES_TABLE . ' s
				WHERE u.user_style = s.style_id AND (' . 
					$daily_digest_sql . $weekly_digest_sql . $monthly_digest_sql . 
					") AND (user_digest_send_hour_gmt = $current_hour_gmt OR user_digest_send_hour_gmt = $current_hour_gmt_plus_30) 
					AND user_inactive_reason = 0
					AND user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . 
					"' ORDER BY user_lang";
		}
		
		if ($this->config['phpbbservices_digests_override_queue'])
		{
			$use_mail_queue = false;
		}
		else
		{
			$use_mail_queue = ($this->config['email_package_size'] > 0) ? true : false;
		}

		$messenger = new \messenger($use_mail_queue);

		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);	// Gets users receiving digests for this hour

		// Fetch all the posts (no private messages) but do it just once for efficiency. These will be filtered later 
		// to remove those posts a particular user should not see.

		// First, determine a maximum date range fetched: daily, weekly or monthly
		if ($monthly_digest_sql <> '')
		{
			// In the case of monthly digests, it's important to include posts that support daily and weekly digests as well, hence dates of posts
			// retrieved may exceed post dates for the previous month. Logic to exclude posts past the end of the previous month in the case of 
			// monthly digests must be handled in the create_content function to skip these.
			$date_limit_sql = ' AND p.post_time >= ' . $gmt_month_1st_begin . ' AND p.post_time <= ' . max($gmt_month_lastday_end, $gmt_time);
		}
		else if ($weekly_digest_sql <> '')	// Weekly
		{
			$date_limit = $gmt_time - (7 * 24 * 60 * 60);
			$date_limit_sql = ' AND p.post_time >= ' . $date_limit . ' AND p.post_time < ' . $gmt_time;
		}
		else	// Daily
		{
			$date_limit = $gmt_time - (24 * 60 * 60);
			$date_limit_sql = ' AND p.post_time >= ' . $date_limit. ' AND p.post_time < ' . $gmt_time;
		}

		// Now get all potential posts for all users and place them in an array for parsing
		
		// Prepare SQL
		$sql_array = array(
			'SELECT'	=> 'f.*, t.*, p.*, u.*',
		
			'FROM'		=> array(
				POSTS_TABLE => 'p',
				USERS_TABLE => 'u',
				TOPICS_TABLE => 't',
				FORUMS_TABLE => 'f'),
		
			'WHERE'		=> "f.forum_id = t.forum_id
						AND p.topic_id = t.topic_id 
						AND t.forum_id = f.forum_id
						AND p.poster_id = u.user_id
						$date_limit_sql
						AND p.post_visibility = 1",
		
			'ORDER_BY'	=> 'f.left_id, f.right_id'
		);
		
		// Build query
		$sql_posts = $this->db->sql_build_query('SELECT', $sql_array);
		
		// Execute the SQL to retrieve the relevant posts. Note, if $this->config['digests_max_items'] == 0 then there is no limit on the rows returned
		$result_posts = $this->db->sql_query_limit($sql_posts, $this->config['digests_max_items']); 
		$rowset_posts = $this->db->sql_fetchrowset($result_posts); // Get all the posts as a set

		// Now that we have all the posts, time to send one digests at a time
		
		foreach ($rowset as $row)
		{
			
			echo $row['username'];

			// Each traverse through this loop sends out exactly one digest
			
			$this->toc = array();		// Create or empty the array containing table of contents information
			$this->toc_post_count = 0; 	// # of posts in the table of contents
			$this->toc_pm_count = 0; 	// # of private messages in the table of contents
		
			// Set the text showing the digest type
			switch ($row['user_digest_type'])
			{
				case constants::DIGESTS_DAILY_VALUE:
					$digest_type = $this->user->lang['DIGESTS_DAILY'];
				break;
				
				case constants::DIGESTS_WEEKLY_VALUE:
					$digest_type = $this->user->lang['DIGESTS_WEEKLY'];
				break;
				
				case constants::DIGESTS_MONTHLY_VALUE:
					$digest_type = $this->user->lang['DIGESTS_MONTHLY'];
				break;
				
				default:
					add_log('admin', sprintf('LOG_CONFIG_DIGESTS_BAD_DIGEST_TYPE', $row['user_digest_type']));
					add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
					return false;
				break;
			}
		
			$email_subject = sprintf($this->user->lang['DIGESTS_SUBJECT_TITLE'], $this->config['sitename'], $digest_type);
		
			// Set various variables and flags based on the requested digest format
			switch($row['user_digest_format'])
			{
			
				case constants::DIGESTS_TEXT_VALUE:
					$format = $this->user->lang['DIGESTS_FORMAT_TEXT'];
					$messenger->template('digests_text', '', './../ext/phpbbservices/digests/language/en/email/'); // Change based on whether text, plain HTML or expanded HTML
					$is_html = false;
					$disclaimer = strip_tags(sprintf($this->user->lang['DIGESTS_DISCLAIMER'], $this->board_url, $this->config['sitename'], $this->board_url, $this->phpEx, $this->config['board_contact'], $this->config['sitename']));
					$powered_by = $this->config['phpbbservices_digests_host'];
					$use_classic_template = false;
				break;
				
				case constants::DIGESTS_PLAIN_VALUE:
					$format = $this->user->lang['DIGESTS_FORMAT_PLAIN'];
					$messenger->template('digests_plain_html', '', './../ext/phpbbservices/digests/language/en/email/'); // Change based on whether text, plain HTML or expanded HTML
					$is_html = true;
					$disclaimer = sprintf($this->user->lang['DIGESTS_DISCLAIMER'], $this->board_url, $this->config['sitename'], $this->board_url, $this->phpEx, $this->config['board_contact'], $this->config['sitename']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['phpbbservices_digests_page_url'], $this->config['phpbbservices_digests_host']);
					$use_classic_template = false;
				break;
				
				case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
					$format = $this->user->lang['DIGESTS_FORMAT_PLAIN_CLASSIC'];
					$messenger->template('digests_plain_html', '', './../ext/phpbbservices/digests/language/en/email/'); // Change based on whether text, plain HTML or expanded HTML
					$is_html = true;
					$disclaimer = sprintf($this->user->lang['DIGESTS_DISCLAIMER'], $this->board_url, $this->config['sitename'], $this->board_url, $this->phpEx, $this->config['board_contact'], $this->config['sitename']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['phpbbservices_digests_page_url'], $this->config['phpbbservices_digests_host']);
					$use_classic_template = true;
				break;
				
				case constants::DIGESTS_HTML_VALUE:
					$format = $this->user->lang['DIGESTS_FORMAT_HTML'];
					$messenger->template('digests_html', '', './../ext/phpbbservices/digests/language/en/email/'); // Change based on whether text, plain HTML or expanded HTML
					$is_html = true;
					$disclaimer = sprintf($this->user->lang['DIGESTS_DISCLAIMER'], $this->board_url, $this->config['sitename'], $this->board_url, $this->phpEx, $this->config['board_contact'], $this->config['sitename']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['phpbbservices_digests_page_url'], $this->config['phpbbservices_digests_host']);
					$use_classic_template = false;
				break;
				
				case constants::DIGESTS_HTML_CLASSIC_VALUE:
					$format = $this->user->lang['DIGESTS_FORMAT_HTML_CLASSIC'];
					$messenger->template('digests_html', '', './../ext/phpbbservices/digests/language/en/email/'); // Change based on whether text, plain HTML or expanded HTML
					$is_html = true;
					$disclaimer = sprintf($this->user->lang['DIGESTS_DISCLAIMER'], $this->board_url, $this->config['sitename'], $this->board_url, $this->phpEx, $this->config['board_contact'], $this->config['sitename']);
					$powered_by = sprintf("<a href=\"%s\">%s</a>", $this->config['digests_page_url'], $this->config['phpbbservices_digests_host']);
					$use_classic_template = true;
				break;
				
				default:
					add_log('admin', sprintf('LOG_CONFIG_DIGESTS_FORMAT_ERROR', $row['user_digest_type']));
					add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
					return false;
				break;
				
			}
	
			// Set email header information
			$from_field_email = (isset($this->config['phpbbservices_digests_from_email_address']) && (strlen($this->config['phpbbservices_digests_from_email_address']) > 0)) ? $this->config['phpbbservices_digests_from_email_address'] : $this->config['board_email'];
			$from_field_name = (isset($this->config['phpbbservices_digests_from_email_name']) && (strlen($this->config['phpbbservices_digests_from_email_name']) > 0)) ? $this->config['phpbbservices_digests_from_email_name'] : $this->config['sitename'] . ' ' . $this->user->lang['DIGESTS_ROBOT'];
			$reply_to_field_email = (isset($this->config['phpbbservices_digests_reply_to_email_address']) && (strlen($this->config['phpbbservices_digests_reply_to_email_address']) > 0)) ? $this->config['phpbbservices_digests_reply_to_email_address'] : $this->config['board_email'];
		
			$messenger->to($row['user_email']);	
			
			// SMTP delivery must strip text names due to likely bug in messenger class
			if ($this->config['smtp_delivery'])
			{
				$messenger->from($from_field_email);
			}
			else
			{	
				$messenger->from($from_field_name . ' <' . $from_field_email . '>');
			}
			$messenger->replyto($reply_to_field_email);
			$messenger->subject($email_subject);
				
			// Transform user_digest_send_hour_gmt to local time
			$local_send_hour = $row['user_digest_send_hour_gmt'] + $this->make_tz_offset($row['user_timezone']);
			if ($local_send_hour >= 24)
			{
				$local_send_hour = $local_send_hour - 24;
			}
			else if ($local_send_hour < 0)
			{
				$local_send_hour = $local_send_hour + 24;
			}
			
			if (($local_send_hour >= 24) || ($local_send_hour < 0))
			{
				add_log('admin', sprintf('LOG_CONFIG_DIGESTS_BAD_SEND_HOUR', $row['user_digest_type'], $row['user_digest_send_hour_gmt']));
				add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
				return false;
			}

			// Change the filter type into something human readable
			switch($row['user_digest_filter_type'])
			{
			
				case constants::DIGESTS_ALL:
					$post_types = $this->user->lang['DIGESTS_POSTS_TYPE_ANY'];
				break;
				
				case constants::DIGESTS_FIRST:
					$post_types = $this->user->lang['DIGESTS_POSTS_TYPE_FIRST'];
				break;
				
				case constants::DIGESTS_BOOKMARKS:
					$post_types = $this->user->lang['DIGESTS_USE_BOOKMARKS'];
				break;
				
				default:
					add_log('admin', sprintf('LOG_CONFIG_DIGESTS_FILTER_ERROR', $row['user_digest_filter_type']));
					add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
					return false;
				break;
					
			}
			
			// Change the sort by into something human readable
			switch ($row['user_digest_sortby'])
			{
			
				case constants::DIGESTS_SORTBY_BOARD:
					$sort_by = $this->user->lang['DIGESTS_SORT_USER_ORDER'];
				break;
					
				case constants::DIGESTS_SORTBY_STANDARD:
					$sort_by = $this->user->lang['DIGESTS_SORT_FORUM_TOPIC'];
				break;
					
				case constants::DIGESTS_SORTBY_STANDARD_DESC:
					$sort_by = $this->user->lang['DIGESTS_SORT_FORUM_TOPIC_DESC'];
				break;
					
				case constants::DIGESTS_SORTBY_POSTDATE:
					$sort_by = $this->user->lang['DIGESTS_SORT_POST_DATE'];
				break;
					
				case constants::DIGESTS_SORTBY_POSTDATE_DESC:
					$sort_by = $this->user->lang['DIGESTS_SORT_POST_DATE_DESC'];
				break;
					
				default:
					add_log('admin', sprintf('LOG_CONFIG_DIGESTS_SORT_BY_ERROR', $row['user_digest_sortby']));
					add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
					return false;
				break;
					
			}
	
			// Send a proper content-language to the output
			$user_lang = $row['user_lang'];
			if (strpos($user_lang, '-x-') !== false)
			{
				$user_lang = substr($user_lang, 0, strpos($user_lang, '-x-'));
			}
			
			// Create proper message for indicating number of posts allowed in digest
			if (($row['user_digest_max_posts'] == 0) || ($this->config['digests_max_items'] == 0))
			{
				$max_posts_msg = $this->user->lang['DIGESTS_NO_LIMIT'];
			}
			else if ($this->config['digests_max_items'] < $row['user_digest_max_posts'])
			{
				$max_posts_msg = sprintf($this->user->lang['DIGESTS_BOARD_LIMIT'], $this->config['digests_max_items']);
			}
			else
			{
				$max_posts_msg = $row['user_digest_max_posts'];
			}
		
			$recipient_time = $gmt_time + ($this->make_tz_offset($row['user_timezone']) * 60 * 60);

			// Print the non-post and non-private message information in the digest. The actual posts and private messages require the full templating system, 
			// because the messenger class is too dumb to do more than basic templating. Note: most language variables are handled automatically by the template system.
			
			$messenger->assign_vars(array(
				'DIGESTS_BLOCK_IMAGES'			=> ($row['user_digest_block_images'] == 0) ? $this->user->lang['NO'] : $this->user->lang['YES'],
				'DIGESTS_COUNT_LIMIT'			=> $max_posts_msg,
				'DIGESTS_DISCLAIMER'				=> $disclaimer,
				'DIGESTS_FILTER_FOES'			=> ($row['user_digest_remove_foes'] == 0) ? $this->user->lang['NO'] : $this->user->lang['YES'],
				'DIGESTS_FILTER_TYPE'			=> $post_types,
				'DIGESTS_FORMAT_FOOTER'			=> $format,
				'DIGESTS_LASTVISIT_RESET'		=> ($row['user_digest_reset_lastvisit'] == 0) ? $this->user->lang['NO'] : $this->user->lang['YES'],
				'DIGESTS_MAIL_FREQUENCY'			=> $digest_type,
				'DIGESTS_MAX_SIZE'				=> ($row['user_digest_max_display_words'] == 0) ? $this->user->lang['DIGESTS_NO_POST_TEXT'] : (($row['user_digest_max_display_words'] == -1) ?  $this->user->lang['DIGESTS_NO_LIMIT'] : $row['user_digest_max_display_words']),
				'DIGESTS_MIN_SIZE'				=> ($row['user_digest_min_words'] == 0) ? $this->user->lang['DIGESTS_NO_CONSTRAINT'] : $row['user_digest_min_words'],
				'DIGESTS_NO_POST_TEXT'			=> ($row['user_digest_no_post_text'] == 1) ? $this->user->lang['YES'] : $this->user->lang['NO'],
				'DIGESTS_POWERED_BY'				=> $powered_by,
				'DIGESTS_REMOVE_YOURS'			=> ($row['user_digest_show_mine'] == 0) ? $this->user->lang['YES'] : $this->user->lang['NO'],
				'DIGESTS_SALUTATION'				=> $row['username'],
				'DIGESTS_SEND_HOUR'				=> $this->make_hour_string($local_send_hour, $row['user_dateformat']),
				'DIGESTS_SEND_IF_NO_NEW_MESSAGES'=> ($row['user_digest_send_on_no_posts'] == 0) ? $this->user->lang['NO'] : $this->user->lang['YES'],
				'DIGESTS_SHOW_ATTACHMENTS'		=> ($row['user_digest_attachments'] == 0) ? $this->user->lang['NO'] : $this->user->lang['YES'],
				'DIGESTS_SHOW_NEW_POSTS_ONLY'	=> ($row['user_digest_new_posts_only'] == 1) ? $this->user->lang['YES'] : $this->user->lang['NO'],
				'DIGESTS_SHOW_PMS'				=> ($row['user_digest_show_pms'] == 0) ? $this->user->lang['NO'] : $this->user->lang['YES'],
				'DIGESTS_SORT_BY'				=> $sort_by,
				'DIGESTS_TOC_YES_NO'				=> ($row['user_digest_toc'] == 0) ? $this->user->lang['NO'] : $this->user->lang['YES'],
				'DIGESTS_VERSION'				=> $this->config['phpbbservices_digests_version'],
				'L_DIGESTS_INTRODUCTION'			=> sprintf($this->user->lang['DIGESTS_INTRODUCTION'], $this->config['sitename']),
				'L_DIGESTS_PUBLISH_DATE'			=> sprintf($this->user->lang['DIGESTS_PUBLISH_DATE'], $row['username'], date(str_replace('|','',$row['user_dateformat']), $recipient_time)),
				'L_DIGESTS_TITLE'				=> $email_subject,
				'L_DIGESTS_YOUR_DIGEST_OPTIONS'	=> sprintf($this->user->lang['DIGESTS_YOUR_DIGEST_OPTIONS'], $row['username']),
				'S_CONTENT_DIRECTION'			=> $this->user->lang['DIRECTION'],
				'S_USER_LANG'					=> $user_lang,
				'T_STYLESHEET_LINK'				=> ($this->config['digests_enable_custom_stylesheets']) ? "{$this->board_url}styles/" . $this->config['digests_custom_stylesheet_path'] : "{$this->board_url}styles/" . $row['style_name'] . '/theme/stylesheet.css',
				'T_THEME_PATH'					=> "{$this->board_url}styles/" . $row['style_name'] . '/theme',
			));

			// Get any private messages for this user
			
			$digest_exception = false;
		
			if ($row['user_digest_show_pms'])
			{
			
				// If there are any unread private messages, they are fetched separately and passed as a rowset to create_content.
				$pm_sql = 	'SELECT *
							FROM ' . PRIVMSGS_TO_TABLE . ' pt, ' . PRIVMSGS_TABLE . ' pm, ' . USERS_TABLE . ' u
							WHERE pt.msg_id = pm.msg_id
								AND pt.author_id = u.user_id
								AND pt.user_id = ' . $row['user_id'] . '
								AND (pm_unread = 1 OR pm_new = 1)
							ORDER BY message_time';
				$pm_result = $this->db->sql_query($pm_sql);
				$pm_rowset = $this->db->sql_fetchrowset($pm_result);
				$this->db->sql_freeresult();
				
			}
			else
			{
				// Avoid some PHP Notices...
				$pm_result = NULL;
				$pm_rowset = NULL;
			}

			// Construct the body of the digest. We use the templating system because of the advanced features missing in the 
			// email templating system, e.g. loops and switches. Note, create_content may set the flag $digest_exception.
			$digest_content = $this->create_content($rowset_posts, $pm_rowset, $row, $is_html);
			echo $digest_content;
		
			// Assemble a digest table of contents
			if ($row['user_digest_toc'] == 1)
			{
			
				// Create Table of Contents header for private messages
				if ($is_html)
				{
					// For HTML digests, the table of contents always appears in a HTML table
					$digest_toc = "<h2 style=\"color:#000000\">" . $this->user->lang['DIGESTS_TOC'] . "</h2>\n";
					$digest_toc .= "<p><a href=\"#skip\">" . $this->user->lang['DIGESTS_SKIP'] . "</a></p>\n";
				}
				else
				{
					$digest_toc = "____________________________________________________________\n\n" . $this->user->lang['DIGESTS_TOC'] . "\n";
				}
				
				if ($row['user_digest_show_pms'] == 1)
				{
					
					// Heading for table of contents
					if ($is_html)
					{
						$digest_toc .= sprintf("<div class=\"content\"><table border=\"1\">\n<tbody>\n<tr>\n<th id=\"j1\">%s</th><th id=\"j2\">%s</th><th id=\"j3\">%s</th><th id=\"j4\">%s</th>\n</tr>\n",
							$this->user->lang['DIGESTS_JUMP_TO'] , ucwords($this->user->lang['PRIVATE_MESSAGE'] . ' ' . $this->user->lang['SUBJECT']), $this->user->lang['DIGESTS_SENDER'], $this->user->lang['DIGESTS_DATE']); 
					}
					
					// Add a table row for each private message
					if ($this->toc_pm_count > 0)
					{
						for ($i=0; $i <= $this->toc_pm_count; $i++)
						{
							if ($is_html)
							{
								$digest_toc .= (isset($this->toc['pms'][$i])) ? "<tr>\n<td headers=\"j1\" style=\"text-align: right;\"><a href=\"#m" . $this->toc['pms'][$i]['message_id'] . '">' . $this->toc['pms'][$i]['message_id'] . '</a></td><td headers="j2">' . $this->toc['pms'][$i]['message_subject'] . '</td><td headers="j3">' . $this->toc['pms'][$i]['author'] . '</td><td headers="j4">' . $this->toc['pms'][$i]['datetime'] . "</td>\n</tr>\n" : '';
							}
							else
							{
								$digest_toc .= (isset($this->toc['pms'][$i])) ? $this->toc['pms'][$i]['author'] . ' ' . $this->user->lang['DIGESTS_SENT_YOU_A_MESSAGE'] . ' ' . $this->user->lang['DIGESTS_OPEN_QUOTE'] . $this->toc['pms'][$i]['message_subject'] . $this->user->lang['DIGESTS_CLOSED_QUOTE'] . ' ' . $this->user->lang['DIGESTS_ON'] . ' ' . $this->toc['pms'][$i]['datetime'] . "\n" : '';
							}
						}
					}
					else
					{
						$digest_toc .= ($is_html) ? '<tr><td colspan="4">' . $this->user->lang['DIGESTS_NO_PRIVATE_MESSAGES'] . "</td></tr>" : $digest_toc = $this->user->lang['DIGESTS_NO_PRIVATE_MESSAGES'];
					}
			
					// Create Table of Contents footer for private messages
					$digest_toc .= ($is_html) ? "</tbody></table>\n<br />" : "\n\n"; 
				
				}
				else
				{
					$digest_toc = null;	// Avoid a PHP Notice
				}
				
				// Create Table of Contents header for posts
				if ($is_html)
				{
					// For HTML digests, the table of contents always appears in a HTML table
					$digest_toc .= sprintf("<table border=\"1\">\n<tbody>\n<tr>\n<th id=\"h1\">%s</th><th id=\"h2\">%s</th><th id=\"h3\">%s</th><th id=\"h4\">%s</th><th id=\"h5\">%s</th>\n</tr>\n",
						$this->user->lang['DIGESTS_JUMP_TO'] , $this->user->lang['FORUM'], $this->user->lang['TOPIC'], $this->user->lang['AUTHOR'], $this->user->lang['DIGESTS_DATE']); 
				}
				
				// Add a table row for each post
				if ($this->toc_post_count > 0)
				{
					for ($i=0; $i <= $this->toc_post_count; $i++)
					{
						if ($is_html)
						{
							$digest_toc .= (isset($this->toc['posts'][$i])) ? "<tr>\n<td headers=\"h1\" style=\"text-align: right;\"><a href=\"#p" . $this->toc['posts'][$i]['post_id'] . '">' . $this->toc['posts'][$i]['post_id'] . '</a></td><td headers="h2">' . $this->toc['posts'][$i]['forum'] . '</td><td headers="h3">' . $this->toc['posts'][$i]['topic'] . '</td><td headers="h4">' . $this->toc['posts'][$i]['author'] . '</td><td headers="h5">' . $this->toc['posts'][$i]['datetime'] . "</td>\n</tr>\n" : '';
						}
						else
						{
							$digest_toc .= (isset($this->toc['posts'][$i])) ? $this->toc['posts'][$i]['author'] . ' ' . $this->user->lang['DIGESTS_POSTED_TO_THE_TOPIC'] . ' ' . $this->user->lang['DIGESTS_OPEN_QUOTE'] . $this->toc['posts'][$i]['topic'] . $this->user->lang['DIGESTS_CLOSED_QUOTE'] . ' ' . $this->user->lang['IN'] . ' ' . $this->user->lang['DIGESTS_OPEN_QUOTE'] . $this->toc['posts'][$i]['forum'] . $this->user->lang['DIGESTS_CLOSED_QUOTE'] . ' ' . $this->user->lang['DIGESTS_ON'] . ' ' . $this->toc['posts'][$i]['datetime'] . "\n" : '';
						}
					}
				}
				else
				{
					$digest_toc = ($is_html) ? '<tr><td colspan="5">' . $this->user->lang['DIGESTS_NO_POSTS'] . "</td></tr>" : $this->user->lang['DIGESTS_NO_POSTS'];
				}
				
				// Create Table of Contents footer
				$digest_toc .= ($is_html) ? "</tbody>\n</table></div>\n<br />" : "\n\n"; 
			
				// Publish the table of contents
				$messenger->assign_vars(array(
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
		
			// Publish the digest content, marshaled elsewhere
			$messenger->assign_vars(array(
				'DIGESTS_CONTENT'		=> $digest_content,	
			));
			
			// Mark private messages in the digest as read, if so instructed
			if ((sizeof($pm_rowset) != 0) && ($row['user_digest_show_pms'] == 1) && ($row['user_digest_pm_mark_read'] == 1))
			{
				$pm_read_sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . '
					SET pm_new = 0, pm_unread = 0 
					WHERE user_id = ' . $row['user_id'] . '
						AND (pm_unread = 1 OR pm_new = 1)';
				$pm_read_sql_result = $this->db->sql_query($pm_read_sql);
				$this->db->sql_freeresult($pm_read_sql_result);
			}
				 
			$this->db->sql_freeresult($result_posts);
			$this->db->sql_freeresult($pm_result);

			// Send the digest out only if there are new qualifying posts or the user requests a digest to be sent if there are no posts OR
			// if there are unread private messages, the user wants to see private messages in the digest.
			if (!$digest_exception)
			{
				if ($row['user_digest_send_on_no_posts'] || $this->toc_post_count > 0 || ((sizeof($pm_rowset) > 0) && $row['user_digest_show_pms']))
				{
					
					$mail_sent = $messenger->send(NOTIFY_EMAIL, false, $is_html, true);
		
					if (!$mail_sent)
					{
						if ($this->config['digests_show_email'])
						{
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD', $row['username'], $row['user_email']);
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
							return false;
						}
						else
						{
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD_NO_EMAIL', $row['username']);
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
							return false;
						}
					}
					else
					{
						$sent_to_created_for = ($use_mail_queue) ? $this->user->lang['DIGESTS_CREATED_FOR'] : $this->user->lang['DIGESTS_SENT_TO'];
						if ($this->config['digests_show_email'])
						{
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD', $sent_to_created_for, $row['username'], $row['user_email'], $posts_in_digest, sizeof($pm_rowset));
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
							return false;
						}
						else
						{
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_NO_EMAIL', $sent_to_created_for, $row['username'], $posts_in_digest, sizeof($pm_rowset));
							add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
							return false;
						}
						// Capture the fact that a digest should have been successfully sent
						$sql2 = 'UPDATE ' . USERS_TABLE . '
									SET user_digest_last_sent = ' . time() . ' 
									WHERE user_id = ' . $row['user_id'];
						$result2 = $this->db->sql_query($sql2);
						$digests_sent++;
					}
				}
				else
				{
					if ($this->config['digests_show_email'])
					{
						add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE', $row['username'], $row['user_email']);
						add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
						return false;
					}
					else
					{
						add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE_NO_EMAIL', $row['username']);
						add_log('admin', 'LOG_CONFIG_DIGESTS_LOG_END');
						return false;
					}
				}
			}

		}	// foreach
			
		// Do not forget to update the configuration variable for last run time.
		$this->config->set('phpbbservices_digests_cron_task_last_gc', time());
		
		return $run_successful;
		
	}
	
	private function create_content(&$rowset, &$pm_rowset, &$user_row, $is_html)
	{

		/*global $user, $template, $board_url, $phpEx, $config, $is_html, $server_timezone, $use_classic_template, $db, $toc,
			$phpbb_root_path, $auth, $read_id, $date_limit, $digest_exception, $posts_in_digest, $toc_pm_count, $toc_post_count,
			$gmt_month_lastday_end, $time;*/
			
		// Load the right template
		
		$mail_template = ($is_html) ? 'template/mail_digests_html.html' : 'template/mail_digests_text.html';
				
		$this->template->set_custom_style($mail_template, './../ext/phpbbservices/digests/styles/prosilver/');

		$this->template->set_filenames(array(
		   'mail_digests'      => $mail_template,
		));
			
		$show_post_text = ($user_row['user_digest_no_post_text'] == 0);
		
		$posts_in_digest = 0;

		// Process private messages, if any, first since they appear before posts
		
		if ((sizeof($pm_rowset) != 0) && ($user_row['user_digest_show_pms'] == 1))	
		{
		
			// There are private messages and the user wants to see them in the digest
			
			$this->template->assign_vars(array(
				'L_YOU_HAVE_PRIVATE_MESSAGES'	=> sprintf($this->user->lang['DIGESTS_YOU_HAVE_PRIVATE_MESSAGES'], $user_row['username']),
				'S_SHOW_PMS'					=> true,
			));
			
			foreach ($pm_rowset as $pm_row)
			{
			
				// If there are inline attachments, remove them otherwise they will show up twice. Getting the styling right
				// in these cases is probably a lost cause due to the complexity to be addressed due to various styling issues.
				$pm_row['message_text'] = preg_replace('#\[attachment=.*?\].*?\[/attachment:.*?]#', '', censor_text($pm_row['message_text']));
	
				// Now adjust post time to digest recipient's local time
				$recipient_time = $pm_row['message_time'] - ($this->server_timezone * 60 * 60) + (($user_row['user_timezone'] + $user_row['user_dst']) * 60 * 60);
	
				// Add to table of contents array
				$this->toc['pms'][$this->toc_pm_count]['message_id'] = html_entity_decode($pm_row['msg_id']);
				$this->toc['pms'][$this->toc_pm_count]['message_subject'] = html_entity_decode(censor_text($pm_row['message_subject']));
				$this->toc['pms'][$this->toc_pm_count]['author'] = html_entity_decode($pm_row['username']);
				$this->toc['pms'][$this->toc_pm_count]['datetime'] = date(str_replace('|', '', $user_row['user_dateformat']), $recipient_time);
				$this->toc_pm_count++;
	
				$flags = (($pm_row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
					(($pm_row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
					(($pm_row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
					
				$pm_text = generate_text_for_display(censor_text($pm_row['message_text']), $pm_row['bbcode_uid'], $pm_row['bbcode_bitfield'], $flags);
				
				// User signature wanted?
				$user_sig = ($pm_row['enable_sig'] && $pm_row['user_sig'] != '' && $this->config['allow_sig']) ? censor_text($pm_row['user_sig']) : '';
				if ($user_sig != '')
				{
					// Format the signature for display
					$user_sig = generate_text_for_display(censor_text($user_sig), $rowset['user_sig_bbcode_uid'], $rowset['user_sig_bbcode_bitfield'], $flags);
				}
			
				// Handle logic to display attachments in private messages
				if ($pm_row['message_attachment'] > 0 && $this->user_row['user_digest_attachments'])
				{
					$pm_text .= sprintf("<div class=\"box\">\n<p>%s</p>\n", $this->user->lang['ATTACHMENTS']);
					
					// Get all attachments
					$sql3 = 'SELECT *
						FROM ' . ATTACHMENTS_TABLE . '
						WHERE post_msg_id = ' . $pm_row['msg_id'] . ' AND in_message = 1 
						ORDER BY attach_id';
					$result3 = $this->db->sql_query($sql3);
					while ($row3 = $this->db->sql_fetchrow($result3))
					{
						$file_size = round(($row3['filesize']/1024),2);
						// Show images, link to other attachments
						if (substr($row3['mimetype'],0,6) == 'image/')
						{
							$anchor_begin = '';
							$anchor_end = '';
							$pm_image_text = '';
							$thumbnail_parameter = '';
							$is_thumbnail = ($row3['thumbnail'] == 1) ? true : false;
							// Logic to resize the image, if needed
							if ($is_thumbnail)
							{
								$anchor_begin = sprintf("<a href=\"%s\">", $this->board_url . "download/file.$this->phpEx?id=" . $row3['attach_id']);
								$anchor_end = '</a>';
								$pm_image_text = $this->user->lang['DIGESTS_POST_IMAGE_TEXT'];
								$thumbnail_parameter = '&t=1';
							}
							$pm_text .= sprintf("%s<br /><em>%s</em> (%s KiB)<br />%s<img src=\"%s\" alt=\"%s\" title=\"%s\" />%s\n<br />%s", censor_text($row3['attach_comment']), $row3['real_filename'], $file_size, $anchor_begin, $this->board_url . "download/file.$this->phpEx?id=" . $row3['attach_id'] . $thumbnail_parameter, censor_text($row3['attach_comment']), censor_text($row3['attach_comment']), $anchor_end, $pm_image_text);
						}
						else
						{
							$pm_text .= ($row3['attach_comment'] == '') ? '' : '<em>' . censor_text($row3['attach_comment']) . '</em><br />';
							$pm_text .= 
								sprintf("<img src=\"%s\" title=\"\" alt=\"\" /> ", 
									$this->board_url . 'styles/' . $user_row['style_name'] . '/theme/images/icon_topic_attach.gif') .
								sprintf("<b><a href=\"%s\">%s</a></b> (%s KiB)<br />",
									$this->board_url . "download/file.$this->phpEx?id=" . $row3['attach_id'], 
									$row3['real_filename'], 
									$file_size);
						}
					}
					$this->db->sql_freeresult($result3);
					
					$pm_text .= '</div>';
								
				}
					
				// Add signature to bottom of private message
				$pm_text = ($user_sig != '') ? $pm_text . "\n" . $this->user->lang['DIGESTS_POST_SIGNATURE_DELIMITER'] . "\n" . $user_sig : $pm_text . "\n";
	
				// If required or requested, remove all images
				if ($this->config['digests_block_images'] || $user_row['user_digest_block_images'])
				{
					$pm_text = preg_replace('<img.*?\/>', '', $pm_text);
				}
					
				// If a text digest is desired, this is a good point to strip tags, after first replacing <br /> with \n
				if (!$is_html)
				{
					$pm_text = str_replace('<br />', "\n\n", $pm_text);
					$pm_text = html_entity_decode(strip_tags($pm_text));
				}
				else
				{
					// Board URLs must be absolute in the digests, so substitute board URL for relative URL
					$pm_text = str_replace('<img src="' . $this->phpbb_root_path, '<img src="' . $this->board_url, $pm_text);
				} 
	
				$this->template->assign_block_vars('pm', array(
					'ANCHOR'					=> "<a name=\"m" . $pm_row['msg_id'] . "\"></a>",
					'CONTENT'					=> $pm_text,
					'DATE'						=> date(str_replace('|','',$pm_row['user_dateformat']), $recipient_time) . "\n",
					'FROM'						=> ($is_html) ? sprintf('<a href="%s?mode=viewprofile&amp;u=%s">%s</a>', $this->board_url . 'memberlist.' . $this->phpEx, $pm_row['author_id'], $pm_row['username']) : $pm_row['username'],
					'NEW_UNREAD'				=> ($pm_row['pm_new'] == 1) ? $this->user->lang['DIGESTS_NEW'] . ' ' : $this->user->lang['DIGESTS_UNREAD'] . ' ',
					'PRIVATE_MESSAGE_LINK'		=> ($is_html) ? sprintf('<a href="%s?i=pm&amp;mode=view&amp;f=0&amp;p=%s">%s</a>', $this->board_url . 'ucp.' . $this->phpEx, $pm_row['msg_id'], $pm_row['msg_id']) . "\n" : html_entity_decode(censor_text($pm_row['message_subject'])) . "\n",
					'PRIVATE_MESSAGE_SUBJECT'	=> ($is_html) ? sprintf('<a href="%s?i=pm&amp;mode=view&amp;f=0&amp;p=%s">%s</a>', $this->board_url . 'ucp.' . $this->phpEx, $pm_row['msg_id'], html_entity_decode(censor_text($pm_row['message_subject']))) . "\n" : html_entity_decode(censor_text($pm_row['message_subject'])) . "\n",
					'S_USE_CLASSIC_TEMPLATE'	=> $use_classic_template,
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

		$digest_body = $this->template->assign_display('mail_digests');

		//$this->template->set_style($saved_style);

		return $digest_body;
		
	}
	
	private function make_tz_offset ($tz_text)
	{
		// This function translates a text timezone (like America/New York) to an hour offset from GMT, doing magic like figuring out DST
		$tz = new \DateTimeZone($tz_text);
		$datetime_tz = new \DateTime('now', $tz);
		$timeOffset = $tz->getOffset($datetime_tz) / 3600;
		return $timeOffset;
	}

	private function make_hour_string($hour, $user_dateformat)
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

}
