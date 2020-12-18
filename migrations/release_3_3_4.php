<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_3_4 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_2_17',
			'\phpbb\db\migration\data\v320\v320',
		);
	}

	public function update_data()
	{
		return array(
			array('config.remove', array('phpbbservices_digests_user_check_all_forums')),
		);
	}

}