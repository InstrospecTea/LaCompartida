<?php

require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../../conf.php';

$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);

$agrupadores = explode('-', $vista);

if ($filtros_check) {
	$clientes = null;
	$usuarios = null;

	if ($check_clientes) {
		$clientes = $clientesF;
	}
	if ($check_profesionales) {
		$usuarios = $usuariosF;
	}
	if ($check_area_prof) {
		$areas_usuario = $areas;
	}
	if ($check_cat_prof) {
		$categorias_usuario = $categorias;
	}
	if (!$check_area_asunto) {
		$areas_asunto = null;
	}
	if (!$check_tipo_asunto) {
		$tipos_asunto = null;
	}
	if (!$check_estado_cobro) {
		$estado_cobro = null;
	}
	if (!$check_encargados) {
		$encargados = null;
	}
}

$titulo_reporte = __('Resumen - ') . ' ' . __($tipo_dato) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);

$dato = $tipo_dato;
$reporte = new Reporte($sesion);
$filtros = compact('clientes', 'usuarios', 'tipos_asunto', 'areas_asunto',
	'areas_usuario', 'categorias_usuario', 'encargados', 'estado_cobro',
	'fecha_ini', 'fecha_fin', 'campo_fecha', 'dato', 'vista', 'prop', 'id_moneda');

$reporte->setFiltros($filtros);
$reporte->Query();

$r = $reporte->toCross();

$wb = new Spreadsheet_Excel_Writer();

$wb->send('Planilla Horas por Cliente.xls');

/* FORMATOS */
$wb->setCustomColor(35, 220, 255, 220);
$wb->setCustomColor(36, 255, 255, 220);
$encabezado = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'left',
		'Bold' => '1',
		'FgColor' => '35',
		'underline' => 1,
		'Color' => 'black'));
$titulo = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'left',
		'Bold' => '1',
		'underline' => 1,
		'Color' => 'black'));

$txt_opcion = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'left',
		'Border' => 1,
		'Color' => 'black'));
$txt_opcion->setTextWrap();

$txt_valor = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'black'));
$txt_valor->setTextWrap();
$txt_rojo = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'red'));
$txt_rojo->setTextWrap();

$txt_derecha = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'black'));
$txt_derecha->setTextWrap();

$fecha = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'center',
		'Border' => 1,
		'Color' => 'black'));
$fecha->setTextWrap();

$numeros = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'black'));
$numeros->setNumFormat('0');

$horas_minutos = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'black'));
$horas_minutos->setNumFormat('[hh]:mm');

$titulo_filas = & $wb->addFormat(array('Size' => 12,
		'Align' => 'center',
		'Bold' => '1',
		'FgColor' => '35',
		'Border' => 1,
		'Locked' => 1,
		'Color' => 'black'));

$formato_moneda = & $wb->addFormat(array('Size' => 11,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'black'));
$formato_moneda->setNumFormat('#,##0.00');

/* TITULOS */
$ws1 = & $wb->addWorksheet(__('Reportes'));
$ws1->setInputEncoding('utf-8');
$ws1->fitToPages(1, 0);
$ws1->setZoom(75);

$cantidad_columnas = sizeof($agrupadores) * ( $comparar ? 3 : 2 );

for ($i = 0; $i < $cantidad_columnas; $i++) {
	$ws1->setColumn($fila_inicial + $i, $fila_inicial + $i, 25.00);
}

$fila = 1;

$ws1->write($fila, 1, $titulo_reporte, $titulo);
$ws1->write($fila, 2, '');
$ws1->write($fila, 3, '');
$ws1->mergeCells($fila, 1, $fila, 3);

++$fila;
$ws1->write($fila, 0, __('PERIODO RESUMEN') . ':', $titulo);

$ws1->write($fila, 1, $fecha_ini . ' ' . __('al') . ' ' . $fecha_fin, $titulo);
$ws1->write($fila, 2, '');
$ws1->mergeCells($fila, 1, $fila, 2);

++$fila;

$hoy = date('d-m-Y');

$ws1->write($fila, 0, __('FECHA REPORTE'), $titulo);
$ws1->write($fila, 1, $hoy, $titulo);
$ws1->write($fila, 2, '');
$ws1->mergeCells($fila, 1, $fila, 2);



$columna = 0;

$fila += 3;

function dato($fila, $columna, $valor) {
	global $ws1;
	global $numeros;
	global $horas_minutos;
	global $tipo_dato;
	global $sesion;
	global $tipo_dato_comparado;
	global $txt_rojo;
	if ($valor === '99999!*') {
		$ws1->write($fila, $columna, '99999!*', $txt_rojo);
		$ws1->writeNote($fila, $columna, __('Valor Indeterminado: denominador de fórmula es 0.'));
	} else {
		if (Conf::GetConf($sesion, 'MostrarSoloMinutos') && (strpos($tipo_dato, 'oras_') || strpos($tipo_dato_comparado, 'oras_')))
			$ws1->writeNumber($fila, $columna, Reporte::FormatoValor($sesion, $valor, $tipo_dato, 'excel'), $horas_minutos);
		else
			$ws1->writeNumber($fila, $columna, $valor, $numeros);
	}
}

function texto($fila, $columna, $valor) {
	global $ws1;
	global $txt_opcion;
	if ($valor == __('Indefinido')) {
		$ws1->write($fila, $columna, $valor, $txt_opcion);
		$ws1->writeNote($fila, $columna, __('Agrupador no existe, o no está definido para estos datos.'));
	} else
		$ws1->write($fila, $columna, $valor, $txt_opcion);
}

if (is_array($r['labels'])) {

	//LABELS
	$fil = $fila;
	foreach ($r['labels'] as $id => $nombre) {
		texto($fil, $col, $nombre['nombre']);
		$fil++;
	}
	$ws1->write($fil, $columna, 'TOTAL', $txt_derecha);

	//ENCABEZADOS
	$col = $columna + 1;
	foreach ($r['labels_col'] as $id_col => $nombre_col) {
		texto($fila - 1, $col, $nombre_col['nombre']);
		$col++;
	}
	$ws1->write($fila - 1, $col, 'TOTAL', $txt_opcion);

	//CELDAS
	$fil = $fila;
	foreach ($r['labels'] as $id => $nombre) {
		$col = $columna + 1;
		foreach ($r['labels_col'] as $id_col => $nombre_col) {
			if (isset($r['celdas'][$id][$id_col]['valor'])) {
				dato($fil, $col, $r['celdas'][$id][$id_col]['valor']);
			} else {
				$ws1->write($fil, $col, '', $txt_opcion);
			}
			$col++;
		}
		//TOTAL_COLUMNA:
		dato($fil, $col, $r['labels'][$id]['total']);
		$fil++;
	}

	//TOTAL_FILAS
	$col = $columna + 1;
	foreach ($r['labels_col'] as $id_col => $nombre) {
		dato($fil, $col, $r['labels_col'][$id_col]['total']);
		$col++;
	}
	dato($fil, $col, $r['total']);
}
$wb->close();
