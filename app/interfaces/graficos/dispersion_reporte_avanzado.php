<?php
require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";
require_once "../../../app/classes/Reporte.php";

$sesion = new Sesion();

/* DATA y LABELS */
$data = explode(',',$t);
$labels = $n;
$dataC = explode(',',$c);

# Create a XY object of size 560 x 270 pixels, with a golden background and a 1
# pixel 3D border
$c = new XYChart(700, 390); 


# Add a title box using 15 pts Times Bold Italic font and metallic pink background
# color
$textBoxObj = $c->addTitle($titulo, "timesb.ttf", 15);
$textBoxObj->setBackground(metalColor(0xA7DF60));
$c->setPlotArea(20, 40, 600 , 280 ); 


if($compara)
{
	$c->yAxis->setTitle(__($unidad).Reporte::unidad($unidad,$sesion,$moneda)); 
	$c->xAxis->setTitle(__($unidadC).Reporte::unidad($unidadC,$sesion,$moneda));
	$c->yAxis->setColors( 0x000000,  0x000000,  0xD00000);
	$c->xAxis->setColors( 0x000000,  0x000000,  0x0000D0); 

	$layer = $c->addScatterLayer($dataC, $data, "G", DiamondSymbol, 13, 0xff9933); 
	
	$layer->addExtraField($labels); 
	$layer->addExtraField2($dataC); 


	$c->setYAxisOnRight(true); 

	$c->yAxis->setLabelStyle("arialbd.ttf"); 
}

if($imp_pdf==1)
{
		require_once Conf::ServerDir().'/fpdf/PDF_MemImage.php';
		$pdf = new PDF_MemImage();
		
		$pdf->SetTitle("Reporte");
		$pdf->AddPage();
		$pdf->SetFont('Arial', '', 12);
		
		$data = $c->makeChart2(PNG);
		$pdf->MemImage($data, 24, 40, 150);
		$contenido = $pdf->Output('', 'S');
		
		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen($contenido));
		header('Content-Disposition: attachment; filename=reporte_consolidado.pdf');
		print $contenido;		
}

$chart1URL = $c->makeSession("chart1"); 

$showText = "onmouseover='showInfo({value},{field1},\"{field0}\" );' ";
$hideText = "onmouseout='showInfo(null);' "; 
$toolTip = "title='{field0}'"; 

$imageMap = $c->getHTMLImageMap("", "", "$showText$hideText$toolTip"); 

?>


<html>
<body>

<script>
function showInfo(valor_x,valor_y,nombre) 
{
	var obj;
	obj = document.getElementById('detailInfo'); 
	if (!nombre)
	{ 
		obj.style.visibility = "hidden"; return; 
	}

	var unidad = " <?=__($unidad) ?>";
	var unidadC = " <?=__($unidadC) ?>";
	var simbolo = "<?=Reporte::unidad($unidad,$sesion,$moneda) ?>";
	var simboloC = "<?=Reporte::unidad($unidadC,$sesion,$moneda) ?>";

	var content = "<table border='1' cellpadding='3' style='font-size:10pt; " + "font-family:verdana; background-color:#CCCCFF' width='480'>";
	content += "<tr><td><b>Agrupador</b></td><td width='300'>" + nombre + "</td></tr>";
	content += "<tr><td><b>"+ unidad +" </b></td><td> " + valor_x + " "+simbolo+"</td></tr>"; 
	content += "<tr><td><b>"+ unidadC +"</b></td><td>" + valor_y + " "+simboloC+"</td></tr>"; 
	content += "</table>"; obj.innerHTML = content; obj.style.visibility = "visible"; 
	} 
</script>

<?php echo $imageMap;?>
<img src="getchart.php?<?php echo $chart1URL?>" border="0" usemap="#map1"> 
<map name="map1"> <?php echo $imageMap?> </map>

<div id="detailInfo" style="margin-left:60"></div> 
</body>
</html> 
