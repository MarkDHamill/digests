<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\ucp;

use phpbbservices\digests\constants\constants;

if (!defined('IN_PHPBB'))
{
	exit;
}

class main_module
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)
	{
		global $db, $user, $auth, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		global $cache, $phpbb_container, $phpbb_dispatcher, $table_prefix, $request;
		
		$user->add_lang_ext('phpbbservices/digests', array('info_acp_common','common'));

		$action	= request_var('action', '');
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
					'title'	=> 'UCP_DIGESTS_GENERAL_SETTINGS',
					'vars'	=> array(
						'legend1'								=> '',
						//'phpbbservices_digests_enabled'					=> array('lang' => 'DIGESTS_ENABLED',							'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_show_output'					=> array('lang' => 'DIGESTS_SHOW_OUTPUT',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_show_email'					=> array('lang' => 'DIGESTS_SHOW_EMAIL',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_enable_log'					=> array('lang' => 'DIGESTS_ENABLE_LOG',							'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_enable_auto_subscriptions'	=> array('lang' => 'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_registration_field'			=> array('lang' => 'DIGESTS_REGISTRATION_FIELD',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_block_images'				=> array('lang' => 'DIGESTS_BLOCK_IMAGES',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_weekly_digest_day'			=> array('lang' => 'DIGESTS_WEEKLY_DIGESTS_DAY',					'validate' => 'int:0:6',	'type' => 'select', 'method' => 'dow_select', 'explain' => false),
						'phpbbservices_digests_max_items'					=> array('lang' => 'DIGESTS_MAX_ITEMS',							'validate' => 'int:0',	'type' => 'text:5:5', 'explain' => true),
						'phpbbservices_digests_enable_custom_stylesheets'	=> array('lang' => 'DIGESTS_ENABLE_CUSTOM_STYLESHEET',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_custom_stylesheet_path'		=> array('lang' => 'DIGESTS_CUSTOM_STYLESHEET_PATH',				'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_require_key'					=> array('lang' => 'DIGESTS_REQUIRE_KEY',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_key_value'					=> array('lang' => 'DIGESTS_KEY_VALUE',							'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_override_queue'				=> array('lang' => 'DIGESTS_OVERRIDE_QUEUE',						'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_from_email_address'			=> array('lang' => 'DIGESTS_FROM_EMAIL_ADDRESS',					'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_from_email_name'				=> array('lang' => 'DIGESTS_FROM_EMAIL_NAME',					'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_reply_to_email_address'		=> array('lang' => 'DIGESTS_REPLY_TO_EMAIL_ADDRESS',				'validate' => 'string',	'type' => 'text:40:255', 'explain' => true),
						'phpbbservices_digests_users_per_page'				=> array('lang' => 'DIGESTS_USERS_PER_PAGE',						'validate' => 'int:0',	'type' => 'text:4:4', 'explain' => true),
						'phpbbservices_digests_include_forums'				=> array('lang' => 'DIGESTS_INCLUDE_FORUMS',						'validate' => 'string',	'type' => 'text:15:255', 'explain' => true),
						'phpbbservices_digests_exclude_forums'				=> array('lang' => 'DIGESTS_EXCLUDE_FORUMS',						'validate' => 'string',	'type' => 'text:15:255', 'explain' => true),
						'phpbbservices_digests_time_zone'					=> array('lang' => 'DIGESTS_TIME_ZONE',							'validate' => 'int:-12:12',	'type' => 'text:5:5', 'explain' => true),
					)
				);
			break;
				
			case 'digests_user_defaults':
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_USER_DEFAULT_SETTINGS',
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
					'title'	=> 'UCP_DIGESTS_EDIT_SUBSCRIBERS',
					'vars'	=> array(
						'legend1'											=> '',
					)
				);

				// Grab some URL parameters
				$member = request_var('member', '');
				$start = request_var('start', 0	);
				$subscribe = request_var('subscribe', 'a');
				$sortby = request_var('sortby', 'u');
				$sortorder = request_var('sortorder', 'a');
				
				// Translate time zone information
				$template->assign_vars(array(
					'L_DIGESTS_HOUR_SENT'               			=> sprintf($user->lang['DIGESTS_HOUR_SENT'], $config['phpbbservices_digests_time_zone']),
					'L_DIGESTS_BASED_ON'							=> sprintf($user->lang['DIGESTS_BASED_ON'], $config['phpbbservices_digests_time_zone']),
					'S_EDIT_SUBSCRIBERS'							=> true,
				));

				// Set up subscription filter				
				$all_selected = $stopped_subscribing = $subscribe_selected = $unsubscribe_selected = '';
				switch ($subscribe)
				{
					case 'u':
						$subscribe_sql = "user_digest_type = 'NONE' AND user_digest_has_unsubscribed = 0 AND ";
						$unsubscribe_selected = ' selected="selected"';
						$context = $user->lang['DIGESTS_UNSUBSCRIBED'];
					break;
					case 't':
						$subscribe_sql = "user_digest_type = 'NONE' AND user_digest_has_unsubscribed = 1 AND";
						$stopped_subscribing = ' selected="selected"';
						$context = $user->lang['DIGESTS_STOPPED_SUBSCRIBING'];
					break;
					case 's':
						$subscribe_sql = "user_digest_type <> 'NONE' AND user_digest_send_hour_gmt >= 0 AND user_digest_send_hour_gmt < 24 AND user_digest_has_unsubscribed = 0 AND";
						$subscribe_selected = ' selected="selected"';
						$context = $user->lang['DIGESTS_SUBSCRIBED'];
					break;
					case 'a':
					default:
						$subscribe_sql = '';
						$all_selected = ' selected="selected"';
						$context = $user->lang['DIGESTS_ALL'];
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
				$member_sql = ($member <> '') ? " username " . $db->sql_like_expression($match_any_chars . $member . $match_any_chars) . " AND " : '';

				// Get the total rows for pagination purposes
				$sql = 'SELECT count(*) AS total_users 
					FROM ' . USERS_TABLE . "
					WHERE $subscribe_sql $member_sql user_type <> " . USER_IGNORE;
				$result = $db->sql_query($sql);
	
				// Get the total users, this is a single row, single field.
				$total_users = $db->sql_fetchfield('total_users');
				
				// Free the result
				$db->sql_freeresult($result);
				
				// Create pagination logic
				$pagination = $phpbb_container->get('pagination');

				$base_url = 'index.php?i=-phpbbservices-digests-acp-main_module&amp;mode=digests_edit_subscribers';
				$base_url = append_sid($base_url);	
				$pagination->generate_template_pagination($base_url, 'pagination', 'start', $total_users, $config['phpbbservices_digests_users_per_page'], $start);
								
				// Stealing some code from my Smartfeed mod so I can get a list of forums that a particular user can access
				
				// We need to know which auth_option_id corresponds to the forum read privilege (f_read) and forum list (f_list) privilege.
				$auth_options = array('f_read', 'f_list');
				$sql = 'SELECT auth_option, auth_option_id
						FROM ' . ACL_OPTIONS_TABLE . '
						WHERE ' . $db->sql_in_set('auth_option', $auth_options);
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
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
					'PAGE_NUMBER'       		=> $current_page,
					'PAGINATION'        		=> $total_pages_string,
					'STOPPED_SUBSCRIBING_SELECTED'	=> $stopped_subscribing,
					'SUBSCRIBE_SELECTED'		=> $subscribe_selected,
					'TOTAL_USERS'       		=> ($total_users == 1) ? $user->lang['DIGESTS_LIST_USER'] : sprintf($user->lang['DIGESTS_LIST_USERS'], $total_users),
					'UNSUBSCRIBE_SELECTED'		=> $unsubscribe_selected,
					'USERNAME_SELECTED'			=> $username_selected,
				));
	
				$sql = 'SELECT *, CASE
					WHEN user_digest_send_hour_gmt + ' . ($config['board_timezone'] + $config['board_dst']) . ' >= 24 THEN
						 user_digest_send_hour_gmt + ' . ($config['board_timezone'] + $config['board_dst']) . ' - 24  
					WHEN user_digest_send_hour_gmt + ' . ($config['board_timezone'] + $config['board_dst']) . ' < 0 THEN
						 user_digest_send_hour_gmt + ' . ($config['board_timezone'] + $config['board_dst']) . ' + 24 
					ELSE user_digest_send_hour_gmt + ' . ($config['board_timezone'] + $config['board_dst']) . '
					END AS send_hour_board
					FROM ' . USERS_TABLE . "
					WHERE $subscribe_sql $member_sql user_type <> " . USER_IGNORE . "
					ORDER BY " . sprintf($sort_by_sql, $order_by_sql, $order_by_sql);
				$result = $db->sql_query_limit($sql, $config['phpbbservices_digests_users_per_page'], $start);
	
				while ($row = $db->sql_fetchrow($result))
				{
					
					// Make some translations into something more readable
					switch($row['user_digest_type'])
					{
						case 'DAY':
							$digest_type = $user->lang['DIGESTS_DAILY'];
						break;
						case 'WEEK':
							$digest_type = $user->lang['DIGESTS_WEEKLY'];
						break;
						case 'MNTH':
							$digest_type = $user->lang['DIGESTS_MONTHLY'];
						break;
						default:
							$digest_type = $user->lang['DIGESTS_UNKNOWN'];
						break;
					}
					
					switch($row['user_digest_format'])
					{
						case constants::DIGESTS_HTML_VALUE:
							$digest_format = $user->lang['DIGESTS_FORMAT_HTML'];
						break;
						case constants::DIGESTS_HTML_CLASSIC_VALUE:
							$digest_format = $user->lang['DIGESTS_FORMAT_HTML_CLASSIC'];
						break;
						case constants::DIGESTS_PLAIN_VALUE:
							$digest_format = $user->lang['DIGESTS_FORMAT_PLAIN'];
						break;
						case constants::DIGESTS_PLAIN_CLASSIC_VALUE:
							$digest_format = $user->lang['DIGESTS_FORMAT_PLAIN_CLASSIC'];
						break;
						case constants::DIGESTS_TEXT_VALUE:
							$digest_format = $user->lang['DIGESTS_FORMAT_TEXT'];
						break;
						default:
							$digest_format = $user->lang['DIGESTS_UNKNOWN'];
						break;
					}
					
					// Calculate a digest send hour in board time
					$send_hour_board = str_replace('.',':', floor($row['user_digest_send_hour_gmt']) + $config['board_timezone'] + $config['board_dst']);
					if ($send_hour_board >= 24)
					{
						$send_hour_board = $send_hour_board - 24;
					}
					else if ($send_hour_board < 0)
					{
						$send_hour_board = $send_hour_board + 24;
					}
					
					// Create an array of GMT offsets from board time zone
					$gmt_offset = $config['board_timezone'] + $config['board_dst'];
					for($i=0;$i<24;$i++)
					{
						if (($i - $gmt_offset) < 0)
						{
							$board_to_gmt[$i] = $i - $gmt_offset + 24;
						}
						else if (($i - $gmt_offset) > 23)
						{
							$board_to_gmt[$i] = $i - $gmt_offset - 24;
						}
						else
						{
							$board_to_gmt[$i] = $i - $gmt_offset;
						}
					}

					// Get current subscribed forums for this user, if any. If none, all allowed forums are assumed
					$sql2 = 'SELECT forum_id 
							FROM ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
							WHERE user_id = ' . (int) $row['user_id'];
					$result2 = $db->sql_query($sql2);
					$subscribed_forums = $db->sql_fetchrowset($result2);
					$db->sql_freeresult();

					$all_by_default = (sizeof($subscribed_forums) == 0) ? true : false;
					
					$template->assign_block_vars('digests_edit_subscribers', array(
						'1ST'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_FIRST),
						'ALL'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_ALL),
						'BM'								=> ($row['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS),
						'BOARD_TO_GMT_0'					=> $board_to_gmt[0],
						'BOARD_TO_GMT_1'					=> $board_to_gmt[1],
						'BOARD_TO_GMT_2'					=> $board_to_gmt[2],
						'BOARD_TO_GMT_3'					=> $board_to_gmt[3],
						'BOARD_TO_GMT_4'					=> $board_to_gmt[4],
						'BOARD_TO_GMT_5'					=> $board_to_gmt[5],
						'BOARD_TO_GMT_6'					=> $board_to_gmt[6],
						'BOARD_TO_GMT_7'					=> $board_to_gmt[7],
						'BOARD_TO_GMT_8'					=> $board_to_gmt[8],
						'BOARD_TO_GMT_9'					=> $board_to_gmt[9],
						'BOARD_TO_GMT_10'					=> $board_to_gmt[10],
						'BOARD_TO_GMT_11'					=> $board_to_gmt[11],
						'BOARD_TO_GMT_12'					=> $board_to_gmt[12],
						'BOARD_TO_GMT_13'					=> $board_to_gmt[13],
						'BOARD_TO_GMT_14'					=> $board_to_gmt[14],
						'BOARD_TO_GMT_15'					=> $board_to_gmt[15],
						'BOARD_TO_GMT_16'					=> $board_to_gmt[16],
						'BOARD_TO_GMT_17'					=> $board_to_gmt[17],
						'BOARD_TO_GMT_18'					=> $board_to_gmt[18],
						'BOARD_TO_GMT_19'					=> $board_to_gmt[19],
						'BOARD_TO_GMT_20'					=> $board_to_gmt[20],
						'BOARD_TO_GMT_21'					=> $board_to_gmt[21],
						'BOARD_TO_GMT_22'					=> $board_to_gmt[22],
						'BOARD_TO_GMT_23'					=> $board_to_gmt[23],
						'DIGEST_MAX_SIZE' 					=> $row['user_digest_max_display_words'],
						'L_DIGEST_CHANGE_SUBSCRIPTION' 		=> ($row['user_digest_type'] != 'NONE') ? $user->lang['DIGESTS_UNSUBSCRIBE'] : $user->lang['DIGESTS_SUBSCRIBE_LITERAL'],
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
						'S_DIGEST_PRIVATE_MESSAGES_IN_DIGEST_NO' 	=> ($user->data['user_digest_show_pms'] == 0),
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
						'USER_DIGEST_LAST_SENT'				=> ($row['user_digest_last_sent'] == 0) ? $user->lang['DIGESTS_NO_DIGESTS_SENT'] : date($config['default_dateformat'], $row['user_digest_last_sent'] + (60 * 60 * $config['phpbbservices_digests_time_zone'])),
						'USER_DIGEST_MAX_DISPLAY_WORDS'		=> $row['user_digest_max_display_words'],
						'USER_DIGEST_MAX_POSTS'				=> $row['user_digest_max_posts'],
						'USER_DIGEST_MIN_WORDS'				=> $row['user_digest_min_words'],
						'USER_DIGEST_TYPE'					=> $digest_type,
						'USER_EMAIL'						=> $row['user_email'],
						'USER_ID'							=> $row['user_id'],
						'USER_LAST_VISIT'					=> ($row['user_lastvisit'] == 0) ? $user->lang['DIGESTS_NEVER_VISITED'] : date($config['default_dateformat'], $row['user_lastvisit'] + (60 * 60 * $config['phpbbservices_digests_time_zone'])),
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

						$sql2 = 'SELECT forum_name, forum_id, parent_id, forum_type
								FROM ' . FORUMS_TABLE . ' 
								WHERE ' . $db->sql_in_set('forum_id', $allowed_forums) . ' AND forum_type <> ' . FORUM_LINK . "
								AND forum_password = ''
								ORDER BY left_id ASC";
						
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
							
							$success = $template->assign_block_vars('digests_edit_subscribers.forums', array( 
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
					'title'	=> 'UCP_DIGESTS_BALANCE_LOAD',
					'vars'	=> array(
					'legend1'								=> '',
					)
				);

				// Translate time zone information
				$template->assign_vars(array(
					'L_DIGESTS_HOUR_SENT'               		=> sprintf($user->lang['DIGESTS_HOUR_SENT'], $config['phpbbservices_digests_time_zone']),
					'S_BALANCE_LOAD'							=> true,
				));

				$sql = 'SELECT user_digest_send_hour_gmt AS hour, count(*) AS hour_count
					FROM ' . USERS_TABLE . "
					WHERE user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' AND user_type <> " . USER_IGNORE . '
					GROUP BY user_digest_send_hour_gmt
					ORDER BY 1';

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
					'title'	=> 'UCP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE',
					'vars'	=> array(
						'legend1'								=> '',
						'phpbbservices_digests_enable_subscribe_unsubscribe'	=> array('lang' => 'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE',	'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_subscribe_all'					=> array('lang' => 'DIGESTS_SUBSCRIBE_ALL',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_include_admins'				=> array('lang' => 'DIGESTS_INCLUDE_ADMINS',				'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						//'phpbbservices_digests_include_inactive'				=> array('lang' => 'DIGESTS_INCLUDE_INACTIVE',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => true),
						'phpbbservices_digests_notify_on_mass_subscribe'		=> array('lang' => 'DIGESTS_NOTIFY_ON_MASS_SUBSCRIBE',			'validate' => 'bool',	'type' => 'radio:yes_no', 'explain' => false),
					)
				);
			break;

			default:
				trigger_error('NO_MODE', E_USER_ERROR);
			break;
				
		}

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = array();

		// We validate the complete config if wished
		validate_config_vars($display_vars['vars'], $cfg_array, $error);

		if ($submit && !check_form_key($form_key))
		{
			$error[] = $user->lang['FORM_INVALID'];
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
				if (strpos($data['type'], 'password') === 0 && $config_value === '********')
				{
					// Do not update password fields if the content is ********,
					// because that is the password replacement we use to not
					// send the password to the output
					continue;
				}
				set_config($config_name, $config_value);

			}
		}

		if ($submit && $mode == 'digests_edit_subscribers')
		{
			
			// Find the value of "selected" so we can set a switch
			$subscribe_mode = request_var('selected', constants::DIGESTS_NONE_VALUE);

			// Get the entire request variables as an array for parsing
			unset($requests_vars);
			$request_vars = $request->get_super_global(\phpbb\request\request_interface::POST);
					
			// Now let's sort them so we process one user at a time
			ksort($request_vars);

			// Set some flags
			$current_user_id = NULL;
			$subscribe_default = false;	// When true, subscribe this user with the default rules
			unset($sql_ary, $sql_ary2);
			
			// Any users to unsubscribe or subscribe? If yes, process these now.
			
			foreach ($request_vars as $name => $value)
			{
				
				// We only care if the request variable starts with "user-". There are likely multiple variables like this for the same user
				// representing all the controls for that user.
				if (substr(htmlspecialchars($name),0,5) == 'user-')
				{
					
					// Parse for the user id, which is embedded in the form field name. Format is user-99-column_name where 99
					// is the user id.
					$delimiter_pos = strpos($name, '-', 5);
					$user_id = substr(htmlspecialchars($name), 5, $delimiter_pos - 5);
					$var_part = substr(htmlspecialchars($name), $delimiter_pos + 1);
					
					if ($current_user_id === NULL)
					{
						$current_user_id = $user_id;
						// We need to set these variables so we can detect if individual forum subscriptions will need to be processed.
						$var = 'user-' . $current_user_id . '-all_forums';
						$all_forums = request_var($var,'');
						$var = 'user-' . $current_user_id . '-filter_type';
						$filter_type = request_var($var,'');
					}
						
					// Associate the database columns with its requested value
					switch (substr($name,$delimiter_pos + 1))
					{
						case 'digest_type':
							// Default digest wanted because row is checked
							if ((($subscribe_mode == constants::DIGESTS_DEFAULT_VALUE) && (array_key_exists('mark-' . $user_id, $request_vars))) ||
								($value == constants::DIGESTS_DEFAULT_VALUE))
							{
								$subscribe_default = true;
							}
							else if (($subscribe_mode == constants::DIGESTS_NONE_VALUE) && (array_key_exists('mark-' . $user_id, $request_vars)))
							{
								$sql_ary['user_digest_type'] = constants::DIGESTS_NONE_VALUE;
							}
							else
							{
								$sql_ary['user_digest_type'] = $value;
							}
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
					}
					
					// Note that if "all_forums" is unchecked and bookmarks is unchecked, there are individual forum subscriptions, so they must be saved.
					if (substr($var_part,0,4) == 'elt_')
					{
						// We should save this forum as an individual forum subscription, but only if the all forums checkbox
						// is not set AND the user should not get posts for bookmarked topics only.

						// This request variable is a checkbox for a forum for this user. It should be checked or it would
						// not be in the $request_vars array.
						
						$delimiter_pos = strpos($var_part, '_', 4);
						$forum_id = substr($var_part,4,$delimiter_pos - 4);
						
						if (($all_forums !== 'on') && (trim($filter_type) !== constants::DIGESTS_BOOKMARKS)) 
						{
							$sql_ary2[] = array(
								'user_id'		=> $current_user_id,
								'forum_id'		=> $forum_id);
						}
					}

					if ($user_id !== $current_user_id)
					{
						// Since the $user_id has changed, we need to save the digest settings for this user
						if ($subscribe_default)
						{
							unset($sql_ary);
							
							// Create a digest subscription using board defaults
							$sql_ary = array(
								'user_digest_type' 				=> $config['phpbbservices_digests_user_digest_type'],
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
						}
						
						// Save this subscriber's digest settings
						$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
							WHERE user_id = ' . $current_user_id;
						$result = $db->sql_query($sql);
						
						// If there are any individual forum subscriptions for this user, remove the old ones. 
						$sql = 'DELETE FROM ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
								WHERE user_id = ' . $current_user_id;
						
						$result = $db->sql_query($sql);
		
						// Now save the individual forum subscriptions, if any
						if (isset($sql_ary2) && sizeof($sql_ary2) > 0)
						{
							foreach($sql_ary2 as $sql_row)
							{
								$sql = 'INSERT INTO ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_row);
								$result = $db->sql_query($sql);
							}
						}

						// With all the data saved for this user, we can set variables needed to process the next user.
						$current_user_id = $user_id;
						unset($sql_ary, $sql_ary2);
						$subscribe_default = false;
						
						// We need to set these variables so we can detect if individual forum subscriptions will need to be processed.
						$var = 'user-' . $current_user_id . '-all_forums';
						$all_forums = request_var($var,'');
						$var = 'user-' . $current_user_id . '-filter_type';
						$filter_type = request_var($var,'');

					}
					
				} // $request_vars variable is named user-*
				
			} // foreach
			
			// Process last user
			if (isset($sql_ary) && sizeof($sql_ary) > 0)
			{
				
				// Since the $user_id has changed, we need to save the digest settings for this user
				if ($subscribe_default)
				{
					unset($sql_ary);
					
					// Create a digest subscription using board defaults
					$sql_ary = array(
						'user_digest_type' 				=> $config['phpbbservices_digests_user_digest_type'],
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
				}
					
				// Save this subscriber's digest setting
				$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . $current_user_id;
				$result = $db->sql_query($sql);
				
				// If there are any individual forum subscriptions for this user, remove the old ones. 
				$sql = 'DELETE FROM ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
						WHERE user_id = ' . $current_user_id;
				$result = $db->sql_query($sql);

				// Now save the individual forum subscriptions, if any
				if (isset($sql_ary2) && sizeof($sql_ary2) > 0)
				{
					foreach($sql_ary2 as $sql_row)
					{
						$sql = 'INSERT INTO ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_row);
						$result = $db->sql_query($sql);
					}
				}
				
			}
					
			$message = $user->lang['CONFIG_UPDATED'];
				
		}

		if ($submit && $mode == 'digests_balance_load')
		{

			// Get total number of digest subscriptions
			$sql = "SELECT count(*) AS digests_count
				FROM " . USERS_TABLE . "
				WHERE user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' AND user_type <> " . USER_IGNORE;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			
			// Determine the average number of subscribers per hour. We need to assume at least one subscriber per hour to avoid 
			// resetting user's preferred digest time unnecessarily. If the average is 3 per hour, the first 3 already subscribed 
			// will not have their digest arrival time changed.
			
			$avg_subscribers_per_hour = max(floor($row['digests_count']/24), 1);
			
			$db->sql_freeresult($result);
			
			// Get oversubscribed hours, place in an array

			$sql = 'SELECT user_digest_send_hour_gmt AS hour, count(*) AS hour_count
				FROM ' . USERS_TABLE . "
				WHERE user_digest_type <> 'NONE' AND user_type <> " . USER_IGNORE . "
				GROUP BY user_digest_send_hour_gmt
				HAVING count(user_digest_send_hour_gmt ) > " . (int) $avg_subscribers_per_hour . "
				ORDER BY 1";
			
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
			if (sizeof($oversubscribed_hours) > 0)
			{
				
				$sql = 'SELECT user_digest_send_hour_gmt, user_id
					FROM ' . USERS_TABLE . "
					WHERE user_digest_type <> '" . constants::DIGESTS_NONE_VALUE . "' AND user_type <> " . USER_IGNORE . '
					AND ' . $db->sql_in_set('user_digest_send_hour_gmt', $oversubscribed_hours) . '
					ORDER BY 1, 2';
				$result = $db->sql_query_limit($sql, 100000, $avg_subscribers_per_hour - 1); // Result sets start with array indexed at zero
				$rowset = $db->sql_fetchrowset($result);
				
				$current_hour = -1;
				$counted_for_this_hour= 0;
				
				// Finally, change the digest send hour for these subscribers to a random hour between 0 and 24.
				foreach ($rowset as $row)
				{
					if ($current_hour <> $row['user_digest_send_hour_gmt'])
					{
						$current_hour = $row['user_digest_send_hour_gmt'];
						$counted_for_this_hour = 0;
					}
					$counted_for_this_hour++;
					if ($counted_for_this_hour > $avg_subscribers_per_hour)
					{
						// Change this subscription to a random hour to help balance the load
						$sql2 = 'UPDATE ' . USERS_TABLE . '
							SET user_digest_send_hour_gmt = ' . rand(0, 23) . "
							WHERE user_id = " . $row['user_id'];
						$result2 = $db->sql_query($sql2);
						$rebalanced++;
					}
				}
				
				$db->sql_freeresult($result);
			
			}
			
			$message = sprintf($user->lang['DIGESTS_REBALANCED'], $rebalanced);

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
				/*if ($config['phpbbservices_digests_include_inactive'])
				{
					$user_types[] = USER_INACTIVE;
				}*/
				
				// If doing a mass subscription, we don't want to mess up digest subscriptions already in place, so we need to create a snippet of SQL.
				// If doing a mass unsubscribe, all qualified subscriptions are removed. Note however that except for the digest type, all other settings 
				// are retained.
				$sql_qualifier = ($config['phpbbservices_digests_subscribe_all']) ? " AND user_digest_type = '" . constants::DIGESTS_NONE_VALUE . "'": " AND user_digest_type != '" . constants::DIGESTS_NONE_VALUE . "'";
				
				if ($config['phpbbservices_digests_notify_on_mass_subscribe'])
				{

					// Collect the email addresses of those who will be affected to send them an email notification.
					$sql = 'SELECT username, user_email, user_lang FROM ' . USERS_TABLE . 
						' WHERE ' . $db->sql_in_set('user_type', $user_types) . $sql_qualifier;
						
					$result = $db->sql_query($sql);
					$rowset = $db->sql_fetchrowset($result);
				
					foreach ($rowset as $row)
					{
						$digest_notify_list[$row['username']] = array('user_email' => $row['user_email'], 'user_lang' => $row['user_lang']);
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
				if ($config['phpbbservices_digests_notify_on_mass_subscribe'])
				{

					if (isset($digest_notify_list))
					{
						
						if (!class_exists('messenger'))
						{
							include($phpbb_root_path . 'includes/functions_messenger.' . $phpEx); // Used to send emails
						}
						
						// Set up associations between digest types as constants and their language equivalents
						switch ($config['phpbbservices_digests_user_digest_type'])
						{
							case DIGESTS_DAILY_VALUE:
								$digest_type_text = strtolower($user->lang['DIGESTS_DAILY']);
								break;
							case DIGESTS_WEEKLY_VALUE:
								$digest_type_text = strtolower($user->lang['DIGESTS_WEEKLY']);
								break;
							case DIGESTS_MONTHLY_VALUE:
								$digest_type_text = strtolower($user->lang['DIGESTS_MONTHLY']);
								break;
						}
						
						// Set up associations between digest formats as constants and their language equivalents
						switch ($config['phpbbservices_digests_user_digest_format'])
						{
							case DIGESTS_HTML_VALUE:
								$digest_format_text = $user->lang['DIGESTS_FORMAT_HTML'];
								break;
							case DIGESTS_HTML_CLASSIC_VALUE:
								$digest_format_text = $user->lang['DIGESTS_FORMAT_HTML_CLASSIC'];
								break;
							case DIGESTS_PLAIN_VALUE:
								$digest_format_text = $user->lang['DIGESTS_FORMAT_PLAIN'];
								break;
							case DIGESTS_PLAIN_CLASSIC_VALUE:
								$digest_format_text = $user->lang['DIGESTS_FORMAT_PLAIN_CLASSIC'];
								break;
							case DIGESTS_TEXT_VALUE:
								$digest_format_text = strtolower($user->lang['DIGESTS_FORMAT_TEXT']);
								break;
						}
						
						foreach ($digest_notify_list as $username => $row_info_array)
						{
							
							// E-mail setup
							$messenger = new \messenger();
							$digest_notify_template = ($config['phpbbservices_digests_subscribe_all']) ? 'digests_subscribe' : 'digests_unsubscribe';
							$digest_email_subject = ($config['phpbbservices_digests_subscribe_all']) ? $user->lang['DIGESTS_SUBSCRIBE_SUBJECT'] : $user->lang['DIGESTS_UNSUBSCRIBE_SUBJECT'];
							$messenger->template('@phpbbservices_digests/' . $digest_notify_template, $row_info_array['user_lang']);
							$messenger->to($row_info_array['user_email']);
								
							// SMTP delivery must strip text names due to likely bug in messenger class
							if ($config['smtp_delivery'])
							{
								$messenger->from($config['board_email']);
							}
							else
							{	
								$messenger->from($config['board_email'] . ' <' . $config['board_contact'] . '>');
							}
							
							$messenger->replyto($config['board_contact']);
							$messenger->subject($digest_email_subject);
							
							$messenger->assign_vars(array(
								'DIGEST_FORMAT'			=> $digest_format_text,
								'DIGEST_TYPE'			=> $digest_type_text,
								'DIGEST_UCP_LINK'		=> generate_board_url() . '/' . 'ucp.' . $phpEx,
								'FORUM_NAME'			=> $config['sitename'],
								'USERNAME'				=> $username,
								)
							);
							
							$mail_sent = $messenger->send(NOTIFY_EMAIL, false, false, true);
							
							if (!$mail_sent)
							{
								add_log('admin', sprintf($user->lang['LOG_CONFIG_DIGESTS_SEND_MASS_EMAIL_ERROR'], $row_info_array['user_email']));
							}
							$messenger->reset();
							
						}
						
					}
					
				}
				
				if ($config['phpbbservices_digests_subscribe_all'])
				{
					$message = sprintf($user->lang['DIGESTS_ALL_SUBSCRIBED'], sizeof($digest_notify_list));
				}
				else
				{
					$message = sprintf($user->lang['DIGESTS_ALL_UNSUBSCRIBED'], sizeof($digest_notify_list));
				}
				
			}
			else
			{
				// show no update message
				$message = $user->lang['DIGESTS_NO_MASS_ACTION'];
			}

		}

		if ($submit)
		{
			add_log('admin', 'LOG_CONFIG_' . strtoupper($mode));

			if (!isset($message))
			{
				$message = $user->lang('CONFIG_UPDATED');
			}
			$message_type = E_USER_NOTICE;
			trigger_error($message . adm_back_link($this->u_action), $message_type);
		}

		$this->tpl_name = 'acp_digests';
		$this->page_title = $display_vars['title'];

		$template->assign_vars(array(
			'ERROR_MSG'			=> implode('<br />', $error),
			'L_TITLE'			=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> $user->lang[$display_vars['title'] . '_EXPLAIN'],
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
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars,
					'S_LEGEND'		=> true)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}

			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

			if (empty($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'CONTENT'		=> $content,
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				)
			);

			unset($display_vars['vars'][$config_key]);
		}

	}

	function dow_select($default = '')
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

	function digest_type_select($default = '')
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_DAILY_VALUE) ? ' selected="selected"' : '';
		$digest_types = '<option value="' . constants::DIGESTS_DAILY_VALUE . '"' . $selected. '>' . $user->lang['DIGESTS_DAILY'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_WEEKLY_VALUE) ? ' selected="selected"' : '';
		$digest_types .= '<option value="' . constants::DIGESTS_WEEKLY_VALUE . '"' . $selected . '>' . $user->lang['DIGESTS_WEEKLY'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_type'] == constants::DIGESTS_MONTHLY_VALUE) ? ' selected="selected"' : '';
		$digest_types .= '<option value="' . constants::DIGESTS_MONTHLY_VALUE . '"' . $selected . '>' . $user->lang['DIGESTS_MONTHLY'] . '</option>';
		
		return $digest_types;
	}

	function digest_style_select($default = '')
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_HTML_VALUE) ? ' selected="selected"' : '';
		$digest_styles = '<option value="' . constants::DIGESTS_HTML_VALUE . '"' . $selected . '>' . $user->lang['DIGESTS_FORMAT_HTML'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_HTML_CLASSIC_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_HTML_CLASSIC_VALUE . '"' . $selected . '>' . $user->lang['DIGESTS_FORMAT_HTML_CLASSIC'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_PLAIN_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_PLAIN_VALUE . '"' . $selected . '>' . $user->lang['DIGESTS_FORMAT_PLAIN'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_PLAIN_CLASSIC_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_PLAIN_CLASSIC_VALUE . '"' . $selected . '>' . $user->lang['DIGESTS_FORMAT_PLAIN_CLASSIC'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_format'] == constants::DIGESTS_TEXT_VALUE) ? ' selected="selected"' : '';
		$digest_styles .= '<option value="' . constants::DIGESTS_TEXT_VALUE . '"' . $selected  . '>' . $user->lang['DIGESTS_FORMAT_TEXT'] . '</option>';
		
		return $digest_styles;
	} 

	function digest_send_hour_gmt($default = '')
	{
		global $config, $user;
		
		$digest_send_hour_gmt = '';
		
		// Populate the Hour Sent select control
		for($i=-1;$i<24;$i++)
		{
			$selected = ($i == $config['phpbbservices_digests_user_digest_send_hour_gmt']) ? ' selected="selected"' : '';
			$display_text = ($i == -1) ? $user->lang['DIGESTS_RANDOM_HOUR'] : $i;
			$digest_send_hour_gmt .= '<option value="' . $i . '"' . $selected . '>' . $display_text . '</option>';
		}
		
		
		return $digest_send_hour_gmt;
	} 

	function digest_filter_type ($default = '')
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_ALL) ? ' selected="selected"' : '';
		$digest_filter_types = '<option value="' . constants::DIGESTS_ALL . '"' . $selected. '>' . $user->lang['DIGESTS_ALL_FORUMS'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_FIRST) ? ' selected="selected"' : '';
		$digest_filter_types .= '<option value="' . constants::DIGESTS_FIRST . '"' . $selected . '>' . $user->lang['DIGESTS_POSTS_TYPE_FIRST'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS) ? ' selected="selected"' : '';
		$digest_filter_types .= '<option value="' . constants::DIGESTS_BOOKMARKS . '"' . $selected. '>' . $user->lang['DIGESTS_USE_BOOKMARKS'] . '</option>';
		
		return $digest_filter_types;
	} 

	function digest_post_sort_order ($default = '')
	{
		global $config, $user;
		
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_BOARD) ? ' selected="selected"' : '';
		$digest_sort_order = '<option value="' . constants::DIGESTS_SORTBY_BOARD . '"' . $selected . '>' . $user->lang['DIGESTS_SORT_USER_ORDER'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD . '"' . $selected .  '>' . $user->lang['DIGESTS_SORT_FORUM_TOPIC'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_STANDARD_DESC . '"' . $selected. '>' . $user->lang['DIGESTS_SORT_FORUM_TOPIC_DESC'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE . '"' . $selected. '>' . $user->lang['DIGESTS_SORT_POST_DATE'] . '</option>';
		$selected = ($config['phpbbservices_digests_user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE_DESC) ? ' selected="selected"' : '';
		$digest_sort_order .= '<option value="' . constants::DIGESTS_SORTBY_POSTDATE_DESC . '"' . $selected. '>' . $user->lang['DIGESTS_SORT_POST_DATE_DESC'] . '</option>';
		
		return $digest_sort_order;
	} 

}
