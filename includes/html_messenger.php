<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbbservices\digests\includes;

// The purpose of this class is to override the messenger class so HTML can be sent in email. The code is a copy and paste for the relevant events
// from the 3.3.2 source for /includes/functions_messenger.php with minimal changes needed to add this functionality.

class html_messenger extends \messenger
{

	/**
	 * Send the mail out to the recipients set previously in var $this->addresses
	 *
	 * @param int	$method		User notification method NOTIFY_EMAIL|NOTIFY_IM|NOTIFY_BOTH
	 * @param bool	$break		Flag indicating if the function only formats the subject
	 *							and the message without sending it
	 * @param bool	$is_html 	true if you want HTML email headers because the content contains HTML, false assumes content is text
	 * @param bool 	$is_digest	true if sending a digest	 *
	 * @return bool	* Send the mail out to the recipients set previously in var $this->addresses
	*/

	function send($method = NOTIFY_EMAIL, $break = false, $is_html = false, $is_digest = false)
	{
		global $config, $user, $phpbb_dispatcher;;

		// We add some standard variables we always use, no need to specify them always
		$this->assign_vars(array(
			'U_BOARD'	=> generate_board_url(),
			'EMAIL_SIG'	=> str_replace('<br />', "\n", "-- \n" . htmlspecialchars_decode($config['board_email_sig'])),
			'SITENAME'	=> htmlspecialchars_decode($config['sitename']),
		));

		$subject = $this->subject;
		$template = $this->template;
		/**
		 * Event to modify the template before parsing
		 *
		 * @event phpbbservices.digests.modify_notification_template
		 * @var	int						method		User notification method NOTIFY_EMAIL|NOTIFY_IM|NOTIFY_BOTH
		 * @var	bool					break		Flag indicating if the function only formats the subject
		 *											and the message without sending it
		 * @var	string					subject		The message subject
		 * @var \phpbb\template\template template	The (readonly) template object
		 * @since 3.2.4-RC1
		 */
		$vars = array('method', 'break', 'subject', 'template');
		extract($phpbb_dispatcher->trigger_event('phpbbservices.digests.modify_notification_template', compact($vars)));

		// Parse message through template
		$message = trim($this->template->assign_display('body'));

		/**
		* Event to modify notification message text after parsing
		*
		* @event phpbbservices.digests.modify_notification_message
		* @var	int		method	User notification method NOTIFY_EMAIL|NOTIFY_IM|NOTIFY_BOTH
		* @var	bool	break	Flag indicating if the function only formats the subject
		*						and the message without sending it
		* @var	string	subject	The message subject
		* @var	string	message	The message text
		* @since 3.1.11-RC1
		*/
		$vars = array('method', 'break', 'subject', 'message');
		extract($phpbb_dispatcher->trigger_event('phpbbservices.digests.modify_notification_message', compact($vars)));

		$this->subject = $subject;
		$this->msg = $message;
		unset($subject, $message, $template);

		// Because we use \n for newlines in the body message we need to fix line encoding errors for those admins who uploaded email template files in the wrong encoding
		$this->msg = str_replace("\r\n", "\n", $this->msg);

		// We now try and pull a subject from the email body ... if it exists,
		// do this here because the subject may contain a variable
		$drop_header = '';
		$match = array();
		if (!$is_digest && preg_match('#^(Subject:(.*?))$#m', $this->msg, $match))
		{
			$this->subject = (trim($match[2]) != '') ? trim($match[2]) : (($this->subject != '') ? $this->subject : $user->lang['NO_EMAIL_SUBJECT']);
			$drop_header .= '[\r\n]*?' . preg_quote($match[1], '#');
		}
		else
		{
			$this->subject = (($this->subject != '') ? $this->subject : $user->lang['NO_EMAIL_SUBJECT']);
		}

		if (preg_match('#^(List-Unsubscribe:(.*?))$#m', $this->msg, $match))
		{
			$this->extra_headers[] = $match[1];
			$drop_header .= '[\r\n]*?' . preg_quote($match[1], '#');
		}

		if ($drop_header)
		{
			$this->msg = trim(preg_replace('#' . $drop_header . '#s', '', $this->msg));
		}

		if ($break)
		{
			return true;
		}

		$result = true;
		switch ($method)
		{
			case NOTIFY_EMAIL:
				$result = $this->msg_email($is_html);
			break;

			case NOTIFY_IM:
				$result = $this->msg_jabber();
			break;

			case NOTIFY_BOTH:
				$result = $this->msg_email($is_html);
				$this->msg_jabber();
			break;
		}

		$this->reset();
		return $result;
	}

	/**
	* Return email header
	*/
	function build_header($to, $cc, $bcc, $is_html = false)
	{
		global $config, $phpbb_dispatcher;

		// We could use keys here, but we won't do this for 3.0.x to retain backwards compatibility
		$headers = array();

		$headers[] = 'From: ' . $this->from;

		if ($cc)
		{
			$headers[] = 'Cc: ' . $cc;
		}

		if ($bcc)
		{
			$headers[] = 'Bcc: ' . $bcc;
		}

		$headers[] = 'Reply-To: ' . $this->replyto;
		$headers[] = 'Return-Path: <' . $config['board_email'] . '>';
		$headers[] = 'Sender: <' . $config['board_email'] . '>';
		$headers[] = 'MIME-Version: 1.0';
		$headers[] = 'Message-ID: <' . $this->generate_message_id() . '>';
		$headers[] = 'Date: ' . date('r', time());
		$headers[] = ($is_html) ? 'Content-Type: text/html; charset=UTF-8' : 'Content-Type: text/plain; charset=UTF-8';
		$headers[] = 'Content-Transfer-Encoding: 8bit'; // 7bit

		$headers[] = 'X-Priority: ' . $this->mail_priority;
		$headers[] = 'X-MSMail-Priority: ' . (($this->mail_priority == MAIL_LOW_PRIORITY) ? 'Low' : (($this->mail_priority == MAIL_NORMAL_PRIORITY) ? 'Normal' : 'High'));
		$headers[] = 'X-Mailer: phpBB3';
		$headers[] = 'X-MimeOLE: phpBB3';
		$headers[] = 'X-phpBB-Origin: phpbb://' . str_replace(array('http://', 'https://'), array('', ''), generate_board_url());

		/**
		 * Event to modify email header entries
		 *
		 * @event phpbbservices.digests.modify_email_headers
		 * @var	array	headers	Array containing email header entries
		 * @since 3.1.11-RC1
		 */
		$vars = array('headers');
		extract($phpbb_dispatcher->trigger_event('phpbbservices.digests.modify_email_headers', compact($vars)));

		if (count($this->extra_headers))
		{
			$headers = array_merge($headers, $this->extra_headers);
		}

		return $headers;
	}

	/**
	* Send out emails
	*/
	function msg_email($is_html=false)
	{
		global $config, $phpbb_dispatcher;

		if (empty($config['email_enable']))
		{
			return false;
		}

		// Addresses to send to?
		if (empty($this->addresses) || (empty($this->addresses['to']) && empty($this->addresses['cc']) && empty($this->addresses['bcc'])))
		{
			// Send was successful. ;)
			return true;
		}

		$use_queue = false;
		if ($config['email_package_size'] && $this->use_queue)
		{
			if (empty($this->queue))
			{
				$this->queue = new \queue();
				$this->queue->init('email', $config['email_package_size']);
			}
			$use_queue = true;
		}

		$contact_name = htmlspecialchars_decode($config['board_contact_name']);
		$board_contact = (($contact_name !== '') ? '"' . mail_encode($contact_name) . '" ' : '') . '<' . $config['board_contact'] . '>';

		$break = false;
		$addresses = $this->addresses;
		$subject = $this->subject;
		$msg = $this->msg;
		/**
		 * Event to send message via external transport
		 *
		 * @event phpbbservices.digests.notification_message_email
		 * @var	bool	break		Flag indicating if the function return after hook
		 * @var	array	addresses 	The message recipients
		 * @var	string	subject		The message subject
		 * @var	string	msg			The message text
		 * @since 3.2.4-RC1
		 */
		$vars = array(
			'break',
			'addresses',
			'subject',
			'msg',
		);
		extract($phpbb_dispatcher->trigger_event('phpbbservices.digests.notification_message_email', compact($vars)));

		if ($break)
		{
			return true;
		}

		if (empty($this->replyto))
		{
			$this->replyto = $board_contact;
		}

		if (empty($this->from))
		{
			$this->from = $board_contact;
		}

		$encode_eol = ($config['smtp_delivery']) ? "\r\n" : PHP_EOL;

		// Build to, cc and bcc strings
		$to = $cc = $bcc = '';
		foreach ($this->addresses as $type => $address_ary)
		{
			if ($type == 'im')
			{
				continue;
			}

			foreach ($address_ary as $which_ary)
			{
				${$type} .= ((${$type} != '') ? ', ' : '') . (($which_ary['name'] != '') ? mail_encode($which_ary['name'], $encode_eol) . ' <' . $which_ary['email'] . '>' : $which_ary['email']);
			}
		}

		// Build header
		$headers = $this->build_header($to, $cc, $bcc, $is_html);

		// Send message ...
		if (!$use_queue)
		{
			$mail_to = ($to == '') ? 'undisclosed-recipients:;' : $to;
			$err_msg = '';

			if ($config['smtp_delivery'])
			{
				$result = smtpmail($this->addresses, mail_encode($this->subject), wordwrap(utf8_wordwrap($this->msg), 997, "\n", true), $err_msg, $headers);
			}
			else
			{
				$result = phpbb_mail($mail_to, $this->subject, $this->msg, $headers, PHP_EOL, $err_msg);
			}

			if (!$result)
			{
				$this->error('EMAIL', $err_msg);
				return false;
			}
		}
		else
		{
			$this->queue->put('email', array(
				'to'			=> $to,
				'addresses'		=> $this->addresses,
				'subject'		=> $this->subject,
				'msg'			=> $this->msg,
				'headers'		=> $headers)
			);
		}


		return true;
	}

}
