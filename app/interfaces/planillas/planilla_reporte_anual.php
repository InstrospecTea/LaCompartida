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

	$wb = new Spreadsheet_Excel_Writer();
    $wb->send("Reporte Anual.xls");

	/* FORMATOS */
    $wb->setCustomColor ( 35, 15, 40, 190 );
    $wb->setCustomColor ( 36, 255, 255, 220 );

	/*
	$fmi = $fecha_mes_ini; 
	if($fecha_mes_ini < 10)
		$fmi = '0'.$fmi;

	$fmf = $fecha_mes_fin; 
	if($fecha_mes_fin < 10)
		$fmf = '0'.$fmf;

	$fecha_ini = '01-'.$fmi.'-'.$fecha_anio_ini;
	$fecha_fin = date('t',mktime(1,1,1,$fecha_mes_fin,1,$fecha_anio_fin)).'-'.$fmf.'-'.$fecha_anio_fin;*/
    
	$fecha_ini = '01-01-'.$fecha_anio;
	$fecha_fin = '31-12-'.$fecha_anio;
    
		$f = array();
		$f['encabezado'] =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'bottom',
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'underline'=>1,
									'Color' => 'white'));
		$f['encabezado']->setTextRotation(270);
		$f['titulo'] =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));
	
		$f['txt_opcion'] =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black'));
		$f['txt_opcion']->setTextWrap();
		
		$f['txt_valor'] =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$f['txt_valor']->setTextWrap();
		
		$f['txt_total'] =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Bold' => '1',
									'Color' => 'black'));
		$f['txt_total']->setTextWrap();

		$f['txt_rojo'] =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'red'));
		$f['txt_rojo']->setTextWrap();
		
		$f['txt_derecha'] =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$f['txt_derecha']->setTextWrap();
		
		$f['fecha'] =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black'));
		$f['fecha']->setTextWrap();
		
		$f['numeros'] =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$f['numeros']->setNumFormat("#,##0");

		$f['numeros_total'] =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Bold' => '1',
									'Color' => 'black'));
		$f['numeros_total']->setNumFormat("#,##0");

		$f['horas_minutos'] =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$f['horas_minutos']->setNumFormat("[h]:mm");

		$f['titulo_filas'] =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));
	
		$f['moneda'] =& $wb->addFormat(array('Size' => 11,
										'VAlign' => 'top',
										'Align' => 'right',
										'Border' => 1,
										'Color' => 'black'));
		$f['moneda']->setNumFormat("#,##0");

		$f['moneda_total'] =& $wb->addFormat(array('Size' => 11,
										'VAlign' => 'top',
										'Align' => 'right',
										'Border' => 1,
										'Bold' => '1',
										'Color' => 'black'));
		$f['moneda_total']->setNumFormat("#,##0");

		$f['porcentaje'] = $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black'));
		$f['porcentaje']->setNumFormat("0%");

		$f['porcentaje_total'] = $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
										'Bold' => '1',
									'Color' => 'black'));
		$f['porcentaje_total']->setNumFormat("0%");
	


	function escribir_multiple($hoja,$fila,$columna_ini,$columna_fin,$txt,$f)
	{
		$hoja->write($fila,$columna_ini,$txt, $f);
		for($i = $columna_ini+1; $i <= $columna_fin; $i++)
			$hoja->write($fila,$i,'');
		$hoja->mergeCells($fila,$columna_ini,$fila,$columna_fin);
	}


	function iniciar_hoja($hoja,$titulo_hoja,&$fila,&$col,$f, $ancho=8.00)
	{
			global $fecha_ini, $fecha_fin;
			$hoja->setInputEncoding('utf-8');
			$hoja->fitToPages(1,0);
			$hoja->setZoom(75);
		   
			$fila_inicial = 0;
			$hoja->setColumn( $fila_inicial, $fila_inicial,  24.00);
			$hoja->setColumn( $fila_inicial+1, $fila_inicial+1,  $ancho);
			$hoja->setColumn( $fila_inicial+2, $fila_inicial+2,  $ancho);
			$hoja->setColumn( $fila_inicial+3, $fila_inicial+3,  $ancho);
			$hoja->setColumn( $fila_inicial+4, $fila_inicial+4,  $ancho);
			$hoja->setColumn( $fila_inicial+5, $fila_inicial+5,  $ancho);
			$hoja->setColumn( $fila_inicial+6, $fila_inicial+6,  $ancho);
			$hoja->setColumn( $fila_inicial+7, $fila_inicial+7,  $ancho);
	
			$fila = 1;
			escribir_multiple($hoja,$fila,0,8,__($titulo_hoja),$f['titulo']);

			$fila += 1;
			$hoja->write($fila, 0, __('PERIODO RESUMEN').":", $f['titulo']);
			escribir_multiple($hoja,$fila,1,4,$fecha_ini." ".__("al")." ".$fecha_fin,$f['titulo']);
			
			$fila += 1;

			$hoy = date("d-m-Y");
			
			$hoja->write($fila, 0, __('FECHA REPORTE'), $f['titulo']);
			escribir_multiple($hoja,$fila,1,4,$hoy,$f['titulo']);
		   
			$columna = 0;
			$fila+= 2;
		  
		//	$hoja->write($fila,0,__('Horas declaradas por sistema de cobro'),$f['titulo']);
		//	$hoja->write($fila,1,'');
		//	$hoja->write($fila,2,'');
		//	$hoja->write($fila,3,'');
		//	$hoja->mergeCells($fila,0,$fila,3);
		//	$fila+=2;		
	}
	
	/* TITULOS */	
	
	function fila_col($fila,$col)
	{
			return Spreadsheet_Excel_Writer::rowcolToCell($fila, $col);
	}
	function print_headers($hoja,$encabezados,&$fila,$f)
	{
		$col = 0;
		foreach($encabezados as $e)
			$hoja->write($fila,$col++,__($e),$f['encabezado']);
		$fila++;
	}
	function n($num)
	{
		return number_format($num,0,'','');
	}

	//Elementos:
	$where_fecha = " (trabajo.fecha BETWEEN '".Utiles::fecha2sql($fecha_ini)."' AND '".Utiles::fecha2sql($fecha_fin)."') ";
	if(is_array($clientesF))
		$where_fecha .= " AND asunto.codigo_cliente IN ('".implode("','",$clientesF)."') ";
	if(is_array($usuariosF))
		$where_fecha .= " AND trabajo.id_usuario IN ('".implode("','",$usuariosF)."') ";


	//Reportes: se puede utilizar el modulo de Reportes Avanzados. A continuación se pueden crear hasta 14 reportes, quedando los resultados en un arreglo. Luego se llama cada resultado en la tabla que corresponde.

	//Arreglo de tipos de datos de los 14 reportes:
	$tipo_dato = array(	'horas_trabajadas',
						'horas_trabajadas',
						'horas_trabajadas',
						'horas_trabajadas',
						'horas_visibles',
						'horas_visibles',
						'horas_visibles',
						'horas_visibles',
						'valor_cobrado',
						'valor_estandar',
						'valor_cobrado',
						'valor_estandar',
						'valor_cobrado',
						'valor_estandar');
	//Los agrupadores de los 14 reportes
	$vista = array(	'forma_cobro',
					'mes_reporte-forma_cobro',
					'grupo_o_cliente-forma_cobro',
					'profesional-forma_cobro',
					'forma_cobro',
					'mes_reporte-forma_cobro',
					'grupo_o_cliente-forma_cobro',
					'profesional-forma_cobro',
					'mes_reporte',
					'mes_reporte',
					'grupo_o_cliente',
					'grupo_o_cliente',
					'profesional',
					'profesional');
	//Forma del arreglo resultado de los 14 reportes.
	$forma = array( 'arreglo',
					'tabla',
					'tabla',
					'tabla',
					'arreglo',
					'tabla',
					'tabla',
					'tabla',
					'arreglo',
					'arreglo',
					'arreglo',
					'arreglo',
					'arreglo',
					'arreglo');

	//Que reportes se usan. Por el momento, sólo los de valores.
	$usa_reporte_horas = false;
	$usa_reporte_valores = true;

	//Se cargan los primeros 8 reportes (de Hora), si es que se usan.
	$numero_reportes_horas = 8;
	if($usa_reporte_horas)
		for($i = 0; $i < $numero_reportes_horas; $i++)
		{
			$reporte[$i] = new Reporte($sesion);
			$reporte[$i]->addRangoFecha($fecha_ini,$fecha_fin);
			if($clientes)
				foreach($clientes as $cliente)
					if($cliente)
						$reporte[$i]->addFiltro('cliente','codigo_cliente',$cliente);
			if($usuarios)
				foreach($usuarios as $usuario)
					if($usuario)
						$reporte[$i]->addFiltro('usuario','id_usuario',$usuario);

			$reporte[$i]->setTipoDato($tipo_dato[$i]);
			$reporte[$i]->setVista($vista[$i]);

			$reporte[$i]->Query();
			if($forma[$i]=='arreglo')
				$resultado[$i] = $reporte[$i]->toArray();
			else
				$resultado[$i] = $reporte[$i]->toCross();
		}

	//Se cargan los 6 reportes de valores, si es que se usan
	$numero_reportes_valores = 6;
	$id_moneda=3;
	if($usa_reporte_valores)
		for($i = $numero_reportes_horas; $i <  $numero_reportes_horas + $numero_reportes_valores; $i++)
		{
			$reporte[$i] = new Reporte($sesion);
			$reporte[$i]->addRangoFecha($fecha_ini,$fecha_fin);
			if($clientes)
				foreach($clientes as $cliente)
					if($cliente)
						$reporte[$i]->addFiltro('cliente','codigo_cliente',$cliente);
			if($usuarios)
				foreach($usuarios as $usuario)
					if($usuario)
						$reporte[$i]->addFiltro('usuario','id_usuario',$usuario);

			$reporte[$i]->setTipoDato($tipo_dato[$i]);
			$reporte[$i]->setVista($vista[$i]);
			$reporte[$i]->id_moneda = $id_moneda;

			$reporte[$i]->Query();
			if($forma[$i]=='arreglo')
				$resultado[$i] = $reporte[$i]->toArray();
			else
				$resultado[$i] = $reporte[$i]->toCross();
		}

	//HOJA 1, TABLA 1
	$ws1 =& $wb->addWorksheet(__('Hrs Declaradas x FC x mes'));
	iniciar_hoja($ws1,__('Horas Declaradas Corregidas por Forma de Cobro'),$fila,$col,$f);

	$encabezados = array(__('Forma de Cobro'),'Horas');
	print_headers($ws1,$encabezados,$fila,$f);
	
	$fila_ini = $fila;

	if($usa_reporte_horas)
	{
		foreach($resultado[0] as $forma_cobro => $r)
		{
			if(is_array($r))
			{
				$ws1->write($fila,0,$forma_cobro,$f['txt_valor']);
				$ws1->write($fila,1,n($r['valor']),$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
			$query = "SELECT 
			IF(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC,
			SUM(TIME_TO_SEC( trabajo.duracion ))/3600
			FROM trabajo LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
			LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
			WHERE $where_fecha
			group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro))
			ORDER BY trabajo.cobrable";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			while(list($forma_cobro,$duracion) = mysql_fetch_array($resp))
			{	
				$ws1->write($fila,0,$forma_cobro,$f['txt_valor']);
				$ws1->write($fila,1,n($duracion),$f['numeros']);
				$fila++;
			}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws1->write($fila,0,__('Total'),$f['txt_total']);
		$ws1->write($fila,1,'=SUM('.fila_col($fila_ini,1).':'.fila_col($fila_fin,1).')',$f['numeros_total']);
	}
	$fila+=2;

	escribir_multiple($ws1,$fila,0,8,__('Horas Declaradas Corregidas por Forma de Cobro') . " " . __('desagregadas por mes'),$f['titulo']);
	$fila+=2;
	  
	//HOJA 1, TABLA 2
	if($usa_reporte_horas)
	{
		$r = $resultado[1];
		if(is_array($r['labels']))
		{
			//Encabezados
			$encabezados = array('Mes');
			foreach($r['labels_col'] as $id_col => $nombre_col)
				$encabezados[] = __($nombre_col['nombre']);
			$encabezados[] = __('Total');
			$encabezados[] = __('Porcentaje');
			print_headers($ws1,$encabezados,$fila,$f);

			$fila_ini = $fila;
			foreach($r['labels'] as $id => $nombre)
			{
				$ws1->write($fila,0,$nombre['nombre'],$f['txt_valor']);
				$col = 1;
				foreach($r['labels_col'] as $id_col => $nombre_col)
				{
					if(isset($r['celdas'][$id][$id_col]['valor']))
					{	
						$ws1->write($fila,$col,n($r['celdas'][$id][$id_col]['valor']),$f['numeros']);
					}
					else
					{
						$ws1->write($fila,$col,'',$f['numeros']);
					}
					$col++;		
				}
				$ws1->write($fila,$col,'=SUM('.fila_col($fila,1).':'.fila_col($fila,$col-1).')',$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
		$encabezados = array('Mes','TASA','FLAT FEE','RETAINER','PROPORCIONAL','CAP','NO COBRABLE','Total','Porcentaje');
		print_headers($ws1,$encabezados,$fila,$f);
			
		$query = "Select mes, fecha
		, sum(if(FC='TASA',horas,0)) as 'TASA'
		, sum(if(FC='FLAT FEE',horas,0)) as 'FLET FEE'
		, sum(if(FC='RETAINER',horas,0)) as 'RETAINER'
		, sum(if(FC='PROPORCIONAL',horas,0)) as 'PROPORCIONAL'
		, sum(if(FC='CAP',horas,0)) as 'CAP'
		, sum(if(FC='NO COBRABLE',horas,0)) as 'NO COBRABLE' 
		FROM ( 
		select CONCAT(Month(trabajo.fecha), '-', Year(trabajo.fecha)) as mes, trabajo.fecha, if(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC, sum(TIME_TO_SEC(duracion))/3600 as horas from trabajo LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
		WHERE $where_fecha
		group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)), MONTH(trabajo.fecha)
		) as tabla1
		GROUP BY mes
		ORDER BY fecha ";

		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($mes,$fecha,$tasa,$flat_fee,$retainer,$proporcional,$cap,$no_cobrable) = mysql_fetch_array($resp))
		{
			$ws1->write($fila,0,$mes,$f['txt_valor']);
			$ws1->write($fila,1,n($tasa),$f['numeros']);
			$ws1->write($fila,2,n($flat_fee),$f['numeros']);
			$ws1->write($fila,3,n($retainer),$f['numeros']);
			$ws1->write($fila,4,n($proporcional),$f['numeros']);
			$ws1->write($fila,5,n($cap),$f['numeros']);
			$ws1->write($fila,6,n($no_cobrable),$f['numeros']);
			$ws1->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;
	
	if($fila_fin > $fila_ini)
	{
		$ws1->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws1->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
		
		$ws1->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
	
		$fila++;
	
		$ws1->write($fila,0,__('Porcentaje'),$f['txt_total']);
		for($i = 1; $i<8; $i++) 
			$ws1->write($fila,$i,'=('.fila_col($fila-1,$i).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);			

		for($i = $fila_ini; $i <= $fila_fin+1; $i++)
			$ws1->write($i,8,'=('.fila_col($i,7).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);
	}
	
	

	//HOJA 2 TABLA 1
	$ws2 =& $wb->addWorksheet(__('Hrs Declaradas x FC x grupo'));
	iniciar_hoja($ws2,'Horas Declaradas por ' . __('Forma de Cobro') . ' desagregadas por Grupo',$fila,$col,$f);
	if($usa_reporte_horas)
	{
		$r = $resultado[2];
		if(is_array($r['labels']))
		{
			//Encabezados
			$encabezados = array(__('Grupo o Cliente'));
			foreach($r['labels_col'] as $id_col => $nombre_col)
				$encabezados[] = __($nombre_col['nombre']);
			$encabezados[] = __('Total');
			$encabezados[] = __('Porcentaje');
			print_headers($ws2,$encabezados,$fila,$f);

			$fila_ini = $fila;
			foreach($r['labels'] as $id => $nombre)
			{
				$ws2->write($fila,0,$nombre['nombre'],$f['txt_valor']);
				$col = 1;
				foreach($r['labels_col'] as $id_col => $nombre_col)
				{
					if(isset($r['celdas'][$id][$id_col]['valor']))
						$ws2->write($fila,$col,n($r['celdas'][$id][$id_col]['valor']),$f['numeros']);
					else
						$ws2->write($fila,$col,'',$f['numeros']);
					$col++;		
				}
				$ws2->write($fila,$col,'=SUM('.fila_col($fila,1).':'.fila_col($fila,$col-1).')',$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
			$encabezados = array('Grupo o Cliente','TASA','FLAT FEE','RETAINER','PROPORCIONAL','CAP','NO COBRABLE','Total','Porcentaje');
			print_headers($ws2,$encabezados,$fila,$f);
			
			$query ="select glosa_cliente2
		, sum(if(FC='TASA',horas,0)) as 'TASA'
		, sum(if(FC='FLAT FEE',horas,0)) as 'FLET FEE'
		, sum(if(FC='RETAINER',horas,0)) as 'RETAINER'
		, sum(if(FC='PROPORCIONAL',horas,0)) as 'PROPORCIONAL'
		, sum(if(FC='CAP',horas,0)) as 'CAP'
		, sum(if(FC='NO COBRABLE',horas,0)) as 'NO COBRABLE'
		, sum(horas) as total 
		FROM ( 
		select if(grupo_cliente.id_grupo_cliente IS NULL,cliente.glosa_cliente, grupo_cliente.glosa_grupo_cliente) as glosa_cliente2, if(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC, sum(TIME_TO_SEC(duracion))/3600 as horas from trabajo
		LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
		LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
		LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
		LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
		LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
		WHERE $where_fecha
		group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)), glosa_cliente2
		) as tabla1
		GROUP BY glosa_cliente2 order by total desc";
			$fila_ini = $fila;
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			while(list($cliente,$tasa,$flat_fee,$retainer,$proporcional,$cap,$no_cobrable) = mysql_fetch_array($resp))
			{
				$ws2->write($fila,0,$cliente,$f['txt_valor']);
				$ws2->write($fila,1,n($tasa),$f['numeros']);
				$ws2->write($fila,2,n($flat_fee),$f['numeros']);
				$ws2->write($fila,3,n($retainer),$f['numeros']);
				$ws2->write($fila,4,n($proporcional),$f['numeros']);
				$ws2->write($fila,5,n($cap),$f['numeros']);
				$ws2->write($fila,6,n($no_cobrable),$f['numeros']);
				
				$ws2->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
				$fila++;
			}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws2->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws2->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
		$ws2->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
		
		$fila++;
	
		$ws2->write($fila,0,__('Porcentaje'),$f['txt_total']);
		for($i = 1; $i<8; $i++) 
			$ws2->write($fila,$i,'=('.fila_col($fila-1,$i).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);			

		for($i = $fila_ini; $i <= $fila_fin+1; $i++)
			$ws2->write($i,8,'=('.fila_col($i,7).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);
	}

	//HOJA 3 TABLA 1
	$ws3 =& $wb->addWorksheet(__('Hrs Declaradas x FC x prof'));
	iniciar_hoja($ws3,'Horas Declaradas por ' . __('Forma de Cobro') . ' desagregadas por Profesional',$fila,$col,$f);
	if($usa_reporte_horas)
	{
		$r = $resultado[3];
		if(is_array($r['labels']))
		{
			//Encabezados
			$encabezados = array(__('Profesional'));
			foreach($r['labels_col'] as $id_col => $nombre_col)
				$encabezados[] = __($nombre_col['nombre']);
			$encabezados[] = __('Total');
			$encabezados[] = __('Porcentaje');
			print_headers($ws3,$encabezados,$fila,$f);

			$fila_ini = $fila;
			foreach($r['labels'] as $id => $nombre)
			{
				$ws3->write($fila,0,$nombre['nombre'],$f['txt_valor']);
				$col = 1;
				foreach($r['labels_col'] as $id_col => $nombre_col)
				{
					if(isset($r['celdas'][$id][$id_col]['valor']))
						$ws3->write($fila,$col,n($r['celdas'][$id][$id_col]['valor']),$f['numeros']);
					else
						$ws3->write($fila,$col,'',$f['numeros']);
					$col++;		
				}
				$ws3->write($fila,$col,'=SUM('.fila_col($fila,1).':'.fila_col($fila,$col-1).')',$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
		$encabezados = array('Profesional','TASA','FLAT FEE','RETAINER','PROPORCIONAL','CAP','NO COBRABLE','Total','Porcentaje');
		print_headers($ws3,$encabezados,$fila,$f);
		
		$query ="select username
		, sum(if(FC='TASA',horas,0)) as 'TASA'
		, sum(if(FC='FLAT FEE',horas,0)) as 'FLET FEE'
		, sum(if(FC='RETAINER',horas,0)) as 'RETAINER'
		, sum(if(FC='PROPORCIONAL',horas,0)) as 'PROPORCIONAL'
		, sum(if(FC='CAP',horas,0)) as 'CAP'
		, sum(if(FC='NO COBRABLE',horas,0)) as 'NO COBRABLE'
		, sum(horas) as total 
		FROM ( 
		select usuario.username, if(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC, sum(TIME_TO_SEC(duracion))/3600 as horas from trabajo
		LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
		LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
		LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
		LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
		LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
		WHERE $where_fecha
		group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)), usuario.id_usuario
		) as tabla1
		GROUP BY username
		order by total desc";
		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($profesional,$tasa,$flat_fee,$retainer,$proporcional,$cap,$no_cobrable) = mysql_fetch_array($resp))
		{
			$ws3->write($fila,0,$profesional,$f['txt_valor']);
			$ws3->write($fila,1,n($tasa),$f['numeros']);
			$ws3->write($fila,2,n($flat_fee),$f['numeros']);
			$ws3->write($fila,3,n($retainer),$f['numeros']);
			$ws3->write($fila,4,n($proporcional),$f['numeros']);
			$ws3->write($fila,5,n($cap),$f['numeros']);
			$ws3->write($fila,6,n($no_cobrable),$f['numeros']);
			
			$ws3->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws3->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws3->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
		$ws3->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
		
		$fila++;
	
		$ws3->write($fila,0,__('Porcentaje'),$f['txt_total']);
		for($i = 1; $i<8; $i++) 
			$ws3->write($fila,$i,'=('.fila_col($fila-1,$i).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);			

		for($i = $fila_ini; $i <= $fila_fin+1; $i++)
			$ws3->write($i,8,'=('.fila_col($i,7).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);
	}

	//HOJA 4, TABLA 1
	$ws1 =& $wb->addWorksheet(__('Hrs Liquidadas x FC x mes'));
	iniciar_hoja($ws1,__('Horas Liquidadas por Forma de Cobro'),$fila,$col,$f);

	$encabezados = array(__('Forma de Cobro'),'Horas');
	print_headers($ws1,$encabezados,$fila,$f);
	
	$fila_ini = $fila;
	if($usa_reporte_horas)
	{
		foreach($resultado[4] as $forma_cobro => $r)
		{
			if(is_array($r))
			{
				$ws1->write($fila,0,$forma_cobro,$f['txt_valor']);
				$ws1->write($fila,1,n($r['valor']),$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
		$query = "SELECT 
				IF(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC,
				SUM(TIME_TO_SEC( trabajo.duracion_cobrada ))/3600
	FROM trabajo LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
	LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
	WHERE cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE') AND $where_fecha
	group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro))
	ORDER BY trabajo.cobrable";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($forma_cobro,$duracion) = mysql_fetch_array($resp))
		{
			$ws1->write($fila,0,$forma_cobro,$f['txt_valor']);
			$ws1->write($fila,1,n($duracion),$f['numeros']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;
	
	$ws1->write($fila,0,__('Total'),$f['txt_total']);
	$ws1->write($fila,1,'=SUM('.fila_col($fila_ini,1).':'.fila_col($fila_fin,1).')',$f['numeros_total']);
	
	$fila+=2;
	
	//HOJA 4, TABLA 2
	escribir_multiple($ws1,$fila,0,8,__('Horas Liquidadas por Forma de Cobro') . " " . __('desagregadas por mes'),$f['titulo']);
	$fila+=2;
  
	if($usa_reporte_horas)
	{
		$r = $resultado[5];
		if(is_array($r['labels']))
		{
			//Encabezados
			$encabezados = array('Mes');
			foreach($r['labels_col'] as $id_col => $nombre_col)
				$encabezados[] = __($nombre_col['nombre']);
			$encabezados[] = __('Total');
			$encabezados[] = __('Porcentaje');
			print_headers($ws1,$encabezados,$fila,$f);

			$fila_ini = $fila;
			foreach($r['labels'] as $id => $nombre)
			{
				$ws1->write($fila,0,$nombre['nombre'],$f['txt_valor']);
				$col = 1;
				foreach($r['labels_col'] as $id_col => $nombre_col)
				{
					if(isset($r['celdas'][$id][$id_col]['valor']))
					{	
						$ws1->write($fila,$col,n($r['celdas'][$id][$id_col]['valor']),$f['numeros']);
					}
					else
					{
						$ws1->write($fila,$col,'',$f['numeros']);
					}
					$col++;		
				}
				$ws1->write($fila,$col,'=SUM('.fila_col($fila,1).':'.fila_col($fila,$col-1).')',$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
		$encabezados = array('Mes','TASA','FLAT FEE','RETAINER','PROPORCIONAL','CAP','NO COBRABLE','Total','Porcentaje');
		print_headers($ws1,$encabezados,$fila,$f);
		
		$query = "Select mes, fecha
		, sum(if(FC='TASA',horas,0)) as 'TASA'
		, sum(if(FC='FLAT FEE',horas,0)) as 'FLET FEE'
		, sum(if(FC='RETAINER',horas,0)) as 'RETAINER'
		, sum(if(FC='PROPORCIONAL',horas,0)) as 'PROPORCIONAL'
		, sum(if(FC='CAP',horas,0)) as 'CAP'
		, sum(if(FC='NO COBRABLE',horas,0)) as 'NO COBRABLE' 
		FROM ( 
		select CONCAT(Month(trabajo.fecha), '-', Year(trabajo.fecha)) as mes, trabajo.fecha, if(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC, sum(TIME_TO_SEC(duracion_cobrada))/3600 as horas from trabajo LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
		WHERE $where_fecha AND cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE')
		group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)), mes
		) as tabla1
		GROUP BY mes
		ORDER BY fecha ";

		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($mes,$fecha,$tasa,$flat_fee,$retainer,$proporcional,$cap,$no_cobrable) = mysql_fetch_array($resp))
		{
			$ws1->write($fila,0,$mes,$f['txt_valor']);
			$ws1->write($fila,1,n($tasa),$f['numeros']);
			$ws1->write($fila,2,n($flat_fee),$f['numeros']);
			$ws1->write($fila,3,n($retainer),$f['numeros']);
			$ws1->write($fila,4,n($proporcional),$f['numeros']);
			$ws1->write($fila,5,n($cap),$f['numeros']);
			$ws1->write($fila,6,n($no_cobrable),$f['numeros']);
			$ws1->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws1->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws1->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
		$ws1->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
		
		$fila++;
	
		$ws1->write($fila,0,__('Porcentaje'),$f['txt_total']);
		for($i = 1; $i<8; $i++) 
			$ws1->write($fila,$i,'=('.fila_col($fila-1,$i).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);			

		for($i = $fila_ini; $i <= $fila_fin+1; $i++)
			$ws1->write($i,8,'=('.fila_col($i,7).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);
	}

	//HOJA 5 TABLA 1
	$ws2 =& $wb->addWorksheet(__('Hrs Liquidadas x FC x grupo'));
	iniciar_hoja($ws2,__('Horas Liquidadas por Forma de Cobro') . ' desagregadas por Grupo',$fila,$col,$f);
	
	if($usa_reporte_horas)
	{
		$r = $resultado[6];
		if(is_array($r['labels']))
		{
			//Encabezados
			$encabezados = array(__('Grupo o Cliente'));
			foreach($r['labels_col'] as $id_col => $nombre_col)
				$encabezados[] = __($nombre_col['nombre']);
			$encabezados[] = __('Total');
			$encabezados[] = __('Porcentaje');
			print_headers($ws2,$encabezados,$fila,$f);

			$fila_ini = $fila;
			foreach($r['labels'] as $id => $nombre)
			{
				$ws2->write($fila,0,$nombre['nombre'],$f['txt_valor']);
				$col = 1;
				foreach($r['labels_col'] as $id_col => $nombre_col)
				{
					if(isset($r['celdas'][$id][$id_col]['valor']))
						$ws2->write($fila,$col,n($r['celdas'][$id][$id_col]['valor']),$f['numeros']);
					else
						$ws2->write($fila,$col,'',$f['numeros']);
					$col++;		
				}
				$ws2->write($fila,$col,'=SUM('.fila_col($fila,1).':'.fila_col($fila,$col-1).')',$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
		$encabezados = array('Grupo o Cliente','TASA','FLAT FEE','RETAINER','PROPORCIONAL','CAP','NO COBRABLE','Total','Porcentaje');
		print_headers($ws2,$encabezados,$fila,$f);
		
		$query ="select glosa_cliente2
		, sum(if(FC='TASA',horas,0)) as 'TASA'
		, sum(if(FC='FLAT FEE',horas,0)) as 'FLET FEE'
		, sum(if(FC='RETAINER',horas,0)) as 'RETAINER'
		, sum(if(FC='PROPORCIONAL',horas,0)) as 'PROPORCIONAL'
		, sum(if(FC='CAP',horas,0)) as 'CAP'
		, sum(if(FC='NO COBRABLE',horas,0)) as 'NO COBRABLE'
		, sum(horas) as total 
		FROM ( 
		select if(grupo_cliente.id_grupo_cliente IS NULL,cliente.glosa_cliente, grupo_cliente.glosa_grupo_cliente) as glosa_cliente2, if(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC, sum(TIME_TO_SEC(duracion_cobrada))/3600 as horas from trabajo
		LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
		LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
		LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
		LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
		LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
		WHERE $where_fecha AND cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE')
		group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)), glosa_cliente2
		) as tabla1
		GROUP BY glosa_cliente2 order by total desc";
		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($cliente,$tasa,$flat_fee,$retainer,$proporcional,$cap,$no_cobrable) = mysql_fetch_array($resp))
		{
			$ws2->write($fila,0,$cliente,$f['txt_valor']);
			$ws2->write($fila,1,n($tasa),$f['numeros']);
			$ws2->write($fila,2,n($flat_fee),$f['numeros']);
			$ws2->write($fila,3,n($retainer),$f['numeros']);
			$ws2->write($fila,4,n($proporcional),$f['numeros']);
			$ws2->write($fila,5,n($cap),$f['numeros']);
			$ws2->write($fila,6,n($no_cobrable),$f['numeros']);
			
			$ws2->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws2->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws2->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
		$ws2->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
		
		$fila++;
	
		$ws2->write($fila,0,__('Porcentaje'),$f['txt_total']);
		for($i = 1; $i<8; $i++) 
			$ws2->write($fila,$i,'=('.fila_col($fila-1,$i).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);			

		for($i = $fila_ini; $i <= $fila_fin+1; $i++)
			$ws2->write($i,8,'=('.fila_col($i,7).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);
	}

	//HOJA 6 TABLA 1
	$ws3 =& $wb->addWorksheet(__('Hrs Liquidadas x FC x prof'));
	iniciar_hoja($ws3,__('Horas Liquidadas por Forma de Cobro') . " " . 'desagregadas por Profesional',$fila,$col,$f);

	if($usa_reporte_horas)
	{
		$r = $resultado[7];
		if(is_array($r['labels']))
		{
			//Encabezados
			$encabezados = array(__('Profesional'));
			foreach($r['labels_col'] as $id_col => $nombre_col)
				$encabezados[] = __($nombre_col['nombre']);
			$encabezados[] = __('Total');
			$encabezados[] = __('Porcentaje');
			print_headers($ws3,$encabezados,$fila,$f);

			$fila_ini = $fila;
			foreach($r['labels'] as $id => $nombre)
			{
				$ws3->write($fila,0,$nombre['nombre'],$f['txt_valor']);
				$col = 1;
				foreach($r['labels_col'] as $id_col => $nombre_col)
				{
					if(isset($r['celdas'][$id][$id_col]['valor']))
						$ws3->write($fila,$col,n($r['celdas'][$id][$id_col]['valor']),$f['numeros']);
					else
						$ws3->write($fila,$col,'',$f['numeros']);
					$col++;		
				}
				$ws3->write($fila,$col,'=SUM('.fila_col($fila,1).':'.fila_col($fila,$col-1).')',$f['numeros']);
				$fila++;
			}
		}
	}
	else
	{
	
		$encabezados = array('Profesional','TASA','FLAT FEE','RETAINER','PROPORCIONAL','CAP','NO COBRABLE','Total','Porcentaje');
		print_headers($ws3,$encabezados,$fila,$f);
		
		$query ="select username
		, sum(if(FC='TASA',horas,0)) as 'TASA'
		, sum(if(FC='FLAT FEE',horas,0)) as 'FLET FEE'
		, sum(if(FC='RETAINER',horas,0)) as 'RETAINER'
		, sum(if(FC='PROPORCIONAL',horas,0)) as 'PROPORCIONAL'
		, sum(if(FC='CAP',horas,0)) as 'CAP'
		, sum(if(FC='NO COBRABLE',horas,0)) as 'NO COBRABLE'
		, sum(horas) as total 
		FROM ( 
		select usuario.username, if(trabajo.cobrable=0,'NO COBRABLE',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)) as FC, sum(TIME_TO_SEC(duracion_cobrada))/3600 as horas from trabajo
		LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
		LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
		LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
		LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
		LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
		WHERE $where_fecha AND cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE')
		group by if(trabajo.cobrable=0,'No Cobrable',if(cobro.forma_cobro IS NOT NULL,cobro.forma_cobro, contrato_asunto.forma_cobro)), usuario.id_usuario
		) as tabla1
		GROUP BY username
		order by total desc";
		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($profesional,$tasa,$flat_fee,$retainer,$proporcional,$cap,$no_cobrable) = mysql_fetch_array($resp))
		{
			$ws3->write($fila,0,$profesional,$f['txt_valor']);
			$ws3->write($fila,1,n($tasa),$f['numeros']);
			$ws3->write($fila,2,n($flat_fee),$f['numeros']);
			$ws3->write($fila,3,n($retainer),$f['numeros']);
			$ws3->write($fila,4,n($proporcional),$f['numeros']);
			$ws3->write($fila,5,n($cap),$f['numeros']);
			$ws3->write($fila,6,n($no_cobrable),$f['numeros']);
			
			$ws3->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws3->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws3->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
		$ws3->write($fila,7,'=SUM('.fila_col($fila,1).':'.fila_col($fila,6).')',$f['numeros_total']);
		
		$fila++;
	
		$ws3->write($fila,0,__('Porcentaje'),$f['txt_total']);
		for($i = 1; $i<8; $i++) 
			$ws3->write($fila,$i,'=('.fila_col($fila-1,$i).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);			

		for($i = $fila_ini; $i <= $fila_fin+1; $i++)
			$ws3->write($i,8,'=('.fila_col($i,7).'/'.fila_col($fila_fin+1,7).')',$f['porcentaje_total']);
	}

	//HOJA 7 TABLA 1
	$ws7 =& $wb->addWorksheet(__('Hrs x Estado'));
	iniciar_hoja($ws7,'Horas por Estado',$fila,$col,$f);
	
	$encabezados = array('Liquidadas','Por Liquidar','Incobrables','Castigadas','No Cobrables','Declaradas');
	print_headers($ws7,$encabezados,$fila,$f);
	
	$query ="SELECT SUM(IF((cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE') 
			AND 
			trabajo.cobrable=1)
				,TIME_TO_SEC(duracion_cobrada)
				,0
		)
	)/3600 as hrs_liquidadas,
sum(if(trabajo.cobrable=1 AND (cobro.estado IN ('CREADO', 'EN REVISION') OR cobro.estado IS NULL)
                ,TIME_TO_SEC(duracion_cobrada),0)) / 3600 as hrs_x_liquidar,                
sum(if(trabajo.cobrable=1 AND cobro.estado = 'INCOBRABLE'
                ,TIME_TO_SEC(duracion_cobrada),0)) / 3600 as hrs_incobrables,
sum(if(trabajo.cobrable=1,TIME_TO_SEC(duracion) - TIME_TO_SEC(IFNULL(duracion_cobrada,0)),0))/3600 as hrs_castigadas,
sum( if(trabajo.cobrable=0,TIME_TO_SEC(duracion),0))/3600 as hrs_no_cobrables,
sum(TIME_TO_SEC(duracion))/3600 as hrs_declaradas 
from trabajo
LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
LEFT JOIN contrato as contrato_asunto ON asunto.id_contrato=contrato_asunto.id_contrato
WHERE $where_fecha ";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	while(list($liquidadas,$por_liquidar,$incobrables,$castigadas,$no_cobrables,$declaradas) = mysql_fetch_array($resp))
	{
		$ws7->write($fila,0,n($liquidadas),$f['numeros']);
		$ws7->write($fila,1,n($por_liquidar),$f['numeros']);
		$ws7->write($fila,2,n($incobrables),$f['numeros']);
		$ws7->write($fila,3,n($castigadas),$f['numeros']);
		$ws7->write($fila,4,n($no_cobrables),$f['numeros']);
		$ws7->write($fila,5,n($declaradas),$f['numeros']);
		$fila++;
	}

	//HOJA 8 TABLA 1
	$ws7 =& $wb->addWorksheet(__('Hrs x Estado x Grupo'));
	iniciar_hoja($ws7,'Horas por Estado por Grupo',$fila,$col,$f);
	
	$encabezados = array('Grupo o Cliente','Liquidadas','Por Liquidar','Incobrables','Castigadas','No Cobrables','Declaradas');
	print_headers($ws7,$encabezados,$fila,$f);
	
	$query ="select if(grupo_cliente.id_grupo_cliente IS NULL,cliente.glosa_cliente, grupo_cliente.glosa_grupo_cliente) as glosa_cliente2,sum(if(cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE') AND trabajo.cobrable=1,TIME_TO_SEC(duracion_cobrada),0))/3600 as hrs_liquidadas,
sum(if(trabajo.cobrable=1 AND (cobro.estado IN ('CREADO', 'EN REVISION') OR cobro.estado IS NULL)
                ,TIME_TO_SEC(duracion_cobrada),0)) / 3600 as hrs_x_liquidar,
sum(if(trabajo.cobrable=1 AND cobro.estado = 'INCOBRABLE'
                ,TIME_TO_SEC(duracion_cobrada),0)) / 3600 as hrs_incobrables,
sum(if(trabajo.cobrable=1,TIME_TO_SEC(duracion) - TIME_TO_SEC(IFNULL(duracion_cobrada,0)),0))/3600 as hrs_castigadas,
sum( if(trabajo.cobrable=0,TIME_TO_SEC(duracion),0))/3600 as hrs_no_cobrables,
sum(TIME_TO_SEC(duracion))/3600 as hrs_declaradas
FROM trabajo
LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
WHERE $where_fecha
group by glosa_cliente2
ORDER BY hrs_declaradas DESC";
	$fila_ini = $fila;
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	while(list($cliente,$liquidadas,$por_liquidar,$incobrables,$castigadas,$no_cobrables,$declaradas) = mysql_fetch_array($resp))
	{
		$ws7->write($fila,0,$cliente,$f['txt_valor']);
		$ws7->write($fila,6,n($declaradas),$f['numeros']);
		$ws7->write($fila,5,n($no_cobrables),$f['numeros']);
		$ws7->write($fila,4,n($castigadas),$f['numeros']);
		$ws7->write($fila,3,n($incobrables),$f['numeros']);
		$ws7->write($fila,2,n($por_liquidar),$f['numeros']);
		$ws7->write($fila,1,n($liquidadas),$f['numeros']);
		
		$fila++;
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws7->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws7->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
	}

	//HOJA 9 TABLA 1
	$ws7 =& $wb->addWorksheet(__('Hrs x Estado x Profesional'));
	iniciar_hoja($ws7,'Horas por Estado por Profesional',$fila,$col,$f);
	
	$encabezados = array('Profesional','Liquidadas','Por Liquidar','Incobrables','Castigadas','No Cobrables','Declaradas');
	print_headers($ws7,$encabezados,$fila,$f);
	
	$query ="select usuario.username,
sum(if(cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE') AND trabajo.cobrable=1,TIME_TO_SEC(duracion_cobrada),0))/3600 as hrs_liquidadas,
sum(if(trabajo.cobrable=1 AND (cobro.estado IN ('CREADO', 'EN REVISION','INCOBRABLE') OR cobro.estado IS NULL)
                ,TIME_TO_SEC(duracion_cobrada),0)) / 3600 as hrs_x_liquidar,
sum(if(trabajo.cobrable=1 AND cobro.estado = 'INCOBRABLE'
                ,TIME_TO_SEC(duracion_cobrada),0)) / 3600 as hrs_incobrables,
sum(if(trabajo.cobrable=1,TIME_TO_SEC(duracion) - TIME_TO_SEC(IFNULL(duracion_cobrada,0)),0))/3600 as hrs_castigadas,
sum( if(trabajo.cobrable=0,TIME_TO_SEC(duracion),0))/3600 as hrs_no_cobrables,
sum(TIME_TO_SEC(duracion))/3600 as hrs_declaradas
FROM trabajo
LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
WHERE $where_fecha
group by usuario.id_usuario
ORDER BY hrs_declaradas DESC";
	$fila_ini = $fila;
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	while(list($usuario,$liquidadas,$por_liquidar,$incobrables,$castigadas,$no_cobrables,$declaradas) = mysql_fetch_array($resp))
	{
		$ws7->write($fila,0,$usuario,$f['txt_valor']);
		$ws7->write($fila,6,n($declaradas),$f['numeros']);
		$ws7->write($fila,5,n($no_cobrables),$f['numeros']);
		$ws7->write($fila,4,n($castigadas),$f['numeros']);
		$ws7->write($fila,3,n($incobrables),$f['numeros']);
		$ws7->write($fila,2,n($por_liquidar),$f['numeros']);
		$ws7->write($fila,1,n($liquidadas),$f['numeros']);
		
		$fila++;
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws7->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<7; $i++) 
			$ws7->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['numeros_total']);
	}
	//HOJA 10 TABLA 1
	$ws7 =& $wb->addWorksheet(__('Honorarios x Mes'));
	iniciar_hoja($ws7,'Valor Liquidado por Mes',$fila,$col,$f,10.00);
	
	$encabezados = array('Mes','Total Liquidado','Tarifa Estándar','Rentabilidad');
	print_headers($ws7,$encabezados,$fila,$f);
	
	if($usa_reporte_valores)
	{
		//Valor y Valor estándar:
		$r = $resultado[8];
		$r_c = $resultado[9];
		$r = Reporte::fixArray($r,$r_c);
		$r_c = Reporte::fixArray($r_c,$r);

		foreach($r as $k_a => $a)
		{
			if(is_array($a))
			{
				$ws7->write($fila,0,$k_a,$f['txt_valor']);
				$ws7->write($fila,1,n($a['valor']),$f['numeros']);
				$ws7->write($fila,2,n($r_c[$k_a]['valor']),$f['numeros']);

				$formula = '='.fila_col($fila,1).'/'.fila_col($fila,2);
				$ws7->write($fila,3,n($r_c[$k_a]['valor'])? $formula:'0',$f['porcentaje']);

				$fila++;
			}
		}
	}
	else
	{
		$query ="
		SELECT
		CONCAT(Month(trabajo.fecha), '-', Year(trabajo.fecha)) as mes, trabajo.fecha AS fecha,
		SUM( ( IF(cobro.forma_cobro='FLAT FEE',tarifa_hh_estandar,tarifa_hh) * TIME_TO_SEC( duracion_cobrada)/3600 ) * (cobro.monto_trabajos / IF(cobro.forma_cobro='FLAT FEE',IF(cobro.monto_thh_estandar>0,cobro.monto_thh_estandar,IF(cobro.monto_trabajos>0,cobro.monto_trabajos,1)),IF(cobro.monto_thh>0,cobro.monto_thh,IF(cobro.monto_trabajos>0,cobro.monto_trabajos,1))) ) * (cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base) / cobro_moneda.tipo_cambio ) as total_liquidado, 
		SUM(trabajo.tarifa_hh_estandar * (TIME_TO_SEC( duracion_cobrada)/3600) * (cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base)
							 /   cobro_moneda.tipo_cambio) as total_tarifa_std 
		from trabajo
		JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
		LEFT JOIN cobro_moneda ON (cobro.id_cobro = cobro_moneda.id_cobro AND cobro_moneda.id_moneda = '3')
		LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
		WHERE cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE')
		and $where_fecha
		GROUP BY mes
		ORDER BY fecha";
		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($mes,$fecha,$liquidado,$estandar) = mysql_fetch_array($resp))
		{
			$ws7->write($fila,0,$mes,$f['txt_valor']);
			$ws7->write($fila,1,n($liquidado),$f['moneda']);
			$ws7->write($fila,2,n($estandar),$f['moneda']);
			
			$formula = '='.fila_col($fila,1).'/'.fila_col($fila,2);
			$ws7->write($fila,3,$estandar? $formula:'0',$f['porcentaje']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws7->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<3; $i++) 
			$ws7->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['moneda_total']);
		$ws7->write($fila,3,'='.fila_col($fila,1).'/'.fila_col($fila,2),$f['porcentaje_total']);
	}

	//HOJA 11 TABLA 1
	$ws7 =& $wb->addWorksheet(__('Honorarios x Grupo'));
	iniciar_hoja($ws7,'Valor Liquidado por Grupo en UF',$fila,$col,$f,10.00);
	
	$encabezados = array('Grupo o Cliente','Total Liquidado','Tarifa Estándar','Rentabilidad');
	print_headers($ws7,$encabezados,$fila,$f);
	
	if($usa_reporte_valores)
	{
		//Valor y Valor estándar:
		$r = $resultado[10];
		$r_c = $resultado[11];
		$r = Reporte::fixArray($r,$r_c);
		$r_c = Reporte::fixArray($r_c,$r);

		foreach($r as $k_a => $a)
		{
			if(is_array($a))
			{
				$ws7->write($fila,0,$k_a,$f['txt_valor']);
				$ws7->write($fila,1,n($a['valor']),$f['numeros']);
				$ws7->write($fila,2,n($r_c[$k_a]['valor']),$f['numeros']);

				$formula = '='.fila_col($fila,1).'/'.fila_col($fila,2);
				$ws7->write($fila,3,n($r_c[$k_a]['valor'])? $formula:'0',$f['porcentaje']);

				$fila++;
			}
		}
	}
	else
	{
		$query ="SELECT
							if(grupo_cliente.id_grupo_cliente IS NULL,cliente.glosa_cliente, grupo_cliente.glosa_grupo_cliente) as glosa_cliente2, 
							SUM( ( IF(cobro.forma_cobro='FLAT FEE',tarifa_hh_estandar,tarifa_hh) * TIME_TO_SEC( duracion_cobrada)/3600 ) * (cobro.monto_trabajos / IF(cobro.forma_cobro='FLAT FEE',IF(cobro.monto_thh_estandar>0,cobro.monto_thh_estandar,IF(cobro.monto_trabajos>0,cobro.monto_trabajos,1)),
							IF(cobro.monto_thh>0,cobro.monto_thh,IF(cobro.monto_trabajos>0,cobro.monto_trabajos,1))) ) * (cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base) / cm_cobro.tipo_cambio ) as total_liquidado, 
							SUM(trabajo.tarifa_hh_estandar * (TIME_TO_SEC( duracion_cobrada)/3600) * (cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base) / cm_cobro.tipo_cambio) as total_tarifa_std 
						from trabajo
						JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
						LEFT JOIN cobro_moneda as cm_cobro ON (cobro.id_cobro = cm_cobro.id_cobro AND cm_cobro.id_moneda = '3')
						LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
						LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
						LEFT JOIN grupo_cliente ON cliente.id_grupo_cliente = grupo_cliente.id_grupo_cliente
						WHERE cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE')
						and $where_fecha
						GROUP BY glosa_cliente2
						ORDER BY total_liquidado DESC ";
		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($cliente,$liquidado,$estandar) = mysql_fetch_array($resp))
		{
			$ws7->write($fila,0,$cliente,$f['txt_valor']);
			$ws7->write($fila,1,n($liquidado),$f['moneda']);
			$ws7->write($fila,2,n($estandar),$f['moneda']);
			
			$formula = '='.fila_col($fila,1).'/'.fila_col($fila,2);
			$ws7->write($fila,3,$estandar? $formula:'0',$f['porcentaje']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws7->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<3; $i++) 
			$ws7->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['moneda_total']);
		$ws7->write($fila,3,'='.fila_col($fila,1).'/'.fila_col($fila,2),$f['porcentaje_total']);
	}

	//HOJA 12 TABLA 1
	$ws7 =& $wb->addWorksheet(__('Honorarios x Profesional'));
	iniciar_hoja($ws7,'Valor Liquidado por Profesional en UF',$fila,$col,$f,10.00);
	
	$encabezados = array('Profesional','Total Liquidado','Tarifa Estándar','Rentabilidad');
	print_headers($ws7,$encabezados,$fila,$f);
	
	if($usa_reporte_valores)
	{
		//Valor y Valor estándar:
		$r = $resultado[12];
		$r_c = $resultado[13];
		$r = Reporte::fixArray($r,$r_c);
		$r_c = Reporte::fixArray($r_c,$r);

		foreach($r as $k_a => $a)
		{
			if(is_array($a))
			{
				$ws7->write($fila,0,$k_a,$f['txt_valor']);
				$ws7->write($fila,1,n($a['valor']),$f['numeros']);
				$ws7->write($fila,2,n($r_c[$k_a]['valor']),$f['numeros']);

				$formula = '='.fila_col($fila,1).'/'.fila_col($fila,2);
				$ws7->write($fila,3,n($r_c[$k_a]['valor'])? $formula:'0',$f['porcentaje']);

				$fila++;
			}
		}
	}
	else
	{
		$query ="select  
		usuario.username,
		SUM( ( IF(cobro.forma_cobro='FLAT FEE',tarifa_hh_estandar,tarifa_hh) * TIME_TO_SEC( duracion_cobrada)/3600 ) * (cobro.monto_trabajos / IF(cobro.forma_cobro='FLAT FEE',IF(cobro.monto_thh_estandar>0,cobro.monto_thh_estandar,IF(cobro.monto_trabajos>0,cobro.monto_trabajos,1)),IF(cobro.monto_thh>0,cobro.monto_thh,IF(cobro.monto_trabajos>0,cobro.monto_trabajos,1))) ) * (cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base) / cobro_moneda.tipo_cambio ) as total_liquidado, 
		SUM(trabajo.tarifa_hh_estandar * (TIME_TO_SEC( duracion_cobrada)/3600) * (cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base)

								/   cobro_moneda.tipo_cambio) as total_tarifa_std 
		from trabajo
		JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
		LEFT JOIN cobro_moneda ON (cobro.id_cobro = cobro_moneda.id_cobro AND cobro_moneda.id_moneda = '3')
		LEFT JOIN usuario ON usuario.id_usuario=trabajo.id_usuario
		LEFT JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto
		WHERE cobro.estado NOT IN ('CREADO', 'EN REVISION','INCOBRABLE')
		and $where_fecha 
		GROUP BY usuario.id_usuario
		ORDER BY total_liquidado DESC";
		$fila_ini = $fila;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while(list($usuario,$liquidado,$estandar) = mysql_fetch_array($resp))
		{
			$ws7->write($fila,0,$usuario,$f['txt_valor']);
			$ws7->write($fila,1,n($liquidado),$f['moneda']);
			$ws7->write($fila,2,n($estandar),$f['moneda']);
			
			$formula = '='.fila_col($fila,1).'/'.fila_col($fila,2);
			$ws7->write($fila,3, $estandar? $formula:'0' ,$f['porcentaje']);
			$fila++;
		}
	}
	$fila_fin = $fila-1;

	if($fila_fin > $fila_ini)
	{
		$ws7->write($fila,0,__('Total'),$f['txt_total']);
		for($i = 1; $i<3; $i++) 
			$ws7->write($fila,$i,'=SUM('.fila_col($fila_ini,$i).':'.fila_col($fila_fin,$i).')',$f['moneda_total']);
		$ws7->write($fila,3,'='.fila_col($fila,1).'/'.fila_col($fila,2),$f['porcentaje_total']);
	}

    $wb->close();
?>
	
