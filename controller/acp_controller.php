<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\controller;

use phpbbservices\digests\constants\constants;

/**
 * Digests ACP controller.
 */
class acp_controller
{

	protected $auth;
	protected $config;
	protected $db;
	protected $helper;
	protected $language;
	protected $mailer;
	protected $pagination;
	protected $phpbb_extension_manager;
	protected $phpbb_log;
	protected $phpbb_path_helper;
	protected $phpbb_root_path;
	protected $phpEx;
	protected $request;
	protected $table_prefix;
	protected $template;
	protected $user;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\auth\auth 							$auth 						The auth object
	 * @param \phpbb\config\config						$config						Config object
	 * @param \phpbb\db\driver\factory 					$db 						The database factory object
	 * @param \phpbbservices\digests\core\common		$helper						Digests helper object
	 * @param \phpbb\language\language					$language					Language object
	 * @param \phpbbservices\digests\cron\task\digests	$mailer						Digests mailer object
	 * @param \phpbb\pagination 						$pagination					Pagination object
	 * @param \phpbb\extension\manager 					$phpbb_extension_manager	phpBB extension manager object
	 * @param \phpbb\log\log 							$phpbb_log 					phpBB log object
	 * @param \phpbb\path_helper						$phpbb_path_helper 			phpBB path helper object
	 * @param string									$phpbb_root_path			Relative path to phpBB root
	 * @param string									$php_ext 					PHP file suffix
	 * @param \phpbb\request\request					$request					Request object
	 * @param string									$table_prefix 				Prefix for phpbb's database tables
	 * @param \phpbb\template\template					$template					Template object
	 * @param \phpbb\user								$user						User object
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\config\config $config, \phpbb\db\driver\factory $db, \phpbbservices\digests\core\common $helper, \phpbb\language\language $language, \phpbbservices\digests\cron\task\digests $mailer, \phpbb\pagination $pagination, \phpbb\extension\manager $phpbb_extension_manager, \phpbb\log\log $phpbb_log, \phpbb\path_helper $phpbb_path_helper, string $phpbb_root_path, string $php_ext, \phpbb\request\request $request, string $table_prefix, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->auth						= $auth;
		$this->config					= $config;
		$this->db						= $db;
		$this->helper					= $helper;
		$this->language					= $language;
		$this->mailer					= $mailer;
		$this->pagination				= $pagination;
		$this->phpbb_extension_manager	= $phpbb_extension_manager;
		$this->phpbb_log				= $phpbb_log;
		$this->phpbb_path_helper		= $phpbb_path_helper;
		$this->phpbb_root_path			= $phpbb_root_path;
		$this->phpEx					= $php_ext;
		$this->request					= $request;
		$this->table_prefix				= $table_prefix;
		$this->template					= $template;
		$this->user						= $user;
	}

	/**
	 * Display the options a user can configure for this extension.
	 *
	 * @return void
	 */
	public function display_options($mode, $u_action)
	{
		// Add our common language file
		$this->language->add_lang(array('acp/common'), 'phpbbservices/digests');

		$submit = $this->request->is_set_post('submit');

		$form_key = 'phpbbservices/digests';
		add_form_key($form_key);
		$my_time_zone = $this->helper->make_tz_offset($this->user->data['user_timezone']);

		$error = array();

		$this->template->assign_vars(array(
			'U_ACTION'			=> $u_action)
		);

		switch ($mode)
		{
			case 'digests_general':
				$this->template->assign_vars(array(
					'CUSTOM_STYLESHEET_PATH'				=> $this->config['phpbbservices_digests_custom_stylesheet_path'],
					'DOW_OPTIONS'							=> $this->dow_select(),
					'EXCLUDE_FORUMS'						=> $this->config['phpbbservices_digests_exclude_forums'],
					'FROM_EMAIL_ADDRESS'					=> $this->config['phpbbservices_digests_from_email_address'],
					'FROM_EMAIL_NAME'						=> $this->config['phpbbservices_digests_from_email_name'],
					'INCLUDE_FORUMS'						=> $this->config['phpbbservices_digests_include_forums'],
					'L_TITLE'								=> $this->language->lang('ACP_DIGESTS_GENERAL_SETTINGS'),
					'L_TITLE_EXPLAIN'						=> $this->language->lang('ACP_DIGESTS_GENERAL_SETTINGS_EXPLAIN'),
					'MAX_CRON_HOURS'						=> $this->config['phpbbservices_digests_max_cron_hrs'],
					'MAX_ITEMS'								=> $this->config['phpbbservices_digests_max_items'],
					'MIN_POPULARITY_SIZE'					=> $this->config['phpbbservices_digests_min_popularity_size'],
					'REPLY_TO_EMAIL_ADDRESS'				=> $this->config['phpbbservices_digests_reply_to_email_address'],
					'STRIP_TAGS'							=> $this->config['phpbbservices_digests_strip_tags'],
					'SALUTATION_FIELDS'						=> $this->config['phpbbservices_digests_saluation_fields'],
					'S_DIGESTS_BLOCK_IMAGES'				=> (bool) $this->config['phpbbservices_digests_block_images'],
					'S_DIGESTS_DEBUG'						=> (bool) $this->config['phpbbservices_digests_debug'],
					'S_DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS'	=> (bool) $this->config['phpbbservices_digests_enable_auto_subscriptions'],
					'S_DIGESTS_ENABLE_CUSTOM_STYLESHEET'	=> (bool) $this->config['phpbbservices_digests_enable_custom_stylesheets'],
					'S_DIGESTS_ENABLE_LOG'					=> (bool) $this->config['phpbbservices_digests_enable_log'],
					'S_DIGESTS_GENERAL'						=> true,	// Show fields for the general settings module
					'S_DIGESTS_LOWERCASE_DIGEST_TYPE'		=> (bool) $this->config['phpbbservices_digests_lowercase_digest_type'],
					'S_DIGESTS_NOTIFY_ON_ADMIN_CHANGES'		=> (bool) $this->config['phpbbservices_digests_notify_on_admin_changes'],
					'S_DIGESTS_REGISTRATION_FIELD'			=> (bool) $this->config['phpbbservices_digests_registration_field'],
					'S_DIGESTS_SHOW_EMAIL'					=> (bool) $this->config['phpbbservices_digests_show_email'],
					'S_DIGESTS_SHOW_FORUM_PATH'				=> (bool) $this->config['phpbbservices_digests_show_forum_path'],
					'S_DIGESTS_UNLINK_FOREIGN_URLS'			=> (bool) $this->config['phpbbservices_digests_foreign_urls'],
					'USERS_PER_PAGE'						=> $this->config['phpbbservices_digests_users_per_page'],
				));
			break;

			case 'digests_user_defaults':
				$this->template->assign_vars(array(
					'COUNT_LIMIT'							=> $this->config['phpbbservices_digests_user_digest_max_posts'],
					'FILTER_TYPE'							=> $this->digest_filter_type(),
					'FREQUENCY'								=> $this->digest_type_select(),
					'HOUR_SENT'								=> $this->digest_send_hour_utc(),
					'MAX_DISPLAY_WORDS'						=> $this->config['phpbbservices_digests_user_digest_max_display_words'],
					'MIN_WORDS'								=> $this->config['phpbbservices_digests_user_digest_min_words'],
					'POPULARITY_SIZE'						=> $this->config['phpbbservices_digests_user_digest_popularity_size'],
					'SORT_BY'								=> $this->digest_post_sort_order(),
					'STYLING'								=> $this->digest_style_select(),
					'L_TITLE'								=> $this->language->lang('ACP_DIGESTS_USER_DEFAULT_SETTINGS'),
					'L_TITLE_EXPLAIN'						=> $this->language->lang('ACP_DIGESTS_USER_DEFAULT_SETTINGS_EXPLAIN'),
					'S_DIGESTS_ATTACHMENTS'					=> (bool) $this->config['phpbbservices_digests_user_digest_attachments'],
					'S_DIGESTS_BLOCK_IMAGES'				=> (bool) $this->config['phpbbservices_digests_user_digest_block_images'],
					'S_DIGESTS_NEW_POSTS_ONLY'				=> (bool) $this->config['phpbbservices_digests_user_digest_new_posts_only'],
					'S_DIGESTS_PM_MARK_READ'				=> (bool) $this->config['phpbbservices_digests_user_digest_pm_mark_read'],
					'S_DIGESTS_POPULAR_ONLY'				=> (bool) $this->config['phpbbservices_digests_user_digest_popular'],
					'S_DIGESTS_REGISTER'					=> (bool) $this->config['phpbbservices_digests_user_digest_registration'],
					'S_DIGESTS_REMOVE_FOES'					=> (bool) $this->config['phpbbservices_digests_user_digest_remove_foes'],
					'S_DIGESTS_REMOVE_YOURS'				=> (bool) $this->config['phpbbservices_digests_user_digest_show_mine'],
					'S_DIGESTS_RESET_LASTVISIT'				=> (bool) $this->config['phpbbservices_digests_user_digest_reset_lastvisit'],
					'S_DIGESTS_SEND_ON_NO_POSTS'			=> (bool) $this->config['phpbbservices_digests_user_digest_send_on_no_posts'],
					'S_DIGESTS_SORT_BY'						=> (bool) $this->config['phpbbservices_digests_user_digest_sortby'],
					'S_DIGESTS_SHOW_PMS'					=> (bool) $this->config['phpbbservices_digests_user_digest_show_pms'],
					'S_DIGESTS_TOC'							=> (bool) $this->config['phpbbservices_digests_user_digest_toc'],
					'S_DIGESTS_USER_DEFAULTS'				=> true,	// Show fields for the user defaults module
				));

			break;

			case 'digests_edit_subscribers':
				$this->template->assign_vars(array(
					'L_TITLE'								=> $this->language->lang('ACP_DIGESTS_EDIT_SUBSCRIBERS'),
					'L_TITLE_EXPLAIN'						=> $this->language->lang('ACP_DIGESTS_EDIT_SUBSCRIBERS_EXPLAIN'),
				));

				// Grab some URL parameters that are used in sorting and filtering
				$selected = $this->request->variable('selected', 'i', true);
				$member = $this->request->variable('member', '', true);
				$start = $this->request->variable('start', 0);
				$subscribe = $this->request->variable('subscribe', 'a', true);
				$sortby = $this->request->variable('sortby', 'u', true);
				$sortorder = $this->request->variable('sortorder', 'a', true);

				// Retain the "With selected" setting
				$selected_ignore = ($selected == 'i') ? ' selected="selected"' : '';
				$selected_unsubscribe = ($selected == 'n') ? ' selected="selected"' : '';
				$selected_subscribe = ($selected == 'd') ? ' selected="selected"' : '';

				// Translate time zone information and set other switches
				$this->template->assign_vars(array(
					'L_DIGESTS_BASED_ON'							=> $this->language->lang('DIGESTS_BASED_ON', $my_time_zone),
					'L_DIGESTS_HOUR_SENT'               			=> $this->language->lang('DIGESTS_HOUR_SENT', $my_time_zone),
					'S_EDIT_SUBSCRIBERS'							=> true,	// In this module
					'S_INCLUDE_DIGESTS_CSS'							=> true,	// Need to include special Digests CSS
					'S_INCLUDE_DIGESTS_JS'							=> true,	// Need to include special Digests Javascript
				));

				// Set up subscription filter
				$all_selected = $stopped_subscribing = $subscribe_selected = $unsubscribe_selected = $daily_selected = $weekly_selected = $monthly_selected = '';
				switch ($subscribe)
				{
					case 'u':
						$subscribe_sql = "user_digest_type = 'NONE' AND user_digest_has_unsubscribed = 0 AND ";
						$unsubscribe_selected = ' selected="selected"';
						$context = $this->language->lang('DIGESTS_UNSUBSCRIBED');
					break;

					case 't':
						$subscribe_sql = "user_digest_type = 'NONE' AND user_digest_has_unsubscribed = 1 AND";
						$stopped_subscribing = ' selected="selected"';
						$context = $this->language->lang('DIGESTS_STOPPED_SUBSCRIBING');
					break;

					case 's':
						$subscribe_sql = "user_digest_type <> 'NONE' AND user_digest_send_hour_gmt >= 0 AND user_digest_send_hour_gmt < 24 AND user_digest_has_unsubscribed = 0 AND";
						$subscribe_selected = ' selected="selected"';
						$context = $this->language->lang('DIGESTS_SUBSCRIBED');
					break;

					case 'a':
						$subscribe_sql = '';
						$all_selected = ' selected="selected"';
						$context = $this->language->lang('DIGESTS_ALL');
					break;

					case 'd':
						$subscribe_sql = "user_digest_type <> 'NONE' AND user_digest_type = '" . constants::DIGESTS_DAILY_VALUE . "'  AND ";
						$daily_selected = ' selected="selected"';
						$context = $this->language->lang('DIGESTS_DAILY_ONLY');
					break;

					case 'w':
						$subscribe_sql = "user_digest_type <> 'NONE' AND user_digest_type = '" . constants::DIGESTS_WEEKLY_VALUE . "'  AND ";
						$weekly_selected = ' selected="selected"';
						$context = $this->language->lang('DIGESTS_WEEKLY_ONLY');
					break;

					case 'm':
						$subscribe_sql = "user_digest_type <> 'NONE' AND user_digest_type ='" . constants::DIGESTS_MONTHLY_VALUE . "' AND ";
						$monthly_selected = ' selected="selected"';
						$context = $this->language->lang('DIGESTS_MONTHLY');
					break;

					default:
						// Keep PhpStorm happy, this block should never get invoked
						$subscribe_sql = '';
						$all_selected = '';
						$context = '';

				}

				// Set up sort by column
				$last_sent_selected = $has_unsubscribed_selected = $username_selected = $frequency_selected = $format_selected = $hour_selected = $lastvisit_selected = $email_selected = '';
				switch ($sortby)
				{
					case 'f':
						$sort_by_sql = 'user_digest_type %s, lower(username) %s';
						$frequency_selected = ' selected="selected"';
					break;

					case 'e':
						$sort_by_sql = 'user_email %s, lower(username) %s';
						$email_selected = ' selected="selected"';
					break;

					case 's':
						$sort_by_sql = 'user_digest_format %s, lower(username) %s';
						$format_selected = ' selected="selected"';
					break;

					case 'h':
						$sort_by_sql = 'send_hour_board %s, lower(username) %s';
						$hour_selected = ' selected="selected"';
					break;

					case 'l':
						$sort_by_sql = 'user_lastvisit %s, lower(username) %s';
						$lastvisit_selected = ' selected="selected"';
					break;

					case 'b':
						$sort_by_sql = 'user_digest_has_unsubscribed %s, lower(username) %s';
						$has_unsubscribed_selected = ' selected="selected"';
					break;

					case 't':
						$sort_by_sql = 'user_digest_last_sent %s, lower(username) %s';
						$last_sent_selected = ' selected="selected"';
					break;

					case 'u':
					default:
						$sort_by_sql = 'lower(username) %s';
						$username_selected = ' selected="selected"';
					break;
				}

				// Set up sort order
				$order_by_sql = ($sortorder == 'a') ? 'ASC' : 'DESC';
				$ascending_selected = ($sortorder == 'a') ? ' selected="selected"' : '';
				$descending_selected = ($sortorder == 'd') ? ' selected="selected"' : '';

				// Set up member search SQL, either by email or username
				$match_any_chars = $this->db->get_any_char();
				if (strpos($member, '@') === false)
				{
					// Username search
					$member_sql = ($member !== '') ? " username_clean " . $this->db->sql_like_expression($match_any_chars . utf8_case_fold_nfc($member) . $match_any_chars) . " AND " : '';
				}
				else
				{
					// Email search
					$member_sql = ($member !== '') ? " user_email " . $this->db->sql_like_expression($match_any_chars . utf8_case_fold_nfc($member) . $match_any_chars) . " AND " : '';
				}

				// Get the total rows for pagination purposes
				$sql_array = array(
					'SELECT'	=> 'COUNT(user_id) AS total_users',

					'FROM'		=> array(
						USERS_TABLE		=> 'u',
					),

					'WHERE'		=> "$subscribe_sql $member_sql " . $this->db->sql_in_set('user_type', array(USER_NORMAL, USER_FOUNDER)),
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query($sql);

				// Get the total users, this is a single row, single field.
				$total_users = $this->db->sql_fetchfield('total_users');

				// Free the result
				$this->db->sql_freeresult($result);

				// Create pagination logic
				$pagination_url = append_sid("index.$this->phpEx?i=-phpbbservices-digests-acp-main_module&amp;mode=digests_edit_subscribers&amp;sortby=$sortby&amp;subscribe=$subscribe&amp;member=$member&amp;selected=$selected&amp;sortorder=$sortorder");
				$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total_users, $this->config['phpbbservices_digests_users_per_page'], $start);

				// Stealing some code from my Smartfeed extension so I can get a list of forums that a particular user can access

				// We need to know which auth_option_id corresponds to the forum read privilege (f_read) privilege.
				$auth_options = array('f_read');

				$sql_array = array(
					'SELECT'	=> 'auth_option, auth_option_id',

					'FROM'		=> array(
						ACL_OPTIONS_TABLE		=> 'o',
					),

					'WHERE'		=> $this->db->sql_in_set('auth_option', $auth_options),
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query($sql);	// Should return 20
				$row = $this->db->sql_fetchrow($result);
				$read_id = $row['auth_option_id'];

				$this->db->sql_freeresult($result); // Query be gone!

				// Fill in some non-block template variables
				$this->template->assign_vars(array(
					'ALL_SELECTED'				=> $all_selected,
					'ASCENDING_SELECTED'		=> $ascending_selected,
					'DAILY_SELECTED'			=> $daily_selected,
					'DEFAULT_SELECTED'			=> $selected_subscribe,
					'DESCENDING_SELECTED'		=> $descending_selected,
					'DIGESTS_HTML_VALUE'			=> constants::DIGESTS_HTML_VALUE,
					'DIGESTS_HTML_CLASSIC_VALUE'	=> constants::DIGESTS_HTML_CLASSIC_VALUE,
					'DIGESTS_PLAIN_VALUE'			=> constants::DIGESTS_PLAIN_VALUE,
					'DIGESTS_PLAIN_CLASSIC_VALUE'	=> constants::DIGESTS_PLAIN_CLASSIC_VALUE,
					'DIGESTS_FORMAT_TEXT_VALUE'	=> constants::DIGESTS_TEXT_VALUE,
					'EMAIL_SELECTED'			=> $email_selected,
					'FORMAT_SELECTED'			=> $format_selected,
					'FREQUENCY_SELECTED'		=> $frequency_selected,
					'HAS_UNSUBSCRIBED_SELECTED'	=> $has_unsubscribed_selected,
					'HOUR_SELECTED'				=> $hour_selected,
					'IGNORE_SELECTED'			=> $selected_ignore,
					'IMAGE_PATH'				=> $this->phpbb_root_path . 'ext/phpbbservices/digests/adm/images/',
					'LAST_SENT_SELECTED'		=> $last_sent_selected,
					'LASTVISIT_SELECTED'		=> $lastvisit_selected,
					'L_CONTEXT'					=> $context,
					'MEMBER'					=> $member,
					'MONTHLY_SELECTED'			=> $monthly_selected,
					'NONE_SELECTED'				=> $selected_unsubscribe,
					'STOPPED_SUBSCRIBING_SELECTED'	=> $stopped_subscribing,
					'SUBSCRIBE_SELECTED'		=> $subscribe_selected,
					'TOTAL_USERS'       		=> $this->language->lang('DIGESTS_LIST_USERS', (int) $total_users),
					'UNSUBSCRIBE_SELECTED'		=> $unsubscribe_selected,
					'USERNAME_SELECTED'			=> $username_selected,
					'WEEKLY_SELECTED'			=> $weekly_selected,
				));

				$sql_array = array(
					'SELECT'	=> '*, CASE
										WHEN user_digest_send_hour_gmt + ' . $my_time_zone . ' >= 24 THEN
						 					user_digest_send_hour_gmt + ' . $my_time_zone . ' - 24  
										WHEN user_digest_send_hour_gmt + ' . $my_time_zone . ' < 0 THEN
						 					user_digest_send_hour_gmt + ' . $my_time_zone . ' + 24 
										ELSE user_digest_send_hour_gmt + ' . $my_time_zone . '
										END AS send_hour_board',

					'FROM'		=> array(
						USERS_TABLE		=> 'u',
					),

					'WHERE'		=> "$subscribe_sql $member_sql " . $this->db->sql_in_set('user_type', array(USER_NORMAL, USER_FOUNDER)),

					'ORDER_BY'	=> sprintf($sort_by_sql, $order_by_sql, $order_by_sql),
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query_limit($sql, $this->config['phpbbservices_digests_users_per_page'], $start);

				while ($row = $this->db->sql_fetchrow($result))
				{

					// Make some translations into something more readable
					switch($row['user_digest_type'])
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
							$digest_type = $this->language->lang('DIGESTS_UNKNOWN');
						break;
					}

					switch($row['user_digest_format'])
					{
						case constants::DIGESTS_HTML_VALUE:
							$digest_format = $this->language->lang('DIGESTS_FORMAT_HTML');
						break;

						case constants::DIGESTS_HTML_CLASSIC_VALUE:
							$digest_format = $this->language->lang('DIGESTS_FORMAT_HTML_CLASSIC');
						break;

						case constants::DIGESTS_PLAIN_VALUE:
							$digest_format = $this->language->lang('DIGESTS_FORMAT_PLAIN');
						break;

						case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
							$digest_format = $this->language->lang('DIGESTS_FORMAT_PLAIN_CLASSIC');
						break;

						case constants::DIGESTS_TEXT_VALUE:
							$digest_format = $this->language->lang('DIGESTS_FORMAT_TEXT');
						break;

						default:
							$digest_format = $this->language->lang('DIGESTS_UNKNOWN');
						break;
					}

					// Calculate a digest send hour in administrator's time zone
					$send_hour_admin_offset = floor($row['user_digest_send_hour_gmt']) + $my_time_zone;
					$send_hour_admin_offset = $this->helper->check_send_hour($send_hour_admin_offset);

					// Create an array of UTC offsets from board time zone. Also create the display hour format.
					$hour_utc = array();
					$display_hour = array();

					for($i=0; $i<24; $i++)
					{
						if (($i - $my_time_zone) < 0)
						{
							$hour_utc[$i] = $i - $my_time_zone + 24;
						}
						else if (($i - $my_time_zone) > 23)
						{
							$hour_utc[$i] = $i - $my_time_zone - 24;
						}
						else
						{
							$hour_utc[$i] = $i - $my_time_zone;
						}
						$display_hour[$i] = $this->helper->make_hour_string($i, $this->user->data['user_dateformat']);
					}

					$sql_array = array(
						'SELECT'	=> 'forum_id ',

						'FROM'		=> array(
							$this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE => 'sf',
						),

						'WHERE'		=> 'user_id = ' . (int) $row['user_id'],
					);

					$sql2 = $this->db->sql_build_query('SELECT', $sql_array);

					$result2 = $this->db->sql_query($sql2);
					$subscribed_forums = $this->db->sql_fetchrowset($result2);
					$this->db->sql_freeresult($result2);

					$all_by_default = (count($subscribed_forums) === 0);

					$user_lastvisit = ($row['user_lastvisit'] == 0) ? $this->language->lang('DIGESTS_NEVER_VISITED') : $this->user->format_date($row['user_lastvisit'] + (60 * 60 * ($my_time_zone - (date('O')/100))), $this->user->data['user_dateformat']);
					$user_digest_last_sent = ($row['user_digest_last_sent'] == 0) ? $this->language->lang('DIGESTS_NO_DIGESTS_SENT') : $this->user->format_date($row['user_digest_last_sent'] + (60 * 60 * ($my_time_zone - (date('O')/100))), $this->user->data['user_dateformat']);

					$this->template->assign_block_vars('digests_edit_subscribers', array(
							'1ST'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_FIRST),
							'ALL'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_ALL),
							'BM'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS),
							'BOARD_OFFSET_0'					=> $hour_utc[0],
							'BOARD_OFFSET_1'					=> $hour_utc[1],
							'BOARD_OFFSET_2'					=> $hour_utc[2],
							'BOARD_OFFSET_3'					=> $hour_utc[3],
							'BOARD_OFFSET_4'					=> $hour_utc[4],
							'BOARD_OFFSET_5'					=> $hour_utc[5],
							'BOARD_OFFSET_6'					=> $hour_utc[6],
							'BOARD_OFFSET_7'					=> $hour_utc[7],
							'BOARD_OFFSET_8'					=> $hour_utc[8],
							'BOARD_OFFSET_9'					=> $hour_utc[9],
							'BOARD_OFFSET_10'					=> $hour_utc[10],
							'BOARD_OFFSET_11'					=> $hour_utc[11],
							'BOARD_OFFSET_12'					=> $hour_utc[12],
							'BOARD_OFFSET_13'					=> $hour_utc[13],
							'BOARD_OFFSET_14'					=> $hour_utc[14],
							'BOARD_OFFSET_15'					=> $hour_utc[15],
							'BOARD_OFFSET_16'					=> $hour_utc[16],
							'BOARD_OFFSET_17'					=> $hour_utc[17],
							'BOARD_OFFSET_18'					=> $hour_utc[18],
							'BOARD_OFFSET_19'					=> $hour_utc[19],
							'BOARD_OFFSET_20'					=> $hour_utc[20],
							'BOARD_OFFSET_21'					=> $hour_utc[21],
							'BOARD_OFFSET_22'					=> $hour_utc[22],
							'BOARD_OFFSET_23'					=> $hour_utc[23],
							'DIGEST_MAX_SIZE' 					=> $row['user_digest_max_display_words'],
							'DISPLAY_HOUR_0'					=> $display_hour[0],
							'DISPLAY_HOUR_1'					=> $display_hour[1],
							'DISPLAY_HOUR_2'					=> $display_hour[2],
							'DISPLAY_HOUR_3'					=> $display_hour[3],
							'DISPLAY_HOUR_4'					=> $display_hour[4],
							'DISPLAY_HOUR_5'					=> $display_hour[5],
							'DISPLAY_HOUR_6'					=> $display_hour[6],
							'DISPLAY_HOUR_7'					=> $display_hour[7],
							'DISPLAY_HOUR_8'					=> $display_hour[8],
							'DISPLAY_HOUR_9'					=> $display_hour[9],
							'DISPLAY_HOUR_10'					=> $display_hour[10],
							'DISPLAY_HOUR_11'					=> $display_hour[11],
							'DISPLAY_HOUR_12'					=> $display_hour[12],
							'DISPLAY_HOUR_13'					=> $display_hour[13],
							'DISPLAY_HOUR_14'					=> $display_hour[14],
							'DISPLAY_HOUR_15'					=> $display_hour[15],
							'DISPLAY_HOUR_16'					=> $display_hour[16],
							'DISPLAY_HOUR_17'					=> $display_hour[17],
							'DISPLAY_HOUR_18'					=> $display_hour[18],
							'DISPLAY_HOUR_19'					=> $display_hour[19],
							'DISPLAY_HOUR_20'					=> $display_hour[20],
							'DISPLAY_HOUR_21'					=> $display_hour[21],
							'DISPLAY_HOUR_22'					=> $display_hour[22],
							'DISPLAY_HOUR_23'					=> $display_hour[23],
							'L_DIGEST_CHANGE_SUBSCRIPTION' 		=> ($row['user_digest_type'] !== constants::DIGESTS_NONE_VALUE) ? $this->language->lang('DIGESTS_UNSUBSCRIBE') : $this->language->lang('DIGESTS_SUBSCRIBE_LITERAL'),
							'S_ALL_BY_DEFAULT'					=> $all_by_default,
							'S_ATTACHMENTS_NO_CHECKED' 			=> ($row['user_digest_attachments'] == 0),
							'S_ATTACHMENTS_YES_CHECKED' 		=> ($row['user_digest_attachments'] == 1),
							'S_BLOCK_IMAGES_NO_CHECKED' 		=> ($row['user_digest_block_images'] == 0),
							'S_BLOCK_IMAGES_YES_CHECKED' 		=> ($row['user_digest_block_images'] == 1),
							'S_BOARD_SELECTED' 					=> ($row['user_digest_sortby'] == constants::DIGESTS_SORTBY_BOARD),
							'S_DIGEST_FILTER_FOES_CHECKED_NO' 	=> ($row['user_digest_remove_foes'] == 0),
							'S_DIGEST_FILTER_FOES_CHECKED_YES' 	=> ($row['user_digest_remove_foes'] == 1),
							'S_DIGEST_DAY_CHECKED' 				=> ($row['user_digest_type'] == constants::DIGESTS_DAILY_VALUE),
							'S_DIGEST_HTML_CHECKED' 			=> ($row['user_digest_format'] == constants::DIGESTS_HTML_VALUE),
							'S_DIGEST_HTML_CLASSIC_CHECKED' 	=> ($row['user_digest_format'] == constants::DIGESTS_HTML_CLASSIC_VALUE),
							'S_DIGEST_MONTH_CHECKED' 			=> ($row['user_digest_type'] == constants::DIGESTS_MONTHLY_VALUE),
							'S_DIGEST_NEW_POSTS_ONLY_CHECKED_NO' 	=> ($row['user_digest_new_posts_only'] == 0),
							'S_DIGEST_NEW_POSTS_ONLY_CHECKED_YES' 	=> ($row['user_digest_new_posts_only'] == 1),
							'S_DIGEST_NONE_CHECKED' 			=> ($row['user_digest_type'] == constants::DIGESTS_NONE_VALUE),
							'S_DIGEST_NO_POST_TEXT_CHECKED_NO' 	=> ($row['user_digest_no_post_text'] == 0),
							'S_DIGEST_NO_POST_TEXT_CHECKED_YES' => ($row['user_digest_no_post_text'] == 1),
							'S_DIGEST_PLAIN_CHECKED' 			=> ($row['user_digest_format'] == constants::DIGESTS_PLAIN_VALUE),
							'S_DIGEST_PLAIN_CLASSIC_CHECKED' 	=> ($row['user_digest_format'] == constants::DIGESTS_PLAIN_CLASSIC_VALUE),
							'S_DIGEST_PM_MARK_READ_CHECKED_NO' 	=> ($row['user_digest_pm_mark_read'] == 0),
							'S_DIGEST_PM_MARK_READ_CHECKED_YES' => ($row['user_digest_pm_mark_read'] == 1),
							'S_DIGEST_POST_ANY'					=> ($row['user_digest_filter_type'] == constants::DIGESTS_ALL),
							'S_DIGEST_POST_BM'					=> ($row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS),
							'S_DIGEST_POST_FIRST'				=> ($row['user_digest_filter_type'] == constants::DIGESTS_FIRST),
							'S_DIGEST_PRIVATE_MESSAGES_IN_DIGEST_NO' 	=> ($row['user_digest_show_pms'] == 0),
							'S_DIGEST_PRIVATE_MESSAGES_IN_DIGEST_YES' 	=> ($row['user_digest_show_pms'] == 1),
							'S_DIGEST_SEND_HOUR_0_CHECKED'		=> ($send_hour_admin_offset == 0),
							'S_DIGEST_SEND_HOUR_1_CHECKED'		=> ($send_hour_admin_offset == 1),
							'S_DIGEST_SEND_HOUR_2_CHECKED'		=> ($send_hour_admin_offset == 2),
							'S_DIGEST_SEND_HOUR_3_CHECKED'		=> ($send_hour_admin_offset == 3),
							'S_DIGEST_SEND_HOUR_4_CHECKED'		=> ($send_hour_admin_offset == 4),
							'S_DIGEST_SEND_HOUR_5_CHECKED'		=> ($send_hour_admin_offset == 5),
							'S_DIGEST_SEND_HOUR_6_CHECKED'		=> ($send_hour_admin_offset == 6),
							'S_DIGEST_SEND_HOUR_7_CHECKED'		=> ($send_hour_admin_offset == 7),
							'S_DIGEST_SEND_HOUR_8_CHECKED'		=> ($send_hour_admin_offset == 8),
							'S_DIGEST_SEND_HOUR_9_CHECKED'		=> ($send_hour_admin_offset == 9),
							'S_DIGEST_SEND_HOUR_10_CHECKED'		=> ($send_hour_admin_offset == 10),
							'S_DIGEST_SEND_HOUR_11_CHECKED'		=> ($send_hour_admin_offset == 11),
							'S_DIGEST_SEND_HOUR_12_CHECKED'		=> ($send_hour_admin_offset == 12),
							'S_DIGEST_SEND_HOUR_13_CHECKED'		=> ($send_hour_admin_offset == 13),
							'S_DIGEST_SEND_HOUR_14_CHECKED'		=> ($send_hour_admin_offset == 14),
							'S_DIGEST_SEND_HOUR_15_CHECKED'		=> ($send_hour_admin_offset == 15),
							'S_DIGEST_SEND_HOUR_16_CHECKED'		=> ($send_hour_admin_offset == 16),
							'S_DIGEST_SEND_HOUR_17_CHECKED'		=> ($send_hour_admin_offset == 17),
							'S_DIGEST_SEND_HOUR_18_CHECKED'		=> ($send_hour_admin_offset == 18),
							'S_DIGEST_SEND_HOUR_19_CHECKED'		=> ($send_hour_admin_offset == 19),
							'S_DIGEST_SEND_HOUR_20_CHECKED'		=> ($send_hour_admin_offset == 20),
							'S_DIGEST_SEND_HOUR_21_CHECKED'		=> ($send_hour_admin_offset == 21),
							'S_DIGEST_SEND_HOUR_22_CHECKED'		=> ($send_hour_admin_offset == 22),
							'S_DIGEST_SEND_HOUR_23_CHECKED'		=> ($send_hour_admin_offset == 23),
							'S_DIGEST_SEND_ON_NO_POSTS_NO_CHECKED' 	=> ($row['user_digest_send_on_no_posts'] == 0),
							'S_DIGEST_SEND_ON_NO_POSTS_YES_CHECKED' => ($row['user_digest_send_on_no_posts'] == 1),
							'S_DIGEST_SHOW_MINE_CHECKED_YES' 	=> ($row['user_digest_show_mine'] == 1),
							'S_DIGEST_SHOW_MINE_CHECKED_NO' 	=> ($row['user_digest_show_mine'] == 0),
							'S_DIGEST_TEXT_CHECKED' 			=> ($row['user_digest_format'] == constants::DIGESTS_TEXT_VALUE),
							'S_DIGEST_WEEK_CHECKED' 			=> ($row['user_digest_type'] == constants::DIGESTS_WEEKLY_VALUE),
							'S_LASTVISIT_NO_CHECKED' 			=> ($row['user_digest_reset_lastvisit'] == 0),
							'S_LASTVISIT_YES_CHECKED' 			=> ($row['user_digest_reset_lastvisit'] == 1),
							'S_POSTDATE_DESC_SELECTED' 			=> ($row['user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE_DESC),
							'S_POSTDATE_SELECTED' 				=> ($row['user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE),
							'S_STANDARD_DESC_SELECTED' 			=> ($row['user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD_DESC),
							'S_STANDARD_SELECTED' 				=> ($row['user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD),
							'S_TOC_NO_CHECKED' 					=> ($row['user_digest_toc'] == 0),
							'S_TOC_YES_CHECKED' 				=> ($row['user_digest_toc'] == 1),
							'USERNAME'							=> $row['username'],
							'USER_DIGEST_FORMAT'				=> $digest_format,
							'USER_DIGEST_HAS_UNSUBSCRIBED'		=> ($row['user_digest_has_unsubscribed']) ? 'x' : '-',
							'USER_DIGEST_LAST_SENT'				=> $user_digest_last_sent,
							'USER_DIGEST_MAX_DISPLAY_WORDS'		=> $row['user_digest_max_display_words'],
							'USER_DIGEST_MAX_POSTS'				=> $row['user_digest_max_posts'],
							'USER_DIGEST_MIN_WORDS'				=> $row['user_digest_min_words'],
							'USER_DIGEST_TYPE'					=> $digest_type,
							'USER_EMAIL'						=> $row['user_email'],
							'USER_ID'							=> $row['user_id'],
							'USER_LAST_VISIT'					=> $user_lastvisit,
							'USER_SUBSCRIBE_UNSUBSCRIBE_FLAG'	=> ($row['user_digest_type'] !== constants::DIGESTS_NONE_VALUE) ? 'u' : 's')
					);

					// Now let's get this user's forum permissions. Note that non-registered, robots etc., get a list of public forums
					// with read permissions.

					unset($allowed_forums, $forum_array, $parent_stack);

					$forum_array = $this->auth->acl_raw_data_single_user($row['user_id']);

					foreach ($forum_array as $key => $value)
					{

						foreach ($value as $auth_option_id => $auth_setting)
						{
							if ($auth_option_id == $read_id)
							{
								if ($auth_setting == 1)
								{
									$allowed_forums[] = $key;
								}
							}
						}

					}

					// Now we will display the forums that this user can read, as well as any parent forums, checking those if any that
					// the user has subscribed to.

					if (isset($allowed_forums) && is_array($allowed_forums) && count($allowed_forums) > 0)
					{

						$sql_array = array(
							'SELECT'	=> 'forum_name, forum_id, parent_id, forum_type',

							'FROM'		=> array(
								FORUMS_TABLE		=> 'f',
							),

							'WHERE'		=> $this->db->sql_in_set('forum_id', $allowed_forums) . ' AND forum_type <> ' . FORUM_LINK . "
									AND forum_password = ''",

							'ORDER_BY'	=> 'left_id ASC',
						);

						$sql2 = $this->db->sql_build_query('SELECT', $sql_array);
						$result2 = $this->db->sql_query($sql2);

						$parent_stack = array();	// Holds a stack showing the current parent_id of the forum
						$last_parent_id = 0;

						$rowset2 = $this->db->sql_fetchrowset($result2);
						for ($i=0; $i < count($rowset2); $i++)
						{

							$parent_id = (int) $rowset2[$i]['parent_id'];
							$forum_id = (int) $rowset2[$i]['forum_id'];
							$forum_type = (int) $rowset2[$i]['forum_type'];	// 0 = category, 1 = forum

							// Create a div block? Yes, if parent is not in the stack.
							if (!in_array($parent_id, $parent_stack))
							{
								// Need to add a category level here
								$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
										'DIV_ID' 			=> $rowset2[$i]['parent_id'],
										'S_DIV_CLOSE' 		=> false,
										'S_DIV_OPEN' 		=> true,
										'S_PRINT' 			=> false,
									)
								);
								array_push($parent_stack, (int) $parent_id);
							}
							else
							{
								// Close a div block? Only if parent_id is in parent stack and the current parent id has changed
								if ($parent_id !== $last_parent_id && in_array($parent_id, $parent_stack))
								{
									// Need to close a category level here
									$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
											'S_DIV_CLOSE' 	=> true,
											'S_DIV_OPEN' 	=> false,
											'S_PRINT' 		=> false,
										)
									);
									array_pop($parent_stack);
								}
							}

							// Check this forum's checkbox? Only if they have forum subscriptions.
							if (!$all_by_default)
							{
								$check = false;
								if ($forum_type == FORUM_POST)	// Categories can't be checked, as they are wrappers
								{
									foreach($subscribed_forums as $this_row)
									{
										if ($this_row['forum_id'] == $forum_id)
										{
											$check = true;
											break;
										}
									}
								}
							}
							else
							{
								$check = true;
							}

							// Show the forum or category
							$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
									'FORUM_ID' 				=> $forum_id,
									'FORUM_LABEL' 			=> $rowset2[$i]['forum_name'],
									'S_FORUM_SUBSCRIBED' 	=> $check,
									'S_IS_FORUM' 			=> ($forum_type == FORUM_POST),
									'S_PRINT' 				=> true,
								)
							);

							$last_parent_id = $parent_id;

						}

						// Now out of the loop, it is important to remember to close any open <div> tags. Typically there is at least one.
						if (isset($rowset2) && is_array($rowset2))
						{
							for ($i=0; $i<count($parent_stack); $i++)
							{
								array_pop($parent_stack);
								// Need to close the <div> tag
								$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
										'S_DIV_CLOSE' 	=> true,
										'S_DIV_OPEN' 	=> false,
										'S_PRINT' 		=> false,
									)
								);
							}
						}

						$this->db->sql_freeresult($result2);

					}

				}

				$this->db->sql_freeresult($result); // Query be gone!

			break;

			case 'digests_balance_load':

				$avg_per_hour = $this->average_subscribers_per_hour();

				// Translate time zone information
				$this->template->assign_vars(array(
					'L_DIGESTS_HOUR_SENT'               		=> $this->language->lang('DIGESTS_HOUR_SENT', $my_time_zone),
					'L_TITLE'									=> $this->language->lang('ACP_DIGESTS_BALANCE_LOAD'),
					'L_TITLE_EXPLAIN'							=> $this->language->lang('ACP_DIGESTS_BALANCE_LOAD_EXPLAIN'),
					'S_BALANCE_LOAD'							=> true,
					'S_DIGESTS_AVERAGE'							=> '<strong>' . $avg_per_hour . '</strong>',
					'S_INCLUDE_DIGESTS_CSS'						=> true,	// Need to include special Digests CSS
				));

				$sql_array = array(
					'SELECT'	=> 'user_digest_send_hour_gmt AS hour, COUNT(user_id) AS hour_count',

					'FROM'		=> array(
						USERS_TABLE		=> 'u',
					),

					'WHERE'		=> "user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' AND user_type <> " . USER_IGNORE,

					'GROUP_BY'	=> 'user_digest_send_hour_gmt',

					'ORDER_BY'	=> '1',
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query($sql);
				$rowset = $this->db->sql_fetchrowset($result);

				for($i=0;$i<24;$i++)
				{

					// Convert digest hour to UTC
					$hour_utc = floor($i - $my_time_zone);
					$hour_utc = $this->helper->check_send_hour($hour_utc);

					// If there are digest counts for this UTC hour, show it, otherwise show zero (no digests for this UTC hour)
					$hour_count = 0;
					if (isset($rowset))
					{
						foreach ($rowset as $row)
						{
							if (floor($row['hour']) == $hour_utc)
							{
								$hour_count = $row['hour_count'];
								break;
							}
						}
					}

					$hour_subscribers = $this->get_subscribers_for_hour($i, $my_time_zone);

					$daily_subscribers = array();
					$weekly_subscribers = array();
					$monthly_subscribers = array();

					foreach ($hour_subscribers as $hour_subscriber)
					{

						if (key($hour_subscriber) == constants::DIGESTS_DAILY_VALUE)
						{
							$daily_subscribers[] = current($hour_subscriber);
						}
						if (key($hour_subscriber) == constants::DIGESTS_WEEKLY_VALUE)
						{
							$weekly_subscribers[] = current($hour_subscriber);
						}
						if (key($hour_subscriber) == constants::DIGESTS_MONTHLY_VALUE)
						{
							$monthly_subscribers[] = current($hour_subscriber);
						}

					}

					$daily_subscribers_str = (count($daily_subscribers) > 0 ) ? implode($this->language->lang('DIGESTS_COMMA'), $daily_subscribers) : '';
					$weekly_subscribers_str = (count($weekly_subscribers) > 0 ) ? implode($this->language->lang('DIGESTS_COMMA'), $weekly_subscribers) : '';
					$monthly_subscribers_str = (count($monthly_subscribers) > 0 ) ? implode($this->language->lang('DIGESTS_COMMA'), $monthly_subscribers): '';

					$this->template->assign_block_vars('digests_balance_load', array(
						'HOUR'              	=> $this->helper->make_hour_string($i, $this->user->data['user_dateformat']),
						'HOUR_COUNT'        	=> ($hour_count > $avg_per_hour) ? '<strong>' . $hour_count . '</strong>' : $hour_count,
						'HOUR_UTC'        		=> $hour_utc,
						'SUBSCRIBERS_DAILY'		=> $daily_subscribers_str,
						'SUBSCRIBERS_WEEKLY'	=> $weekly_subscribers_str,
						'SUBSCRIBERS_MONTHLY'	=> $monthly_subscribers_str,
					));

				}
				$this->db->sql_freeresult($result); // Query be gone!
			break;

			case 'digests_mass_subscribe_unsubscribe':
				$this->template->assign_vars(array(
					'L_TITLE'									=> $this->language->lang('ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'),
					'L_TITLE_EXPLAIN'							=> $this->language->lang('ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'),
					'S_DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE'	=> (bool) $this->config['phpbbservices_digests_enable_subscribe_unsubscribe'],
					'S_DIGESTS_INCLUDE_ADMINS'					=> (bool) $this->config['phpbbservices_digests_include_admins'],
					'S_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'		=> true,	// Show this module
					'S_DIGESTS_SUBSCRIBE_ALL'					=> (bool) $this->config['phpbbservices_digests_subscribe_all'],
				));
			break;

			case 'digests_reset_cron_run_time':
				$this->template->assign_vars(array(
					'L_TITLE'									=> $this->language->lang('ACP_DIGESTS_RESET_CRON_RUN_TIME'),
					'L_TITLE_EXPLAIN'							=> $this->language->lang('ACP_DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'),
					'S_DIGESTS_RESET_CRON_RUN_TIME'				=> (bool) $this->config['phpbbservices_digests_reset_cron_run_time'],
					'S_DIGESTS_RESET_MAILER'					=> true, // Enable this module
				));
			break;

			case 'digests_test':
				// Show subscribers for the current hour. This gives admins some idea who or if anyone will received digests for the current hour.

				$server_timezone = (float) date('O')/100;	// Server timezone offset from UTC, in hours. Digests are mailed based on UTC time, so rehosting is unaffected.
				$utc_time = time() - (int) ($server_timezone * 60 * 60);	// Convert server time (or requested run date) into UTC

				// Get the current hour in UTC, so applicable digests can be sent out for this hour
				$current_hour_utc = (int) date('G', $utc_time); // 0 thru 23

				// Get subscribers for current hour
				$current_hour_subscribers = array();
				$sql_array = array(
					'SELECT'	=> 'username, user_digest_type',

					'FROM'		=> array(
						USERS_TABLE	=> 'u',
					),

					'WHERE'		=> $this->db->sql_in_set('user_digest_send_hour_gmt', array($current_hour_utc)) . ' AND ' . $this->db->sql_in_set('user_digest_type', array(constants::DIGESTS_NONE_VALUE), true),

					'ORDER_BY'	=> 'username',
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query($sql);
				$rowset = $this->db->sql_fetchrowset($result);

				foreach ($rowset as $row)
				{
					switch ($row['user_digest_type'])
					{
						case constants::DIGESTS_WEEKLY_VALUE:
							$digest_type = $this->language->lang('DIGESTS_WEEKLY');
						break;

						case constants::DIGESTS_MONTHLY_VALUE:
							$digest_type = $this->language->lang('DIGESTS_MONTHLY');
						break;

						case constants::DIGESTS_DAILY_VALUE:
						default:
							$digest_type = $this->language->lang('DIGESTS_DAILY');
						break;
					}
					$current_hour_subscribers[] = $row['username'] . ' (' . $digest_type . ')';
				}

				$this->template->assign_vars(array(
					'L_TITLE'									=> $this->language->lang('ACP_DIGESTS_TEST'),
					'L_TITLE_EXPLAIN'							=> sprintf($this->language->lang('ACP_DIGESTS_TEST_EXPLAIN'),implode($this->language->lang('DIGESTS_COMMA'), $current_hour_subscribers)),
					'S_DIGESTS_MANUAL_RUN'						=> true, // Run this module
					'S_DIGESTS_RUN_TEST'						=> (bool) $this->config['phpbbservices_digests_test'],
					'S_DIGESTS_RUN_TEST_SEND_TO_ADMIN'			=> (bool) $this->config['phpbbservices_digests_test_send_to_admin'],
					'S_DIGESTS_RUN_TEST_CLEAR_SPOOL'			=> (bool) $this->config['phpbbservices_digests_test_clear_spool'],
					'S_DIGESTS_RUN_TEST_SPOOL'					=> (bool) $this->config['phpbbservices_digests_test_spool'],
					'S_INCLUDE_DIGESTS_MANUAL_MAILER'			=> true,	// Allows inclusion of date and hour picker
					'TEST_DATE_HOUR'							=> $this->config['phpbbservices_digests_test_date_hour'],
					'TEST_EMAIL_ADDRESS'						=> $this->config['phpbbservices_digests_test_email_address'],
				));



			break;

			default:
				trigger_error('NO_MODE', E_USER_ERROR);

		}

		if ($submit)
		{
			if (!check_form_key($form_key))
			{
				$error[] = $this->language->lang('FORM_INVALID');
				$mode = NULL;
			}

			if ($mode === 'digests_general')
			{
				$this->config->set('phpbbservices_digests_block_images', $this->request->variable('phpbbservices_digests_block_images', 0));
				$this->config->set('phpbbservices_digests_custom_stylesheet_path', $this->request->variable('phpbbservices_digests_custom_stylesheet_path', ''));
				$this->config->set('phpbbservices_digests_debug', $this->request->variable('phpbbservices_digests_debug', 0));
				$this->config->set('phpbbservices_digests_enable_auto_subscriptions', $this->request->variable('phpbbservices_digests_enable_auto_subscriptions', 0));
				$this->config->set('phpbbservices_digests_enable_custom_stylesheets', $this->request->variable('phpbbservices_digests_enable_custom_stylesheets', 0));
				$this->config->set('phpbbservices_digests_enable_log', $this->request->variable('phpbbservices_digests_enable_log', 0));
				$this->config->set('phpbbservices_digests_exclude_forums', $this->request->variable('phpbbservices_digests_exclude_forums', ''));
				$this->config->set('phpbbservices_digests_foreign_urls', $this->request->variable('phpbbservices_digests_foreign_urls', ''));
				$this->config->set('phpbbservices_digests_from_email_address', $this->request->variable('phpbbservices_digests_from_email_address', ''));
				$this->config->set('phpbbservices_digests_from_email_name', $this->request->variable('phpbbservices_digests_from_email_name', ''));
				$this->config->set('phpbbservices_digests_include_forums', $this->request->variable('phpbbservices_digests_include_forums', ''));
				$this->config->set('phpbbservices_digests_lowercase_digest_type', $this->request->variable('phpbbservices_digests_lowercase_digest_type', 0));
				$this->config->set('phpbbservices_digests_max_cron_hrs', $this->request->variable('phpbbservices_digests_max_cron_hrs', 0));
				$this->config->set('phpbbservices_digests_max_items', $this->request->variable('phpbbservices_digests_max_items', 0));
				$this->config->set('phpbbservices_digests_notify_on_admin_changes', $this->request->variable('phpbbservices_digests_notify_on_admin_changes', 0));
				$this->config->set('phpbbservices_digests_registration_field', $this->request->variable('phpbbservices_digests_registration_field', 0));
				$this->config->set('phpbbservices_digests_reply_to_email_address', $this->request->variable('phpbbservices_digests_reply_to_email_address', ''));
				$this->config->set('phpbbservices_digests_saluation_fields', $this->request->variable('phpbbservices_digests_saluation_fields', ''));
				$this->config->set('phpbbservices_digests_show_email', $this->request->variable('phpbbservices_digests_show_email', 0));
				$this->config->set('phpbbservices_digests_show_forum_path', $this->request->variable('phpbbservices_digests_show_forum_path', 0));
				$this->config->set('phpbbservices_digests_strip_tags', $this->request->variable('phpbbservices_digests_strip_tags', ''));
				$this->config->set('phpbbservices_digests_users_per_page', $this->request->variable('phpbbservices_digests_users_per_page', 20));
				$this->config->set('phpbbservices_digests_weekly_digest_day', $this->request->variable('phpbbservices_digests_weekly_digest_day', 0));

				// If config variable phpbbservices_digests_min_popularity_size value is more than any row in the phpbb_users table for the
				// column user_digest_popularity_size, adjust this column
				$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET user_digest_popularity_size = ' . (int) $this->request->variable('phpbbservices_digests_min_popularity_size', 0) . '
							WHERE user_digest_popularity_size < ' . (int) $this->request->variable('phpbbservices_digests_min_popularity_size', 0);
				$this->db->sql_query($sql);

				// Also adjust the digest default so new digest subscriptions will have this value if it is lower than the new value.
				if ((int) $this->config['phpbbservices_digests_user_digest_popularity_size'] < (int) $this->request->variable('phpbbservices_digests_min_popularity_size', 0))
				{
					$this->config->set('phpbbservices_digests_user_digest_popularity_size', (int) $this->request->variable('phpbbservices_digests_min_popularity_size', 0));
				}
			}

			if ($mode === 'digests_user_defaults')
			{
				$this->config->set('phpbbservices_digests_user_digest_attachments', $this->request->variable('phpbbservices_digests_user_digest_attachments', 0));
				$this->config->set('phpbbservices_digests_user_digest_block_images', $this->request->variable('phpbbservices_digests_user_digest_block_images', 0));
				$this->config->set('phpbbservices_digests_user_digest_filter_type', $this->request->variable('phpbbservices_digests_user_digest_filter_type', constants::DIGESTS_ALL));
				$this->config->set('phpbbservices_digests_user_digest_format', $this->request->variable('phpbbservices_digests_user_digest_format', constants::DIGESTS_HTML_VALUE));
				$this->config->set('phpbbservices_digests_user_digest_min_words', $this->request->variable('phpbbservices_digests_user_digest_min_words', 0));
				$this->config->set('phpbbservices_digests_user_digest_max_display_words', $this->request->variable('phpbbservices_digests_user_digest_max_display_words', 0));
				$this->config->set('phpbbservices_digests_user_digest_max_posts', $this->request->variable('phpbbservices_digests_user_digest_max_posts', 0));
				$this->config->set('phpbbservices_digests_user_digest_new_posts_only', $this->request->variable('phpbbservices_digests_user_digest_new_posts_only', 0));
				$this->config->set('phpbbservices_digests_user_digest_pm_mark_read', $this->request->variable('phpbbservices_digests_user_digest_pm_mark_read', 0));
				$this->config->set('phpbbservices_digests_user_digest_popular', $this->request->variable('phpbbservices_digests_user_digest_popular', 0));
				$this->config->set('phpbbservices_digests_user_digest_popularity_size', $this->request->variable('phpbbservices_digests_user_digest_popularity_size', 0));
				$this->config->set('phpbbservices_digests_user_digest_registration', $this->request->variable('phpbbservices_digests_user_digest_registration', 0));
				$this->config->set('phpbbservices_digests_user_digest_remove_foes', $this->request->variable('phpbbservices_digests_user_digest_remove_foes', 0));
				$this->config->set('phpbbservices_digests_user_digest_reset_lastvisit', $this->request->variable('phpbbservices_digests_user_digest_reset_lastvisit', 0));
				$this->config->set('phpbbservices_digests_user_digest_send_hour_gmt', $this->request->variable('phpbbservices_digests_user_digest_send_hour_gmt', -1));
				$this->config->set('phpbbservices_digests_user_digest_send_on_no_posts', $this->request->variable('phpbbservices_digests_user_digest_send_on_no_posts', 0));
				$this->config->set('phpbbservices_digests_user_digest_show_mine', $this->request->variable('phpbbservices_digests_user_digest_show_mine', 0));
				$this->config->set('phpbbservices_digests_user_digest_show_pms', $this->request->variable('phpbbservices_digests_user_digest_show_pms', 0));
				$this->config->set('phpbbservices_digests_user_digest_sortby', $this->request->variable('phpbbservices_digests_user_digest_sortby', constants::DIGESTS_SORTBY_BOARD));
				$this->config->set('phpbbservices_digests_user_digest_toc', $this->request->variable('phpbbservices_digests_user_digest_toc', 0));
				$this->config->set('phpbbservices_digests_user_digest_type', $this->request->variable('phpbbservices_digests_user_digest_type', constants::DIGESTS_DAILY_VALUE));
			}

			if ($mode === 'digests_edit_subscribers')
			{

				// The "selected" input control indicates whether to do mass actions or not.
				$selected = $this->request->variable('selected', 'i', true);
				$change_details = $selected == 'i';
				$unsubscribe = $selected == 'n';
				$subscribe_defaults = $selected == 'd';

				unset($sql_ary, $sql_ary2, $requests_vars);

				// Get the entire POST request variables as an array for parsing. Only fields for subscribers requiring change should be in
				// the request variables.
				$request_vars = $this->request->get_super_global(\phpbb\request\request_interface::POST);

				// Sort the request variables so we process one can user at a time
				ksort($request_vars);

				// Set some flags
				$current_user_id = NULL;
				$user_fields_found = 0;

				foreach ($request_vars as $name => $value)
				{

					// We only care if the request variable starts with "user-".
					if (substr($name,0,5) === 'user-')
					{

						$user_fields_found++;

						// Parse for the user_id, which is embedded in the form field name. Format is user-99-column_name where 99
						// is the user id. The mark_all checkbox is the form field user-99.
						$delimiter_pos = strpos($name, '-', 5);
						if ($delimiter_pos === false)
						{
							// This is the mark_all checkbox for a given user
							$delimiter_pos = strlen($name);
						}

						$user_id = substr($name, 5, $delimiter_pos - 5);
						$column_name_fragment = substr($name,$delimiter_pos + 1);

						if ($current_user_id === NULL)
						{
							$current_user_id = $user_id;
						}

						if ($current_user_id !== $user_id)
						{

							// Save this subscriber's digest settings
							if ($unsubscribe)
							{
								// Remove digest subscription explicitly; old settings are retained in case user resubscribes
								$sql = 'UPDATE ' . USERS_TABLE . "
										SET user_digest_type = '" . constants::DIGESTS_NONE_VALUE . "'
										WHERE user_id = " . (int) $current_user_id;
								$this->db->sql_query($sql);
							}
							else if ($subscribe_defaults)	// Subscribe user with digest defaults
							{
								$sql_ary = $this->create_digests_default_sql();

								$sql = 'UPDATE ' . USERS_TABLE . ' 
										SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
										WHERE user_id = ' . (int) $current_user_id;

								$this->db->sql_query($sql);
							}
							else if (isset($sql_ary) && count($sql_ary) > 0)	// Change individual settings on a per user basis, $change_details == true
							{
								$sql = 'UPDATE ' . USERS_TABLE . ' 
										SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
										WHERE user_id = ' . (int) $current_user_id;
								$this->db->sql_query($sql);
							}

							// If there are any individual forum subscriptions for this user, remove the old ones.
							$sql = 'DELETE FROM ' . $this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
									WHERE user_id = ' . (int) $current_user_id;
							$this->db->sql_query($sql);

							// Now save the individual forum subscriptions, if any
							if ($change_details && isset($sql_ary2) && count($sql_ary2) > 0)
							{
								$this->db->sql_multi_insert($this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE, $sql_ary2);
							}

							// Also want to save some information to an array to be used for sending emails to affected users.
							if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
							{
								$digest_notify_list[] = $current_user_id;
							}

							// We need to set/reset these variables so we can detect if individual forum subscriptions will need to be processed.
							$current_user_id = $user_id;
							unset($sql_ary, $sql_ary2);

						}

						if ($change_details)
						{
							switch ($column_name_fragment)
							{
								case 'digest_type':
									$sql_ary['user_digest_type'] = $value;
								break;

								case 'style':
									$sql_ary['user_digest_format'] = $value;
								break;

								case 'send_hour':
									$sql_ary['user_digest_send_hour_gmt'] = $value;
								break;

								case 'filter_type':
									$sql_ary['user_digest_filter_type'] = $value;
								break;

								case 'max_posts':
									$sql_ary['user_digest_max_posts'] = $value;
								break;

								case 'min_words':
									$sql_ary['user_digest_min_words'] = $value;
								break;

								case 'new_posts_only':
									$sql_ary['user_digest_new_posts_only'] = $value;
								break;

								case 'show_mine':
									$sql_ary['user_digest_show_mine'] = ($value == '0') ? '1' : '0';
								break;

								case 'filter_foes':
									$sql_ary['user_digest_remove_foes'] = $value;
								break;

								case 'pms':
									$sql_ary['user_digest_show_pms'] = $value;
								break;

								case 'mark_read':
									$sql_ary['user_digest_pm_mark_read'] = $value;
								break;

								case 'sortby':
									$sql_ary['user_digest_sortby'] = $value;
								break;

								case 'max_display_words':
									$sql_ary['user_digest_max_display_words'] = $value;
								break;

								case 'no_post_text':
									$sql_ary['user_digest_no_post_text'] = $value;
								break;

								case 'send_on_no_posts':
									$sql_ary['user_digest_send_on_no_posts'] = $value;
								break;

								case 'lastvisit':
									$sql_ary['user_digest_reset_lastvisit'] = $value;
								break;

								case 'attachments':
									$sql_ary['user_digest_attachments'] = $value;
								break;

								case 'blockimages':
									$sql_ary['user_digest_block_images'] = $value;
								break;

								case 'toc':
									$sql_ary['user_digest_toc'] = $value;
								break;

								default;
								break;
							}

							if ($column_name_fragment === 'forums')
							{
								// There are some individual user forum subscriptions.  We should save them, but only if the
								// all forums checkbox is not set AND the user should not get posts for bookmarked topics only.

								// We need to get these variables so we can detect if individual forum subscriptions will need to be processed.
								$var = 'user-' . $current_user_id . '-all_forums';
								$all_forums = $this->request->variable($var, '', true);
								$var = 'user-' . $current_user_id . '-filter_type';
								$filter_type = $this->request->variable($var, '', true);

								if (($all_forums !== 'on') && (trim($filter_type) !== constants::DIGESTS_BOOKMARKS))
								{
									// $value is an array like ( [0] => 3-2 [1] => 3-13 )
									foreach ($value as $subscript => $user_forum)
									{
										// To decode $user_forum, the user_id is to the left of the -, the forum_id is to its right
										$delimiter_pos = strpos($user_forum, '-');
										$subscriber_forum_id = (int) substr($user_forum, $delimiter_pos + 1);

										// Write this forum subscription
										$sql_ary2[] = array(
											'user_id'		=> $current_user_id,
											'forum_id'		=> $subscriber_forum_id);
									}
								}
							}
						}

					} // $request_vars variable is named user-*

				} // foreach

				// Process last user

				// Save this subscriber's digest settings
				if ($unsubscribe)
				{
					// Remove digest subscription explicitly; old settings are retained in case user resubscribes
					$sql = 'UPDATE ' . USERS_TABLE . "
							SET user_digest_type = '" . constants::DIGESTS_NONE_VALUE . "'
							WHERE user_id = " . (int) $current_user_id;
					$this->db->sql_query($sql);
				}
				else if ($subscribe_defaults)	// Subscribe user with digest defaults
				{
					$sql_ary = $this->create_digests_default_sql();

					$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . (int) $current_user_id;
					$this->db->sql_query($sql);
				}
				else if (isset($sql_ary) && count($sql_ary) > 0)	// Change individual settings on a per user basic, $change_details == true
				{
					$sql = 'UPDATE ' . USERS_TABLE . ' 
							SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . (int) $current_user_id;
					$this->db->sql_query($sql);
				}

				// If there are any individual forum subscriptions for this user, remove the old ones.
				$sql = 'DELETE FROM ' . $this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
						WHERE user_id = ' . (int) $current_user_id;
				$this->db->sql_query($sql);

				// Now save the individual forum subscriptions, if any
				if ($change_details && isset($sql_ary2) && count($sql_ary2) > 0)
				{
					$this->db->sql_multi_insert($this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE, $sql_ary2);
				}

				// Also want to save some information to an array to be used for sending emails to affected users.
				$digest_notify_list = array();
				if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
				{
					$digest_notify_list[] = $current_user_id;
				}

				// Notify users whose subscriptions were changed
				if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
				{
					$this->helper->notify_subscribers($digest_notify_list, 'digests_subscription_edited');
				}

				if ($user_fields_found > 0)
				{
					$message = $this->language->lang('CONFIG_UPDATED');
				}
				else
				{
					$message = $this->language->lang('DIGESTS_NO_USERS_SELECTED');
				}

			}

			if ($mode === 'digests_balance_load')
			{

				// Get the balance type: all, daily, weekly or monthly. Only these digest types will be balanced.
				$balance = $this->request->variable('balance', constants::DIGESTS_ALL);
				$balance_sql = ($balance == constants::DIGESTS_ALL) ?
					"user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "'" :
					"user_digest_type = '" . $this->db->sql_escape($balance) . "'";

				// Get the hours to balance. If -1 is among those hours returned, all hours are wanted. Others that may be selected are ignored.
				$for_hours = $this->request->variable('for_hrs', array('' => 0));
				$for_hours_sql = (in_array(-1, $for_hours)) ? '' : ' AND ' . $this->db->sql_in_set('user_digest_send_hour_gmt', $for_hours);

				// Get the hours to apply the balance to. If -1 is among those hours returned, all hours are candidates for being used.
				$to_hours = $this->request->variable('to_hrs', array('' => 0));

				// Determine the average number of subscribers per hour. We need to assume at least one subscriber per hour to avoid
				// resetting user's preferred digest time unnecessarily. If the average is 3 per hour, the first 3 already subscribed
				// will not have their digest arrival time changed.
				$avg_per_hour = $this->average_subscribers_per_hour();
				$avg_subscribers_per_hour = max($avg_per_hour, 1);

				// Get oversubscribed hours, place in an array
				$sql_array = array(
					'SELECT'	=> 'user_digest_send_hour_gmt AS hour, COUNT(user_id) AS hour_count',

					'FROM'		=> array(
						USERS_TABLE		=> 'u',
					),

					'WHERE'		=> $balance_sql . $for_hours_sql . ' AND ' . $this->db->sql_in_set('user_type', array(USER_NORMAL, USER_FOUNDER)),

					'GROUP_BY'	=> 'user_digest_send_hour_gmt',

					'HAVING'	=> 'COUNT(user_digest_send_hour_gmt) > ' . (int) $avg_subscribers_per_hour,

					'ORDER_BY'	=> '1',
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query($sql);
				$rowset = $this->db->sql_fetchrowset($result);
				$oversubscribed_hours = array();
				foreach ($rowset as $row)
				{
					$oversubscribed_hours[] = (int) $row['hour'];
				}
				$this->db->sql_freeresult($result);

				// Get a list of subscribers whose hour to get digest should be changed because they exceed the average number of subscribers
				// allowed in that hour. We will ignore the first $oversubscribed_hours subscribers.

				$rebalanced = 0;
				$digest_notify_list = array();

				if (count($oversubscribed_hours) > 0)
				{

					$sql_array = array(
						'SELECT'	=> 'user_digest_send_hour_gmt, user_id, username, user_email, user_lang',

						'FROM'		=> array(
							USERS_TABLE	=> 'u',
						),

						'WHERE'		=> $balance_sql . $for_hours_sql . ' AND ' . $this->db->sql_in_set('user_type', array(USER_NORMAL, USER_FOUNDER)) . '
						AND ' . $this->db->sql_in_set('user_digest_send_hour_gmt', $oversubscribed_hours),

						'ORDER_BY'	=> '1, 2',
					);

					$sql = $this->db->sql_build_query('SELECT', $sql_array);

					$result = $this->db->sql_query_limit($sql, 100000, $avg_subscribers_per_hour - 1); // Result sets start with array indexed at zero
					$rowset = $this->db->sql_fetchrowset($result);

					$current_hour = -1;
					$counted_for_this_hour = 0;

					// Finally, change the digest send hour for these subscribers to a random hour between 0 and 23 (if all from hours specified)
					// or a random hour from those the user wants the hour to be chose from (if some hours specified).
					foreach ($rowset as $row)
					{

						if ($current_hour !== $row['user_digest_send_hour_gmt'])
						{
							$current_hour = $row['user_digest_send_hour_gmt'];
							$counted_for_this_hour = 0;
						}
						$counted_for_this_hour++;

						if ($counted_for_this_hour > $avg_subscribers_per_hour)
						{

							// Assign a new hour for this subscriber to receive the digest.
							if (in_array(-1, $to_hours))
							{
								$new_hour = rand(0, 23);	// No constraint on the hour to assign
							}
							else
							{
								$new_hour = $to_hours[rand(0, count($to_hours) - 1)]; // Assign only to an hour that the user has specified.
							}

							$sql_ary = array(
								'user_digest_send_hour_gmt'		=> $new_hour,
							);

							$sql2 = 'UPDATE ' . USERS_TABLE . '
							SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . (int)  $row['user_id'];

							$this->db->sql_query($sql2);

							if ($row['user_digest_send_hour_gmt'] != $new_hour)
							{
								$digest_notify_list[] = $row['user_id'];
							}

							$rebalanced++;

						}

					}

					$this->db->sql_freeresult($result);

					// Notify users whose subscriptions were changed
					if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
					{
						$this->helper->notify_subscribers($digest_notify_list, 'digests_subscription_edited');
					}

				}

				$message = $this->language->lang('DIGESTS_REBALANCED', $rebalanced);

			}

			if ($mode === 'digests_mass_subscribe_unsubscribe')
			{

				// Save the form fields
				$this->config->set('phpbbservices_digests_enable_subscribe_unsubscribe', $this->request->variable('phpbbservices_digests_enable_subscribe_unsubscribe', 0));
				$this->config->set('phpbbservices_digests_include_admins', $this->request->variable('phpbbservices_digests_include_admins', 0));
				$this->config->set('phpbbservices_digests_subscribe_all', $this->request->variable('phpbbservices_digests_subscribe_all', 0));

				// Did the admin explicitly request a mass subscription or unsubscription action?
				if ($this->config['phpbbservices_digests_enable_subscribe_unsubscribe'])
				{

					// Determine which user types are to be updated
					$user_types = array(USER_NORMAL);
					if ($this->config['phpbbservices_digests_include_admins'])
					{
						$user_types[] = USER_FOUNDER;
					}

					// If doing a mass subscription, we don't want to mess up digest subscriptions already in place, so we need to get just those users unsubscribed.
					// If doing a mass unsubscribe, all qualified subscriptions are removed. Note however that except for the digest type, all other settings
					// are retained.
					$sql_qualifier = ($this->config['phpbbservices_digests_subscribe_all']) ? " AND user_digest_type = '" . constants::DIGESTS_NONE_VALUE . "'" : " AND user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "'";

					$digest_notify_list = array();

					// Get a list of users to be mass subscribed or unsubscribed. This will be used to send them email
					// notifications if this feature is enabled, but also to update the database.

					$sql_array = array(
						'SELECT'	=> 'user_id',

						'FROM'		=> array(
							USERS_TABLE	=> 'u',
						),

						'WHERE'		=> $this->db->sql_in_set('user_type', $user_types) . $sql_qualifier,
					);

					$sql = $this->db->sql_build_query('SELECT', $sql_array);

					$result = $this->db->sql_query($sql);
					$rowset = $this->db->sql_fetchrowset($result);

					foreach ($rowset as $row)
					{
						$digest_notify_list[] = $row['user_id'];
					}

					$this->db->sql_freeresult($result); // Query be gone!

					// Set columns in users table to be updated
					if ($this->config['phpbbservices_digests_subscribe_all'])	// subscribe
					{
						$sql_ary = array(
							'user_digest_type' 				=> $this->config['phpbbservices_digests_user_digest_type'],
							'user_digest_format' 			=> $this->config['phpbbservices_digests_user_digest_format'],
							'user_digest_show_mine' 		=> ($this->config['phpbbservices_digests_user_digest_show_mine'] == 1) ? 0 : 1,
							'user_digest_send_on_no_posts' 	=> $this->config['phpbbservices_digests_user_digest_send_on_no_posts'],
							'user_digest_show_pms' 			=> $this->config['phpbbservices_digests_user_digest_show_pms'],
							'user_digest_max_posts' 		=> $this->config['phpbbservices_digests_user_digest_max_posts'],
							'user_digest_min_words' 		=> $this->config['phpbbservices_digests_user_digest_min_words'],
							'user_digest_remove_foes' 		=> $this->config['phpbbservices_digests_user_digest_remove_foes'],
							'user_digest_sortby' 			=> $this->config['phpbbservices_digests_user_digest_sortby'],
							'user_digest_max_display_words' => ($this->config['phpbbservices_digests_user_digest_max_display_words'] == -1) ? 0 : $this->config['phpbbservices_digests_user_digest_max_display_words'],
							'user_digest_reset_lastvisit' 	=> $this->config['phpbbservices_digests_user_digest_reset_lastvisit'],
							'user_digest_filter_type' 		=> $this->config['phpbbservices_digests_user_digest_filter_type'],
							'user_digest_pm_mark_read' 		=> $this->config['phpbbservices_digests_user_digest_pm_mark_read'],
							'user_digest_new_posts_only' 	=> $this->config['phpbbservices_digests_user_digest_new_posts_only'],
							'user_digest_no_post_text'		=> ($this->config['phpbbservices_digests_user_digest_max_display_words'] == 0) ? 1 : 0,
							'user_digest_attachments' 		=> $this->config['phpbbservices_digests_user_digest_attachments'],
							'user_digest_block_images'		=> $this->config['phpbbservices_digests_user_digest_block_images'],
							'user_digest_toc'				=> $this->config['phpbbservices_digests_user_digest_toc'],
						);
					}
					else	// unsubscribe
					{
						$sql_ary = array(
							'user_digest_type' 				=> constants::DIGESTS_NONE_VALUE,
						);
					}

					foreach ($digest_notify_list as $user_id)
					{
						if ($this->config['phpbbservices_digests_subscribe_all'])
						{
							// Add the hour of the subscription to the end of $sql_ary, using a random hour if that setting exists
							$sql_ary['user_digest_send_hour_gmt'] = ($this->config['phpbbservices_digests_user_digest_send_hour_gmt'] == -1) ? rand(0,23) : $this->config['phpbbservices_digests_user_digest_send_hour_gmt'];
						}

						$sql = 'UPDATE ' . USERS_TABLE . ' 
						SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE ' . $this->db->sql_in_set('user_id', $user_id);

						$this->db->sql_query($sql);

						if ($this->config['phpbbservices_digests_subscribe_all'])
						{
							array_pop($sql_ary);	// Remove $sql_ary['user_digest_send_hour_gmt'] since it may change for each subscriber
						}

					}

					// Notify users or subscription or unsubscription if directed
					if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
					{
						$this->helper->notify_subscribers($digest_notify_list);
					}

					if ($this->config['phpbbservices_digests_subscribe_all'])
					{
						$message = $this->language->lang('DIGESTS_ALL_SUBSCRIBED', count($digest_notify_list));
					}
					else
					{
						$message = $this->language->lang('DIGESTS_ALL_UNSUBSCRIBED', count($digest_notify_list));
					}

				}
				else
				{
					// show no update message
					$message = $this->language->lang('DIGESTS_NO_MASS_ACTION');
				}

			}

			if ($mode === 'digests_reset_cron_run_time')
			{
				// Save the setting
				$this->config->set('phpbbservices_digests_reset_cron_run_time', $this->request->variable('phpbbservices_digests_reset_cron_run_time', 0));

				// This allows the digests to go out next time cron.php is run.
				$this->config->set('phpbbservices_digests_cron_task_last_gc', 0);

				// This resets all the date/time stamps for when a digest was last sent to a user.
				$sql_ary = array('user_digest_last_sent' => 0);

				$sql = 'UPDATE ' . USERS_TABLE . ' 
				SET ' . $this->db->sql_build_array('UPDATE', $sql_ary);
				$this->db->sql_query($sql);

				if ($this->request->variable('phpbbservices_digests_reset_cron_run_time', 0) == 1)
				{
					$message = $this->language->lang('DIGESTS_MAILER_RESET');
				}
			}

			if ($mode === 'digests_test')
			{

				define('IN_DIGESTS_TEST', true);
				$proceed = true;
				$digests_storage_path = $this->phpbb_root_path . 'store/phpbbservices/digests';

				// Store the form field settings
				$this->config->set('phpbbservices_digests_test', $this->request->variable('phpbbservices_digests_test', 0));
				$this->config->set('phpbbservices_digests_test_clear_spool', $this->request->variable('phpbbservices_digests_test_clear_spool', 0));
				$this->config->set('phpbbservices_digests_test_date_hour', $this->request->variable('phpbbservices_digests_test_date_hour', ''));
				$this->config->set('phpbbservices_digests_test_send_to_admin', $this->request->variable('phpbbservices_digests_test_send_to_admin', 0));
				$this->config->set('phpbbservices_digests_test_spool', $this->request->variable('phpbbservices_digests_test_spool', 0));
				$this->config->set('phpbbservices_digests_test_email_address', $this->request->variable('phpbbservices_digests_test_email_address', ''));

				if (!$this->config['phpbbservices_digests_test'] && !$this->config['phpbbservices_digests_test_clear_spool'])
				{
					$message = $this->language->lang('DIGESTS_MAILER_NOT_RUN');
					$proceed = false;
				}

				// Create the store/phpbbservices/digests folder. It should exist already.
				if (!$this->helper->make_directories())
				{
					$message_type = E_USER_WARNING;
					$message = sprintf($this->language->lang('DIGESTS_CREATE_DIRECTORY_ERROR'), $digests_storage_path);
					$proceed = false;
				}

				if ($proceed && $this->config['phpbbservices_digests_test_clear_spool'])
				{

					// Clear the digests store folder of .txt and .html files, if so instructed

					$all_cleared = true;

					foreach (new \DirectoryIterator($digests_storage_path) as $file_info)
					{
						$file_name = $file_info->getFilename();
						// Exclude dot files, hidden files and non "real" files, and real files if they don't have the .html or .txt suffix
						if ((substr($file_name, 0, 1) !== '.') && $file_info->isFile() && ($file_info->getExtension() == 'html' || $file_info->getExtension() == 'txt'))
						{
							$deleted = unlink($digests_storage_path . '/' . $file_name); // delete file
							if (!$deleted)
							{
								$all_cleared = false;
							}
						}
					}

					if ($this->config['phpbbservices_digests_enable_log'])
					{
						if ($all_cleared)
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_CACHE_CLEARED');
						}
						else
						{
							$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_CLEAR_SPOOL_ERROR');
						}
					}

					if (!$all_cleared)
					{
						$message_type = E_USER_WARNING;
						$message = $this->language->lang('DIGESTS_RUN_TEST_CLEAR_SPOOL_ERROR');
						$proceed = false;
					}

				}

				if ($proceed && (trim($this->config['phpbbservices_digests_test_date_hour']) !== ''))
				{

					// Make sure run date is valid, if a run date was requested.
					$good_date = $this->helper->validate_iso_date($this->config['phpbbservices_digests_test_date_hour']);
					if (!$good_date)
					{
						$message_type = E_USER_WARNING;
						$message = $this->language->lang('DIGESTS_ILLOGICAL_DATE');
						$proceed = false;
					}

				}

				// Get ready to manually mail digests
				if ($proceed && $this->config['phpbbservices_digests_test'])
				{

					// Call the mailer's run method. The logic for sending a digest is embedded in this method, which is normally run as a cron task.
					$success = $this->mailer->run();

					if (!$success)
					{
						$message_type = E_USER_WARNING;
						$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_DIGESTS_MAILER_RAN_WITH_ERROR');
						$message = $this->language->lang('DIGESTS_MAILER_RAN_WITH_ERROR');
					}
					else if ($this->config['phpbbservices_digests_test_spool'])
					{
						$message = $this->language->lang('DIGESTS_MAILER_SPOOLED');
					}
					else
					{
						$message = $this->language->lang('DIGESTS_MAILER_RAN_SUCCESSFULLY');
					}

				}

			}

			if (!isset($message_type))
			{
				$message_type = E_USER_NOTICE;
			}
			
			if (!isset($message))
			{
				if (count($error))
				{
					$message = implode('<br>', $error);
				}
				else
				{
					$message = $this->language->lang('CONFIG_UPDATED');
				}
			}

			if ($mode !== 'digests_test')
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_' . strtoupper($mode));
			}

			trigger_error($message . adm_back_link($u_action), $message_type);

		}

	}
	
	function average_subscribers_per_hour()
	{

		// This function returns the average number of digest subscribers per hour.

		$sql_array = array(
			'SELECT'	=> 'COUNT(user_id) AS digests_count',

			'FROM'		=> array(
				USERS_TABLE		=> 'u',
			),

			'WHERE'		=> "user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' 
					AND " . $this->db->sql_in_set('user_type', array(USER_NORMAL, USER_FOUNDER)),
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		$avg_subscribers_per_hour = round((float) $row['digests_count']/24);
		$this->db->sql_freeresult($result);

		return $avg_subscribers_per_hour;

	}

	function get_subscribers_for_hour ($hour, $offset_from_utc)
	{

		// Returns an array of subscribers for a given hour, keyed by digest type

		$subscribers = array();

		$hour_utc = $this->helper->check_send_hour($hour - $offset_from_utc);

		$sql_array = array(
			'SELECT'	=> 'username, user_digest_type',

			'FROM'		=> array(
				USERS_TABLE		=> 'u',
			),

			'WHERE' 	=> $this->db->sql_in_set('user_digest_send_hour_gmt', array($hour_utc)),

			'ORDER_BY'	=> 'username'
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);

		foreach ($rowset as $row)
		{
			$subscribers[][$row['user_digest_type']] = $row['username'];
		}

		return $subscribers;

	}

	function dow_select ()
	{
		// Returns a string containing HTML that gives the days of the week as <option> tags inside a <select> tag
		// with the day of the week used for sending weekly digests selected.

		$dow_options = '';
		$index = 0;
		$weekdays = explode(',', $this->language->lang('DIGESTS_WEEKDAY'));
		foreach ($weekdays as $key => $value)
		{
			$selected = ($index == $this->config['phpbbservices_digests_weekly_digest_day']) ? ' selected="selected"' : '';
			$dow_options .= '<option value="' . $index . '"' . $selected . '>' . $value . '</option>';
			$index++;
		}

		return $dow_options;
	}

	function digest_type_select ()
	{
		// Returns a string containing HTML, basically a set of option tags so the admin can pick daily, weekly
		// or monthly digests as the default digest type.

		$selected = ($this->config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_DAILY_VALUE) ? ' selected="selected"' : '';
		$digest_types = '<option value="' . constants::DIGESTS_DAILY_VALUE . '"' . $selected. '>' . $this->language->lang('DIGESTS_DAILY') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_WEEKLY_VALUE) ? ' selected="selected"' : '';
		$digest_types .= '<option value="' . constants::DIGESTS_WEEKLY_VALUE . '"' . $selected . '>' . $this->language->lang('DIGESTS_WEEKLY') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_MONTHLY_VALUE) ? ' selected="selected"' : '';
		$digest_types .= '<option value="' . constants::DIGESTS_MONTHLY_VALUE . '"' . $selected . '>' . $this->language->lang('DIGESTS_MONTHLY') . '</option>';

		return $digest_types;
	}

	function digest_style_select ()
	{
		// Returns a string containing HTML, basically a set of option tags so the admin can pick the default digest format.

		$selected = ($this->config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_HTML_VALUE) ? ' selected="selected"' : '';
		$digest_styles = '<option value="' . constants::DIGESTS_HTML_VALUE . '"' . $selected . '>' . $this->language->lang('DIGESTS_FORMAT_HTML') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_HTML_CLASSIC_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_HTML_CLASSIC_VALUE . '"' . $selected . '>' . $this->language->lang('DIGESTS_FORMAT_HTML_CLASSIC') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_PLAIN_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_PLAIN_VALUE . '"' . $selected . '>' . $this->language->lang('DIGESTS_FORMAT_PLAIN') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_PLAIN_CLASSIC_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_PLAIN_CLASSIC_VALUE . '"' . $selected . '>' . $this->language->lang('DIGESTS_FORMAT_PLAIN_CLASSIC') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_TEXT_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_TEXT_VALUE . '"' . $selected  . '>' . $this->language->lang('DIGESTS_FORMAT_TEXT') . '</option>';

		return $digest_styles;
	}

	function digest_send_hour_utc ()
	{
		// Returns a set of option tags for all the hours of the day selecting a send hour for digests including the default
		// to assign a random hour. The values should be interpreted as UTC hour.

		$digest_send_hour_utc = '';

		// Populate the Hour Sent select control
		for($i=-1;$i<24;$i++)
		{
			$selected = ($i == $this->config['phpbbservices_digests_user_digest_send_hour_gmt']) ? ' selected="selected"' : '';
			$display_text = ($i == -1) ? $this->language->lang('DIGESTS_RANDOM_HOUR') : $i;
			$digest_send_hour_utc .= '<option value="' . $i . '"' . $selected . '>' . $display_text . '</option>';
		}
		return $digest_send_hour_utc;
	}

	function digest_filter_type ()
	{
		// Returns a set of option tags so the default filter type can be set

		$selected = ($this->config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_ALL) ? ' selected="selected"' : '';
		$digest_filter_types = '<option value="' . constants::DIGESTS_ALL . '"' . $selected . '>' . $this->language->lang('DIGESTS_ALL_FORUMS') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_FIRST) ? ' selected="selected"' : '';
		$digest_filter_types .= '<option value="' . constants::DIGESTS_FIRST . '"' . $selected . '>' . $this->language->lang('DIGESTS_POSTS_TYPE_FIRST') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS) ? ' selected="selected"' : '';
		$digest_filter_types .= '<option value="' . constants::DIGESTS_BOOKMARKS . '"' . $selected. '>' . $this->language->lang('DIGESTS_USE_BOOKMARKS') . '</option>';

		return $digest_filter_types;
	}

	function digest_post_sort_order ()
	{
		// Returns a set of option tags so teh default digest sorting in digests can be set

		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_BOARD) ? ' selected="selected"' : '';
		$digest_sort_order = '<option value="' . constants::DIGESTS_SORTBY_BOARD . '"' . $selected . '>' . $this->language->lang('DIGESTS_SORT_USER_ORDER') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD . '"' . $selected . '>' . $this->language->lang('DIGESTS_SORT_FORUM_TOPIC') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD_DESC . '"' . $selected . '>' . $this->language->lang('DIGESTS_SORT_FORUM_TOPIC_DESC') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE . '"' . $selected . '>' . $this->language->lang('DIGESTS_SORT_POST_DATE') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE_DESC . '"' . $selected . '>' . $this->language->lang('DIGESTS_SORT_POST_DATE_DESC') . '</option>';

		return $digest_sort_order;
	}

	function create_digests_default_sql ()
	{

		$sql_ary['user_digest_type'] 				= $this->config['phpbbservices_digests_user_digest_type'];
		$sql_ary['user_digest_format'] 				= $this->config['phpbbservices_digests_user_digest_format'];
		$sql_ary['user_digest_show_mine'] 			= ($this->config['phpbbservices_digests_user_digest_show_mine'] == 1) ? 0 : 1;
		$sql_ary['user_digest_send_on_no_posts'] 	= $this->config['phpbbservices_digests_user_digest_send_on_no_posts'];
		$sql_ary['user_digest_send_hour_gmt'] 		= ($this->config['phpbbservices_digests_user_digest_send_hour_gmt'] == -1) ? rand(0,23) : $this->config['phpbbservices_digests_user_digest_send_hour_gmt'];
		$sql_ary['user_digest_show_pms'] 			= $this->config['phpbbservices_digests_user_digest_show_pms'];
		$sql_ary['user_digest_max_posts'] 			= $this->config['phpbbservices_digests_user_digest_max_posts'];
		$sql_ary['user_digest_min_words'] 			= $this->config['phpbbservices_digests_user_digest_min_words'];
		$sql_ary['user_digest_remove_foes'] 		= $this->config['phpbbservices_digests_user_digest_remove_foes'];
		$sql_ary['user_digest_sortby'] 				= $this->config['phpbbservices_digests_user_digest_sortby'];
		$sql_ary['user_digest_max_display_words'] 	= ($this->config['phpbbservices_digests_user_digest_max_display_words'] == -1) ? 0 : $this->config['phpbbservices_digests_user_digest_max_display_words'];
		$sql_ary['user_digest_reset_lastvisit'] 	= $this->config['phpbbservices_digests_user_digest_reset_lastvisit'];
		$sql_ary['user_digest_filter_type'] 		= $this->config['phpbbservices_digests_user_digest_filter_type'];
		$sql_ary['user_digest_pm_mark_read'] 		= $this->config['phpbbservices_digests_user_digest_pm_mark_read'];
		$sql_ary['user_digest_new_posts_only'] 		= $this->config['phpbbservices_digests_user_digest_new_posts_only'];
		$sql_ary['user_digest_no_post_text']		= ($this->config['phpbbservices_digests_user_digest_max_display_words'] == 0) ? 1 : 0;
		$sql_ary['user_digest_attachments'] 		= $this->config['phpbbservices_digests_user_digest_attachments'];
		$sql_ary['user_digest_block_images']		= $this->config['phpbbservices_digests_user_digest_block_images'];
		$sql_ary['user_digest_toc']					= $this->config['phpbbservices_digests_user_digest_toc'];

		return ($sql_ary);

	}

}
