<?php

$Slim = Slim::getInstance('default', true);

$Slim->hook('hook_factura_fin', 'Ofrece_Planilla_Resumen_Facturacion');
$Slim->hook('hook_factura_inicio', 'Descarga_Planilla_Resumen_Facturacion');

//$Slim->hook('hook_factura_descarga', 'Descarga_Planilla_Registro_Ventas');

function Ofrece_Planilla_Resumen_Facturacion() {
	$text .= '<a class="btn botonizame" icon="ui-icon-invoice"    id="boton_resumen_ventas" name="boton_resumen_ventas"
					onclick="jQuery(\'#form_facturas\').attr(\'action\',\'facturas.php?opc=buscar&descargar_excel=1&planilla=resumen_ventas\').submit();">' . __('Resumen Ventas') . '</a>';
	echo $text;
}

function Descarga_Planilla_Resumen_Facturacion() {
	global $sesion, $factura, $orden, $where, $numero, $fecha1, $fecha2, $codigo_cliente_secundario,
	$tipo_documento_legal_buscado, $codigo_cliente, $codigo_asunto, $id_contrato, $id_estudio,
	$id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable;

	if ($_GET['planilla']) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';
		$SimpleReport = new SimpleReport($sesion);
		$results = $factura->DatosReporte($orden, $where, $numero, $fecha1, $fecha2
		,$tipo_documento_legal_buscado
		, $codigo_cliente,$codigo_cliente_secundario
		, $codigo_asunto,$codigo_asunto_secundario
		, $id_contrato, $id_estudio,
		$id_cobro, $id_estado, $id_moneda, $grupo_ventas, $razon_social, $descripcion_factura, $serie, $desde_asiento_contable);
	}

	if ($_GET['planilla'] == 'resumen_ventas') {
		$cofiguracion_resumen = array(
			array(
				'field' => 'tipo',
				'title' => 'T/DOC',
			));

		if (Conf::GetConf($sesion, 'NumeroFacturaConSerie')) {
			array_push($cofiguracion_resumen, array(
				'field' => 'serie',
				'title' => 'Serie',
				'format' => 'text'
			));
		}
		array_push($cofiguracion_resumen , 	array(
			'field' => 'estado',
			'title' => 'ESTADO',
		));

		$ArregloMonedas = UtilesApp::ArregloMonedas($sesion);
		foreach($ArregloMonedas as $id_moneda => $Moneda) {
			array_push($cofiguracion_resumen, array(
				'field' => "neto_$id_moneda",
				'format' => 'number',
				'title' => "NETO {$Moneda['simbolo']}"
			));
			array_push($cofiguracion_resumen, array(
				'field' => "iva_$id_moneda",
				'format' => 'number',
				'title' => __('IVA') . " {$Moneda['simbolo']}"
			));
		}

		$SimpleReport->LoadConfigFromArray($cofiguracion_resumen);
		$resumen = array();

		foreach ($results as $key => $result) {

			$results[$key]['estado'] = strtoupper($results[$key]['estado']);
			$llave = $results[$key]['tipo'] . '_' . $results[$key]['serie_documento_legal'] . '_' . $results[$key]['estado'];
			$results[$key]['neto'] = $results[$key]['honorarios'] + $results[$key]['subtotal_gastos'] + $results[$key]['subtotal_gastos_sin_impuesto'];
			$resumen[$llave]['tipo'] = $results[$key]['tipo'];
			$resumen[$llave]['serie'] = $results[$key]['serie_documento_legal'];
			$resumen[$llave]['estado'] = $results[$key]['estado'];


			$resumen[$llave]['iva_' . $results[$key]['id_moneda']]+=$results[$key]['iva'] * ($results[$key]['tipo'] == 'NC' ? -1 : 1);
			$resumen[$llave]['neto_' . $results[$key]['id_moneda']]+=$results[$key]['neto'] * ($results[$key]['tipo'] == 'NC' ? -1 : 1);
		}

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

