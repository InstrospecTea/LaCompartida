<?php
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

	$sesion = new Sesion(array());
	$pagina = new Pagina($sesion);
	
	$cobro = new Cobro($sesion);
	if( $id_cobro )
		$cobro->Load($id_cobro);
?>

<style>
table.main
{
    border-color: #000;
    border-width: 0 0 0 0;
    border-style: solid;
    border-collapse:collapse;
}

table.main td,th
{
    border-color: #000;
    border-width: 1px 1px 1px 1px;
    border-style: solid;
    margin: 0;
    padding: 4px;
}
</style>
<?php 
	$z=0;
	$arr_montos=array();	
	$arr_montos[$z]='monto';
	$arr_montos[$z++]='honorarios';
	$arr_montos[$z++]='monto_subtotal';//--> honorarios: monto_subtotal(moneda_tarifa)-descuento(moneda_tarifa)
	$arr_montos[$z++]='subtotal_honorarios'; //--> honorarios en tabla documento.
	$arr_montos[$z++]='monto_trabajos';//-->monto Trabajo: monto_trabajos(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
	$arr_montos[$z++]='monto_tramites';//-->monto tramites: monto_tramites(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
	$arr_montos[$z++]='impuesto';//-->iva honorarios: impuesto(moneda_tarifa) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
	$arr_montos[$z++]='descuento_honorarios';//--> descuento honorarios
	$arr_montos[$z++]='descuento';//--> descuento: descuento(moneda_tarifa)
	$arr_montos[$z++]='monto_honorarios';
	$arr_montos[$z++]='subtotal_gastos';//--> gastos: subtotal_gastos(moneda_total)
	$arr_montos[$z++]='impuesto_gastos';//-->iva gastos: impuesto_gastos(moneda_total) #esto hay que revisarlo porque se hizo hace poco y no se que pasa hacia atras
	$arr_montos[$z++]='monto_gastos';
	$arr_montos[$z++]='subtotal_gastos_sin_impuesto';
	// --> Totales del cobro:
	$arr_montos[$z++]='monto_iva';
	$arr_montos[$z++]='monto_total_cobro';
	$arr_montos[$z++]='monto_total_cobro_thh';
	$arr_montos[$z++]='monto_cobro_original';
	$arr_montos[$z++]='monto_cobro_original_con_iva';
	$arr_montos[$z++]='saldo_honorarios';
	$arr_montos[$z++]='saldo_gastos';

	$resultados = array();
	$pagina->PrintTop();
?>
<form name="form1" action="<?php echo $_server['php_self'];?>" method="post">
<table>
	<tr>
		<td colspan="6">Id Cobro: <input type="text" name="id_cobro" value="<?php echo $id_cobro; ?>" />&nbsp;<input type="submit" name="enviar" /></td></tr>
	<tr><td>&nbsp;</td></tr>
<?php 
	if($id_cobro)
	{
		$resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $id_cobro);
		$query = "SELECT id_moneda FROM prm_moneda ORDER BY id_moneda ASC";
		$lista_monedas = new ListaMonedas($sesion,'',$query);
?>	

	<tr>
		<td>
			<table class='main'  width="100%" >
				<tr>
					<td>Tabla</td>
					<td><?php echo $resultados['tabla']; ?></td>
				</tr>
				<tr>
					<td>Forma de cobro</td>
					<td><?php echo $resultados['forma_cobro']; ?></td>
				</tr>
				<tr>
					<td>Estado Cobro</td>
					<td><?php echo $cobro->fields['estado']; ?></td>
				</tr>
			</table>
			<table class='main'  width="100%" >	
				<tr>
					<th>&nbsp;</th>
					<th>id</th>
					<th>tipo cambio</th>
					<th>cifras decimal</th>
				</tr>	
				<tr>
					<td>id_moneda</td>
					<td><?php echo $resultados['id_moneda']; ?></td>
					<td><?php echo $resultados['tipo_cambio_id_moneda']; ?></td>
					<td><?php echo $resultados['cifras_decimales_id_moneda']; ?></td>
				</tr>
				<tr>
					<td>id_moneda_monto</td>
					<td><?php echo $resultados['id_moneda_monto']; ?></td>
					<td><?php echo $resultados['tipo_cambio_id_moneda_monto']; ?></td>
					<td><?php echo $resultados['cifras_decimales_id_moneda_monto']; ?></td>
				</tr>
				<tr>
					<td>opc_moneda_total</td>
					<td><?php echo $resultados['opc_moneda_total']; ?></td>
					<td><?php echo $resultados['tipo_cambio_opc_moneda_total']; ?></td>
					<td><?php echo $resultados['cifras_decimales_opc_moneda_total']; ?></td>
				</tr>
			</table>
		</td>
	</tr>
	</table>
	<table class='main'  width="100%" >
		<tr>
			<th>Resultado <?php echo $glosa_moneda ?></th>
			<?php
			for($e=1;$e<7;$e++)
			{	
			?>
			<th><?php echo $e;?></th>
			<?php
			}
			?>
		</tr>
		</tr>
		<?php
		for($i=0;$i < count($arr_montos);$i++)
		{
		?>
		<tr>
			<td><?php echo $arr_montos[$i]; ?></td>
			<?php
			for($e=1;$e<7;$e++)
			{
			?>
				<td><?php echo $resultados[$arr_montos[$i]][$e]; ?></td>
			<?php
			}
			?>
			
		</tr>
		<?php
		}
		?>
	</table>
	<?php
}
?>
</form>
<?php
	$pagina->PrintBottom();
?> 
