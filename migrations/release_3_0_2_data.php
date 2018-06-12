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

class release_3_0_2_data extends \phpbb\db\migration\migration
{


	public function effectively_installed()
	{
		return $this->config->offsetExists('phpbbservices_digests_enable_auto_subscriptions');
	}

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\convert_mod_data',
			'\phpbb\db\migration\data\v31x\v319',
		);
	}

	public function update_data()
	{

		return array(
		
			// Add Digest extension configuration variables
			array('config.add',	array('phpbbservices_digests_block_images', 0)),
			array('config.add', array('phpbbservices_digests_cron_task_last_gc', 0)), // timestamp when the digests mailer was last run
			array('config.add', array('phpbbservices_digests_cron_task_gc', (60 * 60))), // seconds between runs -- digests are sent hourly		
			array('config.add',	array('phpbbservices_digests_custom_stylesheet_path', '')),
			array('config.add',	array('phpbbservices_digests_enable_auto_subscriptions', 0)),
			array('config.add',	array('phpbbservices_digests_enable_custom_stylesheets', 0)),
			array('config.add',	array('phpbbservices_digests_enable_log', 1)),
			array('config.add',	array('phpbbservices_digests_enable_subscribe_unsubscribe', 0)),
			array('config.add',	array('phpbbservices_digests_exclude_forums', 0)), 
			array('config.add',	array('phpbbservices_digests_from_email_name', '')),
			array('config.add',	array('phpbbservices_digests_from_email_address', '')),
			array('config.add',	array('phpbbservices_digests_host', 'phpbbservices.com')),
			array('config.add',	array('phpbbservices_digests_include_admins', 0)),
			array('config.add',	array('phpbbservices_digests_include_forums', 0)),
			array('config.add',	array('phpbbservices_digests_max_items', 0)),
			array('config.add',	array('phpbbservices_digests_notify_on_admin_changes', 1)), 
			array('config.add',	array('phpbbservices_digests_page_url', 'https://www.phpbbservices.com/digests_wp/')),
			array('config.add',	array('phpbbservices_digests_registration_field', 0)),
			array('config.add',	array('phpbbservices_digests_reply_to_email_address', '')),
			array('config.add',	array('phpbbservices_digests_show_email', 0)),
			array('config.add',	array('phpbbservices_digests_subscribe_all', 1)), 
			array('config.add',	array('phpbbservices_digests_test', 0)),
			array('config.add',	array('phpbbservices_digests_test_clear_spool', 1)),
			array('config.add',	array('phpbbservices_digests_test_day', date('j'))),
			array('config.add',	array('phpbbservices_digests_test_email_address', '')),
			array('config.add',	array('phpbbservices_digests_test_hour', date('G'))),
			array('config.add',	array('phpbbservices_digests_test_month', date('n'))),
			array('config.add',	array('phpbbservices_digests_test_send_to_admin', 0)),
			array('config.add',	array('phpbbservices_digests_test_spool', 0)),
			array('config.add',	array('phpbbservices_digests_test_time_use', 0)),
			array('config.add',	array('phpbbservices_digests_test_year', date('Y'))),
			array('config.add',	array('phpbbservices_digests_user_check_all_forums', 1)),
			array('config.add',	array('phpbbservices_digests_user_digest_attachments', 1)),
			array('config.add',	array('phpbbservices_digests_user_digest_block_images', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_filter_type', constants::DIGESTS_ALL)),
			array('config.add',	array('phpbbservices_digests_user_digest_format', constants::DIGESTS_HTML_VALUE)),
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
			array('config.add',	array('phpbbservices_digests_user_digest_sortby', constants::DIGESTS_SORTBY_BOARD)),
			array('config.add',	array('phpbbservices_digests_user_digest_toc', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_type', constants::DIGESTS_DAILY_VALUE)),
			array('config.add',	array('phpbbservices_digests_users_per_page', 20)),
			array('config.add',	array('phpbbservices_digests_weekly_digest_day', 0)),

		);
	}

}
