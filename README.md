# Digests
Digests extension for phpBB 3.2

Please note that when deployed the extension will go under ext/phpbbservices/digests. Only the digests tree is shown here.

If you are upgrading from the digests modification for phpBB 3.0, please remove the cron job that you created. 

Also, due to bugs in the migration software in phpBB 3.2.2, before installing the extension you should remove old digest modules or the extension may fail to install. Note that there are both ACP and UCP digest modules. These can be removed via ACP > System > Module management. For ACP modules, find them under Administration Control Panel > ACP_DIGEST_SETTINGS. Delete all these modules including the ACP_DIGEST_SETTINGS category itself working from the bottom up. For UCP modules, find them under User Control Panel > UCP_DIGESTS. Delete all these modules including the UCP_DIGESTS category itself working from the bottom up.

If AutoMOD was previously installed, the AutoMOD modules are likely still in the database too. These should be removed via ACP > System > Module management > Administration Control Panel. Select ACP_CAT_MODS and delete all modules and categories including the ACP_CAT_MODS category itself working from the bottom up.

Subscriber settings in the database from the digests modification should be successfully retained during installation.

Digests no longer requires that a cron job be run hourly. It now uses phpBB's built in cron.php program. If traffic is light on your forum, digests may be delivered hours or even days later than the scheduled time. However the digest will always contain posts for the date and time requested.

After installation, there are recommended steps for testing digests. Creating a system cron job is also advised. Please see the FAQ at https://www.phpbb.com/customise/db/extension/digests_extension/faq.

The following translations exist but due to packaging requirements for extensions cannot be included in the extension itself. Please check to make sure the translation is for the release you download. Translations are placed in /ext/phpbbservices/digests/language. The proper translation may be on a branch. And thanks to the authors for taking the time to provide these translations:

Czech: https://github.com/petr-hendl/phpBBDigests-cs/
French: https://github.com/bonnaphil/digests-fr
German: https://github.com/Praggle/digests
Spanish: https://mega.nz/#F!mGQzAQba!tSEPx3HrO8tHPiZKywcWqQ