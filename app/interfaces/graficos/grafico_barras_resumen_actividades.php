<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

$data = UtilesApp::utf8izar(json_decode(base64_decode($datos), true), false);
$data_compara = empty($datos_compara) ? false : UtilesApp::utf8izar(json_decode(base64_decode($datos_compara), true), false);
$labels = UtilesApp::utf8izar(json_decode(base64_decode($labels), true), false);

$datos = array_combine($data['nombres'], $data['tiempo']);
$datos_compara = array_combine($data_compara['nombres'], $data_compara['tiempo']);

function dort_desc($a, $b) {
	return $a < $b;
}
uasort($datos, 'dort_desc');

if ($datos_compara) {
	// $labels = explode(',', $labels);
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

$estimado_ampliacion = 0;
$cantidad_datos = count($datos);

if ($cantidad_datos > 20) {
	$estimado_ampliacion = 18 * ($cantidad_datos - 20);
}

$grafico = new TTB\Graficos\GraficoBarra();
$dataset = new TTB\Graficos\GraficoDataset();

$dataset->addLabel('Horas cobrables')
	->addData(array_values($datos));

$grafico->addDataSets($dataset)
	->addNameChart($titulo)
	->addLabels($data['nombres']);

if ($datos_comparados) {
	$dataset_comparado = new TTB\Graficos\GraficoDataset();

	$dataset_comparado->addLabel('Horas trabajadas')
		->addFillColor(39, 174, 96, 0.5)
		->addStrokeColor(39, 174, 96, 0.8)
		->addHighlightFill(39, 174, 96, 0.75)
		->addHighlightStroke(39, 174, 96, 1)
	  ->addData($datos_comparados);

	$grafico->addDataSets($dataset_comparado);
}

echo $grafico->getJson();
