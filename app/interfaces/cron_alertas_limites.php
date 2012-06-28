<?
	require_once dirname(__FILE__).'/../conf.php';
	require_once dirname(__FILE__).'/../classes/AlertaCron.php';
	require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
	require_once Conf::ServerDir().'/../fw/classes/Sesion.php';
	require_once Conf::ServerDir().'/../fw/classes/Usuario.php';
	require_once Conf::ServerDir().'/../fw/classes/Lista.php';
	require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
	require_once Conf::ServerDir().'/classes/Observacion.php';
	require_once Conf::ServerDir().'/classes/Cobro.php';
	//require_once Conf::ServerDir().'/classes/Alerta.php';
	require_once Conf::ServerDir().'/classes/Asunto.php';
	require_once Conf::ServerDir().'/classes/UtilesApp.php';
	require_once Conf::ServerDir().'/classes/Contrato.php';
	require_once Conf::ServerDir().'/classes/Reporte.php';
	require_once Conf::ServerDir().'/classes/Notificacion.php';
	require_once Conf::ServerDir().'/classes/Tarea.php';
	//require_once Conf::ServerDir().'/interfaces/graficos/Grafico.php';
	
	//$dbh = mysql_connect(Conf::dbHost(), Conf::dbUser(), Conf::dbPass());
	//mysql_select_db(Conf::dbName()) or mysql_error($dbh);
	$sesion = new Sesion (null, true);
	$alerta = new Alerta ($sesion);


	$notificacion = new Notificacion($sesion);
	
	//El arreglo dato_x se construirá con los datos de cada usuarios de la forma: id_usuario => datos (otro arreglo)
	//Por ejemplo, se pueden anexar los siguientes componentes
	// $dato_x['usuarios'][5]['alerta_propia'] => 'Estimado usuario 5: no has ingresado horas'. (5: PRO)
	// $dato_x['usuarios'][5]['alerta_revisado'][7] => 'Estimado usuario 5: usuario 7 no ha ingresado horas'. (5:REV ó revisor(5,7)
	// $dato_x['usuarios'][5]['reportes'][3] => 'Estimado usuario 5: <imagen_reporte_5>. (5:REP)	
	$dato_mensual = array();
	$dato_semanal = array();
	$dato_diario = array();
	
	/*Mensajes */
	$warning = '<span style="color:#CC2233;">Alerta:</span>';
	$msg['horas_minimas_propio'] = $warning." s&oacute;lo ha ingresado %HORAS horas de un m&iacute;nimo de %MINIMO.";
	$msg['horas_maximas_propio'] = $warning." ha ingresado %HORAS horas, superando su m&aacute;ximo de %MAXIMO.";
	$msg['horas_minimas_revisado'] = $warning." no alcanza su m&iacute;nimo de %MINIMO horas.";
	$msg['horas_maximas_revisado'] = $warning." supera su m&aacute;ximo de %MAXIMO horas.";
	
	//Mail diario, Segundo Componente: Alertas de límites de Asuntos 
	$query_asuntos=
		"SELECT asunto.codigo_asunto,
				usuario.id_usuario,
				usuario.username,
				cliente.glosa_cliente
		FROM asunto
		JOIN usuario ON (asunto.id_encargado = usuario.id_usuario)
		JOIN cliente ON (asunto.codigo_cliente = cliente.codigo_cliente)
		WHERE asunto.activo = '1' AND cliente.activo = '1'";
	$result_asuntos = mysql_query ($query_asuntos,$sesion->dbh) or Utiles::errorSQL($query_asuntos,__FILE__,__LINE__,$sesion->dbh); 
	while( list($codigo_asunto, $id_usuario, $nombre_usuario,$glosa_cliente) = mysql_fetch_array($result_asuntos))
	{
		$asunto = new Asunto($sesion);
		$cobro = new Cobro($sesion);
		$asunto->LoadByCodigo($codigo_asunto);
		
		$dato_diario[$id_usuario]['nombre_pila'] = $nombre_usuario;

		/*Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido. */
		if($asunto->fields['limite_monto'] > 0)
			list($total_monto,$moneda_total_monto) =  $asunto->TotalMonto();
		if($asunto->fields['limite_hh'] > 0)
			$total_horas_trabajadas = $asunto->TotalHoras();
		if($asunto->fields['alerta_hh'] > 0) //Alerta de limite de horas no emitidas
			$total_horas_ult_cobro =  $asunto->TotalHoras(false);
		if($asunto->fields['alerta_monto'] > 0) //Significa que se requiere alerta por monto no emitido
			list($total_monto_ult_cobro,$moneda_desde_ult_cobro) =  $asunto->TotalMonto(false);	
		
		//Notificacion "Límite de monto"
		$total_monto = number_format($total_monto,1);
		$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

		if (($total_monto > $asunto->fields['limite_monto']) && ($asunto->fields['limite_monto'] > 0) && ($asunto->fields['notificado_monto_excedido']==0))
		{
			$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_monto'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['limite_monto'],
			'actual' => $total_monto,
			'moneda' => $moneda_total_monto);
			$asunto->Edit('notificado_monto_excedido','1');
            $asunto->Write();
		}

		//Notificacion "Límite de horas"
		if(($total_horas_trabajadas > $asunto->fields['limite_hh']) && ($asunto->fields['limite_hh'] > 0 ) && ($asunto->fields['notificado_hr_excedido']==0))
		{
			$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_horas'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['limite_hh'],
			'actual' => $total_horas_trabajadas);
			$asunto->Edit('notificado_hr_excedido','1');
			$asunto->Write();
		}

		//Notificacion "Monto desde el último cobro"
		if(($total_monto_ult_cobro > $asunto->fields['alerta_monto']) && ($asunto->fields['alerta_monto'] > 0) && ($asunto->fields['notificado_monto_excedido_ult_cobro']==0))
		{
			$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_ultimo_cobro'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['alerta_monto'],
			'actual' => $total_monto_ult_cobro,
			'moneda' => $moneda_desde_ult_cobro);			
			$asunto->Edit('notificado_monto_excedido_ult_cobro','1');
            $asunto->Write();
		}

		//Notificacion "Horas desde el último cobro"
		if(($total_horas_ult_cobro > $asunto->fields['alerta_hh']) &&  ($asunto->fields['alerta_hh'] > 0) && ($asunto->fields['notificado_hr_excedida_ult_cobro']==0)){
			
			$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['alerta_hh'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['alerta_hh'],
			'actual' => $total_horas_ult_cobro);
			$asunto->Edit('notificado_hr_excedida_ult_cobro','1');
            $asunto->Write();
		}
	}
	// Mail diario - Tercer componente: alertas de limites de Contrato.
		$query_contratos=
		"SELECT contrato.id_contrato,
				usuario.id_usuario,
				usuario.username,
				cliente.glosa_cliente,
				GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ',') as asuntos
		FROM contrato
		JOIN usuario ON (contrato.id_usuario_responsable = usuario.id_usuario)
		JOIN cliente ON (contrato.codigo_cliente = cliente.codigo_cliente)
		JOIN asunto ON (asunto.id_contrato = contrato.id_contrato)
		WHERE contrato.activo = 'SI' AND cliente.activo = '1' GROUP BY contrato.id_contrato";
	$result_contratos = mysql_query ($query_contratos,$sesion->dbh) or Utiles::errorSQL($query_contratos,__FILE__,__LINE__,$sesion->dbh); 
	while( list($id_contrato, $id_usuario, $nombre_usuario,$glosa_cliente,$asuntos) = mysql_fetch_array($result_contratos))
	{
		$contrato = new Contrato($sesion);
		$cobro = new Cobro($sesion);
		$contrato->Load($id_contrato);
		
		$dato_diario[$id_usuario]['nombre_pila'] = $nombre_usuario;

		// Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido. 
		if($contrato->fields['limite_monto'] > 0)
			list($total_monto,$moneda_total_monto) =  $contrato->TotalMonto();
		if($contrato->fields['limite_hh'] > 0)
			$total_horas_trabajadas = $contrato->TotalHoras();
		if($contrato->fields['alerta_hh'] > 0) //Alerta de limite de horas no emitidas
			$total_horas_ult_cobro =  $contrato->TotalHoras(false);
		if($contrato->fields['alerta_monto'] > 0) //Significa que se requiere alerta por monto no emitido
			list($total_monto_ult_cobro,$moneda_desde_ult_cobro) =  $contrato->TotalMonto(false);	
		
		//Notificacion "Límite de monto"
		$total_monto = number_format($total_monto,1);
		$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

		if (($total_monto > $contrato->fields['limite_monto']) && ($contrato->fields['limite_monto'] > 0) && ($contrato->fields['notificado_monto_excedido']==0))
		{
			$dato_diario[$id_usuario]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_monto'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',',$asuntos),
			'max' => $contrato->fields['limite_monto'],
			'actual' => $total_monto,
			'moneda' => $moneda_total_monto);
			$contrato->Edit('notificado_monto_excedido','1');
            $contrato->Write();
		}

		//Notificacion "Límite de horas"
		if(($total_horas_trabajadas > $contrato->fields['limite_hh']) && ($contrato->fields['limite_hh'] > 0 ) && ($contrato->fields['notificado_hr_excedido']==0))
		{
			$dato_diario[$id_usuario]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_horas'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',',$asuntos),
			'max' => $contrato->fields['limite_hh'],
			'actual' => $total_horas_trabajadas);
			$contrato->Edit('notificado_hr_excedido','1');
			$contrato->Write();
		}

		//Notificacion "Monto desde el último cobro"
		if(($total_monto_ult_cobro > $contrato->fields['alerta_monto']) && ($contrato->fields['alerta_monto'] > 0) && ($contrato->fields['notificado_monto_excedido_ult_cobro']==0))
		{
			$dato_diario[$id_usuario]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_ultimo_cobro'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',',$asuntos),
			'max' => $contrato->fields['alerta_monto'],
			'actual' => $total_monto_ult_cobro,
			'moneda' => $moneda_desde_ult_cobro);			
			$contrato->Edit('notificado_monto_excedido_ult_cobro','1');
            $contrato->Write();
		}

		//Notificacion "Horas desde el último cobro"
		if(($total_horas_ult_cobro > $contrato->fields['alerta_hh']) &&  ($contrato->fields['alerta_hh'] > 0) && ($contrato->fields['notificado_hr_excedida_ult_cobro']==0)){
			
			$dato_diario[$id_usuario]['contrato_excedido'][$contrato->fields['id_contrato']]['alerta_hh'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',',$asuntos),
			'max' => $contrato->fields['alerta_hh'],
			'actual' => $total_horas_ult_cobro);
			$contrato->Edit('notificado_hr_excedida_ult_cobro','1');
            $contrato->Write();
		}
	}
	// Mail diario - Cuarto componente: alertas de limites de Cliente.
		$query_clientes=
		"SELECT cliente.codigo_cliente,
				usuario.id_usuario,
				usuario.username,
				cliente.glosa_cliente
		FROM cliente
		JOIN usuario ON (cliente.id_usuario_encargado = usuario.id_usuario)
		WHERE cliente.activo = '1'";
	$result_clientes = mysql_query ($query_clientes,$sesion->dbh) or Utiles::errorSQL($query_clientes,__FILE__,__LINE__,$sesion->dbh); 
	while( list($codigo_cliente, $id_usuario, $nombre_usuario,$glosa_cliente) = mysql_fetch_array($result_clientes))
	{
		$cliente = new Cliente($sesion);
		$cliente->LoadByCodigo($codigo_cliente);
		
		$dato_diario[$id_usuario]['nombre_pila'] = $nombre_usuario;

		//Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido.
		if($cliente->fields['limite_monto'] > 0)
			list($total_monto,$moneda_total_monto) =  $cliente->TotalMonto();
		if($cliente->fields['limite_hh'] > 0)
			$total_horas_trabajadas = $cliente->TotalHoras();
		if($cliente->fields['alerta_hh'] > 0) //Alerta de limite de horas no emitidas
			$total_horas_ult_cobro =  $cliente->TotalHoras(false);
		if($cliente->fields['alerta_monto'] > 0) //Significa que se requiere alerta por monto no emitido
			list($total_monto_ult_cobro,$moneda_desde_ult_cobro) =  $cliente->TotalMonto(false);	
			
		
		//Notificacion "Límite de monto"
		$total_monto = number_format($total_monto,1);
		$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

		if (($total_monto > $cliente->fields['limite_monto']) && ($cliente->fields['limite_monto'] > 0) && ($cliente->fields['notificado_monto_excedido']==0))
		{
			$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_monto'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['limite_monto'],
			'actual' => $total_monto,
			'moneda' => $moneda_total_monto);
			$cliente->Edit('notificado_monto_excedido','1');
            $cliente->Write();
		}

		//Notificacion "Límite de horas"
		if(($total_horas_trabajadas > $cliente->fields['limite_hh']) && ($cliente->fields['limite_hh'] > 0 ) && ($cliente->fields['notificado_hr_excedido']==0))
		{
			$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_horas'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['limite_hh'],
			'actual' => $total_horas_trabajadas);
			$cliente->Edit('notificado_hr_excedido','1');
			$cliente->Write();
		}

		//Notificacion "Monto desde el último cobro"
		if(($total_monto_ult_cobro > $cliente->fields['alerta_monto']) && ($cliente->fields['alerta_monto'] > 0) && ($cliente->fields['notificado_monto_excedido_ult_cobro']==0))
		{
			$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_ultimo_cobro'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['alerta_monto'],
			'actual' => $total_monto_ult_cobro,
			'moneda' => $moneda_desde_ult_cobro);
			$cliente->Edit('notificado_monto_excedido_ult_cobro','1');
            $cliente->Write();
		}

		//Notificacion "Horas desde el último cobro"
		if(($total_horas_ult_cobro > $cliente->fields['alerta_hh']) &&  ($cliente->fields['alerta_hh'] > 0) && ($cliente->fields['notificado_hr_excedida_ult_cobro']==0)){
			
			$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['alerta_hh'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['alerta_hh'],
			'actual' => $total_horas_ult_cobro);
			$cliente->Edit('notificado_hr_excedida_ult_cobro','1');
                        $cliente->Write();
		}
	}
        
        // Mail Diario: Sexto componente: Alertas de ingreso de horas
	if(date ("N") < 6) // Lunes a Viernes
	{ 
			$opc = 'mail_retrasos';
			$query="SELECT usuario.id_usuario
					FROM usuario
					JOIN usuario_permiso USING(id_usuario)
					WHERE codigo_permiso='PRO' AND alerta_diaria = '1' AND activo=1 AND retraso_max_notificado = 0";
			$result = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			while ($row = mysql_fetch_array($result))
			{ 
					$id_usuario = $row["id_usuario"];
					$prof = new Usuario($sesion);
					$prof->LoadId($id_usuario);

					if($prof->fields['retraso_max'] > 0)
					{
						//Calcular horas de retraso excluyendo los fines de semana
                                                $diferencia_db_app = UtilesApp::DiferenciaDbAplicacionEnSegundos($sesion);
                                                
                                                $query = "SELECT MAX(fecha_creacion) FROM trabajo WHERE id_usuario='$id_usuario'";
						$resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						list($ultima_fecha_ingreso) = mysql_fetch_array($resp);
                                                $start = 0;
                                                if( !empty($ultima_fecha_ingreso) && $ultima_fecha_ingreso != "NULL" ) {
                                                    $start = strtotime($ultima_fecha_ingreso);
                                                }
						$end = strtotime(date("Y-m-d H:i:s"));
                                                if($start == 0) {
                                                    $retraso = 0;
                                                } else {
                                                    $retraso = $end - $start - $diferencia_db_app;
                                                    while ($start <= $end) {
                                                        if (date('N', $start) > 5 ) {
                                                               $retraso -= 86400;
                                                        }
                                                        $start += 86400;
                                                    }
                                                }
						$horas_retraso = max(0,$retraso/3600);
						if($horas_retraso > $prof->fields['retraso_max'])
						{ 
							$dato_diario[$id_usuario]['retraso_max'] = array('actual'=>$horas_retraso,'max'=>$prof->fields['retraso_max']);
							$query = "UPDATE usuario SET retraso_max_notificado = 1 WHERE id_usuario = '$id_usuario'";
							mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
						}
					}
			}
	}
	
	
	// Fin del mail diario. Envío.
	$mensajes = $notificacion->mensajeDiario($dato_diario);
	foreach($mensajes as $id_usuario => $mensaje)
	{
			if($argv[1]=='correo' || isset($_GET['correo']))
				$alerta->EnviarAlertaProfesional($id_usuario,$mensaje, $sesion, false);
	}
	if($desplegar_correo == 'aefgaeddfesdg23k1h3kk1')
	{
			var_dump($dato_diario);
			echo implode('<br><br><br>',$mensajes);
	}
	
?>
