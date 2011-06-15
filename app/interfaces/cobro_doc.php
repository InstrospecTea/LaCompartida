<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../app/classes/Funciones.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/../app/classes/Asunto.php';
	require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/Gasto.php';
	require_once Conf::ServerDir().'/../app/classes/DocGenerator.php';
	require_once Conf::ServerDir().'/../app/classes/TemplateParser.php';



	$sesion = new Sesion(array('COB'));
	$pagina = new Pagina($sesion);

	// Carga de datos del cobro
	$cobro = new Cobro($sesion);
	//$cobro->Load(this->fields['id_cobro'];

	if(!$cobro->Load($id_cobro))
		$pagina->FatalError('Cobro inválido');

	$cobro->LoadAsuntos();
	$comma_separated = implode("','", $cobro->asuntos);

	if( $lang == '' )
		$lang = 'es';
	require_once Conf::ServerDir()."/lang/$lang.php";

	//Usa el segundo formato de nota de cobro
	//solo si lo tiene definido en el conf y solo tiene gastos
	$css_cobro=1;
	$solo_gastos=true;
	for($k=0;$k<count($cobro->asuntos);$k++)
	{
	
		$asunto = new Asunto($sesion);
		$asunto->LoadByCodigo($cobro->asuntos[$k]);
		$query = "SELECT SUM(TIME_TO_SEC(duracion))
							FROM trabajo AS t2
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
							WHERE t2.cobrable = 1
							AND t2.codigo_asunto='".$asunto->fields['codigo_asunto']."'
							AND cobro.id_cobro='".$cobro->fields['id_cobro']."'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		list($total_monto_trabajado) = mysql_fetch_array($resp);
		if( $asunto->fields['trabajos_total_duracion'] > 0 )
		{
			$solo_gastos=false;
		}
	}
	if( method_exists('Conf','GetConf') )
	{
		if($solo_gastos && Conf::GetConf($sesion,'CSSSoloGastos'))
			$css_cobro=2;
	}
	else if (method_exists('Conf','CSSSoloGastos'))
	{
		if($solo_gastos && Conf::CSSSoloGastos())
			$css_cobro=2;
	}

	#$cobro->GuardarCobro();

	$html .= $cobro->GeneraHTMLCobro(false,$cobro->fields['id_formato']);
	$cssData = UtilesApp::TemplateCartaCSS($sesion,$cobro->fields['id_carta']);
	$cssData .= UtilesApp::CSSCobro($sesion);
	$doc = new DocGenerator( $html, $cssData, $cobro->fields['opc_papel'], $cobro->fields['opc_ver_numpag'] ,'PORTRAIT',1.5,2.0,2.0,2.0,$cobro->fields['estado'], $cobro->fields['id_formato']);
	$valor_unico=substr(time(),-3);

	//echo '<style>'.$cssData.'</style>'.$html;
	//exit;

	$doc->output('cobro_'.$id_cobro.'_'.$valor_unico.'.doc');
	exit;






# PRobablemente hay que borrar todo para abajo, dejar un tiempo para ver si lo hacemos 28-5-08
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($cobro->fields['codigo_cliente']);

	
	//	Moneda total (moneda en la cual se imprimirá el total)
	//	tipo cambio corresponde al que se ha guardado
	//	de acuerdo a la moneda en tabla cobro_moneda
	
	$cobro_moneda = new CobroMoneda($sesion);
	$moneda_total = new Objeto($sesion,'','','prm_moneda','id_moneda');
	if($opc_moneda_total) #Moneda impresión DOC
	{
		$tipo_cambio_moneda_total = $cobro_moneda->CobroIdMoneda($id_cobro,$opc_moneda_total );
		$moneda_total->Load($opc_moneda_total);
	}
	else
	{
		$tipo_cambio_moneda_total = $cobro_moneda->CobroIdMoneda($id_cobro,$cobro->fields['opc_moneda_total']);
		$moneda_total->Load($cobro->fields['opc_moneda_total']);
	}

	if( $guardar_opciones == '1' )
	{
		$cobro->Edit('opc_ver_modalidad',isset($opc_ver_modalidad)?'1':'0');
		$cobro->Edit('opc_ver_profesional',isset($opc_ver_profesional)?'1':'0');
		$cobro->Edit('opc_ver_gastos',isset($opc_ver_gastos)?'1':'0');
		$cobro->Edit('opc_ver_descuento',isset($opc_ver_descuento)?'1':'0');
		$cobro->Edit('opc_ver_numpag',isset($opc_ver_numpag)?'1':'0');
		$cobro->Edit('opc_papel',$opc_papel);
		if($opc_moneda_total) #Moneda impresión DOC
		{
			$cobro->Edit('opc_moneda_total',$opc_moneda_total);
		}
		$cobro->Write();
	}

	$cobro->LoadAsuntos();
	$comma_separated = implode("','", $cobro->asuntos);

	// Idiomas
	if( $lang == '' ) $lang = 'es';
	require_once Conf::ServerDir()."/../app/lang/$lang.php";

	$idioma = new Objeto($sesion,'','','prm_idioma','codigo_idioma');
	$idioma->Load($lang);

	// Moneda
	$moneda = new Objeto($sesion,'','','prm_moneda','id_moneda');
	$moneda->Load($cobro->fields['id_moneda']);
	$moneda_base = new Objeto($sesion,'','','prm_moneda','id_moneda');
	$moneda_base->Load($cobro->fields['id_moneda_base']);

	//Moneda cliente
	$moneda_cli = new Objeto($sesion,'','','prm_moneda','id_moneda');
	$moneda_cli->Load($cliente->fields['id_moneda']);
	$moneda_cliente_cambio = $cobro_moneda->CobroIdMoneda($id_cobro,$cliente->fields['id_moneda'] );

	########### CARTA ###############
	$templateData_carta = UtilesApp::TemplateCarta($sesion,$id_carta);
	$cssData = UtilesApp::TemplateCartaCSS($sesion,$id_carta);
	$parser_carta = new TemplateParser($templateData_carta);
	function GenerarDocumentoCarta( &$parser_carta, $theTag='' )
	{
		global $lang, $parser_carta, $moneda_cliente_cambio, $moneda_cli, $sesion, $idioma, $moneda, $moneda_base, $cobro, $asunto, $trabajo, $profesionales, $gasto, $totales, $moneda_total, $tipo_cambio_moneda_total, $cliente, $id_carta;

		if( !isset($parser_carta->tags[$theTag]) )
			return;

		$html2 = $parser_carta->tags[$theTag];

		switch( $theTag )
		{
			case 'CARTA':
				if (method_exists('Conf','GetConf'))
				{
					$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
					$PdfLinea2 = Conf::GetConf($sesion, 'PdfLinea2');
				}
				else
				{
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea2();
				}

				$html2 = str_replace('%logo_carta%', Conf::Server().'/'.Conf::ImgDir(), $html2);
				$html2 = str_replace('%direccion%', $PdfLinea1, $html2);
				$html2 = str_replace('%titulo%', $PdfLinea1, $html2);
				$html2 = str_replace('%subtitulo%', $PdfLinea2, $html2);

				$html2 = str_replace('%FECHA%', GenerarDocumentoCarta($parser_carta,'FECHA'), $html2);
				$html2 = str_replace('%ENVIO_DIRECCION%', GenerarDocumentoCarta($parser_carta,'ENVIO_DIRECCION'), $html2);
				$html2 = str_replace('%DETALLE%', GenerarDocumentoCarta($parser_carta,'DETALLE'), $html2);
				$html2 = str_replace('%ADJ%', GenerarDocumentoCarta($parser_carta,'ADJ'), $html2);
				$html2 = str_replace('%PIE%', GenerarDocumentoCarta($parser_carta,'PIE'), $html2);
			break;

			case 'FECHA':
				if( $lang == 'es' )
					$fecha_lang = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %d, %Y'));
				else
					$fecha_lang = date('F d, Y');

				$fecha_ingles = date('F d, Y');

				$html2 = str_replace('%fecha%', $fecha_lang, $html2);
				$html2 = str_replace('%fecha_ingles%', $fecha_ingles, $html2);
			break;

			case 'ENVIO_DIRECCION':
				$html2 = str_replace('%glosa_cliente%', strtoupper($cliente->fields['glosa_cliente']), $html2);
				$html2 = str_replace('%valor_direccion%', $cliente->fields['dir_calle']." ".$cliente->fields['dir_numero'].'<br>'.Utiles::Glosa( $sesion, $cliente->fields['dir_comuna'], 'glosa_comuna', 'prm_comuna','id_comuna'), $html2);
				$html2 = str_replace('%At.%', __('Sr.').' '.$cliente->fields['nombre_contacto'], $html2);
				$asuntos_doc = '';
				for($k=0;$k<count($cobro->asuntos);$k++)
				{
					$asunto = new Asunto($sesion);
					$asunto->LoadByCodigo($cobro->asuntos[$k]);
					$espace = $k<count($cobro->asuntos)-1 ? ', ' : '';
					$asuntos_doc .= $asunto->fields['glosa_asunto'].''.$espace;
				}
				$html2 = str_replace('%Asunto%', $asuntos_doc, $html2);
				$html2 = str_replace('%Our Ref.%', $cobro->fields['id_cobro'], $html2);
				$html2 = str_replace('%pais%', 'Chile', $html2);
			break;

			case 'DETALLE':
				//
				//	Total Gastos
				//	se suma cuando idioma es inglés
				//	se presenta separadamente cuando es en español
				//
				$total_gastos = 0;
				$query = "SELECT SQL_CALC_FOUND_ROWS *
									FROM cta_corriente
									WHERE id_cobro='".$cobro->fields['id_cobro']."' and egreso > 0
									ORDER BY fecha ASC";
				$lista_gastos = new ListaGastos($sesion,'',$query);
				for($i=0;$i<$lista_gastos->num;$i++)
				{
					$gasto = $lista_gastos->Get($i);
					$moneda_gasto = new Objeto($sesion,'','','prm_moneda','id_moneda');
					$moneda_gasto->Load($gasto->fields['id_moneda']);
					$total_gastos += ((double)$gasto->fields['egreso'] * (double)$moneda_gasto->fields['tipo_cambio'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				}
				$monto_moneda = ((double)$cobro->fields['monto']*(double)$cobro->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				$monto_moneda_sin_gasto = ((double)$cobro->fields['monto']*(double)$cobro->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				$monto_moneda_con_gasto = ((double)$cobro->fields['monto']*(double)$cobro->fields['tipo_cambio_moneda'])/($moneda_cliente_cambio > 0 ? $moneda_cliente_cambio : $moneda_cli->fields['tipo_cambio']);
				$monto_moneda_con_gasto += $total_gastos;
				if( $lang != 'es' )
					$monto_moneda += $total_gastos;
				else if($total_gastos > 0)
					$html2 = str_replace('%monto_gasto%', $moneda_cli->fields['simbolo'].' '.number_format($total_gastos,$moneda_cli->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);

				$parrafo_1 = str_replace('%num_factura%', $cobro->fields['documento'], $parrafo_1);

				$datefrom = strtotime($cobro->fields['fecha_ini'], 0);
				$dateto = strtotime($cobro->fields['fecha_fin'], 0);
				$difference = $dateto - $datefrom; //Dif segundos
				$months_difference = floor($difference / 2678400);
				while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto)
				{
					$months_difference++;
				}
					$months_difference--;
				$datediff = $months_difference;

				
				//	Mostrando fecha según idioma
				
				if( $lang == 'es' )
					$fecha_diff = $datediff > 0 ? ucfirst(Utiles::sql3fecha($cobro->fields['fecha_ini'],'%B %Y')).' '.__('y').' '.ucfirst(Utiles::sql3fecha($cobro->fields['fecha_fin'],'%B %Y')) : ucfirst(Utiles::sql3fecha($cobro->fields['fecha_ini'],'%B %Y'));
				else
					$fecha_diff = $datediff > 0 ? ucfirst(date('F Y', strtotime($cobro->fields['fecha_ini']))).' '.__('y').' '.ucfirst(date('F Y', strtotime($cobro->fields['fecha_fin']))) : '';

				if($fecha_diff == 'No existe fecha')
					$fecha_diff = ucfirst(Utiles::sql3fecha(date('Y-m-d'),'%B %Y'));

				$html2 = str_replace('%num_factura%', $cobro->fields['documento'], $html2);
				$html2 = str_replace('%fecha%', $fecha_diff, $html2);
				$html2 = str_replace('%monto%', $moneda_cli->fields['simbolo'].' '.number_format($monto_moneda,$moneda_cli->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_sin_gasto%', $moneda_cli->fields['simbolo'].' '.number_format($monto_moneda_sin_gasto,$moneda_cli->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);
				$html2 = str_replace('%monto_con_gasto%', $moneda_cli->fields['simbolo'].' '.number_format($monto_moneda_con_gasto,$moneda_cli->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html2);

				#Para montos solamente sea distinto a pesos $
				if($cobro->fields['id_moneda'] > 1) #!= $moneda_cli->fields['id_moneda']
				{
					$en_pesos = (double)$cobro->fields['monto']*($cobro->fields['tipo_cambio_moneda']/$cobro->fields['tipo_cambio_moneda_base']);
					$html2 = str_replace('%monto_en_pesos%', 'equivalente a $'.number_format($en_pesos,0,',','.'), $html2);
				}
				else
					$html2 = str_replace('%monto_en_pesos%', '', $html2);

				#si hay gastos se muestran
				if($total_gastos > 0)
				{
					$gasto_en_pesos = ($total_gastos*($cobro->fields['tipo_cambio_moneda']/$cobro->fields['tipo_cambio_moneda_base']))/$tipo_cambio_moneda_total;
					$txt_gasto = "Asimismo, se agregan los gastos por la suma total de";
					$html2 = str_replace('%monto_gasto_separado%', $txt_gasto.' $'.number_format($gasto_en_pesos,0,',','.'), $html2);
				}
				else
					$html2 = str_replace('%monto_gasto_separado%', '', $html2);
			break;

			case 'ADJ':
				$html2 = str_replace('%nro_factura%', $cobro->fields['documento'], $html2);
				$html2 = str_replace('%num_letter%', $cobro->fields['id_cobro'], $html2);
				$html2 = str_replace('%cliente_fax%', '('.$cliente->fields['cod_fono_contacto'].')'.$cliente->fields['fono_contacto'], $html2);
			break;

			case 'PIE':
				if (method_exists('Conf','GetConf'))
				{
					$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
					$PdfLinea3 = Conf::GetConf($sesion, 'PdfLinea3');
				}
				else
				{
					$PdfLinea1 = Conf::PdfLinea1();
					$PdfLinea2 = Conf::PdfLinea3();
				}

				if( method_exists('Conf','GetConf') )
					$pie_pagina = $PdfLinea2.' '.$PdfLinea3.'<br>'.Conf::GetConf($sesion,'SitioWeb').' - E-mail: '.Conf::GetConf($sesion,'Email');
				else
					$pie_pagina = $PdfLinea2.' '.$PdfLinea3.'<br>'.Conf::SitioWeb().' - E-mail: '.Conf::Email();
				$html2 = str_replace('%direccion%', $pie_pagina, $html2);
			break;
		}

		return $html2;
	}
	########### FIN CARTA ###########

	########### DOC COBRO ###########
	$templateData = UtilesApp::TemplateCobro($sesion); // Lectura del Template
	$cssData .= UtilesApp::CSSCobro($sesion);
	$parser = new TemplateParser($templateData);
	$html = GenerarDocumento($parser); 	#Genera Doc

	$doc = new DocGenerator( $html, $cssData, $cobro->fields['opc_papel'], $cobro->fields['opc_ver_numpag']==1 );
	$doc->output("cobro_$id_cobro.doc");

	function StrToNumber($str)
	{
		$legalChars = "%[^0-9\-\ ]%";
		$str=preg_replace($legalChars,"",$str);
		return number_format((float)$str,0,',','.');
	}

	function GenerarDocumento( &$parser, $theTag='INFORME' )
	{
		global $html2, $sesion, $idioma, $cliente, $moneda, $moneda_base, $cobro, $asunto, $trabajo, $profesionales, $gasto, $totales, $moneda_total, $tipo_cambio_moneda_total;

		if( !isset($parser->tags[$theTag]) )
			return;

		$html = $parser->tags[$theTag];

		switch( $theTag )
		{
		case 'INFORME':
			#INSERTANDO CARTA
			$html = str_replace('%COBRO_CARTA%', GenerarDocumentoCarta($parser_carta,'CARTA'), $html);

			if (method_exists('Conf','GetConf'))
			{
				$PdfLinea1 = Conf::GetConf($sesion, 'PdfLinea1');
				$PdfLinea2 = Conf::GetConf($sesion, 'PdfLinea2');
				$PdfLinea3 = Conf::GetConf($sesion, 'PdfLinea3');
			}
			else
			{
				$PdfLinea1 = Conf::PdfLinea1();
				$PdfLinea2 = Conf::PdfLinea2();
				$PdfLinea3 = Conf::PdfLinea3();
			}

			$html = str_replace('%logo%', Conf::LogoDoc(true), $html);
			$html = str_replace('%titulo%', $PdfLinea1, $html);
			$html = str_replace('%logo_cobro%', Conf::Server().'/'.Conf::ImgDir(), $html);
			$html = str_replace('%subtitulo%', $PdfLinea2, $html);
			$html = str_replace('%direccion%', $PdfLinea3, $html);
			$html = str_replace('%fecha%', ($cobro->fields['fecha_cobro'] == '0000-00-00' or $cobro->fields['fecha_cobro'] == '') ? Utiles::sql2fecha(date('Y-m-d'),$idioma->fields['formato_fecha']) : Utiles::sql2fecha($cobro->fields['fecha_cobro'],$idioma->fields['formato_fecha']), $html);

			$html = str_replace('%CLIENTE%', GenerarDocumento($parser,'CLIENTE'), $html);
			$html = str_replace('%DETALLE_COBRO%', GenerarDocumento($parser,'DETALLE_COBRO'), $html);
			$html = str_replace('%ASUNTOS%', GenerarDocumento($parser,'ASUNTOS'), $html);
			$html = str_replace('%GASTOS%', GenerarDocumento($parser,'GASTOS'), $html);
			$html = str_replace('%CTA_CORRIENTE%', GenerarDocumento($parser,'CTA_CORRIENTE'), $html);
			break;

		case 'CLIENTE':
			$html = str_replace('%glosa_cliente%', $cliente->fields['glosa_cliente'], $html);
			$html = str_replace('%direccion%', __('Dirección'), $html);
			$html = str_replace('%valor_direccion%', $cliente->fields['dir_calle']." ".$cliente->fields['dir_numero'], $html);
			$html = str_replace('%rut%',__('RUT'), $html);
			if($cliente->fields['rut'] != '0' || $cliente->fields['rut'] != '')
				$rut_split = split('-',$cliente->fields['rut'],2);

			#$cliente->fields['rut'] != '0' ? number_format($cliente->fields['rut'],0,'','.')."-".$cliente->fields['dv'] : __('No Aplicable')
			$html = str_replace('%valor_rut%', $rut_split[0] ? StrToNumber($rut_split[0])."-".$rut_split[1] : __('No Aplicable'), $html);
			$html = str_replace('%contacto%', __('Contacto'), $html);
			$html = str_replace('%valor_contacto%', $cliente->fields['nombre_contacto'], $html);
			$html = str_replace('%telefono%', __('Teléfono'), $html);
			$html = str_replace('%valor_telefono%', $cliente->fields['cod_fono_contacto']."-".$cliente->fields['fono_contacto'], $html);
			break;

		case 'DETALLE_COBRO':
			$horas = floor(($cobro->fields['total_minutos'])/60);
			$minutos = sprintf("%02s",$cobro->fields['total_minutos']%60);

			$detalle_modalidad = $cobro->fields['forma_cobro']=='TASA' ? '' : __('POR').' '.$moneda->fields['simbolo'].' '.number_format($cobro->fields['monto_contrato'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']);

			if( $cobro->fields['forma_cobro'] == 'RETAINER' and $cobro->fields['retainer_horas'] != '' )
				$detalle_modalidad .= '<br>'.sprintf( __('HASTA').' %s '.__('HORAS'), $cobro->fields['retainer_horas']);
			if( $cobro->fields['forma_cobro'] == 'PROPORCIONAL' and $cobro->fields['retainer_horas'] != '' )
				$detalle_modalidad .= '<br>'.sprintf( __('HASTA').' %s '.__('HORAS'), $cobro->fields['retainer_horas']);

			$html = str_replace('%glosa_cobro%', __('Detalle Cobro'), $html);
			$html = str_replace('%cobro%', __('Cobro'), $html);
			$html = str_replace('%valor_cobro%', $cobro->fields['id_cobro'], $html);
			$html = str_replace('%factura%', __('Factura'), $html);
			$html = str_replace('%nro_factura%', $cobro->fields['documento'], $html);
			$html = str_replace('%modalidad%', $cobro->fields['opc_ver_modalidad']==1 ? __('Modalidad'):'', $html);
			$html = str_replace('%valor_modalidad%', $cobro->fields['opc_ver_modalidad']==1 ? __($cobro->fields['forma_cobro']):'', $html);
			$html = str_replace('%detalle_modalidad%', $cobro->fields['opc_ver_modalidad']==1 ? $detalle_modalidad:'', $html);
			$html = str_replace('%fecha_ini%', ($cobro->fields['fecha_ini'] == '0000-00-00' or $cobro->fields['fecha_ini'] == '') ? '' : __('Fecha desde'), $html);
			$html = str_replace('%valor_fecha_ini%', ($cobro->fields['fecha_ini'] == '0000-00-00' or $cobro->fields['fecha_ini'] == '') ? '' : Utiles::sql2fecha($cobro->fields['fecha_ini'],$idioma->fields['formato_fecha']), $html);
			$html = str_replace('%fecha_fin%', ($cobro->fields['fecha_fin'] == '0000-00-00' or $cobro->fields['fecha_fin'] == '') ? '' : __('Fecha hasta'), $html);
			$html = str_replace('%valor_fecha_fin%', ($cobro->fields['fecha_fin'] == '0000-00-00' or $cobro->fields['fecha_fin'] == '') ? '' : Utiles::sql2fecha($cobro->fields['fecha_fin'],$idioma->fields['formato_fecha']), $html);

			$html = str_replace('%horas%', __('Total Horas'), $html);
			$html = str_replace('%valor_horas%', $horas.':'.$minutos, $html);
			$html = str_replace('%honorarios%', __('Total Honorarios'), $html);
			$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($cobro->fields['monto'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

			#$html = str_replace('%valor_honorarios_monedabase%', $moneda_base->fields['simbolo'].' '.number_format($cobro->fields['monto']*($cobro->fields['tipo_cambio_moneda']/$cobro->fields['tipo_cambio_moneda_base']),$moneda_base->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

			#valor en moneda previa selección para impresión
			$en_pesos = $cobro->fields['monto']*($cobro->fields['tipo_cambio_moneda']/$cobro->fields['tipo_cambio_moneda_base']);
			$total_en_moneda = ($cobro->fields['monto']*($cobro->fields['tipo_cambio_moneda']/$cobro->fields['tipo_cambio_moneda_base']))/$tipo_cambio_moneda_total;

			$html = str_replace('%valor_honorarios_monedabase%', $moneda_total->fields['simbolo'].' '.number_format($total_en_moneda,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

			$html = str_replace('%DETALLE_COBRO_DESCUENTO%', GenerarDocumento($parser,'DETALLE_COBRO_DESCUENTO'), $html);
			break;

		case 'DETALLE_COBRO_DESCUENTO':
			if( $cobro->fields['opc_ver_descuento'] == 0 )
				return '';

			$html = str_replace('%honorarios%', __('Subtotal Honorarios'), $html);
			$html = str_replace('%valor_honorarios%', $moneda->fields['simbolo'].' '.number_format($cobro->fields['monto_subtotal'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			$html = str_replace('%descuento%', __('Descuento'), $html);
			$html = str_replace('%valor_descuento%', $moneda->fields['simbolo'].' '.number_format($cobro->fields['descuento'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

			break;

		case 'ASUNTOS':
			$row_tmpl = $html;
			$html = '';

			for($k=0;$k<count($cobro->asuntos);$k++)
			{
				$asunto = new Asunto($sesion);
				$asunto->LoadByCodigo($cobro->asuntos[$k]);

				unset($GLOBALS['profesionales']);
				$profesionales = array();

				unset($GLOBALS['totales']);
				$totales = array();
				$totales['tiempo'] = 0;
				$totales['valor'] = 0;

				$row = $row_tmpl;
				$row = str_replace('%asunto%',__('Asunto'),$row);
				$row = str_replace('%glosa_asunto%', $asunto->fields['codigo_asunto']." - ".$asunto->fields['glosa_asunto'],$row);
				$row = str_replace('%contacto%',__('Contacto'),$row);
				$row = str_replace('%valor_contacto%', $asunto->fields['contacto'],$row);
				$row = str_replace('%servicios%',__('Servicios prestados'),$row);
				$row = str_replace('%telefono%', __('Teléfono'), $row);
				$row = str_replace('%valor_telefono%', $asunto->fields['fono_contacto'], $row);

				$row = str_replace('%TRABAJOS_ENCABEZADO%', GenerarDocumento($parser,'TRABAJOS_ENCABEZADO'),$row);
				$row = str_replace('%TRABAJOS_FILAS%', GenerarDocumento($parser,'TRABAJOS_FILAS'),$row);
				$row = str_replace('%TRABAJOS_TOTAL%', GenerarDocumento($parser,'TRABAJOS_TOTAL'),$row);
				$row = str_replace('%DETALLE_PROFESIONAL%', GenerarDocumento($parser,'DETALLE_PROFESIONAL'),$row);

				if( $asunto->fields['trabajos_total_duracion'] > 0 )
					$html .= $row;
			}
			break;
		case 'TRABAJOS_ENCABEZADO':
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%profesional%',__('Profesional'), $html);
			$html = str_replace('%duracion%',__('Duración'), $html);
			$html = str_replace('%valor%',__('Valor'), $html);
			break;
		case 'TRABAJOS_FILAS':
			$row_tmpl = $html;
			$html = '';

			//Tabla de Trabajos
			$query = "SELECT SQL_CALC_FOUND_ROWS trabajo.duracion_cobrada, trabajo.descripcion,trabajo.fecha,trabajo.id_usuario,
							trabajo.monto_cobrado, trabajo.id_trabajo, trabajo.tarifa_hh,
							trabajo.codigo_asunto, CONCAT_WS(' ', nombre, apellido1) as nombre_usuario
							FROM trabajo
							LEFT JOIN usuario ON trabajo.id_usuario=usuario.id_usuario
							WHERE trabajo.id_cobro = '". $cobro->fields['id_cobro'] . "'
							AND trabajo.codigo_asunto = '".$asunto->fields['codigo_asunto']."'
							ORDER BY trabajo.fecha ASC";

			$lista_trabajos = new ListaTrabajos($sesion,'',$query);

			$asunto->fields['trabajos_total_duracion'] = 0;
			$asunto->fields['trabajos_total_valor'] = 0;

			for($i=0;$i<$lista_trabajos->num;$i++)
			{
				$trabajo = $lista_trabajos->Get($i);

				list($h,$m,$s) = split(":",$trabajo->fields['duracion_cobrada']);

				$asunto->fields['trabajos_total_duracion'] += $h*60 + $m;
				$asunto->fields['trabajos_total_valor'] += $trabajo->fields['monto_cobrado'];

				if( !isset($profesionales[$trabajo->fields['nombre_usuario']]) )
				{
					$profesionales[$trabajo->fields['nombre_usuario']] = array();
					$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] = 0;
					$profesionales[$trabajo->fields['nombre_usuario']]['valor'] = 0;
					$profesionales[$trabajo->fields['nombre_usuario']]['tarifa'] = Funciones::Tarifa($sesion,$trabajo->fields['id_usuario'],$cobro->fields['id_moneda'],$trabajo->fields['codigo_asunto']);
				}

				$profesionales[$trabajo->fields['nombre_usuario']]['tiempo'] += $h*60 + $m;
				$profesionales[$trabajo->fields['nombre_usuario']]['valor'] += $trabajo->fields['monto_cobrado'];

				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($trabajo->fields['fecha'],$idioma->fields['formato_fecha']), $row);
				$row = str_replace('%descripcion%', $trabajo->fields['descripcion'], $row);
				$row = str_replace('%profesional%', $trabajo->fields['nombre_usuario'], $row);
				$row = str_replace('%duracion%', $h.':'.$m, $row);
				$row = str_replace('%valor%', $trabajo->fields['monto_cobrado']>0 ? number_format($trabajo->fields['monto_cobrado'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']):'---', $row);
				$html .= $row;
			}

			break;

		case 'TRABAJOS_TOTAL':
			$horas = floor(($asunto->fields['trabajos_total_duracion'])/60);
			$minutos = sprintf("%02s",$asunto->fields['trabajos_total_duracion']%60);

			$html = str_replace('%glosa%',__('Total'), $html);
			$html = str_replace('%duracion%', $horas.':'.$minutos, $html);

			if( $asunto->fields['trabajos_total_valor'] > 0 )
				$html = str_replace('%valor%', $moneda->fields['simbolo'].' '.number_format($asunto->fields['trabajos_total_valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%valor%', '&nbsp;', $html);

			break;

		case 'DETALLE_PROFESIONAL':
			if( $cobro->fields['opc_ver_profesional'] == 0 )
				return '';

			$html = str_replace('%glosa_profesional%',__('Detalle profesional'), $html);

			$html = str_replace('%PROFESIONAL_ENCABEZADO%', GenerarDocumento($parser,'PROFESIONAL_ENCABEZADO'), $html);
			$html = str_replace('%PROFESIONAL_FILAS%', GenerarDocumento($parser,'PROFESIONAL_FILAS'), $html);
			$html = str_replace('%PROFESIONAL_TOTAL%', GenerarDocumento($parser,'PROFESIONAL_TOTAL'), $html);
			break;

		case 'PROFESIONAL_ENCABEZADO':
			$html = str_replace('%nombre%',__('Nombre'), $html);
			$html = str_replace('%hh%',__('HH'), $html);
			$html = str_replace('%valor_hh%',__('Valor HH'), $html);
			$html = str_replace('%total%',__('Total'), $html);
			break;

		case 'PROFESIONAL_FILAS':
			$row_tmpl = $html;
			$html = '';

			if( is_array($profesionales) )
			{
				foreach($profesionales as $prof => $data)
				{
					$totales['tiempo'] += $data['tiempo'];
					$totales['valor'] += $data['valor'];

					$horas = floor(($data['tiempo'])/60);
					$minutos = sprintf("%02s",$data['tiempo']%60);

					$row = $row_tmpl;
					$row = str_replace('%nombre%', $prof, $row);
					$row = str_replace('%hh%', $horas.':'.$minutos,$row);
					$row = str_replace('%valor_hh%', $moneda->fields['simbolo'].' '.number_format($data['tarifa'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
					$row = str_replace('%total%', $data['valor']>0 ? $moneda->fields['simbolo'].' '.number_format($data['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']):'---',$row);
					$html .= $row;
				}
			}
			break;

		case 'PROFESIONAL_TOTAL':
			$horas = floor(($totales['tiempo'])/60);
			$minutos = sprintf("%02s",$totales['tiempo']%60);

			$html = str_replace('%glosa%',__('Total'), $html);
			$html = str_replace('%hh%', $horas.':'.$minutos, $html);

			if( $totales['valor'] > 0)
				$html = str_replace('%total%', $moneda->fields['simbolo'].' '.number_format($totales['valor'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			else
				$html = str_replace('%total%', '&nbsp;', $html);

			break;

		case 'GASTOS':
			if( $cobro->fields['opc_ver_gastos'] == 0 )
				return '';

			$html = str_replace('%glosa_gastos%',__('Gastos'), $html);

			$html = str_replace('%GASTOS_ENCABEZADO%', GenerarDocumento($parser,'GASTOS_ENCABEZADO'), $html);
			$html = str_replace('%GASTOS_FILAS%', GenerarDocumento($parser,'GASTOS_FILAS'), $html);
			$html = str_replace('%GASTOS_TOTAL%', GenerarDocumento($parser,'GASTOS_TOTAL'), $html);
			break;

		case 'GASTOS_ENCABEZADO':
			$html = str_replace('%fecha%',__('Fecha'), $html);
			$html = str_replace('%descripcion%',__('Descripción'), $html);
			$html = str_replace('%monto_original%',__('Monto'), $html);
			$html = str_replace('%monto%',__('Monto').' ('.$moneda->fields['simbolo'].')', $html);
			break;

		case 'GASTOS_FILAS':
			$row_tmpl = $html;
			$html = '';

			$query = "SELECT SQL_CALC_FOUND_ROWS *
					FROM cta_corriente
					WHERE id_cobro='".$cobro->fields['id_cobro']."' and egreso > 0
					ORDER BY fecha ASC";

			$lista_gastos = new ListaGastos($sesion,'',$query);

			$totales['total'] = 0;

			if( $lista_gastos->num == 0 )
			{
				$row = $row_tmpl;
				$row = str_replace('%fecha%', '&nbsp;',$row);
				$row = str_replace('%descripcion%', __('No hay gastos en este cobro'),$row);
				$row = str_replace('%monto_original%', '&nbsp;',$row);
				$row = str_replace('%monto%', '&nbsp;',$row);
				$html .= $row;
			}

			for($i=0;$i<$lista_gastos->num;$i++)
			{
				$gasto = $lista_gastos->Get($i);

				$moneda_gasto = new Objeto($sesion,'','','prm_moneda','id_moneda');
				$moneda_gasto->Load($gasto->fields['id_moneda']);

				$totales['total'] += $gasto->fields['egreso'] * ($moneda_gasto->fields['tipo_cambio']/$moneda->fields['tipo_cambio']);

				$row = $row_tmpl;
				$row = str_replace('%fecha%', Utiles::sql2fecha($gasto->fields['fecha'],$idioma->fields['formato_fecha']),$row);
				$row = str_replace('%descripcion%',$gasto->fields['descripcion'],$row);
				$row = str_replace('%monto_original%', $moneda_gasto->fields['simbolo'].' '.number_format($gasto->fields['egreso'],$moneda_gasto->fields['cifras_decimales'],$moneda_gasto->fields['separador_decimales'],$moneda_gasto->fields['separador_miles']),$row);
				$row = str_replace('%monto%', $moneda->fields['simbolo'].' '.number_format($gasto->fields['egreso'] * ($moneda_gasto->fields['tipo_cambio']/$moneda->fields['tipo_cambio']),$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']),$row);
				$html .= $row;
			}
			break;

		case 'GASTOS_TOTAL':
			$html = str_replace('%total%',__('Total'), $html);
			$html = str_replace('%valor_total%', $moneda->fields['simbolo'].' '.number_format($totales['total'],$moneda->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);

			$gastos_moneda_total = ($totales['total']*($cobro->fields['tipo_cambio_moneda']/$cobro->fields['tipo_cambio_moneda_base']))/$tipo_cambio_moneda_total;
			$html = str_replace('%valor_total_monedabase%', $moneda_total->fields['simbolo'].' '.number_format($gastos_moneda_total,$moneda_total->fields['cifras_decimales'],$idioma->fields['separador_decimales'],$idioma->fields['separador_miles']), $html);
			break;

		case 'CTA_CORRIENTE':
			break;
		}
		return $html;
	}
	######### FIN DOC COBRO #########
?>


