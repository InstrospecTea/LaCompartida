<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';

	$sesion = new Sesion(array('REP'));
	$pagina = new Pagina($sesion);
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);

	set_time_limit(300);
	
	if($xls)
	{
		$moneda = new Moneda($sesion);
		$id_moneda_referencia = $moneda->GetMonedaTipoCambioReferencia($sesion);
		$id_moneda_base = $moneda->GetMonedaBase($sesion);
		
		$arreglo_monedas = ArregloMonedas($sesion); 
		
		$moneda_base = Utiles::MonedaBase($sesion);
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();

		$wb->setCustomColor(35, 220, 255, 220);
		$wb->setCustomColor(36, 255, 255, 220);

		$formato_encabezado =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline'=>1,
									'Color' => 'black'));

		$formato_texto =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
									'Border' => 1,
									'Color' => 'black',
									'TextWrap' => 1));
		$formato_tiempo =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' =>'[h]:mm'));
		$formato_numero =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => 0));
		$formato_titulo =& $wb->addFormat(array('Size' => 12,
									'Align' => 'center',
									'Bold' => '1',
									'FgColor' => '35',
									'Border' => 1,
									'Locked' => 1,
									'Color' => 'black'));

		$mostrar_encargado_secundario = UtilesApp::GetConf($sesion, 'EncargadoSecundario');

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
		$formatos_moneda_tc = array();
		$query = 'SELECT id_moneda, simbolo, cifras_decimales
				FROM prm_moneda
				ORDER BY id_moneda';
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		while(list($id_moneda, $simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp)){
			$formatos_moneda_tc[$id_moneda] =& $wb->addFormat(array('Size' => 11,
																'VAlign' => 'top',
																'Align' => 'right',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => "[$$simbolo_moneda] #,###,0.00"));
		}
		$cifras_decimales = $moneda_base['cifras_decimales'];
		if($cifras_decimales>0)
		{
			$decimales = '.';
			while($cifras_decimales-- >0)
				$decimales .= '0';
		}
		else
			$decimales = '';
		$formato_moneda_base_rojo =& $wb->addFormat(array('Size' => 11,
														'VAlign' => 'top',
														'Align' => 'right',
														'Border' => 1,
														'Color' => 'red',
														'NumFormat' => '[$'.$moneda_base['simbolo']."] #,###,0$decimales"));
		
		$ws1 =& $wb->addWorksheet(__('Facturacion'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1,0);
		$ws1->setZoom(75);
		
		$filas += 1;
		$ws1->mergeCells($filas, 1, $filas, 2);
		$ws1->write($filas, 1, __('REPORTE HORAS POR FACTURAR'), $formato_encabezado);
		$ws1->write($filas, 2, '', $formato_encabezado);
		$filas +=2;
		$ws1->write($filas,1,__('GENERADO EL:'),$formato_texto);
		$ws1->write($filas,2,date("d-m-Y H:i:s"),$formato_texto);
		
		$filas +=4;
		$col = 0;
		
		$col_codigo_cliente = ++$col;
		$col_cliente = ++$col;
		$col_usuario_encargado = ++$col;
		if($mostrar_encargado_secundario)
			$col_usuario_encargado_secundario = ++$col;
		$col_asunto = ++$col;
		$col_ultimo_trabajo = ++$col;
		$col_ultimo_gasto = ++$col;
		$col_monto_gastos = ++$col;
		$col_ultimo_cobro = ++$col;
		$col_estado_ultimo_cobro = ++$col;
		$col_horas_trabajadas = ++$col;
		$col_forma_cobro = ++$col;
		$col_valor_estimado = ++$col;
		$col_tipo_cambio = ++$col;
		if( $id_moneda_base != $id_moneda_referencia ) {
			$col_tipo_cambio_moneda_base = ++$col;
		}
		$col_valor_en_moneda_base = ++$col;
		$col_valor_en_moneda_base_segun_THH = ++$col;
		
		if($debug)
		{
			$col_monto_contrato = ++$col;
			$col_horas_retainer = ++$col;
			$col_valor_cap = ++$col;
			$col_porcentaje_retainer = ++$col;
		}
		unset($col);
		
		$ws1->setColumn($col_codigo_cliente, $col_codigo_cliente, 16);
		$ws1->setColumn($col_cliente, $col_cliente, 40);
		$ws1->setColumn($col_usuario_encargado, $col_usuario_encargado, 40);
		if($mostrar_encargado_secundario)
			$ws1->setColumn($col_usuario_encargado_secundario, $col_usuario_encargado_secundario, 40);
		$ws1->setColumn($col_asunto, $col_asunto, 40);
		
		$ws1->setColumn($col_ultimo_trabajo, $col_ultimo_trabajo, 15);
		$ws1->setColumn($col_ultimo_gasto, $col_ultimo_gasto, 15);
		$ws1->setColumn($col_monto_gastos, $col_monto_gastos, 18);
		
		$ws1->setColumn($col_ultimo_cobro, $col_ultimo_cobro, 14);
		$ws1->setColumn($col_estado_ultimo_cobro, $col_estado_ultimo_cobro, 22);
		$ws1->setColumn($col_forma_cobro, $col_forma_cobro, 14);
		$ws1->setColumn($col_valor_estimado, $col_valor_estimado, 18);
		$ws1->setColumn($col_tipo_cambio, $col_tipo_cambio, 14);
		if( $id_moneda_base != $id_moneda_referencia ) {
			$ws1->setColumn($col_tipo_cambio_moneda_base, $col_tipo_cambio_moneda_base, 14);
		}
		$ws1->setColumn($col_valor_en_moneda_base, $col_valor_en_moneda_base, 18);
		$ws1->setColumn($col_valor_en_moneda_base_segun_THH, $col_valor_en_moneda_base_segun_THH, 23);
		$ws1->setColumn($col_horas_trabajadas, $col_horas_trabajadas, 19);

		if($debug)
		{	
			$ws1->setColumn($col_monto_contrato, $col_monto_contrato, 18);
			$ws1->setColumn($col_horas_retainer, $col_horas_retainer, 18);
			$ws1->setColumn($col_valor_cap, $col_valor_cap, 18);
			$ws1->setColumn($col_porcentaje_retainer, $col_porcentaje_retainer, 18);
		}

		$ws1->write($filas, $col_codigo_cliente, __('Código Asunto'), $formato_titulo);
		$ws1->write($filas, $col_cliente, __('Cliente'), $formato_titulo);
		$ws1->write($filas, $col_usuario_encargado, __('Encargado Comercial'), $formato_titulo);
		if($mostrar_encargado_secundario)
			$ws1->write($filas, $col_usuario_encargado_secundario, __('Encargado Secundario'), $formato_titulo);
		$ws1->write($filas, $col_asunto, __('Asunto'), $formato_titulo);
		
		$ws1->write($filas, $col_ultimo_trabajo, __('Último trabajo'), $formato_titulo);
		$ws1->write($filas, $col_ultimo_gasto, __('Último gasto'), $formato_titulo);
		$ws1->write($filas, $col_monto_gastos, __('Monto gastos'), $formato_titulo);
		
		$ws1->write($filas, $col_ultimo_cobro, __('Último cobro'), $formato_titulo);
		$ws1->write($filas, $col_estado_ultimo_cobro, __('Estado último cobro'), $formato_titulo);
		$ws1->write($filas, $col_forma_cobro, __('Forma cobro'), $formato_titulo);
		$ws1->write($filas, $col_valor_estimado, __('Valor estimado'), $formato_titulo);
		$ws1->write($filas, $col_tipo_cambio, __('Tipo Cambio'), $formato_titulo);
		if( $id_moneda_base != $id_moneda_referencia ) {
			$ws1->write($filas, $col_tipo_cambio_moneda_base, __('Tipo Cambio '.$arreglo_monedas[$id_moneda_base]['simbolo']), $formato_titulo);
		}
		$ws1->write($filas, $col_valor_en_moneda_base, __('Valor en '.Moneda::GetSimboloMoneda($sesion,Moneda::GetMonedaBase($sesion))), $formato_titulo);
		$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, __('Valor en '.Moneda::GetSimboloMoneda($sesion,Moneda::GetMonedaBase($sesion)).' según THH'), $formato_titulo);
		$ws1->write($filas, $col_horas_trabajadas, __('Horas trabajadas'), $formato_titulo);
		if($debug)
		{
			$ws1->write($filas, $col_monto_contrato, __('Monto Contrato'), $formato_titulo);
			$ws1->write($filas, $col_horas_retainer, __('Horas Retainer'), $formato_titulo);
			$ws1->write($filas, $col_valor_cap, __('Cap Usado'), $formato_titulo);
			$ws1->write($filas, $col_porcentaje_retainer, __('Porcentaje Retainer'), $formato_titulo);
		}
		
		$where_trabajo = " ( trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		$where_gasto   = " ( cta_corriente.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		if( $fecha1 != '' && $fecha2 != '' ) {
			$where_trabajo .= " AND trabajo.fecha >= '".$fecha1."' AND trabajo.fecha <= '".$fecha2."'";
			$where_gasto .= " AND cta_corriente.fecha >= '".$fecha1."' AND cta_corriente.fecha <= '".$fecha2."' ";
		}
		
		$where = " 1 ";
		if(is_array($socios)) {
			$lista_socios = join("','", $socios);
			$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
		}
		if($separar_asuntos) {
			$group_by="asunto.codigo_asunto";
		}
		else {
			$group_by="contrato.id_contrato";
		}
		
		$query = "SELECT 
								asunto.codigo_asunto, 
								asunto.glosa_asunto, 
								GROUP_CONCAT( asunto.glosa_asunto ) as asuntos, 
								cliente.codigo_cliente, 
								cliente.glosa_cliente, 
								GROUP_CONCAT( cliente.glosa_cliente ) as clientes, 
								CONCAT_WS( ec.nombre, ec.apellido1, ec.apellido2 ) as nombre_encargado_comercial, 
								ec.username as username_encargado_comercial, 
								CONCAT_WS( es.nombre, es.apellido1, es.apellido2 ) as nombre_encargado_secundario, 
								es.username as username_encargado_secundario, 
								contrato.id_contrato, 
								contrato.forma_cobro, 
								contrato.id_moneda as id_moneda_contrato, 
								contrato.opc_moneda_total as id_moneda_total 
							FROM asunto 
							LEFT JOIN contrato USING( id_contrato ) 
							LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente 
							LEFT JOIN usuario as ec ON ec.id_usuario = contrato.id_usuario_responsable 
							LEFT JOIN usuario as es ON es.id_usuario = contrato.id_usuario_secundario 
							WHERE $where 
								AND ( ( SELECT count(*) 
													FROM trabajo 
										 LEFT JOIN cobro ON cobro.id_cobro = trabajo.id_cobro 
										 		 WHERE trabajo.codigo_asunto = asunto.codigo_asunto 
										 		 	 AND trabajo.cobrable = 1 
										 		 	 AND trabajo.id_tramite = 0 
										 		 	 AND trabajo.duracion_cobrada != '00:00:00' 
										 		 	 AND $where_trabajo ) > 0
										OR ( SELECT count(*) 
														FROM cta_corriente 
											LEFT JOIN cobro ON cobro.id_cobro = cta_corriente.id_cobro 
													WHERE cta_corriente.codigo_asunto = asunto.codigo_asunto 
														AND cta_corriente.cobrable = 1 
														AND $where_gasto ) > 0 )
							GROUP BY $group_by ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		$fila_inicial = $filas+2;
		while($cobro = mysql_fetch_array($resp))
		{
			$contrato = new Contrato($sesion);
			$contrato->Load($cobro['id_contrato']);
			
			// Definir datos ...
			if($separar_asuntos) {
				$fecha_ultimo_trabajo = $contrato->FechaUltimoTrabajo( $fecha1, $fecha2, $cobro['codigo_asunto'] );
				$fecha_ultimo_gasto = $contrato->FechaUltimoGasto( $fecha1, $fecha2, $cobro['codigo_asunto'] );
				$horas_no_cobradas = $contrato->TotalHoras( false, $cobro['codigo_asunto'], $fecha1, $fecha2 );
				list($monto_estimado_trabajos, $simbolo_moneda_trabajos, $id_moneda_trabajos) = $contrato->TotalMonto( false, $cobro['codigo_asunto'], $fecha1, $fecha2 );
				list($monto_estimado_thh, $simbolo_moneda_thh, $id_moneda_thh) = $contrato->MontoHHTarifaSTD( false, $cobro['codigo_asunto'], $fecha1, $fecha2 );
				list($monto_estimado_gastos, $simbolo_moneda_gastos, $id_moneda_gastos) = $contrato->MontoGastos( false, $cobro['codigo_asunto'], $fecha1, $fecha2 );
			}
			else {
				$fecha_ultimo_trabajo = $contrato->FechaUltimoTrabajo( $fecha1, $fecha2 );
				$fecha_ultimo_gasto = $contrato->FechaUltimoGasto( $fecha1, $fecha2 );
				$horas_no_cobradas = $contrato->TotalHoras( false, '', $fecha1, $fecha2 );
				list($monto_estimado_trabajos, $simbolo_moneda_trabajos, $id_moneda_trabajos) = $contrato->TotalMonto( false );
				list($monto_estimado_thh, $simbolo_moneda_thh, $id_moneda_thh) = $contrato->MontoHHTarifaSTD( false, '', $fecha1, $fecha2 );
				list($monto_estimado_gastos, $simbolo_moneda_gastos, $id_moneda_gastos) = $contrato->MontoGastos( false, '', $fecha1, $fecha2 );
			}
			$id_ultimo_cobro = $contrato->UltimoCobro();
			$ultimo_cobro = new Cobro($sesion);
			$ultimo_cobro->Load($id_ultimo_cobro);
			
			if( empty( $id_moneda_trabajos ) ) {
				$id_moneda_trabajos = $cobro['id_moneda_contrato'];
			}
			++$filas;
			$ws1->write($filas, $col_codigo_cliente, $cobro['codigo_asunto'], $formato_texto);
			$ws1->write($filas, $col_cliente, $cobro['glosa_cliente'], $formato_texto);
			if( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaUsernameEnTodoElSistema') ){
				$ws1->write($filas, $col_usuario_encargado, $cobro['username_encargado_comercial'], $formato_texto);
				if($mostrar_encargado_secundario)
					$ws1->write($filas, $col_usuario_encargado_secundario, $cobro['username_encargado_secundario'], $formato_texto);
			}
			else{
				$ws1->write($filas, $col_usuario_encargado, $cobro['nombre_encargado_comercial'], $formato_texto);
				if($mostrar_encargado_secundario)
					$ws1->write($filas, $col_usuario_encargado_secundario, $cobro['nombre_encargado_secundario'], $formato_texto);
			}
			$ws1->write($filas, $col_asunto,$cobro['asuntos'], $formato_texto);
			$ws1->write($filas, $col_ultimo_trabajo, empty($fecha_ultimo_trabajo) ? "" : Utiles::sql2fecha($fecha_ultimo_trabajo, $formato_fecha, "-" ), $formato_texto);
			$ws1->write($filas, $col_ultimo_gasto, empty($fecha_ultimo_gasto) ? "" : Utiles::sql2fecha($fecha_ultimo_gasto, $formato_fecha, "-"), $formato_texto);
			$ws1->write($filas, $col_monto_gastos, $monto_estimado_gastos, $formatos_moneda[$id_moneda_gastos]);
			$ws1->write($filas, $col_ultimo_cobro,$ultimo_cobro->fields['fecha_fin'] != '' ? Utiles::sql2fecha($ultimo_cobro->fields['fecha_fin'], $formato_fecha, "-") : '', $formato_texto);
			$ws1->write($filas, $col_estado_ultimo_cobro,$ultimo_cobro->fields['estado'] != '' ? $ultimo_cobro->fields['estado'] : '', $formato_texto);
			$ws1->write($filas, $col_horas_trabajadas, number_format($horas_no_cobradas/24,6,'.',''), $formato_tiempo);
			$ws1->write($filas, $col_forma_cobro, $cobro['forma_cobro'], $formato_texto);

			// En el primer asunto de un contrato hay que actualizar el valor descuento al contrato actual
			if( $cobro['id_contrato'] != $id_contrato_anterior )
				$valor_descuento = $cobro['valor_descuento'];
			
			$valor_estimado = $monto_estimado_trabajos;
			
			if($cobro['forma_cobro']=='CAP')
			{
					$cobro_aux = new Cobro($sesion);
					$usado = $cobro_aux->TotalCobrosCap($cobro['id_contrato']); //Llevamos lo cobrado en el CAP a la moneda TOTAL
					if($monto_estimado_trabajos+$usado > $cobro['monto'] )
					{
						$valor_estimado = $cobro['monto'] - $usado;
						if($valor_estimado < 0)
							$valor_estimado = 0;
					}
					else
						$valor_estimado = $monto_estimado_trabajos;
			}
			else
				$valor_estimado = $monto_estimado_trabajos;
				
			// Aplicar descuentos del contrato al valor estimado
			if( $cobro['porcentaje_descuento'] > 0 )
				{
					$valor_estimado *= ( 1 - $cobro['porcentaje_descuento']/100 );
				}
			else if( $valor_descuento > 0 )
				{
					$valor_estimado = $valor_estimado - $valor_descuento;
					if( $valor_estimado < 0 )
						{
							$valor_descuento =  abs($valor_estimado); 
							$valor_estimado = 0;
						}
					else
						$valor_descuento = 0;
				}
			$valor_estimado = UtilesApp::CambiarMoneda( $valor_estimado, 
																									number_format($arreglo_monedas[$cobro['id_moneda_contrato']]['tipo_cambio'],
																																$arreglo_monedas[$cobro['id_moneda_contrato']]['cifras_decimales'],'.',''), 
																									$arreglo_monedas[$cobro['id_moneda_contrato']]['cifras_decimales'], 
																									number_format($arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio'],
																																$arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales'],'.',''), 
																									$arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales']);
			$valor_estimado_moneda_base = UtilesApp::CambiarMoneda( $valor_estimado, 
																															number_format($arreglo_monedas[$cobro['id_moneda_total']]['tipo_cambio'],
																																						$arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales'],'.',''), 
																															$arreglo_monedas[$cobro['id_moneda_total']]['cifras_decimales'], 
																															number_format($moneda_base['tipo_cambio'],
																																						$moneda_base['cifras_decimales'],'.',''), 
																															$moneda_base['cifras_decimales']);
			$valor_thh_moneda_base = UtilesApp::CambiarMoneda( $monto_estimado_thh, 
																												 number_format($arreglo_monedas[$id_moneda_thh]['tipo_cambio'],
																																			 $arreglo_monedas[$id_moneda_thh]['cifras_decimales'],'.',''), 
																												 $arreglo_monedas[$id_moneda_thh]['cifras_decimales'], 
																												 number_format($moneda_base['tipo_cambio'],$moneda_base['cifras_decimales'],'.',''), 
																												 $moneda_base['cifras_decimales']);
			
			$ws1->writeNumber($filas, $col_valor_estimado, $valor_estimado, $formatos_moneda[$cobro['id_moneda_total']]);
			$ws1->writeNumber($filas, $col_tipo_cambio,number_format($arreglo_monedas[$id_moneda_trabajos]['tipo_cambio'],$arreglo_monedas[$id_moneda_trabajos]['cifras_decimales'],'.',''), $formatos_moneda_tc[$id_moneda_referencia]);
			if( $id_moneda_base != $id_moneda_referencia ) {
				$ws1->writeNumber($filas, $col_tipo_cambio_moneda_base, number_format($arreglo_monedas[$id_moneda_base]['tipo_cambio'],$arreglo_monedas[$id_moneda_base]['cifras_decimales'],'.',''), $formatos_moneda_tc[$id_moneda_referencia]);
			}
			
			$ws1->write($filas, $col_valor_en_moneda_base, $valor_estimado_moneda_base, $formatos_moneda[$moneda_base['id_moneda']]);
			
			if($valor_estimado_moneda_base < $valor_thh_moneda_base )
				$formato = $formato_moneda_base_rojo;
			else
				$formato = $formatos_moneda[$moneda_base['id_moneda']];
			$ws1->write($filas, $col_valor_en_moneda_base_segun_THH, $valor_thh_moneda_base, $formato);

			// Excel guarda los tiempos en base a días, por eso se divide en 24.
			//$ws1->writeNumber($filas, $col_horas_trabajadas, $cobro['horas_por_cobrar']/24, $formato_tiempo);

			if($debug)
			{
				if($cobro['forma_cobro'] != 'TASA')
				$ws1->write($filas, $col_monto_contrato, $cobro['monto'], $formatos_moneda[$cobro['id_moneda_total']]);
				if($cobro['forma_cobro'] == 'PROPORCIONAL' || $cobro['forma_cobro'] == 'RETAINER')
					$ws1->write($filas, $col_horas_retainer, $cobro['retainer_horas'] , $formato_tiempo);
				if($cobro['forma_cobro'] == 'CAP')
					$ws1->write($filas, $col_valor_cap, $usado, $formatos_moneda[$cobro['id_moneda_total']]);
				if($cobro['forma_cobro'] == 'PROPORCIONAL' || $cobro['forma_cobro'] == 'RETAINER')
					$ws1->write($filas, $col_porcentaje_retainer, $porcentaje_retainer, $formato_numero);

				$ws1->write($filas, $col_porcentaje_retainer+1,$cobro['horas_por_cobrar'], $formato_numero);
			}
			// Memorizarse el id_contrato para ver en el proximo 
			// paso si todavia estamos en el mismo contrato, importante por el tema del descuento
			$id_contrato_anterior = $cobro['id_contrato'];
		}
		
		if($fila_inicial != ($filas+2))
		{
			// Escribir totales
			$col_formula_valor_en_moneda_base = Utiles::NumToColumnaExcel($col_valor_en_moneda_base);
			$ws1->writeFormula(++$filas, $col_valor_en_moneda_base, "=SUM($col_formula_valor_en_moneda_base$fila_inicial:$col_formula_valor_en_moneda_base$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

			$col_formula_valor_en_moneda_base_segun_THH = Utiles::NumToColumnaExcel($col_valor_en_moneda_base_segun_THH);
			$ws1->writeFormula($filas, $col_valor_en_moneda_base_segun_THH, "=SUM($col_formula_valor_en_moneda_base_segun_THH$fila_inicial:$col_formula_valor_en_moneda_base_segun_THH$filas)", $formatos_moneda[$moneda_base['id_moneda']]);

			$col_formula_horas_trabajadas = Utiles::NumToColumnaExcel($col_horas_trabajadas);
		
			$ws1->writeFormula($filas, $col_horas_trabajadas, "=SUM($col_formula_horas_trabajadas$fila_inicial:$col_formula_horas_trabajadas$filas)", $formato_tiempo);
		}

		$wb->send("Planilla horas por facturar.xls");
		$wb->close();
		exit;
	}

	$pagina->titulo = __('Reporte Facturación pendiente');
	$pagina->PrintTop();
?>
<form method=post name=formulario action="planilla_facturacion_pendiente.php?xls=1">
	<table class="border_plomo tb_base">
		<tr>
			<td align=right>
				<?=__('Fecha desde')?>
			</td>
			<td align=left>
				<?= Html::PrintCalendar("fecha1", "$fecha1"); ?>
			</td>
		</tr>
		<tr>
			<td align=right>
				<?=__('Fecha hasta')?>
			</td>
			<td align=left>
				<?= Html::PrintCalendar("fecha2", "$fecha2"); ?>
			</td>
		</tr>
		<tr>
			<td align=center colspan="2">
				<?=Html::SelectQuery($sesion,"SELECT usuario.id_usuario,CONCAT_WS(' ',apellido1,apellido2,',',nombre)
					FROM usuario JOIN usuario_permiso USING(id_usuario)
					WHERE codigo_permiso='SOC' ORDER BY apellido1", "socios[]", $socios,"class=\"selectMultiple\" multiple size=6 ","","200"); ?>
			</td>
		</tr>
		<tr>
			<td align=center colspan="2">
				<input type="checkbox" value=1 name="separar_asuntos" <?=$separar_asuntos ? 'checked' : ''?>><?=__('Separar Asuntos')?>
			</td>
		</tr>
		<tr>
			<td align=right colspan=2>
				<input type="hidden" name="debug" value="<?=$debug?>" />
				<input type="submit" class=btn value="<?=__('Generar reporte')?>" name="btn_reporte">
			</td>
		</tr>
	</table>
</form>
<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
