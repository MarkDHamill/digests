<?php
/**
 *
 * @package phpBB Extension - Digests
 * @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace phpbbservices\digests\controller;

use phpbbservices\digests\constants\constants;

/**
 * Digests one-click unsubscribe.
 */
class unsubscribe
{

	protected $db;
	protected $helper;
	protected $language;
	protected $request;
	protected $user;

	/**
	 * Constructor
	 *
	 * @param \phpbb\request\request 	$request 	The request object
	 * @param \phpbb\user 				$user 		The user object
	 * @param \phpbb\db\driver\factory 	$db 		The database factory object
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param \phpbb\language\language $language Language object
	 *
	 *
	 */
	public function __construct(\phpbb\request\request $request, \phpbb\user $user, \phpbb\db\driver\factory $db, \phpbb\controller\helper $helper, \phpbb\language\language $language)
	{
		$this->db 		= $db;
		$this->helper	= $helper;
		$this->language = $language;
		$this->request 	= $request;
		$this->user 	= $user;
	}

	/**
	 * Controller handler for route /digests/
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function handle()
	{
		// This function handles one-click unsubscribe. The link is in the footer of the email digest.

		$user_id = $this->request->variable('u', ANONYMOUS, true);
		$email = $this->request->variable('e', '', true);	// The user's email address must match the user_id for the unsubscribe request to be assumed to be legitimate

		$success = false;
		if ($user_id != ANONYMOUS)
		{
			$sql = 'SELECT user_email 
				FROM ' . USERS_TABLE . '
				WHERE user_id = ' . (int) $user_id . ' AND ' . $this->db->sql_in_set('user_type', array(USER_IGNORE), true);
			$result = $this->db->sql_query($sql);
			$rowset = $this->db->sql_fetchrowset($result);

			if (count($rowset) == 1 && trim($rowset[0]['user_email']) == trim($email))
			{
				// This unsubscribe request should be valid
				$sql2 = 'UPDATE ' . USERS_TABLE . "
					SET user_digest_type = '" . constants::DIGESTS_NONE_VALUE . "'
					WHERE user_id = " . (int) $user_id;
				$this->db->sql_query($sql2);
				$success = true;
			}
			$this->db->sql_freeresult($result);
		}

		if ($success)
		{
			trigger_error($this->language->lang('DIGESTS_UNSUBSCRIBE_SUCCESS'));
			// Send a courtesy unsubscribe confirmation email
			$this->helper->notify_subscribers(array($email), 'digests_unsubscribe_one_click');
		}
		else
		{
			trigger_error($this->language->lang('DIGESTS_UNSUBSCRIBE_FAILURE'), E_USER_WARNING);
		}
	}

}
