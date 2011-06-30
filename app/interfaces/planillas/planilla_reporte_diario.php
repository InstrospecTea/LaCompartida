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

	$vista .= '-dia_reporte';
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

	$titulo_reporte = __('Resumen - ').' '.__($tipo_dato).' '.__(' en vista por').' '.__($agrupadores[0]);


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
	
	$simbolo_tipo_dato = Reporte::simboloTipoDato($tipo_dato,$sesion,$id_moneda);

	$reporte->Query();

	$r = $reporte->toCross();


    $wb = new Spreadsheet_Excel_Writer();

    $wb->send("Planilla Horas por Cliente.xls");

	/* FORMATOS */
    $wb->setCustomColor ( 35, 220, 255, 220 );
    $wb->setCustomColor ( 36, 255, 255, 220 );
    $wb->setCustomColor ( 37, 250, 12, 12);
    $wb->setCustomColor ( 38, 245, 245, 15 );
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
		$txt_derecha_bold =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Bold' => 1,
									'Color' => 'black'));
		
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
		$numeros_bold =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Bold' => 1,
									'Color' => 'black'));
		$numeros_bold->setNumFormat("0");

		$numeros_alerta =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'FgColor' => '38',
									'Color' => 'black'));
		$numeros_alerta->setNumFormat("0");

		$horas_minutos =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$horas_minutos->setNumFormat("[hh]:mm");

		$horas_minutos_bold =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Bold' => 1,
									'Color' => 'black'));
		$horas_minutos_bold->setNumFormat("[hh]:mm");

		$horas_minutos_alerta =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'FgColor' => '38',
									'Color' => 'black'));
		$horas_minutos_alerta->setNumFormat("[hh]:mm");

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

		$formato_fecha =& $wb->addFormat(array('Size' => 10,
									'Valign' => 'top',
									'Color' => 'black'));
		$formato_fecha->setNumFormat('DD-MMM;');

		$formato_nulo =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Border' => '1',
									'FgColor' => '37',
									'underline'=>1,
									'Color' => 'black'));
	

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
			$ws1->setColumn( $fila_inicial+$i, $fila_inicial+$i,  8.00);
			$i++;
		}
	
	$fila = 1;

	$ws1->write($fila,1,$titulo_reporte, $titulo);
	$ws1->write($fila,2,'');
	$ws1->write($fila,3,'');
	$ws1->write($fila,4,'');
	$ws1->write($fila,5,'');
	$ws1->write($fila,6,'');
	$ws1->write($fila,7,'');
	$ws1->mergeCells($fila,1,$fila,7);
	
	$fila += 1;
	$ws1->write($fila, 0, __('PERIODO RESUMEN').":", $titulo);
	$ws1->write($fila, 1, '');	
	$ws1->mergeCells($fila, 0, $fila, 1);

    $ws1->write($fila,2,$fecha_ini." ".__("al")." ".$fecha_fin, $titulo);
	$ws1->write($fila,3,'');
	$ws1->write($fila,4,'');
	$ws1->write($fila,5,'');
	$ws1->mergeCells($fila,2,$fila,5);
	
	$fila += 1;

    $hoy = date("d-m-Y");
	
	$ws1->write($fila, 0, __('FECHA REPORTE'), $titulo);
	$ws1->write($fila, 1, '');	
	$ws1->mergeCells($fila, 0, $fila, 1);
    

	$ws1->write($fila, 2, $hoy, $titulo);
	$ws1->write($fila, 3, '');	
	$ws1->write($fila, 4, '');	
	$ws1->mergeCells($fila, 2, $fila, 4);
     
    $columna = 0;
	$fila+= 3;


	//Retorna el timestamp excel de la fecha
	function fecha_valor($fecha)
	{
		$fecha = explode('-',$fecha);
		if(sizeof($fecha)!=3)
			return 0;
		// number of seconds in a day
		$seconds_in_a_day = 86400;
		// Unix timestamp to Excel date difference in seconds
		$ut_to_ed_diff = $seconds_in_a_day * 25569;
		$time = mktime(0,0,0,$fecha[1],$fecha[0],$fecha[2]);

		if($fecha[0] != '0000')
				return floor(($time + $ut_to_ed_diff) / $seconds_in_a_day);
		return 0;
	}
	//Imprime la Fecha en formato timestap excel
	function fecha_excel($worksheet, $fila, $col, $fecha, $formato, $default='00-00-00')
	{
		$valor = fecha_valor($fecha);
		if(!$valor)
			$valor = fecha_valor($default);
		$worksheet->writeNumber($fila, $col, $valor, $formato);
	}

	function fila_col($fila,$col)
	{
			return Spreadsheet_Excel_Writer::rowcolToCell($fila, $col);
	}

	function total($fila,$columna,$valor)
	{
		global $ws1;
		global $numeros_bold;
		global $horas_minutos_bold;
		global $tipo_dato;
		global $sesion;
		
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) ||  ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )  )  && (strpos($tipo_dato,"oras_") || strpos($tipo_dato_comparado,"oras_")))
				$ws1->write($fila,$columna,Reporte::FormatoValor($sesion,$valor,$tipo_dato,"excel"),$horas_minutos_bold);
			else
				$ws1->write($fila,$columna,$valor,$numeros_bold);
	}

	function dato($fila,$columna,$valor,$bold = false, $alerta = false)
	{
		global $ws1;
		global $numeros;
		global $horas_minutos;
		global $numeros_bold;
		global $horas_minutos_bold;
		global $numeros_alerta;
		global $horas_minutos_alerta;
		global $tipo_dato;
		global $sesion;
		global $tipo_dato_comparado;
		global $txt_rojo;
		global $formato_nulo;

		$hm = $bold? $horas_minutos_bold:$horas_minutos;
		$n = $bold? $numeros_bold:$numeros;

		$hm = $alerta? $horas_minutos_alerta:$hm;
		$n = $alerta? $numeros_alerta:$n;

		if($valor === '99999!*')
		{
			$ws1->write($fila,$columna,'99999!*',$txt_rojo);
			$ws1->writeNote($fila,$columna,__("Valor Indeterminado: denominador de fórmula es 0."));
		}
		else
		{
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'MostrarSoloMinutos') ) ||  ( method_exists('Conf','MostrarSoloMinutos') && Conf::MostrarSoloMinutos() )  )  && (strpos($tipo_dato,"oras_") || strpos($tipo_dato_comparado,"oras_")))
				$ws1->writeNumber($fila,$columna,Reporte::FormatoValor($sesion,$valor,$tipo_dato,"excel"),$hm);
			else
				$ws1->writeNumber($fila,$columna,$valor,$n);
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

	$fila++;
	if(is_array($r['labels']))
	{
		//LABELS
		$fil = $fila;
		$ws1->write($fil-1,$col,__($agrupadores[0]),$encabezado);	
		foreach($r['labels'] as $id => $nombre)
		{
			texto($fil,$col,$nombre['nombre']);
			$fil++;
		}
		$ws1->write($fil,$columna,'TOTAL',$txt_derecha_bold);

		//ENCABEZADOS
		$col = $columna+1;
		foreach($r['labels_col'] as $id_col => $nombre_col)
		{
			fecha_excel($ws1,$fila-2,$col,$nombre_col['nombre'],$formato_fecha);
			$ws1->write($fila-1,$col,$simbolo_tipo_dato,$encabezado);
			$col++;
		}
		$ws1->write($fila-1,$col,'TOTAL',$encabezado);

		//CELDAS
		$fil = $fila;
		foreach($r['labels'] as $id => $nombre)
		{
			$col = $columna + 1;
			foreach($r['labels_col'] as $id_col => $nombre_col)
			{
				if(isset($r['celdas'][$id][$id_col]['valor']))
				{
					if($agrupador[0] == 'profesional' && $tipo_dato == 'horas_trabajadas')
					{
						#Almacena el minimo valor sin alerta del usuario id.
						if(!isset($min[$id]))
						{
							$prof = new UsuarioExt($sesion);
							$prof->LoadId($id);
							if($prof->fields['restriccion_diario'])
								$min[$id] = $prof->fields['restriccion_diario'];
							else
								$min[$id] = 0;
						}
						if(!$min[$id] || $min[$id] <= $r['celdas'][$id][$id_col]['valor'])					
							dato($fil,$col,$r['celdas'][$id][$id_col]['valor']);
						else
							dato($fil,$col,$r['celdas'][$id][$id_col]['valor'],false,true);
					}
					else
						dato($fil,$col,$r['celdas'][$id][$id_col]['valor']);
				}
				else
				{
					$ws1->write($fil,$col,'',$formato_nulo);
				}
				$col++;
			}
			//TOTAL_COLUMNA:
			total($fil,$col,'=SUM('.fila_col($fil,1).':'.fila_col($fil, $col-1).')');
			$fil++;
		}

		//TOTAL_FILAS
		$col = $columna + 1;
		foreach($r['labels_col'] as $id_col => $nombre)
		{
			total($fil,$col,'=SUM('.fila_col($fila,$col).':'.fila_col($fil-1, $col).')');
			$col++;
		}
		total($fil,$col,'=SUM('.fila_col($fila,$col).':'.fila_col($fil-1, $col).')');
			
	}
    $wb->close();
?>
