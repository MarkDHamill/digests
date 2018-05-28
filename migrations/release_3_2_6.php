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

class release_3_2_6 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_2_1',
			'\phpbb\db\migration\data\v320\v320',
		);
	}

	public function update_data()
	{

		return array(
			array('config.update',	array('phpbbservices_digests_page_url', 'https://www.phpbbservices.com/my-software/digests_wp/digests-extension/')),
		);

	}

}
