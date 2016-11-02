<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class convert_mod_data extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return !$this->config->offsetExists('digests_version');
	}

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\convert_mod_modules',
			'\phpbb\db\migration\data\v31x\v319',
		);
	}

	public function update_data()
	{
		return array(
			array('custom', array(array($this, 'update_configs'))),
		);
	}

	public function update_configs()
	{
		// Load the new configuration values array
		$new_config = array(
			'digests_block_images',
			'digests_cron_task_last_gc',
			'digests_cron_task_gc',
			'digests_custom_stylesheet_path',
			'digests_enable_auto_subscriptions',
			'digests_enable_custom_stylesheets',
			'digests_enable_log',
			'digests_enable_subscribe_unsubscribe',
			'digests_exclude_forums',
			'digests_from_email_name',
			'digests_from_email_address',
			'digests_host',
			'digests_include_admins',
			'digests_include_forums',
			'digests_max_items',
			'digests_notify_on_admin_changes',
			'digests_registration_field',
			'digests_reply_to_email_address',
			'digests_show_email',
			'digests_subscribe_all',
			'digests_test',
			'digests_test_clear_spool',
			'digests_test_day',
			'digests_test_email_address',
			'digests_test_hour',
			'digests_test_month',
			'digests_test_send_to_admin',
			'digests_test_spool',
			'digests_test_time_use',
			'digests_test_year',
			'digests_time_zone',
			'digests_user_check_all_forums',
			'digests_user_digest_attachments',
			'digests_user_digest_block_images',
			'digests_user_digest_filter_type',
			'digests_user_digest_format',
			'digests_user_digest_max_display_words',
			'digests_user_digest_max_posts',
			'digests_user_digest_min_words',
			'digests_user_digest_new_posts_only',
			'digests_user_digest_no_post_text',
			'digests_user_digest_pm_mark_read',
			'digests_user_digest_registration',
			'digests_user_digest_remove_foes',
			'digests_user_digest_reset_lastvisit',
			'digests_user_digest_send_hour_gmt',
			'digests_user_digest_send_on_no_posts',
			'digests_user_digest_show_mine',
			'digests_user_digest_show_pms',
			'digests_user_digest_sortby',
			'digests_user_digest_toc',
			'digests_user_digest_type',
			'digests_users_per_page',
			'digests_weekly_digest_day',
		);

		$old_config = array();

		// Get all digests_ configs
		$sql = 'SELECT *
			FROM ' . $this->table_prefix . 'config
			WHERE config_name ' . $this->db->sql_like_expression('digests_' . $this->db->get_any_char());
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$old_config[$row['config_name']] = $row['config_value'];
		}
		$this->db->sql_freeresult($result);


		// If the old configuration value exists, keep its value but it must change its name to add the vendor name as a prefix
		foreach ($old_config as $key => $value)
		{
			if (array_key_exists($key, $new_config))
			{
				// add the new config value with vendor in the config_name
				$this->config->set('phpbbservices_' . $key, $value);
			}
			$this->config->delete($key);
		}
	}
}
