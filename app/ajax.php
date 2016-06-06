<?php

require_once 'conf.php';

ini_set('display_errors', 'On');

$sesion = new Sesion('');
$pagina = new Pagina($sesion);
if (isset($_POST['accion'])) {
	$accion = $_POST['accion'];
}

$codigo_asunto = mysql_real_escape_string($codigo_asunto);
$id = mysql_real_escape_string($id);

switch ($accion) {

	case 'actualiza_langs':

		$maxid = $_POST['cantidad'];
		$orden = 0;
		$query = array();
		$archivos = array();
		foreach ($_POST as $llave => $datorecibido) {
			if (strpos($llave, '_php')) {
				$archivonombre = str_replace('_php', '.php', $llave);
				$query[] = "insert into prm_lang (archivo_nombre,orden,activo) values ('" . $archivonombre . "'," . ++$orden . ",1) on duplicate key update  orden=$orden, activo=1; ";
				$archivos[] = $archivonombre;
			}
		}
		$query[] = "update prm_lang set orden=$maxid, activo=0 where archivo_nombre not in ('" . implode("','", $archivos) . "');";
		foreach ($query as $q) {
			mysql_query($q, $sesion->dbh);
		}


		$queryrespuesta = 'select * from prm_lang order by orden ASC';
		$resultado = mysql_query($queryrespuesta, $sesion->dbh);
		$maxid = 0;
		$orden = 0;
		$archivos = array();
		while ($archivo = mysql_fetch_array($resultado)) {
			$maxid = $archivo[0];
			$orden = $archivo[2];

			$archivos[$archivo[1]] = $archivo[1];
			echo '<li > <input type="checkbox" class="checkbox"  id="' . $archivo[0] . '_' . $archivo[1] . '" name="' . $archivo[1] . '" value="1" ' . (($archivo[3] == 1) ? 'checked="checked"' : '') . ' /><label for="' . $archivo[0] . '_' . $archivo[1] . '">' . $archivo[1] . '</label> <span class="ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
		}

		$myDirectory = opendir(Conf::ServerDir() . '/lang/');

		while ($entryName = readdir($myDirectory)) {
			if (!array_key_exists($entryName, $archivos) && is_file(Conf::ServerDir() . '/lang/' . $entryName)) {
				echo '<li > <input type="checkbox" class="checkbox"  id="' . ++$maxid . '_' . $entryName . '" name="' . $entryName . '" value="1" /><label for="' . $maxid . '_' . $entryName . '">' . $entryName . '</label> <span class="ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
			}
		}

		closedir($myDirectory);

		break;

	case 'actualiza_plugins':

		$maxid = $_POST['cantidad'];
		$orden = 0;
		$query = array();
		$archivos = array();
		foreach ($_POST as $llave => $datorecibido):
			if (strpos($llave, '_php')) {
				$archivonombre = str_replace('_php', '.php', $llave);
				$query[] = "insert into prm_plugin (archivo_nombre,orden,activo) values ('" . $archivonombre . "'," . ++$orden . ",1) on duplicate key update  orden=$orden, activo=1; ";
				$archivos[] = $archivonombre;
			}
		endforeach;
		$query[] = "update prm_plugin set orden=$maxid, activo=0 where archivo_nombre not in ('" . implode("','", $archivos) . "');";
		foreach ($query as $q) {
			mysql_query($q, $sesion->dbh);
		}

		$queryrespuesta = 'select * from prm_plugin order by orden ASC';
		$resultado = mysql_query($queryrespuesta, $sesion->dbh);
		$maxid = 0;
		$orden = 0;
		$archivos = array();
		while ($archivo = mysql_fetch_array($resultado)) {
			$maxid = $archivo[0];
			$orden = $archivo[2];

			$archivos[$archivo[1]] = $archivo[1];
			echo '<li > <input type="checkbox" class="checkbox"  id="' . $archivo[0] . '_' . $archivo[1] . '" name="' . $archivo[1] . '" value="1" ' . (($archivo[3] == 1) ? 'checked="checked"' : '') . ' /><label for="' . $archivo[0] . '_' . $archivo[1] . '">' . $archivo[1] . '</label> <span class="ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
		}

		$myDirectory = opendir(Conf::ServerDir() . '/lang/');

		while ($entryName = readdir($myDirectory)) {
			if (!array_key_exists($entryName, $archivos) && is_file(Conf::ServerDir() . '/plugins/' . $entryName)) {
				echo '<li > <input type="checkbox" class="checkbox"  id="' . ++$maxid . '_' . $entryName . '" name="' . $entryName . '" value="1" /><label for="' . $maxid . '_' . $entryName . '">' . $entryName . '</label> <span class="ui-icon ui-icon-arrowthick-2-n-s"></span></li>';
			}
		}

		closedir($myDirectory);

		break;

	case 'actualiza_beacon':
		mysql_query("INSERT IGNORE INTO `configuracion_categoria` (`glosa_configuracion_categoria`) VALUES ('Plugins - Hooks');", $sesion->dbh);
		$resp = mysql_query("select id_configuracion_categoria from configuracion_categoria where glosa_configuracion_categoria='Plugins - Hooks'", $sesion->dbh);
		list($cat_id) = mysql_fetch_row($resp);
		$beaconleft = intval(base64_decode($_POST['beaconleft']));
		mysql_query("replace INTO configuracion (glosa_opcion, valor_opcion, valores_posibles, comentario, id_configuracion_categoria, orden)
                                        VALUES('BeaconTimer', '{$beaconleft}', 'numero','Tiempo Disponible de Uso del Programa', {$cat_id}, -1)", $sesion->dbh);

		echo '<!--' . $beaconleft . '-->';
		break;

	case "borrar_evento":

		$query = "DELETE FROM events WHERE event_id=" . $id;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		echo(true);
		break;

	case "actualizar_evento":

		list($dia_semana, $mes_letra, $dia, $anio, $hora) = explode(' ', $start_date);
		switch ($mes_letra) {
			case 'Jan': $mes = '01';
				break;
			case 'Feb': $mes = '02';
				break;
			case 'Mar': $mes = '03';
				break;
			case 'Apr': $mes = '04';
				break;
			case 'May': $mes = '05';
				break;
			case 'Jun': $mes = '06';
				break;
			case 'Jul': $mes = '07';
				break;
			case 'Aug': $mes = '08';
				break;
			case 'Sep': $mes = '09';
				break;
			case 'Oct': $mes = '10';
				break;
			case 'Nov': $mes = '11';
				break;
			case 'Dec': $mes = '12';
				break;
		}

		$start_date = $anio . '-' . $mes . '-' . $dia . ' ' . $hora;

		list($dia_semana, $mes_letra, $dia, $anio, $hora) = explode(' ', $end_date);
		switch ($mes_letra) {
			case 'Jan': $mes = '01';
				break;
			case 'Feb': $mes = '02';
				break;
			case 'Mar': $mes = '03';
				break;
			case 'Apr': $mes = '04';
				break;
			case 'May': $mes = '05';
				break;
			case 'Jun': $mes = '06';
				break;
			case 'Jul': $mes = '07';
				break;
			case 'Aug': $mes = '08';
				break;
			case 'Sep': $mes = '09';
				break;
			case 'Oct': $mes = '10';
				break;
			case 'Nov': $mes = '11';
				break;
			case 'Dec': $mes = '12';
				break;
		}

		$end_date = $anio . '-' . $mes . '-' . $dia . ' ' . $hora;

		$query = "UPDATE events SET event_name='" . $event_text . "', start_date='" . $start_date . "', end_date='" . $end_date . "' WHERE event_id=" . $id;
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		echo(true);
		break;

	case "agregar_evento":

		list($dia_semana, $mes_letra, $dia, $anio, $hora) = explode(' ', $start_date);
		switch ($mes_letra) {
			case 'Jan': $mes = '01';
				break;
			case 'Feb': $mes = '02';
				break;
			case 'Mar': $mes = '03';
				break;
			case 'Apr': $mes = '04';
				break;
			case 'May': $mes = '05';
				break;
			case 'Jun': $mes = '06';
				break;
			case 'Jul': $mes = '07';
				break;
			case 'Aug': $mes = '08';
				break;
			case 'Sep': $mes = '09';
				break;
			case 'Oct': $mes = '10';
				break;
			case 'Nov': $mes = '11';
				break;
			case 'Dec': $mes = '12';
				break;
		}

		$start_date = $anio . '-' . $mes . '-' . $dia . ' ' . $hora;

		list($dia_semana, $mes_letra, $dia, $anio, $hora) = explode(' ', $end_date);
		switch ($mes_letra) {
			case 'Jan': $mes = '01';
				break;
			case 'Feb': $mes = '02';
				break;
			case 'Mar': $mes = '03';
				break;
			case 'Apr': $mes = '04';
				break;
			case 'May': $mes = '05';
				break;
			case 'Jun': $mes = '06';
				break;
			case 'Jul': $mes = '07';
				break;
			case 'Aug': $mes = '08';
				break;
			case 'Sep': $mes = '09';
				break;
			case 'Oct': $mes = '10';
				break;
			case 'Nov': $mes = '11';
				break;
			case 'Dec': $mes = '12';
				break;
		}

		$end_date = $anio . '-' . $mes . '-' . $dia . ' ' . $hora;

		$query = "INSERT INTO events( event_name, start_date, end_date, details ) VALUES('" . $event_text . "','" . $start_date . "','" . $end_date . "', 'Trabajo')";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		echo(true);
		break;

	case "actualizacion_usuario":

		if (Conf::GetConf($sesion, 'ColumnaNotificacion') != '') {
			if (!$id_not) {
				$id_not = 0;
			}
			if (!$corr) {
				$corr = 0;
			}
			$query = "UPDATE usuario SET id_notificacion_tt=" . $id_not . "+" . $corr . " WHERE id_usuario='$id' AND id_notificacion_tt < " . $id_not . "+" . $corr;
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			echo(true);
		} else {
			echo(false);
		}
		break;

	case "cargar_cliente":

		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			$codigo_asunto = 'asunto.codigo_asunto_secundario';
			$codigo_cliente = 'cliente.codigo_cliente_secundario';
			$join = 'LEFT JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente';
		} else {
			$codigo_asunto = 'asunto.codigo_asunto';
			$codigo_cliente = 'cliente.codigo_cliente';
			$join = 'LEFT JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente';
		}
		$query = "SELECT DISTINCT $codigo_cliente,glosa_cliente
									FROM cliente $join";
		if ($id != "") {
			$query .= "WHERE cliente.activo=1 AND $codigo_asunto = '$id' ";   // eleciona solo el cliente correspondente al id del asunto
		}
		$query .= "ORDER BY glosa_cliente";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		for ($i = 0; $fila = mysql_fetch_assoc($resp); $i++) {
			if ($i > 0) {
				echo("~");
			}
			echo(join("|", $fila));
		}
		if ($i == 0) {
			echo("VACIO|");
		}
		break;

	case "cargar_asuntos":
	case "cargar_asuntos_desde_campo":

		if ($id != "") {
			$vacio = false;
		} else {
			$vacio = true;
		}
		list($accion, $codigo_cli) = explode("//", $accion);

		if ($accion == "cargar_asuntos_desde_campo" && ( ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ) || ( method_exists('Conf', 'GetConf') && Conf::GetConf($sesion, 'CodigoSecundario') ) )) {
			$query = "SELECT codigo_cliente_secundario
    								FROM cliente
    								JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente
    								WHERE codigo_asunto_secundario = '$id' ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list( $id ) = mysql_fetch_array($resp);
			if (!$id) {
				$vacio = true;
			} else {
				$vacio = false;
			}
		} elseif ($accion == "cargar_asuntos_desde_campo") {
			$query = "SELECT codigo_cliente FROM asunto WHERE codigo_asunto = '$id' ";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			list( $id ) = mysql_fetch_array($resp);
			if (!$id) {
				$vacio = true;
			} else {
				$vacio = false;
			}
		}

		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			$codigo_asunto = 'asunto.codigo_asunto_secundario';
			$codigo_cliente = 'cliente.codigo_cliente_secundario';
			$join = 'LEFT JOIN cliente ON cliente.codigo_cliente=asunto.codigo_cliente ';
			$where = '';
		} else {
			$codigo_asunto = 'codigo_asunto';
			$codigo_cliente = 'codigo_cliente';
			$join = '';
			$where = '';
		}
		$query = "SELECT $codigo_asunto,glosa_asunto
				   FROM asunto " . $join;
		if ($id != "") {
			$query .= " WHERE asunto.activo=1 AND $codigo_cliente = '$id' ";
		} else if ($vacio) {
			$query .= " WHERE 1=0 ";
		}
		$query .= " ORDER BY glosa_asunto ";

		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		for ($i = 0; $fila = mysql_fetch_assoc($resp); $i++) {
			if ($i > 0) {
				echo("~");
			}
			echo(join("|", $fila));
		}
		if ($vacio) {
			echo("~noexiste");
		}
		if ($i == 0) {
			echo("VACIO|");
		}
		break;

	case 'averiguar_codigo_cliente':

		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			// asumo que recib� un codigo asunto secundario (m�s vale)

			$cliente = $sesion->pdodbh->query("SELECT cliente.codigo_cliente_secundario  FROM cliente
								JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente
								WHERE asunto.codigo_asunto_secundario='$id'");

			if ($dato = $cliente->fetch()) {
				echo $dato['codigo_cliente_secundario'];
			} else {
				echo false;
			}
		} else {
			$cliente = $sesion->pdodbh->query("SELECT cliente.codigo_cliente   FROM cliente
								JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente
								WHERE asunto.codigo_asunto ='$id'");

			if ($dato = $cliente->fetch()) {
				echo $dato['codigo_cliente'];
			} else {
				echo false;
			}
		}
		break;

	case 'cambiar_tarifa_usuario':

		$query2 = "SELECT tarifa,id_tarifa,id_moneda FROM categoria_tarifa
							WHERE id_categoria_usuario='$id_2'";
		$resp2 = mysql_query($query2, $sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $sesion->dbh);

		$UsuarioTarifa = new UsuarioTarifa($sesion);
		while (list($tarifa, $id_tarifa, $id_moneda) = mysql_fetch_array($resp2)) {
			$UsuarioTarifa->GuardarTarifa($id_tarifa, $id, $id_moneda, $tarifa);
		}
		echo("OK");
		break;

	case "cargar_actividades":
		echo cargarActividades($sesion, $id);
		break;

	case "cargar_actividades_activas":
		echo cargarActividades($sesion, $id, true);
		break;

	case 'cargar_cargos':

		$query_clientes = "SELECT contrato.factura_razon_social, contrato.factura_direccion, contrato.rut FROM cobro
												JOIN cliente ON cobro.codigo_cliente=cliente.codigo_cliente
												JOIN contrato ON cliente.id_contrato = contrato.id_contrato
												WHERE cliente.codigo_cliente=$id AND cobro.documento IS NULL LIMIT 1";

		$query = "SELECT * FROM cobro WHERE documento IS NULL AND codigo_cliente = '$id'";
		$resp = mysql_query($query_clientes, $sesion->dbh) or Utiles::errorSQL($query_clientes, __FILE__, __LINE__, $sesion->dbh);

		for ($i = 0; $fila = mysql_fetch_assoc($resp); $i++) {
			if ($i > 0) {
				echo("~");
			}
			echo(join("|", $fila));
		}
		if ($i == 0) {
			echo("VACIO|");
		}

		break;

	case "cargar_glosa_cliente":

		if (Conf::GetConf($sesion, 'CodigoSecundario')) {
			if ($id_asunto != '') {
				$query = "SELECT codigo_cliente_secundario FROM cliente
									JOIN asunto ON cliente.codigo_cliente=asunto.codigo_cliente
									WHERE asunto.codigo_asunto_secundario='$id_asunto'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list($codigo_cliente) = mysql_fetch_array($resp);
			}
			$codigo = 'codigo_cliente_secundario';
		} else {
			if ($id_asunto != '') {
				$query = "SELECT codigo_cliente FROM asunto
									WHERE codigo_asunto='$id_asunto'";
				$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
				list($codigo_cliente) = mysql_fetch_array($resp);
			}
			$codigo = 'codigo_cliente';
		}
		$query = "SELECT glosa_cliente FROM cliente WHERE activo=1 AND " . $codigo . "='$id' ";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		list($glosa) = mysql_fetch_array($resp);
		$glosa = str_replace('/', '|#slash|', $glosa);
		echo $glosa . '/' . $codigo_cliente;
		break;

	case 'cargar_glosa_asunto':
		$asunto = array();
		if (!empty($codigo_asunto)) {
			$campo_codigo_asunto = 'codigo_asunto';

			if (Conf::GetConf($sesion, 'CodigoSecundario')) {
				$campo_codigo_asunto = 'codigo_asunto_secundario';
			}
			$activo_and = $todos ? '' : 'asunto.activo = 1 AND';
			$query = "SELECT asunto.id_asunto, asunto.codigo_asunto, asunto.codigo_asunto_secundario, asunto.glosa_asunto, asunto.codigo_cliente
				FROM asunto
				WHERE {$activo_and} asunto.{$campo_codigo_asunto} = '{$codigo_asunto}'";
			$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
			$asunto = mysql_fetch_array($resp);

			echo json_encode(UtilesApp::utf8izar($asunto));
		}
		break;

	case "cargar_cuenta_banco":

		$query = "SELECT id_cuenta, numero FROM cuenta_banco
							WHERE id_banco='$id'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		for ($i = 0; $fila = mysql_fetch_assoc($resp); $i++) {
			if ($i > 0) {
				echo("~");
			}
			echo(join("|", $fila));
		}
		if ($i == 0) {
			echo("VACIO|");
		}
		break;

	case 'cargar_contratos':
		require_once Conf::ServerDir() . '/classes/Contrato.php';
		$contrato = new Contrato($sesion);
		echo $contrato->ListaSelector($codigo_cliente, 'CargarTabla(1);');
		break;

	case 'evaluacion':
		// s� existe la ventana del evaluacion despu�s el login
		$query = "INSERT evaluacion SET id_usuario= '$id', valuacion = '$valuacion',
						glosa_valuacion = '$glosa_valuacion', fecha_creacion = '" . Utiles::fecha2sql(date()) . "'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);
		break;

	default:
		echo("ERROR");
};

function cargarActividades(Sesion $Sesion, $codigo_asunto, $activas = false) {
	$retorno = '';

	if (Conf::GetConf($Sesion, 'CodigoSecundario')) {
		$Asunto = new Asunto($Sesion);
		$Asunto->LoadByCodigoSecundario($codigo_asunto);
		if ($Asunto->Loaded()) {
			$codigo_asunto = $Asunto->fields['codigo_asunto'];
		}
	}

	$Actividad = new Actividad($Sesion);
	$actividades = $Actividad->obtenerActividadesSegunAsunto($codigo_asunto, $activas);

	if (!empty($actividades)) {
		for($i = 0; $i < count($actividades); $i++) {
			if ($i > 0) {
				$retorno .= '~';
			}
			$retorno .= join('|', $actividades[$i]);
		}
	} else {
		$retorno = 'VACIO|';
	}

	return $retorno;
}
