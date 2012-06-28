<?php

require_once 'Spreadsheet/Excel/Writer.php';
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
set_time_limit(0);
ini_set("memory_limit", "256M");
$where_cobro = ' 1 ';

//void Worksheet::setLandscape();
$contrato = new Contrato($sesion);

$formato_fechas = UtilesApp::ObtenerFormatoFecha($sesion);
$cambios = array("%d" => "d", "%m" => "m", "%y" => "Y", "%Y" => "Y");
$formato_fechas_php = strtr($formato_fechas, $cambios);

// Esta variable se usa para que cada página tenga un nombre único.
$numero_pagina = 0;

// Buscar todos los borradores o cargar de nuevo el cobro especifico que hay que imprimir

$id_moneda_filtro = $id_moneda;

if ($orden == "") {
	$orden = "fecha DESC";
}

if ($where == '') {
	$join = "";
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
	if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario) {
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
		$codigo_cliente = $cliente->fields['codigo_cliente'];
	}
	if ($tipo_documento_legal_buscado)
		$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";
	if ($codigo_cliente) {
		//$where .= " AND factura.codigo_cliente='".$codigo_cliente."' ";
		$where .= " AND cobro.codigo_cliente='" . $codigo_cliente . "' ";
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
		$where .= " AND cobro.id_contrato=" . $id_contrato . " ";
	}
	if ($id_cobro) {
		$where .= " AND factura.id_cobro=" . $id_cobro . " ";
	}
	if ($id_estado) {
		$where .= " AND factura.id_estado = " . $id_estado . " ";
	}
	if ($id_moneda) {
		$where .= " AND factura.id_moneda = " . $id_moneda . " ";
	}
	if ($grupo_ventas) {
		$where .= " AND prm_documento_legal.grupo = 'VENTAS' ";
	}

	if ($id_cia && ( method_exists('Conf', 'dbUser') && Conf::dbUser() == "rebaza" )) {
		$where .= " AND factura.id_cia = '$id_cia' ";
	}
	if ($razon_social) {
		$where .= " AND factura.cliente LIKE '%" . $razon_social . "%'";
	}
	if ($descripcion_factura) {
		$where .= " AND (factura.descripcion LIKE '%" . $descripcion_factura . "%' OR factura.descripcion_subtotal_gastos LIKE '%" . $descripcion_factura . "%' OR factura.descripcion_subtotal_gastos_sin_impuesto LIKE '%" . $descripcion_factura . "%')";
	}
} else {
	$where = base64_decode($where);
}

$numero_factura = "";
if (UtilesApp::GetConf($sesion, 'NumeroFacturaConSerie')) {
	$numero_factura = "CONCAT(LPAD(factura.serie_documento_legal, 3, '0'), '-', factura.numero) as numero";
} else {
	$numero_factura = "factura.numero";
}

$query = "SELECT cliente.glosa_cliente
						, DATE_FORMAT(fecha, '" . $formato_fechas . "') as fecha
						, prm_documento_legal.codigo as tipo
						, $numero_factura";
if (UtilesApp::GetConf($sesion, 'NuevoModuloFactura')) {
	$query .= "			, cliente as cliente_facturable";
}
$query .= "			, '' glosa_asunto
						, '' codigo_asunto
						, usuario.username AS encargado_comercial
						, descripcion
						, factura.id_cobro
						, prm_moneda.simbolo
						, prm_moneda.cifras_decimales
						, prm_moneda.tipo_cambio
						, factura.id_moneda
						, factura.honorarios
						, factura.subtotal_gastos
						, factura.subtotal_gastos_sin_impuesto
						, '' as subtotal
						, factura.iva
						, total
						, '' as monto_real
						, '' as observaciones
						, '' as saldo_pagos
						, cta_cte_fact_mvto.saldo as saldo
						, '' as monto_pagos_moneda_base
						, '' as saldo_moneda_base
						, factura.id_factura
						, '' as fecha_ultimo_pago
						, prm_estado_factura.codigo as estado
						, prm_estado_factura.glosa as estado_glosa
						, if(factura.RUT_cliente != contrato.rut,factura.cliente,'no' ) as mostrar_diferencia_razon_social
					FROM factura
					JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
					JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
					LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
					LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
					LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
					LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
					LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
					LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
					WHERE $where";

$lista_suntos_liquidar = new ListaAsuntos($sesion, "", $query);

if ($lista_suntos_liquidar->num == 0) {
	$pagina->FatalError('No existe información con este criterio');
}

$fecha_actual = date('Y-m-d');

// Crear y preparar planilla
$wb = new Spreadsheet_Excel_Writer();
// Enviar headers a la pagina
$wb->send(__('Documentos tributarios') . ' ' . $fecha_actual . '.xls');


// Definir colores
$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);

// Crear formatos de celda
$formato_encabezado = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'top',
			'Align' => 'left',
			'Bold' => '1',
			'underline' => 1,
			'Color' => 'black'));
$formato_titulo = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Bold' => 1,
			'Locked' => 1,
			'Bottom' => 1,
			'FgColor' => 35,
			'Color' => 'black'));
$formato_normal = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black'));
$formato_descripcion = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Align' => 'left',
			'Color' => 'black',
			'TextWrap' => 1));
$formato_tiempo = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black',
			'NumFormat' => '[h]:mm'));
$formato_fecha_tiempo = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Color' => 'black',
			'TextWrap' => 1));
$formato_total = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'top',
			'Bold' => 1,
			'Top' => 1,
			'Color' => 'black'));
// Generar formatos para los distintos tipos de moneda
$formatos_moneda = array();
$query = 'SELECT id_moneda, simbolo, cifras_decimales, moneda_base, tipo_cambio
			FROM prm_moneda
			ORDER BY id_moneda';
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
$id_moneda_base = 0;
while (list($id_moneda, $simbolo_moneda, $cifras_decimales, $moneda_base, $tipo_cambio) = mysql_fetch_array($resp)) {
	if ($cifras_decimales > 0) {
		$decimales = '.';
		while ($cifras_decimales-- > 0)
			$decimales .= '0';
	} else {
		$decimales = '';
	}
	$formatos_moneda[$id_moneda] = & $wb->addFormat(array('Size' => 10,
				'VAlign' => 'top',
				'Align' => 'right',
				'Color' => 'black',
				'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));

	if ($moneda_base == 1) {
		$id_moneda_base = $id_moneda;
		$cifras_decimales_moneda_base = $cifras_decimales;
		$tipo_cambio_moneda_base = $tipo_cambio;
		$simbolo_moneda_base = $simbolo_moneda;
	}
}
// Columnas a mostrar
if (UtilesApp::GetConf($sesion, 'MostrarColumnaReporteFacturacion')) {
	$columnas = explode(',', UtilesApp::GetConf($sesion, 'MostrarColumnaReporteFacturacion'));
} else {
	$columnas = array('glosa_cliente', 'fecha', 'tipo', 'numero', 'cliente_facturable', 'glosa_asunto', 'codigo_asunto', 'encargado_comercial',
		'descripcion', 'id_cobro','iva', 'total', 'monto_real', 'observaciones', 'saldo_pagos', 'saldo', 'fecha_ultimo_pago', 'estado_glosa');
}
// Crear worksheet
$ws1 = & $wb->addWorksheet(__('Documentos tributarios'));
$ws1->setLandscape();

$col_name = array_keys($lista_suntos_liquidar->Get()->fields);
$col_num = count($lista_suntos_liquidar->Get()->fields);

// Definimos visible, css, ancho y titulo de cada celda
$arr_col = array();
$col = 0;




for ($i = 0; $i < $col_num; ++$i) {
	if (in_array($col_name[$i], $columnas)) {
		$arr_col[$col_name[$i]]['celda'] = $col++;

		if (in_array($col_name[$i], array('descripcion', 'subtotal', 'monto_real', 'total','saldo_pagos','saldo'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 12);
		} else if (in_array($col_name[$i], array('saldo', 'iva', 'saldo_moneda_base', 'glosa_estado'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 11);
		} else if (in_array($col_name[$i], array('numero', 'cobro', 'id_cobro'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 8);
		} else if (in_array($col_name[$i], array('encargado_comercial', 'fecha_ultimo_pago', 'glosa_asunto'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 9);
		} else if (in_array($col_name[$i], array('tipo', 'estado'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 3);
		} else if (in_array($col_name[$i], array('glosa_cliente'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 20);
		} else {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
		}

		// css celdas
		if (in_array($col_name[$i], array('fecha', 'fecha_ultimo_pago'))) {
			$arr_col[$col_name[$i]]['css'] = $formato_fecha_tiempo;
		} else if (in_array($col_name[$i], array('glosa_cliente', 'descripcion', 'glosa_asunto'))) {
			$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
		} else {
			$arr_col[$col_name[$i]]['css'] = $formato_normal;
		}

		// titulos celdas
		if (in_array($col_name[$i], array('glosa_cliente'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Cliente');
		}
		if (method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NuevoModuloFactura')) {
			if (in_array($col_name[$i], array('cliente_facturable'))) {
				$arr_col[$col_name[$i]]['titulo'] = __('Cliente Facturable');
			}
		}
		if (in_array($col_name[$i], array('glosa_asunto'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Asuntos');
		} else if (in_array($col_name[$i], array('encargado_comercial'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Abogado');
		} else if (in_array($col_name[$i], array('id_cobro'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Cobro');
		} else if (in_array($col_name[$i], array('iva'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('IVA');
		} else if (in_array($col_name[$i], array('saldo_pagos'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Pagos');
		} else if (in_array($col_name[$i], array('subtotal'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Subtotal');
		} else if (in_array($col_name[$i], array('monto_real'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Monto Real');
		} else if (in_array($col_name[$i], array('observaciones'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Observaciones');
		} else if (in_array($col_name[$i], array('saldo_moneda_base'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Saldo' . ' ' . $simbolo_moneda_base);
		} else if (in_array($col_name[$i], array('monto_pagos_moneda_base'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Pagos' . ' ' . $simbolo_moneda_base);
		} else if (in_array($col_name[$i], array('fecha_ultimo_pago'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Fecha último pago');
		} else if (in_array($col_name[$i], array('estado_glosa'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Estado');
		} else if (in_array($col_name[$i], array('numeracion_excel'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('');
		} else {
			$arr_col[$col_name[$i]]['titulo'] = str_replace('_', ' ', $col_name[$i]);
		}


		//formato columna excel para formulas
		$arr_col[$col_name[$i]]['celda_excel'] = Utiles::NumToColumnaExcel($arr_col[$col_name[$i]]['celda']);
	}
}

unset($col);
$fila = 0; //fila inicial

$col_formula_pago = $arr_col['monto_pagos_moneda_base']['celda_excel'];
$col_formula_saldo = $arr_col['saldo_moneda_base']['celda_excel'];
$col_formula_numeracion_excel = $arr_col['numeracion_adxcel']['celda_excel'];

// Escribir encabezado reporte
$ws1->write($fila, 0, __('Documentos tributarios'), $formato_encabezado);
$fila++;

$fecha_actual = date($formato_fechas_php);
$ws1->write($fila, 0, $fecha_actual, $formato_encabezado);
$fila++;
$fila++;
// Escribir titulos
for ($i = 0; $i < $col_num; ++$i) {
	if (in_array($col_name[$i], $columnas)) {
			$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], ucfirst($arr_col[$col_name[$i]]['titulo']), $formato_titulo);
	}
}
//$ws1->writeNote($fila, $arr_col['hh_val_cobrado']['celda'], 'Work in Progress');
$fila++;
$ws1->freezePanes(array($fila));

$fila_inicial = $fila;
// Escribir filas}
for ($j = 0; $j < $lista_suntos_liquidar->num; ++$j, ++$fila) {
	$fila_actual = $fila + 1;
	$proc = $lista_suntos_liquidar->Get($j);

	$ws1->write($fila, $col_glosa_cliente, $proc->fields[$col_name[$i]], $formato_normal);

	$query = "SELECT GROUP_CONCAT(ca.codigo_asunto SEPARATOR ', ') , GROUP_CONCAT(a.glosa_asunto SEPARATOR ', ')
					FROM cobro_asunto ca
					LEFT JOIN asunto a ON ca.codigo_asunto = a.codigo_asunto
					WHERE ca.id_cobro='" . $proc->fields['id_cobro'] . "' GROUP BY ca.id_cobro";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	$lista_asuntos = '';
	$lista_asuntos_glosa = '';
	while (list($lista_codigo_asunto, $lista_glosa_asunto) = mysql_fetch_array($resp)) {
		$lista_asuntos = '(' . $lista_codigo_asunto . ')';
		$lista_asuntos_glosa = $lista_glosa_asunto;
	}

	$query2 = "SELECT SUM(ccfmn.monto) as monto_aporte, MAX(ccfm.fecha_modificacion) as ultima_fecha_pago
					FROM factura_pago AS fp
					JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
					JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
					LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
					LEFT JOIN prm_moneda mo ON ccfm.id_moneda = mo.id_moneda
					WHERE ccfm2.id_factura =  '" . $proc->fields['id_factura'] . "' GROUP BY ccfm2.id_factura ";

	$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);
	$monto_pago = 0;
	list($monto_pago, $ultima_fecha_pago) = mysql_fetch_array($resp2);

	if ($monto_pago <= 0) {
		$monto_pago = 0;
	}

	$proc->fields['tipo_cambio'] /= $tipo_cambio_moneda_base;

	for ($i = 0; $i < $col_num; $i++) {
		//if (!isset($arr_col[$col_name[$i]]['hidden']) || $arr_col[$col_name[$i]]['hidden'] != 'SI') {
		if (in_array($col_name[$i], $columnas)) {

			$subtotal = $proc->fields['honorarios'] + $proc->fields['subtotal_gastos'] + $proc->fields['subtotal_gastos_sin_impuesto'];
			$nombrecolumna = $col_name[$i];
			if ($col_name[$i] == 'total' || $col_name[$i] == 'iva') {
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $formatos_moneda[$proc->fields['id_moneda']]);
			} elseif ($col_name[$i] == 'monto_real') {
				if (strtoupper($proc->fields['estado']) == 'A') {
					$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], '0', $formatos_moneda[$proc->fields['id_moneda']]);					
				} else {
					$fact = new Factura($sesion);
					$total_facturas = $fact->ObtenerValorReal($proc->fields['id_factura']);

					$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $total_facturas, $formatos_moneda[$proc->fields['id_moneda']]);
				}
			} elseif ($col_name[$i] == 'observaciones') {
				$fact = new Factura($sesion);
				$ids_doc = $fact->ObtenerIdsDocumentos($proc->fields['id_factura']);
				$ids_doc_array = explode('||', $ids_doc);
				$valores = array();
				$total_nc = $fact->ObtenerValorReal($proc->fields['id_factura']);
				$comentarios = "";
				if ($proc->fields['total'] != $total_nc) {
					foreach ($ids_doc_array as $key => $par_cod_num) {
						$documento = strtr($par_cod_num, "::", " ");
						if (strlen($documento) > 0) {
							array_push($valores, $documento);
						}
					}
					$comentarios = implode(", ", $valores);
				}
				$ws1->write($fila, $arr_col['observaciones']['celda'], $comentarios, $formato_normal);
			} else if ($col_name[$i] == 'subtotal') {
				$subtotal = $proc->fields['honorarios'] + $proc->fields['subtotal_gastos'] + $proc->fields['subtotal_gastos_sin_impuesto'];
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $subtotal, $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'honorarios') {
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields['honorarios'], $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'subtotal_gastos') {
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields['subtotal_gastos'], $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'subtotal_gastos_sin_impuesto') {
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields['subtotal_gastos_sin_impuesto'], $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'saldo') {
				$saldo = $proc->fields[$col_name[$i]] * (-1);
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo, $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'tipo_cambio') {
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields['tipo_cambio'], $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'saldo_pagos') {
				//$factura = new Factura($sesion);
				//$lista_pagos_fact = $factura->GetPagosSoyFactura($proc->fields['id_factura']);

				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $monto_pago, $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'saldo_moneda_base') {
				//$saldo_moneda_base = UtilesApp::CambiarMoneda($saldo, $proc->fields['tipo_cambio'], $proc->fields['cifras_decimales'], $tipo_cambio_moneda_base,$cifras_decimales_moneda_base,false);
				$ws1->writeFormula($fila, $arr_col['saldo_moneda_base']['celda'], "=" . $arr_col['saldo']['celda_excel'] . "$fila_actual*" . $arr_col['tipo_cambio']['celda_excel'] . "$fila_actual", $formatos_moneda[$id_moneda_base]);
				//$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo_moneda_base, $formatos_moneda[$id_moneda_base]);
			} else if ($col_name[$i] == 'monto_pagos_moneda_base') {
				//$monto_pago_moneda_base = UtilesApp::CambiarMoneda($monto_pago, $proc->fields['tipo_cambio'], $proc->fields['cifras_decimales'], $tipo_cambio_moneda_base,$cifras_decimales_moneda_base,false);
				$ws1->writeFormula($fila, $arr_col['monto_pagos_moneda_base']['celda'], "=" . $arr_col['saldo_pagos']['celda_excel'] . "$fila_actual*" . $arr_col['tipo_cambio']['celda_excel'] . "$fila_actual", $formatos_moneda[$id_moneda_base]);
				//$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $monto_pago_moneda_base, $formatos_moneda[$id_moneda_base]);
			} else if ($col_name[$i] == 'glosa_cliente') {
				$glosa_cliente = $proc->fields['glosa_cliente'];
				if ($proc->fields['mostrar_diferencia_razon_social'] != 'no') {
					$glosa_cliente .= " (" . $proc->fields['mostrar_diferencia_razon_social'] . ")";
				}
				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $glosa_cliente, $arr_col[$col_name[$i]]['css']);
			} else if ($col_name[$i] == 'glosa_asunto') {

				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos_glosa, $arr_col[$col_name[$i]]['css']);
			} else if ($col_name[$i] == 'codigo_asunto') {

				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos, $arr_col[$col_name[$i]]['css']);
			} else if ($col_name[$i] == 'fecha_ultimo_pago') {

				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], Utiles::sql2fecha($ultima_fecha_pago, $formato_fechas, "-"), $arr_col[$col_name[$i]]['css']);
			} else if ($col_name[$i] == 'estado_glosa') {
				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
				/* } else if ($col_name[$i] == 'numeracion_excel') {

				  $ws1->writeFormula($fila, $arr_col['numeracion_excel']['celda'], "=SUM(" . $arr_col['numeracion_excel']['celda_excel'] . "$fila + 1)", $arr_col[$col_name[$i]]['css']); */
			} else {
				$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
			}
		}
	}
}
//suma columnas con moneda base
if (in_array(array('monto_pago_moneda_base'), $columnas)) {
	$ws1->writeFormula($fila, $arr_col['monto_pagos_moneda_base']['celda'], "=SUM(" . $arr_col['monto_pagos_moneda_base']['celda_excel'] . "$fila_inicial:" . $arr_col['monto_pagos_moneda_base']['celda_excel'] . "$fila)", $formatos_moneda[$id_moneda_base]);
}
if (in_array(array('saldo_moneda_base'), $columnas)) {
	$ws1->writeFormula($fila, $arr_col['saldo_moneda_base']['celda'], "=SUM(" . $arr_col['saldo_moneda_base']['celda_excel'] . "$fila_inicial:" . $arr_col['saldo_moneda_base']['celda_excel'] . "$fila)", $formatos_moneda[$id_moneda_base]);
}

for ($i = 0; $i < $col_num; ++$i) {
	// ancho celdas
	if (in_array($col_name[$i], $columnas)) {
		if (in_array($col_name[$i], array('descripcion'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 12);
		} else if (in_array($col_name[$i], array('numero', 'cobro', 'id_cobro'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
		} else if (in_array($col_name[$i], array('fecha_ultimo_pago'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 11);
		} else if (in_array($col_name[$i], array('tipo', 'estado_glosa'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
		} else if (in_array($col_name[$i], array('glosa_cliente', 'glosa_asunto', 'cliente_facturable'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 20);
		} else if (in_array($col_name[$i], array('encargado_comercial', 'codigo_asunto'))) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 15);
		} else {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 12);
		}
	}

}

$wb->close();
exit;
?>
