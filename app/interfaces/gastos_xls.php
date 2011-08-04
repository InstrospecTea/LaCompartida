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

		set_time_limit(300);
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


		$ws1 =& $wb->addWorksheet(__('Reportes'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,0);
		$ws1->setZoom(75);

		// se setea el ancho de las columnas
		$ws1->setColumn( 0, 0,  18.00);
		$ws1->setColumn( 1, 1,  45.00);
		$ws1->setColumn( 2, 2,  40.00);
		$ws1->setColumn( 3, 3,  30.00);
		$ws1->setColumn( 4, 4,  30.00);
		$ws1->setColumn( 5, 5,  30.00);
		$ws1->setColumn( 6, 6,  30.00);
		$ws1->setColumn( 7, 7,  30.00);
		$ws1->setColumn( 8, 8,  30.00);
		$ws1->setColumn( 9, 9,  30.00);
		$ws1->setColumn( 10, 10,  30.00);
		$ws1->setColumn( 11, 11,  30.00);
		$ws1->setColumn( 12, 12,  30.00);
		
		$ws1->write(1, 0, __('Resumen de gastos'), $encabezado);
		$ws1->mergeCells (1, 0, 1, 8);

		if($fecha1 && $fecha2)
			$ws1->write(3, 0, __('Entre el ').$fecha1.__(' y el ').$fecha2, $encabezado);
		else if($fecha1)
			$ws1->write(3, 0, __('Desde el ').$fecha1, $encabezado);
		else if($fecha2)	
			$ws1->write(3, 0, __('Antes de ').$fecha2, $encabezado);
		$ws1->mergeCells (3, 0, 3, 8);
		
		if($codigo_cliente)
		{
			$info_usr1 = str_replace('<br>',' - ', 'Cliente: '.Utiles::Glosa( $sesion, $codigo_cliente, 'glosa_cliente', 'cliente', 'codigo_cliente'));
			$ws1->write(2, 0, $info_usr1, $encabezado);
			$ws1->mergeCells (2, 0, 2, 8);
		}
		if($codigo_asunto)
		{
			$info_usr = str_replace('<br>',' - ', 'Asunto: '.Utiles::Glosa( $sesion, $codigo_asunto, 'glosa_asunto', 'asunto', 'codigo_asunto'));
			$ws1->write(3, 0, $info_usr, $encabezado);
			$ws1->mergeCells (3, 0, 3, 8);
		}
		########################### SQL INFORME DE GASTOS #########################
		$where = 1;
		if($cobrado == 'NO')
				$where .= " AND cta_corriente.id_cobro is null ";
			if($cobrado == 'SI')
				$where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL') ";
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
		if($clientes_activos == 'activos')
			$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
		if( $clientes_activos == 'inactivos')
			$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
		if($fecha1 && $fecha2)
			$where .= " AND cta_corriente.fecha BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
		else if($fecha1)
			$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."' ";
		else if($fecha2)
			$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2)."' ";
		else if(!empty($id_cobro))
			$where .= " AND cta_corriente.id_cobro = '$id_cobro' ";
		
		// Filtrar por moneda del gasto
		if ($moneda_gasto != '')
			$where .= " AND cta_corriente.id_moneda=$moneda_gasto ";
		
		$col_select ="";
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		{
			$col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";
		}
		
		
		$moneda_base = Utiles::MonedaBase($sesion);
		$moneda = new Moneda($sesion);
		$total_balance_egreso = 0;
		$total_balance_ingreso = 0;

		$query = "SELECT cta_corriente.egreso, cta_corriente.ingreso, cta_corriente.monto_cobrable, cta_corriente.codigo_cliente, cliente.glosa_cliente, 
					cta_corriente.id_cobro, cta_corriente.id_moneda, prm_moneda.simbolo, cta_corriente.fecha, asunto.glosa_asunto,
					cta_corriente.descripcion, prm_cta_corriente_tipo.glosa as glosa_tipo, cta_corriente.numero_documento,
					cta_corriente.numero_ot, cta_corriente.codigo_factura_gasto, cta_corriente.fecha_factura, prm_moneda.cifras_decimales, cobro.estado
					$col_select,
					prm_proveedor.rut as rut_proveedor, prm_proveedor.glosa as nombre_proveedor
					FROM cta_corriente 
					LEFT JOIN asunto USING(codigo_asunto)
					LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro
					LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
					LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
					JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
					LEFT JOIN prm_proveedor ON ( cta_corriente.id_proveedor = prm_proveedor.id_proveedor )
					WHERE $where";
		$lista_gastos = new ListaGastos($sesion,'',$query);
		for( $v=0; $v < $lista_gastos->num; $v++ )
		{
			$gasto = $lista_gastos->Get($v);
			
			$tipo_cambio = GetTipoCambioMoneda($sesion, $gasto->fields['id_moneda']);
			if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
			{
				if($gasto->fields['esCobrable'] == 'Si' ){
					if($gasto->fields['egreso'] > 0 )
						$total_balance_egreso += ($gasto->fields['monto_cobrable'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
					if($gasto->fields['ingreso'] > 0)
						$total_balance_ingreso += ($gasto->fields['monto_cobrable'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
				}
	
			}
			else
			{	
				if($gasto->fields['egreso'] > 0 )
					$total_balance_egreso += ($gasto->fields['monto_cobrable'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
				if($gasto->fields['ingreso'] > 0)
					$total_balance_ingreso += ($gasto->fields['monto_cobrable'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
			}
		}
		if($total_balance_egreso > 0 && $total_balance_ingreso > 0)
			$total_balance = $total_balance_ingreso  - $total_balance_egreso;
		elseif($total_balance_egreso > 0)
			$total_balance = -$total_balance_egreso;
		elseif($total_balance_ingreso > 0)
			$total_balance = $total_balance_ingreso;			
			
		
		$ws1->write(5, 0, __("Total balance").': '.$moneda_base['simbolo'].' '.number_format($total_balance,$moneda_base['cifras_decimales'],',','.'), $encabezado);
		$ws1->mergeCells (5, 0, 5, 8);
    
    $fila_inicial = 7;
    $columna_actual=0;
    $ws1->write($fila_inicial, $columna_actual++, __('Fecha'), $tit);
    if(!$codigo_cliente)
    	$ws1->write($fila_inicial, $columna_actual++, __('Cliente'), $tit);
    if(!$codigo_asunto)
    	$ws1->write($fila_inicial, $columna_actual++, __('Asunto'), $tit);
	if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
	{
		$ws1->write($fila_inicial, $columna_actual++, utf8_decode(__('N° Documento')), $tit);
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) )
	{
		$ws1->write($fila_inicial, $columna_actual++,utf8_decode(__('N° OT')), $tit);
	}
	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') )
	{
		$ws1->write($fila_inicial, $columna_actual++,utf8_decode(__('N° Factura')), $tit);
		$ws1->write($fila_inicial, $columna_actual++,__('Fecha Factura'), $tit);
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
	{
		$ws1->write($fila_inicial, $columna_actual++, __('Tipo'), $tit);
	}
    $ws1->write($fila_inicial, $columna_actual++, utf8_decode(__('Descripción')), $tit);
    $ws1->write($fila_inicial, $columna_actual++, __('Egreso'), $tit);
    $columna_balance_glosa = $columna_actual;
    $ws1->write($fila_inicial, $columna_actual++, __('Ingreso'), $tit);
    $columna_balance_valor = $columna_actual;
    if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
	    $ws1->write($fila_inicial, $columna_actual++, __('Monto cobrable'), $tit);
    $ws1->write($fila_inicial, $columna_actual++, __('Cobro'), $tit);
    $ws1->write($fila_inicial, $columna_actual++, __('Estado Cobro'), $tit);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::TipoGasto() ) )
	{
		$ws1->write($fila_inicial, $columna_actual++, __('Cobrable'), $tit);
	}
	$ws1->write($fila_inicial, $columna_actual++, __('RUT Proveedor'), $tit);
	$ws1->write($fila_inicial, $columna_actual++, __('Nombre Proveedor'), $tit);
	
	
    $fila_inicial++;    
    if($orden == "")
			$orden = "fecha DESC";		
		
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	  while($row = mysql_fetch_array($resp2))
    {
    	$columna_actual=0;
	    $ws1->write($fila_inicial, $columna_actual++, Utiles::sql2date($row[fecha]), $f4);
	    if(!$codigo_cliente)
	    	$ws1->write($fila_inicial, $columna_actual++, $row[glosa_cliente], $f4);
	    if(!$codigo_asunto)
	    	$ws1->write($fila_inicial, $columna_actual++, $row[glosa_asunto], $f4);
	    if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
				$ws1->write($fila_inicial, $columna_actual++, $row[numero_documento], $f4);
		}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) )
		{
				$ws1->write($fila_inicial, $columna_actual++, $row[numero_ot], $f4);
		}
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') )
		{
			$ws1->write($fila_inicial, $columna_actual++, !empty($row['codigo_factura_gasto']) ? $row['codigo_factura_gasto'] : "", $f4);
			$ws1->write($fila_inicial, $columna_actual++, !empty($row['fecha_factura']) && $row['fecha_factura'] != '0000-00-00' ? Utiles::sql2fecha($row['fecha_factura'],'%d/%m/%y') : '-' , $f4);
		}
	    if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
		{
				$ws1->write($fila_inicial, $columna_actual++, $row[glosa_tipo], $f4);
		}
		
	    $ws1->write($fila_inicial, $columna_actual++, $row[descripcion], $f4);
	    $ws1->write($fila_inicial, $columna_actual++, $row[ingreso] ? '' : $row[simbolo] . " " .number_format($row[egreso],$row[cifras_decimales],",","."), $f4);            
	    $ws1->write($fila_inicial, $columna_actual++, $row[egreso] ? '' : $row[simbolo] . " " .number_format($row[ingreso],$row[cifras_decimales],",","."), $f4);
	    
	    if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		{
			if($row['esCobrable'] == 'No') {
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
				$ws1->write($fila_inicial, $columna_actual++, 0, $f4);
			}
			else 
			{
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
				$ws1->write($fila_inicial, $columna_actual++, $row[simbolo] . " " .number_format($row[monto_cobrable],$row[cifras_decimales],",","."), $f4); 
			}	
		}
		else
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
	    	$ws1->write($fila_inicial, $columna_actual++, $row[simbolo] . " " .number_format($row[monto_cobrable],$row[cifras_decimales],",","."), $f4); 
		}	
	    
	    
	    $ws1->write($fila_inicial, $columna_actual++, $row[id_cobro], $f4);
			$ws1->write($fila_inicial, $columna_actual++, $row[estado], $f4);
		
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		{
			$ws1->write($fila_inicial, $columna_actual++, $row['esCobrable'], $f4);
		}
		$ws1->write($fila_inicial, $columna_actual++, $row[rut_proveedor], $f4);
		$ws1->write($fila_inicial, $columna_actual++, $row[nombre_proveedor], $f4);
		$fila_inicial++;
		}
		
		$fila_inicial += 2;
		
		$ws1->write($fila_inicial,$columna_balance_glosa, __("Total balance: "), $encabezado);
		$ws1->write($fila_inicial,$columna_balance_valor, $moneda_base['simbolo'].' '.number_format($total_balance,$moneda_base['cifras_decimales'],',','.'), $encabezado);
		
    $wb->close();
    exit;
?>
