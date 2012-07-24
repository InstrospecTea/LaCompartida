<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once dirname(__FILE__) . '/../classes/AlertaCron.php';
require_once Conf::ServerDir() . '/../fw/classes/Utiles.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Usuario.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/classes/Observacion.php';
//require_once Conf::ServerDir() . '/classes/Cobro.php';
//require_once Conf::ServerDir().'/classes/Alerta.php';
require_once Conf::ServerDir() . '/classes/Asunto.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';
require_once Conf::ServerDir() . '/classes/Contrato.php';
require_once Conf::ServerDir() . '/classes/Reporte.php';
require_once Conf::ServerDir() . '/classes/Notificacion.php';
require_once Conf::ServerDir() . '/classes/Tarea.php';
require_once Conf::ServerDir() . '/classes/CobroPendiente.php';

//require_once Conf::ServerDir().'/interfaces/graficos/Grafico.php';
//$dbh = mysql_connect(Conf::dbHost(), Conf::dbUser(), Conf::dbPass());
//mysql_select_db(Conf::dbName()) or mysql_error($dbh);
$sesion = new Sesion(null, true);
$alerta = new Alerta($sesion);

set_time_limit(300);
$sesion->phpConsole();
$sesion->debug('empieza el cron notificacion');
$timezone_offset = UtilesApp::get_offset_os_utc() - UtilesApp::get_utc_offset(Conf::GetConf($sesion, 'ZonaHoraria'));

if (method_exists('Conf', 'GetConf')) {
	date_default_timezone_set(Conf::GetConf($sesion, 'ZonaHoraria'));
} else {
	date_default_timezone_set('America/Santiago');
}

$notificacion = new Notificacion($sesion);

//El arreglo dato_x se construirá con los datos de cada usuarios de la forma: id_usuario => datos (otro arreglo)
//Por ejemplo, se pueden anexar los siguientes componentes
// $dato_x['usuarios'][5]['alerta_propia'] => 'Estimado usuario 5: no has ingresado horas'. (5: PRO)
// $dato_x['usuarios'][5]['alerta_revisado'][7] => 'Estimado usuario 5: usuario 7 no ha ingresado horas'. (5:REV ó revisor(5,7)
// $dato_x['usuarios'][5]['reportes'][3] => 'Estimado usuario 5: <imagen_reporte_5>. (5:REP)
$dato_mensual = array();
$dato_semanal = array();
$dato_diario = array();

/* Mensajes */
$warning = '<span style="color:#CC2233;">Alerta:</span>';
if (Conf::GetConf($sesion, 'MensajeAlertaProfessionalSemanal') && Conf::GetConf($sesion, 'MensajeAlertaProfessionalSemanal') != ''){
    $msg['horas_minimas_propio'] = Conf::GetConf($sesion, 'MensajeAlertaProfessionalSemanal');
}else{
$msg['horas_minimas_propio'] = $warning . " s&oacute;lo ha ingresado %HORAS horas de un m&iacute;nimo de %MINIMO.";    
}

$msg['horas_maximas_propio'] = $warning . " ha ingresado %HORAS horas, superando su m&aacute;ximo de %MAXIMO.";
$msg['horas_minimas_revisado'] = $warning . " no alcanza su m&iacute;nimo de %MINIMO horas.";
$msg['horas_maximas_revisado'] = $warning . " supera su m&aacute;ximo de %MAXIMO horas.";


//Queries de Notificacion Semanal
$DiaMailSemanal = 'Fri';
if (method_exists('Conf', 'GetConf')) {
	$DiaMailSemanal = Conf::GetConf($sesion, 'DiaMailSemanal');
} else if (method_exists('Conf', 'DiaMailSemanal')) {
	$DiaMailSemanal = Conf::DiaMailSemanal();
}

if (date("D") == $DiaMailSemanal || (isset($forzar_semanal) && $forzar_semanal == 'aefgaeddfesdg23k1h3kk1')) {
	// Mensaje para JPRO: Alertas de Mínimo y Máximo de horas semanales
	$ids_usuarios_profesionales = '';
	$query = "SELECT usuario.id_usuario,
									alerta_semanal,
									usuario.nombre AS nombre_pila,
									username AS nombre_usuario
								FROM usuario
								JOIN usuario_permiso USING(id_usuario)
								WHERE codigo_permiso = 'PRO' AND activo = 1 ";
	$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	while (list($id_usuario, $alerta_semanal, $nombre_pila, $nombre_usuario) = mysql_fetch_array($result)) {
		$profesional = new Usuario($sesion);
		$profesional->LoadId($id_usuario);
		$minimo = $profesional->fields['restriccion_min'];
		$maximo = $profesional->fields['restriccion_max'];
		$horas = $alerta->HorasUltimaSemana($id_usuario);
		$horas_cobrables = $alerta->HorasCobrablesUltimaSemana($id_usuario);

		if (!$horas) {
			$horas = '0.00';
		}
		if (!$horas_cobrables) {
			$horas_cobrables = '0.00';
		}

		if (UtilesApp::GetConf($sesion, 'AlertaSemanalTodosAbogadosaAdministradores')) {
			$ids_usuarios_profesionales .= ',' . $id_usuario;
		}

		if ($minimo > 0 && $horas < $minimo) {
			//Alerto al usuario
			if ($alerta_semanal) {
				$txt = str_replace('%HORAS', $horas, $msg['horas_minimas_propio']);
				$txt = str_replace('%MINIMO', $minimo, $txt);
				$dato_semanal[$id_usuario]['alerta_propia'] = $txt;
			}
			//Alerto a sus revisores
			$txt = str_replace('%HORAS', $horas, $msg['horas_minimas_revisado']);
			$txt = str_replace('%MINIMO', $minimo, $txt);
			$cache_revisados[$id_usuario]['alerta'] = $txt;
		}
		if ($maximo > 0 && $horas > $maximo) {
			//Alerto al usuario
			if ($alerta_semanal) {
				$txt = str_replace('%HORAS', $horas, $msg['horas_maximas_propio']);
				$txt = str_replace('%MAXIMO', $maximo, $txt);
				$dato_semanal[$id_usuario]['alerta_propia'] = $txt;
			}
			//Alerta a sus revisores
			$txt = str_replace('%HORAS', $horas, $msg['horas_maximas_revisado']);
			$txt = str_replace('%MAXIMO', $maximo, $txt);
			$cache_revisados[$id_usuario]['alerta'] = $txt;
		}
		$dato_semanal[$id_usuario]['nombre_pila'] = $nombre_pila;
		$cache_revisados[$id_usuario]['nombre'] = $nombre_usuario;
		$cache_revisados[$id_usuario]['horas'] = number_format($horas, 1);
		$cache_revisados[$id_usuario]['horas_cobrables'] = number_format($horas_cobrables, 1);
	}
	// Mensaje para REV: horas de cada revisado, alertas.
	if (( UtilesApp::GetConf($sesion, 'ReporteRevisadosATodosLosAbogados') )
			|| ( UtilesApp::GetConf($sesion, 'ResumenHorasSemanalesAAbogadosIndividuales') )
			|| ( UtilesApp::GetConf($sesion, 'AlertaSemanalTodosAbogadosaAdministradores') )) {
		$having = "";
	} else {
		$having = " AND (codigo_permiso = 'REV' OR revisados IS NOT NULL)";
	}
	$query = "SELECT usuario.id_usuario, alerta_semanal, codigo_permiso,
													GROUP_CONCAT(DISTINCT usuario_revisor.id_revisado SEPARATOR ',') as revisados
					    			FROM usuario
										LEFT JOIN usuario_permiso ON (usuario.id_usuario = usuario_permiso.id_usuario 
											AND ( usuario_permiso.codigo_permiso = 'REV' OR usuario_permiso.codigo_permiso = 'ADM' ))
										LEFT JOIN usuario_revisor ON (usuario.id_usuario = usuario_revisor.id_revisor)
										WHERE activo = 1
											AND alerta_revisor = 1
										GROUP BY usuario.id_usuario
										HAVING 1 $having";
	$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	while (list($id_usuario, $alerta_semanal, $codigo_permiso, $revisados) = mysql_fetch_array($result)) {
		$profesional = new Usuario($sesion);
		$profesional->LoadId($id_usuario);

		if (UtilesApp::GetConf($sesion, 'AlertaSemanalTodosAbogadosaAdministradores')) {
			if ($codigo_permiso == 'ADM') {
				$revisados = $ids_usuarios_profesionales;
			} else {
				$revisados = $id_usuario;
			}
		} else if ($revisados != "") {
			$revisados .= ',' . $id_usuario;
		} else if (UtilesApp::GetConf($sesion, 'ResumenHorasSemanalesAAbogadosIndividuales')) {
			$revisados = $id_usuario;
		}

		// Comentado por Stefan Moers 5.4.2011, siempre se debería hacer el intersect con el array de los revisados.
		/* if($codigo_permiso == 'REV') //Si es revisor, informo sobre todos en cache_revisados
		  $dato_semanal[$id_usuario]['alerta_revisados'] = $cache_revisados;
		  else */ //Si no, array_intersect_key devolverá un segmento de cache_revisados dado por el arreglo 'revisados' (explotado e invertido).
		if (UtilesApp::GetConf($sesion, 'ReporteRevisadosATodosLosAbogados')) {
			$dato_semanal[$id_usuario]['alerta_revisados'] = $cache_revisados;
		} else {
			$dato_semanal[$id_usuario]['alerta_revisados'] = array_intersect_key($cache_revisados, array_flip(explode(',', $revisados)));
		}
	}



	// Mensaje para REP: Imagenes de Reporte Consolidado. Genero el pdf que se anexará

	/* Imagenes para Encargado comercial:
	 * Mis contratos: horas de la semana, monto de la semana, que tal el CAP.
	 * */
}
// echo htmlentities(print_r($mail_semanal,true));
// Ahora que tengo los datos, construyo el arreglo de mensajes a enviar
$mensajes = $notificacion->mensajeSemanal($dato_semanal);
foreach ($mensajes as $id_usuario => $mensaje) {
	if ($argv[1] == 'correo' || isset($_GET['correo'])) {
		$alerta->EnviarAlertaProfesional($id_usuario, $mensaje, $sesion, false);
	}
}
if (isset($desplegar_correo) && $desplegar_correo == 'aefgaeddfesdg23k1h3kk1') {
	var_dump($dato_semanal);
	echo implode('<br><br><br>', $mensajes);
}

// Mail diario, Primer componente: la modificación de datos se alerta cada día a los responsables del contrato
$CorreosModificacionAdminDatos = '';
if (method_exists('Conf', 'GetConf')) {
	$CorreosModificacionAdminDatos = Conf::GetConf($sesion, 'CorreosModificacionAdminDatos');
} else if (method_exists('Conf', 'CorreosModificacionAdminDatos')) {
	$CorreosModificacionAdminDatos = Conf::CorreosModificacionAdminDatos();
}

if ($CorreosModificacionAdminDatos != '') {
	$query_enviado = "SELECT MAX(fecha_enviado)
							FROM modificaciones_contrato";
	$resp_enviado = mysql_query($query_enviado, $sesion->dbh) or Utiles::errorSQL($query_enviado, __FILE__, __LINE__, $sesion->dbh);
	list($fecha) = mysql_fetch_array($resp_enviado);

	// Buscar para todas las personas responsables que necesitas informar
	$query_responsables = "
				SELECT
				DISTINCT u.nombre as nombre_pila_responsable,
				u.email,
				u.id_usuario
				FROM usuario AS u
				JOIN modificaciones_contrato AS mc ON u.id_usuario=mc.id_usuario_responsable
				WHERE u.activo=1 AND mc.fecha_modificacion>'" . $fecha . "'";
	$resp_responsables = mysql_query($query_responsables, $sesion->dbh) or Utiles::errorSQL($query_responsables, __FILE__, __LINE__, $sesion->dbh);

	while (list($nombre_pila_responsable, $email, $id_usuario_responsable) = mysql_fetch_array($resp_responsables)) {
		//Buscar para todas las modificaciones desde ultimo Email enviado
		$query_mod = "SELECT c.glosa_cliente,
											username as nombre_modificador,
											mc.fecha_modificacion,
											GROUP_CONCAT(DISTINCT a.glosa_asunto SEPARATOR ',') as asuntos,
											contrato.glosa_contrato
								 		FROM modificaciones_contrato AS mc
										JOIN contrato ON contrato.id_contrato=mc.id_contrato
										JOIN usuario AS u ON mc.id_usuario=u.id_usuario
										JOIN cliente AS c ON c.codigo_cliente=contrato.codigo_cliente
										LEFT JOIN asunto AS a ON a.id_contrato=mc.id_contrato
										WHERE mc.fecha_modificacion > '" . $fecha . "'
										AND mc.id_usuario_responsable='$id_usuario_responsable'
										GROUP BY contrato.id_contrato";
		$resp_mod = mysql_query($query_mod, $sesion->dbh) or Utiles::errorSQL($query_mod, __FILE__, __LINE__, $sesion->dbh);


		while (list($nombre_cliente, $nombre_modificador, $fecha_modificacion, $asuntos, $glosa_contrato) = mysql_fetch_array($resp_mod)) {
			$date = new DateTime($fecha_modificacion);
			$asuntos = explode(',', $asuntos);
			$dato_diario[$id_usuario_responsable]['nombre_pila'] = $nombre_pila_responsable;
			$dato_diario[$id_usuario_responsable]['modificacion_contrato'][] =
					array(
						'nombre_cliente' => $nombre_cliente,
						'asuntos' => $asuntos,
						'nombre_modificador' => $nombre_modificador,
						'fecha' => date_format($date, 'd/m/Y  H:i:s'));
		}
		$query_update = " UPDATE modificaciones_contrato
											SET fecha_enviado=NOW()
											WHERE fecha_modificacion >= '" . $fecha . "'";
		if ($argv[1] == 'correo' || isset($_GET['correo'])) {
			$resp_update = mysql_query($query_update, $sesion->dbh) or Utiles::errorSQL($query_update, __FILE__, __LINE__, $sesion->dbh);
		}
	}
}



//Mail diario, Segundo Componente: Alertas de límites de Asuntos
$query_asuntos =
		"SELECT asunto.codigo_asunto,
				usuario.id_usuario,
				usuario.username,
				cliente.glosa_cliente
		FROM asunto
		JOIN usuario ON (asunto.id_encargado = usuario.id_usuario)
		JOIN cliente ON (asunto.codigo_cliente = cliente.codigo_cliente)
		WHERE asunto.activo = '1' AND cliente.activo = '1'";
$result_asuntos = mysql_query($query_asuntos, $sesion->dbh) or Utiles::errorSQL($query_asuntos, __FILE__, __LINE__, $sesion->dbh);
while (list($codigo_asunto, $id_usuario, $nombre_usuario, $glosa_cliente) = mysql_fetch_array($result_asuntos)) {
	$asunto = new Asunto($sesion);
	//$cobro = new Cobro($sesion);
	$asunto->LoadByCodigo($codigo_asunto);

	$dato_diario[$id_usuario]['nombre_pila'] = $nombre_usuario;

	/* Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido. */
	if ($asunto->fields['limite_monto'] > 0) {
		list($total_monto, $moneda_total_monto) = $asunto->TotalMonto();
	} else {
		list($total_monto, $moneda_total_monto) =array(0,1);
	}
	if ($asunto->fields['limite_hh'] > 0) {
		$total_horas_trabajadas = $asunto->TotalHoras();
	} else {
		$total_horas_trabajadas = 0;
	}
	//Alerta de limite de horas no emitidas
	if ($asunto->fields['alerta_hh'] > 0) {
		$total_horas_ult_cobro = $asunto->TotalHoras(false);
	} else {
		$total_horas_ult_cobro=0;
	}
	//Significa que se requiere alerta por monto no emitido
	if ($asunto->fields['alerta_monto'] > 0) {
		list($total_monto_ult_cobro, $moneda_desde_ult_cobro) = $asunto->TotalMonto(false);
	} else {
		list($total_monto_ult_cobro, $moneda_desde_ult_cobro)  = array(0,1);
	}

	//Notificacion "Límite de monto"
	$total_monto = number_format($total_monto, 1, '.', '');
	$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);
	if (($total_monto > $asunto->fields['limite_monto']) && ($asunto->fields['limite_monto'] > 0) && ($asunto->fields['notificado_monto_excedido'] == 0)) {
		$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_monto'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['limite_monto'],
			'actual' => $total_monto,
			'moneda' => $moneda_total_monto);
		$asunto->Edit('notificado_monto_excedido', '1');
		$asunto->Write();
	}

	//Notificacion "Límite de horas"
	if (($total_horas_trabajadas > $asunto->fields['limite_hh']) && ($asunto->fields['limite_hh'] > 0 ) && ($asunto->fields['notificado_hr_excedido'] == 0)) {
		echo "Límite de horas\n";
		$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_horas'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['limite_hh'],
			'actual' => $total_horas_trabajadas);
		$asunto->Edit('notificado_hr_excedido', '1');
		$asunto->Write();
	}

	//Notificacion "Monto desde el último cobro"
	if (($total_monto_ult_cobro > $asunto->fields['alerta_monto']) && ($asunto->fields['alerta_monto'] > 0) && ($asunto->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
		$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['limite_ultimo_cobro'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['alerta_monto'],
			'actual' => $total_monto_ult_cobro,
			'moneda' => $moneda_desde_ult_cobro);
		$asunto->Edit('notificado_monto_excedido_ult_cobro', '1');
		$asunto->Write();
	}

	//Notificacion "Horas desde el último cobro"
	if (($total_horas_ult_cobro > $asunto->fields['alerta_hh']) && ($asunto->fields['alerta_hh'] > 0) && ($asunto->fields['notificado_hr_excedida_ult_cobro'] == 0)) {

		$dato_diario[$id_usuario]['asunto_excedido'][$asunto->fields['codigo_asunto']]['alerta_hh'] = array(
			'cliente' => $glosa_cliente,
			'asunto' => $asunto->fields['glosa_asunto'],
			'max' => $asunto->fields['alerta_hh'],
			'actual' => $total_horas_ult_cobro);
		$asunto->Edit('notificado_hr_excedida_ult_cobro', '1');
		$asunto->Write();
	}
}
// Mail diario - Tercer componente: alertas de limites de Contrato.
$query_contratos =
		"SELECT contrato.id_contrato,
				usuario_encargado_principal.id_usuario,
				usuario_encargado_principal.username,
				usuario_encargado_secundario.id_usuario,
				usuario_encargado_secundario.username,
				cliente.glosa_cliente,
				GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ',') as asuntos
		FROM contrato
		LEFT JOIN usuario usuario_encargado_principal ON (contrato.id_usuario_responsable = usuario_encargado_principal.id_usuario)
		LEFT JOIN usuario usuario_encargado_secundario ON (contrato.id_usuario_secundario = usuario_encargado_secundario.id_usuario)
		JOIN cliente ON (contrato.codigo_cliente = cliente.codigo_cliente)
		JOIN asunto ON (asunto.id_contrato = contrato.id_contrato)
		WHERE contrato.activo = 'SI' AND 
		cliente.activo = '1' AND 
		(contrato.id_usuario_responsable IS NOT NULL OR contrato.id_usuario_secundario IS NOT NULL OR (contrato.notificar_otros_correos IS NOT NULL AND contrato.notificar_otros_correos <> '')) GROUP BY contrato.id_contrato";
$result_contratos = mysql_query($query_contratos, $sesion->dbh) or Utiles::errorSQL($query_contratos, __FILE__, __LINE__, $sesion->dbh);
while (list($id_contrato, $id_usuario, $nombre_usuario, $id_usuario_secundario, $nombre_usuario_secundario, $glosa_cliente, $asuntos) = mysql_fetch_array($result_contratos)) {
	$contrato = new Contrato($sesion);
	//$cobro = new Cobro($sesion);
	$contrato->Load($id_contrato);

	// Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido.
	if ($contrato->fields['limite_monto'] > 0) {
		list($total_monto, $moneda_total_monto) = $contrato->TotalMonto();
	}
	if ($contrato->fields['limite_hh'] > 0) {
		$total_horas_trabajadas = $contrato->TotalHoras();
	}
	//Alerta de limite de horas no emitidas
	if ($contrato->fields['alerta_hh'] > 0) {
		$total_horas_ult_cobro = $contrato->TotalHoras(false);
	}
	//Significa que se requiere alerta por monto no emitido
	if ($contrato->fields['alerta_monto'] > 0) {
		list($total_monto_ult_cobro, $moneda_desde_ult_cobro) = $contrato->TotalMonto(false);
	}

	//Notificacion "Límite de monto"
	$total_monto = number_format($total_monto, 1);
	$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

	if (($total_monto > $contrato->fields['limite_monto']) && ($contrato->fields['limite_monto'] > 0) && ($contrato->fields['notificado_monto_excedido'] == 0)) {

		$contrato_excedido = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',', $asuntos),
			'max' => $contrato->fields['limite_monto'],
			'actual' => $total_monto,
			'moneda' => $moneda_total_monto
		);

		if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
			$dato_diario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $nombre_usuario;
			$dato_diario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_monto'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
			echo $contrato->fields['id_usuario_secundario'] . "\n";
			$dato_diario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $nombre_usuario_secundario;
			$dato_diario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_monto'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['notificar_otros_correos'])) {
			$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
			foreach ($otros_correos as $otro_correo) {
				if (empty($otro_correo)) {
					continue;
				}
				$dato_diario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_monto'] = $contrato_excedido;
			}
		}
		$contrato->Edit('notificado_monto_excedido', '1');
		$contrato->Write();
	}

	//Notificacion "Límite de horas"
	if (($total_horas_trabajadas > $contrato->fields['limite_hh']) && ($contrato->fields['limite_hh'] > 0 ) && ($contrato->fields['notificado_hr_excedido'] == 0)) {

		$contrato_excedido = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',', $asuntos),
			'max' => $contrato->fields['limite_hh'],
			'actual' => $total_horas_trabajadas
		);
		if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
			$dato_diario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $nombre_usuario;
			$dato_diario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_horas'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
			echo $contrato->fields['id_usuario_secundario'] . "\n";
			$dato_diario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $nombre_usuario_secundario;
			$dato_diario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_horas'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['notificar_otros_correos'])) {
			$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
			foreach ($otros_correos as $otro_correo) {
				if (empty($otro_correo)) {
					continue;
				}
				$dato_diario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_horas'] = $contrato_excedido;
			}
		}
		$contrato->Edit('notificado_hr_excedido', '1');
		$contrato->Write();
	}

	//Notificacion "Monto desde el último cobro"
	if (($total_monto_ult_cobro > $contrato->fields['alerta_monto']) && ($contrato->fields['alerta_monto'] > 0) && ($contrato->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
		$contrato_excedido = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',', $asuntos),
			'max' => $contrato->fields['alerta_monto'],
			'actual' => $total_monto_ult_cobro,
			'moneda' => $moneda_desde_ult_cobro
		);
		if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
			$dato_diario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $nombre_usuario;
			$dato_diario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_ultimo_cobro'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
			echo $contrato->fields['id_usuario_secundario'] . "\n";
			$dato_diario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $nombre_usuario_secundario;
			if(!isset($dato_diario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'])) $dato_diario[$contrato->fields['id_usuario_secundario']]['contrato_excedido']=array();
			$dato_diario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_ultimo_cobro'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['notificar_otros_correos'])) {
			$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
			foreach ($otros_correos as $otro_correo) {
				if (empty($otro_correo)) {
					continue;
				}
				$dato_diario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['limite_ultimo_cobro'] = $contrato_excedido;
			}
		}
		$contrato->Edit('notificado_monto_excedido_ult_cobro', '1');
		$contrato->Write();
	}

	//Notificacion "Horas desde el último cobro"
	if (($total_horas_ult_cobro > $contrato->fields['alerta_hh']) && ($contrato->fields['alerta_hh'] > 0) && ($contrato->fields['notificado_hr_excedida_ult_cobro'] == 0)) {
		$contrato_excedido = array(
			'cliente' => $glosa_cliente,
			'asunto' => explode(',', $asuntos),
			'max' => $contrato->fields['alerta_hh'],
			'actual' => $total_horas_ult_cobro
		);
		if (!empty($contrato->fields['id_usuario_responsable']) && $contrato->fields['notificar_encargado_principal'] == '1') {
			$dato_diario[$contrato->fields['id_usuario_responsable']]['nombre_pila'] = $nombre_usuario;
			$dato_diario[$contrato->fields['id_usuario_responsable']]['contrato_excedido'][$contrato->fields['id_contrato']]['alerta_hh'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['id_usuario_secundario']) && $contrato->fields['notificar_encargado_secundario'] == '1') {
			echo $contrato->fields['id_usuario_secundario'] . "\n";
			$dato_diario[$contrato->fields['id_usuario_secundario']]['nombre_pila'] = $nombre_usuario_secundario;
			$dato_diario[$contrato->fields['id_usuario_secundario']]['contrato_excedido'][$contrato->fields['id_contrato']]['alerta_hh'] = $contrato_excedido;
		}

		if (!empty($contrato->fields['notificar_otros_correos'])) {
			$otros_correos = explode(',', $contrato->fields['notificar_otros_correos']);
			foreach ($otros_correos as $otro_correo) {
				if (empty($otro_correo)) {
					continue;
				}
				$dato_diario[$otro_correo]['contrato_excedido'][$contrato->fields['id_contrato']]['alerta_hh'] = $contrato_excedido;
			}
		}
		$contrato->Edit('notificado_hr_excedida_ult_cobro', '1');
		$contrato->Write();
	}
}

// Mail diario - Cuarto componente: alertas de limites de Cliente.
$query_clientes =
		"SELECT cliente.codigo_cliente,
				usuario.id_usuario,
				usuario.username,
				cliente.glosa_cliente
		FROM cliente
		JOIN usuario ON (cliente.id_usuario_encargado = usuario.id_usuario)
		WHERE cliente.activo = '1'";
$result_clientes = mysql_query($query_clientes, $sesion->dbh) or Utiles::errorSQL($query_clientes, __FILE__, __LINE__, $sesion->dbh);
while (list($codigo_cliente, $id_usuario, $nombre_usuario, $glosa_cliente) = mysql_fetch_array($result_clientes)) {
	$cliente = new Cliente($sesion);
	$cliente->LoadByCodigo($codigo_cliente);

	$dato_diario[$id_usuario]['nombre_pila'] = $nombre_usuario;

	//Los cuatro límites: monto desde siempre, horas desde siempre, horas no emitidas, monto no emitido.
	if ($cliente->fields['limite_monto'] > 0) {
		list($total_monto, $moneda_total_monto) = $cliente->TotalMonto();
	}
	if ($cliente->fields['limite_hh'] > 0) {
		$total_horas_trabajadas = $cliente->TotalHoras();
	}
	//Alerta de limite de horas no emitidas
	if ($cliente->fields['alerta_hh'] > 0) {
		$total_horas_ult_cobro = $cliente->TotalHoras(false);
	}
	//Significa que se requiere alerta por monto no emitido
	if ($cliente->fields['alerta_monto'] > 0) {
		list($total_monto_ult_cobro, $moneda_desde_ult_cobro) = $cliente->TotalMonto(false);
	}


	//Notificacion "Límite de monto"
	$total_monto = number_format($total_monto, 1);
	$total_monto_ult_cobro = number_format($total_monto_ult_cobro, 1);

	if (($total_monto > $cliente->fields['limite_monto']) && ($cliente->fields['limite_monto'] > 0) && ($cliente->fields['notificado_monto_excedido'] == 0)) {
		$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_monto'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['limite_monto'],
			'actual' => $total_monto,
			'moneda' => $moneda_total_monto);
		$cliente->Edit('notificado_monto_excedido', '1');
		$cliente->Write();
	}

	//Notificacion "Límite de horas"
	if (($total_horas_trabajadas > $cliente->fields['limite_hh']) && ($cliente->fields['limite_hh'] > 0 ) && ($cliente->fields['notificado_hr_excedido'] == 0)) {
		$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_horas'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['limite_hh'],
			'actual' => $total_horas_trabajadas);
		$cliente->Edit('notificado_hr_excedido', '1');
		$cliente->Write();
	}

	//Notificacion "Monto desde el último cobro"
	if (($total_monto_ult_cobro > $cliente->fields['alerta_monto']) && ($cliente->fields['alerta_monto'] > 0) && ($cliente->fields['notificado_monto_excedido_ult_cobro'] == 0)) {
		$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['limite_ultimo_cobro'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['alerta_monto'],
			'actual' => $total_monto_ult_cobro,
			'moneda' => $moneda_desde_ult_cobro);
		$cliente->Edit('notificado_monto_excedido_ult_cobro', '1');
		$cliente->Write();
	}

	//Notificacion "Horas desde el último cobro"
	if (($total_horas_ult_cobro > $cliente->fields['alerta_hh']) && ($cliente->fields['alerta_hh'] > 0) && ($cliente->fields['notificado_hr_excedida_ult_cobro'] == 0)) {

		$dato_diario[$id_usuario]['cliente_excedido'][$cliente->fields['codigo_cliente']]['alerta_hh'] = array(
			'cliente' => $glosa_cliente,
			'max' => $cliente->fields['alerta_hh'],
			'actual' => $total_horas_ult_cobro);
		$cliente->Edit('notificado_hr_excedida_ult_cobro', '1');
		$cliente->Write();
	}
}

// Mail diario - quinto componente: cierre de cobranza
$query = "SELECT usuario.id_usuario, usuario.username, usuario.restriccion_mensual from usuario
							JOIN usuario_permiso USING( id_usuario )
						 where codigo_permiso='PRO' and activo=1";
$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
while (list($id_usuario, $username, $restriccion_mensual) = mysql_fetch_array($result)) {
	$dato_diario[$id_usuario]['nombre_pila'] = $username;
	if (method_exists('Conf', 'GetConf')) {
		$adelanto_alerta_fin_de_mes = (int) Conf::GetConf($sesion, 'AdelantoAlertaFinDeMes');
	}
	$manana = mktime(date('G'), date('i'), date('s'), date('n'), date('j') + $adelanto_alerta_fin_de_mes, date('Y'));

	/* Cuarto componente: Mail de alerta mensual de cierre de cobranza */
	if (UtilesApp::GetConf($sesion, 'CorreosMensuales') && UtilesApp::esUltimoDiaHabilDelMes($manana)) {
		$dato_diario[$id_usuario]['fin_de_mes'] = 1;
	}

	if (UtilesApp::GetConf($sesion, 'CorreosMensuales') && UtilesApp::esSegundoDiaHabilDelMes()) {
		// horas ingresadas el mes anterior
		$mes = date('n') - 1;
		$ano = date('Y');
		if ($mes == 0) {
			$mes = 12;
			--$ano;
		}

		$query = "SELECT SUM(TIME_TO_SEC(duracion))/3600
					FROM trabajo
					WHERE id_usuario = '$id_usuario'
						AND MONTH(fecha) = $mes
						AND YEAR(fecha) = $ano";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($horas_mes) = mysql_fetch_array($resp);
		if (!$horas_mes)
			$horas_mes = '0.00';
		if ($horas_mes < $restriccion_mensual) {
			$dato_diario[$id_usuario]['restriccion_mensual'] = array('actual' => $horas_mes, 'min' => $restriccion_mensual);
		}
	}
}

################################################################
// Mail Diario: Sexto componente: Alertas de ingreso de horas
if (date("N") < 6) { //Lunes a Viernes
	$opc = 'mail_retrasos';
	$query = "SELECT usuario.id_usuario
						FROM usuario
						JOIN usuario_permiso USING(id_usuario)
						WHERE codigo_permiso='PRO' AND alerta_diaria = '1' AND retraso_max_notificado = 0 AND activo=1";
	$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	while ($row = mysql_fetch_array($result)) {
		$id_usuario = $row["id_usuario"];
		$prof = new Usuario($sesion);
		$prof->LoadId($id_usuario);

		if ($prof->fields['retraso_max'] > 0) {
			//Calcular horas de retraso excluyendo los fines de semana

			$query = "SELECT MAX(fecha_creacion) FROM trabajo WHERE id_usuario='$id_usuario'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($ultima_fecha_ingreso) = mysql_fetch_array($resp);
			$start = strtotime($ultima_fecha_ingreso);
			$end = strtotime(date("Y-m-d"));
			$dias_retraso = 0;
			while ($start <= $end) {
				if (date('N', $start) <= 5) {
					$dias_retraso++;
				}
				$start += 86400;
			}
			$horas_retraso = 24 * $dias_retraso;

			//Calcular horas de retraso excluyendo los fines de semana como query (Deje el codigo de arriba porque es mas entendible)
			/* $query = "
			  SELECT
			  24 * ( (floor( datediff( NOW( ) , MAX( fecha_creacion ) ) /7 ) *5 ) +
			  CASE dayofweek( MAX( fecha_creacion) )
			  WHEN 1
			  THEN mod( datediff( NOW( ) , MAX( fecha_creacion ) ) , 7 ) -2
			  WHEN 7
			  THEN mod( datediff( NOW( ) , MAX( fecha_creacion ) ) , 7 ) -1
			  ELSE LEAST( 7 - dayofweek( MAX( fecha_creacion ) ) , mod( datediff( NOW( ) , MAX( fecha_creacion ) ) , 7 ) )
			  END) AS 'horas_retraso'
			  FROM trabajo
			  WHERE id_usuario = '$id_usuario'";
			  $resp = mysql_query($query,$sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
			  list($horas_retraso) = mysql_fetch_array($resp); */

			if ($horas_retraso > $prof->fields['retraso_max']) {
				$dato_diario[$id_usuario]['retraso_max'] = array('actual' => $horas_retraso, 'max' => $prof->fields['retraso_max']);
				$query = "UPDATE usuario SET retraso_max_notificado = 1 WHERE id_usuario = '$id_usuario'";
				mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			}
		}

		if ($prof->fields['restriccion_diario'] > 0) {
			$query = "SELECT SUM( TIME_TO_SEC( duracion )/3600 ) FROM trabajo WHERE id_usuario = '$id_usuario' AND fecha = DATE( DATE_ADD( NOW(), INTERVAL $timezone_offset HOUR ) ) ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list($cantidad_horas) = mysql_fetch_array($resp);
			if (!$cantidad_horas) {
				$cantidad_horas = 0;
			}
			if ($cantidad_horas < $prof->fields['restriccion_diario']) {
				$cantidad_horas = number_format($cantidad_horas, 1, ',', '.');
				$dato_diario[$id_usuario]['restriccion_diario'] = array('actual' => $cantidad_horas, 'min' => $prof->fields['restriccion_diario']);
			}
		}
	}
}

//Mail diario: septimo componente: Alertas de Tareas
//Ya que los mails se envían al final del día, se debe enviar la alerta de 1 día si tiene plazo pasado mañana.
//FFF Comprueba la existencia de tarea.alerta. Si no existe, lo crea. Compensa la posible falta del update 3.69
if(!UtilesApp::ExisteCampo('alerta','tarea',$sesion->dbh)) mysql_query("ALTER TABLE `tarea` ADD `alerta` INT( 2 ) NOT NULL DEFAULT '0' AFTER `prioridad` ;",$sesion->dbh) ;
$query = "SELECT cliente.glosa_cliente,
					asunto.glosa_asunto,
					CONCAT_WS(' ',e.nombre, e.apellido1, LEFT(e.apellido2,1)) AS nombre_encargado,
					CONCAT_WS(' ',r.nombre, r.apellido1, LEFT(r.apellido2,1)) AS nombre_revisor,
					e.id_usuario as encargado,
					r.id_usuario as revisor,
					tarea.fecha_entrega,
					tarea.nombre,
					tarea.detalle,
					tarea.estado,
					tarea.alerta
			FROM tarea
			JOIN cliente ON tarea.codigo_cliente = cliente.codigo_cliente
			JOIN asunto ON tarea.codigo_asunto = asunto.codigo_asunto
			LEFT JOIN usuario AS e ON e.id_usuario = tarea.usuario_encargado
			JOIN usuario AS r ON r.id_usuario = tarea.usuario_revisor
			WHERE
				alerta > 0
				AND
				DATE_ADD(NOW(), INTERVAL (alerta) DAY) < fecha_entrega
				AND
				DATE_ADD(NOW(), INTERVAL (alerta+1) DAY) > fecha_entrega
				AND estado <> 'Lista'";
$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

function glosa_dia($alerta_previo) {
	if ($alerta_previo == 1) {
		return $alerta_previo . ' ' . __('d&iacute;a previo');
	}
	return $alerta_previo . ' ' . __('d&iacute;as previos');
}

$tarea = new Tarea($sesion);
while (list($cliente, $asunto, $nombre_encargado, $nombre_revisor, $id_encargado, $id_revisor, $fecha_entrega, $nombre, $detalle, $estado, $alerta_previo) = mysql_fetch_array($result)) {
	$t = array();
	$t['cliente'] = $cliente;
	$t['asunto'] = $asunto;
	$t['fecha_entrega'] = $fecha_entrega;
	$t['nombre'] = $nombre;
	$t['detalle'] = $detalle;
	$t['estado'] = $tarea->IconoEstado($estado, true);
	$t['alerta'] = __('Alerta') . ' - ' . __('Fecha de entrega') . ': ' . Utiles::sql2fecha($fecha_entrega, '%d-%m-%y') . '. ' . __('Se ha activado la alerta de') . ' ' . glosa_dia($alerta_previo) . '.<br>';
	if ($id_encargado) {
		$t['alerta'] .= '&nbsp;&nbsp;' . __('Encargado') . ': ' . $nombre_encargado . '.<br>';
	}
	$t['alerta'] .= '&nbsp;&nbsp;' . __('Revisor') . ': ' . $nombre_revisor . '.';

	if ($estado == 'Por Asignar' || $estado == 'Por Asignar' || !$id_encargado) {
		$dato_diario[$id_revisor]['tarea_alerta'][] = $t;
	} else {
		$dato_diario[$id_encargado]['tarea_alerta'][] = $t;
	}
}

// refresca los cobros pagados ayer
$update1 = "update trabajo join cobro c on trabajo.id_cobro=c.id_cobro set trabajo.estadocobro=c.estado where c.fecha_touch>= trabajo.fecha_touch ;";
$update2 = "update cta_corriente join cobro c on  cta_corriente.id_cobro=c.id_cobro  set cta_corriente.estadocobro=c.estado  where c.fecha_touch >=cta_corriente.fecha_touch;";
$update3 = "update tramite join cobro c on tramite.id_cobro=c.id_cobro set tramite.estadocobro=c.estado where c.fecha_touch >= tramite.fecha_touch ;";
$resp = mysql_query($update1, $sesion->dbh);
$resp = mysql_query($update2, $sesion->dbh);
$resp = mysql_query($update3, $sesion->dbh);
$AtacheSecundarioSoloAsunto = UtilesApp::GetConf($sesion, 'AtacheSecundarioSoloAsunto');



$updategastos="update olap_liquidaciones ol join cta_corriente cc on ol.id_unico=(20000000+cc.id_movimiento)
set
ol.id_usuario_entry=cc.id_usuario_orden,
ol.codigo_asunto= cc.codigo_asunto,
ol.cobrable=cc.cobrable,
ol.incluir_en_cobro= if(cc.incluir_en_cobro='SI',2,1) ,
ol.duracion_cobrada_segs=0,
ol.monto_cobrable=IF( ISNULL( cc.egreso ) , -1, 1 ) * cc.monto_cobrable,
ol.id_moneda_entry= cc.id_moneda,
ol.fechaentry=cc.fecha,
ol.id_cobro=cc.id_cobro,
ol.estadocobro=cc.estadocobro,
ol.fecha_modificacion=cc.fecha_touch
where ol.tipo='GAS' and cc.fecha_touch>ol.fecha_modificacion";

$updatetrabajos="update olap_liquidaciones ol join trabajo tr on ol.id_unico=(10000000 + tr.id_trabajo)
set
  
ol.id_usuario_entry=tr.id_usuario,
ol.codigo_asunto=tr.codigo_asunto,
ol.cobrable=tr.cobrable,
 ol.duracion_cobrada_segs=TIME_TO_SEC( duracion_cobrada ),
 ol.monto_thh=TIME_TO_SEC( duracion_cobrada ) * tarifa_hh,
ol.monto_thh_estandar=TIME_TO_SEC( duracion_cobrada ) * tarifa_hh_estandar,
ol.id_moneda_entry= tr.id_moneda,
ol.fechaentry=tr.fecha,
ol.id_cobro=tr.id_cobro,
ol.estadocobro=tr.estadocobro,
ol.fecha_modificacion=tr.fecha_touch
where ol.tipo='TRB'
AND tr.fecha_touch> ol.fecha_modificacion ";

$updatetramite="update olap_liquidaciones ol join tramite tram on ol.id_unico=( 30000000 + tram.id_tramite)
set
ol.id_usuario_entry=tram.id_usuario,
ol.codigo_asunto=tram.codigo_asunto,
ol.cobrable=tram.cobrable,
ol.incluir_en_cobro=2,
ol.duracion_cobrada_segs=TIME_TO_SEC(duracion) ,
ol.monto_cobrable=tram.tarifa_tramite,
ol.id_moneda_entry=tram.id_moneda_tramite,
ol.fechaentry=tram.fecha,
ol.id_cobro=tram.id_cobro,
ol.estadocobro=tram.estadocobro ,
ol.fecha_modificacion=tram.fecha_touch
where ol.tipo='TRA'

 and tram.fecha_touch>ol.fecha_modificacion";
 	
 
	$resp = mysql_query($updategastos, $sesion->dbh);
	$resp = mysql_query($updatetrabajos, $sesion->dbh);
	$resp = mysql_query($updatetramite, $sesion->dbh);
	


//Correo Hitos
$cobro_pendiete = new CobroPendiente($sesion);
$hitos_cumplidos = $cobro_pendiete->ObtenerHitosCumplidosParaCorreos();

foreach ($hitos_cumplidos as $usuario_responable => $hito_cumplido) {
	$dato_diario[$usuario_responable]['hitos_cumplidos'][] = $hito_cumplido;
}


if (UtilesApp::GetConf($sesion, 'AlertaDiariaHorasMensuales')) {
	$query = "SELECT id_usuario, TIME_FORMAT(SEC_TO_TIME(SUM(TIME_TO_SEC(duracion))), '%H:%i') as horas
					FROM trabajo
					WHERE fecha >= '" . date('Y-m') . "-01'
					GROUP BY id_usuario";
	$result = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
	while (list($id_usuario, $horas) = mysql_fetch_array($result)) {
		$dato_diario[$id_usuario]['horas_mensuales'] = $horas;
	}
}

// Fin del mail diario. Envío.
$mensajes = $notificacion->mensajeDiario($dato_diario);

foreach ($mensajes as $id_usuario => $mensaje) {
	if ($argv[1] == 'correo' || isset($_GET['correo'])) {
		$alerta->EnviarAlertaProfesional($id_usuario, $mensaje, $sesion, false);
	}
}

if (isset($desplegar_correo) && $desplegar_correo == 'aefgaeddfesdg23k1h3kk1') {
	var_dump($dato_diario);
	echo implode('<br><br><br>', $mensajes);
}

if (date("j") == 1) {
	CobroPendiente::GenerarCobrosPeriodicos($sesion);
}

/**
 * Notificacion de suspencion de pago por comision por concepto de
 * presentacion de nuevos clientes.
 */
if (UtilesApp::GetConf($sesion, 'UsoPagoComisionNuevoCliente') == 1) {
	$max = UtilesApp::GetConf($sesion, 'UsoPagoComisionNuevoClienteTiempo');
	$max = $max && is_numeric($max) ? $max : 730; /* 730 dias */

	$email = UtilesApp::GetConf($sesion, 'UsoPagoComisionNuevoClienteEmail');
	$email = $email ? $email : 'soporte@lemontech.cl';

	$column = 'c.id_cliente, c.fecha_creacion';

	$query = 'SELECT %s FROM cliente c, usuario u ';
	$query .= 'WHERE c.id_usuario_encargado = u.id_usuario ';
	$query .= 'AND UNIX_TIMESTAMP(CURRENT_DATE)-UNIX_TIMESTAMP(c.fecha_creacion) >= ' . $max;

	$r = mysql_query(sprintf($query, 'COUNT(*) AS cant'), $sesion->dbh)
			or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

	if (!$r) {
		
	} else {
		$cant = array_shift(mysql_fetch_row($r));
		if ($cant < 1) {
			
		} else {
			$pages = ceil($cant / 10);
			$query .= ' ORDER BY c.id_cliente DESC LIMIT %s, %s';

			for ($i = 10; $i <= $cant; $i = $i + 10) {
				$columns = "c.id_cliente, CONCAT(u.nombre, ' ', u.apellido1, ' ', u.apellido2) AS usuario, c.glosa_cliente";

				$q = sprintf($query, $columns, ($i - 10), $i);
				$r = mysql_query($q, $sesion->dbh)
						or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

				$message = 'El usuario "%s" deja de recibir comision por concepto de captacion del cliente "%s"';

				while ($row = mysql_fetch_object($r)) {
						$from =  html_entity_decode(Conf::AppName());

					$m = sprintf($message, $row->usuario, $row->glosa_cliente);
					Utiles::Insertar($sesion, __("Alerta de facturación de tiempos") . " $from", $m, $email, false);
				}
			}
		}
	}
}
?>
