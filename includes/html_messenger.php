<?php
/**
*
* @package phpBB Extension - digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\includes;

class html_messenger extends messenger
{
	
	protected $phpEx;
	protected $phpbb_root_path; // Only used in functions.

	public function __construct($php_ext, $phpbb_root_path)
	{
		$this->phpbb_root_path = $phpbb_root_path;
		$this->phpEx = $php_ext;
	}
		
    // method declaration
    public function send()
	{
 
		include($phpbb_root_path . 'includes/functions_messenger.' . $phpEx); // Used to send emails
		
		echo 'Hello world!';
		
    }
}
