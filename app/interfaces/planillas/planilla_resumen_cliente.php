<?
  require_once 'Spreadsheet/Excel/Writer.php';
  require_once dirname(__FILE__).'/../../conf.php';
  require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
  require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
  require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
  require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';

  $sesion = new Sesion( array('REP') );
$fila_debug = 20;
  $pagina = new Pagina( $sesion );

  set_time_limit(300);
  
	$moneda = new Moneda($sesion);
	$moneda->Load($id_moneda);
	 
  if($moneda->fields['cifras_decimales'] == 0)
      $string_decimales = "";
  else if($moneda->fields['cifras_decimales'] == 1)
      $string_decimales = ".0";
  else if($moneda->fields['cifras_decimales'] == 2)
      $string_decimales = ".00";
  $simbolo_moneda = $moneda->fields['simbolo'];

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
								'NumFormat' => "[$$simbolo_moneda] #,###,0$string_decimales"));

  $formato_moneda_rojo =& $wb->addFormat(array('Size' => 11,
                              'VAlign' => 'top',
                              'Align' => 'right',
                              'Border' => 1,
                              'Bold' => 1,
                              'Color' => 'red',
							'NumFormat' => "[$$simbolo_moneda] #,###,0$string_decimales"));

  $periodo_inicial = substr($fecha_ini,0,4)*12+ substr($fecha_ini,5,2);
  $periodo_final = substr($fecha_fin,0,4)*12+ substr($fecha_fin,5,2);
  $time_periodo = strtotime($fecha_ini);

	$ws1 =& $wb->addWorksheet(__('Reportes'));
  $ws1->setInputEncoding('utf-8');
  $ws1->fitToPages(1,0);
  $ws1->setZoom(75);
	$ws1->setColumn( 1, 1, 46.00);
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

	$filas++;
		$glosa_comparacion = " Los montos facturados se comparan con el monto THH segun ";
		if( $tarifa = 'monto_thh' )
			$glosa_comparacion .= "tarifa estandar";
		else 
			$glosa_comparacion .= "tarifa del cliente";
		$ws1->write($filas, 1, $glosa_comparacion,$txt_opcion);
		$ws1->mergeCells( $filas, 1, $filas, 13 );

	$filas += 3;
	
	list($x_anio_ini, $x_mes_ini,$x_dia_ini)=split('-',$fecha_ini);
	list($x_anio_fin, $x_mes_fin,$x_dia_fin)=split('-',$fecha_fin);
		
	#MESES
  for($i=0;$i <= ($periodo_final-$periodo_inicial);$i++)
  {
    $ws1->write($filas, $i+2,date('M Y',$time_periodo), $titulo_filas); //Se imprime el titulo del periodo
    $time_periodo = strtotime('+1 month',$time_periodo);
	}


   
       $m=$x_mes_ini;
       
            for($a=$x_anio_ini;$a<=$x_anio_fin;$a++)
	{
                if( $a == $x_anio_fin ) {
                    $mes_f = $x_mes_fin;
                } else {
                    $mes_f = 12;
                }
                for(;$m<=$mes_f;$m++)
  	{
  			$dosdigitos = 2 - strlen($m);
  			if($dosdigitos>0)
  			{
  				$m = "0".$m;
  			}
  			$select_col .= " ,IF(DATE_FORMAT(fecha_emision,'%Y-%m')='".$a."-".$m."', id_cobro,null) AS emitido_".$a.$m." ";
  			$select_group .= " ,group_concat(emitido_".$a.$m.") AS list_idcobro_".$a.$m." ";			 			
			}
                $m = 1;
  	}

  $where= "";
    if(is_array($forma_cobro))
        $where = $where . " AND cobro.forma_cobro IN ('".join("','",$forma_cobro)."') ";
    if(is_array($clientes))
        $where = $where . " AND cobro.codigo_cliente IN ('".join("','",$clientes)."') ";
    if(is_array($monedas))
    		$where .= " AND cobro.opc_moneda_total IN ( '".join("','",$monedas)."') ";
    if(is_array($grupos))
    		$where .= " AND cliente.id_grupo_cliente IN ( '".join("','",$grupos)."') ";

	$query = "SELECT
						codigo_cliente
						,glosa_cliente
						,fecha_emision
						$select_group
						FROM(SELECT
						cliente.codigo_cliente as codigo_cliente
						,cliente.glosa_cliente as glosa_cliente
						,fecha_emision
						$select_col
						FROM cobro
						LEFT JOIN cliente AS cliente ON cobro.codigo_cliente=cliente.codigo_cliente
						WHERE cobro.estado <> 'CREADO' 
						AND cobro.estado <> 'EN REVISION'
						AND cobro.fecha_emision BETWEEN '$fecha_ini 00:00:01' AND '$fecha_fin 23:59:59' $where 
						)ZZ
						GROUP BY codigo_cliente
						ORDER BY glosa_cliente";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	//echo $query.'<br><br>';
		$campo_monto="monto";
		$campo_monto_thh=$tarifa;
	/*
	if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) ) ) 
	{
		$campo_monto="monto_total_cobro";
		$campo_monto_thh="monto_total_cobro_thh";
	}
	else
	{
		$campo_monto="monto";
		$campo_monto_thh="monto_thh";
	}*/
	
	$glosa_comentario = array();
	$ultimo_cliente = '';
	while($row = mysql_fetch_array($resp))
  {
  	$glosa_comentario[$row['codigo_cliente']] = array();
		$x_monto[$row['codigo_cliente']]['glosa_cliente']	=$row['glosa_cliente'];
	
                $m=$x_mes_ini;
        
	  	for($a=$x_anio_ini;$a<=$x_anio_fin;$a++)
  	{
                    if( $a == $x_anio_fin ) {
                        $mes_f = $x_mes_fin;
                    } else {
                        $mes_f = 12;
                    }
	  		for(;$m<=$mes_f;$m++)
	  	{
	  			$dosdigitos = 2 - strlen($m);
	  			if($dosdigitos>0)
	  			{
	  				$m = "0".$m;
	  			}
	  			if($row['list_idcobro_'.$a.$m]!=null)
					{
						$arr_idcobro_cliente=array();
						$arr_idcobro_cliente=explode(",",$row['list_idcobro_'.$a.$m]);
						if(count($arr_idcobro_cliente)>=0)
						{
							for($o=0;$o<count($arr_idcobro_cliente);$o++)
							{
								$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $arr_idcobro_cliente[$o]);
								$x_monto[$row['codigo_cliente']]['monto_'.$a.$m]	+=$x_resultados[$campo_monto][$id_moneda];
								$x_monto[$row['codigo_cliente']]['monto_thh_'.$a.$m]	+=$x_resultados[$campo_monto_thh][$id_moneda];
								$x_monto[$row['codigo_cliente']]['id_cobro_'.$a.$m] .= $arr_idcobro_cliente[$o] ." , ";
								if( $arr_idcobro_cliente[$o] > 0 )
									$glosa_comentario[$row['codigo_cliente']][$a.$m] = $glosa_comentario[$row['codigo_cliente']][$a.$m] . "C".$arr_idcobro_cliente[$o].": $simbolo_moneda".$x_resultados[$campo_monto][$id_moneda]. "\n";
							}
						}
						else
						{
							$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $row['list_idcobro_'.$a.$m]);
							$x_monto[$row['codigo_cliente']]['monto_'.$a.$m]	+=$x_resultados[$campo_monto][$id_moneda];
							$x_monto[$row['codigo_cliente']]['monto_thh_'.$a.$m]	+=$x_resultados[$campo_monto_thh][$id_moneda];
							$x_monto[$row['codigo_cliente']]['id_cobro_'.$a.$m] .= $row['list_idcobro_'.$a.$m] ." , ";
							if( $row['list_idcobro_'.$a.$m] > 0 )
								$glosa_comentario[$row['codigo_cliente']][$a.$m] = $glosa_comentario[$row['codigo_cliente']][$a.$m] . "C".$row['list_idcobro_'.$a.$m].": $simbolo_moneda".$x_resultados[$campo_monto][$id_moneda]. "\n";
						}
					}	
	  		}	
                        $m = 1;
	  	}
	
		if($ultimo_cliente != $row['codigo_cliente'])
		{
			$ultimo_cliente = $row['codigo_cliente'];
			$filas++;
			$total_clientes++;
				$ws1->write($filas,1,$x_monto[$row['codigo_cliente']]['glosa_cliente'],$titulo_filas);
			for($i=0;$i <= ($periodo_final-$periodo_inicial);$i++)
				$ws1->write($filas, 2+$i,'',$formato_moneda);
		}
		
		$col=1;
  	
                $m=$x_mes_ini;
                
	  	for($a=$x_anio_ini;$a<=$x_anio_fin;$a++)
  	{
                    if( $a == $x_anio_fin ) {
                        $mes_f = $x_mes_fin;
                    } else {
                        $mes_f = 12;
                    }
	  		for(;$m<=$mes_f;$m++)
	  	{
	  			$dosdigitos = 2 - strlen($m);
	  			if($dosdigitos>0)
	  			{
	  				$m = "0".$m;
	  			}
	  			$col++;
	  			if($x_monto[$row['codigo_cliente']]['monto_'.$a.$m] < $x_monto[$row['codigo_cliente']]['monto_thh_'.$a.$m])
					{
						$formato = $formato_moneda_rojo;
						}
					else
					{
						$formato = $formato_moneda;
					}
					if($x_monto[$row['codigo_cliente']]['monto_thh_'.$a.$m] == 0)
					{
						$diferencia_cobrada = "inf";
					}
					else {
						$diferencia_cobrada = floor(100*($x_monto[$row['codigo_cliente']]['monto_'.$a.$m])/$x_monto[$row['codigo_cliente']]['monto_thh_'.$a.$m])."%";
					}
					$glosa_comentario[$row['codigo_cliente']][$a.$m] .= "Valor THH: $simbolo_moneda ".$x_monto[$row['codigo_cliente']]['monto_thh_'.$a.$m]."\n";
					$glosa_comentario[$row['codigo_cliente']][$a.$m] .= "Cobrado/THH :". $diferencia_cobrada;
					if($x_monto[$row['codigo_cliente']]['monto_'.$a.$m]!=0)
					{
						$ws1->writeNote($filas,$col , $glosa_comentario[$row['codigo_cliente']][$a.$m]);
						$ws1->write($filas,$col , number_format($x_monto[$row['codigo_cliente']]['monto_'.$a.$m],$moneda->fields['cifras_decimales'],'.','') ,$formato);
					}
				}
                                $m=1;
			}
			
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
	}
	else
	{
		$ws1->mergeCells( $filas, 2, $filas, 13 );
	    $ws1->write($filas,2,__('No se encontraron resultados'),$txt_opcion);
    	for($x=3;$x<14;$x++)
        	$ws1->write($filas, $x, '', $txt_opcion);
	}
	$wb->close();
  exit;
?>
