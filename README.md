# Digests
Digests extension for phpBB 3.1
Please note that when deployed the extension will go under ext/phpbbservices/digests. Only the digests tree is shown here.

If you are upgrading from the digests mod for phpBB 3.0, please remove the cron job that you created. 

Digests no longer requires that a cron job be run hourly. It now uses phpBB's built in cron.php program. If traffic is light on your forum, digests may be delivered hours or even days later than the scheduled time. However the digest will always contain posts for the date and time requested.
