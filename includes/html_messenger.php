<?php
/**
*
* @package phpBB Extension - digests
* @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace phpbbservices\digests\includes;

global $phpbb_root_path, $phpEx;
include($phpbb_root_path . 'includes/functions_messenger.' . $phpEx); // Used to send emails

// The purpose of this class is to override the messenger class so HTML can be sent in email. The code is a copy and paste for the relevant events
// from the 3.2.0 source for /includes/functions_messenger.php with minimal changes needed to add this functionality. I made one major change from
// the way phpBB works by default: to bypass the queue altogether as the expectation is that a digest will be delivered promptly.

class html_messenger extends \messenger
{

	/**
	* Send the mail out to the recipients set previously in var $this->addresses
	*
	* $is_html 		true if you want HTML email headers because the content contains HTML, false assumes content is text
	* $is_digest	true if sending a digest
	*/

	function send($method = NOTIFY_EMAIL, $break = false, $is_html = false, $is_digest = false)
	{
		global $config, $user;

		// We add some standard variables we always use, no need to specify them always
		$this->assign_vars(array(
			'U_BOARD'	=> generate_board_url(),
			'EMAIL_SIG'	=> str_replace('<br>', "\n", "-- \n" . htmlspecialchars_decode($config['board_email_sig'])),
			'SITENAME'	=> htmlspecialchars_decode($config['sitename']),
		));

		// Parse message through template
		$this->msg = trim($this->template->assign_display('body'));

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
		 * @event core.modify_email_headers
		 * @var	array	headers	Array containing email header entries
		 * @since 3.1.11-RC1
		 */
		$vars = array('headers');
		extract($phpbb_dispatcher->trigger_event('core.modify_email_headers', compact($vars)));

		if (sizeof($this->extra_headers))
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
		global $config;

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

		$contact_name = htmlspecialchars_decode($config['board_contact_name']);
		$board_contact = (($contact_name !== '') ? '"' . mail_encode($contact_name) . '" ' : '') . '<' . $config['board_contact'] . '>';

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

		return true;
	}

}
