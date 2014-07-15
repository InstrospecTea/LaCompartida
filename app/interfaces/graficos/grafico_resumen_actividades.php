<?php

require_once dirname(__FILE__) . '/../../conf.php';
require_once(Conf::RutaGraficos());

require_once Conf::ServerDir() . '/../app/interfaces/graficos/GraficoBarras.php';


$Sesion = new Sesion();
// The data for the pie chart

$ancho = Conf::GetConf($Sesion, 'AnchoGraficoReporteGeneral') ? Conf::GetConf($Sesion, 'AnchoGraficoReporteGeneral') : 900;

$alto = Conf::GetConf($Sesion, 'AltoGraficoReporteGeneral') ? Conf::GetConf($Sesion, 'AltoGraficoReporteGeneral') : 900;

$radio = 100;

$data = UtilesApp::utf8izar(json_decode(base64_decode($_GET['datos']), true), false);

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

// Create a PieChart object of size 560 x 270 pixels, with a golden background and a 1
// pixel 3D border
$c = new PieChart($ancho, $alto, goldColor(), -1, 1);

// Add a title box using 15 pts Times Bold Italic font and metallic pink background
// color
$textBoxObj = $c->addTitle($titulo, "timesb.ttf", 15);
$textBoxObj->setBackground(metalColor(0xA7DF60));

// Set the center of the pie at (280, 135) and the radius to 110 pixels
$c->setPieSize($ancho / 2, $alto / 2, $radio);

// Draw the pie in 3D with 20 pixels 3D depth
$c->set3D(20);

// Use the side label layout method
$c->setLabelLayout(SideLayout, -1, 30, $alto - 10);
$c->setLabelFormat("{label} {value|2}hrs. ({percent}%)");

// Set the label box background color the same as the sector color, with glass effect,
// and with 5 pixels rounded corners
$t = $c->setLabelStyle();
$t->setBackground(SameAsMainColor, Transparent, glassEffect());
$t->setRoundedCorners(5);

// Set the border color of the sector the same color as the fill color. Set the line
// color of the join line to black (0x0)
$c->setLineColor(SameAsMainColor, 0x000000);

// Set the start angle to 135 degrees may improve layout when there are many small
// sectors at the end of the data array (that is, data sorted in descending order). It
// is because this makes the small sectors position near the horizontal axis, where
// the text label has the least tendency to overlap. For data sorted in ascending
// order, a start angle of 45 degrees can be used instead.
$c->setStartAngle(0);

// Set the pie data and the pie labels
$c->setData(array_values($datos), array_keys($datos));

// output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
