<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

$data = UtilesApp::utf8izar(json_decode(base64_decode($datos), true), false);
$data_compara = empty($datos_compara) ? false : UtilesApp::utf8izar(json_decode(base64_decode($datos_compara), true), false);
$labels = UtilesApp::utf8izar(json_decode(base64_decode($labels), true), false);

$datos = array_combine($data['nombres'], $data['tiempo']);

function dort_desc($a, $b) {
	return $a < $b;
}
uasort($datos, 'dort_desc');

if ($datos_compara) {
	$datos_compara = array_combine($data_compara['nombres'], $data_compara['tiempo']);
	$datos_comparados = array();
	foreach ($datos as $key => $value) {
		$datos_comparados[] = $datos_compara[$key];
	}
}

$colores = array();
$nombres = array_keys($datos);
foreach ($nombres as $d) {
	if ($d > 0) {
		$colores[] = 0x0044ff;
	} else {
		$colores[] = 0xff0044;
	}
}

$y_axes = [];

$titulo = utf8_decode($_POST['titulo']);

$grafico = new TTB\Graficos\Grafico();
if (is_null($datos)) {
	echo $grafico->getJsonError(3, 'No exiten datos para generar el gr�fico');
	return;
}

$display['base'] = true;
$display['comparado'] = true;
$position['base'] = 'left';
$position['comparado'] = 'right';
$id_y['base'] = 'y-axis-1';
$id_y['comparado'] = 'y-axis-2';

if (max($datos) > max($datos_comparados)) {
	$display['comparado'] = false;
	$id_y['comparado'] = 'y-axis-1';
} else {
	$display['base'] = false;
	$position['comparado'] = 'left';
	$id_y['base'] = 'y-axis-2';
}

$dataset = new TTB\Graficos\Dataset();
$dataset->setLabel(__('Horas cobrables'))
	->setYAxisID($id_y['base'])
	->setData(array_values($datos));

$grafico->setNameChart($titulo)
	->addDataset($dataset)
	->addLabels($data['nombres']);

$LanguageManager = new LanguageManager($Sesion);

foreach ($datos as $key => $value) {
	$leyend_value = Format::number($value);
	$language_code = strtolower(Conf::read('Idioma'));
	$language = $LanguageManager->getByCode($language_code);
	$separators = [
		'decimales' => $language->get('separador_decimales'),
		'miles' => $language->get('separador_miles')
	];

	$labels_tooltips[] = "{$leyend_value} Hrs.";
}

$y_axes[] = [
	'type' => 'linear',
	'display' => $display['base'],
	'position' => $position['base'],
	'id' => 'y-axis-1',
	'gridLines' => [
		'display' => false
	],
	'labels' => [
		'show' => true
	],
	'ticks' => [
		'beginAtZero' => true,
		'callback' => $separators
	]
];

if ($datos_comparados) {
	$dataset_comparado = new TTB\Graficos\Dataset();

	$dataset_comparado->setLabel(__('Horas trabajadas'))
		->setYAxisID($id_y['comparado'])
		->setBackgroundColor(39, 174, 96, 0.5)
		->setBorderColor(39, 174, 96, 0.8)
		->setHoverBackgroundColor(39, 174, 96, 0.75)
		->setHoverBorderColor(39, 174, 96, 1)
	  ->setData($datos_comparados);

	foreach ($datos_comparados as $key => $value) {
		$leyend_value = Format::number($value);
		$language_code = strtolower(Conf::read('Idioma'));
		$language = $LanguageManager->getByCode($language_code);
		$separators = [
			'decimales' => $language->get('separador_decimales'),
			'miles' => $language->get('separador_miles')
		];

		$labels_tooltips_comparado[] = "{$leyend_value} Hrs.";
	}

	$grafico->addDataset($dataset_comparado);
	$y_axes[] = [
		'type' => 'linear',
		'display' => $display['comparado'],
		'position' => $position['comparado'],
		'id' => 'y-axis-2',
		'gridLines' => [
			'display' => false
		],
		'labels' => [
			'show' => true
		],
		'ticks' => [
			'beginAtZero' => true,
			'callback' => $separators
		]
	];
}

foreach ($labels_tooltips as $key => $value) {
	$labels_tooltips_callback[] = [$value, $labels_tooltips_comparado[$key]];
}

$options = [
	'responsive' => true,
	'tooltips' => [
		'mode' => 'label',
		'callbacks' => [
			'label' => $labels_tooltips_callback,
		]
	],
	'title' => [
		'display' => true,
		'fontSize' => 14,
		'text' => Convert::utf8(__($titulo))
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
		'yAxes' => $y_axes
	]
];

$grafico->setOptions($options);
echo $grafico->getJson();
