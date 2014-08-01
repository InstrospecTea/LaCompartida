<?php

require_once 'Spreadsheet/Excel/Writer.php';
require_once dirname(__FILE__) . '/../../conf.php';
$sesion = new Sesion(array('REP'));
$pagina = new Pagina($sesion);
$agrupadores = explode('-', $vista);

$datos = array();
$datos[] = $tipo_dato;
if ($comparar) {
	$datos[] = $tipo_dato_comparado;
}
$reporte = array();
$resultado = array();

if (!$filtros_check) {
	$fecha_ultimo_dia = date('t', mktime(0, 0, 0, $fecha_mes, 5, $fecha_anio));
	$fecha_m = '' . $fecha_mes;
} else {
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

// Cuando es tabla dinamica se repiten los resultados en vez de hacer merge de celdas
$extender_con_merge = $formato_tabla_dinamica == 0;

if ($comparar) {
	$titulo_reporte = __('Resumen - ') . ' ' . __($tipo_dato) . ' vs. ' . __($tipo_dato_comparado) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
} else {
	$titulo_reporte = __('Resumen - ') . ' ' . __($tipo_dato) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
}

foreach ($datos as $dato) {
	$reporte[$dato] = new Reporte($sesion);
	$filtros = compact('clientes', 'usuarios', 'tipos_asunto', 'areas_asunto',
		'areas_usuario', 'categorias_usuario', 'encargados', 'estado_cobro',
		'fecha_ini', 'fecha_fin', 'campo_fecha', 'dato', 'vista', 'prop', 'id_moneda');
	$reporte[$dato]->setFiltros($filtros);
	$reporte[$dato]->Query();
	$resultado[$dato] = $reporte[$dato]->toArray();
}
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
$txt_derecha_bold = & $wb->addFormat(array('Size' => 11,
		'Valign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Bold' => 1,
		'Color' => 'black'));

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
$numeros_bold = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Bold' => 1,
		'Color' => 'black'));
$numeros_bold->setNumFormat('0');

$horas_minutos = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Color' => 'black'));
$horas_minutos->setNumFormat('[h]:mm');

$horas_minutos_bold = & $wb->addFormat(array('Size' => 12,
		'VAlign' => 'top',
		'Align' => 'right',
		'Border' => 1,
		'Bold' => 1,
		'Color' => 'black'));
$horas_minutos_bold->setNumFormat('[hh]:mm');

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

$fila += 1;
$ws1->write($fila, 0, __('PERIODO RESUMEN') . ':', $titulo);

$ws1->write($fila, 1, $fecha_ini . ' ' . __('al') . ' ' . $fecha_fin, $titulo);
$ws1->write($fila, 2, '');
$ws1->mergeCells($fila, 1, $fila, 2);

$fila += 1;

$hoy = date('d-m-Y');

$ws1->write($fila, 0, __('FECHA REPORTE'), $titulo);
$ws1->write($fila, 1, $hoy, $titulo);
$ws1->write($fila, 2, '');
$ws1->mergeCells($fila, 1, $fila, 2);



$columna = 0;

$fila += 2;

if ($comparar) {
	$resultado[$tipo_dato] = Reporte::fixArray($resultado[$tipo_dato], $resultado[$tipo_dato_comparado]);
	$resultado[$tipo_dato_comparado] = Reporte::fixArray($resultado[$tipo_dato_comparado], $resultado[$tipo_dato]);
}

function extender($fila, $columna, $filas, $valor = '') {
	global $ws1;
	global $txt_opcion;
	global $extender_con_merge;

	$valor = $extender_con_merge ? '' : $valor;
	for ($f = 1; $f < $filas; $f++) {
		$ws1->write($fila + $f, $columna, $valor, $txt_opcion);
	}
	if ($extender_con_merge) {
		$ws1->mergeCells($fila, $columna, $fila + $filas - 1, $columna);
	}
}

function fila_col($fila, $col) {
	return Spreadsheet_Excel_Writer::rowcolToCell($fila, $col);
}

function total($fila, $columna, $valor) {
	global $ws1;
	global $numeros_bold;
	global $horas_minutos_bold;
	global $tipo_dato;
	global $sesion;

	if (Conf::GetConf($sesion, 'MostrarSoloMinutos') && (strpos($tipo_dato, 'oras_') || strpos($tipo_dato_comparado, 'oras_'))) {
		$ws1->write($fila, $columna, Reporte::FormatoValor($sesion, $valor, $tipo_dato, 'excel'), $horas_minutos_bold);
	} else {
		$ws1->write($fila, $columna, $valor, $numeros_bold);
	}
}

function dato($fila, $columna, $valor, $bold = false) {
	global $ws1;
	global $numeros;
	global $numeros_bold;
	global $horas_minutos;
	global $horas_minutos_bold;
	global $tipo_dato;
	global $sesion;
	global $tipo_dato_comparado;
	global $txt_rojo;

	$hm = $bold ? $horas_minutos_bold : $horas_minutos;
	$n = $bold ? $numeros_bold : $numeros;

	if ($valor === '99999!*') {
		$ws1->write($fila, $columna, '99999!*', $txt_rojo);
		$ws1->writeNote($fila, $columna, __('Valor Indeterminado: denominador de fórmula es 0.'));
	} else {
		if (Conf::GetConf($sesion, 'MostrarSoloMinutos') && (strpos($tipo_dato, 'oras_') || strpos($tipo_dato_comparado, 'oras_'))) {
			$ws1->writeNumber($fila, $columna, Reporte::FormatoValor($sesion, $valor, $tipo_dato, 'excel'), $hm);
		} else {
			$ws1->writeNumber($fila, $columna, $valor, $n);
		}
	}
}

function texto($fila, $columna, $valor) {
	global $ws1;
	global $txt_opcion;
	if ($valor == __('Indefinido')) {
		$ws1->write($fila, $columna, $valor, $txt_opcion);
		$ws1->writeNote($fila, $columna, __('Agrupador no existe, o no está definido para estos datos.'));
	} else {
		$ws1->write($fila, $columna, $valor, $txt_opcion);
	}
}

//ENCABEZADOS

$col = $columna;

for ($i = 0; $i < sizeof($agrupadores); $i++) {
	$indice_agrupador = 6 - sizeof($agrupadores) + $i;
	$ws1->write($fila, $col, __($reporte[$tipo_dato]->agrupador[$indice_agrupador]), $encabezado);
	$col++;
	$ws1->write($fila, $col, __($tipo_dato), $encabezado);
	$col++;
	if ($comparar) {
		$ws1->write($fila, $col, __($tipo_dato_comparado), $encabezado);
		$col++;
	}
}

$fila++;

/* 	CELDAS Y VALORES. Se indica Total después del agrupador principal (nombre de la vista) y el agrupador más pequeño.
  Usa el resultado del Reporte en forma de Planilla (toArray), recorriendo en las 4 profundidades.
 */
$fila_a = $fila;
foreach ($resultado[$tipo_dato] as $k_a => $a) {
	if (is_array($a)) {
		$col_a = $columna;

		if (sizeof($agrupadores) > 5) {
			texto($fila_a, $col_a, $k_a);
			extender($fila_a, $col_a, $a['filas'], $k_a);
			$col_a++;

			dato($fila_a, $col_a, $resultado[$tipo_dato][$k_a]['valor']);
			extender($fila_a, $col_a, $a['filas'], '');
			$col_a++;

			if ($comparar) {
				dato($fila_a, $col_a, $resultado[$tipo_dato_comparado][$k_a]['valor']);
				extender($fila_a, $col_a, $a['filas'], '');
				$col_a++;
			}
		}

		$fila_b = $fila_a;
		foreach ($a as $k_b => $b) {
			if (is_array($b)) {
				$col_b = $col_a;

				if (sizeof($agrupadores) > 4) {
					texto($fila_b, $col_b, $k_b);
					extender($fila_b, $col_b, $b['filas'], $k_b);
					$col_b++;

					dato($fila_b, $col_b, $resultado[$tipo_dato][$k_a][$k_b]['valor']);
					extender($fila_b, $col_b, $b['filas'], '');
					$col_b++;

					if ($comparar) {
						dato($fila_b, $col_b, $resultado[$tipo_dato_comparado][$k_a][$k_b]['valor']);
						extender($fila_b, $col_b, $b['filas'], '');
						$col_b++;
					}
				}

				$fila_c = $fila_b;
				foreach ($b as $k_c => $c) {
					if (is_array($c)) {
						$col_c = $col_b;

						if (sizeof($agrupadores) > 3) {
							texto($fila_c, $col_c, $k_c);
							extender($fila_c, $col_c, $c['filas'], $k_c);
							$col_c++;

							dato($fila_c, $col_c, $resultado[$tipo_dato][$k_a][$k_b][$k_c]['valor']);
							extender($fila_c, $col_c, $c['filas'], '');
							$col_c++;

							if ($comparar) {
								dato($fila_c, $col_c, $resultado[$tipo_dato_comparado][$k_a][$k_b][$k_c]['valor']);
								extender($fila_c, $col_c, $c['filas'], '');
								$col_c++;
							}
						}

						$fila_d = $fila_c;
						foreach ($c as $k_d => $d) {
							if (is_array($d)) {
								$col_d = $col_c;
								if (sizeof($agrupadores) > 2) {
									texto($fila_d, $col_d, $k_d);
									extender($fila_d, $col_d, $d['filas'], $k_d);
									$col_d++;

									dato($fila_d, $col_d, $resultado[$tipo_dato][$k_a][$k_b][$k_c][$k_d]['valor']);
									extender($fila_d, $col_d, $d['filas'], '');
									$col_d++;

									if ($comparar) {
										dato($fila_d, $col_d, $resultado[$tipo_dato_comparado][$k_a][$k_b][$k_c][$k_d]['valor']);
										extender($fila_d, $col_d, $d['filas'], '');
										$col_d++;
									}
								}

								$fila_e = $fila_d;
								foreach ($d as $k_e => $e) {
									if (is_array($e)) {
										$col_e = $col_d;
										if (sizeof($agrupadores) > 1) {
											texto($fila_e, $col_e, $k_e);
											extender($fila_e, $col_e, $e['filas'], $k_e);
											$col_e++;

											dato($fila_e, $col_e, $resultado[$tipo_dato][$k_a][$k_b][$k_c][$k_d][$k_e]['valor']);
											extender($fila_e, $col_e, $e['filas'], '');
											$col_e++;

											if ($comparar) {
												dato($fila_e, $col_e, $resultado[$tipo_dato_comparado][$k_a][$k_b][$k_c][$k_d][$k_e]['valor']);
												extender($fila_e, $col_e, $e['filas'], '');
												$col_e++;
											}
										}

										$fila_f = $fila_e;
										foreach ($e as $k_f => $f) {
											if (is_array($f)) {
												$col_f = $col_e;

												texto($fila_f, $col_f, $k_f);
												$col_f++;

												dato($fila_f, $col_f, $resultado[$tipo_dato][$k_a][$k_b][$k_c][$k_d][$k_e][$k_f]['valor']);
												$col_f++;

												if ($comparar) {
													dato($fila_f, $col_f, $resultado[$tipo_dato_comparado][$k_a][$k_b][$k_c][$k_d][$k_e][$k_f]['valor']);
												}
												$fila_f++;
											}
										}
										$fila_e += $e['filas'];
									}
								}
								$fila_d += $d['filas'];
							}
						}
						$fila_c += $c['filas'];
					}
				}
				$fila_b += $b['filas'];
			}
		}
		$fila_a += $a['filas'];
	}
}
$col_c = $cantidad_columnas - ($comparar ? 3 : 2);
//TOTALES
$ws1->write($fila_a, $columna + $col_c, 'TOTAL', $txt_derecha_bold);

total($fila_a, $col_c + 1, '=SUM(' . fila_col($fila, $col_c + 1) . ':' . fila_col($fila_a - 1, $col_c + 1) . ')');

if ($comparar) {
	total($fila_a, $col_c + 2, '=SUM(' . fila_col($fila, $col_c + 2) . ':' . fila_col($fila_a - 1, $col_c + 2) . ')');
}

$wb->close();
