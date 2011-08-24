<?
    require_once 'Spreadsheet/Excel/Writer.php';
    require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../app/classes/Reporte.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';

    $sesion = new Sesion( array('REP') );

    $pagina = new Pagina( $sesion );

	if(!is_array($usuarios))	
		$usuarios = array($usuarios);
	
	if(!is_array($clientes))	
		$clientes = array($clientes);

	$vista_cliente = "glosa_cliente-profesional-profesional-profesional-profesional-profesional";
	$vista_empleado = "profesional-glosa_cliente-glosa_cliente-glosa_cliente-glosa_cliente-glosa_cliente"; 
	$vista_asunto = "glosa_cliente-codigo_asunto-glosa_asunto-glosa_asunto-glosa_asunto-glosa_asunto";

	switch($tipo_reporte)
	{
		case "hh_por_cliente":
			$vista = $vista_cliente;
			$titulo_reporte = __('Reporte de Horas por Cliente');
			break;
		case "hh_por_empleado":
			$vista = $vista_empleado;
			$titulo_reporte = __('Reporte de Horas por Profesional');
			break;
		case "hh_por_asunto":
			$vista = $vista_asunto;
			$titulo_reporte = __('Reporte de Horas por Asunto');
			break;
	}
	$agrupadores = explode('-',$vista);

	$datos = array("horas_trabajadas","horas_cobrables","horas_no_cobrables","horas_castigadas","horas_cobradas","horas_por_cobrar","horas_pagadas","horas_por_pagar");
	$reporte = array();


	foreach($datos as $dato)
	{
		$reporte[$dato] = new Reporte($sesion);

		foreach($clientes as $cliente)
			if($cliente)
				$reporte[$dato]->addFiltro('cliente','codigo_cliente',$cliente);

		foreach($usuarios as $usuario)
			if($usuario)
				$reporte[$dato]->addFiltro('trabajo','id_usuario',$usuario);
		
		$reporte[$dato]->addRangoFecha($fecha_ini,$fecha_fin);
		$reporte[$dato]->setVista($vista);


		$reporte[$dato]->setTipoDato($dato);
		$reporte[$dato]->Query();


		if($vista == $vista_empleado || $vista == $vista_cliente)
			$resultado[$dato] = $reporte[$dato]->toBars();
		else if($vista == $vista_asunto)
			$resultado[$dato] = $reporte[$dato]->toArray();
		else
			echo "error";
	}	

    $wb = new Spreadsheet_Excel_Writer();

    $wb->send("Planilla Horas General.xls");

	/* FORMATOS */
    $wb->setCustomColor ( 35, 220, 255, 220 );
    $wb->setCustomColor ( 36, 255, 255, 220 );
		$encabezado =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'FgColor' => '35',
									'underline'=>1,
									'Color' => 'black'));
		$titulo =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));
		$txt_opcion =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black'));
		$txt_opcion->setTextWrap();
		
		$txt_valor =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$txt_valor->setTextWrap();
		
		$txt_derecha =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$txt_derecha->setTextWrap();
		
		$fecha =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black'));
		$fecha->setTextWrap();
		
		$numeros =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$numeros->setNumFormat("0");
									
		$titulo_filas =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));
		$formato_moneda =& $wb->addFormat(array('Size' => 11,
										'VAlign' => 'top',
										'Align' => 'right',
										'Border' => 1,
										'Color' => 'black'));
		$formato_moneda->setNumFormat("#,##0.00");
	




   $ws1 =& $wb->addWorksheet(__('Reportes'));
   $ws1->setInputEncoding('utf-8');
   $ws1->fitToPages(1,0);
   $ws1->setZoom(75);

   /* TITULOS */

    $ws1->setColumn( $fila_inicial, $fila_inicial,  25.00);
	if($vista == "asunto")
		$ws1->setColumn( $fila_inicial, $fila_inicial+1,  12.00);
	else
		$ws1->setColumn( $fila_inicial, $fila_inicial+1,  25.00);
    $ws1->setColumn( $fila_inicial, $fila_inicial+2,  25.00);

	$fila = 1;

	$ws1->mergeCells($fila,1,$fila,3);
	$ws1->write($fila,1,$titulo_reporte,$titulo);

	$fila += 2;  

	$ws1->write($fila, 0, __('PERIODO RESUMEN').":", $titulo);
    $ws1->mergeCells($fila,1,$fila,2);
	$ws1->write($fila,1,$fecha_ini." ".__("al")." ".$fecha_fin, $titulo);
	$fila += 1;

    $hoy = date("d-m-Y");
    $ws1->write($fila, 0, __('FECHA REPORTE'), $titulo);
	$ws1->mergeCells($fila, 1, $fila, 2);
    $ws1->write($fila, 1, $hoy, $titulo);
	for($x=2;$x<3;$x++)
        $ws1->write($fila, $x, '', $titulo);

    $fila += 2;
    $columna = 0;

	$fila+= 2;

	
	//VISTA cliente y profesional usan Arreglo de Barras.
	if($vista == $vista_empleado || $vista == $vista_cliente)
	{
		foreach($datos as $col => $dato)
		{
			$resultado[$dato]= Reporte::fixBar($resultado[$dato],$resultado['horas_trabajadas']);
			ksort($resultado[$dato]);
		}
		//TITULOS
		$ws1->write($fila-1,$columna,__($agrupadores[0]), $encabezado);
		foreach($datos as $col => $dato)
		{
			$ws1->setColumn($fila-1, $columna + 1 + $col,21.00);
			$ws1->write($fila-1, $columna + 1 + $col , __($dato), $encabezado);
		}
		//LABELS
		$i = 0;
		foreach($resultado['horas_trabajadas'] as $campo)
		{
			if(is_array($campo))		
			{
					$ws1->write($fila+$i, $columna, $campo['label'], $txt_opcion);
					$i++;
			}
		}
		//VALORES
		foreach($datos as $col => $dato)
		{
			$i=0;
			foreach($resultado[$dato] as $campo)
			{
				if(is_array($campo))		
				{	
					$ws1->write($fila+$i, $columna + 1 + $col , $campo['valor'], $numeros);
					$i++;
				}
			}
		}
		//TOTALES
		$ws1->write($fila+$i, $columna,"TOTAL", $txt_derecha);
		foreach($datos as $col => $dato)
		{
			$ws1->write($fila+$i, $columna+$col+1,$resultado[$dato]['total'], $txt_derecha);
		}
	}
	//VISTA asunto usa arreglo en forma de Planilla. Sólo llega hasta la tercera profundidad del arreglo.
	else if($vista == $vista_asunto)
	{
		function extender($fila,$columna,$filas)
		{
			global $ws1;
			for($f = 0; $f < $filas-1; $f++)
				$ws1->write($fila+$f, $columna,'',$txt_opcion);
			$ws1->mergeCells($fila, $columna, $fila+$filas-1, $columna);
		}

		foreach($datos as $col => $dato)
			$resultado[$dato]= Reporte::fixArray($resultado[$dato],$resultado['horas_trabajadas']);
		$ws1->write($fila-1,$columna,__("Cliente"), $encabezado);
		$ws1->mergeCells($fila-1,$columna+1,$fila-1,$columna+2);
		$ws1->write($fila-1,$columna+1,__("Asunto"), $encabezado);
		
		//TITULOS
		foreach($datos as $col => $dato)
		{
			$ws1->setColumn($fila-1, $columna + 3 + $col,21.00);
			$ws1->write($fila-1, $columna + 3 + $col , __($dato), $encabezado);
		}
		//CELDAS Y VALORES
		$fila_a = $fila;
		foreach($resultado['horas_trabajadas'] as $k_a => $a)
		{
			if(is_array($a))
			{
				$col_a = $columna;

				$ws1->write($fila_a, $col_a, $k_a, $txt_opcion);
				extender($fila_a, $col_a, $a['filas']);
				$col_a++;
	
				$fila_b = $fila_a;
				foreach($a as $k_b => $b)
				{
					if(is_array($b))
					{
						$col_b = $col_a;

						$ws1->write($fila_b, $col_b, $k_b, $txt_opcion);
						$col_b++;

						$fila_c = $fila_b;
						foreach($b as $k_c => $c)
						{
							if(is_array($c))
							{
								$col_c = $col_b;	
								$ws1->write($fila_c, $col_c, $k_c, $txt_opcion);
								$col_c++;

								foreach($datos as $dato)
								{
									$ws1->writeNumber($fila_c, $col_c, $resultado[$dato][$k_a][$k_b][$k_c]['valor'], $numeros);	
									$col_c++;
								}
								
								$fila_c += $c['filas'];
							}
						}
						$fila_b += $b['filas'];
					}
				}
				$fila_a += $a['filas'];
			}
		}
		$col = $columna+2;
		$ws1->write($fila_a,$col,"TOTAL",$txt_derecha);
		$col++;
		foreach($datos as $dato)
		{
			$ws1->writeNumber($fila_a, $col, $resultado[$dato]['total'], $numeros);	
			$col++;
		}
	}

	$wb->close();

?>
