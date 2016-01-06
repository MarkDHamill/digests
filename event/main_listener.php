<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2015 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

//use phpbbservices\digests\constants\constants;

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

	/**
	* Constructor
	*
	* @param \phpbb\config\config		$config
	* @param \phpbb\controller\helper	$helper		Controller helper object
	* @param \phpbb\template			$template	Template object
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, \phpbb\template\template $template)
	{
		$this->config = $config;
		$this->helper = $helper;
		$this->template = $template;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'						=> 'load_language_on_setup',
			'ucp_register_profile_fields_before'		=> 'ucp_register_profile_fields_before',
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
   	
	public function ucp_register_profile_fields_before($event)
	{

		$this->template->assign_vars(array(
			// Begin Digest Mod
			'S_DIGESTS'							=> (!$this->config['phpbbservices_digests_enable_auto_subscriptions'] && $this->config['phpbbservices_digests_registration_field']) ? true : false,
			'S_DIGESTS_REGISTER_CHECKED_YES' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? true : false,
			'S_DIGESTS_REGISTER_CHECKED_NO' 	=> ($this->config['phpbbservices_digests_user_digest_registration']) ? false : true,
			// End Digest Mod
			)
		);
		
	}

}
