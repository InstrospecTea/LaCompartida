<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	require_once Conf::ServerDir().'/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/classes/Gasto.php';
	require_once Conf::ServerDir().'/classes/InputId.php';

	$sesion = new Sesion(array('REP'));
	//Revisa el Conf si esta permitido
	
	set_time_limit(300);

	$pagina = new Pagina($sesion);

	if($xls)
	{ 
		$moneda_base = Utiles::MonedaBase($sesion);
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();


		$wb->setCustomColor (35, 220, 255, 220);
		$wb->setCustomColor (36, 255, 255, 220);

		$encabezado =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));
		$txt_opcion =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_valor =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$txt_centro =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$fecha =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'center',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$numeros =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '0'));
		$titulo_filas =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));
		$time_format =& $wb->addFormat(array('Size' => 10,
									'VAlign' => 'top',
									'Align' => 'justify',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => '[h]:mm'));

		// Generar formatos para los distintos tipos de moneda
		$formatos_moneda = array();
		$query = 'SELECT id_moneda, simbolo, cifras_decimales 
				FROM prm_moneda
				ORDER BY id_moneda';
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while(list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp)){
			if($cifras_decimales>0)
			{
				$decimales = '.';
				while($cifras_decimales-- >0)
					$decimales .= '0';
			}
			else
				$decimales = '';
			$formatos_moneda[$id_moneda] =& $wb->addFormat(array('Size' => 11,
																'VAlign' => 'top',
																'Align' => 'right',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
		}

		$ws1 =& $wb->addWorksheet(__('Facturacion'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,5);
		$ws1->setZoom(75);
		$ws1->hideGridlines();
		$ws1->setLandscape();

		$ws2 =& $wb->addWorksheet(__('Historial'));
		$ws2->setInputEncoding('utf-8');
		$ws2->fitToPages(2,5);
		$ws2->setZoom(75);
		$ws2->hideGridlines();
		$ws2->setLandscape();
		$filas2 = 1;
		$col2_fecha = 1;
		$col2_comentario = 2;
		$ws2->setColumn($col2_fecha, $col2_fecha, 17);
		$ws2->setColumn($col2_comentario, $col2_comentario, 40);

		// Definir los números de las columnas
		// El orden que tienen en esta sección es el que mantienen en la planilla.
		$col = 0;
		$col_numero_cobro = ++$col;
		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || ( method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) )
			$col_nota_cobro = ++$col;
		$col_factura = ++$col;
		$col_fecha_emision = ++$col;
		$col_cliente = ++$col;
		$col_asuntos = ++$col;
		$col_encargado = ++$col;
		$col_horas_trabajadas = ++$col;
		$col_horas_cobradas = ++$col;
		$col_total_cobro_original = ++$col;
		if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
			$col_total_con_iva = ++$col;
		else
			$col_total_cobro = ++$col;
		$col_honorarios = ++$col;
		$col_gastos = ++$col;
		if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
			{
				$col_iva = ++$col;
			}
		$col_monto_pago_honorarios = ++$col;
		$col_monto_pago_gastos = ++$col;
		$col_estado = ++$col;
		$col_fecha_creacion = ++$col;
		$col_fecha_revision = ++$col;
		$col_fecha_corte = ++$col;
		$col_fecha_facturacion = ++$col;
		$col_fecha_envio_a_cliente = ++$col;
		$col_fecha_pago = ++$col;
		unset($col);
		// Para las fórmulas de la hoja
		$col_formula_honorarios = Utiles::NumToColumnaExcel($col_honorarios);
		$col_formula_gastos = Utiles::NumToColumnaExcel($col_gastos);
		if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
			{
				$col_formula_iva = Utiles::NumToColumnaExcel($col_iva);
				$col_formula_total_con_iva = Utiles::NumToColumnaExcel($col_total_con_iva);
			}
		else
			$col_formula_total_cobro = Utiles::NumToColumnaExcel($col_total_cobro);
		
		$ws1->setColumn($col_numero_cobro, $col_numero_cobro, 15);
		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || ( method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) )
			$ws1->setColumn($col_nota_cobro, $col_nota_cobro, 15);
		$ws1->setColumn($col_factura, $col_factura, 11);
		$ws1->setColumn($col_fecha_emision, $col_fecha_emision, 16);
		$ws1->setColumn($col_cliente, $col_cliente, 40);
		$ws1->setColumn($col_asuntos, $col_asuntos, 40);
		$ws1->setColumn($col_encargado, $col_encargado, 20);
		$ws1->setColumn($col_horas_trabajadas, $col_horas_trabajadas, 17);
		$ws1->setColumn($col_horas_cobradas, $col_horas_cobradas, 15);
		$ws1->setColumn($col_total_cobro_original, $col_total_cobro_original, 22);
		$ws1->setColumn($col_honorarios, $col_honorarios, 22); // Los trámites están incluídos aqu.
		$ws1->setColumn($col_gastos, $col_gastos, 22);
		if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
			{
				$ws1->setColumn($col_iva, $col_iva, 22);
				$ws1->setColumn($col_total_con_iva, $col_total_con_iva, 22);
			}
		else
			$ws1->setColumn($col_total_cobro, $col_total_cobro, 22);
		$ws1->setColumn($col_monto_pago_honorarios, $col_monto_pago_honorarios, 22);
		$ws1->setColumn($col_monto_pago_gastos, $col_monto_pago_gastos, 22);
		$ws1->setColumn($col_estado, $col_estado, 21);
		$ws1->setColumn($col_fecha_creacion, $col_fecha_creacion, 17);
		$ws1->setColumn($col_fecha_revision, $col_fecha_revision, 17);
		$ws1->setColumn($col_fecha_corte, $col_fecha_corte, 13);
		$ws1->setColumn($col_fecha_facturacion, $col_fecha_facturacion, 17);
		$ws1->setColumn($col_fecha_envio_a_cliente, $col_fecha_envio_a_cliente, 17);
		$ws1->setColumn($col_fecha_pago, $col_fecha_pago, 13);
		
		++$filas;
		$ws1->mergeCells($filas, $col_numero_cobro, $filas, $col_numero_cobro+2);
		$ws1->write($filas, $col_numero_cobro, __('REPORTE COBRANZAS'), $encabezado);
		$filas +=2;
		$ws1->write($filas, $col_numero_cobro, __('GENERADO EL:'), $txt_opcion);
		if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || ( method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) )
			$ws1->write($filas, $col_nota_cobro, date("d-m-Y H:i:s"), $txt_opcion);
		else
			$ws1->write($filas, $col_factura, date("d-m-Y H:i:s"), $txt_opcion);

		$where = "1";
		if(is_array($socios))
		{
			$lista_socios = join("','", $socios);
			$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
		}
		if(is_array($clientes))
		{
			$lista_clientes = join("','", $clientes);
			$where .= " AND cliente.codigo_cliente IN ('".$lista_clientes."')";
		}
		if($fecha_ini != '' and $fecha_fin != '' and $rango==1 and is_array($estados))
		{
				if($estado=='CREADO') $where_estado='fecha_creacion';
				if($estado=='EN REVISION') $where_estado='fecha_en_revision';
				if($estado=='EMITIDO') $where_estado='fecha_emision';
				if($estado=='ENVIADO AL CLIENTE') $where_estado='fecha_enviado_cliente';
				if($estado=='INCOBRABLE' || $estado=='PAGADO') $where_estado='fecha_cobro';
				if($estado=='CORTE') $where_estado='fecha_fin';

				$where .= " AND cobro.".$where_estado." >= '".Utiles::fecha2sql($fecha_ini)."' AND cobro.".$where_estado." <= '".Utiles::fecha2sql($fecha_fin)." 23:59:59' ";
		}
		else if($fecha_ini != '' and $fecha_fin != '' and $rango==1)
		{
				if($estado=='CREADO') $where_estado='fecha_creacion';
				if($estado=='EN REVISION') $where_estado='fecha_en_revision';
				if($estado=='EMITIDO') $where_estado='fecha_emision';
				if($estado=='ENVIADO AL CLIENTE') $where_estado='fecha_enviado_cliente';
				if($estado=='INCOBRABLE' || $estado=='PAGADO') $where_estado='fecha_cobro';
				if($estado=='CORTE') $where_estado='fecha_fin';

				$where .= " AND cobro.".$where_estado." >= '".Utiles::fecha2sql($fecha_ini)."' AND cobro.".$where_estado." <= '".Utiles::fecha2sql($fecha_fin)." 23:59:59' ";
			++$filas;
			$ws1->write($filas, $col_numero_cobro, __('PERIODO CONSULTA:'), $txt_opcion);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || ( method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) )
				$ws1->write($filas, $col_nota_cobro, $fecha_ini.' - '.$fecha_fin, $txt_opcion);
			else
				$ws1->write($filas, $col_factura, $fecha_ini.' - '.$fecha_fin, $txt_opcion);
		}
		else if($fecha_mes != '' and $fecha_anio != '' and is_array($estados))
		{
				if($estado=='CREADO') $where_estado='fecha_creacion';
				if($estado=='EN REVISION') $where_estado='fecha_en_revision';
				if($estado=='EMITIDO') $where_estado='fecha_emision';
				if($estado=='ENVIADO AL CLIENTE') $where_estado='fecha_enviado_cliente';
				if($estado=='INCOBRABLE' || $estado=='PAGADO') $where_estado='fecha_cobro';
				if($estado=='CORTE') $where_estado='fecha_fin';

				$where .= " AND cobro.".$where_estado." >= '$fecha_anio-$fecha_mes-01 00:00:00' AND cobro.".$where_estado." <= '$fecha_anio-$fecha_mes-31 23:59:59' ";
		}
		else if($fecha_mes != '' and $fecha_anio != '')
		{
				if($estado=='CREADO') $where_estado='fecha_creacion';
				if($estado=='EN REVISION') $where_estado='fecha_en_revision';
				if($estado=='EMITIDO') $where_estado='fecha_emision';
				if($estado=='ENVIADO AL CLIENTE') $where_estado='fecha_enviado_cliente';
				if($estado=='INCOBRABLE' || $estado=='PAGADO') $where_estado='fecha_cobro';
				if($estado=='CORTE') $where_estado='fecha_fin';

				$where .= " AND cobro.".$where_estado." >= '$fecha_anio-$fecha_mes-01 00:00:00' AND cobro.".$where_estado." <= '$fecha_anio-$fecha_mes-31 23:59:59' ";
			++$filas;
			$ws1->write($filas, $col_numero_cobro, __('PERIODO CONSULTA:'), $txt_opcion);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || ( method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) )
				$ws1->write($filas, $col_nota_cobro, $fecha_mes.'-'.$fecha_anio, $txt_opcion);
			else
				$ws1->write($filas, $col_factura, $fecha_mes.'-'.$fecha_anio, $txt_opcion);
		}
		if(is_array($estados))
		{
			$lista_estados = join("','", $estados);
			$where .= " AND cobro.estado IN ('$lista_estados')";
		}
		$filas +=4;
		$tabla_creada=false;
		$query = "SELECT cobro.fecha_creacion,
						cliente.glosa_cliente,
						CONCAT(usuario.nombre,' ', usuario.apellido1) AS nombre,
						cobro.saldo_final_gastos * (cobro_moneda.tipo_cambio /cambio.tipo_cambio)*-1 as gastos,
						cobro.estado,
						cobro.id_cobro,
						prm_moneda_cobro.simbolo,
						prm_moneda_titulo.glosa_moneda,
						cobro.monto,
						cobro.monto_subtotal, 
						prm_moneda_cobro.cifras_decimales,
						cobro.tipo_cambio_moneda,
						cambio.tipo_cambio,
						cobro.id_moneda_monto as id_moneda_monto,
						prm_moneda_titulo.cifras_decimales as cifras_decimales_titulo,
						cobro.fecha_emision,
						cobro.forma_cobro,
						cobro.porcentaje_impuesto, 
						cobro.fecha_fin,
						cobro.monto_contrato as monto_contrato,
						cobro.fecha_en_revision,
						cobro.opc_moneda_total,
						cobro.porcentaje_impuesto_gastos, 
						cobro.descuento,
						cobro.subtotal_gastos,
						cobro.fecha_facturacion,
						cobro.fecha_enviado_cliente,
						cobro.fecha_cobro,
						cobro.documento,
						cobro.monto_gastos,
						cobro.id_moneda,
						cobro.modalidad_calculo,
						documento.honorarios,
						documento.gastos,
						documento.saldo_honorarios,
						documento.saldo_gastos
					FROM cobro
						LEFT JOIN documento ON documento.id_cobro = cobro.id_cobro AND documento.tipo_doc = 'N'
						LEFT JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
						LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
						LEFT JOIN usuario ON usuario.id_usuario = contrato.id_usuario_responsable
						LEFT JOIN prm_moneda as prm_moneda_cobro ON prm_moneda_cobro.id_moneda = cobro.id_moneda
						LEFT JOIN prm_moneda as prm_moneda_titulo ON prm_moneda_titulo.id_moneda = ".$moneda."
						LEFT JOIN
							(SELECT id_cobro,tipo_cambio FROM cobro_moneda WHERE id_moneda=".$moneda.")
							AS cambio ON cambio.id_cobro=cobro.id_cobro
						LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro=cobro.id_cobro AND cobro_moneda.id_moneda=cobro.opc_moneda_total
					WHERE $where 
					ORDER BY cliente.glosa_cliente,
						cobro.fecha_creacion";
		// Obtener los asuntos de cada cobro
		$query_asuntos = "SELECT cobro.id_cobro,
							GROUP_CONCAT(distinct glosa_asunto SEPARATOR '\n') as asuntos
						FROM cobro
							LEFT JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
							LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
							LEFT JOIN cobro_asunto ON cobro.id_cobro = cobro_asunto.id_cobro
							LEFT JOIN asunto ON asunto.codigo_asunto = cobro_asunto.codigo_asunto
						WHERE $where
						GROUP BY cobro.id_cobro";
		$resp = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos, __FILE__, __LINE__, $sesion->dbh);
		$glosa_asuntos = array();
		while(list($id_cobro, $asuntos) = mysql_fetch_array($resp)){
			$glosa_asuntos[$id_cobro] = $asuntos;
		}

		#Clientes
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		$fila_inicial = $filas + 2;
		while($cobro = mysql_fetch_array($resp))
		{
			if(!$tabla_creada)
			{
				$ws1->write($filas, $col_numero_cobro, __('N° del Cobro'), $titulo_filas);
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || ( method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) )
					$ws1->write($filas, $col_nota_cobro, __('Nota Cobro'), $titulo_filas);
				$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
				$ws1->write($filas, $col_fecha_creacion, __('Fecha Creación'), $titulo_filas);
				$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
				$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
				$ws1->write($filas, $col_encargado, __('Encargado'), $titulo_filas);
				$ws1->write($filas, $col_horas_trabajadas, __('Hrs. Trabajadas'), $titulo_filas);
				$ws1->write($filas, $col_horas_cobradas, __('Hrs. Cobradas'), $titulo_filas);
				$ws1->write($filas, $col_total_cobro_original, __('Total Cobro Original'), $titulo_filas);
				if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
					{
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) )  )
						$ws1->write($filas, $col_total_con_iva, __('Total facturado'), $titulo_filas);
					else 
						$ws1->write($filas, $col_total_con_iva, __('Total con IVA'), $titulo_filas);
					}
				else	
					{
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'PermitirFactura') ) || ( method_exists('Conf','PermitirFactura') && Conf::PermitirFactura() ) )  )
						$ws1->write($filas, $col_total_cobro, __('Total facturado'), $titulo_filas);
					else
						$ws1->write($filas, $col_total_cobro, __('Total Cobro'), $titulo_filas);
					}
				$ws1->write($filas, $col_honorarios, __('Honorarios'), $titulo_filas);
				$ws1->write($filas, $col_gastos, __('Gastos'), $titulo_filas);
				if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
					$ws1->write($filas, $col_iva, __('IVA'), $titulo_filas);
				$ws1->write($filas, $col_estado, __('Estado'), $titulo_filas);
				$ws1->write($filas, $col_fecha_revision, __('Fecha Revisión'), $titulo_filas);
				$ws1->write($filas, $col_fecha_emision, __('Fecha Emisión'), $titulo_filas);
				$ws1->write($filas, $col_fecha_corte, __('Fecha Corte'), $titulo_filas);
				$ws1->write($filas, $col_fecha_facturacion, __('Fecha Facturación'), $titulo_filas);
				$ws1->write($filas, $col_fecha_envio_a_cliente, __('Fecha Envío al Cliente'), $titulo_filas);
				$ws1->write($filas, $col_fecha_pago, __('Fecha Pago'), $titulo_filas);
				$ws1->write($filas, $col_monto_pago_honorarios, __('Honorarios pagados'), $titulo_filas);
				$ws1->write($filas, $col_monto_pago_gastos, __('Gastos pagados'), $titulo_filas);
				$tabla_creada=true;
			}
			$query_trabajos = "SELECT SUM(TIME_TO_SEC(duracion)),SUM(TIME_TO_SEC(duracion_cobrada))
								FROM trabajo
								WHERE id_cobro=".$cobro['id_cobro'];
			$resp2 = mysql_query($query_trabajos, $sesion->dbh) or Utiles::errorSQL($query_trabajos, __FILE__, __LINE__, $sesion->dbh);
			list($duracion, $duracion_cobrable)=mysql_fetch_array($resp2);
			$duracion=$duracion/24/3600; //Excel calcula el tiempo en días
			$duracion_cobrable=$duracion_cobrable/24/3600;

			// Calcular gastos
			$gastos=0;
			
			
			
			
			$cobro_moneda = new CobroMoneda($sesion);
			$cobro_moneda->Load($cobro['id_cobro']);

			if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'SinAproximacion') ) || ( method_exists('Conf','SinAproximacion') && Conf::SinAproximacion() ) )  )
				{
					if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )  )
						$aproximacion_gastos = $cobro['subtotal_gastos'];
					else 
						$aproximacion_gastos = $cobro['monto_gastos'];
					$monto_gastos = $aproximacion_gastos*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro['tipo_cambio'];
					
					$aproximacion_honorarios = $cobro['monto_subtotal']-$cobro['descuento'];
					$monto_honorarios = $aproximacion_honorarios*($cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro['tipo_cambio']);
					
					if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
						{
							if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )  )
								$aproximacion_iva = (($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'])*$cobro['porcentaje_impuesto']/100+$cobro['subtotal_gastos']*$cobro['porcentaje_impuesto_gastos']/100;
							else
								$aproximacion_iva = (($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'])*$cobro['porcentaje_impuesto']/100;
							$monto_iva = $aproximacion_iva*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro['tipo_cambio'];
						}
					
					if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
						{
								if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )  )
									$aproximacion_monto = (($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'])*(1+$cobro['porcentaje_impuesto']/100)+$cobro['subtotal_gastos']*(1+$cobro['porcentaje_impuesto_gastos']/100);
								else 
									$aproximacion_monto = (($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'])*(1+$cobro['porcentaje_impuesto']/100)+$cobro['monto_gastos']*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio'];
						}
					else	
						$aproximacion_monto = $cobro['monto']*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']+$cobro['monto_gastos'];
				}
			else
				{
					if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) ) )
						$aproximacion_gastos = $cobro['subtotal_gastos'];
					else 
						$aproximacion_gastos = $cobro['monto_gastos'];
					$monto_gastos = number_format($aproximacion_gastos*$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro['tipo_cambio'],$cobro_moneda->moneda[$moneda]['cifras_decimales'],'.','');
				
					$aproximacion_honorarios = number_format($cobro['monto_subtotal']-$cobro['descuento'],$cobro['cifras_decimales'],'.','');
					$monto_honorarios = number_format($aproximacion_honorarios*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro['tipo_cambio'],$cobro_moneda->moneda[$moneda]['cifras_decimales'],'.','');
		
					if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  ) 
						{
							if( $cobro['opc_moneda_total'] == $moneda )
								{
									if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )  )
										$monto_iva = number_format(($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']*$cobro['porcentaje_impuesto']/100+$cobro['subtotal_gastos']*$cobro['porcentaje_impuesto_gastos']/100, $cobro_moneda->moneda[$cobro['opc_moneda_total']]['cifras_decimales'],'.','');
									else
										$monto_iva = number_format(($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']*$cobro['porcentaje_impuesto']/100, $cobro_moneda->moneda[$cobro['opc_moneda_total']]['cifras_decimales'],'.','');
								}
							else
								{
									if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )  )
										$aproximacion_iva = number_format(($cobro['monto_subtotal']-$cobro['descuento'])*$cobro['porcentaje_impuesto']/100+$cobro['subtotal_gastos']*$cobro['porcentaje_impuesto_gastos']/100,$cobro['cifras_decimales'],'.','');
									else
										$aproximacion_iva = number_format((($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'])*$cobro['porcentaje_impuesto']/100,$cobro['cifras_decimales'],'.','');
									$monto_iva = number_format($aproximacion_iva*($cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']/$cobro['tipo_cambio']),$cobro['cifras_decimales'],'.','');
								}
						}
					
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) )
						{
						if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoPorGastos') ) || ( method_exists('Conf','UsarImpuestoPorGastos') && Conf::UsarImpuestoPorGastos() ) )
							$aproximacion_monto = number_format((($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'])*(1+$cobro['porcentaje_impuesto']/100)+$cobro['subtotal_gastos']*(1+$cobro['porcentaje_impuesto_gastos']/100),$cobro_moneda->moneda[$cobro['opc_moneda_total']]['cifras_decimales'],'.','');
						else	
							$aproximacion_monto = number_format((($cobro['monto_subtotal']-$cobro['descuento'])*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'])*(1+$cobro['porcentaje_impuesto']/100)+$cobro['monto_gastos'],$cobro_moneda->moneda[$cobro['opc_moneda_total']]['cifras_decimales'],'.','');
						}	
					else
						$aproximacion_monto = number_format($cobro['monto']*$cobro_moneda->moneda[$cobro['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio']+$cobro['monto_gastos'], $cobro_moneda->moneda[$cobro['opc_moneda_total']]['cifras_decimales'], '.', '');
				}
			
			// Calcular monto pago para honorarios y gastos (por separado)
			// Calcular monto pago para honorarios y gastos (por separado)
			$monto_pago_gastos = $cobro['gastos']-$cobro['saldo_gastos'];
			$monto_pago_honorarios = $cobro['honorarios']-$cobro['saldo_honorarios'];
			
			/* Cambio al tipo de cambio del cobro*/
			$monto_pago_gastos *= $cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'];
			$monto_pago_honorarios *= $cobro_moneda->moneda[$cobro['opc_moneda_total']]['tipo_cambio'];

			$monto_pago_gastos /= $cobro['tipo_cambio'];
			$monto_pago_honorarios /= $cobro['tipo_cambio'];

			$pago_parcial = false;
			if( $cobro['gastos'] > $cobro['saldo_gastos'] && $cobro['saldo_gastos'] > 0)
				$pago_parcial = true;
			if( $cobro['honorarios'] > $cobro['saldo_honorarios'] && $cobro['saldo_honorarios'] > 0)
				$pago_parcial = true;
			if( $cobro['estado'] == 'PAGADO' )
				$pago_parcial = false;
				
			/*
			 * IF para mostrar calculo segun forma antigua o nueva
			 * Si forma_calculo == 1 (forma nueva, con función procesaCobroIdMoneda)
			 * Si forma_calculo == 0 (forma antigua)
			 */
			if($cobro['modalidad_calculo']==1){
				$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro['id_cobro']);
				/***
				* Total Cobro Original = monto(honorarios, gastos,iva,descuentos)[opc_moneda_total]
				* Honorarios = monto_honorarios(honorarios,descuento)[moneda_seleccionada]
				* Gastos = monto_gastos(gasto_subtotal)[moneda_seleccionada]
				* IVA = suma(impuesto,impuesto_gastos)[moneda_seleccionada]
				**/
				$x_monto												=$x_resultados['monto'][$cobro['opc_moneda_total']];
				$x_monto_subtotal								=$x_resultados['monto_subtotal'][$moneda];
				$x_monto_trabajos								=$x_resultados['monto_trabajos'][$moneda];
				$x_monto_tramites								=$x_resultados['monto_tramites'][$moneda];
				$x_impuesto											=$x_resultados['impuesto'][$moneda];
				$x_impuesto_gastos							=$x_resultados['impuesto_gastos'][$moneda];
				$x_descuento										=$x_resultados['descuento'][$moneda];
				$x_monto_iva										=$x_resultados['monto_iva'][$moneda];
				$x_monto_total_cobro						=$x_resultados['monto_total_cobro'][$moneda];
				$x_monto_honorarios							=$x_resultados['monto_honorarios'][$moneda];
				$x_subtotal_gastos							=$x_resultados['subtotal_gastos'][$moneda];
				$x_monto_gastos									=$x_resultados['monto_gastos'][$moneda];
				$x_monto_cobro_original					=$x_resultados['monto_cobro_original'][$cobro['opc_moneda_total']];
				$x_monto_cobro_original_con_iva	=$x_resultados['monto_cobro_original_con_iva'][$cobro['opc_moneda_total']];
			}
			elseif($cobro['modalidad_calculo']==0){
				$x_monto_honorarios = number_format($monto_honorarios, $cobro_moneda->moneda[$moneda]['cifras_decimales_titulo'], '.', '');
				$x_monto_gastos = number_format($monto_gastos, $cobro_moneda->moneda[$moneda]['cifras_decimales_titulo'], '.', '');
				$x_subtotal_gastos = number_format($monto_gastos, $cobro_moneda->moneda[$moneda]['cifras_decimales_titulo'], '.', '');
				$x_monto_iva = number_format($monto_iva,$cobro_moneda->moneda[$moneda]['cifras_decimales_titulo'], '.', '');
				$x_monto_cobro_original = $aproximacion_monto;
				$x_monto_cobro_original_con_iva = $aproximacion_monto;
			}
			
			++$filas;
			$ws1->write($filas, $col_numero_cobro, $cobro['id_cobro'], $fecha);
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'NotaCobroExtra') ) || ( method_exists('Conf','NotaCobroExtra') && Conf::NotaCobroExtra() ) ) )
			{
				$ws1->write($filas, $col_nota_cobro, $cobro['nota_cobro'], $fecha);
			}
			$ws1->write($filas, $col_factura, $cobro['documento'], $fecha);
			$ws1->write($filas, $col_fecha_creacion,Utiles::sql2date($cobro['fecha_creacion']), $fecha);
			$ws1->write($filas, $col_cliente, $cobro['glosa_cliente'], $txt_opcion);
			$ws1->write($filas, $col_asuntos, $glosa_asuntos[$cobro['id_cobro']], $txt_opcion);
			$ws1->write($filas, $col_encargado, $cobro['nombre'], $txt_opcion);
			$ws1->writeNumber($filas, $col_horas_trabajadas, $duracion, $time_format);
			$ws1->writeNumber($filas, $col_horas_cobradas, $duracion_cobrable, $time_format);
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) || ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) )
			{	
				$ws1->writeNumber($filas, $col_total_cobro_original, $x_monto_cobro_original_con_iva, $formatos_moneda[$cobro['opc_moneda_total']]);
			}
			else{
				$ws1->writeNumber($filas, $col_total_cobro_original, $x_monto_cobro_original, $formatos_moneda[$cobro['opc_moneda_total']]);
			}
			
			if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
			{
				$ws1->writeFormula($filas, $col_total_con_iva, "=$col_formula_honorarios".($filas+1)."+$col_formula_gastos".($filas+1)."+$col_formula_iva".($filas+1), $formatos_moneda[$moneda]);
			}
			else{
				$ws1->writeFormula($filas, $col_total_cobro, "=$col_formula_honorarios".($filas+1)."+$col_formula_gastos".($filas+1), $formatos_moneda[$moneda]);
			}
			$ws1->writeNumber($filas, $col_honorarios, $x_monto_honorarios, $formatos_moneda[$moneda]);
			//-->$ws1->writeNumber($filas, $col_gastos, number_format($monto_gastos, 6/*$cobro['cifras_decimales_titulo']*/, '.', ''), $formatos_moneda[$moneda]);
			$ws1->writeNumber($filas, $col_gastos, $x_subtotal_gastos, $formatos_moneda[$moneda]);
			if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() ) )
			{
				$ws1->writeNumber($filas, $col_iva, $x_monto_iva, $formatos_moneda[$moneda]);
			}
			
			if(!$pago_parcial)
				$ws1->write($filas, $col_estado, $cobro['estado'], $txt_centro);
			else
				$ws1->write($filas, $col_estado, __('PAGO PARCIAL'), $txt_centro);
				
			$ws1->write($filas, $col_fecha_revision,Utiles::sql2date($cobro['fecha_en_revision']) ? Utiles::sql2date($cobro['fecha_en_revision']) : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_emision,Utiles::sql2date($cobro['fecha_emision']) ? Utiles::sql2date($cobro['fecha_emision']) : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_corte,Utiles::sql2date($cobro['fecha_fin']) ? Utiles::sql2date($cobro['fecha_fin']) : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_facturacion,Utiles::sql2date($cobro['fecha_facturacion']) ? Utiles::sql2date($cobro['fecha_facturacion']) : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_envio_a_cliente,Utiles::sql2date($cobro['fecha_enviado_cliente']) ? Utiles::sql2date($cobro['fecha_enviado_cliente']) : ' - ', $fecha);
			$ws1->write($filas, $col_fecha_pago,Utiles::sql2date($cobro['fecha_cobro']) ? Utiles::sql2date($cobro['fecha_cobro']) : ' - ', $fecha);
			$ws1->writeNumber($filas, $col_monto_pago_honorarios, number_format($monto_pago_honorarios, $cobro['cifras_decimales_titulo'], '.', ''), $formatos_moneda[$moneda]);
			$ws1->writeNumber($filas, $col_monto_pago_gastos, number_format($monto_pago_gastos, $cobro['cifras_decimales_titulo'], '.', ''), $formatos_moneda[$moneda]);

			if($cobro['estado']!='CREADO' && $cobro['estado']!='EN REVISION'){
				$comentario="";
				$query_historial="SELECT fecha, comentario FROM cobro_historial WHERE id_cobro=".$cobro['id_cobro'];
				$resp_historial = mysql_query($query_historial, $sesion->dbh) or Utiles::errorSQL($query_historial, __FILE__, __LINE__, $sesion->dbh);

				$ws2->mergeCells($filas2, $col2_fecha, $filas2, $col2_comentario);
				$ws2->write($filas2, $col2_fecha, __("Historial Cobro") . " ".$cobro['id_cobro'].' ('.$cobro['glosa_cliente'].')', $titulo_filas);
				++$filas2;
				$ws2->write($filas2, $col2_fecha, __('Fecha'), $titulo_filas);
				$ws2->write($filas2, $col2_comentario, __('Comentario'), $titulo_filas);
				++$filas2;
				while($historial = mysql_fetch_array($resp_historial))
				{
					$comentario .= Utiles::sql2date($historial['fecha']).": ".$historial['comentario']."\n";
					$ws2->write($filas2, $col2_fecha, Utiles::sql2date($historial['fecha']), $fecha);
					$ws2->write($filas2, $col2_comentario, $historial['comentario'], $txt_opcion);
					++$filas2;
				}
				++$filas2;
				$ws1->writeNote($filas, $col_estado, $comentario);
			}
			$tabla_creada=true;
		}
		if ($tabla_creada)
		{
			++$filas;
			$ws1->write($filas, $col_numero_cobro, __('Total'), $encabezado);

			if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
				$ws1->writeFormula($filas, $col_total_con_iva, "=SUM($col_formula_total_con_iva$fila_inicial:$col_formula_total_con_iva$filas)", $formatos_moneda[$moneda]);
			else
				$ws1->writeFormula($filas, $col_total_cobro, "=SUM($col_formula_total_cobro$fila_inicial:$col_formula_total_cobro$filas)", $formatos_moneda[$moneda]);
			$ws1->writeFormula($filas, $col_honorarios, "=SUM($col_formula_honorarios$fila_inicial:$col_formula_honorarios$filas)", $formatos_moneda[$moneda]);
			$ws1->writeFormula($filas, $col_gastos, "=SUM($col_formula_gastos$fila_inicial:$col_formula_gastos$filas)", $formatos_moneda[$moneda]);
			if(  ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarImpuestoSeparado') ) ||  ( method_exists('Conf','UsarImpuestoSeparado') && Conf::UsarImpuestoSeparado() )  )  )
				$ws1->writeFormula($filas, $col_iva, "=SUM($col_formula_iva$fila_inicial:$col_formula_iva$filas)", $formatos_moneda[$moneda]);
		}
		else
		{
			$ws1->mergeCells($filas, $col_numero_cobro, $filas, $col_fecha_pago);
			$ws1->write($filas, $col_numero_cobro, __('No se encontraron resultados'), $encabezado);
		}
		$wb->send("planilla_cobranza.xls");
		$wb->close();
		exit;
	}
	$pagina->titulo = __('Reporte Cobranza');
	$pagina->PrintTop();
?>
<form method=post name=formulario action="<?php echo $_server['php_self'];?>?xls=1">
<input type=hidden name=horas_sql id=horas_sql value='<?=$horas_sql ? $horas_sql : 'hr_trabajadas' ?>'/>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->
<?
$hoy = date("Y-m-d");
?>
<table class="border_plomo tb_base" width="650px" cellpadding="0" cellspacing="3" align="center">
	<tr>
		<td align="center">
<table style="border: 0px solid black;" width="99%" cellpadding="0" cellspacing="3">
	<tr valign=top>
		<td align=left colspan="2" width='33%' >
			<b><?=__('Clientes')?>:</b>
		</td>
		<td align=left width='33%'>
			<b><?=__('Encargados Comerciales')?>:</b>
		</td>
		<td align=left width='33%'>
			<b><?=__('Estado')?>:</b>
		</td>
	</tr>
	<tr valign=top>
		<td rowspan="2" colspan="2" align=left>
			<?=Html::SelectQuery($sesion,"SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]", $clientes,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
		</td>
		<td rowspan="2" align=left>
			<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC' ORDER BY apellido1", "socios[]", $socios,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
		</td>
		<td rowspan="2" align=left>
			<?=Html::SelectQuery($sesion,"SELECT codigo_estado_cobro AS estado FROM prm_estado_cobro ORDER BY orden ASC", "estados[]", $estados,"class=\"selectMultiple\" multiple size=6 ","","200"); ?></td>
		</td>
	</tr>
	<tr><td colspan="3">&nbsp;</td></tr>
<?
	if(!$tipo)
		$tipo = 'Profesional';
?>
<!-- PERIODOS -->
	<tr>
		<td align=right><b><?=__('Fecha de') ?>:&nbsp;</b></td><td align=left>
		<select name='estado' id='estado' style='width: 120px;'>
			<option value='CORTE'>CORTE</option>
			<option value='CREADO'>CREADO</option>
			<option value='EN REVISION'>EN REVISION</option>
			<option value='EMITIDO'>EMITIDO</option>
			<option value='FACTURADO'>FACTURADO</option>
			<option value='ENVIADO AL CLIENTE'>ENVIADO AL CLIENTE</option>
			<option value='PAGO PARCIAL'>PAGO PARCIAL</option>
			<option value='PAGADO'>PAGADO</option>
			<option value='INCOBRABLE'>INCOBRABLE</option>
		</select></td>
		<td align=right>
			<b><?=__('Periodo') ?>:</b>&nbsp;&nbsp;
			<input type="checkbox" id="rango" name="rango" value="1" <?=$rango ? 'checked' : '' ?> onclick='Rangos(this, this.form);' title='Otro rango' />&nbsp;
			<label for="rango" style="font-size:9px"><?=__('Otro rango') ?></label>
		</td>
		<td align=left>
<?
		if(!$fecha_mes)
			$fecha_mes = date('m');
?>
			<div id=periodo style='display:<?=!$rango ? 'inline' : 'none' ?>;'>
				<select name="fecha_mes" style='width:60px'>
					<option value='1' <?=$fecha_mes==1 ? 'selected':'' ?>><?=__('Enero') ?></option>
					<option value='2' <?=$fecha_mes==2 ? 'selected':'' ?>><?=__('Febrero') ?></option>
					<option value='3' <?=$fecha_mes==3 ? 'selected':'' ?>><?=__('Marzo') ?></option>
					<option value='4' <?=$fecha_mes==4 ? 'selected':'' ?>><?=__('Abril') ?></option>
					<option value='5' <?=$fecha_mes==5 ? 'selected':'' ?>><?=__('Mayo') ?></option>
					<option value='6' <?=$fecha_mes==6 ? 'selected':'' ?>><?=__('Junio') ?></option>
					<option value='7' <?=$fecha_mes==7 ? 'selected':'' ?>><?=__('Julio') ?></option>
					<option value='8' <?=$fecha_mes==8 ? 'selected':'' ?>><?=__('Agosto') ?></option>
					<option value='9' <?=$fecha_mes==9 ? 'selected':'' ?>><?=__('Septiembre') ?></option>
					<option value='10' <?=$fecha_mes==10 ? 'selected':'' ?>><?=__('Octubre') ?></option>
					<option value='11' <?=$fecha_mes==11 ? 'selected':'' ?>><?=__('Noviembre') ?></option>
					<option value='12' <?=$fecha_mes==12 ? 'selected':'' ?>><?=__('Diciembre') ?></option>
				</select>
<?
			if(!$fecha_anio)
				$fecha_anio = date('Y');
?>
				<select name="fecha_anio" style='width:55px'>
					<? for($i=(date('Y')-5);$i < (date('Y')+5);$i++){ ?>
					<option value='<?=$i?>' <?=$fecha_anio == $i ? 'selected' : '' ?>><?=$i ?></option>
					<? } ?>
				</select>
			</div>
			<div id=periodo_rango style='display:<?=$rango ? 'inline' : 'none' ?>;'>
				<?=__('Fecha desde')?>:
					<input type="text" name="fecha_ini" value="<?=$fecha_ini ? $fecha_ini : date("d-m-Y",strtotime("$hoy")) ?>" id="fecha_ini" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
				<br />
				<?=__('Fecha hasta')?>:&nbsp;
					<input type="text" name="fecha_fin" value="<?=$fecha_fin ? $fecha_fin : date("d-m-Y",strtotime("$hoy")) ?>" id="fecha_fin" size="11" maxlength="10" />
					<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_fin" style="cursor:pointer" />
			</div>
	</tr>
	<tr>
		<td align=right><b><?=__('Moneda') ?>:</b>&nbsp;</td><td colspan="3" align=left><?=Html::SelectQuery($sesion,"SELECT id_moneda,glosa_moneda AS nombre FROM prm_moneda ORDER BY id_moneda ASC", "moneda", $moneda,"","","50"); ?>&nbsp;</td>
	</tr>
	<tr>
			<td align=right colspan="4">
				<input type=submit class=btn value="<?=__('Generar planilla')?>" />
			</td>
	</tr>
</table>
		</td>
	</tr>
</table>
</form>
<script type="text/javascript">
<!-- //
	function Rangos(obj, form) {
		if (obj.checked) {
			jQuery('#periodo').hide();
			jQuery('#periodo_rango').show();
		} else {
			jQuery('#periodo').show();
			jQuery('#periodo_rango').hide();
		}
	}
Calendar.setup(
	{
		inputField	: "fecha_ini",		// ID of the input field
		ifFormat	: "%d-%m-%Y",		// the date format
		button		: "img_fecha_ini"	// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha_fin",		// ID of the input field
		ifFormat	: "%d-%m-%Y",		// the date format
		button		: "img_fecha_fin"	// ID of the button
	}
);
// ->
</script>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
