<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2022 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

use phpbbservices\digests\constants\constants;

class release_3_3_16 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_3_14',
			'\phpbb\db\migration\data\v330\v330',
		);
	}

	public function update_data()
	{
		return array(array('custom', array(array($this, 'clean_up'))));
	}

	public function clean_up()
	{
		// This fixes a logical database inconsistency where inactive users might still have a digest subscription type set
		$this->db->sql_query('UPDATE ' . USERS_TABLE . " 
			SET user_digest_type = '" . constants::DIGESTS_NONE_VALUE . "' 
			WHERE user_type = " . USER_INACTIVE);
	}

}
