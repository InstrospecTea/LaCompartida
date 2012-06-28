<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../app/classes/PaginaCobro.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Asunto.php';
	require_once Conf::ServerDir().'/../app/classes/CobroAsunto.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Gasto.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Moneda.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

set_time_limit(1000);
function DOMinnerHTML($element)
{
$innerHTML = "";
$children = $element->childNodes;
foreach ($children as $child)
{
$tmp_dom = new DOMDocument();
$tmp_dom->appendChild($tmp_dom->importNode($child, true));
$innerHTML.=trim($tmp_dom->saveHTML());
}
return $innerHTML;
}


	$sesion = new Sesion(array());
	$pagina = new Pagina($sesion);

	$pagina->PrintTop();
?>
<form name="form1" action="<?php echo $_server['php_self'];?>" method="post">
<table width="100%">
	<tr>
		<td colspan="6" align="center">
			Id Cobro: <input type="text" name="id_cobro" value="<?php echo $id_cobro; ?>" />&nbsp;<input type="submit" name="enviar" />
		</td>
	</tr>
	<tr>
		<td align="center">
			<?=Html::SelectQuery($sesion,"SELECT id_formato, descripcion AS nombre FROM cobro_rtf", "formatos[]", $formatos,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
		</td>
	</tr>
	<tr>
		<td>
			&nbsp;
		</td>
	</tr>
</table>
</form>
	<?
	if($id_cobro)
	{
		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);
		$cobro->LoadAsuntos();
		
		if( $formatos )
			{
				$lista_formatos = implode(', ',$formatos);
				$where = " WHERE id_formato IN ( ".$lista_formatos." ) ";
			}
		else
			$where = "";
		
		$query = " SELECT id_formato, descripcion FROM cobro_rtf $where";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		
		while( list($id_formato,$descripcion) = mysql_fetch_array($resp) )
		{
			echo '<br><br><h2>'.$descripcion.'</h2><br><br>';
			$html = $cobro->GeneraHTMLCobro(false,$id_formato,1);
			$html .= $cobro->GeneraHTMLCobro(false,$id_formato,2);
			$cssData = UtilesApp::TemplateCartaCSS($sesion,$cobro->fields['id_carta']);
			$cssData .= UtilesApp::CSSCobro($sesion);
			$doc = new DocGenerator( $html, $cssData, $cobro->fields['opc_papel'], $cobro->fields['opc_ver_numpag'] ,'PORTRAIT',1.5,2.0,2.0,2.0,$cobro->fields['estado']);
			$doc->isDebugging=true;
			libxml_use_internal_errors(true);
			$dom = new DOMDocument();
			$dom->loadHTML($doc->output());
			/* Parseo pagina_5 para obtener Fixed Incomes y Variable Incomes */
			$xpath = new DOMXPath($dom);
	
			$query = "//table[@class='tabla_normal']//td//table | //table[@class='tabla_normal' and not(.//table) and not(.='')]";
			$elementos = $xpath->query($query);
			foreach ($elementos as $elemento)
			{
				echo "<table style='border:1px solid black;'>";
				echo DOMinnerHTML($elemento);
				echo "</table>";
			}
		}
	}	
	?>

<?
	$pagina->PrintBottom();
?> 
