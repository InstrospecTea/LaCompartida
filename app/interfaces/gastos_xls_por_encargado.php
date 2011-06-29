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

		$moneda_base = Utiles::MonedaBase( $sesion );
		$id_moneda_base = $moneda_base['id_moneda'];

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

    $f4 =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Align' => 'justify',
                                'Border' => 1,
                                'Color' => 'black'));
    $f4->setNumFormat("0");

    $formato_moneda =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Align' => 'center',
                                'Border' => 1,
                                'Color' => 'black'));

    $formato_monto =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Align' => 'right',
                                'Border' => 1,
                                'Color' => 'black'));
                                
    $formato_moneda_total =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Bold' => '1',
                                'Align' => 'center',
                                'Border' => 1,
                                'Color' => 'black'));

    $formato_monto_total =& $wb->addFormat(array('Size' => 10,
                                'VAlign' => 'top',
                                'Bold' => '1',
                                'Align' => 'right',
                                'Border' => 1,
                                'Color' => 'black'));
                                
  /*  $formato_monto =& $wb->addFormat(array('Size' => 10,
															'VAlign' => 'top',
															'Align' => 'right',
															'Border' => 1,
															'Color' => 'black',
															'NumFormat' => "#,###,0.00")); */

		$total =& $wb->addFormat(array('Size' => 10,
                                'Align' => 'right',
                                'Bold' => '1',
                                'FgColor' => '36',
                                'Border' => 1,
                                'Color' => 'black'));
    $total->setNumFormat("0");

		$col = 0;
		$col_encargado 		= $col++;
		$col_cliente 			= $col++;
		$col_fecha 				= $col++;
		$col_descripcion 	= $col++;
		$col_moneda 			= $col++;
		$col_monto 				= $col++;
		$col_monto_base		= $col++;

		$ws1 =& $wb->addWorksheet(__('Reportes'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,0);
		$ws1->setZoom(75);

		// se setea el ancho de las columnas
		$ws1->setColumn( $col_encargado		, $col_encargado	,  10.00);
		$ws1->setColumn( $col_cliente			, $col_cliente		,  10.00);
		$ws1->setColumn( $col_fecha				, $col_fecha			,  18.00);
		$ws1->setColumn( $col_descripcion	, $col_descripcion,  60.00);
		$ws1->setColumn( $col_moneda			, $col_moneda			,  15.00);
		$ws1->setColumn( $col_monto				, $col_monto			,  20.00);
		$ws1->setColumn( $col_monto_base	, $col_monto_base ,  20.00);

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
			$where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE') ";
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
		if($id_usuario_responsable)
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable'";
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

		$moneda = new Moneda($sesion);
		$total_balance_egreso = 0;
		$total_balance_ingreso = 0;

		$query = "SELECT 
								cta_corriente.egreso, 
								cta_corriente.ingreso, 
								cta_corriente.monto_cobrable, 
								IF( cta_corriente.id_cobro > 0, cta_corriente.monto_cobrable * cm_gasto.tipo_cambio / cm_monedabase.tipo_cambio, cta_corriente.monto_cobrable * m_gasto.tipo_cambio / m_monedabase.tipo_cambio ) as monto_monedabase, 
								cta_corriente.codigo_cliente, 
								cliente.glosa_cliente, 
								cta_corriente.id_cobro, 
								cta_corriente.id_moneda, 
								m_gasto.tipo_cambio as tc1, 
								m_monedabase.tipo_cambio as tc2, 
								m_gasto.simbolo, 
								cta_corriente.fecha, 
								cliente.glosa_cliente, 
								CONCAT_WS(' ',usuario_encargado.apellido1,usuario_encargado.apellido2, usuario_encargado.nombre) as encargado_comercial, 
								asunto.glosa_asunto, 
								cta_corriente.descripcion, 
								prm_cta_corriente_tipo.glosa as glosa_tipo, 
								cta_corriente.numero_documento, 
								cta_corriente.numero_ot, 
								m_gasto.cifras_decimales, 
								cobro.estado 
								$col_select 
							FROM cta_corriente 
							LEFT JOIN asunto USING(codigo_asunto)
							LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
							LEFT JOIN usuario as usuario_encargado ON usuario_encargado.id_usuario = contrato.id_usuario_responsable  
							LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro
							LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
							LEFT JOIN prm_moneda   as m_gasto       ON m_gasto.id_moneda      = cta_corriente.id_moneda 
							LEFT JOIN prm_moneda   as m_monedabase  ON m_monedabase.id_moneda = '$id_moneda_base' 
							LEFT JOIN cobro_moneda as cm_gasto      ON cta_corriente.id_cobro = cm_gasto.id_cobro      AND cta_corriente.id_moneda = cm_gasto.id_moneda 
							LEFT JOIN cobro_moneda as cm_monedabase ON cta_corriente.id_cobro = cm_monedabase.id_cobro AND cm_monedabase.id_moneda = '$id_moneda_base' 
							JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
							LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
							WHERE $where 
							ORDER BY usuario_encargado.apellido1, cliente.glosa_cliente, asunto.glosa_asunto";
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
		$ws1->mergeCells(5, 0, 5, 8);
    
    $fila_inicial = 7;
    
    $fila_inicial++;
    if($orden == "")
			$orden = "fecha DESC";		
		
		$total_en_moneda_base = 0;
		$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	  while($row = mysql_fetch_array($resp2))
    {
    	$imprimir_encabezado = false;
    	$fila_final_ultima_tabla = $fila_inicial;
    	if($encargado_anterior != $row['encargado_comercial'])
    		{
    			$fila_inicial += 2;
    			$ws1->write($fila_inicial,$col_encargado, $row['encargado_comercial'], $encabezado);
    			$ws1->mergeCells($fila_inicial,$col_encargado,$fila_inicial,$col_encargado+3);
    			$imprimir_encabezado = true;
    		}
    	if($cliente_anterior != $row['glosa_cliente'])
    		{
    			$fila_inicial += 2;
    			$ws1->write($fila_inicial,$col_cliente, $row['glosa_cliente'],$encabezado);
    			$ws1->mergeCells($fila_inicial,$col_cliente,$fila_inicial,$col_cliente+3);
    			$imprimir_encabezado = true;
    		}
    	if($asunto_anterior != $row['glosa_asunto'])
    		{
    			$fila_inicial += 2;
    			$ws1->write($fila_inicial,$col_fecha, $row['glosa_asunto'],$encabezado);
    			$ws1->mergeCells($fila_inicial,$col_fecha, $fila_inicial, $col_fecha+3);
    			$imprimir_encabezado = true;
    		}
    	if( $imprimir_encabezado )
    		{
    			if( !( $encargado_anterior == "" && $cliente_anterior == "" && $asunto_anterior == "" ) )
    				{
    					$ws1->write($fila_final_ultima_tabla, $col_moneda, $moneda_base['simbolo'], $formato_moneda_total );
    					$ws1->write($fila_final_ultima_tabla, $col_monto , $total_en_moneda_base  , $formato_monto_total  );
    					$total_en_moneda_base = 0;
    				}
    			$fila_inicial += 2;
    			ImprimirEncabezado($sesion,$ws1);
    			$fila_inicial++;
    		}
    	$columna_actual=2;
	    $ws1->write($fila_inicial, $col_fecha, Utiles::sql2date($row['fecha']), $f4);
	    $ws1->write($fila_inicial, $col_descripcion, $row['descripcion'], $f4);
	    
	    if($row['ingreso'] > 0) 
	    	$multiplicador = -1;
	    else
	    	$multiplicador = 1;
	    	
			$ws1->write($fila_inicial, $col_moneda, $row['simbolo'], $formato_moneda);
			$row['monto_cobrable'] = $multiplicador * $row['monto_cobrable'];
			$ws1->write($fila_inicial, $col_monto   , number_format($row['monto_cobrable'],$row['cifras_decimales'],".",""), $formato_monto);
			$monto_monedabase = $multiplicador * number_format($row['monto_monedabase'],$moneda_base['cifras_decimales'],'.','');
			$total_en_moneda_base += $monto_monedabase;
			
			$cliente_anterior = $row['glosa_cliente'];
			$asunto_anterior = $row['glosa_asunto'];
			$encargado_anterior = $row['encargado_comercial'];
			$fila_inicial++;
		}
		$ws1->write($fila_inicial, $col_moneda, $moneda_base['simbolo'], $formato_moneda_total );
		$ws1->write($fila_inicial, $col_monto , $total_en_moneda_base  , $formato_monto_total  );
		$fila_inicial += 2;
		
    $wb->close();
    
    function ImprimirEncabezado($sesion,$ws1)
    {
    	global $fila_inicial, $col_fecha, $col_descripcion, $col_moneda, $col_monto, $col_monto_base, $tit;
    	
    	$columna_actual=2;
	    $ws1->write($fila_inicial, $col_fecha, __('Fecha'), $tit);
	    $ws1->write($fila_inicial, $col_descripcion, utf8_decode(__('Descripción')), $tit);
	    $ws1->write($fila_inicial, $col_moneda, __('Moneda'), $tit);
	    $columna_balance_glosa = $columna_actual;
	    $ws1->write($fila_inicial, $col_monto, __('Monto'), $tit);
    }
    exit;
?>
