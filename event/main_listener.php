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

	protected $config;
	protected $db;
	protected $report_details_table;
	protected $request;
	protected $subscribed_forums_table;
	protected $template;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config			Config object
	* @param \phpbb\db\driver\factory 	$db 			The database factory object
	* @param string						$report_details_table		Extension's digests report details table
	* @param \phpbb\request\request		$request		Request object
	* @param string						$subscribed_forums_table	Extension's subscribed forums table
	* @param \phpbb\template\template	$template		Template object
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\template\template $template, \phpbb\request\request $request, \phpbb\db\driver\factory $db, string $subscribed_forums_table, string $report_details_table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->report_details_table = $report_details_table;
		$this->request = $request;
		$this->subscribed_forums_table = $subscribed_forums_table;
		$this->template = $template;
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
		
		$subscribe_on_registration = $this->request->variable('digest', '0') == '1';	// Test if user wanted to subscribe to digests on registration

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
			'S_DIGESTS'							=> !$this->config['phpbbservices_digests_enable_auto_subscriptions'] && $this->config['phpbbservices_digests_registration_field'],
			'S_DIGESTS_REGISTER_CHECKED_YES' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? true : false,
			'S_DIGESTS_REGISTER_CHECKED_NO' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? false : true,
			)
		);
		
	}

	/**
	 * Event after the user(s) delete action has been performed
	 *
	 * @event core.delete_user_after
	 * @var string	mode				Mode of posts deletion (retain|delete)
	 * @var array	user_ids			ID(s) of the deleted user(s)
	 * @var bool	retain_username		True if username should be retained, false otherwise
	 * @var array	user_rows			Array containing data of the deleted user(s)
	 * @since 3.1.0-a1
	 * @changed 3.2.2-RC1 Added user_rows
	 */
	public function delete_user_after($event)
	{
		// If a user is being deleted in the ACP, delete any individual forum subscriptions and report statistics.
		// This is true regardless of whether there are any posts to be retained.
		if (($event['mode'] == 'remove') || ($event['mode'] == 'retain'))
		{
			$sql = 'DELETE FROM ' . $this->subscribed_forums_table . ' 
				WHERE ' . $this->db->sql_in_set('user_id' , $event['user_ids']);
			$this->db->sql_query($sql);

			$sql = 'DELETE FROM ' . $this->report_details_table . ' 
				WHERE ' . $this->db->sql_in_set('user_id' , $event['user_ids']);
			$this->db->sql_query($sql);
		}
	}
}
