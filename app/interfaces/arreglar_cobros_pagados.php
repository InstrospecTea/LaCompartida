<?
    require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';

	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Documento.php';

	$sesion = new Sesion(array('ADM'));
    $pagina = new Pagina($sesion);
	$pagina->PrintTop();

?>

<table width="90%">
<tr>
	<th>
		Cobro
	</th>
	<th>
		Estado
	</th>
	<th>
		Documento
	</th>
	<th>
		Saldo Honorarios
	</th>
	<th>
		Saldo Gastos
	</th>
	<th>
		Ver
	</th>
	<th>
		Arreglar
	</th>
</tr>
<?
	$sel = " SELECT cobro.id_cobro, cobro.codigo_cliente, cobro.estado, documento.id_documento, documento.saldo_honorarios, documento.saldo_gastos
FROM cobro
JOIN documento ON (documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N')  HAVING
cobro.estado = 'PAGADO' AND (documento.saldo_honorarios >0 OR documento.saldo_gastos > 0)";

	$resp = mysql_query($sel,$sesion->dbh) or Utiles::errorSQL($sel,__FILE__,__LINE__,$sesion->dbh);
	while( list($id_cobro,$codigo_cliente,$estado_cobro,$id_documento,$saldo_honorarios,$saldo_gastos) = mysql_fetch_array($resp))
	{
?>
	<tr>
		<td align=center>
			<?=$id_cobro?>
		</td>
		<td align=center>
			<?=$estado_cobro?>
		</td>
		<td align=center>
			<?=$id_documento?$id_documento:'NULL'?>
		</td>
		<td align=center>
			<?=
				$saldo_honorarios;
			?>
		</td>
		<td align=center>
			<?=
				$saldo_gastos;
			?>
		</td>
		<td align=center>
			<img src='<?=Conf::ImgDir()?>/coins_16.png' title='Ver <?php echo __('cobro'); ?>' border=0 style='cursor:pointer' onclick="nuevaVentana('Editar_Cobro',730,580,'cobros6.php?id_cobro=<?=$id_cobro?>&popup=1&popup=1&contitulo=true', 'top=100, left=155');"/>
		</td>
		<td align=center>
			<a href= 'javascript:void(0);' onclick="nuovaFinestra('Agregar Pago',730,580,'ingresar_documento_pago_automatico.php?id_cobro=<?=$id_cobro?>&codigo_cliente=<?=$codigo_cliente?>&popup=1&popup=1&contitulo=true', 'top=100, left=155');">Arreglar</a>
		</td>
	</tr>
<?
	}
?>

</table>

<br><br>
<a href="?">Buscar problemas</a>
<br><br>

<?
	$pagina->PrintBottom();
?>
