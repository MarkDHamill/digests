<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class release_3_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return false;
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
			array('config.add',	array('phpbbservices_digests_custom_stylesheet_path', 'prosilver/theme/digest_stylesheet.css')),
			array('config.add',	array('phpbbservices_digests_digests_title','Digests')),	// Probably not needed
			array('config.add',	array('phpbbservices_digests_enable_auto_subscriptions', 0)),
			array('config.add',	array('phpbbservices_digests_enable_custom_stylesheets', 0)),
			array('config.add',	array('phpbbservices_digests_enable_log', 0)),
			array('config.add',	array('phpbbservices_digests_enable_subscribe_unsubscribe', '0')),
			//array('config.add',	array('phpbbservices_digests_enabled', 1)),
			array('config.add',	array('phpbbservices_digests_exclude_forums', '0')), 
			array('config.add',	array('phpbbservices_digests_from_email_name', '')),
			array('config.add',	array('phpbbservices_digests_from_email_address', '')),
			array('config.add',	array('phpbbservices_digests_host', 'phpbbservices.com')),
			array('config.add',	array('phpbbservices_digests_include_admins', '0')),
			array('config.add',	array('phpbbservices_digests_include_forums', '0')),
			//array('config.add',	array('phpbbservices_digests_include_inactive', '0')), 
			array('config.add',	array('phpbbservices_digests_key_value', '')),
			array('config.add',	array('phpbbservices_digests_mailed_date', 0)),
			array('config.add',	array('phpbbservices_digests_mailed_successfully', 1)),
			array('config.add',	array('phpbbservices_digests_max_items', 0)),
			array('config.add',	array('phpbbservices_digests_notify_on_mass_subscribe', '1')), 
			array('config.add',	array('phpbbservices_digests_override_queue', 1)),
			array('config.add',	array('phpbbservices_digests_page_url', 'https://phpbbservices.com/digests_wp/')),
			array('config.add',	array('phpbbservices_digests_registration_field', 0)),
			array('config.add',	array('phpbbservices_digests_reply_to_email_address', '')),
			array('config.add',	array('phpbbservices_digests_require_key', 0)),
			array('config.add',	array('phpbbservices_digests_show_email', 0)),
			array('config.add',	array('phpbbservices_digests_show_output', 1)),
			array('config.add',	array('phpbbservices_digests_subscribe_all', '1')), 
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
					'modes'				=> array('digests_general', 'digests_user_defaults', 'digests_edit_subscribers', 'digests_balance_load', 'digests_mass_subscribe_unsubscribe'),
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
					'user_digest_type' => array('VCHAR:4', 'NONE'),
					'user_digest_toc' => array('TINT:4', 0),
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
