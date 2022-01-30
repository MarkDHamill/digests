<?php
/**
*
* @package phpBB Extension - digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\acp;

class main_info
{
	function module()
	{
		return array(
			'filename'	=> '\phpbbservices\digests\acp\main_module',
			'title'		=> 'ACP_CAT_DIGESTS',
			'version'	=> '3.3.5',
			'modes'		=> array(
				'digests_general'						=> array('title' => 'ACP_DIGESTS_GENERAL_SETTINGS',	'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_user_defaults'					=> array('title' => 'ACP_DIGESTS_USER_DEFAULT_SETTINGS', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_edit_subscribers'				=> array('title' => 'ACP_DIGESTS_EDIT_SUBSCRIBERS', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_balance_load'					=> array('title' => 'ACP_DIGESTS_BALANCE_LOAD', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_mass_subscribe_unsubscribe'	=> array('title' => 'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_test'							=> array('title' => 'ACP_DIGESTS_TEST', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_reset_cron_run_time'			=> array('title' => 'ACP_DIGESTS_RESET_CRON_RUN_TIME', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_clear_cached'					=> array('title' => 'ACP_DIGESTS_CLEAR_CACHED_DIGESTS', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
				'digests_report'						=> array('title' => 'ACP_DIGESTS_REPORTS', 'auth' => 'ext_phpbbservices/digests && acl_a_user', 'cat' => array('ACP_CAT_DIGESTS')),
			),
		);
	}
}
