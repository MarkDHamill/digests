<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_2_10 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_2_7',
			'\phpbb\db\migration\data\v320\v320',
		);
	}

	public function update_data()
	{
		return array(
			// Add new digest configuration variables
			array('config.add',	array('phpbbservices_digests_min_popularity_size', 5)),
			array('config.add',	array('phpbbservices_digests_user_digest_popular', 0)),
			array('config.add',	array('phpbbservices_digests_user_digest_popularity_size', 5)),
		);
	}

	public function update_schema()
	{
		return array(
			'add_columns'        => array(
				$this->table_prefix . 'users'    => array(
					'user_digest_popular' => array('TINT:4', 0),
					'user_digest_popularity_size' => array('UINT', 5),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'users'        => array(
					'user_digest_popular',
					'user_digest_popularity_size',
				),
			),
		);
	}

}
