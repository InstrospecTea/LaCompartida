<?php

require_once dirname(__FILE__) . '/../conf.php';
$sesion = new Sesion(array('REP'));

for($x = 0; $x < $_POST['numero_agrupadores']; ++$x) {
	$agrupadores[] = $agrupador[$x];
}

$tipo_dato_comparado = $comparar ? $tipo_dato_comparado : null;
$limite = $limitar ? $limite : null;

########## VER GRAFICO ##########
if ($tipo_dato_comparado) {
	$titulo_reporte = __('Gráfico de') . ' ' . __($tipo_dato) . ' vs. ' . __($tipo_dato_comparado) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
} else {
	$titulo_reporte = __('Gráfico de') . ' ' . __($tipo_dato) . ' ' . __('en vista por') . ' ' . __($agrupadores[0]);
}

$datos_grafico = '';
$total = 0;


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

/* Se crea el reporte según el Input del usuario */
$reporte = new ReporteCriteria($sesion);
$dato = $tipo_dato;
$filtros = compact('clientes', 'usuarios', 'tipos_asunto', 'areas_asunto',
	'areas_usuario', 'categorias_usuario', 'encargados', 'estado_cobro',
	'fecha_ini', 'fecha_fin', 'campo_fecha', 'dato', 'vista', 'prop', 'id_moneda');
$reporte->setFiltros($filtros);
$reporte->Query();
$r = $reporte->toBars();
/** FIN PRINCIPAL * */

/* * REPORTE COMPARADO* */
if ($tipo_dato_comparado) {
	$reporte_c = new ReporteCriteria($sesion);
	$dato = $tipo_dato_comparado;
	$filtros = compact('clientes', 'usuarios', 'tipos_asunto', 'areas_asunto',
		'areas_usuario', 'categorias_usuario', 'encargados', 'estado_cobro',
		'fecha_ini', 'fecha_fin', 'campo_fecha', 'dato', 'vista', 'prop', 'id_moneda');
	$reporte_c->setFiltros($filtros);
	$reporte_c->setTipoDato($tipo_dato_comparado);

	$reporte_c->Query();

	$r_c = $reporte_c->toBars();
	$r = $reporte_c->fixBar($r, $r_c);
	$r_c = $reporte_c->fixBar($r_c, $r);

	if ($orden_barras_max2min) {
		arsort($r);
	}

	$datos_grafico .= "&compara=1&unidadC=" . $tipo_dato_comparado;

	$valores_comparados = array();
	foreach ($r as $id => $fila) {
		if (is_array($fila)) {
			$valores_comparados[] = str_replace(',', '.', $r_c[$id]['valor']);
		}
	}
} else {
	if ($orden_barras_max2min) {
		arsort($r);
	}
}
/* * FIN COMPARADO* */

$existen_datos = false;
$valores = array();
$labels = array();
foreach ($r as $id => $fila) {
	if (is_array($fila)) {
		$labels[] = substr($fila['label'], 0, 12);
		$valores[] = str_replace(',', '.', $fila['valor']);
		$existen_datos = true;
	}
}

if ($limite) {
	$lim_labels = array();
	$lim_valores = array();
	if ($tipo_dato_comparado) {
		$lim_valores_comparados = array();
	}

	if ($limite > 0 && $limite < 1000) {
		for ($i = 0; ($i < $limite && $i < sizeof($labels)); $i++) {
			$lim_labels[] = $labels[$i];
			$lim_valores[] = $valores[$i];
			if ($tipo_dato_comparado) {
				$lim_valores_comparados[] = $valores_comparados[$i];
			}
		}
	}

	if ($agrupar) {
		if ($limite < sizeof($labels)) {
			$valor_otros = 0;
			for ($i = $limite; $i < sizeof($labels); $i++) {
				$valor_otros += $valores[$i];
			}
			$lim_labels[] = 'Otros';
			$lim_valores[] = str_replace(',', '.', $valor_otros);
		}
	}

	$valores = $lim_valores;
	$labels = $lim_labels;
	if ($tipo_dato_comparado) {
		$valores_comparados = $lim_valores_comparados;
	}
}

foreach ($labels as $label) {
	$datos_grafico .= "&n[]=" . ($label);
}

$datos_grafico .= "&t=" . implode(',', $valores);

if ($tipo_dato_comparado) {
	$datos_grafico .= "&c=" . implode(',', $valores_comparados);
}

$html_info = '<style type="text/css">
		@media print {
		div#print_link {
		display: none;
		}
		}
		</style>';

switch ($tipo_grafico) {
	case 'barras': {
		$datos_grafico .= $r['promedio'];
		graficoBarras($titulo_reporte, $labels, $valores, $valores_comparados, $tipo_dato, $tipo_dato_comparado, $id_moneda);
		break;
	}
	case 'dispersion': {
		graficoLinea($titulo_reporte, $labels, $valores, $valores_comparados, $tipo_dato, $tipo_dato_comparado, $id_moneda);
		break;
	}
	case 'circular': {
		graficoTarta($titulo_reporte, $labels, $valores, $tipo_dato);
		break;
	}
}

function graficoBarras($titulo, $labels, $datos, $datos_comparados, $tipo_dato, $tipo_dato_comparado, $id_moneda) {
	$grafico = new TTB\Graficos\GraficoBarra();
	$dataset = new TTB\Graficos\GraficoDataset();

	$dataset->addLabel(__($tipo_dato))
		->addData($datos);

	$grafico->addDataSets($dataset)
		->addNameChart($titulo)
		->addLabels($labels);

	if ($datos_comparados) {
		$dataset_comparado = new TTB\Graficos\GraficoDataset();

		$dataset_comparado->addLabel(__($tipo_dato_comparado))
			->addFillColor(39, 174, 96, 0.5)
			->addStrokeColor(39, 174, 96, 0.8)
			->addHighlightFill(39, 174, 96, 0.75)
			->addHighlightStroke(39, 174, 96, 1)
		  ->addData($datos_comparados);

		$grafico->addDataSets($dataset_comparado);
	}

	echo $grafico->getJson();
}

function graficoTarta($titulo, $labels, $datos, $tipo_dato) {
	$grafico = new TTB\Graficos\GraficoTarta();

	foreach ($datos as $key => $value) {
		$data_grafico = new TTB\Graficos\GraficoData();

		$data_grafico->addLabel($labels[$key], true)
		->addValue($value);

		$grafico->addData($data_grafico);
	}

	$titulo = mb_detect_encoding($titulo, 'UTF-8', true) ? $titulo : utf8_encode($titulo);

	echo json_encode(array('json' => json_decode($grafico->getJson()), 'chart_name' => $titulo));
}

function graficoLinea($titulo, $labels, $datos, $datos_comparados, $tipo_dato, $tipo_dato_comparado, $id_moneda) {
	$grafico = new TTB\Graficos\GraficoLinea();
	$datasetLinea = new TTB\Graficos\GraficoDatasetLine();
	$datasetLineaComparado = new TTB\Graficos\GraficoDatasetLine();

	$datasetLinea->addLabel(__($tipo_dato))
		->addData($datos);

	$datasetLineaComparado->addLabel(__($tipo_dato_comparado))
		->addFillColor(151, 187, 205, 0.2)
		->addStrokeColor(151, 187, 205, 1)
		->addPointColor(151, 187, 205, 1)
		->addPointHighlightStroke(151, 187, 205, 1)
		->addData($datos_comparados);

	$grafico->addDataSets($datasetLinea)
		->addDataSets($datasetLineaComparado)
		->addNameChart($titulo)
		->addLabels($labels);

	echo $grafico->getJson();
}
