<?php
/**
*
* @package phpBB Extension - digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\ucp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\phpbbservices\digests\ucp\main_module',
			'title'		=> 'UCP_DIGESTS_TITLE',
			'version'	=> '3.0.0',
			'modes'		=> array(
				'digests_general'						=> array('title' => 'UCP_DIGESTS_GENERAL_SETTINGS', 'auth' => 'ext_phpbbservices/digests && acl_a_board', 'cat' => array('UCP_DIGESTS')),
				'digests_user_defaults'					=> array('title' => 'UCP_DIGESTS_USER_DEFAULT_SETTINGS', 'auth' => 'ext_phpbbservices/digests && acl_a_board', 'cat' => array('UCP_DIGESTS')),
				'digests_edit_subscribers'				=> array('title' => 'UCP_DIGESTS_EDIT_SUBSCRIBERS', 'auth' => 'ext_phpbbservices/digests && acl_a_board', 'cat' => array('UCP_DIGESTS')),
				'digests_balance_load'					=> array('title' => 'UCP_DIGESTS_BALANCE_LOAD', 'auth' => 'ext_phpbbservices/digests && acl_a_board', 'cat' => array('UCP_DIGESTS')),
				'digests_mass_subscribe_unsubscribe'	=> array('title' => 'UCP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE', 'auth' => 'ext_phpbbservices/digests && acl_a_board', 'cat' => array('UCP_DIGESTS')),
			),
		);
	}
}
