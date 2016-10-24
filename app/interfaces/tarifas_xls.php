<?php
	require_once dirname(__FILE__).'/../conf.php';

  $sesion = new Sesion(array('REV', 'ADM'));
  $pagina = new Pagina($sesion);

  $wb = new WorkbookMiddleware();

  $wb->setCustomColor ( 35, 220, 255, 220 );
  $wb->setCustomColor ( 36, 255, 255, 220 );

	$titulo = $wb->addFormat(array('Size' => 12,
	                      'VAlign' => 'top',
	                      'Align' => 'justify',
	                      'Bold' => '1',
	                      'Color' => 'black'));
	$cabecera = $wb->createFormatArray(array('Size' => 12,
	                      'VAlign' => 'top',
	                      'Align' => 'justify',
	                      'Bold' => '1',
	                      'Border' => 1,
	                      'FgColor' => 35,
	                      'Color' => 'black'));
	$cabecera['borders']['allborders']['style'] = PHPExcel_Style_Border::BORDER_THIN;

	$texto = $wb->createFormatArray(array('Size' => 11, 'VAlign' => 'top', 'Align' => 'left', 'Color' => 'black'	));
	$texto['borders']['allborders']['style'] = PHPExcel_Style_Border::BORDER_THIN;

	// Tarifas
	$tarifa = new Criteria($sesion);
	$tarifa = $tarifa->add_select('id_tarifa')
										->add_select('glosa_tarifa')
										->add_from('tarifa')
										->add_restriction(CriteriaRestriction::equals('id_tarifa', $id_tarifa_edicion))
										->run();
	$tarifa = $tarifa[0];

	$id_tarifa = $tarifa['id_tarifa'];
	$titulo_hoja = __('Tarifa') . " {$id_tarifa} " . substr($tarifa['glosa_tarifa'], 0, 15);
	$exp = [':', '\\', '/', '?', '*', '[', ']'];
	$titulo_hoja = str_replace($exp, '', $titulo_hoja);
	$fila_inicial = 2;
	$columna_fin = 0;
	$columna_monedas = [];

	$ws = $wb->addWorksheet($titulo_hoja);
	$ws->write(0, 0, __('Detalle de tarifa') . " {$tarifa['glosa_tarifa']}", $titulo);
	// Merge de A1:A3
	$ws->mergeCells(0, 0, 0, 2);
	$ws->setRow(0, 14);
	$ws->setRow(1, 14);

	/* ENCABEZADOS */
	$monedas = new Criteria($sesion);
	$monedas = $monedas->add_select('id_moneda')
										->add_select('glosa_moneda')
										->add_from('prm_moneda')
										->add_ordering('id_moneda')
										->run();
	$ws->write($fila_inicial, $columna_fin, __('Categoria'));
	$ws->setColumn(0, 0, 70);

	foreach ($monedas as $moneda) {
		$ws->write($fila_inicial, ++$columna_fin, $moneda['glosa_moneda']);
		$ws->setColumn($columna_fin, $columna_fin, 25);
		$columna_monedas[$moneda['id_moneda']] = $columna_fin;
	}

	$fila = $fila_inicial + 1;
	$columna_fin = PHPExcel_Cell::stringFromColumnIndex($columna_fin);
	$wb->workSheetObj->getStyle("A{$fila}:{$columna_fin}{$fila}")->applyFromArray($cabecera);

	// Sección Usuarios
	$usuarios = new Criteria($sesion);
	$usuarios = $usuarios->add_select('usuario_tarifa.id_usuario')
										->add_select("CONCAT_WS(' ', TRIM(usuario.apellido1), TRIM(usuario.apellido2), TRIM(usuario.nombre))", 'nombre_usuario')
										->add_select("IF(usuario_tarifa.tarifa > 0,usuario_tarifa.tarifa,'')", 'tarifa')
										->add_select('usuario_tarifa.id_moneda')
										->add_from('usuario_tarifa')
										->add_inner_join_with('usuario', CriteriaRestriction::equals('usuario_tarifa.id_usuario', 'usuario.id_usuario'))
										->add_inner_join_with('usuario_permiso', CriteriaRestriction::equals('usuario_permiso.id_usuario', 'usuario_tarifa.id_usuario'))
										->add_restriction(CriteriaRestriction::and_clause(
											CriteriaRestriction::equals('usuario_tarifa.id_tarifa', $id_tarifa),
											CriteriaRestriction::equals('usuario.visible', 1),
											CriteriaRestriction::equals('usuario_permiso.codigo_permiso', "'PRO'")
										))
										->add_ordering('nombre_usuario')
										->add_ordering('usuario_tarifa.id_moneda')
										->run();

	$usuarios_tarifa = [];

	foreach ($usuarios as $usuario) {
		$usuarios_tarifa[$usuario['id_usuario']][] = [
			'nombre' => $usuario['nombre_usuario'],
			'tarifa' => $usuario['tarifa'],
			'id_moneda' => $usuario['id_moneda']
		];
	}

	$fila_inicio = $fila_inicial + 1;
	foreach ($usuarios_tarifa as $tarifas) {
		++$fila_inicial;
		foreach ($tarifas as $tarifa) {
			$ws->write($fila_inicial, 0, $tarifa['nombre']);
			$ws->write($fila_inicial, $columna_monedas[$tarifa['id_moneda']], $tarifa['tarifa']);
		}
	}

	$fila_fin = $fila_inicial + 1;
	$wb->workSheetObj->getStyle("A{$fila_inicio}:A{$fila_fin}")->applyFromArray($texto);
	$wb->workSheetObj->getStyle("B{$fila_inicio}:{$columna_fin}{$fila_fin}")->applyFromArray($texto);

	// Sección Clientes
	$clientes = new Criteria($sesion);
	$clientes = $clientes->add_select('cliente.id_cliente')
											->add_select("CONCAT_WS(' - ', cliente.codigo_cliente, cliente.glosa_cliente)", 'glosa_cliente')
											->add_select("CONCAT_WS(' - ', asunto.codigo_asunto, asunto.glosa_asunto)", 'glosa_asunto')
											->add_from('cliente')
											->add_inner_join_with('asunto', CriteriaRestriction::equals('cliente.codigo_cliente', 'asunto.codigo_cliente'))
											->add_inner_join_with('contrato', CriteriaRestriction::equals('asunto.id_contrato', 'contrato.id_contrato'))
											->add_restriction(CriteriaRestriction::equals('contrato.id_tarifa', $id_tarifa))
											->add_ordering('cliente.id_cliente')
											->run();

	$clientes_tarifa = [];

	foreach ($clientes as $cliente) {
		$clientes_tarifa[$cliente['glosa_cliente']][] = $cliente['glosa_asunto'];
	}

	$fila_inicial = $fila_inicial + 2;
	$ws->write($fila_inicial, 0, __('Clientes'), $titulo);
	$ws->setRow($fila_inicial, 14);
	// Merge de $fila_inicial1:$fila_inicial3
	$ws->mergeCells($fila_inicial, 0, $fila_inicial, 2);

	$ws->write(++$fila_inicial, 0, __('Cliente'));
	$ws->write($fila_inicial, 1, __('Asuntos'));
	$ws->mergeCells($fila_inicial, 1, $fila_inicial, 5);

	$fila = $fila_inicial + 1;
	$wb->workSheetObj->getStyle("A{$fila}:B{$fila}")->applyFromArray($cabecera);

	$fila_inicio = $fila_inicial + 1;
	foreach ($clientes_tarifa as $cliente => $asuntos) {
		++$fila_inicial;
		$ws->write($fila_inicial, 0, $cliente);
		$ws->write($fila_inicial, 1, implode(', ', $asuntos));
		$ws->mergeCells($fila_inicial, 1, $fila_inicial, 5);
	}
	$fila_fin = $fila_inicial + 1;
	$columna_fin = PHPExcel_Cell::stringFromColumnIndex(5);
	$wb->workSheetObj->getStyle("A{$fila_inicio}:{$columna_fin}{$fila_fin}")->applyFromArray($texto);

	$wb->send('Tarifa_'.$glosa.'.xls');
  $wb->close();
  exit;
