<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2021 Mark D. Hamill (mark@phpbbservices.com)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'DIGESTS_WEEKDAY' 					=> 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
));

$weekdays = explode(',', $lang['DIGESTS_WEEKDAY']);

$lang = array_merge($lang, array(
	'PLURAL_RULE'											=> 1,

	'DIGESTS_ALL'											=> 'All',
	'DIGESTS_ALL_ALLOWED_FORUMS'							=> 'All allowed forums',
	'DIGESTS_ALL_HOURS'										=> 'All hours',
	'DIGESTS_ALL_TYPES'										=> 'All digest types',
	'DIGESTS_ALL_SUBSCRIBED'								=> array(
																1 => '%d member was mass subscribed to receive digests',
																2 => '%d members were mass subscribed to receive digests',
															),
	'DIGESTS_ALL_UNSUBSCRIBED'								=> array(
																1 => '%d member was mass unsubscribed to receive digests',
																2 => '%d members were mass unsubscribed to receive digests',
															),
	'DIGESTS_APPLY_TO'										=> 'Apply to',
	'DIGESTS_AVERAGE'										=> 'Average',
	'DIGESTS_BALANCE_APPLY_HOURS'							=> 'Apply balancing to these hours',
	'DIGESTS_BALANCE_LOAD'									=> 'Balance load',
	'DIGESTS_BALANCE_HOURS'									=> 'Balance these hours',
	'DIGESTS_BASED_ON'										=> '(Based on UTC%+d)',
	'DIGESTS_COLLAPSE'										=> 'Collapse',
	'DIGESTS_COMMA'											=> ', ',		// Used  in salutations and to separate items in lists
	'DIGESTS_CREATE_DIRECTORY_ERROR'						=> 'Unable to create the folder %s. This may be due to insufficient permissions. The file permissions on the folder should be set to publicly writeable (777 on Unix-based systems).',
	'DIGESTS_CURRENT_VERSION_INFO'							=> 'You are running version <strong>%s</strong>.',
	'DIGESTS_CUSTOM_STYLESHEET_PATH'						=> 'Custom stylesheet path',
	'DIGESTS_CUSTOM_STYLESHEET_PATH_EXPLAIN'				=> 'This setting only applies if the enable custom stylesheet box is enabled. If it is enabled, this stylesheet will be applied to all styled digests. The path should be a relative path from your phpBB styles folder and should normally be in the theme subfolder. Note: you are responsible for creating this stylesheet and placing it in a file with the name entered here on the appropriate location on your server. Example: prosilver/theme/digest_stylesheet.css. For information on creating stylesheets, click <a href="http://www.w3schools.com/css/">here</a>.',
	'DIGESTS_DEBUG'											=> 'Enable digests debugging',
	'DIGESTS_DEBUG_EXPLAIN'									=> 'Used for technical debugging. This will write certain key troubleshooting information, such as database queries used to assemble digests, to the admin log. Generally, you need advanced development skills to interpret this information.',
	'DIGESTS_DEFAULT'										=> 'Subscribe checked rows only using defaults',
	'DIGESTS_DEFAULT_SHORT'									=> 'Subscribe using defaults',
	'DIGESTS_DAILY_ONLY'									=> 'Daily digests only',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS'						=> 'Enable automatic subscriptions',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS_EXPLAIN'				=> 'If you want new users to automatically get digests, select yes. The digests&rsquo; default settings will be automatically applied. (These are set in the digests&rsquo; user default settings). Enabling this option will <em>not</em> create subscriptions for currently unsubscribed users, inactive members or for new members who chose not to receive a digest during registration. You can set these individually using the edit subscribers function, or globally with the mass subscribe/unsubscribe option.',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET'						=> 'Enable custom stylesheet',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET_EXPLAIN'				=> 'If not enabled, the default stylesheet for the style selected in the user&rsquo;s profile is applied to styled versions of their digests.',
	'DIGESTS_ENABLE_LOG'									=> 'Write all digest actions to the admin log',
	'DIGESTS_ENABLE_LOG_EXPLAIN'							=> 'If this is enabled, all digest actions will be written to the admin log (found on the maintenance tab). This is helpful for answering digest questions since it indicates what the digests&rsquo; mailer did, when and for which subscribers. Enabling this will quickly result in a very long admin log since at least two entries will be written every hour to the log. <em>Note</em>: errors, exceptions and warnings are always written to the log.',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE'					=> 'Enable mass subscribe or unsubscribe',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'			=> 'If you say yes, when you press Submit the mass subscribe or unsubscribe action will occur. Enable with care!',
	'DIGESTS_EXCLUDE_FORUMS'								=> 'Always exclude these forums',
	'DIGESTS_EXCLUDE_FORUMS_EXPLAIN'						=> 'Enter the forum_ids for forums that must never appear in a digest. Separate the forum_ids with commas. If set to 0, no forums have to be excluded. To determine the forum_ids, when browsing a forum observe the &ldquo;f&rdquo; parameter on the URL field. This is the forum_id. Example: http://www.example.com/phpBB3/viewforum.php?f=1. Do not use forum_ids that correspond to categories. <i>This setting is ignored if bookmarked topics only are requested by a subscriber.</i>',
	'DIGESTS_EXPAND'										=> 'Expand',
	'DIGESTS_FREQUENCY_EXPLAIN'								=> 'Weekly digests are sent on the day of the week set in digests general settings. Monthly digests are sent on the first of the month. Coordinated Universal Time (UTC) is used for determining the day of the week.',
	'DIGESTS_FORMAT_FOOTER' 								=> 'Digest format',
	'DIGESTS_FROM_EMAIL_ADDRESS'							=> 'From email address',
	'DIGESTS_FROM_EMAIL_ADDRESS_EXPLAIN'					=> 'When users receive a digest, this email address will appear in the FROM field. If left blank it will default to your board&rsquo;s email contract address. Use caution if using an email address with a domain other than the one the digest is hosted on, as your mail server or the user&rsquo;s email server may interpret the email as spam.',
	'DIGESTS_FROM_EMAIL_NAME'								=> 'From email name',
	'DIGESTS_FROM_EMAIL_NAME_EXPLAIN'						=> 'This is the plain text FROM name that will appear in the email client. If left blank it will identify itself as a robot for your board.',
	'DIGESTS_HAS_UNSUBSCRIBED'								=> 'Has unsubscribed',
	'DIGESTS_HOUR_SENT'										=> 'Hour sent (based on UTC%+d)',
	'DIGESTS_HOUR_SENT_GMT'									=> 'Default hour sent (UTC)',
	'DIGESTS_IGNORE'										=> 'Change checked rows only',
	'DIGESTS_ILLOGICAL_DATE'								=> 'Your date and time is invalid. Please fix and resubmit, using a date and time format like YYYY-MM-DD HH:MM:SS.',
	'DIGESTS_INCLUDE_ADMINS'								=> 'Include administrators',
	'DIGESTS_INCLUDE_ADMINS_EXPLAIN'						=> 'This will subscribe or unsubscribe administrators in addition to normal users.',
	'DIGESTS_INCLUDE_FORUMS'								=> 'Always include these forums',
	'DIGESTS_INCLUDE_FORUMS_EXPLAIN'						=> 'Enter the forum_ids for forums that must appear in a digest. Separate the forum_ids with commas. If set to 0, no forums have to be included. To determine the forum_ids, when browsing a forum observe the &ldquo;f&rdquo; parameter on the URL field. This is the forum_id. Example: http://www.example.com/phpBB3/viewforum.php?f=1. Do not use forum_ids that correspond to categories. <i>This setting is ignored if bookmarked topics only are requested by a subscriber.</i>',
	'DIGESTS_LAST_SENT'										=> 'Digest last sent',
	'DIGESTS_LIST_USERS'    								=> array(
																	1 => '%d User',
																	2 => '%d Users',
															),
	'DIGESTS_LOWERCASE_DIGEST_TYPE'							=> 'Lowercase the digest type in digests',
	'DIGESTS_LOWERCASE_DIGEST_TYPE_EXPLAIN'					=> 'In English, the title of the digest will be something like &ldquo;My board name Daily Digest&rdquo;. In certain languages &ldquo;Digest Daily&rdquo; will logically precede the board name. If set to yes, then the digest type will appear something like &ldquo;Digest daily of my board name&rdquo;, with the first letter of the board name in lowercase.',
	'DIGESTS_MAILER_NOT_RUN'								=> 'Mailer was not run because it was not enabled or there was no request to clear the digests folder.',
	'DIGESTS_MAILER_RAN_SUCCESSFULLY'						=> 'Mailer was run successfully.',
	'DIGESTS_MAILER_RAN_WITH_ERROR'							=> 'An error occurred while the mailer was running. One or more digests may have been successfully generated. The phpBB admin and error logs may contain more information.',
	'DIGESTS_MAILER_SPOOLED'								=> 'Any digests created for the date and hour were saved in the store/phpbbservices/digests folder.',
	'DIGESTS_MARK_UNMARK_ROW'								=> 'Mark or unmark row for changes',
	'DIGESTS_MARK_ALL'										=> 'Mark or unmark all the rows',
	'DIGESTS_MAX_CRON_HOURS'								=> 'Maximum hours for mailer to process',
	'DIGESTS_MAX_CRON_HOURS_EXPLAIN'						=> 'Set this to 0 (zero) to process all digests for all hours in the queue when the mailer is run. However, if you have <strong>shared hosting</strong> then running the mailer may trigger resource limits and cause the mailer to error. This is more likely to happen if you have many subscribers and board traffic is light. Setting up a <a href="https://wiki.phpbb.com/PhpBB3.1/RFC/Modular_cron#Use_system_cron">system cron</a> is the easiest way to minimize this issue and should also ensure the timely arrival of digests.',
	'DIGESTS_MAX_ITEMS'										=> 'Maximum posts allowed in any digest',
	'DIGESTS_MAX_ITEMS_EXPLAIN'								=> 'For performance reasons, you may need to set an absolute limit to the number of posts in any one digest. If you set this to 0 (zero) this allows a digest to be of an unlimited size. You may use any whole number in this field. Please note that a digest is constrained by the number of posts in the type of digest requested (daily, weekly or monthly) as well as other criteria the user may set.',
	'DIGESTS_MAIL_FREQUENCY' 								=> 'Digest frequency',
	'DIGESTS_MAILER_RESET' 									=> 'The digest’s mailer was reset',
	'DIGESTS_MIGRATE_UNSUPPORTED_VERSION'					=> 'Upgrades of the digests modification (for phpBB 3.0) are supported from version 2.2.6 forward. You have version %s. The extension cannot be migrated or installed. Please seek help on the discussion forum for the extension on phpbb.com.',
	'DIGESTS_MIN_POPULARITY_SIZE'							=> 'Minimum topic post count popularity',
	'DIGESTS_MIN_POPULARITY_SIZE_EXPLAIN'					=> 'This sets the minimum number of posts per day needed for a topic to considered popular. A subscriber cannot set a value below this value. This value is applied to the subscriber&rsquo;s time period only: day, week or month, so it reflects recent topic popularity.',
	'DIGESTS_MONTHLY_ONLY'									=> 'Monthly digests only',
	'DIGESTS_NEVER_VISITED'									=> 'Never visited',
	'DIGESTS_NO_DIGESTS_SENT'								=> 'No digests sent',
	'DIGESTS_NO_MASS_ACTION'								=> 'No action was taken, because you did not enable the feature',
	'DIGESTS_NOTIFY_ON_ADMIN_CHANGES'						=> 'Notify member via email of administrator digest changes',
	'DIGESTS_NOTIFY_ON_ADMIN_CHANGES_EXPLAIN'				=> 'Edit subscribers, balance load and mass subscribe/unsubscribe allow the administrator to change a user&rsquo;s digest settings. If yes, emails will be sent to subscribers when any aspect of their subscription is changed by an administrator.',
	'DIGESTS_NUMBER_OF_SUBSCRIBERS'							=> 'No. of subscribers',
	'DIGESTS_PMS_MARK_READ'									=> 'Mark my private messages read if in digest',
	'DIGESTS_RANDOM_HOUR'									=> 'Random hour',
	'DIGESTS_REBALANCED'									=> array(
																	1 => 'During this rebalancing, %d digest subscriber had their digest send hour changed.',
																	2 => 'During this rebalancing, %d digest subscribers had their digest send hour changed.',
															),
	'DIGESTS_REGISTRATION_FIELD'							=> 'Allow users to subscribe to digests upon registration',
	'DIGESTS_REGISTRATION_FIELD_EXPLAIN'					=> 'If enabled, upon registration users have the option to get digests using the board&rsquo;s defaults. This option does not appear if automatic subscriptions are enabled.',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS'						=> 'Reply to email address',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS_EXPLAIN'				=> 'When users receive a digest, this email address will appear in the REPLY TO field. If left blank it will default to your board&rsquo;s email contact address. Use caution if using an email address with a domain other than the one the digest is hosted on, as your mail server or the user&rsquo;s mail server may interpret the email as spam.',
	'DIGESTS_RESET_CRON_RUN_TIME'							=> 'Reset the mailer',
	'DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'					=> 'If reset, when the mailer is next run it will create digests for the current hour only. Any digests in the queue are removed. Resetting can be useful when you are done testing digests or if phpBB&rsquo;s cron has not been run in a long while.',
	'DIGESTS_RUN_TEST'										=> 'Run the mailer',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL'							=> 'Clear the store/phpbbservices/digests folder',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_ERROR'					=> 'Could not remove all the files in the store/phpbbservices/digests folder. This may be due to a permissions issue or the folder was deleted. The file permissions on the folder should be set to publicly writeable (777 on Unix-based systems).',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_EXPLAIN'					=> 'If yes, any files in the store/phpbbservices/digests folder will be erased before any digests are stored. You can also run this without running the mailer.',
	'DIGESTS_RUN_TEST_DATE_HOUR'							=> 'Date and hour to run',
	'DIGESTS_RUN_TEST_DATE_HOUR_EXPLAIN'					=> 'Use the date and hour picker control to select a date and hour. The date and time you select will be interpreted based on your timezone set in your board profile.',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS'						=> 'Test email address',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS_EXPLAIN'				=> 'If an email address is specified in this field, all digests for the requested hour will be sent to this email address instead of the board contact email address. <em>Note</em>: if you elected to send emails to files, this field is ignored.',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN'						=> 'Send all digests to the email address specified',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN_EXPLAIN'				=> 'If you want to email the digests in the test, all digests will be emailed to the address specified in the field below. <em>Note</em>: if you elected to send emails to files, this setting is ignored. If yes, but no email address is specified, the board contact email address will be used. <em>Caution</em>: certain email servers may interpret a large volume of emails in a short period of time from the same address as spam or inappropriate use. Enable with care. If you say no then digests will actually be mailed to subscribers, which may confuse them.',
	'DIGESTS_RUN_TEST_SPOOL'								=> 'Send results to files instead of emailing',
	'DIGESTS_RUN_TEST_SPOOL_EXPLAIN'						=> 'Prevents digests from being mailed. Instead each digest is written to a file in the store/phpbbservices/digests folder with file names in the following format: username-yyyy-mm-dd-hh.html or username-yyyy-mm-dd-hh.txt. (Files with a .txt suffix are text-only digests.) username is the phpBB board username. yyyy indicates the year, mm the month, dd the day in month and hh the hour in UTC. To view these files, first download them to a local folder. View the file in a browser using its local mode: CTRL+O or CMD+O (Mac). <em>Note</em>: use the letter O, not the number 0.',
	'DIGESTS_SALUTATION_FIELDS'								=> 'Select salutation fields',
	'DIGESTS_SALUTATION_FIELDS_EXPLAIN'						=> 'Enter the custom profile field names, if any, you want to substitute for the username in the digest salutation. If left blank, the username is used. Enter the field identification name(s) found on the custom profile fields page. Separate multiple field names with commas. <em>Note:</em> The fields must be of type “Single text field”. If none of the custom profile fields exist or there is no value for the fields for the subscriber, the username will be used instead. Example: firstname,lastname (if you created custom profile fields with these names). One space will be placed between each custom profile field in the digest salutation.',
	'DIGESTS_SEARCH_FOR_MEMBER'								=> 'Search for member',
	'DIGESTS_SEARCH_FOR_MEMBER_EXPLAIN'						=> 'Enter the full or partial member name or email address to look for, then press <strong>Go</strong>. Leave blank to disable this kind of search. Member name searches are not case sensitive. <em>Note</em>: There must be an @ symbol in the field for an email search to be performed.',
	'DIGESTS_SELECT_FORUMS_ADMIN_EXPLAIN'					=> 'The list of forums includes only those forums this user is allowed to read. If you wish to give this user access to additional forums not shown here, expand their forum user or group permissions. Note although you can fine tune the forums that appear in their digest, if their digest type is &ldquo;None&rdquo; no digest will actually be sent.',
	'DIGESTS_SHOW'											=> 'Show',
	'DIGESTS_SHOW_EMAIL'									=> 'Show email address in log',
	'DIGESTS_SHOW_EMAIL_EXPLAIN'							=> 'If this is enabled, the subscriber&rsquo;s email address is shown in entries in the admin log by the username of the subscriber. This can be useful in troubleshooting digest mailer issues.',
	'DIGESTS_SHOW_FORUM_PATH'								=> 'Show forum path in digest',
	'DIGESTS_SHOW_FORUM_PATH_EXPLAIN'						=> 'If enabled, digest forum names will show the categories and forums a forum is nested within, for example: &ldquo;Category 1 &#8249; Forum 1 &#8249; Category A &#8249; Forum B&rdquo;, going as deep as categories and forums are nested on your board. Otherwise only the name of the forum containing will be shown, &ldquo;Forum B&rdquo; in this example.',
	'DIGESTS_SORT_ORDER'									=> 'Sort order',
	'DIGESTS_SORTING_AND_FILTERING'							=> 'Sorting and filtering',
	'DIGESTS_STOPPED_SUBSCRIBING'							=> 'Has unsubscribed',
	'DIGESTS_STRIP_TAGS'									=> 'Tags to strip from digests',
	'DIGESTS_STRIP_TAGS_EXPLAIN'							=> 'Mail servers may reject emails or blacklist senders containing certain HTML tags, or place digests in a spam mail folder. Type the name of the tags (without &lt; or &gt; characters) to exclude, separated by commas. For example, to remove the video and iframe tags, enter &ldquo;video,iframe&rdquo; in this field. Avoid entering common tags like h1, p and div as these are essential to rendering digests.',
	'DIGESTS_SUBSCRIBE_EDITED'								=> 'Your digest subscription settings have been edited',
	'DIGESTS_SUBSCRIBE_SUBJECT'								=> 'You have been subscribed to receive email digests',
	'DIGESTS_SUBSCRIBE_ALL'									=> 'Subscribe all',
	'DIGESTS_SUBSCRIBE_ALL_EXPLAIN'							=> 'If you say no, everyone will be unsubscribed.',
	'DIGESTS_SUBSCRIBE_LITERAL'								=> 'Subscribe',
	'DIGESTS_SUBSCRIBED'									=> 'Subscribed',
	'DIGESTS_SUBSCRIBERS_DAILY'                           	=> 'Daily subscribers',
	'DIGESTS_SUBSCRIBERS_WEEKLY'                           	=> 'Weekly subscribers',
	'DIGESTS_SUBSCRIBERS_MONTHLY'                           => 'Monthly subscribers',
	'DIGESTS_UNLINK_FOREIGN_URLS'							=> 'Remove foreign URLs from digests',
	'DIGESTS_UNLINK_FOREIGN_URLS_EXPLAIN'					=> 'Removes links in digests to other domains. Some email systems will flag emails containing links to other domains as likely spam. This could cause digests to be sent to spam folders or keep digests from being sent by the outgoing email server.',
	'DIGESTS_UNSUBSCRIBE'									=> 'Unsubscribe checked rows only',
	'DIGESTS_UNSUBSCRIBE_SUBJECT'							=> 'You have been unsubscribed from receiving email digests',
	'DIGESTS_UNSUBSCRIBED'									=> 'Has not subscribed',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS'				=> 'Maximum words to display in a post',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS_EXPLAIN'		=> 'Set to -1 to show the full post text by default. Setting at zero (0) means by default  the user will see no post text at all.',
	'DIGESTS_USER_DIGESTS_PM_MARK_READ'						=> 'Mark private messages as read when they appear in the digest',
	'DIGESTS_NO_USERS_SELECTED'								=> 'No changes made! You must mark one or more checkboxes to make subscription changes for users.',
	'DIGESTS_USERS_PER_PAGE'								=> 'Subscribers per page',
	'DIGESTS_USERS_PER_PAGE_EXPLAIN'						=> 'This controls how many rows of digest subscribers an administrator sees per page when they select the edit subscribers option. It is recommended you leave this at 20. Setting this value too high may trigger a PHP max_input_vars error.',
	'DIGESTS_WEEKLY_DIGESTS_DAY'							=> 'Select the day of the week for sending out weekly digests',
	'DIGESTS_WEEKLY_DIGESTS_DAY_EXPLAIN'					=> 'The day of the week is based on UTC. Depending on the hour wanted, subscribers in the western hemisphere may actually receive their weekly digest one day earlier than expected.',
	'DIGESTS_WEEKLY_ONLY'									=> 'Weekly digests only',
	'DIGESTS_WITH_SELECTED'									=> 'With selected',

));
