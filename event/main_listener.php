<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
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
	
	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;	
	
	/* @var \phpbb\request\request */
	protected $request;

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper		Controller helper object
	* @param \phpbb\template\template	$template	Template object
	* @param \phpbb\request\request		$request	Request object
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template, \phpbb\request\request $request)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
		$this->request = $request;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'					=> 'load_language_on_setup',
			'core.user_add_modify_data'			=> 'subscribe_digests_on_registration',
			'core.ucp_register_data_before'		=> 'ucp_register_data_before',
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
		
		$this->template->assign_vars(array(
			'S_DIGESTS'							=> (!$this->config['phpbbservices_digests_enable_auto_subscriptions'] && $this->config['phpbbservices_digests_registration_field']) ? true : false,	
			'S_DIGESTS_REGISTER_CHECKED_YES' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? true : false,
			'S_DIGESTS_REGISTER_CHECKED_NO' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? false : true,
			)
		);
		
	}

}
