<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2022 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_3_14 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_3_13',
		);
	}

	public function update_schema()
	{

		return array(
			'add_columns' => array(
				$this->table_prefix . 'users' => array(
					'user_digest_last_sent_for' => array('UINT:11', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'        => array(
				$this->table_prefix . 'users'        => array(
					'user_digest_last_sent_for',
				),
			),
		);
	}

}
