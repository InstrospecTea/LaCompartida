<?php

$Slim = Slim::getInstance('default', true);


$Slim->hook('hook_factura_fin', 'Ofrece_Planilla_Resumen_Facturacion');
$Slim->hook('hook_factura_inicio', 'Descarga_Planilla_Resumen_Facturacion');

//$Slim->hook('hook_factura_descarga', 'Descarga_Planilla_Registro_Ventas');


function Ofrece_Planilla_Resumen_Facturacion() {
	$text.='<a class="btn botonizame" icon="ui-icon-invoice"    id="boton_resumen_ventas" name="boton_resumen_ventas" 
					onclick="jQuery(\'#form_facturas\').attr(\'action\',\'facturas.php?opc=buscar&descargar_excel=1&planilla=resumen_ventas\').submit();">' . __('Resumen Ventas') . '</a>';
	echo $text;
}

function Descarga_Planilla_Resumen_Facturacion() {
	global $sesion, $factura, $orden, $where, $numero, $fecha1, $fecha2, $codigo_cliente_secundario,
	$tipo_documento_legal_buscado, $codigo_cliente, $codigo_asunto, $id_contrato, $id_cia,
	$id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable;

	if ($_GET['planilla']) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';
		$SimpleReport = new SimpleReport($sesion);
		$results = $factura->DatosReporte($orden, $where, $numero, $fecha1, $fecha2, $codigo_cliente_secundario, $tipo_documento_legal_buscado, $codigo_cliente, $codigo_asunto, $id_contrato, $id_cia, $id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable);
	}
	if ($_GET['planilla'] == 'resumen_ventas') {

		$cofiguracion_resumen = array(
			array(
				'field' => 'tipo',
				'title' => 'T/DOC',
			),
			array(
				'field' => 'serie',
				'title' => 'Serie',
				'format' => 'text',
			),
			array(
				'field' => 'estado',
				'title' => 'ESTADO',
			),
		

			array('field' => 'neto_1', 'format' => 'number', 'title' => 'NETO S/.',),
			array('field' => 'iva_1', 'format' => 'number', 'title' => 'IGV S/.',),
			array('field' => 'neto_2', 'format' => 'number', 'title' => 'NETO US$',),
			array('field' => 'iva_2', 'format' => 'number', 'title' => 'IGV US$',),
			array('field' => 'neto_3', 'format' => 'number', 'title' => 'NETO EUR',),
			array('field' => 'iva_3', 'format' => 'number', 'title' => 'IGV EUR',),
		);
		$SimpleReport->LoadConfigFromArray($cofiguracion_resumen);
		$resumen = array();
		foreach ($results as $key => $result) {

			$results[$key]['estado'] = strtoupper($results[$key]['estado']);
			$llave = $results[$key]['tipo'] . '_' . $results[$key]['serie_documento_legal'] . '_' . $results[$key]['estado'];
			$results[$key]['neto'] = $results[$key]['honorarios'] + $results[$key]['subtotal_gastos'] + $results[$key]['subtotal_gastos_sin_impuesto'];
			$resumen[$llave]['tipo'] = $results[$key]['tipo'];
			$resumen[$llave]['serie'] = sprintf("%03d", $results[$key]['serie_documento_legal']);
			$resumen[$llave]['estado'] = $results[$key]['estado'];

			
			//$resumen[$llave]['totalreal_' . $results[$key]['id_moneda']]+=$results[$key]['monto_real'] * ($results[$key]['tipo'] == 'NC' ? 0 : 1); //$results[$key]['monto_real'];
			$resumen[$llave]['iva_' . $results[$key]['id_moneda']]+=$results[$key]['iva'] * ($results[$key]['tipo'] == 'NC' ? -1 : 1);
			$resumen[$llave]['neto_' . $results[$key]['id_moneda']]+=$results[$key]['neto'] * ($results[$key]['tipo'] == 'NC' ? -1 : 1);
			//$resumen[$llave]['total_' . $results[$key]['id_moneda']]+=$results[$key]['total'] * ($results[$key]['tipo'] == 'NC' ? -1 : 1); //$results[$key]['monto_real'];
		}
		/* 	echo '<pre>';
		  print_r($resumen);
		  echo '</pre>';
		  die(); */

		function ordenaresumen($filaA, $filaB) {
			if ($filaA['serie'] == $filaB['serie']) {

				if ($filaA['tipo'] == $filaB['tipo']) {
					/* if($filaA['moneda']==$filaB['moneda']) {
					  return ($filaA['estado'] > $filaB['estado']) ? +1 : -1;
					  } else { */
					return strcmp($filaA['moneda'], $filaB['moneda']);
					//}
				} else {
					return ($filaA['tipo'] > $filaB['tipo']) ? +1 : -1;
				}
			} else {
				return strcmp($filaA['serie'], $filaB['serie']);
			}
		}

		usort($resumen, "ordenaresumen");
		$SimpleReport->LoadResults($resumen);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Excel');
		$writer->save(__('Facturas'));
	} 
}

