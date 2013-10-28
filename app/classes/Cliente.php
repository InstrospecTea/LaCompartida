<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/Contrato.php';

class Cliente extends Objeto {
	//TODO: carga masiva con hitos (num_hitos -> dividir monto en N hitos)
	public static $llave_carga_masiva = 'glosa_cliente';
	public static $id_carga_masiva = 'codigo_cliente';
	public static $campos_carga_masiva = array(
		'glosa_cliente' => 'Nombre Cliente',
		'id_grupo_cliente' => array(
			'titulo' => 'Grupo',
			'relacion' => 'GrupoCliente',
			'creable' => true
		),
		'codigo_cliente_secundario' => 'Código Secundario',
		'rut' => 'Rut/CNI',
		'rsocial' => 'Razón social',
		'dir_calle' => 'Dirección',
		'factura_ciudad' => 'Ciudad',
		'factura_comuna' => 'Comuna',
		'giro' => 'Giro del Cliente',
		'nombre_contacto' => 'Nombre Contacto',
		'fono_contacto' => 'Teléfono Contacto',
		'mail_contacto' => array(
			'titulo' => 'Email Contacto',
			'tipo' => 'email'
		),
		'activo' => array(
			'titulo' => 'Está Activo (SI/NO)',
			'tipo' => 'bool',
			'defval' => true
		),
		'id_cliente_referencia' => array(
			'titulo' => 'Cliente Referencia',
			'relacion' => 'ClienteReferencia',
			'creable' => true
		),
		'forma_cobro' => array(
			'titulo' => 'Forma de Cobro',
			'tipo' => array('TASA', 'FLAT FEE', 'RETAINER', 'PROPORCIONAL', 'HITOS'),
			'defval' => 'TASA'
		),
		'id_tarifa' => array(
			'titulo' => 'Tarifa',
			'relacion' => 'Tarifa',
			'creable' => true
		),
		'monto_tarifa_flat' => 'Monto Tarifa Flat',
		'id_moneda' => array(
			'titulo' => 'Moneda Tarifa',
			'relacion' => 'Moneda'
		),
		'monto' => 'Monto Fijo',
		'id_moneda_monto' => array(
			'titulo' => 'Moneda Monto Fijo',
			'relacion' => 'Moneda'
		),
		'retainer_horas' => 'Horas Retainer',
		'opc_moneda_gastos' => array(
			'titulo' => 'Moneda Gastos',
			'relacion' => 'Moneda'
		),
		'opc_moneda_total' => array(
			'titulo' => 'Moneda Liquidación',
			'relacion' => 'Moneda'
		),
		'id_cuenta' => array(
			'titulo' => 'Cuenta Bancaria',
			'relacion' => 'CuentaBanco'
		),
		'id_usuario_encargado' => array(
			'titulo' => 'Usuario Encargado',
			'relacion' => 'UsuarioExt'
		),
	);
	public static $configuracion_reporte = array(
		array(
			'field' => 'codigo_cliente',
			'title' => 'Código',
			'extras' => array(
				'width' => 8
			)
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Nombre',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'glosa_grupo_cliente',
			'title' => 'Grupo',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'username',
			'title' => 'Encargado Comercial',
			'extras' => array(
				'width' => 25
			)
		),
		array(
			'field' => 'username_secundario',
			'title' => 'Encargado Secundario',
			'extras' => array(
				'width' => 25
			)
		),
		array(
			'field' => 'usuario_nombre',
			'title' => 'Encargado Comercial',
			'extras' => array(
				'width' => 25
			)
		),
		array(
			'field' => 'usuario_secundario_nombre',
			'title' => 'Encargado Secundario',
			'extras' => array(
				'width' => 25
			)
		),
		array(
			'field' => 'codigo_cliente_secundario',
			'title' => 'Código Secundario',
			'extras' => array(
				'width' => 16
			)
		),
		array(
			'field' => 'rut',
			'title' => 'Rut',
			'extras' => array(
				'width' => 16
			)
		),
		array(
			'field' => 'factura_razon_social',
			'title' => 'Razón Social',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'glosa_tarifa',
			'title' => 'Tarifa',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'glosa_moneda',
			'title' => 'Moneda',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'forma_cobro',
			'title' => 'Forma Cobro',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'monto',
			'title' => 'Monto(FF/R/C)',
			'format' => 'number',
			'extras' => array(
				'symbol' => 'simbolo_moneda',
				'decimals' => 'decimales_moneda',
				'width' => 20
			)
		),
		array(
			'field' => 'factura_direccion',
			'title' => 'Dirección',
			'extras' => array(
				'width' => 40
			)
		),
		array(
			'field' => 'telefono',
			'title' => 'Teléfono',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'contacto',
			'title' => 'Nombre Contacto',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'fono_contacto',
			'title' => 'Teléfono Contacto',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'email_contacto',
			'title' => 'E-mail Contacto',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'direccion_contacto',
			'title' => 'Dirección contacto',
			'extras' => array(
				'width' => 40
			)
		),
		array(
			'field' => 'glosa_cliente_referencia',
			'title' => 'Referencia',
			'extras' => array(
				'width' => 25
			)
		),
		array(
			'field' => 'fecha_creacion',
			'title' => 'Fecha Creación',
			'format' => 'date',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'fecha_inactivo',
			'title' => 'Fecha Inactivo',
			'format' => 'date',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'activo',
			'title' => 'Activo',
			'extras' => array(
				'width' => 10
			)
		),
	);

	function Cliente($sesion, $fields = "", $params = "") {
		$this->tabla = "cliente";
		$this->campo_id = "id_cliente";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = true;
	}

	function LoadByCodigo($codigo) {
		$query = "SELECT * FROM {$this->tabla} WHERE codigo_cliente = '$codigo'";
		return $this->LoadWithQuery($query);
	}

	function LoadByCodigoSecundario($codigo) {
		$query = "SELECT * FROM {$this->tabla} WHERE codigo_cliente_secundario = '$codigo'";
		return $this->LoadWithQuery($query);
	}

	function LoadByGlosa($glosa) {
		$query = "SELECT * FROM {$this->tabla} WHERE glosa_cliente = '$glosa'";
		return $this->LoadWithQuery($query);
	}

	// Esto debiera ir al framework
	function LoadWithQuery($query) {
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

		if ($this->fields = mysql_fetch_assoc($resp))   {
			$this->loaded = true;
			return true;
		}
		return false;
	}

	function IntentaInsertar() {

		$this->error = "";

		if (!$this->Check())
			return false;

		if ($this->Loaded()) {
			$query = "UPDATE " . $this->tabla . " SET ";
			if ($this->guardar_fecha)
				$query .= "fecha_modificacion=NOW(),";

			$c = 0;
			foreach ($this->fields as $key => $val) {
				if ($this->changes[$key]) {
					$do_update = true;
					if ($c > 0)
						$query .= ",";
					if ($val != 'NULL')
						$query .= "$key = '" . addslashes($val) . "'";
					else
						$query .= "$key = NULL ";
					$c++;
				}
				if ($this->logear[$key]) {   // log data
					$query_log = "INSERT INTO log_db SET id_field = '" . $this->fields[$this->campo_id] . "', titulo_tabla = '" . $this->tabla . "', campo_tabla = '" . $key . "', fecha = NOW(), usuario = '" . $this->sesion->usuario->fields['id_usuario'] . "', valor_antiguo = '" . $this->valor_antiguo[$key] . "', valor_nuevo = '" . addslashes($val) . "' ";
					$resp_log = mysql_query($query_log, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
					$this->logear[$key] = false;
				}
			}

			$query .= " WHERE " . $this->campo_id . "='" . $this->fields[$this->campo_id] . "'";
			if ($do_update) {
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			}
			return true;
		} else {
			$query = "INSERT INTO " . $this->tabla . " SET ";
			if ($this->guardar_fecha)
				$query .= "fecha_creacion=NOW(),";

			$c = 0;
			foreach ($this->fields as $key => $val) {
				if ($this->changes[$key]) {
					if ($c > 0)
						$query .= ",";
					if ($val != 'NULL')
						$query .= "$key = '" . addslashes($val) . "'";

					else
						$query .= "$key = NULL ";

					$c++;
				}
			}

			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			$this->fields[$this->campo_id] = mysql_insert_id($this->sesion->dbh);
			#Utiles::CrearLog($this->sesion, "reserva", $this->fields['id_reserva'], "INSERTAR","",$query);
		}
		return true;
	}

	function InsertarDatos(/* Argumentos */) {
		// Copia de pedazo de codigo en agregar_cliente.php
	}

	//funcion que asigna el nuevo codigo automatico para un cliente
	function AsignarCodigoCliente() {
		if (UtilesApp::GetConf($this->sesion, 'EsPRC')) {
			$query = "SELECT 1*codigo_cliente AS x FROM cliente WHERE codigo_cliente NOT IN ('2000','2001','2002','2003') ORDER BY x DESC LIMIT 1";
		} else {
			$query = "SELECT 1*codigo_cliente AS x FROM cliente ORDER BY x DESC LIMIT 1";
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);
		$f = $codigo + 1;
		
        if ( UtilesApp::GetConf($this->sesion, 'MascaraCodigoCliente')) {
            $codigo_cliente = sprintf("%04d", $f);
        } else {
            $codigo_cliente = sprintf("%06d", $f);
        }
		return $codigo_cliente;
	}

	//funcion que actualiza los codigos de los clientes (usar una vez para actualizar el registro)
	function ActualizacionCodigosClientes() {
		$query = "SELECT id_cliente FROM cliente";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		for ($i = 1; list($id) = mysql_fetch_array($resp); $i++) {
			$this->fields[$this->campo_id] = $id;
			$codigo_cliente = sprintf("%04d", $i);
			$this->Edit("codigo_cliente", $codigo_cliente);
			$this->Write();
		}
		return true;
	}

	/*
	  La cuenta corriente funciona sólo restando de los ingresos para gastos,
	  todos los montos_descontados(monto real en pesos) de cada gasto ingresado
	 */

	function CodigoACodigoSecundario($codigo_cliente) {
		if ($codigo_cliente != '') {
			$query = "SELECT codigo_cliente_secundario FROM cliente WHERE codigo_cliente = '$codigo_cliente'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($codigo_cliente_secundario) = mysql_fetch_array($resp);
			return $codigo_cliente_secundario;
		}
		else
			return false;
	}

	function CodigoSecundarioACodigo($codigo_cliente_secundario) {
		if ($codigo_cliente_secundario != '') {
			$query = "SELECT codigo_cliente FROM cliente WHERE codigo_cliente_secundario = '$codigo_cliente_secundario'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($codigo_cliente) = mysql_fetch_array($resp);
			return $codigo_cliente;
		}
		else
			return false;
	}

	function TotalCuentaCorriente($lista_asuntos = '', $codigo_cliente = '') {
		$where = 1;
		if ($lista_asuntos != '')
			$where .= " AND codigo_asunto IN ('$lista_asuntos') ";

		$codigo_cliente = $this->fields['codigo_cliente'];
		$query = "SELECT SUM(ingreso*tipo_cambio), SUM(egreso*tipo_cambio)
							FROM cta_corriente JOIN prm_moneda on prm_moneda.id_moneda =cta_corriente.id_moneda
							WHERE $where AND codigo_cliente ='$codigo_cliente'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($ingresos, $egresos) = mysql_fetch_array($resp);
		$total = $ingresos - $egresos;
#		$total = $total / $this->TipoCambio();
		return $total;
	}

	function TotalHoras($emitido = true) {
		$where = '';
		if (!$emitido)
			$where = "AND (t2.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado='EN REVISION')";

		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 as hrs_no_cobradas
							FROM trabajo AS t2
							JOIN asunto ON t2.codigo_asunto = asunto.codigo_asunto
							JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
							WHERE 1 $where
							AND t2.cobrable = 1
							AND asunto.codigo_cliente='" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_horas_no_cobradas) = mysql_fetch_array($resp);
		return $total_horas_no_cobradas;
	}

	function TotalMonto($emitido = true) {
		$where = '';
		if (!$emitido)
			$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";

		$query = "SELECT SUM((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa), prm_moneda.simbolo
							FROM trabajo
							JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
							JOIN contrato ON asunto.id_contrato = contrato.id_contrato
							JOIN cliente ON asunto.codigo_cliente = cliente.codigo_cliente
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
							LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
							LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro
							WHERE 1 $where
							AND trabajo.cobrable = 1
							AND cliente.codigo_cliente ='" . $this->fields['codigo_cliente'] . "' GROUP BY cliente.codigo_cliente";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_monto_trabajado, $moneda) = mysql_fetch_array($resp);
		return array($total_monto_trabajado, $moneda);
	}

	function InactivarAsuntos() {
		$query = "UPDATE asunto SET activo = 0 WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function TipoCambio() {
		$query = "SELECT tipo_cambio,glosa_moneda,simbolo FROM prm_moneda WHERE id_moneda = " . $this->fields['id_moneda'];
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cliente_tipo_cambio, $glosa_moneda, $simbolo) = mysql_fetch_array($resp);
		$this->fields['tipo_cambio'] = $cliente_tipo_cambio;
		$this->fields['glosa_moneda'] = $glosa_moneda;
		$this->fields['simbolo'] = $simbolo;
		return $cliente_tipo_cambio;
	}

	#retorna el id del ultimo ingreso que se le hace a la cuenta corriente del cliente.
	# Creo que esta función no se ocupa

	function UltimoIngreso() {
		$cod = $this->fields[codigo_cliente];
		$query = "SELECT MAX(id_movimiento) FROM cta_corriente WHERE codigo_cliente = '$cod' AND ingreso IS NOT NULL";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_ingreso) = mysql_fetch_array($resp);
		return $id_ingreso;
	}

	# eliminar clientes

	function Eliminar() {
		if (!$this->Loaded())
			return false;

		#solo se puede eliminar clientes que no tengan cobros asociados
		$query = "SELECT COUNT(*) FROM cobro WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$query = "SELECT id_cobro FROM cobro WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($cobro) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un') . ' ' . __('cliente') . ' ' . __('que tiene cobros asociados') . ". " . __('Cobro asociado') . ': #' . $cobro;
			return false;
		}
		#Valida si no tiene algún asunto relacionado
		$query = "SELECT COUNT(*) FROM asunto WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$query = "SELECT glosa_asunto FROM asunto WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($asunto) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un') . ' ' . __('cliente') . ' ' . __('que tiene un') . ' ' . __('asunto') . ' ' . __('asociado.') . ' ' . __('Asunto') . ' ' . __('asociado:') . ' ' . $asunto;
			return false;
		}
		#Valida que no tenga documentos asociados
		$query = "SELECT COUNT(*) FROM archivo INNER JOIN contrato ON archivo.id_contrato=contrato.id_contrato WHERE contrato.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);

		$query = "SELECT COUNT(*) FROM cta_corriente WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$this->error = __('No se puede eliminar un') . ' ' . __('cliente') . ' ' . __('que tiene gastos asociados');
			return false;
		}

		$query = "SELECT COUNT(*) FROM documento WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count_doc) = mysql_fetch_array($resp);
		if ($count > 0 || $count_doc > 0) {
			$this->error = __('No se puede eliminar un') . ' ' . __('cliente') . ' ' . __('que tiene un') . ' ' . __('documento') . ' ' . __('asociado.');
			return false;
		}
		$query = "DELETE modificaciones_contrato FROM modificaciones_contrato JOIN contrato USING(id_contrato) WHERE contrato.codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM contrato WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM cliente WHERE codigo_cliente = '" . $this->fields['codigo_cliente'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/**
	 * @var UsuarioExt
	 */
	var $Encargado;

	/**
	 * @var Contrato
	 */
	var $Contrato;

	public function Encargado() {
		if (!$this->Loaded()) {
			return null;
		}
		if (!isset($this->Encargado)) {
			$this->Encargado = new UsuarioExt($this->sesion);
			$this->Encargado->LoadId($this->fields['id_usuario_encargado']);
		}

		return $this->Encargado;
	}

	public function Contrato() {
		if (!$this->Loaded()) {
			return null;
		}
		if (!isset($this->Contrato)) {
			$this->Contrato = new Contrato($this->sesion);
			$this->Contrato->Load($this->fields['id_contrato']);
		}

		return $this->Contrato;
	}

	public function FillTemplate() {
		if (!$this->Loaded()) {
			return '';
		}

		$datos = array(
			'Nombre' => $this->fields['glosa_cliente'],
			'NombreContacto' => $this->fields['nombre_contacto'],
			'Encargado' => $this->Encargado()->FillTemplate(),
			'Contrato' => $this->Contrato()->FillTemplate()
		);

		return $datos;
	}

	public function QueryReporte($filtros = array()) {
		extract($filtros);
		$wheres = array();
		if ($glosa_cliente != '') {
			$nombre = strtr($glosa_cliente, ' ', '%');
			$wheres[] = "cliente.glosa_cliente LIKE '%$nombre%'";
		}
		if ($codigo != '') {
			$wheres[] = "cliente.codigo_cliente = '$codigo'";
		}
		if ($id_grupo_cliente > 0) {
			$wheres[] = "cliente.id_grupo_cliente = " . $id_grupo_cliente;
		}
		if (!empty($fecha1)) {
			$wheres[] = "cliente.fecha_creacion >= '" . Utiles::fecha2sql($fecha1) . "'";
		}
		if (!empty($fecha2)) {
			$wheres[] = "cliente.fecha_creacion <= '" . Utiles::fecha2sql($fecha2) . "'";
		}
		if ($solo_activos == 1) {
			$wheres[] = "cliente.activo = 1 ";
		}

		$where = empty($wheres) ? '' : (' WHERE ' . implode(' AND ', $wheres));
		$query = "SELECT SQL_CALC_FOUND_ROWS
				cliente.codigo_cliente as codigo_cliente,
				cliente.codigo_cliente_secundario,
				cliente.glosa_cliente,
				grupo_cliente.glosa_grupo_cliente,
				moneda.glosa_moneda,
				CONCAT(usuario.nombre,' ',usuario.apellido1) as usuario_nombre,
				usuario.username,
				CONCAT(usuario_secundario.nombre,' ',usuario_secundario.apellido1) as usuario_secundario_nombre,
				usuario_secundario.username as username_secundario,
				contrato.factura_razon_social,
				CONCAT(contrato.cod_factura_telefono,' ',contrato.factura_telefono) as telefono,
				contrato.factura_direccion,
				contrato.rut,
				CONCAT_WS(' ',contrato.contacto,contrato.apellido_contacto) as contacto,
				contrato.fono_contacto,
				contrato.email_contacto,
				contrato.direccion_contacto,
				contrato.forma_cobro,
				contrato.monto,
				prm_cliente_referencia.glosa_cliente_referencia,
				tarifa.glosa_tarifa,
				contrato.id_moneda_monto,
				cliente.fecha_creacion,
				cliente.fecha_inactivo,
				IF(cliente.activo = 1, 'Si', 'No') as activo,
				moneda_monto.simbolo as simbolo_moneda,
				moneda_monto.cifras_decimales as decimales_moneda
			FROM cliente
				LEFT JOIN grupo_cliente USING (id_grupo_cliente)
				LEFT JOIN prm_cliente_referencia ON cliente.id_cliente_referencia = prm_cliente_referencia.id_cliente_referencia
				LEFT JOIN contrato ON cliente.id_contrato = contrato.id_contrato
				LEFT JOIN prm_moneda AS moneda ON contrato.id_moneda = moneda.id_moneda
				LEFT JOIN prm_moneda AS moneda_monto ON contrato.id_moneda_monto = moneda_monto.id_moneda
				LEFT JOIN usuario ON contrato.id_usuario_responsable = usuario.id_usuario
				LEFT JOIN usuario as usuario_secundario ON contrato.id_usuario_secundario = usuario_secundario.id_usuario
				LEFT JOIN tarifa ON contrato.id_tarifa=tarifa.id_tarifa
			$where ORDER BY cliente.glosa_cliente ASC";
		return $query;
	}

	public function DownloadExcel($filtros = array()) {
		$statement = $this->sesion->pdodbh->prepare($this->QueryReporte($filtros));
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);

		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('CLIENTES');

		//overridear configuraciones del reporte con confs
		$usa_username = !!UtilesApp::GetConf($this->sesion, 'UsaUsernameEnTodoElSistema');
		//siempre mostrar esto asi? convertirla en conf por defecto? que sean solo 2 columnas y cambiarles el field?
		$SimpleReport->Config->columns['username']->Visible($usa_username);
		$SimpleReport->Config->columns['username_secundario']->Visible($usa_username);
		$SimpleReport->Config->columns['usuario_nombre']->Visible(!$usa_username);
		$SimpleReport->Config->columns['usuario_secundario_nombre']->Visible(!$usa_username);

		//swapear codigo y codigo_secundario
		if(UtilesApp::GetConf($this->sesion, 'CodigoSecundario')){
			$SimpleReport->Config->columns['codigo_cliente']->Field('codigo_cliente_secundario');
			$SimpleReport->Config->columns['codigo_cliente_secundario']->Field('codigo_cliente');
		}

		//estos confs hacen q no se ocupen estos datos asi q no tiene sentido mostrarlos
		if (!UtilesApp::GetConf($this->sesion, 'ClienteReferencia')) {
			$SimpleReport->Config->columns['glosa_cliente_referencia']->Visible(false);
		}
		if(!UtilesApp::GetConf($this->sesion, 'EncargadoSecundario')){
			$SimpleReport->Config->columns['username_secundario']->Visible(false);
			$SimpleReport->Config->columns['usuario_secundario_nombre']->Visible(false);
		}

		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save(__('Planilla_Clientes'));
	}

	public function PreCrearDato($data) {
		if(isset($data['codigo_cliente'])) {
			$this->LoadByCodigo($data['codigo_cliente']);
		} else {
			$data['codigo_cliente'] = $this->AsignarCodigoCliente();
		}

		if (!empty($data['monto_tarifa_flat'])) {
			$Tarifa = new Tarifa($this->sesion);
			$data['id_tarifa'] = $Tarifa->GuardaTarifaFlat($data['monto_tarifa_flat'], $data['id_moneda']);
		}
		unset($data['monto_tarifa_flat']);

		//no intento guardar los campos que son de la tabla contrato
		$campos_contrato = array('id_moneda_monto', 'id_tarifa', 'factura_ciudad', 'factura_comuna', 'id_cuenta',
			'forma_cobro', 'id_moneda', 'monto', 'retainer_horas', 'opc_moneda_gastos', 'opc_moneda_total');
		$this->editable_fields = array_diff(array_keys($data), $campos_contrato);

		//copio algunos datos de la tabla cliente a su equivalente en contrato
		if (empty($data['id_moneda']) || $data['id_moneda'] == 'NULL') {
			$data['id_moneda'] = 1;
			$monedas = array('opc_moneda_total', 'id_moneda_monto', 'opc_moneda_gastos', 'id_moneda_tramite');
			foreach ($monedas as $moneda) {
				if (!empty($data[$moneda]) && $data[$moneda] != 'NULL') {
					$data['id_moneda'] = $data[$moneda];
					break;
				}
			}
		}
		$datos_clon = array(
			'id_moneda_tramite' => 'id_moneda',
			'id_moneda_monto' => 'id_moneda',
			'opc_moneda_total' => 'id_moneda',
			'opc_moneda_gastos' => 'id_moneda',
			'id_usuario_responsable' => 'id_usuario_encargado',
			'factura_razon_social' => 'rsocial',
			'factura_giro' => 'giro',
			'factura_direccion' => 'dir_calle',
			'factura_telefono' => 'fono_contacto',
			'email_contacto' => 'mail_contacto',
			'contacto' => 'nombre_contacto',
			'direccion_contacto' => 'dir_calle'
		);
		foreach ($datos_clon as $nombre_contrato => $nombre_cliente) {
			if (isset($data[$nombre_cliente]) &&
				(!isset($data[$nombre_contrato]) || empty($data[$nombre_contrato]) || $data[$nombre_contrato] == 'NULL')) {
				$data[$nombre_contrato] = $data[$nombre_cliente];
			}
		}
		$this->extra_fields['activo'] = empty($data['activo']) ? 'NO' : 'SI';
		$this->extra_fields['rut'] = $data['rut'];

		if (Conf::GetConf($this->sesion, 'NombreIdentificador') == 'RUT') {
			$rutdv = explode('-', $data['rut']);
			$data['rut'] = preg_replace('/\D/', '', $rutdv[0]);
			$data['dv'] = trim($rutdv[1]);
			$this->editable_fields[] = 'dv';
		}

		return $data;
	}

	public function PostCrearDato() {
		$id_contrato = $this->fields['id_contrato'];
		$Contrato = new Contrato($this->sesion);
		if (!empty($id_contrato)) {
			$Contrato->Load($id_contrato);
		}

		$Contrato->editable_fields = array_keys($this->extra_fields);
		$Contrato->Fill($this->extra_fields, true);
		$Contrato->Edit('codigo_cliente', $this->fields['codigo_cliente']);
		if ($Contrato->Write()) {
			if (!$id_contrato) {
				$this->Edit('id_contrato', $Contrato->fields['id_contrato']);
				if (!$this->Write()) {
					throw new Exception('No se pudo asociar el contrato al cliente');
				}
			}
		} else {
			throw new Exception('No se pudo guardar el contrato asociado al cliente');
		}
	}

	/**
	 * Find all active clients
	 * Return an array with next elements:
	 * 	code (secondary if used), name and address
	 */
	public function findAllActive($timestamp = 0) {
		$active = 1;
		$clients = array();
		$sql_select_client_code = '`client`.`codigo_cliente`';

		// find if the client used secondary code
		if (UtilesApp::GetConf($this->sesion, 'CodigoSecundario') == '1') {
			$sql_select_client_code = '`client`.`codigo_cliente_secundario`';
		}

		$sql = "SELECT $sql_select_client_code AS `code`, `client`.`glosa_cliente` AS `name`,
			`contract`.`direccion_contacto` AS `address`
			FROM `cliente` AS `client`
				INNER JOIN `contrato` AS `contract` ON `contract`.`id_contrato`=`client`.`id_contrato`
			WHERE `client`.`activo`=:active AND (`client`.`fecha_touch`>=:timestamp OR `client`.`fecha_creacion`>=:timestamp)
			ORDER BY `client`.`glosa_cliente` ASC";

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->bindParam('active', $active);
		$Statement->bindParam('timestamp', date('Y-m-d', $timestamp));

		$Statement->execute();

		while ($client = $Statement->fetch(PDO::FETCH_OBJ)) {
			array_push($clients,
				array(
					'code' => $client->code,
					'name' => !empty($client->name) ? $client->name : null,
					'address' => !empty($client->address) ? $client->address : null
				)
			);
		}

		return $clients;
	}
}

class ListaClientes extends Lista {

	function ListaClientes($sesion, $params, $query) {
		$this->Lista($sesion, 'Cliente', $params, $query);
	}

}
