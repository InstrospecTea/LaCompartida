<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	if($fecha1 != '')
		$pagina->Redirect("planillas/planilla_resumen_abogado.php?fecha_ini=$fecha1&fecha_fin=$fecha2");
	$pagina->titulo = __('Reporte de Ventas');
	$pagina->PrintTop();

	$query = "SELECT id_moneda FROM prm_moneda WHERE moneda_base = 1";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($id_moneda_base) = mysql_fetch_array($resp);
?>

<form method=post name=formulario action="planillas/planilla_resumen_cliente.php">

<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?=__('Fecha desde')?>
		</td>
		<td align=left>
			<?= Html::PrintCalendar("fecha_ini", "$fecha_ini"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Fecha hasta')?>
		</td>
		<td align=left>
			<?= Html::PrintCalendar("fecha_fin", "$fecha_fin"); ?>
		</td>
	</tr>
   <tr>
        <td align=right>
           <?=__('Clientes')?>
        </td>
        <td align=left>
            <?=Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente FROM cliente WHERE activo=1 ORDER BY glosa_cliente", "clientes[]",$clientes,"multiple size=5",""); ?>
        </td>
    </tr>
    <tr>
        <td align=right>
           <?=__('Grupos Clientes')?>
        </td>
        <td align=left>
            <?=Html::SelectQuery($sesion,"SELECT id_grupo_cliente, glosa_grupo_cliente FROM grupo_cliente", "grupos[]",$grupos,"multiple size=3",""); ?>
        </td>
    </tr>
   <tr>
        <td align=right>
           <?=__('Forma Cobro')?>
        </td>
        <td align=left>
            <?=Html::SelectQuery($sesion,"SELECT forma_cobro, descripcion FROM prm_forma_cobro ORDER BY forma_cobro", "forma_cobro[]",$forma_cobro,"multiple size=5",""); ?>
        </td>
    </tr>
  	<tr>
  			<td align=right>
  					<?=__('Facturado en')?>
  			</td>
  			<td align=left>
  					<?=Html::SelectQuery($sesion,"SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", "monedas[]", $monedas,"multiple size=3",""); ?>
  			</td>
  	</tr>
  	<tr>
  			<td align=right>
  					<?=__('Mostrar valores en:')?>
  			</td>
  			<td align=left>
  				<?=Html::SelectQuery($sesion,"SELECT id_moneda, glosa_moneda FROM prm_moneda ORDER BY id_moneda", "id_moneda", $id_moneda ? $id_moneda : $id_moneda_base, "", ""); ?>
				</td>
		</tr>
		<tr>
				<td align=right>
						<?=__('Comparar seg&uacute;n:')?>
				</td>
				<td align=left>
						<select name='tarifa' id='tarifa' style='width: 150px;'>
							<option value='monto_thh'> Tarifa del cliente </option>
							<option value='monto_thh_estandar'> Tarifa estandar </option>
						</select>
				</td>
		</tr>
	<tr>
		<td colspan=4 align=right>
			<input type=submit class=btn value="<?=__('Generar planilla')?>">
		</td>
	</tr>

</table>

</form>

<script type="text/javascript">
<!-- //
function setDateDefecto()
{
    hoy = new Date();//tiene hora actual
    hoy.setHours(0,0,0,0);
    ninety_days = new Date();
    ninety_days.setDate(hoy.getDate()-30);

    if(fecha1_Object.picked.date.getTime() == hoy.getTime())
        fecha1_Object.setValor(ninety_days);
}
setDateDefecto();
// ->
</script>

<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
