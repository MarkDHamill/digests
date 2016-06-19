<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

if (!defined('IN_PHPBB'))
{
	exit;
}

use phpbb\db\tools;
use phpbb\db\migration\tool\module;

class release_3_0_5 extends \phpbb\db\migration\migration
{
	
	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_0_2',
			'\phpbb\db\migration\data\v31x\v319',
		);
	}

	public function update_data()
	{
		return array(

			// Add a new ACP digest modules
			array('module.add', array(
				'acp',
				'ACP_CAT_DIGESTS',
				array(
					'module_basename'	=> '\phpbbservices\digests\acp\main_module',
					'modes'				=> array('digests_reset_cron_run_time'),
				),
			)),
						
		);
	}
}
