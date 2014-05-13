<?php

require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';

$sesion = new Sesion(array('REV', 'ADM', 'PRO'));

// Defino los permisos validos
$cobranzapermitido = false;
$params_array['codigo_permiso'] = 'COB';
$p_cobranza = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
if ($p_cobranza->fields['permitido']) {
	$cobranzapermitido = true;
}

$revisorpermitido = false;
$params_array['codigo_permiso'] = 'REV';
$p_revisor = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
if ($p_revisor->fields['permitido']) {
	$revisorpermitido = true;
}

$profesionalpermitido = false;
$params_array['codigo_permiso'] = 'PRO';
$p_profesional = $sesion->usuario->permisos->Find('FindPermiso', $params_array);
if ($p_profesional->fields['permitido']) {
	$profesionalpermitido = true;
}

// Le muestro la tarifa cuando tiene el Conf, es profesional no revisor
$mostrar_tarifa_al_profesional =
	UtilesApp::GetConf($sesion, 'MostrarTarifaAlProfesional') &&
	$profesionalpermitido && !$revisorpermitido;


//$key = substr(md5(microtime().posix_getpid()), 0, 8);

$wb = new Spreadsheet_Excel_Writer();

$wb->setVersion(8);
$wb->send('Revisión de horas.xls');
$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);

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
$fdd = & $wb->addFormat(array('Size' => 11,
		'VAlign' => 'top',
		'Align' => 'justify',
		'Border' => 1,
		'Color' => 'black'));
$fdd->setNumFormat(0.0);
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

$ws = & $wb->addWorksheet(__('Reportes'));
#$ws->setInputEncoding('utf-8');
$ws->fitToPages(1, 0);
$ws->setZoom(75);

// Definición de columnas
$col = 0;
if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
	$col_id_trabajo = $col++;
}
$col_fecha = $col++;
$col_cliente = $col++;
if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
	$col_codigo_asunto = $col++;
}
$col_asunto = $col++;
$col_encargado = $col++;
$col_id_cobro = $col++;
if (UtilesApp::GetConf($sesion, 'UsoActividades')) {
	$col_actividad = $col++;
}

$col_descripcion = $col++;
$col_nombre_usuario = $col++;

$solicitante = Conf::GetConf($sesion, 'OrdenadoPor');
if ($solicitante == 1 || $solicitante == 2 ){
	$col_solicitante = $col++;
}

$col_duracion = $col++;
$col_duracion_cobrada = $col++;
$col_cobrable = $col++;
$col_tarifa_hh = $col++;
$col_valor_trabajo = $col++;

// Valores para las fórmulas
$col_formula_duracion = Utiles::NumToColumnaExcel($col_duracion);
$col_formula_duracion_cobrada = Utiles::NumToColumnaExcel($col_duracion_cobrada);
$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);

$col = 3;
// Setear el ancho de las columnas
if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
	$ws->setColumn($col_id_trabajo, $col_id_trabajo, 15);
}
$ws->setColumn($col_fecha, $col_fecha, 10);
$ws->setColumn($col_cliente, $col_cliente, 30);
if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
	$ws->setColumn($col_codigo_asunto, $col_codigo_asunto, 20);
}
$ws->setColumn($col_asunto, $col_asunto, 30);
$ws->setColumn($col_encargado, $col_encargado, 30);
$ws->setColumn($col_id_cobro, $col_id_cobro, 15);
if (UtilesApp::GetConf($sesion, 'UsoActividades')) {
	$ws->setColumn($col_actividad, $col_actividad, 30);
}
$ws->setColumn($col_descripcion, $col_descripcion, 33);
$ws->setColumn($col_nombre_usuario, $col_nombre_usuario, 30);
if ($solicitante == 1 || $solicitante == 2 ){
	$ws->setColumn($col_solicitante, $col_solicitante, 30);
}

$ws->setColumn($col_duracion, $col_duracion, 15.67);
$ws->setColumn($col_duracion_cobrada, $col_duracion_cobrada, 15.67);
$ws->setColumn($col_cobrable, $col_cobrable, 15);
$ws->setColumn($col_tarifa_hh, $col_tarifa_hh, 15.67);
$ws->setColumn($col_valor_trabajo, $col_valor_trabajo, 20);

if (method_exists('Conf', 'GetConf')) {
	$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
	$PdfLinea2 = Conf::GetConf($sesion, 'PdfLinea2');
} else {
	$PdfLinea1 = Conf::PdfLinea1();
	$PdfLinea2 = Conf::PdfLinea2();
}

$info_usr1 = str_replace('<br>', ' - ', $PdfLinea1);
$ws->write(1, 0, $info_usr1, $encabezado);
$ws->mergeCells(1, 0, 1, 9);
$info_usr = str_replace('<br>', ' - ', $PdfLinea2);
$ws->write(2, 0, utf8_decode($info_usr), $encabezado);
$ws->mergeCells(2, 0, 2, 9);

$fila_inicial = 4;

if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
	$ws->write($fila_inicial, $col_id_trabajo, __('N° Trabajo'), $tit);
}
$ws->write($fila_inicial, $col_fecha, __('Fecha'), $tit);
$ws->write($fila_inicial, $col_cliente, __('Cliente'), $tit);
if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
	$ws->write($fila_inicial, $col_codigo_asunto, __('Código Asunto'), $tit);
}
$ws->write($fila_inicial, $col_asunto, __('Asunto'), $tit);
$ws->write($fila_inicial, $col_encargado, __('Encargado Comercial'), $tit);
$ws->write($fila_inicial, $col_id_cobro, __('Cobro'), $tit);
if (UtilesApp::GetConf($sesion, 'UsoActividades')) {
	$ws->write($fila_inicial, $col_actividad, __('Actividad'), $tit);
}
$ws->write($fila_inicial, $col_descripcion, __('Descripción'), $tit);
$ws->write($fila_inicial, $col_nombre_usuario, __('Nombre Usuario'), $tit);
if ($solicitante == 1 || $solicitante == 2 ){
	$ws->write($fila_inicial, $col_solicitante, __('Ordenado Por '), $tit);
}
$ws->write($fila_inicial, $col_duracion, __('Duración'), $tit);
$ws->write($fila_inicial, $col_duracion_cobrada, __('Duración cobrada'), $tit);
$ws->write($fila_inicial, $col_cobrable, __('Cobrable'), $tit);

if ($cobranzapermitido || $mostrar_tarifa_al_profesional) {
	$ws->write($fila_inicial, $col_tarifa_hh, __('Tarifa HH'), $tit);
	$ws->write($fila_inicial, $col_valor_trabajo, __('Valor Trabajo'), $tit);
}
$fila_inicial++;

#La lista viene de la pagina en la cual se incluye esta.
for ($i = 0; $i < $lista->num; $i++) {
	$trabajo = $lista->Get($i);

	$moneda_total = new Objeto($sesion, '', '', 'prm_moneda', 'id_moneda');
	$moneda_total->Load($trabajo->fields['id_moneda_cobro'] > 0 ? $trabajo->fields['id_moneda_cobro'] : ( $trabajo->fields['id_moneda_asunto'] ? $trabajo->fields['id_moneda_asunto'] : 1 ) );

	// Redefinimos el formato de la moneda, para que sea consistente con la cifra.
	$simbolo_moneda = $moneda_total->fields['simbolo'];
	$cifras_decimales = $moneda_total->fields['cifras_decimales'];
	if ($cifras_decimales) {
		$decimales = '.';
		while ($cifras_decimales--)
			$decimales .= '0';
	}
	else
		$decimales = '';
	$money_format = & $wb->addFormat(array('Size' => 11,
			'VAlign' => 'top',
			'Align' => 'justify',
			'Border' => 1,
			'Color' => 'black',
			'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));

	if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
		$ws->write($fila_inicial + $i, $col_id_trabajo, $trabajo->fields['id_trabajo'], $tex);
	}
	$ws->write($fila_inicial + $i, $col_fecha, Utiles::sql2date($trabajo->fields['fecha'], "%d-%m-%Y"), $tex);
	$ws->write($fila_inicial + $i, $col_cliente, $trabajo->fields['glosa_cliente'], $tex);
	if (UtilesApp::GetConf($sesion, 'ColumnaIdYCodigoAsuntoAExcelRevisarHoras')) {
		if (UtilesApp::GetConf($sesion, 'CodigoSecundario')) {
			$ws->write($fila_inicial + $i, $col_codigo_asunto, $trabajo->fields['codigo_asunto_secundario'], $tex);
		} else {
			$ws->write($fila_inicial + $i, $col_codigo_asunto, $trabajo->fields['codigo_asunto'], $tex);
		}
	}
	$ws->write($fila_inicial + $i, $col_asunto, $trabajo->fields['glosa_asunto'], $tex);
	$ws->write($fila_inicial + $i, $col_encargado, $trabajo->fields['encargado_comercial'] ? $trabajo->fields['encargado_comercial'] : '', $tex);
	$ws->write($fila_inicial + $i, $col_id_cobro, $trabajo->fields['id_cobro'] ? $trabajo->fields['id_cobro'] : '', $tex);
	if (UtilesApp::GetConf($sesion, 'UsoActividades')) {
		$ws->write($fila_inicial + $i, $col_actividad, $trabajo->fields['glosa_actividad'], $tex);
	}

	$text_descripcion = addslashes($trabajo->fields['descripcion']);

	$ws->write($fila_inicial + $i, $col_descripcion, $text_descripcion, $tex);
	if (UtilesApp::GetConf($sesion, 'UsaUsernameEnTodoElSistema')) {
		$ws->write($fila_inicial + $i, $col_nombre_usuario, $trabajo->fields['username'], $tex);
	} else {
		$ws->write($fila_inicial + $i, $col_nombre_usuario, $trabajo->fields['usr_nombre'], $tex);
	}

	if ($solicitante == 1 || $solicitante == 2 ){
		$ws->write($fila_inicial + $i, $col_solicitante, $trabajo->fields['solicitante'], $tex);
	}

	list($duracion, $duracion_cobrada) = split('<br>', $trabajo->fields['duracion']);
	list($h, $m) = split(':', $duracion);
	$duracion_decimal = number_format($h + $m / 60, 1, '.', '');
	$tiempo_excel = $h / (24) + $m / (24 * 60); //Excel cuenta el tiempo en días
	if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
		$ws->writeNumber($fila_inicial + $i, $col_duracion, $duracion_decimal, $fdd);
	} else {
		$ws->writeNumber($fila_inicial + $i, $col_duracion, $tiempo_excel, $time_format);
	}

	if ($revisorpermitido || $mostrar_tarifa_al_profesional) {
		if ($trabajo->fields['cobrable'] == 0) {
			$duracion_cobrada = '0:00';
		}
		list($h, $m) = split(':', $duracion_cobrada);

		$duracion_cobrada_decimal = number_format($h + $m / 60, 1, '.', '');
		$tiempo_excel = $h / (24) + $m / (24 * 60); //Excel cuenta el tiempo en días
		if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			$ws->writeNumber($fila_inicial + $i, $col_duracion_cobrada, $duracion_cobrada_decimal, $fdd);
		} else {
			$ws->writeNumber($fila_inicial + $i, $col_duracion_cobrada, $tiempo_excel, $time_format);
		}
	} else {
		$ws->write($fila_inicial + $i, $col_duracion_cobrada, '', $time_format);
	}

	$ws->write($fila_inicial + $i, $col_cobrable, $trabajo->fields['cobrable'] == 1 ? "SI" : "NO", $tex);

	if ($cobranzapermitido || $mostrar_tarifa_al_profesional) {
		// Tratamos de sacar la tarifa del trabajo, si no está guardada usamos la tarifa estándar.
		if ($trabajo->fields['tarifa_hh'] > 0
			&& !empty($trabajo->fields['estado_cobro'])
			&& $trabajo->fields['estado_cobro'] != 'CREADO'
			&& $trabajo->fields['estado_cobro'] != 'EN REVISION') {

			$tarifa = $trabajo->fields['tarifa_hh'];
		} else {
			if ($trabajo->fields['id_tarifa'] && $trabajo->fields['id_moneda_contrato'] && $trabajo->fields['id_usuario']) {
				$query = "SELECT tarifa
							FROM usuario_tarifa
							WHERE id_tarifa=" . $trabajo->fields['id_tarifa'] . "
								AND id_moneda=" . $trabajo->fields['id_moneda_contrato'] . "
								AND id_usuario=" . $trabajo->fields['id_usuario'];
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list($tarifa) = mysql_fetch_array($resp);
			} else if ($trabajo->fields['id_moneda_contrato'] && $trabajo->fields['id_usuario']) {
				$query = "SELECT id_tarifa FROM tarifa WHERE tarifa_defecto=1";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list($id_tarifa) = mysql_fetch_array($resp);

				if ($id_tarifa) {
					$query = "SELECT tarifa
								FROM usuario_tarifa
								WHERE id_tarifa=" . $id_tarifa . "
									AND id_moneda=" . $trabajo->fields['id_moneda_contrato'] . "
									AND id_usuario=" . $trabajo->fields['id_usuario'];
					$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
					list($tarifa) = mysql_fetch_array($resp);
				}
			} else {
				$tarifa = 0;
			}
		}
		$ws->writeNumber($fila_inicial + $i, $col_tarifa_hh, $tarifa, $money_format);
		if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
			$formula_monto = "=$col_formula_tarifa_hh" . ($fila_inicial + $i + 1) . "*($col_formula_duracion_cobrada" . ($fila_inicial + $i + 1) . ")";
		} else {
			$formula_monto = "=$col_formula_tarifa_hh" . ($fila_inicial + $i + 1) . "*(24*($col_formula_duracion_cobrada" . ($fila_inicial + $i + 1) . "))";
		}
		$ws->writeFormula($fila_inicial + $i, $col_valor_trabajo, $formula_monto, $money_format);
	}
}
if (UtilesApp::GetConf($sesion, 'TipoIngresoHoras') == 'decimal') {
	$ws->writeFormula($fila_inicial + $i, $col_duracion, "=SUM($col_formula_duracion" . ($fila_inicial + 1) . ":$col_formula_duracion" . ($fila_inicial + $i) . ")", $fdd);
	$ws->writeFormula($fila_inicial + $i, $col_duracion_cobrada, "=SUM($col_formula_duracion_cobrada" . ($fila_inicial + 1) . ":$col_formula_duracion_cobrada" . ($fila_inicial + $i) . ")", $fdd);
} else {
	$ws->writeFormula($fila_inicial + $i, $col_duracion, "=SUM($col_formula_duracion" . ($fila_inicial + 1) . ":$col_formula_duracion" . ($fila_inicial + $i) . ")", $time_format);
	$ws->writeFormula($fila_inicial + $i, $col_duracion_cobrada, "=SUM($col_formula_duracion_cobrada" . ($fila_inicial + 1) . ":$col_formula_duracion_cobrada" . ($fila_inicial + $i) . ")", $time_format);
}
// No tiene sentido sumar los totales porque pueden estar en monedas distintas.

$wb->close();

exit;
