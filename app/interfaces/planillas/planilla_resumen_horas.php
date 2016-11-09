<?php
require_once dirname(__FILE__) . '/../../conf.php';

/*
	Este archivo debe ser llamado mediante require_once() desde otro archivo (actualmente solo desde app/interfaces/reporte_financiero.php)
	Necesita las liguientes variables para funcionar:
	$Sesion
	$fecha1	: fecha inicio periodo consulta, en formato dd-mm-aaaa.
	$fecha2	: fecha término periodo consulta, en formato dd-mm-aaaa.
	$vista	: varible que indica la forma de agrupar los datos. Puede tomar los siguientes valores:
	- 'profesional'
	- 'mes_reporte'
	- 'glosa_cliente'
	- 'glosa_asunto' : agrupa primero por cliente y luego por asunto.
 */

if (!Conf::GetConf($Sesion, 'ReportesAvanzados')) {
	exit;
}

$Moneda = new Moneda($Sesion);

$id_moneda = isset($moneda_visualizacion) ? $moneda_visualizacion : $Moneda::GetMonedaBase($Sesion);
$proporcionalidad = isset($proporcionalidad) ? $proporcionalidad : 'cliente';

$Moneda->Load($id_moneda);
$simbolo_moneda = $Moneda->fields['simbolo'];
$cifras_decimales = $Moneda->fields['cifras_decimales'];

$wb = new WorkbookMiddleware();

$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);
$wb->setCustomColor(40, 204, 204, 255);
$wb->setCustomColor(41, 192, 192, 192);
$wb->setCustomColor(42, 255, 204, 0);
$ws->setRow(2, 14);

// Formatos para distintos tipos de celdas
$formato_titulo_1 = $wb->addFormat(array('FgColor' => '35', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
$formato_titulo_2 = $wb->addFormat(array('FgColor' => '36', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
$formato_titulo_3 = $wb->addFormat(array('FgColor' => '40', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
$formato_titulo_4 = $wb->addFormat(array('FgColor' => '41', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
$formato_titulo_5 = $wb->addFormat(array('FgColor' => '42', 'Size' => 12, 'VAlign' => 'top', 'Align' => 'justify', 'Bold' => '1', 'Locked' => 1, 'Border' => 1, 'Color' => 'black'));
$formato_nombre = $wb->createFormatArray(array('Border' => 1, 'Size' => 12, 'VAlign' => 'top'));
$formato_nombre['borders']['allborders']['style'] = PHPExcel_Style_Border::BORDER_THIN;
$formato_porcentaje = $wb->createFormatArray(array('NumFormat' => '0.##%', 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
$formato_porcentaje['borders']['allborders']['style'] = PHPExcel_Style_Border::BORDER_THIN;
$formato_porcentaje_total = $wb->addFormat(array('NumFormat' => '0.##%', 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));

if ($cifras_decimales) {
	$decimales = '.';
	while ($cifras_decimales--) {
		$decimales .= '#';
	}
} else {
	$decimales = '';
}

$formato_moneda = $wb->createFormatArray(array('NumFormat' => "[$$simbolo_moneda] #,##0{$decimales}", 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
$formato_moneda['borders']['allborders']['style'] = PHPExcel_Style_Border::BORDER_THIN;
$formato_moneda_total = $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,##0{$decimales}", 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));
$formato_numero = $wb->createFormatArray(array('NumFormat' => "#,##0.00", 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
$formato_numero['borders']['allborders']['style'] = PHPExcel_Style_Border::BORDER_THIN;
$formato_numero_total = $wb->addFormat(array('NumFormat' => "#,##0.00", 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));
$formato_encabezado = $wb->addFormat(array('Bold' => '1', 'Size' => 12, 'Align' => 'justify', 'VAlign' => 'top', 'Color' => 'black'));

$offset_columnas = 1;
$offset_filas = 2;

// Declarar hoja
$ws = $wb->addWorksheet(__("Reporte financiero"));
$ws->setInputEncoding('utf-8');
$ws->fitToPages(1, 0);
$ws->setZoom(80);

// Imprimir encabezado
$ws->write($offset_filas, $offset_columnas, __("Reporte financiero"), $formato_encabezado);
$ws->write($offset_filas + 2, $offset_columnas, __("Generado el:"), $formato_encabezado);
$ws->write($offset_filas + 2, $offset_columnas + 1, date("Y-m-d h:i:s"), $formato_encabezado);
$ws->write($offset_filas + 3, $offset_columnas, __("Fecha consulta:"), $formato_encabezado);
$ws->write($offset_filas + 3, $offset_columnas + 1, "$fecha1 - $fecha2", $formato_encabezado);

// Setear el ancho de las columnas y unir celdas del encabezado.
if ($offset_columnas > 0) {
	$ws->setColumn(0, $offset_columnas - 1, 5);
}

if ($vista == 'glosa_asunto') {
	$ws->setColumn($offset_columnas, $offset_columnas, 30);
	$ws->setColumn($offset_columnas + 1, $offset_columnas + 1, 15);
	$ws->setColumn($offset_columnas + 2, $offset_columnas + 2, 30);
	$ws->setColumn($offset_columnas + 3, $offset_columnas + 13, 15);
} else {
	$ws->setColumn($offset_columnas, $offset_columnas, 30);
	$ws->setColumn($offset_columnas + 1, $offset_columnas + 11, 15);
}

$ws->mergeCells($offset_filas, $offset_columnas, $offset_filas, $offset_columnas + 5);
$ws->mergeCells($offset_filas + 2, $offset_columnas + 1, $offset_filas + 2, $offset_columnas + 5);
$ws->mergeCells($offset_filas + 3, $offset_columnas + 1, $offset_filas + 3, $offset_columnas + 5);

$offset_filas += 7;

// Imprimir títulos de la tabla
if ($vista == 'glosa_asunto') {
	$ws->write($offset_filas, $offset_columnas, __('glosa_cliente'), $formato_titulo_1);
	++$offset_columnas;
	$ws->write($offset_filas, $offset_columnas, __('Código'), $formato_titulo_1);
	++$offset_columnas;
}

$ws->write($offset_filas, $offset_columnas, __($vista), $formato_titulo_1);
$ws->write($offset_filas, $offset_columnas + 1, __('Horas trabajadas'), $formato_titulo_2);
$ws->write($offset_filas, $offset_columnas + 2, __('Horas cobrables'), $formato_titulo_2);
$ws->write($offset_filas, $offset_columnas + 3, __('Horas cobrables corregidas'), $formato_titulo_2);
$ws->write($offset_filas, $offset_columnas + 4, __('Horas cobradas'), $formato_titulo_2);
$ws->write($offset_filas, $offset_columnas + 5, __('Horas pagadas'), $formato_titulo_2);
$ws->write($offset_filas, $offset_columnas + 6, __('Valor trámites'), $formato_titulo_2);
$ws->write($offset_filas, $offset_columnas + 7, __('Valor cobrado'), $formato_titulo_3);
$ws->write($offset_filas, $offset_columnas + 8, __('Valor cobrado por hora'), $formato_titulo_3);
$ws->write($offset_filas, $offset_columnas + 9, __('Costo'), $formato_titulo_4);
$ws->write($offset_filas, $offset_columnas + 10, __('Costo por hora trabajada'), $formato_titulo_4);
$ws->write($offset_filas, $offset_columnas + 11, __('Margen bruto'), $formato_titulo_5);
$ws->write($offset_filas, $offset_columnas + 12, __('Porcentaje margen'), $formato_titulo_5);

$fila = $offset_filas;

$meses = array(__("Enero"), __("Febrero"), __("Marzo"), __("Abril"), __("Mayo"), __("Junio"), __("Julio"), __("Agosto"), __("Septiembre"), __("Octubre"), __("Noviembre"), __("Diciembre"));

$fecha1_a = substr($fecha1, 6);
$fecha2_a = substr($fecha2, 6);
$fecha1_m = substr($fecha1, 3, 2);
$fecha2_m = substr($fecha2, 3, 2);

if ($vista == 'profesional') {

	$fecha_ini = "{$fecha1_a}-{$fecha1_m}-01";
	$largo_meses = cal_days_in_month(CAL_GREGORIAN, $fecha2_m, $fecha2_a);
	$fecha_fin = "{$fecha2_a}-{$fecha2_m}-{$largo_meses}";


	if (Conf::GetConf($Sesion, 'UsaUsernameEnTodoElSistema')) {
		$dato_profesional = "username";
	} else {
		$dato_profesional = "CONCAT(apellido1,' ',apellido2,', ',nombre)";

		if ($seleccion == 'profesionales') {
			$where_usuarios = " AND usuario_permiso.codigo_permiso = 'PRO' ";
		}

		$where_usuarios .= " AND (
			(
				SELECT SUM(costo)
				FROM usuario_costo
				WHERE usuario_costo.id_usuario = usuario.id_usuario
				AND usuario_costo.fecha >= '$fecha_ini'
				AND usuario_costo.fecha <= '$fecha_fin' ) > 0
				OR (
					SELECT SUM( TIME_TO_SEC( duracion_cobrada ) )
					FROM trabajo
					WHERE trabajo.id_usuario = usuario.id_usuario
					AND trabajo.fecha >= '$fecha_ini'
					AND trabajo.fecha <= '$fecha_fin'
				) > 0
			) ";
	}

	// Lista de abogados sobre los que se calculan valores.
	$query = "SELECT
							{$dato_profesional} AS nombre_usuario,
							usuario.id_usuario
						FROM usuario
						LEFT JOIN usuario_permiso ON
							usuario_permiso.id_usuario = usuario.id_usuario
							AND usuario_permiso.codigo_permiso = 'PRO'
						WHERE visible = 1 $where_usuarios
						ORDER BY apellido1, apellido2, nombre, id_usuario";

	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	while (list($nombre_usuario, $id_usr) = mysql_fetch_array($resp)) {
		$ids[$id_usr] = $nombre_usuario;
	}
} elseif ($vista == 'glosa_cliente') {
	// Lista de clientes sobre los que se calculan valores. Aparece el encargado comercial
	$query = "SELECT
							glosa_cliente,
							codigo_cliente
						FROM cliente
						ORDER BY glosa_cliente, id_cliente";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	while (list($nombre_cliente, $id_cli) = mysql_fetch_array($resp)) {
		$ids[$id_cli] = $nombre_cliente;
	}
} elseif ($vista == 'mes_reporte') {
	for ($a = 0; $a < $fecha2_a - $fecha1_a + 1; ++$a) {
		for ($m = ($a == 0 ? $fecha1_m[1] : 0); $m <= ($a == $fecha2_a - $fecha1_a ? $fecha2_m : 12); ++$m) {
			$auxmes = ($m < 10 ? '0' . $m : $m);
			$ids[($auxmes) . '-' . ($fecha1_a + $a)] = ($fecha1_a + $a) . ' - ' . $meses[$m - 1];
		}
	}
} elseif ($vista == 'glosa_asunto') {
	$_fecha_desde = Utiles::fecha2sql($fecha1);
	$_fecha_hasta = Utiles::fecha2sql($fecha2);

	$query = "SELECT DISTINCT
							trabajo.codigo_asunto,
							cliente.glosa_cliente,
							asunto.glosa_asunto
						FROM trabajo
						LEFT JOIN asunto  ON trabajo.codigo_asunto = asunto.codigo_asunto
						LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
						WHERE trabajo.fecha >= '{$_fecha_desde}'
							AND trabajo.fecha <= '{$_fecha_hasta}'
						ORDER BY trabajo.codigo_asunto";

	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	$nombre_temp = '';
	$n_clientes = 0;
	$_fila = $fila;

	while (list($codigo_asunto, $nombre_cliente, $nombre_asunto) = mysql_fetch_array($resp)) {
		++$_fila;
		if ($nombre_temp != $nombre_cliente) {
			// Revisar si hay que fusionar varias celdas verticalmente.
			if ($n_clientes > 0) {
				$ws->mergeCells($_fila - $n_clientes, $offset_columnas - 2, $_fila - 1, $offset_columnas - 2);
				$n_clientes = 0;
			}
			$ws->write($_fila, $offset_columnas - 2, $nombre_cliente);
			$nombre_temp = $nombre_cliente;
		}
		++$n_clientes;
		$ws->write($_fila, $offset_columnas - 1, $codigo_asunto);
		$ids[$codigo_asunto] = $nombre_asunto;
	}
}

/*
 *
 * Varibles necesarias para obtener los distintos tipos de horas usando la clase Reporte
 * valor_pagado 	 Este se puede comparar con las horas pagadas.
 * valor_cobrado  Este se compara con las horas trabajadas para tener el costo real por hora.
 */

$_vista = $vista == 'glosa_asunto' ? 'codigo_asunto' : $vista;
$datos_reporte = array(
	'horas_trabajadas' => array(
		'vista' => $_vista
	),
	'horas_cobrables' => array(
		'vista' => $_vista
	),
	'horas_visibles' => array(
		'vista' => $_vista
	),
	'horas_cobradas' => array(
		'vista' => $_vista
	),
	'horas_pagadas' => array(
		'vista' => $_vista
	),
	'valor_tramites' => array(
		'vista' => $_vista
 	),
	'valor_cobrado' => array(
		'vista' => $_vista
	),
	'costo' => array(
		'vista' => $_vista
	)
);
$data = [];
foreach ($datos_reporte as $tipo_dato => $config) {
	$reporte = new Reporte($Sesion);
	$reporte->id_moneda = $id_moneda;
	if (!empty($proporcionalidad)) {
		$reporte->setProporcionalidad($proporcionalidad);
	}
	// $fecha1 y $fecha2 deben estar en formato dd-mm-aaaa
	$reporte->addRangoFecha($fecha1, $fecha2);
	$data[$tipo_dato] = setDatosColumna($reporte, $tipo_dato, $config['vista']);
}

// variables para usar en las fórmulas
$col_trabajadas = Utiles::NumToColumnaExcel($offset_columnas + 1);
$col_cobrables = Utiles::NumToColumnaExcel($offset_columnas + 2);
$col_cobrables_corregidas = Utiles::NumToColumnaExcel($offset_columnas + 3);
$col_cobradas = Utiles::NumToColumnaExcel($offset_columnas + 4);
$col_pagadas = Utiles::NumToColumnaExcel($offset_columnas + 5);
$col_valor_tramites = Utiles::NumToColumnaExcel($offset_columnas + 6);
$col_valor_cobrado = Utiles::NumToColumnaExcel($offset_columnas + 7);
$col_costo = Utiles::NumToColumnaExcel($offset_columnas + 9);
$col_margen_bruto = Utiles::NumToColumnaExcel($offset_columnas + 11);

foreach($ids as $key => $value) {
	$celda_valor_cobrado = "$col_valor_cobrado" . ($offset_filas + $t + 2);
	$celda_valor_tramites = "$col_valor_tramites" . ($offset_filas + $t + 2);
	$celda_horas_trabajadas = "$col_trabajadas" . ($offset_filas + $t + 2);
	$celda_horas_cobradas = "$col_cobradas" . ($offset_filas + $t + 2);
	$celda_costo = "$col_costo" . ($offset_filas + $t + 2);
	$celda_margen_bruto = "$col_margen_bruto" . ($offset_filas + $t + 2);

	$ws->write(++$offset_filas, $offset_columnas, $value);

	$ws->writeNumber($offset_filas, $offset_columnas + 1, !empty($data['horas_trabajadas'][$key]) ? $data['horas_trabajadas'][$key] : 0);
	$ws->writeNumber($offset_filas, $offset_columnas + 2, !empty($data['horas_cobrables'][$key]) ? $data['horas_cobrables'][$key] : 0);
	$ws->writeNumber($offset_filas, $offset_columnas + 3, !empty($data['horas_visibles'][$key]) ? $data['horas_visibles'][$key] : 0);
	$ws->writeNumber($offset_filas, $offset_columnas + 4, !empty($data['horas_cobradas'][$key]) ? $data['horas_cobradas'][$key] : 0);
	$ws->writeNumber($offset_filas, $offset_columnas + 5, !empty($data['horas_pagadas'][$key]) ? $data['horas_pagadas'][$key] : 0);
	$ws->writeNumber($offset_filas, $offset_columnas + 6, !empty($data['valor_tramites'][$key]) ? $data['valor_tramites'][$key] : 0);
	$ws->writeNumber($offset_filas, $offset_columnas + 7, !empty($data['valor_cobrado'][$key]) ? $data['valor_cobrado'][$key] : 0);
	$ws->writeNumber($offset_filas, $offset_columnas + 9, !empty($data['costo'][$key]) ? $data['costo'][$key] : 0);

	// Imprimir valor cobrado por hora
	$ws->writeFormula($offset_filas, $offset_columnas + 8, "=IF($celda_horas_cobradas > 0, ($celda_valor_cobrado - $celda_valor_tramites) / $celda_horas_cobradas, \"- \")");
	// Imprimir costo por hora trabajada
	$ws->writeFormula($offset_filas, $offset_columnas + 10, "=IF($celda_horas_trabajadas > 0, $celda_costo / $celda_horas_trabajadas, \"- \")");
	// Imprimir margen bruto
	$ws->writeFormula($offset_filas, $offset_columnas + 11, "=$celda_valor_cobrado - $celda_costo");
	// Imprimir porcentaje margen
	$ws->writeFormula($offset_filas, $offset_columnas + 12, "=IF($celda_valor_cobrado > 0, $celda_margen_bruto / $celda_valor_cobrado, \"- \")");
}

// Imprimir totales, están afuera del 'for' porque usan otro formato
++$fila;
$fila_total = ++$offset_filas + 1;
$celda_valor_cobrado = "$col_valor_cobrado{$fila_total}";
$celda_valor_tramites = "$col_valor_tramites{$fila_total}";
$celda_horas_trabajadas = "$col_trabajadas{$fila_total}";
$celda_horas_cobradas = "$col_cobradas{$fila_total}";
$celda_costo = "$col_costo{$fila_total}";
$celda_margen_bruto = "$col_margen_bruto{$fila_total}";

$ws->write($offset_filas, $offset_columnas, __("Total"));
$ws->writeFormula($offset_filas, $offset_columnas + 1, "=SUM({$col_trabajadas}{$offset_filas}:{$col_trabajadas}{$fila})", $formato_numero_total);
$ws->writeFormula($offset_filas, $offset_columnas + 2, "=SUM({$col_cobrables}{$offset_filas}:{$col_cobrables}{$fila})", $formato_numero_total);
$ws->writeFormula($offset_filas, $offset_columnas + 3, "=SUM({$col_cobrables_corregidas}{$offset_filas}:{$col_cobrables_corregidas}{$fila})", $formato_numero_total);
$ws->writeFormula($offset_filas, $offset_columnas + 4, "=SUM({$col_cobradas}{$offset_filas}:{$col_cobradas}{$fila})", $formato_numero_total);
$ws->writeFormula($offset_filas, $offset_columnas + 5, "=SUM({$col_pagadas}{$offset_filas}:{$col_pagadas}{$fila})", $formato_numero_total);
$ws->writeFormula($offset_filas, $offset_columnas + 6, "=SUM({$col_valor_tramites}{$offset_filas}:{$col_valor_tramites}{$fila})", $formato_moneda_total);
$ws->writeFormula($offset_filas, $offset_columnas + 7, "=SUM({$col_valor_cobrado}{$offset_filas}:{$col_valor_cobrado}{$fila})", $formato_moneda_total);
$ws->writeFormula($offset_filas, $offset_columnas + 8, "=IF({$celda_horas_cobradas} > 0, ({$celda_valor_cobrado} - {$celda_valor_tramites}) / $celda_horas_cobradas, \"- \")", $formato_moneda_total);
$ws->writeFormula($offset_filas, $offset_columnas + 9, "=SUM({$col_costo}{$offset_filas}:{$col_costo}{$fila})", $formato_moneda_total);
$ws->writeFormula($offset_filas, $offset_columnas + 10, "=IF({$celda_horas_trabajadas} > 0, {$celda_costo} / {$celda_horas_trabajadas}, \"- \")", $formato_moneda_total);
$ws->writeFormula($offset_filas, $offset_columnas + 11, "={$celda_valor_cobrado} - {$celda_costo}", $formato_moneda_total);
$ws->writeFormula($offset_filas, $offset_columnas + 12, "=IF({$celda_valor_cobrado} > 0, {$celda_margen_bruto} / {$celda_valor_cobrado}, \"- \")", $formato_porcentaje_total);

// Formatos
if ($vista == 'glosa_asunto') {
	$columna_inicio = PHPExcel_Cell::stringFromColumnIndex($offset_columnas - 2);
	$columna_fin = PHPExcel_Cell::stringFromColumnIndex($offset_columnas - 1);
	$wb->applyFormat("{$columna_inicio}{$fila}:{$columna_fin}{$offset_filas}", $formato_nombre);
}

$columna_inicio = PHPExcel_Cell::stringFromColumnIndex($offset_columnas);
$columna_fin = PHPExcel_Cell::stringFromColumnIndex($offset_columnas);
$wb->applyFormat("{$columna_inicio}{$fila}:{$columna_fin}{$fila_total}", $formato_nombre);

$columna_inicio = PHPExcel_Cell::stringFromColumnIndex($offset_columnas + 1);
$columna_fin = PHPExcel_Cell::stringFromColumnIndex($offset_columnas + 5);
$wb->applyFormat("{$columna_inicio}{$fila}:{$columna_fin}{$offset_filas}", $formato_numero);

$columna_inicio = PHPExcel_Cell::stringFromColumnIndex($offset_columnas + 6);
$columna_fin = PHPExcel_Cell::stringFromColumnIndex($offset_columnas + 11);
$wb->applyFormat("{$columna_inicio}{$fila}:{$columna_fin}{$offset_filas}", $formato_moneda);

$columna_inicio = PHPExcel_Cell::stringFromColumnIndex($offset_columnas + 12);
$columna_fin = PHPExcel_Cell::stringFromColumnIndex($offset_columnas + 12);
$wb->applyFormat("{$columna_inicio}{$fila}:{$columna_fin}{$offset_filas}", $formato_porcentaje);

// Terminar de imprimir
$wb->send("Planilla resumen horas");
$wb->close();

// Sirve para imprimir una columna, usando la clase Reporte
function setDatosColumna($reporte, $tipo_dato, $vista) {
	global $vacio;
	global $offset_filas;
	global $offset_columnas;

	$reporte->setTipoDato($tipo_dato);
	$reporte->setVista($vista);
	$reporte->Query();
	$r = $reporte->toArray();
	$tmp = [];
	foreach ($r as $key => $value) {
		unset($r[$key][$key]);
		unset($r['total']);
		unset($r['total_divisor']);
		foreach ($r as $filtro => $data) {
			$tmp[$data['filtro_valor']] = $data['valor'];
		}
	}
	return $tmp;
}
