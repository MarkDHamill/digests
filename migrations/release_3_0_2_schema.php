<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class release_3_0_2_schema extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\convert_mod_schema',
			'\phpbb\db\migration\data\v31x\v319',
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
