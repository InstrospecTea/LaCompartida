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
	
	$meses[0]='Enero';
    $meses[1]='Febrero';
    $meses[2]='Marzo';
    $meses[3]='Abril';
    $meses[4]='Mayo';
    $meses[5]='Junio';
    $meses[6]='julio';
    $meses[7]='Agosto';
    $meses[8]='Septiembre';
    $meses[9]='Octubre';
    $meses[10]='Noviembre';
    $meses[11]='Diciembre';

	
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
                                'Align' => 'justify',
                                'Border' => 1,
                                'Color' => 'black'));
    $formato_moneda->setNumFormat("$#,##");

	$ws1 =& $wb->addWorksheet(__('Reportes'));
    $ws1->setInputEncoding('utf-8');
    $ws1->fitToPages(1,0);
    $ws1->setZoom(75);
	$ws1->setColumn( 1, 1, 26.00);
    $ws1->setColumn( 2, 13, 15.15);

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
    $ws1->write($filas,1,__('AÑO RESUMEN'),$txt_opcion);
    $ws1->mergeCells( $filas, 2, $filas, 13 );
    $ws1->write($filas,2,$anio,$txt_opcion);
    for($x=3;$x<14;$x++)
        $ws1->write($filas, $x, '', $txt_opcion);	

	$filas +=4;

	#MESES
	for($z=0;$z<12;$z++)
    {
    	$ws1->write($filas,$z+2,$meses[$z],$titulo_filas);
	}

	#CLIENTE
	$anio_prox = (int)$anio + 1;
	$total_clientes = 0;
	$query = "SELECT
				cliente.codigo_cliente,cliente.glosa_cliente,cobro.fecha_cobro,cobro.estado
				FROM cliente
				Inner Join cobro ON cobro.codigo_cliente = cliente.codigo_cliente
				WHERE
				cobro.fecha_cobro >= '$anio-01-01' AND cobro.fecha_cobro < '$anio_prox-01-01' AND cobro.estado = 'PAGADO'
				GROUP BY cliente.codigo_cliente";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
    for($x=0;$x<list($cod_cliente,$glosa) = mysql_fetch_array($resp);$x++)
    {
		$filas +=1;
		$total_clientes += 1;
        $ws1->write($filas,1,$glosa,$titulo_filas);
		
		#MES X MES
		for($z=1;$z<13;$z++)
		{
			$mes_sql = sprintf("%02s",$z);
			$fecha_sql = $anio.'-'.$mes_sql.'-'.'01';
			$mes_prox = sprintf("%02s",$z+1);
			$squery = "SELECT
							cobro.codigo_cliente,
							SUM(cobro.monto_cobrado_monedabase) as monto
							FROM cobro
							Inner Join cliente ON cliente.codigo_cliente = cobro.codigo_cliente
							WHERE
							estado =  'PAGADO' AND
							fecha_cobro >= '$fecha_sql' AND fecha_cobro < '$anio-$mes_prox-01'
							AND cobro.codigo_cliente = '$cod_cliente'
							GROUP BY codigo_cliente";
			$sresp = mysql_query($squery, $sesion->dbh) or Utiles::errorSQL($squery,__FILE__,__LINE__,$sesion->dbh);
			if(list($codigo_cliente,$monto) = mysql_fetch_array($sresp))
			{
				$ws1->write($filas,$z+1,$monto,$formato_moneda);	
			}
			else
			{
				$ws1->write($filas,$z+1,'0',$formato_moneda);
			}
		}
	}

	#TOTALES
	$fila_inicial = ($filas - $total_clientes)+2;
	$filas +=1;
	
	if($total_clientes >0)
	{
		$ws1->write($filas,1,__('Total'),$titulo_filas);
		for($z=2;$z<14;$z++)
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
