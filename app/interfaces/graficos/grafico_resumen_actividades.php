<?php


require_once dirname(__FILE__) . '/../../conf.php';
require_once(Conf::RutaGraficos());

require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../app/classes/UtilesApp.php';

require_once Conf::ServerDir() . '/../app/interfaces/graficos/GraficoBarras.php';



$sesion = new Sesion();
# The data for the pie chart

$ancho= ( UtilesApp::GetConf($sesion,'AnchoGraficoReporteGeneral'))? UtilesApp::GetConf($sesion,'AnchoGraficoReporteGeneral'):900;

$alto =(UtilesApp::GetConf($sesion,'AltoGraficoReporteGeneral')  )? UtilesApp::GetConf($sesion,'AltoGraficoReporteGeneral') : 900;

$radio = 100;


$data = $tiempo;
# The labels for the pie chart
$labels = $nombres;

$total = array_sum($data);
$derecha = 0;
foreach($data as $k => $v){
	$derecha += $v;
	if($derecha*2 > $total){
		$alto = max($alto, 19 * ($k>count($data)/2 ? $k+2 : count($data)-$k) + 50);
		break;
	}
}

# Create a PieChart object of size 560 x 270 pixels, with a golden background and a 1
# pixel 3D border
$c = new PieChart($ancho, $alto, goldColor(), -1, 1);

# Add a title box using 15 pts Times Bold Italic font and metallic pink background
# color
$textBoxObj = $c->addTitle($titulo, "timesb.ttf", 15);
$textBoxObj->setBackground(metalColor(0xA7DF60));

# Set the center of the pie at (280, 135) and the radius to 110 pixels
$c->setPieSize($ancho / 2, $alto / 2, $radio);

# Draw the pie in 3D with 20 pixels 3D depth
$c->set3D(20);

# Use the side label layout method
$c->setLabelLayout(SideLayout, -1, 30, $alto - 10);
$c->setLabelFormat("{label} {value|2}hrs. ({percent}%)");

# Set the label box background color the same as the sector color, with glass effect,
# and with 5 pixels rounded corners
$t = $c->setLabelStyle();
$t->setBackground(SameAsMainColor, Transparent, glassEffect());
$t->setRoundedCorners(5);

# Set the border color of the sector the same color as the fill color. Set the line
# color of the join line to black (0x0)
$c->setLineColor(SameAsMainColor, 0x000000);

# Set the start angle to 135 degrees may improve layout when there are many small
# sectors at the end of the data array (that is, data sorted in descending order). It
# is because this makes the small sectors position near the horizontal axis, where
# the text label has the least tendency to overlap. For data sorted in ascending
# order, a start angle of 45 degrees can be used instead.
$c->setStartAngle(0);

# Set the pie data and the pie labels
$c->setData($data, $labels);

# output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>