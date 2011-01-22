Ein Roundcube-Plugin um externe Mailkonten (GMX und Co.) abrufen zu können, per User bzw. per Mailbox natürlich. Ganz ISP-Like.
Installationsanleitung:

fetchmail installieren:

apt-get install fetchmail

fetchmail.pl Script nach /var/mail/ kopieren:

cp fetchmail.pl /var/mail/fetchmail.pl

in /var/mail/ Ordner "fetchmail" erstellen:

mkdir /var/mail/fetchmail

den Ordner und das Script müssen vmail:mail gehören, das Script muss ausführbar sein:

chown -R vmail:mail /var/mail/fetchmail*
chmod -R 700 /var/mail/fetchmail*

in der Roundcube-Datenbank muss eine weitere Tabelle erstellt werden:

CREATE TABLE `virtual_fetchmail` (
`mailget_id` int(11) NOT NULL auto_increment,
`userhere` varchar(50) collate utf8_unicode_ci NOT NULL,
`active` varchar(1) collate utf8_unicode_ci NOT NULL default '1',
`options` varchar(50) collate utf8_unicode_ci NOT NULL,
`type` varchar(50) collate utf8_unicode_ci NOT NULL default 'POP3',
`remoteserver` varchar(50) collate utf8_unicode_ci NOT NULL,
`remoteuser` varchar(50) collate utf8_unicode_ci NOT NULL,
`remotepass` varchar(50) collate utf8_unicode_ci NOT NULL,
PRIMARY KEY (`mailget_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=144 ;

in meinem fall ist Roundcube in /var/www/ispcp/gui/tools/roundcube installiert, u.u. Pfad anpassen.
Plugin installieren wir so:

cp -R ispcp_fetchmail /var/www/ispcp/gui/tools/roundcube/plugins/
vi /var/www/ispcp/gui/tools/roundcube/config/main.inc.php

um Zeile 240 findet ihr etwas in dieser Art:

$rcmail_config['plugins'] = array('ispcp_pw_changer', 'managesieve', 'sieverules');

das neue Fetchmail-Plugin aktivieren wir, in dem wir es folgender massen in diese Zeile eintragen:

$rcmail_config['plugins'] = array('ispcp_pw_changer', 'managesieve', 'sieverules', 'ispcp_fetchmail');

und die Anzahl der erlaubten Konten die abgerufen werden sollen (pro lokale Mailbox) anpassen:
vi /var/www/ispcp/gui/tools/roundcube/plugins/ispcp_fetchmail/config/config.inc.php

$rcmail_config['fetchmail_limit'] = 3;

das Perl script benötigt Zugangsdaten zu der MySQL Datenbank

vi /var/mail/fetchmail.pl
$db_database="roundcube";
$db_username="dbuser";
$db_password="dbpass";

natürlich wollen wir, dass Fetchmail regelmäßig die mails abholt (z.B. alle 5 Minuten):

crontab -e
*/5 * * * * sudo -u vmail /var/mail/fetchmail.pl > /dev/null 2&>1

dann noch die Log-Ausgabedatei erstellen und rechte geben:

touch /var/log/fetchmail
chown vmail:mail /var/log/fetchmail
chmod 600 /var/log/fetchmail

zur Funktionsweise: das Roundcube-Plugin speichert die Konten die abgerufen werden sollen in die Datenbank, das Perl-Script wird regelmäßig per Cron aufgerufen, ließt die Datenbank aus und erstellt eine temporäre fetchmailrc, ruft fetchmail damit auf und die User kriegen ihre mails von GMX und Co. in Ihr schönes IMAP-Postfach 
Bei jedem Aufruf werden immer die aktuellen Einträge aus der Datenbank geholt und die alte fetchmailrc überschrieben bzw. nach jedem Durchlauf wird die fetchmailrc gelöscht.

- Die UI des Plugins basiert auf dem ISPConfig3-Fetchmail-Plugin von Horst Fickel.
- Seid willkommen das Plugin nach Lust und Laune zu verbessern, dann aber nicht vergessen hochzuladen.
- Verbesserungsvorschlag 1: Die Sql-Daten in der Plugin Konfiguration raus, indem die Sql-Querys über die Roundcube-Plugin-API umgesetzt werden.
- Verbesserungsvorschlag 2: Leider werden die Passwörter in Plaintext in der Datenbank gespeichert. Vielleicht das Plugin die Passwörter verschlüsselt speichern lassen und im Perl-Script wieder decodieren. Die fetchmailrc muss die passwörter ja in Plaintext enthalten, oder?

changelog:
- es werden keine mysql zugangsdaten mehr benötigt, das plugin arbeitet nun 100% über die roundcube api und holt sich da was es braucht (das perl script benötigt jedoch weiterhin mysql daten)
- einige gui+usability verbesserungen
- diverse bugfixes
- russische übersetzung hinzugefügt
