<?php

require_once dirname(__FILE__) . '/../conf.php';

require_once 'Spreadsheet/Excel/Writer.php';

$Sesion = new Sesion();
$Pagina = new Pagina($Sesion);
$Moneda = new Moneda($Sesion);

/*
 * El usuario debe tener los permisos ADM y REP para acceder a este reporte.
 */
if (!$Sesion->usuario->Es('ADM') || !$Sesion->usuario->Es('REP')) {
	$_SESSION['flash_msg'] = 'No tienes permisos para acceder a ' . __('Reporte financiero') . '.';
	$Pagina->Redirect(Conf::RootDir() . '/app/interfaces/reportes_especificos.php');
}

if (empty($fecha_a) || $fecha_a < 1) {
	$fecha_a = date('Y');
}

if (empty($fecha_m) || $fecha_m < 1 || $fecha_m > 12) {
	$fecha_m = date('m');
}

if (empty($proporcionalidad)) {
	$proporcionalidad = "cliente";
}

$meses = array(
	__('Enero'),
	__('Febrero'),
	__('Marzo'),
	__('Abril'),
	__('Mayo'),
	__('Junio'),
	__('Julio'),
	__('Agosto'),
	__('Septiembre'),
	__('Octubre'),
	__('Noviembre'),
	__('Diciembre')
);

if ($opc == 'reporte') {
	// Calcular fechas para resumen horas, en formato dd-mm-aaaa.
	$duracion_mes = array('31', (Utiles::es_bisiesto($fecha2_a) ? '29' : '28'), '31', '30', '31', '30', '31', '31', '30', '31', '30', '31');
	// Revisar el orden de las fechas.
	if ($fecha2_a < $fecha1_a || ($fecha2_a == $fecha1_a && $fecha2_m < $fecha1_m)) {
		$fecha1 = sprintf('01-%02d-%s', $fecha2_m, $fecha2_a);
		$fecha2 = sprintf('%s-%02d-%s', $duracion_mes[$fecha1_m - 1], $fecha1_m, $fecha1_a);
	} else {
		$fecha1 = sprintf('01-%02d-%s', $fecha1_m, $fecha1_a);
		$fecha2 = sprintf('%s-%02d-%s', $duracion_mes[$fecha2_m - 1], $fecha2_m, $fecha2_a);
	}
	require_once('planillas/planilla_resumen_horas.php');
	exit;
}

$Pagina->titulo = __('Reporte financiero');
$Pagina->PrintTop();
$Form = new Form();

$meses_corto = array(
	1 => __('Ene'),
	2 => __('Feb'),
	3 => __('Mar'),
	4 => __('Abr'),
	5 => __('May'),
	6 => __('Jun'),
	7 => __('Jul'),
	8 => __('Ago'),
	9 => __('Sep'),
	10 => __('Oct'),
	11 => __('Nov'),
	12 => __('Dic')
);

$proporcionalidades = array(
	'estandar' => __('Estándar'),
	'cliente' => __('Cliente')
);
?>
<style>
	#tbl_tarifa
	{
		font-size: 10px;
		padding: 1px;
		margin: 0px;
		vertical-align: middle;
		border:1px solid #CCCCCC;
	}
	.text_box
	{
		font-size: 10px;
		text-align:right;
	}
</style>
<script type="text/javascript">
	function ShowSeleccion() {
		if (jQuery('#vista').val() == 'profesional') {
			jQuery('#tr_seleccion').css({display: 'table-row'});
		} else {
			jQuery('#tr_seleccion').css({display: 'none'});
		}
	}
</script>
<form name="formulario2" id="formulario2" method="post" action="" autocomplete="off">
	<table style="border: 1px solid black;">
		<tr>
			<td align="right">
				<?php echo __('Fecha desde') ?>
			</td>
			<td align="left">
				<table align="left" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td valign="middle">
								<?php echo $Form->select('fecha1_m', $meses_corto, $fecha_m, array('empty' => false)); ?>
							</td>
							<td valign="middle">
								<?php
								$year_input_attributes = array(
									'size' => '4',
									'maxlength' => '4',
									'label' => false,
									'onKeyPress' => 'return YearDigitsOnly(window.event)'
								);
								echo $Form->input('fecha1_a', $fecha_a, $year_input_attributes);
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Fecha hasta') ?>
			</td>
			<td align="left">
				<table align="left" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td valign="middle">
								<?php echo $Form->select('fecha2_m', $meses_corto, $fecha_m, array('empty' => false)); ?>
							</td>
							<td>
								<?php echo $Form->input('fecha2_a', $fecha_a, $year_input_attributes); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Agrupar por') ?>
			</td>
			<td align="left">
				<?php
				$vistas = array(
					'profesional' => __('profesional'),
					'mes_reporte' => __('Mes'),
					'glosa_cliente' => __('glosa_cliente'),
					'glosa_asunto' => __('glosa_cliente') . ' - ' . __('glosa_asunto')
				);
				echo $Form->select('vista', $vistas, '', array('onchange' => 'ShowSeleccion();', 'empty' => false));
				?>
			</td>
		</tr>
		<tr id="tr_seleccion">
			<td align="right">
				<?php echo __('Mostrar') ?>
			</td>
			<td align="left">
				<?php
				$opciones = array(
					'profesionales' => __('solo profesionales'),
					'todo el personal' => __('todo el personal')
				);
				echo $Form->select('seleccion', $opciones, '', array('empty' => false));
				?>
			</td>
		</tr>
		<tr id="tr_seleccion">
			<td align="right">
				<?php echo __('Proporcionalidad') ?>
			</td>
			<td align="left">
				<?php echo $Form->select('proporcionalidad', $proporcionalidades, $proporcionalidad, array('empty' => false)); ?>
			</td>
		</tr>
		<tr>
			<td align="right">
				<?php echo __('Moneda') ?>
			</td>
			<td align="left">
				<?php echo $Form->select('moneda_visualizacion', $Moneda->Listar(), $moneda_visualizacion, array('empty' => false)); ?>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td align="left">
				<input type="hidden" name="opc" value="reporte">
				<?php echo $Form->submit(__('Generar reporte')); ?>
			</td>
		</tr>
	</table>
</form>
<br />
<?php

echo $Form->script();

$Pagina->PrintBottom($popup);