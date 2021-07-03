<?php
/**
*
* @package phpBB Extension - digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
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
			'title'		=> 'UCP_DIGESTS',
			'version'	=> '3.2.12',
			'modes'		=> array(
				'basics'					=> array('title' => 'UCP_DIGESTS_BASICS', 'auth' => 'ext_phpbbservices/digests', 'cat' => array('UCP_DIGESTS')),
				'forums_selection'			=> array('title' => 'UCP_DIGESTS_FORUMS_SELECTION', 'auth' => 'ext_phpbbservices/digests', 'cat' => array('UCP_DIGESTS')),
				'post_filters'				=> array('title' => 'UCP_DIGESTS_POST_FILTERS', 'auth' => 'ext_phpbbservices/digests', 'cat' => array('UCP_DIGESTS')),
				'additional_criteria'		=> array('title' => 'UCP_DIGESTS_ADDITIONAL_CRITERIA', 'auth' => 'ext_phpbbservices/digests', 'cat' => array('UCP_DIGESTS')),
			),
		);
	}
}
