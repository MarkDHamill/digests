<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_2_15 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_2_10',
			'\phpbb\db\migration\data\v320\v320',
		);
	}

	public function update_data()
	{
		return array(array('custom', array(array($this, 'clean_up'))));
	}

	public function revert_data()
	{
		return array(array('custom', array(array($this, 'remove_files'))));
	}

	public function clean_up()
	{
		// Prior to Digests 3.2.15, if a user was deleted in the ACP, their digest subscribed forums were not deleted.
		if ($this->db_tools->sql_table_exists($this->table_prefix . 'digests_subscribed_forums'))
		{
			$this->db->sql_query('DELETE FROM ' . $this->table_prefix . 'digests_subscribed_forums
				WHERE user_id NOT IN (SELECT user_id FROM ' . $this->table_prefix . 'users)');
		}
	}

	public function remove_files()
	{

		// Remove the extension's directory and any files inside it.
		global $phpbb_container;

		$filesystem = $phpbb_container->get('filesystem');
		$filepath = $this->phpbb_root_path . 'store/phpbbservices/digests';
		if ($filesystem->exists($filepath))
		{
			$filesystem->remove($filepath);
		}
		
	}

}
