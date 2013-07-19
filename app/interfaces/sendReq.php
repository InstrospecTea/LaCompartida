<?php
require_once('../classes/Reportes/spell-check-library.php');

$content = "";
$options = array(
	"lang"						=> $_REQUEST['lang'],
	"maxSuggestions"	=> 5,
	"customDict"			=> 0,
	"charset"					=> 'utf-8'
);
$factory = new SpellChecker($options);
$words = strip_tags(file_get_contents('php://input'));
$spell = $factory->create($words);

header('Content-Type: text/xml');
echo $spell->toXML();
exit;


// Thanks to:
//   Marcin 'nosferathoo' Puchalski
//   Johan Sijbesma
//   James Edgington
//   Stephen Wald
	$google = "www.google.com";
	$lang=$_GET['lang'];
	$path="/tbproxy/spell?lang=$lang";
	$data = file_get_contents('php://input');
	$store = "";
	$fp = fsockopen("ssl://".$google, 443, $errno, $errstr, 30);
	if ($fp)
	{
	 $out = "POST $path HTTP/1.1\r\n";
	 $out .= "Host: $google\r\n";
	 $out .= "Content-Length: " . strlen($data) . "\r\n";
	 $out .= "Content-type: application/x-www-form-urlencoded\r\n";
	 $out .= "Connection: Close\r\n\r\n";
	 $out .= $data;
	 fwrite($fp, $out);
	 while (!feof($fp)) {
			 $store .= fgets($fp, 128);
	 }
	 fclose($fp);
	}
	print utf8_decode($store);
?>
