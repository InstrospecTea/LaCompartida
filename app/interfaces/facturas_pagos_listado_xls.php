<?php
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Asunto.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/Factura.php';

	$sesion = new Sesion(array('ADM', 'COB'));
	set_time_limit(0);
	ini_set("memory_limit","256M");
	$where_cobro = ' 1 ';

//void Worksheet::setLandscape();
	$contrato = new Contrato($sesion);

	// Esta variable se usa para que cada página tenga un nombre único.
	$numero_pagina = 0;
		
	// Buscar todos los borradores o cargar de nuevo el cobro especifico que hay que imprimir

	$id_moneda_filtro = $id_moneda;

	if($orden == "")
			$orden = "fecha DESC";

	if($where == '')
	{
		$join = "";
		$where = 1;
		/*
			 * INICIO - obtener listado facturas con pago parcial o total
			 */
			$lista_facturas_con_pagos = '';
			$where_pactura_pago = 1;
			if( $id_concepto )
				$where_pactura_pago .= " AND fp.id_concepto = '".$id_concepto."' ";
			if( $id_banco )
				$where_pactura_pago .= " AND fp.id_banco = '".$id_banco."' ";
			if( $id_cuenta )
				$where_pactura_pago .= " AND fp.id_cuenta = '".$id_cuenta."' ";
			if( $pago_retencion )
				$where_pactura_pago .= " AND fp.pago_retencion = '".$pago_retencion."' ";
			if($fecha1 && $fecha2)
				$where_pactura_pago .= " AND fp.fecha BETWEEN '".Utiles::fecha2sql($fecha1)." 00:00:00' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
			else if( $fecha1 )
				$where_pactura_pago .= " AND fp.fecha >= '".Utiles::fecha2sql($fecha1).' 00:00:00'."' ";
			else if( $fecha2 )
				$where_pactura_pago .= " AND fp.fecha <= '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";


			$query_facturas_con_pago = "SELECT GROUP_CONCAT(ccfm2.id_factura) as lista_factiras_con_pagos, '1' as con_pago
					FROM factura_pago AS fp
					JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
					JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
					LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
					LEFT JOIN prm_moneda mo ON ccfm.id_moneda = mo.id_moneda
					WHERE $where_pactura_pago GROUP BY con_pago ";
			//echo "<br>".$query_facturas_con_pago;
			$resp_facturas_con_pago = mysql_query($query_facturas_con_pago, $sesion->dbh) or Utiles::errorSQL($query_facturas_con_pago, __FILE__, __LINE__, $sesion->dbh);
			list($lista_facturas_con_pagos,$con_pago) = mysql_fetch_array($resp_facturas_con_pago);
			if($lista_facturas_con_pagos!='')
				$where .= " AND factura.id_factura IN (".$lista_facturas_con_pagos.")";
			else
				$where .= " AND factura.id_factura = 0 ";
			/*
			 * INICIO - obtener listado facturas con pago parcial o total
			 */

			if($numero != '')
				$where .= " AND numero = '$numero'";

			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario )
				{
					$cliente = new Cliente($sesion);
					$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
					$codigo_cliente = $cliente->fields['codigo_cliente'];
				}
			if($tipo_documento_legal_buscado)
				$where .= " AND factura.id_documento_legal = '$tipo_documento_legal_buscado' ";

			if($codigo_cliente)
				{
				$where .= " AND factura.codigo_cliente='".$codigo_cliente."' ";
				}
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) ) && $codigo_cliente_secundario)
				{
					$asunto = new Asunto($sesion);
					$asunto->LoadByCodigoSecundario($codigo_cliente_secundario);
					$id_contrato = $asunto->fields['id_contrato'];
				}
			if($codigo_asunto)
				{
					$asunto = new Asunto($sesion);
					$asunto->LoadByCodigo($codigo_asunto);
					$id_contrato = $asunto->fields['id_contrato'];
				}
			if($id_contrato)
				{
					$where .= " AND cobro.id_contrato=".$id_contrato." ";
				}
			if($id_cobro)
				{
					$where .= " AND factura.id_cobro=".$id_cobro." ";
				}
			if($id_estado)
				{
					$where .= " AND factura.id_estado = ".$id_estado." ";
				}
			if($id_moneda)
				{
					$where .= " AND factura.id_moneda = ".$id_moneda." ";
				}
			if($grupo_ventas)
			{
				$where .= " AND prm_documento_legal.grupo = 'VENTAS' ";
			}
	}
	else
		$where = base64_decode($where);

	$query = "SELECT  fecha
					, prm_documento_legal.glosa as tipo
					, numero
					, glosa_cliente
					, '' glosa_asunto
					, '' codigo_asunto
					, usuario.username AS encargado_comercial
					, descripcion
					, prm_estado_factura.glosa as estado
					, factura.id_cobro
					, prm_moneda.simbolo
					, prm_moneda.cifras_decimales
					, prm_moneda.tipo_cambio
					, factura.id_moneda
					, '' as pago_retencion
					, '' as concepto
					, '' as descripcion_pago
					, '' as banco
					, '' as cuenta
					, '' as fecha_ultimo_pago
					, factura.honorarios
					, factura.iva
					, total
					, '' as saldo_pagos
					, cta_cte_fact_mvto.saldo as saldo
					, '' as monto_pagos_moneda_base
					, '' as saldo_moneda_base
					, factura.id_factura
				FROM factura
				JOIN prm_documento_legal ON (factura.id_documento_legal = prm_documento_legal.id_documento_legal)
				JOIN prm_moneda ON prm_moneda.id_moneda=factura.id_moneda
				LEFT JOIN prm_estado_factura ON prm_estado_factura.id_estado = factura.id_estado
				LEFT JOIN cta_cte_fact_mvto ON cta_cte_fact_mvto.id_factura = factura.id_factura
				LEFT JOIN cobro ON cobro.id_cobro=factura.id_cobro
				LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
				LEFT JOIN contrato ON contrato.id_contrato=cobro.id_contrato
				LEFT JOIN usuario ON usuario.id_usuario=contrato.id_usuario_responsable
				$join
				WHERE $where";

	$lista_suntos_liquidar = new ListaAsuntos($sesion, "", $query);
	if($lista_suntos_liquidar->num == 0)
		$pagina->FatalError('No existe información con este criterio');

	$fecha_actual = date('Y-m-d');
	
	// Crear y preparar planilla
	$wb = new Spreadsheet_Excel_Writer();
	// Enviar headers a la pagina
	$wb->send( __('Pago de Documentos tributarios') .' '. $fecha_actual.'.xls');

	// Definir colores
	$wb->setCustomColor ( 35, 220, 255, 220 );
	$wb->setCustomColor ( 36, 255, 255, 220 );

	// Crear formatos de celda
	$formato_encabezado =& $wb->addFormat(array('Size' => 12,
											'VAlign' => 'top',
											'Align' => 'left',
											'Bold' => '1',
											'underline'=>1,
											'Color' => 'black'));
	$formato_titulo =& $wb->addFormat(array('Size' => 10,
											'VAlign' => 'top',
											'Bold' => 1,
											'Locked' => 1,
											'Bottom' => 1,
											'FgColor' => 35,
											'Color' => 'black'));
	$formato_normal =& $wb->addFormat(array('Size' => 10,
											'VAlign' => 'top',
											'Color' => 'black'));
	$formato_descripcion =& $wb->addFormat(array('Size' => 10,
											'VAlign' => 'top',
											'Align' => 'left',
											'Color' => 'black',
											'TextWrap' => 1));
	$formato_tiempo =& $wb->addFormat(array('Size' => 10,
											'VAlign' => 'top',
											'Color' => 'black',
											'NumFormat' =>'[h]:mm'));
	$formato_fecha_tiempo =& $wb->addFormat(array('Size' => 10,
											'VAlign' => 'top',
											'Color' => 'black',
											'TextWrap' => 1));
	$formato_total =& $wb->addFormat(array('Size' => 10,
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
	$id_moneda_base=0;
	while(list($id_moneda, $simbolo_moneda, $cifras_decimales, $moneda_base, $tipo_cambio) = mysql_fetch_array($resp)){
		if($cifras_decimales>0) {
			$decimales = '.';
			while($cifras_decimales-- >0)
				$decimales .= '0';
		}
		else {
			$decimales = '';
		}
		$formatos_moneda[$id_moneda] =& $wb->addFormat(array('Size' => 10,
															'VAlign' => 'top',
															'Align' => 'right',
															'Color' => 'black',
															'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));

		if($moneda_base==1) {
			$id_moneda_base = $id_moneda;
			$cifras_decimales_moneda_base = $cifras_decimales;
			$tipo_cambio_moneda_base = $tipo_cambio;
			$simbolo_moneda_base = $simbolo_moneda;
		}
	}

	// Crear worksheet
	$ws1 =& $wb->addWorksheet( __('Pago de Documentos tributarios'));
	$ws1->setLandscape();

	$col_name = array_keys($lista_suntos_liquidar->Get()->fields);
	$col_num = count($lista_suntos_liquidar->Get()->fields);

	// Definimos visible, css, ancho y titulo de cada celda
	$arr_col = array();
	$col = 0;
	for($i=0; $i<$col_num; ++$i)
	{
		// ocultar celdas con PHP
		if(in_array($col_name[$i],array('simbolo','cifras_decimales','id_moneda','id_factura')) ) {
			$arr_col[$col_name[$i]]['hidden'] = 'SI'; }
		else { $arr_col[$col_name[$i]]['celda'] = $col++; }

		

		// ancho celdas
		if(in_array($col_name[$i],array('saldo','saldo_moneda_base')) ) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 11); }
		else if(in_array($col_name[$i],array('numero','cobro','tipo','saldo_pagos','estado','id_cobro','pago_retencion','banco','cuenta')) ) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 8); }
		else if(in_array($col_name[$i],array('fecha_ultimo_pago','glosa_asunto','glosa_cliente','concepto','descripcion_pago')) ) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 9); }
		else { $ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 10); }

		// ancho celdas ocultos
		if(in_array($col_name[$i],array('descripcion','encargado_comercial','total','id_cobro','monto_pagos_moneda_base','saldo_moneda_base','tipo_cambio','codigo_asunto','iva','honorarios')) ) {
			$ws1->setColumn($arr_col[$col_name[$i]]['celda'], $arr_col[$col_name[$i]]['celda'], 0,0,1); }

		// css celdas
		if(in_array($col_name[$i],array('fecha','fecha_ultimo_pago')) ) {
			$arr_col[$col_name[$i]]['css'] = $formato_fecha_tiempo; }
		else if(in_array($col_name[$i],array('glosa_cliente','descripcion','glosa_asunto','concepto','descripcion_pago')) ) {
			$arr_col[$col_name[$i]]['css'] = $formato_descripcion; }
		else { $arr_col[$col_name[$i]]['css'] = $formato_normal; }

		// titulos celdas
		if(in_array($col_name[$i],array('glosa_cliente')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Cliente'); }
		if(in_array($col_name[$i],array('glosa_asunto')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Asuntos'); }
		else if(in_array($col_name[$i],array('encargado_comercial')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Abogado'); }
		else if(in_array($col_name[$i],array('id_cobro')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Cobro'); }
		else if(in_array($col_name[$i],array('iva')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('IVA'); }
		else if(in_array($col_name[$i],array('saldo_pagos')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Pagos'); }
		else if(in_array($col_name[$i],array('honorarios')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Subtotal'); }
		else if(in_array($col_name[$i],array('saldo_moneda_base')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Saldo'.' '.$simbolo_moneda_base); }
		else if(in_array($col_name[$i],array('monto_pagos_moneda_base')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Pagos'.' '.$simbolo_moneda_base); }
		else if(in_array($col_name[$i],array('fecha_ultimo_pago')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Fecha último pago'); }
		else if(in_array($col_name[$i],array('pago_retencion')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Pago retención impuestos'); }
		else if(in_array($col_name[$i],array('descripcion_pago')) ) {
			$arr_col[$col_name[$i]]['titulo'] = __('Descripcion pago'); }
		else { $arr_col[$col_name[$i]]['titulo'] = str_replace('_',' ',$col_name[$i]); }

		//formato columna excel para formulas
		$arr_col[$col_name[$i]]['celda_excel'] = Utiles::NumToColumnaExcel($arr_col[$col_name[$i]]['celda']);

	}
	unset($col);
	$fila = 0; //fila inicial

	$col_formula_pago = $arr_col['monto_pagos_moneda_base']['celda_excel'];
	$col_formula_saldo = $arr_col['saldo_moneda_base']['celda_excel'];

	// Escribir encabezado reporte
	$ws1->write($fila, 0, __('Pago de documentos tributarios'), $formato_encabezado);
	$fila++;

	$ws1->write($fila, 0, $fecha_actual, $formato_encabezado);
	$fila++;
	$fila++;
	
	// Escribir titulos
	for($i=0; $i<$col_num; ++$i)
	{
		if($arr_col[$col_name[$i]]['hidden'] != 'SI')
		{
			$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], ucfirst($arr_col[$col_name[$i]]['titulo']), $formato_titulo);
		}
	}
	//$ws1->writeNote($fila, $arr_col['hh_val_cobrado']['celda'], 'Work in Progress');
	$fila++;
	$ws1->freezePanes(array($fila));

	$fila_inicial = $fila;
	// Escribir filas
	for($j=0; $j<$lista_suntos_liquidar->num; ++$j, ++$fila) {
		$fila_actual = $fila+1;
		$proc = $lista_suntos_liquidar->Get($j);
		$ws1->write($fila, $col_glosa_cliente, $proc->fields[$col_name[$i]], $formato_normal);

		$query = "SELECT GROUP_CONCAT(ca.codigo_asunto SEPARATOR ', ') , GROUP_CONCAT(a.glosa_asunto SEPARATOR ', ')
					FROM cobro_asunto ca
					LEFT JOIN asunto a ON ca.codigo_asunto = a.codigo_asunto
					WHERE ca.id_cobro='".$proc->fields['id_cobro']."' GROUP BY ca.id_cobro";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$lista_asuntos = '';
		$lista_asuntos_glosa = '';
		while(list($lista_codigo_asunto,$lista_glosa_asunto) = mysql_fetch_array($resp)){
			$lista_asuntos = '('.$lista_codigo_asunto.')';
			$lista_asuntos_glosa = $lista_glosa_asunto;
		}

		$query = "SELECT  SUM(ccfmn.monto) as monto_aporte
						, MAX(ccfm.fecha_modificacion) as ultima_fecha_pago
						, b.nombre as banco
						, cta.numero as cuenta
						, fp.descripcion as descripcion
						, co.glosa as concepto
						, if(fp.pago_retencion=1,'Si','No') as pago_retencion
					FROM factura_pago AS fp
					JOIN cta_cte_fact_mvto AS ccfm ON fp.id_factura_pago = ccfm.id_factura_pago
					JOIN cta_cte_fact_mvto_neteo AS ccfmn ON ccfmn.id_mvto_pago = ccfm.id_cta_cte_mvto
					LEFT JOIN cta_cte_fact_mvto AS ccfm2 ON ccfmn.id_mvto_deuda = ccfm2.id_cta_cte_mvto
					LEFT JOIN prm_banco b ON fp.id_banco = b.id_banco
					LEFT JOIN cuenta_banco cta ON fp.id_cuenta = cta.id_cuenta
					LEFT JOIN prm_factura_pago_concepto AS co ON fp.id_concepto = co.id_concepto
					WHERE ccfm2.id_factura =  '".$proc->fields['id_factura']."' GROUP BY ccfm2.id_factura ";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$monto_pago = 0;
		list($monto_pago, $ultima_fecha_pago, $banco, $cuenta, $descripcion_pago, $concepto, $pago_retencion) = mysql_fetch_array($resp);
			
		

		for($i=0; $i<$col_num; $i++) {
	        if($arr_col[$col_name[$i]]['hidden'] != 'SI') {
				if($col_name[$i] == 'total' || $col_name[$i] == 'honorarios' || $col_name[$i] == 'iva') {
					$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $formatos_moneda[$proc->fields['id_moneda']]);
				}
				else if($col_name[$i] == 'saldo') {
					$saldo = $proc->fields[$col_name[$i]]*(-1);
					$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo, $formatos_moneda[$proc->fields['id_moneda']]);
				}
				else if($col_name[$i] == 'saldo_pagos') {
					//$factura = new Factura($sesion);
					//$lista_pagos_fact = $factura->GetPagosSoyFactura($proc->fields['id_factura']);
					
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $monto_pago, $formatos_moneda[$proc->fields['id_moneda']]);
				}
				else if($col_name[$i] == 'saldo_moneda_base') {
					$saldo_moneda_base = UtilesApp::CambiarMoneda($saldo, $proc->fields['tipo_cambio'], $proc->fields['cifras_decimales'], $tipo_cambio_moneda_base,$cifras_decimales_moneda_base,false);
					$ws1->writeFormula($fila, $arr_col['saldo_moneda_base']['celda'], "=".$arr_col['saldo']['celda_excel']."$fila_actual*".$arr_col['tipo_cambio']['celda_excel']."$fila_actual", $formatos_moneda[$id_moneda_base]);
					//$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $saldo_moneda_base, $formatos_moneda[$id_moneda_base]);
				}
				else if($col_name[$i] == 'monto_pagos_moneda_base') {
					$monto_pago_moneda_base = UtilesApp::CambiarMoneda($monto_pago, $proc->fields['tipo_cambio'], $proc->fields['cifras_decimales'], $tipo_cambio_moneda_base,$cifras_decimales_moneda_base,false);
					$ws1->writeFormula($fila, $arr_col['monto_pagos_moneda_base']['celda'], "=".$arr_col['saldo_pagos']['celda_excel']."$fila_actual*".$arr_col['tipo_cambio']['celda_excel']."$fila_actual", $formatos_moneda[$id_moneda_base]);
					//$ws1->writeNumber($fila, $arr_col[$col_name[$i]]['celda'], $monto_pago_moneda_base, $formatos_moneda[$id_moneda_base]);
				}
				if($col_name[$i] == 'glosa_cliente') {
					
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
				}
				if($col_name[$i] == 'glosa_asunto') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos_glosa, $arr_col[$col_name[$i]]['css']);
				}
				if($col_name[$i] == 'codigo_asunto') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $lista_asuntos, $arr_col[$col_name[$i]]['css']);
				}
				if($col_name[$i] == 'fecha_ultimo_pago') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $ultima_fecha_pago, $arr_col[$col_name[$i]]['css']);
				}

				if($col_name[$i] == 'pago_retencion') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $pago_retencion, $arr_col[$col_name[$i]]['css']);
				}

				if($col_name[$i] == 'concepto') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $concepto, $arr_col[$col_name[$i]]['css']);
				}

				if($col_name[$i] == 'descripcion_pago') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $descripcion_pago, $arr_col[$col_name[$i]]['css']);
				}

				if($col_name[$i] == 'banco') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $banco, $arr_col[$col_name[$i]]['css']);
				}

				if($col_name[$i] == 'cuenta') {

					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $cuenta, $arr_col[$col_name[$i]]['css']);
				}

				else {
					$ws1->write($fila, $arr_col[$col_name[$i]]['celda'], $proc->fields[$col_name[$i]], $arr_col[$col_name[$i]]['css']);
				}
			}
		}
	}
	//suma columnas con moneda base
	$ws1->writeFormula($fila, $arr_col['monto_pagos_moneda_base']['celda'], "=SUM(".$arr_col['monto_pagos_moneda_base']['celda_excel']."$fila_inicial:".$arr_col['monto_pagos_moneda_base']['celda_excel']."$fila)", $formatos_moneda[$id_moneda_base]);
	$ws1->writeFormula($fila, $arr_col['saldo_moneda_base']['celda'], "=SUM(".$arr_col['saldo_moneda_base']['celda_excel']."$fila_inicial:".$arr_col['saldo_moneda_base']['celda_excel']."$fila)", $formatos_moneda[$id_moneda_base]);
	$wb->close();
	exit;
?>
