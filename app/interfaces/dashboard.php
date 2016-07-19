<?php
require_once dirname(__FILE__) . '/../conf.php';

$Sesion = new Sesion();
$Pagina = new Pagina($Sesion);

if (empty($period)) {
	$period = 'this_week';
}

$period_options = array(
	'this_week' => __('Semana actual'),
	'last_week' => __('Semana anterior'),
	'this_month' => __('Mes actual'),
	'last_month' => __('Mes anterior')
);

$opciones = array(
	'opcion_usuario' => 'buscar'
);

switch ($period) {
	case 'this_week':
		$fecha_desde = date('d-m-Y', strtotime('this week monday'));
		$fecha_hasta = date('d-m-Y', strtotime('this week sunday'));
		break;

	case 'last_week':
		$fecha_desde = date('d-m-Y', strtotime('last week monday'));
		$fecha_hasta = date('d-m-Y', strtotime('last week sunday'));
		break;

	case 'this_month':
		$fecha_desde = date('d-m-Y', strtotime('first day of this month'));
		$fecha_hasta = date('d-m-Y', strtotime('last day of this month'));
		break;

	case 'last_month':
		$fecha_desde = date('d-m-Y', strtotime('first day of last month'));
		$fecha_hasta = date('d-m-Y', strtotime('last day of last month'));
		break;

	default:
		break;
}

$datos = array(
	'fecha_desde' => $fecha_desde,
	'fecha_hasta' => $fecha_hasta,
	'id_usuario' => $Sesion->usuario->fields['id_usuario']
);

$reporte = new ReporteRentabilidadProfesional($Sesion, $opciones, $datos);
$SimpleReport = $reporte->generar();
$date_range_text = __('Desde el') . ' ' . $fecha_desde  . ' ' . __('hasta el') . ' ' . $fecha_hasta;

$Pagina->titulo = __('Producción Personal');
$Pagina->PrintTop();
?>
<script src="//static.thetimebilling.com/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//static.thetimebilling.com/css/bootstrap.min.css" />
<style type="text/css">
	.thumbnails .span6 {
		margin-left: 10px !important;
	}
</style>
<form class="form-inline" style="text-align: left">
	<?= Html::SelectArrayDecente($period_options, 'period', $period, 'onchange="form.submit()"'); ?>
	<span class="help-inline"><?= $date_range_text; ?></span>
</form>

<?php
$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
echo $writer->save();

$Pagina->PrintBottom();
