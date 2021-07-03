<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbbservices\digests\constants\constants;

/**
* Event listener
*/
class main_listener implements EventSubscriberInterface
{
	/* @var \phpbb\config\config */
	protected $config;
	
	/* @var \phpbb\template\template */
	protected $template;	
	
	/* @var \phpbb\request\request */
	protected $request;

	protected $table_prefix;
	protected $db;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\template\template	$template		Template object
	* @param \phpbb\request\request		$request		Request object
	* @param string						$table_prefix 	Prefix for phpbb's database tables
	* @param \phpbb\db\driver\factory 	$db 			The database factory object
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\request\request $request, $table_prefix, \phpbb\db\driver\factory $db)
	{
		$this->config = $config;
		$this->template = $template;
		$this->request = $request;
		$this->table_prefix = $table_prefix;
		$this->db = $db;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'					=> 'load_language_on_setup',
			'core.user_add_modify_data'			=> 'subscribe_digests_on_registration',
			'core.ucp_register_data_before'		=> 'ucp_register_data_before',
			'core.delete_user_after'			=> 'delete_user_after',
		);
	}
	
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'phpbbservices/digests',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
   	
	public function subscribe_digests_on_registration($event)
	{
		
		// This logic supports the subscribe a user to a digest automatically feature, if enabled. It also handles a subscription if the user is presented
		// the option to subscribe on registration and they select yes.
		
		$subscribe_on_registration = ($this->request->variable('digest', '0') == '1') ? true : false;	// Test if user wanted to subscribe to digests on registration

		$is_human = ($event['sql_ary']['user_type'] == USER_IGNORE) ? false : true;
		if ($is_human && ($this->config['phpbbservices_digests_enable_auto_subscriptions'] == 1 || $subscribe_on_registration))
		{
			// Subscribe user with digest defaults
			$sql_ary['user_digest_attachments'] 		= $this->config['phpbbservices_digests_user_digest_attachments'];
			$sql_ary['user_digest_block_images'] 		= $this->config['phpbbservices_digests_user_digest_block_images'];
			$sql_ary['user_digest_filter_type'] 		= $this->config['phpbbservices_digests_user_digest_filter_type'];
			$sql_ary['user_digest_format'] 				= $this->config['phpbbservices_digests_user_digest_format'];
			$sql_ary['user_digest_max_display_words'] 	= $this->config['phpbbservices_digests_user_digest_max_display_words'];
			$sql_ary['user_digest_max_posts'] 			= $this->config['phpbbservices_digests_user_digest_max_posts'];
			$sql_ary['user_digest_min_words'] 			= $this->config['phpbbservices_digests_user_digest_min_words'];
			$sql_ary['user_digest_new_posts_only'] 		= $this->config['phpbbservices_digests_user_digest_new_posts_only'];
			$sql_ary['user_digest_no_post_text'] 		= $this->config['phpbbservices_digests_user_digest_no_post_text'];
			$sql_ary['user_digest_pm_mark_read'] 		= $this->config['phpbbservices_digests_user_digest_pm_mark_read'];
			$sql_ary['user_digest_popular'] 			= $this->config['phpbbservices_digests_user_digest_popular'];
			$sql_ary['user_digest_popularity_size'] 	= $this->config['phpbbservices_digests_user_digest_popularity_size'];
			$sql_ary['user_digest_remove_foes'] 		= $this->config['phpbbservices_digests_user_digest_remove_foes'];
			$sql_ary['user_digest_reset_lastvisit'] 	= $this->config['phpbbservices_digests_user_digest_reset_lastvisit'];
			$sql_ary['user_digest_send_hour_gmt'] 		= ($this->config['phpbbservices_digests_user_digest_send_hour_gmt'] == -1) ? rand(0,23) : $this->config['phpbbservices_digests_user_digest_send_hour_gmt'];
			$sql_ary['user_digest_send_on_no_posts'] 	= $this->config['phpbbservices_digests_user_digest_send_on_no_posts'];
			$sql_ary['user_digest_show_mine'] 			= ($this->config['phpbbservices_digests_user_digest_show_mine'] == 1) ? 0 : 1;
			$sql_ary['user_digest_show_pms'] 			= $this->config['phpbbservices_digests_user_digest_show_pms'];
			$sql_ary['user_digest_sortby'] 				= $this->config['phpbbservices_digests_user_digest_sortby'];
			$sql_ary['user_digest_toc'] 				= $this->config['phpbbservices_digests_user_digest_toc'];
			$sql_ary['user_digest_type'] 				= $this->config['phpbbservices_digests_user_digest_type'];

			$event['sql_ary'] = array_merge($event['sql_ary'], $sql_ary);
		}
		
	}

	public function ucp_register_data_before($event)
	{

		// Fields on registration for that allow a user to subscribe to digests, if this feature is enabled.
		$this->template->assign_vars(array(
			'S_DIGESTS'							=> (!$this->config['phpbbservices_digests_enable_auto_subscriptions'] && $this->config['phpbbservices_digests_registration_field']) ? true : false,	
			'S_DIGESTS_REGISTER_CHECKED_YES' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? true : false,
			'S_DIGESTS_REGISTER_CHECKED_NO' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? false : true,
			)
		);
		
	}

	/**
	 * @param data $event
	 */
	public function delete_user_after($event)
	{
		// If a user is being deleted in the ACP, delete any individual forum subscriptions. This is true regardless of
		// whether there are any posts to be retained.
		if (($event['mode'] == 'remove') || ($event['mode'] == 'retain'))
		{
			$sql = 'DELETE FROM ' . $this->table_prefix . constants::DIGESTS_SUBSCRIBED_FORUMS_TABLE . ' 
				WHERE ' . $this->db->sql_in_set('user_id' , $event['user_ids']);
			$this->db->sql_query($sql);
		}
	}
}
