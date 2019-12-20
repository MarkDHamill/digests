<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2019 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_2_17 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_2_16',
			'\phpbb\db\migration\data\v320\v320',
		);
	}

	public function update_data()
	{
		return array(
			// Remove obsolete configuration variables
			array('config.remove', array('phpbbservices_digests_test_year')),
			array('config.remove', array('phpbbservices_digests_test_month')),
			array('config.remove', array('phpbbservices_digests_test_day')),
			array('config.remove', array('phpbbservices_digests_test_hour')),
			array('config.remove', array('phpbbservices_digests_test_time_use')),
			// Add new digest configuration variable
			array('config.add',	array('phpbbservices_digests_test_date_hour', '')),
		);
	}

}