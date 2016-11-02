<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class release_3_0_2_modules extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp'
				AND module_langname = 'ACP_DIGEST_SETTINGS'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id !== false;
	}

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\convert_mod_modules',
			'\phpbb\db\migration\data\v31x\v319',
		);
	}

	public function update_data()
	{
		return array(
			// Add the ACP digests category under the extensions tab
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_CAT_DIGESTS'
			)),
			// Add the four ACP digest modules
			array('module.add', array(
				'acp',
				'ACP_CAT_DIGESTS',
				array(
					'module_basename'	=> '\phpbbservices\digests\acp\main_module',
					'modes'				=> array('digests_general', 'digests_user_defaults', 'digests_edit_subscribers', 'digests_balance_load', 'digests_mass_subscribe_unsubscribe', 'digests_test'),
				),
			)),

			// Add the UCP digests category, a top level category
			array('module.add', array(
				'ucp',
				0,
				'UCP_DIGESTS',
			)),
			// Add the four UCP digest modules
			array('module.add', array(
				'ucp',
				'UCP_DIGESTS',
				array(
					'module_basename'   => '\phpbbservices\digests\ucp\main_module',
					'modes' => array('basics', 'forums_selection', 'post_filters', 'additional_criteria'),
				),
			)),
		);
	}
}
