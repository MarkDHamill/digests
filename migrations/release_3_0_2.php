<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

use phpbbservices\digests\constants\constants;
use phpbbservices\digests\core\common;

class release_3_0_2 extends \phpbb\db\migration\migration
{

	public function effectively_installed()
	{
		return isset($this->config['phpbbservices_digests_weekly_digest_day']) ? true : false;
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\gold');
	}

	public function update_data()
	{
		
		$helper = new common();
		
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
			array('config.add',	array('phpbbservices_digests_time_zone', $helper->make_tz_offset($this->config['board_timezone']))),
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
			   false,
			   'UCP_DIGESTS',
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
	
	/*public function revert_data()
	{
		return array(
			'if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA')),
			),
			'if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS')),
			),
			'if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION')),
			),
			'if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS')),
			),
			'if', array(
				array('module.exists', array('ucp', false, 'UCP_DIGESTS')),
				array('module.remove', array('ucp', false, 'UCP_DIGESTS')),
			),
		);
	}*/
	
}
