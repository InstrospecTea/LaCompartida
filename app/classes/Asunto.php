<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

class Asunto extends Objeto {

	//Etapa actual del proyecto
	var $etapa = null;
	//Primera etapa del proyecto
	var $primera_etapa = null;
	var $monto = null;
	
	//TODO: usar llave unica multiple para editar, si no igual funciona pero no edita asuntos existentes, siempre y cuando:
	//TODO: aplicar logica de campo unique, con soporte para unique de 2 campos (codigo_cliente/glosa_asunto)
	public static $llave_carga_masiva = 'codigo_asunto'; //array('codigo_cliente', 'glosa_asunto');
	public static $campos_carga_masiva = array(
		'codigo_cliente' => array(
			'titulo' => 'Nombre Cliente (vacío para ingresarlo a todos los clientes)',
			'relacion' => 'Cliente',
			'unico' => 'cliente_asunto'
		),
		'glosa_asunto' => array(
			'titulo' => 'Título del Asunto',
			'requerido' => true,
			'unico' => 'cliente_asunto'
		),
		'codigo_asunto_secundario' => 'Código Secundario',
		'id_idioma' => array(
			'titulo' => 'Idioma',
			'relacion' => 'Idioma'
		),
		'id_area_proyecto' => array(
			'titulo' => 'Area Proyecto',
			'relacion' => 'AreaProyecto',
			'creable' => true
		),
		'id_encargado' => array(
			'titulo' => 'Socio encargado del asunto (el que maneja la relación con el cliente)',
			'relacion' => 'UsuarioExt'
		),
		'fono_contacto' => 'Teléfono Contacto',
		'email_contacto' => array(
			'titulo' => 'Email Contacto',
			'tipo' => 'email'
		),
		'activo' => array(
			'titulo' => 'Está Activo (SI/NO)',
			'tipo' => 'bool',
			'defval' => true
		),
		'forma_cobro' => array(
			'titulo' => 'Forma de Cobro',
			'tipo' => array('TASA', 'FLAT FEE', 'RETAINER', 'PROPORCIONAL', 'HITOS')
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
		)
	);
	
	public static $configuracion_reporte = array(
		array(
			'field' => 'codigo_asunto',
			'title' => 'Código',
			'extras' => array(
				'width' => 15
			)
		),
		array(
			'field' => 'glosa_asunto',
			'title' => 'Título',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'codigo_secundario',
			'title' => 'Código Secundario',
			'extras' => array(
				'width' => 15
			)
		),
		array(
			'field' => 'descripcion_asunto',
			'title' => 'Descripción',
			'extras' => array(
				'width' => 45
			)
		),
		array(
			'field' => 'activo',
			'title' => 'Activo',
			'extras' => array(
				'width' => 10
			)
		),
		array(
			'field' => 'horas_trabajadas',
			'title' => 'Horas Trabajadas',
			'format' => 'number',
			'extras' => array(
				'decimals' => 2,
				'width' => 20
			)
		),
		array(
			'field' => 'horas_no_cobradas',
			'title' => 'Horas a cobrar',
			'format' => 'number',
			'extras' => array(
				'decimals' => 2,
				'width' => 20
			)
		),
		array(
			'field' => 'username_ec',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'username_secundario',
			'title' => 'Encargado 2',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'username',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'nombre_ec',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'nombre_secundario',
			'title' => 'Encargado 2',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'nombre',
			'title' => 'Encargado',
			'extras' => array(
				'width' => 30
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
			'field' => 'tipo_proyecto',
			'title' => 'Tipo de Proyecto',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'area_proyecto',
			'title' => 'Area de Práctica',
			'extras' => array(
				'width' => 20
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
			'field' => 'contacto',
			'title' => 'Nombre Contacto',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'fono_contacto',
			'title' => 'Teléfono Contacto',
			'extras' => array(
				'width' => 30
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
			'title' => 'Dirección Contacto',
			'extras' => array(
				'width' => 30
			)
		),
		array(
			'field' => 'glosa_idioma',
			'title' => 'Idioma',
			'extras' => array(
				'width' => 20
			)
		),
		array(
			'field' => 'cobrable',
			'title' => 'Cobrable',
			'extras' => array(
				'width' => 10
			)
		),
		array(
			'field' => 'actividades_obligatorias',
			'title' => 'Act. Obligatorias',
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
		)
	);

	function Asunto($sesion, $fields = "", $params = "") {
		$this->tabla = "asunto";
		$this->campo_id = "id_asunto";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->log_update = true;

	}

	function LoadByCodigo($codigo) {
		$query = "SELECT id_asunto FROM asunto WHERE codigo_asunto='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function LoadByCodigoSecundario($codigo) {
		$query = "SELECT id_asunto FROM asunto WHERE codigo_asunto_secundario='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function LoadByContrato($id_contrato) {
		$query = "SELECT id_asunto FROM asunto WHERE id_contrato = '$id_contrato' LIMIT 1";
		$resp = $this->sesion->pdodbh->query($query)->fetch(PDO::FETCH_ASSOC);
		return $this->Load($resp['id_asunto']);
	}

	function CodigoACodigoSecundario($codigo_asunto) {
		if ($codigo_asunto != '') {
			$query = "SELECT codigo_asunto_secundario FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($codigo_asunto_secundario) = mysql_fetch_array($resp);
			return $codigo_asunto_secundario;
		}
		else
			return false;
	}

	function CodigoSecundarioACodigo($codigo_asunto_secundario) {
		if ($codigo_asunto_secundario != '') {
			$query = "SELECT codigo_asunto FROM asunto WHERE codigo_asunto_secundario = '$codigo_asunto_secundario'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($codigo_asunto) = mysql_fetch_array($resp);
			return $codigo_asunto;
		}
		else
			return false;
	}

	//función que asigna los códigos nuevos
	function AsignarCodigoAsunto($codigo_cliente, $glosa_asunto = "", $secundario = false) {
		$campo = 'codigo_asunto' . ($secundario ? '_secundario' : '');
		$tipo = UtilesApp::GetConf($this->sesion,'TipoCodigoAsunto'); //0: -AAXX, 1: -XXXX, 2: -XXX
		$largo = $tipo == 2 ? 3 : 4;

		$where_codigo_gastos = '';
		if (UtilesApp::GetConf($this->sesion, 'CodigoEspecialGastos')) {
			if ($glosa_asunto=='GASTOS' || $glosa_asunto=='Gastos') {
				return "$codigo_cliente-9999";
			}
			$where_codigo_gastos = "AND asunto.glosa_asunto NOT LIKE 'gastos'";
		}
		$yy = date('y');
		$anio = $tipo ? '' : "AND $campo LIKE '%-$yy%'";

		$where_codigo_cliente = $secundario ?
			"JOIN cliente USING(codigo_cliente) WHERE cliente.codigo_cliente_secundario = '$codigo_cliente'" :
			"WHERE asunto.codigo_cliente = '$codigo_cliente'";

		$query = "SELECT CONVERT(TRIM(LEADING '0' FROM SUBSTRING_INDEX($campo, '-', -1)), UNSIGNED INTEGER) AS x FROM asunto $where_codigo_cliente $anio $where_codigo_gastos ORDER BY x DESC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($codigo) = mysql_fetch_array($resp);
		if(empty($codigo)){
			$codigo = $tipo ? 0 : $yy * 100;
		}

		return sprintf("%s-%0{$largo}d", $codigo_cliente, $codigo + 1);
	}

	function AsignarCodigoAsuntoSecundario($codigo_cliente_secundario, $glosa_asunto = "") {
		return $this->AsignarCodigoAsunto($codigo_cliente_secundario, $glosa_asunto, true);
	}

	//funcion que cambia todos los asuntos de un cliente
	function InsertarCodigoAsuntosPorCliente($codigo_cliente) {
		$query = "SELECT id_asunto FROM asunto WHERE codigo_cliente='$codigo_cliente'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		for ($i = 1; list($id) = mysql_fetch_array($resp); $i++) {
			$this->fields[$this->campo_id] = $id;
			$codigo_asunto = $codigo_cliente . '-' . sprintf("%04d", $i);
			$this->Edit("codigo_asunto", $codigo_asunto);
			$this->Write();
		}
		return true;
	}

	//funcion que actualiza todos los codigos de los clientes existentes (usar una vez para actualizar el registro)
	function ActualizacionCodigosAsuntos() {
		$query = "SELECT codigo_cliente FROM cliente";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		for ($i = 1; list($id) = mysql_fetch_array($resp); $i++) {
			if ($id != 'NULL')
				$this->InsertarCodigoAsuntosPorCliente($id);
		}
		return true;
	}

	function TotalHoras($emitido = true) {
		$where = '';
		if (!$emitido)
			$where = "AND (t2.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado='EN REVISION')";

		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 as hrs_no_cobradas
							FROM trabajo AS t2
							LEFT JOIN cobro on t2.id_cobro=cobro.id_cobro
							WHERE 1 $where
							AND t2.cobrable = 1
							AND t2.codigo_asunto='" . $this->fields['codigo_asunto'] . "'";
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
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
							LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
							LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro
							WHERE 1 $where
							AND trabajo.cobrable = 1
							AND trabajo.codigo_asunto='" . $this->fields['codigo_asunto'] . "' GROUP BY trabajo.codigo_asunto";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_monto_trabajado, $moneda) = mysql_fetch_array($resp);
		return array($total_monto_trabajado, $moneda);
	}

	function AlertaAdministrador($mensaje, $sesion) {
		$query = "SELECT CONCAT_WS(' ',nombre, apellido1, apellido2) as nombre, email
								FROM usuario
							 WHERE activo=1 AND id_usuario = '" . $this->fields['id_encargado'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($nombre, $email) = mysql_fetch_array($resp);

		if (method_exists('Conf', 'GetConf')) {
			$MailAdmin = Conf::GetConf($sesion, 'MailAdmin');
		} else if (method_exists('Conf', 'MailAdmin')) {
			$MailAdmin = Conf::MailAdmin();
		}

		Utiles::Insertar($sesion, __("Alerta") . " " . __("ASUNTO") . " - " . $this->fields['glosa_asunto'] . " | " . Conf::AppName(), $mensaje, $email, $nombre);
		Utiles::Insertar($sesion, __("Alerta") . " " . __("ASUNTO") . " - " . $this->fields['glosa_asunto'] . " | " . Conf::AppName(), $mensaje, $MailAdmin, $nombre);
		return true;
	}

	function Eliminar() {
		if (!$this->Loaded())
			return false;
		#Valida si no tiene algún trabajo relacionado
		$query = "SELECT COUNT(*) FROM trabajo WHERE codigo_asunto = '" . $this->fields['codigo_asunto'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$this->error = __('No se puede eliminar un') . ' ' . __('asunto') . ' ' . __('que tiene trabajos asociados');
			return false;
		}

		$query = "SELECT Count(*) FROM cta_corriente WHERE codigo_asunto = '" . $this->fields['codigo_asunto'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$this->error = __('No se puede eliminar un') . ' ' . __('asunto') . ' ' . __('que tiene gastos asociados');
			return false;
		}

		#solo se puede eliminar asuntos que no tengan cobros asociados
		$query = "SELECT COUNT(*) FROM cobro_asunto WHERE codigo_asunto = '" . $this->fields['codigo_asunto'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$query = "SELECT cobro.id_cobro
									FROM cobro_asunto
									JOIN cobro ON cobro.id_cobro = cobro_asunto.id_cobro
									WHERE cobro_asunto.codigo_asunto = '" . $this->fields['codigo_asunto'] . "'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($cobro) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un') . ' ' . __('asunto') . ' ' . __('que tiene cobros asociados') . ". " .
					__('Cobro asociado') . __(': #' . $cobro);
			return false;
		}

		#solo se pueden eliminar asuntos que no tengan carpetas asociados
		$query = "SELECT COUNT(*) FROM carpeta WHERE codigo_asunto = '" . $this->fields['codigo_asunto'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($count) = mysql_fetch_array($resp);
		if ($count > 0) {
			$query = "SELECT id_carpeta, glosa_carpeta FROM carpeta WHERE codigo_asunto = '" . $this->fields['codigo_asunto'] . "'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utile::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_carpeta, $glosa_carpeta) = mysql_fetch_array($resp);
			$this->error = __('No se puede eliminar un') . ' ' . __('asunto') . ' ' . __('que tiene carpetas asociados. Carpeta asociado: #' . $id_carpeta . ' ( ' . $glosa_carpeta . ' )');
			return false;
		}

		$query = "DELETE FROM asunto WHERE codigo_asunto = '" . $this->fields['codigo_asunto'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	public function QueryReporte($filtros = array()) {
		extract($filtros);
		$wheres = array();

		if ($activo) {
			$wheres[] = "a1.activo = " . ($activo == 'SI' ? 1 : 0);
		}

		if ($codigo_asunto != "") {
			$wheres[] = "a1.codigo_asunto LIKE '$codigo_asunto%'";
		}

		if ($glosa_asunto != "") {
			$nombre = strtr($glosa_asunto, ' ', '%');
			$wheres[] = "a1.glosa_asunto LIKE '%$nombre%'";
		}

		if ($codigo_cliente || $codigo_cliente_secundario) {
			if (UtilesApp::GetConf($this->sesion,'CodigoSecundario')) {
				$wheres[] = "cliente.codigo_cliente_secundario = '$codigo_cliente_secundario'";
				$cliente = new Cliente($this->sesion);
				if ($cliente->LoadByCodigoSecundario($codigo_cliente_secundario)) {
					$codigo_cliente = $cliente->fields['codigo_cliente'];
				}
			} else {
				$wheres[] = "cliente.codigo_cliente = '$codigo_cliente'";
			}
		}

		if ($opc == "entregar_asunto") {
			$wheres[] = "a1.codigo_cliente = '$codigo_cliente' ";
		}

		if ($fecha1 || $fecha2) {
			$wheres[] = "a1.fecha_creacion BETWEEN '" . Utiles::fecha2sql($fecha1) . "' AND '" . Utiles::fecha2sql($fecha2) . " 23:59:59'";
		}

		if ($motivo == "cobros") {
			$wheres[] = "a1.activo='1' AND a1.cobrable = '1'";
		}

		if ($id_usuario) {
			$wheres[] = "a1.id_encargado = '$id_usuario' ";
		}

		if ($id_area_proyecto) {
			$wheres[] = "a1.id_area_proyecto = '$id_area_proyecto' ";
		}

		$on_encargado2 = UtilesApp::GetConf($this->sesion, 'EncargadoSecundario') ? "contrato.id_usuario_secundario" : "a1.id_encargado2";

		$where = empty($wheres) ? '' : (' WHERE ' . implode(' AND ', $wheres));

		//Este query es mejorable, se podría sacar horas_no_cobradas y horas_trabajadas, pero ya no se podría ordenar por estos campos.
		return "SELECT SQL_CALC_FOUND_ROWS
			a1.codigo_asunto,
			a1.codigo_asunto_secundario as codigo_secundario,
			a1.glosa_asunto,
			a1.descripcion_asunto,
			a1.id_moneda,
			IF(a1.activo=1,'SI','NO') as activo,
			a1.fecha_inactivo,
			a1.contacto,
			IF(a1.cobrable=1, 'SI', 'NO') as cobrable,
			a1.fono_contacto,
			a1.email_contacto,
			a1.direccion_contacto,
			IF(a1.actividades_obligatorias=1, 'SI', 'NO') as actividades_obligatorias,
			a1.fecha_creacion,

			tarifa.glosa_tarifa,
			cliente.glosa_cliente,
			prm_tipo_proyecto.glosa_tipo_proyecto AS tipo_proyecto,
			prm_area_proyecto.glosa AS area_proyecto,
			prm_idioma.glosa_idioma,
			contrato.monto,
			contrato.forma_cobro,
			prm_moneda.glosa_moneda,
			prm_moneda.simbolo as simbolo_moneda,
			prm_moneda.cifras_decimales as decimales_moneda,

			usuario.username as username,
			CONCAT(usuario.apellido1, ', ', usuario.nombre) as nombre,

			usuario_ec.username as username_ec,
			CONCAT(usuario_ec.apellido1, ', ', usuario_ec.nombre) as nombre_ec,

			usuario_secundario.username as username_secundario,
			IF(usuario_secundario.username IS NULL, '', CONCAT(usuario_secundario.apellido1, ', ', usuario_secundario.nombre)) as nombre_secundario,

			SUM(TIME_TO_SEC(trabajo.duracion))/3600 AS horas_trabajadas,
			SUM(IF(cobro_trabajo.estado IS NULL OR cobro_trabajo.estado = 'CREADO' OR cobro_trabajo.estado = 'EN REVISION',
				TIME_TO_SEC(trabajo.duracion_cobrada), 0))/3600 AS horas_no_cobradas

			FROM asunto AS a1
			LEFT JOIN cliente ON cliente.codigo_cliente=a1.codigo_cliente
			LEFT JOIN contrato ON contrato.id_contrato = a1.id_contrato
			LEFT JOIN tarifa ON contrato.id_tarifa=tarifa.id_tarifa
			LEFT JOIN prm_idioma ON a1.id_idioma = prm_idioma.id_idioma
			LEFT JOIN prm_tipo_proyecto ON a1.id_tipo_asunto=prm_tipo_proyecto.id_tipo_proyecto
			LEFT JOIN prm_area_proyecto ON a1.id_area_proyecto=prm_area_proyecto.id_area_proyecto
			LEFT JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
			LEFT JOIN usuario ON a1.id_encargado = usuario.id_usuario
			LEFT JOIN usuario as usuario_ec ON contrato.id_usuario_responsable = usuario_ec.id_usuario
			LEFT JOIN usuario as usuario_secundario ON usuario_secundario.id_usuario = $on_encargado2
			LEFT JOIN trabajo ON trabajo.codigo_asunto = a1.codigo_asunto AND trabajo.cobrable = 1
			LEFT JOIN cobro as cobro_trabajo ON trabajo.id_cobro = cobro_trabajo.id_cobro

			$where
			GROUP BY a1.codigo_asunto
			ORDER BY a1.codigo_asunto, a1.codigo_cliente ASC";
	}

	public function DownloadExcel($filtros = array()) {
		$statement = $this->sesion->pdodbh->prepare($this->QueryReporte($filtros));
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);

		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('ASUNTOS');

		//overridear configuraciones del reporte con confs
		$usa_username = UtilesApp::GetConf($this->sesion, 'UsaUsernameEnTodoElSistema');
		$mostrar_encargado_secundario = UtilesApp::GetConf($this->sesion, 'EncargadoSecundario');
		$mostrar_encargado2 = UtilesApp::GetConf($this->sesion, 'AsuntosEncargado2');
		$encargado = $mostrar_encargado_secundario || $mostrar_encargado2;

		$SimpleReport->Config->columns['username']->Visible($usa_username && !$encargado);
		$SimpleReport->Config->columns['username_ec']->Visible($usa_username && $encargado);
		$SimpleReport->Config->columns['username_secundario']->Visible($usa_username && $encargado);
		$SimpleReport->Config->columns['nombre']->Visible(!$usa_username && !$encargado);
		$SimpleReport->Config->columns['nombre_ec']->Visible(!$usa_username && $encargado);
		$SimpleReport->Config->columns['nombre_secundario']->Visible(!$usa_username && $encargado);

		if($mostrar_encargado_secundario){
			$SimpleReport->Config->columns['username_ec']->Title(__('Encargado Comercial'));
			$SimpleReport->Config->columns['nombre_ec']->Title(__('Encargado Comercial'));
			$SimpleReport->Config->columns['username_secundario']->Title(__('Encargado Secundario'));
			$SimpleReport->Config->columns['nombre_secundario']->Title(__('Encargado Secundario'));
		}

		//swapear codigo y codigo_secundario
		if (UtilesApp::GetConf($this->sesion, 'CodigoSecundario')) {
			$SimpleReport->Config->columns['codigo_asunto']->Field('codigo_secundario');
			$SimpleReport->Config->columns['codigo_secundario']->Field('codigo_asunto');
		}

		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save(__('Planilla_Asuntos'));
	}
	
	public function PreCrearDato($data) {
		if(empty($data['codigo_cliente']) || $data['codigo_cliente'] == 'NULL'){
			return $this->IngresarAsuntoGenerico($data);
		}
		
		if (isset($data['codigo_asunto'])) {
			$this->LoadByCodigo($data['codigo_asunto']);
		} else {
			$asunto = null;
			if (!isset($data['id_contrato_cliente'])) {
				$query_contrato = "SELECT id_contrato AS id_contrato_cliente FROM cliente WHERE codigo_cliente = '{$data['codigo_cliente']}'";
				$resp_contrato = $this->sesion->pdodbh->query($query_contrato);
				$contrato = $resp_contrato->fetchAll(PDO::FETCH_ASSOC);
				$data += $contrato[0];
			
				$query_asunto = "SELECT id_asunto, codigo_asunto, id_contrato FROM asunto WHERE codigo_cliente = '{$data['codigo_cliente']}' AND glosa_asunto = '{$data['glosa_asunto']}'";
				$resp_asunto = $this->sesion->pdodbh->query($query_asunto);
				$asunto = $resp_asunto->fetchAll(PDO::FETCH_ASSOC);
			}
			
			if (!empty($asunto)) {
				$data += $asunto[0];
			} else {
				$data['codigo_asunto'] = $this->AsignarCodigoAsunto($data['codigo_cliente']);
				$data['id_contrato'] = $data['id_contrato_cliente'];
			}
		}

		if (!empty($data['forma_cobro']) && $data['forma_cobro'] != 'NULL') {
			if (!empty($data['monto_tarifa_flat'])) {
				$Tarifa = new Tarifa($this->sesion);
				$data['id_tarifa'] = $Tarifa->GuardaTarifaFlat($data['monto_tarifa_flat'], $data['id_moneda']);
			}

			//copio algunos datos a su equivalente en contrato
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
				'id_usuario_responsable' => 'id_encargado'
			);
			foreach ($datos_clon as $nombre_contrato => $nombre_asunto) {
				if (isset($data[$nombre_asunto]) &&
					(!isset($data[$nombre_contrato]) || empty($data[$nombre_contrato]) || $data[$nombre_contrato] == 'NULL')) {
					$data[$nombre_contrato] = $data[$nombre_asunto];
				}
			}
			$this->extra_fields['activo'] = empty($data['activo']) ? 'NO' : 'SI';
		}
		unset($data['monto_tarifa_flat']);

		$campos_contrato = array('id_contrato_cliente', 'id_moneda_monto', 'id_tarifa', 'forma_cobro', 'id_moneda_tramite',
			'id_usuario_responsable', 'id_moneda', 'monto', 'retainer_horas', 'opc_moneda_gastos', 'opc_moneda_total');
		$this->editable_fields = array_diff(array_keys($data), $campos_contrato);
		$this->extra_fields['activo'] = empty($data['activo']) ? 'NO' : 'SI';

		return $data;
	}

	public function PostCrearDato() {
		if (empty($this->extra_fields['forma_cobro']) || $this->extra_fields['forma_cobro'] == 'NULL') {
			return;
		}

		//copiar contrato del cliente y agregar datos especificados aca
		$Contrato = new Contrato($this->sesion);
		$Contrato->Load($this->fields['id_contrato']);
		if($this->fields['id_contrato'] == $this->extra_fields['id_contrato_cliente']){
			unset($Contrato->fields['id_contrato']);
		}
		unset($this->extra_fields['id_contrato_cliente']);

		$Contrato->editable_fields = array_keys($this->extra_fields);
		$Contrato->Fill($this->extra_fields, true);
		$Contrato->Edit('codigo_cliente', $this->fields['codigo_cliente']);
		if ($Contrato->Write()) {
			$this->Edit('id_contrato', $Contrato->fields['id_contrato']);
			$this->Edit('id_contrato_indep', $Contrato->fields['id_contrato']);
			if (!$this->Write()) {
				throw new Exception('No se pudo asociar el contrato al asunto');
			}
		} else {
			throw new Exception('No se pudo guardar el contrato asociado al asunto');
		}
	}

	private function IngresarAsuntoGenerico($data) {
		unset($data['codigo_cliente']);
		
		$query_clientes = "SELECT codigo_cliente, id_contrato AS id_contrato_cliente FROM cliente";
		$resp_clientes = $this->sesion->pdodbh->query($query_clientes);
		$clientes = $resp_clientes->fetchAll(PDO::FETCH_ASSOC);

		foreach ($clientes as $cliente) {
			$this->fields = array();
			$this->changes = array();
			$asunto = $this->PreCrearDato($data + $cliente);

			$this->Fill($asunto, true);
			if ($this->Write()) {
				$this->PostCrearDato();
			} else {
				throw new Exception("Error al guardar asunto genérico {$data['glosa_asunto']} en cliente {$cliente['codigo_cliente']}" .
					(empty($this->error) ? '' : ": {$this->error}"));
			}
		}
	}

}

class ListaAsuntos extends Lista {

	function ListaAsuntos($sesion, $params, $query) {
		$this->Lista($sesion, 'Asunto', $params, $query);
	}

}
