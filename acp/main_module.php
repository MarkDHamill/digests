<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\acp;

use phpbbservices\digests\constants\constants;

class main_module
{

	private $auth;
	private $config;
	private $db;
	private $helper;
	private $language;
	private $pagination;
	private $phpbb_extension_manager;
	private $phpbb_log;
	private $phpbb_path_helper;
	private $phpbb_root_path;
	private $phpEx;
	private $request;
	private $table_prefix;
	private $template;
	private $user;

	function __construct()
	{
		global $phpbb_container;

		// Get global variables via containers to minimize security issues
		$this->phpbb_root_path = $phpbb_container->getParameter('core.root_path');
		$this->phpEx = $phpbb_container->getParameter('core.php_ext');
		$this->table_prefix = $phpbb_container->getParameter('core.table_prefix');

		// Encapsulate certain phpBB objects inside this class to minimize security issues
		$this->auth = $phpbb_container->get('auth');
		$this->config = $phpbb_container->get('config');
		$this->db = $phpbb_container->get('dbal.conn');
		$this->helper = $phpbb_container->get('phpbbservices.digests.common');
		$this->language = $phpbb_container->get('language');
		$this->pagination = $phpbb_container->get('pagination');
		$this->phpbb_extension_manager = $phpbb_container->get('ext.manager');
		$this->phpbb_log = $phpbb_container->get('log');
		$this->phpbb_path_helper = $phpbb_container->get('path_helper');
		$this->request = $phpbb_container->get('request');
		$this->template = $phpbb_container->get('template');
		$this->user = $phpbb_container->get('user');
	}

	function main($id, $mode)
	{

		$this->language->add_lang(array('acp/info_acp_common', 'acp/common'), 'phpbbservices/digests');

		$submit = (isset($_POST['submit'])) ? true : false;

		$form_key = 'phpbbservices/digests';
		add_form_key($form_key);
		$my_time_zone = (float) $this->helper->make_tz_offset($this->user->data['user_timezone']);

		/**
		*	Validation types are:
		*		string, int, bool,
		*		script_path (absolute path in url - beginning with / and no trailing slash),
		*		rpath (relative), rwpath (realtive, writable), path (relative path, but able to escape the root), wpath (writable)
		*/
		switch ($mode)
		{
			case 'digests_general':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_GENERAL_SETTINGS',
					'vars'	=> array(
						'legend1'								=> 'ACP_DIGESTS_GENERAL_SETTINGS',
						'phpbbservices_digests_enable_log'					=> array('lang' => 'DIGESTS_ENABLE_LOG',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_show_email'					=> array('lang' => 'DIGESTS_SHOW_EMAIL',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_enable_auto_subscriptions'	=> array('lang' => 'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_registration_field'			=> array('lang' => 'DIGESTS_REGISTRATION_FIELD',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_block_images'				=> array('lang' => 'DIGESTS_BLOCK_IMAGES',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_notify_on_admin_changes'		=> array('lang' => 'DIGESTS_NOTIFY_ON_ADMIN_CHANGES',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_weekly_digest_day'			=> array('lang' => 'DIGESTS_WEEKLY_DIGESTS_DAY',				'validate' => 'int:0:6',	'type' => 'select', 'method' => 'dow_select', 'explain' => true),
						'phpbbservices_digests_max_cron_hrs'				=> array('lang' => 'DIGESTS_MAX_CRON_HOURS',					'validate' => 'int:0:24',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_max_items'					=> array('lang' => 'DIGESTS_MAX_ITEMS',							'validate' => 'int:0',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_enable_custom_stylesheets'	=> array('lang' => 'DIGESTS_ENABLE_CUSTOM_STYLESHEET',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_custom_stylesheet_path'		=> array('lang' => 'DIGESTS_CUSTOM_STYLESHEET_PATH',			'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_from_email_address'			=> array('lang' => 'DIGESTS_FROM_EMAIL_ADDRESS',				'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_from_email_name'				=> array('lang' => 'DIGESTS_FROM_EMAIL_NAME',					'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_reply_to_email_address'		=> array('lang' => 'DIGESTS_REPLY_TO_EMAIL_ADDRESS',			'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_users_per_page'				=> array('lang' => 'DIGESTS_USERS_PER_PAGE',					'validate' => 'int:0',	'type' => 'text:4:4', 'explain' => true),
						'phpbbservices_digests_include_forums'				=> array('lang' => 'DIGESTS_INCLUDE_FORUMS',					'validate' => 'string',	'type' => 'text:15:255', 'explain' => true),
						'phpbbservices_digests_exclude_forums'				=> array('lang' => 'DIGESTS_EXCLUDE_FORUMS',					'validate' => 'string',	'type' => 'text:15:255', 'explain' => true),
						'phpbbservices_digests_strip_tags'					=> array('lang' => 'DIGESTS_STRIP_TAGS',						'validate' => 'string',	'type' => 'textarea:3:85', 'explain' => true),
						'phpbbservices_digests_show_forum_path'				=> array('lang' => 'DIGESTS_SHOW_FORUM_PATH',					'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_lowercase_digest_type'		=> array('lang' => 'DIGESTS_LOWERCASE_DIGEST_TYPE',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
					)
				);
			break;
				
			case 'digests_user_defaults':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_USER_DEFAULT_SETTINGS',
					'vars'	=> array(						
						'legend1'											=> 'ACP_DIGESTS_USER_DEFAULT_SETTINGS',
						'phpbbservices_digests_user_digest_registration'	=> array('lang' => 'DIGESTS_USER_DIGESTS_REGISTRATION',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_type'			=> array('lang' => 'DIGESTS_FREQUENCY',					'validate' => 'string',	'type' => 'select', 'method' => 'digest_type_select', 'explain' => true),
						'phpbbservices_digests_user_digest_format'			=> array('lang' => 'DIGESTS_FORMAT_STYLING',			'validate' => 'string',	'type' => 'select', 'method' => 'digest_style_select', 'explain' => true),
						'phpbbservices_digests_user_digest_send_hour_gmt'	=> array('lang' => 'DIGESTS_SEND_HOUR',					'validate' => 'int:-1:23',	'type' => 'select', 'method' => 'digest_send_hour_utc', 'explain' => true),
						'phpbbservices_digests_user_digest_filter_type'		=> array('lang' => 'DIGESTS_FILTER_TYPE',				'validate' => 'string',	'type' => 'select', 'method' => 'digest_filter_type', 'explain' => false),
						'phpbbservices_digests_user_check_all_forums'		=> array('lang' => 'DIGESTS_USER_DIGESTS_CHECK_ALL_FORUMS',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_max_posts'		=> array('lang' => 'DIGESTS_COUNT_LIMIT',				'validate' => 'int:0',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_user_digest_min_words'		=> array('lang' => 'DIGESTS_MIN_SIZE',					'validate' => 'int:0',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_user_digest_new_posts_only'	=> array('lang' => 'DIGESTS_NEW_POSTS_ONLY',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_user_digest_show_mine'		=> array('lang' => 'DIGESTS_REMOVE_YOURS',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_remove_foes'		=> array('lang' => 'DIGESTS_FILTER_FOES',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_show_pms'		=> array('lang' => 'DIGESTS_SHOW_PMS',					'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_pm_mark_read'	=> array('lang' => 'DIGESTS_USER_DIGESTS_PM_MARK_READ',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_sortby'			=> array('lang' => 'DIGESTS_SORT_BY',					'validate' => 'string',	'type' => 'select', 'method' => 'digest_post_sort_order', 'explain' 	=> true),
						'phpbbservices_digests_user_digest_max_display_words'	=> array('lang' => 'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS',		'validate' => 'int:-1',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_user_digest_send_on_no_posts'	=> array('lang' => 'DIGESTS_SEND_ON_NO_POSTS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_reset_lastvisit'	=> array('lang' => 'DIGESTS_LASTVISIT_RESET',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_user_digest_attachments'		=> array('lang' => 'DIGESTS_SHOW_ATTACHMENTS',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_user_digest_block_images'	=> array('lang' => 'DIGESTS_BLOCK_IMAGES',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_user_digest_toc'				=> array('lang' => 'DIGESTS_TOC',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
					)
				);
			break;
				
			case 'digests_edit_subscribers':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_EDIT_SUBSCRIBERS',
					'vars'	=> array(
						'legend1'											=> 'ACP_DIGESTS_EDIT_SUBSCRIBERS',
					)
				);

				// Grab some URL parameters
				$member = $this->request->variable('member', '', true);
				$start = $this->request->variable('start', 0);
				$subscribe = $this->request->variable('subscribe', 'a', true);
				$sortby = $this->request->variable('sortby', 'u', true);
				$sortorder = $this->request->variable('sortorder', 'a', true);

				// Translate time zone information
				$this->template->assign_vars(array(
					'L_DIGESTS_HOUR_SENT'               			=> $this->language->lang('DIGESTS_HOUR_SENT', $my_time_zone),
					'L_DIGESTS_BASED_ON'							=> $this->language->lang('DIGESTS_BASED_ON', $my_time_zone),
					'S_EDIT_SUBSCRIBERS'							=> true,
				));

				// Set up subscription filter				
				$all_selected = $stopped_subscribing = $subscribe_selected = $unsubscribe_selected = '';
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
				$ascending_selected = $descending_selected = '';
				switch ($sortorder)
				{
					case 'd':
						$order_by_sql = 'DESC';
						$descending_selected = ' selected="selected"';
					break;
					
					case 'a':
					default:
						$order_by_sql = 'ASC';
						$ascending_selected = ' selected="selected"';
					break;
				}
				
				// Set up member search SQL
				$match_any_chars = $this->db->get_any_char();
				$member_sql = ($member <> '') ? " username_clean " . $this->db->sql_like_expression($match_any_chars . utf8_case_fold_nfc($member) . $match_any_chars) . " AND " : '';

				// Get the total rows for pagination purposes
				$sql_array = array(
					'SELECT'	=> 'COUNT(user_id) AS total_users',
				
					'FROM'		=> array(
						USERS_TABLE		=> 'u',
					),
				
					'WHERE'		=> "$subscribe_sql $member_sql user_type <> " . USER_IGNORE,
				);
				
				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query($sql);

				// Get the total users, this is a single row, single field.
				$total_users = $this->db->sql_fetchfield('total_users');
				
				// Free the result
				$this->db->sql_freeresult($result);
				
				// Create pagination logic
				$this->u_action = append_sid("index.$this->phpEx?i=-phpbbservices-digests-acp-main_module&amp;mode=digests_edit_subscribers&amp;sortby=$sortby&amp;subscribe=$subscribe");
				$this->pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $total_users, $this->config['phpbbservices_digests_users_per_page'], $start);
								
				// Stealing some code from my Smartfeed extension so I can get a list of forums that a particular user can access
				
				// We need to know which auth_option_id corresponds to the forum read privilege (f_read) and forum list (f_list) privilege.
				$auth_options = array('f_read', 'f_list');

				$sql_array = array(
					'SELECT'	=> 'auth_option, auth_option_id',
				
					'FROM'		=> array(
						ACL_OPTIONS_TABLE		=> 'o',
					),
				
					'WHERE'		=> $this->db->sql_in_set('auth_option', $auth_options),
				);
				
				$sql = $this->db->sql_build_query('SELECT', $sql_array);

				$result = $this->db->sql_query($sql);
				$read_id = '';
				while ($row = $this->db->sql_fetchrow($result))
				{
					if ($row['auth_option'] == 'f_read')
					{
						$read_id = $row['auth_option_id'];
					}

				}
				$this->db->sql_freeresult($result); // Query be gone!
				
				// Fill in some non-block template variables
				$this->template->assign_vars(array(
					'ALL_SELECTED'				=> $all_selected,
					'ASCENDING_SELECTED'		=> $ascending_selected,
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
					'IMAGE_PATH'				=> $this->phpbb_root_path . 'ext/phpbbservices/digests/adm/images/',
					'LAST_SENT_SELECTED'		=> $last_sent_selected,
					'LASTVISIT_SELECTED'		=> $lastvisit_selected,
					'L_CONTEXT'					=> $context,
					'MEMBER'					=> $member,
					'STOPPED_SUBSCRIBING_SELECTED'	=> $stopped_subscribing,
					'SUBSCRIBE_SELECTED'		=> $subscribe_selected,
					'TOTAL_USERS'       		=> $this->language->lang('DIGESTS_LIST_USERS', (int) $total_users),
					'UNSUBSCRIBE_SELECTED'		=> $unsubscribe_selected,
					'USERNAME_SELECTED'			=> $username_selected,
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
				
					'WHERE'		=> "$subscribe_sql $member_sql user_type <> " . USER_IGNORE,
					
					'ORDER_BY'	=> sprintf($sort_by_sql, $order_by_sql, $order_by_sql),
				);

				$sql = $this->db->sql_build_query('SELECT', $sql_array);
				$result = $this->db->sql_query_limit($sql, $this->config['phpbbservices_digests_users_per_page'], $start);
				while ($row = $this->db->sql_fetchrow($result))
				{
					
					// Make some translations into something more readable
					switch($row['user_digest_type'])
					{
						case 'DAY':
							$digest_type = $this->language->lang('DIGESTS_DAILY');
						break;
						
						case 'WEEK':
							$digest_type = $this->language->lang('DIGESTS_WEEKLY');
						break;
						
						case 'MNTH':
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
					$send_hour_admin_offset = str_replace('.',':', floor($row['user_digest_send_hour_gmt']) + $my_time_zone);
					$send_hour_admin_offset = $this->helper->check_send_hour($send_hour_admin_offset);

					// Create an array of UTC offsets from board time zone. Also create the display hour format.
					$admin_hour_offset = array();
					$display_hour = array();
					for($i=0; $i<24; $i++)
					{
						if ($i < 0)
						{
							$admin_hour_offset[$i] = $i - $my_time_zone + 24;
							$display_hour[$i] = $this->helper->make_hour_string($i - $my_time_zone + 24, $this->user->data['user_dateformat']);
						}
						else if ($i > 23)
						{
							$admin_hour_offset[$i] = $i - $my_time_zone - 24;
							$display_hour[$i] = $this->helper->make_hour_string($i - $my_time_zone - 24, $this->user->data['user_dateformat']);
						}
						else
						{
							$admin_hour_offset[$i] = $i - $my_time_zone;
							$display_hour[$i] = $this->helper->make_hour_string($i, $this->user->data['user_dateformat']);
						}
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

					$all_by_default = (sizeof($subscribed_forums) == 0) ? true : false;

					$user_lastvisit = ($row['user_lastvisit'] == 0) ? $this->language->lang('DIGESTS_NEVER_VISITED') : $this->user->format_date($row['user_lastvisit'] + (60 * 60 * ($my_time_zone - (date('O')/100))), $this->user->data['user_dateformat']);
					$user_digest_last_sent = ($row['user_digest_last_sent'] == 0) ? $this->language->lang('DIGESTS_NO_DIGESTS_SENT') : $this->user->format_date($row['user_digest_last_sent'] + (60 * 60 * ($my_time_zone - (date('O')/100))), $this->user->data['user_dateformat']);

					$this->template->assign_block_vars('digests_edit_subscribers', array(
						'1ST'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_FIRST),
						'ALL'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_ALL),
						'BM'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS),
						'BOARD_OFFSET_0'					=> $admin_hour_offset[0],
						'BOARD_OFFSET_1'					=> $admin_hour_offset[1],
						'BOARD_OFFSET_2'					=> $admin_hour_offset[2],
						'BOARD_OFFSET_3'					=> $admin_hour_offset[3],
						'BOARD_OFFSET_4'					=> $admin_hour_offset[4],
						'BOARD_OFFSET_5'					=> $admin_hour_offset[5],
						'BOARD_OFFSET_6'					=> $admin_hour_offset[6],
						'BOARD_OFFSET_7'					=> $admin_hour_offset[7],
						'BOARD_OFFSET_8'					=> $admin_hour_offset[8],
						'BOARD_OFFSET_9'					=> $admin_hour_offset[9],
						'BOARD_OFFSET_10'					=> $admin_hour_offset[10],
						'BOARD_OFFSET_11'					=> $admin_hour_offset[11],
						'BOARD_OFFSET_12'					=> $admin_hour_offset[12],
						'BOARD_OFFSET_13'					=> $admin_hour_offset[13],
						'BOARD_OFFSET_14'					=> $admin_hour_offset[14],
						'BOARD_OFFSET_15'					=> $admin_hour_offset[15],
						'BOARD_OFFSET_16'					=> $admin_hour_offset[16],
						'BOARD_OFFSET_17'					=> $admin_hour_offset[17],
						'BOARD_OFFSET_18'					=> $admin_hour_offset[18],
						'BOARD_OFFSET_19'					=> $admin_hour_offset[19],
						'BOARD_OFFSET_20'					=> $admin_hour_offset[20],
						'BOARD_OFFSET_21'					=> $admin_hour_offset[21],
						'BOARD_OFFSET_22'					=> $admin_hour_offset[22],
						'BOARD_OFFSET_23'					=> $admin_hour_offset[23],
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
						'L_DIGEST_CHANGE_SUBSCRIPTION' 		=> ($row['user_digest_type'] != 'NONE') ? $this->language->lang('DIGESTS_UNSUBSCRIBE') : $this->language->lang('DIGESTS_SUBSCRIBE_LITERAL'),
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
						'USER_SUBSCRIBE_UNSUBSCRIBE_FLAG'	=> ($row['user_digest_type'] != 'NONE') ? 'u' : 's')
					);

					// Now let's get this user's forum permissions. Note that non-registered, robots etc. get a list of public forums
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

					if (isset($allowed_forums))
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
						
						$current_level = 0;			// How deeply nested are we at the moment
						$parent_stack = array();	// Holds a stack showing the current parent_id of the forum
						$parent_stack[] = 0;		// 0, the first value in the stack, represents the <div_0> element, a container holding all the categories and forums in the template
						
						while ($row2 = $this->db->sql_fetchrow($result2))
						{
							if ((int) $row2['parent_id'] != (int) end($parent_stack) || (end($parent_stack) == 0))
							{
								if (in_array($row2['parent_id'],$parent_stack))
								{
									// If parent is in the stack, then pop the stack until the parent is found, otherwise push stack adding the current parent. This creates a </div>
									while ((int) $row2['parent_id'] != (int) end($parent_stack))
									{
										array_pop($parent_stack);
										$current_level--;
										// Need to close a category level here
										$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
											'S_DIV_CLOSE' 	=> true,
											'S_DIV_OPEN' 	=> false,
											'S_PRINT' 		=> false,
											)
										);
									}
								}
								else
								{
									// If the parent is not in the stack, then push the parent_id on the stack. This is also a trigger to indent the block. This creates a <div>
									array_push($parent_stack, (int) $row2['parent_id']);
									$current_level++;
									// Need to add a category level here
									$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
										'CAT_ID' 			=> 'div_' . $row2['parent_id'],
										'S_DIV_CLOSE' 		=> false,
										'S_DIV_OPEN' 		=> true,
										'S_PRINT' 			=> false,
										)
									);
								}
							}
							
							// This code prints the forum or category, which will exist inside the previously created <div> block
							
							// Check this forum's checkbox? Only if they have forum subscriptions
							if (!$all_by_default)
							{
								$check = false;
								foreach($subscribed_forums as $this_row)
								{
									if ($this_row['forum_id'] == $row2['forum_id'])
									{
										$check = true;
										break;
									}
								}
							}
							else
							{
								$check = true;
							}
							
							$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
								'FORUM_LABEL' 			=> $row2['forum_name'],
								'FORUM_NAME' 			=> 'elt_' . (int) $row2['forum_id'] . '_' . (int) $row2['parent_id'],
								'S_FORUM_SUBSCRIBED' 	=> $check,
								'S_IS_FORUM' 			=> !($row2['forum_type'] == FORUM_CAT),
								'S_PRINT' 				=> true,
								)
							);
							
						}
					
						$this->db->sql_freeresult($result2);
					
						// Now out of the loop, it is important to remember to close any open <div> tags. Typically there is at least one.
						while ((int) $row2['parent_id'] != (int) end($parent_stack))
						{
							array_pop($parent_stack);
							$current_level--;
							// Need to close the <div> tag
							$this->template->assign_block_vars('digests_edit_subscribers.forums', array(
								'S_DIV_CLOSE' 	=> true,
								'S_DIV_OPEN' 	=> false,
								'S_PRINT' 		=> false,
								)
							);
						}
						
					}
					
				}
		
				$this->db->sql_freeresult($result); // Query be gone!
					
			break;

			case 'digests_balance_load':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_BALANCE_LOAD',
					'vars'	=> array(
						'legend1'								=> 'ACP_DIGESTS_BALANCE_OPTIONS',
					)
				);

				$avg_per_hour = $this->average_subscribers_per_hour();

				// Translate time zone information
				$this->template->assign_vars(array(
					'L_DIGESTS_HOUR_SENT'               		=> $this->language->lang('DIGESTS_HOUR_SENT', $my_time_zone),
					'S_BALANCE_LOAD'							=> true,
					'S_DIGESTS_AVERAGE'							=> $avg_per_hour,
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
					$weekly_subscribers_str = (count($weekly_subscribers) > 0 ) ? '<em>' . implode($this->language->lang('DIGESTS_COMMA'), $weekly_subscribers) . '</em>': '';
					$monthly_subscribers_str = (count($monthly_subscribers) > 0 ) ? '<strong>' . implode($this->language->lang('DIGESTS_COMMA'), $monthly_subscribers) . '</strong>': '';

					// Comma separate the digest types for display
					$hourly_subscribers = $daily_subscribers_str;
					if (strlen($hourly_subscribers) > 0 && strlen($weekly_subscribers_str) > 0)
					{
						$hourly_subscribers .= $this->language->lang('DIGESTS_COMMA') . $weekly_subscribers_str;
					}
					else
					{
						$hourly_subscribers .= $weekly_subscribers_str;
					}
					if (strlen($hourly_subscribers) > 0 && strlen($monthly_subscribers_str) > 0)
					{
						$hourly_subscribers .= $this->language->lang('DIGESTS_COMMA') . $monthly_subscribers_str;
					}
					else
					{
						$hourly_subscribers .= $monthly_subscribers_str;
					}

					$this->template->assign_block_vars('digests_balance_load', array(
						'HOUR'              => $this->helper->make_hour_string($i, $this->user->data['user_dateformat']),
						'HOUR_COUNT'        => ($hour_count > $avg_per_hour) ? '<strong>' . $hour_count . '</strong>' : $hour_count,
						'HOUR_UTC'        	=> $hour_utc,
						'SUBSCRIBERS'		=> $hourly_subscribers,
					));
				
				}				
				$this->db->sql_freeresult($result); // Query be gone!
			break;

			case 'digests_mass_subscribe_unsubscribe':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE',
					'vars'	=> array(
						'legend1'								=> 'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE',
						'phpbbservices_digests_enable_subscribe_unsubscribe'	=> array('lang' => 'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_subscribe_all'					=> array('lang' => 'DIGESTS_SUBSCRIBE_ALL',					'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_include_admins'					=> array('lang' => 'DIGESTS_INCLUDE_ADMINS',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
					)
				);
			break;

			case 'digests_reset_cron_run_time':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_RESET_CRON_RUN_TIME',
					'vars'	=> array(
						'legend1'								=> 'ACP_DIGESTS_RESET_CRON_RUN_TIME',
						'phpbbservices_digests_enable_subscribe_unsubscribe'	=> array('lang' => 'DIGESTS_RESET_CRON_RUN_TIME',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
					)
				);
			break;

			case 'digests_test':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_TEST',
					'vars'	=> array(
						'legend1'									=> 'GENERAL_OPTIONS',
						'phpbbservices_digests_test'				=> array('lang' => 'DIGESTS_RUN_TEST', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_test_spool'			=> array('lang' => 'DIGESTS_RUN_TEST_SPOOL', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_test_clear_spool'	=> array('lang' => 'DIGESTS_RUN_TEST_CLEAR_SPOOL', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_test_send_to_admin'	=> array('lang' => 'DIGESTS_RUN_TEST_SEND_TO_ADMIN', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_test_email_address'	=> array('lang' => 'DIGESTS_RUN_TEST_EMAIL_ADDRESS', 'validate' => 'string',	'type' => 'email:25:100', 'explain' => true),
						'legend2'									=> 'DIGESTS_RUN_TEST_OPTIONS',
						'phpbbservices_digests_test_time_use'		=> array('lang' => 'DIGESTS_RUN_TEST_TIME_USE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_test_year'			=> array('lang' => 'DIGESTS_RUN_TEST_YEAR', 'validate' => 'int:2000:2030', 'type' => 'text:4:4', 'explain' => true),
						'phpbbservices_digests_test_month'			=> array('lang' => 'DIGESTS_RUN_TEST_MONTH', 'validate' => 'int:1:12', 'type' => 'text:2:2', 'explain' => true),
						'phpbbservices_digests_test_day'			=> array('lang' => 'DIGESTS_RUN_TEST_DAY', 'validate' => 'int:1:31', 'type' => 'text:2:2', 'explain' => true),
						'phpbbservices_digests_test_hour'			=> array('lang' => 'DIGESTS_RUN_TEST_HOUR',	'validate' => 'int:0:23',	'type' => 'text:2:2', 'explain' => true),
				)
				);
			break;
				
			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
				
		}

		$this->new_config = $this->config;
		$cfg_array = (isset($_REQUEST['config'])) ? $this->request->variable('config', array('' => ''), true) : $this->new_config;
		$error = array();

		// We validate the complete config if wished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $this->language->lang('FORM_INVALID');
		}
		
		// Do not write values if there is an error
		if (sizeof($error))
		{
			$submit = false;
		}

		// We go through the display_vars to make sure no one is trying to set variables he/she is not allowed to...
		
		foreach ($display_vars['vars'] as $config_name => $data)
		{
			if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
			{
				continue;
			}

			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

			if ($submit)
			{
				$this->config->set($config_name, $config_value);
			}
		}

		if ($submit && $mode == 'digests_edit_subscribers')
		{

			// The "selected" input control indicates whether to do mass actions or not. With mass actions only the select control and
			// the mark checkboxes matter. Other controls are ignored.
			$selected = $this->request->variable('selected', 'IGNORE', true);
			$mass_action = ($selected == 'IGNORE') ? false : true;
			$use_defaults = false;
			$use_defaults_pass = false;
			unset($sql_ary, $sql_ary2);
			
			// Get the entire request variables as an array for parsing
			unset($requests_vars);
			$request_vars = $this->request->get_super_global(\phpbb\request\request_interface::POST);
			
			// If a mass action, we want to remove all post variables with the user-99-col_name pattern. It makes the logic easier by effectively ignoring them.
			if ($mass_action)
			{
				foreach ($request_vars as $key => $value)
				{
					if (substr($key,0,5) == 'user-' && strpos($key, '-', 5) > 5)
					{
						unset($request_vars[$key]);
					}
				}
			}
			
			// Now let's sort the request variables so we process one can user at a time
			ksort($request_vars);

			// Set some flags
			$current_user_id = NULL;
			
			foreach ($request_vars as $name => $value)
			{
				
				// We only care if the request variable starts with "user-".
				if (substr($name,0,5) == 'user-')
				{
					
					// Parse for the user_id, which is embedded in the form field name. Format is user-99-column_name where 99
					// is the user id. The mark switch is in the form user-99.
					$delimiter_pos = strpos($name, '-', 5);
					if ($delimiter_pos === false)
					{
						// This is the mark all checkbox for a given user
						$delimiter_pos = strlen($name);
					}
					$user_id = substr($name, 5, $delimiter_pos - 5);
					$var_part = substr($name, $delimiter_pos + 1);

					if ($current_user_id === NULL)
					{
						$current_user_id = $user_id;
					}
					else if ($current_user_id !== $user_id)
					{
						
						// Save this subscriber's digest settings
						if (isset($sql_ary) && sizeof($sql_ary) > 0)
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
						if (!$mass_action && isset($sql_ary2) && sizeof($sql_ary2) > 0)
						{
							$this->db->sql_multi_insert($this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE, $sql_ary2);
						}

						// Also want to save some information to an array to be used for sending emails to affected users.
						$digest_notify_list[] = $current_user_id;

						// We need to set these variables so we can detect if individual forum subscriptions will need to be processed.
						$current_user_id = $user_id;
						$use_defaults = false;
						$use_defaults_pass = false;
						unset($sql_ary, $sql_ary2);
						
					}

					if ($mass_action)
					{
						
						// We need to save the digest settings for this mass user. There should only be one request variable for the user.
						
						// Do a mass action only if the mark variable for the user_id is checked
						if ($value == 'on')
						{
							switch ($selected)
							{
								case constants::DIGESTS_DEFAULT_VALUE:
									// Use the default digest type
									$sql_ary['user_digest_type'] = $this->config['phpbbservices_digests_user_digest_type'];
								break;
								
								case constants::DIGESTS_NONE_VALUE;
									// Remove user's subscription (mass action)
									$sql_ary['user_digest_type'] = constants::DIGESTS_NONE_VALUE;
								break;
								
								default:
								break;
							}
							
							// Create a digest subscription using board defaults
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
						}
						
					}
					
					else	// Process individual subscriptions
					
					{
						
						// We need to get these variables so we can detect if individual forum subscriptions will need to be processed.
						$var = 'user-' . $current_user_id . '-all_forums';
						$all_forums = $this->request->variable($var, '', true);
						$var = 'user-' . $current_user_id . '-filter_type';
						$filter_type = $this->request->variable($var, '', true);
						
						// No mass action, so associate the database columns with its requested value
						if (!$use_defaults && ($var_part == 'digest_type') && ($value == constants::DIGESTS_DEFAULT_VALUE))
						{
							$use_defaults = true;
							unset($sql_ary);
						}
						
						if ($use_defaults)
						{
							if (!$use_defaults_pass)
							{
								// Create a digest subscription using board defaults. but do it only once for the user_id
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
								$use_defaults_pass = true;
							}
						}
						else
						{
							switch ($var_part)
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
														
						}
						
						// Note that if "all_forums" is unchecked and bookmarks is unchecked, there are individual forum subscriptions, so they must be saved.
						if (substr($var_part, 0, 4) == 'elt_')
						{
							// We should save this forum as an individual forum subscription, but only if the all forums checkbox
							// is not set AND the user should not get posts for bookmarked topics only.
	
							// This request variable is a checkbox for a forum for this user. It should be checked or it would
							// not be in the $request_vars array.
							
							$delimiter_pos = strpos($var_part, '_', 4);
							$forum_id = substr($var_part, 4, $delimiter_pos - 4);
							
							if (($all_forums !== 'on') && (trim($filter_type) !== constants::DIGESTS_BOOKMARKS)) 
							{
								$sql_ary2[] = array(
									'user_id'		=> (int) $current_user_id,
									'forum_id'		=> (int) $forum_id);
							}
						}
						
					}
					
				} // $request_vars variable is named user-*
				
			} // foreach
			
			// Process last user
			
			// Save this subscriber's digest settings
			if (isset($sql_ary) && sizeof($sql_ary) > 0)
			{
				$sql = 'UPDATE ' . USERS_TABLE . ' 
					SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . (int) $current_user_id;
				$this->db->sql_query($sql);
			}
			
			// If there are any individual forum subscriptions for this user, remove the old ones. 
			if (!is_null($current_user_id))
			{
				$sql = 'DELETE FROM ' . $this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
						WHERE user_id = ' . (int) $current_user_id;
				$this->db->sql_query($sql);
			}

			// Now save the individual forum subscriptions, if any
			if (!$mass_action && isset($sql_ary2) && sizeof($sql_ary2) > 0)
			{
				$this->db->sql_multi_insert($this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE, $sql_ary2);
			}

			// Also want to save some information to an array to be used for sending emails to affected users.
			$digest_notify_list[] = $current_user_id;

			// Notify users whose subscriptions were changed
			if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
			{
				$this->notify_subscribers($digest_notify_list, 'digests_subscription_edited');
			}
				
			$message = $this->language->lang('CONFIG_UPDATED');
				
		}

		if ($submit && $mode == 'digests_balance_load')
		{

			// Get the balance type: all, daily, weekly or monthly. Only these digest types will be balanced.
			$balance = $this->request->variable('balance', constants::DIGESTS_ALL);
			$balance_sql = ($balance == constants::DIGESTS_ALL) ?
				"user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "'" :
				"user_digest_type = '" . $balance . "'";

			// Get the hours to balance. If -1 is among those hours returned, all hours are wanted. Others that may be selected are ignored.
 			$for_hours = $this->request->variable('for_hrs', array('' => 0));
			$for_hours_sql = (in_array(-1, $for_hours)) ? '' : ' AND ' . $this->db->sql_in_set('user_digest_send_hour_gmt', $for_hours);

 			// Get the hours to apply the balance to. If -1 is among those hours returned, all hours are candidates for being used.
  			$to_hours = $this->request->variable('to_hrs', array('' => 0));

			// Determine the average number of subscribers per hour. We need to assume at least one subscriber per hour to avoid
			// resetting user's preferred digest time unnecessarily. If the average is 3 per hour, the first 3 already subscribed 
			// will not have their digest arrival time changed.
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

			if (sizeof($oversubscribed_hours) > 0)
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

					$digest_notify_list[] = $row['user_id'];

					if ($current_hour != $row['user_digest_send_hour_gmt'])
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
						$rebalanced++;

					}

				}
				
				$this->db->sql_freeresult($result);
			
				// Notify users whose subscriptions were changed
				if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
				{
					$this->notify_subscribers($digest_notify_list, 'digests_subscription_edited');
				}
				
			}
			
			$message = $this->language->lang('DIGESTS_REBALANCED', $rebalanced);

		}

		if ($submit && $mode == 'digests_mass_subscribe_unsubscribe')
		{
			
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

					$result = $this->db->sql_query($sql);

					$this->db->sql_freeresult($result); // Query be gone!

					if ($this->config['phpbbservices_digests_subscribe_all'])
					{
						array_pop($sql_ary);	// Remove $sql_ary['user_digest_send_hour_gmt'] since it may change for each subscriber
					}

				}

				// Notify users or subscription or unsubscription if directed
				if ($this->config['phpbbservices_digests_notify_on_admin_changes'])
				{
					$this->notify_subscribers($digest_notify_list);
				}
				
				if ($this->config['phpbbservices_digests_subscribe_all'])
				{
					$message = $this->language->lang('DIGESTS_ALL_SUBSCRIBED', sizeof($digest_notify_list));
				}
				else
				{
					$message = $this->language->lang('DIGESTS_ALL_UNSUBSCRIBED', sizeof($digest_notify_list));
				}
				
			}
			else
			{
				// show no update message
				$message = $this->language->lang('DIGESTS_NO_MASS_ACTION');
			}

		}

		if ($submit && $mode == 'digests_reset_cron_run_time')
		{
			// This allows the digests to go out next time cron.php is run.
			$this->config->set('phpbbservices_digests_cron_task_last_gc', 0);
			
			// This resets all the date/time stamps for when a digest was last sent to a user.
			$sql_ary = array('user_digest_last_sent' => 0);
			
			$sql = 'UPDATE ' . USERS_TABLE . ' 
				SET ' . $this->db->sql_build_array('UPDATE', $sql_ary);
			$this->db->sql_query($sql);
			
			$this->db->sql_build_query('UPDATE', $sql_ary);
		}

		if ($submit && $mode == 'digests_test')
		{

			define('IN_DIGESTS_TEST', true);
			$continue = true;

			if (!$this->config['phpbbservices_digests_test'] && !$this->config['phpbbservices_digests_test_clear_spool'])
			{
				$message = $this->language->lang('DIGESTS_MAILER_NOT_RUN');
				$continue = false;
			}
			
			if ($continue && $this->config['phpbbservices_digests_test_clear_spool'])
			{
				
				// Clear the digests cache folder of .txt and .html files, if so instructed
				$all_cleared = true;

				$path = $this->phpbb_root_path . 'cache/phpbbservices/digests';
				if (is_dir($path))
				{
					foreach (new \DirectoryIterator($path) as $file_info)
					{
						$file_name = $file_info->getFilename();
						// Exclude dot files, hidden files and non "real" files, and real files if they don't have the .html or .txt suffix
						if ((substr($file_name, 0, 1) !== '.') && $file_info->isFile() && ($file_info->getExtension() == 'html' || $file_info->getExtension() == 'txt'))
						{
							$deleted = unlink($path . '/' . $file_name); // delete file
							if (!$deleted)
							{
								$all_cleared = false;
							}
						}
					}
				}
				else	// Digests cache directory not found, which is not good. It is created in ext.php when the extension is enabled, so something destroyed it.
				{
					$continue = false;
					$all_cleared = false;
				}
					
				if ($continue && $this->config['phpbbservices_digests_enable_log'])
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
					$continue = false;
				}

			}
			
			if ($continue && $this->config['phpbbservices_digests_test_time_use'])
			{
				
				// Make sure run date is valid, if a run date was requested.
				$good_date = checkdate($this->config['phpbbservices_digests_test_month'], $this->config['phpbbservices_digests_test_day'], $this->config['phpbbservices_digests_test_year']);
				if (!$good_date)
				{
					$message_type = E_USER_WARNING;
					$message = $this->language->lang('DIGESTS_ILLOGICAL_DATE');
					$continue = false;
				}

			}
			
			// Get ready to manually mail digests
			if ($continue)
			{

				// Create a new template object for the mailer to use since we don't want to lose the content in this one. (The mailer will overwrite it and
				// remove the sidebars.) With phpBB 3.2 this first requires creating a template environment object. Note: similar code used to work in 3.1, but
				// doesn't in 3.2. I've looked at it and tried various things but so far it results in a TWIG error. Keeping it around for future attempts.
				/*$mailer_template = new \phpbb\template\twig\twig(
					$this->phpbb_path_helper,
					$this->config,
					new \phpbb\template\context(),
					new \phpbb\template\twig\environment(
						$this->config,
						$phpbb_container->get('filesystem'),
						$this->phpbb_path_helper,
						$phpbb_container->getParameter('core.cache_dir'),
						$this->phpbb_extension_manager,
						new \phpbb\template\twig\loader(
							$phpbb_container->get('filesystem')
						)
					),
					$phpbb_container->getParameter('core.cache_dir'),
					$this->user
				);*/

				//$mailer_template->set_style(array('./ext/phpbbservices/digests/styles', 'styles'));

				// Create a mailer object and call its run method. The logic for sending a digest is embedded in this method, which is normally run as a cron task.
				$mailer = new \phpbbservices\digests\cron\task\digests($this->config, $this->request, $this->user, $this->db, $this->phpEx, $this->phpbb_root_path, $this->template, $this->auth, $this->table_prefix, $this->phpbb_log, $this->helper, $this->language);
				$success = $mailer->run();
				
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
	
		if ($submit)
		{
						
			if (!isset($message_type))
			{
				$message_type = E_USER_NOTICE;
			}
			if (!isset($message))
			{
				$message = $this->language->lang('CONFIG_UPDATED');
			}
			if ($mode !== 'digests_test')
			{
				$this->phpbb_log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_' . strtoupper($mode));
			}
			trigger_error($message . adm_back_link($this->u_action), $message_type);
				
		}

		$this->tpl_name = 'acp_digests';
		$this->page_title = $display_vars['title'];

		$this->template->assign_vars(array(
			'ERROR_MSG'			=> (is_array($error) ? implode('<br>', $error) : $error),
			'L_MESSAGE'			=> $error,
			'L_TITLE'			=> $this->language->lang($display_vars['title']),
			'L_TITLE_EXPLAIN'	=> $this->language->lang($display_vars['title'] . '_EXPLAIN'),
			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'U_ACTION'			=> $this->u_action)
		);

		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && strpos($config_key, 'legend') === false)
			{
				continue;
			}

			if (strpos($config_key, 'legend') !== false)
			{
				$this->template->assign_block_vars('options', array(
					'LEGEND'		=> (null !== $this->language->lang($vars)) ? $this->language->lang($vars) : $vars,
					'S_LEGEND'		=> true)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (null !== $this->language->lang($vars['lang_explain'])) ? $this->language->lang($vars['lang_explain']) : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (null !== $this->language->lang($vars['lang'] . '_EXPLAIN')) ? $this->language->lang($vars['lang'] . '_EXPLAIN') : '';
			}

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$this->template->assign_block_vars('options', array(
				'CONTENT'		=> $content,
				'KEY'			=> $config_key,
				'TITLE'			=> (null !== $this->language->lang($vars['lang'])) ? $this->language->lang($vars['lang']) : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				)
			);

			unset($display_vars['vars'][$config_key]);
		}

	}

	function dow_select()
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

	function digest_type_select()
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

	function digest_style_select()
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

	function digest_send_hour_utc()
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
		$digest_filter_types = '<option value="' . constants::DIGESTS_ALL . '"' . $selected. '>' . $this->language->lang('DIGESTS_ALL_FORUMS') . '</option>';
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
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD . '"' . $selected .  '>' . $this->language->lang('DIGESTS_SORT_FORUM_TOPIC') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD_DESC . '"' . $selected. '>' . $this->language->lang('DIGESTS_SORT_FORUM_TOPIC_DESC') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE . '"' . $selected. '>' . $this->language->lang('DIGESTS_SORT_POST_DATE') . '</option>';
		$selected = ($this->config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE_DESC . '"' . $selected. '>' . $this->language->lang('DIGESTS_SORT_POST_DATE_DESC') . '</option>';
		
		return $digest_sort_order;
	}
	
	function notify_subscribers ($digest_notify_list, $email_template = '')
	{
		
		// This function parses $digest_notify_list, an array of user_ids that represent users that had their digest subscriptions changed, and sends them an email
		// letting them know an action has occurred.
		
		$emails_sent = 0;
		
		if (isset($digest_notify_list) && (sizeof($digest_notify_list) > 0))
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
			
			foreach ($rowset as $row)
			{
				
				// E-mail setup
				$messenger = new \messenger();
				
				switch ($email_template)
				{
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
					case constants::DIGESTS_DAILY_VALUE:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_DAILY'));
					break;
					
					case constants::DIGESTS_WEEKLY_VALUE:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_WEEKLY'));
					break;
					
					case constants::DIGESTS_MONTHLY_VALUE:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_MONTHLY'));
					break;
					
					case constants::DIGESTS_NONE_VALUE:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_NONE'));
					break;

					default:
						$digest_type_text = strtolower($this->language->lang('DIGESTS_DAILY'));
					break;
				}
				
				// Set up associations between digest formats as constants and their language equivalents
				switch ($row['user_digest_format'])
				{
					case constants::DIGESTS_HTML_VALUE:
						$digest_format_text = $this->language->lang('DIGESTS_FORMAT_HTML');
					break;
					
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
	
			$this->db->sql_freeresult($result); // Query be gone!
			
		}
		
		return $emails_sent;
	
	}

	function average_subscribers_per_hour ()
	{

		// This function returns the average number of digest subscribers per hour.

		$sql_array = array(
			'SELECT'	=> 'COUNT(user_id) AS digests_count',

			'FROM'		=> array(
				USERS_TABLE		=> 'u',
			),

			'WHERE'		=> "user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' 
					AND user_type <> " . USER_IGNORE,
		);

		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);

		$avg_subscribers_per_hour = round((float) $row['digests_count']/24);
		$this->db->sql_freeresult($result);

		return $avg_subscribers_per_hour;

	}

	function get_subscribers_for_hour ($hour, $tz_text)
	{

		// Returns an array of subscribers for a given hour, keyed by digest type

		$subscribers = array();

		$hour_utc = $hour - $this->helper->make_tz_offset($tz_text);
		$hour_utc = $this->helper->check_send_hour($hour_utc);

		$sql_array = array(
			'SELECT'	=> 'username, user_digest_type',

			'FROM'		=> array(
				USERS_TABLE		=> 'u',
			),

			'WHERE' 	=> $this->db->sql_in_set('user_digest_send_hour_gmt', $hour_utc),

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

}
