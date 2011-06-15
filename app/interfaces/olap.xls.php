<?
    require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';

    $sesion = new Sesion( array('REP') );

	$pagina = new Pagina( $sesion );

	#$key = substr(md5(microtime().posix_getpid()), 0, 8);

	$wb = new Spreadsheet_Excel_Writer();

	$wb->send("Planilla $dimension1 vs $dimension2.xls");

	$wb->setCustomColor ( 35, 220, 255, 220 );

	$wb->setCustomColor ( 36, 255, 255, 220 );

    $encabezado =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Bold' => '1',
                                'Color' => 'black'));
    $tit =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Bold' => '1',
                                'Locked' => 1,
                                'Border' => 1,
                                'FgColor' => '35',
                                'Color' => 'black'));

    $f3c =& $wb->addFormat(array('Size' => 10,
                                'Align' => 'left',
                                'Bold' => '1',
                                'FgColor' => '35',
                                'Border' => 1,
                                'Locked' => 1,
                                'Color' => 'black'));

    $f4 =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Border' => 1,
                                'Color' => 'black'));
	$f4->setNumFormat("0");

    $time_format =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Border' => 1,
                                'Color' => 'black'));
    if($operacion == "COUNT(*)" )
        $time_format->setNumFormat('0');
    else
        $time_format->setNumFormat('[h]:mm');

    $total =& $wb->addFormat(array('Size' => 10,
                                'Align' => 'right',
                                'Bold' => '1',
                                'FgColor' => '36',
                                'Border' => 1,
                                'Color' => 'black'));
	$total->setNumFormat("0");



   $ws1 =& $wb->addWorksheet('Reportes');
   $ws1->setInputEncoding('utf-8');
   $ws1->fitToPages(1,0);
   $ws1->setZoom(75);

	$fila_inicial = 1;
	$columna_inicial = 1;

	for($i = 0; $i < count($d1s); $i++)
	{
		for($j = 0; $j < count($d2s); $j++)
		{
			if($j == 0) #d1s
				$ws1->write($fila_inicial, $columna_inicial + 1 + $i, $d1s[$i], $tit);
			if($i == 0) #d2s
			{
				$ws1->write($fila_inicial+1+$j, $columna_inicial , $d2s[$j], $tit);
			}
            $ws1->write($fila_inicial + 1 + $j, $columna_inicial + 1 + $i, $result[$d1s[$i]][$d2s[$j]], $time_format);
		}
	}

	$ws1->setColumn( 0, 0, 5.00);
	$ws1->setColumn( 1, 1, 20.00);
    $ws1->setColumn( 2, 2 + count($d1s),12.00);

	$columna_final = $columna_inicial + $i + 1;
	$fila_final = $fila_inicial + $j + 1;

	for($i = 0; $i < count($d1s); $i++)
	{
		$col_ini = Utiles::NumToColumnaExcel($fila_inicial + 1 +$i);
		$col_fin = Utiles::NumToColumnaExcel($fila_final - 1);
		$ws1->writeFormula($fila_final , $columna_inicial + 1 + $i,  "=SUM($col_ini".($fila_inicial+2).":$col_ini".($fila_final).")", $time_format);
	}
	for($j = 0; $j < count($d2s); $j++)
	{
		$col_ini = Utiles::NumToColumnaExcel(2);
		$col_fin = Utiles::NumToColumnaExcel(count($d1s) + 1);
		$ws1->writeFormula($fila_inicial + 1 + $j, $columna_final , "=SUM($col_ini".($fila_inicial+2+$j).":$col_fin".($fila_inicial+2+$j).")", $time_format);
	}

	$wb->close();

	exit;

?>
