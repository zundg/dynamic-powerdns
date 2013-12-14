<?php
include("config.php");
$host=strtolower(preg_replace('/[^0-9a-zA-Z\-]/', '', $_POST['host']));
$email=strtolower(preg_replace('/[^0-9a-zA-Z\-\+_@\.]/', '', $_POST['email']));
$key=webkey();
if ($host == "") {
	echo "empty host, please retry!";
	exit;
}
$r=mysql_query("select count(*) from records where name='$host.0j4.de'");
$row=mysql_fetch_row($r);
if ($row[0] > 0) {
	echo "already taken, try a different hostname!";
	exit;
}
if (! checkEmail($email)) {
	echo "bad email, try again!";
	exit;
}
$domain=strtolower(preg_replace('/[^0-9a-zA-Z\-\.]/', '', $_POST['domain']));
$r=mysql_query("select count(*) from domains where dyndns=1 and name='$domain'");
$row=mysql_fetch_row($r);
if ($row[0] < 1) {
	echo "bad domain selected, try again!";
	exit;
}
mysql_query("insert into records (domain_id, name, type, content, ttl, webkey, email) values (1, '$host.$domain', 'A', '127.0.0.1', 60, '$key', '$email')");
if (mail($email, "$domain dynamic dns service", "Hi, \n\nYour update-url for $host.$domain is: ".curPageURL()."?$key\n\nIf you did not request this email, please ignore. There will be no further mailings")) {
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
