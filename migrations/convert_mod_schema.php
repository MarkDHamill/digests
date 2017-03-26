<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class convert_mod_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return !$this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_digest_attachments');
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
			array('custom', array(array($this, 'update_columns'))),
		);
	}
	
	public function update_columns()
	{
		
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
		
	}
	
}
