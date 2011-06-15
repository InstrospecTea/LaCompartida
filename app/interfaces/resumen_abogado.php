<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];

	if($fecha1 != '')
		$pagina->Redirect("planillas/planilla_resumen_abogado.php?fecha_ini=$fecha1&fecha_fin=$fecha2");	
	$pagina->titulo = __('Reporte Resumen Profesional');
	$pagina->PrintTop();
?>

<form method=post name=formulario action="planillas/planilla_resumen_abogado.php">
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
           <?=__('Profesionales')?>
        </td>
        <td align=left>
            <?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]",$usuarios,"multiple size=7","","200"); ?>
        </td>
    </tr>
   <tr>
        <td align=right>
           <?=__('Forma Cobro')?>
        </td>
        <td align=left>
            <?=Html::SelectQuery($sesion,"SELECT forma_cobro, descripcion FROM prm_forma_cobro ORDER BY forma_cobro", "forma_cobro[]",$forma_cobro,"multiple size=5","","200"); ?>
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
//setDateDefecto();
// ->
</script>

<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
