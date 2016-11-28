<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2016 Mark D. Hamill (mark@phpbbservices.com)
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

$server_settings_url = append_sid('index.php?i=acp_board&amp;mode=server');

$lang = array_merge($lang, array(
	'ACP_CAT_DIGESTS'										=> 'eMail-Zusammenfassungen',
	'ACP_DIGESTS_SETTINGS'									=> 'Konfiguration',
	'ACP_DIGESTS_GENERAL_SETTINGS'							=> 'Allgemeine Konfiguration',
	'ACP_DIGESTS_GENERAL_SETTINGS_EXPLAIN'					=> 'Hier können allgemeine Grundeinstellungen vorgenommen werden. Für einen stundengenauen Versand der eMails muss die <a href="https://wiki.phpbb.com/Modular_cron#Use_system_cron">an dieser Stelle</a> beschriebene Konfiguration vorgenommen werden und <a href="'. $server_settings_url . '"><strong>\'Wiederkehrende Aufgaben über Cron-Job des Systems ausführen\'</strong></a> in der Board-Konfiguration aktiviert werden. Andernfalls werden die Zusammenfassungen der aktuellen und der vorhergehenden Stunden versandt, sobald es wieder Nutzer-Aktivitäten im Board gibt. Weitere Informationen darüber kann man den Digests-Extension-FAQ im Forum von phpbb.com entnehmen.',
	'ACP_DIGESTS_USER_DEFAULT_SETTINGS'						=> 'Standard-Nutzerkonfiguration',
	'ACP_DIGESTS_USER_DEFAULT_SETTINGS_EXPLAIN'				=> 'Hier können Administratoren Standardwerte vorgeben, die zunächst in der individuellen Nutzerkonfiguration vorausgewählt werden sollen.',
	'ACP_DIGESTS_EDIT_SUBSCRIBERS'							=> 'Abonnements verwalten',
	'ACP_DIGESTS_EDIT_SUBSCRIBERS_EXPLAIN'					=> 'Hier kann man wer welche Zusammenfassungen abonniert hat. Man kann ferner einzelne Abonnements ganz individuell und bis ins kleinste Detail hinzufügen, ändern und löschen. Mithilfe der Checkbox am Zeilenanfang können einzelne Nutzer mit der Standard-Konfiguration für der eMailversand aktiviert werden oder auch aus dem Verteiler rausgenommen werden. Dazu muss man unterhalb der Nutzerliste die entsprechende Auswahl vornehmen und auf \'Absenden\' klicken. Ferner findet man dort in Kombination mit dem \'Aktualisieren\'-Button verschiedenene Sortier- und Filtermöglichkeiten.',
	'ACP_DIGESTS_BALANCE_LOAD'								=> 'Last-Verteilung',
	'ACP_DIGESTS_BALANCE_LOAD_EXPLAIN'						=> 'Wenn in manchen Stunden zu viele eMail-Zusammenfassungen versendet werden, kann das zu Performance-Beeinträchtigungen führen. Hier kann deshalb eine ausgewogene Verteilung der Serverlast hergestellt werden. Die Tabelle stellt die Abonnementzahlen für jede einzelne Stunde dar. Die Stundenangabe bezieht sich dabei auf die in den individuellen Grundeinstellungen ausgewählte Sendeuhrzeit. Die Ausführung dieser Funktion beeinflusst die individuell eingestellten Zeiten nicht mehr, als für eine ausgelichene Verteilung wirklich notwendig ist. Es werden nur die Stunden entzerrt, in denen die Abonnentenzahl die Durchschnittslast übersteigt. <em>Achtung</em>: Trotzdem sind damit möglicherweise nicht alle Abonnenten einverstanden.',
	'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'				=> 'Massen-Abonnement',
	'ACP_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'		=> 'Diese Option erlaubt es Administratoren, die Zustellung von eMail-Zusammenstellungen bequem für alle Forumsteilnehmer auf einmal zu aktivieren oder zu deaktivieren. Bei der Aktivierung werden die eingestellten Standardvorgaben verwendet. Wenn ein Nutzer bereits die Zusammenfassung abonniert hat, bleiben seine persönlichen Einstellungen erhalten. Für neue Nutzer werden dabei alle Themenbereiche, für die eine Leseberechtigung besteht, aktiviert. Eine Auswahl ist nicht möglich. <strong>Achtung:</strong> Diese Funktion kann leicht zur Verärgerung einzelner Nutzer führen und ist mit Vorsicht zu verwenden.',
	'ACP_DIGESTS_RESET_CRON_RUN_TIME'						=> 'Mailer zurücksetzen',
	'ACP_DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'				=> '',
	'ACP_DIGESTS_TEST'										=> 'Manueller Test-Versand',
	'ACP_DIGESTS_TEST_EXPLAIN'								=> 'Diese Funktion ermöglicht den manuellen eMail-Versand zur Überprüfung der Grundeinstellungen oder zur Fehlersuche. Man kann mit dieser Funktion auch die abonnierten Zusammenfassungen für eine bestimmten Zeitpunkt (nochmal) versenden. Die Zeitzone des Boards (UTC [+] ' . $timeOffset . ') wird dabei für die Berechnung des Datums und der Stunde verwendet. Bitte beachte, dass der Versand der Zusammenfassungen erst dadurch Nutzer-Aktivitäten im Board angestoßen werden können. Das bedeutet, dass die Zusammenfassungen etwas später als geplant gesendet werden. Für stundengenauen eMail-Versand muss sonst ein <a href="https://wiki.phpbb.com/Modular_cron#Use_system_cron">System-Cronjob</a> eingerichtet werden muss und in der <a href="'. $server_settings_url . '">Serverkonfiguration des Boards</a> unter <strong>\'Wiederkehrende Aufgaben über Cron-Job des Systems ausführen\'</strong> aktiviert werden muss. Weitere Informationen stehen in den FAQ zu dieser Extension im Forum von phpbb.com.',

	'LOG_CONFIG_DIGESTS_BAD_DIGEST_TYPE'					=> '<strong>Hinweis: Abonnent %s hat als Zusammenfassungsart %s eingestellt. Sinnvoller wäre jedoch die \'Tägliche Zusammenfassung\'.</strong>',
	'LOG_CONFIG_DIGESTS_BAD_SEND_HOUR'						=> '<strong>Die Sendeuhrzeit von %s ist ungültig. Sie lautet %s. Der Wert muss immer >= 0 und < 24 sein.</strong>',
	'LOG_CONFIG_DIGESTS_BALANCE_LOAD'						=> '<strong>Umverteilung der Last erfolgreich abgeschlossen</strong>',
	'LOG_CONFIG_DIGESTS_BOARD_DISABLED'						=> '<strong>Der Digest-Mailer wurde aufgerufen, aber auch gleich wieder gestoppt, weil das Board deaktiviert ist.</strong>',
	'LOG_CONFIG_DIGESTS_CACHE_CLEARED'						=> '<strong>Der store/ext/phpbbservices/digests-Ordner wurde geleert',
	'LOG_CONFIG_DIGESTS_CLEAR_SPOOL_ERROR'					=> '<strong>Es konnten nicht alle Dateien aus dem store/ext/phpbbservices/digests-Ordner entfernt werden. Ursache könnten fehlende Datei-Rechte sein. Alle Dateien sollten \'publicly writeable\' sein (777 auf Unix-basierten Systemen).</strong>',
	'LOG_CONFIG_DIGESTS_DIRECTORY_CREATE_ERROR'				=> '<strong>Es konnte kein store/ext/phpbbservices/digests-Ordner angelegt werden. Ursache könnten fehlende Datei-Rechte beim store-Ordner des Forums sein.</strong>',
	'LOG_CONFIG_DIGESTS_EDIT_SUBSCRIBERS'					=> '<strong>Abonnenten bearbeitet</strong>',
	'LOG_CONFIG_DIGESTS_FILE_CLOSE_ERROR'					=> '<strong>Die Datei %s kann nicht geschlossen werden</strong>',
	'LOG_CONFIG_DIGESTS_FILE_OPEN_ERROR'					=> '<strong>File Handler kann im Verzeichnis %s nicht geöffnet werden. Ursache könnten fehlende Datei-Rechte sein. Alle Dateien sollten \'publicly writeable\' sein (777 auf Unix-basierten Systemen).</strong>',
	'LOG_CONFIG_DIGESTS_FILE_WRITE_ERROR'					=> '<strong>Die Datei %s konnte nicht gespeichert werden. Ursache könnten fehlende Datei-Rechte sein. Alle Dateien sollten \'publicly writeable\' sein (777 auf Unix-basierten Systemen).</strong>',
	'LOG_CONFIG_DIGESTS_FILTER_ERROR'						=> '<strong>Der Digests-Mailer wurde mit ungültigem user_digest_filter_type = %s für %s aufgerufen</strong>',
	'LOG_CONFIG_DIGESTS_FORMAT_ERROR'						=> '<strong>Der Digest-Mailer wurde mit ungültigem user_digest_format %s für %s aufgerufen</strong>',
	'LOG_CONFIG_DIGESTS_GENERAL'							=> '<strong>Allgemeine Konfiguration der eMail-Zusammenstellung geändert</strong>',
	'LOG_CONFIG_DIGESTS_HOUR_RUN'							=> '<strong>eMail-Zusammenfassung für %s UTC gestartet.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD'						=> '<strong>eMail-Zusammnestellung für %s (%s) konnte nicht erfolgreich versandt weden</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_BAD_NO_EMAIL'				=> '<strong>eMail-Zusammnestellung für %s konnte nicht erfolgreich versandt weden</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD'						=> '<strong>eMail-Zusammnestellung %s %s (%s) für den %s, %d UTC mit %s Beiträgen und %s Privaten Nachricht(en)</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_DISK'				=> '<strong>Es wurde eine eMail-Zusammnestellung mit dem Dateinamen %s im store/ext/phpbbservices/digests-Ordner abgespeichert. Die Zusammenfassung wurde nicht per eMail versandt, sondern dort für Prüfzwecke hinterlegt.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_GOOD_NO_EMAIL'			=> '<strong>eMail-Zusammnestellung %s %s für den %s, %d UTC mit %s Beiträgen und %s Privaten Nachricht(en)</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE'						=> '<strong>Die geplante eMail-Zusammenstellung für %s (%s) wurde nicht gesendet, weil es aufgrund von Nutzer-Einstellungen und möglichen Filtervorgaben nichts zu versenden gab.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_ENTRY_NONE_NO_EMAIL'			=> '<strong>Die geplante eMail-Zusammenstellung für %s wurde nicht gesendet, weil es aufgrund von Nutzer-Einstellungen und möglichen Filtervorgaben nichts zu versenden gab.</strong>',
	'LOG_CONFIG_DIGESTS_LOG_START'							=> '<strong>Digest-Mailer gestartet</strong>',
	'LOG_CONFIG_DIGESTS_LOG_END'							=> '<strong>Digest-Mailer beendet</strong>',
	'LOG_CONFIG_DIGESTS_MAILER_RAN_WITH_ERROR'				=> '<strong>Ein Fehler trat während der Verwendung des Digest-Mailers auf. Es können dennoch einige eMail-Zusammenfassungen erfolgreich erstellt worden sein.</strong>',
	'LOG_CONFIG_DIGESTS_MANUAL_RUN'							=> '<strong>Mailer manuell gestartet</strong>',
	'LOG_CONFIG_DIGESTS_MESSAGE'							=> '<strong>%s</strong>',	// Used for general debugging, otherwise hard to do in cron mode.
	'LOG_CONFIG_DIGESTS_MASS_SUBSCRIBE_UNSUBSCRIBE'			=> '<strong>Eine Massenabonnementsoperation wurde erfolgreich durchgeführt.</strong>',	
	'LOG_CONFIG_DIGESTS_NO_ALLOWED_FORUMS'					=> '<strong>Hinweis: Abonnent %s besitzt bisher keinerlei Forumsrechte. Die Zusammenfassungen werden deshalb leer bleiben, es sei denn es existieren verpflichtende Themenbereiche.</strong>',
	'LOG_CONFIG_DIGESTS_NO_BOOKMARKS'						=> '<strong>Hinweis: Abonnent %s hat \'Nur Lesezeichenthemen\' ausgewählt, aber noch keine eigenen Lesezeichen erstellt.</strong>',
	'LOG_CONFIG_DIGESTS_NOTIFICATION_ERROR'					=> '<strong>Eine vom Administrator erzeugte Zusammenfassungungsbenachrichtigung könnte nicht an folgen adresse versendet werden: %s</strong>',
	'LOG_CONFIG_DIGESTS_NOTIFICATION_SENT'					=> '<strong>Es wurde eine E-Mail an %s (%s) versandt, die besagt, dass die Zusammenfassungseinstellungen verändert wurden.</strong>',	
	'LOG_CONFIG_DIGESTS_REGULAR_CRON_RUN'						=> '<strong>Mailer durch internen phpBB-Crondienst gestartet.</strong>',
	'LOG_CONFIG_DIGESTS_RESET_CRON_RUN_TIME'				=> '<strong>Letzter Versandzeitpunkt wurde zurückgesetzt.</strong>',
	'LOG_CONFIG_DIGESTS_RUN_TOO_SOON'						=> '<strong>Weniger als eine Stunde ist seit dem letzten Start des Zusammenfassungsversandes vergangen. Die Ausführung wurde deshalb abgebrochen.</strong>',
	'LOG_CONFIG_DIGESTS_SIMULATION_DATE_TIME'				=> '<strong>Ein Administrator hat eMail-Zusammenstellungen für %s um %s:00 Board-Zeit erzeugt.</strong>',
	'LOG_CONFIG_DIGESTS_SORT_BY_ERROR'						=> '<strong>Der Digest-Mailer mit ungültigem user_digest_sortby = %s für %s aufgerufen</strong>',
	'LOG_CONFIG_DIGESTS_SYSTEM_CRON_RUN'						=> '<strong>Mailer durch externen System-Cronjob gestartet.</strong>',
	'LOG_CONFIG_DIGESTS_TIMEZONE_ERROR'				=> '<strong>Die user_timezone "%s" für Nutzer "%s" ist fehlerhaft. Die Zeitzone lautet "%s". Bitte den Nutzer seine Zeitenzoneneinstellung im UCP zu korrigieren. Siehe dazu auch die Liste erlaubter Einstellungen unter http://php.net/manual/de/timezones.php.</strong>',
	'LOG_CONFIG_DIGESTS_USER_DEFAULTS'						=> '<strong>Standard-Nutzereinstellungen geändert</strong>',
));
