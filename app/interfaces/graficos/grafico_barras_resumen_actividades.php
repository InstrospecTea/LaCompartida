<?php

require_once dirname(__FILE__) . '/../../conf.php';
require_once(Conf::RutaGraficos());

require_once Conf::ServerDir() . '/../app/interfaces/graficos/GraficoBarras.php';


$sesion = new Sesion();


$data = json_decode(base64_decode($_GET['datos']), true);
$data_compara = empty($_GET['datos_compara']) ? false : json_decode(base64_decode($_GET['datos_compara']), true);
if ($data_compara) {
	$labels = explode(',', $_GET['labels']);
}
$colores = array();

foreach ($data['nombres'] as $d) {
	if ($d > 0) {
		$colores[] = 0x0044ff;
	} else {
		$colores[] = 0xff0044;
	}
}

$estimado_ampliacion = 0;
$cantidad_labels = sizeof($data['nombres']);
if ($data_compara) {
	if ($labels[0] == $labels[1]) {
		$cantidad_labels *= 2;
	}
}

if ($cantidad_labels > 20) {
	$estimado_ampliacion = 18 * ($cantidad_labels - 20);
}


# Create a XY object
$c = new XYChart(700 + $estimado_ampliacion, 400);

$margen_horizontal = strlen($data['nombres']) * 4;
$margen_vertical = 0;
foreach ($data['nombres'] as $label) {
	if (strlen($label) * 4 > $margen_vertical) {
		$margen_vertical = strlen($label) * 4;
	}
}

# Add a title box using 15 pts Times Bold Italic font and metallic pink background
# color
$textBoxObj = $c->addTitle($titulo, 'timesb.ttf', 15);
$textBoxObj->setBackground(metalColor(0xA7DF60));
$c->setPlotArea(35 + $margen_horizontal, 40, 590 - $margen_horizontal + $estimado_ampliacion, 310 - $margen_vertical);


$c->yAxis->setTitle($labels[0]);
//Cuando se compara se usan dos set de Valores para los mismos Labels
if ($data_compara) {
	$c->yAxis2->setTitle($labels[1]);

	$c->yAxis->setColors(0x000000, 0x000000, 0xD00000);
	$c->yAxis2->setColors(0x000000, 0x000000, 0x0000D0);

	$layer = $c->addBarLayer2(Side, 0);

	$layer->addDataSet($data['tiempo'], 0xff4400, "1");
	$layer->addDataSet($data_compara['tiempo'], 0x4400ff, '2');

	$layer->setAggregateLabelStyle('arial.ttf', 9, 0x000000);

	$c->xAxis->setTickOffset(0.5);
} else {
	$layer = $c->addBarLayer3($data['tiempo'], $colores, $titulo);
	$layer->setAggregateLabelStyle('arialbd.ttf', 8, $layer->yZoneColor(0, 0xcc3300, 0x3333ff));
}
$labelsObj = $c->xAxis->setLabels($data['nombres']);
$labelsObj->setFontStyle('arialbd.ttf');
$labelsObj->setFontAngle(30);

$c->setYAxisOnRight(true);
$c->yAxis->setLabelStyle('arialbd.ttf');



//$chart1URL = $c->makeSession("chart1");
// output the chart
header('Content-type: image/png');
print($c->makeChart2(PNG));
