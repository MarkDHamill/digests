<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2018 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

use phpbbservices\digests\constants\constants;
use phpbbservices\digests\core\common;

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

		// To upgrade from the digests mod, the basic approach is to compare arrays of configuration variables and database column names.
		// Remove what has gone away, add what is missing. If there is a value already, retain it.

		// Load the configuration values for the extension for version 3.2.7 into an array.

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
			'digests_lowercase_digest_type'			=> 0,
			'digests_max_cron_hrs'					=> 0,
			'digests_max_items'                     => 0,
			'digests_notify_on_admin_changes'		=> 1,
			'digests_page_url'                      => 'https://www.phpbbservices.com/my-software/digests_wp/digests-extension/',
			'digests_registration_field'            => 0,
			'digests_reply_to_email_address'        => '',
			'digests_show_email'                    => 0,
			'digests_show_forum_path'				=> 0,
			'digests_strip_tags'					=> '',
			'digests_subscribe_all'                 => 1,
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

	}

}
