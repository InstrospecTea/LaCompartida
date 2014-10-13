<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion();
$Pagina = new Pagina($Sesion);

if (empty($period)) {
  $period = 'this_week';
}

$period_options = array(
  'this_week' => 'Semana actual',
  'last_week' => 'Semana anterior',
  'this_month' => 'Mes actual',
  'last_month' => 'Mes anterior'
);

$opciones = array(
  'opcion_usuario' => 'buscar'
);

switch ($period) {
  case 'this_week':
    $fecha_desde = date('Y-m-d', strtotime('this monday'));
    $fecha_hasta = date('Y-m-d', strtotime('this sunday'));
    break;

  case 'last_week':
    $fecha_desde = date('Y-m-d', strtotime('last monday'));
    $fecha_hasta = date('Y-m-d', strtotime('last sunday'));
    break;

  case 'this_month':
    $fecha_desde = date('Y-m-d', strtotime('first day of this month'));
    $fecha_hasta = date('Y-m-d', strtotime('last day of this month'));
    break;

  case 'last_month':
    $fecha_desde = date('Y-m-d', strtotime('first day of last month'));
    $fecha_hasta = date('Y-m-d', strtotime('last day of last month'));
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

$date_range_text = "Desde el " . Utiles::sql2date($fecha_desde) . " hasta el " . Utiles::sql2date($fecha_hasta);

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
  <?php echo Html::SelectArrayDecente($period_options, 'period', $period, 'onchange="form.submit()"'); ?>
  <span class="help-inline"><?php echo $date_range_text; ?></span>
</form>
<?php
// $writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
// echo $writer->save();
//echo pr($SimpleReport->results);
$result = $SimpleReport->results[0];
?>
<ul class="thumbnails">
  <li class="span6">
    <div class="thumbnail">
      <table class="table">
        <tr style="text-align: center">
          <th style="text-align: center">Acuerdo Comercial</th>
          <th style="text-align: center">Horas</th>
          <th style="text-align: center">Valor</th>
        </tr>
        <tr>
          <td>Tasa Hora Hombre</td>
          <td><?php echo UtilesApp::Hora2HoraMinuto($result['horas_trabajadas']); ?></td>
          <td style="text-align: right"><?php echo UtilesApp::PrintFormatoMoneda($Sesion, $result['valor_tasa_hora_hombre'], 1, $result['moneda'], 2); ?></td>
        </tr>
        <tr>
          <td>Retainer</td>
          <td><?php echo UtilesApp::Hora2HoraMinuto($result['horas_retainer']); ?></td>
          <td style="text-align: right"><?php echo UtilesApp::PrintFormatoMoneda($Sesion, $result['valor_retainer'], 1, $result['moneda'], 2); ?></td>
        </tr>
        <tr>
          <td>Exceso Retainer</td>
          <td><?php echo UtilesApp::Hora2HoraMinuto($result['horas_exceso']); ?></td>
          <td style="text-align: right"><?php echo UtilesApp::PrintFormatoMoneda($Sesion, $result['valor_exceso_retainer'], 1, $result['moneda'], 2); ?></td>
        </tr>
        <tr>
          <td>Flat Fee</td>
          <td></td>
          <td style="text-align: right"><?php echo UtilesApp::PrintFormatoMoneda($Sesion, $result['valor_flat_fee'], 1, $result['moneda'], 2); ?></td>
        </tr>
        <tr>
          <td>Trámites</td>
          <td></td>
          <td style="text-align: right"><?php echo UtilesApp::PrintFormatoMoneda($Sesion, $result['valor_tramites'], 1, $result['moneda'], 2); ?></td>
        </tr>
        <tr>
          <td><abbr title="Suma total de los montos de acuerdos de tipo FLAT FEE o HITOS que no tienen horas trabajadas en el período y en las cuales eres el Encargado Comercial">Flat Fee sin HH</abbr></td>
          <td></td>
          <td style="text-align: right"><?php echo UtilesApp::PrintFormatoMoneda($Sesion, $result['valor_flat_fee_encargado'], 1, $result['moneda'], 2); ?></td>
        </tr>
        <tr>
          <th>Total</td>
          <td></td>
          <th style="text-align: right">
            <?php
              $total = $result['valor_tasa_hora_hombre']
                    + $result['valor_retainer']
                    + $result['valor_exceso_retainer']
                    + $result['valor_flat_fee']
                    + $result['valor_tramites']
                    + $result['valor_flat_fee_encargado'];
              echo UtilesApp::PrintFormatoMoneda($Sesion, $total, 1, $result['moneda'], 2);
            ?>
          </th>
        </tr>
      </table>
    </div>
  </li>
</ul>
<?php
$Pagina->PrintBottom();