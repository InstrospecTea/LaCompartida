<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Pagina.php';
	require_once Conf::ServerDir().'/../fw/classes/Buscador.php';
	require_once Conf::ServerDir().'/../app/classes/Contrato.php';
	require_once Conf::ServerDir().'/../app/classes/Cobro.php';
	require_once Conf::ServerDir().'/../app/classes/InputId.php';
	require_once Conf::ServerDir().'/../app/classes/Moneda.php';
	require_once Conf::ServerDir().'/../app/classes/CobroMoneda.php';
	require_once Conf::ServerDir().'/../app/classes/Debug.php';
	require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
	require_once Conf::ServerDir().'/../app/classes/DocGenerator.php';
	require_once Conf::ServerDir().'/../app/classes/TemplateParser.php';
	require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/../app/classes/Observacion.php';
	require_once Conf::ServerDir().'/../app/classes/Cliente.php';

	$sesion = new Sesion(array('COB','DAT'));

	$pagina = new Pagina($sesion);
	
	$contrato = new Contrato($sesion);

	$cobro = new Cobro($sesion);

	//si no me llega uno, es 0
	$incluye_gastos = !empty($incluye_gastos);
	$incluye_honorarios = !empty($incluye_honorarios);
	//si no me llega ninguno, asumo q son los 2 (comportamiento anterior)
	if(!$incluye_gastos && !$incluye_honorarios)
		$incluye_gastos = $incluye_honorarios = true;

	if($tipo_liquidacion){ //1:honorarios, 2:gastos, 3:mixtas
		$incluye_honorarios = $tipo_liquidacion&1 ? true : false;
		$incluye_gastos = $tipo_liquidacion&2 ? true : false;
	}

	set_time_limit(0);
	
	if($codigo_cliente_secundario)
    	{
    		$cliente=new Cliente($sesion);
    		$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
    		$codigo_cliente=$cliente->fields['codigo_cliente'];
    	}
	####### WHERE SQL ########
	if($print || $emitir)
	{
		$where = 1;
		$join_cobro_cliente = "";
		if($activo)
			$where .= " AND contrato.activo = 'SI' ";
		else
			$where .= " AND contrato.activo = 'NO' ";
		if($id_usuario)
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
		if($codigo_cliente)
			$where .= " AND contrato.codigo_cliente = '$codigo_cliente' ";
		if($id_grupo_cliente){
			$join_cobro_cliente = " JOIN cliente ON cobro.codigo_cliente = cliente.codigo_cliente ";
			$where .= " AND cliente.id_grupo_cliente = '$id_grupo_cliente' ";
		}
		if($rango == '' && $usar_periodo == 1)
		{
			$fecha_periodo_ini = $fecha_anio.'-'.$fecha_mes.'-01';
			$fecha_periodo_fin = $fecha_anio.'-'.$fecha_mes.'-31';
			$where .= " AND cobro.fecha_creacion >= '$fecha_periodo_ini' AND cobro.fecha_creacion <= '$fecha_periodo_fin' ";
		}
		elseif($fecha_periodo_ini != '' && $fecha_periodo_fin != '' && $rango == 1 && $usar_periodo == 1)
			$where .= " AND cobro.fecha_creacion >= '".Utiles::fecha2sql($fecha_periodo_ini)."' AND cobro.fecha_creacion <= '".Utiles::fecha2sql($fecha_periodo_fin)."' ";
		if($forma_cobro)
			$where .= " AND contrato.forma_cobro = '$forma_cobro' ";
		if($tipo_liquidacion)
			$where .= " AND cobro.incluye_gastos = '$incluye_gastos' AND cobro.incluye_honorarios = '$incluye_honorarios' ";
		
		$url = 	'genera_cobros.php?activo='.$activo.'&id_usuario='.$id_usuario.'&codigo_cliente='.$codigo_cliente.'&fecha_ini='.$fecha_ini.'&fecha_fin='.$fecha_fin.'&opc=buscar&rango='.$rango.'&fecha_anio='.$fecha_anio.'&fecha_mes='.$fecha_mes.'&fecha_periodo_ini='.$fecha_periodo_ini.'&fecha_periodo_fin='.$fecha_periodo_fin.'&usar_periodo='.$usar_periodo.'&tipo_liquidacion='.$tipo_liquidacion.'&forma_cobro='.$forma_cobro;
	}
	####### END #########
	
	# IMPRESION	
	if($print)
	{
		$query = "SELECT cobro.id_cobro, cobro.id_usuario, cobro.codigo_cliente, cobro.id_contrato, contrato.id_carta, cobro.estado, cobro.opc_papel 
								FROM cobro
								JOIN contrato ON cobro.id_contrato = contrato.id_contrato
								LEFT JOIN cliente ON cliente.codigo_cliente = contrato.codigo_cliente
								WHERE $where AND cobro.estado IN ( 'CREADO', 'EN REVISION' ) 
								ORDER BY cliente.glosa_cliente";
								
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		if(count(mysql_num_rows($resp)) > 0)
		{
			while($cob = mysql_fetch_array($resp))
			{
				set_time_limit(100);
				if( $cobro->Load($cob['id_cobro']) )
				{
					$cobro->GuardarCobro();
					$lang = $cobro->fields['codigo_idioma'] == '' ? 'es' : $cobro->fields['codigo_idioma'];
					require Conf::ServerDir()."/lang/$lang.php";
					if($opcion != 'cartas')
						$cobro->fields['id_carta'] = null;
					$html .= $cobro->GeneraHTMLCobro(true);
					$html .= "<br size=\"1\" class=\"divisor\">";
				}
	  			$opc_papel = $cob['opc_papel'];
	  			$id_carta = $cob['id_carta'];
	  			$cssData .= UtilesApp::TemplateCartaCSS($sesion,$cobro->fields['id_carta']);
			}
			if($html)
			{
				$cssData .= UtilesApp::CSSCobro($sesion);
				$doc = new DocGenerator( $html, $cssData, 'LETTER', 1 ,'PORTRAIT',1.5,2.0,2.0,2.0,$cobro->fields['estado']);
				$doc->output("cobro_masivo.doc");
			}
		}	
		$pagina->Redirect($url);
	}
	else if($emitir)
	{
		//JOIN cliente ON cobro.codigo_cliente = cliente.codigo_cliente
		$query = "SELECT cobro.id_cobro, cobro.id_usuario, cobro.codigo_cliente, cobro.id_contrato, contrato.id_carta, cobro.estado,
								cobro.opc_papel,contrato.id_carta
								FROM cobro
								JOIN contrato ON cobro.id_contrato = contrato.id_contrato
								LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente 
								WHERE $where
								AND cobro.estado IN ( 'CREADO', 'EN REVISION' )";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		while($cob = mysql_fetch_array($resp))
		{
			set_time_limit(100);
			if( $cobro->Load($cob['id_cobro']) )
			{
				$cobro->Edit('id_carta',$cob['id_carta']);
				$ret = $cobro->GuardarCobro(true);
				$cobro->Edit('etapa_cobro','5');
				$cobro->Edit('fecha_emision',date('Y-m-d H:i:s'));
				$cobro->Edit('estado','EMITIDO');
				if( $ret == '' )
				{
					$his = new Observacion($sesion);
					$his->Edit('fecha',date('Y-m-d H:i:s'));
					$his->Edit('comentario',__('COBRO EMITIDO'));
					$his->Edit('id_usuario',$sesion->usuario->fields['id_usuario']);
					$his->Edit('id_cobro',$cobro->fields['id_cobro']);
					$his->Write();
					$cobro->Write();
				}
			}
		}
		$url .= '&cobros_emitidos=1';
		$pagina->Redirect($url);
	}
	else if($individual)
	{
		if( $id_contrato )
		{
			$id_proceso_nuevo = $cobro->GeneraProceso();
			
			if(empty($monto))
				$monto='';
			if(empty($id_cobro_pendiente))
				$id_cobro_pendiente='';
			
			//Por conf se permite el uso de la fecha desde
			$fecha_ini_cobro = "";
			if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaFechaDesdeCobranza') ) || ( method_exists('Conf','UsaFechaDesdeCobranza') && Conf::UsaFechaDesdeCobranza() ) ) && $fecha_ini)
				$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini); // Comentado por SM 28.01.2011 el conf nunca se usa

			$id = $cobro->PrepararCobro(
				$fecha_ini_cobro,
				Utiles::fecha2sql($fecha_fin),
				$id_contrato,
				true,
				$id_proceso_nuevo,
				$monto,
				$id_cobro_pendiente,
				false,
				false,
				$incluye_gastos,
				$incluye_honorarios);
			
			if($id)
				$pagina->Redirect('cobros5.php?id_cobro='.$id.'&popup=1');
		}
	}
	else #Creación masiva de cobros
	{
		$where = 1;
		$join_programados = "";
		if($tipo_liquidacion)
			$where .= " AND contrato.separar_liquidaciones = ".($tipo_liquidacion=='3' ? 0 : 1)." ";
		if($activo)
			$where .= " AND contrato.activo = 'SI' ";
		else
			$where .= " AND contrato.activo = 'NO' ";
		if($id_usuario)
			$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
		if($codigo_cliente)
			$where .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
		if($id_grupo_cliente)
			$where .= " AND cliente.id_grupo_cliente = '$id_grupo_cliente' ";
		if($forma_cobro)
			$where .= " AND contrato.forma_cobro = '$forma_cobro' ";
		if($programados)
			$join_programados = "INNER JOIN cobro_pendiente ON cobro_pendiente.id_contrato=contrato.id_contrato";
		$query = "SELECT SQL_CALC_FOUND_ROWS contrato.id_contrato,cliente.codigo_cliente, contrato.id_moneda, contrato.forma_cobro, contrato.monto, contrato.retainer_horas, contrato.id_moneda, contrato.separar_liquidaciones
									FROM contrato
									$join_programados
									JOIN tarifa ON contrato.id_tarifa = tarifa.id_tarifa
									LEFT JOIN asunto ON asunto.id_contrato=contrato.id_contrato
									JOIN cliente ON cliente.codigo_cliente=contrato.codigo_cliente
									JOIN prm_moneda  ON (prm_moneda.id_moneda=contrato.id_moneda)
									WHERE $where AND contrato.incluir_en_cierre = 1
									GROUP BY contrato.id_contrato";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		#cobros solo gastos
		if($gastos)
		{
			while($contra = mysql_fetch_array($resp))
			{
				set_time_limit(100);
				#Mala documentaciÃ³n!!! Que significa $contra? Que hace GeneraProceso??? ICC
				$cobro = new Cobro($sesion);
				if(!$id_proceso_nuevo)
				{
					$id_proceso_nuevo = $cobro->GeneraProceso();
				}
				//Por conf se permite el uso de la fecha desde
				$fecha_ini_cobro = "";
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaFechaDesdeCobranza') ) || ( method_exists('Conf','UsaFechaDesdeCobranza') && Conf::UsaFechaDesdeCobranza() ) ) && $fecha_ini)
					$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);  //Comentado por SM 28.01.2011 el conf nunca se usa
					
				$cobro->PrepararCobro($fecha_ini_cobro,Utiles::fecha2sql($fecha_fin),$contra['id_contrato'], false , $id_proceso_nuevo,'','',true, false, true, false);
			}
		}
		#fin gastos
		
		#cobros programados
		else if($programados)
		{
			while($contra = mysql_fetch_array($resp))
			{
				$cobro = new Cobro($sesion);
				if(!$id_proceso_nuevo)
				{
					$id_proceso_nuevo = $cobro->GeneraProceso();
				}

				$query2 = "SELECT cobro_pendiente.id_cobro_pendiente, cobro_pendiente.monto_estimado, cobro_pendiente.incluye_gastos, cobro_pendiente.incluye_honorarios
										FROM cobro_pendiente
										WHERE cobro_pendiente.id_cobro IS NULL AND cobro_pendiente.id_contrato='".$contra['id_contrato']."'
										ORDER BY cobro_pendiente.fecha_cobro LIMIT 1";
				$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2,__FILE__,__LINE__,$sesion->dbh);
				list($id_cobro_pendiente,$monto_programado,$inc_gastos,$inc_honorarios)=mysql_fetch_array($resp2);
				if($contra['forma_cobro']!='FLAT FEE')
					$monto_programado='';

				$cobro->PrepararCobro('',Utiles::fecha2sql($fecha_fin),$contra['id_contrato'], false , $id_proceso_nuevo,$monto_programado,$id_cobro_pendiente, false, false, $inc_gastos=='1', $inc_honorarios=='1');
			}
		}
		#fin cobros programados
		
		#cobros wip
		else
		{
			while($contra = mysql_fetch_array($resp))
			{
				$cobro = new Cobro($sesion);
				if(!$id_proceso_nuevo)
				{
					$id_proceso_nuevo = $cobro->GeneraProceso();
				}
				//Por conf se permite el uso de la fecha desde
				$fecha_ini_cobro = "";
				if( ( ( method_exists('Conf','GetConf') && Conf::GetConf($sesion,'UsaFechaDesdeCobranza') ) || ( method_exists('Conf','UsaFechaDesdeCobranza') && Conf::UsaFechaDesdeCobranza() ) ) && $fecha_ini)
					$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini); // Comentado por SM 28.01.2011 el conf nunca se usa

				//si se separan pero se piden ambos, se generan 2 cobros
				if($contra['separar_liquidaciones']=='1' && $incluye_gastos && $incluye_honorarios){
					$cobro->PrepararCobro(
						$fecha_ini_cobro,
						Utiles::fecha2sql($fecha_fin),
						$contra['id_contrato'],
						false,
						$id_proceso_nuevo,
						'',
						'',
						false,
						false,
						false,
						true);
					$cobro = new Cobro($sesion);
					$id_proceso_nuevo = $cobro->GeneraProceso();
					$cobro->PrepararCobro(
						$fecha_ini_cobro,
						Utiles::fecha2sql($fecha_fin),
						$contra['id_contrato'],
						false,
						$id_proceso_nuevo,
						'',
						'',
						false,
						false,
						true,
						false);
				}
				else{ //no se separan y se piden los 2, o se separan y se pide 1 (no+1 se filtra en la query)
					$cobro->PrepararCobro(
						$fecha_ini_cobro,
						Utiles::fecha2sql($fecha_fin),
						$contra['id_contrato'],
						false,
						$id_proceso_nuevo,
						'',
						'',
						false,
						false,
						$incluye_gastos,
						$incluye_honorarios);
				}
			}
		}
		#fin cobros wip
		
		$contrato->SetIncluirEnCierre($sesion);
		$pagina->Redirect('genera_cobros.php?activo='.$activo.'&id_usuario='.$id_usuario.'&codigo_cliente='.$codigo_cliente.'&fecha_ini='.$fecha_ini.'&fecha_fin='.$fecha_fin.'&id_grupo_cliente='.$id_grupo_cliente.'&fecha_ini='.$fecha_ini.'&opc=buscar&cobros_generado=1&tipo_liquidacion='.$tipo_liquidacion.'&forma_cobro='.$forma_cobro);
	}
?>
