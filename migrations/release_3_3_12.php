<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2020 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\migrations;

class release_3_3_12 extends \phpbb\db\migration\migration
{

	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_3_5',
			'\phpbb\db\migration\data\v330\v330',
		);
	}

	public function update_data()
	{
		// Fix longstanding issue: the configuration variable phpbbservices_digests_cron_task_last_gc must set is_dynamic column to true.
		// Otherwise value is pulled from cache, so when mailing digests it will think it's necessary to send digests again.
		$last_gc = $this->config->offsetGet('phpbbservices_digests_cron_task_last_gc');
		$this->config->delete('phpbbservices_digests_cron_task_last_gc');
		$this->config->set('phpbbservices_digests_cron_task_last_gc', $last_gc, false);

		return array(
			// Remove functionality tied to the test button
			array('config.remove', array('phpbbservices_digests_test')),
			// Add the clear cached digests ACP module
			array('module.add', array(
				'acp',
				'ACP_CAT_DIGESTS',
				array(
					'module_basename'	=> '\phpbbservices\digests\acp\main_module',
					'modes'				=> array('digests_clear_cached'),
				)),
			)
		);
	}

}