<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests;

use phpbbservices\digests\constants\constants;
use phpbbservices\digests\core\common;

/**
 * @ignore
 */
class ext extends \phpbb\extension\base
{
	public function is_enableable()
	{

		global $config;

		if (phpbb_version_compare($config['version'], '3.1.9', '<') || phpbb_version_compare($config['version'], '3.2.0@dev', '>='))
		{
			return false;
		}
		return true;

	}
}
