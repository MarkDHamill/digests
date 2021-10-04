<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\ucp;

class main_module
{

	public $tpl_name;
	public $u_action;

	protected $language;
	protected $phpbb_container;

	/**
	 * Main UCP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */

	function main($id, $mode)
	{

		global $phpbb_container;

		$this->phpbb_container = $phpbb_container;

		/** @var \phpbbservices\digests\controller\ucp_controller $ucp_controller */
		$ucp_controller = $phpbb_container->get('phpbbservices.digests.controller.ucp');

		/** @var \phpbb\language\language $language */
		$this->language = $phpbb_container->get('language');
		$this->language->add_lang('common', 'phpbbservices/digests');

		// Load a template from adm/style for our ACP pages
		$this->tpl_name = 'ucp_digests';

		$this->language->add_lang(array('common', 'acp/common'), 'phpbbservices/digests');

		// Set the page title for our ACP pages (modules)
		switch ($mode)
		{

			case 'basics':
			default:
				$this->page_title = $this->language->lang('UCP_DIGESTS_BASICS');
			break;

			case 'forums_selection':
				$this->page_title = $this->language->lang('UCP_DIGESTS_FORUMS_SELECTION');
			break;

			case 'post_filters':
				$this->page_title = $this->language->lang('UCP_DIGESTS_POST_FILTERS');
			break;

			case 'additional_criteria':
				$this->page_title = $this->language->lang('UCP_DIGESTS_ADDITIONAL_CRITERIA');
			break;

		}

		// Load the display options handle in our ACP controller, passing the mode
		$ucp_controller->display_options($mode, $this->u_action);

	}

}
