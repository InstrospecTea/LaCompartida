<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Sesion.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../fw/classes/Pagina.php';
require_once Conf::ServerDir() . '/../app/classes/Cliente.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../app/classes/CobroPendiente.php';
require_once Conf::ServerDir() . '/../app/classes/ContratoDocumentoLegal.php';
require_once Conf::ServerDir() . '/../app/classes/FacturaPago.php';
require_once Conf::ServerDir() . '/../app/classes/Asunto.php';
require_once Conf::ServerDir() . '/../app/classes/Cliente.php';
require_once Conf::ServerDir() . '/../app/classes/Contrato.php';
require_once Conf::ServerDir() . '/../app/classes/UsuarioExt.php';
require_once Conf::ServerDir() . '/../app/classes/UsuarioExt.php';
require_once Conf::ServerDir() . '/../app/classes/Moneda.php';
require_once Conf::ServerDir() . '/../app/classes/Trabajo.php';
require_once Conf::ServerDir() . '/../app/classes/Tarifa.php';

class Migracion {

	var $sesion = null;
	var $logs = array();
	public $errorcount;
	public $filasprocesadas;
	private $CobroMonedaStatement;
	var $arreglocategorias = array('socio' => 1,
		'asociado senior' => 2,
		'asociado junior' => 3,
		'procurador' => 4,
		'administracion' => 5,
		'secretaria' => 5,
		'asociado' => 6,
		'contratado' => 7,
		'administrativo' => 7,
		'asistente' => 8,
		'practicante' => 9,
		'' => 5
	);

	public function Migracion() {
		$this->sesion = new Sesion();
	}

	public function AgregarAsunto($asunto_generar, $contrato_generar = null, $cobros_pendientes = array(), $documentos_legales = array()) {
		if (empty($asunto_generar)) {
			echo "Faltan parametro asunto\n";
			return false;
		}

		$asunto = new Asunto($this->sesion);
		$asunto->guardar_fecha = false;

		if (!$asunto->Load($asunto_generar->fields['id_asunto'])) {
			$asunto->LoadByCodigo($asunto_generar->fields['codigo_asunto']);
		}

		if ($asunto->Loaded()) {
			echo "--Editando asunto ID " . $asunto->fields["id_asunto"] . "\n";
		} else {
			echo "--Ingresando asunto\n";
		}

		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($asunto_generar->fields['codigo_cliente']);
		if (!$cliente->Loaded()) {
			echo "No existe el cliente con codigo (" . $asunto_generar->fields['codigo_cliente'] . ")";
			return false;
		}

		$asunto->Edit("id_contrato", $cliente->fields['id_contrato']);

		if (empty($asunto_generar->fields["codigo_asunto"])) {
			if ((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'CodigoEspecialGastos') ) || ( method_exists('Conf', 'CodigoEspecialGastos') && Conf::CodigoEspecialGastos())) {
				$asunto_generar->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto->fields["codigo_cliente"], $asunto_generar->fields["glosa_asunto"]);
			} else {
				$asunto_generar->fields["codigo_asunto"] = $asunto->AsignarCodigoAsunto($asunto_generar->fields["codigo_cliente"]);
			}
			echo "Asignado codigo asunto " . $asunto_generar->fields["codigo_asunto"];
		}

		$asunto->Edit("id_usuario", empty($asunto_generar->fields['id_usuario']) ? "NULL" : $asunto_generar->fields['id_usuario']);
		$asunto->Edit("codigo_asunto", $asunto_generar->fields["codigo_asunto"]);

		if (empty($cliente->fields['codigo_cliente_secundario'])) {
			$cliente->Edit('codigo_cliente_secundario', $cliente->CodigoACodigoSecundario($asunto->fields["codigo_cliente"]));
		}

		if (((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'CodigoSecundario') ) || ( method_exists('Conf', 'CodigoSecundario') && Conf::CodigoSecundario() ))) {
			$asunto->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . substr(strtoupper($asunto_generar->fields['codigo_asunto_secundario']), -4));
		} else {
			if (!empty($asunto_generar->fields["codigo_asunto_secundario"])) {
				$asunto->Edit("codigo_asunto_secundario", $cliente->fields['codigo_cliente_secundario'] . '-' . strtoupper($asunto_generar->fields["codigo_asunto_secundario"]));
			} else {
				$asunto->Edit("codigo_asunto_secundario", $asunto_generar->fields['codigo_asunto']);
			}
		}

		$asunto->Edit("actividades_obligatorias", $asunto_generar->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto->Edit("mensual", $asunto_generar->fields['mensual'] ? "SI" : "NO");

		$asunto->Edit("id_cobrador", empty($asunto_generar->fields['id_cobrador']) ? "NULL" : $asunto_generar->fields['id_cobrador']);

		$asunto->Edit("glosa_asunto", $asunto_generar->fields['glosa_asunto']);
		$asunto->Edit("codigo_cliente", $asunto_generar->fields['codigo_cliente']);
		$asunto->Edit("id_tipo_asunto", empty($asunto_generar->fields['id_tipo_asunto']) ? 1 : $asunto_generar->fields['id_tipo_asunto']);
		$asunto->Edit("id_area_proyecto", empty($asunto_generar->fields['id_area_proyecto']) ? 1 : $asunto_generar->fields['id_area_proyecto']);

		$asunto->Edit("id_moneda", $asunto_generar->fields['id_moneda']);
		$asunto->Edit("id_idioma", empty($asunto_generar->fields['id_idioma']) ? "NULL" : $asunto_generar->fields['id_idioma']);

		$asunto->Edit("descripcion_asunto", $asunto_generar->fields['descripcion_asunto']);
		$asunto->Edit("id_encargado", empty($asunto_generar->fields['id_encargado']) ? "NULL" : $asunto_generar->fields['id_encargado']);
		$asunto->Edit("contacto", $asunto_generar->fields['contacto']);
		$asunto->Edit("fono_contacto", $asunto_generar->fields['fono_contacto']);
		$asunto->Edit("email_contacto", $asunto_generar->fields['email_contacto']);
		$asunto->Edit("actividades_obligatorias", $asunto_generar->fields['actividades_obligatorias'] ? '1' : '0');
		$asunto->Edit("activo", $asunto_generar->fields['activo']);
		$asunto->Edit("cobrable", $asunto_generar->fields['cobrable']);
		$asunto->Edit("mensual", $asunto_generar->fields['mensual'] ? "SI" : "NO");
		$asunto->Edit("alerta_hh", $asunto_generar->fields['alerta_hh']);
		$asunto->Edit("alerta_monto", $asunto_generar->fields['alerta_monto']);
		$asunto->Edit("limite_hh", $asunto_generar->fields['limite_hh']);
		$asunto->Edit("limite_monto", $asunto_generar->fields['limite_monto']);
		$asunto->Edit("giro", $asunto_generar->fields['giro']);
		$asunto->Edit("razon_social", $asunto_generar->fields['razon_social']);

		$asunto->Edit("fecha_creacion", $asunto_generar->fields['fecha_creacion']);

		if (!$this->ValidarAsunto($asunto)) {
			echo "Error al guardar el asunto\n";
			return false;
		}

		if (!$this->Write($asunto)) {
			echo "Error al guardar el asunto\n";
			return false;
		}

		echo "Asunto ID " . $asunto->fields['id_asunto'] . " guardado\n";

		if ($contrato_generar) {
			$contrato = new Contrato($this->sesion);
			$contrato->guardar_fecha = false;
			$contrato->Load($asunto->fields['id_contrato_indep']);

			if ($contrato->Loaded()) {
				echo "Editando contrato ID " . $contrato->fields['id_contrato'] . "\n";
			} else {
				unset($contrato_generar->fields['id_contrato']);
				echo "Ingresando contrato para el asunto\n";
			}

			if ($this->GuardarContrato($contrato, $contrato_generar, $cliente, $cobros_pendientes, $documentos_legales)) {
				$asunto->Edit("id_contrato", $contrato->fields['id_contrato']);
				$asunto->Edit("id_contrato_indep", $contrato->fields['id_contrato']);
				if ($this->Write($asunto)) {
					echo "Guardado asunto con contrato independiente ID " . $asunto->fields['id_contrato_indep'] . "\n";
				} else {
					echo "Error al guardar asunto con contrato independiente\n";
				}
			}
		}
	}

	public function ValidarAsunto($asunto) {
		if (!$this->ValidarCodigo($asunto, "id_moneda", "prm_moneda")) {
			return false;
		}

		if (!$this->ValidarCodigo($asunto, "id_usuario", "usuario")) {
			return false;
		}

		if (!$this->ValidarCodigo($asunto, "id_encargado", "usuario", false, "id_usuario")) {
			return false;
		}

		if (!$this->ValidarCodigo($asunto, "id_cobrador", "usuario", false, "id_usuario")) {
			return false;
		}

		if (!$this->ValidarCodigo($asunto, "codigo_cliente", "cliente", false, "codigo_cliente", true)) {
			return false;
		}

		if (!$this->ValidarCodigo($asunto, "id_tipo_asunto", "prm_tipo_proyecto", true, "id_tipo_proyecto")) {
			return false;
		}

		if (!$this->ValidarCodigo($asunto, "id_area_proyecto", "prm_area_proyecto", true)) {
			return false;
		}

		return $this->ValidarCodigo($asunto, "id_idioma", "prm_idioma");
	}

	function SetDatosParametricos($prm) {
		foreach ($prm as $key => $val) {
			$query = "DELETE FROM $key";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

			foreach ($val['datos'] as $llave => $valor) {
				if ($llave == 0)
					$datos_prm = "('" . ($llave + 1) . "','" . $valor . "')";
				else
					$datos_prm .= ",('" . ($llave + 1) . "','" . $valor . "')";
			}

			$query = "INSERT INTO $key ( " . $val['campo_id'] . ", " . $val['campo_glosa'] . " ) VALUES $datos_prm ";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}
	}

	function ImprimirDataEnPantalla($response) {
		echo "<table border='1px solid black'>";
		$i = 0;
		while ($row = mysql_fetch_assoc($response)) {
			echo "<tr>";
			if ($i == 0) {
				foreach ($row as $key => $val)
					echo "<th>$key</th>";
				echo "</tr><tr>";
			}
			foreach ($row as $key => $val)
				echo "<td>$val</th>";
			echo "</tr>";
			$i++;
		}
		echo "</table>";
	}

	function Query2ObjetosCliente($response, $tipo = 'sql') {
		if ($tipo == 'sql') {
			while ($row = mysql_fetch_assoc($response)) {
				$sesion = new Sesion();
				$cliente = new Cliente($this->sesion);
				$contrato = new Contrato($this->sesion);

				$row['cliente_FFF_glosa_cliente'] = addslashes($row['cliente_FFF_glosa_cliente']);
				$row['contrato_FFF_factura_razon_social'] = addslashes($row['contrato_FFF_factura_razon_social']);
				$row['cliente_FFF_rsocial'] = addslashes($row['cliente_FFF_rsocial']);

				foreach ($row as $key => $val) {
					$keys = explode('_FFF_', $key);
					if ($keys[0] == 'cliente') {
						$cliente->Edit($keys[1], $val);
					} else if ($keys[0] == 'contrato') {
						$contrato->Edit($keys[1], $val);
					}
				}

				$this->AgregarCliente($cliente, $contrato);
			}
		} else if ($tipo == 'excel') {
			foreach ($response as $id_usuario => $arreglo_cliente) {
				$sesion = new Sesion();

				$sesion = new Sesion();
				$cliente = new Cliente($this->sesion);
				$contrato = new Contrato($this->sesion);

				$arreglo_cliente['cliente_FFF_dir_calle'] =
					$arreglo_cliente['cliente_FFF_direccion'] . ', ' . $arreglo_cliente['cliente_FFF_ciudad'] . ', ' .
					$arreglo_cliente['cliente_FFF_provinicia'] . ', ' . $arreglo_cliente['cliente_FFF_glosa_pais'];
				$arreglo_cliente['contrato_FFF_direccion_contacto'] =
					$arreglo_cliente['contrato_FFF_direccion'] . ', ' . $arreglo_cliente['contrato_FFF_ciudad'] . ', ' .
					$arreglo_cliente['contrato_FFF_provinicia'] . ', ' . $arreglo_cliente['contrato_FFF_glosa_pais'];
				$arreglo_cliente['contrato_FFF_id_pais'] = $this->GlosaPaisAIdPais($arreglo_cliente['contrato_FFF_glosa_pais']);

				if (strtoupper($arreglo_cliente['contrato_FFF_glosa_idioma']) == "INGLES") {
					$arreglo_cliente['contrato_FFF_codigo_idioma'] = "en";
				} else {
					$arreglo_cliente['contrato_FFF_codigo_idioma'] = "es";
				}
				if (strtoupper($arreglo_cliente['contrato_FFF_codigo_moneda']) == "DOL") {
					$arreglo_cliente['contrato_FFF_id_moneda'] = "2";
					$arreglo_cliente['contrato_FFF_id_moneda_moneda'] = "2";
					$arreglo_cliente['contrato_FFF_opc_moneda_total'] = "2";
				} else {
					$arreglo_cliente['contrato_FFF_id_moneda'] = "1";
					$arreglo_cliente['contrato_FFF_id_moneda_moneda'] = "1";
					$arreglo_cliente['contrato_FFF_opc_moneda_total'] = "1";
				}
				$arreglo_cliente['contrato_FFF_id_usuario_responsable'] = $this->UsernameAIdUsuario($arreglo_cliente['contrato_FFF_username_usuario_responsable']);
				$arreglo_cliente['contrato_FFF_id_usuario_secundario'] = $this->UsernameAIdUsuario($arreglo_cliente['contrato_FFF_username_usuario_secundario']);
				$arreglo_cliente['contrato_FFF_fecha_creacion'] = Utiles::fecha2sql($arreglo_cliente['contrato_FFF_fecha_creacion']);

				unset($arreglo_cliente['cliente_FFF_direccion']);
				unset($arreglo_cliente['cliente_FFF_ciudad']);
				unset($arreglo_cliente['cliente_FFF_provinicia']);
				unset($arreglo_cliente['cliente_FFF_glosa_pais']);
				unset($arreglo_cliente['contrato_FFF_direccion']);
				unset($arreglo_cliente['contrato_FFF_ciudad']);
				unset($arreglo_cliente['contrato_FFF_provinicia']);
				unset($arreglo_cliente['contrato_FFF_glosa_pais']);
				unset($arreglo_cliente['contrato_FFF_glosa_idioma']);
				unset($arreglo_cliente['contrato_FFF_codigo_moneda']);
				unset($arreglo_cliente['contrato_FFF_username_usuario_responsable']);
				unset($arreglo_cliente['contrato_FFF_username_usuario_secundario']);

				foreach ($arreglo_cliente as $key => $value) {
					$keys = explode('_FFF_', $key);
					if ($keys[0] == 'cliente') {
						$cliente->Edit($keys[1], $val);
					} else if ($keys[0] == 'contrato') {
						$contrato->Edit($keys[1], $val);
					}
				}
				$this->AgregarCliente($cliente, $contrato);
			}
		}
	}

	function GlosaPaisAIdPais($glosa_pais) {
		$query = "SELECT id_pais FROM prm_pais WHERE nombre = TRIM('$glosa_pais')";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_pais) = mysql_fetch_array($resp);
		return $id_pais;
	}

	function UsernameAIdUsuario($username) {
		$query = "SELECT id_usuario FROM usuario WHERE username = TRIM('$username')";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_usuario) = mysql_fetch_array($resp);
		return $id_usuario;
	}

	function DefinirGruposPRC() {
		$codigos_de_cliente = array('0707', '0871', '0853', '1292', '1308', '1437', '0897', '0825', '1087', '1368', '0852',
			'0744', '0759', '1448', '1363', '0947', '0605', '0763', '0663', '0878', '1055', '1243',
			'1347', '1404', '1155', '1118', '0917', '1202', '0336', '1042', '1078', '0140', '0352',
			'0710', '1177', '0002', '1450', '1415', '1360', '1282', '0471', '0823', '0184', '0756',
			'0681', '0584');
		$grupos_que_corresponden = array('GRUPO BACKUS', 'GRUPO BACKUS', 'GRUPO VALE', 'GRUPO VALE', 'GRUPO VALE',
			'GRUPO SCOTIA', 'GRUPO SCOTIA', 'GRUPO SCOTIA', 'GRUPO SCOTIA', 'GRUPO SCOTIA',
			'GRUPO AMOV', 'GRUPO AMOV', 'GRUPO AMOV', 'GRUPO CHINALCO', 'GRUPO CHINALCO',
			'GRUPO CHINALCO', 'GRUPO AC CAPITALES', 'GRUPO AC CAPITALES', 'GRUPO AC CAPITALES',
			'GRUPO GOLD', 'GRUPO GOLD', 'GRUPO GOLD', 'GRUPO GOLD', 'GRUPO GOLD', 'GRUPO WWG',
			'GRUPO WWG', 'GRUPO WWG', 'GRUPO BBVA', 'GRUPO BBVA', 'GRUPO BBVA', 'GRUPO BBVA',
			'GRUPO ENDESA', 'GRUPO ENDESA', 'GRUPO ENDESA', 'GRUPO BREADT', 'GRUPO BREADT',
			'FAMILIA SARFATY', 'FAMILIA SARFATY', 'GRUPO ILASA', 'GRUPO ILASA', 'GRUPO BNP',
			'GRUPO BNP', 'GRUPO URÍA', 'GRUPO URÍA', 'GRUPO GOURMET', 'GRUPO GOURMET');

		foreach ($codigos_de_cliente as $key => $value) {
			$query = "UPDATE cliente SET id_grupo_cliente =
														( SELECT id_grupo_cliente
																FROM grupo_cliente
															 WHERE glosa_grupo_cliente LIKE '%" . $grupos_que_corresponden[$key] . "%' )
									 WHERE codigo_cliente = '" . $value . "' ";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}
	}

	function Query2ObjetoAsunto($response) {
		while ($row = mysql_fetch_assoc($response)) {
			$sesion = new Sesion();
			$asunto = new Asunto($this->sesion);
			$contrato = new Contrato($this->sesion);

			foreach ($row as $key => $val) {
				$keys = explode('_FFF_', $key);

				if ($keys[0] == 'asunto') {
					$asunto->Edit($keys[1], $val);
				} else if ($keys[0] == 'contrato') {
					$contrato->Edit($keys[1], $val);
				}
			}
			$this->AgregarAsunto($asunto, $contrato);
		}
	}

	function Query2ObjetoUsuario($response, $tipo = 'sql') {
		if ($tipo == 'sql') {
			while ($row = mysql_fetch_assoc($response)) {
				$sesion = new Sesion();

				$usuario = new UsuarioExt($sesion);
				$usuario->guardar_fecha = false;
				$usuario->tabla = "usuario";

				$permisos = $this->PermisosSegunCategoria($row);
				$row = $this->LimpiarCategoriaUsuario($row);

				if ($row['usuario_FFF_email'] == "")
					$row['usuario_FFF_email'] = 'mail@estudio.pais';
				if ($row['usuario_FFF_rut'] == "" || trim($row['usuario_FFF_rut']) == "000" || trim($row['usuario_FFF_rut']) == "0000")
					$row['usuario_FFF_rut'] = $row['usuario_FFF_id_usuario'];

				foreach ($row as $key => $val) {
					$keys = explode('_FFF_', $key);
					$usuario->Edit($keys[1], $val);
				}

				$this->AgregarUsuario($usuario, $permisos);
			}
		} else if ($tipo == 'excel') {
			foreach ($response as $id_usuario => $arreglo_usuario) {
				$sesion = new Sesion();

				$usuario = new UsuarioExt($sesion);
				$usuario->guardar_fecha = false;
				$usuario->tabla = "usuario";

				$permisos = $this->PermisosSegunCategoria($row);
				foreach ($arreglo_usuario as $key => $value) {
					if ($key == "glosa_categoria") {
						$key = "id_categoria_usuario";
						$value = $this->GlosaCatAIdCat($value);
					}
					$usuario->Edit($key, $value);
				}
				$this->AgregarUsuario($usuario, $permisos);
			}
		}
	}

	public function Query2ObjetoCobro($responseCobros) {
		$cobros = array();
		$this->CobroMonedaStatement = $this->sesion->pdodbh->prepare("replace into cobro_moneda (id_cobro, id_moneda, tipo_cambio) values (:idcobro, :idmoneda, :tipocambio)");
		while ($cobro_ = mysql_fetch_assoc($responseCobros)) {
			$cobro = new Cobro($this->sesion);
			$cobro->guardar_fecha = false;
			foreach ($cobro_ as $key => $val) {
				$keys = explode('_FFF_', $key);
				//Transformar codigo asunto a ID contrato
				if ($keys[1] == "codigo_asunto" && !empty($val)) {
					$id_contrato = $this->ObtenerIDContratoSegunCodigoAsunto($val);
					if (empty($id_contrato)) {
						echo "No se encontro el ID de contrato para el cobro " . $cobro->fields['id_cobro'] . "\n";
						continue 2;
					}
					$keys[1] = "id_contrato";
					$val = $id_contrato;
				} else if ($keys[1] == "codigo_cliente") {
					$id_contrato = $this->ObtenerIDContratoSegunCodigoCliente($val);
					if (empty($id_contrato)) {
						echo "No se encontro el ID de contrato para el cobro " . $cobro->fields['id_cobro'] . "\n";
						continue 2;
					}
					$cobro->Edit($keys[1], $val);
					$keys[1] = "id_contrato";
					$val = $id_contrato;
				}
				if ($keys[1] != "codigo_asunto")
					$cobro->Edit($keys[1], $val);
			}
			$this->GenerarCobroBase($cobro);
		}

		//Buscar trabajos sin ID de cobro y genera sus cobros
		//$this->GenerarCobrosBase();
	}

	public function Query2ObjetoPago($responsePago) {
		while ($datos_pago = mysql_fetch_assoc($responsePago)) {
			$factura_pago = new FacturaPago($this->sesion);
			$factura_pago->guardar_fecha = false;

			$documento_pago = new Documento($this->sesion);
			$documento_pago->guardar_fecha = false;
			//echo '<pre>'; print_r($datos_pago); echo '</pre>'; exit;
			foreach ($datos_pago as $key => $val) {
				$keys = explode('_FFF_', $key);
				if ($keys[1] == "glosa_banco") {
					$keys[1] = "id_banco";
					list($val) = mysql_fetch_array(mysql_query("SELECT id_banco FROM prm_banco WHERE nombre = TRIM('$val')", $this->sesion->dbh));
				} else if ($keys[1] == "cuenta_banco") {
					$keys[1] = "id_cuenta";
					list($val) = mysql_fetch_array(mysql_query("SELECT id_cuenta FROM cuenta_banco WHERE numero = TRIM('$val')", $this->sesion->dbh));
				}
				if ($keys[0] == "documento")
					$documento_pago->Edit($keys[1], $val);
				else if ($keys[0] == "factura")
					$factura_pago->Edit($keys[1], $val);
			}
			$this->GenerarPagos($factura_pago, $documento_pago);
		}
	}

	public function Query2ObjetoGasto($responseGastos) {
		while ($gasto_ = mysql_fetch_assoc($responseGastos)) {
			$gasto = new Gasto($this->sesion);
			$gasto->guardar_fecha = false;

			foreach ($gasto_ as $key => $val) {
				$keys = explode('_FFF_', $key);
				$gasto->Edit($keys[1], $val);
			}

			$this->GenerarGasto($gasto);
		}
	}

	public function Query2ObjetoFactura($responseFacturas) {
		while ($factura_ = mysql_fetch_assoc($responseFacturas)) {
			$factura = new Factura($this->sesion);
			$factura->guardar_fecha = false;

			foreach ($factura_ as $key => $val) {
				$keys = explode('_FFF_', $key);
				$factura->Edit($keys[1], $val);
			}

			$this->GenerarFactura($factura);
		}
	}

	function Query2ObjetoTarifa($response) {

		while ($row = mysql_fetch_assoc($response)) {
			$sesion = new Sesion();
			$tarifa = new Tarifa($sesion);
			//$tarifa->guardar_fecha = false;
			$forzar_insert = true;

			foreach ($row as $key => $val) {
				$tarifa->Edit($key, $val);
			}

			if (!$this->Write($tarifa, $forzar_insert)) {
				echo "Error al generar el tarifa\n";
				return false;
			} else {
				echo "Tarifa creada\n";
				print_r($tarifa->fields);
			}
		}
	}

	function Query2ObjetoUsuarioTarifa($response) {

		while ($row = mysql_fetch_assoc($response)) {
			$sesion = new Sesion();
			$usuario_tarifa = new UsuarioTarifa($sesion);
			$usuario_tarifa->guardar_fecha = false;
			$forzar_insert = true;

			foreach ($row as $key => $val) {
				$usuario_tarifa->Edit($key, $val);
			}

			if (!$this->Write($usuario_tarifa, $forzar_insert)) {
				echo "Error al generar el usuario_tarifa\n";
			} else {
				echo "Usuario_tarifa creada\n";
				print_r($usuario_tarifa->fields);
			}
		}
	}

	public function ObtenerIDContratoSegunCodigoCliente($codigo_cliente) {
		if (empty($codigo_cliente)) {
			echo "El codigo asunto esta vacio\n";
			return false;
		}

		$cliente = new Cliente($this->sesion);
		$cliente->LoadByCodigo($codigo_cliente);
		if ($cliente->Loaded()) {
			return $cliente->fields['id_contrato'];
		}
		echo "El codigo cliente " . $codigo_cliente . " no existe\n";
		return false;
	}

	public function ObtenerIDContratoSegunCodigoAsunto($codigo_asunto) {
		if (empty($codigo_asunto)) {
			echo "El codigo asunto esta vacio\n";
			return false;
		}

		$asunto = new Asunto($this->sesion);
		$asunto->LoadByCodigo($codigo_asunto);
		if ($asunto->Loaded()) {
			return $asunto->fields['id_contrato'];
		}
		echo "El codigo asunto " . $codigo_asunto . " no existe\n";
		return false;
	}

	function TraspasarMonedaHistorial($response) {
		while ($data = mysql_fetch_assoc($response)) {
			$query = "INSERT INTO moneda_historial
									SET id_moneda = " . ( $data['CodigoMonedaFacturacion'] == 'D' ? '2' : ( $data['CodigoMonedaFacturacion'] == 'E' ? '3' : '1' ) ) . " ,
											fecha = '" . $data['FechaTipoCambio'] . "' ,
											valor = '" . $data['TipoDeCambio'] . "' ,
											moneda_base = " . ( $data['CodigoMonedaFacturacion'] == 'S' ? '1' : '0' );
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}
	}

	function LimpiarCategoriaUsuario($data) {
		switch ($data['usuario_FFF_id_categoria_usuario']) {
			case 'AC': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asociado'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ", $this->sesion->dbh));
				break;
			case 'AD': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Administrativo'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa LIKE 'Administra%' ", $this->sesion->dbh));
				break;
			case 'AJ': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asociado Junior'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ", $this->sesion->dbh));
				break;
			case 'AM': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Administrativo'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa LIKE 'Administra%' ", $this->sesion->dbh));
				break;
			case 'AS': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asociado Senior'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ", $this->sesion->dbh));
				break;
			case 'AT': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Asistente'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ", $this->sesion->dbh));
				break;
			case 'NT': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'NT'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ", $this->sesion->dbh));
				break;
			case 'PO': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Procurador'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ", $this->sesion->dbh));
				break;
			case 'PR': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Practicante'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Laboral' ", $this->sesion->dbh));
				break;
			case 'SE': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Secretaria'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa LIKE 'Administra%' ", $this->sesion->dbh));
				break;
			case 'SO': list($data['usuario_FFF_id_categoria_usuario']) = mysql_fetch_array(mysql_query("SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = 'Socio'", $this->sesion->dbh));
				list($data['usuario_FFF_id_area_usuario']) = mysql_fetch_array(mysql_query("SELECT id FROM prm_area_usuario WHERE glosa = 'Corporativo' ", $this->sesion->dbh));
				break;
		}
		return $data;
	}

	function GlosaCatAIdCat($glosa) {
		$query = "SELECT id_categoria_usuario FROM prm_categoria_usuario WHERE glosa_categoria = TRIM('$glosa')";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_categoria_usuario) = mysql_fetch_array($resp);
		return $id_categoria_usuario;
	}

	function PermisosSegunCategoria($data) {
		$id = $data['usuario_FFF_id_usuario'];
		switch ($data['usuario_FFF_id_categoria_usuario']) {
			case 'AC': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'));
			case 'AD': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'),
					array('permitido' => true, 'codigo_permiso' => 'ADM'),
					array('permitido' => true, 'codigo_permiso' => 'COB'),
					array('permitido' => true, 'codigo_permiso' => 'DAT'),
					array('permitido' => true, 'codigo_permiso' => 'OFI'),
					array('permitido' => true, 'codigo_permiso' => 'REP'),
					array('permitido' => true, 'codigo_permiso' => 'REV')
				);
			case 'AJ': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'));
			case 'AM': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'),
					array('permitido' => true, 'codigo_permiso' => 'ADM'),
					array('permitido' => true, 'codigo_permiso' => 'COB'),
					array('permitido' => true, 'codigo_permiso' => 'DAT'),
					array('permitido' => true, 'codigo_permiso' => 'OFI'),
					array('permitido' => true, 'codigo_permiso' => 'REP'),
					array('permitido' => true, 'codigo_permiso' => 'REV')
				);
			case 'AS': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'));
			case 'AT': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'));
			case 'PO': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'));
			case 'PR': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'));
			case 'SE': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'));
			case 'SO': $permisos = array(array('permitido' => true, 'codigo_permiso' => 'PRO'),
					array('permitido' => true, 'codigo_permiso' => 'ADM'),
					array('permitido' => true, 'codigo_permiso' => 'COB'),
					array('permitido' => true, 'codigo_permiso' => 'DAT'),
					array('permitido' => true, 'codigo_permiso' => 'OFI'),
					array('permitido' => true, 'codigo_permiso' => 'REP'),
					array('permitido' => true, 'codigo_permiso' => 'REV'),
					array('permitido' => true, 'codigo_permiso' => 'SOC')
				);
		}
		return $permisos;
	}

	public function ExisteCodigoSecundario($id_cliente, $codigo_cliente_secundario) {
		if (empty($codigo_cliente_secundario)) {
			echo "Ingrese codigo secundario para verificar si existe\n";
			return true;
		}

		$query_codigos = "SELECT codigo_cliente_secundario FROM cliente";
		if (!empty($id_cliente)) {
			$query_codigos .= " WHERE id_cliente != '" . $id_cliente . "'";
		}

		$resp_codigos = mysql_query($query_codigos, $this->sesion->dbh) or Utiles::errorSQL($query_codigos, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($codigo_cliente_secundario_temp) = mysql_fetch_array($resp_codigos)) {
			if ($codigo_cliente_secundario == $codigo_cliente_secundario_temp) {
				echo "El código ingresado ya existe para otro cliente\n";
				return true;
			}
		}

		return false;
	}

	function AgregarCliente($cliente_generar, $contrato_generar, $cobros_pendientes = array(), $documentos_legales = array(), $agregar_asuntos_defecto = false) {
		if (empty($cliente_generar) or empty($contrato_generar)) {
			echo "Faltan parametro(s), cliente o contrato\n";
			return false;
		}

		$forzar_insert = false;

		$cliente = new Cliente($this->sesion);
		if (!$cliente->Load($cliente_generar->fields["id_cliente"])) {
			$cliente->LoadByCodigo($cliente_generar->fields["codigo_cliente"]);
		}

		if ($cliente->Loaded()) {
			echo "Editando cliente ID " . $cliente->fields["id_cliente"] . "\n";
			if (empty($cliente->$fields['activo'])) {
				$cliente->InactivarAsuntos();
			}
		} else {
			if (empty($cliente_generar->fields["id_cliente"])) {
				echo "Ingresando cliente\n";
			} else {
				echo "Ingresando cliente ID " . $cliente_generar->fields["id_cliente"] . "\n";
				$forzar_insert = true;
			}

			if (empty($cliente_generar->fields['codigo_cliente'])) {
				$cliente_generar->fields['codigo_cliente'] = $cliente->AsignarCodigoCliente();
				echo "Cliente sin codigo, asignando nuevo codigo : " . $cliente_generar->fields['codigo_cliente'] . "\n";
			}
		}

		if (!empty($cliente_generar->fields["codigo_cliente_secundario"]) and $this->ExisteCodigoSecundario($cliente->fields['id_cliente'], $cliente_generar->fields["codigo_cliente_secundario"])) {
			return false;
		}

		if ($this->GuardarCliente($cliente, $cliente_generar)) {
			$contrato = new Contrato($this->sesion);
			$contrato->Load($cliente->fields['id_contrato']);
			if ($contrato->Loaded()) {
				echo "Editando contrato ID " . $contrato->fields['id_contrato'] . " para el cliente ID " . $cliente->fields['id_cliente'] . "\n";
			} else {
				unset($contrato_generar->fields['id_contrato']);
				echo "Ingresando contrato para el cliente ID " . $cliente->fields['id_cliente'] . "\n";
			}

			if (!$this->GuardarContrato($contrato, $contrato_generar, $cliente, $cobros_pendientes, $documentos_legales)) {
				return false;
			}

			$cliente->Edit("id_contrato", $contrato->fields['id_contrato']);
			if ($this->Write($cliente)) {
				echo "Cliente y contrato guardados\n";
			} else {
				echo "Error al guardar el cliente y el contrato\n";
				return false;
			}
		}

		if ($agregar_asuntos_defecto) {
			if (((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'AgregarAsuntosPorDefecto') != '') || (method_exists('Conf', 'AgregarAsuntosPorDefecto')))) {

				if (method_exists('Conf', 'GetConf')) {
					$asuntos = explode(';', Conf::GetConf($this->sesion, 'AgregarAsuntosPorDefecto'));
				} else {
					$asuntos = Conf::AgregarAsuntosPorDefecto();
				}

				foreach ($asuntos as $glosa_asunto) {
					$asunto = new Asunto($this->sesion);
					$asunto->Edit('codigo_asunto', $asunto->AsignarCodigoAsunto($cliente->fields['codigo_cliente']));
					$asunto->Edit('codigo_asunto_secundario', $asunto->AsignarCodigoAsuntoSecundario($cliente->fields['codigo_cliente_secundario']));
					$asunto->Edit('glosa_asunto', $glosa_asunto);
					$asunto->Edit('codigo_cliente', $cliente->fields['codigo_cliente']);
					$asunto->Edit('id_contrato', $contrato->fields['id_contrato']);
					$asunto->Edit('id_usuario', $cliente->fields['id_usuario_encargado']);
					$asunto->Edit('contacto', $cliente->fields['nombre_contacto']);
					$asunto->Edit("fono_contacto", $cliente->fields['fono_contacto']);
					$asunto->Edit("email_contacto", $cliente->fields['mail_contacto']);
					$asunto->Edit("direccion_contacto", $cliente->fields['dir_calle'] . " " . $cliente->fields['dir_numero'] . " " . $cliente->fields['dir_comuna']);
					$asunto->Edit("id_encargado", $cliente->fields['id_usuario_encargado']);
					if (!$this->Write($asunto)) {
						"El error al guardar el asunto\n";
						continue;
					}
				}
			}
		}
	}

	public function GuardarCliente($cliente, $cliente_generar) {
		$cliente->Edit("glosa_cliente", $cliente_generar->fields["glosa_cliente"]);
		$cliente->Edit("codigo_cliente", $cliente_generar->fields["codigo_cliente"]);
		$cliente->Edit("codigo_cliente_secundario", empty($cliente_generar->fields["codigo_cliente_secundario"]) ? "NULL" : $cliente->fields["codigo_cliente_secundario"]);
		$cliente->Edit("id_moneda", $cliente_generar->fields["id_moneda"]);
		$cliente->Edit("activo", $cliente_generar->fields["activo"] == 1 ? '1' : '0');
		$cliente->Edit("id_usuario_encargado", $cliente_generar->fields["id_usuario_encargado"]);

		if (!$this->ValidarCliente($cliente)) {
			return false;
		}

		$cliente->Edit("id_grupo_cliente", empty($cliente_generar->fields["id_grupo_cliente"]) ? "NULL" : $cliente_generar->fields["id_grupo_cliente"]);

		if (!$this->Write($cliente)) {
			echo "Error al guardar cliente\n";
			return false;
		}

		echo "Cliente ID " . $cliente->fields['id_cliente'] . " guardado\n";
		return true;
	}

	public function EmitirCobros($from, $size) {

		$query = "SELECT cobro.id_cobro, cobro.id_usuario, cobro.codigo_cliente, cobro.id_contrato, contrato.id_carta, cobro.estado,
                                cobro.opc_papel,contrato.id_carta, cobro.fecha_creacion
                                FROM cobro
                                LEFT JOIN contrato ON cobro.id_contrato = contrato.id_contrato
                                WHERE cobro.estado IN ( 'CREADO', 'EN REVISION' ) ";
		if (intval($size) > 0)
			$query.=" limit 0," . intval($size); // como solamente va tomando los que estan creados y en rev, empieza de 0

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while ($cob = mysql_fetch_assoc($resp)) {
			$cobro = new Cobro($this->sesion);
			if ($cobro->Load($cob['id_cobro'])) {
				$cobro->Edit('id_carta', $cob['id_carta']);
				if ($cobro->fields['id_contrato'] > 0) {
					$ret = $cobro->GuardarCobro(true, true);
					$cobro->Edit('etapa_cobro', '5');
					$cobro->Edit('fecha_emision', $cob['fecha_creacion']);
					if ($cob['estado'] == 'CREADO') {
						$cobro->Edit('estado', 'INCOBRABLE');
					} else {
						$cobro->Edit('estado', 'EMITIDO');
					}
					if ($ret == '') {
						$his = new Observacion($this->sesion);
						$his->Edit('fecha', date('Y-m-d H:i:s'));
						$his->Edit('comentario', __('COBRO EMITIDO'));
						$his->Edit('id_usuario', $sesion->usuario->fields['id_usuario']);
						$his->Edit('id_cobro', $cobro->fields['id_cobro']);
						//$his->Write();
						$cobro->ReiniciarDocumento();
						$cobro->Write();
						echo "cobro " . $cobro->fields['id_cobro'] . " emitido con exito \n";
						$this->filasprocesadas++;
					} else {
						echo "no se pudo emitir cobro " . $cobro->fields['id_cobro'] . "\n";
						$this->errorcount++;
					}
				} else {
					echo "El cobro " . $cobro->fields['id_cobro'] . " no tiene contrato, no puedo procesarlo.\n";
					$this->errorcount++;
				}
			} else {
				echo "no se pudo cargar el cobro " . $cobro->fields['id_cobro'] . " para emitirlo.\n";
				$this->errorcount++;
			}
		}
	}

	public function EmitirFacturasPRC() {
		$query = "SELECT
									cobro.id_cobro										as id_factura,
									cobro.id_cobro										as id_cobro,
									cobro.porcentaje_impuesto								as porcentaje_impuesto,
									cobro.id_estado_factura								as id_estado,
									documento.impuesto									as iva,
									documento.monto										as total,
									cobro.fecha_creacion									as fecha,
									contrato.factura_razon_social							as cliente,
									contrato.rut										as RUT_cliente,
									contrato.factura_direccion								as direccion_cliente,
									cobro.codigo_cliente									as codigo_cliente,
									case substring_index(cobro.documento,'-',1)
									when 'FA' then 1
									when 'NC' then 2
									when 'ND' then 3
									when 'BO' then 4 end									as id_documento_legal,
									substring_index(substring_index(cobro.documento,'-',2),'-',-1)	as serie_documento_legal,
									substring_index(cobro.documento,'-',-1)					as numero,
									cobro.factura_razon_social								as cliente,
									cobro.factura_rut									as RUT_cliente,
									documento.subtotal_honorarios							as subtotal,
									documento.subtotal_sin_descuento						as honorarios,
									documento.subtotal_sin_descuento						as subtotal_sin_descuento,
									documento.subtotal_gastos								as subtotal_gastos,
									documento.subtotal_gastos_sin_impuesto					as subtotal_gastos_sin_impuesto
								FROM documento
								  JOIN cobro ON cobro.id_cobro = documento.id_cobro
								  JOIN contrato ON cobro.id_contrato = contrato.id_contrato
								WHERE cobro.documento IS NOT NULL
									AND cobro.documento != '' 		AND documento.tipo_doc = 'N' and cobro.fecha_emision<=20120825				";
		if ($size > 0)
			$query.="limit $from,$size";
		$this->sesion->debug($query);
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while ($arreglo_factura = mysql_fetch_assoc($resp)) {
			$factura = new Factura($this->sesion);
			$factura->guardar_fecha = true;

			foreach ($arreglo_factura as $key => $val) {
				$factura->Edit($key, $val);
			}

			$this->GenerarFactura($factura);
		}
	}

	public function ValidarCliente($cliente) {
		if (!$this->ValidarCodigo($cliente, "id_moneda", "prm_moneda", true)) {
			return false;
		}

		if (!$this->ValidarCodigo($cliente, "id_grupo_cliente", "grupo_cliente")) {
			return false;
		}

		return $this->ValidarCodigo($cliente, "id_usuario", "usuario", false, "id_usuario_encargado");
	}

	public function ValidarContrato($contrato) {
		if (!$this->ValidarCodigo($contrato, "id_moneda", "prm_moneda", true)) {
			return false;
		}

		if (!$this->ValidarCodigo($contrato, "codigo_cliente", "cliente", true, "codigo_cliente", true)) {
			return false;
		}

		return $this->ValidarCodigo($contrato, "id_usuario", "usuario", false, "id_usuario_responsable");
	}

	public function GuardarContrato($contrato, $contrato_generar, $cliente, $cobros_pendientes = array(), $documentos_legales = array()) {
		$moneda = new Moneda($this->sesion);
		$contrato->guardar_fecha = false;

		if ($contrato_generar->fields["forma_cobro"] != 'TASA' && $contrato_generar->fields["monto"] == 0) {
			echo __('Ud. ha seleccionado forma de cobro:') . " " . $contrato_generar->fields["forma_cobro"] . " " . __('y no ha ingresado monto') . "\n";
			echo "Error al guardar contrato\n";
			return false;
		} else if ($contrato_generar->fields["forma_cobro"] == 'TASA') {
			$contrato_generar->fields["monto"] = '0';
		}

		$contrato->Edit("activo", $contrato_generar->fields["activo_contrato"] ? 'SI' : 'NO');
		$contrato->Edit("usa_impuesto_separado", $contrato_generar->fields["usa_impuesto_separado"] ? '1' : '0');
		$contrato->Edit("usa_impuesto_gastos", $contrato_generar->fields["usa_impuesto_gastos"] ? '1' : '0');

		$contrato->Edit("glosa_contrato", $contrato_generar->fields["glosa_contrato"]);
		$contrato->Edit("codigo_cliente", empty($contrato_generar->fields["codigo_cliente"]) ? $cliente->fields['codigo_cliente'] : $contrato_generar->fields["codigo_cliente"]);

		$contrato->Edit("id_usuario_responsable", !empty($contrato_generar->fields["id_usuario_responsable"]) ? $contrato_generar->fields["id_usuario_responsable"] : "NULL");
		$contrato->Edit("id_usuario_secundario", !empty($contrato_generar->fields["id_usuario_secundario"]) ? $contrato_generar->fields["id_usuario_secundario"] : "NULL" );
		$contrato->Edit("centro_costo", $contrato_generar->fields["centro_costo"]);

		$contrato->Edit("observaciones", $contrato_generar->fields["observaciones"]);




		/* 	FFF: EL RESTO QUEDA COMENTADO PORQUE HAY MAPEO 1:1 entre campo y llave
		  $contrato->Edit("centro_costo", $contrato_generar->fields["centro_costo"]);
		  $contrato->Edit("glosa_contrato", $contrato_generar->fields["glosa_contrato"]);
		  $contrato->Edit("observaciones", $contrato_generar->fields["observaciones"]);
		  if (method_exists('Conf', 'GetConf')) {
		  if (Conf::GetConf($this->sesion, 'TituloContacto')) {
		  $contrato->Edit("titulo_contacto", $contrato_generar->fields["titulo_contacto"]);
		  $contrato->Edit("contacto", $contrato_generar->fields["nombre_contacto"]);
		  $contrato->Edit("apellido_contacto", $contrato_generar->fields["apellido_contacto"]);
		  }
		  } else if (method_exists('Conf', 'TituloContacto')) {
		  if (Conf::TituloContacto()) {
		  $contrato->Edit("titulo_contacto", $contrato_generar->fields["titulo_contacto"]);
		  $contrato->Edit("contacto", $contrato_generar->fields["nombre_contacto"]);
		  $contrato->Edit("apellido_contacto", $contrato_generar->fields["apellido_contacto"]);
		  }
		  }

		  $contrato->Edit("contacto", $contrato_generar->fields["contacto"]);
		  $contrato->Edit("fono_contacto", $contrato_generar->fields["fono_contacto_contrato"]);
		  $contrato->Edit("email_contacto", $contrato_generar->fields["email_contacto_contrato"]);
		  $contrato->Edit("direccion_contacto", $contrato_generar->fields["direccion_contacto_contrato"]);
		  $contrato->Edit("es_periodico", $contrato_generar->fields["es_periodico"]);
		  $contrato->Edit("activo", $contrato_generar->fields["activo_contrato"] ? 'SI' : 'NO');
		  $contrato->Edit("periodo_fecha_inicio", Utiles::fecha2sql($contrato_generar->fields["periodo_fecha_inicio"]));
		  $contrato->Edit("periodo_repeticiones", $contrato_generar->fields["periodo_repeticiones"]);
		  $contrato->Edit("periodo_intervalo", $contrato_generar->fields["periodo_intervalo"]);
		  $contrato->Edit("periodo_unidad", $contrato_generar->fields["codigo_unidad"]);
		  $contrato->Edit("monto", $contrato_generar->fields["monto"]);
		  $contrato->Edit("id_moneda", $contrato_generar->fields["id_moneda"]);
		  $contrato->Edit("forma_cobro", $contrato_generar->fields["forma_cobro"]);
		  $contrato->Edit("fecha_inicio_cap", Utiles::fecha2sql($contrato_generar->fields["fecha_inicio_cap"]));
		  $contrato->Edit("retainer_horas", $contrato_generar->fields["retainer_horas"]);
		  $contrato->Edit("id_usuario_modificador", $this->sesion->usuario->fields['id_usuario']);
		  $contrato->Edit("id_carta", $contrato_generar->fields["id_carta"] ? $contrato->fields["id_carta"] : 'NULL');
		  $contrato->Edit("id_formato", $contrato_generar->fields["id_formato"]);
		  $contrato->Edit("id_tarifa", $contrato_generar->fields["id_tarifa"] ? $contrato->fields["id_tarifa"] : 'NULL');
		  $contrato->Edit("id_tramite_tarifa", $contrato_generar->fields["id_tramite_tarifa"] ? $contrato->fields["id_tramite_tarifa"] : 'NULL' );
		  $contrato->Edit("id_moneda_tramite", empty($contrato_generar->fields['id_moneda_tramite']) ? '1' : $contrato_generar->fields['id_moneda_tramite']);

		  //Facturacion
		  $contrato->Edit("rut", $contrato_generar->fields["factura_rut"]);
		  $contrato->Edit("factura_razon_social", $contrato_generar->fields["factura_razon_social"]);
		  $contrato->Edit("factura_giro", $contrato_generar->fields["factura_giro"]);
		  $contrato->Edit("factura_direccion", $contrato_generar->fields["factura_direccion"]);
		  $contrato->Edit("factura_telefono", $contrato_generar->fields["factura_telefono"]);
		  $contrato->Edit("cod_factura_telefono", $contrato_generar->fields["cod_factura_telefono"]);


		  #Opc contrato
		  $contrato->Edit("opc_ver_modalidad", $contrato_generar->fields["opc_ver_modalidad"]);
		  $contrato->Edit("opc_ver_profesional", $contrato_generar->fields["opc_ver_profesional"]);
		  $contrato->Edit("opc_ver_gastos", $contrato_generar->fields["opc_ver_gastos"]);
		  $contrato->Edit("opc_ver_morosidad", $contrato_generar->fields["opc_ver_morosidad"]);
		  $contrato->Edit("opc_ver_descuento", $contrato_generar->fields["opc_ver_descuento"]);
		  $contrato->Edit("opc_ver_tipo_cambio", $contrato_generar->fields["opc_ver_tipo_cambio"]);
		  $contrato->Edit("opc_ver_numpag", $contrato_generar->fields["opc_ver_numpag"]);
		  $contrato->Edit("opc_ver_resumen_cobro", $contrato_generar->fields["opc_ver_resumen_cobro"]);
		  $contrato->Edit("opc_ver_carta", $contrato_generar->fields["opc_ver_carta"]);
		  $contrato->Edit("opc_papel", $contrato_generar->fields["opc_papel"]);
		  $contrato->Edit("opc_moneda_total", $contrato_generar->fields["opc_moneda_total"]);
		  $contrato->Edit("opc_moneda_gastos", $contrato_generar->fields["opc_moneda_gastos"]);

		  $contrato->Edit("opc_ver_solicitante", $contrato_generar->fields["opc_ver_solicitante"]);

		  $contrato->Edit("opc_ver_asuntos_separados", $contrato_generar->fields["opc_ver_asuntos_separados"]);
		  $contrato->Edit("opc_ver_horas_trabajadas", $contrato_generar->fields["opc_ver_horas_trabajadas"]);
		  $contrato->Edit("opc_ver_cobrable", $contrato_generar->fields["opc_ver_cobrable"]);

		  $contrato->Edit("codigo_idioma", $contrato_generar->fields["codigo_idioma"] != '' ? $contrato->fields["codigo_idioma"] : 'es');

		  #descto
		  $contrato->Edit("tipo_descuento", $contrato_generar->fields["tipo_descuento"]);
		  if ($contrato_generar->fields["PORCENTAJE"]) {
		  $contrato->Edit("porcentaje_descuento", !empty($contrato_generar->fields["porcentaje_descuento"]) ? $contrato_generar->fields["porcentaje_descuento"] : '0');
		  $contrato->Edit("descuento", '0');
		  } else {
		  $contrato->Edit("descuento", empty($contrato_generar->fields["descuento"]) ? '0' : $contrato_generar->fields["descuento"]);
		  $contrato->Edit("porcentaje_descuento", '0');
		  }
		  $contrato->Edit("id_moneda_monto", $contrato_generar->fields["id_moneda_monto"]);
		  $contrato->Edit("alerta_hh", $contrato_generar->fields["alerta_hh"]);
		  $contrato->Edit("alerta_monto", $contrato_generar->fields["alerta_monto"]);
		  $contrato->Edit("limite_hh", $contrato_generar->fields["limite_hh"]);
		  $contrato->Edit("limite_monto", $contrato_generar->fields["limite_monto"]);
		  $contrato->Edit("separar_liquidaciones", $contrato_generar->fields["separar_liquidaciones"]);

		  if (!$this->ValidarContrato($contrato)) {
		  echo "Error al guardar el contrato\n";
		  return false;
		  }

		  if (!$this->Write($contrato)) {
		  echo "Error al guardar el contrato\n";
		  return false;
		  }

		  echo "Contrato ID " . $contrato->fields['id_contrato'] . " guardado\n";

		  return true;

		  /*
		  //Cobros pendientes
		  CobroPendiente::EliminarPorContrato($this->sesion, $contrato->fields['id_contrato']);
		  foreach ($cobros_pendientes as $cobro_pendiente)
		  {
		  $cobro = new CobroPendiente($this->sesion);
		  $cobro->Edit("id_contrato", $contrato->fields["id_contrato"]);
		  $cobro->Edit("fecha_cobro", Utiles::fecha2sql($cobro_pendiente->fields["fecha_cobro"]));
		  $cobro->Edit("descripcion", $cobro_pendiente->fields["descripcion"]);
		  $cobro->Edit("monto_estimado", $cobro_pendiente->fields["monto_estimado"]);
		  if (!self::Write($cobro))
		  {
		  $this->logs[] = $cobro->error;
		  }
		  }

		  //Documentos legales
		  ContratoDocumentoLegal::EliminarDocumentosLegales($this->sesion,
		  $contrato->fields['id_contrato']);
		  foreach ($documentos_legales as $documento_legal) {
		  $contrato_documento_legal = new ContratoDocumentoLegal($this->sesion);
		  $contrato_documento_legal->Edit('id_contrato', $contrato->fields["id_contrato"]);
		  $contrato_documento_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["id_tipo_documento_legal"]);
		  if (!empty($documento_legal->fields["honorarios"]))
		  {
		  $contrato_documento_legal->Edit('honorarios', 1);
		  }
		  if (!empty($documento_legal->fields["gastos_con_iva"]))
		  {
		  $contrato_documento_legal->Edit('gastos_con_impuestos', 1);
		  }
		  if (!empty($documento_legal->fields["gastos_sin_iva"]))
		  {
		  $contrato_documento_legal->Edit('gastos_sin_impuestos', 1);
		  }
		  $contrato_documento_legal->Edit('id_tipo_documento_legal', $documento_legal->fields["documento_legal"]);
		  if (!self::Write($contrato_documento_legal))
		  {
		  $this->logs[] = $contrato_documento_legal->error;
		  }
		  }
		 */
	}

	#Recibe un arreglo de items, construido de la forma
	# $items = array();
	# //foreach [nombre,rut,etc]...
	# $u = new Usuario($this->sesion);
	# $u->Edit('nombre',$nombre)
	# $u->Edit('rut',$rut);
	# $u->Edit ...
	# $items[$rut]['usuario'] = $u;
	# $items[$rut]['permisos'] = array('ADM','PRO');
	# //next
	#
	#  $secretarios = array( $rut_secretario => array($rut_jefe1, $rut_jefe2) )
	#  $revisores = array ( $rut_revisor => array($rut_revisado1, $rut_revisado2) )

	public function AgregarUsuarios($items = array(), $secretarios = array(), $revisores = array()) {
		if (!empty($items)) {
			foreach ($items as $item)
				AgregarUsuario($item['usuario'], $item['permisos']);

			if (!empty($secretarios))
				foreach ($secretarios as $rut_secretario => $ruts_jefes) {
					$secretario = $items[$rut_secretario]['usuario'];
					$ids_jefes = array();

					foreach ($ruts_jefes as $rut_jefe)
						$ids_jefes[] = $items[$rut_jefe]['usuario']->fields['id_usuario'];
					$secretario->GuardarSecretario($ids_jefes);
				}

			if (!empty($revisores))
				foreach ($revisores as $rut_revisor => $ruts_revisados) {
					$revisor = $items[$rut_revisor]['usuario'];
					$ids_revisados = array();

					foreach ($ruts_revisados as $rut_revisado)
						$ids_revisados[] = $items[$rut_revisado]['usuario']->fields['id_usuario'];
					$revisor->GuardarRevisado($ids_revisados);
				}
		}
	}

	/* Datos a recibir
	  #Datos requeridos:

	  #email
	  #rut (Utiles::LimpiarRut($rut))
	  #dv_rut
	  #nombre

	  #apellido1
	  #apellido2
	  #id_categoria_usuario
	  #id_area_usuario

	  #telefono1
	  #telefono2
	  #dir_calle
	  #dir_numero
	  #dir_depto

	  #dir_comuna
	  #activo
	  #restriccion_min
	  #restriccion_max


	  #dias_ingreso_trabajo
	  #retraso_max
	  #restriccion_diario

	  #alerta_diaria
	  #alerta_semanal
	  #alerta_revisor
	  #id_moneda_costo

	  #Datos opcionales:
	  #username :: default = nombre + apellido1 + apellido2

	  #password (en plaintext) :: default = Utiles::NewPassword();
	  #Datos generados:

	  #visible -> $activo==1 ? 1 : 0

	  #Parametros:
	  #permisos. Array('ADM','PRO',etc). 'ALL' siempre se incluye automÃ¡ticamente.
	  #secretario_de. Ids de los usuarios del cual este es secretario. (Por lo tanto, los secretarios se ingresan al final). Array('1'=>'1','2'=>'2').
	  #usuarios_revisados. Ids de los usuarios a los cuales este revisa. Array('1'=>'1','2'=>'2')
	 */

	public function AgregarUsuario($usuario = null, $permisos = array(), $secretario_de = array(), $usuarios_revisados = array()) {
		global $tbl_usuario_permiso, $tbl_prm_permiso;
		$tbl_usuario_permiso = 'usuario_permiso';
		$tbl_prm_permiso = 'prm_permisos';

		echo "---Ingresando usuario\n";

		$usuario->Edit('id_area_usuario', empty($usuario->fields['id_area_usuario']) ? 1 : $usuario->fields['id_area_usuario']);

		if (!$this->ValdiarUsuario($usuario)) {
			echo "Error al ingresar el usuario\n";
			return false;
		}

		$usuario->Edit('id_categoria_usuario', empty($usuario->fields['id_categoria_usuario']) ? "NULL" : $usuario->fields['id_categoria_usuario']);
		/* $usuario->Edit('dir_numero',
		  empty($usuario->fields['$dir_numero']) ? "NULL" : $usuario->fields['$dir_numero']); */
		$usuario->Edit('dir_comuna', empty($usuario->fields['dir_comuna']) ? "NULL" : $usuario->fields['dir_comuna']);

		#Genero el username si no existe
		if (!empty($usuario->fields['id_usuario'])) {
			$usuario->Edit('id_usuario', (int) $usuario->fields['id_usuario']);
			$forzar_insert = true;
		}

		if (!$usuario->fields['username']) {
			$usuario->Edit('username', $nombre . ' ' . $apellido1 . ' ' . $apellido2);
		}

		#Confirmo que el username no exista
		$query = "SELECT count(*) FROM usuario WHERE username = '" . addslashes($usuario->fields['username']) . "'";
		$resp = mysql_query($query, $this->sesion->dbh);
		if (!$resp) {
			echo "Error explicacion: " . mysql_error($this->sesion->dbh) . "\n";
			echo "Query:</b> " . $query . "\n";
			return false;
		}

		list($cantidad) = mysql_fetch_array($resp);
		if ($cantidad > 0) {
			echo "Error ingreso usuario " . $usuario->fields['id_usuario'] . ": username '" . $usuario->fields['username'] . "' ya existe\t\n";
			return false;
		}

		#Confirmo que venga email
		if ($usuario->fields['email'] == "") {
			echo "Error ingreso usuario: debe ingresar email\n";
			return false;
		}

		#Visible depende de activo
		$usuario->Edit('visible', $usuario->fields['activo'] == 1 ? '1' : '0');

		#Calculo el md5 del password provisto, o uno nuevo si no viene.
		if (!$usuario->fields['password']) {
			$password = Utiles::NewPassword();
			//$this->logs[] .= 'Usuario: username "'.$usuario->fields['username'].'" password = "'.$password.'"';
		} else {
			$password = $usuario->fields['password'];
		}
		#Ingreso el password

		$usuario->Edit('password', md5($password));

		if ($this->Write($usuario, $forzar_insert)) {
			#Cargar permisos
			if (is_array($permisos)) {
				foreach ($permisos as $index => $permiso->fields) {
					if (!$usuario->EditPermisos($permiso)) {
						echo "Error en permiso usuario: '" . $usuario->fields['username'] . "': " . $usuario->error . "\n";
					}
				}
			}

			$usuario->PermisoAll($usuario->fields['id_usuario']);

			#End Cargar permisos
			$usuario->GuardarSecretario($secretario_de);
			$usuario->GuardarRevisado($usuarios_revisados);

			$usuario->GuardarTarifaSegunCategoria($usuario->fields['id_usuario'], $usuario->fields['id_categoria_usuario']);
			echo "Usuario '" . $usuario->fields['username'] . "' ingresado con éxito, su password es: " . $password . "\n";
		}
	}

	function Query2ObjetoHora($response) {
		while ($row = mysql_fetch_assoc($response)) {
			$trabajo = new Trabajo($this->sesion);

			foreach ($row as $key => $val) {
				$trabajo->Edit($key, $val);
			}

			$this->AgregarHora($trabajo);
		}
	}

	public function AgregarHora($hora_generar = null) {
		/*
		 * Validar FK
		 */

		$hora = new Trabajo($this->sesion);
		$hora->guardar_fecha = false;

		if ($hora_generar->fields['id_trabajo'] > 0)
			$hora->Load($hora_generar->fields['id_trabajo']);

		#Instancio Clases a usar en validación de FK
		$usuario = new Usuario($this->sesion);
		$asunto = new Asunto($this->sesion);
		$moneda = new Moneda($this->sesion);

		#Confirmo que el id_usuario exista
		$id_usuario = (int) $hora_generar->fields['id_usuario'];
		$hora->Edit('id_usuario', $id_usuario);

		$codigo_asunto = substr($hora_generar->fields['codigo_asunto'], 0, 4) . '-0' . substr($hora_generar->fields['codigo_asunto'], -3);
		$hora->Edit('codigo_asunto', $codigo_asunto);

		#Confirmo que el id_moneda exista
		$hora->Edit('id_moneda', !empty($hora_generar->fields['id_moneda']) ? $hora_generar->fields['id_moneda'] : "2");

		$hora->Edit('fecha', !empty($hora_generar->fields['fecha']) ? $hora_generar->fields['fecha'] : "0000-00-00" );
		$hora->Edit('id_cobro', !empty($hora_generar->fields['id_cobro']) ? $hora_generar->fields['id_cobro'] : "NULL" );
		$hora->Edit('duracion', !empty($hora_generar->fields['duracion']) ? $hora_generar->fields['duracion'] : "00:00:00" );
		$hora->Edit('duracion_cobrada', !empty($hora_generar->fields['duracion_cobrada']) ? $hora_generar->fields['duracion_cobrada'] : "00:00:00" );
		$hora->Edit('descripcion', !empty($hora_generar->fields['descripcion']) ? addslashes($hora_generar->fields['descripcion']) : "" );
		$hora->Edit('fecha_creacion', !empty($hora_generar->fields['fecha_creacion']) ? $hora_generar->fields['fecha_creacion'] : "0000-00-00 00:00:00" );
		$hora->Edit('fecha_modificacion', !empty($hora_generar->fields['fecha_modificacion']) ? $hora_generar->fields['fecha_modificacion'] : "0000-00-00 00:00:00" );
		$hora->Edit('tarifa_hh', !empty($hora_generar->fields['tarifa_hh']) ? $hora_generar->fields['tarifa_hh'] : "0" );
		$hora->Edit('solicitante', !empty($hora_generar->fields['solicitante']) ? addslashes($hora_generar->fields['solicitante']) : "" );
		$hora->Edit('cobrable', (!empty($hora_generar->fields['cobrable']) || $hora_generar->fields['cobrable'] == "0" ) ? $hora_generar->fields['cobrable'] : "1");

		/*
		 * Registrar información
		 */
		if ($this->Write($hora)) {
			echo "Trabajo " . $hora->fields['id_trabajo'] . " ingresado\n";
		} else {
			echo "Trabajo NO FUE ingresado\n";
		}
	}

	public function Write($objeto, $forzar_insert = false) {
		//echo '<pre>'; print_r($objeto->fields); echo '</pre>';
		$objeto->error = "";

		if ($objeto->Loaded() and !$forzar_insert) {
			$query = "UPDATE " . $objeto->tabla . " SET ";
			if ($objeto->guardar_fecha)
				$query .= "fecha_modificacion=NOW(),";

			$c = 0;
			foreach ($objeto->fields as $key => $val) {
				if ($objeto->changes[$key]) {
					$do_update = true;
					if ($c > 0)
						$query .= ",";
					if ($val != 'NULL')
						$query .= "$key = '" . addslashes($val) . "'";
					else
						$query .= "$key = NULL ";
					$c++;
				}
			}

			$query .= " WHERE " . $objeto->campo_id . "='" . $objeto->fields[$objeto->campo_id] . "'";
			if ($do_update) { //Solo en caso de que se haya modificado algÃºn campo
				$resp = mysql_query($query, $this->sesion->dbh);
				if (!$resp) {
					echo "Error explicacion: " . mysql_error($this->sesion->dbh) . "\n";
					echo "Datos: " . $error_string . "\n";
					echo "Query: " . $query . "\n";
					return false;
				}
				return true;
			} else {//Retorna true ya que si no quiere hacer update la funciÃ³n corriÃ³ bien
				return true;
			}
			#Utiles::CrearLog($this->sesion, "reserva", $this->fields[$campo_id], "MODIFICAR","",$query);
		} else {
			if ($replace == true) {
				$query = "REPLACE  INTO " . $objeto->tabla . " SET ";
			} else {
				$query = "INSERT INTO " . $objeto->tabla . " SET ";
			}
			if ($objeto->guardar_fecha)
				$query .= "fecha_creacion=NOW(),";

			$c = 0;
			$error_string = "";

			foreach ($objeto->fields as $key => $val) {
				$error_string .= " $key: $val , ";
				if ($objeto->changes[$key]) {
					if ($c > 0)
						$query .= ",";
					if ($val != 'NULL')
						$query .= "$key = '" . addslashes($val) . "'";
					else
						$query .= "$key = NULL ";

					$c++;
				}
			}

			$resp = mysql_query($query, $this->sesion->dbh);
			if (!$resp) {

				echo "Error explicacion: " . mysql_error($this->sesion->dbh) . "\n";
				echo "Datos: " . $error_string . "\n";
				echo "Query: " . $query . "\n";
				return false;
			} else {
				return true;
			}
			$objeto->fields[$objeto->campo_id] = mysql_insert_id($this->sesion->dbh);
		}
		return true;
	}

	/*
	  public function TieneTrabajos($id_contrato, $fecha_ini, $fecha_fin)
	  {
	  $query = "SELECT COUNT(*)  FROM trabajo JOIN asunto ON trabajo.codigo_asunto=asunto.codigo_asunto  LEFT JOIN contrato ON asunto.id_contrato=contrato.id_contrato WHERE contrato.id_contrato='$id_contrato'  AND trabajo.fecha < '" . date("Y-m-d", $fecha_fin) . "' AND trabajo.fecha > '" . date("Y-m-d", $fecha_ini) . "'";
	  $res_nro_trabajos = mysql_query($query, $this->sesion->dbh);
	  if (!$nro_trabajos)
	  {
	  $this->logs[] = "Error: " . mysql_error($this->sesion->dbh);
	  return false;
	  }
	  list($nro_trabajos) = mysql_fetch_array($res_nro_trabajos);
	  if ($nro_trabajos == 0)
	  {
	  return true;
	  }
	  return false;
	  }
	 */

	function GenerarCobroBase($cobro_generar = null, $incluye_gastos = true, $incluye_honorarios = true, $con_gastos = false, $solo_gastos = false) {
		$cobro_guardado = true;

		$incluye_gastos = empty($incluye_gastos) ? '0' : '1';
		$incluye_honorarios = empty($incluye_honorarios) ? '0' : '1';

		$cobro = new Cobro($this->sesion);
		$cobro->guardar_fecha = false;

		if (!empty($cobro_generar)) {
			$cobro->Load($cobro_generar->fields['id_cobro']);
		}

		if ($cobro->Loaded()) {
			echo "--Editando cobro con ID " . $cobro_generar->fields['id_cobro'] . "\n";
		} else {
			if (empty($cobro_generar->fields['id_cobro'])) {
				echo "--Generando cobro\n";
			} else {
				$cobro->Edit('id_cobro', $cobro_generar->fields['id_cobro']);
				echo "--Generando cobro con ID " . $cobro_generar->fields['id_cobro'] . "\n";
				$forzar_insert = true;
			}
		}

		if (!$this->ValidarCobro($cobro_generar)) {
			if (empty($cobro_generar->fields['id_cobro'])) {
				echo "Error al generar cobro\n";
			} else {
				echo "Error al generar cobro con ID " . $cobro_generar->fields['id_cobro'] . "\n";
			}
			return false;
		}

		$contrato = new Contrato($this->sesion, null, null, 'contrato', 'id_contrato');
		$contrato->Load($cobro_generar->fields['id_contrato']);

		$contrato->EliminarBorrador($incluye_gastos, $incluye_honorarios);

		$moneda_base = Utiles::MonedaBase($this->sesion);
		$moneda = new Moneda($this->sesion);
		$moneda->Load($contrato->fields['id_moneda']);

		$cobro->Edit('id_usuario', !empty($cobro_generar->fields['id_usuario']) ? $cobro_generar->fields['id_usuario'] : "NULL");
		$cobro->Edit('codigo_cliente', empty($cobro_generar->fields['codigo_cliente']) ? $contrato->fields['codigo_cliente'] : $cobro_generar->fields['codigo_cliente']);
		$cobro->Edit('id_contrato', $contrato->fields['id_contrato']);
		$cobro->Edit('id_moneda', empty($cobro_generar->fields['id_moneda']) ? $contrato->fields['id_moneda'] : $cobro_generar->fields['id_moneda']);
		$cobro->Edit('tipo_cambio_moneda', $moneda->fields['tipo_cambio']);
		$cobro->Edit('forma_cobro', empty($cobro_generar->fields['forma_cobro']) ? $contrato->fields['forma_cobro'] : $cobro_generar->fields['forma_cobro']);

		$cobro->Edit('retainer_horas', empty($cobro_generar->fields['retainer_horas']) ? $contrato->fields['retainer_horas'] : $cobro_generar->fields['retainer_horas']);

		//Opciones
		$cobro->Edit('id_carta', empty($cobro_generar->fields['id_carta']) ? $contrato->fields['id_carta'] : $cobro_generar->fields['id_carta']);
		$cobro->Edit("opc_ver_modalidad", empty($cobro_generar->fields['opc_ver_modalidad']) ? $contrato->fields['opc_ver_modalidad'] : $cobro_generar->fields['opc_ver_modalidad']);
		$cobro->Edit("opc_ver_profesional", empty($cobro_generar->fields['opc_ver_profesional']) ? $contrato->fields['opc_ver_profesional'] : $cobro_generar->fields['opc_ver_profesional']);
		$cobro->Edit("opc_ver_gastos", empty($cobro_generar->fields['opc_ver_gastos']) ? $contrato->fields['opc_ver_gastos'] : $cobro_generar->fields['opc_ver_gastos']);
		$cobro->Edit("opc_ver_morosidad", empty($cobro_generar->fields['opc_ver_morosidad']) ? $contrato->fields['opc_ver_morosidad'] : $cobro_generar->fields['opc_ver_morosidad']);
		$cobro->Edit("opc_ver_resumen_cobro", empty($cobro_generar->fields['opc_ver_resumen_cobro']) ? $contrato->fields['opc_ver_resumen_cobro'] : $cobro_generar->fields['opc_ver_resumen_cobro']);
		$cobro->Edit("opc_ver_descuento", empty($cobro_generar->fields['opc_ver_descuento']) ? $contrato->fields['opc_ver_descuento'] : $cobro_generar->fields['opc_ver_descuento']);
		$cobro->Edit("opc_ver_tipo_cambio", empty($cobro_generar->fields['opc_ver_tipo_cambio']) ? $contrato->fields['opc_ver_tipo_cambio'] : $cobro_generar->fields['opc_ver_tipo_cambio']);
		$cobro->Edit("opc_ver_solicitante", empty($cobro_generar->fields['opc_ver_solicitante']) ? $contrato->fields['opc_ver_solicitante'] : $cobro_generar->fields['opc_ver_solicitante']);
		$cobro->Edit("opc_ver_numpag", empty($cobro_generar->fields['opc_ver_numpag']) ? $contrato->fields['opc_ver_numpag'] : $cobro_generar->fields['opc_ver_numpag']);
		$cobro->Edit("opc_ver_carta", empty($cobro_generar->fields['opc_ver_carta']) ? $contrato->fields['opc_ver_carta'] : $cobro_generar->fields['opc_ver_carta']);
		$cobro->Edit("opc_papel", empty($cobro_generar->fields['opc_papel']) ? $contrato->fields['opc_papel'] : $cobro_generar->fields['opc_papel']);
		$cobro->Edit("opc_restar_retainer", empty($cobro_generar->fields['opc_restar_retainer']) ? $contrato->fields['opc_restar_retainer'] : $cobro_generar->fields['opc_restar_retainer']);
		$cobro->Edit("opc_ver_detalle_retainer", empty($cobro_generar->fields['opc_ver_detalle_retainer']) ? $contrato->fields['opc_ver_detalle_retainer'] : $cobro_generar->fields['opc_ver_detalle_retainer']);
		$cobro->Edit("opc_ver_valor_hh_flat_fee", empty($cobro_generar->fields['opc_ver_valor_hh_flat_fee']) ? $contrato->fields['opc_ver_valor_hh_flat_fee'] : $cobro_generar->fields['opc_ver_valor_hh_flat_fee']);

		//Configuración moneda del cobro
		$moneda_cobro_configurada = empty($cobro_generar->fields['opc_moneda_total']) ? $contrato->fields['opc_moneda_total'] : $cobro_generar->fields['opc_moneda_total'];

		//Si incluye solo gastos, utilizar la moneda configurada para ello
		if ($incluye_gastos && !$incluye_honorarios) {
			$moneda_cobro_configurada = empty($cobro_generar->fields['opc_moneda_gastos']) ? $contrato->fields['opc_moneda_gastos'] : $cobro_generar->fields['opc_moneda_gastos'];
		}

		$cobro->Edit("opc_moneda_total", $moneda_cobro_configurada);

		$cobro->Edit("opc_ver_asuntos_separados", empty($cobro_generar->fields['opc_ver_asuntos_separados']) ? $contrato->fields['opc_ver_asuntos_separados'] : $cobro_generar->fields['opc_ver_asuntos_separados']);
		$cobro->Edit("opc_ver_horas_trabajadas", empty($cobro_generar->fields['opc_ver_horas_trabajadas']) ? $contrato->fields['opc_ver_horas_trabajadas'] : $cobro_generar->fields['opc_ver_horas_trabajadas']);
		$cobro->Edit("opc_ver_cobrable", empty($cobro_generar->fields['opc_ver_cobrable']) ? $contrato->fields['opc_ver_cobrable'] : $cobro_generar->fields['opc_ver_cobrable']);

		// Guardamos datos de la moneda base
		$cobro->Edit('id_moneda_base', $moneda_base['id_moneda']);
		$cobro->Edit('tipo_cambio_moneda_base', $moneda_base['tipo_cambio']);
		$cobro->Edit('etapa_cobro', '4');
		$cobro->Edit('codigo_idioma', empty($contrato->fields['codigo_idioma']) ? 'es' : $contrato->fields['codigo_idioma']);
		$cobro->Edit('id_proceso', $cobro->GeneraProceso());

		//Descuento
		$cobro->Edit("tipo_descuento", empty($cobro_generar->fields['tipo_descuento']) ? $contrato->fields['tipo_descuento'] : $cobro_generar->fields['tipo_descuento']);
		$cobro->Edit("descuento", empty($cobro_generar->fields['descuento']) ? $contrato->fields['descuento'] : $cobro_generar->fields['descuento']);
		$cobro->Edit("porcentaje_descuento", empty($cobro_generar->fields['porcentaje_descuento']) ? empty($contrato->fields['porcentaje_descuento']) ? '0' : $contrato->fields['porcentaje_descuento']  : $cobro_generar->fields['porcentaje_descuento']);
		$cobro->Edit("id_moneda_monto", empty($cobro_generar->fields['id_moneda_monto']) ? $contrato->fields['id_moneda_monto'] : $cobro_generar->fields['id_moneda_monto']);

		$fecha_ini = $cobro_generar->fields['fecha_ini'];
		if (!empty($fecha_ini) and $fecha_ini != '0000-00-00') {
			$cobro->Edit('fecha_ini', $fecha_ini);
		}

		$fecha_fin = $cobro_generar->fields['fecha_fin'];
		if (!empty($fecha_fin)) {
			$cobro->Edit('fecha_fin', $fecha_fin);
		}

		if ($solo_gastos) {
			$cobro->Edit('solo_gastos', 1);
		}

		$cobro->Edit("incluye_honorarios", $incluye_honorarios);
		$cobro->Edit("incluye_gastos", $incluye_gastos);

		$cobro->Edit("fecha_creacion", !empty($cobro_generar->fields['fecha_creacion']) ? $cobro_generar->fields['fecha_creacion'] : "NULL");
		$cobro->Edit("id_moneda", (!empty($cobro_generar->fields['id_moneda'])) ? $cobro_generar->fields['id_moneda'] : '1');
		$cobro->Edit("monto", (!empty($cobro_generar->fields['monto'])) ? $cobro_generar->fields['monto'] : "NULL");
		$cobro->Edit("impuesto", (!empty($cobro_generar->fields['impuesto'])) ? $cobro_generar->fields['impuesto'] : '0');
		$cobro->Edit("monto_subtotal", (!empty($cobro_generar->fields['monto_subtotal'])) ? $cobro_generar->fields['monto_subtotal'] : '0');
		$cobro->Edit("monto_contrato", (!empty($cobro_generar->fields['monto_contrato'])) ? $cobro_generar->fields['monto_contrato'] : '0');
		$cobro->Edit("documento", (!empty($cobro_generar->fields['documento'])) ? $cobro_generar->fields['documento'] : "");

		$cobro->Edit("subtotal_gastos", (!empty($cobro_generar->fields['subtotal_gastos'])) ? $cobro_generar->fields['subtotal_gastos'] : "");
		$cobro->Edit("monto_gastos", (!empty($cobro_generar->fields['monto_gastos'])) ? $cobro_generar->fields['monto_gastos'] : "");
		$cobro->Edit("impuesto_gastos", (!empty($cobro_generar->fields['impuesto_gastos'])) ? $cobro_generar->fields['impuesto_gastos'] : '0');
		$cobro->Edit("porcentaje_impuesto_gastos", (!empty($cobro_generar->fields['porcentaje_impuesto_gastos'])) ? $cobro_generar->fields['porcentaje_impuesto_gastos'] : '0');

		$cobro->Edit("porcentaje_impuesto", (!empty($cobro_generar->fields['porcentaje_impuesto'])) ? $cobro_generar->fields['porcentaje_impuesto'] : '0');
		$cobro->Edit('estado', 'EN REVISION');

		if (!empty($cobro_generar->fields['factura_razon_social']))
			$cobro->Edit('factura_razon_social', $cobro_generar->fields['factura_razon_social']);

		if (!empty($cobro_generar->fields['factura_rut']))
			$cobro->Edit('factura_rut', $cobro_generar->fields['factura_rut']);
		if (empty($forzar_insert))
			$forzar_insert = false;
		if (!$this->Write($cobro, $forzar_insert)) {
			if (empty($cobro->fields['id_cobro'])) {
				echo "Error al guardar el cobro\n";
			} else {
				echo "Error al guardar el cobro ID " . $cobro->fields['id_cobro'] . "\n";
			}
			return false;
		}

		echo "Cobro " . $cobro->fields['id_cobro'] . " generado\n";

		if ($cobro->Loaded()) {
			$this->EliminarCobroAsuntos($cobro->fields['id_cobro']);
			$this->AddCobroAsuntos($cobro->fields['id_cobro'], $contrato->fields['id_contrato']);
		}

		//Moneda cobro
		$cobro_moneda = new CobroMoneda($this->sesion);
		$this->IngresarCobroMoneda($cobro, $this->CobroMonedaStatement);

		//Gastos
		if (!empty($incluye_gastos)) {
			if ($solo_gastos == true) {
				$where = '(cta_corriente.egreso > 0 OR cta_corriente.ingreso > 0)';
			} else {
				$where = '1';
			}

			$query_gastos = "SELECT cta_corriente.*
				FROM cta_corriente
					LEFT JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto OR cta_corriente.codigo_asunto IS NULL
				WHERE $where
					AND (cta_corriente.id_cobro IS NULL)
					AND cta_corriente.incluir_en_cobro = 'SI'
					AND cta_corriente.cobrable = 1
					AND cta_corriente.codigo_cliente = '" . $cobro->fields['codigo_cliente'] . "'
					AND (asunto.id_contrato = '" . $cobro->fields['id_contrato'] . "')
					AND cta_corriente.fecha <= '$fecha_fin'";
			//$lista_gastos = new ListaGastos($this->sesion, null, $query_gastos);
			for ($v = 0; $v < 0; $v++) {
				$gasto = $lista_gastos->Get($v);
				$cta_gastos = new Objeto($this->sesion, null, null, 'cta_corriente', 'id_movimiento');
				if ($cta_gastos->Load($gasto->fields['id_movimiento'])) {
					$cta_gastos->Edit('id_cobro', $cobro->fields['id_cobro']);
					if (!$this->Write($cta_gastos)) {
						echo "Error al modificar el gasto " . $trabajo->fields['id_trabajo'] . "\n";
						continue;
					}
					echo "Gasto " . $cta_gastos->fields['id_movimiento'] . " asociado al cobro " . $cobro->fields['id_cobro'] . "\n";
				}
			}
		}

		//Trabajos
		if (!empty($incluye_honorarios) and !$solo_gastos) {
			echo "Asociando trabajos al cobro " . $cobro->fields['id_cobro'] . "\n";
			$where_up = '1';
			if (empty($fecha_ini) or $fecha_ini == '0000-00-00') {
				$where_up .= " AND fecha <= '$fecha_fin' ";
			} else {
				$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
			}

			$query = "SELECT *
				FROM trabajo
					JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
					JOIN contrato ON asunto.id_contrato = contrato.id_contrato
					LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
				WHERE " . $where_up . "
					AND contrato.id_contrato = '" . $cobro->fields['id_contrato'] . "'
					AND cobro.estado IS NULL
					AND trabajo.id_cobro IS NULL";

			//$lista_trabajos = new ListaTrabajos($this->sesion, null, $query);
			//echo $lista_trabajos->num . " trabajos encontrados\n";
			for ($x = 0; $x < 0; $x++) {
				$trabajo = $lista_trabajos->Get($x);
				$emitir_trabajo = new Objeto($this->sesion, null, null, 'trabajo', 'id_trabajo');
				$emitir_trabajo->Load($trabajo->fields['id_trabajo']);
				$emitir_trabajo->Edit('id_cobro', $cobro->fields['id_cobro']);
				if (!$this->Write($emitir_trabajo)) {
					echo "Error al modificar trabajo " . $trabajo->fields['id_trabajo'] . "\n";
					continue;
				}
				echo "Trabajo " . $emitir_trabajo->fields['id_trabajo'] . " asociado al cobro " . $cobro->fields['id_cobro'] . "\n";
			}

			$emitir_tramite = new Objeto($this->sesion, null, null, 'tramite', 'id_tramite');
			$where_up = '1';
			if ($fecha_ini == '' || $fecha_ini == '0000-00-00') {
				$where_up .= " AND fecha <= '$fecha_fin' ";
			} else {
				$where_up .= " AND fecha BETWEEN '$fecha_ini' AND '$fecha_fin'";
			}
			$query_tramites = "SELECT *
				FROM tramite
					JOIN asunto ON tramite.codigo_asunto = asunto.codigo_asunto
					JOIN contrato ON asunto.id_contrato = contrato.id_contrato
					LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
				WHERE $where_up
					AND contrato.id_contrato = '" . $cobro->fields['id_contrato'] . "'
					AND cobro.estado IS NULL";
			//$lista_tramites = new ListaTrabajos($this->sesion, null, $query_tramites);
			for ($y = 0; $y < 0; $y++) {
				$tramite = $lista_tramites->Get($y);
				$emitir_tramite->Load($tramite->fields['id_tramite']);
				$emitir_tramite->Edit('id_cobro', $cobro->fields['id_cobro']);
				$this->Write($emitir_tramite);
			}
		}

		//Se ingresa la anotación en el historial
		$observacion = new Observacion($this->sesion);
		$observacion->Edit('fecha', date('Y-m-d H:i:s'));
		$observacion->Edit('comentario', __('COBRO CREADO'));
		$observacion->Edit('id_usuario', $cobro->fields['id_usuario']);
		$observacion->Edit('id_cobro', $cobro->fields['id_cobro']);
		$this->Write($observacion);

		return $cobro->fields['id_cobro'];
	}

	/**
	 *
	 * @param type $cobro int
	 * @param type $preparainsercion statement
	 */
	public function IngresarCobroMoneda($cobro, $preparainsercion = null) {
		$query_monedas = "SELECT id_moneda, tipo_cambio FROM prm_moneda";
		$resp = mysql_query($query_monedas, $this->sesion->dbh) or Utiles::errorSQL($query_monedas, __FILE__, __LINE__, $this->sesion->dbh);
		if ($preparainsercion == null)
			$preparainsercion = $this->sesion->pdodbh->prepare("replace into cobro_moneda (id_cobro, id_moneda, tipo_cambio) values (:idcobro, :idmoneda, :tipocambio)");

		while (list($id_moneda, $tipo_cambio_actual) = mysql_fetch_array($resp)) {
			$query_revision = "SELECT valor FROM moneda_historial WHERE id_moneda = '$id_moneda' AND fecha < '" . $cobro->fields['fecha_creacion'] . "' ORDER BY fecha DESC LIMIT 1";
			$resp_revision = mysql_query($query_revision, $this->sesion->dbh) or Utiles::errorSQL($query_revision, __FILE__, __LINE__, $this->sesion->dbh);
			list($tipo_cambio) = mysql_fetch_array($resp_revision);

			if (empty($tipo_cambio)) {
				$query_revision2 = "SELECT valor FROM moneda_historial WHERE id_moneda = '$id_moneda' ORDER BY fecha ASC LIMIT 1";
				$resp2 = mysql_query($query_revision2, $this->sesion->dbh) or Utiles::errorSQL($query_revision2, __FILE__, __LINE__, $this->sesion->dbh);
				list($tipo_cambio) = mysql_fetch_array($resp2);
			}

			if (empty($tipo_cambio))
				$tipo_cambio = $tipo_cambio_actual;

			$query_rev = "SELECT count(*) FROM cobro_moneda WHERE id_moneda = '$id_moneda' AND id_cobro = '" . $cobro->fields['id_cobro'] . "' ";
			$resp_rev = mysql_query($query_rev, $this->sesion->dbh) or Utiles::errorSQL($query_rev, __FILE__, __LINE__, $this->sesion->dbh);
			list($cantidad) = mysql_fetch_array($resp_rev);

			try {
				$preparainsercion->execute(array(':idcobro'=>$cobro->fields['id_cobro'],':idmoneda'=>$id_moneda,':tipocambio'=>$tipo_cambio ));
			 } catch (PDOException $e) {
				 	if($this->sesion->usuario->TienePermiso('SADM')) {
							$Slim=Slim::getInstance('default',true);
							$arrayPDOException=array('File'=>$e->getFile(),'Line'=>$e->getLine(),'Mensaje'=>$e->getMessage(),'Query'=>$queryinsercion,'Trace'=>json_encode($e->getTrace()),'Parametros'=>json_encode($preparainsercion) );
							$Slim->view()->setData($arrayPDOException);
							 $Slim->applyHook('hook_error_sql');
						 }
				 debug($e->getTraceAsString());
					Utiles::errorSQL($queryinsercion, "", "",  NULL,"",$e );
					echo 'Error!';
			}
		}
	}

	public function ObtenerIDsCobroDeTrabajos() {
		$ids_cobro = array();
		$query = "SELECT id_cobro FROM trabajo WHERE id_cobro IS NOT NULL AND id_cobro NOT IN (SELECT id_cobro FROM cobro) GROUP BY id_cobro ORDER BY id_cobro";
		$res_ids_cobro = mysql_query($query, $this->sesion->dbh);
		while (list($id_cobro) = mysql_fetch_array($res_ids_cobro)) {
			$ids_cobro[] = $id_cobro;
		}
		return $ids_cobro;
	}

	public function ObtenerTrabajosConIDCobro($id_cobro) {
		$trabajos = array();

		if (empty($id_cobro)) {
			return $trabajos;
		}

		$query = "SELECT * FROM trabajo WHERE id_cobro = " . $id_cobro;
		$lista_trabajos = new ListaTrabajos($this->sesion, null, $query);
		for ($x = 0; $x < $lista_trabajos->num; $x++) {
			$trabajos[] = $lista_trabajos->Get($x);
		}

		return $trabajos;
	}

	public function PertenecenAlContrato($trabajos) {
		$trabajo = $trabajos[0];
		$asunto = new Asunto($this->sesion);
		if (empty($trabajo->fields['codigo_asunto'])) {
			echo "El trabajo " . $trabajo->fields['id_trabajo'] . " no contiene codigo asunto\n";
			return array("error" => true, "contrato" => null);
		}
		$asunto->LoadByCodigo($trabajo->fields['codigo_asunto']);
		if (!$asunto->Loaded()) {
			echo "Para el trabajo " . $trabajo->fields['id_trabajo'] . " no exite el asunto con codigo " . $trabajo->fields['codigo_asunto'] . "\n";
			return array("error" => true, "contrato" => null);
		}
		return array("error" => false, "contrato" => $asunto->fields['id_contrato']);
	}

	public function ObtenerFechasInicioFin($id_cobro) {
		$query = "SELECT MAX(fecha), MIN(fecha) FROM trabajo WHERE id_cobro = " . $id_cobro;
		list($fecha_ini, $fecha_fin) = mysql_fetch_array(mysql_query($query, $this->sesion->dbh));
		return array("inicio" => $fecha_ini, "fin" => $fecha_fin);
	}

	function GenerarCobrosBase() {
		//Buscar todos los trabajos que tengan ID cobro y que el ID cobro no exista en la tabla cobro
		$lista_ids_cobros = $this->ObtenerIDsCobroDeTrabajos();
		foreach ($lista_ids_cobros as $id_cobro) {
			$trabajos = $this->ObtenerTrabajosConIDCobro($id_cobro);
			//Comprueba que todos los trabajos pertenescan al mismo contrato
			$mismo_contrato = $this->PertenecenAlContrato($trabajos);
			if ($mismo_contrato['error'])
				continue;
			$id_contrato = $mismo_contrato['contrato'];
			$fechas = $this->ObtenerFechasInicioFin($id_cobro);
			$this->GenerarCobroBase($id_contrato, $fechas['inicio'], $fechas['fin'], $usuario_generador = 1, $cobro = null, $incluye_gastos = false, $incluye_honorarios = false);
		}
	}

	public function GenerarCobros($datos = array()) {
		//Genera los cobros con los trabajos que tienen ID cobro
		$this->GenerarCobrosBase();

		if (!is_array($datos)) {
			echo "Parametros incorrectos\n";
			return false;
		}

		foreach ($datos as $indice => $dato) {
			if (empty($dato['id_contrato']) or empty($dato['fecha_ini']) or empty($dato['fecha_fin'])) {//empty($dato['cobro']) or //
				echo "Si desea generar cobros entre fechas debe ingresar el contrato y las fechas, para la fila " . $indice . "\n";
				continue;
			}
			$this->GenerarCobroBase($dato['id_contrato'], $dato['fecha_ini'], $dato['fecha_fin'], $usuario_generador = 1);
		}
	}

	public function GenerarPago($pago) {
		$ingreso = new Gasto($this->sesion);
		$ingreso->Load($pago->fields['id_movimiento']);

		$ingreso->Edit('fecha', $pago->fields['fecha_pago'] ? Utiles::fecha2sql($pago->fields['fecha_pago']) : "NULL");
		$ingreso->Edit("id_usuario", !empty($pago->fields['id_usuario']) ? $pago->fields['id_usuario'] : "NULL" );
		$ingreso->Edit("descripcion", $pago->fields['descripcion']);
		$ingreso->Edit("id_moneda", $pago->fields['id_moneda'] ? $pago->fields['id_moneda'] : $id_moneda);
		$ingreso->Edit("codigo_cliente", $pago->fields['codigo_cliente'] ? $pago->fields['codigo_cliente'] : "NULL");
		$ingreso->Edit("codigo_asunto", $pago->fields['codigo_asunto'] ? $pago->fields['codigo_asunto'] : "NULL");
		$ingreso->Edit("id_usuario_orden", !empty($pago->fields['id_usuario_orden']) ? $pago->fields['id_usuario_orden'] : "NULL" );
		if (( method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaMontoCobrable')) or (method_exists('Conf', 'UsaMontoCobrable') && Conf::UsaMontoCobrable()) && $pago->fields['monto_cobrable'] > 0) {
			$ingreso->Edit('ingreso', $pago->fields['monto_pago'] ? $pago->fields['monto_pago'] : $monto_cobrable );
		} else {
			$ingreso->Edit('ingreso', $pago->fields['monto_pago'] ? $pago->fields['monto_pago'] : '0');
		}
		if ((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaMontoCobrable')) or (method_exists('Conf', 'UsaMontoCobrable') && Conf::UsaMontoCobrable())) {
			$ingreso->Edit('monto_cobrable', $pago->fields['monto_cobrable'] ? $pago->fields['monto_cobrable'] : $pago->fields['ingreso']);
		} else {
			$ingreso->Edit('monto_cobrable', $pago->fields['ingreso']);
		}
		$ingreso->Edit("documento_pago", $pago->fields['documento_pago'] ? $pago->fields['documento_pago'] : "NULL");
		if (!$this->Write($ingreso)) {
			return false;
		}
		return $ingreso->fields['id_movimiento'];
	}

	public function GenerarGasto($gasto_generar, $pagado = false) {
		$gasto = new Gasto($this->sesion);

		if (!empty($gasto_generar->fields['id_movimiento']) and (!preg_match("/^[[:digit:]]+$/", $gasto_generar->fields['id_movimiento']) or $gasto_generar->fields['id_movimiento'] == 0)) {
			$gasto_generar->Edit('id_movimiento', '999' . sprintf("%04d", (int) $gasto_generar->fields['id_movimiento']));
			echo "ID cambiado " . $gasto_generar->fields['id_movimiento'] . " al gasto\n";
		}

		$gasto->Load($gasto_generar->fields['id_movimiento']);

		if ($gasto->Loaded()) {
			echo "--Editando gasto ID " . $gasto->fields['id_movimiento'] . "\n";
		} else {
			if (empty($gasto_generar->fields['id_movimiento'])) {
				echo "--Generando gasto\n";
			} else {
				$gasto->Edit("id_movimiento", $gasto_generar->fields['id_movimiento']);
				echo "--Generando gasto ID " . $gasto_generar->fields['id_movimiento'] . "\n";
				$forzar_insert = true;
			}
		}

		if ($gasto_generar->fields["cobrable"] == 1) {
			$gasto->Edit("cobrable", "1");
		} else {
			if ((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarGastosCobrable')) or ( method_exists('Conf', 'UsarGastosCobrable') && Conf::UsarGastosCobrable())) {
				$gasto->Edit("cobrable", "0");
			} else {
				$gasto->Edit("cobrable", "1");
			}
		}

		if ($gasto_generar->fields['con_impuesto']) {
			$gasto->Edit("con_impuesto", $gasto_generar->fields['con_impuesto']);
		} else {
			if ((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsarGastosConSinImpuesto')) or ( method_exists('Conf', 'UsarGastosConSinImpuesto') && Conf::UsarGastosConSinImpuesto())) {
				$gasto->Edit("con_impuesto", "NO");
			} else {
				$gasto->Edit("con_impuesto", "SI");
			}
		}

		if (!empty($gasto_generar->fields['ingreso'])) {
			$monto = str_replace(',', '.', $gasto_generar->fields['ingreso']);
			$gasto->Edit("ingreso", $monto);
			$gasto->Edit("monto_cobrable", $monto);
		}

		$id_proveedor = $this->DeterminarProveedorGasto($gasto_generar->fields['proveedor_ruc'], $gasto->fields['proveedor_rsocial']);

		if (!empty($gasto_generar->fields['egreso'])) {
			$monto = str_replace(',', '.', $gasto_generar->fields['egreso']);
			$monto_cobrable = str_replace(',', '.', $gasto_generar->fields['monto_cobrable']);

			if ((method_exists('Conf', 'GetConf') && Conf::GetConf($this->sesion, 'UsaMontoCobrable')) or (method_exists('Conf', 'UsaMontoCobrable') && Conf::UsaMontoCobrable())) {
				if ($monto <= 0) {
					$gasto->Edit("egreso", $monto_cobrable);
				} else {
					$gasto->Edit("egreso", $monto);
				}

				if ($monto_cobrable >= 0) {
					$gasto->Edit("monto_cobrable", $monto_cobrable);
				} else {
					$gasto->Edit("monto_cobrable", $monto);
				}
			} else {
				$gasto->Edit("egreso", $monto);
				$gasto->Edit("monto_cobrable", $monto);
			}
		}

		$gasto->Edit("fecha", Utiles::fecha2sql($gasto_generar->fields['fecha']));
		$gasto->Edit("id_usuario", !empty($gasto_generar->fields['id_usuario']) ? $gasto_generar->fields['id_usuario'] : "NULL" );
		$gasto->Edit("descripcion", $gasto_generar->fields['descripcion']);
		$gasto->Edit("id_moneda", $gasto_generar->fields['id_moneda']);
		$gasto->Edit("codigo_cliente", $gasto_generar->fields['codigo_cliente'] ? $gasto_generar->fields['codigo_cliente'] : "NULL");
		$gasto->Edit("codigo_asunto", $gasto_generar->fields['codigo_asunto'] ? $gasto_generar->fields['codigo_asunto'] : "NULL");
		$gasto->Edit("id_usuario_orden", $gasto_generar->fields['id_usuario_orden'] ? $gasto_generar->fields['id_usuario_orden'] : "NULL");
		$gasto->Edit("id_cta_corriente_tipo", $gasto_generar->fields['id_cta_corriente_tipo'] ? $gasto_generar->fields['id_cta_corriente_tipo'] : "NULL");
		$gasto->Edit("numero_documento", $gasto_generar->fields['numero_documento'] ? $gasto_generar->fields['numero_documento'] : "NULL");
		$gasto->Edit("numero_ot", $gasto_generar->fields['numero_ot'] ? $gasto_generar->fields['numero_ot'] : "NULL");
		$gasto->Edit("id_cobro", $gasto_generar->fields['id_cobro'] ? $gasto_generar->fields['id_cobro'] : "NULL" );
		$gasto->Edit("fecha_factura", $gasto_generar->fields['fecha_factura'] ? $gasto_generar->fields['fecha_factura'] : "NULL" );
		$gasto->Edit("codigo_factura_gasto", $gasto_generar->fields['codigo_factura_gasto'] ? $gasto_generar->fields['codigo_factura_gasto'] : "NULL" );

		if ($pagado and !empty($gasto_generar->fields['egreso'])) {
			$this->GenerarPago($gasto);
		} else {
			$gasto->Edit('id_movimiento_pago', NULL);
		}

		$gasto->Edit('id_proveedor', $id_proveedor ? $id_proveedor : "NULL");

		if (!$this->ValidarGasto($gasto)) {
			echo "Error al generar el gasto\n";
			return false;
		}

		if (!$this->Write($gasto, $forzar_insert)) {
			echo "Error al generar el gasto\n";
			return false;
		}

		echo "Gasto " . $gasto->fields['id_movimiento'] . " guardado\n";
	}

	public function DeterminarProveedorGasto($ruc, $rsocial) {
		if (!empty($ruc) && !empty($rsocial)) {
			$query = "SELECT id_proveedor FROM prm_proveedor WHERE TRIM(rut) = TRIM('$rut') AND TRIM(glosa) = TRIM('$rsocial')";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_proveedor) = mysql_fetch_array($resp);
		} else if (!empty($ruc)) {
			$query = "SELECT id_proveedor FROM prm_proveedor WHERE TRIM(rut) = TRIM('$ruc') ORDER BY id_proveedor ASC LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_proveedor) = mysql_fetch_array($resp);
		} else if (!empty($rsocial)) {
			$query = "SELECT id_proveedor FROM prm_proveedor WHERE TRIM(glosa) = TRIM('$rsocial') ORDER BY id_proveedor ASC LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_proveedor) = mysql_fetch_array($resp);
		}
		return $id_proveedor;
	}

	public function ValidarGasto($gasto) {
		if (!$this->ValidarCodigo($gasto, "codigo_asunto", "asunto", true, "codigo_asunto", true)) {
			return false;
		}

		return $this->ValidarCodigo($gasto, "id_usuario", "usuario");
	}

	public function ValidarCobro($cobro) {
		if (!$this->ValidarCodigo($cobro, "id_contrato", "contrato", true, "id_contrato")) {
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "id_moneda", "prm_moneda", true, "id_moneda")) {
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "id_moneda_monto", "prm_moneda", true, "id_moneda")) {
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "opc_moneda_total", "prm_moneda", true, "id_moneda")) {
			return false;
		}

		if (!$this->ValidarCodigo($cobro, "codigo_cliente", "cliente", true, "codigo_cliente", true)) {
			return false;
		}

		return $this->ValidarCodigo($cobro, "id_usuario", "usuario");
	}

	public function ValdiarUsuario($usuario) {
		if (!$this->ValidarCodigo($usuario, "id_categoria_usuario", "prm_categoria_usuario")) {
			return false;
		}
		if (!$this->ValidarCodigo($usuario, "dir_comuna", "prm_comuna", false, "id_comuna")) {
			return false;
		}
		if (!$this->ValidarCodigo($usuario, "id_area_usuario", "prm_area_usuario", true, "id")) {
			return false;
		}
		return true;
	}

	public function ValidarCodigo($objeto, $campo_validar, $tabla, $obligatorio = false, $campo_id = null, $string = false) {
		$valido = true;

		$objeto_validar = new Objeto($this->sesion, null, null, $tabla, empty($campo_id) ? $campo_validar : $campo_id);

		if (empty($objeto->fields[$campo_validar]) and $obligatorio) {
			$msg .= "El(la) " . strtolower($campo_validar) . " no fue ingresado(a)";
			$valido = false;
		} else if (!empty($objeto->fields[$campo_validar]) and $objeto->fields[$campo_validar] != "NULL" and !$this->ExisteCodigo($objeto->fields[$campo_validar], $objeto_validar->tabla, $objeto_validar->campo_id, $string)) {
			$msg .= "No existe el(la) " . strtolower($campo_validar) . " ID o Codigo " . $objeto->fields[$campo_validar];
			$valido = false;
		}

		if (!empty($cobro->fields[$objeto->campo_id])) {
			$msg .= " para el " . $objeto > tabla . " " . $objeto->fields[$objeto->campo_id];
		}

		if (!$valido) {
			echo $msg . "\n";
			return false;
		}

		return true;
	}

	public function ExisteCodigo($codigo, $tabla, $campo, $string = false) {
		$sss = ($string) ? "'" . $codigo . "'" : $codigo;
		$query = "SELECT COUNT(*) FROM " . $tabla . " WHERE " . $campo . " = " . $sss;

		if ($campo == "id_area_usuario") {
			echo $query;
		}

		$ttt = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($existe) = mysql_fetch_array($ttt);
		if (empty($existe)) {
			return false;
		}
		return true;
	}

	public function AddCobroAsuntos($id_cobro, $id_contrato = 0) {
		#Genero la parte values a través de queries
		$query = "SELECT CONCAT('(', '$id_cobro',',\'',codigo_asunto,'\')') as value FROM asunto WHERE id_contrato = '" . $id_contrato . "'";
		$resp = mysql_query($query, $this->sesion->dbh);
		if (!$resp) {
			echo "Error explicacion: " . mysql_error($this->sesion->dbh) . "\n";
			echo "Query: " . $query . "\n";
			return false;
		}
		while (list($values) = mysql_fetch_array($resp)) {
			if ($values != "") {
				$query2 = "INSERT INTO cobro_asunto (id_cobro, codigo_asunto) values $values";
				$resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
		return true;
	}

	public function EliminarCobroAsuntos($id_cobro) {
		$query = "DELETE FROM cobro_asunto WHERE id_cobro = " . $id_cobro;
		$resp = mysql_query($query, $this->sesion->dbh);
		if (!$resp) {
			echo "Error explicacion: " . mysql_error($this->sesion->dbh) . "\n";
			echo "Query: " . $query . "\n";
			return false;
		}
		return true;
	}

	public function ValidarFactura($factura) {
		if (!$this->ValidarCodigo($factura, "id_documento_legal_motivo", "prm_documento_legal_motivo")) {
			return false;
		}

		if (!$this->ValidarCodigo($factura, "id_cobro", "cobro")) {
			return false;
		}

		if (!$this->ValidarCodigo($factura, "id_estado", "prm_estado_factura")) {
			return false;
		}

		return $this->ValidarCodigo($factura, "id_moneda", "prm_moneda");
	}

	public function GenerarFactura($factura_generar) {
		$factura = new Factura($this->sesion);
		$factura->Load($factura_generar->fields['id_factura']);
		if ($factura->Loaded()) {
			echo "--Editando factura ID " . $factura->fields['id_factura'] . "\n";
		} else {
			if (empty($factura_generar->fields['id_factura'])) {
				echo "--Generando factura\n";
			} else {
				echo "--Generando factura ID " . $factura_generar->fields['id_factura'] . "\n";
				$factura->Edit('id_factura', $factura_generar->fields['id_factura']);
				$forzar_insert = true;
			}
		}

		$mensaje_accion = 'guardado';
		if (!empty($factura_generar->fields['subtotal']))
			$factura->Edit('subtotal', $factura_generar->fields['subtotal']);
		if (!empty($factura_generar->fields['porcentaje_impuesto']))
			$factura->Edit('porcentaje_impuesto', empty($factura_generar->fields['porcentaje_impuesto']) ? "0" : $factura_generar->fields['porcentaje_impuesto'] );
		if (!empty($factura_generar->fields['iva']))
			$factura->Edit('iva', $factura_generar->fields['iva']);
		$factura->Edit('total', empty($factura_generar->fields['total']) ? ($factura_generar->fields['subtotal'] + $factura_generar->fields['iva']) : empty($factura_generar->fields['total']) );
		if (!empty($factura_generar->fields['id_factura_padre']))
			$factura->Edit("id_factura_padre", empty($factura_generar->fields['id_factura_padre']) ? "NULL" : $factura_generar->fields['id_factura_padre']);
		if (!empty($factura_generar->fields['fecha']))
			$factura->Edit("fecha", $factura_generar->fields['fecha']);
		if (!empty($factura_generar->fields['cliente']))
			$factura->Edit("cliente", empty($factura_generar->fields['cliente']) ? "NULL" : $factura_generar->fields['cliente']);
		if (!empty($factura_generar->fields['RUT_cliente']))
			$factura->Edit("RUT_cliente", empty($factura_generar->fields['RUT_cliente']) ? "NULL" : $factura_generar->fields['RUT_cliente']);
		if (!empty($factura_generar->fields['direccion_cliente']))
			$factura->Edit("direccion_cliente", empty($factura_generar->fields['direccion_cliente']) ? "NULL" : $factura_generar->fields['direccion_cliente']);
		if (!empty($factura_generar->fields['codigo_cliente']))
			$factura->Edit("codigo_cliente", empty($factura_generar->fields['codigo_cliente']) ? "NULL" : $factura_generar->fields['codigo_cliente']);
		$factura->Edit("id_cobro", empty($factura_generar->fields['id_cobro']) ? "NULL" : $factura_generar->fields['id_cobro']);
		if (!empty($factura_generar->fields['id_documento_legal']))
			$factura->Edit("id_documento_legal", empty($factura_generar->fields['id_documento_legal']) ? '1' : $factura_generar->fields['id_documento_legal']);
		if (!empty($factura_generar->fields['serie_documento_legal']))
			$factura->Edit("serie_documento_legal", empty($factura_generar->fields['serie_documento_legal']) ? Conf::GetConf($this->sesion, 'SerieDocumentosLegales') : $factura_generar->fields['serie_documento_legal']);
		if (!empty($factura_generar->fields['numero']))
			$factura->Edit("numero", empty($factura_generar->fields['numero']) ? '' : $factura_generar->fields['numero']);
		if (!empty($factura_generar->fields['id_estado']))
			$factura->Edit("id_estado", empty($factura_generar->fields['id_estado']) ? '1' : $factura_generar->fields['id_estado']);
		if (!empty($factura_generar->fields['id_moneda']))
			$factura->Edit("id_moneda", empty($factura_generar->fields['id_moneda']) ? $factura_generar->fields['id_moneda'] : '1');

		if ($factura_generar->fields['id_estado'] == '5') {
			$factura->Edit('estado', 'ANULADA');
			$factura->Edit('anulado', 1);
			$mensaje_accion = 'anulado';
		} else if (!empty($factura_generar->fields['anulado'])) {
			$factura->Edit('estado', 'ABIERTA');
			$factura->Edit('anulado', '0');
		}

		if (method_exists("Conf", "GetConf") && (Conf::GetConf($this->sesion, "DesgloseFactura") == "con_desglose")) {
			if (!empty($factura_generar->fields['descripcion']))
				$factura->Edit("descripcion", $factura_generar->fields['descripcion']);
			if (!empty($factura_generar->fields['honorarios']))
				$factura->Edit("honorarios", empty($factura_generar->fields['honorarios']) ? "0" : $factura_generar->fields['honorarios']);
			if (!empty($factura_generar->fields['subtotal_sin_descuento']))
				$factura->Edit("subtotal_sin_descuento", empty($factura_generar->fields['subtotal_sin_descuento']) ? "0" : $factura_generar->fields['subtotal_sin_descuento']);
			if (!empty($factura_generar->fields['descripcion_subtotal_gastos']))
				$factura->Edit("descripcion_subtotal_gastos", empty($factura_generar->fields['descripcion_subtotal_gastos']) ? "" : $factura_generar->fields['descripcion_subtotal_gastos']);
			if (!empty($factura_generar->fields['subtotal_gastos']))
				$factura->Edit("subtotal_gastos", empty($factura_generar->fields['subtotal_gastos']) ? "0" : $factura_generar->fields['subtotal_gastos']);
			if (!empty($factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']))
				$factura->Edit("descripcion_subtotal_gastos_sin_impuesto", empty($factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']) ? "" : $factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']);
			if (!empty($factura_generar->fields['subtotal_gastos_sin_impuesto']))
				$factura->Edit("subtotal_gastos_sin_impuesto", empty($factura_generar->fields['subtotal_gastos_sin_impuesto']) ? "0" : $factura_generar->fields['subtotal_gastos_sin_impuesto']);
			/* } else {
			  if( !empty($factura_generar->fields['descripcion']))	$factura->Edit("descripcion", $factura_generar->fields['descripcion']);
			  if( !empty($factura_generar->fields['honorarios']))	$factura->Edit("honorarios", empty($factura_generar->fields['honorarios']) ? "0" : $factura_generar->fields['honorarios']);
			  if( !empty($factura_generar->fields['subtotal_sin_descuento']))	$factura->Edit("subtotal_sin_descuento", empty($factura_generar->fields['subtotal_sin_descuento']) ? "0" : $factura_generar->fields['subtotal_sin_descuento']);
			  if( !empty($factura_generar->fields['descripcion_subtotal_gastos']))	$factura->Edit("descripcion_subtotal_gastos", empty($factura_generar->fields['descripcion_subtotal_gastos']) ? "" : $factura_generar->fields['descripcion_subtotal_gastos']);
			  if( !empty($factura_generar->fields['subtotal_gastos']))	$factura->Edit("subtotal_gastos", empty($factura_generar->fields['subtotal_gastos']) ? "0" : $factura_generar->fields['subtotal_gastos']);
			  if( !empty($factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']))	$factura->Edit("descripcion_subtotal_gastos_sin_impuesto", empty($factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']) ? "" : $factura_generar->fields['descripcion_subtotal_gastos_sin_impuesto']);
			  if( !empty($factura_generar->fields['subtotal_gastos_sin_impuesto']))	$factura->Edit("subtotal_gastos_sin_impuesto", empty($factura_generar->fields['subtotal_gastos_sin_impuesto']) ? "0" : $factura_generar->fields['subtotal_gastos_sin_impuesto']);
			  /*} else {
			  $factura->Edit("descripcion", $factura_generar->fields['descripcion']);
			  } */

			$factura->Edit("letra", $factura_generar->fields['letra']);

			$cobro = new Cobro($this->sesion);
			if ($cobro->Load($factura_generar->fields['id_cobro'])) {
				$factura->Edit('id_moneda', $cobro->fields['opc_moneda_total']);
			}

			if (!$factura->Loaded()) {
				$generar_nuevo_numero = true;
			}

			if (!$this->ValidarFactura($factura)) {
				echo "Error al ingresar la factura: no la pude validar<br>\n";
				$this->errorcount++;
				return false;
			} else {

				$query = "SELECT id_documento_legal, glosa, codigo FROM prm_documento_legal WHERE id_documento_legal = '$id_documento_legal'";
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($tipo_documento_legal, $codigo_tipo_doc) = mysql_fetch_array($resp);

				if ($this->Escribir($factura, $forzar_insert)) {
					if ($generar_nuevo_numero) {
						$factura->GuardarNumeroDocLegal($factura->fields['id_documento_legal'], $factura->fields['numero']);
					}

					$signo = ($codigo_tipo_doc == 'NC') ? 1 : -1; //es 1 o -1 si el tipo de doc suma o resta su monto a la liq
					$neteos = empty($factura->fields['id_factura_padre']) ? null : array(array($factura->fields['id_factura_padre'], $signo * $factura->fields['total']));

					$cta_cte_fact = new CtaCteFact($this->sesion);
					$cta_cte_fact->RegistrarMvto($factura->fields['id_moneda'], $signo * ($factura->fields['total'] - $factura->fields['iva']), $signo * $factura->fields['iva'], $signo * $factura->fields['total'], $factura->fields['fecha'], $neteos, $factura->fields['id_factura'], null, $codigo_tipo_doc, $ids_monedas_documento, $tipo_cambios_documento, !empty($factura->fields['anulado']));

					echo __('Documento Tributario') . " " . $mensaje_accion . "\n";

					if ($factura->fields['id_cobro']) {
						$documento = new Documento($this->sesion);
						$documento->LoadByCobro($factura->fields['id_cobro']);

						$valores = array(
							$factura->fields['id_factura'],
							$factura->fields['id_cobro'],
							$documento->fields['id_documento'],
							$factura->fields['subtotal_sin_descuento'] + $factura->fields['subtotal_gastos'] + $factura->fields['subtotal_gastos_sin_impuesto'],
							$factura->fields['iva'],
							$documento->fields['id_moneda'],
							$documento->fields['id_moneda']
						);

						$query = "DELETE FROM factura_cobro WHERE id_factura = '" . $factura->fields['id_factura'] . "' AND id_cobro = '" . $factura->fields['id_cobro'] . "'";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

						$query = "INSERT INTO factura_cobro (id_factura, id_cobro, id_documento, monto_factura, impuesto_factura, id_moneda_factura, id_moneda_documento) VALUES ('" . implode("','", $valores) . "')";
						$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					}
				} else {
					echo "Error al guardar la factura: no la pude guardar<br>";
					$this->errorcount++;
					return false;
				}

				echo "Factura ID " . $factura->fields['id_factura'] . " guardada";
				$this->filasprocesadas++;

				$observacion = new Observacion($this->sesion);
				$observacion->Edit('fecha', date('Y-m-d H:i:s'));
				$observacion->Edit('comentario', "MODIFICACIÓN FACTURA");
				$observacion->Edit('id_usuario', "NULL");
				$observacion->Edit('id_factura', $factura->fields['id_factura']);
				$observacion->Write();
			}
		}
	}

	public function GenerarPagos($factura_pago_generar = null, $documento_pago_generar = null) {
		$query = "SELECT numero FROM factura WHERE id_factura = '" . $factura_pago_generar->fields['id_factura'] . "' ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($numero_factura) = mysql_fetch_array($resp);
		//echo '<pre>'; print_r($documento_pago_generar->fields); echo '</pre>';exit;
		if (!empty($numero_factura)) {
			$factura_asoc = new Factura($this->sesion);
			$cta_cte_fact = new CtaCteFact($this->sesion);

			$moneda_factura = new Moneda($this->sesion);
			$moneda_cobro = new Moneda($this->sesion);

			$moneda_factura->Load($factura_pago_generar->fields['id_moneda']);

			if ($factura_pago_generar->fields['id_moneda_cobro'])
				$moneda_cobro->Load($factura_pago_generar->fields['id_moneda_cobro']);
			else
				$moneda_cobro->Load($factura_pago_generar->fields['id_moneda']);

			$factura_asoc->Load($factura_pago_generar->fields['id_factura']);

			$factura_pago = new FacturaPago($this->sesion);
			if ($factura_pago_generar->fields['id_factura_pago'] > 0) {
				if ($factura_pago->Load($factura_pago_generar->fields['id_factura_pago'])) {
					echo "Editando Factura Pago No. " . $factura_pago_generar->fields['id_factura_pago'] . "\n";
					$forzar_insert = false;
				} else {
					echo "Ingresando Factura Pago No. " . $factura_pago_generar->fields['id_factura_pago'] . "\n";
					$forzar_insert = true;
				}
				$factura_pago->Edit('id_factura_pago', $factura_pago_generar->fields['id_factura_pago']);
			}

			$factura_pago->Edit('fecha', !empty($factura_pago_generar->fields['fecha']) ? $factura_pago_generar->fields['fecha'] : "000-00-00 00:00:00" );
			$factura_pago->Edit('codigo_cliente', !empty($factura_asoc->fields['codigo_cliente']) ? $factura_asoc->fields['codigo_cliente'] : "NULL" );
			$factura_pago->Edit('monto', !empty($factura_pago_generar->fields['monto']) ? number_format($factura_pago_generar->fields['monto'], $moneda_factura->fields['cifras_decimales'], '.', '') : "0" );
			$factura_pago->Edit('id_moneda', !empty($factura_pago_generar->fields['id_moneda']) ? $factura_pago_generar->fields['id_moneda'] : "1" );
			$factura_pago->Edit('monto_moneda_cobro', !empty($factura_pago_generar->fields['monto_cobro']) ? number_format($factura_pago_generar->fields['monto_cobro'], $moneda_cobro->fields['cifras_decimales'], '.', '') : "0" );
			$factura_pago->Edit('id_moneda_cobro', !empty($factura_pago_generar->fields['id_moneda_cobro']) ? $factura_pago_generar->fields['id_moneda_cobro'] : "1" );
			$factura_pago->Edit('tipo_doc', !empty($factura_pago_generar->fields['tipo_doc']) ? $factura_pago_generar->fields['tipo_doc'] : "" );
			$factura_pago->Edit('nro_documento', !empty($factura_pago_generar->fields['numero_doc']) ? $factura_pago_generar->fields['numero_doc'] : "" );
			$factura_pago->Edit('nro_cheque', !empty($factura_pago_generar->fields['numero_cheque']) ? $factura_pago_generar->fields['numero_cheque'] : "" );
			$factura_pago->Edit('descripcion', !empty($factura_pago_generar->fields['glosa_documento']) ? $factura_pago_generar->fields['glosa_documento'] : "" );
			$factura_pago->Edit('id_banco', !empty($factura_pago_generar->fields['id_banco']) ? $factura_pago_generar->fields['id_banco'] : "0" );
			$factura_pago->Edit('id_cuenta', !empty($factura_pago_generar->fields['id_cuenta']) ? $factura_pago_generar->fields['id_cuenta'] : "0" );
			$factura_pago->Edit('pago_retencion', !empty($factura_pago_generar->fields['pago_retencion']) ? $factura_pago_generar->fields['pago_retencion'] : "0" );
			$factura_pago->Edit('id_concepto', !empty($factura_pago_generar->fields['id_concepto']) ? $factura_pago_generar->fields['id_concepto'] : "0" );
			//echo '<pre>';print_r($factura_pago->fields); echo '</pre>';
			$neteos = array();
			$neteos[] = array($factura_pago_generar->fields['id_factura'], number_format($factura_pago->fields['monto_moneda_cobro'], $moneda_factura->fields['cifras_decimales'], '.', ''), number_format($factura_pago->fields['monto'], $moneda_factura->fields['cifras_decimales'], '.', ''));

			$id_cobro = $factura_asoc->fields['id_cobro'];
			// Buscar tipos de cambios para los pagos:
			$query = " SELECT GROUP_CONCAT( id_moneda ), GROUP_CONCAT( tipo_cambio )
									FROM cobro_moneda WHERE id_cobro = '$id_cobro' GROUP BY id_cobro ";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($ids_monedas_factura_pago, $tipo_cambios_factura_pago) = mysql_fetch_array($resp);

			if ($this->Write($factura_pago, $forzar_insert)) {
				if ($this->IngresarPago($cta_cte_fact, $factura_pago, $neteos, $factura_asoc->fields['id_cobro'], &$pagina, $ids_monedas_factura_pago, $tipo_cambios_factura_pago, $documento_pago_generar->fields['id_documento']))
					echo "Factura_pago y Movimiento guardado con exito \n";
				else
					echo "Error al guardar el movimiento de factura pago " . $factura_pago_generar->fields['id_factura_pago'] . "\n";
			}
			else
				echo "Error al guardar la el factura pago " . $factura_pago_generar->fields['id_factura_pago'] . "\n";
		}
		else {
			$cobro_asoc = new Cobro($this->sesion);
			$cobro_asoc->Load($documento_pago_generar->fields['id_cobro']);

			$documento = new Documento($this->sesion);
			if (!empty($documento_pago_generar->fields['id_documento'])) {
				if ($documento->Load($documento_pago_generar->fields['id_documento'])) {
					echo "Editando documento pago No " . $documento_pago_generar->fields['id_documento'] . "\n";
					$forzar_insert = false;
				} else {
					echo "Ingresando documento pago No " . $documento_pago_generar->fields['id_documento'] . "\n";
					$forzar_insert = true;
				}
				$documento->Edit('id_documento', $documento_pago_generar->fields['id_documento']);
			}

			$documento_cobro = new Documento($this->sesion);
			$documento_cobro->LoadByCobro($documento_pago_generar->fields['id_cobro']);

			$moneda_documento = new Moneda($this->sesion);
			$moneda_documento->Load($documento_pago_generar->fields['id_moneda']);

			$moneda_cobro = new Moneda($this->sesion);
			$moneda_cobro->Load($documento_pago_generar->fields['id_moneda_monto']);

			$fecha = !empty($documento_pago_generar->fields['fecha']) ? $documento_pago_generar->fields['fecha'] : "0000-00-00";
			$codigo_cliente = !empty($cobro_asoc->fields['codigo_cliente']) ? $cobro_asoc->fields['codigo_cliente'] : "0001";
			$id_cobro = !empty($documento_pago_generar->fields['id_cobro']) ? $documento_pago_generar->fields['id_cobro'] : "NULL";
			$monto = number_format($documento_pago_generar->fields['monto'], $moneda_documento->fields['cifras_decimales'], '.', '');
			$id_moneda = !empty($documento_pago_generar->fields['id_moneda']) ? $documento_pago_generar->fields['id_moneda'] : "1";
			$monto_moneda_cobro = !empty($documento_pago_generar->fields['monto_moneda_cobro']) ? number_format($documento_pago_generar->fields['monto_moneda_cobro'], $moneda_cobro->fields['cifras_decimales'], '.', '') : number_format($documento_pago_generar->fields['monto'], $moneda_documento->fields['cifras_decimales'], '.', '');
			$id_moneda_cobro = !empty($documento_pago_generar->fields['id_moneda_cobro']) ? $documento_pago_generar->fields['id_moneda_cobro'] : $documento_pago_generar->fields['id_moneda'];
			$tipo_doc = !empty($documento_pago_generar->fields['tipo_doc']) ? $documento_pago_generar->fields['tipo_doc'] : "T";
			$numero_doc = !empty($documento_pago_generar->fields['numero_doc']) ? $documento_pago_generar->fields['numero_doc'] : "0";
			$numero_cheque = !empty($documento_pago_generar->fields['numero_cheque']) ? $documento_pago_generar->fields['numero_cheque'] : "";
			$glosa_documento = !empty($documento_pago_generar->fields['glosa_documento']) ? $documento_pago_generar->fields['glosa_documento'] : "";
			$id_banco = !empty($documento_pago_generar->fields['id_banco']) ? $documento_pago_generar->fields['id_banco'] : "0";
			$id_cuenta = !empty($documento_pago_generar->fields['id_cuenta']) ? $documento_pago_generar->fields['id_cuenta'] : "0";
			$numero_operacion = !empty($documento_pago_generar->fields['numero_operacion']) ? $documento_pago_generar->fields['numero_operacion'] : "";

			$arreglo_pagos_detalle = array();
			$arreglo_data = array();
			$arreglo_data['id_moneda'] = $id_moneda;
			$arreglo_data['id_documento_cobro'] = $documento_cobro->fields['id_documento'];
			$arreglo_data['monto_honorarios'] = $monto;
			$arreglo_data['monto_gastos'] = "0";
			$arreglo_data['id_cobro'] = $id_cobro;
			array_push($arreglo_pagos_detalle, $arreglo_data);

			// Buscar tipos de cambios para los pagos:
			$query = " SELECT GROUP_CONCAT( id_moneda ), GROUP_CONCAT( tipo_cambio )
									FROM cobro_moneda WHERE id_cobro = '$id_cobro' GROUP BY id_cobro ";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($ids_monedas_documento, $tipo_cambios_documento) = mysql_fetch_array($resp);

			if ($this->IngresoDocumentoPago($forzar_insert, $documento, $id_cobro, $codigo_cliente, $monto_moneda_cobro, $id_moneda_cobro, $tipo_doc, $numero_doc, $fecha, $glosa_documento, $id_banco, $id_cuenta, $numero_operacion, $numero_cheque, $ids_monedas_documento, $tipo_cambios_documento, $arreglo_pagos_detalle, $id_pago))
				echo "Documento Pago guardado con exito \n";
			else
				echo "Error al guardar el documento pago No " . $documento_pago_generar->fields['id_documento'] . "\n";
		}
	}

	function IngresarPago($mvto_pago, $pago, $neteos, $id_cobro, &$pagina, $ids_monedas_documento = '', $tipo_cambios_documento = '', $id_documento_generar) {

		$fecha = $pago->fields['fecha'];
		$codigo_cliente = $pago->fields['codigo_cliente'];
		$monto = $pago->fields['monto'];
		$id_moneda = $pago->fields['id_moneda'];
		$monto_moneda_cobro = $pago->fields['monto_moneda_cobro'];
		$id_moneda_cobro = $pago->fields['id_moneda_cobro'];
		$tipo_doc = $pago->fields['tipo_doc'];
		$numero_doc = $pago->fields['nro_documento'];
		$numero_cheque = $pago->fields['nro_cheque'];
		$glosa_documento = $pago->fields['descripcion'];
		$id_banco = $pago->fields['id_banco'];
		$id_cuenta = $pago->fields['id_cuenta'];
		$id_pago = $pago->Id();

		$numero_operacion = ''; //?????
		//echo '<pre>RegistrarMvto: ';
		//print_r(array($id_moneda, $monto, '0', $monto, $fecha, $neteos, null, $id_pago, 'P'));
		//si el pago no es en la moneda del cobro, guardo tb el equivalente de cada neteo en la moneda del pago
		if ($monto != $monto_moneda_cobro) {
			foreach ($neteos as $k => $neteo) {
				$neteos[$k][2] = $neteo[1] * $monto / $monto_moneda_cobro;
			}
		}

		//ingresar un pago a los movimientos de ctacte (con sus neteos)
		$mvto = $mvto_pago->RegistrarMvto($id_moneda, $monto, '0', $monto, $fecha, $neteos, null, $id_pago, 'P', $ids_monedas_documento, $tipo_cambios_documento);

		$arreglo_monedas = ArregloMonedas($this->sesion);

		$arreglo_pagos_detalle = array();
		foreach ($neteos as $neteo) {
			$id_fac = $neteo[0];
			$monto_neteo = $neteo[1];
			if (empty($id_fac) || empty($monto_neteo))
				continue;

			$fac = new Factura($this->sesion);
			$fac->Load($id_fac);

			$fac_hon = $fac->fields['subtotal'];
			$fac_gasto_con = $fac->fields['subtotal_gastos'];
			$fac_gasto_sin = $fac->fields['subtotal_gastos_sin_impuesto'];
			$fac_iva = $fac->fields['iva'];
			$fac_total = $fac->fields['total'];
			$fac_cobro = $fac->fields['id_cobro'];

			if ($fac_gasto_con + $fac_hon != 0)
				$monto_honorarios = $fac_hon + $fac_iva * $fac_hon / ($fac_gasto_con + $fac_hon);
			else
				$monto_honorarios = 0;
			$monto_gastos = $fac_total - $monto_honorarios;

			if (!isset($arreglo_pagos_detalle[$fac_cobro])) {
				$cobro_moneda = new CobroMoneda($this->sesion);
				$cobro_moneda->Load($fac_cobro);

				$arreglo_pagos_detalle[$fac_cobro] = array(
					'id_cobro' => $fac_cobro,
					'monto_honorarios' => 0,
					'monto_gastos' => 0,
					'id_moneda' => $fac->fields['id_moneda']
				);
			}

			$monto_honorarios *= $monto_neteo / $fac_total;
			$monto_gastos *= $monto_neteo / $fac_total;

			$arreglo_pagos_detalle[$fac_cobro]['monto_honorarios'] += $monto_honorarios;
			$arreglo_pagos_detalle[$fac_cobro]['monto_gastos'] += $monto_gastos;
		}

		$documento = new Documento($this->sesion);
		$id_documento = $mvto->GetIdDocumentoLiquidacionSoyMvto();
		if ($id_documento) {
			$documento->Load($id_documento);
			$forzar_insert = false;
		} else {
			$forzar_insert = true;
			$documento->Edit('id_documento', $id_documento_generar);
		}
		$this->IngresoDocumentoPago($forzar_insert, $documento, $id_cobro, $codigo_cliente, $monto_moneda_cobro, $id_moneda_cobro, $tipo_doc, $numero_doc, $fecha, $glosa_documento, $id_banco, $id_cuenta, $numero_operacion, $numero_cheque, $ids_monedas_documento, $tipo_cambios_documento, $arreglo_pagos_detalle, $id_pago);

		return true;
	}

	function IngresoDocumentoPago($forzar_insert, $documento_generado, $id_cobro, $codigo_cliente, $monto, $id_moneda, $tipo_doc, $numero_doc = "", $fecha, $glosa_documento = "", $id_banco = "", $id_cuenta = "", $numero_operacion = "", $numero_cheque = "", $ids_monedas_documento, $tipo_cambios_documento, $arreglo_pagos_detalle = array(), $id_factura_pago = null) {
		if ($id_cobro) {
			$query = "UPDATE cobro SET fecha_cobro='" . Utiles::fecha2sql($fecha) . " 00:00:00' WHERE id_cobro=" . $id_cobro;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}

		$monto = str_replace(',', '.', $monto);

		/* Es pago, asi que monto es negativo */
		$multiplicador = -1.0;
		$moneda = new Moneda($this->sesion);
		$moneda->Load($id_moneda);
		$moneda_base = Utiles::MonedaBase($this->sesion);
		$monto_base = $monto * $moneda->fields['tipo_cambio'] / $moneda_base['tipo_cambio'];

		$documento_generado->Edit("monto", number_format($monto * $multiplicador, $moneda->fields['cifras_decimales'], ".", ""));
		$documento_generado->Edit("monto_base", number_format($monto_base * $multiplicador, $moneda_base['cifras_decimales'], ".", ""));
		$documento_generado->Edit("saldo_pago", number_format($monto * $multiplicador, $moneda->fields['cifras_decimales'], ".", ""));
		if ($id_cobro)
			$documento_generado->Edit("id_cobro", $id_cobro);
		$documento_generado->Edit('tipo_doc', $tipo_doc);
		$documento_generado->Edit("numero_doc", $numero_doc);
		$documento_generado->Edit("id_moneda", $id_moneda);
		$documento_generado->Edit("fecha", Utiles::fecha2sql($fecha));
		$documento_generado->Edit("glosa_documento", $glosa_documento);
		$documento_generado->Edit("codigo_cliente", $codigo_cliente);
		$documento_generado->Edit("id_banco", $id_banco);
		$documento_generado->Edit("id_cuenta", $id_cuenta);
		$documento_generado->Edit("numero_operacion", $numero_operacion);
		$documento_generado->Edit("numero_cheque", $numero_cheque);
		$documento_generado->Edit("id_factura_pago", $id_factura_pago ? $id_factura_pago : "NULL" );
		if ($pago_retencion)
			$documento_generado->Edit("pago_retencion", "1");

		$out_neteos = "";

		if ($this->Write($documento_generado, $forzar_insert)) {
			$id_documento = $documento_generado->fields['id_documento'];
			$ids_monedas = explode(',', $ids_monedas_documento);
			$tipo_cambios = explode(',', $tipo_cambios_documento);
			$tipo_cambio = array();
			foreach ($tipo_cambios as $key => $tc) {
				$tipo_cambio[$ids_monedas[$key]] = $tc;
			}
			$documento_generado->ActualizarDocumentoMoneda($tipo_cambio);

			//Si se ingresa el documento, se ingresan los pagos
			foreach ($arreglo_pagos_detalle as $key => $data) {
				$moneda_documento_cobro = new Moneda($this->sesion);
				$moneda_documento_cobro->Load($data['id_moneda']);

				// Guardo los saldos, para indicar cuales fueron actualizados
				$id_cobro_neteado = $data['id_cobro'];
				$documento_cobro_aux = new Documento($this->sesion);
				if ($documento_cobro_aux->LoadByCobro($id_cobro_neteado)) {
					$saldo_honorarios_anterior = $documento_cobro_aux->fields['saldo_honorarios'];
					$saldo_gastos_anterior = $documento_cobro_aux->fields['saldo_gastos'];
				}

				$id_documento_cobro = $documento_cobro_aux->fields['id_documento'];
				$pago_honorarios = $data['monto_honorarios'];
				$pago_gastos = $data['monto_gastos'];
				$cambio_cobro = $documento_generado->TipoCambioDocumento($this->sesion, $id_documento_cobro, $documento_cobro_aux->fields['id_moneda']);
				$cambio_pago = $documento_generado->TipoCambioDocumento($this->sesion, $id_documento_cobro, $id_moneda);
				$decimales_cobro = $moneda_documento_cobro->fields['cifras_decimales'];
				$decimales_pago = $moneda->fields['cifras_decimales'];

				if (!$pago_gastos)
					$pago_gastos = 0;
				if (!$pago_honorarios)
					$pago_honorarios = 0;

				$neteo_documento = new NeteoDocumento($this->sesion);
				//Si el neteo existía, está siendo modificado y se debe partir de 0:
				if ($neteo_documento->Ids($id_documento, $id_documento_cobro))
					$out_neteos .= $neteo_documento->Reestablecer($decimales_cobro);
				else
					$out_neteos .= "<tr><td>No</td><td>0</td><td>0</td>";

				//Luego se modifica
				if ($pago_honorarios != 0 || $pago_gastos != 0)
					$out_neteos .= $neteo_documento->Escribir($pago_honorarios, $pago_gastos, $cambio_pago, $cambio_cobro, $decimales_pago, $decimales_cobro, $id_cobro_neteado);

				/* Compruebo cambios en saldos para mostrar mensajes de actualizacion */
				$documento_cobro_aux = new Documento($this->sesion);
				if ($documento_cobro_aux->Load($id_documento_cobro)) {
					if ($saldo_honorarios_anterior != $documento_cobro_aux->fields['saldo_honorarios'])
						$cambios_en_saldo_honorarios[] = $id_documento_cobro;
					if ($saldo_gastos_anterior != $documento_cobro_aux->fields['saldo_gastos'])
						$cambios_en_saldo_gastos[] = $id_documento_cobro;

					$neteo_documento->CambiarEstadoCobro($id_cobro_neteado, $documento_cobro_aux->fields['saldo_honorarios'], $documento_cobro_aux->fields['saldo_gastos']);
				}
			}
		}

		$out_neteos = "<table border=1><tr> <td>Id Cobro</td><td>Faltaba</td> <td>Aportaba y Devolví</td> <td>Pasó a Faltar</td> <td>Ahora aporto</td> <td>Ahora falta </td> </tr>" . $out_neteos . "</table>";
		//echo $out_neteos;

		return $id_documento;
	}

	function Escribir($objeto, $forzar_insert = false) {
		if (!$this->Write($objeto, $forzar_insert)) {
			return false;
		}

		$cobro = new Cobro($this->sesion);
		if ($cobro->Load($objeto->fields['id_cobro'])) {
			$cobro->Edit('documento', $objeto->ListaDocumentosLegales($cobro));
			if (!$this->Write($cobro)) {
				return false;
			}
		}

		return true;
	}

	/*
	  public function ExisteCodigo($lista_objetos, $tabla, $campo, $string = true)
	  {
	  $codigos_asunto = array();
	  foreach ($lista_objetos as $objeto)
	  {
	  $codigos_asunto[] = (!$string) ? (int)$objeto->fields[$campo] : $objeto->fields[$campo];
	  }
	  $codigos_asunto = array_unique($codigos_asunto);

	  if ($string)
	  {
	  $select = "(SELECT 'COD' " . $campo . " UNION SELECT '" . implode("' UNION SELECT '", $codigos_asunto) . "') AS COD";
	  }
	  else
	  {
	  $select = "(SELECT 0 " . $campo ." UNION SELECT " . implode(" UNION SELECT ", $codigos_asunto) . ") AS COD";
	  }
	  $campo_falso = ($string) ? "'COD'" : "0";

	  $query = "SELECT
	  " . $campo . "
	  FROM
	  " . $select . "
	  WHERE
	  " . $campo . " NOT IN (SELECT DISTINCT " . $campo . " FROM " . $tabla . ") AND
	  " . $campo . " != " . $campo_falso;

	  $response_codigos = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

	  $codigos_asunto = array();
	  while (list($codigo_asunto) = mysql_fetch_array($response_codigos))
	  {
	  $codigos_asunto[] = $codigo_asunto;
	  }

	  $objetos_sin_asunto = array();

	  if (!empty($codigos_asunto))
	  {
	  foreach ($codigos_asunto as $codigo_asunto)
	  {
	  foreach ($lista_objetos as $indice => $objeto)
	  {
	  if ($objeto->fields[$campo] == $codigo_asunto)
	  {
	  $objetos_sin_asunto[] = $indice;
	  }
	  }
	  }

	  echo "Los siguientes " . $campo . " no existen: " . implode(", ", $codigos_asunto) . "\n";
	  }

	  return $objetos_sin_asunto;
	  }

	  public function ExtraerObjetoLista($lista, $lista_extraer)
	  {
	  if (!empty($lista_extraer))
	  {
	  foreach($lista_extraer as $extraer)
	  {
	  unset($lista[$extraer]);
	  }
	  echo "Eliminados de la lista objetos con indice: " . implode(", ", $lista_extraer) . "\n";
	  }
	  return $lista;
	  }
	 */

	/**
	 * Esta función llena la tabla prm_area_proyecto en Time Tracking a partir de las tablas  diccionario de SAEJ
	 */
	function ActualizarAreaAsuntos() {

		$queryAreaAsuntos = "truncate table prm_tipo_proyecto;";
		$queryAreaAsuntos.="truncate table prm_area_proyecto;";
		$queryAreaAsuntos.="replace  into prm_tipo_proyecto (id_tipo_proyecto, glosa_tipo_proyecto,orden) SELECT 1*tabladetablavalor.codigo, descripcion, 1*secuencia
						FROM " . DBORIGEN . ".tabladetabla
						JOIN  " . DBORIGEN . ".`tabladetablavalor`
						USING ( codigotabla )
						WHERE nombretabla =  'saej_tipo_asunto'
						ORDER BY  `tabladetablavalor`.`codigo` ASC ;";


		$queryAreaAsuntos.="replace into prm_area_proyecto (id_area_proyecto, glosa,orden) SELECT 1*codigo, descripcion, 1*secuencia FROM " . DBORIGEN . ".`tabladetabla` join  " . DBORIGEN . ".tabladetablavalor using (codigotabla) where nombretabla ='saej_area_encargada' ;";


		$queryAreaAsuntos.=" update asunto join " . DBORIGEN . ".OrdenFacturacion ofn on asunto.codigo_asunto=ofn.NumeroOrdenFact set id_tipo_asunto=ofn.TipoAsunto*1 where ofn.TipoAsunto*1 in (select id_tipo_proyecto from prm_tipo_proyecto); ";

		$queryAreaAsuntos.=" update asunto join " . DBORIGEN . ".OrdenFacturacion ofn on asunto.codigo_asunto=ofn.NumeroOrdenFact set id_area_proyecto=ofn.codigoareaencargada*1 where ofn.codigoareaencargada*1 in (select id_area_proyecto from prm_area_proyecto); ";


		$this->sesion->pdodbh->beginTransaction();
		$this->sesion->pdodbh->exec($queryAreaAsuntos);
		$this->sesion->pdodbh->commit();
	}

	function ActualizarCuentaAsuntos() {
		$queryCuentaAsuntos = "truncate table prm_banco;";
		$queryCuentaAsuntos.="truncate table cuenta_banco;";
		$queryCuentaAsuntos.="replace  into prm_banco (id_banco, nombre) SELECT 1*CodigoBanco, NombreBanco FROM " . DBORIGEN . ".`TbBancos`;";
		$queryCuentaAsuntos.="insert into cuenta_banco (id_banco, numero, glosa, id_moneda)
			 SELECT distinct 1*codigobanco, cuentabanco,CONCAT(nombrebanco,' ',cuentabanco) as glosa,
			 IF( monedabanco = 'S', 1, IF( monedabanco = 'E', 3, 2 ) ) as id_moneda FROM " . DBORIGEN . ".`BancoEstudio`;";

		$queryCuentaAsuntos.="ALTER TABLE  " . DBORIGEN . ".`PagosRecibidos` ADD INDEX (  `CodigoCuentaBanco` );";
		$queryCuentaAsuntos.="ALTER TABLE  " . DBORIGEN . ".`Factura` ADD INDEX (  `Observacion` );";



		$queryCuentaAsuntos.="create temporary table cuenta_contrato as
						select distinct Factura.observacion as codigopropuesta, cuenta_banco.id_cuenta
						from  cuenta_banco
						join " . DBORIGEN . ".`PagosRecibidos` on PagosRecibidos.CodigoCuentaBanco=cuenta_banco.numero
						join " . DBORIGEN . ".Factura on  1*PagosRecibidos.numerofactura=1*Factura .numerofactura
						where  `CodigoCuentaBanco` is not null and Factura.observacion is not null and Factura.observacion!='';";


		$queryCuentaAsuntos.="update  contrato join cuenta_contrato using (codigopropuesta)  set 1*contrato.id_cuenta=1*cuenta_contrato.id_cuenta;";


		$this->sesion->pdodbh->beginTransaction();
		$this->sesion->pdodbh->exec($queryCuentaAsuntos);
		$this->sesion->pdodbh->commit();
	}

	function ActualizarAreaAsuntosPRC() {
		$asuntos = array(
			'0001036', '0002004', '0002015', '0002016', '0002017',
			'0002018', '0002019', '0002020', '0006039', '0006040', '0006041', '0012001', '0012002', '0012003', '0012004', '0012427',
			'0012558', '0012560', '0012561', '0012562', '0012563', '0012564', '0012565', '0012566', '0012568', '0012569', '0012571', '0012572', '0012573', '0012574', '0012575', '0012576', '0012577',
			'0012578', '0012579', '0012580', '0012581', '0012582', '0012583', '0012584', '0012585', '0012586', '0012587',
			'0012588', '0014019', '0014020', '0014021', '0014022', '0014023', '0014024', '0020094', '0020095', '0020096', '0020097',
			'0020098', '0021015', '0021016', '0021017', '0067015', '0067016', '0067017',
			'0067018', '0140011', '0140018', '0140020', '0140021', '0140022', '0140023', '0140024', '0140025', '0140026', '0140027',
			'0140028', '0142004', '0142013', '0142014', '0142015', '0142016', '0142017',
			'0142018', '0147011', '0147012', '0161003', '0161004', '0161005', '0161006', '0162014', '0162015', '0162016', '0162017',
			'0162018', '0172017', '0172033', '0172034', '0172035', '0172036', '0172037', '0172038', '0172039', '0173012', '0173016', '0173017',
			'0174048', '0174049', '0174050', '0174051', '0174052', '0174053', '0174054', '0174055', '0174056', '0174057',
			'0174058', '0174059', '0174060', '0174061', '0174062', '0174063', '0174064', '0174065', '0174066', '0174067',
			'0174068', '0174069', '0174070', '0174071', '0174072', '0174073', '0174074', '0174075', '0174076', '0174077',
			'0174078', '0174079', '0174080', '0174081', '0174082', '0174083', '0174084', '0174085', '0174086', '0174087',
			'0174088', '0174089', '0174090', '0174091', '0174092', '0174093', '0174094', '0174095', '0174096', '0174097',
			'0174098', '0174099', '0174100', '0174101', '0174102', '0174103', '0174104', '0174105', '0174106', '0174107',
			'0174108', '0174109', '0174110', '0174111', '0176002', '0179003', '0182008', '0184024', '0184025', '0184026', '0184027',
			'0184028', '0184029', '0184030', '0184031', '0184032', '0184033', '0184034', '0184035', '0184036', '0184037',
			'0184038', '0184039', '0187030', '0187031', '0187032', '0187033', '0200007',
			'0200008', '0200009', '0200010', '0200011', '0200012', '0200013', '0200014', '0200015', '0201011', '0204003', '0204004', '0204005', '0204006', '0204007',
			'0204008', '0205001', '0205014', '0205015', '0216001', '0216007',
			'0217004', '0217005', '0217006', '0217007', '0217008', '0217009', '0217010', '0217011', '0217012', '0217013', '0217014', '0217015', '0217016', '0217017',
			'0217018', '0217019', '0217020', '0217021', '0217022', '0217023', '0217024', '0217025', '0217026', '0217027', '0236011', '0248003', '0248004', '0252006', '0252007',
			'0257002', '0277021', '0277022', '0277023', '0277024', '0282010', '0282011', '0282012', '0282013', '0282014', '0282015', '0282016', '0291012', '0295056', '0295064', '0295066', '0295070', '0295083', '0295085', '0295087',
			'0295090', '0295091', '0295092', '0295093', '0295094', '0295095', '0295096', '0295097',
			'0295098', '0295099', '0295100', '0295101', '0295102', '0295103', '0295104', '0295105', '0295106', '0295107',
			'0295108', '0295109', '0295110', '0295111', '0295112', '0295113', '0295114', '0295115', '0295116', '0295117',
			'0295118', '0295119', '0295120', '0295121', '0295122', '0295123', '0295124', '0295125', '0295126', '0295127',
			'0295128', '0295129', '0295130', '0295131', '0295132', '0295133', '0295134', '0295135', '0295136', '0295137', '0295138', '0298003', '0298004', '0298005', '0298006', '0298007',
			'0298008', '0298009', '0298010', '0298011', '0298012', '0298013', '0298015', '0298016', '0313007', '0322006', '0322007', '0325001', '0329003', '0336007',
			'0336028', '0336031', '0336032', '0336033', '0336034', '0336035', '0336036', '0336037',
			'0336038', '0336039', '0336040', '0336041', '0336042', '0336043', '0336045', '0336046', '0336047',
			'0336048', '0336049', '0336050', '0336051', '0336052', '0336053', '0336054', '0336055', '0336057',
			'0352001', '0352005', '0352019', '0352020', '0352021', '0352022', '0352023', '0352024', '0352025', '0352026', '0352027',
			'0352028', '0353002', '0360004', '0360005', '0360006', '0360007',
			'0370001', '0370002', '0370003', '0370004', '0370005', '0370006', '0373003', '0378006', '0379004', '0379005', '0379006', '0386015', '0386016', '0386017',
			'0386018', '0386019', '0386020', '0386021', '0386022', '0386023', '0386024', '0386025', '0386026', '0386027',
			'0386028', '0386029', '0386030', '0386031', '0386032', '0426002', '0443028', '0443029', '0443030', '0443038', '0443039', '0443040', '0443041', '0443042', '0443043', '0443044', '0443045', '0443046', '0443047',
			'0443048', '0443049', '0444005', '0444027', '0444030', '0444043', '0444047', '0444049', '0444065', '0444067',
			'0444071', '0444076', '0444084', '0444101', '0444103', '0444105', '0444113', '0444123', '0444124', '0444125', '0444126', '0444127',
			'0451001', '0460002', '0460003', '0463003', '0463004', '0463005', '0463006', '0463007',
			'0465008', '0465020', '0465021', '0471021', '0471029', '0471030', '0471031', '0471032', '0471033', '0471034', '0477005', '0477006', '0477007',
			'0486008', '0486011', '0486013', '0486015', '0486019', '0486021', '0486022', '0486023', '0486024', '0486025', '0486026', '0486027',
			'0493009', '0493013', '0493014', '0493015', '0498008', '0498009', '0498010', '0498016', '0498019', '0498020', '0498021', '0498022', '0498023', '0498024', '0498025', '0498026', '0498027',
			'0498028', '0506004', '0506005', '0506006', '0506007', '0506008', '0508002', '0508003', '0508004', '0508005', '0510002', '0527007', '0527008', '0532007',
			'0542004', '0542005', '0543002', '0550009', '0551002', '0553003', '0565001', '0565002', '0565003', '0565021', '0565022', '0566003', '0566004', '0573002', '0581003', '0583004', '0583005', '0583006', '0583007',
			'0583008', '0583009', '0583010', '0584005', '0584008', '0584009', '0590003', '0590004', '0591022', '0595003', '0595004', '0597004', '0597005', '0597006', '0597007',
			'0599003', '0603002', '0603003', '0605003', '0605004', '0605005', '0605006', '0605007',
			'0605008', '0605009', '0605010', '0605011', '0605012', '0605013', '0605014', '0605015', '0605016', '0605017', '0605018', '0605019', '0605020', '0605021', '0605022', '0608002', '0608003', '0608004', '0614001', '0614002', '0620004', '0620005', '0622003', '0622016', '0622017',
			'0622018', '0622019', '0622020', '0622021', '0622022', '0622023', '0622024', '0622025', '0622026', '0623008', '0624002', '0625001', '0625002', '0628028', '0628029', '0628030', '0628031', '0642002', '0652008', '0654001', '0661003', '0661004', '0661005', '0661007', '0661008', '0662004', '0662005', '0663022', '0663024', '0663025', '0663026', '0663027',
			'0663028', '0663029', '0663030', '0663032', '0663033', '0663034', '0663035', '0663036', '0663037', '0663038', '0663039', '0665003', '0665004', '0665005', '0665006', '0665007',
			'0667003', '0667008', '0667011', '0667012', '0668004', '0668005', '0669002', '0669005', '0674002', '0679002', '0681008', '0681009', '0681010', '0681011', '0681012', '0681013', '0681014', '0681015', '0688005', '0697001', '0702002', '0702003', '0702004', '0702006', '0702007',
			'0702008', '0702009', '0702010', '0705005', '0706002', '0706003', '0706005', '0706006', '0706009', '0706010', '0706011', '0706012', '0706013', '0706014', '0706015', '0706016', '0706017',
			'0706018', '0706019', '0706020', '0706021', '0706022', '0706023', '0706024', '0706025', '0706026', '0706027', '0706028', '0706029', '0707006', '0707007',
			'0707009', '0707021', '0707022', '0707026', '0707029', '0707031', '0707036', '0707037', '0707038', '0707042', '0707043', '0707044', '0707045', '0707046', '0707047',
			'0707048', '0707049', '0707050', '0707051', '0707052', '0707053', '0707054', '0707055', '0707056', '0707057',
			'0707058', '0707059', '0707060', '0707061', '0707062', '0707063', '0707064', '0707065', '0707066', '0707067',
			'0707068', '0707069', '0707070', '0707071', '0707072', '0707073', '0707074', '0707075', '0707077', '0707078', '0707079', '0707080', '0707081', '0710005', '0710006', '0710007',
			'0710008', '0710009', '0710010', '0716004', '0716009', '0716014', '0716016', '0716018', '0716019', '0716020', '0716021', '0716022', '0716023', '0716024', '0716025', '0716026', '0716027',
			'0716028', '0716029', '0716030', '0716031', '0716032', '0716033', '0716034', '0716035', '0716036', '0716037',
			'0716038', '0718002', '0720004', '0726001', '0726002', '0728002', '0732001', '0732002', '0741010', '0744003', '0744004', '0744005', '0744011', '0744018', '0744019', '0744020', '0744021', '0744022', '0744023', '0744024', '0744025', '0744026', '0745003', '0748002', '0753002', '0753003', '0753004', '0754002', '0754018', '0754021', '0754022', '0754026', '0754027',
			'0754028', '0754029', '0754030', '0754031', '0754032', '0754033', '0754034', '0754035', '0754036', '0754037',
			'0754038', '0754039', '0754040', '0754041', '0754042', '0754043', '0754044', '0754045', '0754046', '0756003', '0758004', '0758005', '0759006', '0759007',
			'0759009', '0761003', '0761013', '0761014', '0761015', '0761016', '0761018', '0761019', '0761020', '0761021', '0761022', '0761023', '0761024', '0761025', '0763001', '0763008', '0763009', '0763010', '0763012', '0763013', '0763014', '0763015', '0763016', '0763017',
			'0763018', '0763019', '0763020', '0763021', '0763022', '0764001', '0764003', '0764004', '0764005', '0764006', '0764007',
			'0764008', '0764009', '0766004', '0778001', '0794008', '0794009', '0794010', '0798004', '0798005', '0798006', '0798007', '0798008', '0798009', '0800001', '0800006', '0800007',
			'0800008', '0800009', '0800010', '0800011', '0800012', '0800013', '0800014', '0800015', '0800016', '0804003', '0804009', '0806003', '0807002', '0807003', '0807004', '0809004', '0809005', '0810002', '0811001', '0811003', '0812002', '0812004', '0820002', '0821004', '0821005', '0821006', '0821007',
			'0821008', '0821009', '0821010', '0821011', '0821012', '0821013', '0821014', '0821015', '0821016', '0823002', '0824001', '0824004', '0824006', '0824007',
			'0825001', '0825007', '0825011', '0825012', '0825013', '0825015', '0825016', '0825017', '0825019', '0825020', '0827002', '0827011', '0827012', '0827013', '0827014', '0827015', '0827016', '0827017',
			'0827018', '0828002', '0833001', '0833002', '0836002', '0837001', '0837002', '0840003', '0840004', '0846001', '0849002', '0849003', '0850003', '0852003', '0852006', '0852007',
			'0852009', '0852011', '0852013', '0852015', '0852017', '0852019', '0852020', '0852021', '0852022', '0852023', '0852024', '0852025', '0852026', '0852027',
			'0852028', '0852029', '0852030', '0852031', '0852032', '0852033', '0852034', '0852035', '0852036', '0852037',
			'0852038', '0852039', '0852040', '0852041', '0852042', '0852043', '0852044', '0852045', '0852046', '0852047',
			'0852048', '0852049', '0852050', '0852051', '0852052', '0852053', '0852054', '0852055', '0852056', '0852057',
			'0852058', '0852059', '0852060', '0852061', '0852062', '0852063', '0852064', '0852065', '0852066', '0852067',
			'0852068', '0852069', '0852070', '0852071', '0853001', '0853002', '0853003', '0853008', '0853009', '0853013', '0853015', '0853016', '0853018', '0853019', '0853020', '0853021', '0853022', '0853023', '0853024', '0853025', '0853026', '0853027',
			'0853028', '0854003', '0854006', '0856003', '0856004', '0860003', '0860004', '0860010', '0861001', '0861004', '0863001', '0864002', '0866002', '0871001', '0871004', '0871005', '0871006', '0871007',
			'0871009', '0871010', '0871011', '0871012', '0871013', '0871014', '0871015', '0875002', '0875003', '0875004', '0875005', '0875006', '0875007',
			'0876003', '0878002', '0878003', '0878006', '0878007',
			'0878009', '0878010', '0878011', '0878012', '0878013', '0878014', '0878015', '0878016', '0878017',
			'0881002', '0881003', '0881004', '0881005', '0881006', '0881007',
			'0881008', '0881009', '0881010', '0881011', '0881012', '0881013', '0881014', '0881015', '0881016', '0884002', '0884003', '0884004', '0888002', '0888003', '0888004', '0888005', '0888006', '0888007',
			'0888008', '0888009', '0888010', '0888011', '0888012', '0888013', '0888014', '0888015', '0888016', '0888017',
			'0888018', '0888019', '0888020', '0888021', '0888022', '0888023', '0888024', '0888025', '0888026', '0893001', '0893002', '0894005', '0894006', '0894007',
			'0894008', '0895001', '0895002', '0896001', '0897001', '0897005', '0897007',
			'0897008', '0897009', '0897010', '0897011', '0897012', '0897013', '0897014', '0897015', '0897020', '0897021', '0897022', '0897023', '0897024', '0897025', '0897027',
			'0897028', '0897029', '0897030', '0897031', '0897032', '0897034', '0897035', '0897036', '0897038', '0897039', '0897040', '0897041', '0897042', '0897043', '0897044', '0897045', '0897046', '0897047',
			'0897048', '0897049', '0897050', '0897051', '0897053', '0897054', '0897055', '0897056', '0897057',
			'0897058', '0897059', '0897060', '0897061', '0897062', '0897063', '0897064', '0897065', '0897066', '0897067',
			'0897068', '0897069', '0897070', '0897071', '0897072', '0897073', '0897074', '0897075', '0897076', '0897077',
			'0897078', '0897079', '0897080', '0897081', '0897082', '0897083', '0897084', '0897085', '0897086', '0897087',
			'0897088', '0897089', '0897090', '0897091', '0897092', '0897093', '0897094', '0897095', '0897096', '0897097',
			'0897098', '0897099', '0897100', '0897101', '0897102', '0897103', '0897104', '0897105', '0897106', '0897107',
			'0897108', '0897109', '0897110', '0897111', '0897112', '0897113', '0897114', '0897115', '0897116', '0897117',
			'0897118', '0897119', '0897120', '0897121', '0897122', '0897123', '0897124', '0897125', '0897126', '0897127',
			'0897128', '0897129', '0897130', '0897131', '0902003', '0902004', '0902011', '0902012', '0902013', '0902014', '0902015', '0902016', '0902017',
			'0902018', '0902019', '0902020', '0902021', '0902022', '0902023', '0902024', '0907001', '0910002', '0912002', '0914002', '0915003', '0915004', '0917001', '0917002', '0917005', '0917006', '0917007',
			'0917008', '0917009', '0917010', '0917011', '0917012', '0917013', '0917014', '0920001', '0921001', '0921002', '0925004', '0929001', '0929002', '0930001', '0931001', '0931002', '0931003', '0931004', '0932002', '0932003', '0932004', '0932005', '0932006', '0932007',
			'0932008', '0932009', '0934003', '0934004', '0939003', '0939004', '0940003', '0940004', '0945001', '0946001', '0946002', '0947002', '0947006', '0947007',
			'0947008', '0947009', '0947010', '0947011', '0947012', '0947013', '0947014', '0947015', '0947016', '0947017',
			'0948001', '0948003', '0948004', '0948005', '0948006', '0948007',
			'0948008', '0948009', '0951001', '0951002', '0951003', '0952002', '0952003', '0952004', '0952005', '0952006', '0953001', '0953002', '0953004', '0954001', '0955001', '0955003', '0955004', '0955005', '0955006', '0958004', '0959001', '0960004', '0961001', '0962001', '0962002', '0962003', '0963001', '0965002', '0965003', '0965004', '0966003', '0966004', '0966005', '0966006', '0966008', '1000001', '1000002', '1000003', '1000004', '1000005', '1000006', '1001001', '1001002', '1001003', '1001004', '1001005', '1001006', '1001007',
			'1001008', '1001009', '1001010', '1001011', '1001012', '1001013', '1001015', '1002001', '1002002', '1002003', '1002004', '1002005', '1002006', '1002007',
			'1002008', '1002009', '1002010', '1002011', '1002012', '1002013', '1002014', '1002015', '1002016', '1002017',
			'1002018', '1002019', '1002020', '1002021', '1002023', '1002024', '1002025', '1002027',
			'1002028', '1002029', '1002031', '1002032', '1002033', '1003001', '1003002', '1006002', '1006003', '1006004', '1006005', '1006006', '1006007',
			'1008001', '1008002', '1008003', '1013002', '1017001', '1017002', '1017003', '1017004', '1017005', '1017006', '1018001', '1019002', '1019003', '1019004', '1020004', '1021001', '1025001', '1026001', '1027001', '1028001', '1030001', '1030002', '1030003', '1030004', '1030005', '1033001', '1033002', '1034003', '1034005', '1034006', '1034007',
			'1034008', '1035001', '1035002', '1040001', '1041001', '1041002', '1041003', '1041004', '1041005', '1041006', '1042001', '1042002', '1042003', '1042004', '1042005', '1042006', '1042007',
			'1042008', '1042009', '1042010', '1042011', '1042012', '1042013', '1042014', '1042015', '1042016', '1042017',
			'1042018', '1042019', '1042020', '1044003', '1044004', '1045001', '1048001', '1048002', '1048003', '1049002', '1052002', '1052003', '1054001', '1055001', '1055002', '1055003', '1056001', '1056002', '1056003', '1056004', '1056005', '1056006', '1057001', '1058002', '1058003', '1058004', '1059001', '1061002', '1062001', '1064002', '1066001', '1068001', '1069001', '1070001', '1070002', '1072001', '1072002', '1072003', '1072004', '1072005', '1072006', '1073001', '1073002', '1073003', '1073004', '1073005', '1073006', '1073007',
			'1073008', '1073009', '1074001', '1075002', '1076001', '1077005', '1077008', '1077009', '1077010', '1077012', '1077013', '1077014', '1078002', '1078003', '1080001', '1081001', '1082001', '1083001', '1084001', '1087002', '1087003', '1088002', '1088004', '1088005', '1088006', '1088007',
			'1088008', '1088009', '1088010', '1088011', '1089002', '1091001', '1091002', '1091003', '1092001', '1092002', '1093001', '1094001', '1095001', '1095002', '1095003', '1095004', '1095005', '1096001', '1097002', '1097004', '1097005', '1097006', '1097007',
			'1097008', '1097009', '1097010', '1097011', '1098001', '1098002', '1098003', '1099001', '1105002', '1105003', '1106001', '1107001', '1108001', '1108002', '1108003', '1109002', '1109003', '1110001', '1111001', '1114001', '1114002', '1114003', '1114004', '1114005', '1114006', '1115001', '1115003', '1116001', '1116002', '1117001', '1117002', '1117003', '1117004', '1117005', '1118001', '1118002', '1118003', '1118004', '1118005', '1118006', '1118007',
			'1119003', '1120001', '1120002', '1120003', '1120004', '1120005', '1120006', '1120007',
			'1120008', '1120009', '1120010', '1120011', '1120012', '1123001', '1123002', '1124001', '1124002', '1124003', '1124004', '1126001', '1127001', '1128001', '1128002', '1128003', '1128004', '1129001', '1130001', '1131001', '1131002', '1132001', '1132002', '1132003', '1132004', '1132005', '1132006', '1132007',
			'1132008', '1132009', '1132010', '1132011', '1132012', '1132013', '1132014', '1132015', '1133001', '1134001', '1134002', '1134003', '1135001', '1136001', '1138001', '1140001', '1140002', '1141001', '1144001', '1146001', '1146002', '1146003', '1146004', '1146005', '1146006', '1146007',
			'1146008', '1146009', '1146010', '1146011', '1146012', '1146013', '1146014', '1146015', '1146016', '1146017',
			'1146018', '1147001', '1148001', '1148002', '1149001', '1150001', '1151001', '1152001', '1153001', '1154002', '1154003', '1155001', '1155002', '1155003', '1155004', '1155005', '1155006', '1155007',
			'1155008', '1155009', '1155010', '1156001', '1156002', '1156003', '1156004', '1156005', '1156006', '1157001', '1158001', '1159001', '1160001', '1160002', '1160003', '1161001', '1161002', '1162001', '1162002', '1162003', '1163001', '1164001', '1164002', '1164003', '1164004', '1164005', '1164006', '1165001', '1166001', '1168001', '1168002', '1168003', '1169001', '1170001', '1171001', '1172001', '1173001', '1174001', '1175001', '1176001', '1176002', '1176003', '1176004', '1176005', '1177001', '1177002', '1177003', '1178001', '1179001', '1179002', '1179003', '1179004', '1180001', '1180002', '1181001', '1181002', '1182001', '1183001', '1183002', '1183003', '1184001', '1186001', '1186002', '1187001', '1188001', '1188002', '1189001', '1189002', '1190001', '1190002', '1190003', '1191001', '1191002', '1191003', '1191004', '1192001', '1192002', '1193001', '1194001', '1194002', '1194003', '1194004', '1194005', '1194006', '1194007',
			'1194008', '1194009', '1194010', '1195001', '1195002', '1195003', '1195004', '1196001', '1196002', '1196003', '1196004', '1197001', '1198001', '1198002', '1198003', '1198004', '1199001', '1200001', '1201001', '1201002', '1202001', '1202002', '1202003', '1202004', '1202005', '1203001', '1203002', '1204001', '1204002', '1205001', '1206001', '1207001', '1207002', '1208001', '1209001', '1209002', '1209003', '1209004', '1209005', '1209006', '1209007',
			'1209008', '1209009', '1210001', '1211001', '1211002', '1212001', '1213001', '1214001', '1215001', '1215002', '1216001', '1217001', '1218001', '1218002', '1218003', '1218004', '1219001', '1220001', '1221001', '1222001', '1223001', '1223002', '1223003', '1223004', '1223005', '1224001', '1225001', '1225002', '1226001', '1227001', '1228001', '1228002', '1229001', '1229002', '1229003', '1229004', '1230001', '1230002', '1230003', '1230004', '1230005', '1230006', '1230007',
			'1230008', '1230009', '1230010', '1231001', '1232001', '1232002', '1233001', '1233002', '1234001', '1235001', '1235002', '1235003', '1235004', '1236001', '1236002', '1236003', '1236004', '1237001', '1237002', '1238001', '1238002', '1238003', '1239001', '1239002', '1239003', '1239004', '1239005', '1239006', '1240001', '1240002', '1240003', '1240004', '1241001', '1242001', '1242002', '1242003', '1242004', '1242005', '1243001', '1243002', '1243003', '1243004', '1243005', '1243006', '1243007',
			'1243008', '1243009', '1244001', '1245001', '1246001', '1247001', '1248001', '1249001', '1249002', '1250001', '1251001', '1252001', '1252002', '1252003', '1253001', '1254001', '1255001', '1256001', '1257001', '1258001', '1259001', '1259002', '1259003', '1259004', '1259005', '1259006', '1260001', '1261001', '1261002', '1262001', '1262002', '1262003', '1263001', '1264001', '1264002', '1264003', '1265001', '1266001', '1267001', '1268001', '1269001', '1270001', '1270002', '1270003', '1270004', '1270005', '1271001', '1272001', '1273001', '1274001', '1274002', '1274003', '1275001', '1276001', '1277001', '1278001', '1278002', '1278003', '1279001', '1279002', '1279004', '1279005', '1279006', '1280001', '1280002', '1280003', '1280004', '1280005', '1281001', '1282001', '1283001', '1284001', '1284002', '1285001', '1286001', '1286002', '1287001', '1288001', '1289001', '1290001', '1291001', '1291002', '1291003', '1291004', '1291005', '1292001', '1293001', '1293002', '1294001', '1295001', '1296001', '1297001', '1298001', '1298002', '1298003', '1298004', '1299001', '1300001', '1301001', '1301002', '1302001', '1303001', '1304001', '1304002', '1305001', '1306001', '1307001', '1308001', '1308002', '1308003', '1308004', '1308005', '1308006', '1308007',
			'1308008', '1309001', '1310001', '1310002', '1310003', '1311001', '1312001', '1313001', '1313002', '1314001', '1315001', '1316001', '1316002', '1317001', '1318001', '1318002', '1319001', '1320001', '1321001', '1321002', '1321003', '1322001', '1323001', '1323002', '1324001', '1324002', '1324003', '1325001', '1326001', '1327001', '1327002', '1328001', '1329001', '1330001', '1330002', '1330003', '1331001', '1331002', '1332001', '1333001', '1333002', '1334001', '1335001', '1336001', '1337001', '1338001', '1339001', '1340001', '1340002', '1341001', '1342001', '1343001', '1343002', '1344001', '1344002', '1344003', '1344004', '1344005', '1345001', '1345002', '1345003', '1346001', '1347001', '1348001', '1348002', '1349001', '1350001', '1350002', '1350003', '1351001', '1352001', '1353001', '1354001', '1354002', '1354003', '1355001', '1356001', '1357001', '1357002', '1357003', '1358001', '1358002', '1358003', '1359001', '1360001', '1361001', '1362001', '1362002', '1363001', '1363002', '1363003', '1363004', '1363005', '1363006', '1363007',
			'1363008', '1363009', '1363010', '1363011', '1363012', '1363013', '1363014', '1363015', '1363016', '1363017',
			'1363018', '1363019', '1363020', '1363021', '1363022', '1363023', '1363024', '1363025', '1363026', '1364001', '1365001', '1366001', '1367001', '1368001', '1368002', '1369001', '1369002', '1369003', '1369004', '1369005', '1369006', '1370001', '1371001', '1372001', '1373001', '1374001', '1375001', '1376001', '1376002', '1377001', '1378001', '1379001', '1380001', '1381001', '1381002', '1382001', '1383001', '1384001', '1385001', '1385002', '1386001', '1387001', '1388001', '1389001', '1390001', '1391001', '1391002', '1392001', '1393001', '1394001', '1394002', '1395001', '1396001', '1397001', '1398001', '1398002', '1398003', '1399001', '1400001', '1401001', '1402001', '1403001', '1404001', '1405001', '1405002', '1405003', '1405004', '1406001', '1407001', '1408001', '1408002', '1409001', '1410001', '1411001', '1412001', '1413001', '1413002', '1414001', '1415001', '1415002', '1416001', '1417002', '1418001', '1419001', '1420001', '1420002', '1420003', '1421001', '1422001', '1423001', '1424001', '1424002', '1425001', '1426001', '1426002', '1427001', '1428001', '1428002', '1429001', '1430001', '1431001', '1431002', '1432001', '1433001', '1434001', '1435001', '1436001', '1437001', '1437002', '1438001', '1439001', '1440001', '1441001', '1442001', '1443001', '1443002', '1443003', '1444001', '1444002', '1445001', '1446001', '1447001', '1447002', '1448001', '1448002', '1448003', '1449001', '1450001', '1450002', '1451001', '1452001', '1453001', '1454001', '1455001', '1455002', '1456001', '1457001', '1457002', '1457003', '1458001', '1459001', '1459002', '1460001', '1461001', '1462001', '1463001', '1463002', '1463003', '1463004', '1463005', '1464001', '1465001', '1466001', '1467001', '1468001', '1468002', '1468003', '1468004', '1468005', '1468006', '1468007', '1468008', '1469001', '1469002', '1469003', '1470001', '1471001', '1472001', '1473001', '1474001', '1476001', '1476002', '1476003', '1478001', '1479001', '1480001', '1481001', '1482001', '1483001', '1484001', '1485001', '1486001', '1487001', '1488001', '1488002', '1489001', '1489002', '1490001', '1491001', '1491002', '1492001', '1493001', '1494001', '1495001', '1496001', '1497001', '1498001', '1499001', '1500001', '1501001', '1502001', '1503001', '1504001', '1505001', '1506001', '1507001', '1508001', '1510001', '1511001', '1511002', '1511003', '1511004', '1511005', '1512001', '1513001', '1515001', '1516001', '1516002', '1516003', '1516004', '1517001', '1518001', '1519001', '1520001', '1520002', '1521001', '1522001', '1522002', '1523001', '1524001', '1524002', '1525001', '1526001', '1527001', '1528001', '1528002', '1528003', '1528004', '1529001', '1530001', '1531001', '1532001', '1533001', '1534001', '1535001', '1536001', '1538001', '1538002', '1539001', '1540001', '1541001', '1542001', '1543001', '1544001', '1545001', '1546001', '1548001', '2001001', '2001003', '2001005', '2001006', '2001007', '2001009');

		$areas = array(
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Regulatorio', 'Procesal', 'Corporativo', 'Procesal', 'Laboral',
			'Tributario', 'Corporativo', 'Corporativo', 'Procesal', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Finanzas', 'Regulatorio', 'Procesal', 'Corporativo', 'Corporativo', 'Laboral', 'Regulatorio', 'Laboral', 'Laboral',
			'Regulatorio', 'Regulatorio', 'Regulatorio', 'Finanzas', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Laboral',
			'Corporativo', 'Regulatorio', 'Finanzas', 'Laboral', 'Corporativo', 'Corporativo', 'Procesal', 'Regulatorio', 'Procesal', 'Corporativo', 'Laboral', 'Laboral', 'Corporativo', 'Laboral',
			'Corporativo', 'Finanzas', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Regulatorio', 'Corporativo', 'Tributario', 'Corporativo', 'Procesal', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Laboral', 'Laboral',
			'Tributario', 'Procesal', 'Corporativo', 'Corporativo', 'Tributario', 'Finanzas', 'Finanzas', 'Tributario', 'Corporativo', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Procesal', 'Corporativo', 'Finanzas', 'Finanzas', 'Regulatorio', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Regulatorio', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Finanzas', 'Finanzas', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Tributario', 'Corporativo', 'Corporativo', 'Finanzas', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Tributario', 'Procesal', 'Procesal', 'Corporativo', 'Finanzas', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Regulatorio', 'Procesal', 'Regulatorio', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Regulatorio', 'Regulatorio', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Laboral',
			'Laboral', 'Regulatorio', 'Regulatorio', 'Procesal', 'Laboral', 'Procesal', 'Procesal', 'Laboral', 'Regulatorio', 'Procesal', 'Procesal', 'Laboral',
			'Procesal', 'Procesal', 'Procesal', 'Tributario', 'Procesal', 'Corporativo', 'Laboral', 'Procesal', 'Procesal', 'Laboral', 'Procesal', 'Procesal', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Finanzas', 'Tributario', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Regulatorio', 'Corporativo', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Regulatorio', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Regulatorio', 'Procesal', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Procesal', 'Procesal', 'Regulatorio', 'Regulatorio', 'Laboral',
			'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Laboral',
			'Laboral', 'Laboral', 'Laboral', 'Procesal', 'Laboral', 'Laboral', 'Laboral', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Laboral', 'Laboral',
			'Procesal', 'Procesal', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Laboral',
			'Procesal', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Laboral',
			'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Tributario', 'Laboral',
			'Laboral', 'Tributario', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Finanzas', 'Corporativo', 'Tributario', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Procesal', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Laboral',
			'Procesal', 'Laboral', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Tributario', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Corporativo', 'Corporativo', 'Tributario', 'Regulatorio', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Laboral',
			'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Tributario', 'Regulatorio', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Laboral',
			'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Regulatorio', 'Laboral',
			'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Regulatorio', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Laboral',
			'Laboral', 'Corporativo', 'Corporativo', 'Procesal', 'Laboral', 'Laboral', 'Corporativo', 'Laboral', 'Laboral', 'Laboral', 'Corporativo', 'Laboral',
			'Corporativo', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Finanzas', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Procesal', 'Procesal', 'Regulatorio', 'Regulatorio', 'Procesal', 'Procesal', 'Laboral',
			'Corporativo', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Regulatorio', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Tributario', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Laboral',
			'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Corporativo', 'Laboral', 'Procesal', 'Regulatorio', 'Laboral', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Laboral', 'Laboral', 'Laboral',
			'Procesal', 'Corporativo', 'Laboral', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Laboral', 'Laboral', 'Procesal', 'Laboral', 'Procesal', 'Laboral', 'Regulatorio', 'Procesal', 'Laboral',
			'Laboral', 'Laboral', 'Procesal', 'Laboral', 'Procesal', 'Procesal', 'Procesal', 'Laboral', 'Laboral', 'Regulatorio', 'Laboral', 'Procesal', 'Procesal', 'Laboral', 'Procesal', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Procesal', 'Procesal', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Corporativo', 'Corporativo', 'Laboral', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Laboral', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Laboral', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Laboral', 'Procesal', 'Corporativo', 'Procesal', 'Laboral', 'Procesal', 'Procesal', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Procesal', 'Corporativo', 'Laboral', 'Laboral', 'Corporativo', 'Corporativo', 'Laboral', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral', 'Laboral', 'Laboral', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Tributario', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Tributario', 'Procesal', 'Regulatorio', 'Corporativo', 'Procesal', 'Laboral',
			'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Finanzas', 'Finanzas', 'Finanzas', 'Finanzas', 'Procesal', 'Procesal', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Finanzas', 'Finanzas', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Tributario', 'Tributario', 'Corporativo', 'Finanzas', 'Finanzas', 'Procesal', 'Finanzas', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Procesal', 'Tributario', 'Tributario', 'Regulatorio', 'Corporativo', 'Laboral',
			'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Procesal', 'Laboral',
			'Corporativo', 'Corporativo', 'Laboral', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Finanzas', 'Tributario', 'Tributario', 'Corporativo', 'Procesal', 'Procesal', 'Regulatorio', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Laboral',
			'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Regulatorio', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Laboral', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral', 'Laboral', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Tributario', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Laboral',
			'Laboral', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Laboral',
			'Laboral', 'Finanzas', 'Corporativo', 'Corporativo', 'Laboral', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Laboral',
			'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Regulatorio', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Procesal', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Procesal', 'Laboral',
			'Procesal', 'Laboral', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Laboral',
			'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Laboral',
			'Procesal', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Finanzas', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Laboral',
			'Laboral', 'Laboral', 'Tributario', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Corporativo', 'Tributario', 'Tributario', 'Corporativo', 'Tributario', 'Tributario', 'Tributario', 'Tributario', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Tributario', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Laboral',
			'Procesal', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Procesal', 'Procesal', 'Tributario', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Finanzas', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Tributario', 'Procesal', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Laboral',
			'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral', 'Laboral',
			'Regulatorio', 'Procesal', 'Procesal', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Corporativo', 'Laboral', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Laboral',
			'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Finanzas', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Laboral', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral', 'Laboral',
			'Procesal', 'Corporativo', 'Finanzas', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Procesal', 'Tributario', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Finanzas', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Tributario', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Tributario', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Finanzas', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Laboral', 'Laboral', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Procesal', 'Laboral', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Corporativo', 'Finanzas', 'Finanzas', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Finanzas', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Procesal', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Laboral',
			'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Finanzas', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Corporativo', 'Laboral',
			'Corporativo', 'Laboral', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Procesal', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Procesal', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Tributario', 'Corporativo', 'Procesal', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Laboral', 'Procesal', 'Corporativo', 'Procesal', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Regulatorio', 'Tributario', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Procesal', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Laboral',
			'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Corporativo', 'Regulatorio', 'Corporativo', 'Corporativo', 'Corporativo', 'Procesal', 'Corporativo', 'Regulatorio', 'Corporativo', 'Procesal', 'Corporativo', 'Procesal', 'Procesal', 'Procesal');

		foreach ($asuntos as $key => $val) {
			$codigo_asunto = substr($val, 0, 4) . '-' . substr($val, 4, 3);

			$query_area = "SELECT id_area_proyecto FROM prm_area_proyecto WHERE glosa LIKE '%" . $areas[$key] . "%' ";
			$resp = mysql_query($query_area, $this->sesion->dbh) or Utiles::errorSQL($query_area, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_area_proyecto) = mysql_fetch_array($resp);

			$query_update = "UPDATE asunto SET id_area_proyecto = '$id_area_proyecto' WHERE codigo_asunto = '$codigo_asunto' ";
			mysql_query($query_update, $this->sesion->dbh) or Utiles::errorSQL($query_update, __FILE__, __LINE__, $this->sesion->dbh);
		}
	}

	function ActualizarCuentaAsuntosPrc() {
		$asuntos = array(
			'0002004', '0002019', '0002020', '0006039',
			'0012001', '0012002', '0012003', '0012004', '0012427', '0012572', '0012573', '0012575', '0012581', '0012582', '0012583', '0012585', '0012586', '0012587', '0012588', '0014024', '0020094', '0020095', '0067015', '0067017', '0067018', '0140011', '0140018', '0140027', '0147012', '0161003', '0162015', '0162018', '0173012', '0174056', '0174064', '0174066', '0174068', '0174070', '0174071', '0174073', '0174074', '0174075', '0174076', '0174079',
			'0174081', '0174083', '0174084', '0174085', '0174086', '0174088', '0174089',
			'0174090', '0174092', '0174093', '0174094', '0174095', '0174096', '0174098', '0174102', '0174103', '0174104', '0174106', '0174107', '0174108', '0182008', '0184029',
			'0184030', '0184031', '0184032', '0184034', '0184037', '0184038', '0187033', '0204007', '0216001', '0217009', '0217019',
			'0217022', '0217023', '0217025', '0228003', '0248004', '0252007', '0257002', '0282014', '0295056', '0295066', '0295070', '0295083', '0295087', '0295090', '0295094', '0295095', '0295096', '0295102', '0295103', '0295104', '0295105', '0295106', '0295109',
			'0295111', '0295112', '0295113', '0295115', '0295117', '0295118', '0295120', '0295121', '0295122', '0295123', '0295124', '0295125', '0295126', '0295127', '0295128', '0295129',
			'0295130', '0295131', '0295132', '0295133', '0295134', '0295136', '0295137', '0298003', '0298004', '0298006', '0298007', '0298016', '0329003', '0336042', '0336050', '0336052', '0336053', '0336054', '0352001', '0352027', '0360005', '0360007', '0370001', '0370002', '0370003', '0370004', '0370005', '0370006', '0373003', '0379006', '0386015', '0386017', '0386018', '0386019',
			'0386020', '0386021', '0386022', '0386023', '0386024', '0386025', '0386026', '0386027', '0386028', '0386029',
			'0386030', '0386031', '0386032', '0426002', '0443041', '0443042', '0443043', '0443044', '0443045', '0443046', '0444027', '0444030', '0444043', '0444047', '0444049',
			'0444065', '0444067', '0444071', '0444076', '0444101', '0444123', '0444124', '0451001', '0463007', '0465008', '0471021', '0486019', '0486025', '0486026', '0493002', '0493009',
			'0498008', '0498010', '0498016', '0498020', '0498021', '0498025', '0498027', '0498028', '0506008', '0508002', '0508003', '0508004', '0550009', '0553003', '0566003', '0566004', '0573002', '0583009',
			'0583010', '0597004', '0599003', '0603002', '0605003', '0605006', '0605013', '0605014', '0605016', '0605019', '0605020', '0605021', '0608004', '0620005', '0622003', '0622016', '0622017', '0622018', '0622019',
			'0622021', '0622022', '0622023', '0622024', '0622025', '0622026', '0654001', '0661005', '0662004', '0662005', '0663025', '0663037', '0667003', '0667008', '0668005', '0669002', '0674002', '0681011', '0702002', '0702003', '0702004', '0702006', '0702007', '0702008', '0702009',
			'0702010', '0706003', '0706006', '0706013', '0706015', '0706016', '0706017', '0706018', '0706019', '0706020', '0706021', '0706023', '0706024', '0706025', '0706026', '0706027', '0706028', '0706029',
			'0707029', '0707031', '0707036', '0707042', '0707052', '0707053', '0707054', '0707055', '0707057', '0707059', '0707064', '0707067', '0707068', '0707069',
			'0707070', '0707071', '0707072', '0707073', '0707074', '0707075', '0707076', '0707079', '0707080', '0707081', '0716009', '0716014', '0716018', '0716019',
			'0716020', '0716027', '0716028', '0716031', '0716032', '0716033', '0716034', '0716035', '0716036', '0716037', '0744003', '0744004', '0744005', '0744011', '0744018', '0744019',
			'0744025', '0744026', '0753003', '0754018', '0754032', '0754035', '0754037', '0754038', '0754041', '0754042', '0754043', '0754044', '0754045', '0754046', '0758005', '0759009',
			'0761003', '0761013', '0761014', '0761015', '0761016', '0761018', '0761019',
			'0761020', '0761021', '0761022', '0761023', '0761024', '0761025', '0763021', '0763022', '0764001', '0764003', '0764004', '0764005', '0764006', '0764008', '0778001', '0794008', '0794010', '0800001', '0800007', '0800008', '0800009',
			'0800010', '0800011', '0800012', '0800013', '0800014', '0800015', '0807003', '0810002', '0812002', '0812004', '0820002', '0825007', '0827002', '0827018', '0836002', '0850003', '0852003', '0852006', '0852007', '0852009',
			'0852011', '0852013', '0852015', '0852017', '0852019', '0852020', '0852021', '0852022', '0852025', '0852026', '0852027', '0852028', '0852030', '0852031', '0852034', '0852036', '0852037', '0852039',
			'0852040', '0852041', '0852042', '0852044', '0852045', '0852046', '0852047', '0852049', '0852050', '0852052', '0852053', '0852054', '0852056', '0852057', '0852058', '0852059',
			'0852060', '0852061', '0852062', '0852063', '0852064', '0852066', '0852067', '0853001', '0853002', '0853025', '0854006', '0856004', '0860003', '0860010', '0861001', '0863001', '0871008', '0871015', '0875003', '0875004', '0875005', '0875006', '0875007', '0878002', '0878013', '0878014', '0881002', '0881003', '0881008', '0881010', '0881013', '0881014', '0881015', '0881016', '0884003', '0884004', '0888002', '0888003', '0888004', '0888005', '0888006', '0888007', '0888008', '0888009',
			'0888010', '0888011', '0888012', '0888013', '0888014', '0888015', '0888016', '0888017', '0888018', '0888019',
			'0888020', '0888021', '0888024', '0888025', '0895001', '0897001', '0897005', '0897007', '0897009', '0897013', '0897014', '0897021', '0897022', '0897028', '0897029',
			'0897032', '0897035', '0897036', '0897038', '0897039', '0897040', '0897041', '0897042', '0897043', '0897044', '0897050', '0897051', '0897054', '0897056', '0897058', '0897059',
			'0897060', '0897065', '0897066', '0897068', '0897078', '0897079', '0897080', '0897081', '0897082', '0897084', '0897085', '0897087', '0897089',
			'0897090', '0897091', '0897092', '0897093', '0897094', '0897096', '0897097', '0897099', '0897101', '0897103', '0897104', '0897105', '0897107', '0897108', '0897109',
			'0897113', '0897114', '0897115', '0897116', '0897117', '0897118', '0897119', '0897121', '0897122', '0897123', '0897124', '0897125', '0897126', '0897127', '0897128', '0897129',
			'0897130', '0902004', '0915004', '0917001', '0917008', '0917010', '0917011', '0917012', '0917014', '0921002', '0930001', '0931003', '0932002', '0932003', '0932007', '0932009',
			'0934004', '0945001', '0947007', '0948001', '0948003', '0948005', '0948006', '0948007', '0948008', '0951002', '0952004', '0952005', '0952006', '0954001', '0955001', '0961001', '0965002', '0966003', '1006002', '1006004', '1008003', '1017001', '1017002', '1018001', '1021001', '1025001', '1033002', '1034008', '1035001', '1035002', '1041001', '1041005', '1041006', '1042009',
			'1042015', '1042016', '1042017', '1042018', '1042019',
			'1042020', '1044003', '1045001', '1048001', '1048002', '1048003', '1055001', '1055002', '1055003', '1056006', '1059001', '1062001', '1070001', '1072001', '1076001', '1077012', '1082001', '1087003', '1088004', '1088005', '1088006', '1088007', '1093001', '1095005', '1097011', '1098003', '1114002', '1114006', '1116002', '1117001', '1117002', '1117003', '1118001', '1118002', '1118004', '1118005', '1118006', '1118007', '1120005', '1120006', '1120008', '1120009',
			'1120010', '1120011', '1124002', '1124003', '1124004', '1132001', '1132013', '1132014', '1132015', '1134001', '1134002', '1134003', '1146001', '1146006', '1146013', '1146014', '1146015', '1146016', '1146017', '1153001', '1155001', '1155003', '1155006', '1155007', '1155009',
			'1156002', '1156003', '1156004', '1156005', '1156006', '1158001', '1159001', '1164001', '1164002', '1164003', '1164004', '1164005', '1164006', '1166001', '1168002', '1168003', '1171001', '1177001', '1177002', '1177003', '1178001', '1181001', '1181002', '1183001', '1183002', '1183003', '1187001', '1189001', '1189002', '1191001', '1191004', '1192001', '1192002', '1194002', '1194005', '1194006', '1194009',
			'1194010', '1195001', '1195003', '1195004', '1198002', '1198004', '1199001', '1202001', '1209002', '1209004', '1209006', '1209007', '1210001', '1218002', '1219001', '1221001', '1223001', '1223002', '1223003', '1223004', '1227001', '1228002', '1229002', '1229004', '1230001', '1230002', '1230003', '1230004', '1230005', '1230006', '1230007', '1230008', '1230009',
			'1230010', '1232001', '1232002', '1233001', '1233002', '1235003', '1236004', '1238003', '1239003', '1239005', '1241001', '1243002', '1243006', '1243007', '1243008', '1243009',
			'1244001', '1245001', '1246001', '1248001', '1250001', '1252001', '1252003', '1262003', '1270002', '1275001', '1278003', '1280001', '1280002', '1280003', '1283001', '1286002', '1288001', '1289001', '1292001', '1297001', '1303001', '1305001', '1309001', '1310002', '1310003', '1314001', '1315001', '1316001', '1321003', '1323002', '1324001', '1324002', '1327001', '1327002', '1330003', '1331002', '1333001', '1333002', '1336001', '1337001', '1340002', '1342001', '1343001', '1344003', '1344005', '1349001', '1350003', '1351001', '1354001', '1354002', '1354003', '1355001', '1356001', '1357001', '1357002', '1357003', '1363001', '1363002', '1363003', '1363004', '1363005', '1363006', '1363007', '1363008', '1363009',
			'1363010', '1363011', '1363012', '1363013', '1363014', '1363015', '1363016', '1363017', '1363018', '1363019',
			'1363020', '1363021', '1363023', '1363024', '1363025', '1363026', '1364001', '1367001', '1368002', '1369003', '1369004', '1369005', '1369006', '1370001', '1371001', '1372001', '1375001', '1376002', '1377001', '1380001', '1381002', '1382001', '1383001', '1385001', '1386001', '1387001', '1391001', '1391002', '1392001', '1397001', '1400001', '1403001', '1405001', '1405002', '1405003', '1405004', '1407001', '1410001', '1411001', '1415001', '1418001', '1420001', '1420002', '1420003', '1422001', '1423001', '1424001', '1425001', '1428002', '1431001', '1431002', '1432001', '1435001', '1436001', '1437001', '1442001', '1443003', '1444001', '1446001', '1447002', '1448002', '1448003', '1450001', '1453001', '1454001', '1455001', '1455002', '1457001', '1457002',
			'1457003', '1460001', '1461001', '1462001', '1463001', '1463002', '1463003', '1465001', '1468001', '1468002', '1468004', '1469003', '1470001', '1471001', '1472001', '1474001', '1475001', '1476001', '1476002', '1476003', '1479001', '1480001', '1481001', '1483001', '1484001', '1485001', '1486001', '1488001', '1489001', '1490001', '1492001', '1493001', '1494001', '1495001', '1496001', '1497001', '1499001', '1502001', '1503001', '1504001', '1505001', '1506001', '1507001', '1508001', '1509001', '1510001', '1511001', '1512001', '1513001', '1513002', '1514001', '1515001', '1516001', '1516002', '1516003', '1516004', '1517001', '1518001', '1519001', '1520001', '1521001', '1522001', '1522002', '1523001', '1524001', '1525001', '2001007'
		);

		$cuentas = array(
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-4676475', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '170-0538468', '170-0538468', '170-0538468', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '011-0380-0100011066', '011-0380-0100011066', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '000-2923877',
			'000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239--43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-4676475', '000-4676475', '000-4676475', '000-2923877', '000-4676475', '000-4676475', '000-4676475', '000-2923877', '000-2923877', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-2923877', '000-4676475', '000-2923877', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-2923877', '000-4676475', '000-4676475', '000-4676475', '000-4676475', '000-4676475',
			'000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-4676475', '000-4676475', '000-2923877', '000-4676475', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-4676475', '000-4676475', '000-2923877', '000-4676475', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17',
			'194-0041067-0-17', '194-0041067-0-17', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '000-2923877', '000-2923877', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877',
			'000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-4676475', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-4676475', '000-4676475', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '000-4676475', '000-4676475', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '011-0380-0100011066', '011-0380-0100011066', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '000-2923877', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '000-2923877', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17',
			'194-0041067-0-17', '194-0119239-1-43', '011-0380-0100011066', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '011-0380-0100011066', '194-0119239-1-43',
			'194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '000-2923877', '000-2923877', '194-0119239-1-43', '011-0380-0100011066', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-4676475', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '170-0538468', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877',
			'000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '000-2923877', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '170-0538468', '170-0538468', '170-0538468', '170-0538468', '194-0041067-0-17', '194-0119239-1-43', '170-0538468', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '170-0538468', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-4676475', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '000-2923877', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43',
			'194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0041067-0-17',
			'194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0041067-0-17', '194-0041067-0-17', '194-0119239-1-43', '194-0119239-1-43', '194-0041067-0-17', '194-0119239-1-43'
		);

		foreach ($asuntos as $key => $val) {
			$codigo_asunto = substr($val, 0, 4) . '-' . substr($val, 4, 3);

			$query_cuenta = "SELECT id_cuenta FROM cuenta_banco WHERE numero LIKE '%" . $cuentas[$key] . "%' ";
			$resp = mysql_query($query_cuenta, $this->sesion->dbh) or Utiles::errorSQL($query_cuenta, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_cuenta) = mysql_fetch_array($resp);

			$query_update = "UPDATE contrato LEFT JOIN asunto USING( id_contrato )
													SET contrato.id_cuenta = '$id_cuenta' WHERE asunto.codigo_asunto = '$codigo_asunto' ";
			if (!empty($id_cuenta))
				mysql_query($query_update, $this->sesion->dbh) or Utiles::errorSQL($query_update, __FILE__, __LINE__, $this->sesion->dbh);
		}
	}

}
