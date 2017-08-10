<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2017 Mark D. Hamill (mark@phpbbservices.com)
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

global $phpbb_container;

$config = $phpbb_container->get('config');
$helper = $phpbb_container->get('phpbbservices.digests.common');

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
	'DIGESTS_CURRENT_VERSION_INFO'							=> 'You are running version <strong>%s</strong>.',
	'DIGESTS_CUSTOM_STYLESHEET_PATH'						=> 'Custom stylesheet path',
	'DIGESTS_CUSTOM_STYLESHEET_PATH_EXPLAIN'				=> 'This setting only applies if the Enable custom stylesheet box is enabled. If it is enabled, this stylesheet will be applied to all HTML digests. The path should be a relative path from your phpBB styles directory and should normally be in the theme subdirectory. Note: you are responsible for creating this stylesheet and placing it in a file with the name entered here on the appropriate location on your server. Example: prosilver/theme/digest_stylesheet.css. For information on creating stylesheets, click <a href="http://www.w3schools.com/css/">here</a>.',
	'DIGESTS_COLLAPSE'										=> 'Collapse',
	'DIGESTS_COMMA'											=> ', ',		// Used  in salutations and to separate items in lists
	'DIGESTS_DEFAULT'										=> 'Subscribe using default settings',
	'DIGESTS_DAILY_ONLY'									=> 'Daily digests only',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS'						=> 'Enable automatic subscriptions',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS_EXPLAIN'				=> 'If you want new users to automatically get digests, select Yes. The digests&rsquo; default settings will be automatically applied. (These are set in the digests&rsquo; user default settings). Enabling this option will <em>not</em> create subscriptions for currently unsubscribed users, inactive members or for new members who chose not to receive a digest during registration. You can set these individually using the edit subscribers function, or globally with the mass subscribe/unsubscribe option.',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET'						=> 'Enable custom stylesheet',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET_EXPLAIN'				=> 'If not enabled, the default stylesheet for the style selected in the user&rsquo;s profile is applied to HTML versions of their digests.',
	'DIGESTS_ENABLE_LOG'									=> 'Write all digest actions to the admin log',
	'DIGESTS_ENABLE_LOG_EXPLAIN'							=> 'If this is enabled, all digest actions will be written to the admin log (found on the Maintenance tab). This is helpful for answering digest questions since it indicates what the digests&rsquo; mailer did, when and for which subscribers. Enabling this will quickly result in a very long Admin log since at least two entries will be written every hour to the log. Note: errors, exceptions and warnings are always written to the log.',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE'					=> 'Enable mass subscribe or unsubscribe',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'			=> 'If you say yes, when you press Submit the mass subscribe or unsubscribe action will occur. Enable with care!',
	'DIGESTS_EXCLUDE_FORUMS'								=> 'Always exclude these forums',
	'DIGESTS_EXCLUDE_FORUMS_EXPLAIN'						=> 'Enter the forum_ids for forums that must never appear in a digest. Separate the forum_ids with commas. If set to 0, no forums have to be excluded. To determine the forum_ids, when browsing a forum observe the &ldquo;f&rdquo; parameter on the URL field. This is the forum_id. Example: http://www.example.com/phpBB3/viewforum.php?f=1. Do not use forum_ids that correspond to categories. <i>This setting is ignored if bookmarked topics only are requested by a subscriber.</i>',
	'DIGESTS_EXPAND'										=> 'Expand',
	'DIGESTS_FREQUENCY_EXPLAIN'								=> 'Weekly digests are sent on ' . $weekdays[$config['phpbbservices_digests_weekly_digest_day']] . '. Monthly digests are sent on the first of the month. Coordinated Universal Time (UTC) is used for determining the day of the week.',
	'DIGESTS_FORMAT_FOOTER' 								=> 'Digest format',
	'DIGESTS_FROM_EMAIL_ADDRESS'							=> 'From email address',
	'DIGESTS_FROM_EMAIL_ADDRESS_EXPLAIN'					=> 'When users receive a digest, this email address will appear in the FROM field. If left blank it will default to your board&rsquo;s email contract address. Use caution if using an email address with a domain other than the one the digest is hosted on, as your mail server or the user&rsquo;s email server may interpret the email as spam.',
	'DIGESTS_FROM_EMAIL_NAME'								=> 'From email name',
	'DIGESTS_FROM_EMAIL_NAME_EXPLAIN'						=> 'This is the plain text FROM name that will appear in the email client. If left blank it will identify itself as a robot for your board.',
	'DIGESTS_HAS_UNSUBSCRIBED'								=> 'Has unsubscribed',
	'DIGESTS_HOUR_SENT'										=> 'Hour sent (based on UTC%+d)',
	'DIGESTS_IGNORE'										=> 'Ignore global actions',
	'DIGESTS_ILLOGICAL_DATE'								=> 'Your simulation date is illogical, such as February 31. Please fix and resubmit.',
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
	'DIGESTS_MAILER_NOT_RUN'								=> 'Mailer was not run because it was not enabled.',
	'DIGESTS_MAILER_RAN_SUCCESSFULLY'						=> 'Mailer was run successfully.',
	'DIGESTS_MAILER_RAN_WITH_ERROR'							=> 'An error occurred while the mailer was running. One or more digests may have been successfully generated.',
	'DIGESTS_MAILER_SPOOLED'								=> 'Any digests created for the date and hour were saved in the cache/phpbbservices/digests directory.',
	'DIGESTS_MAX_CRON_HOURS'								=> 'Maximum hours for mailer to process',
	'DIGESTS_MAX_CRON_HOURS_EXPLAIN'						=> 'Set this to 0 (zero) to process all digests for all hours in the queue when the mailer is run. However, if you have <strong>shared hosting</strong> then running the mailer may trigger resource limits and cause the mailer to error. This is more likely to happen if you have many subscribers and board traffic is light. Setting up a <a href="https://wiki.phpbb.com/PhpBB3.1/RFC/Modular_cron#Use_system_cron">system cron</a> is the easiest way to minimize this issue and should also ensure the timely arrival of digests.',
	'DIGESTS_MAX_ITEMS'										=> 'Maximum posts allowed in any digest',
	'DIGESTS_MAX_ITEMS_EXPLAIN'								=> 'For performance reasons, you may need to set an absolute limit to the number of posts in any one digest. If you set this to 0 (zero) this allows a digest to be of an unlimited size. You may use any whole number in this field. Please note that a digest is constrained by the number of posts in the type of digest requested (daily, weekly or monthly) as well as other criteria the user may set.',
	'DIGESTS_MAIL_FREQUENCY' 								=> 'Digest frequency',
	'DIGESTS_MIGRATE_UNSUPPORTED_VERSION'					=> 'Upgrades of the digests modification (for phpBB 3.0) are supported from version 2.2.6 forward. You have version %s. The extension cannot be migrated or installed. Please seek help on the support forum on phpbb.com.',
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
	'DIGESTS_REFRESH'										=> 'Refresh',
	'DIGESTS_REGISTRATION_FIELD'							=> 'Allow users to subscribe to digests upon registration',
	'DIGESTS_REGISTRATION_FIELD_EXPLAIN'					=> 'If enabled, upon registration users have the option to get digests using the board&rsquo;s defaults. This option does not appear if automatic subscriptions are enabled.',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS'						=> 'Reply to email address',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS_EXPLAIN'				=> 'When users receive a digest, this email address will appear in the REPLY TO field. If left blank it will default to your board&rsquo;s email contact address. Use caution if using an email address with a domain other than the one the digest is hosted on, as your mail server or the user&rsquo;s mail server may interpret the email as spam.',
	'DIGESTS_RESET_CRON_RUN_TIME'							=> 'Reset the mailer',
	'DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'					=> 'If reset, when the mailer is next run it will create digests for the current hour only. Any digests in the queue are removed. Resetting can be useful when you are done testing digests or if phpBB&rsquo;s cron has not been run in a long while.',
	'DIGESTS_RUN_TEST'										=> 'Run the mailer',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL'							=> 'Clear the cache/phpbbservices/digests directory',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_ERROR'					=> 'Could not remove all the files in the cache/phpbbservices/digests directory. This may be due to a permissions issue or the directory was deleted. The file permissions on the directory should be set to publicly writeable (777 on Unix-based systems).',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_EXPLAIN'					=> 'If Yes, any files in the cache/phpbbservices/digests directory will be erased. This is a good thing to do to ensure previous digest files are not accessible. This action is done before any new digests are written to this directory.',
	'DIGESTS_RUN_TEST_DAY'									=> 'Simulation day in the month',
	'DIGESTS_RUN_TEST_DAY_EXPLAIN'							=> 'Enter a whole number from 1 to 31. If the year, month and day are in the future of course no digests will be created. Don&rsquo;t use a day that does not logically belong in the month, like February 31.',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS'						=> 'Test email address',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS_EXPLAIN'				=> 'If an email address is specified in this field, all digests for the requested hour will be sent to this email address instead of the board contact email address.',
	'DIGESTS_RUN_TEST_HOUR'									=> 'Simulation hour',
	'DIGESTS_RUN_TEST_HOUR_EXPLAIN'							=> 'Digests will be sent as of the hour specified. The hour is based on your board timezone (' . $helper->make_tz_offset($config['board_timezone']) . ' UTC). If it is in the future there will be no digests created. Enter a whole number from 0 to 23.',
	'DIGESTS_RUN_TEST_MONTH'								=> 'Simulation month',
	'DIGESTS_RUN_TEST_MONTH_EXPLAIN'						=> 'Enter a whole number from 1 to 12. Normally this should be set to the current month. If the year and month are in the future of course no digests will be created.',
	'DIGESTS_RUN_TEST_OPTIONS'								=> 'Run date and time options',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN'						=> 'Send all digests to the email address specified',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN_EXPLAIN'				=> 'If you want to email the digests in the test, all digests will be emailed to the address specified in the field below. If Yes, but no email address is specified, the board contact email address (' . $config['board_email']. ') will be used. <em>Caution</em>: certain email servers may interpret a large volume of emails in a short period of time from the same address as spam or inappropriate use. Enable with care. If you say No then digests will actually be mailed to subscribers, which may confuse them.',
	'DIGESTS_RUN_TEST_SPOOL'								=> 'Send results to files instead of emailing',
	'DIGESTS_RUN_TEST_SPOOL_EXPLAIN'						=> 'Prevents digests from being mailed. Instead each digest is written to a file in the cache/phpbbservices/digests directory with file names in the following format: username-yyyy-mm-dd-hh.html or username-yyyy-mm-dd-hh.txt. (Files with a .txt suffix are text-only digests.) yyyy indicates the year, mm the month, dd the day in month and hh the hour. Dates and hours in the file name are based on Coordinated Universal Time (UTC). If you simulate a different day or hour for mailing the digest using the fields below, file names will use those dates and hours. These digests can then be viewed if you specify the correct URL.',
	'DIGESTS_RUN_TEST_TIME_USE'								=> 'Simulate month and hour, or day of week and hour for sending digest',
	'DIGESTS_RUN_TEST_TIME_USE_EXPLAIN'						=> 'If set to Yes, the controls below will be used to send a digest as if it were the month and hour or the day of the week and hour specified. If No, the current date and hour will be used.',
	'DIGESTS_RUN_TEST_YEAR'									=> 'Simulation year',
	'DIGESTS_RUN_TEST_YEAR_EXPLAIN'							=> 'Years from 2000 through 2030 are allowed. Normally this should be set to the current year. If the year is in the future of course no digests will created.',
	'DIGESTS_SEARCH_FOR_MEMBER'								=> 'Search for member',
	'DIGESTS_SEARCH_FOR_MEMBER_EXPLAIN'						=> 'Enter the full or partial member name to look for then press Refresh. Leave blank to see all members. Searches are not case sensitive.',
	'DIGESTS_SELECT_FORUMS_ADMIN_EXPLAIN'					=> 'The list of forums includes only those forums this user is allowed to read. If you wish to give this user access to additional forums not shown here, expand their forum user or group permissions. Note although you can fine tune the forums that appear in their digest, if their digest type is &ldquo;None&rdquo; no digest will actually be sent.',
	'DIGESTS_SHOW'											=> 'Show',
	'DIGESTS_SHOW_EMAIL'									=> 'Show email address in log',
	'DIGESTS_SHOW_EMAIL_EXPLAIN'							=> 'If this is enabled, the subscriber&rsquo;s email address is shown in entries in the admin log by the username of the subscriber. This can be useful in troubleshooting digest mailer issues.',
	'DIGESTS_SHOW_FORUM_PATH'								=> 'Show forum path in digest',
	'DIGESTS_SHOW_FORUM_PATH_EXPLAIN'						=> 'If enabled, digest forum names will show the categories and forums a forum is nested within, for example: &ldquo;Category 1 :: Forum 1 :: Category A :: Forum B&rdquo;, going as deep as categories and forums are nested on your board. Otherwise only the name of the forum containing will be shown, &ldquo;Forum B&rdquo; in this example.',
	'DIGESTS_SORT_ORDER'									=> 'Sort order',
	'DIGESTS_STOPPED_SUBSCRIBING'							=> 'Has unsubscribed',
	'DIGESTS_STRIP_TAGS'									=> 'Tags to strip from digests',
	'DIGESTS_STRIP_TAGS_EXPLAIN'							=> 'The presence of certain HTML tags in digests may cause issues. Mail servers may reject emails or blacklist senders containing certain HTML tags, or place digests in a spam mail folder. Type the name of the tags (without &lt; or &gt; characters) to exclude and separated by commas. For example, to remove the video and iframe tags, enter &ldquo;video,iframe&rdquo; in this field. Avoid entering common tags like h1, p and div as these are essential to rendering digests.',
	'DIGESTS_SUBSCRIBE_EDITED'								=> 'Your digest subscription settings have been edited',
	'DIGESTS_SUBSCRIBE_SUBJECT'								=> 'You have been subscribed to receive email digests',
	'DIGESTS_SUBSCRIBE_ALL'									=> 'Subscribe all',
	'DIGESTS_SUBSCRIBE_ALL_EXPLAIN'							=> 'If you say no, everyone will be unsubscribed.',
	'DIGESTS_SUBSCRIBE_LITERAL'								=> 'Subscribe',
	'DIGESTS_SUBSCRIBED'									=> 'Subscribed',
	'DIGESTS_SUBSCRIBERS'                           		=> 'Subscribers',	
	'DIGESTS_UNSUBSCRIBE'									=> 'Unsubscribe',
	'DIGESTS_UNSUBSCRIBE_SUBJECT'							=> 'You have been unsubscribed from receiving email digests',
	'DIGESTS_UNSUBSCRIBED'									=> 'Has not subscribed',
	'DIGESTS_USER_DIGESTS_CHECK_ALL_FORUMS'					=> 'All forums to be selected by default',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS'				=> 'Maximum words to display in a post',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS_EXPLAIN'		=> 'Set to -1 to show the full post text by default. Setting at zero (0) means by default  the user will see no post text at all.',
	'DIGESTS_USER_DIGESTS_PM_MARK_READ'						=> 'Mark private messages as read when they appear in the digest',
	'DIGESTS_USER_DIGESTS_REGISTRATION'						=> 'Allow user to subscribe to digests during registration',
	'DIGESTS_USERS_PER_PAGE'								=> 'Subscribers per page',
	'DIGESTS_USERS_PER_PAGE_EXPLAIN'						=> 'This controls how many rows of digest subscribers an administrator sees per page when they select the edit subscribers option.',
	'DIGESTS_WEEKLY_DIGESTS_DAY'							=> 'Select the day of the week for sending out weekly digests',
	'DIGESTS_WEEKLY_DIGESTS_DAY_EXPLAIN'					=> 'The day of the week is based on UTC. Depending on the hour wanted, subscribers in the western hemisphere may actually receive their weekly digest one day earlier than expected.',
	'DIGESTS_WEEKLY_ONLY'									=> 'Weekly digests only',
	'DIGESTS_WITH_SELECTED'									=> 'With selected',

));
