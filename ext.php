<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests;

/**
 * @ignore
 */
class ext extends \phpbb\extension\base
{

	public function is_enableable()
	{
		$config = $this->container->get('config');

		// Only phpBB 3.3 is now supported.
		if (
			phpbb_version_compare($config['version'], '3.3.0', '>=') &&
			phpbb_version_compare($config['version'], '4.0', '<')
		)
		{
			// Conditions met to install extension
			return true;
		}
		else
		{
			// Import the extension's language file
			$language = $this->container->get('language');
			$language->add_lang('common', 'phpbbservices/digests');

			// Return generic message indicating not all install requirements were met.
			return [$language->lang('DIGESTS_INSTALL_REQUIREMENTS')];
		}
	}

}
