<?php

require_once dirname(__FILE__) . '/../conf.php';

if ($_POST['autologin'] && $_POST['hash'] == Conf::hash()) {
	$Sesion = new Sesion();
	$Sesion->usuario = new Usuario($Sesion);
	$Sesion->usuario->LoadId($_POST['id_usuario_login']);
} else {
	$Sesion = new Sesion(array('COB', 'DAT'));
}

if (empty($_GET['generar_silenciosamente'])) {
	$Pagina = new Pagina($Sesion);
}

$opcion = explode(',', $opcion);
$imprimir_cartas = $opcion[0] == 'cartas';
$agrupar_cartas = $opcion[1] == 'agrupar';

if ((!isset($_POST['cobrosencero']) || $_POST['cobrosencero'] == 0 ) && isset($_GET['generar_silenciosamente'])) {
	$forzar = false;
} else {
	$forzar = true;
}

// Retrocompatibilidad con funcionamiento de Conf en GTD Solicitado por @gtigre el 26-03-2014
if (Conf::GetConf($Sesion, 'UsaFechaDesdeCobranza') && empty($fecha_ini)) {
	$query = "SELECT DATE_ADD(MAX(fecha_fin), INTERVAL 1 DAY) FROM cobro WHERE id_contrato = '$id_contrato'";
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

$Contrato = new Contrato($Sesion);

if ($individual && $id_contrato) {
	Log::write(" |- Contrato: {$id_contrato}", Cobro::PROCESS_NAME);

	set_time_limit(100);
	//Mala documentación!!! Que significa $contra? Que hace GeneraProceso??? ICC
	// por lo que logré entender : $contra = contrato, y GeneraProceso es la que genera un cobro nuevo vacío y devuelve el id, para ingresar los valores (ESM)
	$Cobro = new Cobro($Sesion);

	//Por conf se permite el uso de la fecha desde
	$fecha_ini_cobro = '';
	if (Conf::GetConf($Sesion, 'UsaFechaDesdeCobranza') && $fecha_ini) {
		$fecha_ini_cobro = Utiles::fecha2sql($fecha_ini);  //Comentado por SM 28.01.2011 el conf nunca se usa
	}

	if (!$id_proceso_nuevo) {
		$id_proceso_nuevo = $Cobro->GeneraProceso();
	}

	$con_gastos = $solo_gastos = false;

	Log::write(' |  |- no separar_liquidaciones', Cobro::PROCESS_NAME);
	$newcobro = $Cobro->PrepararCobro($fecha_ini_cobro, Utiles::fecha2sql($fecha_fin), $id_contrato, $forzar, $id_proceso_nuevo, '', '', $con_gastos, $solo_gastos, $incluye_gastos, $incluye_honorarios);

	Log::write(' |  -', Cobro::PROCESS_NAME);

	Log::write(' |- SetIncluirEnCierre', Cobro::PROCESS_NAME);
	$Contrato->SetIncluirEnCierre($Sesion);

	if (isset($_GET['generar_silenciosamente']) && $_GET['generar_silenciosamente'] == 1) {
		Log::write(' |- generar_silenciosamente', Cobro::PROCESS_NAME);
		Log::write(' -', Cobro::PROCESS_NAME);

		unset($Sesion);
		unset($Cobro);
		unset($Contrato);
		die(json_encode(array('proceso' => $id_proceso_nuevo, 'cobro' => $newcobro)));
	} else {
		Log::write(' |- redirect', Cobro::PROCESS_NAME);
		Log::write(' -', Cobro::PROCESS_NAME);
		$Pagina->Redirect("cobros5.php?id_cobro={$newcobro}&popup=1");
	}
}

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
			$NotaCobro->GeneraCobrosMasivos($cobroRT, $imprimir_cartas, $agrupar_cartas);
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
	$errores_cobro = array();
	$total_cobros_procesados = 0;
	$total_cobros_emitidos = 0;

	$query = "SELECT
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
			LEFT JOIN cliente ON cliente.codigo_cliente = cobro.codigo_cliente
		WHERE {$where} AND cobro.estado IN ('CREADO', 'EN REVISION')";

	$resp = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);

	while ($cobro = mysql_fetch_array($resp)) {
		set_time_limit(100);
		$total_cobros_procesados++;

		if ($Cobro->Load($cobro['id_cobro'])) {
			$Cobro->Edit('id_carta', $cobro['id_carta']);
			$retorno_guardar_cobro = $Cobro->GuardarCobro(true);
			$Cobro->Edit('etapa_cobro', '5');
			$Cobro->Edit('fecha_emision', date('Y-m-d H:i:s'));
			$estado_anterior = $Cobro->fields['estado'];
			$Cobro->Edit('estado', 'EMITIDO');
			if ($retorno_guardar_cobro == '' && $estado_anterior != 'EMITIDO') {
				$Cobro->Write();
				$total_cobros_emitidos++;
			} else {
				array_push($errores_cobro, utf8_encode('#' . $cobro['id_cobro'] . ': ' . $retorno_guardar_cobro));
			}
		}
	}

	$url .= '&cobros_emitidos=1';

	if (isset($return_json) && $return_json == 'true') {
		$json = array(
			'url_redirect' => $url,
			'total_cobros_procesados' => $total_cobros_procesados,
			'total_cobros_emitidos' => $total_cobros_emitidos,
			'total_cobros_error' => $total_cobros_procesados - $total_cobros_emitidos
		);

		if (!empty($errores_cobro)) {
			$json['errores'] = $errores_cobro;
		}
		echo json_encode($json);
	} else {
		$Pagina->Redirect($url);
	}
}