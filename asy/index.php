<?php
if(empty($_GET['format'])){$f='svg';}
//if(empty($_GET['format'])){$f='png';}
elseif($_GET['format']=='svg'||$_GET['format']=='png'||$_GET['format']=='html') {$f=$_GET['format'];}
$url = "http://asymptote.ualberta.ca:10007?f=".$f;

if(isset($_GET['code'])){

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Content-Type: text/plain",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_POSTFIELDS, $_GET['code']);

$resp = curl_exec($curl);
curl_close($curl);
if($f=='svg'){header( 'Content-type: image/svg+xml' );}
if($f=='png'){header("Content-Type: image/png");}
print($resp);
}
?>