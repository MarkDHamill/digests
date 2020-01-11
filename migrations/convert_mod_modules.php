<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

class convert_mod_modules extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		// If ACP_DIGEST_SETTINGS is not in the modules table, there are no modules from the digests mod to worry about.
		// Instead, the modules will be created in release_3_0_2_modules.php.
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules 
			WHERE module_class = 'acp'
				AND module_langname = 'ACP_DIGEST_SETTINGS'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id === false;
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v319');
	}

	public function update_data()
	{
		// This is a hopefully temporary means to get around phpBB module tool bugs in phpBB 3.2. This worked on phpBB 3.1
		// but phpBB 3.2 won't remove the UCP_DIGESTS category, triggering an error.
		return array(
			// ----- Remove old ACP Modules, note the old pattern of ACP_DIGEST_% instead of ACP_DIGESTS_% used for the mod ----- //
			array('if', array(
				array('module.exists', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_GENERAL_SETTINGS')),
				array('module.remove', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_GENERAL_SETTINGS')),
			)),
			array('if', array(
				array('module.exists', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_USER_DEFAULT_SETTINGS')),
				array('module.remove', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_USER_DEFAULT_SETTINGS')),
			)),
			array('if', array(
				array('module.exists', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_EDIT_SUBSCRIBERS')), // Appeared in 2.2.16
				array('module.remove', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_EDIT_SUBSCRIBERS')),
			)),
			array('if', array(
				array('module.exists', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_BALANCE_LOAD')), // Appeared in 2.2.22
				array('module.remove', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_BALANCE_LOAD')),
			)),
			array('if', array(
				array('module.exists', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_MASS_SUBSCRIBE_UNSUBSCRIBE')), // Appeared in 2.2.25
				array('module.remove', array('acp', 'ACP_DIGEST_SETTINGS', 'ACP_DIGEST_MASS_SUBSCRIBE_UNSUBSCRIBE')),
			)),
			array('if', array(
				array('module.exists', array('acp', false, 'ACP_DIGEST_SETTINGS')),
				array('module.remove', array('acp', false, 'ACP_DIGEST_SETTINGS')),
			)),

			// ----- Remove UCP modules ----- //
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION')), // Only in 2.2.6, gone in 2.2.7
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION')), // Appeared in 2.2.7
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA')),
			)),
			array('if', array(
				array('module.exists', array('ucp', false, 'UCP_DIGESTS')),
				array('module.remove', array('ucp', false, 'UCP_DIGESTS')),
			)),
		);
	}
}
