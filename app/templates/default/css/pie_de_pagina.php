<?
require_once dirname(__FILE__).'/../../../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Sesion.php';

$sesion = new Sesion();
?>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">

<head>
<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
<meta name=ProgId content=Word.Document>
<meta name=Generator content="Microsoft Word 11">
<meta name=Originator content="Microsoft Word 11">
</head>

<body lang=ES>

<div style='mso-element:footnote-separator' id=fs>

<p class=MsoNormal><span style='mso-special-character:footnote-separator'><![if !supportFootnotes]>

<hr align=left size=1 width="33%">

<![endif]></span></p>

</div>

<div style='mso-element:footnote-continuation-separator' id=fcs>

<p class=MsoNormal><span style='mso-special-character:footnote-continuation-separator'><![if !supportFootnotes]>

<hr align=left size=1>

<![endif]></span></p>

</div>

<div style='mso-element:header' id=eh1>

<p class=MsoHeader><o:p>&nbsp;</o:p></p>

</div>

<div style='mso-element:header' id=h1>
<? 
// Busca html de header y pie de pagina 
if( $id_formato != '' )
	$where = " WHERE id_formato = '$id_formato' ";
else
	$where = " WHERE 1=2";
$query = "SELECT html_header, html_pie FROM cobro_rtf $where"; 
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh); 
list($html_header, $html_pie) = mysql_fetch_array($resp); 

// reemplacar anchores 
$html_header = str_replace('%img_dir%', Conf::ImgDir(), $html_header); 
?> 

<p class=MsoHeader align=center style='text-align:center'> 
	<? echo $html_header; ?> 
</p> 

</div> 

<div style='mso-element:endnote-separator' id=es> 

<p class=MsoNormal><span style='mso-special-character:footnote-separator'><![if !supportFootnotes]>

<hr align=left size=1 width="33%">

<![endif]></span></p>

</div>

<div style='mso-element:endnote-continuation-separator' id=ecs>

<p class=MsoNormal><span style='mso-special-character:footnote-continuation-separator'><![if !supportFootnotes]>

<hr align=left size=1>

<![endif]></span></p>

</div>

<div style='mso-element:footer' id=f1>

<?
$html_pie = str_replace('%img_dir%', Conf::ImgDir(), $html_pie);
?>
<div class=MsoFooter align=center style='text-align:center'>
	<span style='mso-no-proof:yes;'>
		<? echo $html_pie; ?>
	</span>
</div>

</div>

</body>

</html>
<?
?>
