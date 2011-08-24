<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Html.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/classes/InputId.php';
	require_once Conf::ServerDir().'/classes/Trabajo.php';
	require_once Conf::ServerDir().'/classes/Funciones.php';
	require_once Conf::ServerDir().'/classes/Gasto.php';
	require_once Conf::ServerDir().'/classes/Moneda.php';
	require_once Conf::ServerDir().'/classes/Cliente.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/Documento.php';
	require_once Conf::ServerDir().'/classes/NeteoDocumento.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Autocompletador.php';
	#require_once Conf::ServerDir().'/classes/GastoGeneral.php';

	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);
	$formato_fecha = UtilesApp::ObtenerFormatoFecha($sesion);


	if($id_documento != "")
	{
		$documento = new Documento($sesion);
		$documento->Load($id_documento);
		if($accion == "eliminar")
		{
			$documento->EliminarNeteos();

			$query_p = "DELETE from cta_corriente WHERE cta_corriente.documento_pago = '".$id_documento."' ";
			mysql_query($query_p, $sesion->dbh) or Utiles::errorSQL($query_p,__FILE__,__LINE__,$sesion->dbh);

				if($documento->Delete())
					$pagina->AddInfo(__('El documento ha sido eliminado satisfactoriamente'));

			$opc = 'buscar';
		}
	}

	// Obtener datos de la moneda base.
	$query = 'SELECT simbolo, cifras_decimales FROM prm_moneda WHERE moneda_base=1;';
	$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	list($simbolo_moneda, $cifras_decimales) = mysql_fetch_array($resp);

	if($opc == 'buscar')
	{
		$where = "";
		$query_total = "SELECT SUM(monto_base) FROM documento WHERE codigo_cliente = '$codigo_cliente'";
		$resp = mysql_query($query_total, $sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh);
		list($total_cta) = mysql_fetch_array($resp);

		if($orden == "")
			$orden = "doc.id_documento DESC";

		if($fecha1 || $fecha2)
			$where .= " AND doc.fecha BETWEEN '".($fecha1? Utiles::fecha2sql($fecha1):'01-01-1800')."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";

		if( $codigo_cliente_secundario != '' && $codigo_cliente == '' )
			{
				$cliente = new Cliente($sesion);
				$codigo_cliente = $cliente->CodigoSecundarioACodigo( $codigo_cliente_secundario );
			} 
			
		if($codigo_cliente)
			$where .= " AND doc.codigo_cliente = '$codigo_cliente' ";

		$query = "SELECT SQL_CALC_FOUND_ROWS doc.id_documento as id_documento,
								doc.tipo_doc,
								doc.monto as monto,
								doc.glosa_documento,
								doc.fecha,
								cobro.codigo_idioma as codigo_idioma,
								moneda.simbolo,
								moneda.cifras_decimales,
								doc.id_cobro,
								doc.codigo_cliente,
								cliente.glosa_cliente AS nombre_cliente
							FROM documento as doc
								LEFT JOIN cobro USING( id_cobro ) 
								JOIN prm_moneda moneda ON doc.id_moneda = moneda.id_moneda
								LEFT JOIN cliente ON cliente.codigo_cliente=doc.codigo_cliente
							WHERE 1 ".$where." GROUP BY doc.id_documento ";

		$x_pag = 13;
		$b = new Buscador($sesion, $query, "Documento", $desde, $x_pag, $orden);
		$b->formato_fecha = "$formato_fecha";
		$b->nombre = "busc_documentos";
		$b->titulo = "Documentos";

		$b->AgregarEncabezado("id_documento",__('N°'));
		if(!$codigo_cliente)
			$b->AgregarEncabezado("nombre_cliente",__('Cliente'));
		$b->AgregarEncabezado("fecha",__('Fecha'));
		$b->AgregarEncabezado("glosa_documento",__('Descripción'), "align=left");
		$b->AgregarFuncion("Egreso","Monto","align=right nowrap");
		$b->AgregarFuncion("Ingreso","Ingreso","align=right nowrap");
		$b->AgregarFuncion(__('Opción'),"Opciones","align=right nowrap");
		$b->color_mouse_over = "#bcff5c";


		function Opciones(& $fila)
		{
			$id_documento = $fila->fields['id_documento'];
			$codigo_cliente = $fila->fields['codigo_cliente'];
			$html_opcion = '';

			if($fila->fields['id_cobro'] && $fila->fields['tipo_doc'] == 'N')
				$html_opcion .= "<img src='".Conf::ImgDir()."/coins_16.png' title='".__('Editar cobro')."' border=0 style='cursor:pointer' onclick=\"nuevaVentana('Editar_Cobro',730,580,'cobros6.php?id_cobro=".$fila->fields['id_cobro']."&popup=1&popup=1&contitulo=true', 'top=100, left=155');\">&nbsp;";
			else
			{
				if($fila->fields['monto'] > 0)
					$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Ingreso',730,580,'ingresar_documento_cobro.php?id_documento=$id_documento&popup=1', 'top=100, left=155');\" ><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar></a>&nbsp;";
				else
					$html_opcion .= "<a href='javascript:void(0)' onclick=\"nuevaVentana('Editar_Ingreso',730,580,'ingresar_documento_pago.php?id_documento=$id_documento&popup=1', 'top=100, left=155');\" ><img src='".Conf::ImgDir()."/editar_on.gif' border=0 title=Editar></a>&nbsp;";

				$html_opcion .= "<a target=_parent href='javascript:void(0)' onclick=\"parent.EliminaDocumento($id_documento,'$codigo_cliente')\" ><img src='".Conf::ImgDir()."/cruz_roja.gif' border=0 title=Eliminar></a>";
			}

			return $html_opcion;
		}
		
		function Monto(& $fila)
		{
			global $sesion;
			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			if( $fila->fields['codigo_idioma'] != '' )
				$idioma->Load($fila->fields['codigo_idioma']);
			else
				$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
			return $fila->fields['monto'] > 0 ? $fila->fields['simbolo'] . " " .number_format($fila->fields['monto'],$fila->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']) : '';
		}
		
		function Ingreso(& $fila)
		{
			global $sesion;
			$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
			if( $fila->fields['codigo_idioma'] != '' )
				$idioma->Load($fila->fields['codigo_idioma']);
			else
				$idioma->Load(strtolower(UtilesApp::GetConf($sesion,'Idioma')));
			return $fila->fields['monto'] < 0 ? $fila->fields['simbolo'] . " " .str_replace("-","",number_format($fila->fields['monto'],$fila->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles'])) : '';
		}
	}
	elseif($opc=='excel_cuenta_corriente_cliente')
	{
		require_once 'Spreadsheet/Excel/Writer.php';

		$query_todas_monedas = 'SELECT id_moneda, simbolo, cifras_decimales, tipo_cambio 
				FROM prm_moneda
				ORDER BY id_moneda';
		$resp_todas_monedas = mysql_query($query_todas_monedas, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		
		// Obtener datos de la moneda base.
		$query = 'SELECT simbolo, cifras_decimales, tipo_cambio, id_moneda FROM prm_moneda WHERE moneda_base=1;';
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($simbolo_moneda, $cifras_decimales, $tipo_cambio, $id_moneda_base) = mysql_fetch_array($resp);

		$query = "SELECT glosa_cliente FROM cliente WHERE codigo_cliente='$codigo_cliente';";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($glosa_cliente) = mysql_fetch_array($resp);

		// Obtener movimientos de la cuenta en el período seleccionado.
		if($fecha1 || $fecha2)
			$where = " AND fecha BETWEEN '".($fecha1? Utiles::fecha2sql($fecha1):'01-01-1800')."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
		else
			$where = '';
			
		if( $codigo_cliente_secundario != '' && $codigo_cliente == '' )
			{
				$cliente = new Cliente($sesion);
				$codigo_cliente = $cliente->CodigoSecundarioACodigo( $codigo_cliente_secundario );
			} 
		
		$query = "SELECT id_documento AS num_docs
				, glosa_documento
				, SUM(IF(monto_base>0, monto_base, 0)) AS egresos
				, SUM(IF(monto_base>0, 0, -monto_base)) AS ingresos
				, monto_base
				, id_moneda as id_moneda_documento
					FROM documento
					WHERE codigo_cliente = '$codigo_cliente' $where
					GROUP BY id_documento
					ORDER BY id_documento DESC";
			
	
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		// Crear la planilla.
		$wb = new Spreadsheet_Excel_Writer();
		$wb->send("cuenta_corriente_cliente.xls");
		$ws =& $wb->addWorksheet(__('Movimientos'));
		$ws->setInputEncoding('utf-8');
		$ws->setZoom(80);

		$wb->setCustomColor ( 35, 220, 255, 220 );
		$wb->setCustomColor ( 36, 255, 255, 220 );

		$formato_encabezado =& $wb->addFormat(array('Bold' => '1',
												'Size' => 12,
												'Align' => 'justify',
												'VAlign' => 'top',
												'Color' => 'black'));
		$formato_titulo =& $wb->addFormat(array('Size' => 12,
												'Align' => 'center',
												'Bold' => '1',
												'FgColor' => '35',
												'Border' => 1,
												'Locked' => 1,
												'Color' => 'black'));
		$formato_celda =& $wb->addFormat(array('Size' => 12,
												'VAlign' => 'top',
												'Align' => 'right',
												'Border' => 1,
												'Color' => 'black'));
		$formato_total =& $wb->addFormat(array('Size' => 12,
												'VAlign' => 'top',
												'Align' => 'right',
												'Border' => 1,
												'Color' => 'black',
												'Bold' => '1'));
		if($cifras_decimales)
		{
			$decimales = '.';
			while($cifras_decimales--)
				$decimales .= '#';
		}
		else
			$decimales = '';
		
		// inicio formato para todas las monedas
		$formatos_monedas = array();
		while(list($id_moneda, $simbolo_moneda, $cifras_decimales, $tipo_cambio) = mysql_fetch_array($resp_todas_monedas)){
			$cifras_decimales_tmp = $cifras_decimales;
			if($cifras_decimales_tmp>0)
			{
				$decimales = '.';
				while($cifras_decimales_tmp-- >0)
					$decimales .= '0';
			}
			else
			{
				$decimales = '';
			}
			
			$formatos_monedas[$id_moneda]['tipo_cambio']=$tipo_cambio;
			$formatos_monedas[$id_moneda]['cifras_decimales']=$cifras_decimales;	
			$formatos_monedas[$id_moneda]['xls'] =& $wb->addFormat(array('Size' => 12,
																'VAlign' => 'top',
																'Align' => 'right',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formatos_monedas[$id_moneda]['xls_total'] =& $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
																'Border' => 1,
																'Size' => 12,
																'Align' => 'right',
																'Bold' => '1'));
		}
		// fin formato para todas las monedas

		// Imprimir encabezado
		$ws->write(1, 1, __("Resumen cuenta corriente ").$glosa_cliente, $formato_encabezado);
		$ws->mergeCells(1, 1, 1, 5);
		$ws->write(3, 1, __("Generado el:"), $formato_encabezado);
		$ws->mergeCells(3, 1, 3, 2);
		$ws->write(3, 3, date("Y-m-d  h:i:s"), $formato_encabezado);
		$ws->mergeCells(3, 3, 3, 5);
		if($fecha1 && $fecha2)
		{
			$ws->write(4, 1, __("Fecha consulta:"), $formato_encabezado);
			$ws->mergeCells(4, 1, 4, 2);
			$ws->write(4, 3, $fecha1." - ".$fecha2, $formato_encabezado);
		}
		elseif($fecha1)
		{
			$ws->write(4, 1, __("Fecha consulta:"), $formato_encabezado);
			$ws->mergeCells(4, 1, 4, 2);
			$ws->write(4, 3, __("Desde ") . $fecha1. __(" hasta hoy."), $formato_encabezado);
		}
		elseif($fecha2)
		{
			$ws->write(4, 1, __("Fecha consulta:"), $formato_encabezado);
			$ws->mergeCells(4, 1, 4, 2);
			$ws->write(4, 3, __("Hasta ").$fecha2, $formato_encabezado);
			$ws->mergeCells(4, 3, 4, 5);
		}

		$filas = 7;
		$inicio_datos = $filas;
		$col_num = 1;
		$col_fecha = 2;
		$col_descripcion = 3;
		$col_monto_moneda_tarifa = 4;
		$col_egreso = 5;
		$col_ingreso = 6;
		$col_balance = 7;
		$col_egreso_para_formula = Utiles::NumToColumnaExcel($col_egreso);
		$col_ingreso_para_formula = Utiles::NumToColumnaExcel($col_ingreso);
		$col_balance_para_formula = Utiles::NumToColumnaExcel($col_balance);
		$ws->setColumn($col_num, $col_fecha, 13);
		$ws->setColumn($col_nombre, $col_monto_moneda_tarifa, 18);
		$ws->setColumn($col_descripcion, $col_descripcion, 30);
		$ws->setColumn($col_egreso, $col_balance, 18);

		// imprimir encabezado
		$ws->write($filas, $col_num, __('Nº'), $formato_titulo);
		$ws->write($filas, $col_fecha, __('Fecha'), $formato_titulo);
		$ws->write($filas, $col_descripcion, __('Descripción'), $formato_titulo);
		$ws->write($filas, $col_monto_moneda_tarifa, __('Monto Original'), $formato_titulo);
		$ws->write($filas, $col_egreso, __('Egreso'), $formato_titulo);
		$ws->write($filas, $col_ingreso, __('Ingreso'), $formato_titulo);
		$ws->write($filas, $col_balance, __('Balance parcial'), $formato_titulo);

		// imprimir filas
		while(list($numero, $descripcion, $egresos, $ingresos, $monto, $id_moneda_documento) = mysql_fetch_array($resp))
		{
			$egreso_moneda_base  = UtilesApp::CambiarMoneda($egresos,$formatos_monedas[$id_moneda_documento]['tipo_cambio'],$formatos_monedas[$id_moneda_documento]['cifras_decimales'],$formatos_monedas[$id_moneda_base]['tipo_cambio'],$formatos_monedas[$id_moneda_base]['cifras_decimales']);
			$ingreso_moneda_base = UtilesApp::CambiarMoneda($ingresos,$formatos_monedas[$id_moneda_documento]['tipo_cambio'],$formatos_monedas[$id_moneda_documento]['cifras_decimales'],$formatos_monedas[$id_moneda_base]['tipo_cambio'],$formatos_monedas[$id_moneda_base]['cifras_decimales']);
			
			
			++$filas;
			// monto negativo <-> ingreso
			$ws->write($filas, $col_num, $numero, $formato_celda);
			$ws->write($filas, $col_fecha, $fecha, $formato_celda);
			$ws->write($filas, $col_descripcion, $descripcion, $formato_celda);
			$ws->write($filas, $col_monto_moneda_tarifa, $monto, $formatos_monedas[$id_moneda_documento]['xls']);
			$ws->write($filas, $col_egreso, $egreso_moneda_base, $formatos_monedas[$id_moneda_base]['xls']);
			$ws->write($filas, $col_ingreso, $ingreso_moneda_base, $formatos_monedas[$id_moneda_base]['xls']);
			$ws->writeFormula($filas, $col_balance, "=$col_egreso_para_formula".($filas+1)." - $col_ingreso_para_formula".($filas+1).")", $formatos_monedas[$id_moneda_base]['xls']);
		}

		// imprimir totales
		++$filas;
		$ws->write($filas, $col_num, __('Total'), $formato_total);
		$ws->write($filas, $col_fecha, '', $formato_total);
		$ws->write($filas, $col_descripcion,'', $formato_total);
		$ws->write($filas, $col_monto_moneda_tarifa,'', $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->writeFormula($filas, $col_egreso, "=SUM($col_egreso_para_formula".($inicio_datos+2).":$col_egreso_para_formula$filas)", $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->writeFormula($filas, $col_ingreso, "=SUM($col_ingreso_para_formula".($inicio_datos+2).":$col_ingreso_para_formula$filas)", $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->writeFormula($filas, $col_balance, "=$col_egreso_para_formula".($filas+1)." - $col_ingreso_para_formula".($filas+1).")", $formatos_monedas[$id_moneda_documento]['xls_total']);

		// cerrar archivo
		$wb->close();
		exit;
	}
	elseif($opc=='excel_todos')
	{
		require_once 'Spreadsheet/Excel/Writer.php';
		
		$query_todas_monedas = 'SELECT id_moneda, simbolo, cifras_decimales, tipo_cambio 
				FROM prm_moneda
				ORDER BY id_moneda';
		$resp_todas_monedas = mysql_query($query_todas_monedas, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		
		// Obtener datos de la moneda base.
		$query = 'SELECT simbolo, cifras_decimales, tipo_cambio, id_moneda FROM prm_moneda WHERE moneda_base=1;';
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($simbolo_moneda, $cifras_decimales, $tipo_cambio, $id_moneda_base) = mysql_fetch_array($resp);

		// Obtener movimientos de la cuenta en el período seleccionado.
		if($fecha1 || $fecha2)
			$where = " AND fecha BETWEEN '".($fecha1? Utiles::fecha2sql($fecha1):'01-01-1800')."' AND '".Utiles::fecha2sql($fecha2).' 23:59:59'."' ";
		else
			$where = '';
		$query = "SELECT COUNT(id_documento) AS num_docs
				, SUM(IF(monto_base>0, monto_base, 0)) AS egresos
				, SUM(IF(monto_base>0, 0, -monto_base)) AS ingresos
				, sum(monto_base) as monto
				, glosa_cliente
				, documento.id_moneda as id_moneda_documento
					FROM documento
						LEFT JOIN cliente ON cliente.codigo_cliente=documento.codigo_cliente
					WHERE 1 $where
					GROUP BY glosa_cliente
					ORDER BY id_documento DESC";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		// Crear la planilla.
		$wb = new Spreadsheet_Excel_Writer();
		$wb->send("cuenta_corriente.xls");
		$ws =& $wb->addWorksheet(__('Balances'));
		$ws->setInputEncoding('utf-8');
		$ws->setZoom(80);

		$wb->setCustomColor ( 35, 220, 255, 220 );
		$wb->setCustomColor ( 36, 255, 255, 220 );

		$formato_encabezado =& $wb->addFormat(array('Bold' => '1',
												'Size' => 12,
												'Align' => 'justify',
												'VAlign' => 'top',
												'Color' => 'black'));
		$formato_titulo =& $wb->addFormat(array('Size' => 12,
												'Align' => 'center',
												'Bold' => '1',
												'FgColor' => '35',
												'Border' => 1,
												'Locked' => 1,
												'Color' => 'black'));
		$formato_celda =& $wb->addFormat(array('Size' => 12,
												'VAlign' => 'top',
												'Align' => 'right',
												'Border' => 1,
												'Color' => 'black'));
		$formato_total =& $wb->addFormat(array('Size' => 12,
												'VAlign' => 'top',
												'Align' => 'right',
												'Border' => 1,
												'Color' => 'black',
												'Bold' => '1'));
		
		// inicio formato para todas las monedas
		$formatos_monedas = array();
		while(list($id_moneda, $simbolo_moneda, $cifras_decimales, $tipo_cambio) = mysql_fetch_array($resp_todas_monedas)){
			$cifras_decimales_tmp = $cifras_decimales;
			if($cifras_decimales_tmp>0)
			{
				$decimales = '.';
				while($cifras_decimales_tmp-- >0)
					$decimales .= '0';
			}
			else
			{
				$decimales = '';
			}
			
			$formatos_monedas[$id_moneda]['tipo_cambio']=$tipo_cambio;
			$formatos_monedas[$id_moneda]['cifras_decimales']=$cifras_decimales;	
			$formatos_monedas[$id_moneda]['xls'] =& $wb->addFormat(array('Size' => 12,
																'VAlign' => 'top',
																'Align' => 'right',
																'Border' => '1',
																'Color' => 'black',
																'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formatos_monedas[$id_moneda]['xls_total'] =& $wb->addFormat(array('NumFormat' => "[$$simbolo_moneda] #,###,0$decimales",
																'Border' => 1,
																'Size' => 12,
																'Align' => 'right',
																'Bold' => '1'));
		}
		// fin formato para todas las monedas
		
		
		
		// Imprimir encabezado
		$ws->mergeCells(1, 1, 1, 5);
		$ws->mergeCells(3, 1, 3, 2);
		$ws->mergeCells(3, 3, 3, 5);
		$ws->mergeCells(4, 1, 4, 2);
		$ws->mergeCells(4, 3, 4, 5);

		$ws->write(1, 1, __("Resumen cuenta corriente"), $formato_encabezado);
		$ws->write(3, 1, __("Generado el:"), $formato_encabezado);
		$ws->write(3, 3, date("Y-m-d  h:i:s"), $formato_encabezado);
		if($fecha1 && $fecha2)
		{
			$ws->write(4, 1, __("Fecha consulta:"), $formato_encabezado);
			$ws->write(4, 3, $fecha1." - ".$fecha2, $formato_encabezado);
		}
		elseif($fecha1)
		{
			$ws->write(4, 1, __("Fecha consulta:"), $formato_encabezado);
			$ws->write(4, 3, __("Desde ") . $fecha1. __(" hasta hoy."), $formato_encabezado);
		}
		elseif($fecha2)
		{
			$ws->write(4, 1, __("Fecha consulta:"), $formato_encabezado);
			$ws->write(4, 3, __("Hasta ").$fecha2, $formato_encabezado);
		}

		$filas = 7;
		$inicio_datos = $filas;
		$col_nombre = 1;
		$col_monto_moneda_tarifa = 2;
		$col_egreso = 3;
		$col_ingreso = 4;
		$col_balance = 5;
		$col_egreso_para_formula = Utiles::NumToColumnaExcel($col_egreso);
		$col_ingreso_para_formula = Utiles::NumToColumnaExcel($col_ingreso);
		$col_balance_para_formula = Utiles::NumToColumnaExcel($col_balance);
		$ws->setColumn($col_nombre, $col_nombre, 25);
		$ws->setColumn($col_nombre, $col_monto_moneda_tarifa, 18);
		$ws->setColumn($col_egreso, $col_balance, 18);

		// imprimir encabezado
		$ws->write($filas, $col_nombre, __('Cliente'), $formato_titulo);
		$ws->write($filas, $col_monto_moneda_tarifa, __('Monto Original'), $formato_titulo);
		$ws->write($filas, $col_egreso, __('Egresos'), $formato_titulo);
		$ws->write($filas, $col_ingreso, __('Ingresos'), $formato_titulo);
		$ws->write($filas, $col_balance, __('Balance'), $formato_titulo);

		// imprimir filas
		while(list($num_docs, $egresos, $ingresos, $monto_base, $glosa_cliente, $id_moneda_documento) = mysql_fetch_array($resp))
		{
			if($num_docs==0)
				continue;
				
			$egreso_moneda_base  = UtilesApp::CambiarMoneda($egresos,$formatos_monedas[$id_moneda_documento]['tipo_cambio'],$formatos_monedas[$id_moneda_documento]['cifras_decimales'],$formatos_monedas[$id_moneda_base]['tipo_cambio'],$formatos_monedas[$id_moneda_base]['cifras_decimales']);
			$ingreso_moneda_base = UtilesApp::CambiarMoneda($ingresos,$formatos_monedas[$id_moneda_documento]['tipo_cambio'],$formatos_monedas[$id_moneda_documento]['cifras_decimales'],$formatos_monedas[$id_moneda_base]['tipo_cambio'],$formatos_monedas[$id_moneda_base]['cifras_decimales']);
			
			
			++$filas;
			// monto negativo <-> ingreso
			$ws->write($filas, $col_nombre, $glosa_cliente, $formato_celda);
			$ws->write($filas, $col_monto_moneda_tarifa, $monto_base, $formatos_monedas[$id_moneda_documento]['xls']);
			$ws->write($filas, $col_egreso, $egreso_moneda_base, $formatos_monedas[$id_moneda_base]['xls']);
			$ws->write($filas, $col_ingreso, $ingreso_moneda_base, $formatos_monedas[$id_moneda_base]['xls']);
			$ws->writeFormula($filas, $col_balance, "=$col_egreso_para_formula".($filas+1)." - $col_ingreso_para_formula".($filas+1).")", $formatos_monedas[$id_moneda_base]['xls']);
		}

		// imprimir totales
		++$filas;
		$ws->write($filas, $col_nombre, __('Total'), $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->write($filas, $col_fecha, '', $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->write($filas, $col_descripcion,'', $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->write($filas, $col_monto_moneda_tarifa,'', $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->writeFormula($filas, $col_egreso, "=SUM($col_egreso_para_formula".($inicio_datos+2).":$col_egreso_para_formula$filas)", $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->writeFormula($filas, $col_ingreso, "=SUM($col_ingreso_para_formula".($inicio_datos+2).":$col_ingreso_para_formula$filas)", $formatos_monedas[$id_moneda_documento]['xls_total']);
		$ws->writeFormula($filas, $col_balance, "=$col_egreso_para_formula".($filas+1)." - $col_ingreso_para_formula".($filas+1).")", $formatos_monedas[$id_moneda_documento]['xls_total']);

		// cerrar archivo
		$wb->close();
		exit;
	}

	$pagina->titulo = __('Cuenta Cliente');
	$pagina->PrintTop();
?>


<script style="text/javascript">

function EliminaDocumento(id_documento, codigo_cliente)
{
	<?
	if($desde)
			$pagina_desde = '"&desde='.$desde.'"';
	else
			$pagina_desde = '""';
	?>
	var desde = <?=$pagina_desde ?>;
	var form = $('form_documentos');
	if(parseInt(id_documento) > 0 && confirm('¿Desea eliminar el documento seleccionado?') == true)
		self.location.href = 'lista_documentos.php?id_documento='+id_documento+desde+'&codigo_cliente='+codigo_cliente+'&accion=eliminar';
}

function BuscarGastos( form, from )
{
	if(!form)
		var form = $('form_documentos');
	form.opc.value = 'buscar';

	var fecha1 = $('fecha1').value;
	var fecha2 = $('fecha2').value;
	<? 
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
	 { ?>
		var codigo_cliente = '&codigo_cliente_secundario='+$('codigo_cliente_secundario').value;
<? } 
	else
	 { ?>
		var codigo_cliente = '&codigo_cliente='+$('codigo_cliente').value;
<? } ?>

	if(fecha1)
		fecha1 = '&fecha1='+fecha1;
	if(fecha2)
		fecha2 = '&fecha2='+fecha2;

	if(from == 'excel_uno')
		self.location.href = 'lista_documentos.php?opc=excel_cuenta_corriente_cliente&codigo_cliente='+codigo_cliente+fecha1+fecha2;
	else if(from == 'excel_todos')
		self.location.href = 'lista_documentos.php?opc=excel_todos'+codigo_cliente+fecha1+fecha2;
	else
		self.location.href = 'lista_documentos.php?opc=buscar'+codigo_cliente+fecha1+fecha2;
	return true;
}

function AgregarNuevo(tipo)
{
	if( $('codigo_cliente_secundario') ) 
		var valor_cliente = '&codigo_cliente_secundario=' + $('codigo_cliente_secundario').value;
	else if( $('codigo_cliente') ) 
		var valor_cliente = '&codigo_cliente=' + $('codigo_cliente').value;

	if(tipo == 'ingreso')
	{
		var urlo = "ingresar_documento_pago.php?popup=1&pago=true"+valor_cliente;
		nuevaVentana('Ingreso',730,470,urlo,'top=100, left=125');
	}
	else if(tipo == 'egreso')
	{
		var urlo = "ingresar_documento_cobro.php?popup=1&pago=false"+valor_cliente;
		nuevaVentana('Egreso',730,470,urlo,'top=100, left=125');
	}
}

function Refrescar()
{

	<?
		$pagina_desde = '';
		$fecha_ini = '';
		$fecha_fin = '';
		if($desde)
			$pagina_desde = '&desde='.$desde;
		if($fecha1)
			$fecha_ini = '&fecha1='.$fecha1;
		if($fecha2)
			$fecha_fin = '&fecha2='.$fecha2;

	echo "self.location.href= 'lista_documentos.php?opc=buscar".$pagina_desde.$fecha_ini.$fecha_fin."&codigo_cliente=".$codigo_cliente."';"; ?>
}

function MostrarOcultarExcel(value)
{

	var span = $('mostrar_bajar_excel');
	if(value>0)
		span.style['display'] = 'inline';
	else
		span.style['display'] = 'none';
}

</script>
<? echo Autocompletador::CSS(); ?>
<form method=post name="form_documentos" id="form_documentos">
<input type=hidden name=opc value=buscar>
<!-- Calendario DIV -->
<div id="calendar-container" style="width:221px; position:absolute; display:none;">
	<div class="floating" id="calendar"></div>
</div>
<!-- Fin calendario DIV -->

<table width="90%"><tr><td>
<fieldset class="tb_base" width="100%" style="border: 1px solid #BDBDBD;">
<legend><?=__('Filtros')?></legend>
<table style="border: 0px solid black" width='720px'>
	<tr>
		<td align=right width='30%'><b><?=__('Cliente ')?></b></td>
		<td colspan=3 align=left>
			<? 
	 	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
				{
					if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						echo Autocompletador::ImprimirSelector($sesion, '', $codigo_cliente_secundario);
					else	
						echo Autocompletador::ImprimirSelector($sesion, $codigo_cliente);
				}
			else
				{
				  if ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf','CodigoSecundario') && Conf::CodigoSecundario() ) )
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente_secundario","glosa_cliente", "codigo_cliente_secundario", $codigo_cliente_secundario,"","", 320);
					else
						echo InputId::Imprimir($sesion,"cliente","codigo_cliente","glosa_cliente", "codigo_cliente", $codigo_cliente,"","", 320);
				}?>
		</td>
	</tr>
	<tr>
		<td align=right>
			<?=__('Con Fecha posterior a:')?>
		</td>
		<td nowrap align=center width='100px'>
			<input onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha1" value="<?=$fecha1 ?>" id="fecha1" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha1" style="cursor:pointer" />
		</td>
		<td align=left width='80px'>
			<?=__(' y anterior a:')?>
		</td>
		<td nowrap align=left>
			<input onkeydown="if(event.keyCode==13)BuscarGastos(this.form,'buscar')" type="text" name="fecha2" value="<?=$fecha2 ?>" id="fecha2" size="11" maxlength="10" />
			<img src="<?=Conf::ImgDir()?>/calendar.gif" id="img_fecha2" style="cursor:pointer" />
		</td>
	</tr>
	<tr>
		<td></td>
		<td colspan=2 align=center>
			<input name=boton_buscar type=button value="<?=__('Buscar')?>" onclick="BuscarGastos(this.form,'buscar')" class=btn>
			<span id="mostrar_bajar_excel" style="display:<?=$codigo_cliente?'inline':'none' ?>;">
				<input name="boton_xls" type="button" value="<?=__('Descargar Excel del cliente')?>" onclick="BuscarGastos(this.form,'excel_uno')" class="btn">
			</span>
			<input name="boton_xls" type="button" value="<?=__('Descargar Excel de todos los clientes')?>" onclick="BuscarGastos(this.form,'excel_todos')" class="btn">
		</td>
		<td align=right>
			<? if($opc == 'buscar'): ?>
			<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('ingreso')" title="Agregar Ingreso"><?=__('Agregar Pago')?></a>&nbsp;&nbsp;&nbsp;&nbsp;
			<img src="<?=Conf::ImgDir()?>/agregar.gif" border=0> <a href='javascript:void(0)' onclick="AgregarNuevo('egreso')" title="Agregar Egreso"><?=__('Agregar Cobro')?></a>
			<? endif; ?>
		</td>
	</tr>
</table>
</fieldset>
</td></tr></table>
</form>
<br>
<script type="text/javascript">
Calendar.setup(
	{
		inputField	: "fecha1",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha1"		// ID of the button
	}
);
Calendar.setup(
	{
		inputField	: "fecha2",				// ID of the input field
		ifFormat		: "%d-%m-%Y",			// the date format
		button			: "img_fecha2"		// ID of the button
	}
);
</script>
<?
	if($opc == 'buscar')
	{
		echo($total_cta ? "<table width=100%><tr><td align=left><span style='font-size:11px'><b>".__('Balance cuenta corriente').": ".$simbolo_moneda." ".number_format($total_cta,$cifras_decimales,',','.')."</b></span></td></tr></table>":"");
		$b->Imprimir();
	}
	if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'TipoSelectCliente')=='autocompletador' ) || ( method_exists('Conf','TipoSelectCliente') && Conf::TipoSelectCliente() ) )
	{
		echo(Autocompletador::Javascript($sesion,false));
	}
	echo(InputId::Javascript($sesion));
	$pagina->PrintBottom();
?>
