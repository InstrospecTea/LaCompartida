<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../app/classes/Moneda.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Gasto.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once 'Spreadsheet/Excel/Writer.php';

	$sesion = new Sesion(array('OFI','COB'));
	$pagina = new Pagina($sesion);

	#$key = substr(md5(microtime().posix_getpid()), 0, 8);

	$wb = new Spreadsheet_Excel_Writer();
	$wb->send('Planilla_gastos.xls');

	$query = "SELECT id_moneda, simbolo, cifras_decimales FROM prm_moneda WHERE moneda_base=1";
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp);

	if($cifras_decimales)
	{
		$decimales = '.';
		while($cifras_decimales--)
			$decimales .= '#';
	}
	else
		$decimales = '';

	$wb->setCustomColor(35, 220, 255, 220);
	$wb->setCustomColor(36, 255, 255, 220);
	$formato_encabezado =& $wb->addFormat(array('Size' => 12,
										'VAlign' => 'top',
										'Align' => 'justify',
										'Bold' => '1',
										'Color' => 'black'));
	$formato_moneda_encabezado =& $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
												'Size' => 12,
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
	$formato_normal =& $wb->addFormat(array('Size' => 10,
								'VAlign' => 'top',
								'Align' => 'justify',
								'Border' => 1,
								'Color' => 'black'));
	$formato_moneda =& $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
								'Border' => 1,
								'Size' => 10,
								'Align' => 'right'));
	$formato_total =& $wb->addFormat(array('Size' => 10,
								'VAlign' => 'top',
								'Align' => 'justify',
								'Bold' => '1',
								'Border' => 1,
								'Color' => 'black'));
	$formato_moneda_total =& $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
								'Border' => 1,
								'Bold' => '1',
								'Size' => 10,
								'Align' => 'right'));

	$ws1 =& $wb->addWorksheet(__('Reportes'));
	$ws1->setInputEncoding('utf-8');
	$ws1->fitToPages(1,0);
	$ws1->setZoom(75);

	// se setea el ancho de las columnas
	$ws1->setColumn(0, 0, 10);
	$ws1->setColumn(1, 1, 30);
	$ws1->setColumn(2, 2, 30);
	$ws1->setColumn(3, 3, 30);
	$ws1->setColumn(4, 4, 30);
	$ws1->setColumn(4, 5, 30);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
	{	
		$ws1->setColumn(4, 6, 10);
		$ws1->setColumn(4, 7, 30);
	}
	else
	{
		$ws1->setColumn(4, 6, 30);
	}

	$ws1->write(0, 1, __('Resumen de gastos'), $formato_encabezado);
	$ws1->mergeCells(0, 1, 0, 6);
	
	$columna_cliente = 1;
	$columna_egreso = 2;
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		{
		$columna_egreso_cobrable =3;
		$columna_ingreso = 4;
		$columna_ingreso_cobrable = 5;
		if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
			{
			$columna_es_cobrable = 6;
			$columna_balance = 7;
			}
		else{
			$columna_balance = 6;
			}
		}
	else
		{
		$columna_ingreso = 3;
		$columna_balance = 4;
		}

	if($codigo_cliente)
	{
		$info_usr1 = str_replace('<br>',' - ', 'Cliente: '.Utiles::Glosa($sesion, $codigo_cliente, 'glosa_cliente', 'cliente', 'codigo_cliente'));
		$ws1->write(2, 1, utf8_decode($info_usr1), $formato_encabezado);
		$ws1->mergeCells(2, 1, 2, 4);
	}
	if($codigo_asunto)
	{
		$info_usr = str_replace('<br>',' - ', 'Asunto: '.Utiles::Glosa($sesion, $codigo_asunto, 'glosa_asunto', 'asunto', 'codigo_asunto'));
		$ws1->write(3, 1, utf8_decode($info_usr), $formato_encabezado);
		$ws1->mergeCells(3, 1, 3, 4);
	}
	########################### SQL INFORME DE GASTOS #########################
	$where = 1;
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
	if($id_usuario_responsable)
		$where .= " AND contrato.id_usuario_responsable = '$id_usuario_responsable' ";
	if($id_tipo)
		$where .= " AND cta_corriente.id_cta_corriente_tipo = '$id_tipo'";
	if($clientes_activos == 'activos')
		$where .= " AND ( ( cliente.activo = 1 AND asunto.activo = 1 ) OR ( cliente.activo AND asunto.activo IS NULL ) ) ";
	if($clientes_activos == 'inactivos')
		$where .= " AND ( cliente.activo != 1 OR asunto.activo != 1 ) ";
	if($fecha1)
		$where .= " AND cta_corriente.fecha >= '".Utiles::fecha2sql($fecha1)."'";
	if($fecha2)
		$where .= " AND cta_corriente.fecha <= '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";

	// Filtrar por moneda del gasto
	if ($moneda_gasto != '')
		$where .= " AND cta_corriente.id_moneda=$moneda_gasto ";

	$moneda_base = Utiles::MonedaBase($sesion);
	$moneda = new Moneda($sesion);
	$total_balance_egreso = 0;
	$total_balance_ingreso = 0;
	$total_balance_egreso_cobrable = 0;
	$total_balance_ingreso_cobrable = 0;
	
	$filas = 7;
	$fila_inicio = 7;
	
	$ws1->write($filas, $columna_cliente, __('Cliente'), $formato_titulo);
	$ws1->write($filas, $columna_egreso, __('Egreso'), $formato_titulo);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		$ws1->write($filas, $columna_egreso_cobrable, __('Monto cobrable egreso'), $formato_titulo);
	$ws1->write($filas, $columna_ingreso, __('Ingreso'), $formato_titulo);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		$ws1->write($filas, $columna_ingreso_cobrable, __('Monto cobrable ingreso'), $formato_titulo);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
		$ws1->write($filas, $columna_es_cobrable, __('Cobrable'), $formato_titulo);	
	$ws1->write($filas, $columna_balance, __('Balance'), $formato_titulo);
	$filas++;
	if($orden == "")
		$orden = "cliente.glosa_cliente ASC, fecha DESC";
	
	$col_select ="";
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
	{
		$col_select = " ,if(cta_corriente.cobrable = 1,'Si','No') as esCobrable ";
	}
	
	$query = "SELECT cta_corriente.egreso,
				cta_corriente.ingreso,
				cta_corriente.monto_cobrable,
				cta_corriente.codigo_cliente,
				cliente.glosa_cliente,
				cta_corriente.id_cobro,
				cta_corriente.id_moneda,
				prm_moneda.simbolo,
				cta_corriente.fecha,
				asunto.glosa_asunto,
				cta_corriente.descripcion
				$col_select
			FROM cta_corriente
				LEFT JOIN asunto USING(codigo_asunto)
				LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
				LEFT JOIN usuario ON usuario.id_usuario=cta_corriente.id_usuario
				LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
				JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
			WHERE $where 
			ORDER BY $orden";
	$lista_gastos = new ListaGastos($sesion,'',$query);

	$egreso = $lista_gastos->Get(0)->fields['egreso']*$tipo_cambio/$moneda_base['tipo_cambio'];
	$ingreso = $lista_gastos->Get(0)->fields['ingreso']*$tipo_cambio/$moneda_base['tipo_cambio'];
	if($egreso > 0)
		$egreso_cobrable = $lista_gastos->Get(0)->fields['monto_cobrable']*$tipo_cambio/$moneda_base['tipo_cambio'];
	if($ingreso > 0)
		$ingreso_cobrable = $lista_gastos->Get(0)->fields['monto_cobrable']*$tipo_cambio/$moneda_base['tipo_cambio'];
	$nombre_cliente_anterior = $lista_gastos->Get(0)->fields['glosa_cliente'];

	$col_egreso_para_formula = Utiles::NumToColumnaExcel($columna_egreso);
	$col_egreso_cobrable_para_formula = Utiles::NumToColumnaExcel($columna_egreso_cobrable);
	$col_ingreso_para_formula = Utiles::NumToColumnaExcel($columna_ingreso);
	$col_ingreso_cobrable_para_formula = Utiles::NumToColumnaExcel($columna_ingreso_cobrable);
	$col_balance_para_formula = Utiles::NumToColumnaExcel($columna_balance);

	for($v=0; $v < $lista_gastos->num; $v++)
	{
		$gasto = $lista_gastos->Get($v);
		$tipo_cambio = Moneda::GetTipoCambioMoneda($sesion, $gasto->fields['id_moneda']);
		$columna_actual = 0;
		
		
		if($gasto->fields['egreso'] > 0) {
			$total_balance_egreso +=($gasto->fields['egreso'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
			$total_balance_egreso_cobrable +=($gasto->fields['monto_cobrable'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
			}
		if($gasto->fields['ingreso'] > 0) {
			$total_balance_ingreso +=($gasto->fields['ingreso'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
			$total_balance_ingreso_cobrable +=($gasto->fields['monto_cobrable'] * $tipo_cambio)/$moneda_base['tipo_cambio'];
			}

		if($nombre_cliente_anterior == $gasto->fields['glosa_cliente'])
		{
			if($gasto->fields['egreso'] > 0) {
			$egreso += (double)($gasto->fields['egreso']*$tipo_cambio/$moneda_base['tipo_cambio']);
			$egreso_cobrable += (double)($gasto->fields['monto_cobrable']*$tipo_cambio/$moneda_base['tipo_cambio']);
			}
			if($gasto->fields['ingreso'] > 0) {
			$ingreso += (double)($gasto->fields['ingreso']*$tipo_cambio/$moneda_base['tipo_cambio']);
			$ingreso_cobrable += (double)($gasto->fields['monto_cobrable']*$tipo_cambio/$moneda_base['tipo_cambio']);
			}
		}
		else
		{
			$ws1->write($filas, $columna_cliente, $nombre_cliente_anterior, $formato_normal);
			$ws1->writeNumber($filas, $columna_egreso, $egreso, $formato_moneda);
			$ws1->writeNumber($filas, $columna_ingreso, $ingreso, $formato_moneda);	
			
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarGastosCobrable') ) || ( method_exists('Conf','UsarGastosCobrable') && Conf::UsarGastosCobrable() ) )
			{
				$ws1->write($filas, $columna_es_cobrable, $gasto->fields['esCobrable'], $formato_moneda);
				
				if($gasto->fields['esCobrable'] == 'No'){
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
						$ws1->writeNumber($filas, $columna_egreso_cobrable, 0, $formato_moneda);
					
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
						$ws1->writeNumber($filas, $columna_ingreso_cobrable, 0, $formato_moneda);
						
					$ws1->writeFormula($filas, $columna_balance, "=$col_balance_para_formula".($filas), $formato_moneda);
				}
				else
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
						$ws1->writeNumber($filas, $columna_egreso_cobrable, $egreso_cobrable, $formato_moneda);
					
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
						$ws1->writeNumber($filas, $columna_ingreso_cobrable, $ingreso_cobrable, $formato_moneda);
				
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
						$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula".($filas+1)." - $col_egreso_cobrable_para_formula".($filas+1), $formato_moneda);
					else
						$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_para_formula".($filas+1)." - $col_egreso_para_formula".($filas+1), $formato_moneda);
				}
			}
			else
			{
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
					$ws1->writeNumber($filas, $columna_egreso_cobrable, $egreso_cobrable, $formato_moneda);
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
					$ws1->writeNumber($filas, $columna_ingreso_cobrable, $ingreso_cobrable, $formato_moneda);
					
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
					$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula".($filas+1)." - $col_egreso_cobrable_para_formula".($filas+1), $formato_moneda);
				else
					$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_para_formula".($filas+1)." - $col_egreso_para_formula".($filas+1), $formato_moneda);
			}
			
			
			$filas++;
			$egreso = (double)($gasto->fields['egreso']*$tipo_cambio/$moneda_base['tipo_cambio']);
			$ingreso = (double)($gasto->fields['ingreso']*$tipo_cambio/$moneda_base['tipo_cambio']);
			if( $gasto->fields['egreso'] > 0 ) {
			$ingreso_cobrable = 0;
			$egreso_cobrable = (double)($gasto->fields['monto_cobrable']*$tipo_cambio/$moneda_base['tipo_cambio']);
			}
			if( $gasto->fields['ingreso'] > 0 ) {
			$egreso_cobrable = 0;
			$ingreso_cobrable = (double)($gasto->fields['monto_cobrable']*$tipo_cambio/$moneda_base['tipo_cambio']);
			}
			$nombre_cliente_anterior = $gasto->fields['glosa_cliente'];
		}
	}
	$columna_actual = 0;
	$ws1->write($filas, $columna_cliente, $nombre_cliente_anterior, $formato_normal);
	$ws1->writeNumber($filas, $columna_egreso, $egreso, $formato_moneda);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		$ws1->writeNumber($filas, $columna_egreso_cobrable, $egreso_cobrable, $formato_moneda);
	$ws1->writeNumber($filas, $columna_ingreso, $ingreso, $formato_moneda);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		$ws1->writeNumber($filas, $columna_ingreso_cobrable, $ingreso_cobrable, $formato_moneda);
	$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula".($filas+1)." - $col_egreso_cobrable_para_formula".($filas+1), $formato_moneda);

++$filas;

	$ws1->write($filas, $columna_cliente, __('Total'), $formato_total);
	$ws1->writeFormula($filas, $columna_egreso, "=SUM($col_egreso_para_formula".($fila_inicio+2).":$col_egreso_para_formula$filas)", $formato_moneda_total);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		$ws1->writeFormula($filas, $columna_egreso_cobrable, "=SUM($col_egreso_cobrable_para_formula".($fila_inicio+2).":$col_egreso_cobrable_para_formula$filas)", $formato_moneda_total);
	$ws1->writeFormula($filas, $columna_ingreso, "=SUM($col_ingreso_para_formula".($fila_inicio+2).":$col_ingreso_para_formula$filas)", $formato_moneda_total);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		$ws1->writeFormula($filas, $columna_ingreso_cobrable, "=SUM($col_ingreso_cobrable_para_formula".($fila_inicio+2).":$col_ingreso_cobrable_para_formula$filas)", $formato_moneda_total);
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaMontoCobrable') ) || ( method_exists('Conf','UsaMontoCobrable') && Conf::UsaMontoCobrable() ) )
		$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_cobrable_para_formula".($filas+1)." - $col_egreso_cobrable_para_formula".($filas+1), $formato_moneda);
	else
		$ws1->writeFormula($filas, $columna_balance, "=$col_ingreso_para_formula".($filas+1)." - $col_egreso_para_formula".($filas+1), $formato_moneda);

	if($total_balance_egreso_cobrable > 0 && $total_balance_ingreso_cobrable > 0)
		$total_balance = $total_balance_ingreso_cobrable - $total_balance_egreso_cobrable;
	elseif($total_balance_egreso_cobrable > 0)
		$total_balance = - $total_balance_egreso_cobrable;
	elseif($total_balance_ingreso > 0)
		$total_balance = $total_balance_ingreso_cobrable;
	
	$ws1->write(5, 1, __("Total balance"), $formato_encabezado);
	$ws1->writeFormula(5, 2, "=$col_balance_para_formula".($filas+1), $formato_moneda_encabezado);
	$wb->close();
	exit;
?>
