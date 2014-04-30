<?php
require_once dirname(__FILE__) . '/../../conf.php';

require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$sesion = new sesion(array('REP'));

$query_usuario = "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2,',',nombre) as nombre FROM usuario
			JOIN usuario_permiso USING(id_usuario) WHERE codigo_permiso='SOC' ORDER BY nombre";
	
if (in_array($opcion, array('buscar', 'xls'))) {

	if($solo_monto_facturado){
		$linktofile = 'cobros6.php?id_cobro=';
	}
	else{
		$linktofile = 'cobros6.php?id_cobro=';
	}

	$opciones = array(
			'solo_monto_facturado' => $solo_monto_facturado,
			'mostrar_detalle' => $mostrar_detalle,
			'encargado_comercial' => $encargado_comercial,
			'opcion_usuario' => $opcion,
			'totales_especiales' => $totales_especiales
		);

	$datos = array(
			'codigo_cliente' => $codigo_cliente,
			'id_contrato' => $id_contrato,
			'tipo_liquidacion' => $tipo_liquidacion,
			'codigo_asunto' => $codigo_asunto,
			'encargado_comercial' => $id_encargado_comercial
		);

	$reporte = new ReporteAntiguedadDeudas($sesion, $opciones, $datos);
	
	

	$SimpleReport = $reporte->generar();

}

$Pagina = new Pagina($sesion);
$Pagina->titulo = __('Reporte Antigüedad Deudas Clientes');
$Pagina->PrintTop();

?>
<script type="text/javascript" src="//static.thetimebilling.com/js/bootstrap.min.js"></script>
<table width="90%">
	<tr>
		<td>
			<form method="POST" name="form_reporte_saldo" action="planilla_deudas.php" id="form_reporte_saldo">
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
							<td align="right" width="30%">
								<label for="codigo_cliente"><?php echo __('Cliente'); ?></label>
							</td>
							<td colspan="3" align="left">
<?php echo UtilesApp::CampoCliente($sesion, $_REQUEST['codigo_cliente']); ?>
							</td>
						</tr>
<?php UtilesApp::FiltroAsuntoContrato($sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, $id_contrato); ?>

						<tr>
							<td align="right" width="30%">
								<?php echo __('Encargado Comercial') ?>
							</td>
							<td align='left' colspan="2">
								<?php echo  Html::SelectQuery($sesion, $query_usuario, "id_encargado_comercial", $id_encargado_comercial, "", "Ninguno","width=100px")  ?>
							</td>
						</tr>

						<tr>
							<td align="right" width="30%">
								<label for="filtro_facturado"><?php echo __('Considerar sólo monto facturado'); ?></label>
							</td>
							<td colspan="3" align="left"  >
								<input type="checkbox" id="solo_monto_facturado" name="solo_monto_facturado"  value="1" <?php echo $solo_monto_facturado ? 'checked' : '' ?>/>
								<div class="inlinehelp" title="Sólo monto facturado" style="cursor: help;vertical-align:middle;padding:2px;margin: -5px 1px 2px;display:inline-block;font-weight:bold;color:#999;" help="El reporte por defecto considera el saldo liquidado de cada liquidación. Active este campo para considerar sólo el saldo facturado.">?</div>
							</td>
						</tr>
						<tr>
							<td align="right" width="30%">
								<label for="filtro_facturado">Incluir totales normales y vencidos</label>
							</td>
							<td colspan="3" align="left"  >
								<input type="checkbox" id="totales_especiales" name="totales_especiales"  value="1" <?php echo $totales_especiales ? 'checked' : '' ?>/>
								<div class="inlinehelp" title="Totales normales y vencidos" style="cursor: help;vertical-align:middle;padding:2px;margin: -5px 1px 2px;display:inline-block;font-weight:bold;color:#999;" help="Incluir en el reporte el cálculo de montos totales, tanto normales como vencidos.">?</div>
							</td>
						</tr>
						<tr>
							<td align="right" width="30%">
								<!-- TODO : LANG!! -->
								<label for="filtro_facturado">Desglosar reporte</label>
							</td>
							<td colspan="3" align="left">
								<input type="checkbox" id="mostrar_detalle" name="mostrar_detalle" value="1" <?php echo $mostrar_detalle ? 'checked' : '' ?>>
								<div class="inlinehelp" title="Desglosar reporte" style="cursor: help;vertical-align:middle;padding:2px;margin: -5px 1px 2px;display:inline-block;font-weight:bold;color:#999;" help="El reporte por defecto solo muestra los totales agrupados para cada resultado que se obtiene. Active esta opción para mostrar el detalle de cada agrupación de totales.">?</div>
							</td>
						</tr>
						<tr>
							<td align="right" width="30%">
								<!-- TODO : LANG!! -->
								<label for="filtro_facturado">Incluir encargado comercial</label>
							</td>
							<td colspan="3" align="left">
								<input type="checkbox" id="encargado_comercial" name="encargado_comercial" value="1" <?php echo $encargado_comercial ? 'checked' : '' ?>>
								<div class="inlinehelp" title="Incluir encargado comercial" style="cursor: help;vertical-align:middle;padding:2px;margin: -5px 1px 2px;display:inline-block;font-weight:bold;color:#999;" help="Incluye en el reporte información respecto del encargado comercial asociado a los clientes.">?</div>
							</td>
						</tr>
						<tr>
							<td align=right>
								<label for="tipo_liquidacion"><?php echo __('Tipo de Liquidación') ?></label>
							</td>
							<td colspan=2 align=left>
<?php
echo Html::SelectArray(array(
		array('1', __('Sólo Honorarios')),
		array('2', __('Sólo Gastos')),
		array('3', __('Sólo Mixtas (Honorarios y Gastos)'))), 'tipo_liquidacion', $_REQUEST['tipo_liquidacion'], '', __('Todas'))
?>
							</td>
						</tr>
						<tr>
							<td></td>
							<td align=left>
								<input name="boton_buscar" id="boton_buscar" type="submit" value="<?php echo __('Buscar') ?>" class="btn" />
							</td>
							<td width='40%' align="right">
								<input name="boton_xls" id="boton_xls" type="submit" value="<?php echo __('Descargar Excel') ?>" class="btn" />
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</td>
	</tr>
</table>
<div id="seguimiento_template">
	<div class="popover left">
		<div class="arrow"></div>
		<h3 class="popover-title">Seguimiento del cliente</h3>
		<div class="popover-content">
			<iframe id="seguimiento_iframe" width="100%" border="0" style="border: 1px solid white" src="../ajax/ajax_seguimiento.php"></iframe>
		</div>
	</div>
</div>
<script type="text/javascript">
<?php echo "var linktofile= '$linktofile';"; ?>
	// var current_popover;
	// show_popover = function(sender) {
	// 	codigo_cliente = sender.data('codigo_cliente');

	// 	if (current_popover != undefined) {
	// 		codigo_cliente_old = current_popover.data('codigo_cliente');
	// 		if (codigo_cliente != codigo_cliente_old) {
	// 			current_popover.popover('hide');
	// 		} else {
	// 			sender.popover('hide');
	// 		}
	// 	}
	// 	current_popover = sender;
	// 	current_popover.popover({
	// 		title: '<?php echo __('Seguimiento del cliente'); ?>',
	// 		trigger: 'manual',
	// 		placement: 'left',
	// 		html: true,
	// 		content: '<iframe width="100%" border="0" style="border: 1px solid white" src="../ajax/ajax_seguimiento.php?codigo_cliente=' + codigo_cliente + '" />'
	// 	});

	// 	current_popover.popover('show')
	// };
	show_popover = function(sender) {
		codigo_cliente = sender.data('codigo_cliente');

		tpl = jQuery('#seguimiento_template').find('.popover');

		jQuery('#seguimiento_iframe').attr('src', '../ajax/ajax_seguimiento.php?codigo_cliente=' + codigo_cliente);

		sender_pos = sender.position();
		tpl.css('display', 'block');
		tpl.css('left', sender_pos.left - 414);
		tpl.css('top', sender_pos.top - 106);
		tpl.parent().show();
	};

	jQuery(document).ready(function() {
		var seguimiento_template = jQuery('#seguimiento_template');
		jQuery('.inlinehelp').each(function() {
			jQuery(this).popover({title: jQuery(this).attr('title'), trigger: 'hover', animation: true, content: jQuery(this).attr('help')});
		});
		jQuery('#boton_xls').click(function() {
			jQuery('#opcion').val('xls');
		});
		jQuery('#boton_buscar').click(function() {
			jQuery('#opcion').val('buscar');
		});

		jQuery('.subreport tr.encabezado td.encabezado').css('background-color','#ddd');
		jQuery('.subreport tr.encabezado td.encabezado').css('color','#040');

		jQuery('.subtotal td').css('font-weight', 'bold');
		jQuery('.encabezado').show();
		jQuery('.identificadores').show().each(function() {
			var td = jQuery(this);
			var contenido = td.html();
			td.html('');
			jQuery.each(jQuery.parseJSON(contenido), function(id, label) {
				td.append(jQuery('<a/>', {
					text: label,
					style: 'white-space:nowrap;',
					href: 'javascript:void(0)',
					onclick: "nuovaFinestra('Cobro', 1000, 700,'../" + linktofile + id + "&popup=1&contitulo=true&id_foco=2', 'top=100, left=155');"
				})).append(' ');
			});
		});

		jQuery('.total_normal').each(function(){
			var td = jQuery(this);
			td.css('color','blue');
		})

		jQuery('.total_vencido').each(function(){
			var td = jQuery(this);
			td.css('color','red');
		})

		jQuery('.seguimiento').each(function () {
			var td = jQuery(this);
			var contenido = td.html();
			if (contenido.trim() != '') {
				partes = contenido.split('|');
				codigo_cliente = partes[0];
				cantidad_seguimiento = partes[1];
				var link = jQuery('<a/>', { text: '', href: 'javascript:void(0)' });
				icono = 'tarea_inactiva.gif';
				if (parseInt(cantidad_seguimiento) > 0) {
					icono = 'tarea.gif';
				}
				link.append(jQuery('<img/>', { src: '<?php echo Conf::ImgDir(); ?>/' + icono }));
				td.html('');
				link.data('codigo_cliente', codigo_cliente);
				link.click(function (e) {
					e.preventDefault();
					e.stopPropagation();

					show_popover(jQuery(this));
				});
				td.append(link).append(' ');
			}
		});

		jQuery('body').click(function () {
			// current_popover.popover('hide');
			jQuery('#seguimiento_template').hide();
		});
	});
</script>

<?php
if ($_REQUEST['opcion'] == 'buscar') {
	$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
	echo $writer->save();
}

$Pagina->PrintBottom();
