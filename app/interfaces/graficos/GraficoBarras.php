<?php 
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::RutaGraficos();
	
class GraficoBarras extends XYChart
{
	var $layer;
	
	function GraficoBarras()
	{
		#Create a XYChart object of size 500 x 340 pixels, with a pale yellow (0xffff80)
		#background, a black border, and 0 pixel 3D border effect
		$this->XYChart(600,340); #, 0xffff80, 0x0, 0);

		#Set the plotarea at (55, 45) and of size 420 x 210 pixels, with white
		#background. Turn on both horizontal and vertical grid lines with light grey
		#color (0xc0c0c0)
		
		#$this->setPlotArea(55, 45, 420, 210, 0xffffff, -1, -1, 0xc0c0c0, -1);

		#Set the plot area at (45, 25) and of size 239 x 180. Use two alternative
		#background colors (0xffffc0 and 0xffffe0)
		$plotAreaObj = $this->setPlotArea(45, 25, 539, 180);
		$plotAreaObj->setBackground(0xffffc0, 0xffffe0);

		#Add a legend box at (55, 25) (top of the chart) with horizontal layout. Use 8
		#pts Arial font. Set the background and border color to Transparent.
		#$legendObj = $this->addLegend(55, 25, false, "", 9);
		$legendObj = $this->addLegend(45, 20, false, "", 8);
		$legendObj->setBackground(Transparent);

		$this->layer = $this->addBarLayer2(Side, 2);
		$this->layer->setAggregateLabelStyle("arialbd.ttf", 8, 0x000);

	}

	function Titulo($titulo)
	{
		#Add a title box to the chart using 11 pts Arial Bold Italic font. The text is
		#white (0xffffff) on a dark red (0x800000) background, with a 1 pixel 3D border.
		#$titleObj = $this->addTitle($titulo, "arialbd.ttf", 11, 0xffffff);
		#$titleObj->setBackground(0x800000, -1, 1);
		$this->addTitle("         $titulo", "", 10);
	}
	function Labels($labels)
	{
		if(is_array($labels))
		{
			foreach($labels as $key => $value)
				if(strlen($value) > 9)
				{
					if(preg_match("/\s/", $value))
						$labels[$key] = str_replace(" ", "\n", $value);
					else
						$labels[$key] = substr($value,0,7);
				}
		}
		$this->xAxis->setLabels($labels);
		$this->xAxis->setLabelStyle("arial.ttf", 9, 0x000, 45);
		#$labelStyleObj = $this->xAxis->setLabelStyle("arial.ttf", 9, 0x000);
		#$labelStyleObj->setFontAngle(90);
	}
	function Ejes($ejex, $ejey)
	{
		$this->yAxis->setTitle($ejey);
		$this->xAxis->setTitle($ejex);
		#Reserve 20 pixels at the top of the y-axis for the legend box
		$this->yAxis->setTopMargin(20);
	}
	function Imprimir()
	{
		header("Content-type: image/png");
		print($this->makeChart2(PNG));
	}
}
