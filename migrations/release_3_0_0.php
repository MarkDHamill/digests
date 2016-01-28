<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

use phpbb\db\tools;
use phpbb\db\migration\tool\module;

class release_3_0_0 extends \phpbb\db\migration\migration
{
	
	public function effectively_installed()
	{
		
		// This handles upgrades from the phpBB Digests mod for phpBB 3.0.x to this extension, allowing configuration and user settings to be preserved
		// if possible. Versions 2.2.6 through 2.2.26 are supported.
		
		global $user, $cache, $phpbb_root_path, $phpEx;
		
		$user->add_lang_ext('phpbbservices/digests', array('info_acp_common', 'common'));

		if (!$this->config['digests_version'])
		{
			return false;	// Digests not previously installed, so go ahead and do a clean install
		}

		// If the version of the digests mod is old (prior to 2.2.6, the first officially approved digests mod for 3.0) or is more than the last version 
		// 2.2.26 then this migrator cannot be used.
		if ((version_compare($this->config['digests_version'], '2.2.6') === -1) && (version_compare($this->config['digests_version'], '2.2.26') === 1))
		{
			$message_type = E_USER_WARNING;
			trigger_error(sprintf($user->lang['DIGESTS_MIGRATE_UNSUPPORTED_VERSION'], $this->config['digests_version']), $message_type);
			return;
		}

		// To upgrade from 2.2.6 or greater, the basic approach is to compare arrays of configuration variables and database column names.
		// Remove what has gone away, add what is missing.
		
		// Load the new configuration values array
		
		$new_config = array( 
			'digests_block_images' => 0,
			'digests_cron_task_last_gc' => 0, // timestamp when the digests mailer was last run
			'digests_cron_task_gc' => (60 * 60), // seconds between run; digests are sent hourly		
			'digests_custom_stylesheet_path' => 'prosilver/theme/digest_stylesheet.css',
			'digests_enable_auto_subscriptions' => 0,
			'digests_enable_custom_stylesheets' => 0,
			'digests_enable_log' => 0,
			'digests_enable_subscribe_unsubscribe' => '0',
			'digests_exclude_forums' => '0', 
			'digests_from_email_name' => '',
			'digests_from_email_address' => '',
			'digests_host' => 'phpbbservices.com',
			'digests_include_admins' => '0',
			'digests_include_forums' => '0',
			'digests_max_items' => 0,
			'digests_notify_on_mass_subscribe' => '1', 
			'digests_page_url' => 'https://phpbbservices.com/digests_wp/',
			'digests_registration_field' => 0,
			'digests_reply_to_email_address' => '',
			'digests_show_email' => 0,
			'digests_subscribe_all' => '1', 
			'digests_test' => 0,
			'digests_test_clear_spool' => 1,
			'digests_test_day' => 1,
			'digests_test_email_address' => '',
			'digests_test_hour' => 0,
			'digests_test_month' => 1,
			'digests_test_send_to_admin' => 0,
			'digests_test_spool' => 0,
			'digests_test_time_use' => 0,
			'digests_test_year' => 2016,
			'digests_time_zone' => $this->config['board_timezone'],
			'digests_user_check_all_forums' => 1,
			'digests_user_digest_attachments' => 1,
			'digests_user_digest_block_images' => 0,
			'digests_user_digest_filter_type' => 'ALL',
			'digests_user_digest_format' => 'HTML',
			'digests_user_digest_max_display_words' => -1,
			'digests_user_digest_max_posts' => 0,
			'digests_user_digest_min_words' => 0,
			'digests_user_digest_new_posts_only' => 0,
			'digests_user_digest_no_post_text' => 0,
			'digests_user_digest_pm_mark_read' => 0,
			'digests_user_digest_registration' => 0,
			'digests_user_digest_remove_foes' => 0,
			'digests_user_digest_reset_lastvisit' => 0,
			'digests_user_digest_send_hour_gmt' => -1,
			'digests_user_digest_send_on_no_posts' => 0,
			'digests_user_digest_show_mine' => 1,
			'digests_user_digest_show_pms' => 1,
			'digests_user_digest_sortby' => 'board',
			'digests_user_digest_toc' => 0,
			'digests_user_digest_type' => 'DAY',
			'digests_users_per_page' => 20,
			'digests_weekly_digest_day' => 0,
		);
		
		$remove_config = array();
		
		// If the old configuration value exists, keep its value but it must change its name to add the vendor name as a prefix
		foreach ($this->config as $key => $value)
		{
			if (substr($key, 0, 8) == 'digests_')
			{
				if (array_key_exists($key, $new_config))
				{
					// add the new config value with vendor in the config_name
					$this->config->set('phpbbservices_' . $key, $value);
				}
				// mark the old config value for deletion once outside of the loop
				$remove_config[] = $key;
			}
		}
		
		// Remove the old configuration variables, i.e. digests_* rather than phpbbservices_digests_*
		foreach ($remove_config as $key => $value)
		{
			$this->config->delete($value);
		}
		
		// Add in any new configuration variables using the defaults. These were introduced by later versions of digests or are new in the extension
		// and all must have phpbbservices_ as a prefix.
		foreach ($new_config as $key => $value)
		{
			if (array_key_exists($key, $this->config) === false)
			{
				$this->config->set('phpbbservices_' . $key, $value);
			}
		}
		
		// Modify problematic configuration variables explicitly. The digests page is now in Wordpress.
		$this->config->set('phpbbservices_digests_page_url', 'https://phpbbservices.com/digests_wp/');
		
		// ----- Fix users table ----- //

		$new_columns = array(
			'user_digest_attachments' => array('TINT:4', 1),
			'user_digest_block_images' => array('TINT:4', 0),
			'user_digest_filter_type' => array('VCHAR:3', 'ALL'),
			'user_digest_format' => array('VCHAR:4', 'HTML'),
			'user_digest_has_unsubscribed' => array('TINT:4', 0),
			'user_digest_last_sent' => array('UINT:11', 0),
			'user_digest_max_display_words' => array('INT:4', 0),
			'user_digest_max_posts' => array('UINT', 0),
			'user_digest_min_words' => array('UINT', 0),
			'user_digest_new_posts_only' => array('TINT:4', 0),
			'user_digest_no_post_text' => array('TINT:4', 0),
			'user_digest_pm_mark_read' => array('TINT:4', 0),
			'user_digest_remove_foes' => array('TINT:4', 0),
			'user_digest_reset_lastvisit' => array('TINT:4', 1),
			'user_digest_send_hour_gmt' => array('DECIMAL', '0.00'),
			'user_digest_send_on_no_posts' => array('TINT:4', 0),
			'user_digest_show_mine' => array('TINT:4', 1),
			'user_digest_show_pms' => array('TINT:4', 1),
			'user_digest_sortby' => array('VCHAR:13', 'board'),
			'user_digest_toc' => array('TINT:4', 0),
			'user_digest_type' => array('VCHAR:4', 'NONE'),
		);
		
		// Get a succinct array of new column names without metadata to make it easier to find missing columns
		$new_column_names = array_keys($new_columns);
		
		$found_digest_columns = array();
		
		// The tools class has some convenient methods we will use to add and remove columns
		$tools = new \phpbb\db\tools($this->db);
		
		// Get a list of the current columns in the phpbb_users table.
		$user_table_columns = array_keys($tools->sql_list_columns($this->table_prefix . 'users'));
		
		// Note the columns found that start with "user_digest_"
		foreach ($user_table_columns as $key => $value)
		{
			if (substr($value, 0, 12) == 'user_digest_')
			{
				$found_digest_columns[] = $value;
			}
		}
		
		// Delete those columns in the phpbb_users table for digests that are no longer used 
		$columns_to_remove = array_diff($found_digest_columns, $new_column_names);
		foreach ($columns_to_remove as $key => $value)
		{
			$tools->sql_column_remove($this->table_prefix . 'users', $value);
		}
		
		// Add those digest columns to the phpbb_users table were not in the version of the mod previously installed
		$columns_to_add = array_diff($new_column_names, $found_digest_columns);
		foreach ($columns_to_add as $key => $value)
		{
			$tools->sql_column_add($this->table_prefix . 'users', $value, array($new_columns[$value][0], $new_columns[$value][1]));
		}
		
		// ----- Remove old ACP Modules ----- //
		
		// Use the module class to conveniently remove dead modules as well as add new ones
		$modules_table = $this->table_prefix . 'modules';
		$modules_tool = new \phpbb\db\migration\tool\module($this->db, $cache, $user, $phpbb_root_path, $phpEx, $modules_table);
		
		// Remove ACP modules if they exist.
		if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_GENERAL_SETTINGS'))
		{
			$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_GENERAL_SETTINGS');
		}
		if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_USER_DEFAULT_SETTINGS'))
		{
			$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_USER_DEFAULT_SETTINGS');
		}
		if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_EDIT_SUBSCRIBERS'))	// Appeared in 2.2.16
		{
			$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_EDIT_SUBSCRIBERS');
		}
		if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_BALANCE_LOAD'))	// Appeared in 2.2.22
		{
			$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_BALANCE_LOAD');
		}
		if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_MASS_SUBSCRIBE_UNSUBSCRIBE'))	// Appeared in 2.2.25
		{
			$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_MASS_SUBSCRIBE_UNSUBSCRIBE');
		}

		// Remove the digests ACP module category, originally on the general tab. It will move to the extensions tab.
		if ($modules_tool->exists('acp', 'ACP_CAT_GENERAL', 'ACP_DIGEST_SETTINGS'))
		{
			$modules_tool->remove('acp', 'ACP_CAT_GENERAL', 'ACP_DIGEST_SETTINGS');
		}

		// ----- Remove UCP modules ----- //

		if ($modules_tool->exists('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS'))
		{
			$modules_tool->remove('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS');
		}
		if ($modules_tool->exists('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION'))	// Only in 2.2.6, gone in 2.2.7
		{
			$modules_tool->remove('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION');
		}
		if ($modules_tool->exists('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION'))	// Appeared in 2.2.7
		{
			$modules_tool->remove('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION');
		}
		if ($modules_tool->exists('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS'))
		{
			$modules_tool->remove('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS');
		}
		if ($modules_tool->exists('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA'))
		{
			$modules_tool->remove('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA');
		}
		
		// Remove the digests UCP module category
		if ($modules_tool->exists('ucp', 0, 'UCP_DIGESTS'))
		{
			$modules_tool->remove('ucp', 0, 'UCP_DIGESTS');
		}
		
		// ----- Add New ACP Modules ----- //
		
		// We need the module_id for the extensions tab to add a new category for Digests within the ACP
		$sql = 'SELECT module_id FROM ' . $this->table_prefix . "modules WHERE module_langname = 'ACP_CAT_DOT_MODS'";
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		$module_id = $rowset[0]['module_id'];
		$this->db->sql_freeresult();
		
		// Add Digests category
		$modules_tool->add('acp', 'ACP_CAT_DOT_MODS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '',
			'module_class'		=> 'acp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'ACP_CAT_DIGESTS',
			'module_mode'		=> '',
			'module_auth'		=> '',
			)
		);
		
		// We need the module_id for the newly created Digests category
		$sql = 'SELECT module_id FROM ' . $this->table_prefix . "modules WHERE module_langname = 'ACP_CAT_DIGESTS'";
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		$module_id = $rowset[0]['module_id'];
		$this->db->sql_freeresult();
		
		// Add General Settings ACP module
		$modules_tool->add('acp', 'ACP_CAT_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\acp\main_module',
			'module_class'		=> 'acp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'ACP_DIGESTS_GENERAL_SETTINGS',
			'module_mode'		=> 'digests_general',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);
		
		// Add User Default Settings ACP module
		$modules_tool->add('acp', 'ACP_CAT_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\acp\main_module',
			'module_class'		=> 'acp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'ACP_DIGESTS_USER_DEFAULT_SETTINGS',
			'module_mode'		=> 'digests_user_defaults',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);
		
		// Add Edit Subscribers ACP module
		$modules_tool->add('acp', 'ACP_CAT_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\acp\main_module',
			'module_class'		=> 'acp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'ACP_DIGESTS_EDIT_SUBSCRIBERS',
			'module_mode'		=> 'digests_edit_subscribers',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);
		
		// Add Balance Load ACP module
		$modules_tool->add('acp', 'ACP_CAT_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\acp\main_module',
			'module_class'		=> 'acp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'ACP_DIGESTS_BALANCE_LOAD',
			'module_mode'		=> 'digests_balance_load',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);
		
		// Add Mass subscribe/unsubscribe ACP module
		$modules_tool->add('acp', 'ACP_CAT_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\acp\main_module',
			'module_class'		=> 'acp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE',
			'module_mode'		=> 'digests_mass_subscribe_unsubscribe',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);
		
		// Add Digests Tests ACP module
		$modules_tool->add('acp', 'ACP_CAT_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\acp\main_module',
			'module_class'		=> 'acp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'ACP_DIGESTS_TEST',
			'module_mode'		=> 'digests_test',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);
		
		// ----- Add New UCP Modules ----- //
		
		// Add Digests category
		$modules_tool->add('ucp', 0 , array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '',
			'module_class'		=> 'ucp',
			'parent_id'			=> 0,
			'module_langname'	=> 'UCP_DIGESTS',
			'module_mode'		=> '',
			'module_auth'		=> '',
			)
		);
		
		// We need the module_id for the new Digests category
		$sql = 'SELECT module_id FROM ' . $this->table_prefix . "modules WHERE module_langname = 'UCP_DIGESTS'";
		$result = $this->db->sql_query($sql);
		$rowset = $this->db->sql_fetchrowset($result);
		$module_id = $rowset[0]['module_id'];
		$this->db->sql_freeresult();
		
		// Add Digests basics UCP module
		$modules_tool->add('ucp', 'UCP_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\ucp\main_module',
			'module_class'		=> 'ucp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'UCP_DIGESTS_BASICS',
			'module_mode'		=> 'basics',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);

		// Add Digests forums selection UCP module
		$modules_tool->add('ucp', 'UCP_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\ucp\main_module',
			'module_class'		=> 'ucp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'UCP_DIGESTS_FORUMS_SELECTION',
			'module_mode'		=> 'forums_selection',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);

		// Add Digests post filters UCP module
		$modules_tool->add('ucp', 'UCP_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\ucp\main_module',
			'module_class'		=> 'ucp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'UCP_DIGESTS_POST_FILTERS',
			'module_mode'		=> 'post_filters',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);

		// Add Digests additional criteria UCP module
		$modules_tool->add('ucp', 'UCP_DIGESTS', array(
			'module_enabled'	=> 1,
			'module_display'	=> 1,
			'module_basename'	=> '\phpbbservices\digests\ucp\main_module',
			'module_class'		=> 'ucp',
			'parent_id'			=> (int) $module_id,
			'module_langname'	=> 'UCP_DIGESTS_ADDITIONAL_CRITERIA',
			'module_mode'		=> 'additional_criteria',
			'module_auth'		=> 'ext_phpbbservices/digests && acl_a_board',
			)
		);

		// At this point we should be effectively installed. All the configuration variable for digests, columns in the users table for digests, and ACP and UCP
		// categories and modules exist and behave exactly as if a fresh install of this extension were made. All the phpBB 3.0 mod detritus should be gone.
		// Any digest cron that was set up will need to be removed manually, however.
		
		return true;
			
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\gold');
	}

	public function update_data()
	{
		return array(

			// Add Digest extension configuration variables
			array('config.add',	array('phpbbservices_digests_block_images', 0)),
			array('config.add', array('phpbbservices_digests_cron_task_last_gc', 0)), // timestamp when the digests mailer was last run
			array('config.add', array('phpbbservices_digests_cron_task_gc', (60 * 60))), // seconds between run; digests are sent hourly		
			array('config.add',	array('phpbbservices_digests_custom_stylesheet_path', 'prosilver/theme/digest_stylesheet.css')),
			array('config.add',	array('phpbbservices_digests_enable_auto_subscriptions', 0)),
			array('config.add',	array('phpbbservices_digests_enable_custom_stylesheets', 0)),
			array('config.add',	array('phpbbservices_digests_enable_log', 0)),
			array('config.add',	array('phpbbservices_digests_enable_subscribe_unsubscribe', '0')),
			array('config.add',	array('phpbbservices_digests_exclude_forums', '0')), 
			array('config.add',	array('phpbbservices_digests_from_email_name', '')),
			array('config.add',	array('phpbbservices_digests_from_email_address', '')),
			array('config.add',	array('phpbbservices_digests_host', 'phpbbservices.com')),
			array('config.add',	array('phpbbservices_digests_include_admins', '0')),
			array('config.add',	array('phpbbservices_digests_include_forums', '0')),
			array('config.add',	array('phpbbservices_digests_max_items', 0)),
			array('config.add',	array('phpbbservices_digests_notify_on_mass_subscribe', '1')), 
			array('config.add',	array('phpbbservices_digests_page_url', 'https://phpbbservices.com/digests_wp/')),
			array('config.add',	array('phpbbservices_digests_registration_field', 0)),
			array('config.add',	array('phpbbservices_digests_reply_to_email_address', '')),
			array('config.add',	array('phpbbservices_digests_show_email', 0)),
			array('config.add',	array('phpbbservices_digests_subscribe_all', '1')), 
			array('config.add',	array('phpbbservices_digests_test', 0)),
			array('config.add',	array('phpbbservices_digests_test_clear_spool', 1)),
			array('config.add',	array('phpbbservices_digests_test_day', 1)),
			array('config.add',	array('phpbbservices_digests_test_email_address', '')),
			array('config.add',	array('phpbbservices_digests_test_hour', 0)),
			array('config.add',	array('phpbbservices_digests_test_month', 1)),
			array('config.add',	array('phpbbservices_digests_test_send_to_admin', 0)),
			array('config.add',	array('phpbbservices_digests_test_spool', 0)),
			array('config.add',	array('phpbbservices_digests_test_time_use', 0)),
			array('config.add',	array('phpbbservices_digests_test_year', 2016)),
			array('config.add',	array('phpbbservices_digests_time_zone', $this->config['board_timezone'])),
			array('config.add',	array('phpbbservices_digests_user_check_all_forums', 1)),
			array('config.add',	array('phpbbservices_digests_user_digest_attachments', 1)),
			array('config.add',	array('phpbbservices_digests_user_digest_block_images', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_filter_type', 'ALL')),
			array('config.add',	array('phpbbservices_digests_user_digest_format', 'HTML')),
			array('config.add',	array('phpbbservices_digests_user_digest_max_display_words', -1)),
			array('config.add',	array('phpbbservices_digests_user_digest_max_posts', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_min_words', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_new_posts_only', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_no_post_text', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_pm_mark_read', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_registration', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_remove_foes', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_reset_lastvisit', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_send_hour_gmt', -1)),
			array('config.add',	array('phpbbservices_digests_user_digest_send_on_no_posts', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_show_mine', 1)),
			array('config.add',	array('phpbbservices_digests_user_digest_show_pms', 1)),
			array('config.add',	array('phpbbservices_digests_user_digest_sortby', 'board')),
			array('config.add',	array('phpbbservices_digests_user_digest_toc', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_type', 'DAY')),
			array('config.add',	array('phpbbservices_digests_users_per_page', 20)),
			array('config.add',	array('phpbbservices_digests_weekly_digest_day', 0)),
			
			// Add the ACP digests category under the extensions tab
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_CAT_DIGESTS'
			)),
			// Add the four ACP digest modules
			array('module.add', array(
				'acp',
				'ACP_CAT_DIGESTS',
				array(
					'module_basename'	=> '\phpbbservices\digests\acp\main_module',
					'modes'				=> array('digests_general', 'digests_user_defaults', 'digests_edit_subscribers', 'digests_balance_load', 'digests_mass_subscribe_unsubscribe', 'digests_test'),
				),
			)),

			// Add the UCP digests category, a top level category
			array('module.add', array(
				'ucp',
				0,
				'UCP_DIGESTS'
			)),
			// Add the four UCP digest modules
			array('module.add', array(
				'ucp', 
				'UCP_DIGESTS', 
				array(
					'module_basename'   => '\phpbbservices\digests\ucp\main_module',
					'modes' => array('basics', 'forums_selection', 'post_filters', 'additional_criteria'),
				),
			)),
						
		);
	}
	
	public function update_schema()
	{
		return array(
		
			'add_columns'        => array(
				$this->table_prefix . 'users'    => array(
					'user_digest_attachments' => array('TINT:4', 1),
					'user_digest_block_images' => array('TINT:4', 0),
					'user_digest_filter_type' => array('VCHAR:3', 'ALL'),
					'user_digest_format' => array('VCHAR:4', 'HTML'),
					'user_digest_has_unsubscribed' => array('TINT:4', 0),
					'user_digest_last_sent' => array('UINT:11', 0),
					'user_digest_max_display_words' => array('INT:4', 0),
					'user_digest_max_posts' => array('UINT', 0),
					'user_digest_min_words' => array('UINT', 0),
					'user_digest_new_posts_only' => array('TINT:4', 0),
					'user_digest_no_post_text' => array('TINT:4', 0),
					'user_digest_pm_mark_read' => array('TINT:4', 0),
					'user_digest_remove_foes' => array('TINT:4', 0),
					'user_digest_reset_lastvisit' => array('TINT:4', 1),
					'user_digest_send_hour_gmt' => array('DECIMAL', '0.00'),
					'user_digest_send_on_no_posts' => array('TINT:4', 0),
					'user_digest_show_mine' => array('TINT:4', 1),
					'user_digest_show_pms' => array('TINT:4', 1),
					'user_digest_sortby' => array('VCHAR:13', 'board'),
					'user_digest_toc' => array('TINT:4', 0),
					'user_digest_type' => array('VCHAR:4', 'NONE'),
				),
			),
	
			'add_tables'    => array(
				$this->table_prefix . 'digests_subscribed_forums'        => array(
					'COLUMNS'        => array(
						'user_id' => array('UINT', 0),
						'forum_id' => array('UINT', 0),
					),
					'PRIMARY_KEY'        => array('user_id', 'forum_id'),
				),
			),

		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'users'        => array(
					'user_digest_attachments',
					'user_digest_block_images',
					'user_digest_filter_type',
					'user_digest_format',
					'user_digest_has_unsubscribed',
					'user_digest_last_sent',
					'user_digest_max_display_words',
					'user_digest_max_posts',
					'user_digest_min_words',
					'user_digest_new_posts_only',
					'user_digest_no_post_text',
					'user_digest_pm_mark_read',
					'user_digest_remove_foes',
					'user_digest_reset_lastvisit',
					'user_digest_send_hour_gmt',
					'user_digest_send_on_no_posts',
					'user_digest_show_mine',
					'user_digest_show_pms',
					'user_digest_sortby',
					'user_digest_type',
					'user_digest_toc',
				),
			),

			'drop_tables'    => array(
				$this->table_prefix . 'digests_subscribed_forums',
			),

		);
	}
	
}
