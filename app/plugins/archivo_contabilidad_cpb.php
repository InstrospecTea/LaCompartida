<?php

$Slim = Slim::getInstance('default', true);


$Slim->hook('hook_factura_fin', 'Ofrece_Planilla_Registro_Ventas');
$Slim->hook('hook_factura_inicio', 'Descarga_Planilla_Registro_Ventas');

//$Slim->hook('hook_factura_descarga', 'Descarga_Planilla_Registro_Ventas');


function Ofrece_Planilla_Registro_Ventas() {
	$text = '<script>if(jQuery(\'#fecha1\').val()==\'\') { jQuery(\'#fecha1\').val(\'01-' . date("m-Y", strtotime("-1 MONTH")) . '\');}</script>';
	$text.='<a class="btn botonizame" icon="ui-icon-invoice2"    id="boton_registro_ventas" name="boton_registro_ventas"
					onclick="jQuery(\'#form_facturas\').attr(\'action\',\'facturas.php?opc=buscar&descargar_excel=1&planilla=registro_ventas\').submit();">' . __('Registro Ventas') . '</a>';
	echo $text;
}

function Descarga_Planilla_Registro_Ventas() {
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
	 if ($_GET['planilla'] == 'registro_ventas') {



		foreach ($results as $key => $result) {
			/* 	if($results[$key]['codigo_estado']=='A') {
			  unset($results[$key]);
			  continue;
			  } */
			$results[$key]['empresa'] = "017";
			$results[$key]['libro'] = '711001';
			$results[$key]['descrec'] = $results[$key]['servicio'] = $results[$key]['otros'] = '0';
			$results[$key]['estado'] = strtoupper($results[$key]['estado']);
			$results[$key]['fechax'] = strftime("%Y%m%d", strtotime($result['fecha']));

			$results[$key]['codigodocumento'] = str_replace(array('FA', 'BO', 'NC', 'ND'), array('FAC', 'BOL', 'NAB', 'NDB'), $results[$key]['tipo']) . sprintf("%s%07d", $results[$key]['serie_documento_legal'], $results[$key]['numero']);
			$results[$key]['tipo_doc'] = str_replace(array('FA', 'BO', 'NC', 'ND'), array('01', '03', '07', '08'), $results[$key]['tipo']);
			$results[$key]['neto'] = $results[$key]['honorarios'] + $results[$key]['subtotal_gastos'] + $results[$key]['subtotal_gastos_sin_impuesto'];
			$results[$key]['moneda'] = str_replace(array('1', '2', '3'), array('PEN', 'DOL', 'EUR'), $results[$key]['id_moneda']);
			$results[$key]['RUT_cliente'] = (!empty($results[$key]['RUT_cliente']) && substr($results[$key]['RUT_cliente'],0,7)!='0000000')? $results[$key]['RUT_cliente'] : 'AUTO';
			$results[$key]['factura_rsocial'] =  ($results[$key]['factura_rsocial']);

			$results[$key]['tipo_cambio'] = number_format($results[$key]['tipo_cambio_factura'], '3', '.', ',');

			$results[$key]['neto'] = number_format($results[$key]['neto'], '2', '.', ',');
			$results[$key]['iva'] = number_format($results[$key]['iva'], '2', '.', ',');
			$results[$key]['total'] = number_format($results[$key]['total'], '2', '.', ',');
			$results[$key]['importe'] = number_format($results[$key]['importe'], '2', '.', ',');
		}

		function ordenareporte($filaA, $filaB) {
			if ($filaA['codigodocumento'] == $filaB['codigodocumento']) {

				if ($filaA['serie_documento_legal'] == $filaB['serie_documento_legal']) {
					if ($filaA['fechax'] == $filaB['fechax']) {
						return ($filaA['numero'] > $filaB['numero']) ? +1 : -1;
					} else {
						return strcmp($filaA['fechax'], $filaB['fechax']);
					}
				} else {
					return ($filaA['serie_documento_legal'] > $filaB['serie_documento_legal']) ? +1 : -1;
				}
			} else {
				return strcmp($filaA['codigodocumento'], $filaB['codigodocumento']);
			}
		}

		usort($results, "ordenareporte");
		array_unshift($results, array(
			'empresa' => "017",
			'libro' => "711001",
			'fechax' => substr($results[1]['fechax'], 0, 6),
			'neto' => "709701",
			'iva' => "401101",
			'servicio' => "NULL",
			'otros' => "NULL",
			'total' => "121201",
		));

		$cofiguracion_mini = array(
			array(
				'field' => 'empresa',
				'title' => 'EMPRESA',
				'format' => 'text',
			),
			array(
				'field' => 'libro',
				'title' => 'LIBRO',
			),
			array(
				'field' => 'fechax',
				'title' => 'FECHA',
				'format' => 'text',
			),
			array(
				'field' => 'codigodocumento',
				'title' => 'DOCUMENTO',
			),
			array(
				'field' => 'tipo_doc',
				'title' => 'T/DOCT',
			),
			array(
				'field' => 'RUT_cliente',
				'title' => 'IDENTIDAD',
			),
			array(
				'field' => 'factura_rsocial',
				'title' => 'RAZON SOCIAL',
			),
			array(
				'field' => 'neto',
				'format' => 'text',
				'title' => 'NETO',
			),
			array(
				'field' => 'iva',
				'format' => 'text',
				'title' => __('IVA'),
			),
			array(
				'field' => 'servicio',
				'title' => 'SERVICIO',
			),
			array(
				'field' => 'otros',
				'title' => 'OTROS',
			),
			array(
				'field' => 'total',
				'format' => 'text',
				'title' => 'TOTAL',
			),
			array(
				'field' => 'descrec',
				'title' => 'DESC/REC',
			),
			array(
				'field' => 'estado',
				'title' => 'ESTADO',
			),
			array(
				'field' => 'moneda',
				'title' => 'MONEDA',
			),
			array(
				'field' => 'tipo_cambio',
				'title' => 'TIPCAMBIO',
			),
			array(
				'field' => 'codigo_idioma',
				'title' => 'Código Idioma',
				'visible' => false,
			),
			array(
				'field' => 'cifras_decimales',
				'title' => 'Cifras Decimales',
				'visible' => false,
			),
			array('field' => 'banco', 'title' => 'banco',),
			array('field' => 'No Operación', 'title' => 'No Operación',),
			array('field' => 'importe', 'title' => 'importe',),
			array('field' => 'fecha2', 'title' => 'fecha',),
		);
		$SimpleReport->LoadConfigFromArray($cofiguracion_mini);

		$SimpleReport->LoadResults($results);
		$SimpleReport->Config->SetTitle(strtoupper(strftime("%B", strtotime($results[1]['fecha']))));
		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');

		$writer->save(__('Registro_Ventas'));
		die();
	}
}

