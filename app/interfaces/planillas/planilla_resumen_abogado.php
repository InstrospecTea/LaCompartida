<?php

require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../app/classes/Reporte.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);
set_time_limit(0);
$moneda_base = Utiles::MonedaBase($sesion);
#ARMANDO XLS
$wb = new Spreadsheet_Excel_Writer();

$wb->send("Planilla_resumen_profesionales.xls");


if ($moneda_base['cifras_decimales'] == 0)
	$string_decimales = "";
else if ($moneda_base['cifras_decimales'] == 1)
	$string_decimales = ".0";
else if ($moneda_base['cifras_decimales'] == 2)
	$string_decimales = ".00";

define('CTEMONEDA', $id_moneda);
$arraymoneda = array();



$wb->setCustomColor(35, 100, 86, 171);
$formato_morado = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'left',
		'Bold' => '1',
		'BgColor' => '35',
		'fgColor' => '35',
		'Color' => 'black'));
$formato_morado_giant = & $wb->addFormat(array('Size' => 14,
		'VAlign' => 'top',
		'Align' => 'left',
		'Bold' => '1',
		'BgColor' => '35',
		'fgColor' => '35',
		'Color' => 'black'));
$formato_duracion_morado = $formato_morado;
if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'MostrarSoloMinutos') ) || ( method_exists('Conf', 'MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )))
	$formato_duracion_morado->setNumFormat("[hh]:mm");
$formato_morado_numero = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'left',
		'Bold' => '1',
		'BgColor' => '35',
		'fgColor' => '35',
		'Color' => 'black'));
$formato_morado_numero->setNumFormat("#,##0$string_decimales");

$wb->setCustomColor(36, 255, 255, 220);
$formato_titulo_rotado = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'left',
		'Border' => 1,
		'fgColor' => '36',
		'Color' => 'black'));
$formato_titulo_rotado->setTextRotation(270);
$formato_titulo = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'left',
		'Border' => 1,
		'fgColor' => '36',
		'Color' => 'black'));
$formato_titulo->setTextWrap();
$wb->setCustomColor(38, 0, 0, 128);
$formato_totales = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'left',
		'Border' => 1,
		'fgColor' => '38',
		'Color' => 'white'));
$formato_duracion_totales = $formato_totales;
if (( ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'MostrarSoloMinutos') ) || ( method_exists('Conf', 'MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )))
	$formato_duracion_totales->setNumFormat("[hh]:mm");
$formato_totales_cobro = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'left',
		'Border' => 1,
		'fgColor' => '38',
		'Color' => 'white'));
$formato_totales_cobro->setNumFormat("#,##0$string_decimales");
$wb->setCustomColor(40, 204, 204, 255);
$formato_periodo = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'center',
		'fgColor' => '40',
		'Border' => 1,
		'Bold' => 1,
		'Color' => 'black'));
$wb->setCustomColor(41, 192, 192, 192);
$formato_cliente_asunto = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'left',
		'Border' => 1,
		'Color' => 'black'));
$formato_cliente_asunto2 = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'left',
		'fgColor' => '41',
		'Border' => 1,
		'Color' => 'black'));
$formato_cliente_asunto->setTextWrap();
$formato_cliente_asunto2->setTextWrap();
$formato_moneda = & $wb->addFormat(array('Size' => 11,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'RightColor' => 'blue',
		'Color' => 'black'));
$formato_moneda->setNumFormat("#,##0$string_decimales");
$wb->setCustomColor(39, 102, 102, 153);
$formato_moneda2 = & $wb->addFormat(array('Size' => 11,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'RightColor' => 'blue',
		#'TopColor' => '41',
		#'LeftColor' => '41',
		#'fgColor' => '39',
		'fgColor' => '41',
		'Color' => 'black'));
$formato_moneda2->setNumFormat("#,##0$string_decimales");
$wb->setCustomColor(35, 220, 255, 220);
$titulo_filas = & $wb->addFormat(array('Size' => 12,
		'Align' => 'center',
		'Bold' => '1',
		'FgColor' => '35',
		'Border' => 1,
		'Locked' => 1,
		'Color' => 'black'));

#$ws1->setColumn( 1, 9, 19.00);
$formatos_moneda_totales = array();
$formatos_moneda_morado = array();
$formatos_moneda_asunto = array();
$formatos_moneda_asunto2 = array();
$query = 'SELECT id_moneda, simbolo, cifras_decimales, glosa_moneda
				FROM prm_moneda
				ORDER BY id_moneda';
$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
while (list($idmoneda, $simbolo_moneda, $cifras_decimales, $glosa_moneda) = mysql_fetch_array($resp)) {
	$arraymoneda[$idmoneda] = array($simbolo_moneda, $cifras_decimales, $glosa_moneda);
	if ($cifras_decimales > 0) {
		$decimales = '.';
		while ($cifras_decimales-- > 0)
			$decimales .= '0';
	} else {
		$decimales = '';
	}

	$formatos_moneda_totales[$idmoneda] = & $wb->addFormat(array('Size' => 11,
			'VAlign' => 'top',
			'Align' => 'right',
			'Border' => '1',
			'Bold' => '1',
			'Border' => '1',
			'fgColor' => '38', 'Color' => 'white',
			'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
	$formatos_moneda_morado[$idmoneda] = & $wb->addFormat(array('Size' => 12,
			'VAlign' => 'top',
			'Align' => 'left',
			'Bold' => '1',
			'fgColor' => '35', 'Color' => 'black',
			'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));




	$formatos_moneda_asunto[$idmoneda] = & $wb->addFormat(array('Size' => 11,
			'VAlign' => 'top',
			'Align' => 'right',
			'Border' => '1',
			'Color' => 'black',
			'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));

	$formatos_moneda_asunto2[$idmoneda] = & $wb->addFormat(array('Size' => 11,
			'VAlign' => 'top',
			'Align' => 'right',
			'Border' => '1',
			'fgColor' => '41',
			'Color' => 'black',
			'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
}

$hoy = date("d-m-Y");
$filas += 1;
if (is_array($usuarios))
	$lista_usuarios = join(',', $usuarios);
else
	die(__('Usted no ha seleccionado a ningún usuario para generar el informe'));

if ($lista_usuarios == "")
	$lista_usuarios = 'NULL';

//Inicializo el arreglo de resultados
$tipo_dato = array('horas_trabajadas', 'horas_cobradas', 'horas_por_cobrar', 'horas_castigadas', 'horas_no_cobrables', 'valor_cobrado_no_estandar', 'valor_cobrado');
foreach ($usuarios as $usuario) {
	foreach ($tipo_dato as $td) {
		$reporte = new Reporte($sesion);
		if (is_array($forma_cobro))
			foreach ($forma_cobro as $fc)
				$reporte->addFiltro('cobro', 'forma_cobro', $fc);
		$reporte->addFiltro('usuario', 'id_usuario', $usuario);
		$reporte->setTipoDato($td);
		$reporte->id_moneda = CTEMONEDA;
		$reporte->ignorar_cobros_sin_horas = true;
		$reporte->setVista('glosa_cliente_asunto-mes_reporte');
		$reporte->addRangoFecha(Utiles::fecha2sql($fecha_ini), Utiles::fecha2sql($fecha_fin));
		$reporte->Query();
		$resultado[$usuario][$td] = $reporte->toCross();
	}
}

$filas_nombres = array();
$query_nombres = "SELECT id_usuario,CONCAT_WS(', ',TRIM(apellido1),TRIM(nombre)) as corto, CONCAT_WS(', ',TRIM(apellido1),TRIM(apellido2),TRIM(nombre)) as largo FROM usuario";
$resp = mysql_query($query_nombres, $sesion->dbh) or Utiles::errorSQL($query_nombres, __FILE__, __LINE__, $sesion->dbh);
while ($row = mysql_fetch_array($resp)) {
	if (!isset($filas_nombres[$row['corto']]))
		$filas_nombres[$row['corto']] = array();
	$filas_nombres[$row['corto']][] = $row;
}
$nombres_paginas = array();
foreach ($filas_nombres as $filas) {
	if (count($filas) == 1) {
		$nombres_paginas[$filas[0]['id_usuario']] = $filas[0]['corto'];
	} else {
		foreach ($filas as $fila) {
			$nombres_paginas[$fila['id_usuario']] = $fila['largo'];
		}
	}
}

//Recorro el arreglo ingresando los datos
foreach ($resultado as $u => $tipo_dato) {
	//Veo el profesional
	$profesional = $nombres_paginas[$u];
	$ws1 = & $wb->addWorksheet($profesional);
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1, 1);
	$ws1->setZoom(75);
	$ws1->setLandscape();
	$ws1->setMarginRight(0.25);
	$ws1->setMarginLeft(0.25);
	$ws1->setLandscape();
	$fila = 19;

	Print_Prof($ws1, $tipo_dato);
}

function extender($ws1, $fila_titulos, $col, $ancho, $formato_periodo) {
	for ($i = 1; $i < $ancho; $i++)
		$ws1->write($fila_titulos, $col + $i, '', $formato_periodo); //Esto es solo para que quede con formato
	$ws1->mergeCells($fila_titulos, $col, $fila_titulos, $col + $i - 1);
}

function n($num, $decimales = 2) {
	if ($num)
		return number_format($num, $decimales, '.', '');
	else
		return 0;
}

//Imprime la pagina de un profesional, dado el arreglo de resultados para cada tipo de dato td
function Print_Prof(& $ws1, $td) {
	global $formato_morado;
	global $formato_duracion_morado;
	global $formato_morado_numero;
	global $profesional;
	global $fecha_ini;
	global $fecha_fin;
	global $formato_titulo_rotado;
	global $formato_titulo;
	global $formato_periodo;
	global $formato_cliente_asunto;
	global $formato_cliente_asunto2;
	global $formato_totales;
	global $formato_duracion_totales;
	global $formato_totales_cobro;
	global $fila, $hoy;
	global $sesion;
	global $arraymoneda;
	global $id_moneda;
	global $formato_morado_giant;

	global $formatos_moneda_totales;
	global $formatos_moneda_morado;
	global $formatos_moneda_asunto;
	global $formatos_moneda_asunto2;
	$fila_titulos = 14;
	$time_periodo = strtotime($fecha_ini);


	$ws1->write($filas, 1, __('REPORTE RENDIMIENTO PROFESIONALES'), $formato_morado_giant);
	$ws1->mergeCells($filas, 1, $filas, 2 + 4);

	$ws1->write(++$filas, 1, strtoupper(__('Profesional')), $formato_morado);
	$ws1->write($filas, 2, $profesional, $formato_morado);
	$ws1->mergeCells($filas, 2, $filas, 2 + 4);

	$ws1->write(++$filas, 1, __('FECHA REPORTE'), $formato_morado);
	$ws1->write($filas, 2, "$hoy", $formato_morado);
	$ws1->mergeCells($filas, 2, $filas, 2 + 4);

	$ws1->write(++$filas, 1, __('PERIODO'), $formato_morado);
	$ws1->write($filas, 2, Utiles::sql2date($fecha_ini) . ' a ' . Utiles::sql2date($fecha_fin), $formato_morado);
	$ws1->mergeCells($filas, 2, $filas, 2 + 4);

	$ws1->setColumn(1, 2, 28);
	//Aqui normalmente debería rellenar, pero como tengo el resultado 'Horas Trabajadas', este tiene siempre el universo de resultados.
	//(Si un mes aparece en alguno, aparecerá en Horas Trabajadas).
	$r = $td['horas_trabajadas'];
	if (is_array($r['labels'])) {
		$ws1->freezePanes(array(0, 3));

		$ws1->write($fila_titulos, 1, __('Horas Trabajadas'), $formato_titulo);
		$ws1->write($fila_titulos, 2, '', $formato_titulo);
		$ws1->mergeCells($fila_titulos, 1, $fila_titulos, 2);

		$ws1->write($fila_titulos + 1, 1, __('Hrs. Liquidadas'), $formato_titulo);
		$ws1->write($fila_titulos + 1, 2, '', $formato_titulo);
		$ws1->mergeCells($fila_titulos + 1, 1, $fila_titulos + 1, 2);

		$ws1->write($fila_titulos + 2, 1, __('Hrs. por Liquidar'), $formato_titulo);
		$ws1->write($fila_titulos + 2, 2, '', $formato_titulo);
		$ws1->mergeCells($fila_titulos + 2, 1, $fila_titulos + 2, 2);

		$ws1->write($fila_titulos + 3, 1, __('Hrs. Castigadas'), $formato_titulo);
		$ws1->write($fila_titulos + 3, 2, '', $formato_titulo);
		$ws1->mergeCells($fila_titulos + 3, 1, $fila_titulos + 3, 2);

		$ws1->write($fila_titulos + 4, 1, __('Hrs. no Cobrables'), $formato_titulo);
		$ws1->write($fila_titulos + 4, 2, '', $formato_titulo);
		$ws1->mergeCells($fila_titulos + 4, 1, $fila_titulos + 4, 2);

		$ws1->write($fila_titulos + 5, 1, __('monto_facturado'), $formato_titulo);
		$ws1->write($fila_titulos + 5, 2, '', $formato_titulo);
		$ws1->mergeCells($fila_titulos + 5, 1, $fila_titulos + 5, 2);

		$ws1->write($fila_titulos + 6, 1, __('monto_facturado').__('(E)'), $formato_titulo);
		$ws1->write($fila_titulos + 6, 2, '', $formato_titulo);
		$ws1->mergeCells($fila_titulos + 6, 1, $fila_titulos + 6, 2);

		$ws1->write($fila_titulos + 7, 1, __('Cliente - Asunto'), $formato_titulo);
		$ws1->write($fila_titulos + 7, 2, __('Encargado Comercial'), $formato_titulo);




		$fila_base = 22;
		foreach ($r['labels'] as $id_lab => $label) {
			if ($fila_base % 2) {
				$formato = $formato_cliente_asunto2;
				$formato_moneda_fila = $formatos_moneda_asunto2[CTEMONEDA];
			} else {
				$formato = $formato_cliente_asunto;
				$formato_moneda_fila = $formatos_moneda_asunto[CTEMONEDA];
			}
			$query_encargado = "SELECT usuario.username FROM asunto JOIN contrato ON asunto.id_contrato = contrato.id_contrato JOIN usuario ON usuario.id_usuario = contrato.id_usuario_responsable WHERE asunto.codigo_asunto = '" . $id_lab . "'";
			$resp = mysql_query($query_encargado, $sesion->dbh) or Utiles::errorSQL($query_encargado, __FILE__, __LINE__, $sesion->dbh);
			$row = mysql_fetch_assoc($resp);

			$ws1->write($fila_base, 1, $label['nombre'], $formato);
			$ws1->write($fila_base, 2, $row['username'], $formato);

			$fila_base++;
		}
		$ws1->write($fila_base, 1, __('TOTAL'), $formato_totales);
		$ws1->write($fila_base, 2, '', $formato_totales);
		$ws1->mergeCells($fila_base, 1, $fila_base, 2);

		$col = 3;
		//Encabezados
		$arr_col = array();
		foreach ($r['labels_col'] as $id_col => $column) {
			$mes_anyo = split('-', $column['nombre']);
			$fecha = mktime(0, 0, 0, $mes_anyo[0], 1, $mes_anyo[1]);
			$arr_col[$fecha] = array('id' => $id_col, 'fecha' => $fecha);
		}
		ksort($arr_col);

		foreach ($arr_col as $columna) {
			$id_col = $columna['id'];
			$fecha = $columna['fecha'];

			$ws1->write($fila_titulos - 1, $col, date('M Y', $fecha), $formato_periodo); //Se imprime el titulo del periodo
			extender($ws1, $fila_titulos - 1, $col, 6, $formato_periodo);

			$ws1->write($fila_titulos, $col, n($td['horas_trabajadas']['labels_col'][$id_col]['total']), $formato_duracion_totales);
			extender($ws1, $fila_titulos, $col, 6, $formato_periodo);

			$ws1->write($fila_titulos + 1, $col, n($td['horas_cobradas']['labels_col'][$id_col]['total']), $formato_duracion_totales);
			extender($ws1, $fila_titulos + 1, $col, 6, $formato_periodo);

			$ws1->write($fila_titulos + 2, $col, n($td['horas_por_cobrar']['labels_col'][$id_col]['total']), $formato_duracion_totales);
			extender($ws1, $fila_titulos + 2, $col, 6, $formato_periodo);

			$ws1->write($fila_titulos + 3, $col, n($td['horas_castigadas']['labels_col'][$id_col]['total']), $formato_duracion_totales);
			extender($ws1, $fila_titulos + 3, $col, 6, $formato_periodo);

			$ws1->write($fila_titulos + 4, $col, n($td['horas_no_cobrables']['labels_col'][$id_col]['total']), $formato_duracion_totales);
			extender($ws1, $fila_titulos + 4, $col, 6, $formato_periodo);

			$ws1->write($fila_titulos + 5, $col, n($td['valor_cobrado_no_estandar']['labels_col'][$id_col]['total']), $formatos_moneda_totales[CTEMONEDA]);
			extender($ws1, $fila_titulos + 5, $col, 6, $formato_periodo);

			$ws1->write($fila_titulos + 6, $col, n($td['valor_cobrado']['labels_col'][$id_col]['total']), $formatos_moneda_totales[CTEMONEDA]);
			extender($ws1, $fila_titulos + 6, $col, 6, $formato_periodo);


			#Títulos columnas verticales
			$ws1->write($fila_titulos + 7, $col, __('Hrs. Liquidadas'), $formato_titulo_rotado);
			$ws1->write($fila_titulos + 7, $col + 1, __('Hrs. por Liquidar'), $formato_titulo_rotado);
			$ws1->write($fila_titulos + 7, $col + 2, __('Hrs. Castigadas'), $formato_titulo_rotado);
			$ws1->write($fila_titulos + 7, $col + 3, __('Hrs. no Cobrables'), $formato_titulo_rotado);
			$ws1->write($fila_titulos + 7, $col + 4, __('Cobrado'), $formato_titulo_rotado);
			$ws1->write($fila_titulos + 7, $col + 5, __('Cobrado').__(' (E)'), $formato_titulo_rotado);
			$ws1->setColumn($col + 5, $col + 5, 15);


			#celdas
			$fila_inicio = 22;
			$fila_base = 22;
			foreach ($r['labels'] as $id_lab => $label) {
				if ($fila_base % 2) {
					$formato = $formato_cliente_asunto2;
					$formato_moneda_fila = $formatos_moneda_asunto2[CTEMONEDA];
				} else {
					$formato = $formato_cliente_asunto;
					$formato_moneda_fila = $formatos_moneda_asunto[CTEMONEDA];
				}
				$ws1->writeNumber($fila_base, $col, Reporte::FormatoValor($sesion, number_format($td['horas_cobradas']['celdas'][$id_lab][$id_col]['valor'], 2, '.', ''), "horas_", "excel"), $formato);
				$ws1->writeNumber($fila_base, $col + 1, Reporte::FormatoValor($sesion, number_format($td['horas_por_cobrar']['celdas'][$id_lab][$id_col]['valor'], 2, '.', ''), "horas_", "excel"), $formato);
				$ws1->writeNumber($fila_base, $col + 2, Reporte::FormatoValor($sesion, number_format($td['horas_castigadas']['celdas'][$id_lab][$id_col]['valor'], 2, '.', ''), "horas_", "excel"), $formato);
				$ws1->writeNumber($fila_base, $col + 3, Reporte::FormatoValor($sesion, number_format($td['horas_no_cobrables']['celdas'][$id_lab][$id_col]['valor'], 2, '.', ''), "horas_", "excel"), $formato);
				$ws1->writeNumber($fila_base, $col + 4, n($td['valor_cobrado_no_estandar']['celdas'][$id_lab][$id_col]['valor']), $formato_moneda_fila);
				$ws1->writeNumber($fila_base, $col + 5, n($td['valor_cobrado']['celdas'][$id_lab][$id_col]['valor']), $formato_moneda_fila);
				$fila_base++;
			}
			#totales

			$ws1->WriteFormula($fila_base, $col, '=SUM(' . excel_column($col) . "$fila_inicio:" . excel_column($col) . ($fila_base) . ')', $formato_duracion_totales);
			$ws1->WriteFormula($fila_base, $col + 1, '=SUM(' . excel_column($col + 1) . "$fila_inicio:" . excel_column($col + 1) . ($fila_base) . ')', $formato_duracion_totales);
			$ws1->WriteFormula($fila_base, $col + 2, '=SUM(' . excel_column($col + 2) . "$fila_inicio:" . excel_column($col + 2) . ($fila_base) . ')', $formato_duracion_totales);
			$ws1->WriteFormula($fila_base, $col + 3, '=SUM(' . excel_column($col + 3) . "$fila_inicio:" . excel_column($col + 3) . ($fila_base) . ')', $formato_duracion_totales);
			$ws1->WriteFormula($fila_base, $col + 4, '=SUM(' . excel_column($col + 4) . "$fila_inicio:" . excel_column($col + 4) . ($fila_base) . ')', $formatos_moneda_totales[CTEMONEDA]);
			$ws1->WriteFormula($fila_base, $col + 5, '=SUM(' . excel_column($col + 5) . "$fila_inicio:" . excel_column($col + 5) . ($fila_base) . ')', $formatos_moneda_totales[CTEMONEDA]);
			#FIN de una columna
			$col+=5;
		}

		$ws1->write(++$filas, 1, __('HORAS TRABAJADAS'), $formato_morado);
		$ws1->write($filas, 2, $td['horas_trabajadas']['total'], $formato_duracion_morado);
		$ws1->mergeCells($filas, 2, $filas, 2 + 4);

		$ws1->write(++$filas, 1, __('MONTO FACTURADO'), $formato_morado);
		//$ws1->write($filas, 2, $td['valor_cobrado']['total'], $formatos_moneda_morado[CTEMONEDA]);
		$ws1->write($filas, 2, '=SUM(D' . ($fila_titulos + 6) . ':FF' . ($fila_titulos + 6) . ')', $formatos_moneda_morado[CTEMONEDA]); // ojo, para la clase la fila titulos+6 es la 20, para el excel es la 19
		$ws1->mergeCells($filas, 2, $filas, 2 + 4);

		$ws1->write(++$filas, 1, __('MONTO FACTURADO').__(' (E)'), $formato_morado);
		//$ws1->write($filas, 2, $td['valor_cobrado']['total'], $formatos_moneda_morado[CTEMONEDA]);
		$ws1->write($filas, 2, '=SUM(D' . ($fila_titulos + 7) . ':FF' . ($fila_titulos + 7) . ')', $formatos_moneda_morado[CTEMONEDA]); // ojo, para la clase la fila titulos+6 es la 20, para el excel es la 19
		$ws1->mergeCells($filas, 2, $filas, 2 + 4);

		$ws1->write(++$filas, 1, __('HORAS LIQUIDADAS'), $formato_morado);
		$ws1->write($filas, 2, $td['horas_cobradas']['total'], $formato_duracion_morado);
		$ws1->mergeCells($filas, 2, $filas, 2 + 4);

		$ws1->write(++$filas, 1, __('HORAS POR LIQUIDAR'), $formato_morado);
		$ws1->write($filas, 2, $td['horas_por_cobrar']['total'], $formato_duracion_morado);
		$ws1->mergeCells($filas, 2, $filas, 2 + 4);

		$ws1->write(++$filas, 1, __('HORAS CASTIGADAS'), $formato_morado);
		$ws1->write($filas, 2, $td['horas_castigadas']['total'], $formato_duracion_morado);
		$ws1->mergeCells($filas, 2, $filas, 2 + 4);

		$ws1->write(++$filas, 1, __('HORAS NO COBRABLES'), $formato_morado);
		$ws1->write($filas, 2, $td['horas_no_cobrables']['total'], $formato_duracion_morado);
		$ws1->mergeCells($filas, 2, $filas, 2 + 4);

	} else {
		$filas+=3;
		$ws1->write($filas, 1, __('No se encontraron Trabajos.'), $formato_cliente_asunto);
		extender($ws1, $filas, 1, 3, $formato_cliente_asunto);
	}
}

$wb->close();

function excel_column($col_number) {
	if (($col_number < 0) || ($col_number > 701))
		die('Column must be between 0(A) and 701(ZZ)');
	if ($col_number < 26) {
		return(chr(ord('A') + $col_number));
	} else {
		$remainder = floor($col_number / 26) - 1;
		return(chr(ord('A') + $remainder) . excel_column($col_number % 26));
	}
}
