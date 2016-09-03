<?php
include("config.php");
$host=strtolower(preg_replace('/[^0-9a-zA-Z\-]/', '', $_POST['host']));
$email=strtolower(preg_replace('/[^0-9a-zA-Z\-\+_@\.]/', '', $_POST['email']));
$key=webkey();
$domain=strtolower(preg_replace('/[^0-9a-zA-Z\-\.]/', '', $_POST['domain']));
$r=mysql_query("select id from domains where dyndns=1 and name='$domain'");
$row=mysql_fetch_row($r);
$domain_id=$row[0];

if (! $domain_id > 0) {
	echo "bad domain selected, try again!";
	exit;
}

if ($host == "") {
	echo "empty host, please retry!";
	exit;
}

$r=mysql_query("select count(*) from records where name='$host.$domain' union select count(*) from domains where name='$host.$domain'");
$row1=mysql_fetch_row($r);
$row2=mysql_fetch_row($r);
if ($row1[0] > 0 || $row2[0] > 0) {
	echo "already taken, try a different hostname!";
	exit;
}
if (! checkEmail($email)) {
	echo "bad email, try again!";
	exit;
}
mysql_query("insert into domains (name, type, account) values ('$host.$domain', 'MASTER', 'EXTERN')");
$r=mysql_query("select id from domains where name='$host.$domain'");
$row=mysql_fetch_row($r);
$domain_id=$row[0];
mysql_query("insert into records (domain_id, name, type, content, ttl, webkey, email) values ($domain_id, '$host.$domain', 'A', '127.0.0.1', 3600, '$key', '$email')");
mysql_query("insert into records (domain_id, name, type, content, ttl) values ($domain_id, '$host.$domain', 'NS', 'ns01.0j4.de', 3600)");
mysql_query("insert into records (domain_id, name, type, content, ttl) values ($domain_id, '$host.$domain', 'NS', 'ns02.0j4.de', 3600)");
mysql_query("insert into records (domain_id, name, type, content, ttl) values ($domain_id, '$host.$domain', 'SOA', 'ns1.$host.$domain. s-dns.geekbox.info. 1 12000 1800 604800 86400', 60)");
mysql_query("insert into domainmetadata (domain_id, kind, content) values ($domain_id, 'ALLOW-DNSUPDATE-FROM', '0.0.0.0/0')");
mysql_query("insert into domainmetadata (domain_id, kind, content) values ($domain_id, 'TSIG-ALLOW-DNSUPDATE', '$host.$domain')");
`rm /tmp/K$host.$domain*`;
$k = chop (`/usr/sbin/dnssec-keygen -a hmac-md5 -b128 -K /tmp/ -n USER $host.$domain`);
$tsig = `/bin/grep Key: /tmp/$k.private`;
`/bin/rm /tmp/$k.private`;
$tsig =substr( $tsig, 5, -1);
mysql_query("insert into tsigkeys (name, algorithm, secret) values ('$host.$domain', 'hmac-md5', '$tsig')");
if (mail($email, "$domain dynamic dns service", "Hi, \n
\n
Your update-url for $host.$domain is: ".curPageURL()."?$key\n
Alternatively pass the key as password with any user name to above URL.\n
\n
Here is the TSIG/rfc2136 data:\n
Server: ".$_SERVER['SERVER_ADDR']."\n
Port: 53\n
Key name: $host.$domain\n
Key: $tsig\n
Type: tsig/hmac-md5\n
\n
If you did not request this email, please ignore. There will be no further mailings")) {
	echo "success. check your email!";
} else {
	echo "sending of email failed, please try again later";
}

function webkey($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
function checkEmail($email) {
  if(preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $email)){
    list($username,$domain)=split('@',$email);
    if(!checkdnsrr($domain,'MX')) { // as if an A record would not be okay, too... 
      return false;
    }
    return true;
  }
  return false;
}
function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 $pageURL=substr($pageURL, 0, strpos($pageURL, "signup.php"));
 return $pageURL;
}
?>
