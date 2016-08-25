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
	$grafico = new TTB\Graficos\Grafico();
	$dataset = new TTB\Graficos\Dataset();

	$yAxes[] = [
		'type' => 'linear',
		'display' => true,
		'position' => 'left',
		'id' => 'y-axis-1',
		'gridLines' => [
			'display' => false
		],
		'labels' => [
			'show' => true
		],
		'ticks' => [
			'beginAtZero' => true
		]
	];

	$dataset->setType('bar')
		->setLabel(__($tipo_dato))
		->setYAxisID('y-axis-1')
		->setData($datos);

	$grafico->setNameChart($titulo)
		->addDataset($dataset)
		->addLabels($labels);

	if ($datos_comparados) {
		if (strcmp(Reporte::sTipoDato($tipo_dato), Reporte::sTipoDato($tipo_dato_comparado)) !== 0) {
			$option_display = true;
			$dataset_comparado = new TTB\Graficos\DatasetLine();
			$dataset_comparado->setType('line')
			->setYAxisID('y-axis-2')
			->setLabel(__($tipo_dato_comparado))
			->setBackgroundColor(39, 174, 96, 0.5)
			->setBorderColor(39, 174, 96, 0.8)
			->setPointHoverBackgroundColor(39, 174, 96, 0.75)
			->setPointHoverBorderColor(39, 174, 96, 1)
		  ->setData($datos_comparados);
		} else {
			$option_display = false;
			$dataset_comparado = new TTB\Graficos\Dataset();
			$dataset_comparado->setType('bar')
			->setYAxisID('y-axis-2')
			->setLabel(__($tipo_dato_comparado))
			->setBackgroundColor(39, 174, 96, 0.5)
			->setBorderColor(39, 174, 96, 0.8)
			->setHoverBackgroundColor(39, 174, 96, 0.75)
			->setHoverBorderColor(39, 174, 96, 1)
		  ->setData($datos_comparados);
		}

		$grafico->addDataset($dataset_comparado);

		$yAxes[] = [
			'type' => 'linear',
			'display' => $option_display,
			'position' => 'right',
			'id' => 'y-axis-2',
			'gridLines' => [
				'display' => false
			],
			'labels' => [
				'show' => true
			],
			'ticks' => [
				'beginAtZero' => true
			]
		];
	}

	$options = [
		'responsive' => true,
		'tooltips' => [
			'mode' => 'label'
		],
		'scales' => [
			'xAxes' => [[
				'display' => true,
				'gridLines' => [
					'display' => false
				],
				'labels' => [
					'show' => true,
				]
			]],
			'yAxes' => $yAxes
		]
	];

	$grafico->setOptions($options);

	echo $grafico->getJson();
}

function graficoTarta($titulo, $labels, $datos, $tipo_dato) {
	$grafico = new TTB\Graficos\Grafico();
	$dataset = new TTB\Graficos\DatasetPie();

	$dataset->setData(array_values($datos))
	->setLabel('Resumen actividades profesionales')
	->setBorderColor(255, 255, 255, 0)
	->setHoverBorderColor(255, 255, 255, 0);

	$grafico->setNameChart($titulo)
		->setType('pie')
		->addLabels(array_values($labels))
		->addDataset($dataset);

	echo $grafico->getJson();
}

function graficoLinea($titulo, $labels, $datos, $datos_comparados, $tipo_dato, $tipo_dato_comparado, $id_moneda) {
	$grafico = new TTB\Graficos\Grafico();
	$datasetLinea = new TTB\Graficos\DatasetLine();
	$datasetLineaComparado = new TTB\Graficos\DatasetLine();

	$datasetLinea->setLabel(__($tipo_dato))
		->setType('line')
		->setYAxisID('y-axis-1')
		->setData($datos);

	$datasetLineaComparado->setLabel(__($tipo_dato_comparado))
		->setType('line')
		->setYAxisID('y-axis-2')
		->setBackgroundColor(39, 174, 96, 0.5)
		->setBorderColor(39, 174, 96, 0.8)
		->setData($datos_comparados);

	$yAxes[] = [
		'type' => 'linear',
		'display' => true,
		'position' => 'left',
		'stacked' => true,
		'id' => 'y-axis-1',
		'gridLines' => [
			'display' => false
		],
		'labels' => [
			'show' => true
		],
		'ticks' => [
			'beginAtZero' => true
		]
	];

	$yAxes[] = [
		'type' => 'linear',
		'display' => true,
		'position' => 'right',
		'id' => 'y-axis-2',
		'gridLines' => [
			'display' => false
		],
		'labels' => [
			'show' => true
		],
		'ticks' => [
			'beginAtZero' => true
		]
	];

	$options = [
		'scales' => [
			'yAxes' => $yAxes
		]
	];

	$grafico->setNameChart($titulo)
		->setType('line')
		->addLabels($labels)
		->addDataset($datasetLinea)
		->addDataset($datasetLineaComparado)
		->setOptions($options);

	echo $grafico->getJson();
}
