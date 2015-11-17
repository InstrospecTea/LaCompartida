<?php 
	require_once dirname(__FILE__).'/../../app/conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';

	$sesion = new Sesion();
	$pagina = new Pagina($sesion);

	$pagina->titulo = "Preguntas frecuentes";
	$pagina->PrintTop();

	
	$query = "SELECT pregunta,respuesta FROM faq";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	for($i = 1; list($pregunta,$respuesta) = mysql_fetch_array($resp); $i++)
	{
		$pregs .= "<tr><td>$i. <a href=\"#pr$i\">$pregunta</a></td></tr>";
		$resps .= "<tr><td><a name=pr$i>$i</a>. <font color=blue>$pregunta</font></td></tr>";
		$resps .= "<tr><td>$respuesta</td></tr>";
		$resps .= "<tr><td><hr color=\"#000000\" size=1></td></tr>";
	}
	echo("<table>");
	echo ($pregs);
	echo ("<tr><td height=15>&nbsp;</td></tr>");	
	echo ($resps);
	echo("</table>");

	$pagina->PrintBottom();
