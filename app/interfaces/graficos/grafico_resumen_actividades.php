<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

$data = json_decode(base64_decode($datos), true);
$datos = array_combine($data['nombres'], $data['tiempo']);

function dort_desc($a, $b) {
	return $a < $b;
}

uasort($datos, 'dort_desc');

$total = array_sum($datos);
$derecha = 0;
$total_tiempo = count($datos);
$k = 0;
foreach ($datos as $key => $value) {
	$derecha += $value;
	if ($derecha * 2 > $total) {
		$alto = max($alto, 19 * ($k > $total_tiempo / 2 ? $k + 2 : $total_tiempo - $k) + 50);
		break;
	}
	++$k;
}

$titulo = utf8_decode($_POST['titulo']);

$grafico = new TTB\Graficos\Grafico();
if (is_null($datos)) {
	echo $grafico->getJsonError(3, 'No exiten datos para generar el gráfico');
	return;
}

$labels = [];
foreach ($datos as $key => $value) {
	$percentage = round((($value / $total) * 100), 2);
	$value_formated = Format::number(floatval($value));
	$labels[] = "{$key}: {$value_formated} Hrs. ({$percentage}%)";
	$labels_tooltips[] = ["{$key}: {$value_formated} Hrs. ({$percentage}%)"];
}

$dataset = new TTB\Graficos\DatasetPie();

$dataset->setData(array_values($datos))
	->setLabel(__('Resumen actividades profesionales'))
	->setBorderColor(255, 255, 255, 0)
	->setHoverBorderColor(255, 255, 255, 0);

$options = [
	'responsive' => true,
	'legend' => [
		'display' => true,
		'position' => 'bottom'
	],
	'title' => [
		'display' => true,
		'fontSize' => 14,
		'text' => __($titulo)
	],
	'tooltips' => [
		'mode' => 'label',
		'callbacks' => [
			'label' => $labels_tooltips,
		]
	]
];

$grafico->setType('pie')
	->setNameChart(__($titulo))
	->addLabels($labels)
	->addDataset($dataset)
	->setOptions($options);

echo $grafico->getJson();
