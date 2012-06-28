<?php
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
    require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once 'Spreadsheet/Excel/Writer.php';

	//Parámetros generales para los 2 casos de listas a extraer
	$sesion = new Sesion( array('REV','ADM') );
	$pagina = new Pagina( $sesion );

	#$key = substr(md5(microtime().posix_getpid()), 0, 8);
	$wb = new Spreadsheet_Excel_Writer();
	$wb->setCustomColor ( 35, 220, 255, 220 );
	$wb->setCustomColor ( 36, 255, 255, 220 );
	$encabezado =& $wb->addFormat(array('Size' => 12,
	                              'VAlign' => 'top',
	                              'Align' => 'justify',
	                              'Bold' => '1',
	                              'Color' => 'black'));
	$tit =& $wb->addFormat(array('Size' => 12,
	                              'VAlign' => 'top',
	                              'Align' => 'center',
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
	$f5 =& $wb->addFormat(array('Size' => 10,
	                              'VAlign' => 'top',
	                              'Align' => 'center',
	                              'Border' => 1,
	                              'Color' => 'black'));
	$ws1 =& $wb->addWorksheet(__('Tipo de Cambio'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1,0);
	$ws1->setZoom(75);
	#$ws1->protect( $key );
	if (method_exists('Conf','GetConf'))
	{
		$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
		$PdfLinea2 = Conf::GetConf($sesion, 'PdfLinea2');
	}
	else
	{
		$PdfLinea1 = Conf::PdfLinea1();
		$PdfLinea2 = Conf::PdfLinea2();
	}
	
	$where = 1;
  
	//Lista de vacaciones
	if(!empty($tipo_cambio))
	{
		$wb->send('Historico_tipo_de_cambio.xls');
		$ws1->setColumn( 1, 1, 25.00);
		$ws1->setColumn( 2, 2, 15.00);
		$ws1->setColumn( 3, 3, 20.00);
		$ws1->setColumn( 4, 4, 20.00);
		$ws1->setColumn( 5, 5, 20.00);
		$ws1->write(0, 0, 'Listado de tipo de cambio', $encabezado);
		$ws1->mergeCells (0, 0, 0, 8);
		$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
		$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
		$ws1->mergeCells (2, 0, 2, 8);
		$info_usr = str_replace('<br>',' - ',$PdfLinea2);
		$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
		$ws1->mergeCells (3, 0, 3, 8);
		$i=0;
		$fila_inicial = 7;
		
		$query = "SELECT 
						fecha, ";
						/*,    
						SUM( CASE id_moneda WHEN '2' THEN valor ELSE 0 END ),
						SUM( CASE id_moneda WHEN '3' THEN valor ELSE 0 END ),
						SUM( CASE id_moneda WHEN '4' THEN valor ELSE 0 END ),
						SUM( CASE id_moneda WHEN '5' THEN valor ELSE 0 END ),
						SUM( CASE id_moneda WHEN '6' THEN valor ELSE 0 END )
					FROM moneda_historial
					GROUP BY fecha;";*/
		$ws1->write($fila_inicial, 1, __('Fecha'), $tit);
		$num_monedas_tmp = 0;
		$lista = new ListaMonedas($sesion,"","SELECT * FROM prm_moneda");		
		$moneda = new Moneda($sesion);
		$num_monedas_tmp = $lista->num;
	    for($x=0;$x<$lista->num;$x++)
	    {
    	    $mon = $lista->Get($x);
			$moneda->Load($mon->fields['id_moneda']);
			if($x > 0)
			{
				$query .= ",
						";
			}
			$ws1->write($fila_inicial, $x+2, $moneda->fields['glosa_moneda'], $tit);
			$query .= "SUM( CASE id_moneda WHEN '" . $moneda->fields['id_moneda'] . "' THEN valor ELSE 0 END )";
		}
		


		$fila_inicial++;
		$where = "";
		if( $fecha_ini )
		{
			$fecha_ini = Utiles::fecha2sql($fecha_ini);			
			$where .= ( strlen( $where ) > 0 ? " AND " : " WHERE " );			
			$where .= " DATE_FORMAT(fecha, '%Y-%m-%d') >= '$fecha_ini' ";
		}
		
		if( $fecha_fin )
		{
			$fecha_fin = Utiles::fecha2sql($fecha_fin);			
			$where .= ( strlen( $where ) > 0 ? " AND " : " WHERE " );			
			$where .= " DATE_FORMAT(fecha, '%Y-%m-%d') <= '$fecha_fin' ";
		}
		
		$query .= "FROM moneda_historial" . $where . "
			GROUP BY fecha;";
		
		$fecha_formato = UtilesApp::ObtenerFormatoFecha($sesion);
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);	
		while($row = mysql_fetch_array($resp))
		{
			$i=0;
			
			$ws1->write($fila_inicial, 1, Utiles::sql2date($row[0], $fecha_formato, "-"), $f5);
			for( $cm= 1; $cm <= $num_monedas_tmp; $cm++)
			{
				$ws1->write( $fila_inicial , ($cm+1), $row[$cm], $f5);
			}
			$fila_inicial++;
		}
	}

  $wb->close();
  exit;
?>