<?
	require_once 'Spreadsheet/Excel/Writer.php';
	require_once dirname(__FILE__).'/../../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';

	$sesion = new Sesion(array('REP'));
	
	set_time_limit(300);
	
	$pagina = new Pagina($sesion);
	if($xls)
	{
		$filas = 1;
		$moneda_base = Utiles::MonedaBase($sesion);
		#ARMANDO XLS
		$wb = new Spreadsheet_Excel_Writer();
		$wb->send("planilla_morosidad.xls");

		$wb->setCustomColor(35, 220, 255, 220);
		$wb->setCustomColor(36, 255, 255, 220);

		$encabezado =& $wb->addFormat(array('Size' => 12,
									'VAlign' => 'top',
									'Align' => 'left',
									'Bold' => '1',
									'underline' =>1,
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
		$txt_izquierda =& $wb->addFormat(array('Size' => 11,
									'Valign' => 'top',
									'Align' => 'left',
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
		$formato_moneda =& $wb->addFormat(array('Size' => 11,
									'VAlign' => 'top',
									'Align' => 'right',
									'Border' => 1,
									'Color' => 'black',
									'NumFormat' => "#,##0.00"));

		$ws1 =& $wb->addWorksheet(__('Morosidad'));
		$ws1->setInputEncoding('utf-8');
		$ws1->fitToPages(1, 5);
		$ws1->setZoom(75);
		$ws1->hideGridlines();
		$ws1->setLandscape();

		// Definir columnas a usar
		$indice_columnas = 1;
		$col_cliente = $indice_columnas++;
		$col_total_pesos = $indice_columnas++;
		$col_asuntos = $indice_columnas++;
		$col_fecha_emision = $indice_columnas++;
		$col_fecha_envio = $indice_columnas++;
		$col_cobro = $indice_columnas++;
		$col_factura = $indice_columnas++;
		$col_moneda = $indice_columnas++;
		$col_monto = $indice_columnas++;
		$col_monto_honorarios_pesos = $indice_columnas++;
		$col_monto_gastos_pesos = $indice_columnas++;
		unset($indice_columnas);

		// Definir columnas para fórmulas de excel
		$col_formula_total_pesos = Utiles::NumToColumnaExcel($col_total_pesos);
		$col_formula_monto_honorarios_pesos = Utiles::NumToColumnaExcel($col_monto_honorarios_pesos);
		$col_formula_monto_gastos_pesos = Utiles::NumToColumnaExcel($col_monto_gastos_pesos);

		// Definir los anchos de las columnas
		$ws1->setColumn($col_cliente, $col_cliente, 35);
		$ws1->setColumn($col_total_pesos, $col_total_pesos, 17);
		$ws1->setColumn($col_fecha_emision, $col_fecha_emision, 17);
		$ws1->setColumn($col_fecha_envio, $col_fecha_envio, 14);
		$ws1->setColumn($col_cobro, $col_cobro, 14);
		$ws1->setColumn($col_asuntos, $col_asuntos, 30);
		$ws1->setColumn($col_factura, $col_factura, 10);
		$ws1->setColumn($col_moneda, $col_moneda, 14);
		$ws1->setColumn($col_monto, $col_monto, 14);
		$ws1->setColumn($col_monto_honorarios_pesos, $col_monto_honorarios_pesos, 30);
		$ws1->setColumn($col_monto_gastos_pesos, $col_monto_gastos_pesos, 26);

		++$filas;
		$ws1->mergeCells($filas, $col_cliente, $filas, $col_factura);
		$ws1->write($filas, $col_cliente, __('REPORTE MOROSIDAD'), $encabezado);
		$filas += 2;
		$ws1->write($filas, $col_cliente, __('GENERADO EL:'), $txt_opcion);
		$ws1->write($filas, $col_total_pesos, date("d-m-Y H:i:s"), $txt_opcion);

		$where = "1";
		if(is_array($clientes))
		{
			$lista_clientes = join("','", $clientes);
			$where .= " AND cliente.codigo_cliente IN ('$lista_clientes')";
		}
		if(is_array($socios))
		{
			$lista_socios = join("','", $socios);
			$where .= " AND contrato.id_usuario_responsable IN ('$lista_socios')";
		}
		if(is_array($monedas))
		{
			$lista_monedas = join("','", $monedas);
			$where .= " AND cobro.id_moneda IN ('$lista_monedas')";
		}
		
		if($periodo && $fecha_ini)
				$where .= " AND cobro.fecha_emision >=  '".Utiles::fecha2sql($fecha_ini)."' ";
		
		if( ( ( method_exists('Conf','ReporteMorosidadEnviados') && Conf::GetConf($sesion,'ReporteMorosidadEnviado') ) || ( method_exists('Conf','ReporteMorosidadEnviados') && Conf::ReporteMorosidadEnviados() ) ) )
			$where .= " AND cobro.estado = 'ENVIADO AL CLIENTE' ";
		else
			$where .= " AND ( cobro.estado = 'ENVIADO AL CLIENTE' OR cobro.estado = 'EMITIDO' ) ";
		$where_documento = "AND documento.tipo_doc = 'N'";

		//Orden de los datos
		if(!$desglosar_por_encargado&&!$desglosar_por_moneda)
		{
			$orderby = "cliente.glosa_cliente";
			$sin_desglose = true;
		}
		else
		{
			if($desglosar_por_encargado&&!$desglosar_por_moneda)
				$orderby = "nombre, cliente.glosa_cliente";
			if($desglosar_por_moneda&&!$desglosar_por_encargado)
				$orderby = "documento.id_moneda, cliente.glosa_cliente";
			if($desglosar_por_moneda&&$desglosar_por_encargado)
				$orderby = "nombre, documento.id_moneda, cliente.glosa_cliente";
			$sin_desglose = false;
		}
		$orderby .= ", cobro.fecha_enviado_cliente, cobro.id_cobro";

		++$filas;
		$tabla_creada = false;
		$cliente_creado = false;
		$nombre_cliente = "";
		$encargado = "";
		$moneda = "";
		$nueva_tabla = false;
		$cont_filas_cliente = 0;
		$total_cliente = 0;
		$total_moneda = 0;
		$query = "SELECT cobro.fecha_enviado_cliente,
								cobro.fecha_emision,
								cliente.glosa_cliente,
								cobro.documento,
								CONCAT(usuario.nombre, ' ', usuario.apellido1) AS nombre,
								documento.saldo_honorarios,
								documento.saldo_gastos,
								documento.saldo_honorarios*cobro_moneda.tipo_cambio as saldo_honorarios_pesos,
								documento.saldo_gastos*cobro_moneda.tipo_cambio as saldo_gastos_pesos,
								cobro.estado,
								cobro.id_cobro,
								prm_moneda.simbolo,
								prm_moneda.glosa_moneda,
								documento.id_moneda
							FROM cobro
								LEFT JOIN documento ON documento.id_cobro = cobro.id_cobro $where_documento 
								LEFT JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
								LEFT JOIN contrato ON contrato.id_contrato = cobro.id_contrato
								LEFT JOIN usuario ON usuario.id_usuario = contrato.id_usuario_responsable
								LEFT JOIN prm_moneda ON prm_moneda.id_moneda = documento.id_moneda
								LEFT JOIN cobro_moneda ON cobro_moneda.id_cobro = cobro.id_cobro AND cobro_moneda.id_moneda = documento.id_moneda
							WHERE $where 
							ORDER BY $orderby;";
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
		while($cobro = mysql_fetch_array($resp))
		{
			if($sin_desglose&&!$tabla_creada)
			{
				$filas += 3;
				$ws1->write($filas, $col_cliente, __('Cobranza Morosa'), $encabezado);
				$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_gastos_pesos);
				++$filas;
				//se ponen los titulos por tabla
				$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
				$ws1->write($filas, $col_total_pesos, __('Total en '.$moneda_base['glosa_moneda']), $titulo_filas);
				$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
				$ws1->write($filas, $col_fecha_emision, __('Fecha Emision'), $titulo_filas);
				$ws1->write($filas, $col_fecha_envio, __('Fecha Envío'), $titulo_filas);
				$ws1->write($filas, $col_cobro, __('Cobro'), $titulo_filas);
				$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
				$ws1->write($filas, $col_moneda, __('Moneda'), $titulo_filas);
				$ws1->write($filas, $col_monto, __('Monto'), $titulo_filas);
				$ws1->write($filas, $col_monto_honorarios_pesos, __('Monto Honorarios en '.$moneda_base['glosa_moneda']), $titulo_filas);
				$ws1->write($filas, $col_monto_gastos_pesos, __('Monto Gastos en '.$moneda_base['glosa_moneda']), $titulo_filas);
				$fila_inicial_tabla = $filas+1;
				$tabla_creada = true;
			}
			else if($desglosar_por_encargado&&!$desglosar_por_moneda)
			{
				if($encargado != $cobro['nombre'])
				{
					if($tabla_creada)
					{
						$fila_final_tabla = $filas;
						++$filas;
						$ws1->write($filas, $col_cliente, __('Total'), $encabezado);
						$ws1->writeNumber($filas, $col_total_pesos, ($total_pesos+$total_gastos_pesos), $formato_moneda);
						$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $total_pesos, $formato_moneda);
						$ws1->writeNumber($filas, $col_monto_gastos_pesos, $total_gastos_pesos, $formato_moneda);
						$total_pesos = 0;
						$total_gastos_pesos = 0;
					}
					$encargado = $cobro['nombre'];
					$filas += 3;
					$ws1->write($filas, $col_cliente, __('Cobranza Morosa ').$cobro['nombre'], $encabezado);
					$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_gastos_pesos);
					++$filas;
					//se ponen los titulos por tabla
					$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
					$ws1->write($filas, $col_total_pesos, __('Total en Pesos'), $titulo_filas);
					$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
					$ws1->write($filas, $col_fecha_emision, __('Fecha Emision'), $titulo_filas);
					$ws1->write($filas, $col_fecha_envio, __('Fecha Envío'), $titulo_filas);
					$ws1->write($filas, $col_cobro, __('Cobro'), $titulo_filas);
					$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
					$ws1->write($filas, $col_moneda, __('Moneda'), $titulo_filas);
					$ws1->write($filas, $col_monto, __('Monto'), $titulo_filas);
					$ws1->write($filas, $col_monto_honorarios_pesos, __('Monto Honorarios en Pesos'), $titulo_filas);
					$ws1->write($filas, $col_monto_gastos_pesos, __('Monto Gastos en Pesos'), $titulo_filas);
					$fila_inicial_tabla = $filas+1;
					$tabla_creada = true;
					$nueva_tabla = true;
				}
			}
			else if($desglosar_por_moneda&&!$desglosar_por_encargado)
			{
				if($moneda != $cobro['id_moneda'])
				{
					if($tabla_creada)
					{
						$fila_final_tabla = $filas;
						++$filas;
						$ws1->write($filas, $col_cliente, __('Total'), $encabezado);
						$ws1->writeNumber($filas, $col_total_pesos, ($total_moneda+$total_gastos_moneda), $formato_moneda);
						$ws1->writeNumber($filas, $col_monto, $total_moneda, $formato_moneda);
						$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $total_gastos_moneda, $formato_moneda);
						$total_moneda = 0;
						$total_gastos_moneda = 0;
					}
					$moneda = $cobro['id_moneda'];
					$filas += 2;
					$ws1->write($filas, $col_cliente, __('Cobranza en ').$cobro['glosa_moneda'], $encabezado);
					$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_honorarios_pesos);
					++$filas;
					//se ponen los titulos por tabla
					$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
					$ws1->write($filas, $col_total_pesos, __('Total'), $titulo_filas);
					$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
					$ws1->write($filas, $col_fecha_emision, __('Fecha Emision'), $titulo_filas);
					$ws1->write($filas, $col_fecha_envio, __('Fecha Envio'), $titulo_filas);
					$ws1->write($filas, $col_cobro, __('Cobro'), $titulo_filas);
					$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
					$ws1->write($filas, $col_moneda, __('Moneda'), $titulo_filas);
					$ws1->write($filas, $col_monto, __('Monto Honorarios'), $titulo_filas);
					$ws1->write($filas, $col_monto_honorarios_pesos, __('Monto Gastos'), $titulo_filas);
					$fila_inicial_tabla = $filas+1;
					$tabla_creada = true;
					$nueva_tabla = true;
				}
			}
			else if($desglosar_por_encargado&&$desglosar_por_moneda)
			{
				if($moneda != $cobro['id_moneda']&&$encargado != $cobro['nombre'])
				{
					if($tabla_creada)
					{
						$fila_final_tabla = $filas;
						++$filas;
						$ws1->write($filas, $col_cliente, __('Total'), $encabezado);
						$ws1->writeFormula($filas, $col_total_pesos, "=SUM()", $formato_moneda);
						//$ws1->writeNumber($filas, $col_total_pesos, ($total_moneda+$total_gastos_moneda), $formato_moneda);
						$ws1->writeNumber($filas, $col_monto, $total_moneda, $formato_moneda);
						$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $total_gastos_moneda, $formato_moneda);
						$total_moneda = 0;
						$total_gastos_moneda = 0;
					}
					$moneda = $cobro['id_moneda'];
					$encargado = $cobro['nombre'];
					$filas += 3;
					$ws1->write($filas, $col_cliente, __('Cobranza Morosa ').$cobro['nombre'], $encabezado);
					$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_honorarios_pesos);
					$filas += 2;
					$ws1->write($filas, $col_cliente, __('Cobranza en ').$cobro['glosa_moneda'], $encabezado);
					$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_honorarios_pesos);
					++$filas;
					//se ponen los titulos por tabla
					$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
					$ws1->write($filas, $col_total_pesos, __('Total'), $titulo_filas);
					$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
					$ws1->write($filas, $col_fecha_emision, __('Fecha Emision'), $titulo_filas);
					$ws1->write($filas, $col_fecha_envio, __('Fecha Envío'), $titulo_filas);
					$ws1->write($filas, $col_cobro, __('Cobro'), $titulo_filas);
					$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
					$ws1->write($filas, $col_moneda, __('Moneda'), $titulo_filas);
					$ws1->write($filas, $col_monto, __('Monto Honorarios'), $titulo_filas);
					$ws1->write($filas, $col_monto_honorarios_pesos, __('Monto Gastos'), $titulo_filas);
					$fila_inicial_tabla = $filas+1;
					$tabla_creada = true;
					$nueva_tabla = true;
				}
				if($moneda != $cobro['id_moneda']&&$encargado == $cobro['nombre'])
				{
					if($tabla_creada)
					{
						$fila_final_tabla = $filas;
						++$filas;
						$ws1->write($filas, $col_cliente, __('Total'), $encabezado);
						$ws1->writeNumber($filas, $col_total_pesos, ($total_moneda+$total_gastos_moneda), $formato_moneda);
						$ws1->writeNumber($filas, $col_monto, $total_moneda, $formato_moneda);
						$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $total_gastos_moneda, $formato_moneda);
						$total_moneda = 0;
						$total_gastos_moneda = 0;
					}
					$moneda = $cobro['id_moneda'];
					$filas += 2;
					$ws1->write($filas, $col_cliente, __('Cobranza en ').$cobro['glosa_moneda'], $encabezado);
					$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_honorarios_pesos);
					++$filas;
					//se ponen los titulos por tabla
					$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
					$ws1->write($filas, $col_total_pesos, __('Total'), $titulo_filas);
					$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
					$ws1->write($filas, $col_fecha_emision, __('Fecha Emision'), $titulo_filas);
					$ws1->write($filas, $col_fecha_envio, __('Fecha Envio'), $titulo_filas);
					$ws1->write($filas, $col_cobro, __('Cobro'), $titulo_filas);
					$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
					$ws1->write($filas, $col_moneda, __('Moneda'), $titulo_filas);
					$ws1->write($filas, $col_monto, __('Monto Honorarios'), $titulo_filas);
					$ws1->write($filas, $col_monto_honorarios_pesos, __('Monto Gastos'), $titulo_filas);
					$fila_inicial_tabla = $filas+1;
					$tabla_creada = true;
					$nueva_tabla = true;
				}
				if($encargado != $cobro['nombre']&&$moneda == $cobro['id_moneda'])
				{
					if($tabla_creada)
					{
						$fila_final_tabla = $filas;
						++$filas;
						$ws1->write($filas, $col_cliente, __('Total'), $encabezado);
						$ws1->writeNumber($filas, $col_total_pesos, ($total_moneda+$total_gastos_moneda), $formato_moneda);
						$ws1->writeNumber($filas, $col_monto, $total_moneda, $formato_moneda);
						$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $total_gastos_moneda, $formato_moneda);
						$total_moneda = 0;
						$total_gastos_moneda = 0;
					}
					$encargado = $cobro['nombre'];
					$filas += 3;
					$ws1->write($filas, $col_cliente, __('Cobranza Morosa ').$cobro['nombre'], $encabezado);
					$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_honorarios_pesos);
					$filas += 2;
					$ws1->write($filas, $col_cliente, __('Cobranza en ').$cobro['glosa_moneda'], $encabezado);
					$ws1->mergeCells($filas, $col_cliente, $filas, $col_monto_honorarios_pesos);
					++$filas;
					//se ponen los titulos por tabla
					$ws1->write($filas, $col_cliente, __('Cliente'), $titulo_filas);
					$ws1->write($filas, $col_total_pesos, __('Total'), $titulo_filas);
					$ws1->write($filas, $col_asuntos, __('Asuntos'), $titulo_filas);
					$ws1->write($filas, $col_fecha_emision, __('Fecha Emision'), $titulo_filas);
					$ws1->write($filas, $col_fecha_envio, __('Fecha Envio'), $titulo_filas);
					$ws1->write($filas, $col_cobro, __('Cobro'), $titulo_filas);
					$ws1->write($filas, $col_factura, __('Factura'), $titulo_filas);
					$ws1->write($filas, $col_moneda, __('Moneda'), $titulo_filas);
					$ws1->write($filas, $col_monto, __('Monto Honorarios'), $titulo_filas);
					$ws1->write($filas, $col_monto_honorarios_pesos, __('Monto Gastos'), $titulo_filas);
					$fila_inicial_tabla = $filas+1;
					$nueva_tabla = true;
					$tabla_creada = true;
				}
			}
			++$filas;
			if($nombre_cliente != $cobro['glosa_cliente']||$nueva_tabla)
			{
				if($cliente_creado)
				{
					$fila_final_cliente = $fila_inicial_cliente+$cont_filas_cliente;
					if($sin_desglose||($desglosar_por_encargado&&!$desglosar_por_moneda))
						$ws1->writeNumber($fila_inicial_cliente, $col_total_pesos, $total_cliente_pesos, $formato_moneda);
					else
						$ws1->writeNumber($fila_inicial_cliente, $col_total_pesos, $total_cliente, $formato_moneda);
					$total_cliente = 0;
					$total_cliente_pesos = 0;
					$ws1->mergeCells($fila_inicial_cliente, $col_cliente, $fila_final_cliente, $col_cliente);
					$ws1->mergeCells($fila_inicial_cliente, $col_total_pesos, $fila_final_cliente, $col_total_pesos);
					$cont_filas_cliente = 0;
				}
				$nombre_cliente = $cobro['glosa_cliente'];
				$ws1->write($filas, $col_cliente, $nombre_cliente, $txt_opcion);
				$fila_inicial_cliente = $filas;
				$cliente_creado = true;
			}
			else
			{
				$cont_filas_cliente++;
				$ws1->write($filas, $col_cliente, "", $txt_opcion);
				$ws1->write($filas, $col_total_pesos, "", $txt_opcion);
			}
			$total_cliente += $cobro['saldo_honorarios']+$cobro['saldo_gastos'];
			$total_cliente_pesos += $cobro['saldo_honorarios_pesos']+$cobro['saldo_gastos_pesos'];
			$total_moneda += $cobro['saldo_honorarios'];
			$total_pesos += $cobro['saldo_honorarios_pesos'];
			$total_gastos_moneda += $cobro['saldo_gastos'];
			$total_gastos_pesos += $cobro['saldo_gastos_pesos'];
			$ws1->write($filas, $col_fecha_emision, Utiles::sql2date($cobro['fecha_emision']), $fecha);
			$ws1->write($filas, $col_fecha_envio, Utiles::sql2date($cobro['fecha_enviado_cliente']), $fecha);
			$ws1->write($filas, $col_cobro, $cobro['id_cobro'], $txt_centro);
			$ws1->write($filas, $col_asuntos, $glosa_asuntos[$cobro['id_cobro']], $txt_izquierda);
			$ws1->write($filas, $col_factura, $cobro['documento'] ? $cobro['documento'] : '-', $txt_centro);
			$ws1->write($filas, $col_moneda, $cobro['simbolo'], $txt_centro);
			if($sin_desglose||($desglosar_por_encargado&&!$desglosar_por_moneda))
			{
				$ws1->writeNumber($filas, $col_monto, ($cobro['saldo_honorarios']+$cobro['saldo_gastos']), $formato_moneda);
				$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $cobro['saldo_honorarios_pesos'], $formato_moneda);
				$ws1->writeNumber($filas, $col_monto_gastos_pesos, $cobro['saldo_gastos_pesos'], $formato_moneda);
			}
			else
			{
				$ws1->writeNumber($filas, $col_monto, $cobro['saldo_honorarios'], $formato_moneda);
				$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $cobro['saldo_gastos'], $formato_moneda);
			}
			$nueva_tabla = false;
		}
		if($tabla_creada)
		{
			$fila_final_tabla = $filas;
			$fila_final_cliente = $fila_inicial_cliente+$cont_filas_cliente;
			if($sin_desglose||($desglosar_por_encargado&&!$desglosar_por_moneda))
				$ws1->writeNumber($fila_inicial_cliente, $col_total_pesos, $total_cliente_pesos, $formato_moneda);
			else
				$ws1->writeNumber($fila_inicial_cliente, $col_total_pesos, $total_cliente, $formato_moneda);
			$ws1->mergeCells($fila_inicial_cliente, $col_cliente, $fila_final_cliente, $col_cliente);
			$ws1->mergeCells($fila_inicial_cliente, $col_total_pesos, $fila_final_cliente, $col_total_pesos);
			++$filas;
			$ws1->write($filas, $col_cliente, __('Total'), $encabezado);
			if($sin_desglose||($desglosar_por_encargado&&!$desglosar_por_moneda))
			{
				$ws1->writeNumber($filas, $col_total_pesos, ($total_pesos+$total_gastos_pesos), $formato_moneda);
				$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $total_pesos, $formato_moneda);
				$ws1->writeNumber($filas, $col_monto_gastos_pesos, $total_gastos_pesos, $formato_moneda);
			}
			else
			{
				$ws1->writeNumber($filas, $col_total_pesos, ($total_moneda+$total_gastos_moneda), $formato_moneda);
				$ws1->writeNumber($filas, $col_monto, $total_moneda, $formato_moneda);
				$ws1->writeNumber($filas, $col_monto_honorarios_pesos, $total_gastos_moneda, $formato_moneda);
			}
		}
		else
		{
			$filas += 2;
			$ws1->mergeCells($filas, $col_cliente, $filas, $col_moneda);
			$ws1->write($filas, $col_cliente, __('No se encontraron resultados'), $encabezado);
		}
		$wb->close();
		exit;
	}
	$pagina->titulo = __('Reporte Morosidad');
	$pagina->PrintTop();
?>


<form method=post name=formulario action="planilla_morosidad.php?xls=1">
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<table class="border_plomo tb_base" width="250px" align="center">
	<tr valign=top>
		<td align=left>
			<b><?=__('Clientes')?>:</b>
		</td>
	</tr>
	<tr valign=top>
		<td align=center>
			<?=Html::SelectQuery($sesion, "SELECT codigo_cliente, glosa_cliente AS nombre FROM cliente WHERE activo=1 ORDER BY nombre ASC", "clientes[]", $clientes, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
		</td>
	</tr>
	<tr valign=top>
		<td align=left>
			<b><?=__('Encargados Comerciales')?>:</b>
		</td>
	</tr>
	<tr>
		<td align=center>
			<?=Html::SelectQuery($sesion, "SELECT usuario.id_usuario, CONCAT_WS(' ', apellido1, apellido2, ', ', nombre)
				FROM usuario JOIN usuario_permiso USING(id_usuario)
				WHERE codigo_permiso='SOC' ORDER BY apellido1", "socios[]", $socios, "class=\"selectMultiple\" multiple size=6 ", "", "200"); ?>
		</td>
	</tr>
	<tr>
		<td align=left>
			<b><?=__('Monedas') ?>:</b>
		</td>
	</tr>
	<tr>
		<td align=center>
			<?=Html::SelectQuery($sesion, "SELECT id_moneda, glosa_moneda AS nombre FROM prm_moneda ORDER BY id_moneda ASC", "monedas[]", $monedas, "class=\"selectMultiple\" multiple size=4 ", "", "200"); ?>
		</td>
	</tr>
	<tr>
		<td align=left>
			<input type="checkbox" value="1" name="desglosar_por_moneda" id="desglosar_por_moneda" <?=$desglosar_por_moneda ? 'checked' : ''?> />
			<label for="desglosar_por_moneda"><?=__('Desglosar por moneda')?></label>
		</td>
	</tr>
	<tr>
		<td align=left>
			<input type="checkbox" value="1" name="desglosar_por_encargado" id="desglosar_por_encargado" <?=$desglosar_por_encargado ? 'checked' : ''?>>
			<label for="desglosar_por_encargado"><?=__('Desglosar por encargado comercial')?></label>
		</td>
	</tr>
	<tr>
		<td align=left>
			<input type="checkbox" name="periodo" value="1" <?=$periodo ? 'checked' : '' ?> onclick='' title='<?=__('Periodo del Cobro')?>' />&nbsp;	
			<b><?=__('Periodo') ?>:</b>
			<div id=periodo_rango style='align:center'>
				&nbsp;&nbsp;&nbsp;
				<?=__('Emitido desde')?>
				<input type="text" name="fecha_ini" value="<?=$fecha_ini ? $fecha_ini : date("d-m-Y",strtotime('-1 year')) ?>" id="fecha_ini" size="11" maxlength="10" />
				<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha_ini" style="cursor:pointer" />
				</div>
			</td>
	</tr>
	<tr>
		<td align=right>
			&nbsp;
		</td>
	</tr>
	<tr>
		<td align=right>
			<input type=submit class=btn value="<?=__('Generar planilla')?>" />
		</td>
	</tr>
</table>
</form>

<script type="text/javascript">
Calendar.setup(
	{
		inputField	: "fecha_ini",		// ID of the input field
		ifFormat	: "%d-%m-%Y",		// the date format
		button		: "img_fecha_ini"	// ID of the button
	}
);
</script>

<?
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
