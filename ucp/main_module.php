<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\ucp;

use phpbbservices\digests\constants\constants;

class main_module
{

	protected $common;
	var $u_action;
	var $new_config = array();

	/**
	* Constructor
	*
	* @param \phpbbservices\digests\core\common	$common
	*/
	function main($id, $mode)
	{
		global $config, $db, $user, $auth, $template, $table_prefix, $request, $phpbb_root_path, $phpEx, $phpbb_container;

		// Get the common functions
		$helper = $phpbb_container->get('phpbbservices.digests.common');
		
		$user->add_lang_ext('phpbbservices/digests', array('info_acp_common', 'common'));

		$form_key = 'phpbbservices/digests';
		$action	= $request->variable('action', '');
		$submit = (isset($_POST['submit'])) ? true : false;

		if ($submit && !check_form_key($form_key))
		{
			$message = $user->lang('FORM_INVALID') . '<br /><br />' . $user->lang('RETURN_UCP', '<a href="' . $this->u_action . '">', '</a>');
			trigger_error($message);	// Program exits
		}
		
		if ($submit)
		{

			// Save settings for each mode
			switch ($mode)
			{

				case constants::DIGESTS_MODE_BASICS:
				
					// If no subscription is desired, remove any individual forum subscriptions and save some disk space!
					if ($request->variable('digest_type', constants::DIGESTS_DAILY_VALUE) == constants::DIGESTS_NONE_VALUE)
					{
						
						$sql = 'DELETE FROM ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
								WHERE user_id = ' . (int) $user->data['user_id'];
						$result = $db->sql_query($sql);
						
						// If a user chooses to unsubscribe, keep track of this so the admin is aware of this fact so if they 
						// resubscribe the person it won't be out of ignorance. The concern is that an admin resubscription would be
						// perceived as spam.
						$sql_ary['user_digest_has_unsubscribed'] = 1;
						
					}
					else
					{
						// If a user chooses to resubscribe, they may have unsubscribed in the past, so we want to clear this flag.
						$sql_ary['user_digest_has_unsubscribed'] = 0;
					}
			
					// Note: user_digest_send_hour_gmt is stored in UTC and translated to local time (as set in the profile). 
					// This is different than in phpBB 2, when all times were stored in server time.
					
					$local_send_hour = $request->variable('send_hour', (int) $user->data['user_digest_send_hour_gmt']) - ((int) $helper->make_tz_offset($user->data['user_timezone']));
					if ($local_send_hour >= 24)
					{
						$local_send_hour = $local_send_hour - 24;
					}
					else if ($local_send_hour < 0)
					{
						$local_send_hour = $local_send_hour + 24;
					}
					
					$sql_ary['user_digest_type']			= $request->variable('digest_type', $user->data['user_digest_type']);
					$sql_ary['user_digest_format']			= $request->variable('style', $user->data['user_digest_format']);
					$sql_ary['user_digest_send_hour_gmt']	= $local_send_hour;
					
				break;
					
				case constants::DIGESTS_MODE_FORUMS_SELECTION:
				
					// If there are any individual forum subscriptions, remove the old ones and create the new ones
					$sql = 'DELETE FROM ' . $table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
							WHERE user_id = ' . (int) $user->data['user_id'];
					$result = $db->sql_query($sql);
	
					// Note that if "all_forums" is unchecked and bookmarks is unchecked, there are individual forum subscriptions, so they must be saved.
					$all_forums = $request->variable('all_forums', $user->data['user_digest_filter_type']);
					$digest_type = $request->variable('digest_type', $user->data['user_digest_type']);
					
					// Get the POST variables as an array the phpBB approved way so they can be parse to find individual digest subscriptions
					$request_vars = $request->get_super_global(\phpbb\request\request_interface::POST);

					if (($all_forums !== 'on') && (trim($digest_type) !== constants::DIGESTS_BOOKMARKS)) 
					{
						foreach ($request_vars as $key => $value) 
						{
							if (substr($key, 0, 4) == 'elt_') 
							{
								$forum_id = intval(substr($key, 4, strpos($key, '_', 4) - 4));
	
								$sql_ary[] = array(
									'user_id'		=> (int) $user->data['user_id'],
									'forum_id'		=> $forum_id);
							}
						}
						if (sizeof($sql_ary) > 0)
						{
							$result = $db->sql_multi_insert($table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE, $sql_ary);
						}
					}
					unset($sql_ary);
					
					$sql_ary = array(
						'user_digest_filter_type'	=> $request->variable('filtertype', $user->data['user_digest_filter_type']));
						
				break;

				case constants::DIGESTS_MODE_POST_FILTERS:
				
					$mark_read = ($request->variable('mark_read', '') == 'on') ? 1 : 0;
					$sql_ary = array(
						'user_digest_max_posts'			=> $request->variable('count_limit', 0),
						'user_digest_min_words'			=> $request->variable('min_word_size', 0),
						'user_digest_new_posts_only'	=> $request->variable('new_posts', (int) $user->data['user_digest_new_posts_only']),
						'user_digest_show_mine'			=> $request->variable('show_mine', (int) $user->data['user_digest_show_mine']),
						'user_digest_remove_foes'		=> $request->variable('filter_foes', (int) $user->data['user_digest_remove_foes']),
						'user_digest_show_pms'			=> $request->variable('pms', (int) $user->data['user_digest_show_pms']),
						'user_digest_pm_mark_read'		=> $mark_read);
						
				break;
					
				case constants::DIGESTS_MODE_ADDITIONAL_CRITERIA:
				
					$no_post_text = ($request->variable('no_post_text', '') == 'on') ? 1 : 0;
					$sql_ary = array(
						'user_digest_sortby'			=> $request->variable('sort_by', $user->data['user_digest_sortby']),
						'user_digest_max_display_words'	=> $request->variable('max_word_size', 0),
						'user_digest_no_post_text'		=> $no_post_text,
						'user_digest_send_on_no_posts'	=> $request->variable('send_on_no_posts', (int) $user->data['user_digest_send_on_no_posts']),
						'user_digest_reset_lastvisit'	=> $request->variable('lastvisit', (int) $user->data['user_digest_reset_lastvisit']),
						'user_digest_attachments'		=> $request->variable('attachments', (int) $user->data['user_digest_attachments']),
						'user_digest_block_images'		=> $request->variable('blockimages', (int) $user->data['user_digest_block_images']),
						'user_digest_toc'				=> $request->variable('toc', (int) $user->data['user_digest_toc']));

				break;
					
				default:
					trigger_error($user->lang('UCP_DIGESTS_MODE_ERROR', $mode) . '<br /><br />' . $user->lang('RETURN_UCP', '<a href="' . $this->u_action . '">', '</a>'));
				break;
				
			}
			
			// Update the user's digest settings
			if (sizeof($sql_ary) > 0)
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . (int) $user->data['user_id'];
				$db->sql_query($sql);
				$result = $db->sql_query($sql);
			}
			
			// Send a confirmation message
			meta_refresh(3, $this->u_action);
			$message = $user->lang('DIGESTS_UPDATED') . '<br /><br />' . $user->lang('RETURN_UCP', '<a href="' . $this->u_action . '">', '</a>');
			trigger_error($message);	// Program exits
			
		}

		// Present the form for the appropriate digests mode
		
		$this->tpl_name = 'ucp_digests';
			
		add_form_key($form_key);
		
		// Don't show submit or reset buttons if there is no digest subscription, but it can be placed on the Basics page so it can be changed.
		$show_buttons = ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE) ? false : true;
		if ($mode == constants::DIGESTS_MODE_BASICS)
		{
			$show_buttons = true; // Buttons must appear in basics mode otherwise there is no way to resubscribe
		}

		switch ($mode)
		{
			
			case constants::DIGESTS_MODE_BASICS:
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_BASICS',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);

				// If user hasn't set their timezone, trigger an error message because to select an hour for a digest to go out it must be
				// calculated from their timezone.
				if ($user->data['user_timezone'] == '')
				{
					$template->assign_vars(array(
						'L_DIGESTS_NO_TIMEZONE'		=> $user->lang('DIGESTS_NO_TIMEZONE', append_sid($phpbb_root_path . "ucp.$phpEx?i=ucp_prefs&mode=personal")),
						'S_DIGESTS_NO_TIMEZONE'		=> true,
					));
				}
				
				else
				
				{

					if ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE)
					{
						if ($config['phpbbservices_digests_user_digest_send_hour_gmt'] == -1)
						{
							// Pick a random hour, since this is a new digest and the administrator requested this to even out digest server processing
							$local_send_hour = rand(0,23);
						}
						else
						{
							$local_send_hour = $config['phpbbservices_digests_user_digest_send_hour_gmt'];
						}
					}
					else
					{
						// Translate the digests send hour (in GMT) to the local timezone, based on the timezone set in the user's profile.
						$local_send_hour = (int) $user->data['user_digest_send_hour_gmt'] + (int) $helper->make_tz_offset($user->data['user_timezone']);
					}
					
					// Adjust time if outside of hour range
					if ($local_send_hour >= 24)
					{
						$local_send_hour = $local_send_hour - 24;
					}
					else if ($local_send_hour < 0)
					{
						$local_send_hour = $local_send_hour + 24;
					}
					
	
					// Set other form fields using board defaults if necessary, otherwise pull from the user's settings
					// Note, setting an administator configured default for digest type is a bad idea because
					// the user might think they have a digest subscription when they do not.
					
					if ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE)
					{
						$styling_html = ($config['phpbbservices_digests_user_digests_format'] == constants::DIGESTS_HTML_VALUE);
						$styling_html_classic = ($config['phpbbservices_digests_user_digests_format'] == constants::DIGESTS_HTML_CLASSIC_VALUE);
						$styling_plain = ($config['phpbbservices_digests_user_digests_format'] == constants::DIGESTS_PLAIN_VALUE);
						$styling_plain_classic = ($config['phpbbservices_digests_user_digests_format'] == constants::DIGESTS_PLAIN_CLASSIC_VALUE);
						$styling_text = ($config['phpbbservices_digests_user_digests_format'] == constants::DIGESTS_TEXT_VALUE);
					}
					else
					{
						$styling_html = ($user->data['user_digest_format'] == constants::DIGESTS_HTML_VALUE);
						$styling_html_classic = ($user->data['user_digest_format'] == constants::DIGESTS_HTML_CLASSIC_VALUE);
						$styling_plain = ($user->data['user_digest_format'] == constants::DIGESTS_PLAIN_VALUE);
						$styling_plain_classic = ($user->data['user_digest_format'] == constants::DIGESTS_PLAIN_CLASSIC_VALUE);
						$styling_text = ($user->data['user_digest_format'] == constants::DIGESTS_TEXT_VALUE);
					}
					
					// Populated the Hour Sent select control
					for($i=0;$i<24;$i++)
					{
						$template->assign_block_vars('hour_loop',array(
							'COUNT' 						=>	$i,
							'SELECTED'						=>	($local_send_hour == $i) ? ' selected="selected"' : '',
							'DISPLAY_HOUR'					=>	$helper->make_hour_string($i, $user->data['user_dateformat']),
						));
					}
		
					$template->assign_vars(array(
						'L_DIGESTS_FREQUENCY_EXPLAIN'		=> $user->lang('DIGESTS_FREQUENCY_EXPLAIN', $user->lang['DIGESTS_WEEKDAY'][$config['phpbbservices_digests_weekly_digest_day']]),
						'L_DIGESTS_HTML_CLASSIC_VALUE'		=> constants::DIGESTS_HTML_CLASSIC_VALUE,
						'L_DIGESTS_HTML_VALUE'				=> constants::DIGESTS_HTML_VALUE,
						'L_DIGESTS_PLAIN_CLASSIC_VALUE'		=> constants::DIGESTS_PLAIN_CLASSIC_VALUE,
						'L_DIGESTS_PLAIN_VALUE'				=> constants::DIGESTS_PLAIN_VALUE,
						'L_DIGESTS_TEXT_VALUE'				=> constants::DIGESTS_TEXT_VALUE,
						'S_DIGESTS_BASICS'					=> true,
						'S_DIGESTS_DAY_CHECKED' 			=> ($user->data['user_digest_type'] == constants::DIGESTS_DAILY_VALUE),
						'S_DIGESTS_HTML_CHECKED' 			=> $styling_html || ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE),
						'S_DIGESTS_HTML_CLASSIC_CHECKED' 	=> $styling_html_classic,
						'S_DIGESTS_MONTH_CHECKED' 			=> ($user->data['user_digest_type'] == constants::DIGESTS_MONTHLY_VALUE),
						'S_DIGESTS_NONE_CHECKED' 			=> ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE),
						'S_DIGESTS_PLAIN_CHECKED' 			=> $styling_plain,
						'S_DIGESTS_PLAIN_CLASSIC_CHECKED' 	=> $styling_plain_classic,
						'S_DIGESTS_TEXT_CHECKED' 			=> $styling_text,
						'S_DIGESTS_WEEK_CHECKED' 			=> ($user->data['user_digest_type'] == constants::DIGESTS_WEEKLY_VALUE),
						)
					);
					
				}

			break;
			
			case constants::DIGESTS_MODE_FORUMS_SELECTION:
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_FORUMS_SELECTION',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);

				// Create a list of required and excluded forum_ids
				$required_forum_ids = isset($config['phpbbservices_digests_include_forums']) ? explode(',',$config['phpbbservices_digests_include_forums']) : array();
				$excluded_forum_ids = isset($config['phpbbservices_digests_exclude_forums']) ? explode(',',$config['phpbbservices_digests_exclude_forums']) : array();

				// Individual forum checkboxes should be disabled if no digest is wanted or if bookmarks are requested/expected
				if ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE)
				{
					$disabled_all = true;
					$disabled_first = true;
					$disabled_bm = true;
					$disabled = true;	// used to disable individual forums in this case
				}
				else if ($user->data['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS)
				{
					$disabled_all = false;
					$disabled_first = false;
					$disabled_bm = false;
					$disabled = true;	// used to disable individual forums in this case
				}
				else
				{
					$disabled_all = false;
					$disabled_first = false;
					$disabled_bm = false;
					$disabled = false;	// used to disable individual forums in this case
				}

				// Get current subscribed forums for this user, if any. If none, all allowed forums are assumed
				$rowset = array();

				$sql_array = array(
					'SELECT'	=> 'forum_id',
				
					'FROM'		=> array(
						$table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE	=> 'sf',
					),
				
					'WHERE'		=> 'user_id = ' . (int) $user->data['user_id'],
				);
				
				$sql = $db->sql_build_query('SELECT', $sql_array);

				$result = $db->sql_query($sql);
				$rowset = $db->sql_fetchrowset($result);
				$db->sql_freeresult();

				$all_by_default = ((sizeof($rowset) == 0) && $config['phpbbservices_digests_user_check_all_forums']) ? true : false;

				$forum_read_ary = array();
				$allowed_forums = array();
				
				$forum_read_ary = $auth->acl_getf('f_read');
				
				// Get a list of parent_ids for each forum and put them in an array.
				$parent_array = array();

				$sql_array = array(
					'SELECT'	=> 'forum_id, parent_id',
				
					'FROM'		=> array(
						FORUMS_TABLE	=> 'f',
					),
				
					'ORDER_BY'		=> '1',
				);

				$sql = $db->sql_build_query('SELECT', $sql_array);

				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$parent_array[$row['forum_id']] = $row['parent_id'];
				}
				$db->sql_freeresult();

				foreach ($forum_read_ary as $forum_id => $allowed)
				{
					if ($allowed['f_read'])
					{
						// Since this user has read access to this forum, add it to the $allowed_forums array
						$allowed_forums[] = (int) $forum_id;
						
						// Also add to $allowed_forums the parents, if any, of this forum. Actually we have to find the parent's parents, etc., going up as far as necesary because 
						// $auth->act_getf does not return the parents for which the user has access, yet parents must be shown are on the interface
						$there_are_parents = true;
						$this_forum_id = (int) $forum_id;
						
						while ($there_are_parents)
						{
							if ($parent_array[$this_forum_id] == 0)
							{
								$there_are_parents = false;
							}
							else
							{
								// Do not add this parent to the list of allowed forums if it is already in the array
								if (!in_array((int) $parent_array[$this_forum_id], $allowed_forums))
								{
									$allowed_forums[] = (int) $parent_array[$this_forum_id];
								} 
								$this_forum_id = (int) $parent_array[$this_forum_id];	// Keep looping...
							}
						}
					}
				}

				// Get a list of forums as they appear on the main index for this user. For presentation purposes indent them so they show the natural phpBB3 hierarchy.
				// Indenting is cleverly handled by nesting <div> tags inside of other <div> tags, and the template defines the relative offset (20 pixels).
				
				if (sizeof($allowed_forums) > 0)
				
				{
				
					// Set a flag in case no forums should be checked
					$uncheck = ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE) && ($config['phpbbservices_digests_user_check_all_forums'] == 0);
				
					$sql_array = array(
						'SELECT'	=> 'forum_name, forum_id, parent_id, forum_type',
					
						'FROM'		=> array(
							FORUMS_TABLE		=> 'f',
						),
					
						'WHERE'		=> $db->sql_in_set('forum_id', $allowed_forums) . ' AND forum_type <> ' . FORUM_LINK . "
							AND forum_password = ''",
					
						'ORDER_BY'	=> 'left_id ASC',
					);
					
					$sql = $db->sql_build_query('SELECT', $sql_array);

					$result = $db->sql_query($sql);
					
					$template->assign_block_vars('show_forums', array());
					
					$current_level = 0;			// How deeply nested are we at the moment
					$parent_stack = array();	// Holds a stack showing the current parent_id of the forum
					$parent_stack[] = 0;		// 0, the first value in the stack, represents the <div_0> element, a container holding all the categories and forums in the template
					
					while ($row = $db->sql_fetchrow($result))
					{
					
						if ((int) $row['parent_id'] != (int) end($parent_stack) || (end($parent_stack) == 0))
						{
							if (in_array($row['parent_id'],$parent_stack))
							{
								// If parent is in the stack, then pop the stack until the parent is found, otherwise push stack adding the current parent. This creates a </div>
								while ((int) $row['parent_id'] != (int) end($parent_stack))
								{
									array_pop($parent_stack);
									$current_level--;
									// Need to close a category level here
									$template->assign_block_vars('forums', array( 
										'S_DIGESTS_DIV_CLOSE' 	=> true,
										'S_DIGESTS_DIV_OPEN' 	=> false,
										'S_DIGESTS_PRINT' 		=> false,
										)
									);
								}
							}
							else
							{
								// If the parent is not in the stack, then push the parent_id on the stack. This is also a trigger to indent the block. This creates a <div>
								array_push($parent_stack, (int) $row['parent_id']);
								$current_level++;
								// Need to add a category level here
								$template->assign_block_vars('forums', array( 
									'CAT_ID' 			=> 'div_' . $row['parent_id'],
									'S_DIGESTS_DIV_CLOSE' 		=> false,
									'S_DIGESTS_DIV_OPEN' 		=> true,
									'S_DIGESTS_PRINT' 			=> false
									)
								);
							}
						}
						
						// This section contains logic to handle forums that are either required or excluded by the Administrator
						
						// Is the forum either required or excluded from digests?
						$required_forum = (in_array((int) $row['forum_id'], $required_forum_ids)) ? true : false;
						$excluded_forum = (in_array((int) $row['forum_id'], $excluded_forum_ids)) ? true : false;
						$forum_disabled = $required_forum || $excluded_forum;
						
						// Markup to visually show required or excluded forums
						if ($required_forum)
						{
							$prefix = '<strong>';
							$suffix = '</strong>';
						}
						else
						{
							if ($excluded_forum)
							{
								$prefix = '<span style="text-decoration:line-through">';
								$suffix = '</span>';
							}
							else
							{
								$prefix = '';
								$suffix = '';
							}
						}
						
						// This code prints the forum or category, which will exist inside the previously created <div> block
						
						// Check this forum's checkbox? Only if they have forum subscriptions
						if (!$all_by_default)
						{
							$check = false;
							foreach($rowset as $this_row)
							{
								if ($this_row['forum_id'] == $row['forum_id'])
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
						
						// Let's make the check logic more complicated. If "All Forums" is unchecked and there is no digest subscription
						// then we must make sure every forum is also unchecked. Also need to uncheck if bookmarks are turned on
						if ($check || $all_by_default)
						{
							$check = true;
						}

						// Make sure required forums are checked
						if ($required_forum)
						{
							$check = true;
						}
						
						// Make sure excluded forums are unchecked
						if ($excluded_forum)
						{
							$check = false;
						}
							
						$template->assign_block_vars('forums', array( 
							'FORUM_LABEL' 					=> $row['forum_name'],
							'FORUM_NAME' 					=> 'elt_' . (int) $row['forum_id'] . '_' . (int) $row['parent_id'],
							'FORUM_PREFIX' 					=> $prefix,
							'FORUM_SUFFIX' 					=> $suffix,
							'S_DIGESTS_FORUM_DISABLED' 		=> ($disabled || $forum_disabled || $user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE),
							'S_DIGESTS_FORUM_SUBSCRIBED' 	=> ($check),
							'S_DIGESTS_IS_FORUM' 			=> !($row['forum_type'] == FORUM_CAT),
							'S_DIGESTS_PRINT' 				=> true,
							)
						);
						
					}
				
					$db->sql_freeresult($result);
					
					// Now out of the loop, it is important to remember to close any open <div> tags. Typically there is at least one.
					while ((int) $row['parent_id'] != (int) end($parent_stack))
					{
						array_pop($parent_stack);
						$current_level--;
						// Need to close the <div> tag
						$template->assign_block_vars('forums', array( 
							'S_DIGESTS_DIV_CLOSE' 	=> true,
							'S_DIGESTS_DIV_OPEN' 	=> false,
							'S_DIGESTS_PRINT' 		=> false,
							)
						);
					}
					
					$template->assign_vars(array(
						'S_DIGESTS_ALL_BY_DEFAULT'		=> $all_by_default,
						'S_DIGESTS_ALL_DISABLED'		=> ($disabled || $user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE),
						'S_DIGESTS_ALL_CONTROL_DISABLED' 	=> $disabled_all,
						'S_DIGESTS_BM_CONTROL_DISABLED' 	=> $disabled_bm,
						'S_DIGESTS_FIRST_CONTROL_DISABLED' 	=> $disabled_first,
						'S_DIGESTS_POST_ANY'			=> ($user->data['user_digest_filter_type'] == constants::DIGESTS_ALL),
						'S_DIGESTS_POST_BM'				=> ($user->data['user_digest_filter_type'] == constants::DIGESTS_BOOKMARKS),
						'S_DIGESTS_POST_FIRST'			=> ($user->data['user_digest_filter_type'] == constants::DIGESTS_FIRST),
						'S_DIGESTS_NO_FORUMS' 			=> false, 
						)
					);
				}
					
				else
					
				{
					// No forums to show!
					$template->assign_vars(array(
						'L_DIGESTS_NO_FORUMS_MESSAGE' 	=> $user->lang('DIGESTS_NO_FORUMS_AVAILABLE'),
						'S_DIGESTS_NO_FORUMS' 			=> true, 
						)
					);
				}				

				$template->assign_vars(array(
					'S_DIGESTS_FORUMS_SELECTION'							=> true,
					)
				);
				
			break;
			
			case constants::DIGESTS_MODE_POST_FILTERS:
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_POST_FILTERS',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);

				if ($config['phpbbservices_digests_max_items'] > 0)
				{
					$max_posts = min((int) $user->data['user_digest_max_posts'], $config['phpbbservices_digests_max_items']);
				}
				else
				{
					$max_posts = (int) $user->data['user_digest_max_posts'];
				}
				
				$template->assign_vars(array(
					'L_DIGEST_COUNT_LIMIT_EXPLAIN'				=> $user->lang('DIGESTS_SIZE_ERROR', $config['digests_max_items']),
					'LA_DIGEST_SIZE_ERROR'						=> $user->lang('DIGESTS_SIZE_ERROR', $config['digests_max_items']),
					'S_DIGESTS_FILTER_FOES_CHECKED_NO' 			=> ($user->data['user_digest_remove_foes'] == 0),
					'S_DIGESTS_FILTER_FOES_CHECKED_YES' 		=> ($user->data['user_digest_remove_foes'] == 1),
					'S_DIGESTS_MARK_READ_CHECKED' 				=> ($user->data['user_digest_pm_mark_read'] == 1),
					'S_DIGESTS_MAX_ITEMS' 						=> $max_posts,
					'S_DIGESTS_MIN_SIZE' 						=> ($user->data['user_digest_min_words'] == 0) ? '' : (int) $user->data['user_digest_min_words'],
					'S_DIGESTS_NEW_POSTS_ONLY_CHECKED_NO' 		=> ($user->data['user_digest_new_posts_only'] == 0),
					'S_DIGESTS_NEW_POSTS_ONLY_CHECKED_YES' 		=> ($user->data['user_digest_new_posts_only'] == 1),
					'S_DIGESTS_PRIVATE_MESSAGES_IN_DIGEST_NO' 	=> ($user->data['user_digest_show_pms'] == 0),
					'S_DIGESTS_PRIVATE_MESSAGES_IN_DIGEST_YES' 	=> ($user->data['user_digest_show_pms'] == 1),
					'S_DIGESTS_REMOVE_YOURS_CHECKED_NO' 		=> ($user->data['user_digest_show_mine'] == 1),
					'S_DIGESTS_REMOVE_YOURS_CHECKED_YES' 		=> ($user->data['user_digest_show_mine'] == 0),
					'S_DIGESTS_POST_FILTERS'					=> true,
					)
				);

			break;
			
			case constants::DIGESTS_MODE_ADDITIONAL_CRITERIA:
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_ADDITIONAL_CRITERIA',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);
				$template->assign_vars(array(
					'DIGESTS_MAX_SIZE' 								=> ($user->data['user_digest_max_display_words'] == 0) ? '' : (int) $user->data['user_digest_max_display_words'],
					'S_DIGESTS_ADDITIONAL_CRITERIA'					=> true,
					'S_DIGESTS_ATTACHMENTS_NO_CHECKED' 				=> ($user->data['user_digest_attachments'] == 0),
					'S_DIGESTS_ATTACHMENTS_YES_CHECKED' 			=> ($user->data['user_digest_attachments'] == 1),
					'S_DIGESTS_BLOCK_IMAGES' 						=> ($config['phpbbservices_digests_block_images'] == 1),
					'S_DIGESTS_BLOCK_IMAGES_NO_CHECKED' 			=> ($user->data['user_digest_block_images'] == 0),
					'S_DIGESTS_BLOCK_IMAGES_YES_CHECKED' 			=> ($user->data['user_digest_block_images'] == 1),
					'S_DIGESTS_BOARD_SELECTED' 						=> ($user->data['user_digest_sortby'] == constants::DIGESTS_SORTBY_BOARD),
					'S_DIGESTS_LASTVISIT_NO_CHECKED' 				=> ($user->data['user_digest_reset_lastvisit'] == 0),
					'S_DIGESTS_LASTVISIT_YES_CHECKED' 				=> ($user->data['user_digest_reset_lastvisit'] == 1),
					'S_DIGESTS_NO_POST_TEXT_CHECKED'				=> ($user->data['user_digest_no_post_text'] == 1),
					'S_DIGESTS_POSTDATE_DESC_SELECTED' 				=> ($user->data['user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE_DESC),
					'S_DIGESTS_POSTDATE_SELECTED' 					=> ($user->data['user_digest_sortby'] == constants::DIGESTS_SORTBY_POSTDATE),
					'S_DIGESTS_SEND_ON_NO_POSTS_NO_CHECKED' 		=> ($user->data['user_digest_send_on_no_posts'] == 0),
					'S_DIGESTS_SEND_ON_NO_POSTS_YES_CHECKED' 		=> ($user->data['user_digest_send_on_no_posts'] == 1),
					'S_DIGESTS_STANDARD_DESC_SELECTED' 				=> ($user->data['user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD_DESC),
					'S_DIGESTS_STANDARD_SELECTED' 					=> ($user->data['user_digest_sortby'] == constants::DIGESTS_SORTBY_STANDARD),
					'S_DIGESTS_TOC_NO_CHECKED' 						=> ($user->data['user_digest_toc'] == 0),
					'S_DIGESTS_TOC_YES_CHECKED' 					=> ($user->data['user_digest_toc'] == 1),
					)
				);
				
			break;
				
			default:
			break;
			
		}

		// These template variables are used on all the pages
		$template->assign_vars(array(
			'L_DIGESTS_DISABLED_MESSAGE' 	=> ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE) ? '<p><em>' . $user->lang('DIGESTS_DISABLED_MESSAGE') . '</em></p>' : '',
			'L_DIGESTS_MODE'				=> $user->lang('UCP_DIGESTS_' . strtoupper($mode)),
			'S_DIGESTS_CONTROL_DISABLED' 	=> ($user->data['user_digest_type'] == constants::DIGESTS_NONE_VALUE),
			'S_DIGESTS_HOME'				=> $config['phpbbservices_digests_digests_title'],
			'S_DIGESTS_SHOW_BUTTONS'		=> $show_buttons,
			'U_DIGESTS_ACTION'  			=> $this->u_action,
			'U_DIGESTS_PAGE_URL'			=> $config['phpbbservices_digests_page_url'],
			)
		);

	}

}

