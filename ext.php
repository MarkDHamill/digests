<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
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
<<<<<<< HEAD
		// phpBB 3.2 is not supported. phpBB 3.1.9 and above is required.
		return ( phpbb_version_compare($config['version'], '3.1.9', '>=') || phpbb_version_compare($config['version'], '3.2.0@dev', '<') );
=======
		// phpBB 3.2 is supported. phpBB 3.1 is not due to changes in TWIG.
		return ( phpbb_version_compare($config['version'], '3.2.0', '>=') || (phpbb_version_compare($config['version'], '3.3', '<')) );
>>>>>>> 3.2.0-RC7
	}
}
