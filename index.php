<?php
include("config.php");
$key="";
$k=explode("?",$_SERVER["REQUEST_URI"]);
if(isset($k[1])) $key=preg_replace('/[^0-9a-zA-Z]*/', '', $k[1]);
if (isset($_SERVER["PHP_AUTH_PW"])) { # oh shits, fritz!box is too stupid to GET an URL....
	$key=$_SERVER["PHP_AUTH_PW"];
}
$key=preg_replace('/[^0-9a-zA-Z]*/', '', $key);
$ip=$_SERVER['REMOTE_ADDR'];
if(strpos($ip, ":") != false) {
	$type = "AAAA";
} else {
	$type = "A";
}
if($key != ""){
	mysqli_query($db, "UPDATE records SET content='$ip', change_date=unix_timestamp(),type='$type' WHERE webkey='$key';");
	$r=mysqli_query($db, "select content from records where type='SOA' and domain_id=(select domain_id from records where webkey='$key')");
	$row=mysqli_fetch_row($r);
	$soa=explode(" ", $row[0]);
	$soa[2]++ || error_log("SOA-Probleme fuer Key '$key'");
	$soa[2] = "0"; # once set don't need to care about SOA anymore...
	$r=mysqli_query($db, "select domain_id from records where webkey='$key'");
	$domain_id=mysqli_fetch_row($r);
	mysqli_query($db, "update records set content='" . implode(" ", $soa) . "', change_date=unix_timestamp() 
		where type='SOA' and domain_id=$domain_id[0]");
	echo "all records for key '$key' have been updated. <br>";
} else {
	$a="<select name=domain>";
	$r=mysqli_query($db, "select id, name from domains where dyndns=1");
	while($row=mysqli_fetch_row($r)){
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

