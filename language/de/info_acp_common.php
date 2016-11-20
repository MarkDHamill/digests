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

global $config;

// Needed for some timezone offset magic
$tz_board = new \DateTimeZone($config['board_timezone']);
$datetime_tz_board = new \DateTime('now', $tz_board);
$timeOffset = $tz_board->getOffset($datetime_tz_board) / 3600;

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

	'DIGESTS_ALL'											=> 'Alle Nutzer',
	'DIGESTS_ALL_ALLOWED_FORUMS'							=> 'Alle Themenbereiche für die Leseberechtigung besteht',
	'DIGESTS_ALL_SUBSCRIBED'								=> 'Es wurden Abonnements für %s Nutzer erstellt',
	'DIGESTS_ALL_UNSUBSCRIBED'								=> 'Die Abonnements von insgesamt %s Nutzern wurden gelöscht',
	'DIGESTS_BALANCE_LOAD'									=> 'Last-Verteilung',
	'DIGESTS_BASED_ON'										=> '(UTC [+] %s)',
	'DIGESTS_CURRENT_VERSION_INFO'							=> 'Aktuelle Version: <strong>%s</strong>',
	'DIGESTS_CUSTOM_STYLESHEET_PATH'						=> 'Pfad zum Custom-Stylesheet',
	'DIGESTS_CUSTOM_STYLESHEET_PATH_EXPLAIN'				=> 'Dieser Pfadangabe ist nur von Bedeutung, wenn weiter oben auch die Verwendung des Costum-Sylesheet aktiviert ist. Das Stylesheet wird dann für alle HTML-Zusammenfassungen verwendet. Es muss der relative Pfad zum phpBB-styles-Verzeichnis angegeben werden. Es ist sinnvoll, dafür ein eigenes Unterverzeichnis innerhalb des Themes anzulegen. Anmerkung: Es fällt in deinen eigenen Zuständigkeitsbereich, selbst ein solches Stylesheet zu entwickeln und es unter dem hier angegebenen Pfad und Namen auf den Server zu hinterlegen. Beispiel: prosilver/theme/digest_stylesheet.css. Informationen zum Erstellen von Stylesheets findest du <a href="http://www.w3schools.com/css/">hier</a>.',
	'DIGESTS_COLLAPSE'										=> 'Einklappen',
	'DIGESTS_DEFAULT'										=> 'Abonnement mit Standard-Einstellungen anlegen',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS'						=> 'Automatisches Abonnieren aktivieren',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS_EXPLAIN'				=> 'Um bei neuen Forumsnutzern standardmäßig nach der Registrierung ein aktiviertes Abonnement zu bewirken, muss hier \'Ja\' ausgewählt sein. Es werden dabei die Standard-Vorgaben der Administration verwendet (Zu finden unter \'Standard-Nutzerkonfiguration\'). Das Aktivieren dieser Option bewirkt <em>kein</em> Abonnement bei Nutzern, deren Abonnement beendet worden ist, die inaktiv sind oder die bei der Registrierung die email-Zusammenfassung deaktiviert haben. Einzelne Nutzer lassen sich aber noch individuell mithilfe der Abonnementverwaltung hinzufügen oder pauschal mithilfe des Massen-Abonnements.',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET'						=> 'Custom-Stylesheet aktivieren',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET_EXPLAIN'				=> 'Wenn kein Costum-Stylesheet eingerichtet ist, wird bei der Erzeugung aller HTML-Zusammenfassungen jeweils das Standard-Stylsheet verwendet, welches zum Style gehört, der im jeweiligen Nutzer-Profil ausgewählt wurde.',
	'DIGESTS_ENABLE_LOG'									=> 'Alle Aktivitäten der Digest-Extension mit im Admin-Log erfassen',
	'DIGESTS_ENABLE_LOG_EXPLAIN'							=> 'Wenn diese option aktiviert wurde, werden alle Aktivitäten der Extension ins Administrations-Protokoll geschrieben (zu finden im Wartungstab). Das kann bei der Fehlersuche sehr hilfreich sein, weil es genau anzeigt, welche Schritte der Digest-Mailer für welche Abonnenten durchgeführt hat und zu welchem Zeitpunkt das war. Daraus resultiert allerdings ziemlich schnell ein extrem langes Admin-Log, denn der Digest-Mailer erzeugt mindestens zwei Einträge in jeder Stunde. Hinweis: Fehlermeldungen, Ausnahmezustände und Warnungen werden unabhängig von dieser Einstellung immer ins Protokoll geschrieben.',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE'					=> 'Massenabonnement ermöglichen',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'			=> 'Wenn hier \'Ja\' ausgewählt wurde, wird beim Anklicken von \'Absenden\' die ausgewählte Aktion für alle Nutzer unumkehrbar durchgeführt. Bitte nur mit äußerster Vorsicht einsetzen!',
	'DIGESTS_EXCLUDE_FORUMS'								=> 'Diese Themenbereiche immer ausschließen',
	'DIGESTS_EXCLUDE_FORUMS_EXPLAIN'						=> 'Hier können die IDs der Themenbereiche ausgewählt werden, die von allen Zusammenfassungen generell ausgeschlossen werden sollen. Mehrere Forum-IDs können, durch Kommas getrennt, zusammen angegeben werden. 0 bedeutet, dass gar keine Themenbereich ausgeschlossen werden. Um die Themenbereichs-ID herauszufinden, muss man nach dem \'F=\'-Parameter in den URL-Feldern suchen. Beispiel: http://www.example.com/phpBB3/viewforum.php?f=1. Bitte nicht die IDs von Kategorien verwenden! <i>Diese Option wird ignoriert, wenn nur die Lesezeichen-Themen vom Nutzer ausgewählt wurden.</i>',
	'DIGESTS_EXPAND'										=> 'Ausklappen',
	'DIGESTS_FROM_EMAIL_ADDRESS'							=> 'Absendeemailadresse',
	'DIGESTS_FROM_EMAIL_ADDRESS_EXPLAIN'					=> 'Wenn die eMail-Zusammenfassung beim Nutzer eintrifft, erscheint diese Emaildresse im Absender-Feld (From). Wird das Feld leer gelassen, so wird automatisch die Kontakt-Emailadsresse des Boards verwendet. Diese Adresse sollte mit Bedacht gewählt werden, da Adressen von einer fremden Domain schon vom absendenden Mailserver oder dann vom empfangeneden Server leicht als spamverdächtig eingestuft und herausgefiltert werden könnten.',
	'DIGESTS_FROM_EMAIL_NAME'								=> 'Absendername',
	'DIGESTS_FROM_EMAIL_NAME_EXPLAIN'						=> 'Hier kann der Name festgelegt werden, der beim Empfänger als Absender der eMail-Zusammenfassungen angezeigt werden soll. Wenn das Feld frei gelassen wird, identifiziert der Mailer sich als Mail-Robot.',
	'DIGESTS_HAS_UNSUBSCRIBED'								=> 'Abonnement eigenhändig beendet',
	'DIGESTS_HOUR_SENT'										=> 'Sendeuhrzeit<br />(UTC [+] %s)',
	'DIGESTS_IGNORE'										=> 'Keine globale Änderung vornehmen',
	'DIGESTS_ILLOGICAL_DATE'								=> 'Dein Simulationsdatum ist unlogisch (z.B. 31. Februar). Bitte korrigieren und erneut absenden.',
	'DIGESTS_INCLUDE_ADMINS'								=> 'Schließe Administratoren mit ein',
	'DIGESTS_INCLUDE_ADMINS_EXPLAIN'						=> 'Dadurch werden neben den Standard-Nutzern auch alle Administratoren beim Erstellen oder Beeden des Massen-Abonnements mit eingeschlossen.',
	'DIGESTS_INCLUDE_FORUMS'								=> 'Diese Themenbereich immer mit einschließen',
	'DIGESTS_INCLUDE_FORUMS_EXPLAIN'						=> 'Bitte hier alle Themenbereichs-IDs aufführen, die verpflichtend immer mit in der zusammenfassung erscheinen sollen. Mehrere IDs werden durch Komma getrennt. Eine 0 bedeutet, dass keine Themenbereiche verpflichtend mit abonniert werden sollen. Um die Themenbereichs-ID herauszufinden, muss man nach dem \'F=\'-Parameter in den URL-Feldern suchen. Beispiel: http://www.example.com/phpBB3/viewforum.php?f=1. Bitte nicht die IDs von Kategorien verwenden! <i>Diese Option wird ignoriert, wenn nur die Lesezeichen-Themen vom Nutzer ausgewählt wurden.</i>',
	'DIGESTS_LAST_SENT'										=> 'Letzter Versandzeitpunkt',
	'DIGESTS_LIST_USERS'    								=> array(
																	1	=>	'Es wird nur ein Nutzer angezeigt ',
																	2	=>	'Es werden insgesamt %s Nutzer aufgelistet |',
																),
	'DIGESTS_MAILER_NOT_RUN'								=> 'Mailer wurde nicht gestartet, weil er nicht aktiviert war.',
	'DIGESTS_MAILER_RAN_SUCCESSFULLY'						=> 'Mailer wurde erfolgreich gestartet.',
	'DIGESTS_MAILER_SPOOLED'								=> 'Die für diesen Tag und diese Stunde vorgesehenen Zusammenfassungen wurden im store/ext/phpbbservices/digests-Verzeichnis gespeichert.',
	'DIGESTS_MAX_CRON_HOURS'								=> 'Maximale Ausführungsdauer pro Aufruf für den Mailer',
	'DIGESTS_MAX_CRON_HOURS_EXPLAIN'						=> 'Bei einem Dedicated Server oder bei Virtual Hosting Umgebungen kann diese Einstellung normalerweise auf 0 (Null) bleiben, um alle Versandzeitpunkte abzudecken. Läuft das Forum dagegen in einer <strong>Shared Hosting</strong> Umgebung, dann kann die Ausführung des Mailers Fehler verursachen, insbesondere, wenn es dort viele Abonnenten und viele abzudeckende Versandzeitpunkte gibt. Der einfachste Weg zur Vermeidung solcher Probleme ist die <em>Einrichtung eines<a href="https://wiki.phpbb.com/PhpBB3.1/RFC/Modular_cron#Use_system_cron">System-Cronjobs</a></em>. Nur ein System-Cronjob kann den fristgerechten Versand der eMail-Zusammenfassung gewährleisten. Andernfalls läuft man Gefahr, dass solche Fehler durch das Erreichen von Obergrenzen in den aufgeteilten Ressourcen eines Shared Hosts verursacht werden. Wenn das vorkommt, und ein System-Cronjob nicht verwendet werden kann, sollte dieser Wert auf 1 gesetzt werden. Eine weitere Erhöhung dieses Wertes kann eventuell darüberhinaus vorgenommen werden, wenn man die Erfahrung gemacht hat, dass mehrere Stunden bis zum Abschluss der Aufgabe nötig sind. <em>Hinweis:</em> Der Versand der Zusammenfassungen kann sich durch eine solche Konfiguration für manche Abonnenten verzögern, weil das Forum immer Nutzerverkehr benötigt, um den Mailer laufen zu lassen.',
	'DIGESTS_MAX_ITEMS'										=> 'Maximale Beitragszahl pro Zusammenfassung',
	'DIGESTS_MAX_ITEMS_EXPLAIN'								=> 'Aus Performance-Gründen kann es sinnvoll sein, hier eine absolute Obergrenze für alle Zusammenfassungen festzulegen. Eine Null bedeutet, dass es kein Beitragslimit gibt und die Zusammenfassungen unendlich groß werden können. Es sind nur ganzzahlige Werte erlaubt. Dabei ist zu bedenken, dass die Größe der Zusammenstellungen auch durch die gewählte Zusammenfassungsart (täglich, wöchentlich, monatlich) und durch andere Voreinstellungen weiter eingeschränkt werden kann.',
	'DIGESTS_MIGRATE_UNSUPPORTED_VERSION'					=> 'Upgrades of the digests modification (for phpBB 3.0) are supported from version 2.2.6 forward. You have version %s. The extension cannot be migrated or installed. Please seek help on the support forum on phpbb.com.',
	'DIGESTS_NEVER_VISITED'									=> 'Noch nie besucht',
	'DIGESTS_NO_DIGESTS_SENT'								=> 'Keine Zusammenfassung versandt',
	'DIGESTS_NO_MASS_ACTION'								=> 'Es wurde kein Arbeitsvorgang durchgeführt, weil diese Funktion deaktiviert ist',
	'DIGESTS_NOTIFY_ON_ADMIN_CHANGES'						=> 'Nutzer via eMail über die vom Administrator geänderten Einstellungen benachrichtigen',
	'DIGESTS_NOTIFY_ON_ADMIN_CHANGES_EXPLAIN'				=> "Die Abonnement-Verwaltung, Last-Verteilung und Massenabonnement-Operationen ermöglichen dem Administrator, Nutzereinstellungen zu verändern. 'Ja' bedeutet, dass eine E-Mail an die entsprechenden Abonnenten verschickt wird, sobald einzelne Punkte ihrer Konfiguration durch den Administrator beeinflusst worden sind.",
	'DIGESTS_RANDOM_HOUR'									=> 'Zufälliger Zeitpunkt',
	'DIGESTS_REBALANCED'									=> 'Während der Lastumverteilung hat/haben gerade %s Abonnent/en selbst eine Änderung des Sendezeitpunktes vergenommen.',
	'DIGESTS_REFRESH'										=> 'Aktualisieren',
	'DIGESTS_REGISTRATION_FIELD'							=> 'Neuen Nutzern während der Registrierung ermöglichen, eine eMail-Zusammenfassung zu abonnieren.',
	'DIGESTS_REGISTRATION_FIELD_EXPLAIN'					=> 'Wenn diese Option aktiviert ist, können Nutzer schon bei Ausfüllen des Registrierungsformulares auswählen, ob sie die eMail-Zusammenfassung mit den Standardvorgaben abonnieren möchten. Diese Auswahlmöglichkeit erscheint dort nicht, wenn das \'Automatische Abonnieren\' aktiviert ist.',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS'						=> 'Antwortemailadresse',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS_EXPLAIN'				=> 'Diese Emailadresse erscheint beim Empfänger im REPLY-TO-Feld (Antworten). Wenn das feld leer ist, wird die Kontakt-Emailadresse des Boards verwendet. Diese Adresse sollte mit Bedacht gewählt werden, da Adressen von einer fremden Domain schon vom absendenden Mailserver oder dann vom empfangeneden Server leicht als spamverdächtig eingestuft und herausgefiltert werden könnten.',
	'DIGESTS_RESET_CRON_RUN_TIME'							=> 'Mailer zurücksetzen',
	'DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'					=> 'Wenn der Mailer zurückgesetzt wurde, werden bei der nächsten Ausführung nur noch Zusammenfassungen für die aktuelle Stunde erzeugt. Alle Zusammenfassungen in der Warteschlange werden entfernt. Ein Zurücksetzen kann z.B. nach dem Ausprobieren des Zusammenfassungsversandes sinnvoll sein oder wenn der phpBB-interne Cron-Dienst sehr lange nicht gelaufen ist.', 
	'DIGESTS_RUN_TEST'										=> 'Mailer starten',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL'							=> 'store/ext/phpbbservices/digests-Ordner leeren',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_ERROR'					=> 'Es konnten nicht alle Dateien aus dem store/ext/phpbbservices/digests-Ordner entfernt werden. Ursache könnten fehlende Datei-Rechte sein. Alle Dateien sollten \'publicly writeable\' sein (777 auf Unix-basierten Systemen).',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_EXPLAIN'					=> '\'Ja\' bedeutet, dass alle Dateien im store/ext/phpbbservices/digests-Ordner gelöscht werden. Diese Einstellung ist sinnvoll, um auszuschließen, dass noch auf alte Zusammenfassungen zugegriffen werden kann, während bereits neue Zusammenfassungen in diesem Ordner abgelegt werden.',
	'DIGESTS_RUN_TEST_DAY'									=> 'Simulierter Kalendertag',
	'DIGESTS_RUN_TEST_DAY_EXPLAIN'							=> 'Ganze Zahl zwischen 1 und 31. Wenn Jahr, Monat und Kalendertag in der Zukunft liegen, wird natürlich keine Zusammenfassung erzeugt. Unlogische Eingaben, wie z.B. \'31. Februar\' werden nicht akzeptiert.',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS'						=> 'Test-Emailadresse',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS_EXPLAIN'				=> 'Wenn hier eine Enailadresse angegeben ist, werden alle Zusammenfassungen des gewünschten Zeitpunktes an diese Adresse gesendet. Ist sie leer, wird bei Bedarf die Kontakt-Emailadresse des Boards als Empfängeradresse verwendet.',
	'DIGESTS_RUN_TEST_HOUR'									=> 'Simulierte Uhrzeit',
	'DIGESTS_RUN_TEST_HOUR_EXPLAIN'							=> 'Die Zusammenfassungen, die für die hier angegebene Stunde geplant sind oder waren, werden im Rahmen der Simulation erzeugt und versandt. Die zeit bezieht sich auf die Zeitzoneneinstellung des Boards (UTC [+] ' . $timeOffset . '). Liegt dieser Zeitpunkt in der Zukunft, so können die Zusammenfassungen keine Beiträge enthalten. Erlaubt sind ganze Zahlen zwischen 0 und 23.',
	'DIGESTS_RUN_TEST_MONTH'								=> 'Simulierter Monat',
	'DIGESTS_RUN_TEST_MONTH_EXPLAIN'						=> 'Ganzzahliger Wert von 1 bis 12. Meist ist es sinnvoll, den gegenwärtigen Monat zu wählen. Wenn Jahr und Monat in der Zukunft liegen, werden natürlich keine Test-Zusammenfassungen erzeugt.',
	'DIGESTS_RUN_TEST_OPTIONS'								=> 'Definierten Zeitpunkt simulieren',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN'						=> 'Alle eMail-Zusammenfassungen an die unten festgelegte Adresse senden',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN_EXPLAIN'				=> 'Zu Testzwecken, sollten die Zusammenfassungen nicht an die Nutzeradressen gesendet werden, sondern an eine Test-Emailadresse. Wenn hier \'Ja\' aktiviert ist, aber weiter unten keine entsprechende Emailadresse angegeben ist, gehen alle Test-Zusammenfassungen an die Kontaktadresse des Boards(' . $config['board_email']. '). <em>Achtung:</em> Manche eMmail-Server werden solche großen Mengen an ähnlichen eMails vom selben Absender und in kurzem Zeitintervall als Spam-Versuch oder unangemessene Benutzung zurückweisen. Diese Funktion sollte sehr überlegt ausgewählt werden. Wird hier nämlich \'Nein\' selektiert, so werden evtl. alte Zusammenfassungen erneut an die Adressen der Nutzer gesendet, was zur Verwirrung führen kann.',
	'DIGESTS_RUN_TEST_SPOOL'								=> 'Zusammenfassungen als Dateien speichern, anstatt sie als Emails zu senden',
	'DIGESTS_RUN_TEST_SPOOL_EXPLAIN'						=> 'Verhindert das Versenden der Zusammenfassungen per Email. Stattdessen wird jede Zusammenfassung in eine Datei im store/ext/phpbbservices/digests-Ordner geschrieben. Die Dateinamen haben dabei folgendes Format: username-yyyy-mm-dd-hh.html oder username-yyyy-mm-dd-hh.txt. (Dateien mit der Endung .txt sind Nur-Text-Zusammenfassungen.) yyyy ist das Jahr, mm der Monat, dd der Kalendertag und hh die Stunde. Daten und Uhrzeiten sind dort als UTC angegeben. Wenn weiter unten ein spezieller Simulationszeitpunkt angegeben wird, so wird dieser auch für den Dateinamen verwendet. Diese Dateien können unter der entsprechenden URL im Browser angesehen werden.',
	'DIGESTS_RUN_TEST_TIME_USE'								=> 'Simuliere einen bestimmten Sendezeitpunkt',
	'DIGESTS_RUN_TEST_TIME_USE_EXPLAIN'						=> 'Wenn hier \'Ja\' ausgewählt wird, wird die Zusammenstellung so generiert und versendet, als wäre jetzt dieser Zeitpunkt. \'Nein\' bedeutet dagegen, dass die aktuelle Uhrzeit und das heutige Datum verwendet werden.',
	'DIGESTS_RUN_TEST_YEAR'									=> 'Simuliertes Jahr',
	'DIGESTS_RUN_TEST_YEAR_EXPLAIN'							=> 'Es sind nur Jahre zwischen 2000 und 2030 erlaubt. Es wird empfohlen das aktuelle Jahr zu nehmen. Liegt das Jahr in der Zukunft, so werden natürlich keine Zusammenfassungen erzeugt.',
	'DIGESTS_SEARCH_FOR_MEMBER'								=> 'Teilnehmer suchen',
	'DIGESTS_SEARCH_FOR_MEMBER_EXPLAIN'						=> 'Man kann hier den vollständigen Nutzernamen oder auch nur einen Teil davon eingeben. Anschließend auf \'Aktualisieren\' klicken. Um alle Nutzer zu sehen, das Feld leer lassen. Die Suche ist \'nicht case sensitive\'.',
	'DIGESTS_SELECT_FORUMS_ADMIN_EXPLAIN'					=> 'In der Auswahlliste tauchen nur die Themenbereiche auf, für die der Nutzer auch eine Leseberechtigung hat. Wenn Bedarf besteht, dem Nutzer auch Beiträge aus hier nicht mit aufgeführten Themenbereichen zukommen zu lassen, muss dafür eine entsprechende Änderung in den Benutzer- oder Gruppenrechten vorgenommen werden.',
	'DIGESTS_SHOW'											=> 'Anzeigen',
	'DIGESTS_SHOW_EMAIL'									=> 'Emailadresse im Log anzeigen',
	'DIGESTS_SHOW_EMAIL_EXPLAIN'							=> 'Wenn diese Option aktiviert ist, wird zusätzlich zum Nutzernamen auch die Emailadresse des Nutzers im Administrationsprotokoll mit aufgeführt. Gerade im Zusammenhang mit Mailer-Problemen kann diese Funktion bei der Fehlersuche hilfreich sein.',
	'DIGESTS_SORT_ORDER'									=> 'Sortierreihenfolge',
	'DIGESTS_STOPPED_SUBSCRIBING'							=> 'Abonnement gestoppt',
	'DIGESTS_SUBSCRIBE_EDITED'								=> 'Deine Konfiguration des eMail-Zusammensfassungsversandes wurde geändert.',
	'DIGESTS_SUBSCRIBE_SUBJECT'								=> 'Du erhältst ab jetzt regelmäßig Zusammenfassungen der Forumsbeiträge per Email an die in deinem Profil hinterlegte Adresse.',
	'DIGESTS_SUBSCRIBE_ALL'									=> 'Überall Abonnements anlegen (sonst löschen)',
	'DIGESTS_SUBSCRIBE_ALL_EXPLAIN'							=> 'Achtung: \'Nein\' bedeutet, dass alle Abonnements gelöscht werden. Bitte mit Bedacht auswählen!',
	'DIGESTS_SUBSCRIBE_LITERAL'								=> 'Abonnieren',
	'DIGESTS_SUBSCRIBED'									=> 'Abonniert',
	'DIGESTS_SUBSCRIBERS'									=> 'Abonnenten',
	'DIGESTS_TIME_ZONE'										=> 'Zeitzone',
	'DIGESTS_TIME_ZONE_EXPLAIN'								=> 'Bei der Abonnement-Verwaltung und der Last-Verteilung werden Sendeuhrzeiten, bzw. Besuchs- und Versandzeitpunkte aufgeführt. Diese können dort mithilfe dieser Funktion in eine bestimmte Zeitzone übersetzt werden. Üblicherweise sollte hier die Standard-Zeitzone des Boards stehen. Es werden Integer-Werte von -12 bis 12 akzeptiert. Null bedeutet, dass die UTC verwendet wird.',
	'DIGESTS_UNSUBSCRIBE'									=> 'Abonnement beenden',
	'DIGESTS_UNSUBSCRIBE_SUBJECT'							=> 'Du erhältst ab jetzt keine eMail-Zusammenstellungen mehr',
	'DIGESTS_UNSUBSCRIBED'									=> 'Noch nie abonniert',
	'DIGESTS_USER_DIGESTS_ATTACHMENTS'						=> 'Standardwert für \'Zeige Datei-Anhänge\'',
	'DIGESTS_USER_DIGESTS_BLOCK_IMAGES'						=> 'Standardwert für \'Blockiere Grafiken\'',
	'DIGESTS_USER_DIGESTS_CHECK_ALL_FORUMS'					=> 'Sollen alle Themenbereiche standardmäßig ausgewählt werden?',
	'DIGESTS_USER_DIGESTS_FILTER_TYPE'						=> 'Standard-Vorauswahl der Themenbereiche',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS'				=> 'Standardwert für die maximal angezeigte Wortanzahl pro Beitrag',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS_EXPLAIN'		=> '-1 bedeutet dass immer der komplette Beitrag wiedergegeben wird. Null (0) bedeutet dagegen, dass gar kein Beitragsinhalt wiedergegeben wird.',
	'DIGESTS_USER_DIGESTS_MAX_POSTS'						=> 'Standardwert für die maximal in einer Zusammenstellung aufgeführten Beiträge',
	'DIGESTS_USER_DIGESTS_MAX_POSTS_EXPLAIN'				=> 'Null (0) bedeutet, dass eine unbegrenzte Anzahl von Beiträgen in den Zusammenstellungen möglich ist. Dieser Wert wird aber auch schon durch die in der allgemeinen Konfiguration angegebene \'maximale Beitragszahl pro Zusammenfassung\' limitiert.',
	'DIGESTS_USER_DIGESTS_MIN_POSTS'						=> 'Standardwert für die mindestens notwendige Wortanzahl, damit ein Beitrag mit aufgeführt wird',
	'DIGESTS_USER_DIGESTS_MIN_POSTS_EXPLAIN'				=> 'Null (0) bedeutet, dass auch die kleinsten Beiträge mit in die Zusammenfassung aufgenommen werden.',
	'DIGESTS_USER_DIGESTS_NEW_POSTS_ONLY'					=> 'Standardwert für \'Zeige nur neu veröffentlichte Beiträge\'',
	'DIGESTS_USER_DIGESTS_PM_MARK_READ'						=> 'Standardwert für \'Private Nachrichten als gelesen markieren, wenn sie in der Zusammenfassung erscheinen\'',
	'DIGESTS_USER_DIGESTS_REGISTRATION'						=> 'Standardwert, ob ein Nutzer gleich bei der Registrierung die Zusammenfassung abonniert',
	'DIGESTS_USER_DIGESTS_RESET_LASTVISIT'					=> 'Standardwert für das Zurücksetzen des letzten Besuchszeitpunktes beim Absenden der Zusammenfassung',
	'DIGESTS_USER_DIGESTS_SEND_HOUR_GMT'					=> 'Standard-Sendezeit (UTC)',
	'DIGESTS_USER_DIGESTS_SEND_ON_NO_POSTS'					=> 'Standardwert für \'Zusammenfassung auch versenden, wenn es keine neuen Beiträge gibt\'',
	'DIGESTS_USER_DIGESTS_SHOW_FOES'						=> 'Standardwert für \"Beiträge von \'ignorierten Mitgliedern\' entfernen\"',
	'DIGESTS_USER_DIGESTS_SHOW_MINE'						=> 'Standardwert für \'Entferne eigene Beiträge\'',
	'DIGESTS_USER_DIGESTS_SHOW_PMS'							=> 'Standardwert für \'Meine ungelesenen Privaten Nachrichten mit anzeigen\'',
	'DIGESTS_USER_DIGESTS_SORT_ORDER'						=> 'Standdard-Sortierreihenfolge',
	'DIGESTS_USER_DIGESTS_STYLE'							=> 'Standard-Layout der Zusammenfassungen',
	'DIGESTS_USER_DIGESTS_TYPE'								=> 'Standard-Zusammenfassungsart',
	'DIGESTS_USER_DIGESTS_TOC'								=> 'Standard-Einstellung fürs Inhaltsverszeichnis',
	'DIGESTS_USERS_PER_PAGE'								=> 'Nutzer pro Seite',
	'DIGESTS_USERS_PER_PAGE_EXPLAIN'						=> 'Hier wird eingestellt wieviele Nutzer pro Seite in der Abonnement-Verwaltung angezeigt werden sollen. Basierend auf PHP max_input_vars, sollte das auf ' . floor((ini_get('max_input_vars') - 100) / 24) . ' Nutzer pro Seite oder auf weniger beschränkt sein. Andernfalls müsste max_input_vars in der php.ini vergrößert werden um eine fehlerhafte Ausgabe zu vermeiden.',
	'DIGESTS_WEEKLY_DIGESTS_DAY'							=> 'Bitte den Wochentag auswählen, an welchem die eMail-Zusammenfassung versendet werden soll',
	'DIGESTS_WITH_SELECTED'									=> 'Abonnement für ausgewählte Nutzer ändern', 

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
