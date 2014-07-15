<?php
require_once dirname(__FILE__) . '/../../conf.php';
require_once 'Spreadsheet/Excel/Writer.php';

set_time_limit(300);
/*
  Este archivo debe ser llamado mediante require_once() desde otro archivo (actualmente solo desde app/interfaces/reporte_costos.php)
  Necesita las liguientes variables para funcionar:
  $sesion
  $fecha1_a	: año inicio del período a consultar.
  $fecha1_m	: mes inicio del período a consultar.
  $fecha2_a	: año fin del período a consultar.
  $fecha1_m	: mes fin del período a consultar.
  $opc	: indica qué tipo de reporte generar, puede tomar los siguientes valores:
  "reporte": se usa el rango de fechas especificado por el usuario (fecha1 y fecha2).
  "excel_anual": se genera un reporte anual, con una hoja detallada por mes, un resumen por mes y un resumen por abogado.
 */

if (!Conf::GetConf($sesion, 'ReportesAvanzados')) {
	exit;
}

$query = 'SELECT simbolo, cifras_decimales FROM prm_moneda WHERE moneda_base = 1';
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
list($simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp);

$wb = new Spreadsheet_Excel_Writer();
$wb->send("Planilla resumen costos.xls");
$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);

// Formatos para distintos tipos de celdas
$formato_titulo = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'justify',
		'Bold' => '1',
		'Locked' => 1,
		'Border' => 1,
		'FgColor' => '35',
		'Color' => 'black'));
$formato_nombre = & $wb->addFormat(array('Border' => 1, 'Size' => 12));
$formato_porcentaje = & $wb->addFormat(array('NumFormat' => '0.00%', 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
$formato_porcentaje_total = & $wb->addFormat(array('NumFormat' => '0.00%', 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));

if ($cifras_decimales) {
	$decimales = '.';
	while ($cifras_decimales--) {
		$decimales .= '#';
	}
} else {
	$decimales = '';
}

$formato_moneda = & $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales", 'Border' => 1, 'Size' => 12, 'Align' => 'right'));
$formato_moneda_total = & $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales", 'Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));
$formato_numero = & $wb->addFormat(array('Border' => 1, 'Size' => 12, 'Align' => 'right'));
$formato_numero_total = & $wb->addFormat(array('Border' => 1, 'Size' => 12, 'Align' => 'right', 'Bold' => '1'));
$formato_encabezado = & $wb->addFormat(array('Bold' => '1', 'Size' => 12, 'Align' => 'justify', 'VAlign' => 'top', 'Color' => 'black'));

if (Conf::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
	$dato_usuario = "username";
} else {
	$dato_usuario = "CONCAT(apellido1,' ',apellido2,', ',nombre)";
}

if (Conf::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
	$dato_usuario_valor = 'usuario.username';
} else {
	$dato_usuario_valor = 'CONCAT_WS(\' \',usuario.nombre, usuario.apellido1, LEFT(usuario.apellido2,1))';
}
// Lista de abogados sobre los que se calculan valores. Calculado aquí por eficiencia.
if ($solo_pro) {
	$query = "SELECT " . $dato_usuario . " AS nombre_usuario,
						 " . $dato_usuario_valor . " AS codigo_usuario,
							usuario.id_usuario
						FROM usuario
						JOIN usuario_permiso USING( id_usuario )
						WHERE usuario.visible = 1 AND usuario_permiso.codigo_permiso = 'PRO'
						ORDER BY nombre_usuario, usuario.id_usuario";
} else {
	$query = "SELECT " . $dato_usuario . " AS nombre_usuario,
						 " . $dato_usuario_valor . " AS codigo_usuario,
							id_usuario
						FROM usuario
						WHERE usuario.visible=1
						ORDER BY nombre_usuario, id_usuario";
}
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

$nombres = array();
$codigos_usuarios = array();
$ids = array();
while (list($n, $c, $i) = mysql_fetch_array($resp)) {
	$nombres[] = $n;
	$codigos_usuarios[] = $c;
	$ids[] = $i;
}

$fecha2_a = empty($fecha2_a) ? $fecha_a : $fecha2_a;
$duracion_mes = array('31', (Utiles::es_bisiesto($fecha2_a) ? '29' : '28'), '31', '30', '31', '30', '31', '31', '30', '31', '30', '31');
$meses = array(__("Enero"), __("Febrero"), __("Marzo"), __("Abril"), __("Mayo"), __("Junio"), __("Julio"), __("Agosto"), __("Septiembre"), __("Octubre"), __("Noviembre"), __("Diciembre"));

if ($opc == 'reporte') { // Generar planilla con las fechas ingresadas por el usuario.
	$fecha1 = $fecha1_a . "-" . sprintf("%02d", $fecha1_m + 1) . "-01";
	$fecha2 = $fecha2_a . "-" . sprintf("%02d", $fecha2_m + 1) . "-" . $duracion_mes[$fecha2_m];
	generarHoja($wb, $sesion, $fecha1, $fecha2, $nombres, $ids, $codigos_usuarios);
} else {	// Generar planilla con una hoja por mes.
	// Página con un resumen de facturación por mes
	$ws_facturacion = & $wb->addWorksheet(__("Facturación"));
	$ws_facturacion->setInputEncoding('utf-8');
	$ws_facturacion->fitToPages(1, 0);
	$ws_facturacion->setZoom(80);

	// Página con un resumen de facturación de abogados por mes
	$ws_fact_abogados = & $wb->addWorksheet(__("Fact abogados"));
	$ws_fact_abogados->setInputEncoding('utf-8');
	$ws_fact_abogados->fitToPages(1, 0);
	$ws_fact_abogados->setZoom(80);

	$primera_llamada = true;

	for ($j = 1; $j < 13;  ++$j) {
		$fecha1 = $fecha_a . "-" . sprintf("%02d", $j) . "-01";
		$fecha2 = $fecha_a . "-" . sprintf("%02d", $j) . "-" . $duracion_mes[$j - 1];
		generarHoja($wb, $sesion, $fecha1, $fecha2, $nombres, $ids, $codigos_usuarios, 'Costos ' . $meses[$j - 1]);
	}
}

$wb->close();

function generarHoja($wb, $sesion, $fecha1, $fecha2, $nombres, $ids, $codigos_usuarios, $titulo = '', $offset_filas = 2, $offset_columnas = 1) {
	if ($titulo == '') {
		$titulo = "Costos " . $fecha1 . " - " . $fecha2;
	}
	$ws = & $wb->addWorksheet($titulo);
	$ws->setInputEncoding('utf-8');
	$ws->fitToPages(1, 0);
	$ws->setZoom(80);

	// Formatos de celdas.
	global $formato_titulo;
	global $formato_porcentaje;
	global $formato_porcentaje_total;
	global $formato_moneda;
	global $formato_nombre;
	global $formato_moneda_total;
	global $formato_numero;
	global $formato_numero_total;
	global $formato_encabezado;

	// variables para usar en las fórmulas
	$col_nombres = Utiles::NumToColumnaExcel($offset_columnas + 0);
	$col_horas = Utiles::NumToColumnaExcel($offset_columnas + 1);
	$col_minutos = Utiles::NumToColumnaExcel($offset_columnas + 2);
	$col_total = Utiles::NumToColumnaExcel($offset_columnas + 3);
	$col_factura_promedio = Utiles::NumToColumnaExcel($offset_columnas + 4);
	$col_costo = Utiles::NumToColumnaExcel($offset_columnas + 5);
	$col_costo_promedio = Utiles::NumToColumnaExcel($offset_columnas + 6);
	$col_margen = Utiles::NumToColumnaExcel($offset_columnas + 7);
	$col_porcentaje_costo = Utiles::NumToColumnaExcel($offset_columnas + 8);
	$col_porcentaje_margen = Utiles::NumToColumnaExcel($offset_columnas + 9);

	$ws->write($offset_filas, $offset_columnas, __("Reporte resumen costos"), $formato_encabezado);
	$ws->write($offset_filas + 2, $offset_columnas, __("Generado el:"), $formato_encabezado);
	$ws->write($offset_filas + 2, $offset_columnas + 1, date("Y-m-d  h:i:s"), $formato_encabezado);
	$ws->write($offset_filas + 3, $offset_columnas, __("Fecha consulta:"), $formato_encabezado);
	$ws->write($offset_filas + 3, $offset_columnas + 1, $fecha1 . " - " . $fecha2, $formato_encabezado);

	if ($offset_columnas > 0) {
		$ws->setColumn(0, $offset_columnas - 1, 5);
	}
	$ws->setColumn($offset_columnas, $offset_columnas, 30);
	$ws->setColumn($offset_columnas + 1, $offset_columnas + 9, 15);
	$ws->mergeCells($offset_filas, $offset_columnas, $offset_filas, $offset_columnas + 9);
	$ws->mergeCells($offset_filas + 2, $offset_columnas + 1, $offset_filas + 2, $offset_columnas + 9);
	$ws->mergeCells($offset_filas + 3, $offset_columnas + 1, $offset_filas + 3, $offset_columnas + 9);

	$offset_filas += 7;
	$fila = $offset_filas + 1;

	// Imprimir encabezado tabla
	$ws->write($offset_filas, $offset_columnas, __('Abogado'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 1, __('Horas'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 2, __('Minutos'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 3, __('Total facturado'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 4, __('Factura hora promedio'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 5, __('Total costo'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 6, __('Costo hora promedio'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 7, __('Margen de contribución'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 8, __('%costo'), $formato_titulo);
	$ws->write($offset_filas, $offset_columnas + 9, __('%margen'), $formato_titulo);

	global $primera_llamada;
	global $ws_facturacion;
	global $ws_fact_abogados;

	// Imprimir contenido
	for ($i = 0; $i < count($nombres); ++$i) {
		$id_moneda_base = Moneda::GetMonedaBase($sesion);

		$s_monto_thh_simple = "IF(cobro.monto_thh>0,cobro.monto_thh,IF(cobro.monto_trabajos>0,cobro.monto_trabajos,1))";
		$s_monto_thh = $s_monto_thh_simple;

		$reporte = new Reporte($sesion);
		$reporte->AddFiltro('usuario', 'id_usuario', $ids[$i]);
		$reporte->addRangoFecha(Utiles::sql2date($fecha1), Utiles::sql2date($fecha2));
		$reporte->addAgrupador('profesional');
		$reporte->setVista('profesional');
		$reporte->setTipoDato('valor_cobrado');
		$reporte->id_moneda = $id_moneda_base;
		$reporte->Query();

		$resultado = $reporte->toArray();
		$total = number_format($resultado[$codigos_usuarios[$i]]['valor'], 2, '.', '');

		$reporte = new Reporte($sesion);
		$reporte->AddFiltro('usuario', 'id_usuario', $ids[$i]);
		$reporte->addRangoFecha(Utiles::sql2date($fecha1), Utiles::sql2date($fecha2));
		$reporte->addAgrupador('profesional');
		$reporte->setVista('profesional');
		$reporte->setTipoDato('horas_visibles');
		$reporte->Query();

		$resultado = $reporte->toArray();
		$horas = floor($resultado['total']);
		$minutos = $resultado['total'] % 60;

		// Nombre
		$ws->write($fila, $offset_columnas, $nombres[$i], $formato_nombre);
		// Horas y minutos
		$ws->write($fila, $offset_columnas + 1, $horas ? $horas : 0, $formato_numero);
		$ws->write($fila, $offset_columnas + 2, $minutos ? $minutos : 0, $formato_numero);
		// Total facturado
		$ws->write($fila, $offset_columnas + 3, $total ? $total : 0, $formato_moneda);
		// Factura hora promedio
		$ws->writeFormula($fila, $offset_columnas + 4, "=IF($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60>0, $col_total" . ($fila + 1) . "/($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60), \"- \")", $formato_moneda);
		// Total costo
		$query3 = "SELECT SUM(costo)
						FROM usuario_costo
						WHERE id_usuario=" . $ids[$i] . " AND fecha >= '$fecha1' AND fecha <= '$fecha2'";
		$resp3 = mysql_query($query3, $sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $sesion->dbh);
		list($costo) = mysql_fetch_array($resp3);
		$ws->write($fila, $offset_columnas + 5, $costo, $formato_moneda);
		// Costo hora promedio
		$ws->writeFormula($fila, $offset_columnas + 6, "=IF($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60>0, $col_costo" . ($fila + 1) . "/($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60), \"- \")", $formato_moneda);
		// Margen de contribución
		$ws->writeFormula($fila, $offset_columnas + 7, "=$col_total" . ($fila + 1) . "-$col_costo" . ($fila + 1), $formato_moneda);
		// % costo
		$ws->writeFormula($fila, $offset_columnas + 8, "=IF($col_total" . ($fila + 1) . ">0, $col_costo" . ($fila + 1) . "/$col_total" . ($fila + 1) . ", IF($col_costo" . ($fila + 1) . ">0, 1, \"- \"))", $formato_porcentaje);
		// % margen
		$ws->writeFormula($fila, $offset_columnas + 9, "=IF($col_total" . ($fila + 1) . ">0, $col_margen" . ($fila + 1) . "/$col_total" . ($fila + 1) . ", IF($col_costo" . ($fila + 1) . ">0, 0, \"- \"))", $formato_porcentaje);

		if ($ws_fact_abogados != null) {
			if ($primera_llamada) {
				$ws_fact_abogados->write($fila, $offset_columnas, $nombres[$i], $formato_nombre);
			}
			$ws_fact_abogados->writeFormula($fila, $offset_columnas + substr($fecha1, 5, 2), "='$titulo'!$col_total" . ($fila + 1), $formato_moneda);
		}
		++$fila;
	}

	// Imprimir total
	$ws->write($fila, $offset_columnas, __('Total'), $formato_moneda_total);
	// Horas
	$ws->writeFormula($fila, $offset_columnas + 1, "=SUM($col_horas" . ($offset_filas + 2) . ":$col_horas$fila)+FLOOR(SUM($col_minutos" . ($offset_filas + 2) . ":$col_minutos$fila)/60, 1)", $formato_numero_total);
	// Minutos
	$ws->writeFormula($fila, $offset_columnas + 2, "=MOD(SUM($col_minutos" . ($offset_filas + 2) . ":$col_minutos$fila), 60)", $formato_numero_total);
	// Total facturado
	$ws->writeFormula($fila, $offset_columnas + 3, "=SUM($col_total" . ($offset_filas + 2) . ":$col_total$fila)", $formato_moneda_total);
	// Factura hora promedio
	$ws->writeFormula($fila, $offset_columnas + 4, "=IF($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60>0, $col_total" . ($fila + 1) . "/($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60), \"- \")", $formato_moneda_total);
	// Total costo
	$ws->writeFormula($fila, $offset_columnas + 5, "=SUM($col_costo" . ($offset_filas + 2) . ":$col_costo$fila)", $formato_moneda_total);
	// Costo hora promedio
	$ws->writeFormula($fila, $offset_columnas + 6, "=IF($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60>0, $col_costo" . ($fila + 1) . "/($col_horas" . ($fila + 1) . "+$col_minutos" . ($fila + 1) . "/60), \"- \")", $formato_moneda_total);
	// Margen de contribución
	$ws->writeFormula($fila, $offset_columnas + 7, "=$col_total" . ($fila + 1) . "-$col_costo" . ($fila + 1), $formato_moneda_total);
	// % costo
	$ws->writeFormula($fila, $offset_columnas + 8, "=IF($col_total" . ($fila + 1) . ">0, $col_costo" . ($fila + 1) . "/$col_total" . ($fila + 1) . ", IF($col_costo" . ($fila + 1) . ">0, 1, \"- \"))", $formato_porcentaje_total);
	// % margen
	$ws->writeFormula($fila, $offset_columnas + 9, "=IF($col_total" . ($fila + 1) . ">0, $col_margen" . ($fila + 1) . "/$col_total" . ($fila + 1) . ", IF($col_costo" . ($fila + 1) . ">0, 0, \"- \"))", $formato_porcentaje_total);


	if ($primera_llamada) {
		$primera_llamada = false;
		if ($ws_facturacion != null) {
			$offset_filas -= 7;
			// Escribir título y fecha de creación
			$ws_facturacion->write($offset_filas, $offset_columnas, __("Reporte resumen costos"), $formato_encabezado);
			$ws_facturacion->write($offset_filas + 2, $offset_columnas, __("Generado el:"), $formato_encabezado);
			$ws_facturacion->write($offset_filas + 2, $offset_columnas + 2, date("Y-m-d  h:i:s"), $formato_encabezado);
			$ws_facturacion->write($offset_filas + 3, $offset_columnas, __("Fecha consulta:"), $formato_encabezado);
			$ws_facturacion->write($offset_filas + 3, $offset_columnas + 2, $fecha1 . " - " . substr($fecha1, 0, 5) . "12-31", $formato_encabezado);
			// Dar formato a las columnas
			if ($offset_columnas > 0) {
				$ws_facturacion->setColumn(0, $offset_columnas - 1, 5);
			}
			$ws_facturacion->setColumn($offset_columnas, $offset_columnas + 9, 15);
			$ws_facturacion->mergeCells($offset_filas, $offset_columnas, $offset_filas, $offset_columnas + 7);
			$ws_facturacion->mergeCells($offset_filas + 2, $offset_columnas, $offset_filas + 2, $offset_columnas + 1);
			$ws_facturacion->mergeCells($offset_filas + 3, $offset_columnas, $offset_filas + 3, $offset_columnas + 1);
			$ws_facturacion->mergeCells($offset_filas + 2, $offset_columnas + 2, $offset_filas + 2, $offset_columnas + 7);
			$ws_facturacion->mergeCells($offset_filas + 3, $offset_columnas + 2, $offset_filas + 3, $offset_columnas + 7);
			$offset_filas += 7;
			// Imprimir encabezado
			$ws_facturacion->write($offset_filas, $offset_columnas, __('Mes'), $formato_titulo);
			$ws_facturacion->write($offset_filas, $offset_columnas + 1, __('Horas'), $formato_titulo);
			$ws_facturacion->write($offset_filas, $offset_columnas + 2, __('Minutos'), $formato_titulo);
			$ws_facturacion->write($offset_filas, $offset_columnas + 3, __('Total facturado'), $formato_titulo);
			$ws_facturacion->write($offset_filas, $offset_columnas + 4, __('Factura hora promedio'), $formato_titulo);
			$ws_facturacion->write($offset_filas, $offset_columnas + 5, __('Total costo'), $formato_titulo);
			$ws_facturacion->write($offset_filas, $offset_columnas + 6, __('Costo hora promedio'), $formato_titulo);
			$ws_facturacion->write($offset_filas, $offset_columnas + 7, __('Margen de contribución'), $formato_titulo);
			// Imprimir fórmulas de totales
			$ws_facturacion->write($offset_filas + 13, $offset_columnas, __("Total"), $formato_moneda_total);
			$ws_facturacion->writeFormula($offset_filas + 13, $offset_columnas + 1, "=SUM($col_horas" . ($offset_filas + 2) . ":$col_horas" . ($offset_filas + 13) . ")+FLOOR(SUM($col_minutos" . ($offset_filas + 2) . ":$col_minutos" . ($offset_filas + 13) . ")/60, 1)", $formato_numero_total);
			$ws_facturacion->writeFormula($offset_filas + 13, $offset_columnas + 2, "=MOD(SUM($col_minutos" . ($offset_filas + 2) . ":$col_minutos" . ($offset_filas + 13) . "), 60)", $formato_numero_total);
			$ws_facturacion->writeFormula($offset_filas + 13, $offset_columnas + 3, "=SUM($col_total" . ($offset_filas + 2) . ":$col_total" . ($offset_filas + 13) . ")", $formato_moneda_total);
			$ws_facturacion->writeFormula($offset_filas + 13, $offset_columnas + 4, "=IF($col_horas" . ($offset_filas + 14) . "+$col_minutos" . ($offset_filas + 14) . "/60>0, $col_total" . ($offset_filas + 14) . "/($col_horas" . ($offset_filas + 14) . "+$col_minutos" . ($offset_filas + 14) . "/60), \"- \")", $formato_moneda_total);
			$ws_facturacion->writeFormula($offset_filas + 13, $offset_columnas + 5, "=SUM($col_costo" . ($offset_filas + 2) . ":$col_costo" . ($offset_filas + 13) . ")", $formato_moneda_total);
			$ws_facturacion->writeFormula($offset_filas + 13, $offset_columnas + 6, "=IF($col_horas" . ($offset_filas + 14) . "+$col_minutos" . ($offset_filas + 14) . "/60>0, $col_costo" . ($offset_filas + 14) . "/($col_horas" . ($offset_filas + 14) . "+$col_minutos" . ($offset_filas + 14) . "/60), \"- \")", $formato_moneda_total);
			$ws_facturacion->writeFormula($offset_filas + 13, $offset_columnas + 7, "=$col_total" . ($offset_filas + 14) . "-$col_costo" . ($offset_filas + 14), $formato_moneda_total);
		}
		if ($ws_fact_abogados != null) {
			$offset_filas -= 7;
			// Escribir título y fecha de creación
			$ws_fact_abogados->write($offset_filas, $offset_columnas, __("Reporte resumen costos") . " - " . __("Facturación abogados"), $formato_encabezado);
			$ws_fact_abogados->write($offset_filas + 2, $offset_columnas, __("Generado el:"), $formato_encabezado);
			$ws_fact_abogados->write($offset_filas + 2, $offset_columnas + 1, date("Y-m-d  h:i:s"), $formato_encabezado);
			$ws_fact_abogados->write($offset_filas + 3, $offset_columnas, __("Fecha consulta:"), $formato_encabezado);
			$ws_fact_abogados->write($offset_filas + 3, $offset_columnas + 1, $fecha1 . " - " . substr($fecha1, 0, 5) . "12-31", $formato_encabezado);
			// Dar formato a las columnas
			if ($offset_columnas > 0) {
				$ws_fact_abogados->setColumn(0, $offset_columnas - 1, 5);
			}
			$ws_fact_abogados->setColumn($offset_columnas, $offset_columnas, 25);
			$ws_fact_abogados->setColumn($offset_columnas + 1, $offset_columnas + 13, 15);
			$ws_fact_abogados->mergeCells($offset_filas, $offset_columnas, $offset_filas, $offset_columnas + 7);
			$ws_fact_abogados->mergeCells($offset_filas + 2, $offset_columnas + 1, $offset_filas + 2, $offset_columnas + 7);
			$ws_fact_abogados->mergeCells($offset_filas + 3, $offset_columnas + 1, $offset_filas + 3, $offset_columnas + 7);
			$offset_filas += 7;
			// Imprimir encabezado
			$ws_fact_abogados->write($offset_filas, $offset_columnas, __('Abogado'), $formato_titulo);
			global $meses;
			for ($k = 0; $k < 12; ++$k) {
				$ws_fact_abogados->write($offset_filas, $offset_columnas + 1 + $k, $meses[$k], $formato_titulo);
			}
			$ws_fact_abogados->write($offset_filas, $offset_columnas + 1 + $k, __('Total'), $formato_titulo);
			$ws_fact_abogados->write($fila, $offset_columnas, __("Total"), $formato_nombre);
			// Imprimir fórmulas de totales por mes (abajo)
			for ($k = 0; $k < 13; ++$k) {
				$col = Utiles::NumToColumnaExcel($offset_columnas + $k + 1);
				$ws_fact_abogados->writeFormula($fila, $offset_columnas + $k + 1, "=SUM($col" . ($offset_filas + 2) . ":$col$fila)", $formato_moneda_total);
			}
			// Imprimir fórmulas de totales abogado (a la derecha)
			$col1 = Utiles::NumToColumnaExcel($offset_columnas + 1);
			$col2 = Utiles::NumToColumnaExcel($offset_columnas + 12);
			for ($k = 0; $k < $fila - ($offset_filas + 1); ++$k) {
				$ws_fact_abogados->writeFormula($offset_filas + 1 + $k, $offset_columnas + 13, "=SUM($col1" . ($offset_filas + 2 + $k) . ":$col2" . ($offset_filas + 2 + $k) . ")", $formato_moneda_total);
			}
		}
	}

	// Si es un reporte anual escribir un resumen.
	if ($ws_facturacion != null) {
		global $meses;
		// Rellenar las celdas del mes con las referencias al resumen en su hoja.
		$ws_facturacion->write($offset_filas + substr($fecha1, 5, 2), $offset_columnas, $meses[substr($fecha1, 5, 2) - 1], $formato_nombre);
		$ws_facturacion->writeFormula($offset_filas + substr($fecha1, 5, 2), $offset_columnas + 1, "='$titulo'!$col_horas" . ($fila + 1), $formato_numero);
		$ws_facturacion->writeFormula($offset_filas + substr($fecha1, 5, 2), $offset_columnas + 2, "='$titulo'!$col_minutos" . ($fila + 1), $formato_numero);
		$ws_facturacion->writeFormula($offset_filas + substr($fecha1, 5, 2), $offset_columnas + 3, "='$titulo'!$col_total" . ($fila + 1), $formato_moneda);
		$ws_facturacion->writeFormula($offset_filas + substr($fecha1, 5, 2), $offset_columnas + 4, "='$titulo'!$col_factura_promedio" . ($fila + 1), $formato_moneda);
		$ws_facturacion->writeFormula($offset_filas + substr($fecha1, 5, 2), $offset_columnas + 5, "='$titulo'!$col_costo" . ($fila + 1), $formato_moneda);
		$ws_facturacion->writeFormula($offset_filas + substr($fecha1, 5, 2), $offset_columnas + 6, "='$titulo'!$col_costo_promedio" . ($fila + 1), $formato_moneda);
		$ws_facturacion->writeFormula($offset_filas + substr($fecha1, 5, 2), $offset_columnas + 7, "='$titulo'!$col_margen" . ($fila + 1), $formato_moneda);
	}
}
