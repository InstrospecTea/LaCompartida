<?php
require_once "GraficoBarras.php";
require_once "../../../fw/classes/Sesion.php";
require_once "../../../app/classes/Reporte.php";

$sesion = new Sesion();

/* DATA y LABELS */
$data = explode(',',$t);
$labels = $n;
$dataC = explode(',',$c);

$colores = array();

foreach($data as $d)
{
	if($d > 0)
		$colores[] = 0x0044ff;
	else
		$colores[] = 0xff0044;
}

if(isset($p) && !$compara)
{
	$data[] = $p;
	$labels[] = __("Promedio");
	$colores[] = 0x00ff44;
}


$estimado_ampliacion = 0;
$cantidad_labels = sizeof($data);
if($compara)
	if(Reporte::sTipoDato($unidad) == Reporte::sTipoDato($unidadC))
		$cantidad_labels *= 2;

if($cantidad_labels > 20)
	$estimado_ampliacion = 18*($cantidad_labels - 20); 


# Create a XY object 
$c = new XYChart(700 + $estimado_ampliacion, 400); 

$margen_horizontal = strlen($labels[0])*4;
$margen_vertical = 0;
foreach($labels as $label)
	if(strlen($label)*4 > $margen_vertical)
		$margen_vertical = strlen($label)*4;

# Add a title box using 15 pts Times Bold Italic font and metallic pink background
# color
$textBoxObj = $c->addTitle($titulo, "timesb.ttf", 15);
$textBoxObj->setBackground(metalColor(0xA7DF60));
$c->setPlotArea(35 + $margen_horizontal , 40, 590 - $margen_horizontal + $estimado_ampliacion, 310 - $margen_vertical); 


$c->yAxis->setTitle(__($unidad).Reporte::unidad($unidad,$sesion,$moneda)); 

//Cuando se compara se usan dos set de Valores para los mismos Labels
if($compara)
{
	$c->yAxis2->setTitle(__($unidadC).Reporte::unidad($unidadC,$sesion,$moneda)); 

	$c->yAxis->setColors( 0x000000,  0x000000,  0xD00000);
	$c->yAxis2->setColors( 0x000000,  0x000000,  0x0000D0); 		
			
	//Si el tipo de dato es distinto, se crea un nuevo eje Y
	if(Reporte::sTipoDato($unidad) != Reporte::sTipoDato($unidadC))
	{
			$line = $c->addLineLayer();
			$dataObj = $line->addDataSet($dataC, 0x0000d0);
			$dataObj->setDataSymbol(DiamondSymbol, 11, 0x3030f0); 
			$line->setLineWidth(0); 
			$line->setUseYAxis2(); 

			$layer = $c->addBarLayer2(); 
			$layer->addDataSet($data, 0xff4400, "1"); 
	}
	else
	{
		$layer = $c->addBarLayer2(Side,2); 
		
		$layer->addDataSet($data, 0xff4400, "1"); 
		$layer->addDataSet($dataC, 0x4400ff, "2");

		$layer->setAggregateLabelStyle("arial.ttf", 9, 0x000000); 

		$c->xAxis->setTickOffset(0.5); 
	}

}
else
{
	$layer = $c->addBarLayer3($data, $colores,$titulo);
	

	$layer->setAggregateLabelStyle("arialbd.ttf", 8, $layer->yZoneColor(0, 0xcc3300, 0x3333ff)); 

	//Bueno para positivo - negativo:
	//$c->yAxis->addZone(0, 9999, 0xccccff); 
	//$c->yAxis->addZone(-9999, 0, 0xffcccc); 
}	
	$labelsObj = $c->xAxis->setLabels($labels);
	$labelsObj->setFontStyle("arialbd.ttf"); 
	$labelsObj->setFontAngle(30); 

$c->setYAxisOnRight(true); 
$c->yAxis->setLabelStyle("arialbd.ttf"); 



$chart1URL = $c->makeSession("chart1"); 

$showText = "onmouseover='showInfo(\"{xLabel}\",{value});' ";
$hideText = "onmouseout='showInfo(null);' "; 
$toolTip = "title='{value}'"; 

$imageMap = $c->getHTMLImageMap("", "", "$showText$hideText$toolTip"); 

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
?>


<html>
<body>

<script>
function showInfo(nombre,valor) 
{
	var obj;
	obj = document.getElementById('detailInfo'); 
	if (!valor)
	{ 
		obj.style.visibility = "hidden"; return; 
	}
	/*
	var content = "<table border='1' cellpadding='1' style='font-size:10pt; " + "font-family:verdana; background-color:#CCCCFF' width='480'>";
	content += "<tr><td><b>Valor</b></td><td width='300'>" + valor + "</td></tr>";
	content += "</table>"; obj.innerHTML = content; obj.style.visibility = "visible";*/ 
	} 
</script>

<?php echo $imageMap;?>
<img src="getchart.php?<?php echo $chart1URL?>" border="0" usemap="#map1"> 
<map name="map1"> <?php echo $imageMap?> </map>

<div id="detailInfo" style="margin-left:60"></div> 
</body>
</html> 
