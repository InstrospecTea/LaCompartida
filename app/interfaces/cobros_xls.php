<?
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
		$formato_encabezado_derecha =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Align' => 'right',
												'Bold' => 1,
												'Color' => 'black'));
		$formato_titulo =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Bold' => 1,
												'Locked' => 1,
												'Bottom' => 1,
												'FgColor' => 35,
												'Color' => 'black'));
		$formato_normal =& $wb->addFormat(array('Size' => 7,
												'VAlign' => 'top',
												'Color' => 'black'));
		$formato_descripcion =& $wb->addFormat(array('Size' => 7,
												'VAlign' => 'top',
												'Align' => 'left',
												'Color' => 'black',
												'TextWrap' => 1));
		$formato_tiempo =& $wb->addFormat(array('Size' => 7,
												'VAlign' => 'top',
												'Color' => 'black',
												'NumFormat' =>'[h]:mm'));
		$formato_total =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Bold' => 1,
												'Top' => 1,
												'Color' => 'black'));
		$formato_instrucciones12 =& $wb->addFormat(array('Size' => 12,
												'VAlign' => 'top',
												'Bold' => 1,
												'Color' => 'black'));
		$formato_instrucciones10 =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Bold' => 1,
												'Color' => 'black'));
		$formato_tiempo_total =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Bold' => 1,
												'Top' => 1,
												'Color' => 'black',
												'NumFormat' =>'[h]:mm'));
		$formato_resumen_text =& $wb->addFormat(array('Size' => 7,
												'Valign' => 'top',
												'Align' => 'left',
												'Border' => 1,
												'Color' => 'black',
												'TextWrap' => 1));
		$formato_resumen_text_derecha =& $wb->addFormat(array('Size' => 7,
												'Valign' => 'top',
												'Align' => 'right',
												'Border' => 1,
												'Color' => 'black'));
		$formato_resumen_text_izquierda =& $wb->addFormat(array('Size' => 7,
												'Valign' => 'top',
												'Align' => 'left',
												'Border' => 1,
												'Color' => 'black'));
		$formato_resumen_text_titulo =& $wb->addFormat(array('Size' => 9,
												'Valign' => 'top',
												'Align' => 'left',
												'Bold' => 1, 
												'Border' => 1,
												'Color' => 'black'));
		$formato_resumen_text_amarillo =& $wb->addFormat(array('Size' => 7,
												'Valign' => 'top',
												'Align' => 'left',
												'Border' => 1,
												'FgColor' => '37',
												'Color' => 'black',
												'TextWrap' => 1));
		$numeros =& $wb->addFormat(array('Size' => 7,
												'VAlign' => 'top',
												'Align' => 'right',
												'Border' => 1,
												'Color' => 'black',
												'NumFormat' => '0'));
		$numeros_amarillo =& $wb->addFormat(array('Size' => 7,
												'Valign' => 'top',
												'Align' => 'right',
												'Border' => 1,
												'FgColor' => '37',
												'Color' => 'black',
												'TextWrap' => '0'));

					
										  
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

	$col_fecha_ini = $col++;
	$col_fecha_med = $col++;
	$col_fecha_fin = $col++;
	
	$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
	$idioma->Load($cobro->fields['codigo_idioma']);
	
	if($idioma->fields['formato_fecha'] == '%d/%m/%y')
	{
		$col_fecha_dia = $col_fecha_ini;
		$col_fecha_mes = $col_fecha_med;
		$col_fecha_anyo = $col_fecha_fin;
	}
	else
	{
		$col_fecha_mes = $col_fecha_ini;
		$col_fecha_dia = $col_fecha_med;
		$col_fecha_anyo = $col_fecha_fin;
	}

	$col_abogado = $col++;
	if($opc_ver_cobrable)
		$col_es_cobrable = $col++;
	if(!$opc_ver_asuntos_separados)
		$col_asunto = $col++;
	if($opc_ver_solicitante)
		$col_solicitante = $col++;
	$col_descripcion = $col++;
	if($opc_ver_horas_trabajadas)
		$col_duracion_trabajada = $col++;
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
	if($opc_ver_horas_trabajadas)
		$col_formula_duracion_trabajada = Utiles::NumToColumnaExcel($col_duracion_trabajada);
	$col_formula_duracion_cobrable = Utiles::NumToColumnaExcel($col_tarificable_hh);
	$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
	$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
	$col_formula_tarificable_hh = Utiles::NumToColumnaExcel($col_tarificable_hh);
	//if($opc_ver_cobrable)
		$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
	$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);
	$col_formula_abogado = Utiles::NumToColumnaExcel($col_abogado);
	if($col_asunto)
		$col_formula_asunto = Utiles::NumToColumnaExcel($col_asunto);
	if($col_duracion_retainer)
		$col_formula_duracion_retainer = Utiles::NumToColumnaExcel($col_duracion_retainer);
		
	
	$col_formula_tarificable_hh_detalle = Utiles::NumToColumnaExcel($col_cobrable);

	// Esta variable se usa para que cada página tenga un nombre único.
	$numero_pagina = 0;
	
	if( $borradores ) 
	{
		$ws =& $wb->addWorksheet('Inicio');
				$ws->setPaper(1);
				$ws->hideScreenGridlines();
				$ws->setMargins(0.01);
				
				// Seteamas el ancho de las columnas, se definen en la tabla prm_excel_cobro >>>>>>>>>>>>>>>>>
				$ws->setColumn($col_id_trabajo, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarResumenExcel') ) || ( method_exists('Conf','UsarResumenExcel') && Conf::UsarResumenExcel() ) )
					{
						$ws->setColumn($col_fecha_ini, $col_fecha_ini, 7);
						$ws->setColumn($col_fecha_med, $col_fecha_med, 7);
						$ws->setColumn($col_fecha_fin, $col_fecha_fin, 7);

						$ws->setColumn($col_abogado, $col_abogado, 15);
					}
				else
					{
						$ws->setColumn($col_fecha_dia, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
						$ws->setColumn($col_fecha_mes, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
						$ws->setColumn($col_fecha_anyo, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));


						$ws->setColumn($col_abogado, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
					}
				if(!$opc_ver_asuntos_separados)
					$ws->setColumn($col_asunto, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_descripcion, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_tarificable_hh, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_tarifa_hh, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_valor_trabajo, $col_valor_trabajo, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_id_abogado, $col_id_abogado, 0, 0, 1);
				
				// Es necesario setear estos valores para que la emisión masiva funcione.
				$filas = 0;
 
				// Indicaciones correspondiente a la modificacion de trabajos desde el excel
				++$filas;	
				$ws->write($filas, $col_descripcion, 'INSTRUCCIONES:', $formato_instrucciones12);
				$filas+=2;
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_normal);
				$ws->write($filas, $col_fecha_ini, "Número del trabajo (no modificar). En el caso de dejar en blanco se ingresará un nuevo trabajo", $formato_normal);
				$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
				++$filas;
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_normal);
				$ws->write($filas, $col_fecha_ini, "La fecha se puede modificar, en el caso de ser modificada debe estar en el formato dd-mm-aaaa", $formato_normal);
				$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
				++$filas;
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_normal);
				$ws->write($filas, $col_fecha_ini, "Se puede modificar el usuario que realizó el trabajo, es importante que el nombre utilizado sea el que tiene el sistema. En el caso de que el sistema no reconozca el nombre no se hará la modificación de usuario.", $formato_normal);
				$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
				++$filas;
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_normal);
				$ws->write($filas, $col_fecha_ini, "Se puede hacer cualquier modificación. No hay restricciones de formato.", $formato_normal);
				$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
				++$filas;
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_normal);
				$ws->write($filas, $col_fecha_ini, "Se puede modificar la duración cobrable. Debe tener el formato hh:mm:ss (formato de tiempo de Excel). Si se deja en 00:00:00 el trabajo será 'castigado', no aparecerá en el cobro.", $formato_normal);
				$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
				++$filas;
				$ws->write($filas, $col_id_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_normal);
				$ws->write($filas, $col_fecha_ini, "No se puede modificar la tarifa. Esto debe ser hecho desde la información del cliente o asunto.", $formato_normal);
				$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
				++$filas;
				$ws->write($filas, $col_id_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_normal);
				$ws->write($filas, $col_fecha_ini, "Fórmula que equivale a la multiplicación de la tarifa por la duración cobrable.", $formato_normal);
				$ws->mergeCells($filas, $col_fecha_ini, $filas, $col_valor_trabajo);
				
				++$filas; 
				++$filas; 
                              
        // Encabezado de tabla vacia 
        $ws->write($filas++, $col_id_trabajo, Utiles::GlosaMult($sesion, 'titulo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_encabezado); 
        $ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        $ws->write($filas, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
		$ws->write($filas, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
		$ws->write($filas, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        $ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        if($opc_ver_solicitante) 
        $ws->write($filas, $col_solicitante, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        if(!$opc_ver_asuntos_separados) 
        $ws->write($filas, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        $ws->write($filas, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        if($opc_ver_horas_trabajadas) 
        $ws->write($filas, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        $ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo); 
        $ws->write($filas, $col_tarifa_hh, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_titulo); 
        $ws->write($filas, $col_valor_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_titulo); 
        $ws->write($filas, $col_id_abogado, __('NO MODIFICAR ESTA COLUMNA')); 

        // Deje un poco de espacio entre encabezado y los totales 
        $filas += 3; 

        // Imprimir el total del último asunto. 
        $ws->write($filas, $col_id_trabajo, __('Total'), $formato_total); 
        $ws->write($filas, $col_fecha_ini, '', $formato_total);
		$ws->write($filas, $col_fecha_med, '', $formato_total);
		$ws->write($filas, $col_fecha_fin, '', $formato_total);
        $ws->write($filas, $col_descripcion, '', $formato_total); 
        if($opc_ver_solicitante) 
          $ws->write($filas, $col_solicitante, '', $formato_total); 
        $ws->write($filas, $col_abogado, '', $formato_total); 
        if(!$opc_ver_asuntos_separados) 
          $ws->write($filas, $col_asunto, '', $formato_total); 
        if($opc_ver_horas_trabajadas) 
          $ws->write($filas, $col_duracion_trabajada, "0:00", $formato_tiempo_total); 
        $ws->write($filas, $col_tarificable_hh, "0:00", $formato_tiempo_total); 
        $ws->write($filas, $col_tarifa_hh, '', $formato_total); 
        $ws->write($filas, $col_valor_trabajo, '', $formato_total); 
        
        $ws->setRow($filas-4, 0, 0, 1);
        $ws->setRow($filas-3, 0, 0, 1);
        $ws->setRow($filas-2, 0, 0, 1);
        $ws->setRow($filas-1, 0, 0, 1);
				$ws->setRow($filas, 0, 0, 1);
	}
		
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
			$formato_fecha =& $wb->addFormat(array('Size' => 7,
									'Valign' => 'top',
									'Color' => 'black'));
			$formato_fecha->setNumFormat($ff.';[Red]@ "'.__("Error: Formato incorrecto de fecha").'"');

			
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
			$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'simbolo', 'prm_moneda', 'id_moneda');
			$cifras_decimales = Utiles::glosa($sesion, $cobro->fields['opc_moneda_total'], 'cifras_decimales', 'prm_moneda', 'id_moneda');
			$cifras_decimales_opc_moneda_total = $cifras_decimales;
			$simbolo_moneda_opc_moneda_total = $simbolo_moneda;
			if($cifras_decimales>0)
				{
					$decimales = '.';
					while($cifras_decimales-- >0)
						$decimales .= '0';
				}
			else
				$decimales = '';
			$formato_moneda_resumen =& $wb->addFormat(array('Size' => 10,
															'VAlign' => 'top',
															'Align' => 'right',
															'Bold' => '1',
															'Color' => 'black',
															'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
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
			$formato_moneda_resumen_cobro =& $wb->addFormat(array('Size' => 7,
															'VAlign' => 'top',
															'Align' => 'right',
															'Border' => '1',
															'Color' => 'black',
															'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$simbolo_moneda = Utiles::glosa($sesion, $contrato->fields['id_moneda_monto'], 'simbolo', 'prm_moneda', 'id_moneda');
			$cifras_decimales = Utiles::glosa($sesion, $contrato->fields['id_moneda_monto'], 'cifras_decimales', 'prm_moneda', 'id_moneda');
			$cifras_decimales_moneda_monto = $cifras_decimales;
			$simbolo_moneda_opc_moneda_monto = $simbolo_moneda;
			if($cifras_decimales)
				{
					$decimales = '.';
					while($cifras_decimales-- > 0)
						$decimales .= '0';
				}
			else	
				$decimales = '';
			$formato_moneda_monto_resumen =& $wb->addFormat(array('Size' => 7,
															'Valign' => 'top',
															'Align' => 'right',
															'Border' => 1,
															'Color' => 'black',
															'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$simbolo_moneda = Utiles::glosa($sesion, $cobro->fields['id_moneda_monto'], 'simbolo', 'prm_moneda', 'id_moneda');
			$cifras_decimales = Utiles::glosa($sesion, $cobro->fields['id_moneda_monto'], 'cifras_decimales', 'prm_moneda', 'id_moneda');
			if($cifras_decimales)
			{
				$decimales = '.';
				while($cifras_decimales-- > 0)
					$decimales .= '0';
			}
			else
				$decimales = '';
			$formato_moneda_monto =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Align' => 'right',
												'Bold' => 1,
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
			$formato_moneda =& $wb->addFormat(array('Size' => 7,
												'VAlign' => 'top',
												'Align' => 'right',
												'Color' => 'black',
												'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formato_moneda_total =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Align' => 'right',
												'Bold' => 1,
												'Top' => 1,
												'Color' => 'black',
												'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
			$formato_moneda_encabezado =& $wb->addFormat(array('Size' => 10,
												'VAlign' => 'top',
												'Align' => 'right',
												'Bold' => 1,
												'Color' => 'black',
												'NumFormat' => "[$$simbolo_moneda] #,###,0$decimales"));
		 	
		 	
			// **************** Imprime el encabezado de la hoja **********************
			
				// El largo máximo para el nombre de una hoja son 31 caracteres, reservamos 4 para el número de página y un espacio.
				// Es importante notar que los nombres se truncan automáticamente con el primer caracter con tilde o ñ.
				$nombre_pagina = ++$numero_pagina.' ';
				if(strlen($cliente->fields['glosa_cliente'])>27)
					$nombre_pagina .= substr($cliente->fields['glosa_cliente'], 0, 24).'...';
				else
					$nombre_pagina .= $cliente->fields['glosa_cliente'];
				$ws =& $wb->addWorksheet($nombre_pagina);
				$ws->setPaper(1);
				$ws->hideScreenGridlines();
				$ws->setMargins(0.01);

				// Seteamas el ancho de las columnas >>>>>>>>>>>>>>>>>
				$ws->setColumn($col_id_trabajo, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarResumenExcel') ) || ( method_exists('Conf','UsarResumenExcel') && Conf::UsarResumenExcel() ) )
					{
						$ws->setColumn($col_fecha_ini, $col_fecha_ini, 7);
						$ws->setColumn($col_fecha_med, $col_fecha_med, 7);
						$ws->setColumn($col_fecha_fin, $col_fecha_fin, 7);
						$ws->setColumn($col_abogado,$col_abogado, 15);
					}
				else	
					{
						$ws->setColumn($col_fecha_dia, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
						$ws->setColumn($col_fecha_mes, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
						$ws->setColumn($col_fecha_anyo, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
						$ws->setColumn($col_abogado, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
					}
				if(!$opc_ver_asuntos_separados)
					$ws->setColumn($col_asunto, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				if($opc_ver_solicitante)
					$ws->setColumn($col_solicitante, $col_solicitante, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_descripcion, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				if($opc_ver_horas_trabajadas)
					$ws->setColumn($col_duracion_trabajada, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_tarificable_hh, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				if($col_duracion_retainer)
					$ws->setColumn($col_duracion_retainer, $col_duracion_retainer, Utiles::GlosaMult($sesion, 'duracion_retainer', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				//if($col_cobrable)
					
				$ws->setColumn($col_tarifa_hh, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_valor_trabajo, $col_valor_trabajo, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				$ws->setColumn($col_id_abogado, $col_id_abogado, 0, 0, 1);
				
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'OcultarHorasTarificadasExcel') ) || ( method_exists('Conf','OcultarHorasTarificadasExcel') && Conf::OcultarHorasTarificadasExcel() ) )
				{
					$ws->setColumn($col_cobrable, $col_cobrable, 0, 0, 1);
				}
				else
				{
					$ws->setColumn($col_cobrable, $col_cobrable, Utiles::GlosaMult($sesion, 'cobrable', 'Listado de trabajos', "tamano", 'prm_excel_cobro', 'nombre_interno', 'grupo'));
				}
				// Agregar la imagen del logo
				if( method_exists('Conf', 'LogoExcel') )
				{
					$ws->setRow(0, .8*UtilesApp::AlturaLogoExcel());
					$ws->insertBitmap(0, 0, Conf::LogoExcel(), 0, 0, .8, .8);
				}
				
				// Es necesario setear estos valores para que la emisión masiva funcione.
				$primer_asunto = true;
				$creado = false;
				$filas = 0;
				$filas_totales_asuntos = array();

				$ws->write($filas, $col_descripcion+1, Utiles::GlosaMult($sesion, 'minuta', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo').' '.$cobro->fields['id_cobro'], $formato_encabezado);
				$filas += 2;

				// Escribir el encabezado con los datos del cliente
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'cliente', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
				$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
				$ws->write($filas, $col_abogado, str_replace('\\', '', $contrato->fields['factura_razon_social']), $formato_encabezado);
				$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
				++$filas;

				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'rut', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
				$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
				$ws->write($filas, $col_abogado, $contrato->fields['rut'], $formato_encabezado);
				$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
				++$filas;
				
				
				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'direccion', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
				$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
				$direccion = str_replace("\r", " ", $contrato->fields['direccion_contacto']);
				$direccion = str_replace("\n", " ", $direccion);
				$ws->write($filas, $col_abogado, $direccion, $formato_encabezado);
				$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
				++$filas;

				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'contacto', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
				$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
				$ws->write($filas, $col_abogado, $contacto->fields['contacto'], $formato_encabezado);
				$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
				++$filas;

				$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'telefono', 'Encabezado', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
				$ws->mergeCells($filas, $col_id_trabajo, $filas, $col_fecha_fin);
				$ws->write($filas, $col_abogado, $contacto->fields['fono_contacto'], $formato_encabezado);
				$ws->mergeCells($filas, $col_abogado, $filas, $col_valor_trabajo);
				$filas += 2;
			
				if($opc_ver_resumen_cobro || $borradores)
				{
					$ws->write($filas++, $col_id_trabajo, Utiles::GlosaMult($sesion, 'titulo', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);

					// Esto es para poder escribir la segunda columna más fácilmente.
					$filas2 = $filas;

					$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);

					$ws->write($filas++, $col_abogado, ($trabajo->fields['fecha_emision'] == '0000-00-00' or $trabajo->fields['fecha_emision'] == '') ? Utiles::sql2fecha(date('Y-m-d'), $idioma->fields['formato_fecha']) : Utiles::sql2fecha($trabajo->fields['fecha_emision'], $idioma->fields['formato_fecha']), $formato_encabezado);

					// Se saca la fecha inicial según el primer trabajo
					// esto es especial para LyR
					/*$query_tiempo = "SELECT fecha FROM trabajo WHERE id_cobro='$id_cobro' AND visible='1' ORDER BY fecha LIMIT 1";
					$resp_tiempo = mysql_query($query_tiempo, $sesion->dbh) or Utiles::errorSQL($query_tiempo, __FILE__, __LINE__, $sesion->dbh);
					
					// Se calcula si hay trabajos o no (porque si no sale como fecha 1969)
					if(mysql_num_rows($resp_tiempo) > 0)
						list($fecha_primer_trabajo) = mysql_fetch_array($resp_tiempo);
					else*/
						$fecha_primer_trabajo = $cobro->fields['fecha_ini'];
						
					// También se saca la fecha final según el último trabajo
					/*$query_dia = "SELECT LAST_DAY(fecha) FROM trabajo WHERE id_cobro='$id_cobro' AND visible='1' ORDER BY fecha DESC LIMIT 1";
					$resp_dia = mysql_query($query_dia, $sesion->dbh) or Utiles::errorSQL($query_dia, __FILE__ , __LINE__ , $sesion->dbh);
					
					//Se calcula si hay trabajos o no (porque si no sale como fecha 1969)
					if(mysql_num_rows($resp_dia) > 0)
						list($fecha_ultimo_trabajo) = mysql_fetch_array($resp_dia);
					else*/
						$fecha_ultimo_trabajo = $cobro->fields['fecha_fin'];
					$fecha_inicial_primer_trabajo = date('Y-m-01', strtotime($fecha_primer_trabajo));
					$fecha_final_ultimo_trabajo = date('Y-m-d', strtotime($fecha_ultimo_trabajo));
					if( $fecha_primer_trabajo && $fecha_primer_trabajo != '0000-00-00' )
						{
							$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha_desde', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);

							$ws->write($filas++, $col_abogado, Utiles::sql2fecha($fecha_inicial_primer_trabajo, $idioma->fields['formato_fecha']), $formato_encabezado);
						}

					$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'fecha_hasta', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
					$ws->write($filas++, $col_abogado, Utiles::sql2fecha($fecha_final_ultimo_trabajo, $idioma->fields['formato_fecha']), $formato_encabezado);

					// Si hay una factura asociada mostramos su número.
					if( $cobro->fields['documento'])
					{
						$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'factura', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
						$ws->write($filas++, $col_abogado, $cobro->fields['documento'], $formato_encabezado);
					}

					if($opc_ver_modalidad)
					{
						$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'forma_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
						//$ws->write($filas++, $col_abogado, __($cobro->fields['forma_cobro']), $formato_encabezado);
						if(($cobro->fields['forma_cobro'] == 'PROPORCIONAL') || ($cobro->fields['forma_cobro'] == 'RETAINER')){
							$mje_detalle_forma_cobro = "de ".$cobro->fields['retainer_horas']." Hr. por ".$simbolo_moneda_opc_moneda_monto." ".number_format($cobro->fields['monto_contrato'],$cobro_moneda->moneda[$cobro->fields['id_moneda_monto']]['cifras_decimales'],',','.');
						}
						else{
							$mje_detalle_forma_cobro = "";
						}
						$ws->write($filas++, $col_abogado, __($cobro->fields['forma_cobro'])." ".$mje_detalle_forma_cobro, $formato_encabezado);
					}

					if($trabajo->fields['forma_cobro']=='PROPORCIONAL' || $trabajo->fields['forma_cobro']=='RETAINER')
					{
						$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'horas_retainer', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
						$ws->write($filas++, $col_abogado, $cobro->fields['retainer_horas'], $formato_encabezado_derecha);
						$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'monto_retainer', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
						$ws->writeNumber($filas++, $col_abogado, $cobro->fields['monto_contrato'], $formato_moneda_monto);
					}

							// Segunda columna
							$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_horas', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
							$horas_cobrables = floor($cobro->fields['total_minutos']/60);
							$minutos_cobrables = sprintf("%02d", $cobro->fields['total_minutos']%60);
							$ws->write($filas2++, $col_valor_trabajo, "$horas_cobrables:$minutos_cobrables", $formato_encabezado_derecha);
						
							$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'honorarios', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
							$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['monto_subtotal'], $formato_moneda_encabezado);
						
							if( $cobro->fields['id_moneda'] != $cobro->fields['opc_moneda_total'] )
								{
									$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'equivalente', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
									$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-1).'*'.$cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']."/".$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'], $formato_moneda_resumen);
								}
							if($cobro->fields['descuento'] > 0)
								{
									$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'descuento', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
									$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['descuento']*$cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'], $formato_moneda_resumen);
									$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'subtotal', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
									$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-2)."-$col_formula_valor_trabajo".($filas2-1), $formato_moneda_resumen);
								} 
							if($cobro->fields['porcentaje_impuesto'] > 0 && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ValorImpuesto') > 0 ) || ( method_exists('Conf', 'ValorImpuesto') && Conf::ValorImpuesto()>0 ) ) )
								{
									if( $cobro->fields['porcentaje_impuesto_gastos'] > 0 && ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'ValorImpuestoGastos') > 0 ) || ( method_exists('Conf', 'ValorImpuestoGastos') && Conf::ValorImpuestoGastos()>0 ) ) )
										{
											if($opc_ver_gastos)
											{
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'gastos', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['subtotal_gastos'], $formato_moneda_resumen);
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'impuesto', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-2).'*0.'.$cobro->fields['porcentaje_impuesto']."+$col_formula_valor_trabajo".($filas2-1).'*0.'.$cobro->fields['porcentaje_impuesto_gastos'], $formato_moneda_resumen);
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeFormula($filas2++, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo".($filas2-3).":$col_formula_valor_trabajo".($filas2-1).")", $formato_moneda_resumen);
											}
											else
											{
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'impuesto', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-1).'*0.'.(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ValorImpuesto'):Conf::ValorImpuesto()), $formato_moneda_resumen);
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-2)." + $col_formula_valor_trabajo".($filas2-1), $formato_moneda_resumen);
											}
										}
									else
										{
											$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'impuesto', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
											$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-1).'*0.'.(method_exists('Conf','GetConf')?Conf::GetConf($sesion,'ValorImpuesto'):Conf::ValorImpuesto()), $formato_moneda_resumen);
											if($opc_ver_gastos)
											{
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'gastos', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['monto_gastos'], $formato_moneda_resumen);
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeFormula($filas2++, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo".($filas2-3).":$col_formula_valor_trabajo".($filas2-1).")", $formato_moneda_resumen);
											}
											else
											{
												$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
												$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-2)." + $col_formula_valor_trabajo".($filas2-1), $formato_moneda_resumen);
											}
										}
								}
							else
								{
									if($opc_ver_gastos)
									{
										$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'gastos', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
										$ws->writeNumber($filas2++, $col_valor_trabajo, $cobro->fields['monto_gastos'], $formato_moneda_resumen);
										$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
										$ws->writeFormula($filas2++, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo".($filas2-2).":$col_formula_valor_trabajo".($filas2-1).")", $formato_moneda_resumen);
									}
									else
									{
										$ws->write($filas2, $col_tarifa_hh, Utiles::GlosaMult($sesion, 'total_cobro', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado_derecha);
										$ws->writeFormula($filas2++, $col_valor_trabajo, "=$col_formula_valor_trabajo".($filas2-1), $formato_moneda_resumen);
									}
								}

					// Para seguir imprimiendo datos hay definir en que linea será 
					$filas = max($filas, $filas2);
					++$filas;
				}
				
				$query_num_usuarios = "SELECT DISTINCT id_usuario FROM trabajo WHERE id_cobro=".$cobro->fields['id_cobro'];
				$resp_num_usuarios = mysql_query($query_num_usuarios,$sesion->dbh) or Utiles::errorSQL($query_num_usuarios,__FILE__,__LINE__,$sesion->dbh);
				$num_usuarios = mysql_num_rows($resp_num_usuarios);
				
				// Dejar espacio para el resumen profesional si es necesario.
				if( ( $opc_ver_profesional && $mostrar_resumen_de_profesionales ) || $cobro->fields['opc_ver_profesional'] )
					{
						$fila_inicio_resumen_profesional = $filas - 1;
						if( $num_usuarios > 0 )
							$filas += $num_usuarios + 7;
						else
							$filas += 3;
					}
					
					$cont_asuntos = 0;
					
					// Bucle sobre todos los asuntos de este cobro 
					$cobro_tiene_trabajos = false;
					while( $cobro->asuntos[$cont_asuntos] )
					{
						$asunto = new Asunto($sesion);
						$asunto->LoadByCodigo($cobro->asuntos[$cont_asuntos]);
						$codigo_asunto_secundario = $asunto->CodigoACodigoSecundario($cobro->asuntos[$cont_asuntos]);
						
						$where_trabajos = " 1 ";
										if($opc_ver_asuntos_separados)
											$where_trabajos .= " AND trabajo.codigo_asunto ='".$asunto->fields['codigo_asunto']."' ";
										if(!$opc_ver_cobrable)
											$where_trabajos .= " AND trabajo.visible = 1 ";
										if(!$opc_ver_horas_trabajadas)
											$where_trabajos .= " AND trabajo.duracion_cobrada != '00:00:00' ";
														
						$query_cont_trabajos = "SELECT COUNT(*) FROM trabajo WHERE $where_trabajos AND id_cobro='".$cobro->fields['id_cobro']."' AND id_tramite = 0";
						$resp_cont_trabajos = mysql_query($query_cont_trabajos,$sesion->dbh) or Utiles::errorSQL($query_cont_trabajos,__FILE__,__LINE__,$sesion->dbh);
						list($cont_trabajos) = mysql_fetch_array($resp_cont_trabajos);
							
						$where_tramites = " 1 ";
										if($opc_ver_asuntos_separados)
											$where_tramites .= " AND tramite.codigo_asunto = '".$asunto->fields['codigo_asunto']."' ";

						$query_cont_tramites = "SELECT COUNT(*) FROM tramite WHERE $where_tramites AND id_cobro=".$cobro->fields['id_cobro'];
						$resp_cont_tramites = mysql_query($query_cont_tramites,$sesion->dbh) or Utiles::errorSQL($query_cont_tramites,__FILE__,__LINE__,$sesion->dbh);
						list($cont_tramites) = mysql_fetch_array($resp_cont_tramites);
						
							// Si el asunto tiene trabajos y/o trámites imprime su resumen
							if( ($cont_trabajos+$cont_tramites) > 0 )
								{ 
									$cobro_tiene_trabajos = true;
									if($opc_ver_asuntos_separados)
										{
											// Indicar en una linea que los asuntos se muestran por separado y lluego 
											// esconder la columna para que no ensucia la vista.
											$ws->write($filas, $col_fecha_ini, 'asuntos_separado', $formato_encabezado);
											$ws->write($filas++, $col_descripcion, $asunto->fields['codigo_asunto'], $formato_encabezado);
											$ws->setRow($filas-1, 0, 0, 1);
											$ws->write($filas, $col_fecha_ini, __('Asunto').': ', $formato_encabezado);
											if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ) )
												$ws->write($filas, $col_descripcion, $asunto->fields['codigo_asunto_secundario'].' - '.$asunto->fields['glosa_asunto'], $formato_encabezado);
											else
												$ws->write($filas, $col_descripcion,$asunto->fields['glosa_asunto'], $formato_encabezado);
											$filas += 2;
										}
										
								// Si existen trabajos imprime la tabla 
								if( $cont_trabajos > 0 )
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
																			WHERE $where_trabajos AND trabajo.id_tramite=0 AND trabajo.id_cobro=".$cobro->fields['id_cobro'];
											
									$orden = "trabajo.fecha, trabajo.descripcion";
									$b1 = new Buscador($sesion, $query_trabajos, "Trabajo", $desde, '', $orden);
									$lista_trabajos = $b1->lista;
									
									// Encabezado de la tabla de trabajos
									#$ws->write($filas++, $col_id_trabajo, Utiles::GlosaMult($sesion, 'titulo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_encabezado);
									$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									
									$ws->write($filas, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									$ws->write($filas, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									$ws->write($filas, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);

									$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									if(!$opc_ver_asuntos_separados)
										$ws->write($filas, $col_asunto, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									$ws->write($filas, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									if($opc_ver_solicitante)
										$ws->write($filas, $col_solicitante, Utiles::GlosaMult($sesion, 'solicitante', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									if($opc_ver_horas_trabajadas)
										$ws->write($filas, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									if($col_duracion_retainer)
										$ws->write($filas, $col_duracion_retainer, Utiles::GlosaMult($sesion, 'duracion_retainer', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									if($opc_ver_cobrable)
										$ws->write($filas, $col_es_cobrable, Utiles::GlosaMult($sesion, 'cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
									
									$ws->write($filas, $col_cobrable, __('Hr. Tarificadas'), $formato_titulo);
									$ws->write($filas, $col_tarifa_hh, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_titulo);
									$ws->write($filas, $col_valor_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'valor_trabajo', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_titulo);
									$ws->write($filas, $col_id_abogado, __('NO MODIFICAR ESTA COLUMNA'));
									if(!$primera_fila_primer_asunto)
										$primera_fila_primer_asunto = $filas;
									++$filas;
									$primera_fila_asunto = $filas+1;
							 		$diferencia_proporcional = 0;
							 		$suma_total_inexacto = 0;
							 		$suma_total_exacto = 0;
							 		// Contenido de la tabla de trabajos
									for($i=0;$i<$lista_trabajos->num;$i++)
									{
										$trabajo = $lista_trabajos->Get($i);
										
										$f = explode('-',$trabajo->fields['fecha']);
										$ws->write($filas,$col_fecha_dia, $f[2],$formato_normal);
										$ws->write($filas,$col_fecha_mes, $f[1],$formato_normal);
										$ws->write($filas,$col_fecha_anyo, $f[0],$formato_normal);

										//$ws->write($filas, $col_fecha, Utiles::sql2date($trabajo->fields['fecha'], $idioma->fields['formato_fecha']), $formato_normal);
										if(!$opc_ver_asuntos_separados)
											$ws->write($filas, $col_asunto, substr($trabajo->fields['codigo_asunto_secundario'], -4), $formato_descripcion);
										$ws->write($filas, $col_descripcion, str_replace("\r", '', stripslashes($trabajo->fields['descripcion'])), $formato_descripcion);
										// Se guarda el nombre en una variable porque se usa en el detalle profesional.
										$nombre = $trabajo->fields['username'];
										$ws->write($filas, $col_abogado, $nombre, $formato_normal);
										if($opc_ver_solicitante)
											$ws->write($filas, $col_solicitante, $trabajo->fields['solicitante'], $formato_normal);
										$duracion = $trabajo->fields['duracion'];
										list($h, $m) = split(':', $duracion);
										$duracion = $h/24 + $m/(24*60);
										if($opc_ver_horas_trabajadas)
											$ws->writeNumber($filas, $col_duracion_trabajada, $duracion, $formato_tiempo);
										$duracion_cobrada = $trabajo->fields['duracion_cobrada'];
										list($h, $m) = split(':', $duracion_cobrada);
										if($trabajo->fields['glosa_cobrable'] == 'SI')
											$duracion_cobrada = $h/24 + $m/(24*60);
										else
											$duracion_cobrada = 0;
										$ws->writeNumber($filas, $col_tarificable_hh, $duracion_cobrada, $formato_tiempo);
										
										$query_total_cobrable = "select if(sum(TIME_TO_SEC(duracion_cobrada)/3600)<=0,1,sum(TIME_TO_SEC(duracion_cobrada)/3600)) as duracion_total from trabajo where id_cobro = '".$cobro->fields['id_cobro']."' and cobrable = 1";
										$resp_total_cobrable = mysql_query($query_total_cobrable,$sesion->dbh) or Utiles::errorSQL($query_total_cobrable,__FILE__,__LINE__,$sesion->dbh);
										list($xtotal_hh_cobrable) = mysql_fetch_array($resp_total_cobrable);
										$factor = $cobro->fields['retainer_horas']/$xtotal_hh_cobrable;
										
										if( $cobro->fields['forma_cobro'] =='PROPORCIONAL' ) {
											$duracion_retainer = $duracion_cobrada * $factor;
										}
										else{
											$duracion_retainer = $trabajo->fields['duracion_retainer'];
											list($h,$m,$s) = split(':', $duracion_retainer);
											$duracion_retainer = $h/24 + $m/(24*60) + $s/(24*60*60);
										}
										if( $duracion_retainer > $duracion_cobrada || $cobro->fields['forma_cobro'] == 'FLAT FEE' )
											$duracion_retainer = $duracion_cobrada;
										if($col_duracion_retainer)
										{
											$ws->writeNumber($filas, $col_duracion_retainer, $duracion_retainer, $formato_tiempo);
										}
										$duracion_tarificable = max(($duracion_cobrada - $duracion_retainer),0);
										if($opc_ver_cobrable)
											$ws->write($filas, $col_es_cobrable, $trabajo->fields['cobrable']==1?__("Sí"):__("No"), $formato_normal);
										$ws->writeNumber($filas, $col_cobrable, $duracion_tarificable, $formato_tiempo);
										$ws->writeNumber($filas, $col_tarifa_hh, $trabajo->fields['tarifa_hh'], $formato_moneda);
										if($col_duracion_retainer)
											$ws->writeFormula($filas, $col_valor_trabajo, "=MAX(24*($col_formula_duracion_cobrable".($filas+1)."-$col_formula_duracion_retainer".($filas+1).")*$col_formula_tarifa_hh".($filas+1).";0)", $formato_moneda);
										else
											$ws->writeFormula($filas, $col_valor_trabajo, "=24*$col_formula_duracion_cobrable".($filas+1)."*$col_formula_tarifa_hh".($filas+1), $formato_moneda);
										$ws->write($filas, $col_id_trabajo, $trabajo->fields['id_trabajo'], $formato_normal);
										$ws->write($filas, $col_id_abogado, $trabajo->fields['id_usuario'], $formato_normal);
								
										// Si hay que mostrar el detalle profesional guardamos una lista con los profesionales que trabajaron en este asunto.
										if( $opc_ver_profesional || $cobro->fields['opc_ver_profesional'] )
										{
											if( $trabajo->fields['cobrable'] > 0 && !isset($detalle_profesional[$trabajo->fields['id_usuario']]) )
												{
													$detalle_profesional[$trabajo->fields['id_usuario']]['tarifa'] = $trabajo->fields['tarifa_hh'];
													$detalle_profesional[$trabajo->fields['id_usuario']]['nombre'] = $nombre;
												}
										}
										++$filas;
									}
									
									// Hay que eliminar la variable $diferencia_proporcional para que la proxima vez parte con zero
									$diferencia_proporcional = 0;
									
									// Guardar ultima linea para tener esta información por el resumen profesional
									$ultima_fila_ultimo_asunto = $filas-1;
									
									// Totales de la tabla de trámites 
									$ws->write($filas, $col_id_trabajo, __('Total'), $formato_total);
									$ws->write($filas, $col_fecha_ini, '', $formato_total);
									$ws->write($filas, $col_fecha_med, '', $formato_total);
									$ws->write($filas, $col_fecha_fin, '', $formato_total);
									$ws->write($filas, $col_descripcion, '', $formato_total);
									$ws->write($filas, $col_abogado, '', $formato_total);
									if(!$opc_ver_asuntos_separados)
										$ws->write($filas, $col_asunto, '', $formato_total);
									if($opc_ver_solicitante)
										$ws->write($filas, $col_solicitante, '', $formato_total);
									if($opc_ver_horas_trabajadas)
										$ws->writeFormula($filas, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada$primera_fila_asunto:$col_formula_duracion_trabajada$filas)", $formato_tiempo_total);
									if($col_duracion_retainer)
										$ws->writeFormula($filas, $col_duracion_retainer, "=SUM($col_formula_duracion_retainer$primera_fila_asunto:$col_formula_duracion_retainer$filas)", $formato_tiempo_total);
									$ws->writeFormula($filas, $col_tarificable_hh, "=SUM($col_formula_duracion_cobrable$primera_fila_asunto:$col_formula_duracion_cobrable$filas)", $formato_tiempo_total);
									if($opc_ver_cobrable)
										$ws->write($filas, $col_es_cobrable, '', $formato_total);
									$ws->writeFormula($filas, $col_cobrable, "=SUM($col_formula_cobrable$primera_fila_asunto:$col_formula_cobrable$filas)", $formato_tiempo_total);	
									$ws->write($filas, $col_tarifa_hh, '', $formato_total);
									$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo$primera_fila_asunto:$col_formula_valor_trabajo$filas)", $formato_moneda_total);
									$filas += 2;
								}
									
									
								
								if( $cont_tramites > 0 )
									{
										// Buscar todos los trámites de este cobro/asunto
										$where_tramites = " 1 ";
										if($opc_ver_asuntos_separados)
											$where_tramites .= " AND tramite.codigo_asunto ='".$asunto->fields['codigo_asunto']."' ";
											
										$query_tramites = "SELECT SQL_CALC_FOUND_ROWS *,
																		tramite.id_tramite,
																		tramite.fecha,
																		glosa_tramite,
																		tramite.id_moneda_tramite,
																		username as usr_nombre,
																		tramite.descripcion,
																		tramite.tarifa_tramite as tarifa,
																		tramite.duracion,
																		tramite.id_cobro,
																		tramite.codigo_asunto,
																		cliente.glosa_cliente,
																		prm_moneda.simbolo AS simbolo,
																		cobro.id_moneda AS id_moneda_asunto,
																		asunto.id_asunto AS id,
																		asunto.codigo_asunto_secundario as codigo_asunto_secundario, 
																		cobro.estado AS estado_cobro,
																		DATE_FORMAT(duracion, '%H:%i') AS duracion,
																		TIME_TO_SEC(duracion)/3600 AS duracion_horas,
																		DATE_FORMAT(cobro.fecha_cobro, '%e-%c-%x') AS fecha_cobro
																		FROM tramite
																		JOIN asunto ON tramite.codigo_asunto=asunto.codigo_asunto
																		JOIN contrato ON asunto.id_contrato=contrato.id_contrato
																		JOIN prm_moneda ON contrato.id_moneda_tramite=prm_moneda.id_moneda
																		JOIN usuario ON tramite.id_usuario=usuario.id_usuario
																		JOIN tramite_tipo ON tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
																		LEFT JOIN cliente ON asunto.codigo_cliente=cliente.codigo_cliente
																		LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
																		WHERE $where_tramites AND tramite.id_cobro='".$cobro->fields['id_cobro']."'
																		ORDER BY fecha ASC";
			
												$lista_tramites = new ListaTramites($sesion, '', $query_tramites);
							
												// Encabezado de la tabla de trámites 
												$filas ++;
												#$ws->write($filas++, $col_id_trabajo, Utiles::GlosaMult($sesion, 'titulo', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_encabezado);
												$ws->write($filas, $col_id_trabajo, Utiles::GlosaMult($sesion, 'id_trabajo', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												$ws->write($filas, $col_fecha_dia, Utiles::GlosaMult($sesion, 'fecha_dia', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												$ws->write($filas, $col_fecha_mes, Utiles::GlosaMult($sesion, 'fecha_mes', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												$ws->write($filas, $col_fecha_anyo, Utiles::GlosaMult($sesion, 'fecha_anyo', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												$ws->write($filas, $col_abogado, Utiles::GlosaMult($sesion, 'abogado', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												if(!$opc_ver_asuntos_separados)
													$ws->write($filas, $col_abogado+1, Utiles::GlosaMult($sesion, 'asunto', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												if($opc_ver_solicitante)
													$ws->write($filas, $col_solicitante,'',$formato_titulo);
												$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												if($opc_ver_horas_trabajadas)
													$ws->write($filas, $col_duracion_trabajada, '', $formato_titulo);
												$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'duracion', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												if($cobro->fields['forma_cobro']=='RETAINER' || $cobro->fields['forma_cobro']=='PROPORCIONAL' || $cobro->fields['forma_cobro']=='FLAT FEE' )
													$ws->write($filas, $col_duracion_retainer, '', $formato_titulo);
												//if($opc_ver_cobrable)
													$ws->write($filas, $col_cobrable, '', $formato_titulo);
												$ws->write($filas, $col_tarifa_hh, '', $formato_titulo);
												$ws->write($filas, $col_valor_trabajo, Utiles::GlosaMult($sesion, 'valor', 'Listado de trámites', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'),$formato_titulo);
												++$filas;
												$fila_inicio_tramites = $filas + 1;
												
												// Contenido de la tabla de trámites
												for($i=0;$i<$lista_tramites->num;$i++)
												{
													$tramite = $lista_tramites->Get($i);
													list($h,$m,$s) = split(':',$tramite->fields['duracion']);
													if($h+$m > 0)
														{
														if( $h > 9 )
															$duracion = $h.':'.$m;
														else
															$duracion = substr($h,1,1).':'.$m;
														}
													else
														$duracion='-';
									
													$ws->write($filas, $col_id_trabajo, $tramite->fields['id_tramite'], $formato_normal);
													$f = explode('-',$tramite->fields['fecha']);
													$ws->write($filas,$col_fecha_dia,$f[2],$formato_normal);
													$ws->write($filas,$col_fecha_mes,$f[1],$formato_normal);
													$ws->write($filas,$col_fecha_anyo,$f[0],$formato_normal);

													//$ws->write($filas, $col_fecha, Utiles::sql2fecha($tramite->fields['fecha'], $idioma->fields['formato_fecha']), $formato_normal);
													$ws->write($filas, $col_abogado, $tramite->fields['username'], $formato_normal);
													
													if(!$opc_ver_asuntos_separados)
														$ws->write($filas, $col_abogado+1, substr($tramite->fields['codigo_asunto'], -4), $formato_descripcion);
													if($opc_ver_solicitante)
														$ws->write($filas, $col_solicitante,'',$formato_normal);
													$ws->write($filas, $col_descripcion, $tramite->fields['glosa_tramite'].' - '.$tramite->fields['descripcion'], $formato_descripcion);
													if($opc_ver_horas_trabajadas)
														$ws->write($filas, $col_duracion_trabajada, '', $formato_tiempo);
													$ws->write($filas, $col_tarificable_hh, $duracion, $formato_tiempo);
													if($cobro->fields['forma_cobro']=='RETAINER' || $cobro->fields['forma_cobro']=='PROPORCIONAL' || $cobro->fields['forma_cobro']=='FLAT FEE')
														$ws->write($filas, $col_duracion_retainer, '', $formato_normal);
													//if($opc_ver_cobrable)
														$ws->write($filas, $col_cobrable, '', $formato_normal);
													if( $tramite->fields['id_moneda_tramite']==$opc_moneda_total )
														$ws->write($filas, $col_tarifa_hh, '',$formato_normal);
													else
														$ws->writeNumber($filas, $col_tarifa_hh,$tramite->fields['tarifa'], $formato_moneda);
													$ws->writeNumber($filas, $col_valor_trabajo, ($tramite->fields['tarifa']*$cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio'])/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio'], $formato_moneda_gastos);
									
													++$filas;
												}
												
									// Totales de los trámites:
									$ws->write($filas, $col_id_trabajo, __('Total'), $formato_total);
									$ws->write($filas, $col_fecha_dia, '', $formato_total);
									$ws->write($filas, $col_fecha_mes, '', $formato_total);
									$ws->write($filas, $col_fecha_anyo, '', $formato_total);
									$ws->write($filas, $col_abogado, '', $formato_total);
									if(!$opc_ver_asuntos_separados)
										$ws->write($filas, $col_abogado+1, '', $formato_total);
									if($opc_ver_solicitante)
										$ws->write($filas, $col_solicitante,'',$formato_total);
									$ws->write($filas, $col_descripcion, '', $formato_total);
									if($opc_ver_horas_trabajadas && $col_duracion_trabajada != $col_descripcion+1 )
											$ws->write($filas, $col_duracion_trabajada, '', $formato_tiempo_total);

									$col_formula_tem=Utiles::NumToColumnaExcel($col_tarificable_hh);
									$ws->writeFormula($filas, $col_descripcion+1, "=SUM($col_formula_tem$fila_inicio_tramites:$col_formula_tem$filas)", $formato_tiempo_total);
									if($col_tarificable_hh != $col_descripcion+1) $ws->write($filas, $col_tarificable_hh, $tiempo_final, $formato_tiempo_total);
									if($cobro->fields['forma_cobro']=='RETAINER' || $cobro->fields['forma_cobro']=='PROPORCIONAL' || $cobro->fields['forma_cobro']=='FLAT FEE')
										$ws->write($filas, $col_duracion_retainer, '', $formato_tiempo_total);
									//if($opc_ver_cobrable)
										$ws->write($filas, $col_cobrable, '', $formato_total);
									$ws->write($filas, $col_tarifa_hh, '', $formato_total);
									$col_formula_temp=Utiles::NumToColumnaExcel($col_valor_trabajo);
									$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_temp$fila_inicio_tramites:$col_formula_temp$filas)", $formato_moneda_gastos_total);
						
									$filas += 2;
									}
								}
						// Si se ven los asuntos por separado avanza al proximo
						// Si no salga del while
						if( $opc_ver_asuntos_separados )
							$cont_asuntos++;
						else
							break;
					}
					
					if( count($cobro->asuntos) == 0 || !$cobro_tiene_trabajos )
					{
						$ws->write($filas++,$col_descripcion,'No existen trabajos asociados a este cobro.',$formato_instrucciones10);
						$filas += 2;
					}
				
				if( ( $opc_ver_profesional || $cobro->fields['opc_ver_profesional'] ) && is_array($detalle_profesional) )
					{
						// Si el resumen va al principio cambiar el índice de las filas.
						if($mostrar_resumen_de_profesionales)
						{
							$filas2 = $filas;
							$filas = $fila_inicio_resumen_profesional;
						}
			
						// Escribir las condiciones (ocultas) para poder usar DSUM en las fórmulas
						$filas+=2;
						$contador = 0;
						foreach($detalle_profesional as $id => $data)
							{
								$ws->write($filas, $col_fecha_ini + $contador, __('NO MODIFICAR ESTA COLUMNA'));
								$ws->write($filas+1, $col_fecha_ini + $contador, "$id");
								++$contador;
							}
							
						// Con esto se ocultan las filas con los id de los abogados.
						$ws->setRow($filas, 0, 0, 1);
						$ws->setRow($filas+1, 0, 0, 1);
			
						// Encabezado
						$filas+=2;
						$ws->write($filas++, $col_descripcion, Utiles::GlosaMult($sesion, 'titulo', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
						$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'nombre', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
						if($opc_ver_horas_trabajadas)
						{
							$ws->write($filas, $col_duracion_trabajada, Utiles::GlosaMult($sesion, 'horas_trabajadas', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
							$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'horas_cobrables', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
						}
						else
							$ws->write($filas, $col_tarificable_hh, Utiles::GlosaMult($sesion, 'horas_trabajadas', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
						if($col_duracion_retainer)
							$ws->write($filas, $col_duracion_retainer, Utiles::GlosaMult($sesion, 'horas_retainer', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
						//if($opc_ver_cobrable)
							$ws->write($filas, $col_cobrable, Utiles::GlosaMult($sesion, 'horas_tarificadas', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
						$ws->write($filas, $col_tarifa_hh, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'tarifa_hh', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_titulo);
								$ws->write($filas, $col_valor_trabajo, str_replace('%glosa_moneda%', $simbolo_moneda, Utiles::GlosaMult($sesion, 'total', 'Detalle profesional', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')), $formato_titulo);
						++$filas;
						
						// Para las fórmulas en los totales
						$fila_inicio_detalle_profesional = $filas+1;
			
						// Rellenar la tabla visible
						// Se basa en la fórmula de excel DSUM(datos; columna a sumar; condiciones)
						// Para usar esta formula hay que definir el tamaño de la matriz en cual se encuentran los datos
						$contador = 0;
						if($opc_ver_horas_trabajadas)
							$inicio_datos = "$col_formula_duracion_trabajada".($primera_fila_primer_asunto+1);
						else
							$inicio_datos = "$col_formula_duracion_cobrable".($primera_fila_primer_asunto+1);
						$fin_datos = "$col_formula_id_abogado".($ultima_fila_ultimo_asunto+1);
						if( is_array($detalle_profesional) )
							{
								foreach($detalle_profesional as $id => $data)
									{
										$ws->write($filas, $col_descripcion, $data['nombre'], $formato_normal);
										if($opc_ver_horas_trabajadas)
											$ws->writeFormula($filas, $col_duracion_trabajada, "=DSUM($inicio_datos:$fin_datos; \"".Utiles::GlosaMult($sesion, 'duracion_trabajada', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')."\"; ".Utiles::NumToColumnaExcel($col_fecha_ini+$contador).($fila_inicio_detalle_profesional-4).":".Utiles::NumToColumnaExcel($col_fecha_ini+$contador).($fila_inicio_detalle_profesional-3).")", $formato_tiempo);
										$ws->writeFormula($filas, $col_tarificable_hh, "=DSUM($inicio_datos:$fin_datos; \"".Utiles::GlosaMult($sesion, 'duracion_cobrable', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')."\"; ".Utiles::NumToColumnaExcel($col_fecha_ini+$contador).($fila_inicio_detalle_profesional-4).":".Utiles::NumToColumnaExcel($col_fecha_ini+$contador).($fila_inicio_detalle_profesional-3).")", $formato_tiempo);
										if($col_duracion_retainer)
											$ws->writeFormula($filas, $col_duracion_retainer, "=DSUM($inicio_datos:$fin_datos; \"".Utiles::GlosaMult($sesion, 'duracion_retainer', 'Listado de trabajos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo')."\"; ".Utiles::NumToColumnaExcel($col_fecha_ini+$contador).($fila_inicio_detalle_profesional-4).":".Utiles::NumToColumnaExcel($col_fecha_ini+$contador).($fila_inicio_detalle_profesional-3).")", $formato_tiempo);
										//if($opc_ver_cobrable)
											$ws->writeFormula($filas, $col_cobrable, "=MAX($col_formula_duracion_cobrable".($filas+1).($col_duracion_retainer?" - $col_formula_duracion_retainer".($filas+1):'').";0)", $formato_tiempo);
										$ws->writeNumber($filas, $col_tarifa_hh, $data['tarifa'], $formato_moneda);
										if($col_duracion_retainer)
											$ws->writeFormula($filas, $col_valor_trabajo, "=MAX(24*$col_formula_duracion_cobrable".($filas+1)."-24*$col_formula_duracion_retainer".($filas+1).";0)*$col_formula_tarifa_hh".($filas+1), $formato_moneda);
										else
											$ws->writeFormula($filas, $col_valor_trabajo, "=24*$col_formula_duracion_cobrable".($filas+1)."*$col_formula_tarifa_hh".($filas+1), $formato_moneda);
										++$filas;
										++$contador;
									}
							}	
						// Fórmulas para los totales
						$ws->write($filas, $col_descripcion, __('Total'), $formato_total);
						if($opc_ver_horas_trabajadas)
							$ws->writeFormula($filas, $col_duracion_trabajada, "=SUM($col_formula_duracion_trabajada$fila_inicio_detalle_profesional:$col_formula_duracion_trabajada$filas)", $formato_tiempo_total);
						$ws->writeFormula($filas, $col_tarificable_hh, "=SUM($col_formula_duracion_cobrable$fila_inicio_detalle_profesional:$col_formula_duracion_cobrable$filas)", $formato_tiempo_total);
						if($col_duracion_retainer)
							$ws->write($filas, $col_duracion_retainer, "=SUM($col_formula_duracion_retainer$fila_inicio_detalle_profesional:$col_formula_duracion_retainer$filas)", $formato_tiempo_total);
						//if($opc_ver_cobrable)
							$ws->writeFormula($filas, $col_cobrable, "=SUM($col_formula_cobrable$fila_inicio_detalle_profesional:$col_formula_cobrable$filas)", $formato_tiempo_total);
						$ws->write($filas, $col_tarifa_hh, '', $formato_total);
						$ws->writeFormula($filas, $col_valor_trabajo, "=SUM($col_formula_valor_trabajo$fila_inicio_detalle_profesional:$col_formula_valor_trabajo$filas)", $formato_moneda_total);
						if( $cobro->fields['forma_cobro'] == 'FLAT FEE' )
							$ws->write($filas, $col_valor_trabajo+2, '', $formato_normal);
						//$ws->writeFormula($filas, $col_cobrable, "=SUM($col_formula_cobrable$fila_inicio_detalle_profesional:$col_formula_cobrable$filas)", $formato_moneda_total);
			
						// Borrar el resumen para que el siguiente asunto parta de cero
						unset($detalle_profesional);
						
						// Si el resumen va al principio cambiar el índice de las filas.
						if($mostrar_resumen_de_profesionales)
						{
							$filas = $filas2;
						}
					}
					// Borrar la variable primera_fila_primer asunto para que se define de nuevo en el siguiente cobro
						unset($primera_fila_primer_asunto);
						
					if( $cont_gastos_cobro > 0 && $opc_ver_gastos )
						{
							// Encabezado de la tabla de gastos
							$filas++;
							$ws->write($filas++, $col_descripcion-1, Utiles::GlosaMult($sesion, 'titulo', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_encabezado);
							$ws->write($filas, $col_descripcion-1, Utiles::GlosaMult($sesion, 'fecha', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
							$ws->write($filas, $col_descripcion, Utiles::GlosaMult($sesion, 'descripcion', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
							$ws->mergeCells($filas, $col_descripcion+1, $filas, $col_descripcion+2);
							$ws->write($filas, $col_descripcion+1, '',$formato_titulo);
							$ws->write($filas, $col_descripcion+2, Utiles::GlosaMult($sesion, 'monto', 'Listado de gastos', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_titulo);
							++$filas;
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
								$ws->write($filas, $col_descripcion-1, Utiles::sql2fecha($gasto->fields['fecha'], $idioma->fields['formato_fecha']), $formato_normal);
								$ws->write($filas, $col_descripcion, $gasto->fields['descripcion'], $formato_descripcion);
								if($gasto->fields['egreso'])
									$ws->writeNumber($filas, $col_descripcion+1, $gasto->fields['monto_cobrable']*($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $formato_moneda_gastos);
								else
									$ws->writeNumber($filas, $col_descripcion+1, -$gasto->fields['monto_cobrable']*($cobro_moneda->moneda[$gasto->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']), $formato_moneda_gastos);
								$ws->mergeCells($filas, $col_descripcion+1, $filas, $col_descripcion+2);
								++$filas;
							}
					
							// Total de gastos
							$ws->write($filas, $col_descripcion-1, __('Total'), $formato_total);
							$ws->write($filas, $col_descripcion, '', $formato_total);
							$col_formula_temp = Utiles::NumToColumnaExcel($col_descripcion+1);
							$ws->writeFormula($filas, $col_descripcion+1, "=SUM($col_formula_temp$fila_inicio_gastos:$col_formula_temp$filas)", $formato_moneda_gastos_total);
							$ws->mergeCells($filas, $col_descripcion+1, $filas, $col_descripcion+2);
							$ws->write($filas, $col_descripcion+2, '', $formato_total);
						}
					
				if( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsarResumenExcel') ) || ( method_exists('Conf','UsarResumenExcel') && Conf::UsarResumenExcel() ) ) 
					{
						// Resumen con información la forma de cobro y en caso de CAP una lista de los otros cobro que estan adentro de este CAP.
						$filas += 3;
						// Informacion general sobre el cobro:
						$ws->mergeCells($filas,$col_fecha_ini,$filas,$col_fecha_ini+1);
						$ws->write($filas,$col_fecha_ini, 'Información según ' . __('Cobro') . ':',$formato_resumen_text_titulo);
						$ws->write($filas++,$col_fecha_ini+1,'',$formato_resumen_text);
						$ws->write($filas,$col_fecha_ini, 'Número ' . __('Cobro') . '',$formato_resumen_text);
						$ws->writeNumber($filas++,$col_fecha_ini+1, $cobro->fields['id_cobro'],$numeros);
						$ws->write($filas,$col_fecha_ini, 'Número Factura',$formato_resumen_text_amarillo);
						$ws->writeNumber($filas++,$col_fecha_ini+1,$cobro->fields['documento'],$numeros_amarillo);
						$ws->write($filas,$col_fecha_ini, 'Forma ' . __('Cobro') . '',$formato_resumen_text);
						$ws->write($filas++,$col_fecha_ini+1, $cobro->fields['forma_cobro'],$formato_resumen_text_derecha);
						$ws->write($filas,$col_fecha_ini, 'Periodo ' . __('Cobro') . '',$formato_resumen_text);
						$ws->write($filas++,$col_fecha_ini+1, $cobro->fields['fecha_ini'].' - '.$cobro->fields['fecha_fin'],$formato_resumen_text_derecha); 
						$ws->write($filas,$col_fecha_ini, 'Total ' . __('Cobro') . '',$formato_resumen_text);
						$ws->writeNumber($filas++,$col_fecha_ini+1,$cobro->fields['monto']*$cobro_moneda->moneda[$cobro->fields['id_moneda']]['tipo_cambio']/$cobro_moneda->moneda[$cobro->fields['opc_moneda_total']]['tipo_cambio']+$cobro->fields['monto_gastos'],$formato_moneda_resumen_cobro);
						
						// Si la forma del cobro es cap imprime una lista de todos los cobros anteriores incluido en este CAP:
						if($cobro->fields['forma_cobro']=='CAP')
							{
								$ws->write($filas, $col_fecha_ini, Utiles::GlosaMult($sesion, 'monto_cap_inicial', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_resumen_text);
								$ws->writeNumber($filas++, $col_fecha_ini+1, $contrato->fields['monto'], $formato_moneda_monto_resumen);
								$fila_inicial = $filas;
								$query_cob = "SELECT cobro.id_cobro, cobro.documento, ((cobro.monto_subtotal-cobro.descuento)*cm2.tipo_cambio)/cm1.tipo_cambio 
																FROM cobro 
																JOIN contrato ON cobro.id_contrato=contrato.id_contrato 
																JOIN cobro_moneda as cm1 ON cobro.id_cobro=cm1.id_cobro AND cm1.id_moneda=contrato.id_moneda_monto  
																JOIN cobro_moneda as cm2 ON cobro.id_cobro=cm2.id_cobro AND cm2.id_moneda=cobro.id_moneda 
															 WHERE cobro.id_contrato=".$cobro->fields['id_contrato']." 
															 	 AND cobro.forma_cobro='CAP'"; 
								$resp_cob = mysql_query($query_cob, $sesion->dbh) or Utiles::errorSQL($query_cob,__FILE__,__LINE__,$sesion->dbh); 
								while(list($id_cobro, $id_factura, $monto_cap)=mysql_fetch_array($resp_cob))
									{
										$monto_cap = number_format($monto_cap, $moneda_monto->fields['cifras_decimales'],'.','');
										$ws->write($filas, $col_fecha_ini, __('Factura N°').' '.$id_factura, $formato_resumen_text);
										$ws->writeNumber($filas++, $col_fecha_ini+1, $monto_cap, $formato_moneda_monto_resumen);
									}
								$ws->write($filas, $col_fecha_ini, Utiles::GlosaMult($sesion, 'monto_cap_restante', 'Resumen', "glosa_$lang", 'prm_excel_cobro', 'nombre_interno', 'grupo'), $formato_resumen_text);
								$formula_cap_restante = "$col_formula_abogado$fila_inicial - SUM($col_formula_abogado".($fila_inicial+1).":$col_formula_abogado$filas)";
								$ws->writeFormula($filas++, $col_fecha_ini+1, "=IF($formula_cap_restante>0, $formula_cap_restante, 0)", $formato_moneda_monto_resumen);
							}
					}
				// Si el cobro es RETAINER o PROPORCIONAL vuelve la definición de las columnas al 
				// estado normal para patir en cero en el siguiente cobro
				if(($cobro->fields['forma_cobro']=='RETAINER' || $cobro->fields['forma_cobro']=='PROPORCIONAL' || $cobro->fields['forma_cobro']=='FLAT FEE'))
					{
						unset($col_duracion_retainer);
						unset($col_formula_duracion_retainer); 
						//if($opc_ver_cobrable)
							$col_cobrable--;
						$col_tarifa_hh--;
						$col_valor_trabajo--;
						$col_id_abogado--;
						
						$col_formula_tarifa_hh = Utiles::NumToColumnaExcel($col_tarifa_hh);
						$col_formula_valor_trabajo = Utiles::NumToColumnaExcel($col_valor_trabajo);
						//if($opc_ver_cobrable)
							$col_formula_cobrable = Utiles::NumToColumnaExcel($col_cobrable);
						$col_formula_id_abogado = Utiles::NumToColumnaExcel($col_id_abogado);
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
