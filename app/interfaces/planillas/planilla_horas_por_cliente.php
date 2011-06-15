<?
    require_once 'Spreadsheet/Excel/Writer.php';
    require_once dirname(__FILE__).'/../../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';

    $sesion = new Sesion( array('REP') );
    $pagina = new Pagina( $sesion );

    # En excel el tiempo se mide en dias, por eso se pasa el tiempo a segundos y se divide por 3600/24.0000 (0000 para que tenga 4 decimales y aproxime bien...)

    $query = "SELECT glosa_cliente, SUM(TIME_TO_SEC($horas))/(3600*24.0000), cliente.codigo_cliente
                FROM trabajo LEFT JOIN asunto USING (codigo_asunto)
                    LEFT JOIN cliente USING (codigo_cliente)
                WHERE fecha >= '$fecha1' AND fecha <= '$fecha2'
                GROUP BY asunto.codigo_cliente";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

    $wb = new Spreadsheet_Excel_Writer();

    $wb->setCustomColor(35, 220, 255, 220);
    $wb->setCustomColor(36, 255, 255, 220);

    $formato_encabezado =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Bold' => '1',
                                'Color' => 'black'));
    $formato_titulo =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Bold' => '1',
                                'Locked' => 1,
                                'Border' => 1,
                                'FgColor' => '35',
                                'Color' => 'black'));
    $formato_texto =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Locked' => 1,
                                'Border' => 1,
                                'Color' => 'black'));
    $formato_tiempo =& $wb->addFormat(array('Size' => 11,
                                'VAlign' => 'top',
                                'Align' => 'right',
                                'Border' => 1,
                                'Color' => 'black',
								'NumFormat' => '[h]:mm'));
    $formato_texto_total =& $wb->addFormat(array('Size' => 12,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Bold' => '1',
                                'Locked' => 1,
                                'Border' => 1,
                                'Color' => 'black'));
    $formato_tiempo_total =& $wb->addFormat(array('Size' => 12,
                                'Align' => 'right',
                                'Bold' => '1',
                                'Border' => 1,
                                'Color' => 'black',
								'NumFormat' => '[h]:mm'));

	$ws1 =& $wb->addWorksheet(__('Reportes'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1,0);
	$ws1->setZoom(75);

    $col_cliente = 1;
	$col_duracion = 2;

	$ws1->setColumn($col_cliente, $col_cliente, 25);
	$ws1->setColumn($col_duracion, $col_duracion, 15);

	$fila = 1;

	$ws1->write($fila, 1, __('PERIODO'), $formato_encabezado);
	$ws1->write($fila, 2, Utiles::sql2date($fecha1).' '.__('hasta').' '.Utiles::sql2date($fecha2), $formato_encabezado);
	$ws1->mergeCells($fila, 2, $fila, 5);
	++$fila;
	$hoy = date("d-m-Y");
	$ws1->write($fila, 1, __('FECHA REPORTE'), $formato_encabezado);
	$ws1->write($fila, 2, $hoy, $formato_encabezado);
	$fila += 2;

	$ws1->write($fila, $col_cliente, __('Cliente'), $formato_titulo);
	$ws1->write($fila, $col_duracion, __('Horas').' '.($horas=='duracion_cobrada'?__('cobrables'):__('trabajadas')), $formato_titulo);
	++$fila;

    $fila_inicial = $fila+1;
    while(list($cliente, $horas, $cod_cliente) = mysql_fetch_array($resp))
    {
        $ws1->write($fila, $col_cliente, $cliente, $formato_texto);
        $ws1->write($fila, $col_duracion, $horas, $formato_tiempo);
        ++$fila;
    }
	$col_formula_duracion = Utiles::NumToColumnaExcel($col_duracion);
	$ws1->write($fila, $col_cliente, __('Total'), $formato_texto_total);
	$ws1->writeFormula($fila, $col_duracion, "=SUM($col_formula_duracion$fila_inicial:$col_formula_duracion$fila)", $formato_tiempo_total);

	$wb->send("Planilla Horas por Cliente.xls");
	$wb->close();
    exit;
?>
