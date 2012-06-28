<?php
setlocale(LC_ALL, 'en_US.iso88591', 'en_US', 'en');

$nombre = __('Archivo_Contabilidad');
header('Content-type: text/plain');
header('Content-Disposition: attachment; filename="' . $nombre . '.txt"');

$MARCA_CONTABILIDAD = 'INFORMADO A CONTABILIDAD';

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/Funciones.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/Asunto.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../app/classes/Factura.php';

$sesion = new Sesion(array('ADM', 'COB'));
$where_cobro = ' 1 ';

$contrato = new Contrato($sesion);

$formato_fechas = UtilesApp::ObtenerFormatoFecha($sesion);
$cambios = array("%d" => "d", "%m" => "m", "%y" => "Y", "%Y" => "Y");
$formato_fechas_php = strtr($formato_fechas, $cambios);

// Esta variable se usa para que cada página tenga un nombre único.
$numero_pagina = 0;

// Buscar todos los borradores o cargar de nuevo el cobro especifico que hay que imprimir

$id_moneda_filtro = $id_moneda;

if ($orden == '') {
	$orden = 'fecha DESC';
}

if ($where == '') {
	$join = '';
	$where = 1;
	if ($numero != '') {
		$where .= " AND numero = '$numero'";
	}
	if ($fecha1 && $fecha2) {
		$where .= " AND fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . " 00:00:00' AND '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
	} else if ($fecha1) {
		$where .= " AND fecha >= '" . Utiles::fecha2sql($fecha1) . ' 00:00:00' . "' ";
	} else if ($fecha2) {
		$where .= " AND fecha <= '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
	}
	if (UtilesApp::GetConf($sesion, 'CodigoSecundario') && $codigo_cliente_secundario) {
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
		$codigo_cliente = $cliente->fields['codigo_cliente'];
	}
	if ($tipo_documento_legal_buscado) {
		$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";
	}
	if ($codigo_cliente) {
		//$where .= " AND factura.codigo_cliente='".$codigo_cliente."' ";
		$where .= " AND cobro.codigo_cliente='$codigo_cliente' ";
	}
	if (UtilesApp::GetConf($sesion, 'CodigoSecundario') && $codigo_cliente_secundario) {
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigoSecundario($codigo_cliente_secundario);
		$id_contrato = $asunto->fields['id_contrato'];
	}
	if ($codigo_asunto) {
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($codigo_asunto);
		$id_contrato = $asunto->fields['id_contrato'];
	}
	if ($id_contrato) {
		//$join = " JOIN cobro ON cobro.id_cobro=factura.id_cobro ";
		$where .= " AND cobro.id_contrato='$id_contrato' ";
	}
	if ($id_cobro) {
		$where .= " AND factura.id_cobro='$id_cobro' ";
	}
	if ($id_estado) {
		$where .= " AND factura.id_estado = '$id_estado' ";
	}
	if ($id_moneda) {
		$where .= " AND factura.id_moneda = '$id_moneda' ";
	}
	if ($grupo_ventas) {
		$where .= " AND prm_documento_legal.grupo = 'VENTAS' ";
	}

	if ($razon_social) {
		$where .= " AND factura.cliente LIKE '%$razon_social%'";
	}
	if ($descripcion_factura) {
		$where .= " AND (factura.descripcion LIKE '%$descripcion_factura%' OR factura.descripcion_subtotal_gastos LIKE '%$descripcion_factura%' OR factura.descripcion_subtotal_gastos_sin_impuesto LIKE '%$descripcion_factura%')";
	}
	// Para evitar enviar los informados anteriormente
	$where .= " AND factura.numero != ''";
	$where .= " AND factura.observacion_adicional NOT LIKE '$MARCA_CONTABILIDAD'";
} else {
	$where = base64_decode($where);
}

$where .= " GROUP BY factura.id_factura";
$where .= " ORDER BY factura.mes_contable ASC, factura.asiento_contable ASC";

$query = "SELECT cliente.glosa_cliente
						, DATE_FORMAT(fecha, '$formato_fechas') as fecha
						, prm_documento_legal.codigo as tipo
						, CONCAT_WS('-', LPAD(factura.serie_documento_legal, 3, '0'), factura.numero) AS numero";
if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
	$query .= "			, cliente as cliente_facturable";
}
$query .= "				, '' glosa_asunto
						, cobro_asunto.codigo_asunto
						, factura.asiento_contable
						, factura.id_tipo_documento_identidad
						, usuario.username AS encargado_comercial
						, descripcion
						, factura.id_cobro
						, factura.RUT_cliente
						, prm_moneda.simbolo
						, prm_moneda.cifras_decimales
						, factura.id_moneda
						, factura.honorarios
						, factura.subtotal_gastos
						, factura.subtotal_gastos_sin_impuesto
						, '' as subtotal
						, factura.iva
						, total
						, '' as saldo_pagos
						, cta_cte_fact_mvto.saldo as saldo
						, '' as monto_pagos_moneda_base
						, '' as saldo_moneda_base
						, factura.id_factura
						, '' as fecha_ultimo_pago
						, prm_estado_factura.codigo as estado
						, prm_estado_factura.glosa as estado_glosa
						, if(factura.RUT_cliente != contrato.rut,factura.cliente,'no' ) as mostrar_diferencia_razon_social
						, cobro_moneda.tipo_cambio
					FROM factura
					JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
					JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
					LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
					LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
					LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
					LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
					LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
					LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
					LEFT JOIN cobro_asunto ON cobro_asunto.id_cobro = cobro.id_cobro
					LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro = cobro.id_cobro AND cobro_moneda.id_moneda = 2
					WHERE $where";
$lista_suntos_liquidar = new ListaAsuntos($sesion, "", $query);
if ($lista_suntos_liquidar->num == 0) {
	echo 'No existen facturas con este criterio o ya se encuentran todas informadas.';
	exit;
}

$fecha_actual = date('Y-m-d');

$col_name = array_keys($lista_suntos_liquidar->Get()->fields);
$col_num = count($lista_suntos_liquidar->Get()->fields);

$fila = 0; //fila inicial

$fila_inicial = $fila;

/*
 * Inicio configuraciones
 */
// Formatos printf
$formato_correlativo = '%04d';
$formato_fecha = '%8.8s';
$formato_centro_costo = '%0-10d';
$formato_monto_neto = '%012.2f';
$formato_moneda_facturacion = '%1.1s';
$formato_tipo_cambio = '%0-10.10s';
$formato_tipo_documento = '%2.2s';
$formato_codigo_factura = '%-20.20s';
$formato_id_factura = '%011d';
$formato_cliente_factura = '%-40.40s';
$formato_descripcion_factura = '%-25.25s';
$formato_id_cliente_factura = '%1.1s';
$formato_monto_asunto = '%012.2f';
$formato_impuesto_factura = '%012.2f';
$formato_codigo_asunto = '%07.7d';

$formato_primera_linea = '02'
		. $formato_correlativo
		. $formato_fecha
		. $formato_centro_costo
		. $formato_monto_neto
		. 'D'
		. $formato_moneda_facturacion
		. $formato_tipo_cambio
		. $formato_tipo_documento
		. $formato_codigo_factura
		. $formato_fecha
		. $formato_id_factura
		. '       '
		. '    '
		. '          '
		. ' '
		. $formato_fecha
		. '            '
		. '                                    '
		. '            '
		. $formato_id_factura
		. '1'
		. $formato_cliente_factura
		. $formato_descripcion_factura
		. $formato_id_cliente_factura
		. '   ';

$formato_segunda_linea = '02'
		. $formato_correlativo
		. $formato_fecha
		. $formato_centro_costo
		. $formato_monto_neto
		. 'H'
		. $formato_moneda_facturacion
		. $formato_tipo_cambio
		. $formato_tipo_documento
		. $formato_codigo_factura
		. $formato_fecha
		. $formato_id_factura
		. '       '
		. '    '
		. '          '
		. 'V'
		. $formato_fecha
		. $formato_monto_asunto
		. '                                    '
		. $formato_impuesto_factura
		. $formato_id_factura
		. '1'
		. $formato_cliente_factura
		. $formato_descripcion_factura
		. ' '
		. '   ';

$formato_linea = '02'
		. $formato_correlativo
		. $formato_fecha
		. $formato_centro_costo
		. $formato_monto_asunto
		. 'H'
		. $formato_moneda_facturacion
		. $formato_tipo_cambio
		. $formato_tipo_documento
		. $formato_codigo_factura
		. $formato_fecha
		. $formato_id_factura
		. $formato_codigo_asunto
		. '    '
		. '          '
		. ' '
		. $formato_fecha
		. '            '
		. '                                    '
		. '            '
		. $formato_id_factura
		. '1'
		. $formato_cliente_factura
		. $formato_descripcion_factura
		. ' '
		. '   ';
/*
 * Fin configuraciones
 */

$facturas_para_marcar = array();

// Escribir filas
$mes = 0;
$variable = false;
for ($j = 0; $j < $lista_suntos_liquidar->num; $j++) {

	$documento = $lista_suntos_liquidar->Get($j);

	if (isset($documento->fields['id_factura'])) {
		$facturas_para_marcar[] = $documento->fields['id_factura'];
	}

	// Datos
	$fecha_impresion = $documento->fields['fecha'];

	$nueva_fecha = explode('-', $fecha_impresion);
	$nuevo_dia = intval($nueva_fecha[2]);
	$nuevo_mes = intval($nueva_fecha[1]);

	// Correlativo, de acuerdo al algoritmo extraño de PRC
	$correlativo = $desde_asiento_contable++; //$documento->fields['asiento_contable'];

	// Si la moneda es soles, al centro de costo 12111, sino al 12112
	// Nuevo cambio 12111 -> 12131, 12112 -> 12132
	$centro_costo = ($documento->fields['id_moneda'] == 1) ? 12131 : 12132;
	// Nuevo cambio 40111 -> 401111
	$centro_costo_iva = 401111;
	$moneda_facturacion = ($documento->fields['id_moneda'] == 1) ? 'S' : 'D';
	$monto_neto = $documento->fields['total'];
	$tipo_cambio = $documento->fields['tipo_cambio'];
	$tipo_documento = $documento->fields['tipo'];
	$codigo_factura = $documento->fields['numero'];

	$fecha_pago = $documento->fields['fecha_ultimo_pago'];
	// Si no hay, considero la fecha de vencimiento (1 mes de la emisión)
	if (empty($fecha_pago)) {
		$fecha_pago = DateTime::createFromFormat('d/m/Y', $fecha_impresion);
		$fecha_pago->modify('+1 month');
		$fecha_pago = $fecha_pago->format('d/m/Y');
	}

	$id_factura = $documento->fields['RUT_cliente'];
	$cliente_factura = $documento->fields['cliente_facturable'];
	$id_cliente_factura = $documento->fields['id_tipo_documento_identidad'];
	$impuesto_factura = $documento->fields['iva'];
	$codigo_asunto = $documento->fields['codigo_asunto'];
	$descripcion_factura = trim($documento->fields['descripcion']);
	$descripcion_factura = strip_tags($descripcion_factura);
	$descripcion_factura = str_replace(array("\r\n", "\n\r", "\n", "\r"), " ", $descripcion_factura);
	$descripcion_factura = $descripcion_factura . ' ' . $codigo_asunto;

	$codigo_asunto = explode('-', $codigo_asunto);
	$codigo_asunto = $codigo_asunto[0] . $codigo_asunto[1];

	$subtotal_honorarios = $documento->fields['honorarios'];
	$subtotal_gastos = $documento->fields['subtotal_gastos'] + $documento->fields['subtotal_gastos_sin_impuesto'];
	$monto_asunto = $subtotal_honorarios + $subtotal_gastos;
	// Dependiendo de la moneda para los honorarios se utiliza 70711 o 70712
	// Nuevo cambio 70711 -> 7041, 70712 -> 7042
	$centro_costo_honorarios = ($documento->fields['id_moneda'] == 1) ? 7041 : 7042;
	// Nuevo cambio 75211 -> 75921
	$centro_costo_gastos = 75921;

	// Conversiones
	if ($fecha_impresion != '') {
		$fecha_impresion = DateTime::createFromFormat('d/m/Y', $fecha_impresion)->format('d/m/y');
	}
	if ($fecha_pago != '') {
		$fecha_pago = DateTime::createFromFormat('d/m/Y', $fecha_pago)->format('d/m/y');
	}
	// Codificacion de tipos documento según PRC
	switch ($tipo_documento) {
		case 'FA': $tipo_documento = '01';
			break;
		case 'BO': $tipo_documento = '03';
			break;
		case 'NC': $tipo_documento = '07';
			break;
		case 'ND': $tipo_documento = '08';
			break;
	}

	// E = Documento de Extranjería, L = Libreta Electoral, D = DNI, R = RUC
	// E -> 4, L o D -> 1, R -> 6
	// ver prm_tipo_documento_identidad
	switch ($id_cliente_factura) {
		case 1: $id_cliente_factura = '6'; break;
		case 2: $id_cliente_factura = '4'; break;
		case 3: case 4: $id_cliente_factura = '1'; break;
		default: $id_cliente_factura = ' '; break;
	}

	$tipo_cambio = sprintf('0%.f', $tipo_cambio);
	$codigo_factura = split('-', $codigo_factura);
	if (count($codigo_factura) > 1) {
		$codigo_factura = $codigo_factura[0] . '-' . sprintf('%d', $codigo_factura[1]);
	} else {
		$codigo_factura = sprintf('%d', $codigo_factura[0]);
	}

	// Elimino los datos de los anulados, estado 5 es anulado
	if ($documento->fields['id_estado'] == 5 || $documento->fields['estado'] == 'A') {
		$monto_neto = 0;
		$monto_asunto = 0;
		$impuesto_factura = 0;
		$id_factura = 55555555555;
		$subtotal_honorarios = 0;
		$subtotal_gastos = 0;
		$fecha_pago = '';
	}


	$linea = sprintf($formato_primera_linea, $correlativo, $fecha_impresion, $centro_costo, $monto_neto, $moneda_facturacion, $tipo_cambio, $tipo_documento, $codigo_factura, $fecha_pago, $id_factura, $fecha_impresion, $id_factura, $cliente_factura, $descripcion_factura, $id_cliente_factura
	) . "\r\n";
	echo str_replace(',', '.', $linea);

	$linea = sprintf($formato_segunda_linea, $correlativo, $fecha_impresion, $centro_costo_iva, $impuesto_factura, $moneda_facturacion, $tipo_cambio, $tipo_documento, $codigo_factura, $fecha_pago, $id_factura, $fecha_impresion, $monto_asunto, $impuesto_factura, $id_factura, $cliente_factura, $descripcion_factura
	) . "\r\n";
	echo str_replace(',', '.', $linea);

	/*
	 * Aqui habría que iterar en el detalle?
	 */
	if ($subtotal_honorarios > 0) {
		$linea = sprintf($formato_linea, $correlativo, $fecha_impresion, $centro_costo_honorarios, $subtotal_honorarios, $moneda_facturacion, $tipo_cambio, $tipo_documento, $codigo_factura, $fecha_pago, $id_factura, $codigo_asunto, $fecha_impresion, $id_factura, $cliente_factura, $descripcion_factura
		) . "\r\n";
		echo str_replace(',', '.', $linea);
	}

	if ($subtotal_gastos > 0) {
		$linea = sprintf($formato_linea, $correlativo, $fecha_impresion, $centro_costo_gastos, $subtotal_gastos, $moneda_facturacion, $tipo_cambio, $tipo_documento, $codigo_factura, $fecha_pago, $id_factura, $codigo_asunto, $fecha_impresion, $id_factura, $cliente_factura, $descripcion_factura
		) . "\r\n";
		echo str_replace(',', '.', $linea);
	}
	/* */

//	$fila_actual = $fila + 1;
//	$proc = $lista_suntos_liquidar->Get($j);
//	$ws1->write($fila, $col_glosa_cliente, $proc->fields[$col_name[$i]], $formato_normal);
//
//	$query = "SELECT GROUP_CONCAT(ca.codigo_asunto SEPARATOR ', ') , GROUP_CONCAT(a.glosa_asunto SEPARATOR ', ')
//					FROM cobro_asunto ca
//					LEFT JOIN asunto a ON ca.codigo_asunto = a.codigo_asunto
//					WHERE ca.id_cobro='" . $proc->fields['id_cobro'] . "' GROUP BY ca.id_cobro";
//	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
//	$lista_asuntos = '';
//	$lista_asuntos_glosa = '';
//	while (list($lista_codigo_asunto, $lista_glosa_asunto) = mysql_fetch_array($resp)) {
//		$lista_asuntos = '(' . $lista_codigo_asunto . ')';
//		$lista_asuntos_glosa = $lista_glosa_asunto;
//	}
//
//	$query2 = "SELECT SUM(ccfmn.monto) as monto_aporte, MAX(ccfm.fecha_modificacion) as ultima_fecha_pago
//					FROM factura_pago AS fp
//					JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
//					JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
//					LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
//					LEFT JOIN prm_moneda mo ON ccfm.id_moneda = mo.id_moneda
//					WHERE ccfm2.id_factura =  '" . $proc->fields['id_factura'] . "' GROUP BY ccfm2.id_factura ";
//
//	$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
//	$monto_pago = 0;
//	list($monto_pago, $ultima_fecha_pago) = mysql_fetch_array($resp2);
//
//	if ($monto_pago <= 0)
//		$monto_pago = 0;
//
//	for ($i = 0; $i < $col_num; $i++) {
//		if ($arr_col[$col_name[$i]]['hidden'] != 'SI') {
//
//			$subtotal = $proc->fields['honorarios'] + $proc->fields['subtotal_gastos'] + $proc->fields['subtotal_gastos_sin_impuesto'];
//
//			if ($col_name[$i] == 'total' || $col_name[$i] == 'iva') {
//				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $formatos_moneda[$proc->fields['id_moneda']]);
//			} else if ($col_name[$i] == 'subtotal') {
//				$subtotal = $proc->fields['honorarios'] + $proc->fields['subtotal_gastos'] + $proc->fields['subtotal_gastos_sin_impuesto'];
//				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $subtotal, $formatos_moneda[$proc->fields['id_moneda']]);
//			} else if ($col_name[$i] == 'saldo') {
//				$saldo = $proc->fields[$col_name[$i]] * (-1);
//				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo, $formatos_moneda[$proc->fields['id_moneda']]);
//			} else if ($col_name[$i] == 'saldo_pagos') {
//				//$factura = new Factura($sesion);
//				//$lista_pagos_fact = $factura->GetPagosSoyFactura($proc->fields['id_factura']);
//
//				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $monto_pago, $formatos_moneda[$proc->fields['id_moneda']]);
//			} else if ($col_name[$i] == 'saldo_moneda_base') {
//				$saldo_moneda_base = UtilesApp::CambiarMoneda($saldo, $proc->fields['tipo_cambio'], $proc->fields['cifras_decimales'], $tipo_cambio_moneda_base, $cifras_decimales_moneda_base, false);
//				$ws1->writeFormula($fila, $arr_col['saldo_moneda_base']['celda'], "=" . $arr_col['saldo']['celda_excel'] . "$fila_actual*" . $arr_col['tipo_cambio']['celda_excel'] . "$fila_actual", $formatos_moneda[$id_moneda_base]);
//				//$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo_moneda_base, $formatos_moneda[$id_moneda_base]);
//			} else if ($col_name[$i] == 'monto_pagos_moneda_base') {
//				$monto_pago_moneda_base = UtilesApp::CambiarMoneda($monto_pago, $proc->fields['tipo_cambio'], $proc->fields['cifras_decimales'], $tipo_cambio_moneda_base, $cifras_decimales_moneda_base, false);
//				$ws1->writeFormula($fila, $arr_col['monto_pagos_moneda_base']['celda'], "=" . $arr_col['saldo_pagos']['celda_excel'] . "$fila_actual*" . $arr_col['tipo_cambio']['celda_excel'] . "$fila_actual", $formatos_moneda[$id_moneda_base]);
//				//$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $monto_pago_moneda_base, $formatos_moneda[$id_moneda_base]);
//			}
//			if ($col_name[$i] == 'glosa_cliente') {
//				$glosa_cliente = $proc->fields['glosa_cliente'];
//				if ($proc->fields['mostrar_diferencia_razon_social'] != 'no') {
//					$glosa_cliente .= " (" . $proc->fields['mostrar_diferencia_razon_social'] . ")";
//				}
//				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $glosa_cliente, $arr_col[$col_name[$i]]['css']);
//			}
//			if ($col_name[$i] == 'glosa_asunto') {
//
//				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos_glosa, $arr_col[$col_name[$i]]['css']);
//			}
//			if ($col_name[$i] == 'codigo_asunto') {
//
//				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos, $arr_col[$col_name[$i]]['css']);
//			}
//			if ($col_name[$i] == 'fecha_ultimo_pago') {
//
//				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], Utiles::sql2fecha($ultima_fecha_pago, $formato_fechas, "-"), $arr_col[$col_name[$i]]['css']);
//			}
//			if ($col_name[$i] == 'estado_glosa') {
//
//				$ws1->writeNote($fila, $arr_col['estado']['celda'], $proc->fields[$col_name[$i]]);
//			}
//			if ($col_name[$i] == 'numeracion_excel') {
//
//				$ws1->writeFormula($fila, $arr_col['numeracion_excel']['celda'], "=SUM(" . $arr_col['numeracion_excel']['celda_excel'] . "$fila + 1)", $arr_col[$col_name[$i]]['css']);
//			} else {
//				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
//			}
//
//			$ws1->writeNumber($fila, $arr_col['subtotal']['celda'], $subtotal, $formatos_moneda[$proc->fields['id_moneda']]);
//			$ws1->write($fila, $arr_col['saldo_pagos']['celda'], $monto_pago, $formatos_moneda[$proc->fields['id_moneda']]);
//		}
//	}
}

// Actualizar los documentos legales informados
if (count($facturas_para_marcar) > 0) {
	$sql = "UPDATE factura SET observacion_adicional = '$MARCA_CONTABILIDAD' ";
	$sql.= "WHERE id_factura IN (" . implode(',', $facturas_para_marcar) . ");";

	mysql_query($sql, $sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $sesion->dbh);
}

exit;