<?php
include("config.php");

$k=explode("?",$_SERVER["REQUEST_URI"]);
$key=preg_replace('/[^0-9a-zA-Z]*/', '', $k[1]);
if ($key == "") { # oh shits, fritz!box is too stupid to GET an URL....
	$key=$_SERVER["PHP_AUTH_PW"];
}
$ip=$_SERVER['REMOTE_ADDR'];
$key=preg_replace('/[^0-9a-zA-Z]*/', '', $key);
if(strpos($ip, ":") != false) {
	echo "sorry, IPv6 not supported!";
	exit;
}
if($key != ""){
	mysql_query("UPDATE records SET content='$ip', change_date=unix_timestamp() WHERE webkey='$key';");
	$r=mysql_query("select content from records where type='SOA' and domain_id=(select domain_id from records where webkey='$key')");
	$row=mysql_fetch_row($r);
	$soa=explode(" ", $row[0]);
	$soa[2]++;
	$r=mysql_query("select domain_id from records where webkey='$key'");
	$domain_id=mysql_fetch_row($r);
	mysql_query("update records set content='" . implode(" ", $soa) . "', change_date=unix_timestamp() 
		where type='SOA' and domain_id=$domain_id[0]");
	echo "all records for key '$key' have been updated<br>";
} else {
	$a="<select name=domain>";
	$r=mysql_query("select id, name from domains where dyndns=1");
	while($row=mysql_fetch_row($r)){
		$a.="<option>$row[1]</option>";
	}
	$a.="</select>";
?>
<form method=post action=signup.php>
<table cellspacing=0><tr><th align=right>host</th><td><input name=host size=20>.<?php echo $a; ?></td></tr>
<tr><th align=right>email</th><td><input name=email size=20 maxlength="60"> (for update url, tsig key and stuffs)</td></tr>
<tr><td colspan=2><input size=100% type=submit></td></tr></table></form>
<br><br>
<?php
}
?>

