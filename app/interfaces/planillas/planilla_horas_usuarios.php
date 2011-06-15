<?
    require_once 'Spreadsheet/Excel/Writer.php';
    require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';

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

	list($tmp,$mes_ini,$tmp) = split('-',$fechaini);
	for($i=0;$i<=$periodos;$i++)
   	{
            $hrs_secobra[$i] = 0;
            $hrs_cobradas[$i] = 0;
            $hrs_t[$i] = 0;
            $hrs_pcobrar[$i] = 0;
            $hrs_monto[$i] = 0;
			$mesperiodo[$i] = 0;


			#HORAS COBRADAS (REGISTRO EN COBROS)
            $query = "SELECT TIME_TO_SEC(duracion)/(3600),
                        TIME_TO_SEC(duracion_cobrada)/(3600),
                        trabajo.cobrable, trabajo.monto_cobrado_monedabase,
						DATE_FORMAT(CURDATE(),'%d-%m-%Y'), CURTIME(), cobro.estado
                        FROM trabajo
						LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
                        WHERE trabajo.fecha BETWEEN DATE_ADD('$fechaini',INTERVAL $i MONTH) AND DATE_ADD('$fechaini',INTERVAL ($i+1) MONTH)
                        AND trabajo.id_usuario IN ($wherein)";
            $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
				for($j = 0; list($horas,$horas_cobradas,$secobra,$montocobrado,$dateinfo,$timeinfo,$estado_cobro) = mysql_fetch_array($resp); $j++)
				{
					$hrs_trabajadas[$i] += $horas;
					if($secobra == 1)
					{
						$hrs_cobrables[$i] += $horas_cobradas;
					}
					if($estado_cobro == 'EMITIDO')
					{
						$hrs_cobradas[$i] += $horas_cobradas;
						$total_cobrado[$i] += $montocobrado;
					}
            	}
					$mesperiodo[$i]=$meses[($i+$mes_ini-1)%12];
    }


	#USUARIOS NOMBRES
	$query = "SELECT nombre, apellido1, apellido2
                FROM usuario WHERE id_usuario IN ($wherein)";
    $resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
    for($j = 0; list($nombres,$appat,$apmat) = mysql_fetch_array($resp); $j++)
    {
		$username[$j] = $nombres[0].". ".$appat;
		$topuser += 1;
	}

	$desdehasta = date("d-m-Y",strtotime($fechaini))."  AL  ".date("d-m-Y",strtotime($fechafin));


	#ARMANDO XLS
	$wb = new Spreadsheet_Excel_Writer();

    $wb->send("Planilla Resumen horas-usuarios.xls");

    $wb->setCustomColor ( 35, 220, 255, 220 );

    $wb->setCustomColor ( 36, 255, 255, 220 );

    $encabezado =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'left',
                                'Bold' => '1',
                                'Color' => 'black'));

	$opciones =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'left',
                                'Bold' => '0',
								'Border' => '1',
                                'Color' => 'black'));

    $titulos =& $wb->addFormat(array('Size' => 12,
                                'Valign' => 'top',
                                'Align' => 'center',
                                'Bold' => '1',
                                'Locked' => 1,
                                'Border' => 1,
                                'FgColor' => '35',
                                'Color' => 'black'));
	$titulos->setTextRotation(270);

	$titulo_opcion =& $wb->addFormat(array('Size' => 11,
                                'Align' => 'left',
                                'Bold' => '0',
                                'FgColor' => '',
                                'Border' => 1,
                                'Color' => 'black'));

	$titulo_filas =& $wb->addFormat(array('Size' => 12,
                                'Align' => 'center',
                                'Bold' => '1',
                                'FgColor' => '35',
                                'Border' => 1,
                                'Locked' => 1,
                                'Color' => 'black'));

	$numeros =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Border' => 1,
                                'Color' => 'black'));
    $numeros->setNumFormat("0");

    $numero_porcien =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Border' => 1,
                                'Color' => 'black'));
    $numero_porcien->setNumFormat("00%");


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

	$ws1->setColumn( 0, 1, 15.00);
    #$ws1->setColumn( 2, 6, 30.00);

	$filas = 2;


	#TIULOS - MERGE Y FREZ
    $ws1->mergeCells( $filas, 1, $filas, 10 );
    $ws1->write($filas, 1,__('REPORTE RESÚMEN DE HORAS'), $encabezado);

    for($x=2;$x<11;$x++)
        $ws1->write($filas, $x, '', $encabezado);

    $filas = 6;

	$ws1->write($filas,1,__('GENERADO EL:'),$titulo_opcion);
	$ws1->mergeCells( $filas, 2, $filas, 10 );
    $ws1->write($filas,2,date("d-m-Y H:i:s"),$opciones);
    for($x=3;$x<11;$x++)
        $ws1->write($filas, $x, '', $opciones);

	$filas ++;
	$ws1->write($filas,1,__('FECHAS:'),$titulo_opcion);
	$ws1->mergeCells( $filas, 2, $filas, 10 );
    $ws1->write($filas,2,$desdehasta,$opciones);
    for($x=3;$x<11;$x++)
        $ws1->write($filas, $x, '', $opciones);

	$filas +=2;
	$ws1->write($filas,1,__('USUARIOS:'),$titulo_opcion);
	$todos = '';
	for($z=0;$z<=$topuser;$z++)
	{
		$todos .= $par." ".$username[$z];
		$par=';';
	}

    $ws1->mergeCells( $filas, 2, $filas, 10 );
	$ws1->write($filas,2,$todos,$opciones);
	for($x=3;$x<11;$x++)
        $ws1->write($filas, $x, '', $opciones);


	$filas +=3;
	$ws1->setColumn( 2, 10, 15.00);
	$ws1->write($filas,1,__('Periodos'),$titulos);
	$ws1->write($filas,2,__('Hrs.Trabajadas'),$titulos);
	$ws1->write($filas,3,__('Hrs.Trabajadas cobrables'),$titulos);
	$ws1->write($filas,4,__('Cobrabilidad'),$titulos);
	$ws1->write($filas,5,__('Hrs.cobradas'),$titulos);
	$ws1->write($filas,6,__('% Cobrado'),$titulos);
	$ws1->write($filas,7,__('Valor cobrado'),$titulos);
	$ws1->write($filas,8,__('Valor estimado p/cobrar'),$titulos);
	$ws1->write($filas,9,__('Valor cobrado + p/cobrar'),$titulos);
	$ws1->write($filas,10,-_('Valor Hr. Vendida Prom.'),$titulos);
	$ws1->write($filas,11,__('Total vendido/H. Trabajadas'),$titulos);

	for($i=0;$i<=$periodos;$i++)
	{
		if(1)
		{
			$top += 1;
			$filas += 1;
			$ws1->write($filas,1,$mesperiodo[$i],$titulo_filas); #Mes
			$ws1->write($filas,2,round($hrs_trabajadas[$i]),$numeros); #hrs. trabajadas
			$ws1->write($filas,3,round($hrs_cobrables[$i]),$numeros); #hrs. cobrables

			#Cobrabilidad
			$col_ini = Utiles::NumToColumnaExcel(2);
	        $col_fin = Utiles::NumToColumnaExcel(3);
			$filasform = $filas + 1;
			$ws1->writeFormula($filas, 4, "=ROUND(IF($col_ini".$filasform.">0;$col_fin".$filasform."/$col_ini".$filasform.";\"\");2)", $numero_porcien);

			#Hrs. cobradas
			$ws1->write($filas,5,round($hrs_cobradas[$i]),$numeros);

			#hrs. cobradas / hrs. cobrables
			$col_ini = Utiles::NumToColumnaExcel(5);
            $ws1->writeFormula($filas, 6,  "=ROUND(IF($col_fin".$filasform.">0;($col_ini".$filasform."/$col_fin".$filasform.");\"\");2)", $numero_porcien);

			#valor total cobrado
			$ws1->write($filas,7,round($total_cobrado[$i]),$formato_moneda);

			#Valor estimado por cobrar
			if($hrs_cobradas[$i] >0)
				$dif_valorhora = $total_cobrado[$i]/$hrs_cobradas[$i];
			$dif_horas = $hrs_cobrables[$i] - $hrs_cobradas[$i];
			$valor_estimado = $dif_horas * $dif_valorhora;
			$ws1->write($filas,8,round($valor_estimado,0),$formato_moneda);

			# Valor cobrado + p/cobrar
            $ws1->writeFormula($filas, 9,  "=". Utiles::NumToColumnaExcel(7).$filasform."+".Utiles::NumToColumnaExcel(8).$filasform,$formato_moneda);


			#Valor Estimado Hora
			$col_letra_ini = Utiles::NumToColumnaExcel(9);
			$col_letra_fin = Utiles::NumToColumnaExcel(3);
            $ws1->writeFormula($filas, 10,  "=IF($col_letra_fin".$filasform.">0;$col_letra_ini".$filasform."/$col_letra_fin".$filasform.";\"\")", $formato_moneda);

            $col_letra_fin = Utiles::NumToColumnaExcel(2);
            $ws1->writeFormula($filas, 11,  "=IF($col_letra_fin".$filasform.">0;$col_letra_ini".$filasform."/$col_letra_fin".$filasform.";\"\")", $formato_moneda);
		}
	}

	#Totales
	$topini = $filas - ($top - 2);
    $filas += 1;
	$ws1->write($filas,1,'TOTAL',$titulo_filas);
	for($i=2;$i<11;$i++)
	{
		$columna = Utiles::NumToColumnaExcel($i);
    	switch ($i)
		{
		case 4:
		 	$colfin = Utiles::NumToColumnaExcel(3);
			$colini = Utiles::NumToColumnaExcel(2);
			$ws1->writeFormula($filas, $i, "=ROUND(IF($colini".($filas+1).">0;($colfin".($filas+1)."/$colini".($filas+1).");0);2)",$numero_porcien);
		break;

		case 6:
			$colfin = Utiles::NumToColumnaExcel(3);
            $colini = Utiles::NumToColumnaExcel($i-1);
            $ws1->writeFormula($filas, $i, "=ROUND(IF($colfin".($filas+1).">0;($colini".($filas+1)."/$colfin".($filas+1).");0);2)",$numero_porcien);
		break;
		case 7:
			$ws1->writeFormula($filas, $i, "=SUM($columna".$topini.":$columna".$filas.")",$formato_moneda);
		break;

		case 8:
			$ws1->writeFormula($filas, $i, "=SUM($columna".$topini.":$columna".$filas.")",$formato_moneda);
		break;

		case 9:
			$col_letra_ini = Utiles::NumToColumnaExcel(7);
			$col_letra_fin = Utiles::NumToColumnaExcel(3);
			$ws1->writeFormula($filas, $i,"=IF($col_letra_fin".($filas+1).">0;$col_letra_ini".($filas+1)."/$col_letra_fin".($filas+1).";\"\")", $formato_moneda);// "=AVERAGE($columna".$topini.":$columna".$filas.")",$formato_moneda);
		break;

		case 10:
			$ws1->writeFormula($filas, $i, "=AVERAGE($columna".$topini.":$columna".$filas.")",$formato_moneda);
		break;

		default:
			$ws1->writeFormula($filas, $i, "=SUM($columna".$topini.":$columna".$filas.")",$numeros);
		}
	}

	$wb->close();

    exit;

    function Encabezado($ws1)
    {
        global $titulos, $fila, $estados, $nombres;
		for($w = 0; $w < count($estados); $w++)
            $ws1->write($fila, $w+2, $nombres[$estados[$w]], $titulos);
        $fila++;
    }
?>
