<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2019 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class release_3_0_5 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_langname = 'ACP_DIGESTS_RESET_CRON_RUN_TIME'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id !== false;
	}

	static public function depends_on()
	{
		return array(
			'\phpbb\db\migration\data\v31x\v319',
			'\phpbbservices\digests\migrations\release_3_0_2_modules',
			'\phpbbservices\digests\migrations\release_3_0_2_data',
			'\phpbbservices\digests\migrations\release_3_0_2_schema',
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
