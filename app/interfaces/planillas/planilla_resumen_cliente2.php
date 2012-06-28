<?
    require_once 'Spreadsheet/Excel/Writer.php';
    require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
		require_once Conf::ServerDir().'/classes/Cobro.php';

    $sesion = new Sesion( array('REP') );

    $pagina = new Pagina( $sesion );

    $moneda_base = Utiles::MonedaBase($sesion);
    if($moneda_base['cifras_decimales'] == 0)
        $string_decimales = "";
    else if($moneda_base['cifras_decimales'] == 1)
        $string_decimales = ".0";
    else if($moneda_base['cifras_decimales'] == 2)
        $string_decimales = ".00";

	#ARMANDO XLS
    $wb = new Spreadsheet_Excel_Writer();

    $wb->send("Planilla montos facturados.xls");

    $wb->setCustomColor ( 35, 220, 255, 220 );

    $wb->setCustomColor ( 36, 255, 255, 220 );

	$encabezado =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'left',
                                'Bold' => '1',
								'underline'=>1,
                                'Color' => 'black'));

	$txt_opcion =& $wb->addFormat(array('Size' => 11,
                                'Valign' => 'top',
                                'Align' => 'left',
                                'Color' => 'black'));

	$numeros =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'justify',
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
                                'Color' => 'black',
								'NumFormat' => "[$".$moneda_base['simbolo']."] #,###,0$string_decimales"));

    $formato_moneda_rojo =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'right',
                                'Border' => 1,
                                'Bold' => 1,
                                'Color' => 'red',
								'NumFormat' => "[$".$moneda_base['simbolo']."] #,###,0$string_decimales"));

    $periodo_inicial = substr($fecha_ini,0,4)*12+ substr($fecha_ini,5,2);
    $periodo_final = substr($fecha_fin,0,4)*12+ substr($fecha_fin,5,2);
    $time_periodo = strtotime($fecha_ini);

		$ws1 =& $wb->addWorksheet(__('Reportes'));
    $ws1->setInputEncoding('utf-8');
    $ws1->fitToPages(1,0);
    $ws1->setZoom(75);
		$ws1->setColumn( 1, 1, 26.00);
    $ws1->setColumn( 2, 2 + ($periodo_final-$periodo_inicial), 15.15);

 		$filas += 1;
    #TIULOS - MERGE Y FREZ
    $ws1->mergeCells( $filas, 1, $filas, 13 );
    $ws1->write($filas, 1, __('REPORTE TOTAL MONTOS FACTURADOS MENSUALMENTE'), $encabezado);
    for($x=2;$x<14;$x++)
        $ws1->write($filas, $x, '', $encabezado);
		$filas +=2;
		$ws1->write($filas,1,__('GENERADO EL:'),$txt_opcion);
    $ws1->mergeCells( $filas, 2, $filas, 13 );
    $ws1->write($filas,2,date("d-m-Y H:i:s"),$txt_opcion);
    for($x=3;$x<14;$x++)
        $ws1->write($filas, $x, '', $txt_opcion);

	$filas +=1;
    $ws1->write($filas,1,__('PERIODO ENTRE:'),$txt_opcion);
    $ws1->mergeCells( $filas, 2, $filas, 13 );
    $ws1->write($filas,2,"$fecha_ini HASTA $fecha_fin",$txt_opcion);
    for($x=3;$x<14;$x++)
        $ws1->write($filas, $x, '', $txt_opcion);

	$filas +=4;

	#MESES
    for($i=0;$i <= ($periodo_final-$periodo_inicial);$i++)
    {
        $ws1->write($filas, $i+2,date('M Y',$time_periodo), $titulo_filas); //Se imprime el titulo del periodo
        $time_periodo = strtotime('+1 month',$time_periodo);
	}

	$where= "";
    if(is_array($forma_cobro))
        $where = $where . " AND cobro.forma_cobro IN ('".join("','",$forma_cobro)."') ";
    if(is_array($clientes))
        $where = $where . " AND cobro.codigo_cliente IN ('".join("','",$clientes)."') ";


	#CLIENTE
	if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) )  ) 
		{
			$query = "SELECT cobro.codigo_cliente, 
									cl.glosa_cliente, 
									SUM((cobro.monto+cobro.monto_gastos)*cmmt.tipo_cambio/cmmb.tipo_cambio) AS monto_cobrado_monedabase, 
									SUM((cobro.monto_thh+cobro.monto_gastos)*cmmt.tipo_cambio/cmmb.tipo_cambio) AS monto_thh_monedabase, 
									YEAR(cobro.fecha_emision) AS periodo_ano, 
									MONTH(cobro.fecha_emision) AS periodo_mes 
								FROM cobro 
									JOIN cobro_moneda AS cmmb ON ( cobro.id_cobro=cmmb.id_cobro AND cobro.id_moneda_base=cmmb.id_moneda ) 
									JOIN cobro_moneda AS cmmt ON ( cobro.id_cobro=cmmt.id_cobro AND cobro.opc_moneda_total=cmmt.id_moneda ) 
									LEFT JOIN cliente AS cl ON cobro.codigo_cliente=cl.codigo_cliente 
								WHERE cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' 
									AND cobro.fecha_emision BETWEEN '$fecha_ini' AND '$fecha_fin 23:59:59' $where 
								GROUP BY cobro.codigo_cliente, year(cobro.fecha_emision), month(cobro.fecha_emision) 
								ORDER BY cl.glosa_cliente";
		}
	else
		{
			$query = "SELECT cliente.codigo_cliente, 
								cliente.glosa_cliente, 
								SUM(cobro.monto*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base) AS monto_cobrado_monedabase, 
								SUM(cobro.monto_thh*cobro.tipo_cambio_moneda/cobro.tipo_cambio_moneda_base) AS monto_thh_monedabase, 
								YEAR(cobro.`fecha_emision`) AS periodo_ano, 
								MONTH(cobro.`fecha_emision`) AS periodo_mes 
							FROM cobro 
								LEFT JOIN cliente ON cobro.`codigo_cliente` = cliente.`codigo_cliente`
							WHERE cobro.estado <> 'CREADO' AND cobro.estado <> 'EN REVISION' 
								AND fecha_emision BETWEEN '$fecha_ini' AND '$fecha_fin 23:59:59'
							$where
							GROUP BY cobro.codigo_cliente, year(cobro.`fecha_emision`), month(cobro.`fecha_emision`)
							ORDER BY cliente.glosa_cliente";
		}

	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$ultimo_cliente = '';
    while($row = mysql_fetch_array($resp))
    {
		if($ultimo_cliente != $row['codigo_cliente'])
		{
			$ultimo_cliente = $row['codigo_cliente'];
			$filas +=1;
			$total_clientes += 1;
        	$ws1->write($filas,1,$row['glosa_cliente'],$titulo_filas);
			for($i=0;$i <= ($periodo_final-$periodo_inicial);$i++)
				$ws1->write($filas, 2+$i,'',$formato_moneda);
		}
		$periodo = $row['periodo_ano']*12 + $row['periodo_mes'] - $periodo_inicial;
		if($row['monto_cobrado_monedabase'] < $row['monto_thh_monedabase'])
		{
			$formato = $formato_moneda_rojo;
			if($row['monto_thh_monedabase'] == 0)
				$row['monto_thh_monedabase'] = 1;
			$diferencia_cobrada = floor(100*($row['monto_cobrado_monedabase'])/$row['monto_thh_monedabase']);
			$ws1->writeNote($filas,2 + $periodo,__('El valor cobrado es menor al valor según tasa de horas hombres. Cobrado/THH :'). $diferencia_cobrada . "%");
		}
		else
			$formato = $formato_moneda;
		$ws1->write($filas,2 + $periodo ,$row['monto_cobrado_monedabase'] ,$formato);
	}

	#TOTALES
	$fila_inicial = ($filas - $total_clientes)+2;
	$filas +=1;

	if($total_clientes >0)
	{
		$ws1->write($filas,1,__('Total'),$titulo_filas);
		for($z=2;$z<=2+($periodo_final-$periodo_inicial);$z++)
    	{
			$columna = Utiles::NumToColumnaExcel($z);
			$ws1->writeFormula($filas, $z, "=SUM($columna".$fila_inicial.":$columna".$filas.")",$formato_moneda);
		}
	}else{
		$ws1->mergeCells( $filas, 2, $filas, 13 );
	    $ws1->write($filas,2,__('No se encontraron resultados'),$txt_opcion);
    	for($x=3;$x<14;$x++)
        	$ws1->write($filas, $x, '', $txt_opcion);
	}
	$wb->close();
    exit;
?>
