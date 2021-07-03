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

$lang = array_merge($lang, array(

	'PLURAL_RULE'											=> 1,

	'ACP_CAT_DIGESTS'										=> 'Digests',
	'ACP_DIGESTS_SETTINGS'									=> 'Digest settings',
	'ACP_DIGESTS_GENERAL_SETTINGS'							=> 'General settings',
	'ACP_DIGESTS_GENERAL_SETTINGS_EXPLAIN'					=> 'These are the general digests settings. Please note that if timely delivery of digests must be guaranteed then you must set up and enable phpBB&rsquo;s <strong><a href="https://wiki.phpbb.com/Modular_cron#Use_system_cron">system cron</a></strong> feature. Otherwise the next time there is board traffic, digests for the current and previous hours will be mailed. For more information, see the FAQ for the Digests extension on the forums at phpbb.com.',
	'ACP_DIGESTS_USER_DEFAULT_SETTINGS'						=> 'User default settings',
	'ACP_DIGESTS_USER_DEFAULT_SETTINGS_EXPLAIN'				=> 'These settings allow administrators to set the defaults users see when they subscribe to a digest.',
	'ACP_DIGESTS_EDIT_SUBSCRIBERS'							=> 'Edit subscribers',
	'ACP_DIGESTS_EDIT_SUBSCRIBERS_EXPLAIN'					=> 'This page allows you to see who is or is not receiving digests. You can selectively subscribe or unsubscribe members, and edit all digest details of individual subscribers. By marking rows with the checkbox in the first column, you can subscribe these members with defaults or unsubscribe them. Do this by selecting the appropriate controls near the bottom of the page then pressing Submit. Also note you can use these controls to sort and filter the list in conjunction with the Go button.',
	'ACP_DIGESTS_BALANCE_LOAD'								=> 'Balance load',
	'ACP_DIGESTS_BALANCE_LOAD_EXPLAIN'						=> 'If too many digests going out at certain hours are causing performance issues, this will rebalance digest subscriptions so that roughly the same number of digests are sent for each hour wanted. The table below shows the current number and names of digest subscribers for each hour with <strong>overallocated hours bolded</strong>. This function updates digest send hours minimally. Changes occur only on those hours where the number of subscribers exceeds the average load, and only for subscribers that exceed the hourly average for that hour. <em>Caution</em>: subscribers may be upset that their subscription times were changed and may receive an email notification, depending on the setting in digests general settings. If you want you can restrict the balancing to a digest type, balance for specified hours and apply balancing to specified hours.',
	'ACP_DIGESTS_BALANCE_OPTIONS'							=> 'Balancing options',
	'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'				=> 'Mass subscribe/unsubscribe',
	'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'		=> 'This feature allows administrators to conveniently subscribe or unsubscribe all members of your forum at once. Digests default settings are used to subscribe members. If a member already has a digest subscription, a mass subscription will retain their digest settings. You cannot specify the forums that will be subscribed. Users will be subscribed to all forums to which they have read access. <strong>Caution</strong>: subscribers may be upset if they are subscribed or unsubscribed without their permission.',
	'ACP_DIGESTS_RESET_CRON_RUN_TIME'						=> 'Reset mailer',
	'ACP_DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'				=> '',
	'ACP_DIGESTS_TEST'										=> 'Manually run the mailer',
	'ACP_DIGESTS_TEST_EXPLAIN'								=> 'This feature allows you to manually run digests for initial testing or troubleshooting. You can also use it to recreate digests for hours when they may not have been sent. Only one hour of digests are created and no user data are changed.<br><br> <strong>Subscribers that could receive digests for the current hour:</strong> %s',

	'LOG_CONFIG_DIGESTS_BAD_DIGEST_TYPE'					=> '<strong>Warning: subscriber %1$s has a bad digest type of %2$s. Assumed a daily digest is wanted.</strong>',
	'LOG_CONFIG_DIGESTS_BAD_SEND_HOUR'						=> '<strong>User %1$s digest send hour is invalid. It is %2$d. The number should be >= 0 and < 24.</strong>',
	'LOG_CONFIG_DIGESTS_BALANCE_LOAD'						=> '<strong>Digests balance load run successfully</strong>',
	'LOG_CONFIG_DIGESTS_BOARD_DISABLED'						=> '<strong>Digests mailer run was attempted, but stopped because the board is disabled.</strong>',
	'LOG_CONFIG_DIGESTS_CACHE_CLEARED'						=> '<strong>The store/phpbbservices/digests folder was emptied',
	'LOG_CONFIG_DIGESTS_CLEAR_SPOOL_ERROR'					=> '<strong>Unable to clear files in the store/phpbbservices/digests folder. This may be due to a permissions issue or an incorrect path. The file permissions on the folder should be set to publicly writeable (777 on Unix-based systems).</strong>',
	'LOG_CONFIG_DIGESTS_CREATE_DIRECTORY_ERROR'				=> '<strong>Unable to create the folder %s. This may be due to insufficient permissions. The file permissions on the folder should be set to publicly writeable (777 on Unix-based systems).</strong>',
	'LOG_CONFIG_DIGESTS_CRITICAL_ERROR'						=> '<strong>The digests mailer unexpectedly errored.<br>Error number: [%1$s]<br>Error: %2$s<br>Program: %3$s Line: %4$s</strong>',
	'LOG_CONFIG_DIGESTS_DEBUG_POSTS_CURRENT_HOUR'			=> '<strong>Debug: Subscribers SQL posts query: Date UTC: %s Hour UTC: %s SQL = %s</strong>',
	'LOG_CONFIG_DIGESTS_DEBUG_SHOULD_RUN'					=> '<strong>Debug: Should run: %s, digest can run after this time: %s</strong>',
	'LOG_CONFIG_DIGESTS_DEBUG_SQL_CURRENT_HOUR'				=> '<strong>Debug: Subscribers SQL query: Date UTC: %s Hour UTC: %s SQL = %s</strong>',
	'LOG_CONFIG_DIGESTS_EDIT_SUBSCRIBERS'					=> '<strong>Edited digest subscribers</strong>',
	'LOG_CONFIG_DIGESTS_EMAILING_FAILURE'					=> '<strong>Unable to email digests for date %s hour %d UTC</strong>',
	'LOG_CONFIG_DIGESTS_EXCEPTION_ERROR'					=> '<strong>The following PHP try/catch exception occurred: %s</strong>',
	'LOG_CONFIG_DIGESTS_FILE_CLOSE_ERROR'					=> '<strong>Unable to close file %s</strong>',
	'LOG_CONFIG_DIGESTS_FILE_OPEN_ERROR'					=> '<strong>Unable to open a file handler to the folder %s. This may be due to insufficient permissions. The file permissions on the folder should be set to publicly writeable (777 on Unix-based systems).</strong>',
	'LOG_CONFIG_DIGESTS_FILE_WRITE_ERROR'					=> '<strong>Unable to write file %s. This may be due to insufficient permissions. The file permissions on the folder should be set to publicly writeable (777 on Unix-based systems).</strong>',
	'LOG_CONFIG_DIGESTS_FILTER_ERROR'						=> '<strong>Digests mailer was called with an invalid user_digest_filter_type = %1$s for %2$s</strong>',
	'LOG_CONFIG_DIGESTS_FORMAT_ERROR'						=> '<strong>Digests mailer was called with an invalid user_digest_format of %1$s for %2$s</strong>',
	'LOG_CONFIG_DIGESTS_GENERAL'							=> '<strong>Altered digest general settings</strong>',
	'LOG_CONFIG_DIGESTS_HOUR_RUN'							=> '<strong>Running digests for %1$s at %2$02d UTC</strong>',
	'LOG_CONFIG_DIGESTS_INCONSISTENT_DATES'					=> '<strong>An unusual error occurred. No hours were processed because the last time digests were successfully sent (timestamp %1$d) was after the time digests were run (timestamp %2$d).</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ABEND'							=> '<strong>Ending digests mailer abnormally. See the error log for details.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_END'							=> '<strong>Ending digests mailer</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD'						=> '<strong>Unable to send a digest to %1$s (%2$s). This problem should be investigated and fixed since it likely means there is a general emailing issue.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD_NO_EMAIL'				=> '<strong>Unable to send a digest to %s.  This problem should be investigated and fixed since it likely means there is a general emailing issue.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD'						=> array(
		1 => '<strong>A digest was %1$s %2$s (%3$s) for date %4$s and hour %5$02d UTC containing %6$d post and %7$d private message</strong>',
		2 => '<strong>A digest was %1$s %2$s (%3$s) for date %4$s and hour %5$02d UTC containing %6$d posts and %7$d private messages</strong>',
	),
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_DISK'				=> '<strong>A digest was written to store/phpbbservices/digests/%s. The digest was NOT emailed, but was placed here for analysis.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_NO_EMAIL'			=> array(
		1 => '<strong>A digest was %1$s %2$s for date %3$s and hour %4$02d UTC containing %5$d post and %6$d private message</strong>',
		2 => '<strong>A digest was %1$s %2$s for date %3$s and hour %4$02d UTC containing %5$d posts and %6$d private messages</strong>',
	),
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE'						=> '<strong>A digest was NOT sent to %1$s (%2$s) because user filters and preferences meant there was nothing to send</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE_NO_EMAIL'			=> '<strong>A digest was NOT sent to %s because user filters and preferences meant there was nothing to send</strong>',
	'LOG_CONFIG_DIGESTS_LOG_START'							=> '<strong>Starting digests mailer</strong>',
	'LOG_CONFIG_DIGESTS_MAILER_RAN_WITH_ERROR'				=> '<strong>An error occurred while the mailer was running. One or more digests may have been successfully generated.</strong>',
	'LOG_CONFIG_DIGESTS_MANUAL_RUN'							=> '<strong>Manual run of the mailer invoked</strong>',
	'LOG_CONFIG_DIGESTS_MESSAGE'							=> '<strong>%s</strong>',	// Used for general debugging, otherwise hard to troubleshoot problems in cron mode.
	'LOG_CONFIG_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'			=> '<strong>Executed a digests mass subscribe or unsubscribe action</strong>',
	'LOG_CONFIG_DIGESTS_NO_ALLOWED_FORUMS'					=> '<strong>Warning: subscriber %s does not have any forum permissions, so unless there are required forums, digests will never contain any content.</strong>',
	'LOG_CONFIG_DIGESTS_NO_BOOKMARKS'						=> '<strong>Warning: subscriber %s wants bookmarked topics in their digest but does not have any bookmarked topics.</strong>',
	'LOG_CONFIG_DIGESTS_NOTIFICATION_ERROR'					=> '<strong>Unable to send an administrator generated digests email notification to %s</strong>',
	'LOG_CONFIG_DIGESTS_NOTIFICATION_SENT'					=> '<strong>An email was sent to %1$s (%2$s) indicating that their digest settings were changed</strong>',
	'LOG_CONFIG_DIGESTS_REGULAR_CRON_RUN'					=> '<strong>Regular (phpBB) cron run of the mailer invoked</strong>',
	'LOG_CONFIG_DIGESTS_RESET_CRON_RUN_TIME'				=> '<strong>Digests mailing time was reset</strong>',
	'LOG_CONFIG_DIGESTS_RUN_TOO_SOON'						=> '<strong>Less than an hour has elapsed since digests were last run. Run aborted.</strong>',
	'LOG_CONFIG_DIGESTS_SIMULATION_DATE_TIME'				=> '<strong>Administrator chose to create digests for %s board time.</strong>',
	'LOG_CONFIG_DIGESTS_SORT_BY_ERROR'						=> '<strong>Digests mailer was called with an invalid user_digest_sortby = %1$s for %2$s</strong>',
	'LOG_CONFIG_DIGESTS_SYSTEM_CRON_RUN'					=> '<strong>System cron run of the mailer invoked</strong>',
	'LOG_CONFIG_DIGESTS_TIMEZONE_ERROR'						=> '<strong>The user_timezone "%1$s" for username "%2$s" is invalid. Assumed a timezone of "%3$s". Please ask user to set a proper timezone in the User Control Panel. See http://php.net/manual/en/timezones.php for a list of valid timezones.</strong>',
	'LOG_CONFIG_DIGESTS_USER_DEFAULTS'						=> '<strong>Altered digest user default settings</strong>',
));
