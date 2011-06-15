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
<table class='main'  width="100%" >
<tr>
	<th rowspan=2>
		Cobro
	</th>
	<th rowspan=2>
		Estado
	</th>
	<th colspan=11>
		Gastos
	</th>
</tr>
<tr>
	<th> Total </th>
	<th> Total Base </th>
	<th> Factor Impuesto </th>
	<th> A </th>
	<th> B </th>
	<th> Impuesto </th>
	<th> Total + Impuesto </th>
	<th> Mostrar Impuesto </th>
	<th> Id Gasto </th>
	<th> Glosa </th>
	<th> Monto </th>
	<th> Monto Base </th>
	<th> Con Impuesto </th>
	<th> Monto Impuesto</th>
</tr>

<?
	if($id_cobro_buscar)
		$where .= " AND cobro.id_cobro = $id_cobro_buscar ";  
	if(!$limit_ini)
		$limit_ini = 0;
	if(!$limit_fin)
		$limit_fin = 30;
	$limit = " LIMIT $limit_ini , $limit_fin ";
	
	$sel = " SELECT cobro.id_cobro, cobro.estado, documento.id_documento FROM cobro
LEFT JOIN documento ON (documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N')  WHERE 1 $where $limit";

	$resp = mysql_query($sel,$sesion->dbh) or Utiles::errorSQL($sel,__FILE__,__LINE__,$sesion->dbh);
	while( list($id_cobro,$estado_cobro,$id_documento) = mysql_fetch_array($resp))
	{
		
		$cobro = new Cobro($sesion);
		$cobro->Load($id_cobro);
		$estado_cobro = $cobro->fields['estado'];

		$lista = UtilesApp::ProcesaGastosCobro($sesion,$id_cobro,array('listar_detalle'));
		$num = count($lista['gasto_detalle']);
		if($num == 0)
			$num = 1;
?>
	<tr>
		<td align=center rowspan=<?=$num?> >
			<?=$id_cobro?>
		</td>
		<td align=center rowspan=<?=$num?> >
			<?=$estado_cobro?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['gasto_total']?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['gasto_base']?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['factor_impuesto']?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['subtotal_gastos_con_impuestos']?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['subtotal_gastos_sin_impuestos']?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['gasto_impuesto']?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['gasto_total_con_impuesto']?>
		</td>
		<td align=center rowspan=<?=$num?> >
				<?=$lista['mostrar_impuesto_gasto']?>
		</td>
		<?
		if($num == 0 ) echo '<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>';
		$i=0;
		foreach($lista['gasto_detalle'] as $id_gasto => $detalle)
		{
			if($i != 0)
				echo '</tr><tr>';
			?>
			<td>
				<?=$id_gasto?>
			</td>
			<td>
				<?=$detalle['descripcion']?>
			</td>
			<td>
				<?=$detalle['monto_total']?>
			</td>
			<td>
				<?=$detalle['monto_base']?>
			</td>
			<td>
				<?=$detalle['con_impuesto']?>
			</td>
			<td>
				<?=$detalle['monto_total_impuesto']?>
			</td>
		<?
			$i++;
		}
		?>
	</tr>
<?
	}
?>
</table>

<fieldset>
<form>
<legend>Buscar</legend>
	Id Cobro: <input name='id_cobro_buscar' value='<?=$id_cobro_buscar?>' />
	Limit: <input name='limit_ini' value='<?=$limit_ini?>' /> - <input name='limit_fin' value='<?=$limit_fin?>' />
	<button type=submit>Buscar</button>
</form>
</fieldset>

<?
	$pagina->PrintBottom();
?>
