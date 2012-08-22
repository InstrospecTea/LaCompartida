<?
$dir = preg_replace('/\s/', '', $dir);

if($dir == "")
	$dir = ".";

if(strstr($dir,".."))
	$dir = ".";

if(substr($dir, 0, 1) == "/")
	$dir = ".";

$i = 0;
$array = directorio($dir);
echo("<table border=1 cellspacing=0 cellpadding=0><tr>");
foreach($array as $key => $value)
{
	if($i % 4 == 0)
		echo("</tr><tr>\n");


	$aux = directorio("$dir/$value");

	$cont = count($aux);

	if($cont > 3)
		$valor = "<a href=?dir=$dir/$value><font size=10>$value</font></a>";
	else
		$valor = "<img src=$dir/$value>";
	echo("<td align=center>$valor<br>$value</td>");
	$i++;
}
echo("</tr></table>");

function directorio($directory)
{
   $files = shell_exec("ls ".$directory);
   return explode("\n",$files);
}
?>
