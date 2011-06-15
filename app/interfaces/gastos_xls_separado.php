<?
	require_once dirname(__FILE__).'/../conf.php';
    require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
    require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
    require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
    require_once Conf::ServerDir().'/../fw/classes/Lista.php';
    require_once Conf::ServerDir().'/../app/classes/Debug.php';
    require_once Conf::ServerDir().'/../app/classes/Moneda.php';
    require_once Conf::ServerDir().'/../app/classes/Gasto.php';
    require_once Conf::ServerDir().'/classes/Funciones.php';
    require_once 'Spreadsheet/Excel/Writer.php';

    $sesion = new Sesion( array('OFI','COB') );

    $pagina = new Pagina( $sesion );

    #$key = substr(md5(microtime().posix_getpid()), 0, 8);

    $wb = new Spreadsheet_Excel_Writer();

    $wb->send('Planilla_gastos.xls');

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
    $time_format->setNumFormat('[h]:mm');
	
	$total =& $wb->addFormat(array('Size' => 10,
                                'Align' => 'right',
                                'Bold' => '1',
                                'FgColor' => '36',
                                'Border' => 1,
                                'Color' => 'black'));
    $total->setNumFormat("0");

	########################################## PRIMERA HOJA  #########################################

		$ws1 =& $wb->addWorksheet(__('Gastos'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,0);
		$ws1->setZoom(75);

		// se setea el ancho de las columnas
		$ws1->setColumn( 0, 0,  18.00);
		$ws1->setColumn( 1, 1,  45.00);
		$ws1->setColumn( 2, 2,  40.00);
		$ws1->setColumn( 3, 3,  55.00);
		$ws1->setColumn( 4, 4,  18.00);
		$ws1->setColumn( 5, 5,  18.00);
		$ws1->setColumn( 6, 6,  18.00);
		$ws1->setColumn( 7, 7,  18.00);
		
		$ws1->write(0, 0, __('Resúmen de gastos'), $encabezado);
		$ws1->mergeCells (0, 0, 0, 8);

		if($fecha1 && $fecha2)
			$ws1->write(1, 0, __('Entre el ').$fecha1.__(' y el ').$fecha2, $encabezado);
		else if($fecha1)
			$ws1->write(1, 0, __('Desde el ').$fecha1, $encabezado);
		else if($fecha2)	
			$ws1->write(1, 0, __('Antes de ').$fecha2, $encabezado);
		$ws1->mergeCells (1, 0, 1, 8);
		
		if($codigo_cliente)
		{
			$info_usr1 = str_replace('<br>',' - ', 'Cliente: '.Utiles::Glosa( $sesion, $codigo_cliente, 'glosa_cliente', 'cliente', 'codigo_cliente'));
			$ws1->write(2, 0, utf8_decode($info_usr1), $encabezado);
			$ws1->mergeCells (2, 0, 2, 8);
		}
		if($codigo_asunto)
		{
			$info_usr = str_replace('<br>',' - ', 'Asunto: '.Utiles::Glosa( $sesion, $codigo_asunto, 'glosa_asunto', 'asunto', 'codigo_asunto'));
			$ws1->write(3, 0, utf8_decode($info_usr), $encabezado);
			$ws1->mergeCells (3, 0, 3, 8);
		}
		########################### SQL INFORME DE GASTOS #########################
		$where = " ingreso IS NULL ";
		if($codigo_cliente)
		{
			$where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($codigo_cliente);
			$total_cta = number_format($cliente->TotalCuentaCorriente(),0,",",".");
		}		
		if($codigo_asunto)
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto'";
		if($id_usuario_orden)
			$where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
		if($id_tipo)
			$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
		if($clientes_acitvos == 'activos')
			$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
		if($clientes_activos == 'inactivos')
			$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
		if($fecha1 && $fecha2)
			$where .= " AND cta_corriente.fecha BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
		else if($fecha1)
			$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."' ";
		else if($fecha2)
			$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2)."' ";
		
		$moneda_base = Utiles::MonedaBase($sesion);
		$moneda = new Moneda($sesion);
		$total_balance_egreso = 0;
		$total_balance_ingreso = 0;

		$query = "SELECT cta_corriente.egreso, cta_corriente.ingreso, cta_corriente.codigo_cliente,
					cliente.glosa_cliente, cta_corriente.id_cobro, cta_corriente.id_moneda, prm_moneda.simbolo,
					cta_corriente.fecha, asunto.glosa_asunto, cta_corriente.descripcion, prm_moneda.cifras_decimales,
					prm_cta_corriente_tipo.glosa as glosa_tipo, cta_corriente.numero_documento
					FROM cta_corriente 
					LEFT JOIN asunto USING(codigo_asunto)
					LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
					LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
					JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
					WHERE $where";
		$lista_gastos = new ListaGastos($sesion,'',$query);
		for( $v=0; $v < $lista_gastos->num; $v++ )
		{
			$gasto = $lista_gastos->Get($v);
			
			$tipo_cambio = GetTipoCambioMoneda($sesion, $gasto->fields['id_moneda']);
			if($gasto->fields['egreso'] > 0 )
				$total_balance_egreso += ($gasto->fields['egreso'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
			if($gasto->fields['ingreso'] > 0)
				$total_balance_ingreso += ($gasto->fields['ingreso'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
		}
		if($total_balance_egreso > 0 && $total_balance_ingreso > 0)
			$total_balance = $total_balance_ingreso  - $total_balance_egreso;
		elseif($total_balance_egreso > 0)
			$total_balance = $total_balance_egreso;
		elseif($total_balance_ingreso > 0)
			$total_balance = $total_balance_ingreso;			
			
		
		$ws1->write(5, 0, __("Total balance").': '.$moneda_base['simbolo'].' '.number_format($total_balance,$moneda_base['cifras_decimales'],',','.'), $encabezado);
		$ws1->mergeCells (5, 0, 5, 8);
    
    $fila_inicial = 7;
    $ws1->write($fila_inicial, 0, __('Fecha'), $tit);
    $ws1->write($fila_inicial, 1, __('Cliente'), $tit);
    $ws1->write($fila_inicial, 2, __('Asunto'), $tit);
    $ws1->write($fila_inicial, 3, __('Descripción'), $tit);
    $ws1->write($fila_inicial, 4, __('Egreso'), $tit);
    $ws1->write($fila_inicial, 5, __('Cobro'), $tit);

	$columna_actual = 6;
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
	{
			$ws1->write($fila_inicial, $columna_actual, __('Tipo'), $tit);
			$columna_actual++;
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
				$ws1->write($fila_inicial, $columna_actual, __('N° Documento'), $tit);
		}
    $fila_inicial++;    
    if($orden == "")
			$orden = "fecha DESC";		
		
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	  while($row = mysql_fetch_array($resp2))
    {
	    $ws1->write($fila_inicial, 0, Utiles::sql2date($row[fecha]), $f4);
	    $ws1->write($fila_inicial, 1, $row[glosa_cliente], $f4);
	    $ws1->write($fila_inicial, 2, $row[glosa_asunto], $f4);
	    $ws1->write($fila_inicial, 3, $row[descripcion], $f4);
	    $ws1->write($fila_inicial, 4, $row[simbolo] . " " .number_format($row[egreso],$row[cifras_decimales],",","."), $f4);            
	    $ws1->write($fila_inicial, 5, $row[id_cobro], $f4);
		
		$columna_actual = 6;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
		{
				$ws1->write($fila_inicial, $columna_actual, $row[glosa_tipo], $f4);
				$columna_actual++;
		}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
				$ws1->write($fila_inicial, $columna_actual, $row[numero_documento], $f4);
				$columna_actual++;
		}

		$fila_inicial++;
		}

	############################# SEGUNDA HOJA ################################

	
		$ws2 =& $wb->addWorksheet(__('Provisiones'));
		$ws2->setInputEncoding('utf-8');
		$ws2->fitToPages(1,0);
		$ws2->setZoom(75);

		// se setea el ancho de las columnas
		$ws2->setColumn( 0, 0,  18.00);
		$ws2->setColumn( 1, 1,  45.00);
		$ws2->setColumn( 2, 2,  40.00);
		$ws2->setColumn( 3, 3,  55.00);
		$ws2->setColumn( 4, 4,  18.00);
		$ws2->setColumn( 5, 5,  18.00);
		$ws2->setColumn( 6, 6,  18.00);
		$ws2->setColumn( 7, 7,  18.00);
		
		$ws2->write(0, 0, __('Resúmen de provisiones'), $encabezado);
		$ws2->mergeCells (0, 0, 0, 8);

		if($fecha1 && $fecha2)
			$ws2->write(1, 0, __('Entre el ').$fecha1.__(' y el ').$fecha2, $encabezado);
		else if($fecha1)
			$ws2->write(1, 0, __('Desde el ').$fecha1, $encabezado);
		else if($fecha2)	
			$ws2->write(1, 0, __('Antes de ').$fecha2, $encabezado);
		$ws2->mergeCells (1, 0, 1, 8);
		
		if($codigo_cliente)
		{
			$info_usr1 = str_replace('<br>',' - ', 'Cliente: '.Utiles::Glosa( $sesion, $codigo_cliente, 'glosa_cliente', 'cliente', 'codigo_cliente'));
			$ws2->write(2, 0, utf8_decode($info_usr1), $encabezado);
			$ws2->mergeCells (2, 0, 2, 8);
		}
		if($codigo_asunto)
		{
			$info_usr = str_replace('<br>',' - ', 'Asunto: '.Utiles::Glosa( $sesion, $codigo_asunto, 'glosa_asunto', 'asunto', 'codigo_asunto'));
			$ws2->write(3, 0, utf8_decode($info_usr), $encabezado);
			$ws2->mergeCells (3, 0, 3, 8);
		}
		########################### SQL INFORME DE GASTOS #########################
		$where = " ingreso IS NOT NULL ";
		if($codigo_cliente)
		{
			$where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($codigo_cliente);
			$total_cta = number_format($cliente->TotalCuentaCorriente(),0,",",".");
		}		
		if($codigo_asunto)
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto'";
		if($id_usuario_orden)
			$where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
		if($id_tipo)
				$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
		if($fecha1 && $fecha2)
			$where .= " AND cta_corriente.fecha BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
		else if($fecha1)
			$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."' ";
		else if($fecha2)
			$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2)."' ";
			

		$moneda_base = Utiles::MonedaBase($sesion);
		$moneda = new Moneda($sesion);
		$total_balance_egreso = 0;
		$total_balance_ingreso = 0;

		$query = "SELECT cta_corriente.egreso, cta_corriente.ingreso, cta_corriente.codigo_cliente,
					cliente.glosa_cliente, cta_corriente.id_cobro, cta_corriente.id_moneda, prm_moneda.simbolo,
					cta_corriente.fecha, asunto.glosa_asunto, cta_corriente.descripcion, prm_moneda.cifras_decimales,
					prm_cta_corriente_tipo.glosa as glosa_tipo, cta_corriente.numero_documento
					FROM cta_corriente 
					LEFT JOIN asunto USING(codigo_asunto)
					LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
					LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
					JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
					WHERE $where";
		$lista_gastos = new ListaGastos($sesion,'',$query);
		for( $v=0; $v < $lista_gastos->num; $v++ )
		{
			$gasto = $lista_gastos->Get($v);
			
			$tipo_cambio = GetTipoCambioMoneda($sesion, $gasto->fields['id_moneda']);
			if($gasto->fields['egreso'] > 0 )
				$total_balance_egreso += ($gasto->fields['egreso'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
			if($gasto->fields['ingreso'] > 0)
				$total_balance_ingreso += ($gasto->fields['ingreso'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
		}
		if($total_balance_egreso > 0 && $total_balance_ingreso > 0)
			$total_balance = $total_balance_ingreso  - $total_balance_egreso;
		elseif($total_balance_egreso > 0)
			$total_balance = $total_balance_egreso;
		elseif($total_balance_ingreso > 0)
			$total_balance = $total_balance_ingreso;			
			
		
		$ws2->write(5, 0, __("Total balance").': '.$moneda_base['simbolo'].' '.number_format($total_balance,$moneda_base['cifras_decimales'],',','.'), $encabezado);
		$ws2->mergeCells (5, 0, 5, 8);
    
    $fila_inicial = 7;
    $ws2->write($fila_inicial, 0, __('Fecha'), $tit);
    $ws2->write($fila_inicial, 1, __('Cliente'), $tit);
    $ws2->write($fila_inicial, 2, __('Asunto'), $tit);
    $ws2->write($fila_inicial, 3, __('Descripción'), $tit);
    $ws2->write($fila_inicial, 4, __('Ingreso'), $tit);
    $ws2->write($fila_inicial, 5, __('Cobro'), $tit);

	$columna_actual = 6;
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
	{
			$ws2->write($fila_inicial, $columna_actual, __('Tipo'), $tit);
			$columna_actual++;
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
				$ws2->write($fila_inicial, $columna_actual, __('N° Documento'), $tit);
		}
    $fila_inicial++;    
    if($orden == "")
			$orden = "fecha DESC";		
		
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	  while($row = mysql_fetch_array($resp2))
    {
	    $ws2->write($fila_inicial, 0, Utiles::sql2date($row[fecha]), $f4);
	    $ws2->write($fila_inicial, 1, $row[glosa_cliente], $f4);
	    $ws2->write($fila_inicial, 2, $row[glosa_asunto], $f4);
	    $ws2->write($fila_inicial, 3, $row[descripcion], $f4);
	    $ws2->write($fila_inicial, 4, $row[egreso] ? '' : $row[simbolo] . " " .number_format($row[ingreso],$row[cifras_decimales],",","."), $f4);
	    $ws2->write($fila_inicial, 5, $row[id_cobro], $f4);
		
		$columna_actual = 6;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
		{
				$ws2->write($fila_inicial, $columna_actual, $row[glosa_tipo], $f4);
				$columna_actual++;
		}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
				$ws2->write($fila_inicial, $columna_actual, $row[numero_documento], $f4);
				$columna_actual++;
		}

		$fila_inicial++;
		}




		
    $wb->close();
    exit;
?>