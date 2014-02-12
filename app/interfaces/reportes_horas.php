<?php
require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Html.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/InputId.php';
require_once Conf::ServerDir() . '/classes/Trabajo.php';

$sesion = new Sesion(array('REP'));
//Revisa el Conf si esta permitido

$pagina = new Pagina($sesion);
$fecha_desde = $fecha_anio_desde . '-' . sprintf('%02d', $fecha_mes_desde) . '-01';
$fecha_hasta = $fecha_anio_hasta . '-' . sprintf('%02d', $fecha_mes_hasta) . '-31';

$pagina->titulo = __('Reporte Gráfico por Período');
$pagina->PrintTop();
?>

<script type="text/javascript">

    function Validar(form, opc)
    {
        form.opcion.value = opc;
        if (form.tipo_reporte.selectedIndex === 2)
        {
            var selectedArray = new Array();
            var selObj = $('clientes[]');
            var count = 0;
            for (i = 0; i < selObj.options.length; i++)
            {
                if (selObj.options[i].selected)
                {
                    count++;
                }
            }
            if (count === 0)
            {
                alert('Debe seleccionar un cliente.');
                return false;
            }
        }
        else if (form.tipo_reporte.selectedIndex === 1)
        {
            var selectedArray = new Array();
            var selObj = $('usuarios[]');
            var count = 0;
            for (i = 0; i < selObj.options.length; i++)
            {
                if (selObj.options[i].selected)
                {
                    count++;
                }
            }
            if (count === 0)
            {
                alert('Debe seleccionar un profesional.');
                return false;
            }
        }
        return form.submit();
    }
    
jQuery(document).ready(function() {
    
    var disable_selector = function(){
        if( jQuery('#comparar').is(':checked')) {
            jQuery("#tipo_duracion_comparada").prop('disabled', false);
        } else {
            jQuery("#tipo_duracion_comparada").prop('disabled', 'disabled');
        }
    }; 
    
    jQuery(disable_selector);
    jQuery("#comparar").change(disable_selector);
});

</script>

<style type="text/css">
    #fecha_mes_desde,#fecha_mes_hasta,#tipo_reporte,#tipo_duracion,#tipo_duracion_comparada{
        width: 100px; 
        margin-left: 10px;
    }
    .selectMultiple{
        margin-left: 10px;
    }
    
    #comparar{
        margin-left: 10px;
    }
</style>


<form method='post' name='formulario'>
    <input type=hidden name=opcion value="desplegar" >

    <table class="border_plomo tb_base" width="50%" >

        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

        <tr>
            <td></td>
            <td align="left"><?php echo _('Periodo') ?></td>
        </tr>

        <tr>
            <td align=right>
                <?php echo _('Desde') ?>
            </td>
            <td align=left>
                <?php
                $fecha_mes_desde = $fecha_mes_desde != '' ? $fecha_mes_desde : date('m') - 1;
                ?>
                <select name="fecha_mes_desde" style='width:100px' id="fecha_mes_desde">
                    <option value='1' <?php echo $fecha_mes_desde == 1 ? 'selected' : '' ?>><?php echo _('Enero') ?></option>
                    <option value='2' <?php echo $fecha_mes_desde == 2 ? 'selected' : '' ?>><?php echo _('Febrero') ?></option>
                    <option value='3' <?php echo $fecha_mes_desde == 3 ? 'selected' : '' ?>><?php echo _('Marzo') ?></option>
                    <option value='4' <?php echo $fecha_mes_desde == 4 ? 'selected' : '' ?>><?php echo _('Abril') ?></option>
                    <option value='5' <?php echo $fecha_mes_desde == 5 ? 'selected' : '' ?>><?php echo _('Mayo') ?></option>
                    <option value='6' <?php echo $fecha_mes_desde == 6 ? 'selected' : '' ?>><?php echo _('Junio') ?></option>
                    <option value='7' <?php echo $fecha_mes_desde == 7 ? 'selected' : '' ?>><?php echo _('Julio') ?></option>
                    <option value='8' <?php echo $fecha_mes_desde == 8 ? 'selected' : '' ?>><?php echo _('Agosto') ?></option>
                    <option value='9' <?php echo $fecha_mes_desde == 9 ? 'selected' : '' ?>><?php echo _('Septiembre') ?></option>
                    <option value='10' <?php echo $fecha_mes_desde == 10 ? 'selected' : '' ?>><?php echo _('Octubre') ?></option>
                    <option value='11' <?php echo $fecha_mes_desde == 11 ? 'selected' : '' ?>><?php echo _('Noviembre') ?></option>
                    <option value='12' <?php echo $fecha_mes_desde == 12 ? 'selected' : '' ?>><?php echo _('Diciembre') ?></option>
                </select>
                <?php
                if (!$fecha_anio_desde) {
                    $fecha_anio_desde = date('Y');
                }
                ?>
                <select name="fecha_anio_desde" style='width:55px' id="fecha_anio_desde">
                    <?php for ($i = (date('Y') - 5); $i <= date('Y'); $i++) { ?>
                        <option value='<?php echo $i ?>' <?php echo $fecha_anio_desde == $i ? 'selected' : '' ?>><?php echo $i ?></option>
                    <?php } ?>
                </select>
            </td>
        </tr>

        <tr>
            <td align=right>
                <?php echo _('Hasta') ?>
            </td>
            <td align=left>
                <?php
                $fecha_mes_hasta = $fecha_mes_hasta != '' ? $fecha_mes_hasta : date('m');
                ?>
                <select name="fecha_mes_hasta" style='width:100px' id="fecha_mes_hasta">
                    <option value='1' <?php echo $fecha_mes_hasta == 1 ? 'selected' : '' ?>><?php echo _('Enero') ?></option>
                    <option value='2' <?php echo $fecha_mes_hasta == 2 ? 'selected' : '' ?>><?php echo _('Febrero') ?></option>
                    <option value='3' <?php echo $fecha_mes_hasta == 3 ? 'selected' : '' ?>><?php echo _('Marzo') ?></option>
                    <option value='4' <?php echo $fecha_mes_hasta == 4 ? 'selected' : '' ?>><?php echo _('Abril') ?></option>
                    <option value='5' <?php echo $fecha_mes_hasta == 5 ? 'selected' : '' ?>><?php echo _('Mayo') ?></option>
                    <option value='6' <?php echo $fecha_mes_hasta == 6 ? 'selected' : '' ?>><?php echo _('Junio') ?></option>
                    <option value='7' <?php echo $fecha_mes_hasta == 7 ? 'selected' : '' ?>><?php echo _('Julio') ?></option>
                    <option value='8' <?php echo $fecha_mes_hasta == 8 ? 'selected' : '' ?>><?php echo _('Agosto') ?></option>
                    <option value='9' <?php echo $fecha_mes_hasta == 9 ? 'selected' : '' ?>><?php echo _('Septiembre') ?></option>
                    <option value='10' <?php echo $fecha_mes_hasta == 10 ? 'selected' : '' ?>><?php echo _('Octubre') ?></option>
                    <option value='11' <?php echo $fecha_mes_hasta == 11 ? 'selected' : '' ?>><?php echo _('Noviembre') ?></option>
                    <option value='12' <?php echo $fecha_mes_hasta == 12 ? 'selected' : '' ?>><?php echo _('Diciembre') ?></option>
                </select>
                <?php
                if (!$fecha_anio_hasta)
                    $fecha_anio_hasta = date('Y');
                ?>
                <select name="fecha_anio_hasta" style='width:55px' id="fecha_anio_hasta">
                    <?php for ($i = (date('Y') - 5); $i <= date('Y'); $i++) { ?>
                        <option value='<?php echo $i ?>' <?php echo $fecha_anio_hasta == $i ? 'selected' : '' ?>><?php echo $i ?></option>
                    <?php } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo _('Cliente') ?>
            </td>
            <td align="left">
                <?php echo Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]", $clientes, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo _('Profesionales') ?>
            </td>
            <td align="left">
                <?php echo Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ',usuario.apellido1,usuario.apellido2,',',usuario.nombre) AS nombre FROM usuario JOIN usuario_permiso USING(id_usuario) WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso='PRO' ORDER BY nombre ASC", "usuarios[]", $usuarios, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo _('Tipo de reporte') ?>
            </td>
            <td align="left">
                <select id="tipo_reporte" name="tipo_reporte" style="width:200px">
                    <option <?php echo $tipo_reporte == "trabajos_por_estudio" ? "selected" : "" ?> value="trabajos_por_estudio"><?php echo _('Simple') ?></option>
                    <option <?php echo $tipo_reporte == "trabajos_por_empleado" ? "selected" : "" ?> value="trabajos_por_empleado"><?php echo _('Desglose profesional') ?></option>
                    <option <?php echo $tipo_reporte == "trabajos_por_cliente" ? "selected" : "" ?> value="trabajos_por_cliente"><?php echo _('Desglose cliente') ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo _('Dato') ?><br/>
            </td>
            <td align="left">
                <select id="tipo_duracion" name="tipo_duracion" style="width:200px">
                    <option <?php echo $tipo_duracion == "trabajada" ? "selected" : "" ?> value="trabajada"><?php echo _('Trabajadas') ?></option>
                    <option <?php echo $tipo_duracion == "cobrable" ? "selected" : "" ?> value="cobrable"><?php echo _('Cobrables') ?></option>
                    <option <?php echo $tipo_duracion == "no_cobrable" ? "selected" : "" ?> value="no_cobrable"><?php echo _('No Cobrables') ?></option>
                    <option <?php echo $tipo_duracion == "cobrada" ? "selected" : "" ?> value="cobrada"><?php echo _('Cobradas') ?></option>

                </select>
            </td>
        </tr>

        <tr>
            <td align="right">
                <?php echo __('Comparar'); ?>
            </td>
            <td align="left">
                <input type="checkbox" id="comparar" name="comparar" value="1" <?php echo $comparar ? 'checked="checked"' : '' ?>>
            </td>
        </tr>

        <tr>
            <td align="right">
                <?php echo _('Con') ?><br/>
            </td>
            <td align="left">
                <select id="tipo_duracion_comparada" name="tipo_duracion_comparada" style="width:200px;" >
                    <option <?php echo $tipo_duracion_comparada == "trabajada" ? "selected" : "" ?> value="trabajada"><?php echo _('Trabajadas') ?></option>
                    <option <?php echo $tipo_duracion_comparada == "cobrable" ? "selected" : "" ?> value="cobrable"><?php echo _('Cobrables') ?></option>
                    <option <?php echo $tipo_duracion_comparada == "no_cobrable" ? "selected" : "" ?> value="no_cobrable"><?php echo _('No Cobrables') ?></option>
                    <option <?php echo $tipo_duracion_comparada == "cobrada" ? "selected" : "" ?> value="cobrada"><?php echo _('Cobradas') ?></option>

                </select>
            </td>
        </tr>

        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

        <tr>
            <td colspan="4" align="center">
                <input type=button class=btn value="<?php echo _('Generar Gráfico') ?>" onclick="Validar(this.form, 'desplegar');" >
            </td>
        </tr>

        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

    </table>

</form>

<?php
if ($opcion == "desplegar") {

    $datos = 'tipo_reporte=' . $tipo_reporte;
    $datos .= '&fecha_desde=' . $fecha_desde . '&fecha_hasta=' . $fecha_hasta . '&tipo_duracion=' . $tipo_duracion;

    if ( $comparar == '1') {
        $datos .= '&comparar='. $comparar;
        $datos .= '&tipo_duracion_comparada=' . $tipo_duracion_comparada;
    }
    
    //en caso de que el tipo de reporte sea por el estudio
    //se imprime un grafico resumen de los clientes y/o usuarios seleccionados

    if ($tipo_reporte == "trabajos_por_estudio") {
        if ($usuarios) {
            foreach ($usuarios as $id_usuario) {
                $datos .= '&usuarios[]=' . $id_usuario;
            }
        }
        if ($clientes) {
            foreach ($clientes as $codigo_cliente) {
                $datos .= '&clientes[]=' . $codigo_cliente;
            }
        }

        echo '<br/>';
        echo '<img src="graficos/grafico_trabajos.php?' . $datos . '" alt="" />';
    }

    //en el reporte por cliente se hace un grafico por cada cliente seleccionado
    if ($tipo_reporte == "trabajos_por_cliente") {
        if ($usuarios) {
            foreach ($usuarios as $id_usuario) {
                $datos .= '&usuarios[]=' . $id_usuario;
            }
        }
        if ($clientes) {
            foreach ($clientes as $codigo_cliente) {
                $datos .= '&codigo_cliente=' . $codigo_cliente;
                $query = "SELECT glosa_cliente AS nombre FROM cliente WHERE codigo_cliente=$codigo_cliente LIMIT 1";
                $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
                $temp = mysql_fetch_array($resp);
                $nombre = str_replace(' ', '-', $temp['nombre']);
                $datos .= '&nombre=' . $nombre;
                echo '<br/>';
                echo '<img src="graficos/grafico_trabajos.php?' . $datos . '" alt="" />';
            }
        }
    }

    //en el reporte por empleado se hace un grafico por cada empleado seleccionado
    if ($tipo_reporte == "trabajos_por_empleado") {
        if ($clientes) {
            foreach ($clientes as $codigo_cliente) {
                $datos .= '&clientes[]=' . $codigo_cliente;
            }
        }
        if ($usuarios) {
            foreach ($usuarios as $id_usuario) {
                $datos .= '&id_usuario=' . $id_usuario;
                $query = "SELECT id_usuario, CONCAT_WS(' ',nombre,apellido1,apellido2) AS nombre FROM usuario WHERE id_usuario=$id_usuario LIMIT 1";
                $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
                $temp = mysql_fetch_array($resp);
                $nombre = str_replace(' ', '-', $temp['nombre']);
                $datos .= '&nombre=' . $nombre;
                echo '<br/>';
                echo '<img src="graficos/grafico_trabajos.php?' . $datos . '" alt="" />';
            }
        }
    }
}
$pagina->PrintBottom();
