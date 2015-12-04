<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$Form = new Form($sesion);

  if($id_usuario == "")
		$id_usuario = $sesion->usuario->fields['id_usuario'];

	if (!$fecha1) {
		$fecha1 = date("d-m-Y", strtotime("- 1 month"));
	}
	if (!$fecha2) {
		$fecha2 = date("d-m-Y");
	}

	$pagina->titulo = __('Reporte Gráfico Usuarios');
	$pagina->PrintTop();
?>

<script src="../../public/vendors/Chart.js/Chart.js" ></script>

<form method="post" action="<?php echo $_SERVER[PHP_SELF]; ?>">
<table class="border_plomo tb_base">
	<tr>
		<td align="right">
			<?php echo __('Fecha desde');?>
		</td>
		<td align="left">
			<input class="fechadiff" type="text" name="fecha1" value="<?php echo $fecha1 ?>" id="fecha1" size="11" maxlength="10" />
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta'); ?>
		</td>
		<td align="left">
			<input class="fechadiff" type="text" name="fecha2" value="<?php echo $fecha2 ?>" id="fecha2" size="11" maxlength="10" />
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Usuario');?>
		</td>
		<td align="left"><!-- Nuevo Select -->
			<?php echo $Form->select('id_usuario', $sesion->usuario->ListarActivos('', 'PRO'), $id_usuario, array('empty' => FALSE, 'style' => 'width: 200px')); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Tipo de reporte'); ?>
		</td>
		<td align="left">
			<select id="tipo_reporte" name="tipo_reporte">
				<option <?php echo $tipo_reporte == "proyectos_trabajados" ? "selected" : ""; ?> value="proyectos_trabajados"><?php echo __('Asuntos trabajados'); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="4" align="right">
			<input type="button" class="btn" id="genera_reporte" value="<?php echo __('Generar reporte'); ?>">
		</td>
	</tr>
</table>
</form>

<canvas id="grafico_usuarios" width="600" height="400"></canvas>

<script type="text/javascript">
<!-- //
jQuery(function() {
	var graficoBarraUsuarios;

	jQuery("#genera_reporte").on("click", function() {
		var id_usuario = jQuery("#id_usuario").val();
		var fecha1 = jQuery("#fecha1").val();
		var fecha2 = jQuery("#fecha2").val();

		jQuery.ajax({
			url: 'graficos/grafico_' + jQuery("#tipo_reporte").val() + '.php',
			data: {
				'id_usuario': id_usuario,
				'fecha1': fecha1,
				'fecha2': fecha2
			},
			dataType: 'json',
			type: 'POST',
			success: function(respuesta) {
				var canvas = jQuery("#grafico_usuarios")[0];
				var context = canvas.getContext('2d');

				if (graficoBarraUsuarios) {
					graficoBarraUsuarios.destroy();
				}

				graficoBarraUsuarios = new Chart(context).Bar(respuesta);
			},
			error: function(e) {
				console.log(e);
			}
		});
	});
});

function Habilitar(form)
{
	if(form.tipo_reporte.selectedIndex > 0)
		form.codigo_asunto.disabled = true;
	else
		form.codigo_asunto.disabled = false;
}
// ->
</script>

<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
