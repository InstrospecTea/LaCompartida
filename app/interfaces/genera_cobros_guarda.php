<?php

require_once dirname(__FILE__) . '/../conf.php';

$tini = time();

$Sesion = new Sesion(array('COB', 'DAT'));
PhpConsole::start(false, true, null, 1);

if (empty($_GET['generar_silenciosamente'])) {
	$Pagina = new Pagina($Sesion);
}

if ((!isset($_POST['cobrosencero']) || $_POST['cobrosencero'] == 0 ) && isset($_GET['generar_silenciosamente'])) {
	$forzar = false;
} else {
	$forzar = true;
}

// Retrocompatibilidad con funcionamiento de Conf en GTD Solicitado por @gtigre el 26-03-2014
if (Conf::GetConf($Sesion, 'UsaFechaDesdeCobranza') && empty($fecha_ini)) {
	$query = "SELECT DATE_ADD(MAX(fecha_fin), INTERVAL 1 DAY) FROM cobro WHERE id_contrato = '" . $id_contrato . "'";
	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	list($fecha_ini_cobro) = mysql_fetch_array($resp);
} else {
	if (!empty($fecha_ini)) {
		$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);
	}
}

//si no me llega uno, es 0
$incluye_gastos = !empty($incluye_gastos);
$incluye_honorarios = !empty($incluye_honorarios);
//si no me llega ninguno, asumo q son los 2 (comportamiento anterior)

if (!$incluye_gastos && !$incluye_honorarios) {
	$incluye_gastos = $incluye_honorarios = true;
}
if ($tipo_liquidacion) { //1:honorarios, 2:gastos, 3:mixtas
	$incluye_honorarios = $tipo_liquidacion & 1 ? true : false;
	$incluye_gastos = $tipo_liquidacion & 2 ? true : false;
}

if ($individual) {
	$Cobro = new Cobro($Sesion);
	if ($id_contrato) {

		$id_proceso_nuevo = $Cobro->GeneraProceso();

		if (empty($monto)) {
			$monto = '';
		}
		if (empty($id_cobro_pendiente)) {
			$id_cobro_pendiente = '';
		}

		$id = $Cobro->PrepararCobro(
				$fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $id_contrato, $forzar, $id_proceso_nuevo, $monto, $id_cobro_pendiente, false, false, $incluye_gastos, $incluye_honorarios
		);

		if ($id) {
			if (isset($_GET['generar_silenciosamente']) && $_GET['generar_silenciosamente'] == 1) {
				die($id);
			} else {
				$Pagina->Redirect('cobros5.php?id_cobro=' . $id . '&popup=1');
			}
		}
	}
}

$Contrato = new Contrato($Sesion);

if ($codigo_cliente_secundario) {
	$cliente = new Cliente($Sesion);
	$cliente->LoadByCodigoSecundario($codigo_cliente_secundario);
	$codigo_cliente = $cliente->fields['codigo_cliente'];
}

if ($codigo_asunto && !$id_contrato) {
	$Contrato->LoadByCodigoAsunto($codigo_asunto);
	$id_contrato = $Contrato->fields['id_contrato'];
}

####### WHERE SQL ########
if ($print || $emitir) {

	$where = 1;
	$join_cobro_cliente = "";
	if ($activo) {
		$where .= " AND contrato.activo = 'SI' ";
	} else {
		$where .= " AND contrato.activo = 'NO' ";
	}
	if ($id_usuario) {
		$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
	}
	if ($id_usuario_secundario) {
		$where .= " AND contrato.id_usuario_secundario = '$id_usuario_secundario' ";
	}

	if ($codigo_cliente) {
		$where .= " AND contrato.codigo_cliente = '$codigo_cliente' ";
	}
	if ($id_contrato) {
		$where .= " AND contrato.id_contrato = '$id_contrato' ";
	}
	if ($id_grupo_cliente) {
		$join_cobro_cliente = " JOIN cliente ON cobro.codigo_cliente = cliente.codigo_cliente ";
		$where .= " AND cliente.id_grupo_cliente = '$id_grupo_cliente' ";
	}
	if ($rango == '' && $usar_periodo == 1) {
		$fecha_periodo_ini = $fecha_anio . '-' . $fecha_mes . '-01';
		$fecha_periodo_fin = $fecha_anio . '-' . $fecha_mes . '-31';
		$where .= " AND cobro.fecha_creacion >= '$fecha_periodo_ini' AND cobro.fecha_creacion <= '$fecha_periodo_fin' ";
	} else if ($fecha_periodo_ini != '' && $fecha_periodo_fin != '' && $rango == 1 && $usar_periodo == 1) {
		$where .= " AND cobro.fecha_creacion >= '" . Utiles::fecha2sql($fecha_periodo_ini) . "' AND cobro.fecha_creacion <= '" . Utiles::fecha2sql($fecha_periodo_fin) . "' ";
	} else {
		if (!empty($fecha_ini)) {
			$where .= " AND cobro.fecha_ini >= '" . Utiles::fecha2sql($fecha_ini) . "' ";
		}
		if (!empty($fecha_fin)) {
			$where .= " AND cobro.fecha_fin <= '" . Utiles::fecha2sql($fecha_fin) . "' ";
		}
	}
	if ($forma_cobro) {
		$where .= " AND contrato.forma_cobro = '$forma_cobro' ";
	}
	if ($tipo_liquidacion) {
		$where .= " AND cobro.incluye_gastos = '$incluye_gastos' AND cobro.incluye_honorarios = '$incluye_honorarios' ";
	}

	if (!isset($_POST['cobrosencero']) || $_POST['cobrosencero'] == 0) {
		$where .= " AND ( cobro.monto_subtotal > 0 OR cobro.subtotal_gastos>0 OR cobro.forma_cobro != 'TASA' ) ";
	}
	($Slim = Slim::getInstance('default', true)) ? $Slim->applyHook('hook_query_generar_cobro') : false;

	$url = "genera_cobros.php?activo=$activo&id_usuario=$id_usuario&codigo_cliente=$codigo_cliente&fecha_ini=$fecha_ini" .
			"&fecha_fin=$fecha_fin&opc=buscar&rango=$rango&fecha_anio=$fecha_anio&fecha_mes=$fecha_mes&fecha_periodo_ini=$fecha_periodo_ini" .
			"&fecha_periodo_fin=$fecha_periodo_fin&usar_periodo=$usar_periodo&tipo_liquidacion=$tipo_liquidacion&forma_cobro=$forma_cobro&codigo_asunto=$codigo_asunto";

}
####### END #########
# IMPRESION
if ($print) {
	$NotaCobro = new NotaCobro($Sesion);
	$mincartaST = $Sesion->pdodbh->query("select min(id_carta) from carta");
	$mincartas = $mincartaST->fetchAll(PDO::FETCH_COLUMN, 0);
	$mincarta = $mincartas[0];

	$query = "
		SELECT
			cobro.id_cobro,
			cobro.id_usuario,
			cobro.codigo_cliente,
			cobro.id_contrato,
			contrato.id_carta,
			contrato.codigo_idioma,
			cobro.estado,
			cobro.opc_papel,
			cobro.subtotal_gastos
		FROM cobro
			JOIN contrato ON cobro.id_contrato = contrato.id_contrato
			LEFT JOIN cliente ON cliente.codigo_cliente = contrato.codigo_cliente
				WHERE $where AND cobro.estado IN ( 'CREADO', 'EN REVISION' ) ORDER BY cliente.glosa_cliente";

	try {
		$cobroST = $Sesion->pdodbh->query($query);
		$cobroRT = $cobroST->fetchAll(PDO::FETCH_ASSOC);
		$totaldecobros = count($cobroRT);
		$counter = 0;
		$cantidaddecobros = count($cobroRT);
		$error_logfile = ini_get('error_log');
		$logdir = dirname($error_logfile);

		$html = "";
		if ($totaldecobros > 0) {

			foreach ($cobroRT as $cob) {

				set_time_limit(100);

				if (!$NotaCobro->Load($cob['id_cobro'])) {
					continue;
				}

				if ($opcion != 'cartas') {
				 	$NotaCobro->fields['id_carta'] = null;
				} else {
				 	if (!$NotaCobro->fields['id_carta']) {
				 		$NotaCobro->fields['id_carta'] = $mincarta;
				 		$NotaCobro->fields['opc_ver_carta'] = 1;
				 	}
				}

				if ($cob['subtotal_gastos'] == 0) {
				   $NotaCobro->fields['opc_ver_gastos'] = 0;
				}

				$NotaCobro->LoadAsuntos();

				$lang_archivo = $cob['codigo_idioma'] . '.php';

				require_once Conf::ServerDir() . "/lang/$lang_archivo";

				$html = $NotaCobro->GeneraHTMLCobro(true, $id_formato);

				$opc_papel = $cob['opc_papel'];
				$id_carta = $cob['id_carta'];
				$cssData = UtilesApp::TemplateCartaCSS($Sesion, $NotaCobro->fields['id_carta']);

				if ($html) {

					$cssData .= UtilesApp::CSSCobro($Sesion);

					if (is_object($doc)) {
				 		$doc->newSession($html);
				 	} else {

				 		$orientacion_papel = Conf::GetConf($Sesion, 'OrientacionPapelPorDefecto');

				 		if (empty($orientacion_papel) || !in_array($orientacion_papel, array('PORTRAIT', 'LANDSCAPE'))) {
				 			$orientacion_papel = 'PORTRAIT';
				 		}

						$doc = new DocGenerator($html, $cssData, $opc_papel, 1, $orientacion_papel, 1.5, 2.0, 2.0, 2.0, $NotaCobro->fields['estado']);
				 	}
					$doc->chunkedOutput("cobro_masivo_$id_usuario.doc");
				}

			}
			$doc->endChunkedOutput("cobro_masivo_$id_usuario.doc");
		} else {
			echo "\n<script type=\"text/javascript\">var pause = null;	pause = setTimeout('window.history.back()',3000);	</script>\n";
			die('No hay datos para su criterio de búsqueda');
		}
	} catch (PDOException $pdoe) {
		debug($pdoe->getTraceAsString());
	} catch (Exception $e) {
		debug($e->getTraceAsString());
	}
	if (is_object($Pagina)) {
		$Pagina->Redirect($url);
	}

} else if ($emitir) {

	$Cobro = new Cobro($Sesion);
	$query = "
		SELECT
			cobro.id_cobro,
			cobro.id_usuario,
			cobro.codigo_cliente,
			cobro.id_contrato,
			contrato.id_carta,
			cobro.estado,
			cobro.opc_papel,
			contrato.id_carta
		FROM cobro
			JOIN contrato ON cobro.id_contrato = contrato.id_contrato
			LEFT JOIN cliente ON cliente.codigo_cliente=cobro.codigo_cliente
				WHERE $where AND cobro.estado IN ( 'CREADO', 'EN REVISION' )";

	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

	while ($cob = mysql_fetch_array($resp)) {
		set_time_limit(100);
		if ($Cobro->Load($cob['id_cobro'])) {
			$Cobro->Edit('id_carta', $cob['id_carta']);
			$ret = $Cobro->GuardarCobro(true);
			$Cobro->Edit('etapa_cobro', '5');
			$Cobro->Edit('fecha_emision', date('Y-m-d H:i:s'));
			$estado_anterior = $Cobro->fields['estado'];
			$Cobro->Edit('estado', 'EMITIDO');
			if ($ret == '' && $estado_anterior != 'EMITIDO') {
				$Cobro->Write();
			}
		}
	}
	$url .= '&cobros_emitidos=1';
	$Pagina->Redirect($url);

} else { #Creación masiva de cobros

	$where = 1;
	$join = "";
	$newcobro = array();
	if ($tipo_liquidacion) {
		$where .= " AND contrato.separar_liquidaciones = " . ($tipo_liquidacion == '3' ? 0 : 1) . " ";
	}
	if ($activo) {
		$where .= " AND contrato.activo = 'SI' ";
	} else {
		$where .= " AND contrato.activo = 'NO' ";
	}
	if ($id_usuario) {
		$where .= " AND contrato.id_usuario_responsable = '$id_usuario' ";
	}
	if ($codigo_cliente) {
		$where .= " AND cliente.codigo_cliente = '$codigo_cliente' ";
	}
	if ($id_contrato) {
		$where .= " AND contrato.id_contrato = '$id_contrato' ";
	}
	if ($id_grupo_cliente) {
		$where .= " AND cliente.id_grupo_cliente = '$id_grupo_cliente' ";
	}
	if ($forma_cobro) {
		$where .= " AND contrato.forma_cobro = '$forma_cobro' ";
	}

	$join .= "LEFT JOIN cobro_pendiente ON ( cobro_pendiente.id_contrato=contrato.id_contrato AND cobro_pendiente.id_cobro IS NULL AND cobro_pendiente.fecha_cobro >= NOW() )";

	$where .= " AND cobro_pendiente.id_cobro_pendiente IS NULL ";

	$query = "SELECT SQL_CALC_FOUND_ROWS contrato.id_contrato,cliente.codigo_cliente, contrato.id_moneda, contrato.forma_cobro, contrato.monto, contrato.retainer_horas, contrato.id_moneda, contrato.separar_liquidaciones
				FROM contrato
				$join
				JOIN tarifa ON contrato.id_tarifa = tarifa.id_tarifa
				LEFT JOIN asunto ON asunto.id_contrato=contrato.id_contrato
				JOIN cliente ON cliente.codigo_cliente=contrato.codigo_cliente
				JOIN prm_moneda  ON (prm_moneda.id_moneda=contrato.id_moneda)
				WHERE $where AND contrato.incluir_en_cierre = 1
				AND contrato.forma_cobro != 'HITOS'
				GROUP BY contrato.id_contrato";

	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
	//cobros solo gastos
	if ($gastos) { // desde genera_cobros.php estoy forzando que solamente incluya gastos
		while ($contra = mysql_fetch_array($resp)) {
			set_time_limit(100);
			//Mala documentaciÃ³n!!! Que significa $contra? Que hace GeneraProceso??? ICC
			// por lo que logré entender : $contra = contrato, y GeneraProceso es la que genera un cobro nuevo vacío y devuelve el id, para ingresar los valores (ESM )
			$Cobro = new Cobro($Sesion);
			if (!$id_proceso_nuevo) {
				$id_proceso_nuevo = $Cobro->GeneraProceso();
			}
			//Por conf se permite el uso de la fecha desde
			$fecha_ini_cobro = "";
			if (UtilesApp::GetConf($Sesion, 'UsaFechaDesdeCobranza') && $fecha_ini) {
				$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);  //Comentado por SM 28.01.2011 el conf nunca se usa
			}

			$newcobro[] = $Cobro->PrepararCobro($fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $contra['id_contrato'], false, $id_proceso_nuevo, '', '', true, true, true, false);
		}
		//fin gastos
	} if ($solohh) { // desde genera_cobros.php estoy forzando que solamente incluya honorarios
		while ($contra = mysql_fetch_array($resp)) {
			set_time_limit(100);
			//Mala documentación!!! Que significa $contra? Que hace GeneraProceso??? ICC
			// por lo que logré entender : $contra = contrato, y GeneraProceso es la que genera un cobro nuevo vacío y devuelve el id, para ingresar los valores (ESM)
			$Cobro = new Cobro($Sesion);
			if (!$id_proceso_nuevo) {
				$id_proceso_nuevo = $Cobro->GeneraProceso();
			}
			//Por conf se permite el uso de la fecha desde
			$fecha_ini_cobro = "";
			if (UtilesApp::GetConf($Sesion, 'UsaFechaDesdeCobranza') && $fecha_ini) {
				$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);  //Comentado por SM 28.01.2011 el conf nunca se usa
			}

			$newcobro[] = $Cobro->PrepararCobro($fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $contra['id_contrato'], false, $id_proceso_nuevo, '', '', false, false, false, true);
		}
		//fin gastos
	} else {
		//cobros wip
		while ($contra = mysql_fetch_array($resp)) {
			$Cobro = new Cobro($Sesion);
			if (!$id_proceso_nuevo) {
				$id_proceso_nuevo = $Cobro->GeneraProceso();
			}
			//Por conf se permite el uso de la fecha desde
			$fecha_ini_cobro = "";
			if (UtilesApp::GetConf($Sesion, 'UsaFechaDesdeCobranza') && $fecha_ini) {
				$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini); // Comentado por SM 28.01.2011 el conf nunca se usa
			}

			//si se separan pero se piden ambos, se generan 2 cobros
			if ($contra['separar_liquidaciones'] == '1' && $incluye_gastos && $incluye_honorarios) {
				$newcobro[] = $Cobro->PrepararCobro(
						$fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $contra['id_contrato'], $forzar, $id_proceso_nuevo, '', '', false, false, false, true);
				$Cobro = new Cobro($Sesion);
				$id_proceso_nuevo = $Cobro->GeneraProceso();
				$newcobro[] = $Cobro->PrepararCobro(
						$fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $contra['id_contrato'], $forzar, $id_proceso_nuevo, '', '', false, false, true, false);
			} else { //no se separan y se piden los 2, o se separan y se pide 1 (no+1 se filtra en la query)
				$newcobro[] = $Cobro->PrepararCobro(
						$fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $contra['id_contrato'], $forzar, $id_proceso_nuevo, '', '', false, false, $incluye_gastos, $incluye_honorarios);
			}
		}
	}
	#fin cobros wip

	$Contrato->SetIncluirEnCierre($Sesion);

	if (isset($_GET['generar_silenciosamente']) && $_GET['generar_silenciosamente'] == 1) {

		unset($Sesion);
		unset($Cobro);
		unset($Contrato);
		die('Proceso ' . $id_proceso_nuevo . ' Cobros ' . implode($newcobro));
	} else {
		$Pagina->Redirect(
				"genera_cobros.php?activo=$activo&id_usuario=$id_usuario&codigo_cliente=$codigo_cliente&fecha_ini=$fecha_ini" .
				"&fecha_fin=$fecha_fin&id_grupo_cliente=$id_grupo_cliente&fecha_ini=$fecha_ini&opc=buscar&cobros_generado=1" .
				"&tipo_liquidacion=$tipo_liquidacion&forma_cobro=$forma_cobro&codigo_asunto=$codigo_asunto"
		);
	}
}

