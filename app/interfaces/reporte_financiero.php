<?php

require_once dirname(__FILE__) . '/../conf.php';

require_once 'Spreadsheet/Excel/Writer.php';

$sesion = new Sesion();
$pagina = new Pagina($sesion);

/*
 * El usuario debe tener los permisos ADM y REP para acceder a este reporte.
 */
if (!$sesion->usuario->Es('ADM') || !$sesion->usuario->Es('REP')) {
	$_SESSION['flash_msg'] = 'No tienes permisos para acceder a ' . __('Reporte financiero') . '.';
	$pagina->Redirect(Conf::RootDir() . '/app/interfaces/reportes_especificos.php');
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
		$fecha2 = sprintf('%s-%02d-%s', $duracion_mes[$fecha1_m - 1], sprintf('%02d', $fecha1_m), $fecha1_a);
	} else {
		$fecha1 = sprintf('01-%02d-%s', $fecha1_m, $fecha1_a);
		$fecha2 = sprintf('%s-%02d-%s', $duracion_mes[$fecha2_m - 1], $fecha2_m, $fecha2_a);
	}
	require_once('planillas/planilla_resumen_horas.php');
	exit;
}

$pagina->titulo = __('Reporte financiero');
$pagina->PrintTop();
$Form = new Form;
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
<form name="formulario2" id="formulario2" method="post" action='' autocomplete="off">
	<table style="border: 1px solid black;">
		<tr>
			<td align=right>
				<?php echo __('Fecha desde') ?>
			</td>
			<td align=left>
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td valign="middle"><?php echo $Form->select('fecha1_m', $meses_corto, $fecha_m, array('empty' => false)); ?></td>
							<td valign="middle">
								<input id="fecha1_a" name="fecha1_a" size="4" maxlength="4" value="<?php echo $fecha_a ?>" onkeypress="return YearDigitsOnly(window.event)" type="text">
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
				<table cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td valign="middle"><?php echo $Form->select('fecha2_m', $meses_corto, $fecha_m, array('empty' => false)); ?></td>
							<td>
								<input id="fecha2_a" name="fecha2_a" size="4" maxlength="4" value="<?php echo $fecha_a ?>" onkeypress="return YearDigitsOnly(window.event)" type="text">
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
				<select name="vista" id="vista" onchange="ShowSeleccion();">
					<?php
					$vistas = array('profesional', 'mes_reporte', 'glosa_cliente', 'glosa_asunto');
					$nombre_vistas = array(__('profesional'), __('Mes'), __('glosa_cliente'), __('glosa_cliente') . ' - ' . __('glosa_asunto'));
					// Las vistas se escriben en el select en el lenguaje actual
					for ($i = 0; $i < count($vistas); ++$i) {
						echo "<option value='$vistas[$i]'>$nombre_vistas[$i]</option>\n";
					}
					?>
				</select>
			</td>
		</tr>
		<tr id="tr_seleccion">
			<td align="right">
				<?php echo __('Mostrar') ?>
			</td>
			<td align="left">
				<select name="seleccion" id="seleccion">
					<option value='profesionales'>solo profesionales</option>
					<option value='todos'>todo el personal</option>
				</select>
			</td>
		</tr>
		<tr id="tr_seleccion">
			<td align="right">
				<?php echo __('Proporcionalidad') ?>
			</td>
			<td align="left"><?php echo $Form->select('proporcionalidad', $proporcionalidades, $proporcionalidad, array('empty' => false)); ?></td>
		</tr>
		<tr>
			<td align=right colspan=2>
				<input type=hidden name='opc' value='reporte'>
				<?php echo $Form->submit(__('Generar reporte')); ?>
			</td>
		</tr>
	</table>
</form>
<br />
<?php

echo $Form->script();

$pagina->PrintBottom($popup);

