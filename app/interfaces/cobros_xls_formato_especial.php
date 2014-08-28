<?php

require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

$sesion = new Sesion(array('ADM', 'COB'));
$pagina = new Pagina($sesion);

if ($id_cobro) {
	$where_cobro = " AND cobro.id_cobro=$id_cobro ";
} else {
	$where_cobro = '';
}

if (!$opc_ver_columna_cobrable) {
	$where_cobro .= " AND trabajo.visible=1 AND trabajo.cobrable != 0";
}

$cobro = new Cobro($sesion);
$cobro->Load($id_cobro);

$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS *,
				trabajo.id_cobro,
				trabajo.id_trabajo,
				trabajo.codigo_asunto,
				trabajo.cobrable,
				prm_moneda.simbolo AS simbolo,
				asunto.codigo_cliente AS codigo_cliente,
				cobro.id_moneda AS id_moneda_asunto,
				asunto.id_asunto AS id,
				trabajo.fecha_cobro AS fecha_cobro_orden,
				IF( trabajo.cobrable = 1, 'SI', 'NO') AS glosa_cobrable,
				trabajo.visible,
				cobro.estado AS estado_cobro,
				CONCAT_WS(' ',usuario.nombre, usuario.apellido1) AS usr_nombre,
				DATE_FORMAT(duracion,'%H:%i') AS duracion,
				DATE_FORMAT(duracion_cobrada,'%H:%i') AS duracion_cobrada,
				TIME_TO_SEC(duracion)/3600 AS duracion_horas,
				IF( trabajo.cobrable = 1, trabajo.tarifa_hh, '0') AS tarifa_hh,
				DATE_FORMAT(trabajo.fecha_cobro,'%e-%c-%x') AS fecha_cobro,
				cobro.estado,
				cobro.forma_cobro,
				cobro.monto AS monto_total_cobro,
				asunto.glosa_asunto,
				cobro.descuento,
				cobro.monto_gastos,
				cobro.retainer_horas,
				contrato.id_contrato,
				cobro.fecha_ini,
				cobro.fecha_fin,
				cobro.id_moneda_monto AS id_moneda_monto,
				contrato.rut as rut
			FROM trabajo
				JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
				LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
				JOIN cobro ON trabajo.id_cobro = cobro.id_cobro AND cobro.estado='" . $cobro->fields['estado'] . "'
				LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
				LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
				LEFT JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda
			WHERE 1 $where_cobro";

$orden = "cliente.glosa_cliente, contrato.id_contrato, asunto.glosa_asunto, trabajo.fecha, trabajo.descripcion";
$b1 = new Buscador($sesion, $query, "Trabajo", $desde, '', $orden);
$lista = $b1->lista;

$wb = new Spreadsheet_Excel_Writer();
$wb->setVersion(8);
$wb->send('Resumen de cobros.xls');
$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);

// Definimos los formatos comunes a usar en las celdas.
// Los formatos de moneda se definen para cada asunto.
$formato_encabezado = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'vcenter',
			'Align' => 'justify',
			'Border' => 1,
			'Bold' => 1,
			'Color' => 'black',
			'FontFamily' => 'Calibri'));
$formato_titulo_arriba = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'vcenter',
			'Align' => 'center',
			'Bold' => 1,
			'Locked' => 1,
			'Top' => 1,
			'Left' => 1,
			'Right' => 1,
			'FgColor' => '35',
			'Color' => 'black',
			'FontFamily' => 'Calibri'));
$formato_titulo_abajo = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'vcenter',
			'Align' => 'center',
			'Bold' => 1,
			'Locked' => 1,
			'Left' => 1,
			'Right' => 1,
			'Bottom' => 1,
			'FgColor' => '35',
			'Color' => 'black',
			'FontFamily' => 'Calibri'));
$formato_normal = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'vcenter',
			'Align' => 'justify',
			'Border' => 1,
			'Color' => 'black',
			'FontFamily' => 'Calibri'));
$formato_normal_center = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'vcenter',
			'Align' => 'center',
			'Border' => 1,
			'Color' => 'black',
			'FontFamily' => 'Calibri'));
$formato_tiempo = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'vcenter',
			'Align' => 'center',
			'Border' => 1,
			'Color' => 'black',
			'NumFormat' => '[h]:mm',
			'FontFamily' => 'Calibri'));
$formato_total = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'vcenter',
			'Align' => 'center',
			'Bold' => 1,
			'Border' => 1,
			'Color' => 'black',
			'FontFamily' => 'Calibri'));
$formato_tiempo_total = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'vcenter',
			'Align' => 'center',
			'Bold' => 1,
			'Border' => 1,
			'Color' => 'black',
			'NumFormat' => '[h]:mm',
			'FontFamily' => 'Calibri'));

// Asumiendo que todos los trabajos del cobro están en la misma moneda.
$simbolo_moneda = Utiles::glosa($sesion, $lista->Get(0)->fields['id_moneda_asunto'] ? $lista->Get(0)->fields['id_moneda_asunto'] : $cobro->fields['id_moneda'], 'simbolo', 'prm_moneda', 'id_moneda');
$glosa_moneda = Utiles::glosa($sesion, $lista->Get(0)->fields['id_moneda_asunto'] ? $lista->Get(0)->fields['id_moneda_asunto'] : $cobro->fields['id_moneda'], 'glosa_moneda', 'prm_moneda', 'id_moneda');

if ($glosa_moneda == "Euro") {
	$simbolo_moneda = "EUR";
}

$cifras_decimales = Utiles::glosa($sesion, $lista->Get(0)->fields['id_moneda_asunto'] ? $lista->Get(0)->fields['id_moneda_asunto'] : $cobro->fields['id_moneda'], 'cifras_decimales', 'prm_moneda', 'id_moneda');

if ($cifras_decimales > 0) {

	$decimales = '.';
	while ($cifras_decimales-- > 0) {
		$decimales .= '0';
	}

} else {
	$decimales = '';
}

$formato_moneda_titulo = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'vcenter',
			'Align' => 'center',
			'Left' => 1,
			'Right' => 1,
			'Bottom' => 1,
			'Color' => 'black',
			'FgColor' => '35',
			'Bold' => 1,
			'NumFormat' => "#,###,0$decimales [$" . $simbolo_moneda . "]",
			'FontFamily' => 'Calibri'));
$formato_moneda_total = & $wb->addFormat(array('Size' => 10,
			'VAlign' => 'vcenter',
			'Align' => 'right',
			'Bold' => '1',
			'Border' => 1,
			'Color' => 'black',
			'NumFormat' => "#,###,0$decimales",
			'FontFamily' => 'Calibri'));

// Definimos las columnas, mantenerlas así permite agregar nuevas columnas sin tener que rehacer todo.
$col = 1;
$col_asunto = $col++;
$col_abogados = array();
$nombres = array();

// Calcular cuántos abogados hay para dejar espacio para saber donde poner el total.
// Primero van los socios, luego el resto en orden alfabético por apellido.
$query = "SELECT DISTINCT usuario.id_usuario, usuario.nombre, usuario.apellido1, usuario.username
	FROM trabajo
		JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
		LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
		JOIN cobro ON trabajo.id_cobro = cobro.id_cobro AND cobro.estado = '{$cobro->fields['estado']}'
		LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
		LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
		LEFT JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda
	WHERE 1 {$where_cobro}
	ORDER BY usuario.id_categoria_usuario = 1 DESC, usuario.id_categoria_usuario = 4 DESC, usuario.apellido1";

$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
$col_formula_primer_abogado = Utiles::NumToColumnaExcel($col);

while (list($id_usuario, $nombre, $apellido1, $username) = mysql_fetch_array($resp)) {
	$col_formula_ultimo_abogado = Utiles::NumToColumnaExcel($col);
	$col_abogados[$id_usuario] = $col++;
	$nombres[$id_usuario] = $username;
}

$col_total_horas = $col++;
$col_total = $col++;
unset($col);

// Valores para usar en las fórmulas de la hoja
$col_formula_total_horas = Utiles::NumToColumnaExcel($col_total_horas);
$col_formula_total = Utiles::NumToColumnaExcel($col_total);

$filas = 1;

$ws = & $wb->addWorksheet('Reporte');
// Seteamas el ancho de las columnas
$ws->setPortrait();
# $ws->setLandscape;
// margenes
$ws->setMarginLeft(0.25);
$ws->setMarginRight(0.25);
$ws->setMarginTop(0.75);
$ws->setMarginBottom(0.75);
$ws->fitToPages(1, 1);
$ws->hideGridlines();
$ws->hideScreenGridlines();
$ws->setColumn(0, 0, 2);
$ws->setColumn($col_asunto, $col_asunto, 30);
foreach ($col_abogados as $id => $col)
	$ws->setColumn($col, $col, 12);
$ws->setColumn($col_total_horas, $col_total, 12);
$ws->setZoom(75);

// Escribir encabezado
// Agregar la imagen del logo
$altura_logo = UtilesApp::AlturaLogoExcel();
if ($altura_logo) {
	$ws->setRow($filas, $altura_logo);
	$ws->insertBitmap($filas, $col_asunto, UtilesApp::GetConf($sesion, 'LogoExcel'), 0, 0, 1, 1);
}
$filas += 3;
$ws->write($filas, $col_asunto, __('Cliente'), $formato_encabezado);

$ws->write($filas, $col_asunto + 1, $lista->Get(0)->fields['factura_razon_social'], $formato_encabezado);
$ws->write($filas, $col_asunto + 2, '', $formato_encabezado);
$ws->write($filas, $col_asunto + 3, '', $formato_encabezado);
$ws->write($filas, $col_asunto + 4, '', $formato_encabezado);
$ws->mergeCells($filas, $col_asunto + 1, $filas, $col_asunto + 4);
++$filas;

$ws->write($filas, $col_asunto, __('Período'), $formato_encabezado);

if ( $cobro->fields['fecha_ini'] == '0000-00-00' ) {
	$query_fecha_primer_trabajo = "SELECT MIN( DATE( fecha ) ) FROM trabajo WHERE id_cobro ='".$cobro->fields['id_cobro']."'";
	$resp_fecha_primer_trabajo = mysql_query($query_fecha_primer_trabajo, $sesion->dbh) or Utiles::errorSQL($query_fecha_primer_trabajo, __FILE__, __LINE__, $sesion->dbh);
list($primer_trabajo) = mysql_fetch_array($resp_fecha_primer_trabajo);
	$fecha_primer_trabajo = date('d-m-Y', strtotime($primer_trabajo));
} else {
	$fecha_primer_trabajo = date('d-m-Y', strtotime($cobro->fields['fecha_ini']));
}

$ws->write($filas, $col_asunto + 1, $fecha_primer_trabajo . " hasta " . Utiles::sql2date($cobro->fields['fecha_fin'], "%d-%m-%Y"), $formato_encabezado);
$ws->write($filas, $col_asunto + 2, '', $formato_encabezado);
$ws->write($filas, $col_asunto + 3, '', $formato_encabezado);
$ws->write($filas, $col_asunto + 4, '', $formato_encabezado);
$ws->mergeCells($filas, $col_asunto + 1, $filas, $col_asunto + 4);
$filas += 3;

// Escribir título de la tabla, incluyendo nombres de abogados y tarifas.
$ws->write($filas, $col_asunto, __('Asunto'), $formato_titulo_arriba);
foreach ($col_abogados as $id => $col)
	$ws->write($filas, $col, $nombres[$id], $formato_titulo_arriba);
$ws->write($filas, $col_total_horas, __('Total'), $formato_titulo_arriba);
$ws->write($filas, $col_total, __('Total'), $formato_titulo_arriba);
++$filas;
$ws->write($filas, $col_asunto, '', $formato_titulo_abajo);
$ws->write($filas, $col_total_horas, __('horas'), $formato_titulo_abajo);
$ws->write($filas, $col_total, $simbolo_moneda, $formato_titulo_abajo);
++$filas;
$fila_tarifas = $filas;

$codigo_asunto_anterior = $lista->Get(0)->fields['codigo_asunto'];

for ($i = 0; $i < $lista->num; ++$i) {
	$trabajo = $lista->Get($i);
	// Cada vez que aparezca un nuevo asunto escribimos una línea con los totales del anterior y reseteamos esas variables.
	if ($codigo_asunto_anterior != $trabajo->fields['codigo_asunto']) {
		$ws->write($filas, $col_asunto, $nombre_asunto, $formato_normal);
		foreach ($col_abogados as $id => $col) {
			if ($horas_abogado[$id] > 0)
				$ws->writeNumber($filas, $col, $horas_abogado[$id], $formato_tiempo);
			else
				$ws->write($filas, $col, '', $formato_normal);
		}
		$ws->writeFormula($filas, $col_total_horas, "=SUM($col_formula_primer_abogado" . ($filas + 1) . ":$col_formula_ultimo_abogado" . ($filas + 1) . ")", $formato_tiempo_total);

		$formula = '=24*(';
		foreach ($col_abogados as $id => $col) {
			$col_formula = Utiles::NumToColumnaExcel($col);
			$formula .= $col_formula . ($filas + 1) . "*$col_formula$fila_tarifas + ";
		}
		$formula .= '0)';
		$ws->writeFormula($filas, $col_total, $formula, $formato_moneda_total);

		// Forma elegante, pero que no funciona con la librería Pear.
		// $ws->writeFormula($filas, $col_total, "=24*SUMPRODUCT($col_formula_primer_abogado".($filas+1).":$col_formula_ultimo_abogado".($filas+1)."; $col_formula_primer_abogado$fila_tarifas:$col_formula_ultimo_abogado$fila_tarifas)", $formato_moneda_total);
		++$filas;

		$codigo_asunto_anterior = $trabajo->fields['codigo_asunto'];
		unset($nombre_asunto);
		unset($horas_abogado);
	}

	// Aumentamos los totales del asunto actual.
	$nombre_asunto = $trabajo->fields['glosa_asunto'];
	$duracion_cobrada = $trabajo->fields['duracion_cobrada'];
	list($h, $m) = split(':', $duracion_cobrada);
	$duracion_cobrada = $h / 24 + $m / (24 * 60);
	$horas_abogado[$trabajo->fields['id_usuario']] += $duracion_cobrada;

	$tarifa_abogado[$trabajo->fields['id_usuario']] = $trabajo->fields['tarifa_hh'];
}
// Escribir la línea del último asunto
if ($lista->num > 0) {
	$ws->write($filas, $col_asunto, $nombre_asunto, $formato_normal);
	foreach ($col_abogados as $id => $col) {
		if ($horas_abogado[$id] > 0)
			$ws->writeNumber($filas, $col, $horas_abogado[$id], $formato_tiempo);
		else
			$ws->write($filas, $col, '', $formato_normal);
	}
	if ($lista->num > 0)
		$ws->writeFormula($filas, $col_total_horas, "=SUM($col_formula_primer_abogado" . ($filas + 1) . ":$col_formula_ultimo_abogado" . ($filas + 1) . ")", $formato_tiempo_total);

	$formula = '=24*(';
	foreach ($col_abogados as $id => $col) {
		$col_formula = Utiles::NumToColumnaExcel($col);
		$formula .= $col_formula . ($filas + 1) . "*$col_formula$fila_tarifas + ";
	}
	$formula .= '0)';
	if ($lista->num > 0)
		$ws->writeFormula($filas, $col_total, $formula, $formato_moneda_total);
}
else {
	$ws->write($filas, $col_asunto, 'No existen horas en este ' . __('cobro') . '.', $formato_normal_center);
	$ws->write($filas, $col_asunto + 1, '', $formato_normal);
	$ws->write($filas, $col_asunto + 2, '', $formato_normal);
	$ws->mergeCells($filas, $col_asunto, $filas, $col_asunto + 2);
}
++$filas;

// Escribir la tarifa de cada abogado.
foreach ($col_abogados as $id => $col)
	$ws->writeNumber($fila_tarifas - 1, $col, $tarifa_abogado[$id], $formato_moneda_titulo);

// Escribir la línea con los totales
$ws->write($filas, $col_asunto, __('Total'), $formato_total);
foreach ($col_abogados as $id => $col) {
	$col_formula = Utiles::NumToColumnaExcel($col);
	$ws->writeFormula($filas, $col, "=SUM($col_formula" . ($fila_tarifas + 1) . ":$col_formula$filas)", $formato_tiempo_total);
}
if ($lista->num > 0) {
	$ws->writeFormula($filas, $col_total_horas, "=SUM($col_formula_total_horas" . ($fila_tarifas + 1) . ":$col_formula_total_horas$filas)", $formato_tiempo_total);
	$ws->writeFormula($filas, $col_total, "=SUM($col_formula_total" . ($fila_tarifas + 1) . ":$col_formula_total$filas)", $formato_moneda_total);
} else {
	$ws->writeNumber($filas, $col_asunto + 1, 0, $formato_tiempo_total);
	$ws->writeNumber($filas, $col_asunto + 2, 0, $formato_moneda_total);
}

$encabezado = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'top',
			'Align' => 'justify',
			'Bold' => '1',
			'Color' => 'black'));
$tit = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'top',
			'Align' => 'center',
			'Bold' => '1',
			'Locked' => 1,
			'Border' => 1,
			'FgColor' => '35',
			'Color' => 'black'));
$f3c = & $wb->addFormat(array('Size' => 11,
			'Align' => 'left',
			'Bold' => '1',
			'FgColor' => '35',
			'Border' => 1,
			'Locked' => 1,
			'Color' => 'black'));
$f4 = & $wb->addFormat(array('Size' => 11,
			'VAlign' => 'top',
			'Align' => 'justify',
			'Border' => 1,
			'Color' => 'black',
			'NumFormat' => 0));
$tex = & $wb->addFormat(array('Size' => 11,
			'valign' => 'top',
			'Align' => 'justify',
			'Border' => 1,
			'Color' => 'black',
			'TextWrap' => 1));
$time_format = & $wb->addFormat(array('Size' => 11,
			'VAlign' => 'top',
			'Align' => 'justify',
			'Border' => 1,
			'Color' => 'black',
			'NumFormat' => '[h]:mm'));
$total = & $wb->addFormat(array('Size' => 11,
			'Align' => 'right',
			'Bold' => '1',
			'FgColor' => '36',
			'Border' => 1,
			'Color' => 'black',
			'NumFormat' => 0));

$wb->close();
?>
