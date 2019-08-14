<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2019 Mark D. Hamill (mark@phpbbservices.com)
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
		// Clean up. Prior to Digests 3.2.15, if a user was deleted in the ACP, their digest subscribed forums were not deleted.
		$this->db->sql_query('DELETE FROM ' . $this->table_prefix . 'digests_subscribed_forums
			WHERE user_id NOT IN (SELECT user_id FROM ' . $this->table_prefix . 'users)');
		return array();
	}

	public function revert_data()
	{
		// Clean up. Remove the extension's directory and any files inside it.
		$this->rrmdir('./../store/phpbbservices/digests');
		return array();
	}

	private function rrmdir($dir)
	{

		// Recursively removes files in a directory
		if (is_dir($dir))
		{
			$inodes = scandir($dir);
			if (is_array($inodes))
			{
				foreach ($inodes as $inode)
				{
					if ($inode != "." && $inode != "..")
					{
						if (is_dir($dir . "/" . $inode))
						{
							rrmdir($dir . "/" . $inode);
						}
						else
						{
							unlink($dir . "/" . $inode);
						}
					}
				}
				rmdir($dir);
			}
		}

	}
}
