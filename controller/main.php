<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\controller;

use phpbbservices\digests\constants\constants;

class main
{
	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;
	
	protected $phpEx;

	/* @var \phpbb\db\driver\factory  */
	protected $db;

	/* @var \phpbb\auth\auth */
	protected $auth;

	protected $phpbb_root_path; // Only used in functions.

	/* @var \phpbb\request\request */
	protected $request;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper
	* @param \phpbb\template\template	$template
	* @param \phpbb\user				$user
	* @param string						$php_ext
	* @param \phpbb\db\driver\driver_interface	$db
	* @param \phpbb\auth\auth			$auth
	* @param string						$phpbb_root_path
	* @param \phpbb\request\request 	$request
	*/
	
	public function __construct( \phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\user $user,
		$php_ext, \phpbb\db\driver\factory $db, \phpbb\auth\auth $auth, $phpbb_root_path, \phpbb\request\request $request)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->user = $user;
		$this->phpEx = $php_ext;
		$this->db = $db;
		$this->auth = $auth;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->request = $request;
	}

	/**
	* Digests controller for route /digests/{name}
	*
	* @param string		$name
	* @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	*/
	public function handle($name)
	{

		switch ($name)
		{
			
			case 'feed':	// This generates a RSS or Atom Feed
			
				// Assume no logical errors were encountered creating a feed
				$error = false;
				$error_msg = $this->user->lang['DIGESTS_NO_ERRORS'];
				
				// General variables
				$is_registered = false;	// Assume guest
				$allowed_user_types = array(USER_NORMAL, USER_FOUNDER); // Allowed user types are Normal and Founder. Others (Inactive, Ignore) can only get a public feed.
				$board_url = generate_board_url() . '/';
				$true_false_array = array(false, true);
				$lastvisit = false;
				$user_id = ANONYMOUS;	// Assume guest
				
				// $allowable_tags used when Safe HTML is wanted for item feed output. Only these tags are allowed for HTML in the feed. Others will be stripped.
				$allowable_tags = '<abbr><accept><accept-charset><accesskey><action><align><alt><axis><border><cellpadding><cellspacing><char><charoff><charset><checked><cite><class><clear><cols><colspan><color><compact><coords><datetime><dir><disabled><enctype><for><frame><headers><height><href><hreflang><hspace><id><ismap><label><lang><longdesc><maxlength><media><method><multiple><name><nohref><noshade><nowrap><prompt><readonly><rel><rev><rows><rowspan><rules><scope><selected><shape><size><span><src><start><summary><tabindex><target><title><type><usemap><valign><value><vspace><width>'; 
				
				// If the board is currently disabled, feeds should also be disabled.
				if ($this->config['board_disable'])
				{
					$error = true;
					$error_msg = $this->user->lang['DIGESTS_BOARD_DISABLED'];
				}
				
				// The entire query string will be needed later to parse out the forums wanted.
				$query_string = $this->user->page['query_string'];
				
				// --- BEGIN ERROR CHECKING BLOCK

				// What is the feed type (ATOM 1.0, RSS 1.0 or RSS 2.0?) -- if not specified, default to Atom 1.0.
				if (!$error)
				{
					$feed_type = $this->request->variable(constants::DIGESTS_FEED_TYPE, 'NONE');
					
					if ($feed_type == 'NONE')
					{
						$feed_type = constants::DIGESTS_ATOM;	// If a feed type is not specified, Atom 1.0 is the default
					}
					
					if (!is_numeric($feed_type) || !($feed_type == constants::DIGESTS_ATOM || $feed_type == constants::DIGESTS_RSS1 || $feed_type == constants::DIGESTS_RSS2))
					{
						$error = true;
						$error_msg = sprintf($this->user->lang['DIGESTS_FEED_TYPE_ERROR'], $feed_type);
					}
				}

				// Get the user id. The feed may be customized based on a user's privilege. A public user won't be identified as a user in the URL.
				$user_id = $this->request->variable(constants::DIGESTS_USER_ID, ANONYMOUS);
				
				// Get the encrypted password. When decrypted it is still encoded md5 as it should also be in the database
				$encrypted_pswd = $this->request->variable(constants::DIGESTS_ENCRYPTION_KEY, 'NONE');
	
				// If mcrypt is not compiled with PHP, a user cannot get a feed with posts from non-public forums, so tell the user what to do.
				if (!extension_loaded('mcrypt') && $user_id != ANONYMOUS && $encrypted_pswd != 'NONE')
				{
					$error = true;
					$error_msg = $this->user->lang['DIGESTS_NO_MCRYPT_MODULE'];
				}

				if (!$error)
				{

					// Determine if this is a public request. If so only posts in public forums will be shown in the feed.
					if ($user_id != ANONYMOUS && $encrypted_pswd != 'NONE')
					{
						// Feed privileges are dependent upon the auth_method. This code makes this program consistent with the user interface.
						if (($this->config['auth_method'] == 'apache') && ($this->config['phpbbservices_digests_apache_htaccess_enabled'] == 0))
						{
							$error = true;
							$error_msg = $this->user->lang['DIGESTS_APACHE_AUTHENTICATION_WARNING_REG'];
						}
						$is_registered = true;
					}
					else if (!(($user_id == ANONYMOUS) && ($encrypted_pswd == 'NONE')))
					{
						// Logically if only the u or the e parameter is present, the URL is inconsisent, so generate an error.
						if ($user_id == ANONYMOUS)
						{
							$error = true;
							$error_msg = $this->user->lang['DIGESTS_NO_U_ARGUMENT'];
						}
						if ($encrypted_pswd == 'NONE')
						{
							$error = true;
							$error_msg = $this->user->lang['DIGESTS_NO_E_ARGUMENT'];
						}
					}
				}

				if (!$error)
				{
					// Get the limit parameter. It limits the size of the newsfeed to a point in time from the present, either a day/hour/minute interval, no limit
					// or the time since the user's last visit. If it doesn't exist, $this->config['phpbbservices_digests_default_fetch_time_limit'] is used.
					$time_limit = $this->request->variable(constants::DIGESTS_TIME_LIMIT, 'NONE');
					
					if ($time_limit == 'NONE')
					{
						$time_limit = $this->config['phpbbservices_digests_default_fetch_time_limit'];
					}
					else if (!is_numeric($time_limit))
					{
						$error = true;
					}
					else if ($is_registered && ((int) $time_limit < (int) constants::DIGESTS_SINCE_LAST_VISIT_VALUE) || ((int) $time_limit > (int) constants::DIGESTS_LAST_15_MINUTES_VALUE) )
					{
						$error = true;
					}
					else if (!$is_registered && ((int) $time_limit < (int) constants::DIGESTS_NO_LIMIT_VALUE) || ((int) $time_limit > (int) constants::DIGESTS_LAST_15_MINUTES_VALUE) )
					{
						$error = true;
					}
					
					if ($error)
					{
						$error_msg = $this->user->lang['DIGESTS_LIMIT_FORMAT_ERROR'];
					}
				}

				if (!$error)
				{
					// Validate the sort by parameter. If not present, use the board default sort.
					$sort_by = $this->request->variable(constants::DIGESTS_SORT_BY,'NONE');
	
					if ($sort_by == 'NONE')
					{
						$sort_by = constants::DIGESTS_BOARD;
					}
					else if (!is_numeric($sort_by))
					{
						$error = true;
					}
					else if ( (int) $sort_by < (int) constants::DIGESTS_BOARD || (int) $sort_by > (int) constants::DIGESTS_POSTDATE_DESC) 
					{
						$error = true;
					}
					
					if ($error)
					{
						$error_msg = $this->user->lang['DIGESTS_SORT_BY_ERROR'];
					}
				}

				if (!$error)
				{
					// Validate the firstpostonly parameter
					$first_post_only = $this->request->variable(constants::DIGESTS_FIRST_POST, 'NONE');
					
					if ($first_post_only == 'NONE')
					{
						$first_post_only = false;	// Default is not to show only the first post
					}
					else if (!in_array((int) $first_post_only, $true_false_array) || !(is_numeric($first_post_only)))
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_FIRST_POST_ONLY_ERROR'];
					}
					else
					{
						$first_post_only = (int) $first_post_only;
					}
					
				}

				if (!$error)
				{
					// Check for max items parameter. It is not required, but if present should be a positive number only. The value must
					// be less than or equal to $this->config['phpbbservices_digests_max_items']. But if 
					// $this->config['phpbbservices_digests_max_items'] == 0 then any positive whole number is allowed.
					// If not present the max items is $this->config['phpbbservices_digests_max_items'] if positive, or unlimited if this value is zero.
					$max_items = $this->request->variable(constants::DIGESTS_MAX_ITEMS,'NONE');
					if ($max_items == 'NONE')
					{
						$max_items = 0;	// No explicit limit the number of items in the feed.
					}
					
					if (!is_numeric($max_items) || $max_items < 0)
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_MAX_ITEMS_ERROR'];
					}
					else if (($this->config['phpbbservices_digests_max_items'] > 0) && ($max_items > $this->config['phpbbservices_digests_max_items']))
					{
						$error = true;
						$error_msg = sprintf($this->user->lang['DIGESTS_MAX_ITEMS_MAX_ERROR'], $this->config['phpbbservices_digests_max_items']);
					}
				}

				if (!$error)
				{
					// Validate the maximum number of words the user wants to see in a post
					$max_words = $this->request->variable(constants::DIGESTS_MAX_WORDS, 'NONE');
					if ($max_words == 'NONE')
					{
						$max_words = 0;
					}
					
					if (!is_numeric($max_words) || $max_words < 0)
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_MAX_WORD_SIZE_ERROR'];
					}
				}

				if (!$error)
				{
					// Validate the minimum number of words the user wants to see in a post
					$min_words = $this->request->variable(constants::DIGESTS_MIN_WORDS,'NONE');
					if ($min_words == 'NONE')
					{
						$min_words = 0;
					}
					
					if (!is_numeric($min_words) || $min_words < 0)
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_MIN_WORD_SIZE_ERROR'];
					}
				}

				// Validate the feed style parameter.
				if (!$error)
				{
					$feed_style = $this->request->variable(constants::DIGESTS_FEED_STYLE, 'NONE');
					
					if ($feed_style == 'NONE')
					{
						$feed_style = constants::DIGESTS_HTML;	// If a feed style is not specified, HTML is used
					}
					
					if (!is_numeric($feed_style) || !($feed_style == constants::DIGESTS_COMPACT || $feed_style == constants::DIGESTS_BASIC || $feed_style == constants::DIGESTS_HTMLSAFE || $feed_style == constants::DIGESTS_HTML))
					{
						$error = true;
						$error_msg = sprintf($this->user->lang['DIGESTS_STYLE_ERROR'], $feed_style);
					}
				}

				if (!$error && $is_registered)
				{

					//  Validate the remove my posts parameter, if present
					$remove_my_posts = $this->request->variable(constants::DIGESTS_REMOVE_MINE, 'NONE');
					
					if ($remove_my_posts == 'NONE')
					{
						$remove_my_posts = false;	// Default is to not remove your posts
					}
					else if (!in_array($remove_my_posts, $true_false_array) || !(is_numeric($remove_my_posts)))
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_REMOVE_MINE_ERROR'];
					}
					
				}
				
				if (!$error && $is_registered)
				{

					// Validate the private messages switch
					$show_pms = $this->request->variable(constants::DIGESTS_PRIVATE_MESSAGE, 'NONE');
					
					if ($show_pms == 'NONE')
					{
						$show_pms = false;	// Default is to not show your private messages
					}
					else if (!in_array($show_pms, $true_false_array) || !(is_numeric($show_pms)))
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_BAD_PMS_VALUE'];
					}
					
				}
				
				if (!$error && $is_registered)
				{

					// Validate the mark read private messages switch
					$mark_private_messages = $this->request->variable(constants::DIGESTS_MARK_PRIVATE_MESSAGES, 'NONE');
					
					if ($mark_private_messages == 'NONE')
					{
						$mark_private_messages = false;	// Default is to not mark private messages read
					}
					else if (!in_array($mark_private_messages, $true_false_array) || !(is_numeric($mark_private_messages)))
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_BAD_MARK_PRIVATE_MESSAGES_READ_ERROR'];
					}
					
				}
				
				if (!$error && $is_registered)
				{

					// Validate the bookmark topics only switch
					$bookmarks_only = $this->request->variable(constants::DIGESTS_BOOKMARKS, 'NONE');
					
					if ($bookmarks_only == 'NONE')
					{
						$bookmarks_only = false;	// Default is to not use bookmarks. All posts are retrieved instead.
					}
					else if (!in_array($bookmarks_only, $true_false_array) || !(is_numeric($bookmarks_only)))
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_BAD_BOOKMARKS_VALUE'];
					}
					
				}
				
				if (!$error && $is_registered)
				{

					// Validate the filter foes switch
					$filter_foes = $this->request->variable(constants::DIGESTS_FILTER_FOES, 'NONE');
					
					if ($filter_foes == 'NONE')
					{
						$filter_foes = false;	// Default is to not filter foes.
					}
					else if (!in_array($filter_foes, $true_false_array) || !(is_numeric($filter_foes)))
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_FILTER_FOES_ERROR'];
					}
					
				}
				
				if (!$error && $is_registered)
				{

					// Validate the last visit parameter.
					$lastvisit = $this->request->variable(constants::DIGESTS_LAST_VISIT, 'NONE');
					
					if ($lastvisit == 'NONE')
					{
						$lastvisit = false;	// Default is to not to filter out posts before last visit
					}
					else if (!in_array($lastvisit, $true_false_array) || !(is_numeric($lastvisit)))
					{
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_LASTVISIT_ERROR'];
					}
					
				}
				
				// --- END ERROR CHECKING BLOCK

				// --- BEGIN GET USER INFORMATION BLOCK
				
				$sql = 'SELECT user_id, user_password, user_digests_key, user_topic_sortby_type, user_topic_sortby_dir, 
							user_post_sortby_type, user_post_sortby_dir, user_lastvisit, user_type
						FROM ' . USERS_TABLE . " 
						WHERE user_id = $user_id";
				if ($user_id != ANONYMOUS)
				{
					$sql .= ' AND ' . $this->db->sql_in_set('user_type', $allowed_user_types); // Robots and inactive members are not allowed to get into restricted forums
				}
				
				$result = $this->db->sql_query($sql);
				$rowset = $this->db->sql_fetchrowset($result);
				
				if (sizeof($rowset) == 0)
				{
					$error = true;
					$error_msg = $this->user->lang['constants::DIGESTS_USER_ID_DOES_NOT_EXIST'];
				}
				else
				{
					
					// Make sure user_id exists in database and has normal or founder status
					$row = reset($rowset);
					
					// Save the user variables, although only the first is unneeded for guests.
					$user_digests_key = $row['user_digests_key'];
					$user_topic_sortby_type = $row['user_topic_sortby_type']; 
					$user_topic_sortby_dir = $row['user_topic_sortby_dir']; 
					$user_post_sortby_type = $row['user_post_sortby_type']; 
					$user_post_sortby_dir = $row['user_post_sortby_dir'];
					
					// These other variables are only used by registered users
					$user_password = $row['user_password'];
					$user_lastvisit = $row['user_lastvisit'];
				
				}
				
				$this->db->sql_freeresult($result); // Query be gone!

				// --- END GET USER INFORMATION BLOCK
				
				// Decrypt password using the user_digests_key column in the phpbb_users table. This should have been created 
				// the first time the user interface was run by this user. Note the encoded password is typically md5. There should not be
				// a clear text password in the database.
					
				if ($is_registered)
				{
					
					if (strlen($user_digests_key) == 0)
					{
						// If the $user_digests_key is an empty string, the password cannot be decrypted. It's hard to imagine how this could happen 
						// unless the feed was called before the user interface was run.
						$error = true;
						$error_msg = sprintf($this->user->lang['DIGESTS_BAD_PASSWORD_ERROR'], $encrypted_pswd, $user_id);
					}
					else
					{
					
						$encoded_pswd = decrypt($this->phpbb_root_path, $this->phpEx, $encrypted_pswd, $user_digests_key);
						
						// If IP Authentication was enabled, the encoded password is to the left of the ~ and the IP to the right of the ~
						$tilde = strpos($encoded_pswd, '~');
						if (($tilde == 0) && ($this->config['phpbbservices_digests_require_ip_authentication'] == '1'))
						{
							$error = true;
							$error_msg = $this->user->lang['DIGESTS_IP_AUTH_ERROR'];
						}
						else if ($tilde > 0)
						{
							// Since a tilde is present, authenticate the client IP by comparing it with the IP embedded in the "e" parameter
							$authorized_ip = substr($encoded_pswd, $tilde + 1);
							$encoded_pswd = substr($encoded_pswd, 0, $tilde);
							$client_ip_parts = explode('.', $this->user->ip);	// Client's current IP, based on what the web server recorded.
							$source_ip_parts = explode('.', $authorized_ip);	// IP range authorized for this user
							$is_ipV4 = (sizeof($client_ip_parts) == 4) ? true : false;	// Is this a IP version 4 or 6 IP address?
							
							// Show error message if requested from incorrect range of IP addresses
							switch (sizeof($client_ip_parts))
							{
								
								case 4:	 // IPV4
									if (!(
											($client_ip_parts[0] == $source_ip_parts[0]) && 
											($client_ip_parts[1] == $source_ip_parts[1]) &&
											(($client_ip_parts[2] == $source_ip_parts[2]) || ($source_ip_parts[2] == '*'))
										))
									{
										$error = true;
										$error_msg = $this->user->lang['DIGESTS_IP_AUTH_ERROR'];
									}
								break;
								
								case 8:	 // IPV6
									if (!(
											($client_ip_parts[0] == $source_ip_parts[0]) && 
											($client_ip_parts[1] == $source_ip_parts[1]) &&
											($client_ip_parts[2] == $source_ip_parts[2]) &&
											($client_ip_parts[3] == $source_ip_parts[3]) &&
											($client_ip_parts[4] == $source_ip_parts[4]) &&
											($client_ip_parts[5] == $source_ip_parts[5]) &&
											($client_ip_parts[6] == $source_ip_parts[6]) || ($source_ip_parts[6] == '*')
										))
									{
										$error = true;
										$error_msg = $this->user->lang['DIGESTS_IP_AUTH_ERROR'];
									}
								break;
								
								default:
									// Something is really odd if the number of address ranges in the client is not 4 or 8!
									$error = true;
									$error_msg = sprintf($this->user->lang['DIGESTS_IP_RANGE_ERROR'], $this->user->ip);
								break;
								
							}
						}
					
						// Do not generate a feed if the asserted encrypted password does not equal the actual password. Note: the password is an MD5 hash.
						if (!$error && (trim($encoded_pswd) != trim($user_password)))
						{
							$error = true;
							$error_msg = sprintf($this->user->lang['DIGESTS_BAD_PASSWORD_ERROR'], $encrypted_pswd, $user_id);
						} 
						
					}
				}

				// Logic to limit the range of posts fetched in the feed follows by creating the appropriate SQL qualification
				
				$start_time = ($this->config['phpbbservices_digests_default_fetch_time_limit'] == 0) ? 0 : time() - ($this->config['phpbbservices_digests_default_fetch_time_limit'] * 60 * 60);
				
				switch ($time_limit)
				{

					case constants::DIGESTS_NO_LIMIT_VALUE:
						$date_limit = $start_time;
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_QUARTER_VALUE:
						$date_limit = max($start_time, time() - (90 * 24 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_MONTH_VALUE:
						$date_limit = max($start_time, time() - (30 * 24 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_TWO_WEEKS_VALUE:
						$date_limit = max($start_time, time() - (14 * 24 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_WEEK_VALUE:
						$date_limit = max($start_time, time() - (7 * 24 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_DAY_VALUE:
						$date_limit = max($start_time, time() - (24 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_12_HOURS_VALUE:
						$date_limit = max($start_time, time() - (12 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_6_HOURS_VALUE:
						$date_limit = max($start_time, time() - (6 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_3_HOURS_VALUE:
						$date_limit = max($start_time, time() - (3 * 60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_1_HOURS_VALUE:
						$date_limit = max($start_time, time() - (60 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_30_MINUTES_VALUE:
						$date_limit = max($start_time, time() - (30 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_LAST_15_MINUTES_VALUE:
						$date_limit = max($start_time, time() - (15 * 60));
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
					case constants::DIGESTS_SINCE_LAST_VISIT_VALUE:
					default:
						$date_limit = max($start_time, $user_lastvisit);
						$date_limit_sql = ' AND p.post_time > ' . $date_limit;
					break;
					
				}
				
				$fetched_forums_str = '';
				
				if ($is_registered && $bookmarks_only)
				{
				
					// When selecting bookmarked topics only, we can safely ignore the logic constraining the user to read only 
					// from certain forums. Instead we will create the SQL to get the bookmarked topics, if any, hijacking the 
					// $fetched_forums_str variable since it is convenient
					
					$bookmarked_topic_ids = array();
					$sql = 'SELECT t.topic_id
							FROM ' . USERS_TABLE . ' u, ' . BOOKMARKS_TABLE . ' b, ' . TOPICS_TABLE . " t
							WHERE u.user_id = b.user_id AND b.topic_id = t.topic_id 
								AND t.topic_last_post_time > $date_limit
								AND b.user_id = $user_id";
								
					$result = $this->db->sql_query($sql);
					while ($row = $this->db->sql_fetchrow($result))
					{
						$bookmarked_topic_ids[] = intval($row['topic_id']);
					}
					$this->db->sql_freeresult($result);
					if (sizeof($bookmarked_topic_ids) > 0)
					{
						$fetched_forums_str = ' AND ' . $this->db->sql_in_set('t.topic_id', $bookmarked_topic_ids);
					}
					else
					{
						// Logically, if there are no bookmarked topics for this $user_id then there will be nothing in the feed.
						// Send a message to this effect in the feed.
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_NO_BOOKMARKS'];
					}
				
				}
				else
				{
				
					// Getting a list of allowed forums is now much simpler now that I know about the acl_raw_data_single_user function. 
					
					// We need to know which auth_option_id corresponds to the forum read privilege (f_read) and forum list (f_list) privilege.
					$auth_options = array('f_read', 'f_list');
					$sql = 'SELECT auth_option, auth_option_id
							FROM ' . ACL_OPTIONS_TABLE . '
							WHERE ' . $this->db->sql_in_set('auth_option', $auth_options);
					$result = $this->db->sql_query($sql);
					
					while ($row = $this->db->sql_fetchrow($result))
					{
						if ($row['auth_option'] == 'f_read')
						{
							$read_id = $row['auth_option_id'];
						}
						if ($row['auth_option'] == 'f_list')
						{
							$list_id = $row['auth_option_id'];
						}
					}
					
					$this->db->sql_freeresult($result); // Query be gone!
				
					// Now let's get this user's forum permissions. Note that non-registered, robots etc. get a list of public forums
					// with read permissions.
					
					$allowed_forum_ids = array();
					$parent_array = array();
					
					$forum_array = $this->auth->acl_raw_data_single_user($user_id);
					foreach ($forum_array as $key => $value)
					{
						foreach ($value as $auth_option_id => $auth_setting)
						{
							if ($auth_option_id == $read_id)
							{
								if (($auth_setting == 1) && check_all_parents($this->auth, $parent_array, $key))
								{
									$allowed_forum_ids[] = $key;
								}
							}
						}
					}
					
					if (sizeof($allowed_forum_ids) == 0)
					{
						// If this user cannot retrieve ANY forums, this suggests that this board is tightly locked down to members only,
						// or every member must belong to a user group or have special forum permissions
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_NO_ACCESSIBLE_FORUMS'];
					}
					
					// Get the requested forums. If none are listed, user wants all forums for which they have read access.
					$requested_forum_ids = array();
					$params = explode('&', $query_string);
					$required_forums_only = false;
					foreach ($params as $item)
					{
						if ($item == constants::DIGESTS_FORUMS . '=-1')
						{
							// This is an unusual case and it means that no forums were selected but there are required forums.
							// In this case the feed is restricted to returning content ONLY for required forums.
							$required_forums_only = true;
							break;
						}
						if (substr($item,0,2) == constants::DIGESTS_FORUMS . '=')
						{
							$requested_forum_ids[] = substr($item,2);
						}
					}
					
					// To capture global announcements when forums are specified, we have to add the pseudo-forum with a forum_id = 0.
					if (sizeof($requested_forum_ids) > 0)
					{
						$requested_forum_ids[] = '0';
					}
					
					// Sort requested forums by forum_id and ensure there are no duplicates
					asort($requested_forum_ids);
					$requested_forum_ids = array_unique($requested_forum_ids);
					
					// The forums that will be fetched is the set intersection of the requested and allowed forums. This prevents hacking
					// the URL to get feeds a user is not supposed to get. If no forums are specified on the URL field then all forums that 
					// this user is authorized to access is assumed.
						
					if (!$required_forums_only)
					{
						$fetched_forums = (sizeof($requested_forum_ids) > 0) ? array_intersect($allowed_forum_ids, $requested_forum_ids): $allowed_forum_ids;
						// Add in any required forums
						if (strlen($this->config['phpbbservices_digests_include_forums']) > 0)
						{
							$fetched_forums = array_merge($fetched_forums, explode(',', $this->config['phpbbservices_digests_include_forums']));
						}
					}
					else
					{
						$fetched_forums = explode(',', $this->config['phpbbservices_digests_include_forums']);
					}
				
					// Remove any prohibited forums
					$excluded_forums = (isset($this->config['phpbbservices_digests_exclude_forums'])) ? explode(',', $this->config['phpbbservices_digests_exclude_forums']) : array();
					if (sizeof($excluded_forums) > 0)
					{
						$fetched_forums = array_diff($fetched_forums, $excluded_forums);
					}
					$fetched_forums = array_unique($fetched_forums);
				
					// Create a SQL fragment to return posts from the correct forums
					if (sizeof($fetched_forums) > 0)
					{
						$fetched_forums_str = ' AND ' . $this->db->sql_in_set('p.forum_id', $fetched_forums);
					}
					else
					{
						// If there are no forums to fetch, this will result in an empty newsfeed. 
						$error = true;
						$error_msg = $this->user->lang['DIGESTS_NO_FORUMS_ACCESSIBLE'];
					}
				
				}

				// Create the SQL stub for the sort order
				switch($sort_by)
				{
					case constants::DIGESTS_BOARD:
						$topic_asc_desc = ($user_topic_sortby_dir == 'd') ? 'DESC' : '';
						switch($user_topic_sortby_type)
						{
							case 'a':
								$order_by_sql = "t.topic_first_poster_name $topic_asc_desc, ";
							break;
							case 't':
								$order_by_sql = "t.topic_last_post_time $topic_asc_desc, ";
							break;
							case 'r':
								$order_by_sql = "t.posts_approved $topic_asc_desc, ";
							break;
							case 's':
								$order_by_sql = "t.topic_title $topic_asc_desc, " ; 
							break;
							case 'v':
								$order_by_sql = "t.topic_views $topic_asc_desc, ";
							break;
						}
						$post_asc_desc = ($user_post_sortby_dir == 'd') ? 'DESC' : '';
						switch($user_post_sortby_type)
						{
							case 'a':
								$order_by_sql .= "u.username_clean $post_asc_desc";
							break;
							case 't':
								$order_by_sql .= "p.post_time $post_asc_desc";
							break;
							case 's':
								$order_by_sql .= "p.post_subject $post_asc_desc" ; 
							break;
						}
					break;
					case constants::DIGESTS_STANDARD:
						$order_by_sql = 'f.left_id, f.right_id, t.topic_last_post_time, p.post_time';
					break;
					case constants::DIGESTS_STANDARD_DESC:
						$order_by_sql = 'f.left_id, f.right_id, t.topic_last_post_time, p.post_time DESC';
					break;
					case constants::DIGESTS_POSTDATE:
						$order_by_sql = 'p.post_time';
					break;
					case constants::DIGESTS_POSTDATE_DESC:
						$order_by_sql = 'p.post_time DESC';
					break;
				}

				$new_topics_sql = '';
				$topics_posts_join_sql = 't.topic_id = p.topic_id';
				
				// Create the first_post_only SQL stubs
				if ($first_post_only)
				{
					$new_topics_sql = ' AND t.topic_time > $date_limit ';
					$topics_posts_join_sql = ' t.topic_first_post_id = p.post_id AND t.forum_id = f.forum_id';
				}
				
				// Create SQL to remove your posts from the feed
				$remove_my_posts_sql = '';
				if ($is_registered && ($remove_my_posts == 1))
				{
					$remove_my_posts_sql = " AND p.poster_id <> $user_id ";
				}

				// Create SQL to remove your foes from the feed
				$filter_foes_sql = '';
				$foes = array();
				if ($is_registered && ($filter_foes == 1))
				{
				
					// Fetch your foes
					$sql = 'SELECT zebra_id 
							FROM ' . ZEBRA_TABLE . "
							WHERE user_id = $user_id AND foe = 1";
					$result = $this->db->sql_query($sql);
					while ($row = $this->db->sql_fetchrow($result))
					{
						$foes[] = (int) $row['zebra_id'];
					}
					$this->db->sql_freeresult($result);
				
					if (sizeof($foes) > 0)
					{
						$filter_foes_sql = ' AND ' . $this->db->sql_in_set('p.poster_id', $foes, true);
					}
					
				}

				// At last, construct the SQL to return the relevant posts
				$sql_array = array(
					'SELECT'	=> 'f.*, t.*, p.*, u.*, tt.mark_time AS topic_mark_time, ft.mark_time AS forum_mark_time',
				
					'FROM'		=> array(
						FORUMS_TABLE => 'f',
						TOPICS_TABLE => 't',
						POSTS_TABLE => 'p',
						USERS_TABLE => 'u'),
				
					'WHERE'		=> "f.forum_id = t.forum_id AND 
								$topics_posts_join_sql AND 
								p.poster_id = u.user_id 
								$date_limit_sql
								$fetched_forums_str
								$new_topics_sql
								$remove_my_posts_sql
								$filter_foes_sql
								AND p.post_visibility = 1",
				
					'ORDER_BY'	=> $order_by_sql
				);
				
				$sql_array['LEFT_JOIN'] = array(
					array(
						'FROM'	=> array(TOPICS_TRACK_TABLE => 'tt'),
						'ON'	=> 't.topic_id = tt.topic_id'
					),
					array(
						'FROM'	=> array(FORUMS_TRACK_TABLE => 'ft'),
						'ON'	=> 'f.forum_id = ft.forum_id'
					)
				);
				
				$sql = $this->db->sql_build_query('SELECT', $sql_array);
				
				// Now finally, let's fetch the actual posts to be placed in this newsfeed
				$result = $this->db->sql_query_limit($sql, $max_items); // Execute the SQL to retrieve the relevant posts. Note, if $max_items is 0 then there is no limit on the rows returned
				$rowset = $this->db->sql_fetchrowset($result); // Get all the posts as a set

				// Add private messages, if requested
				if ($is_registered && $show_pms)
				{
				
					$pm_sql = 	'SELECT *
								FROM ' . PRIVMSGS_TO_TABLE . ' pt, ' . PRIVMSGS_TABLE . ' pm, ' . USERS_TABLE . " u
								WHERE pt.msg_id = pm.msg_id
									AND pt.author_id = u.user_id
									AND pt.user_id = $user_id
									AND (pm_unread = 1 OR pm_new = 1)";
					$pm_result = $this->db->sql_query($pm_sql);
					$pm_rowset = $this->db->sql_fetchrowset($pm_result);
				
				}
				else
				{
					$pm_result = NULL;
					$pm_rowset = NULL;
				}

				$display_name = $this->user->lang['DIGESTS_FEED'];	// As XML is generated, there is no real page name to display so this is sort of moot.
				
				// These template variables apply to the overall feed, not to items in it. A post is an item in the newsfeed.
				$this->template->assign_vars(array(
				
					'L_DIGESTS_FEED_DESCRIPTION' 		=> html_entity_decode($this->config['site_desc']),
					'L_DIGESTS_FEED_IMAGE_TITLE'		=> html_entity_decode($this->config['sitename']),	// for RSS 1.0 and 2.0
					'L_DIGESTS_FEED_TITLE' 			=> html_entity_decode($this->config['sitename']),
					'L_DIGESTS_FEED_TYPE_ERROR' 		=> $error_msg,	// This would only work with feed type errors. Most errors are shown in error logic below.

					'S_DIGESTS_FEED_BUILD_DATE'		=> date('r'),	// for RSS 1.0 and 2.0
					'S_DIGESTS_FEED_CHANNEL_ABOUT'	=> generate_board_url(),	// for RSS 1.0
					'S_DIGESTS_FEED_LANGUAGE'			=> ($this->config['phpbbservices_digests_rfc1766_lang'] <> '') ? $this->config['phpbbservices_digests_rfc1766_lang'] : $this->config['default_lang'],	// For RSS 2.0
					'S_DIGESTS_FEED_PUBDATE'			=> date('r'),	// for RSS 2.0
					'S_DIGESTS_FEED_TTL' 				=> ($this->config['phpbbservices_digests_ttl'] <> '') ? $this->config['phpbbservices_digests_ttl'] : '60',	// for RSS 2.0
					'S_DIGESTS_FEED_TYPE' 			=> $feed_type,	// Atom 1.0, RSS 1.0, RSS 2.0, used as a switch. Must be 0, 1 or 2. Atom 1.0 is used to show feed type errors if they occur.
					'S_DIGESTS_FEED_UPDATED'			=> date('c'),	// for Atom and RSS 2.0
					'S_DIGESTS_FEED_VERSION' 			=> constants::DIGESTS_VERSION,
					'S_DIGESTS_IN_DIGESTS' 			=> false,	// Suppress inclusion of Digests Javascript if not in Digests user interface
					'S_DIGESTS_USER_INTERFACE' 		=> false,
					
					'U_DIGESTS_FEED_ID'				=> generate_board_url(),
					'U_DIGESTS_FEED_LINK' 			=> generate_board_url() . '/app.' . $this->phpEx . '/digests/digests',
					'U_DIGESTS_FEED_URL' 				=> ($feed_type == constants::DIGESTS_ATOM) ? generate_board_url() . '/app.' . $this->phpEx . '/digests/feed?' . $this->request->server('QUERY_STRING') : generate_board_url() . '/app.' . $this->phpEx . '/digests/feed?' . htmlspecialchars($this->request->server('QUERY_STRING')),
					'U_DIGESTS_FEED_IMAGE'			=> ($this->config['phpbbservices_digests_feed_image_path'] <> '') ? generate_board_url() . '/styles/' . trim($this->user->style['style_path']) . '/' . $this->config['phpbbservices_digests_feed_image_path'] : generate_board_url() . '/styles/' . trim($this->user->style['style_path']) . '/theme/images/site_logo.gif', // For RSS 1.0 and 2.0.
					'U_DIGESTS_FEED_IMAGE_LINK'		=> generate_board_url() . '/app.' . $this->phpEx . '/digests/digests',	// for RSS 1.0 and RSS 2.0
					'U_DIGESTS_FEED_IMAGE_URL' 		=> ($feed_type == constants::DIGESTS_ATOM) ? generate_board_url() . '/app.' . $this->phpEx . '/digests/feed?' . $this->request->server('QUERY_STRING') : generate_board_url() . '/app.' . $this->phpEx . '/digests/feed?' . htmlspecialchars($this->request->server('QUERY_STRING')),
					'U_DIGESTS_FEED_GENERATOR' 		=> constants::DIGESTS_GENERATOR,
					'U_DIGESTS_FEED_PAGE_URL'			=> $this->config['phpbbservices_digests_url'],
					)
				);
				
				// Show the posts as feed items
				
				if ($error)
				{
					// Since an error has occurred, generate a feed with just one item in it: the error.
					$this->template->assign_block_vars('items', array(
					
						// Atom 1.0 block variables follow
						'L_CATEGORY'	=> $this->user->lang['DIGESTS_ERROR'],
						'L_CONTENT'		=> $error_msg,
						'L_EMAIL'		=> $this->config['board_contact'],
						'L_NAME'		=> ($this->config['board_contact_name'] <> '') ? $this->config['board_contact_name'] : $this->config['board_contact'],
						'L_SUMMARY'		=> $error_msg,	// Should be a "line" or so, perhaps first 80 characters of the post, perhaps stripped of HTML. Irrelevant for errors.
						'L_TITLE'		=> $this->user->lang['DIGESTS_ERROR'],
						'S_PUBLISHED'	=> date('c'),
						'S_UPDATED'		=> date('c'),
						'U_ID'			=> generate_board_url() . '/app.' . $this->phpEx . '/digests/digests',
						'U_LINK'		=> generate_board_url() . '/app.' . $this->phpEx . '/digests/digests',
						
						// RSS 1.0 block variables follow
						'L_DESCRIPTION'	=> $error_msg,
						'S_CREATOR'		=> ($this->config['board_contact_name'] <> '') ? $this->config['board_contact_name'] : $this->config['board_contact'],
						'S_DATE'		=> date('c'),
						'U_RDF'			=> generate_board_url() . '/app.' . $this->phpEx .'/digests/digests',
						'U_RESOURCE'	=> generate_board_url() . '/app.' . $this->phpEx .'/digests/digests',
						'U_SOURCE'		=> generate_board_url(),
						
						// RSS 2.0 block variables follow
						'S_AUTHOR'		=> ($this->config['board_contact_name'] <> '') ? $this->config['board_contact_name'] . ' (' . $this->config['board_contact'] . ')' : $this->config['board_contact'],
						'S_PUBDATE'		=> date('D, d M Y H:i:s O'),	// RFC-822 format required
						'U_COMMENTS'	=> generate_board_url(),
						'U_GUID'		=> generate_board_url() . '/app.' . $this->phpEx .'/digests/digests',
					));
				}
				
				else
				{
					
					// If there are any unread private messages, publish them first.
					if (isset($pm_rowset))
					{
						foreach ($pm_rowset as $row)
						{
							
							// Create the username, title and link for the private message
							if ($this->config['phpbbservices_digests_new_post_notifications_only'])
							{
								$username = $this->user->lang['ADMINISTRATOR'];
								$title = $this->user->lang['DIGESTS_NEW_PMS_NOTIFICATIONS_SHORT'];
								$link = htmlspecialchars($board_url . 'ucp.' . $this->phpEx . '?i=pm&folder=inbox');
								$message = $this->user->lang['DIGESTS_NEW_PMS_NOTIFICATIONS_ONLY'];
							}
							else
							{
								$username = $row['username']; // Don't need to worry about Anonymous users for private messages, they cannot send them
								$title = $this->user->lang['PRIVATE_MESSAGE'] . $this->user->lang['DIGESTS_DELIMITER'] . $row['message_subject'] . $this->user->lang['DIGESTS_DELIMITER'] . $this->user->lang['FROM'] . ' ' . $username;
								$link = htmlspecialchars($board_url . 'ucp.' . $this->phpEx . '?i=pm&mode=view&f=0&p=' . $row['msg_id']);
	
								// Set an email address associated with the poster of the private message. In most cases it should not be seen.
								if ($this->config['phpbbservices_digests_privacy_mode'])
								{
									// Some feeds requires an email field to validate. Use a fake email address.
									$email = ($feed_type==constants::DIGESTS_RSS2 || $feed_type==constants::DIGESTS_ATOM) ? 'no_email@example.com' : '';
								}
								else
								{
									// Digests privacy mode must be off AND the user must give permission for his/her email to appear in their profile for it show.
									$email = ($row['user_allow_viewemail']) ? $row['user_email'] : 'no_email@example.com';
								}
		
								$message = censor_text($row['message_text']);	// No naughty words
								
								$user_sig = ( $row['enable_sig'] && ($row['user_sig'] != '') && $this->config['allow_sig'] && ($this->config['phpbbservices_digests_privacy_mode'] == '0') ) ? censor_text($row['user_sig']) : '';
								
								if (($feed_style == constants::DIGESTS_HTML) || ($feed_style == constants::DIGESTS_HTMLSAFE))
								{
									$flags = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
										(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
										(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
										
									$message = generate_text_for_display($message, $row['bbcode_uid'], $row['bbcode_bitfield'], $flags);
									// Add any attachments to the private message item
									if ($row[message_attachment] > 0)
									{
										$message .= create_attachment_markup ($this->db, $this->$phpEx, $row['msg_id'], false, $this->user->lang['ATTACHMENTS'], $this->user->lang['DIGESTS_POST_IMAGE_TEXT'], $this->user->lang['KIB']);
									}
	
									if ($user_sig != '')
									{
										$user_sig = generate_text_for_display($user_sig, $row['user_sig_bbcode_uid'], $row['user_sig_bbcode_bitfield'], $flags);
									}
						
									$message = ($user_sig != '') ? $message . $this->user->lang['DIGESTS_POST_SIGNATURE_DELIMITER'] . $user_sig : $message;
									if ($feed_style == constants::DIGESTS_HTMLSAFE)
									{
										$message = strip_tags($message, $allowable_tags);
									}
	
								}
								else
								{
									// Either Compact or Basic Style wanted
									if ($feed_style == constants::DIGESTS_BASIC)
									{
										$message = ($user_sig != '') ? $message . "\n\n" . $user_sig : $message;
									}
									strip_bbcode($message); 			// Remove the BBCode
									$message = strip_tags($message);	// Gets rid of any embedded HTML
									// Either condense all text or make line feeds explicit
									$message = ($feed_style == constants::DIGESTS_BASIC) ? nl2br($message) : str_replace("\n", ' ', $message);
								}
							}
						
							// Handle the maximum number of words requested per PM logic
							if ($max_word_size != 0)
							{
								$message = truncate_words($this->user, $message, intval($max_word_size), $this->user->lang['DIGESTS_MAX_WORDS_NOTIFIER']);
							}
	
							// Attach the private message to the feed as an item
							$this->template->assign_block_vars('items', array(
							
								// Common and Atom 1.0 block variables follow
								'L_CATEGORY'	=> $this->user->lang['PRIVATE_MESSAGE'],
								'L_CONTENT'		=> $message,
								'L_EMAIL'		=> $email,
								'L_NAME'		=> $username,
								'L_SUMMARY'		=> $message,
								'L_TITLE'		=> html_entity_decode(censor_text($title)),
								'S_PUBLISHED'	=> date('c', $row['message_time']),
								'S_UPDATED'		=> ($row['message_edit_time'] > 0) ? date('c', $row['message_edit_time']) : date('c', $row['message_time']),
								'U_ID'			=> $link,
								'U_LINK'		=> $link,
								
								// RSS 1.0 block variables follow
								'L_DESCRIPTION'	=> $message,
								'S_CREATOR'		=> $email . ' (' . $username . ')',
								'S_DATE'		=> date('c', $row['message_time']),
								'U_RESOURCE'	=> generate_board_url() . '/app.' . $this->phpEx . '/digests/digests',
								'U_SOURCE'		=> generate_board_url(),
								
								// RSS 2.0 block variables follow
								'S_AUTHOR'		=> $email . ' (' . $username . ')',
								'S_PUBDATE'		=> ($row['message_edit_time'] > 0) ? date('D, d M Y H:i:s O', $row['message_edit_time']) : date('D, d M Y H:i:s O', $row['message_time']),	// RFC-822 date format required.
								'U_COMMENTS'	=> $link,
								'U_GUID'		=> $link,
								
							));
	
							// If we are to get only a notification that there are new private messages, we should go through this loop only once.
							if ($this->config['phpbbservices_digests_new_post_notifications_only'])
							{
								break;
							}
	
							if ($mark_private_messages)
							{
								// Mark this private message as read
								$sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . "
										SET pm_unread = 0, pm_new = 0  
										WHERE msg_id = " . $row['msg_id'] . " 
											AND user_id = $user_id
											AND author_id = " . $row['author_id'] . " 
											AND folder_id = " . $row['folder_id'];
								$this->db->sql_query($sql);
								// Decrement the user_unread_privmsg and user_new_privmsg count
								$sql = 'UPDATE ' . USERS_TABLE . " 
										SET user_unread_privmsg = user_unread_privmsg - 1,
											user_new_privmsg = user_new_privmsg - 1
										WHERE user_id = $user_id";
								$this->db->sql_query($sql);
							}
							
						}
					}
					
					// Loop through the rowset, each row is an item in the feed.
					if (isset($rowset))
					{

						$topics_array = array();

						foreach ($rowset as $row)
						{

							if (!(in_array($row['topic_id'], $topics_array)))
							{
								array_push($topics_array, $row['topic_id']);
								$new_topic = true;
							}
							else
							{
								$new_topic = false;
							}
							
							// Is this topic or forum associated with the post being tracked by this user? If so, exclude the post if the topic track 
							// time or forum track time is before the earliest time allowed for a post.
							if (((!is_null($row['forum_mark_time']) && ($row['forum_mark_time']) < $date_limit)) ||
								((!is_null($row['topic_mark_time']) && ($row['topic_mark_time']) < $date_limit)))
							{
								$include_post = false;
							}
							else
							{
								$include_post = true;
							}
							
							// Allow a post in the feed if no minimum number of words is specified by the user OR if a minimum number 
							// of words is specified by the user and it equals or exceeds their minimum allowed number of words. Also allow if 
							// new post notifications is flagged and the topic has not already been visited. Allow it if it meets the 
							// condition above for being after a forum or topic's marked time for the user.
							
							if ((($min_words == 0 && !$this->config['phpbbservices_digests_new_post_notifications_only']) ||
								($min_words != 0 && !$this->config['phpbbservices_digests_new_post_notifications_only'] && truncate_words($this->user, $row['post_text'], intval($max_word_size), $this->user->lang['DIGESTS_MAX_WORDS_NOTIFIER'], true) >= $min_words)) ||
								($this->config['phpbbservices_digests_new_post_notifications_only'] && $new_topic && $include_post))
							{
								// This post goes in the newsfeed
				
								if ($this->config['phpbbservices_digests_new_post_notifications_only'])
								{
									$username = $this->user->lang['ADMINISTRATOR'];
								}
								else
								{
									$username = ($row['user_id'] == ANONYMOUS) ? $row['post_username'] : $row['username'];
								}
					
								// Create the title for the item (post)
								if ($this->config['phpbbservices_digests_new_post_notifications_only'])
								{
									if ($this->config['phpbbservices_digests_suppress_forum_names'])
									{
										$title = $row['topic_title'];
									}
									else
									{
										$forum_name = ($row['forum_name'] == NULL) ? $this->user->lang['DIGESTS_GLOBAL_ANNOUNCEMENT'] : $row['forum_name'];
										$title = $forum_name . $this->user->lang['DIGESTS_DELIMITER'] . $row['topic_title'];
									}
								}
								else
								{
									$forum_name = ($row['forum_name'] == NULL) ? $this->user->lang['DIGESTS_GLOBAL_ANNOUNCEMENT'] : $row['forum_name'];
									if ($row['post_subject'] != '')
									{
										$title = ($this->config['phpbbservices_digests_suppress_forum_names']) ? $row['post_subject'] : $forum_name . $this->user->lang['DIGESTS_DELIMITER'] . $row['post_subject'];
									}
									else
									{
										$title = ($this->config['phpbbservices_digests_suppress_forum_names']) ? 'Re: ' . $row['topic_title'] : $forum_name . $this->user->lang['DIGESTS_DELIMITER'] . 'Re: ' . $row['topic_title'];
									}
									$title = html_entity_decode($title);		
				
									if ($row['topic_first_post_id'] != $row['post_id'])
									{
										if ($this->config['phpbbservices_digests_show_username_in_replies'])
										{
											$title .= ($row['username'] == '') ? $this->user->lang['DIGESTS_DELIMITER'] . $this->user->lang['DIGESTS_REPLY_BY'] . ' ' . $this->user->lang['GUEST'] . ' ' . $username : $this->user->lang['DIGESTS_DELIMITER'] . $this->user->lang['DIGESTS_REPLY_BY'] . ' ' . $username;
										}
										else
										{
											$title .= $this->user->lang['DIGESTS_DELIMITER'] . $this->user->lang['DIGESTS_REPLY'];
										}
									}
									else
									{
										if ($this->config['phpbbservices_digests_show_username_in_first_topic_post'])
										{
											$title .= $this->user->lang['DIGESTS_DELIMITER'] . $this->user->lang['AUTHOR'] . ' ' . $username;
										}
									}
								}
								
								$title = html_entity_decode(censor_text($title));
								
								$link = htmlspecialchars($board_url . 'viewtopic.' . $this->phpEx . '?f=' . $row['forum_id'] . '&t=' . $row['topic_id'] . '&p=' . $row['post_id']  . '#p' . $row['post_id']);
								$category = html_entity_decode($row['forum_name']);
								$comments = htmlspecialchars($board_url . 'posting.' . $this->phpEx . '?mode=reply&f=' . $row['forum_id'] . '&t=' . $row['topic_id']);
					
								// Set an email address associated with the poster. In most cases it should not be seen.
								if ($this->config['phpbbservices_digests_privacy_mode'])
								{
									// Some feeds requires an email field to validate. Use a fake email address.
									$email = ($feed_type==constants::DIGESTS_RSS2 || $feed_type==constants::DIGESTS_ATOM) ? 'no_email@example.com' : '';
								}
								else
								{
									// Digests privacy mode must be off AND the user must give permission for his/her email to appear in their profile for it show.
									$email = ($row['user_allow_viewemail']) ? $row['user_email'] : 'no_email@example.com';
								}
								
								// To "dress up" the post text with bbCode, images, smilies etc., we need to use generate_text_for_display() function.
								if ($this->config['phpbbservices_digests_new_post_notifications_only'])
								{
									$post_text = $this->user->lang['DIGESTS_NEW_POST_NOTIFICATIONS_ONLY'];
								}
								else
								{
									$post_text = censor_text($row['post_text']);
									
									$user_sig = ( $row['enable_sig'] && $row['user_sig'] != '' && $this->config['allow_sig'] && (!($this->config['phpbbservices_digests_privacy_mode']) || $is_registered) ) ? censor_text($row['user_sig']) : '';
									
									if (($feed_style == constants::DIGESTS_HTML) || ($feed_style == constants::DIGESTS_HTMLSAFE))
									{
										$flags = (($row['enable_bbcode']) ? OPTION_FLAG_BBCODE : 0) +
											(($row['enable_smilies']) ? OPTION_FLAG_SMILIES : 0) + 
											(($row['enable_magic_url']) ? OPTION_FLAG_LINKS : 0);
											
										// If there is an image, show it. If there is a file, link to the attachment
										if ($row['post_attachment'] > 0)
										{
											$post_text .= create_attachment_markup ($this->db, $this->phpEx, $row['post_id'], true, $this->user->lang['ATTACHMENTS'], $this->user->lang['DIGESTS_POST_IMAGE_TEXT'], $this->user->lang['KIB']);
										}
														
										$post_text = generate_text_for_display($post_text, $row['bbcode_uid'], $row['bbcode_bitfield'], $flags);
										$post_text = str_replace('<img src="./', '<img src="' . $board_url, $post_text); 
										
										if ($user_sig != '')
										{
											$user_sig = generate_text_for_display($user_sig, $row['user_sig_bbcode_uid'], $row['user_sig_bbcode_bitfield'], $flags);
										}
							
										$post_text = ($user_sig != '') ? $post_text . $this->user->lang['DIGESTS_POST_SIGNATURE_DELIMITER'] . $user_sig : $post_text;
										if ($feed_style == constants::DIGESTS_HTMLSAFE)
										{
											$post_text = strip_tags($post_text, $allowable_tags);
										}
							
									}
									else
									{
										// Either Compact or Basic Style wanted
										if ($feed_style == constants::DIGESTS_BASIC)
										{
											$post_text = ($user_sig != '') ? $post_text . "\n\n" . $user_sig : $post_text;
										}
										$post_text = strip_tags($post_text);	// Gets rid of any embedded HTML
										strip_bbcode($post_text); 			// Remove the BBCode
										// Either condense all text or make line feeds explicit
										$post_text = ($feed_style == constants::DIGESTS_BASIC) ? nl2br($post_text) : str_replace("\n", ' ', $post_text);
									}
									
									// Handle the maximum number of words requested per post logic
									if ($this->config['phpbbservices_digests_max_size'] > 0 && $max_word_size > 0)
									{
										$post_text = truncate_words($this->user, $post_text, min($this->config['phpbbservices_digests_max_size'], $this->user->lang['DIGESTS_MAX_WORDS_NOTIFIER'], intval($max_word_size)));
									}
									else if ($this->config['phpbbservices_digests_max_size'] > 0 && $max_word_size == 0)
									{
										$post_text = truncate_words($this->user, $post_text, $this->config['phpbbservices_digests_max_size'], $this->user->lang['DIGESTS_MAX_WORDS_NOTIFIER']);
									}
									else if ($max_words > 0)
									{
										$post_text = truncate_words($this->user, $post_text, intval($max_word_size), $this->user->lang['DIGESTS_MAX_WORDS_NOTIFIER']);
									}
								}
								
								// Add the item (post) to the feed
								
								$this->template->assign_block_vars('items', array(
								
									// Common and Atom 1.0 block variables follow
									'L_CATEGORY'	=> $category,
									'L_CONTENT'		=> $post_text,
									'L_EMAIL'		=> $email,
									'L_NAME'		=> $username,
									'L_SUMMARY'		=> $post_text,
									'L_TITLE'		=> html_entity_decode(censor_text($title)),
									'S_PUBLISHED'	=> date('c', $row['post_time']),
									'S_UPDATED'		=> ($row['post_edit_time'] > 0) ? date('c', $row['post_edit_time']) : date('c', $row['post_time']),
									'U_ID'			=> $link,
									'U_LINK'		=> $link,
									
									// RSS 1.0 block variables follow
									'L_DESCRIPTION'	=> $post_text,
									'S_CREATOR'		=> $email . ' (' . $username . ')',
									'S_DATE'		=> date('c', $row['post_time']),
									'U_RDF'			=> generate_board_url() . '/app.' . $this->phpEx . '/digests/digests',
									'U_RESOURCE'	=> generate_board_url() . '/app.' . $this->phpEx . '/digests/digests',
									'U_SOURCE'		=> generate_board_url(),
									
									// RSS 2.0 block variables follow
									'S_AUTHOR'		=> $email . ' (' . $username . ')',
									'S_PUBDATE'		=> ($row['post_edit_time'] > 0) ? date('D, d M Y H:i:s O', $row['post_edit_time']) : date('D, d M Y H:i:s O', $row['post_time']),	// RFC-822 data format required
									'U_COMMENTS'	=> $link,
									'U_GUID'		=> $link,
									
								));
							}

						}
					}
					
				}

				// Reset the user's last visit date on the forum, if so requested
				if (!$error && $is_registered && isset($lastvisit))
				{
					if ($lastvisit)
					{
						$sql = 'UPDATE ' . USERS_TABLE . '
									SET user_lastvisit = ' . time() . " 
									WHERE user_id = $user_id";
							
						$this->db->sql_query($sql);
					}
				}
				
			break;
			
			case 'digests':
			default:
			
				$display_name = $this->user->lang['DIGESTS_TITLE'];
				$this->template->assign_vars(array(
					'S_DIGESTS_USER_INTERFACE' => true,
					)
				);
				
				// Digests cannot be used with Apache authentication unless the .htaccess file is modified to allow digests.php to bypass
				// Apache authentication. If you have made these changes then set the constant DIGESTS_APACHE_HTACCESS_ENABLED to true in the ACP interface.
				if (($this->config['auth_method'] == 'apache') && ($this->config['phpbbservices_digests_apache_htaccess_enabled'] != 1))
				{
					$msg_text = ($this->user->data['user_type'] == USER_FOUNDER) ? $this->user->lang['DIGESTS_APACHE_AUTHENTICATION_WARNING_ADMIN'] : $this->user->lang['DIGESTS_APACHE_AUTHENTICATION_WARNING_REG'];
					trigger_error($msg_text, E_USER_NOTICE);
				}
				
				// Create a list of required and excluded forum_ids
				$required_forum_ids = (isset($this->config['phpbbservices_digests_include_forums']) && strlen(trim($this->config['phpbbservices_digests_include_forums'])) > 0) ? explode(',', $this->config['phpbbservices_digests_include_forums']) : array();
				$excluded_forum_ids = (isset($this->config['phpbbservices_digests_exclude_forums']) && strlen(trim($this->config['phpbbservices_digests_exclude_forums'])) > 0) ? explode(',', $this->config['phpbbservices_digests_exclude_forums']) : array();
		
				// Pass encryption tokens to the user interface for generating URLs, unless of the user is not registered or mcrypt is not supported.
				$is_guest = !$this->user->data['is_registered'] || !extension_loaded('mcrypt');
				
				if (!$is_guest)
				{
					// If the user is registered then great, they can authenticate and see private forums
					$digests_user_id = $this->user->data['user_id'];
					$user_password = $this->user->data['user_password'];
					if ($this->user->data['user_digests_key'])
					{
						$user_digests_key = $this->user->data['user_digests_key'];
						$encrypted_password = encrypt($this->phpbb_root_path, $this->phpEx, $user_password, $user_digests_key);
						$encrypted_password_with_ip = encrypt($this->phpbb_root_path, $this->phpEx, $user_password . '~' . $this->user->ip, $user_digests_key);
					}
					else
					{
						// Generate a digests encryption key. This is a one time action. It is used to authenticate the user when they call digests.php.
						$user_digests_key = gen_rand_string(32);
						$encrypted_password = encrypt($this->phpbb_root_path, $this->phpEx, $user_password, $user_digests_key);
						$encrypted_password_with_ip = encrypt($this->phpbb_root_path, $this->phpEx, $user_password . '~' . $this->user->ip, $user_digests_key);
						
						// Store the key
						$sql = 'UPDATE ' . USERS_TABLE . "
								SET user_digests_key = '" . $this->db->sql_escape($user_digests_key) . "'
								WHERE user_id = " . $this->user->data['user_id'];
						$result = $this->db->sql_query($sql);
					}
					$this->template->assign_vars(array('S_DIGESTS_IS_GUEST' => false, 'S_DIGESTS_DAY_DEFAULT' => ''));
				}
				else
				{
					// Public (anonymous) users do not need to authenticate so no encrypted passwords are needed
					$digests_user_id = ANONYMOUS;
					$encrypted_password = 'NONE';
					$encrypted_password_with_ip = 'NONE';
					$this->template->assign_vars(array('S_DIGESTS_IS_GUEST' => true, 'S_DIGESTS_DAY_DEFAULT' => 'selected="selected"'));
				}
		
				$allowed_forum_ids = array();
				$forum_read_ary = array();
				
				// Get forum read permissions for this user. They are also usually stored in the user_permissions column, but sometimes the field is empty. This always works.
				$forum_array = $this->auth->acl_raw_data_single_user($digests_user_id);
				
				foreach ($forum_array as $key => $value)
				{
					foreach ($value as $auth_option_id => $auth_setting)
					{
						if ($this->auth->acl_get('f_read', $key))
						{
							$forum_read_ary[$key]['f_read'] = 1;
						}
						if ($this->auth->acl_get('f_list', $key))
						{
							$forum_read_ary[$key]['f_list'] = 1;
						}
					}
				}
		
				// Get a list of parent_ids for each forum and put them in an array.
				$parent_array = array();
				$sql = 'SELECT forum_id, parent_id 
					FROM ' . FORUMS_TABLE . '
					ORDER BY forum_id ASC';
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$parent_array[$row['forum_id']] = $row['parent_id'];
				}
				$this->db->sql_freeresult($result);
		
				if (sizeof($forum_read_ary) > 0) // This should avoid a PHP Notice
				{
					foreach ($forum_read_ary as $forum_id => $allowed)
					{
						if ($this->auth->acl_get('f_read', $forum_id) && $this->auth->acl_get('f_list', $forum_id) && check_all_parents($this->auth, $parent_array, $forum_id))
						{
							// Since this user has read access to this forum, add it to the $allowed_forum_ids array
							$allowed_forum_ids[] = (int) $forum_id;
							
							// Also add to $allowed_forum_ids the parents, if any, of this forum. Actually we have to find the parent's parents, etc., going up as far as necessary because 
							// $this->auth->act_getf does not return the parents for which the user has access, yet parents must be shown are in the user interface
							$there_are_parents = true;
							$this_forum_id = (int) $forum_id;
							
							while ($there_are_parents)
							{
								if ($parent_array[$this_forum_id] == 0)
								{
									$there_are_parents = false;
								}
								else
								{
									// Do not add this parent to the list of allowed forums if it is already in the array
									if (!in_array((int) $parent_array[$this_forum_id], $allowed_forum_ids))
									{
										$allowed_forum_ids[] = (int) $parent_array[$this_forum_id];
									} 
									$this_forum_id = (int) $parent_array[$this_forum_id];	// Keep looping...
								}
							}
						}
					}
				}
		
				// Get a list of forums as they appear on the main index for this user. For presentation purposes indent them so they show the natural phpBB3 hierarchy.
				// Indenting is cleverly handled by nesting <div> tags inside of other <div> tags, and the template defines the relative offset (20 pixels).
		
				$no_forums = false;
				
				if (sizeof($allowed_forum_ids) > 0)
				{
					
					$sql = 'SELECT forum_name, forum_id, parent_id, forum_type
							FROM ' . FORUMS_TABLE . ' 
							WHERE ' . $this->db->sql_in_set('forum_id', $allowed_forum_ids) . ' AND forum_type <> ' . FORUM_LINK . '
							ORDER BY left_id ASC';
					$result = $this->db->sql_query($sql);
					
					$this->template->assign_block_vars('show_forums', array());
					
					$current_level = 0;			// How deeply nested are we at the moment
					$parent_stack = array();	// Holds a stack showing the current parent_id of the forum
					$parent_stack[] = 0;		// 0, the first value in the stack, represents the <div_0> element, a container holding all the categories and forums in the template
					
					while ($row = $this->db->sql_fetchrow($result))
					{
					
						if ((int) $row['parent_id'] != (int) end($parent_stack) || (end($parent_stack) == 0))
						{
							if (in_array($row['parent_id'], $parent_stack))
							{
								// If parent is in the stack, then pop the stack until the parent is found, otherwise push stack adding the current parent. This creates a </div>
								while ((int) $row['parent_id'] != (int) end($parent_stack))
								{
									array_pop($parent_stack);
									$current_level--;
									// Need to close a category level here
									$this->template->assign_block_vars('forums', array( 
										'S_DIV_OPEN' => false,
										'S_PRINT' => false));
								}
							}
							else
							{
								// If the parent is not in the stack, then push the parent_id on the stack. This is also a trigger to indent the block. This creates a <div>
								array_push($parent_stack, (int) $row['parent_id']);
								$current_level++;
								// Need to add a category level here
								$this->template->assign_block_vars('forums', array( 
									'S_DIV_OPEN' => true,
									'CAT_ID' => 'div_' . $row['parent_id'],
									'S_PRINT' => false));
							}
						}
						
						// This section contains logic to handle forums that are either required or excluded by the Administrator
						
						// Is the forum either required or excluded from Digests?
						$required_forum = (in_array((int) $row['forum_id'], $required_forum_ids)) ? true : false;
						$excluded_forum = (in_array((int) $row['forum_id'], $excluded_forum_ids)) ? true : false;
						$forum_disabled = $required_forum || $excluded_forum;
						
						// Markup to visually show required or excluded forums
						if ($required_forum)
						{
							$prefix = '<strong>';
							$suffix = '</strong>';
						}
						else
						{
							if ($excluded_forum)
							{
								$prefix = '<span style="text-decoration:line-through">';
								$suffix = '</span>';
							}
							else
							{
								$prefix = '';
								$suffix = '';
							}
						}
						
						// Markup to indicate whether the checkbox for the forum should be checked or not
						$forum_checked = ($this->config['phpbbservices_digests_all_by_default'] == '1');
						if ($required_forum)
						{
							$forum_checked = true;
						}
						if ($excluded_forum)
						{
							$forum_checked = false;
						}
						
						$element_prefix = ($required_forum || $excluded_forum) ? 'xlt_' : 'elt_'; // 'xlt_' will exclude the element from the check/uncheck form feature
						
						// This code prints the forum or category, which will exist inside the previously created <div> block
						$this->template->assign_block_vars('forums', array( 
							'FORUM_NAME' => $element_prefix . (int) $row['forum_id'] . '_' . (int) $row['parent_id'],
							'FORUM_PREFIX' => $prefix,
							'FORUM_LABEL' => $row['forum_name'],
							'FORUM_SUFFIX' => $suffix,
							'FORUM_DISABLED' => ($forum_disabled) ? 'disabled="disabled"' : '',
							'FORUM_CHECKED' => ($forum_checked) ? 'checked="checked"' : '',
							'S_PRINT' => true,
							'S_IS_FORUM' => ($row['forum_type'] == FORUM_CAT) ? false : true));	// Switch to display a category different than a forum
						
					}
				
					$this->db->sql_freeresult($result);
					
					// Now out of the loop, it is important to remember to close any open <div> tags. Typically there is at least one.
					while ((int) $row['parent_id'] != (int) end($parent_stack))
					{
						array_pop($parent_stack);
						$current_level--;
						// Need to close the <div> tag
						$this->template->assign_block_vars('forums', array( 
							'S_DIV_OPEN' => false,
							'S_PRINT' => false));
					}
					
				}
				else
				{
					$no_forums = true;
				}
		
				if ($this->user->ip == '::1')	// Can happen in local test environment, like XAMPP
				{
					$this->user->ip = '127.0.0.1';	// Typical default IP for localhost
				}
				
				// If user_digests_ip exists, parse it, otherwise use $this->user->ip
				// For IPV6 testing, if no IPV6 IP is available, uncomment the following line to test:
				// $this->user->data['user_digests_ip'] = 'fe80.fe80.fe80.fe80.fe80.fe80.fe80.fe80';
				
				if (isset($this->user->data['user_digests_ip']))
				{
					$user_digests_ip = explode('.', $this->user->data['user_digests_ip']);
				}
				else
				{
					$user_digests_ip = explode('.', $this->user->ip);
				}
				
				// For IPV6 testing, if no IPV6 IP is available, uncomment the following line to test:
				// $this->user->ip = 'fe80.fe80.fe80.fe80.fe80.fe80.fe80.fe80';
				
				$client_ip_parts = explode('.', $this->user->ip);
				$is_ipV6 = (sizeof($client_ip_parts) == 8) ? true : false;
				$IPV6 = (sizeof($client_ip_parts) == 8) ? 'true' : 'false';
			
				// Handles an improbable PHP Notice problem
				for ($i = 0; $i < sizeof($client_ip_parts); $i++)
				{
					if (is_null($client_ip_parts[$i]))
					{
						$client_ip_parts[$i] = 0;
					}
				}
				
				// Set up text for the IP authentication explanation string
				$digests_ip_auth_explain = sprintf($this->user->lang['DIGESTS_IP_AUTHENTICATION_EXPLAIN'], $this->user->ip);
				$max_items = ($this->config['phpbbservices_digests_max_items'] == '0') ? 0 : 1;
				$size_error_msg = $this->user->lang('DIGESTS_SIZE_ERROR', $this->config['phpbbservices_digests_max_items'], 0);
		
				// Set the template variables needed to generate a URL for Digests. Note: most can be handled by template language variable substitution.
				$this->template->assign_vars(array(
				
					'L_DIGESTS_IP_AUTHENTICATION_EXPLAIN'	=> $digests_ip_auth_explain,
					'L_DIGESTS_LIMIT_SET_EXPLAIN'		=> ($this->config['phpbbservices_digests_default_fetch_time_limit'] == '0') ? '' : sprintf($this->user->lang['DIGESTS_LIMIT_SET_EXPLAIN'], round(($this->config['phpbbservices_digests_default_fetch_time_limit']/24), 0)),
					'L_DIGESTS_MAX_ITEMS_EXPLAIN_MAX' => ($this->config['phpbbservices_digests_max_items'] == 0) ? $this->user->lang['DIGESTS_MAX_ITEMS_EXPLAIN_BLANK'] : sprintf($this->user->lang['DIGESTS_MAX_ITEMS_EXPLAIN'], $this->config['phpbbservices_digests_max_items'], $max_items),
					'L_DIGESTS_MAX_WORD_SIZE_EXPLAIN' => ($this->config['phpbbservices_digests_max_word_size'] == '0') ? $this->user->lang['DIGESTS_MAX_WORD_SIZE_EXPLAIN_BLANK'] : sprintf($this->user->lang['DIGESTS_MAX_WORD_SIZE_EXPLAIN'], $this->config['phpbbservices_digests_max_word_size']),
					'L_DIGESTS_NOT_LOGGED_IN'			=> !extension_loaded('mcrypt') ? $this->user->lang['DIGESTS_NO_MCRYPT_SUPPORT'] : sprintf($this->user->lang['DIGESTS_NOT_LOGGED_IN'], $this->phpEx, $this->phpEx),
					'L_DIGESTS_SIZE_ERROR'			=> $size_error_msg,
		
					'S_DIGESTS_ALL_BY_DEFAULT'		=> ($this->config['phpbbservices_digests_all_by_default'] == '1') ? 'checked="checked"' : '',
					'S_DIGESTS_ATOM_10_VALUE'			=> constants::DIGESTS_ATOM,
					'S_DIGESTS_AUTO_ADVERTISE_FEED'	=> $this->config['phpbbservices_digests_auto_advertise_public_feed'],  // can this be done here for all pages?
					'S_DIGESTS_BASIC_VALUE'			=> constants::DIGESTS_BASIC,
					'S_DIGESTS_BOARD'					=> constants::DIGESTS_BOARD,
					'S_DIGESTS_BOOKMARKS' 			=> constants::DIGESTS_BOOKMARKS,
					'S_DIGESTS_COMPACT_VALUE'			=> constants::DIGESTS_COMPACT,
					'S_DIGESTS_ENCRYPTION_KEY' 		=> constants::DIGESTS_ENCRYPTION_KEY,
					'S_DIGESTS_FEED_STYLE' 			=> constants::DIGESTS_FEED_STYLE,
					'S_DIGESTS_FEED_TYPE' 			=> constants::DIGESTS_FEED_TYPE,
					'S_DIGESTS_FILTER_FOES' 			=> constants::DIGESTS_FILTER_FOES, 
					'S_DIGESTS_FIRST_POST' 			=> constants::DIGESTS_FIRST_POST,
					'S_DIGESTS_FORUMS' 				=> constants::DIGESTS_FORUMS,
					'S_DIGESTS_HTML_VALUE'			=> constants::DIGESTS_HTML,
					'S_DIGESTS_HTMLSAFE_VALUE'		=> constants::DIGESTS_HTMLSAFE,
					'S_DIGESTS_IN_DIGESTS' 			=> true,	// Suppress inclusion of Digests Javascript if not in Digests user interface
					'S_DIGESTS_IS_GUEST' 				=> $is_guest,
					'S_DIGESTS_IS_IPV6'				=> $IPV6,	// text for true or false, needed for Javascript
					'S_DIGESTS_IS_IPV6_BOOLEAN'		=> $is_ipV6,	// boolean for true or false, needed for template engine
					'S_DIGESTS_IP'					=> $this->user->ip,
					'S_DIGESTS_LAST_QUARTER_VALUE'	=> constants::DIGESTS_LAST_QUARTER_VALUE,
					'S_DIGESTS_LAST_MONTH_VALUE'		=> constants::DIGESTS_LAST_MONTH_VALUE,
					'S_DIGESTS_LAST_TWO_WEEKS_VALUE'	=> constants::DIGESTS_LAST_TWO_WEEKS_VALUE,
					'S_DIGESTS_LAST_VISIT' 			=> constants::DIGESTS_LAST_VISIT,
					'S_DIGESTS_LAST_WEEK_VALUE'		=> constants::DIGESTS_LAST_WEEK_VALUE,
					'S_DIGESTS_LAST_DAY_VALUE'		=> constants::DIGESTS_LAST_DAY_VALUE,
					'S_DIGESTS_LAST_12_HOURS_VALUE'	=> constants::DIGESTS_LAST_12_HOURS_VALUE,
					'S_DIGESTS_LAST_6_HOURS_VALUE'	=> constants::DIGESTS_LAST_6_HOURS_VALUE,
					'S_DIGESTS_LAST_3_HOURS_VALUE'	=> constants::DIGESTS_LAST_3_HOURS_VALUE,
					'S_DIGESTS_LAST_1_HOURS_VALUE'	=> constants::DIGESTS_LAST_1_HOURS_VALUE,
					'S_DIGESTS_LAST_30_MINUTES_VALUE'	=> constants::DIGESTS_LAST_30_MINUTES_VALUE,
					'S_DIGESTS_LAST_15_MINUTES_VALUE'	=> constants::DIGESTS_LAST_15_MINUTES_VALUE,
					'S_DIGESTS_MARK_PRIVATE_MESSAGES' => constants::DIGESTS_MARK_PRIVATE_MESSAGES,
					'S_DIGESTS_MAX_ITEMS'				=> $this->config['phpbbservices_digests_max_items'], // was count_limit, now max_items
					'S_DIGESTS_MAX_ITEMS_L' 			=> constants::DIGESTS_MAX_ITEMS,
					'S_DIGESTS_MAX_WORD_SIZE'			=> $this->config['phpbbservices_digests_max_word_size'], // max_word_size
					'S_DIGESTS_MAX_WORDS' 			=> constants::DIGESTS_MAX_WORDS,
					'S_DIGESTS_MIN_WORDS' 			=> constants::DIGESTS_MIN_WORDS,
					'S_DIGESTS_NO_FORUMS'				=> $no_forums,
					'S_DIGESTS_NO_LIMIT_VALUE' 		=> constants::DIGESTS_NO_LIMIT_VALUE,
					'S_DIGESTS_POSTDATE_ASCENDING'	=> constants::DIGESTS_POSTDATE,
					'S_DIGESTS_POSTDATE_DESCENDING'	=> constants::DIGESTS_POSTDATE_DESC,
					'S_DIGESTS_PRIVATE_MESSAGE' 		=> constants::DIGESTS_PRIVATE_MESSAGE,
					'S_DIGESTS_PWD'					=> $encrypted_password, 
					'S_DIGESTS_PWD_WITH_IP'			=> $encrypted_password_with_ip, 
					'S_DIGESTS_REMOVE_MINE' 			=> constants::DIGESTS_REMOVE_MINE,
					'S_DIGESTS_REQUIRED_FORUMS'		=> (sizeof($required_forum_ids) > 0) ? 'true' : 'false',
					'S_DIGESTS_RSS_10_VALUE'			=> constants::DIGESTS_RSS1,
					'S_DIGESTS_RSS_20_VALUE'			=> constants::DIGESTS_RSS2,
					'S_DIGESTS_SINCE_LAST_VISIT_VALUE'	=> constants::DIGESTS_SINCE_LAST_VISIT_VALUE,
					'S_DIGESTS_SORT_BY' 				=> constants::DIGESTS_SORT_BY,
					'S_DIGESTS_STANDARD'				=> constants::DIGESTS_STANDARD,
					'S_DIGESTS_STANDARD_DESC'			=> constants::DIGESTS_STANDARD_DESC,
					'S_DIGESTS_TIME_LIMIT' 			=> constants::DIGESTS_TIME_LIMIT,
					'S_DIGESTS_USER_ID' 				=> constants::DIGESTS_USER_ID,
					'S_DIGESTS_VERSION' 				=> $this->config['phpbbservices_digests_version'],
					
					'U_DIGESTS_PAGE_URL'				=> $this->config['phpbbservices_digests_url'],
		
					'UA_DIGESTS_SITE_URL'				=> generate_board_url() . '/app.' . $this->phpEx . '/digests/',
					'UA_DIGESTS_USER_ID'				=> $digests_user_id,
		
					)
				);
				
			break;
			
		}
				
		return $this->helper->render('digests_body.html', $display_name);
		
	}
	
}

function base64_encode_urlsafe($input)
{
	// Thanks to phpBB forum user klapray for this logic for creating a "urlsafe" versions of base64_encode and _decode.
	return strtr(base64_encode($input), '+/=', '-_.');
}
	
function base64_decode_urlsafe($input)
{
	// Thanks to phpBB forum user klapray for this logic for creating a "urlsafe" versions of base64_encode and _decode.
	return base64_decode(strtr($input, '-_.', '+/='));
}

function encrypt($phpbb_root_path, $phpEx, $data_input, $key)
{   

	// This function encrypts $data_input with the given $key using the TRIPLEDES encryption algorithm. If mcrypt is not available then private access is not supported.
	
	$cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', MCRYPT_MODE_ECB, '');
	
	mcrypt_generic_init($cipher, $key, constants::DIGESTS_IV);
	$encrypted_string = mcrypt_generic($cipher, $data_input);
	$encrypted_data = base64_encode_urlsafe($encrypted_string);
	mcrypt_generic_end($cipher);
	
	return $encrypted_data;

}

function decrypt($phpbb_root_path, $phpEx, $data_input, $key)
{   

	// This function encrypts $data_input with the given $key using the TRIPLEDES encryption algorithm. If mcrypt is not available then private access is not supported.
	
	$cipher = mcrypt_module_open(MCRYPT_TRIPLEDES, '', MCRYPT_MODE_ECB, '');
	
	mcrypt_generic_init($cipher, $key, constants::DIGESTS_IV);
	
	$decrypted_data = mdecrypt_generic($cipher, base64_decode_urlsafe($data_input));
	mcrypt_generic_end($cipher);
	
	return $decrypted_data;

}

function check_all_parents($auth, $parent_array, $forum_id)
{

	// This function checks all parents for a given forum_id. If any of them do not have the f_list permission
	// the function returns false, meaning the forum should not be displayed because it has a parent that should
	// not be listed. Otherwise it returns true, indicating the forum can be listed.
	
	$there_are_parents = sizeof($parent_array) > 0;
	$current_forum_id = $forum_id;
	$include_this_forum = true;
	
	while ($there_are_parents)
	{
	
		if ($parent_array[$current_forum_id] == 0) 	// No parent
		{
			$there_are_parents = false;
		}
		else
		{
			if ($auth->acl_get('f_list', $current_forum_id) == 1)
			{
				// So far so good
				$current_forum_id = $parent_array[$current_forum_id];
			}
			else
			{
				// Danger Will Robinson! No list permission exists for a parent of the requested forum, so this forum should not be shown
				$there_are_parents = false;
				$include_this_forum = false;
			}
		}
		
	}
	
	return $include_this_forum;
		
}

function truncate_words($user, $text, $max_words, $max_words_lang_string, $just_count_words = false)
{

	// This function returns the first $max_words from the supplied $text. If $just_count_words === true, a word count is returned. Note:
	// for consistency, HTML is stripped. This can be annoying, but otherwise HTML rendered in the feed may not be valid.
	
	if ($just_count_words)
	{
		return str_word_count(strip_tags($text));
	}
	
	$word_array = explode(' ', strip_tags($text));

	if (sizeof($word_array) <= $max_words)
	{
		return rtrim($text);
	}
	else
	{
		$truncated_text = '';
		for ($i=0; $i < $max_words; $i++) 
		{
			$truncated_text .= $word_array[$i] . ' ';
		}
		return rtrim($truncated_text) . $max_words_lang_string;
	}
	
}

function create_attachment_markup ($db, $phpEx, $item_id, $is_post = true, $attach_lang_string, $post_image_text_lang_string, $kib_lang_string)
{
	
	// Both posts and private messages can have attachments. The code for attaching these attachments to feed items is pretty much identical. Only
	// the source of the data differs (from a post or private message). Consequently it makes sense to have one function.
	
	$attachment_markup .= sprintf("<div class=\"box\">\n<p>%s</p>\n", $attach_lang_string);
	
	// Get all attachments
	$sql = 'SELECT *
		FROM ' . ATTACHMENTS_TABLE . '
		WHERE post_msg_id = ' . $item_id . ' AND in_message = ';
	$sql .= ($is_post) ? '0' : '1';
	$sql .= ' ORDER BY attach_id';
	
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$file_size = round(($row['filesize']/1024),2);
		// Show images, link to other attachments
		if (substr($row['mimetype'],0,6) == 'image/')
		{
			$anchor_begin = '';
			$anchor_end = '';
			$pm_image_text = '';
			$thumbnail_parameter = '';
			$is_thumbnail = ($row['thumbnail'] == 1) ? true : false;
			// Logic to resize the image, if needed
			if ($is_thumbnail)
			{
				$anchor_begin = sprintf("<a href=\"%s\">", generate_board_url() . "/download/file.$phpEx?id=" . $row['attach_id']);
				$anchor_end = '</a>';
				$pm_image_text = $post_image_text_lang_string;
				$thumbnail_parameter = '&t=1';
			}
			$attachment_markup .= sprintf("%s<br /><em>%s</em> (%s %s)<br />%s<img src=\"%s\" alt=\"%s\" title=\"%s\" />%s\n<br />%s", $row['attach_comment'], $row['real_filename'], $file_size, $kib_lang_string, $anchor_begin, generate_board_url() . "/download/file.$phpEx?id=" . $row['attach_id'] . $thumbnail_parameter, $row['attach_comment'], $row['attach_comment'], $anchor_end, $pm_image_text);
		}
		else
		{
			$attachment_markup .= ($row['attach_comment'] == '') ? '' : '<em>' . $row['attach_comment'] . '</em><br />';
			$attachment_markup .= 
				sprintf("<img src=\"%s\" title=\"\" alt=\"\" /> ", 
					generate_board_url() . '/styles/' . get_default_style() . '/theme/images/icon_topic_attach.gif') .
				sprintf("<b><a href=\"%s\">%s</a></b> (%s KiB)<br />",
					generate_board_url() . "/download/file.$phpEx?id=" . $row['attach_id'], 
					$row['real_filename'], 
					$file_size);
		}
	}
	$db->sql_freeresult($result);
	
	$attachment_markup .= '</div>';
	
	return $attachment_markup;

}
