<?php

require_once dirname(__FILE__) . '/../../conf.php';

$Sesion = new Sesion();

$data = UtilesApp::utf8izar(json_decode(base64_decode($datos), true), false);
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

$grafico = new TTB\Graficos\GraficoTarta();

foreach ($datos as $key => $value) {
	$data_grafico = new TTB\Graficos\GraficoData();

	$data_grafico->addLabel($key, true)
	->addValue($value);

	$grafico->addData($data_grafico);
}

echo $grafico->getJson();
