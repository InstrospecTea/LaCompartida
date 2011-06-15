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

    if($id_usuario == "")
        $id_usuario = $sesion->usuario->fields['id_usuario'];

	$pagina->titulo = __('Reporte Gráfico Usuarios');
	$pagina->PrintTop();
?>

<form method=post action="<?= $_SERVER[PHP_SELF] ?>">
<input type=hidden name=opcion value="desplegar" />

<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?=__('Fecha desde')?>
		</td>
		<td align=left>
			<?= Html::PrintCalendar("fecha1", "$fecha1"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Fecha hasta')?>
		</td>
		<td align=left>
			<?= Html::PrintCalendar("fecha2", "$fecha2"); ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Usuario')?>
		</td>
		<td align=left>
			<?= Html::SelectQuery($sesion,"SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "id_usuario", $id_usuario) ?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Tipo de reporte')?>
		</td>
		<td align=left>
			<select name="tipo_reporte">
				<option <?= $tipo_reporte == "proyectos_trabajados" ? "selected" : "" ?> value="proyectos_trabajados"><?=__('Asuntos trabajados')?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan=4 align=right>
			<input type=submit class=btn value="<?=__('Generar reporte')?>"/>
		</td>
	</tr>

</table>
	
</form>

<?
	if($opcion == "desplegar")
	{
?>
		<br />
		<img src=graficos/grafico_<?=$tipo_reporte?>.php?id_usuario=<?=$id_usuario?>&fecha1=<?=$fecha1?>&fecha2=<?=$fecha2?> alt='' />
<?
	}


?>
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
function Habilitar(form)
{
	if(form.tipo_reporte.selectedIndex > 0)
		form.codigo_asunto.disabled = true;
	else
		form.codigo_asunto.disabled = false;
}
setDateDefecto();
// ->
</script>

<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
