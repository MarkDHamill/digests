<?php
/**
*
* @package phpBB Extension - Digests
* @copyright (c) 2019 Mark D. Hamill (mark@phpbbservices.com)
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
	'DIGESTS_WEEKDAY' 					=> 'Neděle,Pondělí,Úterý,Středa,Čtvrtek,Pátek,Sobota',
));

$weekdays = explode(',', $lang['DIGESTS_WEEKDAY']);

$lang = array_merge($lang, array(
	'PLURAL_RULE'											=> 8,

	'DIGESTS_ALL'											=> 'Vše',
	'DIGESTS_ALL_ALLOWED_FORUMS'							=> 'Všechna povolená fóra',
	'DIGESTS_ALL_HOURS'										=> 'Všechny hodiny',
	'DIGESTS_ALL_TYPES'										=> 'Všechny typy příspěvků',
	'DIGESTS_ALL_SUBSCRIBED'								=> array(
																1 => '%d uživatel bylo přihlášen k odběru souhrnů.',
																2 => '%d uživatelé byli hromadně přihlášeni k odběru souhrnů.',
																3 => '%d uživatelů bylo hromadně přihlášeno k odběru souhrnů.'
															),
	'DIGESTS_ALL_UNSUBSCRIBED'								=> array(
																1 => '%d uživatel byl hromadně odhlášen z odběru souhrnů.',
																2 => '%d uživatelé byli hromadně odhlášeni z odběru souhrnů.',
																3 => '%d uživatelů bylo hromadně odhlášeno z odběru souhrnů.'
															),
	'DIGESTS_APPLY_TO'										=> 'Použít na',
	'DIGESTS_AVERAGE'										=> 'Průměr',
	'DIGESTS_BALANCE_APPLY_HOURS'							=> 'Použít vyvážení na tyto hodiny',
	'DIGESTS_BALANCE_LOAD'									=> 'Vyvážení zátěže',
	'DIGESTS_BALANCE_HOURS'									=> 'Vyvážit tyto hodiny',
	'DIGESTS_BASED_ON'										=> '(Vztaženo k UTC%+d)',
	'DIGESTS_COLLAPSE'										=> 'Sbalit',
	'DIGESTS_COMMA'											=> ', ',		// Used  in salutations and to separate items in lists
	'DIGESTS_CREATE_DIRECTORY_ERROR'						=> 'Nepodařilo se vytvořit složku "%s". Může to být způsobeno nedostatečnými právy. Práva nadřazené složky by měla umožnit zapisování (777 na unixových systémech).',
	'DIGESTS_CURRENT_VERSION_INFO'							=> 'Vaše verze je <strong>%s</strong>.',
	'DIGESTS_CUSTOM_STYLESHEET_PATH'						=> 'Cesta k vlastnímu CSS stylu',
	'DIGESTS_CUSTOM_STYLESHEET_PATH_EXPLAIN'				=> 'Toto nastavení je použito jen pokud je nastavena volba "Povolit vlastní CSS styl". Pokud je nastavena, styl bude použit pro všechny souhrny ve Stylovaném formátu. Cesta by měla být relativní k složce stylů a zpravidla by se měla nacházet ve složce s tématy. Upozornění: tento styl musíte vytvořit a umístit na server. Příklad: prosilver/theme/digest_stylesheet.css. Více informací o vytváření CSS stylů např. na <a href="http://www.w3schools.com/css/">W3 Schools</a>.',
	'DIGESTS_DEFAULT'										=> 'Objednat pomocí výchozích nastavení',
	'DIGESTS_DAILY_ONLY'									=> 'Jen denní souhrny',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS'						=> 'Automaticky zapnout souhrny novým uživatelům',
	'DIGESTS_ENABLE_AUTO_SUBSCRIPTIONS_EXPLAIN'				=> 'Pokud chcete, aby noví uživatelé automaticky dostávali souhrny, zvolte "Ano". Jako výchozí nastavení se použijí výchozí nastavení (to se nastavuje v sekci "Výchozí nastavení uživatelů" níže). Tímto se ale nenastaví zasílání souhrnů stávajícím uživatelům.',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET'						=> 'Povolit vlastní CSS styl',
	'DIGESTS_ENABLE_CUSTOM_STYLESHEET_EXPLAIN'				=> 'Pokud není nastaven vlastní styl, použije se styl uživatele nastavený ve fóru (platí pro Stylovaný formát).',
	'DIGESTS_ENABLE_LOG'									=> 'Zapisovat všechny aktivity Souhrnů do administračního logu',
	'DIGESTS_ENABLE_LOG_EXPLAIN'							=> 'Pokud zvolíte "Ano", všechny akce Souhrnů budou zapsány do administračního logu (na záložce "Údržba"). To může být užitečné pro ladění a informaci o tom, co kdy rozesílač souhrnů dělal a proč. Nicméně log může značně narůst, protože každou hodinu budou zapsány nejméně dva záznamy. <em>Poznámka:<em> chyby, výjimky a varování budou do logu zapisovány vždy.',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE'					=> 'Povolit hromadné přihlášení a odhlášení souhrnů',
	'DIGESTS_ENABLE_SUBSCRIBE_UNSUBSCRIBE_EXPLAIN'			=> 'Pokud zvolíte "ano", po odeslání se provede hromadné přihlášení nebo odhlášení. Povolte s rozmyslem!',
	'DIGESTS_EXCLUDE_FORUMS'								=> 'Vždy vynechat tato fóra',
	'DIGESTS_EXCLUDE_FORUMS_EXPLAIN'						=> 'Vložte čísla (ID) fór, které se nikdy nemají objevit v souhrnech. Oddělte čísla čárkami. Je-li nastaveno 0, žádné fórum se nevynechá. Číslo (ID) fóra naleznete jako parametr v URL (adrese) stránky jako parameter "&ldquo;f=&rdquo;. Například: http://www.example.com/phpBB3/viewforum.php?f=1. Nepoužívejte čísla fór, která reprezentují kategorie. <i>Toto nastavení se nepoužije, pokud si uživatel objednává jen fóra v záložkách.</i>',
	'DIGESTS_EXPAND'										=> 'Rozbalit',
	'DIGESTS_FREQUENCY_EXPLAIN'								=> 'Týdenní souhrny jsou zasílány v den nastavený administrátorem fóra v globálním nastavení. Týdenní souhrny jsou zasílány prvního v každém měsíci. Pro určení dne v týdnu se používá univerzální světový čas (UTC, bez časového posunu).',
	'DIGESTS_FORMAT_FOOTER' 								=> 'Formát souhrnů',
	'DIGESTS_FROM_EMAIL_ADDRESS'							=> 'Emailová adresa odesílatele',
	'DIGESTS_FROM_EMAIL_ADDRESS_EXPLAIN'					=> 'Tato adresa bude použita jako odesílatel v emailu se souhrny. Pokud necháte pole prázdné, použije se email uvedený jako kontaktní adresa fóra. Buďte opatrní, pokud zvolíte email s jinou doménou, než je adresa vašeho fóra - některé emailové servery mohou souhrn vyhodnotit jako spam.',
	'DIGESTS_FROM_EMAIL_NAME'								=> 'Jméno odesílatele',
	'DIGESTS_FROM_EMAIL_NAME_EXPLAIN'						=> 'Toto jméno se objeví v poli "Od" v emailovém programu. Necháte-li pole prázdné, ohlásí se jako robot vašeho fóra.',
	'DIGESTS_HAS_UNSUBSCRIBED'								=> 'Odhlásil odběr',
	'DIGESTS_HOUR_SENT'										=> 'Hodina zaslání (vztaženo k UTC%+d)',
	'DIGESTS_IGNORE'										=> 'Vynechat z hromadné akce',
	'DIGESTS_ILLOGICAL_DATE'								=> 'Vaše datum simulace je nesmyslné, jako třeba 31. února. Prosím opravte a odešlete znovu.',
	'DIGESTS_INCLUDE_ADMINS'								=> 'Zahrnout administrátory',
	'DIGESTS_INCLUDE_ADMINS_EXPLAIN'						=> 'Tímto přihlásíte nebo odhlásíte z odběrů i administrátory navíc k normálním uživatelům.',
	'DIGESTS_INCLUDE_FORUMS'								=> 'Vždy zahrnout tato fóra',
	'DIGESTS_INCLUDE_FORUMS_EXPLAIN'						=> 'Vložte čísla (ID) fór, které se vždy zahrnou do souhrnů. Oddělte čísla čárkami. Je-li nastaveno 0, žádné fórum se nepřidá. Číslo (ID) fóra naleznete jako parametr v URL (adrese) stránky jako parameter &ldquo;f=&rdquo;. Například: http://www.example.com/phpBB3/viewforum.php?f=1. Nepoužívejte čísla fór, která reprezentují kategorie. <i>Toto nastavení se nepoužije, pokud si uživatel objednává jen fóra v záložkách.</i>',
	'DIGESTS_LAST_SENT'										=> 'Poslední zaslání souhrnu',
	'DIGESTS_LIST_USERS'									=> array(
																	1	=>	'%d uživatel',
																	2	=>	'%d uživatelé',
																	3	=>	'%d uživatelů'
															),
	'DIGESTS_LOWERCASE_DIGEST_TYPE'							=> 'Typ souhrnů s malým písmem',
	'DIGESTS_LOWERCASE_DIGEST_TYPE_EXPLAIN'					=> 'V angličtině je nadpis souhrnů něco jako &ldquo;Moje fórum - Denní Souhrn&rdquo; (&ldquo;Denní&rdquo; a &ldquo;Souhrn&rdquo; s velkými písmeny na začátku). V některých jazycích je &ldquo;Denní Souhrn&rdquo; logicky na začátku nadpisu. Nastavíte-li na Ano, typ souhrn se uvede jako &ldquo;Souhrn denní z mého fóra&rdquo;, s malým prvním písmenem v názvu fóra.',
	'DIGESTS_MAILER_NOT_RUN'								=> 'Rozeslání mailem neproběhlo, protože nebyl povolen.',
	'DIGESTS_MAILER_RAN_SUCCESSFULLY'						=> 'Rozesílání emailů proběhlo úspěšně.',
	'DIGESTS_MAILER_RAN_WITH_ERROR'							=> 'Během rozesílání emailů došlo k chybě. Jeden nebo více souhrnů bylo úspěšně vytvořeno. Administrační log nebo log chyb phpBB by měl obsahovat více informací.',
	'DIGESTS_MAILER_SPOOLED'								=> 'Všechny souhrny vytvořené pro tento den a hodinu byly uloženy do složky store/phpbbservices/digests.',
	'DIGESTS_MARK_UNMARK_ROW'								=> 'Označit nebo odznačit řádku',
	'DIGESTS_MARK_ALL'										=> 'Označit nebo odznačit všechny řádky',
	'DIGESTS_MAX_CRON_HOURS'								=> 'Maximum hodin, které budou zpracovány rozesílačem',
	'DIGESTS_MAX_CRON_HOURS_EXPLAIN'						=> 'Nastavte na 0 (nulu), aby se zpracovaly všechny zprávy pro všeechny hodiny ve frontě. Ovšem pokud máte <strong>sdílený hosting</strong>, běh rozesílače může překročit systémový limit zdrojů a vyvolat chybu. To se může stát, pokud máte hodně adresátů a malý provoz na fóru. Nastavení <a href="https://wiki.phpbb.com/PhpBB3.1/RFC/Modular_cron#Use_system_cron">systemového cronu</a> je nejjednodušší způsob jak tento problém minimalizovat a taky zajistí, že budou souhrny chodit včas.',
	'DIGESTS_MAX_ITEMS'										=> 'Maximální počet příspěvků v jakémkoliv souhrnu',
	'DIGESTS_MAX_ITEMS_EXPLAIN'								=> 'Z důvodu zatížení serveru můžete chtít nastavit maximální počet příspěvků pro jakýkoliv souhrn. Nastavíte-li 0 (nulu), nebude se počet nijak omezovat. Můžete použít jakékoliv celé číslo. Příspěvky jsou dále omezeny frekvencí souhrnu (denně, týdně nebo měsíčně) a dalšími kritérii, která si uživatel nastaví.',
	'DIGESTS_MAIL_FREQUENCY' 								=> 'Frekvence zasílání',
	'DIGESTS_MIGRATE_UNSUPPORTED_VERSION'					=> 'Aktualizace souhrnů na novou verzi (pro phpBB 3.0) je podporováno pro verzi 2.2.6 a novější. Máte verzi %s. Rozšížení nemůže být aktualizováno ani instalováno. Pomoc naleznete na fóru podpory phpbb.com.',
	'DIGESTS_MIN_POPULARITY_SIZE'							=> 'Minimální oblíbenost tématu',
	'DIGESTS_MIN_POPULARITY_SIZE_EXPLAIN'					=> 'Toto nastavuje minimální počet příspěvků denně potřebných proto, aby se téma považovalo za oblíbené. Uživatelé si nemohou nastavit hodnotu nižší než je zde uvedená hodnota. Hodnota je aplikována pouze pro odběratele s frekvencí den, týden nebo měsíc, aby odrážela oblíbenost v posledním období.',
	'DIGESTS_MONTHLY_ONLY'									=> 'Jen měsíční souhrn',
	'DIGESTS_NEVER_VISITED'									=> 'Zatím nenavštíven',
	'DIGESTS_NO_DIGESTS_SENT'								=> 'Souhrn nezaslán',
	'DIGESTS_NO_MASS_ACTION'								=> 'Žádná akce se neprovedla, protože hromadná funkce není povolena',
	'DIGESTS_NOTIFY_ON_ADMIN_CHANGES'						=> 'Upozorni uživatele emailem při změnách nastavení souhrnů administrátorem',
	'DIGESTS_NOTIFY_ON_ADMIN_CHANGES_EXPLAIN'				=> 'Změnou adresátů, vyvažování zátěže, hromadné přihlášení nebo odhlášení se mohou změnit nastavení souhrnů uživatelům. Pokud je povoleno, uživatelé dostanou zprávu emailem kdykoliv se změní jakýkoliv aspekt jejich nastavení administrátorem.',
	'DIGESTS_NUMBER_OF_SUBSCRIBERS'							=> 'Počet odběratelů',
	'DIGESTS_PMS_MARK_READ'									=> 'Označ moje soukromé zprávy jako přečtené, pokud budou odeslány v souhrnu',
	'DIGESTS_RANDOM_HOUR'									=> 'Náhodná hodina',
	'DIGESTS_REBALANCED'									=> array(
																	1 => 'Během rozdělování zátěže byl %d odběrateli změněn čas souhrnů.',
																	2 => 'Během rozdělování zátěže byl %d odběratelům změněn čas souhrnů.',
																	3 => 'Během rozdělování zátěže byl %d odběratelům změněn čas souhrnů.'
															),
	'DIGESTS_REGISTRATION_FIELD'							=> 'Povolit uživatelům objednání souhrnů při registraci',
	'DIGESTS_REGISTRATION_FIELD_EXPLAIN'					=> 'Pokud je povoleno, dostanou noví uživatelé při registraci do fóra možnost objednat si souhrny s výchozím nastavením! Tato možnost se neobjeví, pokud je zvoleno automatické objednání souhrnů pro nové uživatele.',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS'						=> 'Email na odpověď',
	'DIGESTS_REPLY_TO_EMAIL_ADDRESS_EXPLAIN'				=> 'Když uživatelé obdrží souhrn, tato adresa se objeví v poli "Odpovědět" (Reply-to). Pokud necháte pole prázdné, použije se email uvedený jako kontaktní adresa fóra. Buďe opatrní, pokud zvolíte email s jinou doménou, než je adresa vašeho fóra - některé emailové servry mohou souhrn vyhodnotit jako spam.',
	'DIGESTS_RESET_CRON_RUN_TIME'							=> 'Vymazat frontu rozesílání',
	'DIGESTS_RESET_CRON_RUN_TIME_EXPLAIN'					=> 'Po vymazání se vyprázdní fronta všech připravených neodeslaných souhrnů a další souhrny se vytvoří pouze pro aktuální hodinu. Vymazání může být užitečné, když se ve frontě nahromadí velké množství souhrnů, které nebyly rozeslány, protože se nespustil systémový cron, např. po testování.',
	'DIGESTS_RUN_TEST'										=> 'Spustit rozesílač emailů',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL'							=> 'Smazat vyrovnávací paměť ve složce store/phpbbservices/digests',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_ERROR'					=> 'Nebylo možné smazat všechny soubory ve složce store/phpbbservices/digests. Důvodem by mohla být práva k souborům nebo byla smazána rodičovská složka. Práva by měla umožnit zápis do složky pro kohokoliv (777 na unixových systémech).',
	'DIGESTS_RUN_TEST_CLEAR_SPOOL_EXPLAIN'					=> 'Je-li povoleno, všechny soubory v store/phpbbservices/digests budou smazány, než se do nich budou vytvářet nové souhrny.',
	'DIGESTS_RUN_TEST_DAY'									=> 'Den simulace',
	'DIGESTS_RUN_TEST_DAY_EXPLAIN'							=> 'Zadejte celé číslo od 1 do 31. Je-li rok, měsíc a den v buducnosti, nebudou samozřejmě vygenerovány žádné souhrny. Nepoužívejte den, který logicky neexistuje, jako třeba 31. února.',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS'						=> 'Testovací emailová adresa',
	'DIGESTS_RUN_TEST_EMAIL_ADDRESS_EXPLAIN'				=> 'Je-li v tomto poli uvedena emailová adresa, všechny souhrny pro vybranou hodinu budou zaslány na tuto emailovou adresu místo na uživatelské emailové adresy. <em>Poznámka</em>: pokud vytváříte emaily do souborů, toto nastavení bude ignorováno.',
	'DIGESTS_RUN_TEST_HOUR'									=> 'Hodina simulace',
	'DIGESTS_RUN_TEST_HOUR_EXPLAIN'							=> 'Souhrny budou poslány k dané hodině, použije se časová zóna fóra. Pokud je čas v budoucnosti, nevytvoří se žádné souhrny. Zadejte celé číslo od 0 do 23.',
	'DIGESTS_RUN_TEST_MONTH'								=> 'Měsíc simulace',
	'DIGESTS_RUN_TEST_MONTH_EXPLAIN'						=> 'Zadejte celé číslo od 1 do 12. Obyčejně to bude aktuální měsíc. Bude-li rok a měsíc v budoucnosti, žádné souhrny se nevytvoří.',
	'DIGESTS_RUN_TEST_OPTIONS'								=> 'Datum a čas simulace',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN'						=> 'Zaslat všechny souhrny na testovací adresu',
	'DIGESTS_RUN_TEST_SEND_TO_ADMIN_EXPLAIN'				=> 'Pokud chcete v rámci testování zaslat souhrny na určitou adresu, budou zaslány na níže uvedený email. <em>Poznámka</em>: pokud vytváříte souhrny do souborů, toto nastavení se nepoužije. Není-li email zadán, použije se kontaktní email fóra. <em>Pozor</em>: některé emailové servery mohou vyhodnotit velké množství emailů během krátké doby z té samé adresy jako spam (nevyžádanou poštu). Používejte opatrně. Zadáte-li "Ne", budou testovací souhrny zaslány uživatelům, což by je mohlo zmást.',
	'DIGESTS_RUN_TEST_SPOOL'								=> 'Zaslat výsledky do souborů místo odesílání emailem',
	'DIGESTS_RUN_TEST_SPOOL_EXPLAIN'						=> 'Zabrání rozesílání souhrnů emailem. Místo toho bude každý souhrn zapsán do souboru ve složce store/phpbbservices/digests. Jméno souboru bude mít formát: uživatel-rok-měsíc-den-hodina.html nebo uživatel-rok-měsíc-den-hodina.txt. (Soubory s příponou .txt jsou souhrny v textovém formátu.) Dny a hodiny ve jméně souboru jsou vztaženy k UTC (Coordinated Universal Time - s nulovým časovým posunem). Pokud použijete simulaci pro jiný den a hodinu rozesílání, soubory se vytvoří s tímto datem a hodinou. Tyto souhrny je možné stáhnout pomocí FTP a prohlížet na lokálním počítači. Souhrny ve formátu HTML otevřete v prohlížeči.',
	'DIGESTS_RUN_TEST_TIME_USE'								=> 'Simulovat tvorbu souhrnů k určitému měsíci a hodině nebo dni v týdnu a hodině',
	'DIGESTS_RUN_TEST_TIME_USE_EXPLAIN'						=> 'Je-li zvoleno "Ano", nastavení níže budou použita k simulování rozesílání souhrnů jako by byl daný měsíc a hodina nebo den v týdnu a hodina (vztaženo k časové zóně serveru). Vaši čsovou zónu nastavenou na serveru můžete vidět v Administraci phpBB, záložka "Systém", menu "Informace o PHP" a hledejte "Default timezone". Pokud zvolíte "Ne", použije se aktuální den a hodina.',
	'DIGESTS_RUN_TEST_YEAR'									=> 'Simulovat rok',
	'DIGESTS_RUN_TEST_YEAR_EXPLAIN'							=> 'Jsou povoleny roky 2000 až 2030. Obvykle zřejmě použijete aktuální rok. Je-li rok v budoucnosti, žádné souhrny se pochopitelně nevytvoří.',
	'DIGESTS_SALUTATION_FIELDS' => 'Vyberte pole pro pozdrav',
	'DIGESTS_SALUTATION_FIELDS_EXPLAIN' => 'Vložte vlastní pole z uživatelských profilů, která se použijí místo uživatelského jména v pozdravu emailu souhrnu. Pokud nezvolíte žádné pole, použije se uživatelské jméno. Zvolte vlastní pole pomocí označení pole. Více polí oddělte čárkou. <em>Poznámka:</em> Pole musí být typu “Jedno textové pole”. Pokud žádné pole neexistuje anebo pro ně uživatel nevyplnil žádnou hodnotu, použije se uživatelské jméno. Například: "krestni,prijmeni" (pokud jste taková pole vytvořili). V pozdravu se hodnoty polí (je-li jich více) oddělí mezerou.',
	'DIGESTS_SEARCH_FOR_MEMBER'								=> 'Hledat uživatele',
	'DIGESTS_SEARCH_FOR_MEMBER_EXPLAIN'						=> 'Vložte celé nebo částečné jméno nebo emailovou adresu, kterou hledáte, a stiskněte Enter. Chcete-li vidět všechny uživatele, nechte pole prázdné. Vyhledávání nerozlišuje velká a malá písmena. <em>Poznámka</em>: Aby se vyhledávalo podle emailu, je třeba v hledání použít znak "@".',
	'DIGESTS_SELECT_FORUMS_ADMIN_EXPLAIN'					=> 'Seznam fór zahrnuje jen ta fóra, kde má uživatel práva ke čtení. Chcete-li uživateli přidělit práva na fóra, která zde nejsou zobrazena, rozšiřte jejich oprávnění nebo oprávnění jeho skupiny. Ačkoliv zde můžete detailně nastavit, která fóra se uživateli zašlou, nezašle se nic, pokud je formát souhrnů nastaven na &ldquo;Žádný&rdquo;.',
	'DIGESTS_SHOW'											=> 'Zobrazit',
	'DIGESTS_SHOW_EMAIL'									=> 'Zobrazit emailovou adresu v logu',
	'DIGESTS_SHOW_EMAIL_EXPLAIN'							=> 'Pokud povolíte, zobrazí se emailová adresa uživatele v záznamech administrátorského protokolu. To se může hodit při řešení potíží při nastavování či odesílání souhrnů.',
	'DIGESTS_SHOW_FORUM_PATH'								=> 'Zobrazit fórum i s cestou',
	'DIGESTS_SHOW_FORUM_PATH_EXPLAIN'						=> 'Je-li povoleno, zobrazí se v souhrnu fórum i s celou hierarchií nadřazených kategorií a fór, například: &ldquo;Kategorie 1 :: Fórum 1 :: Kategorie A :: Fórum B&rdquo;. V opačném případně bude zobrazen pouze název fóra, v tomto případě &ldquo;Forum B&rdquo;.',
	'DIGESTS_SORT_ORDER'									=> 'Třídění',
	'DIGESTS_STOPPED_SUBSCRIBING'							=> 'Přestal odebírat',
	'DIGESTS_STRIP_TAGS'									=> 'Zakázané HTML značky',
	'DIGESTS_STRIP_TAGS_EXPLAIN'							=> 'Některé mailove servery mohou emaily odmítnout doručit nebo je vyhodnotí jako spam. Uveďte značky (bez znaků &lt; a &gt;), které se mají vynechat, a oddělte je čárkami. Například &ldquo;video,iframe&rdquo;. Nezakazujte značky jako h1, p nebo div, které jsou potřebné pro rozumné zobrazení souhrnů.',
	'DIGESTS_SUBSCRIBE_EDITED'								=> 'Vaše nastavení souhrnů byla změněna',
	'DIGESTS_SUBSCRIBE_SUBJECT'								=> 'Byl(a) jste přihlášen(a) k zasílání souhrnu příspěvků.',
	'DIGESTS_SUBSCRIBE_ALL'									=> 'Přihlásit všechny',
	'DIGESTS_SUBSCRIBE_ALL_EXPLAIN'							=> 'Zvolíte-li "Ne", všichni budou odhlášeni z odběrů.',
	'DIGESTS_SUBSCRIBE_LITERAL'								=> 'Přihlásit k odběru',
	'DIGESTS_SUBSCRIBED'									=> 'Přihlášen k odběru',
	'DIGESTS_SUBSCRIBERS_DAILY'								=> 'Denní odběratelé',
	'DIGESTS_SUBSCRIBERS_WEEKLY'							=> 'Týdenní odběratelé',
	'DIGESTS_SUBSCRIBERS_MONTHLY'							=> 'Měsíční odběratelé',
	'DIGESTS_UNSUBSCRIBE'									=> 'Odhlásit',
	'DIGESTS_UNSUBSCRIBE_SUBJECT'							=> 'Byl jste odhlášen z odebírání souhrnů příspěvků emailem.',
	'DIGESTS_UNSUBSCRIBED'									=> 'Není přihlášen k odběru',
	'DIGESTS_USER_DIGESTS_CHECK_ALL_FORUMS'					=> 'Chcete jako výchozí nastavení zahrnout všechna fóra',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS'				=> 'Maximální počet slov zobrazených v příspěvku',
	'DIGESTS_USER_DIGESTS_MAX_DISPLAY_WORDS_EXPLAIN'		=> 'Chcete-li zobrazit příspěvky celé, zadejte -1. Zadáte-li nulu (0), nezobrazí se text příspěvku vůbec.',
	'DIGESTS_USER_DIGESTS_PM_MARK_READ'						=> 'Označit soukromou zprávu jako přečtenou, pokud je zaslána v souhrnu',
	'DIGESTS_USERS_PER_PAGE'								=> 'Uživatelů na stránce',
	'DIGESTS_USERS_PER_PAGE_EXPLAIN'						=> 'Tímto nastavením se uvádí, kolik uživatelů uvidí administrátor na jedné stránce v přehledu objednaných souhrnů. Je doporučeno nechat počet na 20. Příliš vysoké číslo může způsobit překročení PHP nastavení max_input_vars a povede k chybě při odesílání stránky.',
	'DIGESTS_WEEKLY_DIGESTS_DAY'							=> 'Vyberte den v týdnu pro zasílání týdenních přehledů',
	'DIGESTS_WEEKLY_DIGESTS_DAY_EXPLAIN'					=> 'Den v týdnu je vztažen k GMT (UTC, nulový časový posun). V závislosti na zvolené hodině, odběratelé na západní polokouli mohou dostat týdenní souhrn již předchozí den.',
	'DIGESTS_WEEKLY_ONLY'									=> 'Jen týdenní souhrny',
	'DIGESTS_WITH_SELECTED'									=> 'S vybranými',

));
