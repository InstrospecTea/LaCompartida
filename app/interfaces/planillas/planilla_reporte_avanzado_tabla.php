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

	$agrupadores = explode('-',$vista);
	
	if(!$filtros_check)
	{
		$fecha_ultimo_dia = date('t',mktime(0,0,0,$fecha_mes,5,$fecha_anio));
		$fecha_m = ''.$fecha_mes;
		$fecha_fin = $fecha_ultimo_dia."-".$fecha_m."-".$fecha_anio;
		$fecha_ini = "01-".$fecha_m."-".$fecha_anio;	
	}
	else
	{
		$clientes = $clientesF;
		$usuarios = $usuariosF;

		if($area_y_categoria)
		{
			$areas_usuario = $areas;
			$categorias_usuario = $categorias;
		}
	}

	$titulo_reporte = __('Resumen - ').' '.__($tipo_dato).' '.__('en vista por').' '.__($agrupadores[0]);


	$reporte = new Reporte($sesion);

	if($clientes)
		foreach($clientes as $cliente)
			if($cliente)
				$reporte->addFiltro('cliente','codigo_cliente',$cliente);

	if($usuarios)
		foreach($usuarios as $usuario)
			if($usuario)
				$reporte->addFiltro('usuario','id_usuario',$usuario);

	if($tipos_asunto)
		foreach($tipos_asunto as $tipo)
			if($tipo)
				$reporte->addFiltro('asunto','id_tipo_asunto',$tipo);
		
	if($areas_asunto)
		foreach($areas_asunto as $area)
			if($area)
				$reporte->addFiltro('asunto','id_area_proyecto',$area);

	if($areas_usuario)
			foreach($areas_usuario as $area_usuario)
				if($area_usuario)
					$reporte->addFiltro('usuario','id_area_usuario',$area_usuario);

		if($categorias_usuario)
			foreach($categorias_usuario as $categoria_usuario)
				if($categoria_usuario)
					$reporte->addFiltro('usuario','id_categoria_usuario',$categoria_usuario);

	$reporte->addRangoFecha($fecha_ini,$fecha_fin);
	if($campo_fecha)
		$reporte->setCampoFecha($campo_fecha);

	$reporte->setVista($vista);
	$reporte->setTipoDato($tipo_dato);
	$reporte->id_moneda = $id_moneda;

	$reporte->Query();

	$r = $reporte->toCross();


    $wb = new Spreadsheet_Excel_Writer();

    $wb->send("Planilla Horas por Cliente.xls");

	/* FORMATOS */
    $wb->setCustomColor ( 35, 220, 255, 220 );
    $wb->setCustomColor ( 36, 255, 255, 220 );
		$encabezado =& $wb->addFormat(array('Size' => 12,
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
		$txt_rojo =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'red'));
		$txt_rojo->setTextWrap();
		
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

		$horas_minutos =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$horas_minutos->setNumFormat("[hh]:mm");

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
	

	/* TITULOS */
   $ws1 =& $wb->addWorksheet(__('Reportes'));
   $ws1->setInputEncoding('utf-8');
   $ws1->fitToPages(1,0);
   $ws1->setZoom(75);

    $ws1->setColumn( $fila_inicial, $fila_inicial,  25.00);

	$i = 1;

	if(is_array($r['labels']))
		foreach($r['labels_col'] as $no_usado)
		{
			$ws1->setColumn( $fila_inicial, $fila_inicial+$i,  25.00);
			$i++;
		}
	
	$fila = 1;

	$ws1->write($fila,1,$titulo_reporte, $titulo);
	$ws1->write($fila,2,'');
	$ws1->write($fila,3,'');
	$ws1->mergeCells($fila,1,$fila,3);
	
	$fila += 1;
	$ws1->write($fila, 0, __('PERIODO RESUMEN').":", $titulo);

    $ws1->write($fila,1,$fecha_ini." ".__("al")." ".$fecha_fin, $titulo);
	$ws1->write($fila,2,'');
	$ws1->mergeCells($fila,1,$fila,2);
	
	$fila += 1;

    $hoy = date("d-m-Y");
	
	$ws1->write($fila, 0, __('FECHA REPORTE'), $titulo);
	
	$ws1->write($fila, 1, $hoy, $titulo);
	$ws1->write($fila, 2, '');	
	$ws1->mergeCells($fila, 1, $fila, 2);
     
    $columna = 0;
	$fila+= 3;


	function dato($fila,$columna,$valor)
	{
		global $ws1;
		global $numeros;
		global $horas_minutos;
		global $tipo_dato;
		global $sesion;
		global $tipo_dato_comparado;
		global $txt_rojo;
		if($valor === '99999!*')
		{
			$ws1->write($fila,$columna,'99999!*',$txt_rojo);
			$ws1->writeNote($fila,$columna,__("Valor Indeterminado: denominador de fórmula es 0."));
		}
		else
		{
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) ||  ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )  )  && (strpos($tipo_dato,"oras_") || strpos($tipo_dato_comparado,"oras_")))
				$ws1->writeNumber($fila,$columna,Reporte::FormatoValor($sesion,$valor,$tipo_dato,"excel"),$horas_minutos);
			else
				$ws1->writeNumber($fila,$columna,$valor,$numeros);
		}
	}

	function texto($fila,$columna,$valor)
	{
		global $ws1;
		global $txt_opcion;
		if($valor == __('Indefinido'))
		{
			$ws1->write($fila, $columna,$valor,$txt_opcion);
			$ws1->writeNote($fila,$columna,__("Agrupador no existe, o no está definido para estos datos."));
		}
		else
			$ws1->write($fila,$columna,$valor,$txt_opcion); 
	}


	if(is_array($r['labels']))
	{

		//LABELS
		$fil = $fila;
		foreach($r['labels'] as $id => $nombre)
		{
			texto($fil,$col,$nombre['nombre']);
			$fil++;
		}
		$ws1->write($fil,$columna,'TOTAL',$txt_derecha);

		//ENCABEZADOS
		$col = $columna+1;
		foreach($r['labels_col'] as $id_col => $nombre_col)
		{
			texto($fila-1,$col,$nombre_col['nombre']);
			$col++;
		}
		$ws1->write($fila-1,$col,'TOTAL',$txt_opcion);

		//CELDAS
		$fil = $fila;
		foreach($r['labels'] as $id => $nombre)
		{
			$col = $columna + 1;
			foreach($r['labels_col'] as $id_col => $nombre_col)
			{
				if(isset($r['celdas'][$id][$id_col]['valor']))
				{
					dato($fil,$col,$r['celdas'][$id][$id_col]['valor']);
				}
				else
				{
					$ws1->write($fil,$col,'',$txt_opcion);
				}
				$col++;
			}
			//TOTAL_COLUMNA:
			dato($fil,$col,$r['labels'][$id]['total']);
			$fil++;
		}

		//TOTAL_FILAS
		$col = $columna + 1;
		foreach($r['labels_col'] as $id_col => $nombre)
		{
			dato($fil,$col,$r['labels_col'][$id_col]['total']);
			$col++;
		}
		dato($fil,$col,$r['total']);
	}
    $wb->close();
?>
