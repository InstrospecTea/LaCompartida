<?php
require_once dirname(__FILE__) . '/../../conf.php';

require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$sesion = new sesion(array('REP'));

$query_usuario = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario
      JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre";

if (in_array($opcion, array('buscar', 'xls'))) {

  $opciones = array(
    'opcion_usuario' => $opcion
  );

  $datos = array(
    'fecha_desde' => $fecha_desde,
    'fecha_hasta' => $fecha_hasta,
    'id_contrato' => $id_contrato
  );

  $reporte = new ReporteRentabilidadProfesional($sesion, $opciones, $datos);

  $SimpleReport = $reporte->generar();
}

if (empty($fecha_desde)) {
  $fecha_desde = date('01-m-Y');
}

if (empty($fecha_hasta)) {
  $fecha_hasta = date('t-m-Y');
}

$Pagina = new Pagina($sesion);
$Pagina->titulo = __('Reporte Producción por Profesional');
$Pagina->PrintTop();

?>
<table width="90%">
  <tr>
    <td>
      <form method="POST" name="form_rentabilidad_profesional" action="#" id="form_rentabilidad_profesional">
        <input  id="xdesde"  name="xdesde" type="hidden" value="">
        <input type="hidden" name="opcion" id="opcion" value="buscar">
        <!-- Calendario DIV -->
        <div id="calendar-container" style="width:221px; position:absolute; display:none;">
          <div class="floating" id="calendar"></div>
        </div>
        <!-- Fin calendario DIV -->
        <fieldset class="tb_base" style="width: 100%;border: 1px solid #BDBDBD;">
          <legend><?php echo __('Filtros') ?></legend>
          <table style="border: 0px solid black" width='720px'>
            <tr>
              <td align="right"><?php echo __('Fecha Desde') ?></td>
              <td nowrap align="left">
                  <input type="text" name="fecha_desde" value="<?php echo $fecha_desde ?>" id="fecha_desde" size="11" maxlength="10" />
                  <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_desde" style="cursor:pointer" />
              </td>
              <td align="right"><?php echo __('Fecha Hasta') ?></td>
              <td nowrap align="left">
                  <input type="text" name="fecha_hasta" value="<?php echo $fecha_hasta ?>" id="fecha_hasta" size="11" maxlength="10" />
                  <img src="<?php echo Conf::ImgDir() ?>/calendar.gif" id="img_fecha_hasta" style="cursor:pointer" />
              </td>
            </tr>
            <tr>
              <td>
                <label style="display:none">
                  id_contrato:
                  <input type="text" name="id_contrato" value="<?php echo $id_contrato; ?>" />
                </label>
              </td>
              <td align="left">
                <input name="boton_buscar" id="boton_buscar" type="submit" value="<?php echo __('Buscar') ?>" class="btn" />
              </td>
              <td width="40%" align="right" colspan="2">
                <input name="boton_xls" id="boton_xls" type="submit" value="<?php echo __('Descargar Excel') ?>" class="btn" />
              </td>
            </tr>
          </table>
        </fieldset>
      </form>
    </td>
  </tr>
</table>
<script type="text/javascript">
  jQuery(document).ready(function() {
    jQuery('#boton_xls').click(function() {
      jQuery('#opcion').val('xls');
    });
    jQuery('#boton_buscar').click(function() {
      jQuery('#opcion').val('buscar');
    });

    Calendar.setup({ inputField : "fecha_desde", ifFormat : "%d-%m-%Y", button : "img_fecha_desde" });
    Calendar.setup({ inputField : "fecha_hasta", ifFormat : "%d-%m-%Y", button : "img_fecha_hasta" });
  });
</script>

<?php
if ($_REQUEST['opcion'] == 'buscar') {
  $writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
  echo $writer->save();
}

$Pagina->PrintBottom();