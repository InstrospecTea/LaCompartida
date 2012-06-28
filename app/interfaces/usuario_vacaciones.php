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

	$pagina->titulo = __('Reporte Usuario Vacaciones');
	$pagina->PrintTop();
?>

<form method=post name=formulario action="usuarios_xls.php">
<input type="hidden" name="vacacion" value=true />
<table class="border_plomo tb_base">
	<tr>
		<td align=right>
			<?=__('Fecha desde')?>
		</td>
		<td align=left>
		  <input type="text" name="fecha_ini" value="<?=$fecha_ini ?>" id="fecha_ini" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Fecha hasta')?>
		</td>
		<td align=left>
			<input type="text" name="fecha_fin" value="<?=$fecha_fin ?>" id="fecha_fin" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Informe')?>
		</td>
		<td align=left>
			<?=Html::SelectQuery($sesion,"SELECT id_usuario, CONCAT_WS(' ', nombre, apellido1, apellido2) as nombre_usuario FROM usuario WHERE visible = 1 ORDER BY nombre_usuario","id_usuario", $id_usuario,"","Todos","150")?>
		</td>
	</tr>
	<tr>
		<td colspan=4 align=center>
			<input type=submit class=btn value="<?=__('Descargar Reporte')?>">
		</td>
	</tr>

</table>

</form>

<script type="text/javascript">
<!-- //
Calendar.setup(
	{
		inputField	: "fecha_ini",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_ini"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_fin",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha_fin"		// ID of the button
	}
);

// ->
</script>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
