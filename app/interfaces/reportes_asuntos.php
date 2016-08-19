<?php
	require_once dirname(__FILE__).'/../conf.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$id_usuario = $sesion->usuario->fields['id_usuario'];
	$Form = new Form($sesion);
	$Html = new \TTB\Html;

	$pagina->titulo = __('Reporte Gr�fico asuntos');
	$pagina->PrintTop();

	if(!$fecha_ini)
	{
		$fecha_anio = date('Y');
		$fecha_mes = date('m');
		$dia_fin_mes = date('t');

		$fecha_fin = $dia_fin_mes."-".$fecha_mes."-".$fecha_anio;
		$fecha_ini = "01-".$fecha_mes."-".$fecha_anio;
	}

?>

<form method='post' name='formulario'>
<input type="hidden" name="opcion" value="desplegar">

<table class="border_plomo tb_base">
	<tr>
		<td align="right">
			<?php echo __('Fecha desde'); ?>
		</td>
		<td align=left>
			<?php echo $Html::PrintCalendar('fecha_ini', $fecha_ini); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Fecha hasta'); ?>
		</td>
		<td align="left">
			<?php echo $Html::PrintCalendar('fecha_fin', $fecha_fin); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Clientes'); ?>
		</td>
		<td align="left">
	  		<?php echo Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE 1 ORDER BY nombre ASC", "clientes",$clientes,"class=\"selectMultiple\" multiple size=6 ","","230"); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Profesionales'); ?>
		</td>
		<td align="left">
			<!-- Nuevo Select -->
      <?php echo $Form->select('usuarios', $sesion->usuario->ListarActivos('', 'PRO'), $usuarios, array('empty' => FALSE, 'style' => 'width: 230px', 'class' => 'selectMultiple', 'multiple' => 'multiple', 'size' => '6')); ?>
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Solo activos'); ?>
		</td>
		<td>
			<?php
				if ($solo_activos) {
					$chk = "checked='checked'";
				}
			?>
			<input type="checkbox" name="solo_activos" id="solo_activos" value="1" <?php echo $chk; ?> />
		</td>
	</tr>
	<tr>
		<td align="right">
			<?php echo __('Tipo de reporte');?>
		</td>
		<td align=left>
			<select id="tipo_reporte" name="tipo_reporte">
				<option <?php $tipo_reporte == "hh_por_empleado" ? "selected" : "" ?> value="hh_por_empleado"><?php echo __('Horas trabajadas por empleado'); ?></option>
				<option <?php $tipo_reporte == "hh_por_asunto" ? "selected" : "" ?> value="hh_por_asunto"><?php echo __('Horas trabajadas por asunto'); ?></option>
				<option <?php $tipo_reporte == "hh_por_cliente" ? "selected" : "" ?> value="hh_por_cliente"><?php echo __('Horas trabajadas por cliente'); ?></option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="3" align="right">
			<input type="button" class="btn" id="genera_reporte" value="<?php echo __('Generar reporte'); ?>">
		</td>
		<td align="right">
			<input type="submit" class="btn" value="<?php echo __('Planilla'); ?>" onclick="return Planilla(this.form);">
		</td>
	</tr>
</table>
</form>

<div id="contenedor_grafico_hito"></div>

<script type="text/javascript">
jQuery(function() {
	var graficoBarraHito;

	jQuery("#genera_reporte").on("click", function() {
		var usuarios = jQuery("#usuarios").val();
		var clientes = jQuery("#clientes").val();
		var solo_activos = jQuery("#solo_activos:checked").val();
		var fecha_ini = jQuery("#fecha_ini").val();
		var fecha_fin = jQuery("#fecha_fin").val();
		var url = 'graficos/grafico_' + jQuery("#tipo_reporte").val() + '.php'

		jQuery.ajax({
			url: 'render_grafico.php',
			data: {
				'usuarios': usuarios,
				'clientes': clientes,
				'solo_activos': solo_activos,
				'fecha_ini': fecha_ini,
				'fecha_fin': fecha_fin,
				'url': url
			},
			dataType: 'html',
			type: 'POST',
			success: function(respuesta) {
				if (respuesta != null) {
					var iframe = document.createElement('iframe');
					iframe.style = "border: none;";
					iframe.scrolling = "no";
					iframe.width = "700";
					iframe.height = "500";
					iframe.srcdoc = respuesta;

					jQuery('#contenedor_grafico_hito').empty();
					jQuery('#contenedor_grafico_hito').append(iframe);
				}
			},
			error: function(e) {
				alert('Se ha producido un error en la carga de los gr�ficos, favor volver a cargar la pagina. Si el problema persiste favor comunicarse con nuestra �rea de Soporte.');
			}
		});
	});

	function agregarCanvas(id, contenedor, titulo) {
		var canvas = document.createElement('canvas');
		var h3 = document.createElement('h3');
		canvas.width = 600;
		canvas.height = 400;
		canvas.id = 'grafico_' + id;
		h3.innerHTML = titulo;

		contenedor.empty();
		contenedor.append(h3);
		contenedor.append(canvas);
	}
});

function Planilla(form) {
	form.action = "planillas/planilla_horas_general.php";
	form.submit();
}
</script>
<?php
	$pagina->PrintBottom();
?>
