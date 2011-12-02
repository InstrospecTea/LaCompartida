<?php

require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Buscador.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/Cobro.php';
require_once Conf::ServerDir() . '/../app/classes/Funciones.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/Asunto.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../app/classes/Factura.php';

$Sesion = new Sesion(array('ADM', 'COB'));
$pagina = new Pagina($Sesion);

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

if ($orden == "")
			$orden = "fecha DESC";

if ($where == '') {
		$join = "";
		$where = 1;
		/*
			 * INICIO - obtener listado facturas con pago parcial o total
			 */
			$lista_facturas_con_pagos = '';
			$where = 1;
	if ( UtilesApp::GetConf($Sesion, 'SelectMultipleFacturasPago') ) {
		if ( isset($_REQUEST['id_concepto']) ) {
			$condiciones = "";
			foreach( $_REQUEST['id_concepto'] as $key => $value )
			{
				if( strlen( $condiciones ) > 0 ){
					$condiciones .= " OR ";
				}
				$condiciones .= " fp.id_concepto = '$value' ";
			}
			$where .= " AND ( $condiciones ) ";
		}
		if ( isset($_REQUEST['id_banco']) ) {
			$condiciones = "";
			foreach( $_REQUEST['id_banco'] as $key => $value )
			{
				if( strlen( $condiciones ) > 0 ){
					$condiciones .= " OR ";
				}
				$condiciones .= " fp.id_banco = '$value' ";
			}
			$where .= " AND ( $condiciones ) ";
		}
		if ( isset($_REQUEST['id_cuenta']) ) {
			$condiciones = "";
			foreach( $_REQUEST['id_cuenta'] as $key => $value )
			{
				if( strlen( $condiciones ) > 0 ){
					$condiciones .= " OR ";
				}
				$condiciones .= " fp.id_cuenta = '$value' ";
			}
			$where .= " AND ( $condiciones ) ";
		}
		if ( isset($_REQUEST['id_estado']) ) {
			$condiciones = "";
			foreach( $_REQUEST['id_estado'] as $key => $value )
			{
				if( strlen( $condiciones ) > 0 ){
					$condiciones .= " OR ";
				}
				$condiciones .= " factura.id_estado = '$value' ";
			}
			$where .= " AND ( $condiciones ) ";
		}
	} else {
		if ($id_concepto) {
			$where .= " AND fp.id_concepto = '$id_concepto' ";
		}
		if ($id_banco) {
			$where .= " AND fp.id_banco = '$id_banco' ";
		}
		if ($id_cuenta) {
			$where .= " AND fp.id_cuenta = '$id_cuenta' ";
		}			
		if ($id_estado) {
			$where .= " AND factura.id_estado = '$id_estado' ";
		}
	}
	if ($pago_retencion)
		$where .= " AND fp.pago_retencion = '" . $pago_retencion . "' ";
	if ($fecha1 && $fecha2)
		$where .= " AND fp.fecha BETWEEN '" . Utiles::fecha2sql($fecha1) . " 00:00:00' AND '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";
	else if ($fecha1)
		$where .= " AND fp.fecha >= '" . Utiles::fecha2sql($fecha1) . ' 00:00:00' . "' ";
	else if ($fecha2)
		$where .= " AND fp.fecha <= '" . Utiles::fecha2sql($fecha2) . ' 23:59:59' . "' ";


			
			/*
			 * INICIO - obtener listado facturas con pago parcial o total
			 */

	if ($numero != '') {
		$where .= " AND factura.numero = '$numero'";
	}
			if($serie != '' and $serie != -1)
				$where .= " AND serie_documento_legal = '$serie'";

	if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario) {
		$cliente = new Cliente($Sesion);
		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
		$codigo_cliente = $cliente->fields['codigo_cliente'];
	}
	if ($tipo_documento_legal_buscado) {
				$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";
	}

	if ($codigo_cliente) {
		$where .= " AND factura.codigo_cliente='" . $codigo_cliente . "' ";
				}
	if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario) {
		$asunto = new Asunto($Sesion);
		$asunto->LoadByCodigoSecundario($codigo_cliente_secundario);
		$id_contrato = $asunto->fields['id_contrato'];
	}
	if ($codigo_asunto) {
		$asunto = new Asunto($Sesion);
		$asunto->LoadByCodigo($codigo_asunto);
		$id_contrato = $asunto->fields['id_contrato'];
				}
	if ($id_contrato) {
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
	if ($razon_social) {
		$where .= " AND factura.cliente LIKE '%" . $razon_social . "%'";
	}
	if ($descripcion_factura) {
		$where .= " AND (fp.descripcion LIKE '%" . $descripcion_factura . "%' OR factura.descripcion_subtotal_gastos LIKE '%" . $descripcion_factura . "%' OR factura.descripcion_subtotal_gastos_sin_impuesto LIKE '%" . $descripcion_factura . "%')";
	}

} else {
	$where = base64_decode($where);
}

	$numero_factura = "";
	if (UtilesApp::GetConf($sesion, 'NumeroFacturaConSerie'))
	{
		$numero_factura = "CONCAT(LPAD(factura.serie_documento_legal, 3, '0'), '-', factura.numero) as numero";
	}
	else
	{
		$numero_factura = "factura.numero";
	}

	$query = "SELECT
					 factura.id_factura
					, DATE_FORMAT(fp.fecha, '$formato_fechas') as fecha_pago
					, prm_documento_legal.codigo as tipo
					, $numero_factura";
        
if (method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'NuevoModuloFactura')) {
		$query .= "			, cliente as cliente_facturable";
	}
$query .= "			, cliente.glosa_cliente
					, usuario.username AS encargado_comercial
					, prm_estado_factura.glosa as estado_factura
					, factura.id_cobro
					, prm_factura_pago_concepto.glosa as concepto
					, fp.descripcion as descripcion_pago
					, b.nombre as banco
					, cta.numero as cuenta
					, DATE_FORMAT(factura.fecha, '$formato_fechas') as fecha_factura
					, ( factura.honorarios + factura.subtotal_gastos + factura.subtotal_gastos_sin_impuesto ) as subtotal_factura
					, factura.iva
					, factura.total
					, fp.monto AS monto_aporte
					, -1 * ccfm2.saldo as saldo_factura
					, ccfm.saldo as saldo
					, fp.id_moneda AS id_moneda_factura_pago
					, factura.id_moneda
					, if(factura.RUT_cliente != contrato.rut,factura.cliente,'no' ) as mostrar_diferencia_razon_social
				FROM factura_pago AS fp
				JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
				JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
				LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
				LEFT JOIN factura ON ccfm2.id_factura = factura.id_factura
				LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
				LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
				LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
				LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
				LEFT JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
				LEFT JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
				LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
				LEFT JOIN prm_factura_pago_concepto ON prm_factura_pago_concepto.id_concepto = fp.id_concepto
				LEFT JOIN prm_banco b ON fp.id_banco = b.id_banco
				LEFT JOIN cuenta_banco cta ON fp.id_cuenta = cta.id_cuenta
				WHERE $where";
/*echo $query;
exit;*/

$lista_suntos_liquidar = new ListaAsuntos($Sesion, "", $query);
if ($lista_suntos_liquidar->num == 0)
	$pagina->FatalError('No existe información con este criterio');

$fecha_actual = date('Y-m-d');

$col = 0;
$col_tipo = $col++;

// Crear y preparar planilla
$wb = new Spreadsheet_Excel_Writer();
// Enviar headers a la pagina
$wb->send(__('Pago de Documentos tributarios') . ' ' . $fecha_actual . '.xls');

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
$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
$id_moneda_base = 0;
while (list($id_moneda, $simbolo_moneda, $cifras_decimales, $moneda_base, $tipo_cambio) = mysql_fetch_array($resp)) {
	
	if ($moneda_base == 1) {
		$id_moneda_base = $id_moneda;
		$cifras_decimales_moneda_base = $cifras_decimales;
		$tipo_cambio_moneda_base = $tipo_cambio;
		$simbolo_moneda_base = $simbolo_moneda;
	}
	
	if ($cifras_decimales > 0) {
			$decimales = '.';
		while ($cifras_decimales-- > 0) {
				$decimales .= '0';
		}
	} else {
			$decimales = '';
		}
	$formatos_moneda[$id_moneda] = & $wb->addFormat(array('Size' => 10,
				'VAlign' => 'top',
				'Align' => 'right',
				'Color' => 'black',
				'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
}

	// Crear worksheet
$ws1 = & $wb->addWorksheet(__('Pago de Documentos tributarios'));
	$ws1->setLandscape();

	$col_name = array_keys($lista_suntos_liquidar->Get()->fields);
	$col_num = count($lista_suntos_liquidar->Get()->fields);

	
	$col_name['numero'] = $factura->ObtenerNumero(null, $col_name['serie_documento_legal'], $col_name['numero']);

	// Definimos visible, css, ancho y titulo de cada celda
	$arr_col = array();
	$col = 0;
for ($i = 0; $i < $col_num; ++$i) {
	// ocultar celdas con PHP	
	$cols_para_ocultar = array('simbolo', 'cifras_decimales', 'id_moneda', 'id_factura', 'id_moneda_factura_pago', 'mostrar_diferencia_razon_social');
	if( !UtilesApp::GetConf($Sesion, 'FacturaPagoSubtotalIva') )
	{
		$cols_para_ocultar[] = "subtotal_factura";
		$cols_para_ocultar[] = "iva";
	}
	
	if (in_array($col_name[$i], $cols_para_ocultar )) {
		$arr_col[$col_name[$i]]['hidden'] = 'SI';
	} else {
		$arr_col[$col_name[$i]]['celda'] = $col++;
	}

		// ancho celdas
	if (in_array($col_name[$i], array('saldo_moneda_base'))) {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 11);
	} else if (in_array($col_name[$i], array('numero', 'cobro', 'saldo_pagos', 'id_cobro', 'pago_retencion', 'banco', 'cuenta'))) {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 8);
	} else if (in_array($col_name[$i], array('fecha_ultimo_pago', 'glosa_asunto', 'glosa_cliente', 'concepto', 'descripcion_pago'))) {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 9);
	} else if (in_array($col_name[$i], array('tipo', 'estado_factura', 'id_pago'))) {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 3);
	} else if (in_array($col_name[$i], array('total', 'monto_aporte', 'saldo_factura', 'saldo'))) {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 13);
	} else {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10);
	}

		// ancho celdas ocultos
	$para_ocultar = array('id_moneda_factura_pago', 'descripcion', 'encargado_comercial', 'id_cobro', 'monto_pagos_moneda_base', 'saldo_moneda_base', 'tipo_cambio', 'codigo_asunto', 'honorarios');
	
	//FacturaPagoSubtotalIva
	if( !UtilesApp::GetConf($Sesion, 'FacturaPagoSubtotalIva') )
	{
		$para_ocultar[] = "subtotal_factura";
		$para_ocultar[] = "iva";
	}
	
	if (in_array($col_name[$i], $para_ocultar)) {
		$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 0, 0, 1);
	}

		// css celdas
	if (in_array($col_name[$i], array('fecha', 'fecha_ultimo_pago'))) {
		$arr_col[$col_name[$i]]['css'] = $formato_fecha_tiempo;
	} else if (in_array($col_name[$i], array('glosa_cliente', 'descripcion', 'glosa_asunto', 'concepto', 'descripcion_pago'))) {
		$arr_col[$col_name[$i]]['css'] = $formato_descripcion;
	} else {
		$arr_col[$col_name[$i]]['css'] = $formato_normal;
	}

		// titulos celdas
	if (in_array($col_name[$i], array('glosa_cliente'))) {
		$arr_col[$col_name[$i]]['titulo'] = __('Cliente');
		}
	if (method_exists('Conf', 'GetConf') && Conf::GetConf($Sesion, 'NuevoModuloFactura')) {
		if (in_array($col_name[$i], array('cliente_facturable'))) {
			$arr_col[$col_name[$i]]['titulo'] = __('Cliente Facturable');
		}
	}

	switch ($col_name[$i]) {
		case 'fecha_pago':				$titulo_columna = __('Fecha'); break;
		case 'numero':					$titulo_columna = __('N°'); break;
		case 'glosa_cliente':			$titulo_columna = __('Cliente'); break;
		case 'encargado_comercial':		$titulo_columna = __('Abogado'); break;
		case 'estado_factura':			$titulo_columna = __('Estado'); break;
		case 'id_cobro':				$titulo_columna = __('Cobro'); break;
		case 'concepto':				$titulo_columna = __('Concepto Pago'); break;
		case 'descripcion_pago':		$titulo_columna = __('Descripción Pago'); break;
		case 'subtotal_factura':		$titulo_columna = __('Valor de Venta'); break;
		case 'iva':						$titulo_columna = __('IVA'); break;
		case 'total':					$titulo_columna = __('Monto Factura'); break;
		case 'monto_aporte':			$titulo_columna = __('Monto Pago'); break;
		case 'saldo_factura':			$titulo_columna = __('Saldo Factura'); break;
		case 'saldo':					$titulo_columna = __('Saldo Pago'); break;
		default: $titulo_columna = str_replace('_', ' ', $col_name[$i]); break;

	}

	$arr_col[$col_name[$i]]['titulo'] = $titulo_columna;

		//formato columna excel para formulas
		$arr_col[$col_name[$i]]['celda_excel'] = Utiles::NumToColumnaExcel($arr_col[$col_name[$i]]['celda']);
	}
	unset($col);
	$fila = 0; //fila inicial

	// Escribir encabezado reporte
	$ws1->write($fila, 0, __('Pago de documentos tributarios'), $formato_encabezado);
	$fila++;

	$ws1->write($fila, 0, $fecha_actual, $formato_encabezado);
	$fila++;
	$fila++;
	
	// Escribir titulos
for ($i = 0; $i < $col_num; ++$i) {
	if ($arr_col[$col_name[$i]]['hidden'] != 'SI') {
			$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], ucfirst($arr_col[$col_name[$i]]['titulo']), $formato_titulo);
		}
	}
	//$ws1->writeNote($fila, $arr_col['hh_val_cobrado']['celda'], 'Work in Progress');
	$fila++;
	$ws1->freezePanes(array($fila));

	$fila_inicial = $fila;
	// Escribir filas
for ($j = 0; $j < $lista_suntos_liquidar->num; ++$j, ++$fila) {
	$fila_actual = $fila + 1;
		$proc = $lista_suntos_liquidar->Get($j);
		$ws1->write($fila, $col_glosa_cliente, $proc->fields[$col_name[$i]], $formato_normal);

		$query = "SELECT GROUP_CONCAT(ca.codigo_asunto SEPARATOR ', ') , GROUP_CONCAT(a.glosa_asunto SEPARATOR ', ')
					FROM cobro_asunto ca
					LEFT JOIN asunto a ON ca.codigo_asunto = a.codigo_asunto
					WHERE ca.id_cobro='" . $proc->fields['id_cobro'] . "' GROUP BY ca.id_cobro";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
		$lista_asuntos = '';
		$lista_asuntos_glosa = '';
	while (list($lista_codigo_asunto, $lista_glosa_asunto) = mysql_fetch_array($resp)) {
		$lista_asuntos = '(' . $lista_codigo_asunto . ')';
			$lista_asuntos_glosa = $lista_glosa_asunto;
		}

	for ($i = 0; $i < $col_num; $i++) {
		if ($arr_col[$col_name[$i]]['hidden'] != 'SI') {
			if ($col_name[$i] == 'total' || $col_name[$i] == 'honorarios' || $col_name[$i] == 'subtotal_factura' || $col_name[$i] == 'iva') {
					$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'saldo' ) {
				$saldo = $proc->fields[$col_name[$i]] * (-1);
					$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo, $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ( $col_name[$i] == 'saldo_factura') {
				$saldo = $proc->fields[$col_name[$i]];
				$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo, $formatos_moneda[$proc->fields['id_moneda']]);
			} else if ($col_name[$i] == 'monto_aporte') {
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields['monto_aporte'], $formatos_moneda[$proc->fields['id_moneda_factura_pago']]);
			} else if ($col_name[$i] == 'glosa_cliente') {
					$glosa_cliente = $proc->fields['glosa_cliente'];
				if ($proc->fields['mostrar_diferencia_razon_social'] != 'no') {
					$glosa_cliente .= " (" . $proc->fields['mostrar_diferencia_razon_social'] . ")";
					}
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $glosa_clientes, $arr_col[$col_name[$i]]['css']);
			} else if ($col_name[$i] == 'glosa_asunto') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos_glosa, $arr_col[$col_name[$i]]['css']);
			} else if ($col_name[$i] == 'codigo_asunto') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos, $arr_col[$col_name[$i]]['css']);
                        } else if ($col_name[$i] == 'serie') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]] . " ", $arr_col[$col_name[$i]]['css']);
			} else {
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
				}
			}
		}
	}

	$wb->close();
	exit;
?>
