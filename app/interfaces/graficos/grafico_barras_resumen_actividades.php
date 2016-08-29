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
$dataset = new TTB\Graficos\Dataset();

$dataset->setLabel('Horas cobrables')
	->setYAxisID('y-axis-1')
	->setData(array_values($datos));

$grafico->setNameChart($titulo)
	->addDataset($dataset)
	->addLabels($data['nombres']);

$y_axes[] = [
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

if ($datos_comparados) {
	$dataset_comparado = new TTB\Graficos\Dataset();

	$dataset_comparado->setLabel('Horas trabajadas')
		->setYAxisID('y-axis-2')
		->setBackgroundColor(39, 174, 96, 0.5)
		->setBorderColor(39, 174, 96, 0.8)
		->setHoverBackgroundColor(39, 174, 96, 0.75)
		->setHoverBorderColor(39, 174, 96, 1)
	  ->setData($datos_comparados);

	$grafico->addDataset($dataset_comparado);
	$y_axes[] = [
		'type' => 'linear',
		'display' => false,
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
		'yAxes' => $y_axes
	]
];

$grafico->setOptions($options);
echo $grafico->getJson();
