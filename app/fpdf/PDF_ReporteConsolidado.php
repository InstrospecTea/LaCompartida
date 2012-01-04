<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';
	require_once Conf::ServerDir().'/interfaces/graficos/Grafico.php';
	require_once Conf::ServerDir().'/fpdf/PDF_MemImage.php';

	class PDF_ReporteConsolidado extends PDF_MemImage
	{
		// Estas variables se reciben o generan en el constructor.
		var $header_linea_1;
		var $header_linea_2;
		var $sesion;
		var $id_moneda;
		var $largo_mes;
		var $nombre_mes;
		var $fecha_anio;
		var $fecha_mes;
		var $pos_grafico_y;
		var $num_grafico;
		var $max_por_grafico;
		var $titulos;
		var $ejes_y;
		var $areas;
		var $categorias;
		var $notas;
		var $glosa_notas;
		var $simbolo_notas;
		var $notas_escritas;
		var $nueva_pagina;	# Se usa para detectar si se genera una nueva página al imprimir una tabla, lo que indica que se debe repetir el encabezado.

		function PDF_ReporteConsolidado($sesion, $id_moneda, $fecha_anio, $fecha_mes, $areas_excluidas, $categorias, $max_por_grafico=12, $orientation='P', $unit='mm', $format='Letter')
		{
			$this->PDF_MemImage($orientation, $unit, $format);

			$this->sesion = $sesion;
			$this->id_moneda = $id_moneda;
			$this->largo_mes = array(31, Utiles::es_bisiesto($fecha_anio)?29:28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
			$this->nombre_mes = array(__('Enero'), __('Febrero'), __('Marzo'), __('Abril'), __('Mayo'), __('Junio'), __('Julio'), __('Agosto'), __('Septiembre'), __('Octubre'), __('Noviembre'), __('Diciembre'));

			$this->header_linea_1 = __('Reporte consolidado').' '.$this->nombre_mes[$fecha_mes-1]." $fecha_anio";

			if($areas_excluidas)
				if(!is_array($areas_excluidas))
					$this->areas_excluidas = array($areas_excluidas);
			else
				$this->areas_excluidas = $areas_excluidas;

			if($this->areas_excluidas)
			{
				$query = "SELECT glosa FROM prm_area_proyecto WHERE id_area_proyecto IN('".implode("','",$this->areas_excluidas)."')";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				$this->header_linea_1_5 = __('Excluye').' '.__('prm_area_proyecto.glosa').': ';

				$glosas_areas = array();
				while( list($glosa_area) = mysql_fetch_array($resp) )
					 $glosas_areas[] = $glosa_area;
				$this->header_linea_1_5 .= implode(', ',$glosas_areas);

				$this->glosa_reporte = $this->header_linea_1_5;
			}

			$this->header_linea_2 = method_exists('Conf', 'GetConf')?Conf::GetConf($sesion, 'NombreEmpresa'):(method_exists('Conf', 'PdfLinea1')?Conf::PdfLinea1():'');

			$this->pos_grafico_y = array(40, 110, 180);
			$this->num_grafico = 0;
			$this->fecha_anio = $fecha_anio;
			$this->fecha_mes = $fecha_mes;
			$this->max_por_grafico = $max_por_grafico;

			// Definir títulos y nombres de ejes en base al tipo de dato, para asegurar consistencia.
			$simbolo_moneda = Utiles::glosa($this->sesion, $this->id_moneda, 'simbolo', 'prm_moneda', 'id_moneda');
			$glosa_moneda = Utiles::glosa($this->sesion, $this->id_moneda, 'glosa_moneda', 'prm_moneda', 'id_moneda');

			$this->titulos['horas_trabajadas'] = __('Horas trabajadas');
			$this->titulos['horas_cobrables'] = __('Horas cobrables');
			$this->titulos['horas_no_cobrables'] = __('Horas no cobrables');
			$this->titulos['horas_castigadas'] = __('Horas castigadas');
			$this->titulos['horas_visibles'] = __('Horas visibles');
			$this->titulos['horas_cobradas'] = __('Horas cobradas');
			$this->titulos['horas_por_cobrar'] = __('Horas por cobrar');
			$this->titulos['horas_pagadas'] = __('Horas pagadas');
			$this->titulos['horas_por_pagar'] = __('Horas por pagar');
			$this->titulos['valor_cobrado'] = __('Valor cobrado');
			$this->titulos['valor_pagado'] = __('Valor pagado');
			$this->titulos['valor_por_pagar'] = __('Valor por pagar');
			$this->titulos['rentabilidad'] = __('Rentabilidad');
			$this->titulos['valor_hora'] = __('Valor hora');
			$this->titulos['diferencia_valor_estandar'] = __('Diferencia valor estándar');

			$this->ejes_y['horas_trabajadas'] = __('Horas');
			$this->ejes_y['horas_cobrables'] = __('Horas');
			$this->ejes_y['horas_no_cobrables'] = __('Horas');
			$this->ejes_y['horas_castigadas'] = __('Horas');
			$this->ejes_y['horas_visibles'] = __('Horas');
			$this->ejes_y['horas_cobradas'] = __('Horas');
			$this->ejes_y['horas_por_cobrar'] = __('Horas');
			$this->ejes_y['horas_pagadas'] = __('Horas');
			$this->ejes_y['horas_por_pagar'] = __('Horas');
			$this->ejes_y['valor_cobrado'] = $glosa_moneda==$simbolo_moneda?$glosa_moneda:"$glosa_moneda ($simbolo_moneda)";
			$this->ejes_y['valor_pagado'] = $glosa_moneda==$simbolo_moneda?$glosa_moneda:"$glosa_moneda ($simbolo_moneda)";
			$this->ejes_y['valor_por_pagar'] = $glosa_moneda==$simbolo_moneda?$glosa_moneda:"$glosa_moneda ($simbolo_moneda)";
			$this->ejes_y['rentabilidad'] = __('Rentabilidad');
			$this->ejes_y['valor_hora'] = $glosa_moneda==$simbolo_moneda?$glosa_moneda:"$glosa_moneda ($simbolo_moneda)";
			$this->ejes_y['diferencia_valor_estandar'] = $glosa_moneda==$simbolo_moneda?$glosa_moneda:"$glosa_moneda ($simbolo_moneda)";

			$this->glosa_notas['valor_cobrado'] = __('Se debe tener en cuenta que en este gráfico no están incluídas las horas por cobrar.');
			$this->simbolo_notas['valor_cobrado'] = '*';

			$this->nueva_pagina = false;
		}

		// La primera página tiene el título más grande que el resto.
		function Header()
		{
			$this->SetY(10);
			if($this->PageNo()==1)
			{
				$this->SetFont('Arial', '', 18);
				$this->Cell(0, 8, $this->header_linea_1, 0, 0, 'C');
				$this->Ln();
				if($this->header_linea_1_5)
				{
					$this->Cell(0, 8, $this->header_linea_1_5, 0, 0, 'C');
					$this->Ln();
				}
				$this->Cell(0, 8, $this->header_linea_2, 0, 0, 'C');
				return;
			}

			$this->SetFont('Arial', 'I', 8);
			$this->Cell(0, 4, $this->header_linea_1, 0, 0, 'C');
			$this->Ln();
			$this->Cell(0, 4, $this->header_linea_2, 0, 0, 'C');
		}



		// Permite agregar notas al pie de página, recibe las coordenadas (x, y) del lugar donde va el número y tipo de nota.
		function agregarNota($x, $y, $tipo_nota)
		{
			$this->notas['x'][] = $x;
			$this->notas['y'][] = $y;
			$this->notas['tipo'][] = $tipo_nota;
		}

		// El footer imprime el número de página y las notas al pie.
		function Footer()
		{
			$this->SetY(-15);
			$this->SetFont('Arial', 'I', 8);
			$this->Cell(0, 4, __('Página').' '.$this->PageNo(), 0, 0, 'C');

			// Notas al pie, la idea es poner solo una vez cada tipo de nota, repitiendo su símbolo en todos los lugares donde sea necesario.
			$this->SetFont('Arial', 'I', 12);
			for($i=0; $i<count($this->notas['x']); ++$i)
			{
				$this->SetXY($this->notas['x'][$i], $this->notas['y'][$i]);
				$this->Cell(0, 4, $this->simbolo_notas[$this->notas['tipo'][$i]]);
				if(!$this->notas_escritas[$this->notas['tipo'][$i]])
				{
					$this->notas_escritas[$this->notas['tipo'][$i]] = true;
					$this->SetXY(10, -15-5*count($this->notas_escritas));
					$this->SetFont('Arial', 'I', 8);
					$this->Cell(0, 4, $this->simbolo_notas[$this->notas['tipo'][$i]].': '.$this->glosa_notas[$this->notas['tipo'][$i]]);
					$this->SetFont('Arial', 'I', 12);
				}
			}
			unset($this->notas);
			unset($this->notas_escritas);
		}

		function addPage($orientation='', $format='', $titulo=false)
		{
			parent::addPage($orientation, $format);
			$this->num_grafico = $titulo?-1:0;
			if($titulo)
			{
				$this->SetFont('Arial', '', 14);
				$this->SetY(24);
				$this->Cell(0, 8, $titulo, 0, 0, 'C');
			}
			$this->nueva_pagina = true;
		}

		function getDatos($tipo_dato, $fecha_desde, $fecha_hasta, $detalles=false, $vista='mes_reporte-glosa_cliente', $campo_fecha='trabajo')
		{
			$reporte = new Reporte($this->sesion);
			$reporte->id_moneda = $this->id_moneda;
			$reporte->addRangoFecha($fecha_desde, $fecha_hasta);
			$reporte->setTipoDato($tipo_dato);
			$reporte->setVista($vista);
			$reporte->setCampoFecha($campo_fecha);
			if($this->areas_excluidas[0])
				foreach($this->areas_excluidas as $area)
					$reporte->addFiltro('asunto', 'id_area_proyecto', $area, false);		
			$reporte->Query();
			if($detalles)
				return $reporte->toBars();
			$r = $reporte->toBars();
			return $r['promedio'];
		}

		//Esta funcion es especial porque muestra el valor por cobrar sobre el valor cobrado
		function addGraficoComparacionValorCobrado($tipo_dato = 'valor_cobrado')
		{
			$datos = array();
			# Últimos 12 meses
			$fecha_desde = '01-'.($this->fecha_mes==12?'01-'.$this->fecha_anio:$this->fecha_mes+1 .'-'.($this->fecha_anio-1));
			$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;		
			//Normalmente cada columna stackearía las 4 barras. Esto se arregla poniendo 'NoValue' para que no muestre una barra.
			$datos[] = array($this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta),NoValue,NoValue);
			$por_cobrar_doce_meses = $this->getDatos('valor_por_cobrar', $fecha_desde, $fecha_hasta);
			
			# Año en curso
			$fecha_desde = '01-01-'.$this->fecha_anio;
			$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$datos[] = array(NoValue,$this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta),NoValue);			
			$por_cobrar_anyo_en_curso = $this->getDatos('valor_por_cobrar', $fecha_desde, $fecha_hasta);

			# Mes actual
			$fecha_desde = '01-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$datos[] = array(NoValue,NoValue,$this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta));

			# Valor por cobrar
			$por_cobrar_mes_actual = $this->getDatos('valor_por_cobrar', $fecha_desde, $fecha_hasta);
			$datos[] = array($por_cobrar_doce_meses,$por_cobrar_anyo_en_curso,$por_cobrar_mes_actual);
			$nombres = array(__('Promedio mensual 12 meses'),
							__('Promedio mensual año actual'),
							__('Mes actual'));
			

			$opciones = array('no_aggregate_layer'=>true,'top_margin'=>40);
			if( max($datos[0][0],$datos[1][1],($datos[2][2]+$datos[3][2])) > 10000)
				$opciones['fuente_eje_y'] = 14;

			$grafico = new Grafico($this->titulos[$tipo_dato], false, Stack, false, $opciones);
			$grafico->Ejes('', $this->ejes_y[$tipo_dato]);
			$grafico->Labels($nombres);

			$colores = array(0x0044ff, 0xff0044, 0x11ff44,0xCCBBff);

			$grafico->layer->AddDataGroup(__('Cosa'));
			$grafico->layer->AddDataSet($datos[0],$colores[0],'');
			$grafico->layer->AddDataSet($datos[1],$colores[1],'');
			$grafico->layer->AddDataSet($datos[2],$colores[2],'Cobrado');
			$grafico->layer->AddDataSet($datos[3],$colores[3],'por Cobrar');

			
			$label_12_meses = '<*size=14*><*block*><*color=8855BB*>'.number_format($datos[3][0],2,',','.').'<*/*><*br*><*block*><*color=000000*>'.number_format($datos[0][0],2,',','.').'<*/*>';
			$box[0] = $grafico->layer->addCustomGroupLabel(0, 0, $label_12_meses, 'arial.ttf', 14, 0);

			$label_anyo = '<*size=14*><*block*><*color=8855BB*>'.number_format($datos[3][1],2,',','.').'<*/*><*br*><*block*><*color=000000*>'.number_format($datos[1][1],2,',','.').'<*/*>';
			$box[1] = $grafico->layer->addCustomGroupLabel(0, 1, $label_anyo, 'arial.ttf', 14, 0);

			$label_cobrado = '<*size=14*><*block*><*color=8855BB*>'.number_format($datos[3][2],2,',','.').'<*/*><*br*><*block*><*color=000000*>'.number_format($datos[2][2],2,',','.').'<*/*>';
		    $box[2] = $grafico->layer->addCustomGroupLabel(0, 2, $label_cobrado, 'arial.ttf', 14);

			if(++$this->num_grafico==2*count($this->pos_grafico_y))
				$this->addPage();

			$this->MemImage($grafico->makeChart2(PNG), 24+($this->num_grafico%2?0:90), $this->pos_grafico_y[(int)($this->num_grafico+1)/2], 75);
		}


		// Compara 1 dato con el promedio del año actual y el promedio de los últimos 12 meses.
		function addGraficoComparacion($tipo_dato)
		{
			$grafico = new Grafico($this->titulos[$tipo_dato], false);
			$grafico->Ejes('', $this->ejes_y[$tipo_dato]);
			$datos = array();
			# Últimos 12 meses
			$fecha_desde = '01-'.($this->fecha_mes==12?'01-'.$this->fecha_anio:$this->fecha_mes+1 .'-'.($this->fecha_anio-1));
			//largo_mes parte de 0, hay que retroceder 1 para el largo del mes actual.
			$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$datos[] = $this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta);
			# Año en curso
			$fecha_desde = '01-01-'.$this->fecha_anio;
			$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$datos[] = $this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta);
			# Mes actual
			$fecha_desde = '01-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$datos[] = $this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta);
			$nombres = array(__('Promedio mensual 12 meses'),
							__('Promedio mensual año actual'),
							__('Mes actual'));
			$grafico->Labels($nombres);

			$colores = array(0x0044ff, 0xff0044, 0x00ff44);
			$layer = $grafico->addBarLayer3($datos, $colores, '', 6);
			$layer->setAggregateLabelStyle('arialbd.ttf', 16, 0);

			// Hacer que siempre se incluya el cero en el eje y.
			$grafico->yAxis->setAutoScale(.1, .1, 1);

			if(++$this->num_grafico==2*count($this->pos_grafico_y))
				$this->addPage();

			$this->MemImage($grafico->makeChart2(PNG), 24+($this->num_grafico%2?0:90), $this->pos_grafico_y[(int)($this->num_grafico+1)/2], 75);
		}

		function addGraficoDesglose($tipo_dato, $cifras_decimales, $campo_desglose, $compara_historico=true, $campo_fecha='trabajo', $tipo_dato_comparado=false)
		{
			# Mes actual
			$fecha_desde = '01-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$datos = $this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta, true, $campo_desglose, $campo_fecha);
			if($tipo_dato_comparado)
			{
				$datos_comparados = $this->getDatos($tipo_dato_comparado, $fecha_desde, $fecha_hasta, true, $campo_desglose);
				$this->max_por_grafico -= 2;
			}
			if($compara_historico)
			{
				// Año en curso
				$fecha_desde = '01-01-'.$this->fecha_anio;
				$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;
				$datos_anio_en_curso = $this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta, true, $campo_desglose, $campo_fecha);
				// Últimos 12 meses
				$fecha_desde = '01-'.($this->fecha_mes==12?'01-'.$this->fecha_anio:$this->fecha_mes+1 .'-'.($this->fecha_anio-1));
				$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;
				$datos_12_meses = $this->getDatos($tipo_dato, $fecha_desde, $fecha_hasta, true, $campo_desglose, $campo_fecha);
			}
			// Siempre se escriben todas las formas de cobro, manteniendo el orden en todos los gráficos.
			$nombres = array();
			$valores = array();
			if($campo_desglose=='forma_cobro')
			{
				$query = 'SELECT forma_cobro, descripcion FROM prm_forma_cobro ORDER BY descripcion';
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				$indice = array();
				while($nombre = mysql_fetch_array($resp))
				{
					$indice[$nombre['forma_cobro']] = count($nombres);
					$nombres[] = $nombre['descripcion'];
					$valores[] = 0;
					if($compara_historico)
					{
						$valores_anio_en_curso[] = 0;
						$valores_12_meses[] = 0;
					}
				}
				$max_temp = $this->max_por_grafico;
				$this->max_por_grafico = count($nombres)+1;
			}

			foreach($datos as $k => $v)
				if(is_array($v))	// Solo tomamos en cuenta los valores.
				{
					if($campo_desglose=='forma_cobro')
					{
						if(!isset($indice[$v['label']]))	// Ignorar los trabajos que no están asociados a cobros.
							continue;
						$valores[$indice[$v['label']]] = $v['valor'];
						if($tipo_dato_comparado)
							$valores_comparados[$indice[$v['label']]] = $datos_comparados[$k]['valor'];

						if($compara_historico)
						{
							$valores_anio_en_curso[$indice[$v['label']]] = $datos_anio_en_curso[$k]['valor']/($tipo_dato=='rentabilidad'?1:$this->fecha_mes);
							$valores_12_meses[$indice[$v['label']]] = $datos_12_meses[$k]['valor']/($tipo_dato=='rentabilidad'?1:12);
						}
					}
					else
					{
						$valores[] = $v['valor'];
						$nombres[] = $v['label'];
						if($tipo_dato_comparado)
							$valores_comparados[] = $datos_comparados[$k]['valor'];

						if($compara_historico)
						{
							$valores_anio_en_curso[] = $datos_anio_en_curso[$k]['valor']/($tipo_dato=='rentabilidad'?1:$this->fecha_mes);
							$valores_12_meses[] = $datos_12_meses[$k]['valor']/($tipo_dato=='rentabilidad'?1:12);
						}
					}
				}

			if($compara_historico && $valores_anio_en_curso && $valores_12_meses && $campo_desglose!='forma_cobro')
				array_multisort($valores, SORT_DESC, $valores_anio_en_curso, $valores_12_meses, $nombres);
			elseif($campo_desglose!='forma_cobro')
				array_multisort($valores, SORT_DESC, $nombres);
			elseif($tipo_dato_comparado && $valores_comparados)
				array_multisort($valores, SORT_DESC, $nombres, $valores_comparados);
			// Agregar el promedio al final.
			if($compara_historico && $valores_anio_en_curso && $valores_12_meses)
			{
				$valores_anio_en_curso[] = count($valores_anio_en_curso)?array_sum($valores_anio_en_curso)/count($valores_anio_en_curso):0;
				$valores_12_meses[] = count($valores_12_meses)?array_sum($valores_12_meses)/count($valores_12_meses):0;
			}
			if($tipo_dato_comparado && $valores_comparados)
				$valores_comparados[] = count($valores_comparados)?array_sum($valores_comparados)/count($valores_comparados):0;
			$valores[] = count($valores)?round(array_sum($valores)/count($valores), $cifras_decimales):0;
			$nombres[] = __('Promedio');
			// Gráfico usado solo para calcular escalas.
			$grafico = new Grafico('');
			$grafico->layer->AddDataSet($valores);
			if($compara_historico)
			{
				$grafico->layer->AddDataSet($valores_anio_en_curso);
				$grafico->layer->AddDataSet($valores_12_meses);
			}
			$grafico->layout();
			$y_max = $grafico->yAxis->getMaxValue();
			$y_min = $grafico->yAxis->getMinValue()<0?$grafico->yAxis->getMinValue():0;
			unset($grafico);
			if($tipo_dato_comparado)
			{
				$grafico = new Grafico('');
				$grafico->layer->AddDataSet($valores_comparados);
				$grafico->layout();
				$y_max_2 = $grafico->yAxis->getMaxValue();
				$y_min_2 = $grafico->yAxis->getMinValue()<0?$grafico->yAxis->getMinValue():0;
				unset($grafico);
			}


			$i = 0;
			$partes = count($valores)%$this->max_por_grafico?(int)(count($valores)/$this->max_por_grafico+1):count($valores)/$this->max_por_grafico;

			foreach($valores as $k => $v)
			{
				$valores_temp[] = $v;
				$nombres_temp[] = $nombres[$k];
				if($tipo_dato_comparado)
					$valores_comparados_temp[] = $valores_comparados[$k];
				if($compara_historico)
				{
					$valores_anio_en_curso_temp[] = $valores_anio_en_curso[$k];
					$valores_12_meses_temp[] = $valores_12_meses[$k];
				}
				if(++$i%$this->max_por_grafico==0)
				{
					$grafico = new Grafico($this->titulos[$tipo_dato].($tipo_dato_comparado?' - '.$this->titulos[$tipo_dato_comparado]:'').(count($valores)>$this->max_por_grafico?' ('.(int)($i/$this->max_por_grafico).' '.__('de')." $partes)":''), true, Side, $tipo_dato_comparado);

					$grafico->Ejes('', $this->ejes_y[$tipo_dato], $tipo_dato_comparado?$this->ejes_y[$tipo_dato_comparado]:'');
					$grafico->Labels($nombres_temp, true);
					if($compara_historico)
					{
						$grafico->layer->setAggregateLabelStyle('arialbd.ttf', 0);
						$grafico->layer->AddDataSet($valores_12_meses_temp, 0x0044ff, __('Promedio mensual 12 meses'));
						$grafico->layer->AddDataSet($valores_anio_en_curso_temp, 0xff0044, __('Promedio mensual año actual'));
						$grafico->layer->AddDataGroup(__('Mes actual'));
						$grafico->layer->AddDataSet($valores_temp, 0x00ff44, __('Mes actual'));

						for($j=0; $j<$this->max_por_grafico; ++$j)
							$grafico->layer->addCustomGroupLabel(1, $j, "{value}", 'arialbd.ttf', 16);
					}
					else
						$grafico->layer->AddDataSet($valores_temp);
					$grafico->yAxis->setLinearScale($y_min, $y_max);

					if($tipo_dato_comparado)
					{
						$grafico->yAxis->setColors(0x000000, 0x000000, 0xD00000);
						$grafico->yAxis2->setColors(0x000000, 0x000000, 0x0000D0);
						$grafico->yAxis2->setLinearScale($y_min_2, $y_max_2);

						$dataObj = $grafico->layer_adelante->addDataSet($valores_comparados_temp, 0x0000d0);
						$dataObj->setDataSymbol(DiamondSymbol, 23, 0x3030f0);
					}

					if(++$this->num_grafico==count($this->pos_grafico_y))
						$this->addPage();

					$this->MemImage($grafico->makeChart2(PNG), 24, $this->pos_grafico_y[$this->num_grafico], 150);
					if($tipo_dato=='valor_cobrado')
						$this->agregarNota(170, $this->pos_grafico_y[$this->num_grafico], 'valor_cobrado');
					$nombres_temp = array();
					$valores_temp = array();
					$valores_comparados_temp = array();
					$valores_anio_en_curso_temp = array();
					$valores_12_meses_temp = array();
				}
			}

			if($i%$this->max_por_grafico)
			{
				while(count($valores_temp)<$this->max_por_grafico)
					$valores_temp[] = NoValue;

				$grafico = new Grafico($this->titulos[$tipo_dato].($tipo_dato_comparado?' - '.$this->titulos[$tipo_dato_comparado]:'').($i>$this->max_por_grafico?' ('.(int)(count($valores)/$this->max_por_grafico+1).' '.__('de').' '.(int)(count($valores)/$this->max_por_grafico+1).')':''), true, Side, $tipo_dato_comparado);
				$grafico->Ejes('', $this->ejes_y[$tipo_dato], $tipo_dato_comparado?$this->ejes_y[$tipo_dato_comparado]:'');
					$grafico->Labels($nombres_temp, true);
				if($compara_historico)
				{
					$grafico->layer->setAggregateLabelStyle('arialbd.ttf', 0);
					$grafico->layer->AddDataSet($valores_12_meses_temp, 0x0044ff, __('Promedio mensual 12 meses'));
					$grafico->layer->AddDataSet($valores_anio_en_curso_temp, 0xff0044, __('Promedio mensual año actual'));
					$grafico->layer->AddDataGroup(__('Mes actual'));
					$grafico->layer->AddDataSet($valores_temp, 0x00ff44, __('Mes actual'));

					for($j=0; $j<$this->max_por_grafico; ++$j)
						$grafico->layer->addCustomGroupLabel(1, $j, "{value}", 'arialbd.ttf', 16);
				}
				else
					$grafico->layer->AddDataSet($valores_temp);
				$grafico->yAxis->setLinearScale($y_min, $y_max);

				if($tipo_dato_comparado)
				{
					$grafico->yAxis->setColors(0x000000, 0x000000, 0xD00000);
					$grafico->yAxis2->setColors(0x000000, 0x000000, 0x0000D0);
					$grafico->yAxis2->setLinearScale($y_min_2, $y_max_2);

					$dataObj = $grafico->layer_adelante->addDataSet($valores_comparados_temp, 0x0000d0);
					$dataObj->setDataSymbol(DiamondSymbol, 23, 0x3030f0);
				}

				if(++$this->num_grafico==count($this->pos_grafico_y))
					$this->addPage();
				$this->MemImage($grafico->makeChart2(PNG), 24, $this->pos_grafico_y[$this->num_grafico], 150);
				if($tipo_dato=='valor_cobrado')
					$this->agregarNota(170, $this->pos_grafico_y[$this->num_grafico], 'valor_cobrado');
			}

			if($campo_desglose=='forma_cobro')
				$this->max_por_grafico = $max_temp;
			if($tipo_dato_comparado)
				$this->max_por_grafico += 2;
		}

		function addGraficoStackHoras($campo_desglose)
		{
			$valores_visibles = array();
			$valores_castigadas = array();
			$valores_no_cobrables = array();
			// Últimos 12 meses
			$fecha_desde = '01-'.($this->fecha_mes==12?'01-'.$this->fecha_anio:$this->fecha_mes+1 .'-'.($this->fecha_anio-1));
			$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$valores_visibles[] = $this->getDatos('horas_visibles', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$valores_castigadas[] = $this->getDatos('horas_castigadas', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$valores_no_cobrables[] = $this->getDatos('horas_no_cobrables', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			// Año en curso
			$fecha_desde = '01-01-'.$this->fecha_anio;
			$valores_visibles[] = $this->getDatos('horas_visibles', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$valores_castigadas[] = $this->getDatos('horas_castigadas', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$valores_no_cobrables[] = $this->getDatos('horas_no_cobrables', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			// Mes actual
			$fecha_desde = '01-'.$this->fecha_mes.'-'.$this->fecha_anio;
			$valores_visibles[] = $this->getDatos('horas_visibles', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$valores_castigadas[] = $this->getDatos('horas_castigadas', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$valores_no_cobrables[] = $this->getDatos('horas_no_cobrables', $fecha_desde, $fecha_hasta, true, $campo_desglose);

			$nombres = array();
			$datos_visibles = array();
			$datos_castigadas = array();
			$datos_no_cobrables = array();
			$total = array(); // Usado solo para ordenar los datos
			$divisores = array(12, $this->fecha_mes, 1);

			for($j=0; $j<3; ++$j)
				foreach($valores_visibles[$j] as $k => $v)
					if(is_array($v))
					{
						$datos_visibles[$j][] = $v['valor']/$divisores[$j];
						$datos_castigadas[$j][] = $valores_castigadas[$j][$k]['valor']/$divisores[$j];
						$datos_no_cobrables[$j][] = $valores_no_cobrables[$j][$k]['valor']/$divisores[$j];
						$total[$j][] += $v['valor']/$divisores[$j] + $valores_castigadas[$j][$k]['valor']/$divisores[$j] + $valores_no_cobrables[$j][$k]['valor']/$divisores[$j];
						$nombres[$j][] = $v['label'];
					}

			// Normalizar arreglos para que mantengan orden y tamaño.
			// El arreglo con más datos es el de 12 meses, se mantiene igual.
			for($j=1; $j<3; ++$j)
			{
				$visibles_temp = array();
				$castigadas_temp = array();
				$no_cobrables_temp = array();
				$nombres_temp = array();
				$total_temp = array();
				foreach($datos_visibles[0] as $k => $v)
				{
					$existe = false;
					if(!empty($nombres[$j]))
					foreach($nombres[$j] as $kn => $nn)
						if($nn == $nombres[0][$k])
						{
							$visibles_temp[$k] = $datos_visibles[$j][$kn];
							$castigadas_temp[$k] = $datos_castigadas[$j][$kn];
							$no_cobrables_temp[$k] = $datos_no_cobrables[$j][$kn];
							$nombres_temp[$k] = $nn;
							$total_temp[$k] = $total[$j][$kn];
							$existe = true;
							break;
						}
					if(!$existe)
					{
						$visibles_temp[$k] = 0;
						$castigadas_temp[$k] = 0;
						$no_cobrables_temp[$k] = 0;
						$nombres_temp[$k] = $nombres[0][$k];
						$total_temp[$k] = 0;
					}
				}
				$datos_visibles[$j] = $visibles_temp;
				$datos_castigadas[$j] = $castigadas_temp;
				$datos_no_cobrables[$j] = $no_cobrables_temp;
				$nombres[$j] = $nombres_temp;
				$total[$j] = $total_temp;
			}

			array_multisort($total[2], SORT_DESC, $datos_visibles[2], SORT_DESC, $datos_castigadas[2], $datos_no_cobrables[2], $nombres[2], $datos_visibles[1], SORT_DESC, $datos_castigadas[1], $datos_no_cobrables[1], $nombres[1], $datos_visibles[0], SORT_DESC, $datos_castigadas[0], $datos_no_cobrables[0], $nombres[0]);
			unset($total);
			// Agregar el promedio al final.
			for($j=0; $j<3; ++$j)
			{
				$datos_visibles[$j][] = count($datos_visibles[$j])?round(array_sum($datos_visibles[$j])/count($datos_visibles[$j]), 2):0;
				$datos_castigadas[$j][] = count($datos_castigadas[$j])?round(array_sum($datos_castigadas[$j])/count($datos_castigadas[$j]), 2):0;
				$datos_no_cobrables[$j][] = count($datos_no_cobrables[$j])?round(array_sum($datos_no_cobrables[$j])/count($datos_no_cobrables[$j]), 2):0;
				$nombres[$j][] = __('Promedio');
			}

			$largo = $this->max_por_grafico*(int)(1+count($datos_visibles[0])/$this->max_por_grafico);
			for($j=0; $j<3; ++$j)
			{
				$datos_visibles[$j] = array_pad($datos_visibles[$j], $largo, 0);
				$datos_castigadas[$j] = array_pad($datos_castigadas[$j], $largo, 0);
				$datos_no_cobrables[$j] = array_pad($datos_no_cobrables[$j], $largo, 0);
			}

			// Gráfico usado solo para calcular escalas.
			$grafico = new Grafico('', true, Stack);
			for($j=0; $j<3; ++$j)
			{
				$grafico->layer->AddDataGroup('a');
				$grafico->layer->AddDataSet($datos_visibles[$j]);
				$grafico->layer->AddDataSet($datos_castigadas[$j]);
				$grafico->layer->AddDataSet($datos_no_cobrables[$j]);
			}
			$grafico->layout();
			$y_max = $grafico->yAxis->getMaxValue();
			$y_min = $grafico->yAxis->getMinValue();
			unset($grafico);

			$nombre_grupo = array(__('Promedio mensual 12 meses'), __('Promedio mensual año actual'), __('Mes actual'));
			$color_trabajadas = array(0x0044ff, 0xff0044, 0x00ff44);
			$color_castigadas = 0x00FFBB;
			$color_no_cobrables = 0xBBFF00;
			$periodo = array(__('Últimos 12 meses'), __('Año actual'), __('Mes actual'));

			//No se muestran las barras:
			for($w = 0; $w < count($datos_visibles[0]); $w++)
			{
				//-cuyo label sea null (ultima hoja de grafico)
				if($nombres[0][$w] == null)
				{
					for($j=0; $j<3; ++$j)
					{						
						$datos_visibles[$j][$w] = NoValue;
						$datos_castigadas[$j][$w] = NoValue;
						$datos_no_cobrables[$j][$w] = NoValue;
					}
				}
				else 
				//-cuyo valor sea 0
				{
						for($j=0; $j<3; ++$j)
						{
							if($datos_castigadas[$j][$w] == 0)
								$datos_castigadas[$j][$w] = NoValue;

							if($datos_no_cobrables[$j][$w] == 0)
								$datos_no_cobrables[$j][$w] = NoValue;
						}
				}
			}

			for($i=0; $i<count($datos_visibles[0]); $i+=$this->max_por_grafico)
			{
				$grafico = new Grafico(__('Distribución de horas trabajadas').' ('.($i/$this->max_por_grafico+1).' '.__('de').' '.($largo/$this->max_por_grafico).')', true, Stack);
				$grafico->SetNumberFormat(',');
				$grafico->Ejes('', __('Horas'));
				$grafico->Labels(array_slice($nombres[0], $i, $this->max_por_grafico), true);
				for($j=0; $j<3; ++$j)
				{
					$grafico->layer->AddDataGroup($nombre_grupo[$j]);
					$grafico->layer->AddDataSet(array_slice($datos_visibles[$j], $i, $this->max_por_grafico), $color_trabajadas[$j], $periodo[$j]);
					$grafico->layer->AddDataSet(array_slice($datos_castigadas[$j], $i, $this->max_por_grafico), $color_castigadas, $j==2?__('Castigadas'):'');
					$grafico->layer->AddDataSet(array_slice($datos_no_cobrables[$j], $i, $this->max_por_grafico), $color_no_cobrables, $j==2?__('No cobrables'):'');
				}
				for($j=0; $j<$this->max_por_grafico; ++$j)
				{
					$labels_especiales = array('','','');
					$mostrar_vertical = true;
					for($k=0;$k<3;$k++)
					{

						$labels_especiales[$k] = '<*size=14*>';
						if($datos_castigadas[$k][$i+$j] != NoValue)
							$labels_especiales[$k] .= '<*block,angle=90*><*color='.$color_castigadas.'*> '.number_format($datos_castigadas[$k][$i+$j],0,',','.').'<*/*><*br*>';
						$labels_especiales[$k] .='<*block ,angle=90*><*color=000000*> '.number_format($datos_visibles[$k][$i+$j],0,',','.').'<*/*>';

						
						if( ceil($datos_castigadas[$k][$i+$j]) < 1)
							$mostrar_vertical = false;
					}
					$grafico->layer->addCustomGroupLabel(0, $j, ' ');
					$grafico->layer->addCustomGroupLabel(1, $j, ' ');
					if($mostrar_vertical)
						$grafico->layer->addCustomGroupLabel(2, $j, $labels_especiales[2], 'arialbd.ttf');
					else
						$grafico->layer->addCustomGroupLabel(2, $j, '{value}', 'arialbd.ttf', 16);
				}
				$grafico->yAxis->setLinearScale($y_min, $y_max);

				if(++$this->num_grafico==count($this->pos_grafico_y))
					$this->addPage();

				$this->MemImage($grafico->makeChart2(PNG), 24, $this->pos_grafico_y[$this->num_grafico], 150);
			}
		}


		function addGraficoStackValores($campo_desglose)
		{
			$valores_visibles = array();
			$valores_castigadas = array();
			$valores_no_cobrables = array();
			// Últimos 12 meses
			$fecha_desde = '01-'.($this->fecha_mes==12?'01-'.$this->fecha_anio:$this->fecha_mes+1 .'-'.($this->fecha_anio-1));
			$fecha_hasta = $this->largo_mes[$this->fecha_mes-1].'-'.$this->fecha_mes.'-'.$this->fecha_anio;


			$dato_base = array(array(),array());
			$dato_base[0][0] = $this->getDatos('valor_cobrado', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$dato_base[0][1] = $this->getDatos('valor_por_cobrar', $fecha_desde, $fecha_hasta, true, $campo_desglose);

			
			// Año en curso
			$fecha_desde = '01-01-'.$this->fecha_anio;

			$dato_base[1][0] = $this->getDatos('valor_cobrado', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$dato_base[1][1] = $this->getDatos('valor_por_cobrar', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			
			// Mes actual
			$fecha_desde = '01-'.$this->fecha_mes.'-'.$this->fecha_anio;

			$dato_base[2][0] = $this->getDatos('valor_cobrado', $fecha_desde, $fecha_hasta, true, $campo_desglose);
			$dato_base[2][1] = $this->getDatos('valor_por_cobrar', $fecha_desde, $fecha_hasta, true, $campo_desglose);

			//Debo rellenar todos los 'agujeros': columnas que no existen en una barra se llenarán como 0. 
			//toda_columna tendrá un arreglo de las columnas de cada barra con valor 0. Luego las barras reciben estas columnas. 
			$toda_columna = array('total_divisor'=>0,'total'=>0,'barras'=>0);
			for($i=0;$i<3;$i++)
				for($j=0;$j<2;$j++)
					$toda_columna = Reporte::fixBar($toda_columna,$dato_base[$i][$j]);
			for($i=0;$i<3;$i++)
				for($j=0;$j<2;$j++)
					$dato_base[$i][$j] = Reporte::fixBar($dato_base[$i][$j],$toda_columna);

			//El rellenar los 'agujeros' dejó el orden de los labels-valores diferentes en los arreglos. Debo determinar el orden de los labels y luego ordenar los valores de acuerdo a ese orden. Utilizaré $toda_columna para guardar el valor a ordernar (mes actual: valor_cobrado + valor_por_cobrar)
			foreach($toda_columna as $k => $v)
				if(is_array($v))
					$toda_columna[$k]['valor'] = $dato_base[2][0][$k]['valor'] + $dato_base[2][1][$k]['valor']; 
			
			//Se ordena por el valor de $toda_columna
			arsort($toda_columna);

			//Se replica el orden de $toda_columna en los datos.
			foreach($toda_columna as $k => $v)
				if(is_array($v))
					for($i=0;$i<3;$i++)
						for($j=0;$j<2;$j++)
							$dato_ordenado[$i][$j][$k] = $dato_base[$i][$j][$k];
				

			for($i=0;$i<3;$i++)
			{
				$valores[] = $dato_ordenado[$i][0];
				$valores_por_cobrar[] = $dato_ordenado[$i][1];
			}

			$nombres = array();
			$datos = array();
			$datos_por_cobrar = array();
			
			$total = array(); // Usado solo para ordenar los datos
			$divisores = array(12, $this->fecha_mes, 1);

			for($j=0; $j<3; ++$j)
				foreach($valores[$j] as $k => $v)
					if(is_array($v))
					{
						$dato_por_cobrar = $valores_por_cobrar[$j][$k]['valor']/$divisores[$j];

						$datos[$j][] = $v['valor']/$divisores[$j]; //NoValue ;
						$datos_por_cobrar[$j][] = $dato_por_cobrar;
						
						$total[$j][] += $v['valor']/$divisores[$j] + $valores_por_cobrar[$j][$k]['valor']/$divisores[$j]; 
						$nombres[$j][] = $v['label'];
					}

			// Agregar el promedio al final.
			for($j=0; $j<3; ++$j)
			{
				$datos[$j][] = count($datos[$j])?round(array_sum($datos[$j])/count($datos[$j]), 2):0;
				$datos_por_cobrar[$j][] = count($datos_por_cobrar[$j])?round(array_sum($datos_por_cobrar[$j])/count($datos_por_cobrar[$j]), 2):0;
				$nombres[$j][] = __('Promedio');
			}

			$largo = $this->max_por_grafico*(int)(1+count($datos[0])/$this->max_por_grafico);
			for($j=0; $j<3; ++$j)
			{
				$datos[$j] = array_pad($datos[$j], $largo, 0);
				$datos_por_cobrar[$j] = array_pad($datos_por_cobrar[$j], $largo, 0);
			}

			// Gráfico usado solo para calcular escalas.
			$grafico = new Grafico('', true, Stack);
			for($j=0; $j<3; ++$j)
			{
				$grafico->layer->AddDataGroup('a');
				$grafico->layer->AddDataSet($datos[$j]);
				$grafico->layer->AddDataSet($datos_por_cobrar[$j]);
			}
			$grafico->layout();
			$y_max = $grafico->yAxis->getMaxValue();
			$y_min = $grafico->yAxis->getMinValue();
			unset($grafico);

			$nombre_grupo = array(__('Promedio mensual 12 meses'), __('Promedio mensual año actual'), __('Mes actual'));
			$color = array(0x0044ff, 0xff0044, 0x00ff44);
			$color_por_cobrar = 0xCCBBff;
			$periodo = array(__('Últimos 12 meses'), __('Año actual'), __('Mes actual'));

			//No se muestran las barras:
			for($w = 0; $w < count($datos[0]); $w++)
			{
				//-cuyo label sea null (ultima hoja de grafico)
				if($nombres[0][$w] == null)
				{
					for($j=0; $j<3; ++$j)
					{						
						$datos[$j][$w] = NoValue;
						$datos_por_cobrar[$j][$w] = NoValue;
					}
				}
				else 
				//-cuyo valor sea 0
				{
						for($j=0; $j<3; ++$j)
							if($datos_por_cobrar[$j][$w] == 0)
								$datos_por_cobrar[$j][$w] = NoValue;
				}
			}
				

			for($i=0; $i<count($datos[0]); $i+=$this->max_por_grafico)
			{
				$grafico = new Grafico(__('Valor cobrado').' ('.($i/$this->max_por_grafico+1).' '.__('de').' '.($largo/$this->max_por_grafico).')', true, Stack);
				$grafico->SetNumberFormat('.',',');
				$grafico->Ejes('', $this->ejes_y[$tipo_dato]);
				$grafico->Labels(array_slice($nombres[0], $i, $this->max_por_grafico), true);

				for($j=0; $j<3; ++$j)
				{
					$grafico->layer->AddDataGroup($nombre_grupo[$j]);
					$grafico->layer->AddDataSet(array_slice($datos[$j], $i, $this->max_por_grafico), $color[$j], $periodo[$j]);
					$grafico->layer->AddDataSet(array_slice($datos_por_cobrar[$j], $i, $this->max_por_grafico), $color_por_cobrar, $j==2?__('por Cobrar'):'');
				}
				for($j=0; $j<$this->max_por_grafico; ++$j)
				{
					$labels_especiales = array('','','');
					for($k=0;$k<3;$k++)
					{

						$labels_especiales[$k] = '<*size=14*>';
						if($datos_por_cobrar[$k][$i+$j] != NoValue)
							$labels_especiales[$k] .= '<*block,angle=90*><*color=8855BB*> '.number_format($datos_por_cobrar[$k][$i+$j],0,',','.').'<*/*><*br*>';
						$labels_especiales[$k] .='<*block ,angle=90*><*color=000000*> '.number_format($datos[$k][$i+$j],0,',','.').'<*/*>';
					}

					$grafico->layer->addCustomGroupLabel(0, $j, ' ');
					$grafico->layer->addCustomGroupLabel(1, $j, ' ');
					$grafico->layer->addCustomGroupLabel(2, $j, $labels_especiales[2], 'arialbd.ttf');
				}
				$grafico->yAxis->setLinearScale($y_min, $y_max);
				$grafico->yAxis->setTopMargin(40);

				if(++$this->num_grafico==count($this->pos_grafico_y))
					$this->addPage();

				$this->MemImage($grafico->makeChart2(PNG), 24, $this->pos_grafico_y[$this->num_grafico], 150);
			}
		}
	}
?>
