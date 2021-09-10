<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\acp;

class main_module
{

	public $tpl_name;
	public $u_action;

	/**
	 * Main ACP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	public function main($id, $mode)
	{

		global $phpbb_container;

		/** @var \phpbbservices\digests\controller\acp_controller $acp_controller */
		$acp_controller = $phpbb_container->get('phpbbservices.digests.controller.acp');

		/** @var \phpbb\language\language $language */
		$this->language = $phpbb_container->get('language');
		$this->language->add_lang('common', 'phpbbservices/digests');

		// Load a template from adm/style for our ACP pages
		$this->tpl_name = 'acp_digests';

		// Set the page title for our ACP pages (modules)
		switch ($mode)
		{

			case 'digests_general':
			default:
				$this->page_title = $this->language->lang('ACP_DIGESTS_GENERAL_SETTINGS');
			break;

			case 'digests_user_defaults':
				$this->page_title = $this->language->lang('ACP_DIGESTS_USER_DEFAULT_SETTINGS');
			break;

			case 'digests_edit_subscribers':
				$this->page_title = $this->language->lang('ACP_DIGESTS_EDIT_SUBSCRIBERS');
			break;

			case 'digests_balance_load':
				$this->page_title = $this->language->lang('ACP_DIGESTS_BALANCE_LOAD');
			break;

			case 'digests_mass_subscribe_unsubscribe':
				$this->page_title = $this->language->lang('ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE');
			break;

			case 'digests_test':
				$this->page_title = $this->language->lang('ACP_DIGESTS_TEST');
			break;

			case 'digests_reset_cron_run_time':
				$this->page_title = $this->language->lang('ACP_DIGESTS_RESET_CRON_RUN_TIME');
			break;

		}

		// Load the display options handle in our ACP controller, passing the mode
		$acp_controller->display_options($mode, $this->u_action);
	}

}
