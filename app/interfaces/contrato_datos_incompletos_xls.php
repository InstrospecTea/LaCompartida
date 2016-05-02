<?php
	require_once dirname(__FILE__).'/../conf.php';

	//Parámetros generales para los 2 casos de listas a extraer
	$sesion = new Sesion(array('REV','ADM'));
	$pagina = new Pagina($sesion);

	$Criteria = new Criteria($sesion);

	$wb = new WorkbookMiddleware();
	$wb->setCustomColor ( 35, 220, 255, 220 );
	$wb->setCustomColor ( 36, 255, 255, 220 );
	$encabezado =& $wb->addFormat(array('Size' => 12,
	                              'VAlign' => 'top',
	                              'Align' => 'justify',
	                              'Bold' => '1',
	                              'Color' => 'black'));
	$tit =& $wb->addFormat(array('Size' => 12,
	                              'VAlign' => 'top',
	                              'Align' => 'center',
	                              'Bold' => '1',
	                              'Locked' => 1,
	                              'Border' => 1,
	                              'FgColor' => '35',
	                              'Color' => 'black'));
	$f3c =& $wb->addFormat(array('Size' => 10,
	                              'Align' => 'left',
	                              'Bold' => '1',
	                              'FgColor' => '35',
	                              'Border' => 1,
	                              'Locked' => 1,
	                              'Color' => 'black'));
	$f4 =& $wb->addFormat(array('Size' => 10,
	                              'VAlign' => 'top',
	                              'Align' => 'justify',
	                              'Border' => 1,
	                              'Color' => 'black'));
	$f5 =& $wb->addFormat(array('Size' => 10,
	                              'VAlign' => 'top',
	                              'Align' => 'center',
	                              'Border' => 1,
	                              'Color' => 'black'));

	$ws1 =& $wb->addWorksheet(__('Tipo de Cambio'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1,0);
	$ws1->setZoom(75);

	if (method_exists('Conf','GetConf')) {
		$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
		$PdfLinea2 = Conf::GetConf($sesion, 'PdfLinea2');
	} else {
		$PdfLinea1 = Conf::PdfLinea1();
		$PdfLinea2 = Conf::PdfLinea2();
	}

	$wb->send('Clientes_datos_incompletos.xls');
	$ws1->setColumn( 1, 1, 18);
	$ws1->setColumn( 2, 2, 45);
	$ws1->setColumn( 3, 3, 40);
	$ws1->setColumn( 4, 4, 75);
	$ws1->setColumn( 5, 5, 20);
	$ws1->write(0, 0, 'Listado de Clientes con datos incompletos', $encabezado);
	$ws1->mergeCells (0, 0, 0, 8);
	$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
	$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
	$ws1->mergeCells (2, 0, 2, 8);
	$info_usr = str_replace('<br>',' - ',$PdfLinea2);
	$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
	$ws1->mergeCells (3, 0, 3, 8);
	$i=0;
	$fila_inicial = 7;

	$ws1->write($fila_inicial, 1, __('Código Cliente'), $tit);
	$ws1->write($fila_inicial, 2, __('Cliente'), $tit);
	$ws1->write($fila_inicial, 3, __('Asunto'), $tit);
	$ws1->write($fila_inicial, 4, __('Campos Faltantes'), $tit);

	$Criteria
		->add_select('c.glosa_cliente')
		->add_select('c.id_usuario_encargado')
		->add_select('ct.codigo_cliente')
		->add_select('ct.rut')
		->add_select('ct.factura_razon_social')
		->add_select('ct.factura_giro')
		->add_select('ct.factura_direccion')
		->add_select('ct.cod_factura_telefono')
		->add_select('ct.factura_telefono')
		->add_select('ct.titulo_contacto')
		->add_select('ct.contacto', 'nombre_contacto')
		->add_select('ct.apellido_contacto')
		->add_select('ct.fono_contacto')
		->add_select('ct.email_contacto')
		->add_select('ct.direccion_contacto')
		->add_select('ct.id_tarifa')
		->add_select('ct.id_moneda')
		->add_select('ct.forma_cobro')
		->add_select('ct.opc_moneda_total')
		->add_select('ct.observaciones')
		->add_select('GROUP_CONCAT(glosa_asunto)', 'asunto')
		->add_from('cliente', 'c')
		->add_inner_join_with(
				['contrato', 'ct'],
				CriteriaRestriction::equals('c.codigo_cliente', 'ct.codigo_cliente')
			)
		->add_inner_join_with(
				['asunto', 'a'],
				CriteriaRestriction::equals('ct.id_contrato', 'a.id_contrato')
			)
		->add_grouping('ct.id_contrato');

	$fila_inicial++;
	$incompletos = 0;
	$campos = "";
	$datos_cliente = array(
		'glosa_cliente' => 'Nombre',
		'id_usuario_encargado' => 'Attache Secundario',
		'codigo_cliente' => 'Código Cliente',
		'rut' => "RUC",
	);

	$datos_facturacion = array(
		'factura_razon_social' => 'Razón Social',
		'factura_giro' => 'Giro',
		'factura_direccion' => 'Dirección',
		'cod_factura_telefono' => 'Código Teléfono',
		'factura_telefono' => 'Teléfono',
	);

	$datos_solicitante = array(
		'titulo_contacto' => 'Título',
		'nombre_contacto' => 'Nombre',
		'apellido_contacto' => 'Apellido',
		'fono_contacto' => 'Teléfono',
		'email_contacto' => 'E-mail',
		'direccion_contacto' => 'Dirección Envío',
	);

	$datos_tarificacion = array(
		'id_tarifa' => 'Tarifa Horas',
		'id_moneda' => 'Tarifa en',
		'forma_cobro' => 'Forma de liquidaciones',
		'opc_moneda_total' => 'Mostrar en',
		'observaciones' => 'Detalle de Cobranza',
		'asunto' => 'Asunto'
	);

	try{
		$respuesta = $Criteria->run();
	} catch(Exception $e) {
		error_log('Error al ejecutar la SQL');
	}

	foreach ($respuesta as $key => $row) {
		$campos_tmp = '';
		foreach ($datos_cliente as $key => $value) {
			if (strlen($row[$key]) == 0 || $row[$key] == '0' || $row[$key] == '-1') {
				$campos_tmp .= (strlen($campos_tmp) > 0 ? ', ' : '') . $value;
			}
		}

		$campos .= strlen($campos_tmp) > 0 ? 'Datos Cliente: ' . $campos_tmp . "\n" : '';
		$campos_tmp = '';

		foreach ($datos_facturacion as $key => $value) {
			if (strlen($row[$key]) == 0 || $row[$key] == '0' || $row[$key] == '-1') {
				$campos_tmp .= (strlen($campos_tmp) > 0 ? ', ' : '') . $value;
			}
		}

		$campos .= strlen($campos_tmp) > 0 ? 'Datos Facturacion: ' . $campos_tmp . "\n" : '';
		$campos_tmp = '';

		foreach ($datos_solicitante as $key => $value) {
			if (strlen($row[$key]) == 0 || $row[$key] == '0' || $row[$key] == '-1') {
				$campos_tmp .= (strlen($campos_tmp) > 0 ? ', ' : '') . $value;
			}
		}

		$campos .= strlen($campos_tmp) > 0 ? 'Solicitante: ' . $campos_tmp . "\n" : '';
		$campos_tmp = '';

		foreach ($datos_tarificacion as $key => $value) {
			if (strlen($row[$key]) == 0 || $row[$key] == '0' || $row[$key] == '-1') {
				$campos_tmp .= (strlen($campos_tmp) > 0 ? ', ' : '') . $value;
			}
		}

		$campos .= strlen($campos_tmp) > 0 ? 'Tarificación: ' . $campos_tmp : '';
		$campos_tmp = '';

		if (strlen($campos) > 0) {
			$ws1->write($fila_inicial, 1, $row['codigo_cliente'], $f5);
			$ws1->write($fila_inicial, 2, $row['glosa_cliente'], $f4);
			$ws1->write($fila_inicial, 3, $row['asunto'], $f4);
			$ws1->write($fila_inicial, 4, $campos, $f4);
			$incompletos++;
			$fila_inicial++;
			$campos = '';
		}
	}

	if ($incompletos == 0) {
		$ws1->write($fila_inicial, 1, 'No se encontraron clientes con datos incompletos', $f5);
	}

  $wb->close();
  exit;
