<?php
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';

	$sesion = new Sesion(array('ADM', 'COB'));
	set_time_limit(400);
	ini_set("memory_limit","256M");
	$where_cobro = ' 1 ';

	if($id_cobro)
		$where_cobro .= " AND cobro.id_cobro=$id_cobro ";

	// Procesar los filtros
	if($codigo_cliente_secundario)
	{
		$cliente = new Cliente($sesion);
		$codigo_cliente = $cliente->CodigoSecundarioACodigo($codigo_cliente_secundario);
	}
	if($codigo_cliente)
	{
		$cliente = new Cliente($sesion);
		$codigo_cliente_secundario = $cliente->CodigoACodigoSecundario($codigo_cliente);
	}
	if($activo)
		$where_cobro .= " AND contrato.activo = 'SI' ";
	elseif($no_activo)
		$where_cobro .= " AND contrato.activo = 'NO' ";
	if($id_usuario)
		$where_cobro .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
	if($codigo_cliente)
		$where_cobro .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
	if($id_grupo_cliente)
		$where_cobro .= " AND cliente.id_grupo_cliente = '$id_grupo_cliente' ";

	if($forma_cobro)
	   $where_cobro .= " AND contrato.forma_cobro = '$forma_cobro' ";
	if($tipo_liquidacion){ //1:honorarios, 2:gastos, 3:mixtas
	   $incluye_honorarios = $tipo_liquidacion&1 ? true : false;
	   $incluye_gastos = $tipo_liquidacion&2 ? true : false;
	   $where_cobro .= " AND cobro.incluye_gastos = '$incluye_gastos' AND cobro.incluye_honorarios = '$incluye_honorarios' ";
	}


	if(!$id_cobro)
		{
			$borradores = true;
			$opc_ver_gastos = 1; 
		}
	$mostrar_resumen_de_profesionales = 1; 

	if($guardar_respaldo)
		{
			$wb = new Spreadsheet_Excel_Writer(Conf::ServerDir().'/respaldos/ResumenCobros'.date('ymdHis').'.xls');
			$wb->setVersion(8);
		}
	else
		{
			$wb = new Spreadsheet_Excel_Writer();
			$wb->setVersion(8);
			// No se hace $wb->send() todavía por si acaso no hay horas en el cobro.
		}
	$wb->setCustomColor(35, 220, 255, 220);
	$wb->setCustomColor(36, 255, 255, 220);
	$wb->setCustomColor(37, 255, 255, 0);
		
		
		//--------------- Definamos los formatos generales, los que quedan constante por todo el documento -------------
		
		$formato_encabezado =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'middle',
												'Align' => 'left',
												'Bold' => 1,
												'Color' => 'black'));
		$formato_encabezado_center =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'middle',
												'Align' => 'center',
												'Bold' => 1,
												'Color' => 'black'));
		$formato_tiempo =& $wb->addFormat(array('Size' => 7,
												'VAlign' => 'middle',
												'Color' => 'black',
												'NumFormat' =>'[h]:mm'));
		$formato_tiempo2 =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'top',
												'Color' => 'black',
												'NumFormat' =>'[h]:mm'));
		$formato_tiempo_total =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'top',
												'Bold' => '1',
												'Color' => 'black',
												'NumFormat' =>'[h]:mm'));
		$formato_tiempo_total_tabla =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'top',
												'Bold' => '1',
												'Top' => '1',
												'Color' => 'black',
												'NumFormat' =>'[h]:mm'));
		$formato_total =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Bold' => 1,
												'Top' => 1,
												'Color' => 'black'));
		$formato_resumen_text =& $wb->addFormat(array('Size' => 8,
												'Valign' => 'top',
												'Bold' => '1',
												'Align' => 'left',
												'Color' => 'black',
												'TextWrap' => 1));
		$letra_chica = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Align' => 'left',
			'Italic' => 1
		));
		$letra_chica_derecha = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Align' => 'right',
			'Italic' => 1
		));
		$letra_chica_bold = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Align' => 'left',
			'Bold' => 1,
			'Italic' => 1
		));
		$letra_chica_bold_derecha = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Align' => 'right',
			'Bold' => 1,
			'Italic' => 1
		));
		$letra_chica_underline = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Align' => 'left',
			'Bold' => 1,
			'Italic' => 1,
			'Underline' => 1
		));
		$letra_chica_bottomgrid = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Bottom' => '2',
			'Align' => 'left',
			'Italic' => 1
		));
		$letra_chica_derecha_bottomgrid = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Bottom' => '2',
			'Align' => 'right',
			'Italic' => 1
		));
		$letra_encabezado_lista = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Align' => 'left',
			'FgColor' => '55',
			'Bold' => 1
		));
		$letra_datos_lista = &$wb->addFormat(array(
			'Size' => 8,
			'Valign' => 'top',
			'Align' => 'left',
			'TextWrap' => 1
		));
		//Retorna el timestamp excel de la fecha
		function fecha_valor($fecha)
		{
			$fecha = explode('-',$fecha);
			if(sizeof($fecha)!=3)
				return 0;
			// number of seconds in a day
			$seconds_in_a_day = 86400;
			// Unix timestamp to Excel date difference in seconds
			$ut_to_ed_diff = $seconds_in_a_day * 25569;

			$time = mktime(0,0,0,$fecha[1],$fecha[2],$fecha[0]);
			$time_max = mktime(0,0,0,$fecha[1],$fecha[2],'2037');
	
			if($fecha[0] != '0000')
			{
				if(floatval($fecha[0]) <= 2040)
					return floor(($time + $ut_to_ed_diff) / $seconds_in_a_day);
				else
					return floor(($time_max + $ut_to_ed_diff) / $seconds_in_a_day);
			}
			return 0;
		}
		//Imprime la Fecha en formato timestap excel
		function fecha_excel($worksheet, $fila, $col, $fecha, $formato, $default='00-00-00')
		{
			$valor = fecha_valor($fecha);
			if(!$valor)
				$valor = fecha_valor($default);

			$worksheet->writeNumber($fila, $col, $valor, $formato);
		}

					
										  
		// Definimos las columnas, mantenerlas así permite agregar nuevas columnas sin tener que rehacer todo.
	/*
		IMPORTANTE:
		Se asume que las columnas $col_id_trabajo, $col_fecha y $col_abogado son las primeras tres y que
		$col_tarifa_hh, $col_valor_trabajo y $col_id_abogado son las últimas tres (aunque $col_id_abogado es
		una columan oculta), en ese orden.
		Estos valores se usan para definir dónde se escribe el encabezado, resumen y otros, conviene no modificarlas.
		Se puede modificar el orden de las otras columnas.
	*/
	$col = 0;
	$col_id_trabajo = $col++;
	$col_fecha = $col++;
	$col_abogado = $col++;
	$col_descripcion = $col++;
	$col_tarificable_hh = $col++;
	//if(($lista->Get(0)->fields['forma_cobro']=='Retainer' || $lista->Get(0)->fields['forma_cobro']=='PROPORCIONAL')&&!$borradores)
	//	$col_duracion_retainer = $col++;
	//if($opc_ver_cobrable)
	$col_cobrable = $col++;
	$col_tarifa_hh = $col++;
	$col_valor_trabajo = $col++;
	$col_id_abogado = $col++;
	unset($col);

	// Valores para usar en las fórmulas de la hoja
	$col_formula_descripcion = Utiles::NumToColumnaExcel($col_descripcion);
	$col_formula_duracion_cobrable = Utiles::NumToColumnaExcel($col_tarificable_hh);
	$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
	$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
	$col_formula_tarificable_hh = Utiles::NumToColumnaExcel($col_tarificable_hh);
	//if($opc_ver_cobrable)
		$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
	$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);
	$col_formula_abogado = Utiles::NumToColumnaExcel($col_abogado);
	if($col_duracion_retainer)
		$col_formula_duracion_retainer = Utiles::NumToColumnaExcel($col_duracion_retainer);
		
	
	
	$col_formula_tarificable_hh_detalle = Utiles::NumToColumnaExcel($col_cobrable);

	// Esta variable se usa para que cada página tenga un nombre único.
	$numero_pagina = 0;

		
	// Buscar todos los borradores o cargar de nuevo el cobro especifico que hay que imprimir
	$query = "SELECT DISTINCT cobro.id_cobro 
							FROM cobro 
							JOIN contrato ON cobro.id_contrato = contrato.id_contrato  
							LEFT JOIN asunto ON asunto.id_contrato = contrato.id_contrato 
							LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente 
						 WHERE $where_cobro AND cobro.estado ".($borradores?' IN (\'CREADO\',\'EN REVISION\')':'=\''.$cobro->fields['estado'].'\'')."
						 ORDER BY cliente.glosa_cliente,cobro.codigo_cliente";
	$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	
	while( list($id_cobro) = mysql_fetch_array($resp) )
		{
			// ---------------- Cargar los datos necesarios dentro del cobro -----------------
			$cobro = new Cobro($sesion);
			$cobro->Load($id_cobro);
			$cobro->LoadAsuntos();
			$cobro->GuardarCobro();
			
			$x_resultados = UtilesApp::ProcesaCobroIdMoneda($sesion, $cobro->fields['id_cobro'],array(),0,true);
			
			// Si es que el cobro es RETAINER o PROPORCIONAL modifica las columnas del excel 
			if(($cobro->fields['forma_cobro']=='RETAINER' || $cobro->fields['forma_cobro']=='PROPORCIONAL' || $cobro->fields['forma_cobro']=='FLAT FEE'))
				{
					$col_duracion_retainer = $col_tarificable_hh+1;
					//if($opc_ver_cobrable)
					$col_cobrable++;
					$col_tarifa_hh++;
					$col_valor_trabajo++;
					$col_id_abogado++;
					
					$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
					$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
					//if($opc_ver_cobrable)
					$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
					$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);
					if($col_duracion_retainer)
						$col_formula_duracion_retainer = Utiles::NumToColumnaExcel($col_duracion_retainer);
				}
			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			$idioma->Load($cobro->fields['codigo_idioma']);
			$ff = str_replace('%d','DD',$idioma->fields['formato_fecha']);
			$ff = str_replace('%m','MM',$ff);
			$ff = str_replace('%y','YY',$ff);
			$ff = str_replace('%Y','YY',$ff);
			$formato_fecha =& $wb->addFormat(array('Size' => 8,
									'Valign' => 'middle',
									'Color' => 'black'));
			$formato_fecha->setNumFormat($ff);

			
			// Estas variables son necesario para poder decidir si se imprima una tabla o no, 
			// generalmente si no tiene data no se escribe 
			$query_cont_trabajos_cobro = "SELECT COUNT(*) FROM trabajo WHERE id_cobro=".$cobro->fields['id_cobro'];
			$resp_cont_trabajos_cobro = mysql_query($query_cont_trabajos_cobro,$sesion->dbh) or Utiles::errorSQL($query_cont_trabajos_cobro,__FILE__,__LINE__,$sesion->dbh);
			list($cont_trabajos_cobro) = mysql_fetch_array($resp_cont_trabajos_cobro);
			
			$query_cont_tramites_cobro = "SELECT COUNT(*) FROM tramite WHERE id_cobro=".$cobro->fields['id_cobro'];
			$resp_cont_tramites_cobro = mysql_query($query_cont_tramites_cobro,$sesion->dbh) or Utiles::errorSQL($query_cont_tramites_cobro,__FILE__,__LINE__,$sesion->dbh);
			list($cont_tramites_cobro) = mysql_fetch_array($resp_cont_tramites_cobro);
			
			$query_cont_gastos_cobro = "SELECT COUNT(*) FROM cta_corriente WHERE id_cobro=".$cobro->fields['id_cobro'];
			$resp_cont_gastos_cobro = mysql_query($query_cont_gastos_cobro,$sesion->dbh) or Utiles::errorSQL($query_cont_gastos_cobro,__FILE__,__LINE__,$sesion->dbh);
			list($cont_gastos_cobro) = mysql_fetch_array($resp_cont_gastos_cobro);
			
			
			$cobro_moneda = new CobroMoneda($sesion);
			$cobro_moneda->Load($cobro->fields['id_cobro']);
			
			$contrato = new Contrato($sesion);
			$contrato->Load($cobro->fields['id_contrato']);
			
			$cliente = new Cliente($sesion);
			$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);
			
			// ----------------- Define formatos specificos dentro del cobro ------------------
			$formato_moneda_gastos =& $wb->addFormat(array('Size' => 7,
															'VAlign' => 'top',
															'Align' => 'right',
															'Color' => 'black',
															'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formato_moneda_gastos_total =& $wb->addFormat(array('Size' => 10,
															'VAlign' => 'top',
															'Align' => 'right',
															'Bold' => 1,
															'Top' => 1,
															'Color' => 'black',
															'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			
			$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['id_moneda'], 'simbolo', 'prm_moneda', 'id_moneda');
			$cifras_decimales = Utiles::glosa($sesion, $cobro->fields['id_moneda'], 'cifras_decimales', 'prm_moneda', 'id_moneda');
			if($cifras_decimales)
			{
				$decimales = '.';
				while($cifras_decimales-- > 0)
					$decimales .= '0';
			}
			else
				$decimales = '';
			$formato_moneda =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Align' => 'right',
												'Color' => 'black',
												'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formato_moneda2 =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'middle',
												'Align' => 'right',
												'Color' => 'black',
												'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formato_moneda_total =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'middle',
												'Align' => 'right',
												'Bold' => '1',
												'Color' => 'black',
												'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formato_moneda_total_tabla =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'middle',
												'Align' => 'right',
												'Top' => '1',
												'Bold' => '1',
												'Color' => 'black',
												'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formato_monto =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'middle',
												'Align' => 'right',
												'Color' => 'black',
												'NumFormat' => "#,###,0$decimales")); 
			$formato_total =& $wb->addFormat(array('Size' => 8,
												'VAlign' => 'middle',
												'Align' => 'right',
												'Bold' => '1',
												'Color' => 'black',
												'NumFormat' => "#,###,0$decimales")); 
			//Hoja Liquidación 
			$nombre_pagina = ++$numero_pagina.' ';
			$ws = &$wb->addWorksheet("Liquidación");
			$ws->setPaper(1);
			$ws->hideScreenGridlines(1);
			$ws->setColumn( $col_id_trabajo, $col_id_trabajo, 15 );
			$ws->setColumn( $col_fecha, $col_fecha, 15 );
			$ws->setColumn( $col_abogado, $col_abogado, 15 );
			$ws->setColumn( $col_descripcion, $col_descripcion, 15 );
			$ws->setColumn( $col_tarificable_hh, $col_tarificable_hh, 15 );
			$filas = 9;

			// Agregar la imagen del logo
			if (method_exists('Conf', 'LogoExcel'))
			{
				$ws->insertBitmap(0, 1, Conf::LogoExcel(), 40, 0, 1, 1.2);
			}
		 	
			//Glosa Señores
			$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'senores', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $letra_chica);
			$filas += 1;

			//Glosa de la razón social en el contrato
			$ws->write($filas, $col_id_trabajo, strtoupper($contrato->fields['factura_razon_social']), $letra_chica);
			$filas += 2;

			//Dirección en el contrato
			$ws->write($filas, $col_id_trabajo, strtoupper($contrato->fields['factura_direccion']), $letra_chica);
			$filas += 4;

			//Lista de asuntos del cobro
			$cobro->LoadAsuntos();
			$ws->write($filas, $col_id_trabajo, "Ref. " . implode(", ", $cobro->asuntos), $letra_chica);
			$filas += 1;
			
			$ws->mergeCells($filas, 1, $filas, 2);
			$ws->write($filas, $col_id_trabajo, __('Fecha:').' '.date("d/m/Y"), $letra_chica_bottomgrid);
			$ws->write($filas, 1, '', $letra_chica_bottomgrid);
			$ws->write($filas, 2, '', $letra_chica_bottomgrid);
			$ws->write($filas, 3, "Liquidación N°: " . $cobro->fields['id_cobro'], $letra_chica_derecha_bottomgrid);
			$ws->mergeCells($filas, 3, $filas, 4);
			$ws->write($filas, 4, '', $letra_chica_derecha_bottomgrid);
			$filas += 1;

			$ws->write($filas, $col_id_trabajo, "Descripción del Asunto", $letra_chica_underline);
			$filas += 1;

			$glosa_asuntos = array();
			$asunto = new Asunto($sesion);
			for($k=0;$k<count($cobro->asuntos);$k++)
			{
				$asunto->LoadByCodigo($cobro->asuntos[$k]);
				$glosa_asuntos[] = $asunto->fields['glosa_asunto'];

			}
			$ws->write($filas, $col_id_trabajo, implode(", ", $glosa_asuntos), $letra_chica);
			$filas += 6;
			
			$ws->write($filas, $col_id_trabajo, "Duración de los servicios prestados", $letra_chica_underline);
			$filas += 1;
			
			if( $cobro->fields['fecha_ini'] = '0000-00-00' )
				$fecha_ini = $cobro->FechaPrimerTrabajo();
			else
				$fecha_ini = $cobro->fields['fecha_ini'];

			$ws->write($filas, $col_id_trabajo, Utiles::sql2fecha($fecha_ini,$idioma->fields['formato_fecha']) . " - " . Utiles::sql2fecha($cobro->fields['fecha_fin'],$idioma->fields['formato_fecha']), $formato_fecha);
			$filas += 2;

			$moneda = new Moneda($sesion);
			$moneda->Load($cobro->fields['opc_moneda_total']);
			$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'honorarios', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $letra_chica_underline);
			$ws->write($filas, 3, $moneda->fields['simbolo'], $letra_chica_derecha);
			$ws->writeNumber($filas, 4, $x_resultados['monto_subtotal'][$cobro->fields['opc_moneda_total']], $formato_monto);
			$fila_honorario = $filas+1;
			$filas += 3;

			$ws->write($filas++, $col_id_trabajo, Utiles::GlosaMult($sesion, 'gastos', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $letra_chica_underline);
			$ws->write($filas, $col_id_trabajo, __('Registros Públicos'), $letra_chica);
			$ws->write($filas, 3, $moneda->fields['simbolo'], $letra_chica_derecha);
			$ws->writeNumber($filas, 4, $x_resultados['subtotal_gastos'][$cobro->fields['opc_moneda_total']], $formato_monto);
			$fila_gasto = $filas+1;
			$filas += 3;

			$col_formula_pago = Utiles::NumToColumnaExcel(4);
			$ws->writeFormula($filas, 4, "=SUM(" . $col_formula_pago . $fila_honorario . ";" . $col_formula_pago . $fila_gasto . ")", $formato_total);
			$ws->write($filas, $col_id_trabajo, "Total", $letra_chica_bold);
			$ws->write($filas, 3, $moneda->fields['simbolo'], $letra_chica_bold_derecha);
			$filas += 1;

			$iva = $total * 0.18;
			$ws->write($filas, $col_id_trabajo, "IGV  18%", $letra_chica_bold);
			$ws->write($filas, 3, $moneda->fields['simbolo'], $letra_chica_bold_derecha);
			$ws->writeNumber($filas, 4, $x_resultados['monto_iva'][$cobro->fields['opc_moneda_total']], $formato_total);
			$filas += 1;
			
			$ws->write($filas, $col_id_trabajo, "Total", $letra_chica_bold);
			$ws->write($filas, 3, $moneda->fields['simbolo'], $letra_chica_bold_derecha);
			$ws->writeNumber($filas, 4, $x_resultados['monto_total_cobro'][$cobro->fields['opc_moneda_total']], $formato_total);
			$filas += 1;
			
		for($i=0;$i<=$filas;$i++)
			$ws->setRow($i,'11.25');

		if( $cobro->fields['opc_ver_profesional'] )
		{
			//Hoja Resumen
			$nombre_pagina = ++$numero_pagina . ' ';
			$ws = &$wb->addWorksheet("Resumen");
			$ws->setPaper(2);

			$filas = 0;
			$col = 0;
			$columna_inicial = 0;
			$columna_abogado = $col++;
			$columna_categoria = $col++;
			$columna_hora = $col++;
			if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' ) {
				$columna_hora_tarificada = $col++;
				$col_formula_hora_tarificada = Utiles::NumToColumnaExcel($columna_hora_tarificada);
				$col_formula_hora_importe = Utiles::NumToColumnaExcel($columna_hora_tarificada);
			}
			else {
				$col_formula_hora_importe = Utiles::NumToColumnaExcel($columna_hora);
			}
			$col_formula_hora = Utiles::NumToColumnaExcel($columna_hora);
			$columna_tarifa = $col++;
			$col_formula_tarifa = Utiles::NumToColumnaExcel($columna_tarifa);
			$columna_importe = $col++;
			$col_formula_importe = Utiles::NumToColumnaExcel($columna_importe);

			$fecha_ini_titulo = '';
			if($cobro->fields['fecha_ini'] != '0000-00-00') {
				$fecha_ini_titulo = __('del ').$cobro->fields['fecha_ini'];
			}
			$ws->mergeCells($filas, 0, $filas, 4);
			$ws->write($filas, $columna_categoria, __('DETALLE DE SERVICIOS PRESTADOS'), $formato_encabezado_center);
			$ws->write($filas, $columna_abogado, '', $formato_encabezado);
			$ws->write($filas, $columna_hora, '', $formato_encabezado);
			$ws->write($filas, $columna_tarifa, '', $formato_encabezado);
			$ws->write($filas, $columna_importe, '', $formato_encabezado);
			$filas += 1;
			$ws->mergeCells($filas, 0, $filas, 4);
			$ws->write($filas, $columna_categoria, __('Período').$fecha_ini_titulo.__(' al ').$cobro->fields['fecha_fin'], $formato_encabezado);
			$ws->write($filas, $columna_abogado, '', $formato_encabezado);
			$ws->write($filas, $columna_hora, '', $formato_encabezado);
			$ws->write($filas, $columna_tarifa, '', $formato_encabezado);
			$ws->write($filas, $columna_importe, '', $formato_encabezado);
			$filas += 2;
			$ws->write($filas, $columna_inicial, __('Cliente: ').$cliente->fields['glosa_cliente'], $formato_encabezado);
			$filas += 3;

			$ws->write($filas, $columna_abogado, __('Abogado'), $letra_encabezado_lista);
			$ws->write($filas, $columna_categoria, __('Categoría'), $letra_encabezado_lista);
			$ws->write($filas, $columna_hora, __('Horas'), $letra_encabezado_lista);
			if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' ) {
				$ws->write($filas, $columna_hora_tarificada, __('Horas Tarificadas'), $letra_encabezado_lista);
			}
			$ws->write($filas, $columna_tarifa, __('Tarifa'), $letra_encabezado_lista);
			$ws->write($filas, $columna_importe, __('Importe '.$cobro_moneda->moneda[$cobro->fields['id_moneda']]['simbolo']), $letra_encabezado_lista);
			$filas += 1;
			$ws->freezePanes(array($filas, 0));
			
			$where_trabajos = " 1 ";
			if(!$opc_ver_cobrable)
				$where_trabajos .= " AND trabajo.visible = 1 ";
			if(!$opc_ver_horas_trabajadas)
				$where_trabajos .= " AND trabajo.duracion_cobrada != '00:00:00' ";

			$query_cont_trabajos = "SELECT COUNT(*) FROM trabajo WHERE $where_trabajos AND id_cobro='".$cobro->fields['id_cobro']."' AND id_tramite = 0";
			$resp_cont_trabajos = mysql_query($query_cont_trabajos,$sesion->dbh) or Utiles::errorSQL($query_cont_trabajos,__FILE__,__LINE__,$sesion->dbh);
			list($cont_trabajos) = mysql_fetch_array($resp_cont_trabajos);

			if($cont_trabajos>0)
			{
				// Buscar todos los trabajos de este asunto/cobro
				$query_trabajos = "SELECT DISTINCT SQL_CALC_FOUND_ROWS 
														trabajo.id_cobro,
														trabajo.codigo_asunto,
														trabajo.id_usuario,
														trabajo.cobrable,
														prm_moneda.simbolo AS simbolo,
														asunto.codigo_cliente AS codigo_cliente,
														asunto.id_asunto AS id,
														trabajo.fecha_cobro AS fecha_cobro_orden,
														IF( trabajo.cobrable = 1, 'SI', 'NO') AS glosa_cobrable,
														trabajo.visible,
														CONCAT(usuario.apellido1,' ',usuario.apellido2,', ',usuario.nombre) AS usr_nombre,
														usuario.username as siglas,
														usuario.id_categoria_usuario,
														SEC_TO_TIME( SUM( TIME_TO_SEC(duracion) ) ) AS duracion,
														SEC_TO_TIME( SUM( TIME_TO_SEC(duracion_cobrada) ) ) AS duracion_cobrada,
														SUM(TIME_TO_SEC(duracion_cobrada)) AS duracion_cobrada_decimal,
														SEC_TO_TIME( SUM( TIME_TO_SEC(duracion_retainer) ) ) AS duracion_retainer,
														SEC_TO_TIME( SUM( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) ) as duracion_tarificada,
														SUM(TIME_TO_SEC(duracion)/3600) AS duracion_horas,
														IF( trabajo.cobrable = 1, trabajo.tarifa_hh, '0') AS tarifa_hh,
														DATE_FORMAT(trabajo.fecha_cobro, '%e-%c-%x') AS fecha_cobro,
														asunto.codigo_asunto_secundario as codigo_asunto_secundario 
													FROM trabajo
														JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
														LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
														JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
														LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
														LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario
														LEFT JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda
													WHERE $where_trabajos AND trabajo.id_tramite=0 AND trabajo.id_cobro=".$cobro->fields['id_cobro'] ."
														GROUP BY usuario.id_usuario";
				
				$orden = "usuario.id_categoria_usuario, trabajo.fecha, trabajo.descripcion";
				$b1 = new Buscador($sesion, $query_trabajos, "Trabajo", $desde, '', $orden);
				$lista_trabajos = $b1->lista;
				$fila_inicial = $filas;
				$id_categoria_actual = 0;
				for($i=0;$i<$lista_trabajos->num;$i++)
				{
					$trabajo = $lista_trabajos->Get($i);
					
					if( UtilesApp::GetConf($sesion,'GuardarTarifaAlIngresoDeHora') ) {
						$query_tarifa = "SELECT 
																SUM( ( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) * tarifa_hh ) / SUM( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) as tarifa 
															 FROM trabajo 
															WHERE id_cobro = '".$trabajo->fields['id_cobro']."' 
																AND id_usuario = '".$trabajo->fields['id_usuario']."' 
																AND cobrable = 1";
						$resp_tarifa = mysql_query($query_tarifa,$sesion->dbh) or Utiles::errorSQL($query_tarifa,__FILE__,__LINE__,$sesion->dbh);
						list($trabajo->fields['tarifa_hh']) = mysql_fetch_array($resp_tarifa);
					}
					
					if( $cobro->fields['opc_ver_profesional_iniciales'] == 1 )
						$nombre = $trabajo->fields['siglas'];
					else
						$nombre = $trabajo->fields['usr_nombre'];
					
					$ws->write($filas, $columna_abogado, $nombre, $letra_datos_lista);
					$categoria_usuario = '';
					if($trabajo->fields['id_categoria_usuario'] && $cobro->fields['opc_ver_profesional_categoria'] == 1) {
						$query_categoria_usuario = "SELECT glosa_categoria FROM prm_categoria_usuario WHERE id_categoria_usuario = ".$trabajo->fields['id_categoria_usuario'];
						$resp_query_categoria_usuario = mysql_query($query_categoria_usuario,$sesion->dbh) or Utiles::errorSQL($query_categoria_usuario,__FILE__,__LINE__,$sesion->dbh);
						list($categoria_usuario) = mysql_fetch_array($resp_query_categoria_usuario);
					}
					$ws->write($filas, $columna_categoria, $categoria_usuario, $letra_datos_lista);
					
					$ws->write($filas, $columna_tarifa, $trabajo->fields['tarifa_hh'], $formato_moneda2);

					$duracion = $trabajo->fields['duracion_cobrada'];
					list($h, $m) = split(':', $duracion);
					$duracion = $h/24 + $m/(24*60);
					$ws->writeNumber($filas, $columna_hora,$duracion, $formato_tiempo2);
					
					if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' ) {
						$duracion_tarificada = $trabajo->fields['duracion_tarificada'];
						list($ht, $mt) = split(':', $duracion_tarificada);
						$duracion_tarificada = $ht/24 + $m/(24*60);
						$ws->writeNumber($filas, $columna_hora_tarificada, $duracion_tarificada, $formato_tiempo2);
					}

					$ws->writeFormula($filas, $columna_importe, "=24*$col_formula_tarifa".($filas+1)."*$col_formula_hora_importe".($filas+1), $formato_moneda2);
					
					$filas += 1;
				}
				$filas += 1;
				$ws->writeFormula($filas, $columna_hora, "=SUM($col_formula_hora".($fila_inicial+1).":$col_formula_hora".($filas).")", $formato_tiempo_total_tabla);
				if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' )
					$ws->writeFormula($filas, $columna_hora_tarificada, "=SUM($col_formula_hora_tarificada".($fila_inicial+1).":$col_formula_hora_tarificada".($filas).")", $formato_tiempo_total_tabla);
				$ws->writeFormula($filas, $columna_importe, "=SUM($col_formula_importe".($fila_inicial+1).":$col_formula_importe".($filas).")", $formato_moneda_total_tabla);
			}
			//seteamos el ancho y columnas ocultas segun corresponda
			
			$ws->setColumn($columna_abogado, $columna_abogado, 32);
			$ws->setColumn($columna_hora, $columna_hora, 8);
			if(!$cobro->fields['opc_ver_profesional_categoria'] == 1) {
				$ws->setColumn($columna_categoria, $columna_categoria, 0,0,1);
			}
			else{
				$ws->setColumn($columna_categoria, $columna_categoria, 14);
			}
			if(!$cobro->fields['opc_ver_profesional_tarifa'] == 1) {
				$ws->setColumn($columna_tarifa, $columna_tarifa, 0,0,1);
			}
			else {
				$ws->setColumn($columna_tarifa, $columna_tarifa, 9);
			}
			if(!$cobro->fields['opc_ver_profesional_importe'] == 1) {
				$ws->setColumn($columna_importe, $columna_importe, 0,0,1);
			}
			else {
				$ws->setColumn($columna_importe, $columna_importe, 12);
			}
		}

	 		//Hoja Detalle por profesional
	 		if ($cobro->fields["opc_ver_detalles_por_hora"] == 1)
		 	{
				$nombre_pagina = ++$numero_pagina . ' ';
		 		$ws = &$wb->addWorksheet("Detalle por profesional");
		 		$ws->setPaper(2);
	
				$filas = 0;
				$col = 0;
				$columna_sigla = $col++;
				$columna_abogado = $col++;
				$columna_inicial = empty($opc_ver_detalles_por_hora_iniciales) ? $columna_abogado : $columna_sigla;
				$columna_categoria = $col++;
				$columna_descripcion = $col++;
				$columna_hora = $col++;
				$col_formula_hora = Utiles::NumToColumnaExcel($columna_hora);
				$col_formula_hora_importe = $col_formula_hora;
				if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' ) {
					$columna_hora_tarificada = $col++;
					$col_formula_hora_tarificada = Utiles::NumToColumnaExcel($columna_hora_tarificada);
					$col_formula_hora_importe = $col_formula_hora_tarificada;
				}
				$columna_tarifa = $col++;
				$col_formula_tarifa = Utiles::NumToColumnaExcel($columna_tarifa);
				$columna_importe = $col++;
				$col_formula_importe = Utiles::NumToColumnaExcel($columna_importe);
				
				$ws->mergeCells($filas, 0, $filas, 6);
				$ws->write($filas, $columna_categoria, __('DETALLE DE SERVICIOS PRESTADOS'), $formato_encabezado_center);
				$ws->write($filas, $columna_sigla, '', $formato_encabezado);
				$ws->write($filas, $columna_abogado, '', $formato_encabezado);
				$ws->write($filas, $columna_descripcion, '', $formato_encabezado);
				$ws->write($filas, $columna_hora, '', $formato_encabezado);
				$ws->write($filas, $columna_tarifa, '', $formato_encabezado);
				$ws->write($filas, $columna_importe, '', $formato_encabezado);
				$filas += 1;
				$ws->mergeCells($filas, 0, $filas, 6);
				$ws->write($filas, $columna_categoria, __('Período').$fecha_ini_titulo.__(' al ').$cobro->fields['fecha_fin'], $formato_encabezado_center);
				$ws->write($filas, $columna_sigla, '', $formato_encabezado);
				$ws->write($filas, $columna_abogado, '', $formato_encabezado);
				$ws->write($filas, $columna_descripcion, '', $formato_encabezado);
				$ws->write($filas, $columna_hora, '', $formato_encabezado);
				$ws->write($filas, $columna_tarifa, '', $formato_encabezado);
				$ws->write($filas, $columna_importe, '', $formato_encabezado);
				$filas += 2;
				$ws->write($filas, $columna_inicial, __('Cliente: ').$cliente->fields['glosa_cliente'], $formato_encabezado);
				$filas += 3;
	
				$ws->write($filas, $columna_sigla, __('Siglas'), $letra_encabezado_lista);
				$ws->write($filas, $columna_abogado, __('Nombre'), $letra_encabezado_lista);
				$ws->write($filas, $columna_categoria, __('Categoría'), $letra_encabezado_lista);
				$ws->write($filas, $columna_descripcion, __('Descripción de Servicio'), $letra_encabezado_lista);
				$ws->write($filas, $columna_hora, __('Horas'), $letra_encabezado_lista);
				if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' ) {
					$ws->write($filas, $columna_hora_tarificada, __('Horas Tarificadas'), $letra_encabezado_lista);
				}
				$ws->write($filas, $columna_tarifa, __('Tarifa'), $letra_encabezado_lista);
				$ws->write($filas, $columna_importe, __('Importe '.$cobro_moneda->moneda[$cobro->fields['id_moneda']]['simbolo']), $letra_encabezado_lista);
				$filas += 1;
				$ws->freezePanes(array($filas,0));
				
				$where_trabajos = " 1 ";
				if(!$opc_ver_cobrable)
					$where_trabajos .= " AND trabajo.visible = 1 ";
				if(!$opc_ver_horas_trabajadas)
					$where_trabajos .= " AND trabajo.duracion_cobrada != '00:00:00' ";
				
				$query_cont_trabajos = "SELECT COUNT(*) FROM trabajo WHERE $where_trabajos AND id_cobro='".$cobro->fields['id_cobro']."' AND id_tramite = 0";
				$resp_cont_trabajos = mysql_query($query_cont_trabajos,$sesion->dbh) or Utiles::errorSQL($query_cont_trabajos,__FILE__,__LINE__,$sesion->dbh);
				list($cont_trabajos) = mysql_fetch_array($resp_cont_trabajos);
				
				if($cont_trabajos>0)
				{
					// Buscar todos los trabajos de este asunto/cobro
					$query_trabajos = "SELECT DISTINCT SQL_CALC_FOUND_ROWS *, 
															trabajo.id_cobro, 
															trabajo.id_trabajo, 
															trabajo.codigo_asunto, 
															trabajo.id_usuario, 
															trabajo.cobrable, 
															prm_moneda.simbolo AS simbolo, 
															asunto.codigo_cliente AS codigo_cliente, 
															asunto.id_asunto AS id, 
															trabajo.fecha_cobro AS fecha_cobro_orden, 
															IF( trabajo.cobrable = 1, 'SI', 'NO') AS glosa_cobrable, 
															trabajo.visible, 
															username AS usr_nombre, 
															DATE_FORMAT(duracion, '%H:%i') AS duracion, 
															DATE_FORMAT(duracion_cobrada, '%H:%i') AS duracion_cobrada, 
															TIME_TO_SEC(duracion_cobrada) AS duracion_cobrada_decimal, 
															DATE_FORMAT(duracion_retainer, '%H:%i') AS duracion_retainer, 
															SEC_TO_TIME( TIME_TO_SEC( duracion_cobrada ) - TIME_TO_SEC( duracion_retainer ) ) AS duracion_tarificada, 
															TIME_TO_SEC(duracion)/3600 AS duracion_horas, 
															IF( trabajo.cobrable = 1, trabajo.tarifa_hh, '0') AS tarifa_hh, 
															DATE_FORMAT(trabajo.fecha_cobro, '%e-%c-%x') AS fecha_cobro, 
															asunto.codigo_asunto_secundario as codigo_asunto_secundario 
														FROM trabajo 
															JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto 
															LEFT JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente 
															JOIN cobro ON trabajo.id_cobro = cobro.id_cobro 
															LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato 
															LEFT JOIN usuario ON trabajo.id_usuario = usuario.id_usuario 
															LEFT JOIN prm_moneda ON cobro.id_moneda = prm_moneda.id_moneda 
														WHERE $where_trabajos 
															AND trabajo.id_tramite=0 
															AND trabajo.id_cobro='".$cobro->fields['id_cobro']."'";
	
					$orden = "usuario.id_categoria_usuario, trabajo.fecha, trabajo.descripcion";
					$b1 = new Buscador($sesion, $query_trabajos, "Trabajo", $desde, '', $orden);
					$lista_trabajos = $b1->lista;
					$fila_inicial = $filas;
					$id_categoria_actual = 0;
					for($i=0;$i<$lista_trabajos->num;$i++)
					{
						$trabajo = $lista_trabajos->Get($i);
						
						if( !$cobro->fields['opc_ver_detalles_por_hora_iniciales'] == 1 )
							$siglas = "";
						else
							$siglas = $trabajo->fields['username'];
							$nombre = $trabajo->fields['nombre'] .' '. $trabajo->fields['apellido1'] .' '. $trabajo->fields['apellido2'];
						$ws->write($filas, $columna_sigla, $siglas, $letra_datos_lista);
						$ws->write($filas, $columna_abogado, $nombre, $letra_datos_lista);
						$categoria_usuario = '';
						if($trabajo->fields['id_categoria_usuario'] && $cobro->fields['opc_ver_detalles_por_hora_categoria']==1) {
							$query_categoria_usuario = "SELECT glosa_categoria FROM prm_categoria_usuario WHERE id_categoria_usuario = ".$trabajo->fields['id_categoria_usuario'];
							$resp_query_categoria_usuario = mysql_query($query_categoria_usuario,$sesion->dbh) or Utiles::errorSQL($query_categoria_usuario,__FILE__,__LINE__,$sesion->dbh);
							list($categoria_usuario) = mysql_fetch_array($resp_query_categoria_usuario);
						}
						$ws->write($filas, $columna_categoria, $categoria_usuario, $letra_datos_lista);
						$ws->write($filas, $columna_descripcion, $trabajo->fields['descripcion'], $letra_datos_lista);
						$ws->write($filas, $columna_tarifa, $trabajo->fields['tarifa_hh'], $formato_moneda2);
	
						$duracion = $trabajo->fields['duracion_cobrada'];
						list($h, $m) = split(':', $duracion);
						$duracion = $h/24 + $m/(24*60);
						$ws->writeNumber($filas, $columna_hora,$duracion, $formato_tiempo2);
						
						if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' ) {
							$duracion_tarificada = $trabajo->fields['duracion_tarificada'];
							list($ht, $mt) = split(':', $duracion_tarificada);
							$duracion_tarificada = $ht/24 + $mt/(24*60);
							$ws->writeNumber($filas, $columna_hora_tarificada, $duracion_tarificada, $formato_tiempo2);
						}
						
						$ws->writeFormula($filas, $columna_importe, "=24*$col_formula_tarifa".($filas+1)."*$col_formula_hora_importe".($filas+1), $formato_moneda2);
						
						$filas += 1;
					}
					$filas += 1;
					$ws->writeFormula($filas, $columna_hora, "=SUM($col_formula_hora".($fila_inicial+1).":$col_formula_hora".($filas).")", $formato_tiempo_total_tabla);
					if( $cobro->fields['forma_cobro'] == 'RETAINER' || $cobro->fields['forma_cobro'] == 'PROPORCIONAL' )
						$ws->writeFormula($filas, $columna_hora_tarificada, "=SUM($col_formula_hora_tarificada".($fila_inicial+1).":$col_formula_hora_tarificada".($filas).")", $formato_tiempo_total_tabla);
					$ws->writeFormula($filas, $columna_importe, "=SUM($col_formula_importe".($fila_inicial+1).":$col_formula_importe".($filas).")", $formato_moneda_total_tabla);
				}
				
				
				$ws->setColumn($columna_abogado, $columna_abogado, 23);
				if( !$cobro->fields['opc_ver_detalles_por_hora_categoria'] == 1 ) {
					$ws->setColumn($columna_categoria, $columna_categoria,0,0,1);
				}
				else
					$ws->setColumn($columna_categoria, $columna_categoria, 9);
				$ws->setColumn($columna_descripcion, $columna_descripcion, 73);
				$ws->setColumn($columna_hora, $columna_hora, 7);
				if(!$cobro->fields['opc_ver_detalles_por_hora_iniciales'] == 1) {
					$ws->setColumn($columna_sigla, $columna_sigla,0,0,1);
				}
				else 
					$ws->setColumn($columna_sigla, $columna_sigla, 11);
				if(!$cobro->fields['opc_ver_detalles_por_hora_tarifa'] == 1) {
					$ws->setColumn($columna_tarifa, $columna_tarifa, 0,0,1);
				}
				else {
					$ws->setColumn($columna_tarifa, $columna_tarifa, 10);
				}
				if(!$cobro->fields['opc_ver_detalles_por_hora_importe'] == 1) {
					$ws->setColumn($columna_importe, $columna_importe, 0,0,1);
				}
				else {
					$ws->setColumn($columna_importe, $columna_importe, 12);
				}
			}
			
			 // HOJA GASTOS
			if( $cont_gastos_cobro > 0 && $opc_ver_gastos )
			{
				$nombre_pagina = ++$numero_pagina . ' ';
		 		$ws = &$wb->addWorksheet("Detalle de gastos");
		 		$ws->setPaper(2);
	
				$columna_inicial = 2;
				$filas = 0;
				$col = 0;
	
				$columna_gastos_titulo = $col++;
				$columna_gastos_fecha = $col++;
				$columna_gastos_descripcion = $col++;
				$columna_gastos_montos = $col++;
				$col_formula_gastos_montos = Utiles::NumToColumnaExcel($columna_gastos_montos);
	
				$fecha_ini_titulo = '';
				if($cobro->fields['fecha_ini'] != '0000-00-00') {
					$fecha_ini_titulo = __('del ').$cobro->fields['fecha_ini'];
				}
				
				$ws->mergeCells($filas, 0, $filas, 3);
				$ws->write($filas, $columna_gastos_titulo, '', $formato_encabezado);
				$ws->write($filas, $columna_gastos_fecha, '', $formato_encabezado);
				$ws->write($filas, $columna_gastos_descripcion, __('DETALLE DE GASTOS'), $formato_encabezado_center);
				$ws->write($filas, $columna_gastos_montos, '', $formato_encabezado);
				$filas += 1;
				$ws->mergeCells($filas, 0, $filas, 3);
				$ws->write($filas, $columna_gastos_titulo, '', $formato_encabezado);
				$ws->write($filas, $columna_gastos_fecha, '', $formato_encabezado);
				$ws->write($filas, $columna_gastos_descripcion, __('Período').$fecha_ini_titulo.__(' al ').$cobro->fields['fecha_fin'], $formato_encabezado_center);
				$ws->write($filas, $columna_gastos_montos, '', $formato_encabezado);
				$filas += 2;
				$ws->write($filas, $columna_inicial, __('Cliente: ').$cliente->fields['glosa_cliente'], $formato_encabezado);
				$filas += 3;
	
				
					// Encabezado de la tabla de gastos
					$filas++;
					$ws->write($filas, $columna_gastos_titulo, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $letra_encabezado_lista);
					$ws->write($filas, $columna_gastos_fecha, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $letra_encabezado_lista);
					$ws->write($filas, $columna_gastos_descripcion, __('Concepto'), $letra_encabezado_lista);
					$ws->write($filas, $columna_gastos_montos, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $letra_encabezado_lista);
					++$filas;
					$ws->freezePanes(array($filas,0));
					$fila_inicio_gastos = $filas + 1;
	
					// Contenido de gastos
					$query = "SELECT SQL_CALC_FOUND_ROWS
									ingreso,
									egreso,
									monto_cobrable,
									fecha,
									id_moneda,
									descripcion
								FROM cta_corriente
								WHERE id_cobro='".$cobro->fields['id_cobro']."'
								ORDER BY fecha ASC";
	
					$lista_gastos = new ListaGastos($sesion, '', $query);
					for($i=0;$i<$lista_gastos->num;$i++)
					{
						$gasto = $lista_gastos->Get($i);
						fecha_excel($ws,$filas,$columna_gastos_fecha,$gasto->fields['fecha'],$formato_fecha);
						$ws->write($filas, $columna_gastos_descripcion, $gasto->fields['descripcion'], $letra_datos_lista);
						if($gasto->fields['egreso'])
							$ws->writeNumber($filas, $columna_gastos_montos, $gasto->fields['monto_cobrable']*($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $formato_moneda2);
						else
							$ws->writeNumber($filas, $columna_gastos_montos, -$gasto->fields['monto_cobrable']*($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $formato_moneda2);
						++$filas;
					}
					++$filas;
					// Total de gastos
					$ws->write($filas, $columna_gastos_descripcion, "Total", $formato_resumen_text);
					$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion+1);
					$ws->writeFormula($filas, $columna_gastos_montos, "=SUM($col_formula_gastos_montos$fila_inicio_gastos:$col_formula_gastos_montos$filas)", $formato_moneda_gastos_total);
				
				$ws->setColumn($columna_gastos_titulo, $columna_gastos_titulo, 0,0,1);
				$ws->setColumn($columna_gastos_fecha, $columna_gastos_fecha, 0,0,1);
				$ws->setColumn($columna_categoria, $columna_categoria, 74);
				$ws->setColumn($columna_gastos_montos, $columna_descripcion, 11);
			}
		}
		// fin bucle cobros	
	if(isset($ws))
	{
		// Se manda el archivo aquí para que no hayan errores de headers al no haber resultados.
		if(!$guardar_respaldo)
			$wb->send('Resumen de cobros.xls');
	}
$wb->close();
?>
