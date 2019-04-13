<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2019 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class release_3_0_7 extends \phpbb\db\migration\migration
{
	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_0_5',
		);
	}

	public function update_data()
	{
		return array(
			// Add new digest configuration variable
			array('config.add',	array('phpbbservices_digests_max_cron_hrs', 0)),
		);
	}
}
