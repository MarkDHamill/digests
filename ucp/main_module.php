<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\ucp;

use phpbbservices\digests\constants\constants;

if (!defined('IN_PHPBB'))
{
	exit;
}

class main_module
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)
	{
		global $db, $user, $auth, $template;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		global $cache, $phpbb_container, $phpbb_dispatcher, $table_prefix, $request;
		
		$user->add_lang_ext('phpbbservices/digests', array('info_acp_common','common'));

		$action	= request_var('action', '');
		$submit = (isset($_POST['submit'])) ? true : false;

		$form_key = 'phpbbservices/digests';
		add_form_key($form_key);

		/**
		*	Validation types are:
		*		string, int, bool,
		*		script_path (absolute path in url - beginning with / and no trailing slash),
		*		rpath (relative), rwpath (realtive, writable), path (relative path, but able to escape the root), wpath (writable)
		*/
		switch ($mode)
		{
			case 'basics':
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_BASICS',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);
			break;
			
			case 'forums_selection':
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_FORUMS_SELECTION',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);
			break;
			
			case 'post_filters':
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_POST_FILTERS',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);
			break;
			
			case 'additional_criteria':
				$display_vars = array(
					'title'	=> 'UCP_DIGESTS_ADDITIONAL_CRITERIA',
					'vars'	=> array(
						'legend1'								=> ''
					)
				);
			break;
			
		}
				
	}


}
