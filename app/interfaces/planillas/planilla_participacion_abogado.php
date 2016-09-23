<?php
	require_once dirname(__FILE__).'/../../conf.php';

	$sesion = new Sesion(array('REP'));
	//Revisa el Conf si esta permitido

	$pagina = new Pagina($sesion);
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
	$Form = new Form($sesion);
	$Html = new \TTB\Html;

	if ($xls) {
		$moneda_base = Utiles::MonedaBase($sesion);
		#ARMANDO XLS
		$wb = new WorkbookMiddleware();

		$wb->setCustomColor(35, 220, 255, 220);
		$wb->setCustomColor(36, 255, 255, 220);

		$encabezado = & $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));
		$txt_opcion = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_opcion_color = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'FgColor' => '35',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_valor = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_centro = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_centro_color = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'FgColor' => '35',
									'Color' => 'black',
									'TextWrap' => 1));
		$fecha = & $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$numeros = & $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '0'));
		$numeros_color = & $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'FgColor' => '35',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '0'));
		$titulo_filas = & $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));
		$titulo_filas_color = & $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));
		$time_format = & $wb->addFormat(array('Size' => 10,
									'VAlign' => 'top',
									'Align' => 'justify',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '[h]:mm'));

		// Generar formatos para los distintos tipos de moneda
		$formatos_moneda = array();
		$query = 'SELECT id_moneda, simbolo, cifras_decimales
				FROM prm_moneda
				ORDER BY id_moneda';
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$Moneda = new Moneda($sesion);
		while (list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp)) {
			$formato = $Moneda->getExcelFormat($id_moneda);
			$formatos_moneda[$id_moneda] = & $wb->addFormat(array('Size' => 11,
																'VAlign' => 'top',
																'Align' => 'right',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => $formato));
			$formatos_moneda_color[$id_moneda] = & $wb->addFormat(array('Size' => 11,
																'VAlign' => 'top',
																'Align' => 'right',
																'FgColor' => '35',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => $formato));
		}

		$where = "1";

		if (is_array($socios)) {
			$lista_socios = join("','", $socios);
			$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
		}

		if (is_array($clientes)) {
			$lista_clientes = join("','", $clientes);
			$where .= " AND cliente.codigo_cliente IN ('".$lista_clientes."')";
		}

		if ($fecha_ini != '' and $fecha_fin != '' and $rango == 1 and is_array($estados)) {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}

			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}

			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}

			if ($estado == 'FACTURADO') {
				$where_estado = 'fecha_facturacion';
			}

			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}

			if ($estado == 'PAGO PARCIAL') {
				$where_estado = 'fecha_pago_parcial';
			}

			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}

			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$_fecha_ini = Utiles::fecha2sql($fecha_ini);
			$_fecha_fin = Utiles::fecha2sql($fecha_fin);

			$where .= " AND cobro.{$where_estado} BETWEEN '{$_fecha_ini}' AND '{$_fecha_fin} 23:59:59' ";
		} else if ($fecha_ini != '' and $fecha_fin != '' and $rango == 1) {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}

			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}

			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}

			if ($estado == 'FACTURADO') {
				$where_estado = 'fecha_facturacion';
			}

			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}

			if ($estado == 'PAGO PARCIAL') {
				$where_estado = 'fecha_pago_parcial';
			}

			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}

			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$_fecha_ini = Utiles::fecha2sql($fecha_ini);
			$_fecha_fin = Utiles::fecha2sql($fecha_fin);

			$where .= " AND cobro.{$where_estado} BETWEEN '{$_fecha_ini}' AND '{$_fecha_fin} 23:59:59' ";
		} else if ($fecha_mes != '' and $fecha_anio != '' and is_array($estados)) {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}

			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}

			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}

			if ($estado == 'FACTURADO') {
				$where_estado = 'fecha_facturacion';
			}

			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}

			if ($estado == 'PAGO PARCIAL') {
				$where_estado = 'fecha_pago_parcial';
			}

			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}

			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$where .= " AND cobro.{$where_estado} BETWEEN '{$fecha_anio}-{$fecha_mes}-01 00:00:00' AND '{$fecha_anio}-{$fecha_mes}-31 23:59:59' ";
		} else if ($fecha_mes != '' and $fecha_anio != '') {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}

			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}

			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}

			if ($estado == 'FACTURADO') {
				$where_estado = 'fecha_facturacion';
			}

			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}

			if ($estado == 'PAGO PARCIAL') {
				$where_estado = 'fecha_pago_parcial';
			}

			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}

			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$where .= " AND cobro.{$where_estado} BETWEEN '{$fecha_anio}-{$fecha_mes}-01 00:00:00' AND '{$fecha_anio}-{$fecha_mes}-31 23:59:59' ";
		}

		if (is_array($estados)) {
			$lista_estados = join("','", $estados);
			$where .= " AND cobro.estado IN ('{$lista_estados}')";
		}

		$query_usuarios_total = "SELECT
																trabajo.id_usuario,
																usuario.nombre,
																usuario.apellido1,
																usuario.apellido2,
																usuario.username
												   		FROM trabajo
												   		JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
												   		JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
												   		JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
													  	LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
													  	LEFT JOIN contrato ON cobro.id_contrato = contrato.id_contrato
														  WHERE {$where}
														  GROUP BY id_usuario ORDER BY trabajo.id_usuario";
		$resp_usuarios_total = mysql_query($query_usuarios_total, $sesion->dbh) or Utiles::errorSQL($query_usuarios_total, __FILE__, __LINE__, $sesion->dbh);
		$abogados = array();
		while (list($id, $nombre, $apellido1, $apellido2) = mysql_fetch_array($resp_usuarios_total)) {
			$abogados_datos = array();
			$abogados_datos['id'] = $id;
			$abogados_datos['nombre'] = "{$nombre} {$apellido1} {$apellido2}";
			$abogados[$id] = $abogados_datos;
		}

		$ws1 = & $wb->addWorksheet(__('Facturacion'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,5);
		$ws1->setZoom(75);
		$ws1->hideGridlines();
		$ws1->setLandscape();

		// Definir los n�meros de las columnas
		// El orden que tienen en esta secci�n es el que mantienen en la planilla.
		$hoja_historial = [];
		$col = 0;
		$col_numero_cobro = ++$col;
		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra')) || (method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra()))) {
			$col_nota_cobro = ++$col;
		}
		$col_factura = ++$col;
		$col_fecha_emision = ++$col;
		$col_cliente = ++$col;
		$col_asuntos = ++$col;
		$col_encargado = ++$col;
		foreach($abogados as $abogado => $data) {
			$col_usuario[$data['id']] = ++$col;
			$col_tarifa_usuario[$data['id']] = ++$col;
			$col_tarifa_estandar_usuario[$data['id']] = ++$col;
			$col_valor_usuario[$data['id']] = ++$col;
			$col_valor_moneda_usuario[$data['id']] = ++$col;
		}
		$col_horas_trabajadas = ++$col;
		$col_horas_cobradas = ++$col;
		$col_total_cobro_original = ++$col;
		$col_total_cobro_tarifa_estandar = ++$col;
		$col_honorarios_estandar = ++$col;
		$col_rendimiento = ++$col;
		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
			$col_total_con_iva = ++$col;
		} else {
			$col_total_cobro = ++$col;
		}
		$col_honorarios = ++$col;
		$col_gastos = ++$col;
		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
			$col_iva = ++$col;
		}
		$col_monto_pago_honorarios = ++$col;
		$col_monto_pago_gastos = ++$col;
		$col_estado = ++$col;
		$col_fecha_creacion = ++$col;
		$col_fecha_revision = ++$col;
		$col_fecha_corte = ++$col;
		$col_fecha_facturacion = ++$col;
		$col_fecha_envio_a_cliente = ++$col;
		$col_fecha_pago = ++$col;
		unset($col);
		// Para las f�rmulas de la hoja
		$col_formula_honorarios = Utiles::NumToColumnaExcel($col_honorarios);
		$col_formula_gastos = Utiles::NumToColumnaExcel($col_gastos);
		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
			$col_formula_iva = Utiles::NumToColumnaExcel($col_iva);
			$col_formula_total_con_iva = Utiles::NumToColumnaExcel($col_total_con_iva);
		} else {
			$col_formula_total_cobro = Utiles::NumToColumnaExcel($col_total_cobro);
		}
		foreach ($abogados as $abogado => $data) {
			$col_formula_usuario[$data['id']] = Utiles::NumToColumnaExcel($col_usuario[$data['id']]);
			$col_formula_tarifa_usuario[$data['id']] = Utiles::NumToColumnaExcel($col_tarifa_usuario[$data['id']]);
			$col_formula_tarifa_estandar_usuario[$data['id']] = Utiles::NumToColumnaExcel($col_tarifa_estandar_usuario[$data['id']]);
			$col_formula_valor_usuario[$data['id']] = Utiles::NumToColumnaExcel($col_valor_usuario[$data['id']]);
			$col_formula_valor_moneda_usuario[$data['id']] = Utiles::NumToColumnaExcel($col_valor_moneda_usuario[$data['id']]);
		}

		$ws1->setColumn($col_numero_cobro, $col_numero_cobro, 15);
		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra')) || (method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra()))) {
			$ws1->setColumn($col_nota_cobro, $col_nota_cobro, 15);
		}
		$ws1->setColumn($col_factura, $col_factura, 11);
		$ws1->setColumn($col_fecha_emision, $col_fecha_emision, 16);
		$ws1->setColumn($col_cliente, $col_cliente, 40);
		$ws1->setColumn($col_asuntos, $col_asuntos, 40);
		$ws1->setColumn($col_encargado, $col_encargado, 20);
		foreach ($abogados as $abogado => $data) {
			$ws1->setColumn($col_usuario[$data['id']],$col_usuario[$data['id']],10);
			$ws1->setColumn($col_tarifa_usuario[$data['id']],$col_tarifa_usuario[$data['id']],15);
			$ws1->setColumn($col_tarifa_estandar_usuario[$data['id']],$col_tarifa_estandar_usuario[$data['id']],20);
			$ws1->setColumn($col_valor_usuario[$data['id']],$col_valor_usuario[$data['id']],20);
			$ws1->setColumn($col_valor_moneda_usuario[$data['id']],$col_valor_moneda_usuario[$data['id']],20);
		}
		$ws1->setColumn($col_horas_trabajadas, $col_horas_trabajadas, 17);
		$ws1->setColumn($col_horas_cobradas, $col_horas_cobradas, 15);
		$ws1->setColumn($col_total_cobro_original, $col_total_cobro_original, 22);
		$ws1->setColumn($col_total_cobro_tarifa_estandar, $col_total_cobro_tarifa_estandar, 22);
		$ws1->setColumn($col_honorarios_estandar, $col_honorarios_estandar, 22);
		$ws1->setColumn($col_rendimiento, $col_rendimiento, 22);
		$ws1->setColumn($col_honorarios, $col_honorarios, 22); // Los tr�mites est�n inclu�dos aqu�.
		$ws1->setColumn($col_gastos, $col_gastos, 22);
		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
			$ws1->setColumn($col_iva, $col_iva, 22);
			$ws1->setColumn($col_total_con_iva, $col_total_con_iva, 22);
		} else {
			$ws1->setColumn($col_total_cobro, $col_total_cobro, 22);
		}
		$ws1->setColumn($col_monto_pago_honorarios, $col_monto_pago_honorarios, 22);
		$ws1->setColumn($col_monto_pago_gastos, $col_monto_pago_gastos, 22);
		$ws1->setColumn($col_estado, $col_estado, 21);
		$ws1->setColumn($col_fecha_creacion, $col_fecha_creacion, 17);
		$ws1->setColumn($col_fecha_revision, $col_fecha_revision, 17);
		$ws1->setColumn($col_fecha_corte, $col_fecha_corte, 13);
		$ws1->setColumn($col_fecha_facturacion, $col_fecha_facturacion, 17);
		$ws1->setColumn($col_fecha_envio_a_cliente, $col_fecha_envio_a_cliente, 17);
		$ws1->setColumn($col_fecha_pago, $col_fecha_pago, 13);

		++$filas;
		$ws1->write($filas, $col_numero_cobro, __('REPORTE PARTICIPACI�N ABOGADO'), $encabezado);
		$ws1->write($filas, $col_numero_cobro + 1, '', $encabezado);
		$ws1->write($filas, $col_numero_cobro + 2, '', $encabezado);
		$ws1->mergeCells($filas, $col_numero_cobro, $filas, $col_numero_cobro+2);
		$filas +=2;
		$ws1->write($filas, $col_numero_cobro, __('GENERADO EL:'), $txt_opcion);
		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra')) || (method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra()))) {
			$ws1->write($filas, $col_nota_cobro, date("d-m-Y H:i:s"), $txt_opcion);
		} else {
			$ws1->write($filas, $col_factura, date("d-m-Y H:i:s"), $txt_opcion);
		}

		$where = "1";
		if (is_array($socios)) {
			$lista_socios = join("','", $socios);
			$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
		}
		if (is_array($clientes)) {
			$lista_clientes = join("','", $clientes);
			$where .= " AND cliente.codigo_cliente IN ('".$lista_clientes."')";
		}
		if ($fecha_ini != '' and $fecha_fin != '' and $rango == 1 and is_array($estados)) {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}
			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}
			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}
			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}
			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}
			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$_fecha_ini = Utiles::fecha2sql($fecha_ini);
			$_fecha_fin = Utiles::fecha2sql($fecha_fin);

			$where .= " AND cobro.{$where_estado} BETWEEN '{$_fecha_ini}' AND '{$_fecha_fin} 23:59:59' ";
		} else if ($fecha_ini != '' and $fecha_fin != '' and $rango == 1) {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}

			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}

			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}

			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}

			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}

			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$_fecha_ini = Utiles::fecha2sql($fecha_ini);
			$_fecha_fin = Utiles::fecha2sql($fecha_fin);

			$where .= " AND cobro.{$where_estado} BETWEEN '{$_fecha_ini}' AND '{$_fecha_fin} 23:59:59' ";
			++$filas;
			$ws1->write($filas, $col_numero_cobro, __('PERIODO CONSULTA:'), $txt_opcion);
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf ($sesion, 'NotaCobroExtra')) || (method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra()))) {
				$ws1->write($filas, $col_nota_cobro, "{$fecha_ini} - {$fecha_fin}", $txt_opcion);
			} else {
				$ws1->write($filas, $col_factura, "{$fecha_ini} - {$fecha_fin}", $txt_opcion);
			}
		} else if ($fecha_mes != '' and $fecha_anio != '' and is_array($estados)) {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}

			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}

			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}

			if ($estado == 'FACTURADO') {
				$where_estado = 'fecha_facturacion';
			}

			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}

			if ($estado == 'PAGO PARCIAL') {
				$where_estado = 'fecha_pago_parcial';
			}

			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}

			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$where .= " AND cobro.{$where_estado} BETWEEN '{$fecha_anio}-{$fecha_mes}-01 00:00:00' AND '{$fecha_anio}-{$fecha_mes}-31 23:59:59' ";
		} else if ($fecha_mes != '' and $fecha_anio != '') {
			if ($estado == 'CREADO') {
				$where_estado = 'fecha_creacion';
			}

			if ($estado == 'EN REVISION') {
				$where_estado = 'fecha_en_revision';
			}

			if ($estado == 'EMITIDO') {
				$where_estado = 'fecha_emision';
			}

			if ($estado == 'FACTURADO') {
				$where_estado = 'fecha_facturacion';
			}

			if ($estado == 'ENVIADO AL CLIENTE') {
				$where_estado = 'fecha_enviado_cliente';
			}

			if ($estado == 'PAGO PARCIAL') {
				$where_estado = 'fecha_pago_parcial';
			}

			if ($estado == 'INCOBRABLE' || $estado == 'PAGADO') {
				$where_estado = 'fecha_cobro';
			}

			if ($estado == 'CORTE') {
				$where_estado = 'fecha_fin';
			}

			$where .= " AND cobro.{$where_estado} BETWEEN '{$fecha_anio}-{$fecha_mes}-01 00:00:00' AND '{$fecha_anio}-{$fecha_mes}-31 23:59:59' ";

			++$filas;
			$ws1->write($filas, $col_numero_cobro, __('PERIODO CONSULTA:'), $txt_opcion);
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra')) || (method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra()))) {
				$ws1->write($filas, $col_nota_cobro, $fecha_mes.'-'.$fecha_anio, $txt_opcion);
			} else {
				$ws1->write($filas, $col_factura, $fecha_mes.'-'.$fecha_anio, $txt_opcion);
			}
		}
		if (is_array($estados)) {
			$lista_estados = join("','", $estados);
			$where .= " AND cobro.estado IN ('{$lista_estados}')";
		}

		if (method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
			$dato_usuario = "usuario.username";
		} else {
			$dato_usuario = "CONCAT(usuario.nombre,' ', usuario.apellido1)";
		}
		$filas += 4;
		$tabla_creada = false;
		$query = "SELECT cobro.fecha_creacion,
								cliente.glosa_cliente,
								{$dato_usuario} AS nombre,
								cobro.saldo_final_gastos * (cobro_moneda.tipo_cambio /cambio.tipo_cambio)*-1 as gastos,
								cobro.estado,
								cobro.id_cobro,
								prm_moneda_cobro.simbolo,
								prm_moneda_titulo.glosa_moneda,
								cobro.monto,
								cobro.monto_subtotal,
								prm_moneda_cobro.cifras_decimales,
								cobro.tipo_cambio_moneda,
								cambio.tipo_cambio,
								prm_moneda_titulo.cifras_decimales as cifras_decimales_titulo,
								cobro.fecha_emision,
								cobro.porcentaje_impuesto,
								cobro.fecha_fin,
								cobro.fecha_en_revision,
								cobro.opc_moneda_total,
								cobro.descuento,
								cobro.subtotal_gastos,
								cobro.fecha_facturacion,
								cobro.fecha_enviado_cliente,
								cobro.fecha_cobro,
								cobro.documento,
								cobro.monto_gastos,
								cobro.id_moneda
							FROM cobro
								LEFT JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
								LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
								LEFT JOIN usuario ON usuario.id_usuario = contrato.id_usuario_responsable
								LEFT JOIN prm_moneda as prm_moneda_cobro ON prm_moneda_cobro.id_moneda = cobro.id_moneda
								LEFT JOIN prm_moneda as prm_moneda_titulo ON prm_moneda_titulo.id_moneda = {$moneda}
								LEFT JOIN
									(SELECT id_cobro,tipo_cambio FROM cobro_moneda WHERE id_moneda = {$moneda})
									AS cambio ON cambio.id_cobro = cobro.id_cobro
								LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro = cobro.id_cobro AND cobro_moneda.id_moneda = cobro.opc_moneda_total
							WHERE {$where}
							ORDER BY cliente.glosa_cliente,
								cobro.fecha_creacion";
		// Obtener los asuntos de cada cobro
		$query_asuntos = "SELECT cobro.id_cobro,
							GROUP_CONCAT(distinct CONCAT(asunto.codigo_asunto, ' ', glosa_asunto) SEPARATOR '\n') as asuntos
						FROM cobro
							LEFT JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
							LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
							LEFT JOIN cobro_asunto ON cobro.id_cobro = cobro_asunto.id_cobro
							LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
						WHERE {$where}
						GROUP BY cobro.id_cobro";
		$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos, __FILE__, __LINE__, $sesion->dbh);
		$glosa_asuntos = array();
		while (list($id_cobro, $asuntos) = mysql_fetch_array($resp)){
			$glosa_asuntos[$id_cobro] = $asuntos;
		}

		#Clientes
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$fila_inicial = $filas + 2;
		while ($cobro = mysql_fetch_array($resp)) {
			$moneda_reporte = new Moneda($sesion);
			$moneda_reporte->Load($moneda);
			if (!$tabla_creada) {
				$ws1->write($filas, $col_numero_cobro, __('N� del Cobro'), $titulo_filas);
				if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra')) || (method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra()))) {
					$ws1->write($filas, $col_nota_cobro, __('Nota Cobro'), $titulo_filas);
				}
				$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
				$ws1->write($filas, $col_fecha_creacion, __('Fecha Creaci�n'), $titulo_filas);
				$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
				$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
				$ws1->write($filas, $col_encargado, __('Encargado'), $titulo_filas);
				$i = 0;
				foreach ($abogados as $abogado => $data) {
					$ws1->write($filas-1, $col_usuario[$data['id']], $data['nombre'], $titulo_filas);
					$ws1->write($filas-1, $col_tarifa_usuario[$data['id']], '', $titulo_filas);
					$ws1->write($filas-1, $col_tarifa_estandar_usuario[$data['id']], '', $titulo_filas);
					$ws1->write($filas-1, $col_valor_usuario[$data['id']], '', $titulo_filas);
					$ws1->write($filas-1, $col_valor_moneda_usuario[$data['id']], '', $titulo_filas);
					$ws1->write($filas, $col_usuario[$data['id']], 'Horas', $i % 2 == 0 ? $titulo_filas_color : $titulo_filas);
					$ws1->write($filas, $col_tarifa_usuario[$data['id']], __('Hora vendida'), $i % 2 == 0 ? $titulo_filas_color : $titulo_filas);
					$ws1->write($filas, $col_tarifa_estandar_usuario[$data['id']], __('Tarifa estandar'), $i % 2 == 0 ? $titulo_filas_color : $titulo_filas);
					$ws1->write($filas, $col_valor_usuario[$data['id']], 'Aporte', $i % 2 == 0 ? $titulo_filas_color : $titulo_filas);
					$ws1->write($filas, $col_valor_moneda_usuario[$data['id']], $moneda_reporte->fields['simbolo'], $i % 2 == 0 ? $titulo_filas_color : $titulo_filas);
					$ws1->mergeCells($filas-1, $col_usuario[$data['id']], $filas-1, $col_valor_moneda_usuario[$data['id']]);
					$i++;
				}
				$ws1->write($filas, $col_horas_trabajadas, __('Hrs. Trabajadas'), $titulo_filas);
				$ws1->write($filas, $col_horas_cobradas, __('Hrs. Cobradas'), $titulo_filas);
				$ws1->write($filas, $col_total_cobro_original, __('Total Cobro Original'), $titulo_filas);
				$ws1->write($filas, $col_total_cobro_tarifa_estandar, __('Total seg�n tarifa estandar'), $titulo_filas);
				$ws1->write($filas, $col_honorarios_estandar, __('Honorarios seg�n tarifa estandar'), $titulo_filas);
				$ws1->write($filas, $col_rendimiento, __('Rendimiento'), $titulo_filas);
				if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
					if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'PermitirFactura')) || (method_exists('Conf', 'PermitirFactura') && Conf::PermitirFactura()))) {
						$ws1->write($filas, $col_total_con_iva, __('Total facturado'), $titulo_filas);
					} else {
						$ws1->write($filas, $col_total_con_iva, __('Total con IVA'), $titulo_filas);
					}
				} else {
					if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'PermitirFactura')) || (method_exists('Conf', 'PermitirFactura') && Conf::PermitirFactura()))) {
						$ws1->write($filas, $col_total_cobro, __('Total facturado'), $titulo_filas);
					} else {
						$ws1->write($filas, $col_total_cobro, __('Total Cobro'), $titulo_filas);
					}
				}
				$ws1->write($filas, $col_honorarios, __('Honorarios'), $titulo_filas);
				$ws1->write($filas, $col_gastos, __('Gastos'), $titulo_filas);
				if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
					$ws1->write($filas, $col_iva, __('IVA'), $titulo_filas);
				}
				$ws1->write($filas, $col_estado, __('Estado'), $titulo_filas);
				$ws1->write($filas, $col_fecha_revision, __('Fecha Revisi�n'), $titulo_filas);
				$ws1->write($filas, $col_fecha_emision, __('Fecha Emisi�n'), $titulo_filas);
				$ws1->write($filas, $col_fecha_corte, __('Fecha Corte'), $titulo_filas);
				$ws1->write($filas, $col_fecha_facturacion, __('Fecha Facturaci�n'), $titulo_filas);
				$ws1->write($filas, $col_fecha_envio_a_cliente, __('Fecha Env�o al Cliente'), $titulo_filas);
				$ws1->write($filas, $col_fecha_pago, __('Fecha Pago'), $titulo_filas);
				$ws1->write($filas, $col_monto_pago_honorarios, __('Honorarios pagados'), $titulo_filas);
				$ws1->write($filas, $col_monto_pago_gastos, __('Gastos pagados'), $titulo_filas);
				$tabla_creada = true;
			}
			$query_trabajos = "SELECT SUM(TIME_TO_SEC(duracion)),SUM(TIME_TO_SEC(duracion_cobrada))
													FROM trabajo
													WHERE id_cobro = {$cobro['id_cobro']}";
			$resp2 = mysql_query($query_trabajos, $sesion->dbh) or Utiles::errorSQL($query_trabajos, __FILE__, __LINE__, $sesion->dbh);
			list($duracion, $duracion_cobrable) = mysql_fetch_array($resp2);
			$duracion = $duracion / 24 / 3600; //Excel calcula el tiempo en d�as
			$duracion_cobrable = $duracion_cobrable / 24 / 3600;

			$query_factor = "SELECT SUM(usuario_tarifa.tarifa * (TIME_TO_SEC(duracion_cobrada)/3600))
												FROM trabajo
												LEFT JOIN cobro ON cobro.id_cobro=trabajo.id_cobro
												JOIN tarifa ON tarifa_defecto = 1
												LEFT JOIN usuario_tarifa ON (usuario_tarifa.id_usuario = trabajo.id_usuario AND usuario_tarifa.id_moneda = cobro.id_moneda AND usuario_tarifa.id_tarifa = tarifa.id_tarifa)
											 WHERE trabajo.id_cobro = {$cobro['id_cobro']}";
			$resp_factor = mysql_query($query_factor, $sesion->dbh) or Utiles::errorSQL($query_factor, __FILE__, __LINE__, $sesion->dbh);
			list($factor_cobro) = mysql_fetch_array($resp_factor);

			$query_participacion_usuario = "SELECT trabajo.id_usuario,usuario_tarifa.tarifa,SUM(TIME_TO_SEC(duracion))/3600,SUM(TIME_TO_SEC(duracion_cobrada))/3600
																				FROM trabajo
																				LEFT JOIN cobro ON cobro.id_cobro=trabajo.id_cobro
																				JOIN tarifa ON tarifa_defecto=1
																				LEFT JOIN usuario_tarifa ON (usuario_tarifa.id_usuario = trabajo.id_usuario AND usuario_tarifa.id_moneda = cobro.id_moneda AND usuario_tarifa.id_tarifa = tarifa.id_tarifa)
																			 WHERE trabajo.id_cobro = {$cobro['id_cobro']}
																			 GROUP BY trabajo.id_usuario ORDER BY trabajo.id_usuario";
			$resp_participacion_usuario = mysql_query($query_participacion_usuario, $sesion->dbh) or Utiles::errorSQL($query_participacion_usuario, __FILE__, __LINE__, $sesion->dbh);

			$abogados_horas = array();
			while (list($id,$tarifa_estandar,$duracion_trabajada,$duracion_cobrada) = mysql_fetch_array($resp_participacion_usuario)) {
				$abogados_horas_datos = array();
				$abogados_horas_datos['id'] = $id;
				$abogados_horas_datos['factor_usuario'] = $duracion_cobrada*$tarifa_estandar;
				if ($factor_cobro > 0) {
					$abogados_horas_datos['aporte'] = ($cobro['monto_subtotal']-$cobro['descuento'])*($duracion_cobrada*$tarifa_estandar)/$factor_cobro;
				} else {
					$abogados_horas_datos['aporte'] = 0;
				}
				if ($duracion_cobrada > 0) {
					$abogados_horas_datos['hora_vendida'] = $abogados_horas_datos['aporte'] / $duracion_cobrada;
				} else {
					$abogados_horas_datos['hora_vendida'] = 0;
				}
				$abogados_horas_datos['tarifa_estandar'] = $tarifa_estandar;
				$abogados_horas_datos['duracion'] = round($duracion_trabajda,2);
				$abogados_horas_datos['duracion_cobrada'] = round($duracion_cobrada,2);
				$abogados_horas[$id] = $abogados_horas_datos;
			}


			// Calcular gastos
			$gastos = 0;

			$cobro_moneda = new CobroMoneda($sesion);
			$cobro_moneda->Load($cobro['id_cobro']);

			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoPorGastos')) || (method_exists('Conf', 'UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos()))) {
				$aproximacion_gastos = number_format($cobro['subtotal_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'],$cobro_moneda->moneda[$cobro['id_moneda']]['cifras_decimales'],'.','');
			} else {
				$aproximacion_gastos = number_format($cobro['monto_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'],$cobro_moneda->moneda[$cobro['id_moneda']]['cifras_decimales'],'.','');
			}
			$monto_gastos = number_format($aproximacion_gastos*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro['tipo_cambio'],$cobro_moneda->moneda[$moneda]['cifras_decimales'],'.','');

			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
				if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoPorGastos')) || (method_exists('Conf', 'UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos()))) {
					$aproximacion_iva = number_format(($cobro['monto_subtotal']-$cobro['descuento'])*$cobro['porcentaje_impuesto']/100+($cobro['subtotal_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'])*$cobro['porcentaje_impuesto_gastos']/100,$cobro['cifras_decimales'],'.','');
				} else {
					$aproximacion_iva = number_format(($cobro['monto_subtotal']-$cobro['descuento'])*$cobro['porcentaje_impuesto']/100,$cobro['cifras_decimales'],'.','');
				}
				$monto_iva = number_format($aproximacion_iva*($cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro['tipo_cambio']),$cobro['cifras_decimales'],'.','');
			}

			$aproximacion_honorarios = number_format($cobro['monto_subtotal']-$cobro['descuento'],$cobro['cifras_decimales'],'.','');
			$monto_moneda = $aproximacion_honorarios*($cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro['tipo_cambio']);

			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
				if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoPorGastos')) || (method_exists('Conf', 'UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos()))) {
					$aproximacion_monto_honorarios = number_format(($cobro['monto_subtotal']-$cobro['descuento']),$cobro['cifras_decimales'],'.','');
					$aproximacion_monto = number_format(($cobro['monto_subtotal']-$cobro['descuento'])*(1+$cobro['porcentaje_impuesto']/100)+($cobro['subtotal_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'])*(1+$cobro['porcentaje_impuesto_gastos']/100),$cobro['cifras_decimales'],'.','');
				} else {
					$aproximacion_monto_honorarios = number_format(($cobro['monto_subtotal']-$cobro['descuento']),$cobro['cifras_decimales'],'.','');
					$aproximacion_monto = number_format(($cobro['monto_subtotal']-$cobro['descuento'])*(1+$cobro['porcentaje_impuesto']/100)+$cobro['monto_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'],$cobro['cifras_decimales'],'.','');
				}
			} else {
				$aproximacion_monto_honorarios = number_format($cobro['monto'],$cobro['cifras_decimales'],'.','');
				$aproximacion_monto = number_format($cobro['monto']+$cobro['monto_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'], $cobro['cifras_decimales'], '.', '');
			}

			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
				if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoPorGastos')) || (method_exists('Conf', 'UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos()))) {
					$aproximacion_monto_tarifa_estandar = number_format($factor_cobro*(1+$cobro['porcentaje_impuesto']/100)+($cobro['subtotal_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'])*(1+$cobro['porcentaje_impuesto_gastos']/100),$cobro['cifras_decimales'],'.','');
					$aproximacion_monto_tarifa_estandar_honorarios = number_format($factor_cobro,$cobro['cifras_decimales'],'.','');
				} else {
					$aproximacion_monto_tarifa_estandar = number_format($factor_cobro*(1+$cobro['porcentaje_impuesto']/100)+$cobro['monto_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'],$cobro['cifras_decimales'],'.','');
					$aproximacion_monto_tarifa_estandar_honorarios = number_format($factor_cobro,$cobro['cifras_decimales'],'.','');
				}
			} else {
				$aproximacion_monto_tarifa_estandar = number_format($factor_cobro + $cobro['monto_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'], $cobro['cifras_decimales'],'.', '');
				$aproximacion_monto_tarifa_estandar_honorarios = number_format($factor_cobro,$cobro['cifras_decimales'],'.','');
			}

			if ($aproximacion_monto_tarifa_estandar_honorarios > 0) {
				$rendimiento = $aproximacion_monto_honorarios / $aproximacion_monto_tarifa_estandar_honorarios;
			} else if ($aproximacion_monto_honorarios == 0) {
				$rendimiento = 0;
			} else {
				$rendimiento = 'inf';
			}
			// Calcular monto pago para honorarios y gastos (por separado)
			$monto_pago_gastos = 0;
			$monto_pago_honorarios = 0;
			$query_monto_pago = "SELECT id_documento,
															id_moneda
														FROM documento
														WHERE id_cobro='{$cobro['id_cobro']}'
															AND monto < 0";
			$resp_monto_pago = mysql_query($query_monto_pago, $sesion->dbh) or Utiles::errorSQL($query_monto_pago, __FILE__, __LINE__, $sesion->dbh);
			while($pago = mysql_fetch_array($resp_monto_pago)) {
				$query_monto_separado = "SELECT valor_pago_honorarios,
											valor_pago_gastos
										FROM neteo_documento
										WHERE id_documento_pago='".$pago['id_documento']."'";
				$resp_monto_separado = mysql_query($query_monto_separado, $sesion->dbh) or Utiles::errorSQL($query_monto_separado, __FILE__, __LINE__, $sesion->dbh);
				$tasa_cambio = Utiles::GlosaMult($sesion, $cobro['id_cobro'], $pago['id_moneda'], 'tipo_cambio', 'cobro_moneda', 'id_cobro', 'id_moneda');
				if(!is_numeric($tasa_cambio)) {
					$tasa_cambio = Utiles::Glosa($sesion, $pago['id_moneda'], 'tipo_cambio', 'prm_moneda', 'id_moneda');
				}

				while($pago_separado = mysql_fetch_array($resp_monto_separado)) {
					$monto_pago_gastos += $pago_separado['valor_pago_gastos']*$tasa_cambio;
					$monto_pago_honorarios += $pago_separado['valor_pago_honorarios']*$tasa_cambio;
				}
			}

			$monto_pago_gastos /= $cobro['tipo_cambio'];
			$monto_pago_honorarios /= $cobro['tipo_cambio'];

			++$filas;
			$ws1->write($filas, $col_numero_cobro, $cobro['id_cobro'], $fecha);
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'NotaCobroExtra')) || (method_exists('Conf', 'NotaCobroExtra') && Conf::NotaCobroExtra()))) {
				$ws1->write($filas, $col_nota_cobro, $cobro['nota_cobro'], $fecha);
			}
			$ws1->write($filas, $col_factura, $cobro['documento'], $fecha);
			$ws1->write($filas, $col_fecha_creacion, Utiles::sql2fecha($cobro['fecha_creacion'], $formato_fecha, "-"), $fecha);
			$ws1->write($filas, $col_cliente, $cobro['glosa_cliente'], $txt_opcion);
			$ws1->write($filas, $col_asuntos, $glosa_asuntos[$cobro['id_cobro']], $txt_opcion);
			$ws1->write($filas, $col_encargado, $cobro['nombre'], $txt_opcion);
			$i = 0;
			foreach ($abogados as $abogado => $data) {
				if ($abogados_horas[$data['id']]['id']) {
					$ws1->writeNumber($filas, $col_usuario[$data['id']], $abogados_horas[$data['id']]['duracion_cobrada'], $i % 2 == 0 ? $numeros_color : $numeros);
					$ws1->writeNumber($filas, $col_tarifa_usuario[$data['id']], $abogados_horas[$data['id']]['hora_vendida'], $i % 2 == 0 ? $formatos_moneda_color[$cobro['id_moneda']] : $formatos_moneda[$cobro['id_moneda']] );
					$ws1->writeNumber($filas, $col_tarifa_estandar_usuario[$data['id']], $abogados_horas[$data['id']]['tarifa_estandar'], $i % 2 == 0 ? $formatos_moneda_color[$cobro['id_moneda']] : $formatos_moneda[$cobro['id_moneda']] );
					$ws1->writeNumber($filas, $col_valor_usuario[$data['id']], $abogados_horas[$data['id']]['aporte'], $i % 2 == 0 ? $formatos_moneda_color[$cobro['id_moneda']] : $formatos_moneda[$cobro['id_moneda']]);
					$ws1->writeFormula($filas, $col_valor_moneda_usuario[$data['id']], "=".$col_formula_valor_usuario[$data['id']].($filas+1)."*".$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro['tipo_cambio'], $i % 2 == 0 ? $formatos_moneda_color[$moneda] : $formatos_moneda[$moneda]);
				} else {
					$ws1->write($filas, $col_usuario[$data['id']], '', $i % 2 == 0 ? $txt_centro_color : $txt_centro);
					$ws1->write($filas, $col_tarifa_usuario[$data['id']], '',$i % 2 == 0 ? $txt_centro_color : $txt_centro);
					$ws1->write($filas, $col_tarifa_estandar_usuario[$data['id']], '', $i % 2 == 0 ? $txt_centro_color : $txt_centro);
					$ws1->write($filas, $col_valor_usuario[$data['id']], '',$i % 2 == 0 ? $txt_centro_color : $txt_centro);
					$ws1->write($filas, $col_valor_moneda_usuario[$data['id']], '',$i % 2 == 0 ? $txt_centro_color : $txt_centro);
				}
				$i++;
			}

			$ws1->writeNumber($filas, $col_horas_trabajadas, $duracion, $time_format);
			$ws1->writeNumber($filas, $col_horas_cobradas, $duracion_cobrable, $time_format);
			$ws1->writeNumber($filas, $col_total_cobro_original, $aproximacion_monto, $formatos_moneda[$cobro['id_moneda']]);
			$ws1->writeNumber($filas, $col_total_cobro_tarifa_estandar, $aproximacion_monto_tarifa_estandar, $formatos_moneda[$cobro['id_moneda']]);
			$ws1->writeNumber($filas, $col_honorarios_estandar, $aproximacion_monto_tarifa_estandar_honorarios, $formatos_moneda[$cobro['id_moneda']]);
			if ($aproximacion_monto_tarifa_estandar > 0) {
				$ws1->writeNumber($filas, $col_rendimiento, round($rendimiento,2), $numeros);
			} else {
				$ws1->write($filas,$col_rendimiento, '', $txt_centro);
			}
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
				$ws1->writeFormula($filas, $col_total_con_iva, "=$col_formula_honorarios".($filas+1)."+$col_formula_gastos".($filas+1)."+$col_formula_iva".($filas+1), $formatos_moneda[$moneda]);
			} else {
				$ws1->writeFormula($filas, $col_total_cobro, "=$col_formula_honorarios".($filas+1)."+$col_formula_gastos".($filas+1), $formatos_moneda[$moneda]);
			}
			$ws1->writeNumber($filas, $col_honorarios, number_format($monto_moneda, $cobro['cifras_decimales_titulo'], '.', ''), $formatos_moneda[$moneda]);
			$ws1->writeNumber($filas, $col_gastos, number_format($monto_gastos, 6, '.', ''), $formatos_moneda[$moneda]);
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
				$ws1->writeNumber($filas, $col_iva, number_format($monto_iva,$cobro['cifras_decimales_titulo'], '.', ''), $formatos_moneda[$moneda]);
			}
			$ws1->write($filas, $col_estado, $cobro['estado'], $txt_centro);
			$ws1->write($filas, $col_fecha_revision,Utiles::sql2fecha($cobro['fecha_en_revision'], $formato_fecha, "-") ? Utiles::sql2fecha($cobro['fecha_en_revision'], $formato_fecha, "-") : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_emision,Utiles::sql2fecha($cobro['fecha_emision'], $formato_fecha, "-") ? Utiles::sql2fecha($cobro['fecha_emision'], $formato_fecha, "-") : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_corte,Utiles::sql2fecha($cobro['fecha_fin'], $formato_fecha, "-") ? Utiles::sql2fecha($cobro['fecha_fin'], $formato_fecha, "-") : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_facturacion,Utiles::sql2fecha($cobro['fecha_facturacion'], $formato_fecha, "-") ? Utiles::sql2fecha($cobro['fecha_facturacion'], $formato_fecha, "-") : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_envio_a_cliente,Utiles::sql2fecha($cobro['fecha_enviado_cliente'], $formato_fecha, "-") ? Utiles::sql2fecha($cobro['fecha_enviado_cliente'], $formato_fecha, "-") : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_pago,Utiles::sql2fecha($cobro['fecha_cobro'], $formato_fecha, "-") ? Utiles::sql2fecha($cobro['fecha_cobro'], $formato_fecha, "-") : ' - ', $fecha);
			$ws1->writeNumber($filas, $col_monto_pago_honorarios, number_format($monto_pago_honorarios, $cobro['cifras_decimales_titulo'], '.', ''), $formatos_moneda[$moneda]);
			$ws1->writeNumber($filas, $col_monto_pago_gastos, number_format($monto_pago_gastos, $cobro['cifras_decimales_titulo'], '.', ''), $formatos_moneda[$moneda]);

			if ($cobro['estado'] != 'CREADO' && $cobro['estado'] != 'EN REVISION') {
				$comentario = "";
				$query_historial = "SELECT fecha, comentario FROM cobro_historial WHERE id_cobro = {$cobro['id_cobro']}";
				$resp_historial = mysql_query($query_historial, $sesion->dbh) or Utiles::errorSQL($query_historial, __FILE__, __LINE__, $sesion->dbh);
				$detalle_historial = [];
				while ($historial = mysql_fetch_array($resp_historial)) {
					$comentario .= Utiles::sql2fecha($historial['fecha'], $formato_fecha, "-") . ": {$historial['comentario']}\n";
					$detalle_historial[] = [
						'fecha' => Utiles::sql2fecha($historial['fecha'], $formato_fecha, '-'),
						'comentario' => $historial['comentario']
					];
					$titulo = __("Historial Cobro") . " {$cobro['id_cobro']} ({$cobro['glosa_cliente']})";
					$hoja_historial[$titulo] = $detalle_historial;
				}
				$ws1->writeNote($filas, $col_estado, $comentario);
			}
			$tabla_creada = true;
		}

		if ($tabla_creada) {
			++$filas;
			$ws1->write($filas, $col_numero_cobro, __('Total'), $encabezado);

			$j = 0;
			foreach ($abogados as $abogado => $data) {
				$ws1->writeFormula($filas, $col_usuario[$data['id']], "=SUM(".$col_formula_usuario[$data['id']].$fila_inicial.":".$col_formula_usuario[$data['id']].$filas.")", $j%2==0 ? $numeros_color : $numeros );
				$ws1->writeFormula($filas, $col_valor_moneda_usuario[$data['id']], "=SUM(".$col_formula_valor_moneda_usuario[$data['id']].$fila_inicial.":".$col_formula_valor_moneda_usuario[$data['id']].$filas.")", $j%2==0 ? $formatos_moneda_color[$moneda] : $formatos_moneda[$moneda] );
				$j++;
			}
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
				$ws1->writeFormula($filas, $col_total_con_iva, "=SUM($col_formula_total_con_iva$fila_inicial:$col_formula_gastos$filas)", $formatos_moneda[$moneda]);
			} else {
				$ws1->writeFormula($filas, $col_total_cobro, "=SUM($col_formula_total_cobro$fila_inicial:$col_formula_total_cobro$filas)", $formatos_moneda[$moneda]);
			}
			$ws1->writeFormula($filas, $col_honorarios, "=SUM($col_formula_honorarios$fila_inicial:$col_formula_honorarios$filas)", $formatos_moneda[$moneda]);
			$ws1->writeFormula($filas, $col_gastos, "=SUM($col_formula_gastos$fila_inicial:$col_formula_gastos$filas)", $formatos_moneda[$moneda]);
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'UsarImpuestoSeparado')) || (method_exists('Conf', 'UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado()))) {
				$ws1->writeFormula($filas, $col_iva, "=SUM($col_formula_iva$fila_inicial:$col_formula_iva$filas)", $formatos_moneda[$moneda]);
			}
		} else {
			$ws1->write($filas, $col_numero_cobro, __('No se encontraron resultados'), $encabezado);
			$ws1->mergeCells($filas, $col_numero_cobro, $filas, $col_fecha_pago);
		}

		// Hoja Historial
		$fila = 1;
		$col_fecha = 1;
		$col_comentario = 2;
		$ws2 = & $wb->addWorksheet(__('Historial'));
		$ws2->setInputEncoding('utf-8');
		$ws2->fitToPages(2, 5);
		$ws2->setZoom(75);
		$ws2->hideGridlines();
		$ws2->setLandscape();
		$ws2->setColumn($col_fecha, $col_fecha, 17);
		$ws2->setColumn($col_comentario, $col_comentario, 44);

		foreach ($hoja_historial as $titulo => $historial) {
			$ws2->write($fila, $col_fecha, $titulo, $titulo_filas);
			$ws2->write($fila, $col_comentario, '', $titulo_filas);
			$ws2->mergeCells($fila, $col_fecha, $fila, $col_comentario);
			++$fila;
			$ws2->write($fila, $col_fecha, __('Fecha'), $titulo_filas);
			$ws2->write($fila, $col_comentario, __('Comentario'), $titulo_filas);
			++$fila;
			foreach ($historial as $detalle) {
				$ws2->write($fila, $col_fecha, $detalle['fecha'], $fecha);
				$ws2->write($fila, $col_comentario, $detalle['comentario'], $txt_opcion);
				++$fila;
			}
			++$fila;
		}

		$wb->send("planilla_participacion_abogado.xls");
		$wb->close();
		exit;
	}
	$pagina->titulo = __('Reporte Participaci�n Abogados');
	$pagina->PrintTop();
?>
<form method="post" name="formulario" action="planilla_participacion_abogado.php?xls=1">
<input type="hidden" name="horas_sql" id="horas_sql" value='<?php echo $horas_sql ? $horas_sql : 'hr_trabajadas'; ?>'/>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<?php
	$hoy = date("Y-m-d");
?>
<table class="border_plomo tb_base" width="650px" cellpadding="0" cellspacing="3" align="center">
	<tr>
		<td align="center">
<table style="border: 0px solid black;" width="99%" cellpadding="0" cellspacing="3">
	<tr valign="top">
		<td align="left" colspan="2" width='33%' >
			<b><?php echo __('Clientes'); ?>:</b>
		</td>
		<td align="left" width='33%'>
			<b><?php echo __('Encargados Comerciales'); ?>:</b>
		</td>
		<td align="left" width='33%'>
			<b><?php echo __('Estado'); ?>:</b>
		</td>
	</tr>
	<tr valign="top">
		<td rowspan="2" colspan="2" align="left">
			<?php echo Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]", $clientes,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
		</td>
		<td rowspan="2" align="left"><!-- Nuevo Select -->
            <?php echo $Form->select('socios[]', UsuarioExt::QueryComerciales($sesion), $socios, array('empty' => FALSE, 'style' => 'width: 200px', 'class' => 'selectMultiple', 'multiple' => 'multiple', 'size' => '6')); ?>
		</td>
		<td rowspan="2" align="left">
			<?php echo Html::SelectQuery($sesion,"SELECT codigo_estado_cobro AS estado FROM prm_estado_cobro ORDER BY orden ASC", "estados[]", $estados,"class=\"selectMultiple\" multiple size=6 ","","200"); ?></td>
		</td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr>
<?php
	if (!$tipo) {
		$tipo = 'Profesional';
	}
?>
<!-- PERIODOS -->
	<tr>
		<td align="right"><b><?php echo __('Fecha de'); ?>:&nbsp;</b></td><td align=left>
		<select name='estado' id='estado' style='width: 120px;'>
			<option value='CORTE'>CORTE</option>
			<option value='CREADO'>CREADO</option>
			<option value='EN REVISION'>EN REVISION</option>
			<option value='EMITIDO'>EMITIDO</option>
			<option value='FACTURADO'>FACTURADO</option>
			<option value='ENVIADO AL CLIENTE'>ENVIADO AL CLIENTE</option>
			<option value='PAGO PARCIAL'>PAGO PARCIAL</option>
			<option value='PAGADO'>PAGADO</option>
			<option value='INCOBRABLE'>INCOBRABLE</option>
		</select></td>
		<td align="right">
			<b><?php echo __('Periodo'); ?>:</b>&nbsp;&nbsp;
			<input type="checkbox" id="rango" name="rango" value="1" <?php echo $rango ? 'checked' : ''; ?> onclick='Rangos(this, this.form);' title='Otro rango' />&nbsp;
			<label for="rango" style="font-size:9px"><?php echo __('Otro rango'); ?></label>
		</td>
		<td align="left">
<?php
		if (!$fecha_mes) {
			$fecha_mes = date('m');
		}
?>
			<div id="periodo" style="display:<?php echo !$rango ? 'inline' : 'none'; ?>;">
				<select name="fecha_mes" style='width:60px'>
					<option value='1' <?php echo $fecha_mes==1 ? 'selected':''; ?>><?php echo __('Enero'); ?></option>
					<option value='2' <?php echo $fecha_mes==2 ? 'selected':''; ?>><?php echo __('Febrero'); ?></option>
					<option value='3' <?php echo $fecha_mes==3 ? 'selected':''; ?>><?php echo __('Marzo'); ?></option>
					<option value='4' <?php echo $fecha_mes==4 ? 'selected':''; ?>><?php echo __('Abril'); ?></option>
					<option value='5' <?php echo $fecha_mes==5 ? 'selected':''; ?>><?php echo __('Mayo'); ?></option>
					<option value='6' <?php echo $fecha_mes==6 ? 'selected':''; ?>><?php echo __('Junio'); ?></option>
					<option value='7' <?php echo $fecha_mes==7 ? 'selected':''; ?>><?php echo __('Julio'); ?></option>
					<option value='8' <?php echo $fecha_mes==8 ? 'selected':''; ?>><?php echo __('Agosto'); ?></option>
					<option value='9' <?php echo $fecha_mes==9 ? 'selected':''; ?>><?php echo __('Septiembre'); ?></option>
					<option value='10' <?php echo $fecha_mes==10 ? 'selected':''; ?>><?php echo __('Octubre'); ?></option>
					<option value='11' <?php echo $fecha_mes==11 ? 'selected':''; ?>><?php echo __('Noviembre'); ?></option>
					<option value='12' <?php echo $fecha_mes==12 ? 'selected':''; ?>><?php echo __('Diciembre'); ?></option>
				</select>
<?php
			if (!$fecha_anio) {
				$fecha_anio = date('Y');
			}
?>
				<select name="fecha_anio" style='width:55px'>
					<?php for ($i=(date('Y')-5);$i < (date('Y')+5);$i++) { ?>
						<option value='<?php echo $i; ?>' <?php echo $fecha_anio == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
					<?php } ?>
				</select>
			</div>
			<div id="periodo_rango" style="display:<?php echo $rango ? 'inline' : 'none'; ?>;">
				<?php echo __('Fecha desde'); ?>:
					<?php echo $Html::PrintCalendar('fecha_ini', $fecha_ini); ?>
				<br />
				<?php echo __('Fecha hasta'); ?>:&nbsp;
					<?php echo $Html::PrintCalendar('fecha_fin', $fecha_fin); ?>
			</div>
	</tr>
	<tr>
		<td align="right"><b><?php echo __('Moneda'); ?>:</b>&nbsp;</td><td colspan="3" align=left><?php echo Html::SelectQuery($sesion,"SELECT id_moneda,glosa_moneda AS nombre FROM prm_moneda ORDER BY id_moneda ASC", "moneda", $moneda,"","","50"); ?>&nbsp;</td>
	</tr>
	<tr>
			<td align="right" colspan="4">
				<input type="submit" class="btn" value="<?php echo __('Generar planilla'); ?>" />
			</td>
	</tr>
</table>
		</td>
	</tr>
</table>
</form>
<script type="text/javascript">

	function Rangos(obj, form) {
		if (obj.checked) {
			jQuery('#periodo').hide();
			jQuery('#periodo_rango').show();
		} else {
			jQuery('#periodo').show();
			jQuery('#periodo_rango').hide();
		}
	}

</script>
<?php
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
