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
    require_once Conf::ServerDir().'/classes/UtilesApp.php';
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

	if( $moneda_gasto > 0 )
	{
		$obj_moneda_gasto = new Moneda($sesion);
		$obj_moneda_gasto->Load($moneda_gasto);

		// Redefinimos el formato de la moneda, para que sea consistente con la cifra.
		$simbolo_moneda = $obj_moneda_gasto->fields['simbolo'];
		$cifras_decimales = $obj_moneda_gasto->fields['cifras_decimales'];
		if($cifras_decimales)
		{
			$decimales = '.';
			while($cifras_decimales--){
				$decimales .= '0';
			}
		}
		else
		{
			$decimales = '';
		}
		$formato_moneda =& $wb->addFormat(array('Size' => 10,
						'VAlign' => 'top',
						'Align' => 'justify',
						'Border' => 1,
						'Color' => 'black',
						'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
	}
	else
	{
		$formato_moneda =& $wb->addFormat(array('Size' => 10,
					'VAlign' => 'top',
					'Align' => 'justify',
					'Border' => 1,
					'Color' => 'black'));
	}

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

		// se setea las columnas para facilitar orden 
		$col = 0;
		$col_fecha = $col++;
		$ws1->write($fila_inicial, $col_fecha, __('Fecha'), $tit);
		if(!$codigo_cliente){
			$col_cliente = $col++;
		}
		if(!$codigo_asunto){
			$col_codigo = $col++;		
			$col_asunto = $col++;
		}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
		{
			$col_tipo = $col++;
		}		
		$col_descripcion = $col++;
		$col_egreso = $col++;
		$col_ingreso = $col++;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		{
			$col_monto_cobrable = $col++;
		}	
		$col_liquidacion = $col++;
		$col_estado = $col++;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::TipoGasto() ) )
		{
			$col_facturable = $col++;
		}
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') )
		{
			$col_factura = $col++;
			$col_tipo_doc = $col++;
			$col_fecha_factura = $col++;
		}
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
			$col_numero_documento = $col++;
		}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) )
		{
			$col_numero_ot = $col++;
		}
		$col_rut_proveedor = $col++;
		$col_nombre_proveedor = $col++;
		$col_ingresado_por = $col++;
		$col_ordenado_por = $col++;
		
		
		// se setea el ancho de las columnas		
		$ws1->setColumn( $col_fecha, $col_fecha, 18.00); #fecha
		$ws1->setColumn( $col_cliente, $col_cliente, 30.00); #cliente
		$ws1->setColumn( $col_codigo, $col_codigo, 15.00); #código
		$ws1->setColumn( $col_asunto, $col_asunto, 30.00); #asunto
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
			$ws1->setColumn( $col_numero_documento, $col_numero_documento, 25.00); #n° documento
		}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) )
		{
			$ws1->setColumn( $col_numero_ot, $col_numero_ot, 25.00); #n° orden de trabajo
		}
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') )
		{
			$ws1->setColumn( $col_factura, $col_factura, 15.00); #n° documento
			$ws1->setColumn( $col_tipo_doc, $col_tipo_doc, 25.00); #tipo documento
			$ws1->setColumn( $col_fecha_factura, $col_fecha_factura, 25.00); #fecha documnento
		}
		
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
		{
			$ws1->setColumn( $col_tipo, $col_tipo, 25.00); #tipo gasto
		}		
		$ws1->setColumn( $col_descripcion, $col_descripcion, 25.00); #descripcion
		$ws1->setColumn( $col_egreso, $col_egreso, 25.00); #egreso
		$ws1->setColumn( $col_ingreso, $col_ingreso, 25.00); #ingreso
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		{
			$ws1->setColumn( $col_monto_cobrable, $col_monto_cobrable, 25.00); #monto cobrable
		}		
		$ws1->setColumn( $col_liquidacion, $col_liquidacion, 25.00); #liquidacion
		$ws1->setColumn( $col_estado, $col_estado, 15.00); #estado cobro
		$ws1->setColumn( $col_facturable, $col_facturable, 10.00); #facturable
		$ws1->setColumn( $col_rut_proveedor, $col_rut_proveedor, 15.00); #rut ruc proveedor
		$ws1->setColumn( $col_nombre_proveedor, $col_nombre_proveedor, 15.00); #nombre proveedor
		$ws1->setColumn( $col_ingresado_por, $col_ingresado_por, 15.00); #código
		$ws1->setColumn( $col_ordenadopor, $col_ordenado_por, 15.00); #código
		
		
		$ws1->write(1, 0, __('Resumen de gastos'), $encabezado);
		$ws1->mergeCells (1, 0, 1, 8);

		if($fecha1 && $fecha2)
		{
			$ws1->write(3, 0, __('Entre el ').$fecha1.__(' y el ').$fecha2, $encabezado);
		}
		elseif($fecha1)
		{
			$ws1->write(3, 0, __('Desde el ').$fecha1, $encabezado);
		}
		elseif($fecha2)
		{
			$ws1->write(3, 0, __('Antes de ').$fecha2, $encabezado);
		}
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
		{
			$where .= " AND cta_corriente.id_cobro is null ";
		}
		if($cobrado == 'SI')
		{
			$where .= " AND cta_corriente.id_cobro is not null AND (cobro.estado = 'EMITIDO' OR cobro.estado = 'PAGADO' OR cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'FACTURADO' OR cobro.estado = 'PAGO PARCIAL') ";
		}
		if($codigo_cliente)
		{
			$where .= " AND cta_corriente.codigo_cliente = '$codigo_cliente'";
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($codigo_cliente);
			$total_cta = number_format($cliente->TotalCuentaCorriente(),0,",",".");
		}		
		if($codigo_asunto){
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto'";
		}
		if($id_usuario_responsable){
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable'";
		}
		if($id_usuario_orden){
			$where .= " AND cta_corriente.id_usuario_orden = '$id_usuario_orden'";
		}
		if($id_tipo){
			$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
		}
		if($clientes_activos == 'activos'){
			$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
		}
		if( $clientes_activos == 'inactivos'){
			$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
		}
		if($fecha1 && $fecha2){
			$where .= " AND cta_corriente.fecha BETWEEN '".Utiles::fecha2sql($fecha1)."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
		}
		else if($fecha1){
			$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."' ";
		}
		else if($fecha2){
			$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2)."' ";
		}
		else if(!empty($id_cobro)){
			$where .= " AND cta_corriente.id_cobro = '$id_cobro' ";
		}
		
		// Filtrar por moneda del gasto
		if ($moneda_gasto != ''){
			$where .= " AND cta_corriente.id_moneda=$moneda_gasto ";
		}
		
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
					cta_corriente.id_cobro, cta_corriente.id_moneda, prm_moneda.simbolo, cta_corriente.fecha, asunto.codigo_asunto, asunto.glosa_asunto,
					cta_corriente.descripcion, prm_cta_corriente_tipo.glosa as glosa_tipo, cta_corriente.numero_documento,
					cta_corriente.numero_ot, cta_corriente.codigo_factura_gasto, cta_corriente.fecha_factura, prm_tipo_documento_asociado.glosa as tipo_doc_asoc, 
					prm_moneda.cifras_decimales, cobro.estado
					$col_select,
					prm_proveedor.rut as rut_proveedor, prm_proveedor.glosa as nombre_proveedor,
					CONCAT(usuario.apellido1 , ', ' , usuario.nombre) as usuario_ingresa,
					CONCAT(usuario2.apellido1 , ', ' , usuario2.nombre) as usuario_ordena
					FROM cta_corriente 
					LEFT JOIN asunto USING(codigo_asunto)
					LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
					LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro 
					LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
					LEFT JOIN usuario as usuario2 ON usuario2.id_usuario=cta_corriente.id_usuario_orden
					LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
					LEFT JOIN prm_tipo_documento_asociado ON cta_corriente.id_tipo_documento_asociado = prm_tipo_documento_asociado.id_tipo_documento_asociado
					JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
					LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
					LEFT JOIN prm_proveedor ON ( cta_corriente.id_proveedor = prm_proveedor.id_proveedor )
					WHERE $where";
		
		$lista_gastos = new ListaGastos($sesion,'',$query);
		$moneda_unica = true; #para verificar en el ciclo si es la moneda única
		$id_moneda_check = 0; #igual que el de arriba
		for( $v=0; $v < $lista_gastos->num; $v++ )
		{
			$gasto = $lista_gastos->Get($v);
			
			$tipo_cambio = Moneda::GetTipoCambioMoneda($sesion, $gasto->fields['id_moneda']);
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
			
			if( $v > 0 ) #la primera vez que entra al ciclo nos saltamos este paso por que no hay con que comparar la moneda
			{
				if( $id_moneda_check != $gasto->fields['id_moneda'])
				{
					$moneda_unica = false;
				}
			}
			else
				$id_moneda_check = $gasto->fields['id_moneda'];
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
	# titulos de columnas
    $ws1->write($fila_inicial, $col_fecha, __('Fecha'), $tit);
    if(!$codigo_cliente){
    	$ws1->write($fila_inicial, $col_cliente, __('Cliente'), $tit);
	}
    if(!$codigo_asunto){
    	$ws1->write($fila_inicial, $col_codigo, __('Código'), $tit);
    	$ws1->write($fila_inicial, $col_asunto, __('Asunto'), $tit);
	}
	if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
	{
		$ws1->write($fila_inicial, $col_numero_documento, (__('N° Documento')), $tit);
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) )
	{
		$ws1->write($fila_inicial, $col_numero_ot,(__('N° OT')), $tit);
	}
	if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') )
	{
		$ws1->write($fila_inicial, $col_factura,(__('N° Documento')), $tit);
		$ws1->write($fila_inicial, $col_tipo_doc,(__('Tipo Documento')), $tit);
		$ws1->write($fila_inicial, $col_fecha_factura,__('Fecha Documento'), $tit);
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
	{
		$ws1->write($fila_inicial, $col_tipo, __('Tipo'), $tit);
	}
    $ws1->write($fila_inicial, $col_descripcion, (__('Descripción')), $tit);
    $ws1->write($fila_inicial, $col_egreso, __('Egreso'), $tit);
	$ws1->write($fila_inicial, $col_ingreso_moneda, ' ', $tit);
    $ws1->write($fila_inicial, $col_ingreso, __('Ingreso'), $tit);
    $columna_balance_valor = $col_ingreso;
    if(( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable')) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ))
	{
	    $ws1->write($fila_inicial, $col_monto_cobrable, __('Monto cobrable'), $tit);
	}
    $ws1->write($fila_inicial, $col_liquidacion, __('Cobro'), $tit);
    $ws1->write($fila_inicial, $col_estado, __('Estado Cobro'), $tit);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::TipoGasto() ) )
	{
		$ws1->write($fila_inicial, $col_facturable, __('Cobrable'), $tit);
	}
	$ws1->write($fila_inicial, $col_rut_proveedor, __('RUT Proveedor'), $tit);
	$ws1->write($fila_inicial, $col_nombre_proveedor, __('Nombre Proveedor'), $tit);
	$ws1->write($fila_inicial, $col_ingresado_por, __('Creado por'), $tit);
	$ws1->write($fila_inicial, $col_ordenado_por, __('Ordenado por'), $tit);
	
	
    $fila_inicial++;    
    if($orden == "")
	{
			$orden = "fecha DESC";		
	}
	
	#si es moneda unica creamos el formato de la moneda unida
	if( $moneda_unica ){
		$obj_moneda_unica = new Moneda($sesion);
		$obj_moneda_unica->Load($id_moneda_check);

		// Redefinimos el formato de la moneda, para que sea consistente con la cifra.
		$simbolo_moneda = $obj_moneda_unica->fields['simbolo'];
		$cifras_decimales = $obj_moneda_unica->fields['cifras_decimales'];
		if($cifras_decimales)
		{
			$decimales = '.';
			while($cifras_decimales--){
				$decimales .= '0';
			}
		}
		else
		{
			$decimales = '';
		}
		$formato_moneda =& $wb->addFormat(array('Size' => 10,
						'VAlign' => 'top',
						'Align' => 'justify',
						'Border' => 1,
						'Color' => 'black',
						'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
	}
	
	#valores de columnas
	$resp2 = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);
	while($row = mysql_fetch_array($resp2))
	{
		  
    	$columna_actual=0;
	    $ws1->write($fila_inicial, $col_fecha, Utiles::sql2date($row['fecha'], $formato_fecha), $f4);
	    if(!$codigo_cliente){
	    	$ws1->write($fila_inicial, $col_cliente, $row['glosa_cliente'], $f4);
		}
	    if(!$codigo_asunto){
	    	$ws1->write($fila_inicial, $col_codigo, $row['codigo_asunto'], $f4);
	    	$ws1->write($fila_inicial, $col_asunto, $row['glosa_asunto'], $f4);
		}
	    if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroGasto') ) || ( method_exists('Conf','NumeroGasto') && Conf::NumeroGasto() ) )
		{
				$ws1->write($fila_inicial, $col_numero_documento, $row[numero_documento], $f4);
		}
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NumeroOT') ) || ( method_exists('Conf','NumeroOT') && Conf::NumeroOT() ) )
		{
				$ws1->write($fila_inicial, $col_numero_ot, $row[numero_ot], $f4);
		}
		if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'FacturaAsociada') )
		{
			$ws1->write($fila_inicial, $col_factura, !empty($row['codigo_factura_gasto']) ? $row['codigo_factura_gasto'] : "", $f4);
			$ws1->write($fila_inicial, $col_tipo_doc, !empty($row['tipo_doc_asoc']) ? $row['tipo_doc_asoc'] : "", $f4);
			$ws1->write($fila_inicial, $col_fecha_factura, !empty($row['fecha_factura']) && $row['fecha_factura'] != '0000-00-00' ? Utiles::sql2fecha($row['fecha_factura'],$formato_fecha) : '-' , $f4);
		}
	    if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoGasto') ) || ( method_exists('Conf','TipoGasto') && Conf::TipoGasto() ) )
		{
				$ws1->write($fila_inicial, $col_tipo, $row['glosa_tipo'], $f4);
		}
		
	    $ws1->write($fila_inicial, $col_descripcion, $row['descripcion'], $f4);
	    if( $moneda_gasto > 0 || $moneda_unica )
	    {
	    	$ws1->write($fila_inicial, $col_egreso, $row['egreso'], $formato_moneda);            
	    	$ws1->write($fila_inicial, $col_ingreso, $row['ingreso'], $formato_moneda);
	    }
	    else
	    {      
	    	$ws1->write($fila_inicial, $col_egreso, $row['ingreso'] ? '' : $row['simbolo'] . " " . number_format($row['egreso'],$row['cifras_decimales'],",","."), $f4);
			$ws1->write($fila_inicial, $col_ingreso, $row['egreso'] ? '' : $row['simbolo'] . " " . number_format($row['ingreso'],$row['cifras_decimales'],",","."), $f4);
	    }
	    
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		{
			if($row['esCobrable'] == 'No') {
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) ){
					$ws1->write($fila_inicial, $col_monto_cobrable, 0, $formato_moneda);
				}
			}
			else 
			{
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
				{
					if( $moneda_gasto > 0 || $moneda_unica ){
						$ws1->write($fila_inicial, $col_monto_cobrable, $row['monto_cobrable'], $formato_moneda); 
					}
					else
					{
						$ws1->write($fila_inicial, $col_monto_cobrable, $row['simbolo'] . " " . number_format($row['monto_cobrable'],$row['cifras_decimales'],",","."), $f4); 
					}
				}
			}	
		}
		else
		{
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
			{
				if( $moneda_gasto > 0 || $moneda_unica ){
					$ws1->write($fila_inicial, $col_monto_cobrable, $row['monto_cobrable'], $formato_moneda); 
				}
				else
				{
					$ws1->write($fila_inicial, $col_monto_cobrable, $row['simbolo'] . " " . number_format($row['monto_cobrable'],$row['cifras_decimales'],",","."), $f4);
				} 
			}
		}	
	    
	    
	    $ws1->write($fila_inicial, $col_liquidacion, $row['id_cobro'], $f4);
		$ws1->write($fila_inicial, $col_estado, $row['estado'], $f4);
		
		if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		{
			$ws1->write($fila_inicial, $col_facturable, $row['esCobrable'], $f4);
		}
		$ws1->write($fila_inicial, $col_rut_proveedor, $row['rut_proveedor'], $f4);
		$ws1->write($fila_inicial, $col_nombre_proveedor, $row['nombre_proveedor'], $f4);
		$ws1->write($fila_inicial, $col_ingresado_por, $row['usuario_ingresa'], $f4);
		$ws1->write($fila_inicial, $col_ordenado_por, $row['usuario_ordena'], $f4);
		$fila_inicial++;
	}

	$fila_inicial += 2;

	$ws1->write($fila_inicial,$columna_balance_glosa, __("Total balance: "), $encabezado);
	$ws1->write($fila_inicial,$columna_balance_valor, $moneda_base['simbolo'].' '.number_format($total_balance,$moneda_base['cifras_decimales'],',','.'), $encabezado);
		
    $wb->close();
    exit;
?>
