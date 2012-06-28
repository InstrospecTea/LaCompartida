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
	require_once Conf::ServerDir().'/../app/classes/Factura.php';
	require_once Conf::ServerDir().'/../app/classes/DocGenerator.php';

	$sesion = new Sesion(array());
	$pagina = new Pagina($sesion);

	
	if($numero)
	{
		$factura = new Factura($sesion);
		if(!$factura->LoadByNumero($numero))
			echo 'Factura no existe!';
		else
		{
			if( $formatos )
				{
					$lista_formatos = implode(', ',$formatos);
					$where = " WHERE id_factura_formato = ".$lista_formatos."  ";
				}
			else
				$where = "";
			
			$query = " SELECT id_factura_formato, descripcion FROM factura_rtf $where";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			$doc = 'No se encontro formaato RTF';
			while( list($id_factura_formato,$descripcion) = mysql_fetch_array($resp) )
			{
				$imprimir = "<a href='javascript:void(0)' onclick=\"ImprimirDocumento(".$factura->fields['id_factura'].");\" ><img src='".Conf::ImgDir()."/pdf.gif' border=0 title=Imprimir></a>";
			
				$doc = $factura->GeneraHTMLFactura($id_factura_formato);
			}
			$ruta_cuadricula = Conf::Server().Conf::ImgDir().'/cuadricula_transp.png';
		}
	}	
	
	if($opc == 'generar_factura')
	{
		// POR HACER
		// mejorar
		if($id_factura_grabada)
			include dirname(__FILE__).'/factura_doc.php';
		else
			echo "Error";
		exit;
	}
	
	
	$pagina->PrintTop();
?>
<form name="form1" action="<?php echo $_server['php_self'];?>" method="post">
<table width="100%">
	<tr>
		<td align="center">
			Numero Documento Legal: <input type="text" name="numero" value="<?php echo $numero; ?>" />
			&nbsp;
			<?=Html::SelectQuery($sesion,"SELECT id_factura_formato, descripcion AS nombre FROM factura_rtf", "formatos[]", $formatos,"class=\"select\" size=1 ","",""); ?>
			&nbsp;
			<input type="submit" name="enviar" />
		</td>
	</tr>
	<tr>
		<td>
			&nbsp;
		</td>
	</tr>
</table>
</form>
	<?php
	if($numero)
	{
		echo $imprimir;
		echo "<br><br><br>";
		echo "<div id='hoja_carta_cuadriculada' style='border:1px solid black;' width='842px' height='1056px'>";
					echo "<style>".$doc['css']."</style>";
					echo $doc['html'];
		echo "</div>";
	}	
	?>	
<script type="text/javascript">
$('hoja_carta_cuadriculada').style.background="url('<?=$ruta_cuadricula;?>')";	
function ImprimirDocumento( id_factura )
{
	var vurl = 'testing_doc_html.php?opc=generar_factura&id_factura_grabada=' + id_factura;
	
	self.location.href=vurl;
}	
</script>
<?
	$pagina->PrintBottom();
?> 
