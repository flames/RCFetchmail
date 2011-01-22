#!/usr/bin/perl
######
 #
 #	MySQL to Fetchmail, Frontend: Roundcube Plugin (RC0.4 and above)
 #	Developped by Arthur Mayer, a.mayer@citex.net
 #	Released under GPL license (http://www.gnu.org/licenses/gpl.txt)
 #
######

use DBI;

$db_database="roundcubemail";
$db_username="roundcube";
$db_password="dbpass";

$text='#temp fetchmailrc
set no syslog
set postmaster "postmaster"
set no bouncemail
set properties ""
';

$dbh = DBI->connect("DBI:mysql:$db_database", $db_username, $db_password) or die "Database connection error: $DBI::errstr\n";

$sth = $dbh->prepare("SELECT mailget_id,userhere,remoteserver,remoteuser,remotepass,type,options,active FROM virtual_fetchmail WHERE active='1' ORDER BY remoteserver ASC"); 
$sth->execute;
while(my ($mailget_id,$userhere,$remoteserver,$remoteuser,$remotepass,$type,$options,$active) = $sth->fetchrow_array()) {
	$text.="\npoll $remoteserver with proto $type\n";
	if ( $options eq '0' ) { $keep='keep'; } else { $keep=''; }
	$text.="\tuser \"$remoteuser\" there with password \"$remotepass\" is \"$userhere\" here $keep\n";
}

$dbh->disconnect();

#print $text;

open(DA, ">/var/mail/fetchmail/fetchmailrc") or die "Can't Open File. Try chmod 777";
print DA $text;
close(DA);

$chown=`chown -R vmail:mail /var/mail/fetchmail/fetchmailrc`;
$chmod=`chmod -R 600 /var/mail/fetchmail/fetchmailrc`;
$ret=`/usr/bin/fetchmail -f /var/mail/fetchmail/fetchmailrc -i /var/mail/fetchmail/fetchmailid --pidfile /var/mail/fetchmail/fetchmail.pid -L /var/log/fetchmail`;

unlink "/var/mail/fetchmail/fetchmailrc";
