<?php
require_once dirname(__FILE__) . '/../../conf.php';

require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('REP'));
	

if (in_array($opcion, array('buscar', 'xls'))) {

	$and_statements = array();
	$join_sub_select = new Criteria();
	$sub_query = new Criteria();
	$query = new Criteria();

	if (!empty($codigo_cliente)) {
		$and_statements[] = 'contrato.codigo_cliente = '."$codigo_cliente";
		//
	}

	if (!empty($id_contrato)) {
		$and_statements[] = 'cobro.id_contrato = '."$id_contrato";
		//
	}

	if (!empty($tipo_liquidacion)) {
		$honorarios = $tipo_liquidacion & 1;
		$gastos = $tipo_liquidacion & 2 ? 1 : 0;
		$and_statements[] = 'contrato.separar_liquidaciones = \''.($tipo_liquidacion == '3' ? 0 : 1).'\'';
		$and_statements[] = 'cobro.incluye_honorarios = \''."$honorarios".'\'';
		$and_statements[] = 'cobro.incluye_gastos = \''."$gastos".'\'';
	}

	if ($solo_monto_facturado) {
		$campo_valor = 'T.fsaldo';
		$campo_gvalor = 'T.fgsaldo';
		$campo_hvalor = 'T.fhsaldo';
		$tipo = 'pdl.glosa';
		$label = 'concat(pdl.codigo,\' N° \',  lpad(factura.serie_documento_legal,\'3\',\'0\'),'-',lpad(factura.numero,\'7\',\'0\'))';
		$identificadores = 'facturas';
		$identificador =  'd.id_cobro';
		$linktofile = 'cobros6.php?id_cobro=';
		$and_statements[] = 'ccfm.saldo != 0';
		$sub_query->add_grouping('factura.id_factura');
	}
	else{
		$campo_valor = 'T.saldo';
		$campo_gvalor = 'T.gsaldo';
		$campo_hvalor = 'T.hsaldo';
		$and_statements[] = 'd.tipo_doc = \'N\' AND cobro.estado NOT IN (\'CREADO\', \'EN REVISION\', \'INCOBRABLE\')';
		$and_statements[] = '((d.saldo_honorarios + d.saldo_gastos) > 0)';
		$tipo = '\'liquidacion\'';
		$fecha_atraso = 'cobro.fecha_emision';
		$label = 'd.id_cobro';
		$identificadores = 'cobros';
		$identificador = 'd.id_cobro';
		$linktofile = 'cobros6.php?id_cobro=';
		$sub_query->add_grouping('d.id_documento');
	}

	//Definición de las querys en base a los criterios apropiados.
	//Definición de las querys en base a los criterios apropiados.
	$join_sub_select
				->add_select('codigo_cliente')
				->add_select('COUNT(*)','cantidad')
				->add_select("MAX(CONCAT(fecha_creacion, ' | ', comentario))",'comentario')
				->add_from('cliente_seguimiento')
				->add_grouping('codigo_cliente');

	$sub_query
		->add_select('d.fecha')
		->add_select("$identificador",'identificador')
		->add_select("$tipo",'tipo')
		->add_select('d.glosa_documento','descripcion')
		->add_select("$label",'label')
		->add_select('cliente.codigo_cliente')
		->add_select('cliente.glosa_cliente', 'glosa_cliente')
		->add_select('d.monto', 'monto')
		->add_select('d.monto * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','monto_base')
		->add_select('sum(ccfm.monto_bruto)', 'fmonto')
		->add_select('sum(ccfm.monto_bruto)* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'fmonto_base')
		->add_select('-1 * (d.saldo_honorarios + d.saldo_gastos)','saldo')
		->add_select('-1 * (d.saldo_honorarios + d.saldo_gastos) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'saldo_base')
		->add_select('sum(ccfm.saldo)', 'fsaldo')
		->add_select('sum(ccfm.saldo)* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'fsaldo_base')
		->add_select('-1 * (d.saldo_honorarios)','hsaldo')
		->add_select('-1 * (d.saldo_honorarios ) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))', 'hsaldo_base')
		->add_select('sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0))','fhsaldo')
		->add_select('sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0))* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','fhsaldo_base')
		->add_select('-1 * (d.saldo_gastos)','gsaldo')
		->add_select('-1 * (d.saldo_gastos) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','gsaldo_base')
		->add_select('sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0))', 'fgsaldo')
		->add_select('sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0))* (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio ))','fgsaldo_base')
		->add_select('cobro.fecha_emision')
		->add_select('DATEDIFF(NOW(), cobro.fecha_emision)','dias_atraso_pago')
		->add_select('moneda_documento.simbolo','moneda')
		->add_select('seguimiento.cantidad','cantidad_seguimiento')
		->add_select('seguimiento.comentario','comentario_seguimiento')
		->add_select('CONCAT_WS(\' \',u.nombre,u.apellido1,u.apellido2)','encargado_comercial')
		->add_from('cobro')
		->add_left_join_with('documento d','cobro.id_cobro = d.id_cobro')
		->add_left_join_with('prm_moneda moneda_documento','d.id_moneda = moneda_documento.id_moneda')
		->add_left_join_with('prm_moneda moneda_base','moneda_base.moneda_base = 1')
		->add_left_join_with('factura','factura.id_cobro=cobro.id_cobro')
		->add_left_join_with('cta_cte_fact_mvto ccfm','ccfm.id_factura=factura.id_factura')
		->add_left_join_with('contrato','contrato.id_contrato = cobro.id_contrato')
		->add_left_join_with('usuario u','contrato.id_usuario_responsable = u.id_usuario')
		->add_left_join_with('cliente','contrato.codigo_cliente = cliente.codigo_cliente')
		->add_left_join_with('prm_documento_legal pdl','pdl.id_documento_legal=factura.id_documento_legal')
		->add_left_join_with_criteria($join_sub_select,'seguimiento','cliente.codigo_cliente = seguimiento.codigo_cliente')
		->add_restriction(
			CriteriaRestriction::and_all($and_statements)
		)
		->add_grouping('d.id_documento');

	$query
		->add_select('T.codigo_cliente')
		->add_select('T.glosa_cliente')
		->add_select('T.moneda')
		->add_select('-SUM(IF(T.dias_atraso_pago BETWEEN 0 AND 30,'.$campo_valor.', 0))', '0-30')
		->add_select('-SUM(IF(T.dias_atraso_pago BETWEEN 31 AND 60,'.$campo_valor.', 0))', '31-60')
		->add_select('-SUM(IF(T.dias_atraso_pago BETWEEN 61 AND 90,'.$campo_valor.', 0))', '61-90')
		->add_select('-SUM(IF(T.dias_atraso_pago > 90,'.$campo_valor.', 0))', '91+')
		->add_select('-SUM('.$campo_valor.')', 'total')
		->add_select('-SUM('.$campo_hvalor.')', 'htotal')
		->add_select('-SUM('.$campo_gvalor.')', 'gtotal')
		->add_select("CONCAT('{',group_concat(concat('" . '"' . "',identificador,'" . '":"' . "',label,'" . '"' . "') separator ','), '}')",'identificadores')
		->add_select('T.cantidad_seguimiento')
		->add_select('T.comentario_seguimiento')
		//Dias atraso pago
		->add_select('T.dias_atraso_pago')
		->add_select('T.fecha_emision')
		//Encargado Comercial
		->add_select('T.encargado_comercial')
		->add_from_criteria($sub_query,'T')
		->add_grouping('T.glosa_cliente')
		->add_grouping('T.moneda')
		->add_ordering('glosa_cliente');

	$SimpleReport = new SimpleReport($Sesion);
	$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($Sesion));
	$config_reporte = array(
		array(
			'field' => 'glosa_cliente',
			'title' => __('Cliente'),
			'extras' => array(
				'attrs' => 'width="28%" style="text-align:left;"',
				'groupinline' => true
			)
		),
		array(
			'field' => 'encargado_comercial',
			'title' => 'Encargado Comercial',
			'extras' => array(
				'attrs' => 'width="20%" style="text-align:left;"',
			)
		),
		array(
			'field' => 'fecha_emision',
			'title' => 'Días Vencimiento',
			'extras' => array(
				'attrs' => 'width="20%" style="text-align:center;"',
			)
		),
		array(
			'field' => 'identificadores',
			'title' => __(ucfirst($identificadores)),
			'extras' => array(
				'attrs' => 'width="11%" style="text-align:right;display:none;"', 'class' => 'identificadores'
			)
		),
		array(
			'field' => '0-30',
			'title' => '0-30 ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="10%" style="text-align:right"',
			)
		),
		array(
			'field' => '31-60',
			'title' => '31-60 ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="10%" style="text-align:right"'
			)
		),
		array(
			'field' => '61-90',
			'title' => '61-90 ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="10%" style="text-align:right"'
			)
		),
		array(
			'field' => '91+',
			'title' => '91+ ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="10%" style="text-align:right"'
			)
		),
		array(
			'field' => 'total',
			'title' => __('Total'),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="12%" style="text-align:right;font-weight:bold"'
			)
		)
	);

	// Configuración especial para mostrar el seguimiento del cliente
	if ($opcion != 'xls') {
		$config_reporte[] = array(
			'field' => '=CONCATENATE(%codigo_cliente%,"|",%cantidad_seguimiento%)',
			'title' => '&nbsp;',
			'extras' => array(
				'attrs' => 'width="5%" style="text-align:right"',
				'class' => 'seguimiento'
			)
		);
	} else {
		$config_reporte[] = array(
			'field' => 'comentario_seguimiento',
			'title' => 'Comentario Seguimiento'
		);
	}

	$SimpleReport->LoadConfigFromArray($config_reporte);

	$statement = $Sesion->pdodbh->prepare($query->get_plain_query());
	$statement->execute();
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);
	$SimpleReport->LoadResults($results);

	if ($opcion == 'xls') {
		$new_results = array();
		foreach ($results as $result) {
			// Corregir los identificadores
			$array = json_decode(utf8_encode($result['identificadores']), true);
			$identificadores = array();
			foreach ($array as $key => $value) {
				$identificadores[] = utf8_decode($value);
			}
			$result['identificadores'] = implode(', ', $identificadores);

			// Corregir los comentarios de seguimiento
			$array = explode(' | ', $result['comentario_seguimiento']);
			if (count($array) > 1) {
				$result['comentario_seguimiento'] = Utiles::sql2fecha($array[0], "%d/%m/%Y") . " " . $array[1];
			} else {
				$result['comentario_seguimiento'] = "";
			}

			$new_results[] = $result;
		}
		$SimpleReport->LoadResults($new_results);
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Excel');
		$writer->save('Reporte_antiguedad_deuda');
	}
}

$Pagina = new Pagina($Sesion);

$Pagina->titulo = __('Reporte Antigüedad Deudas Clientes');
$Pagina->PrintTop();
?>
<script type="text/javascript" src="//static.thetimebilling.com/js/bootstrap.min.js"></script>
<table width="90%">
	<tr>
		<td>
			<form method="POST" name="form_reporte_saldo" action="" id="form_reporte_saldo">
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
<?php echo UtilesApp::CampoCliente($Sesion, $_REQUEST['codigo_cliente']); ?>
							</td>
						</tr>
<?php UtilesApp::FiltroAsuntoContrato($Sesion, $codigo_cliente, $codigo_cliente_secundario, $codigo_asunto, $codigo_asunto_secundario, $id_contrato); ?>

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