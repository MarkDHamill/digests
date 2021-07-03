<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_3_5 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_3_4',
			'\phpbb\db\migration\data\v330\v330',
		);
	}

	public function update_data()
	{
		return array(
			// Add new digest configuration variables
			array('config.add',	array('phpbbservices_digests_debug', 0)),
			array('config.add',	array('phpbbservices_digests_foreign_urls', 0)),
			array('config.add',	array('phpbbservices_digests_reset_cron_run_time', 0)),
		);
	}

}
