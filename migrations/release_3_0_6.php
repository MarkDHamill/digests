<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

if (!defined('IN_PHPBB'))
{
	exit;
}

class release_3_0_6 extends \phpbb\db\migration\migration
{
	
	static public function depends_on()
	{
		return array(
			'\phpbbservices\digests\migrations\release_3_0_5',
			'\phpbb\db\migration\data\v31x\v319',
		);
	}

	public function update_data()
	{
		return array(

			// Need to fix an issue introduced by those who "upgraded" from the phpBB 3.0 digests modification using the effectively_installed
			// function prior to version 3.0.4. It limited digests UCP access to those with admin privileges only. Oops.
			
			// Temporarily get rid of the four digest UCP modules...
			array('module.remove', array(
				'ucp',
				'UCP_DIGESTS',
				array(
					'module_basename'       => '\phpbbservices\digests\ucp\main_module',
					'modes'                 => array('basics', 'forums_selection', 'post_filters', 'additional_criteria'),
				)
			)),
			// then its module category
			array('module.remove', array(
				'ucp',
				0,
				'UCP_DIGESTS',
			)),
		
			// Now put them back in but with the right authorization this time
			array('module.add', array(
				'module_class'		=> 'ucp',
				'module_basename'	=>	false,	// 0, or top level
				'module_langname'	=> 'UCP_DIGESTS',
				'module_auth'       => 'ext_phpbbservices/digests',
			)),
			array('module.add', array(
				'ucp', 
				'UCP_DIGESTS', 
				array(
					'module_basename'   => '\phpbbservices\digests\ucp\main_module',
					'modes' => array('basics', 'forums_selection', 'post_filters', 'additional_criteria'),
				),
			)),
						
		);
	}
}
