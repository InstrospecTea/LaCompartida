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
  
	$wb->send('Clientes_datos_incompletos.xls');
	$ws1->setColumn( 1, 1, 18.14);
	$ws1->setColumn( 2, 2, 45.00);
	$ws1->setColumn( 3, 3, 40.00);
	$ws1->setColumn( 4, 4, 75.00);
	$ws1->setColumn( 5, 5, 20.00);
	$ws1->write(0, 0, 'Listado de Clientes con datos incompletos', $encabezado);
	$ws1->mergeCells (0, 0, 0, 8);
	$info_usr1 = str_replace('<br>',' - ',$PdfLinea1);
	$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
	$ws1->mergeCells (2, 0, 2, 8);
	$info_usr = str_replace('<br>',' - ',$PdfLinea2);
	$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
	$ws1->mergeCells (3, 0, 3, 8);
	$i=0;
	$fila_inicial = 7;

	$ws1->write($fila_inicial, 1, __('Código Cliente'), $tit);
	$ws1->write($fila_inicial, 2, __('Cliente'), $tit);
	$ws1->write($fila_inicial, 3, __('Asunto'), $tit);
	$ws1->write($fila_inicial, 4, __('Campos Faltantes'), $tit);

	$query = "SELECT
					c.glosa_cliente, c.id_usuario_encargado,
					ct.codigo_cliente, ct.rut, ct.factura_razon_social, ct.factura_giro, ct.factura_direccion,
					ct.cod_factura_telefono, ct.factura_telefono, ct.titulo_contacto, ct.contacto as nombre_contacto,
					ct.apellido_contacto, ct.fono_contacto, ct.email_contacto, ct.direccion_contacto, 
					ct.id_tarifa, ct.id_moneda, ct.forma_cobro, ct.opc_moneda_total, ct.observaciones,
					GROUP_CONCAT( glosa_asunto ) as asunto
				FROM cliente as c 
					JOIN contrato as ct ON ( c.codigo_cliente = ct.codigo_cliente )
					JOIN asunto as a ON ( ct.id_contrato = a.id_contrato )
				GROUP BY ct.id_contrato;";

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
		$where .= " DATE_FORMAT(fecha, '%Y-%m-%d') >= '$fecha_fin' ";
	}

	$user_temp = "";
	$incompletos = 0;
	$campos = "";
	$campos_tmp = "";
	$head = "";
	$nombre_campos = array(
		"Nombre", "Attache Secundario", "Código Cliente", 
		"RUC", "Razón Social", "Giro", "Dirección", "Código Teléfono", "Teléfono",
		"Título", "Nombre", "Apellido", "Teléfono", "E-mail", "Dirección Envío",
		"Tarifa Horas", "Tarifa en", "Forma de liquidaciones", "Mostrar en", "Detalle de Cobranza",
		"Asunto"
	);
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);	
	while($row = mysql_fetch_array($resp))
	{
		for( $i=0; $i<20; $i++)
		{
			if( strlen( $row[$i] ) == 0  )
			{
				$campos_tmp .= ( strlen( $campos_tmp ) > 0 ? ", " : "") . $nombre_campos[$i] ;
			}
			elseif( $row[$i] == '0' || $row[$i] == '-1' )
			{
				$campos_tmp .= ( strlen( $campos_tmp ) > 0 ? ", " : "") . $nombre_campos[$i] ;
			}
			
			if( $i >= 3 && $i < 9 )
			{
				$head = "Datos Facturacion: ";
				if( $i == 8 )
				{
					$campos .= ( strlen( $campos_tmp ) > 0 ? $head . $campos_tmp . "; \n" : "" );
					$campos_tmp = "";
				}
			}
			elseif( $i>=9 && $i < 15 )
			{
				$head = "Solicitante: ";
				if( $i == 14 )
				{
					$campos .= ( strlen( $campos_tmp ) > 0 ? $head . $campos_tmp . "; \n" : "" );
					$campos_tmp = "";
				}
			}
			elseif( $i>=15 && $i < 20 )
			{
				$head = "Tarificación: ";
				if( $i == 19 )
				{
					$campos .= ( strlen( $campos_tmp ) > 0 ? $head . $campos_tmp . ";" : "" );
					$campos_tmp = "";
				}
			}				
			else
			{
				$head = "Datos Cliente: ";
				if( $i == 2 )
				{
					$campos .= ( strlen( $campos_tmp ) > 0 ? $head . $campos_tmp . "; \n" : "" );
					$campos_tmp = "";
				}
			}
		}

		if( strlen( $campos ) > 0 )
		{
			$ws1->write($fila_inicial, 1, $row["codigo_cliente"], $f5);
			$ws1->write($fila_inicial, 2, $row["glosa_cliente"], $f4);	
			$ws1->write($fila_inicial, 3, $row["asunto"], $f4);
			$ws1->write($fila_inicial, 4, $campos, $f4);
			$incompletos++;
			$fila_inicial++;
			$campos = "";
		}
	}

	if( $incompletos == 0)
	{
		$ws1->write($fila_inicial, 1, "No se encontraron clientes con datos incompletos", $f5);
	}

  $wb->close();
  exit;
?>