<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2018 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

use phpbbservices\digests\core\common;

class release_3_2_1 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_0_7',
			'\phpbb\db\migration\data\v320\v320',
		);
	}

	public function update_data()
	{

		return array(

			array('config.add',		array('phpbbservices_digests_lowercase_digest_type', 0)),
			array('config.add',		array('phpbbservices_digests_show_forum_path', 0)),
			array('config.remove',	array('phpbbservices_digests_time_zone')),

		);

	}

}
