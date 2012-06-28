<?php
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::RutaGraficos();

	class Grafico extends XYChart
	{
		var $layer_adelante;	// Usada en gráficos de comparación.
		var $layer;
		var $ancho_completo;

		function Grafico($titulo, $ancho_completo=true, $tipo_layer=Side, $dos_ejes=false, $opciones = array())
		{
			$this->ancho_completo = $ancho_completo;
			if($this->ancho_completo)
				$this->XYChart(1200, 680);
			else
				$this->XYChart(600, 680);
			$this->yAxis->setTickDensity(42);
			$tamano_fuente = 16;
			if($opciones['fuente_eje_y'])
				$tamano_fuente = $opciones['fuente_eje_y'];
			$this->yAxis->setLabelStyle('', $tamano_fuente);
			$this->yAxis2->setLabelStyle('', $tamano_fuente);
			$this->yAxis2->setTickDensity(42);

			// Dejar un espacio de 20 pixeles para posibles leyendas.
			$this->yAxis->setTopMargin(20);

			if($opciones['top_margin'])
			{
				$this->yAxis->setTopMargin($opciones['top_margin']);
				$this->yAxis2->setTopMargin($opciones['top_margin']);
			}


			$plotAreaObj = $this->setPlotArea($this->ancho_completo?190:95, 50, ($this->ancho_completo?1200:600)-($dos_ejes?300:200), 360);
			$plotAreaObj->setBackground(0xffffc0, 0xffffe0);

			$legendObj = $this->addLegend($this->ancho_completo?190:95, 40, false, '', 16);
			$legendObj->setBackground(Transparent);

			$this->layer_adelante = $this->addLineLayer();
			$this->layer_adelante->setLineWidth(0);
			$this->layer_adelante->setUseYAxis2();

			$this->layer = $this->addBarLayer2($tipo_layer, 6);
			if($opciones['no_aggregate_layer'])
			{
			}
			else
			{
				$this->layer->setAggregateLabelStyle("arialbd.ttf", 16, 0);
			}
			

			$this->addTitle($titulo, '', 20);
			$this->SetNumberFormat('.',',');
			$this->setTransparentColor(0xffffff);
		}

		function Labels($labels, $cortar=false)
		{
			if(is_array($labels))
			{
				foreach($labels as $key => $value)
				{
					$value .= ' ';
					$temp = '';
					$cuenta = 0;
					$lineas = 0;
					// Separar el label en varias líneas si es muy largo (largo mayor a 13), tratando de no cortar palabras.
					for($i=0; $i<strlen($value)-1; ++$i)
					{
						if($cuenta > 13)
						{
							if($cortar && ++$lineas == 2)
							{
								$temp .= '...';
								break;
							}
							$temp .= "-\n".$value[$i];
							$cuenta = -1;
						}
						elseif($value[$i]==' ' && $cuenta + strpos($value, ' ', $i+1) - $i > 13)
						{
							if($cortar && ++$lineas == 2)
							{
								$temp .= '...';
								break;
							}
							$temp .= "\n";
							$cuenta = -1;
						}
						else
							$temp .= $value[$i];
						++$cuenta;
					}
					$labels[$key] = $temp;
				}
			}
			$this->xAxis->setLabels($labels);
			$this->xAxis->setLabelStyle("arial.ttf", 18, 0, 45);
		}
		function Ejes($eje_x, $eje_y, $eje_y_2=false)
		{
			$this->yAxis->setTitle($eje_y, '', 16);
			$this->xAxis->setTitle($eje_x, '', 16);
			if($eje_y_2)
				$this->yAxis2->setTitle($eje_y_2, '', 16);
			
		}
	}
?>
