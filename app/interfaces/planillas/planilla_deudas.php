<?php
require_once dirname(__FILE__) . '/../../conf.php';

require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('REP'));

if (in_array($_REQUEST['opcion'], array('buscar', 'xls'))) {

	$codigo_cliente = $_REQUEST['codigo_cliente'];
	$id_contrato = $_REQUEST['id_contrato'];
	$tipo_liquidacion = $_REQUEST['tipo_liquidacion'];

	$where = '';
	$join = '';

	if (!empty($codigo_cliente)) {
		$where .= " AND d.codigo_cliente = '$codigo_cliente' ";
	}

	if (!empty($id_contrato)) {
		$where .= " AND cobro.id_contrato = '$id_contrato' ";
	}

	if (!empty($tipo_liquidacion)) { //1-2 = honorarios-gastos, 3 = mixtas
		$honorarios = $tipo_liquidacion & 1;
		$gastos = $tipo_liquidacion & 2 ? 1 : 0;
		$where .= "
			AND contrato.separar_liquidaciones = '" . ($tipo_liquidacion == '3' ? 0 : 1) . "'
			AND cobro.incluye_honorarios = '$honorarios'
			AND cobro.incluye_gastos = '$gastos' ";
	}

	if ($_POST['solo_monto_facturado']) {
		$campo_valor = "T.fsaldo";
		$campo_gvalor = "T.fgsaldo";
		$campo_hvalor = "T.fhsaldo";
		$where.="AND  ccfm.saldo!=0 ";
		$groupby.=" factura.id_factura";
		$tipo = " pdl.glosa";
		$identificador = " factura.id_factura";
		$fecha_atraso = " factura.fecha";
		$label = " concat(pdl.codigo,' N° ',  lpad(factura.serie_documento_legal,'3','0'),'-',lpad(factura.numero,'7','0')) ";
		$identificadores = 'facturas';
		$linktofile = 'agregar_factura.php?id_factura=';
	} else {
		$campo_valor = "T.saldo";
		$campo_gvalor = "T.gsaldo";
		$campo_hvalor = "T.hsaldo";
		$where.="AND ((d.saldo_honorarios + d.saldo_gastos)>0 ) ";
		$groupby.=" d.id_documento";
		$tipo = " 'liquidacion'";
		$identificador = " d.id_cobro";
		$fecha_atraso = " cobro.fecha_emision";
		$label = " d.id_cobro ";
		$identificadores = 'cobros';
		$linktofile = 'cobros6.php?id_cobro=';
	}

	$query = "SELECT
			T.codigo_cliente,
			T.glosa_cliente,
			T.moneda,
			-SUM(IF(T.dias_atraso_pago BETWEEN 0 AND 30, $campo_valor, 0)) AS '0-30',
			-SUM(IF(T.dias_atraso_pago BETWEEN 31 AND 60, $campo_valor, 0)) AS '31-60',
			-SUM(IF(T.dias_atraso_pago BETWEEN 61 AND 90, $campo_valor, 0)) AS '61-90',
			-SUM(IF(T.dias_atraso_pago > 90, $campo_valor, 0)) AS '91+',
			-SUM($campo_valor) AS total,
			-SUM($campo_hvalor) AS htotal,
			-SUM($campo_gvalor) AS gtotal,
			CONCAT('{',group_concat(concat('" . '"' . "',identificador,'" . '":"' . "',label,'" . '"' . "') separator ','), '}')  as identificadores,
			T.cantidad_seguimiento
		FROM
			(SELECT 	";
	/* $query .= " if(cobro.incluye_honorarios=1 and cobro.incluye_gastos=0 , 'H',
	  if(cobro.incluye_honorarios=0 and cobro.incluye_gastos=1 , 'G','M')

	  ) as tipo_cobro," */
	$query .= "		d.fecha,
				$identificador AS identificador,
				$tipo AS tipo,
				d.glosa_documento  AS descripcion,
				$label as label,
				cliente.codigo_cliente,
				cliente.glosa_cliente AS glosa_cliente,
				d.monto AS  monto,
				d.monto *  (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS monto_base, 
				sum(ccfm.monto_bruto) AS fmonto, 
				sum(ccfm.monto_bruto)*  (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS fmonto_base, 
				-1 * (d.saldo_honorarios + d.saldo_gastos) AS saldo,
				-1 * (d.saldo_honorarios + d.saldo_gastos) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS saldo_base,
				sum(ccfm.saldo) AS fsaldo, 
				sum(ccfm.saldo)*  (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS fsaldo_base, 
				

				-1 * (d.saldo_honorarios) AS hsaldo,
				-1 * (d.saldo_honorarios ) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS hsaldo_base,
				sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0)) AS fhsaldo, 
				sum(if(cobro.incluye_honorarios=1,ccfm.saldo,0))*  (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS fhsaldo_base, 
				
				-1 * (d.saldo_gastos) AS gsaldo,
				-1 * (d.saldo_gastos) * (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS gsaldo_base,
				sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0)) AS fgsaldo, 
				sum(if(cobro.incluye_honorarios=0,ccfm.saldo,0))*  (if(moneda_documento.id_moneda=moneda_base.id_moneda,1, moneda_documento.tipo_cambio / moneda_base.tipo_cambio )) AS fgsaldo_base, 

				DATEDIFF(NOW(), $fecha_atraso) AS dias_atraso_pago,
				moneda_documento.simbolo AS moneda,
				seguimiento.cantidad AS cantidad_seguimiento
			FROM
				documento d
			left JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
			left JOIN prm_moneda moneda_base ON moneda_base.moneda_base = 1
			left JOIN cliente ON d.codigo_cliente = cliente.codigo_cliente
			left JOIN cobro ON cobro.id_cobro = d.id_cobro
			left JOIN factura  on factura.id_cobro=d.id_cobro
			left JOIN cta_cte_fact_mvto ccfm on ccfm.id_factura=factura.id_factura
			left JOIN contrato ON contrato.id_contrato = cobro.id_contrato
			left join prm_documento_legal pdl on pdl.id_documento_legal=factura.id_documento_legal
			LEFT JOIN (
				SELECT codigo_cliente, COUNT(*) AS cantidad
				FROM cliente_seguimiento
				GROUP BY codigo_cliente
			) seguimiento ON cliente.codigo_cliente = seguimiento.codigo_cliente

			$join
			WHERE
				d.tipo_doc = 'N' AND
				cobro.estado NOT IN ('CREADO', 'EN REVISION', 'INCOBRABLE')
				$where
			GROUP BY $groupby
			) AS T
		GROUP BY T.glosa_cliente, T.moneda
		ORDER BY glosa_cliente";

//echo $query;
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
			/* ,
					  array(
					  'field' => 'htotal',
					  'title' => __('Total Honorarios'),
					  'format' => 'number',
					  'extras' => array(
					  'subtotal' => 'moneda',
					  'symbol' => 'moneda',
					  'attrs' => 'width="16%" style="text-align:right;font-weight:bold"'
					  )
					  ),
					  array(
					  'field' => 'gtotal',
					  'title' => __('Total Gastos'),
					  'format' => 'number',
					  'extras' => array(
					  'subtotal' => 'moneda',
					  'symbol' => 'moneda',
					  'attrs' => 'width="16%" style="text-align:right;font-weight:bold"'
					  )
					  ) */
	);

	// Configuración especial para mostrar el seguimiento del cliente
	if ($_REQUEST['opcion'] != 'xls') {
		$config_reporte[] = array(
			'field' => '=CONCATENATE(%codigo_cliente%,"|",%cantidad_seguimiento%)',
			'title' => '&nbsp;',
			'extras' => array(
				'attrs' => 'width="5%" style="text-align:right"',
				'class' => 'seguimiento'
			)
		);
	}

	$SimpleReport->LoadConfigFromArray($config_reporte);

	//echo $query; exit();

	$statement = $Sesion->pdodbh->prepare($query);
	$statement->execute();
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);
	$SimpleReport->LoadResults($results);

	if ($_REQUEST['opcion'] == 'xls') {
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Excel');
		$writer->save('Reporte_antiguedad_deuda');
	}
}

$Pagina = new Pagina($Sesion);

$Pagina->titulo = __('Reporte Antigüedad Deudas Clientes');
$Pagina->PrintTop();
?>
<link rel="stylesheet" type="text/css" href="//static.thetimebilling.com/css/bootstrap-popover.css"/>
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

</div>
<script type="text/javascript">
<?php echo "var linktofile= '$linktofile';"; ?>
	show_popover = function(sender, codigo_cliente) {
		sender.popover({
			title: '<?php echo __('Seguimiento del cliente'); ?>',
			trigger: 'click',
			placement: 'left',
			html: true,
			content: '<iframe width="100%" border="0" style="border: 1px solid white" src="../ajax/ajax_seguimiento.php?codigo_cliente=' + codigo_cliente + '" />'
		});
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
				td.append(link).append(' ');
			}
			show_popover(td, codigo_cliente);
		});
	});
</script>

<?php
if ($_REQUEST['opcion'] == 'buscar') {
	$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
	echo $writer->save();
}

$Pagina->PrintBottom();
