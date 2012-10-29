<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/Contrato.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

$Sesion = new Sesion(array('REP'));

if(in_array($_REQUEST['opcion'], array('buscar', 'xls'))){

	$codigo_cliente = $_REQUEST['codigo_cliente'];
	$id_contrato = $_REQUEST['id_contrato'];
	$tipo_liquidacion = $_REQUEST['tipo_liquidacion'];

	$where = '';
	$join = '';

	if(!empty($codigo_cliente)){
		$where .= " AND d.codigo_cliente = '$codigo_cliente' ";
	}

	if(!empty($id_contrato)){
		$where .= " AND cobro.id_contrato = '$id_contrato' ";
	}

	if(!empty($tipo_liquidacion)){ //1-2 = honorarios-gastos, 3 = mixtas
		$honorarios = $tipo_liquidacion & 1;
		$gastos = $tipo_liquidacion & 2 ? 1 : 0;

		$join .= ' JOIN contrato ON contrato.id_contrato = cobro.id_contrato ';

		$where .= "
			AND contrato.separar_liquidaciones = '".($tipo_liquidacion=='3' ? 0 : 1)."'
			AND cobro.incluye_honorarios = '$honorarios'
			AND cobro.incluye_gastos = '$gastos' ";
	}

	$query = "SELECT
			T.glosa_cliente,
			T.moneda,
			-SUM(IF(T.dias_atraso_pago BETWEEN 0 AND 30, T.saldo, 0)) AS '0-30',
			-SUM(IF(T.dias_atraso_pago BETWEEN 31 AND 60, T.saldo, 0)) AS '31-60',
			-SUM(IF(T.dias_atraso_pago BETWEEN 61 AND 90, T.saldo, 0)) AS '61-90',
			-SUM(IF(T.dias_atraso_pago > 90, T.saldo, 0)) AS '91+',
			-SUM(T.saldo) AS total,
			GROUP_CONCAT(identificador SEPARATOR ', ') as cobros
		FROM
			(SELECT
				d.fecha,
				d.id_cobro AS identificador,
				'liquidacion' AS tipo,
				d.glosa_documento AS descripcion,
				d.codigo_cliente,
				cliente.glosa_cliente AS glosa_cliente,
				d.monto AS  monto,
				d.monto * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio) AS monto_base,
				-1 * (d.saldo_honorarios + d.saldo_gastos) AS saldo,
				-1 * (d.saldo_honorarios + d.saldo_gastos) * (moneda_documento.tipo_cambio / moneda_base.tipo_cambio) AS saldo_base,
				DATEDIFF(NOW(), cobro.fecha_emision) AS dias_atraso_pago,
				moneda_documento.simbolo AS moneda
			FROM
				documento d
			INNER JOIN prm_moneda moneda_documento ON d.id_moneda = moneda_documento.id_moneda
			INNER JOIN prm_moneda moneda_base ON moneda_base.moneda_base = 1
			JOIN cliente ON d.codigo_cliente = cliente.codigo_cliente
			JOIN cobro ON cobro.id_cobro = d.id_cobro
			$join
			WHERE
				d.tipo_doc = 'N' AND
				(d.saldo_honorarios + d.saldo_gastos) > 0
				$where
			GROUP BY d.id_documento
			) AS T
		GROUP BY T.glosa_cliente, T.moneda
		ORDER BY glosa_cliente";

	$SimpleReport = new SimpleReport($Sesion);
	$config_reporte = array(
		array(
			'field' => 'glosa_cliente',
			'title' => __('Cliente'),
			'extras' => array(
				'attrs' => 'style="text-align:left"',
				'groupinline' => true
			)
		),
                array(
                        'field' => 'cobros',
                        'title' => utf8_encode(__('Cobros')),
                        'extras' => array(
                                'attrs' => 'width="10%" style="text-align:right"'
                        )
                ),
		array(
			'field' => 'cobros',
			'title' => utf8_encode(__('Cobros')),
			'extras' => array(
				'attrs' => 'width="10%" style="text-align:right"',
				'class' => 'cobros'
			)
		),
		array(
			'field' => '0-30',
			'title' => '0-30 ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="16%" style="text-align:right"'
			)
		),
		array(
			'field' => '31-60',
			'title' => '31-60 ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="16%" style="text-align:right"'
			)
		),
		array(
			'field' => '61-90',
			'title' => '61-90 ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="16%" style="text-align:right"'
			)
		),
		array(
			'field' => '91+',
			'title' => '91+ ' . utf8_encode(__('días')),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="16%" style="text-align:right"'
			)
		),
		array(
			'field' => 'total',
			'title' => __('Total'),
			'format' => 'number',
			'extras' => array(
				'subtotal' => 'moneda',
				'symbol' => 'moneda',
				'attrs' => 'width="16%" style="text-align:right;font-weight:bold"'
			)
		)
	);

	$SimpleReport->LoadConfigFromArray($config_reporte);

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
						<tr>
							<td align="right" width="30%">
								<label for="id_contrato"><?php echo __('Asuntos'); ?></label>
							</td>
							<td colspan="3" align="left" id="td_selector_contrato">
								<?php
								$Contrato = new Contrato($Sesion);
								echo $Contrato->ListaSelector($_REQUEST['codigo_cliente'], '', $_REQUEST['id_contrato']);
								?>
							</td>
						</tr>
						<tr>
							<td align=right>
								<label for="tipo_liquidacion"><?php echo __('Tipo de Liquidación')?></label>
							</td>
							<td colspan=2 align=left>
								<?php echo Html::SelectArray(array(
									array('1', __('Sólo Honorarios')),
									array('2', __('Sólo Gastos')),
									array('3', __('Sólo Mixtas (Honorarios y Gastos)'))), 'tipo_liquidacion', $_REQUEST['tipo_liquidacion'], '', __('Todas'))?>
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
<script type="text/javascript">
	var valor_anterior_codigo;
	var campo_cliente;

	function ActualizarContratos(val) {
		var url = root_dir + '/app/ajax.php?accion=cargar_contratos&codigo_cliente=' + val;
		jQuery.ajax({
			url: url,
			success: function (data) {
				jQuery('#td_selector_contrato').html(data);
			}
		});
	}

	function ComprobarCodigos() {
		var valor_nuevo = campo_cliente.val()
		if (valor_anterior_codigo != valor_nuevo) {
			ActualizarContratos(valor_nuevo);
			valor_anterior_codigo = valor_nuevo;
		}
	}

	jQuery(document).ready(function () {
		// Cargar contratos on select
		campo_cliente = jQuery('input[name^="codigo_cliente"], select[name^="codigo_cliente"]');
		valor_anterior_codigo = campo_cliente.val();
		window.setInterval(ComprobarCodigos, 500);

		jQuery('#boton_xls').click(function(){
			jQuery('#opcion').val('xls');
		});
		jQuery('#boton_buscar').click(function(){
			jQuery('#opcion').val('buscar');
		});

		jQuery('.subtotal td').css('font-weight', 'bold');
		
		jQuery('.cobros').each(function(){
			var td = jQuery(this);
			var ids = td.html().split(', ');
			td.html('');
			for(var i=0; i<ids.length; i++){
				td.append(jQuery('<a/>', {
					text: ids[i],
					href: 'javascript:void(0)',
					onclick: "nuovaFinestra('Cobro', 1000, 700,'../cobros6.php?id_cobro="+ids[i]+"&popup=1&contitulo=true&id_foco=2', 'top=100, left=155');"
				})).append(' ');
			}
		});
	});
</script>

<?php
if ($_REQUEST['opcion'] == 'buscar') {
	$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Html');
	echo $writer->save();
}

$Pagina->PrintBottom();
