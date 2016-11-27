<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\acp;

use phpbbservices\digests\constants\constants;

class main_module
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)

	{

		global $phpbb_container;

		// Get global variables as containers to minimize security issues
		$auth = $phpbb_container->get('auth');
		$config = $phpbb_container->get('config');
		$db = $phpbb_container->get('dbal.conn');
		$phpbb_extension_manager = $phpbb_container->get('ext.manager');
		$phpbb_log = $phpbb_container->get('log');
		$phpbb_path_helper = $phpbb_container->get('path_helper');
		$phpbb_root_path = $phpbb_container->getParameter('core.root_path');
		$phpEx= $phpbb_container->getParameter('core.php_ext');
		$request = $phpbb_container->get('request');
		$table_prefix = $phpbb_container->getParameter('core.table_prefix');
		$template = $phpbb_container->get('template');
		$user = $phpbb_container->get('user');

		$user->add_lang_ext('phpbbservices/digests', array('acp/info_acp_common', 'acp/common'));

		$submit = (isset($_POST['submit'])) ? true : false;

		$form_key = 'phpbbservices/digests';
		add_form_key($form_key);

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
						'legend1'								=> '',
						'phpbbservices_digests_enable_log'					=> array('lang' => 'DIGESTS_ENABLE_LOG',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_show_email'					=> array('lang' => 'DIGESTS_SHOW_EMAIL',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_enable_auto_subscriptions'	=> array('lang' => 'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_registration_field'			=> array('lang' => 'DIGESTS_REGISTRATION_FIELD',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_block_images'				=> array('lang' => 'DIGESTS_BLOCK_IMAGES',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_notify_on_admin_changes'		=> array('lang' => 'DIGESTS_NOTIFY_ON_ADMIN_CHANGES',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_weekly_digest_day'			=> array('lang' => 'DIGESTS_WEEKLY_DIGESTS_DAY',				'validate' => 'int:0:6',	'type' => 'select', 'method' => 'dow_select', 'explain' => false),
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
						'phpbbservices_digests_time_zone'					=> array('lang' => 'DIGESTS_TIME_ZONE',							'validate' => 'int:-12:12',	'type' => 'text:5:5', 'explain' => true),
					)
				);
			break;
				
			case 'digests_user_defaults':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_USER_DEFAULT_SETTINGS',
					'vars'	=> array(						
						'legend1'											=> '',
						'phpbbservices_digests_user_digest_registration'	=> array('lang' => 'DIGESTS_USER_DIGESTS_REGISTRATION',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_user_digest_type'			=> array('lang' => 'DIGESTS_USER_DIGESTS_TYPE',					'validate' => 'string',	'type' => 'select', 'method' => 'digest_type_select', 'explain' => false),
						'phpbbservices_digests_user_digest_format'			=> array('lang' => 'DIGESTS_USER_DIGESTS_STYLE',					'validate' => 'string',	'type' => 'select', 'method' => 'digest_style_select', 'explain' 		=> false),
						'phpbbservices_digests_user_digest_send_hour_gmt'	=> array('lang' => 'DIGESTS_USER_DIGESTS_SEND_HOUR_GMT',			'validate' => 'int:-1:23',	'type' => 'select', 'method' => 'digest_send_hour_gmt', 'explain' => false),
						'phpbbservices_digests_user_digest_filter_type'		=> array('lang' => 'DIGESTS_USER_DIGESTS_FILTER_TYPE',			'validate' => 'string',	'type' => 'select', 'method' => 'digest_filter_type', 'explain' => false),
						'phpbbservices_digests_user_check_all_forums'		=> array('lang' => 'DIGESTS_USER_DIGESTS_CHECK_ALL_FORUMS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_user_digest_max_posts'		=> array('lang' => 'DIGESTS_USER_DIGESTS_MAX_POSTS',				'validate' => 'int:0',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_user_digest_min_words'		=> array('lang' => 'DIGESTS_USER_DIGESTS_MIN_POSTS',				'validate' => 'int:0',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_user_digest_new_posts_only'	=> array('lang' => 'DIGESTS_USER_DIGESTS_NEW_POSTS_ONLY',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_show_mine'		=> array('lang' => 'DIGESTS_USER_DIGESTS_SHOW_MINE',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_remove_foes'		=> array('lang' => 'DIGESTS_USER_DIGESTS_SHOW_FOES',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_show_pms'		=> array('lang' => 'DIGESTS_USER_DIGESTS_SHOW_PMS',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_pm_mark_read'	=> array('lang' => 'DIGESTS_USER_DIGESTS_PM_MARK_READ',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_sortby'			=> array('lang' => 'DIGESTS_USER_DIGESTS_SORT_ORDER',				'validate' => 'string',	'type' => 'select', 'method' => 'digest_post_sort_order', 'explain' 	=> false),
						'phpbbservices_digests_user_digest_max_display_words'	=> array('lang' => 'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS',		'validate' => 'int:-1',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_user_digest_send_on_no_posts'	=> array('lang' => 'DIGESTS_USER_DIGESTS_SEND_ON_NO_POSTS',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_reset_lastvisit'	=> array('lang' => 'DIGESTS_USER_DIGESTS_RESET_LASTVISIT',		'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_attachments'		=> array('lang' => 'DIGESTS_USER_DIGESTS_ATTACHMENTS',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_block_images'	=> array('lang' => 'DIGESTS_USER_DIGESTS_BLOCK_IMAGES',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
						'phpbbservices_digests_user_digest_toc'				=> array('lang' => 'DIGESTS_USER_DIGESTS_TOC',					'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
					)
				);
			break;
				
			case 'digests_edit_subscribers':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_EDIT_SUBSCRIBERS',
					'vars'	=> array(
					)
				);

				// Grab some URL parameters
				$member = $request->variable('member', '', true);
				$start = $request->variable('start', 0);
				$subscribe = $request->variable('subscribe', 'a', true);
				$sortby = $request->variable('sortby', 'u', true);
				$sortorder = $request->variable('sortorder', 'a', true);
				
				// Translate time zone information
				$template->assign_vars(array(
					'L_DIGESTS_HOUR_SENT'               			=> $user->lang('DIGESTS_HOUR_SENT', $config['phpbbservices_digests_time_zone']),
					'L_DIGESTS_BASED_ON'							=> $user->lang('DIGESTS_BASED_ON', $config['phpbbservices_digests_time_zone']),
					'S_EDIT_SUBSCRIBERS'							=> true,
				));

				// Set up subscription filter				
				$all_selected = $stopped_subscribing = $subscribe_selected = $unsubscribe_selected = '';
				switch ($subscribe)
				{
					case 'u':
						$subscribe_sql = "user_digest_type = 'NONE' AND user_digest_has_unsubscribed = 0 AND ";
						$unsubscribe_selected = ' selected="selected"';
						$context = $user->lang('DIGESTS_UNSUBSCRIBED');
					break;
					
					case 't':
						$subscribe_sql = "user_digest_type = 'NONE' AND user_digest_has_unsubscribed = 1 AND";
						$stopped_subscribing = ' selected="selected"';
						$context = $user->lang('DIGESTS_STOPPED_SUBSCRIBING');
					break;
					
					case 's':
						$subscribe_sql = "user_digest_type <> 'NONE' AND user_digest_send_hour_gmt >= 0 AND user_digest_send_hour_gmt < 24 AND user_digest_has_unsubscribed = 0 AND";
						$subscribe_selected = ' selected="selected"';
						$context = $user->lang('DIGESTS_SUBSCRIBED');
					break;

					case 'a':
						$subscribe_sql = '';
						$all_selected = ' selected="selected"';
						$context = $user->lang('DIGESTS_ALL');
					break;
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
				$match_any_chars = $db->get_any_char();
				$member_sql = ($member <> '') ? " username_clean " . $db->sql_like_expression($match_any_chars . utf8_case_fold_nfc($member) . $match_any_chars) . " AND " : '';

				// Get the total rows for pagination purposes
				$sql_array = array(
					'SELECT'	=> 'COUNT(user_id) AS total_users',
				
					'FROM'		=> array(
						USERS_TABLE		=> 'u',
					),
				
					'WHERE'		=> "$subscribe_sql $member_sql user_type <> " . USER_IGNORE,
				);
				
				$sql = $db->sql_build_query('SELECT', $sql_array);
				$result = $db->sql_query($sql);

				// Get the total users, this is a single row, single field.
				$total_users = $db->sql_fetchfield('total_users');
				
				// Free the result
				$db->sql_freeresult($result);
				
				// Create pagination logic
				$pagination = $phpbb_container->get('pagination');

				$this->u_action = append_sid("index.$phpEx?i=-phpbbservices-digests-acp-main_module&amp;mode=digests_edit_subscribers&amp;sortby=$sortby");	
				$pagination->generate_template_pagination($this->u_action, 'pagination', 'start', $total_users, $config['phpbbservices_digests_users_per_page'], $start);
								
				// Stealing some code from my Smartfeed extension so I can get a list of forums that a particular user can access
				
				// We need to know which auth_option_id corresponds to the forum read privilege (f_read) and forum list (f_list) privilege.
				$auth_options = array('f_read', 'f_list');

				$sql_array = array(
					'SELECT'	=> 'auth_option, auth_option_id',
				
					'FROM'		=> array(
						ACL_OPTIONS_TABLE		=> 'o',
					),
				
					'WHERE'		=> $db->sql_in_set('auth_option', $auth_options),
				);
				
				$sql = $db->sql_build_query('SELECT', $sql_array);

				$result = $db->sql_query($sql);
				$read_id = '';
				while ($row = $db->sql_fetchrow($result))
				{
					if ($row['auth_option'] == 'f_read')
					{
						$read_id = $row['auth_option_id'];
					}

				}
				$db->sql_freeresult($result); // Query be gone!
				
				// Fill in some non-block template variables
				$template->assign_vars(array(
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
					'IMAGE_PATH'				=> $phpbb_root_path . 'ext/phpbbservices/digests/adm/images/',
					'LAST_SENT_SELECTED'		=> $last_sent_selected,
					'LASTVISIT_SELECTED'		=> $lastvisit_selected,
					'L_CONTEXT'					=> $context,
					'MEMBER'					=> $member,
					'STOPPED_SUBSCRIBING_SELECTED'	=> $stopped_subscribing,
					'SUBSCRIBE_SELECTED'		=> $subscribe_selected,
					'TOTAL_USERS'       		=> $user->lang('DIGESTS_LIST_USERS', (int) $total_users),
					'UNSUBSCRIBE_SELECTED'		=> $unsubscribe_selected,
					'USERNAME_SELECTED'			=> $username_selected,
				));
	
				$board_offset_hours = (int) $config['phpbbservices_digests_time_zone'];
				
				$sql_array = array(
					'SELECT'	=> '*, CASE
										WHEN user_digest_send_hour_gmt + ' . $board_offset_hours . ' >= 24 THEN
						 					user_digest_send_hour_gmt + ' . $board_offset_hours . ' - 24  
										WHEN user_digest_send_hour_gmt + ' . $board_offset_hours . ' < 0 THEN
						 					user_digest_send_hour_gmt + ' . $board_offset_hours . ' + 24 
										ELSE user_digest_send_hour_gmt + ' . $board_offset_hours . '
										END AS send_hour_board',
				
					'FROM'		=> array(
						USERS_TABLE		=> 'u',
					),
				
					'WHERE'		=> "$subscribe_sql $member_sql user_type <> " . USER_IGNORE,
					
					'ORDER_BY'	=> sprintf($sort_by_sql, $order_by_sql, $order_by_sql),
				);
				
				$sql = $db->sql_build_query('SELECT', $sql_array);
				$result = $db->sql_query_limit($sql, $config['phpbbservices_digests_users_per_page'], $start);
	
				while ($row = $db->sql_fetchrow($result))
				{
					
					// Make some translations into something more readable
					switch($row['user_digest_type'])
					{
						case 'DAY':
							$digest_type = $user->lang('DIGESTS_DAILY');
						break;
						
						case 'WEEK':
							$digest_type = $user->lang('DIGESTS_WEEKLY');
						break;
						
						case 'MNTH':
							$digest_type = $user->lang('DIGESTS_MONTHLY');
						break;
						
						default:
							$digest_type = $user->lang('DIGESTS_UNKNOWN');
						break;
					}
					
					switch($row['user_digest_format'])
					{
						case constants::DIGESTS_HTML_VALUE:
							$digest_format = $user->lang('DIGESTS_FORMAT_HTML');
						break;
						
						case constants::DIGESTS_HTML_CLASSIC_VALUE:
							$digest_format = $user->lang('DIGESTS_FORMAT_HTML_CLASSIC');
						break;
						
						case constants::DIGESTS_PLAIN_VALUE:
							$digest_format = $user->lang('DIGESTS_FORMAT_PLAIN');
						break;
						
						case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
							$digest_format = $user->lang('DIGESTS_FORMAT_PLAIN_CLASSIC');
						break;
						
						case constants::DIGESTS_TEXT_VALUE:
							$digest_format = $user->lang('DIGESTS_FORMAT_TEXT');
						break;
						
						default:
							$digest_format = $user->lang('DIGESTS_UNKNOWN');
						break;
					}
					
					// Calculate a digest send hour in board time
					$send_hour_board = str_replace('.',':', floor($row['user_digest_send_hour_gmt']) + $board_offset_hours);
					if ($send_hour_board >= 24)
					{
						$send_hour_board = $send_hour_board - 24;
					}
					else if ($send_hour_board < 0)
					{
						$send_hour_board = $send_hour_board + 24;
					}
					
					// Create an array of GMT offsets from board time zone
					$board_offset = array();
					for($i=0; $i<24; $i++)
					{
						if (($i - $board_offset_hours) < 0)
						{
							$board_offset[$i] = $i - $board_offset_hours + 24;
						}
						else if (($i - $board_offset_hours) > 23)
						{
							$board_offset[$i] = $i - $board_offset_hours - 24;
						}
						else
						{
							$board_offset[$i] = $i - $board_offset_hours;
						}
					}

					$sql_array = array(
						'SELECT'	=> 'forum_id ',
					
						'FROM'		=> array(
							$table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE => 'sf',
						),
					
						'WHERE'		=> 'user_id = ' . (int) $row['user_id'],
					);
					
					$sql2 = $db->sql_build_query('SELECT', $sql_array);

					$result2 = $db->sql_query($sql2);
					$subscribed_forums = $db->sql_fetchrowset($result2);
					$db->sql_freeresult($result2);

					$all_by_default = (sizeof($subscribed_forums) == 0) ? true : false;
					
					$template->assign_block_vars('digests_edit_subscribers', array(
						'1ST'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_FIRST),
						'ALL'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_ALL),
						'BM'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS),
						'BOARD_OFFSET_0'					=> $board_offset[0],
						'BOARD_OFFSET_1'					=> $board_offset[1],
						'BOARD_OFFSET_2'					=> $board_offset[2],
						'BOARD_OFFSET_3'					=> $board_offset[3],
						'BOARD_OFFSET_4'					=> $board_offset[4],
						'BOARD_OFFSET_5'					=> $board_offset[5],
						'BOARD_OFFSET_6'					=> $board_offset[6],
						'BOARD_OFFSET_7'					=> $board_offset[7],
						'BOARD_OFFSET_8'					=> $board_offset[8],
						'BOARD_OFFSET_9'					=> $board_offset[9],
						'BOARD_OFFSET_10'					=> $board_offset[10],
						'BOARD_OFFSET_11'					=> $board_offset[11],
						'BOARD_OFFSET_12'					=> $board_offset[12],
						'BOARD_OFFSET_13'					=> $board_offset[13],
						'BOARD_OFFSET_14'					=> $board_offset[14],
						'BOARD_OFFSET_15'					=> $board_offset[15],
						'BOARD_OFFSET_16'					=> $board_offset[16],
						'BOARD_OFFSET_17'					=> $board_offset[17],
						'BOARD_OFFSET_18'					=> $board_offset[18],
						'BOARD_OFFSET_19'					=> $board_offset[19],
						'BOARD_OFFSET_20'					=> $board_offset[20],
						'BOARD_OFFSET_21'					=> $board_offset[21],
						'BOARD_OFFSET_22'					=> $board_offset[22],
						'BOARD_OFFSET_23'					=> $board_offset[23],
						'DIGEST_MAX_SIZE' 					=> $row['user_digest_max_display_words'],
						'L_DIGEST_CHANGE_SUBSCRIPTION' 		=> ($row['user_digest_type'] != 'NONE') ? $user->lang('DIGESTS_UNSUBSCRIBE') : $user->lang('DIGESTS_SUBSCRIBE_LITERAL'),
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
						'S_DIGEST_SEND_HOUR_0_CHECKED'		=> ($send_hour_board == 0),
						'S_DIGEST_SEND_HOUR_1_CHECKED'		=> ($send_hour_board == 1),
						'S_DIGEST_SEND_HOUR_2_CHECKED'		=> ($send_hour_board == 2),
						'S_DIGEST_SEND_HOUR_3_CHECKED'		=> ($send_hour_board == 3),
						'S_DIGEST_SEND_HOUR_4_CHECKED'		=> ($send_hour_board == 4),
						'S_DIGEST_SEND_HOUR_5_CHECKED'		=> ($send_hour_board == 5),
						'S_DIGEST_SEND_HOUR_6_CHECKED'		=> ($send_hour_board == 6),
						'S_DIGEST_SEND_HOUR_7_CHECKED'		=> ($send_hour_board == 7),
						'S_DIGEST_SEND_HOUR_8_CHECKED'		=> ($send_hour_board == 8),
						'S_DIGEST_SEND_HOUR_9_CHECKED'		=> ($send_hour_board == 9),
						'S_DIGEST_SEND_HOUR_10_CHECKED'		=> ($send_hour_board == 10),
						'S_DIGEST_SEND_HOUR_11_CHECKED'		=> ($send_hour_board == 11),
						'S_DIGEST_SEND_HOUR_12_CHECKED'		=> ($send_hour_board == 12),
						'S_DIGEST_SEND_HOUR_13_CHECKED'		=> ($send_hour_board == 13),
						'S_DIGEST_SEND_HOUR_14_CHECKED'		=> ($send_hour_board == 14),
						'S_DIGEST_SEND_HOUR_15_CHECKED'		=> ($send_hour_board == 15),
						'S_DIGEST_SEND_HOUR_16_CHECKED'		=> ($send_hour_board == 16),
						'S_DIGEST_SEND_HOUR_17_CHECKED'		=> ($send_hour_board == 17),
						'S_DIGEST_SEND_HOUR_18_CHECKED'		=> ($send_hour_board == 18),
						'S_DIGEST_SEND_HOUR_19_CHECKED'		=> ($send_hour_board == 19),
						'S_DIGEST_SEND_HOUR_20_CHECKED'		=> ($send_hour_board == 20),
						'S_DIGEST_SEND_HOUR_21_CHECKED'		=> ($send_hour_board == 21),
						'S_DIGEST_SEND_HOUR_22_CHECKED'		=> ($send_hour_board == 22),
						'S_DIGEST_SEND_HOUR_23_CHECKED'		=> ($send_hour_board == 23),
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
						'USER_DIGEST_LAST_SENT'				=> ($row['user_digest_last_sent'] == 0) ? $user->lang('DIGESTS_NO_DIGESTS_SENT') : date($config['default_dateformat'], $row['user_digest_last_sent'] + (60 * 60 * ($config['phpbbservices_digests_time_zone'] - (date('O')/100)))),
						'USER_DIGEST_MAX_DISPLAY_WORDS'		=> $row['user_digest_max_display_words'],
						'USER_DIGEST_MAX_POSTS'				=> $row['user_digest_max_posts'],
						'USER_DIGEST_MIN_WORDS'				=> $row['user_digest_min_words'],
						'USER_DIGEST_TYPE'					=> $digest_type,
						'USER_EMAIL'						=> $row['user_email'],
						'USER_ID'							=> $row['user_id'],
						'USER_LAST_VISIT'					=> ($row['user_lastvisit'] == 0) ? $user->lang('DIGESTS_NEVER_VISITED') : date($config['default_dateformat'], $row['user_lastvisit'] + (60 * 60 * ($config['phpbbservices_digests_time_zone'] - (date('O')/100)))),
						'USER_SUBSCRIBE_UNSUBSCRIBE_FLAG'	=> ($row['user_digest_type'] != 'NONE') ? 'u' : 's')
					);

					// Now let's get this user's forum permissions. Note that non-registered, robots etc. get a list of public forums
					// with read permissions.
					
					unset($allowed_forums, $forum_array, $parent_stack);
					
					$forum_array = $auth->acl_raw_data_single_user($row['user_id']);
					
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
						
							'WHERE'		=> $db->sql_in_set('forum_id', $allowed_forums) . ' AND forum_type <> ' . FORUM_LINK . "
									AND forum_password = ''",
						
							'ORDER_BY'	=> 'left_id ASC',
						);
						
						$sql2 = $db->sql_build_query('SELECT', $sql_array);

						$result2 = $db->sql_query($sql2);
						
						$current_level = 0;			// How deeply nested are we at the moment
						$parent_stack = array();	// Holds a stack showing the current parent_id of the forum
						$parent_stack[] = 0;		// 0, the first value in the stack, represents the <div_0> element, a container holding all the categories and forums in the template
						
						while ($row2 = $db->sql_fetchrow($result2))
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
										$template->assign_block_vars('digests_edit_subscribers.forums', array( 
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
									$template->assign_block_vars('digests_edit_subscribers.forums', array( 
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
							
							$template->assign_block_vars('digests_edit_subscribers.forums', array(
								'FORUM_LABEL' 			=> $row2['forum_name'],
								'FORUM_NAME' 			=> 'elt_' . (int) $row2['forum_id'] . '_' . (int) $row2['parent_id'],
								'S_FORUM_SUBSCRIBED' 	=> $check,
								'S_IS_FORUM' 			=> !($row2['forum_type'] == FORUM_CAT),
								'S_PRINT' 				=> true,
								)
							);
							
						}
					
						$db->sql_freeresult($result2);
					
						// Now out of the loop, it is important to remember to close any open <div> tags. Typically there is at least one.
						while ((int) $row2['parent_id'] != (int) end($parent_stack))
						{
							array_pop($parent_stack);
							$current_level--;
							// Need to close the <div> tag
							$template->assign_block_vars('digests_edit_subscribers.forums', array( 
								'S_DIV_CLOSE' 	=> true,
								'S_DIV_OPEN' 	=> false,
								'S_PRINT' 		=> false,
								)
							);
						}
						
					}
					
				}
		
				$db->sql_freeresult($result); // Query be gone!
					
			break;

			case 'digests_balance_load':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_BALANCE_LOAD',
					'vars'	=> array(
					)
				);

				// Translate time zone information
				$template->assign_vars(array(
					'L_DIGESTS_HOUR_SENT'               		=> $user->lang('DIGESTS_HOUR_SENT', $config['phpbbservices_digests_time_zone']),
					'S_BALANCE_LOAD'							=> true,
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
				
				$sql = $db->sql_build_query('SELECT', $sql_array);

				$result = $db->sql_query($sql);
				$rowset = $db->sql_fetchrowset($result);
				
				for($i=0;$i<24;$i++)
				{
				
					// Convert digest hour to GMT
					$hour_gmt = floor($i - $config['phpbbservices_digests_time_zone']);
					
					if ($hour_gmt < 0)
					{
						$hour_gmt = $hour_gmt + 24;
					}
					else if ($hour_gmt > 23)
					{
						$hour_gmt = $hour_gmt - 24;
					}
							   
					// If there are digest counts for this GMT hour, show it, otherwise show zero (no digests for this GMT hour)
					$hour_count = 0;
					if (isset($rowset))
					{
						foreach ($rowset as $row)
						{
							if (floor($row['hour']) == $hour_gmt)
							{
								$hour_count = $row['hour_count'];
								break;
							}
						}
					}
					
					$template->assign_block_vars('digests_balance_load', array(
						'HOUR'               => $i,
						'HOUR_COUNT'         => $hour_count,
					));
				
				}				
				$db->sql_freeresult($result); // Query be gone!
			break;

			case 'digests_mass_subscribe_unsubscribe':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE',
					'vars'	=> array(
						'legend1'								=> '',
						'phpbbservices_digests_enable_subscribe_unsubscribe'	=> array('lang' => 'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_subscribe_all'					=> array('lang' => 'DIGESTS_SUBSCRIBE_ALL',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_include_admins'					=> array('lang' => 'DIGESTS_INCLUDE_ADMINS',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
					)
				);
			break;

			case 'digests_reset_cron_run_time':
				$display_vars = array(
					'title'	=> 'ACP_DIGESTS_RESET_CRON_RUN_TIME',
					'vars'	=> array(
						'legend1'								=> '',
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

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? $request->variable('config', array('' => ''), true) : $this->new_config;
		$error = array();

		// We validate the complete config if wished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang('FORM_INVALID');
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
				$config->set($config_name, $config_value);
			}
		}

		if ($submit && $mode == 'digests_edit_subscribers')
		{
			
			// The "selected" input control indicates whether to do mass actions or not. With mass actions only the select control and
			// the mark checkboxes matter. Other controls are ignored.
			$selected = $request->variable('selected', 'IGNORE', true);
			$mass_action = ($selected == 'IGNORE') ? false : true;
			$use_defaults = false;
			$use_defaults_pass = false;
			unset($sql_ary, $sql_ary2);
			
			// Get the entire request variables as an array for parsing
			unset($requests_vars);
			$request_vars = $request->get_super_global(\phpbb\request\request_interface::POST);
			
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
								SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
								WHERE user_id = ' . (int) $current_user_id;
							$db->sql_query($sql);
						}
						
						// If there are any individual forum subscriptions for this user, remove the old ones. 
						$sql = 'DELETE FROM ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
								WHERE user_id = ' . (int) $current_user_id;
						$db->sql_query($sql);
		
						// Now save the individual forum subscriptions, if any
						if (!$mass_action && isset($sql_ary2) && sizeof($sql_ary2) > 0)
						{
							$db->sql_multi_insert($table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE, $sql_ary2);
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
									$sql_ary['user_digest_type'] = $config['phpbbservices_digests_user_digest_type'];
								break;
								
								case constants::DIGESTS_NONE_VALUE;
									// Remove user's subscription (mass action)
									$sql_ary['user_digest_type'] = constants::DIGESTS_NONE_VALUE;
								break;
								
								default:
								break;
							}
							
							// Create a digest subscription using board defaults
							$sql_ary['user_digest_format'] 				= $config['phpbbservices_digests_user_digest_format'];
							$sql_ary['user_digest_show_mine'] 			= ($config['phpbbservices_digests_user_digest_show_mine'] == 1) ? 0 : 1;
							$sql_ary['user_digest_send_on_no_posts'] 	= $config['phpbbservices_digests_user_digest_send_on_no_posts'];
							$sql_ary['user_digest_send_hour_gmt'] 		= ($config['phpbbservices_digests_user_digest_send_hour_gmt'] == -1) ? rand(0,23) : $config['phpbbservices_digests_user_digest_send_hour_gmt'];
							$sql_ary['user_digest_show_pms'] 			= $config['phpbbservices_digests_user_digest_show_pms'];
							$sql_ary['user_digest_max_posts'] 			= $config['phpbbservices_digests_user_digest_max_posts'];
							$sql_ary['user_digest_min_words'] 			= $config['phpbbservices_digests_user_digest_min_words'];
							$sql_ary['user_digest_remove_foes'] 		= $config['phpbbservices_digests_user_digest_remove_foes'];
							$sql_ary['user_digest_sortby'] 				= $config['phpbbservices_digests_user_digest_sortby'];
							$sql_ary['user_digest_max_display_words'] 	= ($config['phpbbservices_digests_user_digest_max_display_words'] == -1) ? 0 : $config['phpbbservices_digests_user_digest_max_display_words'];
							$sql_ary['user_digest_reset_lastvisit'] 	= $config['phpbbservices_digests_user_digest_reset_lastvisit'];
							$sql_ary['user_digest_filter_type'] 		= $config['phpbbservices_digests_user_digest_filter_type'];
							$sql_ary['user_digest_pm_mark_read'] 		= $config['phpbbservices_digests_user_digest_pm_mark_read'];
							$sql_ary['user_digest_new_posts_only'] 		= $config['phpbbservices_digests_user_digest_new_posts_only'];
							$sql_ary['user_digest_no_post_text']		= ($config['phpbbservices_digests_user_digest_max_display_words'] == 0) ? 1 : 0;
							$sql_ary['user_digest_attachments'] 		= $config['phpbbservices_digests_user_digest_attachments'];
							$sql_ary['user_digest_block_images']		= $config['phpbbservices_digests_user_digest_block_images'];
							$sql_ary['user_digest_toc']					= $config['phpbbservices_digests_user_digest_toc'];
						}
						
					}
					
					else	// Process individual subscriptions
					
					{
						
						// We need to get these variables so we can detect if individual forum subscriptions will need to be processed.
						$var = 'user-' . $current_user_id . '-all_forums';
						$all_forums = $request->variable($var, '', true);
						$var = 'user-' . $current_user_id . '-filter_type';
						$filter_type = $request->variable($var, '', true);
						
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
								$sql_ary['user_digest_type'] 				= $config['phpbbservices_digests_user_digest_type'];
								$sql_ary['user_digest_format'] 				= $config['phpbbservices_digests_user_digest_format'];
								$sql_ary['user_digest_show_mine'] 			= ($config['phpbbservices_digests_user_digest_show_mine'] == 1) ? 0 : 1;
								$sql_ary['user_digest_send_on_no_posts'] 	= $config['phpbbservices_digests_user_digest_send_on_no_posts'];
								$sql_ary['user_digest_send_hour_gmt'] 		= ($config['phpbbservices_digests_user_digest_send_hour_gmt'] == -1) ? rand(0,23) : $config['phpbbservices_digests_user_digest_send_hour_gmt'];
								$sql_ary['user_digest_show_pms'] 			= $config['phpbbservices_digests_user_digest_show_pms'];
								$sql_ary['user_digest_max_posts'] 			= $config['phpbbservices_digests_user_digest_max_posts'];
								$sql_ary['user_digest_min_words'] 			= $config['phpbbservices_digests_user_digest_min_words'];
								$sql_ary['user_digest_remove_foes'] 		= $config['phpbbservices_digests_user_digest_remove_foes'];
								$sql_ary['user_digest_sortby'] 				= $config['phpbbservices_digests_user_digest_sortby'];
								$sql_ary['user_digest_max_display_words'] 	= ($config['phpbbservices_digests_user_digest_max_display_words'] == -1) ? 0 : $config['phpbbservices_digests_user_digest_max_display_words'];
								$sql_ary['user_digest_reset_lastvisit'] 	= $config['phpbbservices_digests_user_digest_reset_lastvisit'];
								$sql_ary['user_digest_filter_type'] 		= $config['phpbbservices_digests_user_digest_filter_type'];
								$sql_ary['user_digest_pm_mark_read'] 		= $config['phpbbservices_digests_user_digest_pm_mark_read'];
								$sql_ary['user_digest_new_posts_only'] 		= $config['phpbbservices_digests_user_digest_new_posts_only'];
								$sql_ary['user_digest_no_post_text']		= ($config['phpbbservices_digests_user_digest_max_display_words'] == 0) ? 1 : 0;
								$sql_ary['user_digest_attachments'] 		= $config['phpbbservices_digests_user_digest_attachments'];
								$sql_ary['user_digest_block_images']		= $config['phpbbservices_digests_user_digest_block_images'];
								$sql_ary['user_digest_toc']					= $config['phpbbservices_digests_user_digest_toc'];
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
					SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . (int) $current_user_id;
				$db->sql_query($sql);
			}
			
			// If there are any individual forum subscriptions for this user, remove the old ones. 
			if (!is_null($current_user_id))
			{
				$sql = 'DELETE FROM ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
						WHERE user_id = ' . (int) $current_user_id;
				$db->sql_query($sql);
			}

			// Now save the individual forum subscriptions, if any
			if (!$mass_action && isset($sql_ary2) && sizeof($sql_ary2) > 0)
			{
				$db->sql_multi_insert($table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE, $sql_ary2);
			}

			// Also want to save some information to an array to be used for sending emails to affected users.
			$digest_notify_list[] = $current_user_id;

			// Notify users whose subscriptions were changed
			if ($config['phpbbservices_digests_notify_on_admin_changes'])
			{
				$this->notify_subscribers($digest_notify_list, 'digests_subscription_edited');
			}
				
			$message = $user->lang('CONFIG_UPDATED');
				
		}

		if ($submit && $mode == 'digests_balance_load')
		{

			$sql_array = array(
				'SELECT'	=> 'COUNT(user_id) AS digests_count',
			
				'FROM'		=> array(
					USERS_TABLE		=> 'u',
				),
			
				'WHERE'		=> "user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' 
					AND user_type <> " . USER_IGNORE,
			);
			
			$sql = $db->sql_build_query('SELECT', $sql_array);

			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			
			// Determine the average number of subscribers per hour. We need to assume at least one subscriber per hour to avoid 
			// resetting user's preferred digest time unnecessarily. If the average is 3 per hour, the first 3 already subscribed 
			// will not have their digest arrival time changed.
			
			$avg_subscribers_per_hour = max(floor($row['digests_count']/24), 1);
			
			$db->sql_freeresult($result);
			
			// Get oversubscribed hours, place in an array

			$sql_array = array(
				'SELECT'	=> 'user_digest_send_hour_gmt AS hour, COUNT(user_id) AS hour_count',
			
				'FROM'		=> array(
					USERS_TABLE		=> 'u',
				),
			
				'WHERE'		=> "user_digest_type <> 'NONE' AND user_type <> " . USER_IGNORE,
			
				'GROUP_BY'	=> 'user_digest_send_hour_gmt',
				
				'HAVING'	=> 'COUNT(user_digest_send_hour_gmt) > ' . (int) $avg_subscribers_per_hour,
				
				'ORDER_BY'	=> '1',
			);
			
			$sql = $db->sql_build_query('SELECT', $sql_array);

			$result = $db->sql_query($sql);
			$rowset = $db->sql_fetchrowset($result);
			$oversubscribed_hours = array();
			foreach ($rowset as $row)
			{
				$oversubscribed_hours[] = $row['hour'];
			}
			$db->sql_freeresult($result);
			
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
				
					'WHERE'		=> "user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' AND user_type <> " . USER_IGNORE . '
						AND ' . $db->sql_in_set('user_digest_send_hour_gmt', $oversubscribed_hours),
				
					'ORDER_BY'	=> '1, 2',
				);
				
				$sql = $db->sql_build_query('SELECT', $sql_array);

				$result = $db->sql_query_limit($sql, 100000, $avg_subscribers_per_hour - 1); // Result sets start with array indexed at zero
				$rowset = $db->sql_fetchrowset($result);

				$current_hour = -1;
				$counted_for_this_hour= 0;

				// Finally, change the digest send hour for these subscribers to a random hour between 0 and 24.
				foreach ($rowset as $row)
				{

					$digest_notify_list[] = $row['user_id'];

					if ($current_hour <> $row['user_digest_send_hour_gmt'])
					{
						$current_hour = $row['user_digest_send_hour_gmt'];
						$counted_for_this_hour = 0;
					}
					$counted_for_this_hour++;
					if ($counted_for_this_hour > $avg_subscribers_per_hour)
					{
						$sql_ary = array(
							'user_digest_send_hour_gmt'		=> rand(0, 23),
						);
						
						$sql2 = 'UPDATE ' . USERS_TABLE . '
							SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . (int)  $row['user_id'];
							
						$db->sql_query($sql2);
						$rebalanced++;
					}
				}
				
				$db->sql_freeresult($result);
			
				// Notify users whose subscriptions were changed
				if ($config['phpbbservices_digests_notify_on_admin_changes'])
				{
					$this->notify_subscribers($digest_notify_list, 'digests_subscription_edited');
				}
				
			}
			
			$message = $user->lang('DIGESTS_REBALANCED', $rebalanced);

		}

		if ($submit && $mode == 'digests_mass_subscribe_unsubscribe')
		{
			
			// Did the admin explicitly request a mass subscription or unsubscription action?
			if ($config['phpbbservices_digests_enable_subscribe_unsubscribe'])
			{
				
				// Determine which user types are to be updated
				$user_types = array(USER_NORMAL);
				if ($config['phpbbservices_digests_include_admins'])
				{
					$user_types[] = USER_FOUNDER;
				}
				
				// If doing a mass subscription, we don't want to mess up digest subscriptions already in place, so we need to create a snippet of SQL.
				// If doing a mass unsubscribe, all qualified subscriptions are removed. Note however that except for the digest type, all other settings 
				// are retained.
				$sql_qualifier = ($config['phpbbservices_digests_subscribe_all']) ? " AND user_digest_type = '" . constants::DIGESTS_NONE_VALUE . "'": " AND user_digest_type != '" . constants::DIGESTS_NONE_VALUE . "'";
				$digest_notify_list = array();

				if ($config['phpbbservices_digests_notify_on_admin_changes'])
				{

					$sql_array = array(
						'SELECT'	=> 'user_id',
					
						'FROM'		=> array(
							USERS_TABLE	=> 'u',
						),
					
						'WHERE'		=> $db->sql_in_set('user_type', $user_types) . $sql_qualifier,
					);
					
					$sql = $db->sql_build_query('SELECT', $sql_array);

					$result = $db->sql_query($sql);
					$rowset = $db->sql_fetchrowset($result);

					foreach ($rowset as $row)
					{
						$digest_notify_list[] = $row['user_id'];
					}
							
					$db->sql_freeresult($result); // Query be gone!
					
				}
					
				// Set columns in user table to be updated
				$sql_ary = array(
					'user_digest_type' 				=> ($config['phpbbservices_digests_subscribe_all']) ? $config['phpbbservices_digests_user_digest_type'] : constants::DIGESTS_NONE_VALUE,
					'user_digest_format' 			=> $config['phpbbservices_digests_user_digest_format'],
					'user_digest_show_mine' 		=> ($config['phpbbservices_digests_user_digest_show_mine'] == 1) ? 0 : 1,
					'user_digest_send_on_no_posts' 	=> $config['phpbbservices_digests_user_digest_send_on_no_posts'],
					'user_digest_send_hour_gmt' 	=> ($config['phpbbservices_digests_user_digest_send_hour_gmt'] == -1) ? rand(0,23) : $config['phpbbservices_digests_user_digest_send_hour_gmt'],
					'user_digest_show_pms' 			=> $config['phpbbservices_digests_user_digest_show_pms'],
					'user_digest_max_posts' 		=> $config['phpbbservices_digests_user_digest_max_posts'],
					'user_digest_min_words' 		=> $config['phpbbservices_digests_user_digest_min_words'],
					'user_digest_remove_foes' 		=> $config['phpbbservices_digests_user_digest_remove_foes'],
					'user_digest_sortby' 			=> $config['phpbbservices_digests_user_digest_sortby'],
					'user_digest_max_display_words' => ($config['phpbbservices_digests_user_digest_max_display_words'] == -1) ? 0 : $config['phpbbservices_digests_user_digest_max_display_words'],
					'user_digest_reset_lastvisit' 	=> $config['phpbbservices_digests_user_digest_reset_lastvisit'],
					'user_digest_filter_type' 		=> $config['phpbbservices_digests_user_digest_filter_type'],
					'user_digest_pm_mark_read' 		=> $config['phpbbservices_digests_user_digest_pm_mark_read'],
					'user_digest_new_posts_only' 	=> $config['phpbbservices_digests_user_digest_new_posts_only'],
					'user_digest_no_post_text'		=> ($config['phpbbservices_digests_user_digest_max_display_words'] == 0) ? 1 : 0,
					'user_digest_attachments' 		=> $config['phpbbservices_digests_user_digest_attachments'],
					'user_digest_block_images'		=> $config['phpbbservices_digests_user_digest_block_images'],
					'user_digest_toc'				=> $config['phpbbservices_digests_user_digest_toc'],
				);
					
				$sql = 'UPDATE ' . USERS_TABLE . ' 
						SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE ' . $db->sql_in_set('user_type', $user_types) . $sql_qualifier;
					
				$result = $db->sql_query($sql);
					
				$db->sql_freeresult($result); // Query be gone!
				
				// Notify users or subscription or unsubscription if directed
				if ($config['phpbbservices_digests_notify_on_admin_changes'])
				{
					$this->notify_subscribers($digest_notify_list);
				}
				
				if ($config['phpbbservices_digests_subscribe_all'])
				{
					$message = $user->lang('DIGESTS_ALL_SUBSCRIBED', sizeof($digest_notify_list));
				}
				else
				{
					$message = $user->lang('DIGESTS_ALL_UNSUBSCRIBED', sizeof($digest_notify_list));
				}
				
			}
			else
			{
				// show no update message
				$message = $user->lang('DIGESTS_NO_MASS_ACTION');
			}

		}

		if ($submit && $mode == 'digests_reset_cron_run_time')
		{
			// This allows the digests to go out next time cron.php is run.
			$config->set('phpbbservices_digests_cron_task_last_gc', 0);
			
			// This resets all the date/time stamps for when a digest was last sent to a user.
			$sql_ary = array('user_digest_last_sent' => 0);
			
			$sql = 'UPDATE ' . USERS_TABLE . ' 
				SET ' . $db->sql_build_array('UPDATE', $sql_ary);
			$db->sql_query($sql);
			
			$sql = $db->sql_build_query('UPDATE', $sql_array);
		}

		if ($submit && $mode == 'digests_test')
		{

			define('IN_DIGESTS_TEST', true);
			$continue = true;

			if (!$config['phpbbservices_digests_test'] && !$config['phpbbservices_digests_test_clear_spool'])
			{
				$message = $user->lang('DIGESTS_MAILER_NOT_RUN');
				$continue = false;
			}
			
			if ($continue && $config['phpbbservices_digests_test_clear_spool'])
			{
				
				// Clear the digests cache folder of .txt and .html files, if so instructed
				$all_cleared = true;
				$directory_found = true;
				
				$path = $phpbb_root_path . 'cache/phpbbservices/digests';
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
				else	// Directory not found, which is generally okay. If it's missing it will get recreated.
				{
					$directory_found = false;
				}
					
				if ($config['phpbbservices_digests_enable_log'] && $directory_found)
				{
					if ($all_cleared)
					{
						$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_CONFIG_DIGESTS_CACHE_CLEARED');
					}
					else
					{
						$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_CONFIG_DIGESTS_CLEAR_SPOOL_ERROR');
					}
				}
			
				if (!$all_cleared)
				{
					$message_type = E_USER_WARNING;
					$message = $user->lang('DIGESTS_RUN_TEST_CLEAR_SPOOL_ERROR');
					$continue = false;
				}

			}
			
			if ($continue && $config['phpbbservices_digests_test_time_use'])
			{
				
				// Make sure run date is valid, if a run date was requested.
				$good_date = checkdate($config['phpbbservices_digests_test_month'], $config['phpbbservices_digests_test_day'], $config['phpbbservices_digests_test_year']);
				if (!$good_date)
				{
					$message_type = E_USER_WARNING;
					$message = $user->lang('DIGESTS_ILLOGICAL_DATE');
					$continue = false;
				}

			}
			
			// Get ready to manually mail digests
			if ($continue)
			{
				
				// Get the common include files, to pass the reference to mailer
				$helper = $phpbb_container->get('phpbbservices.digests.common');

				// Create a new template object to pass to the mailer since we don't want to lose the content in this one. (The mailer will overwrite it.)
				$mailer_template = new \phpbb\template\twig\twig($phpbb_path_helper, $config, $user, new \phpbb\template\context(), $phpbb_extension_manager);
				$mailer_template->set_style(array('./ext/phpbbservices/digests/styles', 'styles'));

				// Create a mailer object and call its run method. The logic for sending a digest is embedded in this method, which is normally run as a cron task.
				$mailer = new \phpbbservices\digests\cron\task\digests($config, $request, $user, $db, $phpEx, $phpbb_root_path, $mailer_template, $auth, $table_prefix, $phpbb_log, $helper);
				$success = $mailer->run();
				
				if (!$success)
				{
					$message_type = E_USER_WARNING;
					$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_CONFIG_DIGESTS_MAILER_RAN_WITH_ERROR');
					$message = $user->lang('DIGESTS_MAILER_RAN_WITH_ERROR');
				}
				else if ($config['phpbbservices_digests_test_spool'])
				{
					$message = $user->lang('DIGESTS_MAILER_SPOOLED');
				}
				else
				{
					$message = $user->lang('DIGESTS_MAILER_RAN_SUCCESSFULLY');
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
				$message = $user->lang('CONFIG_UPDATED');
			}
			if ($mode !== 'digests_test')
			{
				$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_CONFIG_' . strtoupper($mode));
			}
			trigger_error($message . adm_back_link($this->u_action), $message_type);
				
		}

		$this->tpl_name = 'acp_digests';
		$this->page_title = $display_vars['title'];

		$template->assign_vars(array(
			'ERROR_MSG'			=> (is_array($error) ? implode('<br />', $error) : $error),
			'L_MESSAGE'			=> $error,
			'L_TITLE'			=> $user->lang($display_vars['title']),
			'L_TITLE_EXPLAIN'	=> $user->lang($display_vars['title'] . '_EXPLAIN'),
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
				$template->assign_block_vars('options', array(
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang($vars) : $vars,
					'S_LEGEND'		=> true)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang($vars['lang_explain']) : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang($vars['lang'] . '_EXPLAIN') : '';
			}

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'CONTENT'		=> $content,
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang($vars['lang']) : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				)
			);

			unset($display_vars['vars'][$config_key]);
		}

	}

	function dow_select()
	{
		global $config, $user;
		
		$dow_options = '';
		$index = 0;
		foreach ($user->lang['DIGESTS_WEEKDAY'] as $key => $value)
		{
			$selected = ($index == $config['phpbbservices_digests_weekly_digest_day']) ? ' selected="selected"' : '';
			$dow_options .= '<option value="' . $index . '"' . $selected . '>' . $value . '</option>';
			$index++;
		}
		
		return $dow_options;
	}

	function digest_type_select()
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_DAILY_VALUE) ? ' selected="selected"' : '';
		$digest_types = '<option value="' . constants::DIGESTS_DAILY_VALUE . '"' . $selected. '>' . $user->lang('DIGESTS_DAILY') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_WEEKLY_VALUE) ? ' selected="selected"' : '';
		$digest_types .= '<option value="' . constants::DIGESTS_WEEKLY_VALUE . '"' . $selected . '>' . $user->lang('DIGESTS_WEEKLY') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_MONTHLY_VALUE) ? ' selected="selected"' : '';
		$digest_types .= '<option value="' . constants::DIGESTS_MONTHLY_VALUE . '"' . $selected . '>' . $user->lang('DIGESTS_MONTHLY') . '</option>';
		
		return $digest_types;
	}

	function digest_style_select()
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_HTML_VALUE) ? ' selected="selected"' : '';
		$digest_styles = '<option value="' . constants::DIGESTS_HTML_VALUE . '"' . $selected . '>' . $user->lang('DIGESTS_FORMAT_HTML') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_HTML_CLASSIC_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_HTML_CLASSIC_VALUE . '"' . $selected . '>' . $user->lang('DIGESTS_FORMAT_HTML_CLASSIC') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_PLAIN_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_PLAIN_VALUE . '"' . $selected . '>' . $user->lang('DIGESTS_FORMAT_PLAIN') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_PLAIN_CLASSIC_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_PLAIN_CLASSIC_VALUE . '"' . $selected . '>' . $user->lang('DIGESTS_FORMAT_PLAIN_CLASSIC') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_TEXT_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_TEXT_VALUE . '"' . $selected  . '>' . $user->lang('DIGESTS_FORMAT_TEXT') . '</option>';
		
		return $digest_styles;
	} 

	function digest_send_hour_gmt()
	{
		global $config, $user;
		
		$digest_send_hour_gmt = '';
		
		// Populate the Hour Sent select control
		for($i=-1;$i<24;$i++)
		{
			$selected = ($i == $config['phpbbservices_digests_user_digest_send_hour_gmt']) ? ' selected="selected"' : '';
			$display_text = ($i == -1) ? $user->lang('DIGESTS_RANDOM_HOUR') : $i;
			$digest_send_hour_gmt .= '<option value="' . $i . '"' . $selected . '>' . $display_text . '</option>';
		}
		
		
		return $digest_send_hour_gmt;
	} 

	function digest_filter_type ()
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_ALL) ? ' selected="selected"' : '';
		$digest_filter_types = '<option value="' . constants::DIGESTS_ALL . '"' . $selected. '>' . $user->lang('DIGESTS_ALL_FORUMS') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_FIRST) ? ' selected="selected"' : '';
		$digest_filter_types .= '<option value="' . constants::DIGESTS_FIRST . '"' . $selected . '>' . $user->lang('DIGESTS_POSTS_TYPE_FIRST') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS) ? ' selected="selected"' : '';
		$digest_filter_types .= '<option value="' . constants::DIGESTS_BOOKMARKS . '"' . $selected. '>' . $user->lang('DIGESTS_USE_BOOKMARKS') . '</option>';
		
		return $digest_filter_types;
	} 

	function digest_post_sort_order ()
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_BOARD) ? ' selected="selected"' : '';
		$digest_sort_order = '<option value="' . constants::DIGESTS_SORTBY_BOARD . '"' . $selected . '>' . $user->lang('DIGESTS_SORT_USER_ORDER') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD . '"' . $selected .  '>' . $user->lang('DIGESTS_SORT_FORUM_TOPIC') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD_DESC . '"' . $selected. '>' . $user->lang('DIGESTS_SORT_FORUM_TOPIC_DESC') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE . '"' . $selected. '>' . $user->lang('DIGESTS_SORT_POST_DATE') . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE_DESC . '"' . $selected. '>' . $user->lang('DIGESTS_SORT_POST_DATE_DESC') . '</option>';
		
		return $digest_sort_order;
	}
	
	function notify_subscribers ($digest_notify_list, $email_template = '')
	{
		
		// This function parses $digest_notify_list, an array of user_ids that represent users that had their digest subscriptions changed, and sends them an email
		// letting them know an action has occurred.
		
		global $phpbb_root_path, $phpEx, $config, $user, $db, $phpbb_log;
		
		$emails_sent = 0;
		
		if (isset($digest_notify_list) && (sizeof($digest_notify_list) > 0))
		{
			
			if (!class_exists('messenger'))
			{
				include($phpbb_root_path . 'includes/functions_messenger.' . $phpEx); // Used to send emails
			}
			
			$sql_array = array(
				'SELECT'	=> 'username, user_email, user_lang, user_digest_type, user_digest_format',
			
				'FROM'		=> array(
					USERS_TABLE	=> 'u',
				),
			
				'WHERE'		=> $db->sql_in_set('user_id', $digest_notify_list),
			);
			
			$sql = $db->sql_build_query('SELECT', $sql_array);
			
			$result = $db->sql_query($sql);
			$rowset = $db->sql_fetchrowset($result);
			
			foreach ($rowset as $row)
			{
				
				// E-mail setup
				$messenger = new \messenger();
				
				switch ($email_template)
				{
					case 'digests_subscription_edited':
						$digest_notify_template = $email_template;
						$digest_email_subject = $user->lang('DIGESTS_SUBSCRIBE_EDITED');
					break;
					
					default:
						// Mass subscribe/unsubscribe
						$digest_notify_template = ($config['phpbbservices_digests_subscribe_all']) ? 'digests_subscribe' : 'digests_unsubscribe';
						$digest_email_subject = ($config['phpbbservices_digests_subscribe_all']) ? $user->lang('DIGESTS_SUBSCRIBE_SUBJECT') : $user->lang('DIGESTS_UNSUBSCRIBE_SUBJECT');
					break;
				}
				
				// Set up associations between digest types as constants and their language equivalents
				switch ($row['user_digest_type'])
				{
					case constants::DIGESTS_DAILY_VALUE:
						$digest_type_text = strtolower($user->lang('DIGESTS_DAILY'));
					break;
					
					case constants::DIGESTS_WEEKLY_VALUE:
						$digest_type_text = strtolower($user->lang('DIGESTS_WEEKLY'));
					break;
					
					case constants::DIGESTS_MONTHLY_VALUE:
						$digest_type_text = strtolower($user->lang('DIGESTS_MONTHLY'));
					break;
					
					case constants::DIGESTS_NONE_VALUE:
						$digest_type_text = strtolower($user->lang('DIGESTS_NONE'));
					break;

					default:
						$digest_type_text = strtolower($user->lang('DIGESTS_DAILY'));
					break;
				}
				
				// Set up associations between digest formats as constants and their language equivalents
				switch ($row['user_digest_format'])
				{
					case constants::DIGESTS_HTML_VALUE:
						$digest_format_text = $user->lang('DIGESTS_FORMAT_HTML');
					break;
					
					case constants::DIGESTS_HTML_CLASSIC_VALUE:
						$digest_format_text = $user->lang('DIGESTS_FORMAT_HTML_CLASSIC');
					break;
					
					case constants::DIGESTS_PLAIN_VALUE:
						$digest_format_text = $user->lang('DIGESTS_FORMAT_PLAIN');
					break;
					
					case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
						$digest_format_text = $user->lang('DIGESTS_FORMAT_PLAIN_CLASSIC');
					break;
					
					case constants::DIGESTS_TEXT_VALUE:
						$digest_format_text = strtolower($user->lang('DIGESTS_FORMAT_TEXT'));
					break;
					
					default:
						$digest_format_text = $user->lang('DIGESTS_FORMAT_HTML');
					break;
				}
					
				$messenger->template('@phpbbservices_digests/' . $digest_notify_template, $row['user_lang']);
				$messenger->to($row['user_email']);
				
				$from_addr = ($config['phpbbservices_digests_from_email_address'] == '') ? $config['board_email'] : $config['phpbbservices_digests_from_email_address'];
				$from_name = ($config['phpbbservices_digests_from_email_name'] == '') ? $config['board_contact'] : $config['phpbbservices_digests_from_email_name'];
					
				// SMTP delivery must strip text names due to likely bug in messenger class
				if ($config['smtp_delivery'])
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
					'DIGESTS_UCP_LINK'		=> generate_board_url() . '/' . 'ucp.' . $phpEx,
					'FORUM_NAME'			=> $config['sitename'],
					'USERNAME'				=> $row['username'],
					)
				);
				
				$mail_sent = $messenger->send(NOTIFY_EMAIL, false);
				
				if (!$mail_sent)
				{
					$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_CONFIG_DIGESTS_NOTIFICATION_ERROR', false, array($row['user_email']));
				}
				else
				{
					$phpbb_log->add('admin', $user->data['user_id'], $user->ip, 'LOG_CONFIG_DIGESTS_NOTIFICATION_SENT', false, array($row['user_email'], $row['username']));
					$emails_sent++;
				}
				
				$messenger->reset();
				
			}
	
			$db->sql_freeresult($result); // Query be gone!
			
		}
		
		return $emails_sent;
	
	}
	
}
