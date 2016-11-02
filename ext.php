<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests;

use phpbbservices\digests\constants\constants;
use phpbbservices\digests\core\common;

/**
 * @ignore
 */
class ext extends \phpbb\extension\base
{
	public function is_enableable()
	{
<<<<<<< HEAD

		global $config, $user, $cache, $phpbb_root_path, $phpEx, $db, $table_prefix;
		
		$helper = new common();

		if (phpbb_version_compare($config['version'], '3.1.9', '<') || phpbb_version_compare($config['version'], '3.2.0@dev', '>='))
		{
			return false;
		}

		$user->add_lang_ext('phpbbservices/digests', array('info_acp_common', 'common'));

		if (trim($config['digests_version']) !== '')
		{

			// This handles upgrades from the phpBB Digests mod for phpBB 3.0.x to this extension, allowing configuration and user settings to be preserved
			// if possible. Versions 2.2.6 through 2.2.27 are supported.

			if ((phpbb_version_compare($config['digests_version'], '2.2.6') === -1) && (phpbb_version_compare($config['digests_version'], '2.2.27') === 1))
			{
				$message_type = E_USER_WARNING;
				trigger_error($user->lang('DIGESTS_MIGRATE_UNSUPPORTED_VERSION', $config['digests_version']), $message_type);
				return false;
			}

			// To upgrade from 2.2.6 or greater, the basic approach is to compare arrays of configuration variables and database column names.
			// Remove what has gone away, add what is missing. If there is a value already, retain it.

			// Load the configuration values for the extensionn for version 3.0.2 into an array.

			$new_config = array(
				'digests_block_images'                  => 0,
				'digests_cron_task_last_gc'             => 0, // timestamp when the digests mailer was last run
				'digests_cron_task_gc'                  => (60 * 60), // seconds between run; digests are sent hourly
				'digests_custom_stylesheet_path'        => '',
				'digests_enable_auto_subscriptions'     => 0,
				'digests_enable_custom_stylesheets'     => 0,
				'digests_enable_log'                    => 1,
				'digests_enable_subscribe_unsubscribe'  => 0,
				'digests_exclude_forums'                => 0,
				'digests_from_email_name'               => '',
				'digests_from_email_address'            => '',
				'digests_host'                          => 'phpbbservices.com',
				'digests_include_admins'                => 0,
				'digests_include_forums'                => 0,
				'digests_max_items'                     => 0,
				'digests_notify_on_admin_changes'		=> 1,
				'digests_page_url'                      => 'https://www.phpbbservices.com/digests_wp/',
				'digests_registration_field'            => 0,
				'digests_reply_to_email_address'        => '',
				'digests_show_email'                    => 0,
				'digests_subscribe_all'                 => '1',
				'digests_test'                          => 0,
				'digests_test_clear_spool'              => 1,
				'digests_test_day'                      => date('j'),
				'digests_test_email_address'            => '',
				'digests_test_hour'                     => date('G'),
				'digests_test_month'                    => date('n'),
				'digests_test_send_to_admin'            => 0,
				'digests_test_spool'                    => 0,
				'digests_test_time_use'                 => 0,
				'digests_test_year'                     => date('Y'),
				'digests_time_zone'                     => $helper->make_tz_offset($config['board_timezone']),
				'digests_user_check_all_forums'         => 1,
				'digests_user_digest_attachments'       => 1,
				'digests_user_digest_block_images'      => 0,
				'digests_user_digest_filter_type'       => constants::DIGESTS_ALL,
				'digests_user_digest_format'            => constants::DIGESTS_HTML_VALUE,
				'digests_user_digest_max_display_words' => -1,
				'digests_user_digest_max_posts'         => 0,
				'digests_user_digest_min_words'         => 0,
				'digests_user_digest_new_posts_only'    => 0,
				'digests_user_digest_no_post_text'      => 0,
				'digests_user_digest_pm_mark_read'      => 0,
				'digests_user_digest_registration'      => 0,
				'digests_user_digest_remove_foes'       => 0,
				'digests_user_digest_reset_lastvisit'   => 0,
				'digests_user_digest_send_hour_gmt'     => -1,
				'digests_user_digest_send_on_no_posts'  => 0,
				'digests_user_digest_show_mine'         => 1,
				'digests_user_digest_show_pms'          => 1,
				'digests_user_digest_sortby'            => constants::DIGESTS_SORTBY_BOARD,
				'digests_user_digest_toc'               => 0,
				'digests_user_digest_type'              => constants::DIGESTS_DAILY_VALUE,
				'digests_users_per_page'                => 20,
				'digests_weekly_digest_day'             => 0,
			);

			$remove_config = array();

			// If the old configuration value exists, keep its value but it must change its name to add the vendor name as a prefix
			foreach ($config as $key => $value)
			{
				if (substr($key, 0, 8) == 'digests_')
				{
					if (array_key_exists($key, $new_config))
					{
						// add the new config value with vendor in the config_name
						$config->set('phpbbservices_' . $key, $value);
					}
					// mark the old config value for deletion once outside of the loop
					$remove_config[] = $key;
				}
			}

			// Remove the old configuration variables, i.e. digests_* rather than phpbbservices_digests_*
			foreach ($remove_config as $key => $value)
			{
				$config->delete($value);
			}

			// Add in any new configuration variables using the defaults. These were introduced by later versions of digests or are new in the extension
			// and all must have phpbbservices_ as a prefix.
			foreach ($new_config as $key => $value)
			{
				if (array_key_exists($key, $config) === false)
				{
					$config->set('phpbbservices_' . $key, $value);
				}
			}

			// Modify problematic configuration variables explicitly. The digests page is now in Wordpress.
			$config->set('phpbbservices_digests_page_url', 'https://www.phpbbservices.com/digests_wp/');

			// ----- Fix users table ----- //
			
			$new_columns = array(
				'user_digest_attachments'       => array('TINT:4', 1),
				'user_digest_block_images'      => array('TINT:4', 0),
				'user_digest_filter_type'       => array('VCHAR:3', 'ALL'),
				'user_digest_format'            => array('VCHAR:4', 'HTML'),
				'user_digest_has_unsubscribed'  => array('TINT:4', 0),
				'user_digest_last_sent'         => array('UINT:11', 0),
				'user_digest_max_display_words' => array('INT:4', 0),
				'user_digest_max_posts'         => array('UINT', 0),
				'user_digest_min_words'         => array('UINT', 0),
				'user_digest_new_posts_only'    => array('TINT:4', 0),
				'user_digest_no_post_text'      => array('TINT:4', 0),
				'user_digest_pm_mark_read'      => array('TINT:4', 0),
				'user_digest_remove_foes'       => array('TINT:4', 0),
				'user_digest_reset_lastvisit'   => array('TINT:4', 1),
				'user_digest_send_hour_gmt'     => array('DECIMAL', '0.00'),
				'user_digest_send_on_no_posts'  => array('TINT:4', 0),
				'user_digest_show_mine'         => array('TINT:4', 1),
				'user_digest_show_pms'          => array('TINT:4', 1),
				'user_digest_sortby'            => array('VCHAR:13', 'board'),
				'user_digest_toc'               => array('TINT:4', 0),
				'user_digest_type'              => array('VCHAR:4', 'NONE'),
			);

			// Get a succinct array of new column names without metadata to make it easier to find missing columns
			$new_column_names = array_keys($new_columns);

			$found_digest_columns = array();

			// The tools class has some convenient methods we will use to add and remove columns
			$tools = new \phpbb\db\tools($db);

			// Get a list of the current columns in the phpbb_users table.
			$user_table_columns = array_keys($tools->sql_list_columns($table_prefix . 'users'));

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
				$tools->sql_column_remove($table_prefix . 'users', $value);
			}

			// Add those digest columns to the phpbb_users table were not in the version of the mod previously installed
			$columns_to_add = array_diff($new_column_names, $found_digest_columns);
			foreach ($columns_to_add as $key => $value)
			{
				$tools->sql_column_add($table_prefix . 'users', $value, array($new_columns[$value][0], $new_columns[$value][1]));
			}

			// ----- Remove old ACP Modules ----- //

			// Use the module class to conveniently remove dead modules as well as add new ones
			$modules_table = $table_prefix . 'modules';
			$modules_tool = new \phpbb\db\migration\tool\module($db, $cache, $user, $phpbb_root_path, $phpEx, $modules_table);

			// Remove ACP modules if they exist.
			if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_GENERAL_SETTINGS'))
			{
				$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_GENERAL_SETTINGS');
			}
			if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_USER_DEFAULT_SETTINGS'))
			{
				$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_USER_DEFAULT_SETTINGS');
			}
			if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_EDIT_SUBSCRIBERS'))    // Appeared in 2.2.16
			{
				$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_EDIT_SUBSCRIBERS');
			}
			if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_BALANCE_LOAD'))    // Appeared in 2.2.22
			{
				$modules_tool->remove('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_BALANCE_LOAD');
			}
			if ($modules_tool->exists('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_MASS_SUBSCRIBE_UNSUBSCRIBE'))    // Appeared in 2.2.25
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
			if ($modules_tool->exists('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION'))    // Only in 2.2.6, gone in 2.2.7
			{
				$modules_tool->remove('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION');
			}
			if ($modules_tool->exists('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION'))    // Appeared in 2.2.7
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

			// ----- Add Modules ----- //
			
			// Add the ACP digests category under the extensions tab
			$modules_tool->add(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_CAT_DIGESTS'
			);
			// Add the four ACP digest modules
			$modules_tool->add(
				'acp',
				'ACP_CAT_DIGESTS',
				array(
					'module_basename'	=> '\phpbbservices\digests\acp\main_module',
					'modes'				=> array('digests_general', 'digests_user_defaults', 'digests_edit_subscribers', 'digests_balance_load', 'digests_mass_subscribe_unsubscribe', 'digests_test'),
				)
			);

			// Add the UCP digests category, a top level category
			$modules_tool->add(
			   'ucp',
			   false,
			   'UCP_DIGESTS'
			);
			// Add the four UCP digest modules
			$modules_tool->add(
				'ucp', 
				'UCP_DIGESTS', 
				array(
					'module_basename'   => '\phpbbservices\digests\ucp\main_module',
					'modes' => array('basics', 'forums_selection', 'post_filters', 'additional_criteria'),
				)
			);
			
		}

		return true;

=======
		$config = $this->container->get('config');
		return phpbb_version_compare($config['version'], '3.1.9', '>=') &&
			phpbb_version_compare($config['version'], '3.2.0@dev', '<') &&
			phpbb_version_compare($config['digests_version'], '2.2.6', '<');
>>>>>>> origin/master
	}
}
