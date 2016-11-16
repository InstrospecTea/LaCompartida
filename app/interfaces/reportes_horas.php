<?php
require_once dirname(__FILE__) . '/../conf.php';

$sesion = new Sesion(array('REP'));
//Revisa el Conf si esta permitido

$pagina = new Pagina($sesion);
$fecha_desde = $fecha_anio_desde . '-' . sprintf('%02d', $fecha_mes_desde) . '-01';
$fecha_hasta = $fecha_anio_hasta . '-' . sprintf('%02d', $fecha_mes_hasta) . '-31';
$Form = new Form($sesion);

$pagina->titulo = __('Reporte Gráfico por Período');
$pagina->PrintTop();
echo $Form->Html->script(Conf::RootDir() . '/app/layers/assets/js/graphic.js');
?>
<script type="text/javascript">

(function($) {
	$(document).ready(function() {
		$("#genera_reporte").on("click", function() {
			var tipo_reporte = $("#tipo_reporte").val();
			var url = 'graficos/grafico_trabajos.php';
			var base_data = {
				'usuarios': $('#usuarios').val(),
				'clientes': $('#clientes').val(),
				'tipo_reporte': tipo_reporte,
				'tipo_duracion': $('#tipo_duracion').val(),
				'comparar': $('#comparar:checked').val(),
				'tipo_duracion_comparada': $('#tipo_duracion_comparada').val(),
				'fecha_desde': $('#fecha_anio_desde').val() + '-' + $('#fecha_mes_desde').val() + '-01',
				'fecha_hasta': $('#fecha_anio_hasta').val() + '-' + $('#fecha_mes_hasta').val() + '-31'
			};

			var charts_data = [];

			if(tipo_reporte == 'trabajos_por_empleado') {
				if (!$('#usuarios option:selected').length) {
					alert('Debe seleccionar un profesional.');
					return false;
				}

				$("#usuarios option:selected").each(function() {
					var data = jQuery.extend({}, base_data);
					data.id_usuario = $(this).val();
					charts_data.push({
						'url': url,
						'data': data
					});
				});
			} else if(tipo_reporte == 'trabajos_por_cliente') {
				if (!$('#clientes option:selected').length) {
					alert('Debe seleccionar un cliente.');
					return false;
				}

				$("#clientes option:selected").each(function() {
					var data = jQuery.extend({}, base_data);
					data.codigo_cliente = $(this).val();
					charts_data.push({
						'url': url,
						'data': data
					});
				});
			} else if(tipo_reporte == 'trabajos_por_estudio') {
				charts_data.push({
					'url': url,
					'data': base_data
				});
			}

			graphic.render('#contenedor_graficos', charts_data);

		});

		var disable_selector = function() {
			if( $('#comparar').is(':checked')) {
				$("#tipo_duracion_comparada").prop('disabled', false);
			} else {
				$("#tipo_duracion_comparada").prop('disabled', 'disabled');
			}
		};

		$(disable_selector);
		$("#comparar").change(disable_selector);
	});
})(jQuery);

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

    .contenedorCanvas {
			padding-top: 10px;
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
            <td align="left"><?php echo __('Periodo') ?></td>
        </tr>

        <tr>
            <td align=right>
                <?php echo __('Desde') ?>
            </td>
            <td align=left>
                <?php
                $fecha_mes_desde = $fecha_mes_desde != '' ? $fecha_mes_desde : date('m') - 1;
                if ($fecha_mes_desde == 0) {
									$fecha_mes_desde = 12;
									$fecha_anio_desde = date('Y') - 1;
                }
                ?>
                <select name="fecha_mes_desde" style='width:100px' id="fecha_mes_desde">
                    <option value='01' <?php echo $fecha_mes_desde == '01' ? 'selected' : '' ?>><?php echo __('Enero') ?></option>
                    <option value='02' <?php echo $fecha_mes_desde == '02' ? 'selected' : '' ?>><?php echo __('Febrero') ?></option>
                    <option value='03' <?php echo $fecha_mes_desde == '03' ? 'selected' : '' ?>><?php echo __('Marzo') ?></option>
                    <option value='04' <?php echo $fecha_mes_desde == '04' ? 'selected' : '' ?>><?php echo __('Abril') ?></option>
                    <option value='05' <?php echo $fecha_mes_desde == '05' ? 'selected' : '' ?>><?php echo __('Mayo') ?></option>
                    <option value='06' <?php echo $fecha_mes_desde == '06' ? 'selected' : '' ?>><?php echo __('Junio') ?></option>
                    <option value='07' <?php echo $fecha_mes_desde == '07' ? 'selected' : '' ?>><?php echo __('Julio') ?></option>
                    <option value='08' <?php echo $fecha_mes_desde == '08' ? 'selected' : '' ?>><?php echo __('Agosto') ?></option>
                    <option value='09' <?php echo $fecha_mes_desde == '09' ? 'selected' : '' ?>><?php echo __('Septiembre') ?></option>
                    <option value='10' <?php echo $fecha_mes_desde == '10' ? 'selected' : '' ?>><?php echo __('Octubre') ?></option>
                    <option value='11' <?php echo $fecha_mes_desde == '11' ? 'selected' : '' ?>><?php echo __('Noviembre') ?></option>
                    <option value='12' <?php echo $fecha_mes_desde == '12' ? 'selected' : '' ?>><?php echo __('Diciembre') ?></option>
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
                <?php echo __('Hasta') ?>
            </td>
            <td align=left>
                <?php
                $fecha_mes_hasta = $fecha_mes_hasta != '' ? $fecha_mes_hasta : date('m');
                ?>
                <select name="fecha_mes_hasta" style='width:100px' id="fecha_mes_hasta">
                    <option value='01' <?php echo $fecha_mes_hasta == '01' ? 'selected' : '' ?>><?php echo __('Enero') ?></option>
                    <option value='02' <?php echo $fecha_mes_hasta == '02' ? 'selected' : '' ?>><?php echo __('Febrero') ?></option>
                    <option value='03' <?php echo $fecha_mes_hasta == '03' ? 'selected' : '' ?>><?php echo __('Marzo') ?></option>
                    <option value='04' <?php echo $fecha_mes_hasta == '04' ? 'selected' : '' ?>><?php echo __('Abril') ?></option>
                    <option value='05' <?php echo $fecha_mes_hasta == '05' ? 'selected' : '' ?>><?php echo __('Mayo') ?></option>
                    <option value='06' <?php echo $fecha_mes_hasta == '06' ? 'selected' : '' ?>><?php echo __('Junio') ?></option>
                    <option value='07' <?php echo $fecha_mes_hasta == '07' ? 'selected' : '' ?>><?php echo __('Julio') ?></option>
                    <option value='08' <?php echo $fecha_mes_hasta == '08' ? 'selected' : '' ?>><?php echo __('Agosto') ?></option>
                    <option value='09' <?php echo $fecha_mes_hasta == '09' ? 'selected' : '' ?>><?php echo __('Septiembre') ?></option>
                    <option value='10' <?php echo $fecha_mes_hasta == '10' ? 'selected' : '' ?>><?php echo __('Octubre') ?></option>
                    <option value='11' <?php echo $fecha_mes_hasta == '11' ? 'selected' : '' ?>><?php echo __('Noviembre') ?></option>
                    <option value='12' <?php echo $fecha_mes_hasta == '12' ? 'selected' : '' ?>><?php echo __('Diciembre') ?></option>
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
                <?php echo __('Cliente') ?>
            </td>
            <td align="left">
                <?php echo Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes", $clientes, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo __('Profesionales') ?>
            </td>
            <td align="left">
                <!-- Nuevo Select -->
                <?php echo $Form->select('usuarios', $sesion->usuario->ListarActivos('', 'PRO'), $usuarios, array('empty' => FALSE, 'style' => 'width: 200px', 'class' => 'selectMultiple','multiple' => 'multiple','size' => '6')); ?>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo __('Tipo de reporte') ?>
            </td>
            <td align="left">
                <select id="tipo_reporte" name="tipo_reporte" style="width:200px">
                    <option <?php echo $tipo_reporte == "trabajos_por_estudio" ? "selected" : "" ?> value="trabajos_por_estudio"><?php echo __('Simple') ?></option>
                    <option <?php echo $tipo_reporte == "trabajos_por_empleado" ? "selected" : "" ?> value="trabajos_por_empleado"><?php echo __('Desglose profesional') ?></option>
                    <option <?php echo $tipo_reporte == "trabajos_por_cliente" ? "selected" : "" ?> value="trabajos_por_cliente"><?php echo __('Desglose cliente') ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo __('Dato') ?><br/>
            </td>
            <td align="left">
                <select id="tipo_duracion" name="tipo_duracion" style="width:200px">
                    <option <?php echo $tipo_duracion == "trabajada" ? "selected" : "" ?> value="trabajada"><?php echo __('Trabajadas') ?></option>
                    <option <?php echo $tipo_duracion == "cobrable" ? "selected" : "" ?> value="cobrable"><?php echo __('Cobrables') ?></option>
                    <option <?php echo $tipo_duracion == "no_cobrable" ? "selected" : "" ?> value="no_cobrable"><?php echo __('No Cobrables') ?></option>
                    <option <?php echo $tipo_duracion == "cobrada" ? "selected" : "" ?> value="cobrada"><?php echo __('Cobradas') ?></option>

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
                <?php echo __('Con') ?><br/>
            </td>
            <td align="left">
                <select id="tipo_duracion_comparada" name="tipo_duracion_comparada" style="width:200px;" >
                    <option <?php echo $tipo_duracion_comparada == "trabajada" ? "selected" : "" ?> value="trabajada"><?php echo __('Trabajadas') ?></option>
                    <option <?php echo $tipo_duracion_comparada == "cobrable" ? "selected" : "" ?> value="cobrable"><?php echo __('Cobrables') ?></option>
                    <option <?php echo $tipo_duracion_comparada == "no_cobrable" ? "selected" : "" ?> value="no_cobrable"><?php echo __('No Cobrables') ?></option>
                    <option <?php echo $tipo_duracion_comparada == "cobrada" ? "selected" : "" ?> value="cobrada"><?php echo __('Cobradas') ?></option>

                </select>
            </td>
        </tr>

        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

        <tr>
            <td colspan="4" align="center">
                <input type="button" class="btn" id="genera_reporte" value="<?php echo __('Generar Gráfico') ?>" >
            </td>
        </tr>

        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>

    </table>
</form>
<div id="contenedor_graficos"></div>
<?php $pagina->PrintBottom(); ?>
