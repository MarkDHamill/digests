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

		// phpBB 3.2 is supported. phpBB 3.1 is not due to changes in TWIG.
		return ( phpbb_version_compare($config['version'], '3.2.0', '>=') || (phpbb_version_compare($config['version'], '3.3', '<')) );
	}

	public function enable_step($old_state)
	{

		// Connect with some necessary services
		$request = $this->container->get('request');

		// Add language strings needed
		switch ($old_state)
		{
			case '': // Empty means nothing has run yet

				// Create the cache/phpbbservices/digests folder
				umask(0);
				$made_directories = false;
				$digests_cache_path = './../cache/phpbbservices/digests/';	// Installer should be in the adm folder
				$ptr = strpos($digests_cache_path, '/', 5);

				while ($ptr !== false)
				{
					$current_path = substr($digests_cache_path, 0, $ptr);
					$ptr = strpos($digests_cache_path, '/', $ptr + 1);
					if (!is_dir($current_path))
					{
						@mkdir($current_path, 0777);
					}
					$made_directories = true;
				}

				if ($made_directories)
				{
					// For Apache-based systems, the digest cache directory requires a .htaccess file with Allow from All permissions so a browser can read files.
					$server_software = $request->server('SERVER_SOFTWARE');
					if (stristr($server_software, 'Apache'))
					{
						$handle = @fopen($digests_cache_path . '.htaccess', 'w');
						$data = "<Files *>\n\tOrder Allow,Deny\n\tAllow from All\n</Files>\n";
						@fwrite($handle, $data);
						@fclose($handle);
					}
				}

				return 'directories';

			break;

			default:
				// Run parent enable step method
				return parent::enable_step($old_state);
			break;
		}

	}

	public function disable_step($old_state)
	{

		switch ($old_state)
		{
			case '': // Empty means nothing has run yet
				// Recursively delete the /cache/phpbbservices directory
				$this->remove_directory('./../cache/phpbbservices');
				return 'directories';
			break;

			default:
				// Run parent disable step method
				return parent::disable_step($old_state);
			break;
		}
		
	}

	private function remove_directory($path)
	{
		$files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $path).'/{,.}*', GLOB_BRACE);	// Include .htaccess and hidden files
		foreach ($files as $file)
		{
			if ($file == $path . '/.' || $file == $path . '/..')
			{
				continue;	// skip special directories
			}
			is_dir($file) ? $this->remove_directory($file) : unlink($file);
		}
		rmdir($path);
		return;
	}

}
