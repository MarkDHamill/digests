<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2018 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\migrations;

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

			// ----- Remove UCP modules ----- //
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_BASICS')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION')), // Only in 2.2.6, gone in 2.2.7
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POSTS_SELECTION')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION')), // Appeared in 2.2.7
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_FORUMS_SELECTION')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_POST_FILTERS')),
			)),
			array('if', array(
				array('module.exists', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA')),
				array('module.remove', array('ucp', 'UCP_DIGESTS', 'UCP_DIGESTS_ADDITIONAL_CRITERIA')),
			)),

			// Now put them back in but with the right authorization this time
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
