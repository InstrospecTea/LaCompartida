<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once 'Cliente.php';
require_once 'Asunto.php';

class Gasto extends Objeto {

	public static $configuracion_reporte = array (
		array (
			'field' => 'id_movimiento',
			'title' => 'N°',
			'visible' => false
		),
		array (
			'field' => 'fecha',
			'format' => 'date',
			'title' => 'Fecha',
		),
		array (
			'field' => 'codigo_cliente',
			'title' => 'Código Cliente',
		),
		array (
			'field' => 'glosa_cliente',
			'title' => 'Cliente',
		),
		array (
			'field' => 'codigo_asunto',
			'title' => 'Código Asunto',
		),
		array (
			'field' => 'glosa_asunto',
			'title' => 'Asunto',
		),
		array (
			'field' => 'encargado_comercial',
			'title' => 'Encargado Comercial',
		),
		array (
			'field' => 'usuario_ingresa',
			'title' => 'Ingresado por',
		),
		array (
			'field' => 'usuario_ordena',
			'title' => 'Ordenado por',
		),
		array (
			'field' => 'tipo',
			'title' => 'Tipo',
		),
		array (
			'field' => 'descripcion',
			'title' => 'Descripción',
		),
		array (
			'field' => 'simbolo',
			'title' => 'Símbolo Moneda',
		),
		array (
			'field' => 'egreso',
			'format' => 'number',
			'title' => 'Egreso',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'ingreso',
			'format' => 'number',
			'title' => 'Ingreso',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'monto_cobrable',
			'format' => 'number',
			'title' => 'Monto Cobrable',
			'extras' =>
			array (
				'symbol' => 'simbolo',
				'subtotal' => 'simbolo'
			),
		),
		array (
			'field' => 'con_impuesto',
			'title' => 'Con Impuesto',
		),
		array (
			'field' => 'id_cobro',
			'title' => 'N° Liquidación',
		),
		array (
			'field' => 'estado_cobro',
			'title' => 'Estado Liquidación',
		),
		array (
			'field' => 'cobrable',
			'title' => 'Cobrable',
		),
		array (
			'field' => 'numero_documento',
			'title' => 'N° Documento',
		),
		array (
			'field' => 'rut_proveedor',
			'title' => 'RUT Proveedor',
		),
		array (
			'field' => 'nombre_proveedor',
			'title' => 'Proveedor',
		),
		array (
			'field' => 'tipo_documento_asociado',
			'title' => 'Tipo Documento Asociado',
		),
		array (
			'field' => 'fecha_documento_asociado',
			'title' => 'Fecha Documento Asociado',
		),
		array (
			'field' => 'codigo_documento_asociado',
			'title' => 'N° Documento Asociado',
		),
	);

	function Gasto($sesion, $fields = "", $params = "") {
		$this->tabla = "cta_corriente";
		$this->campo_id = "id_movimiento";
		#$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function Check() {
		# Los gastos dependiendo de si son generales o no, van a diferentes tablas.
		# La tabla por defecto es cta_corriente
		# Además a los gastos asociados a un asunto se les calcula un monto descontado que es con la tasa de cambio del dia en que se anoto. Esto es para que no cambie el monto que se descuenta si es que cambia la tasa.
		if ($this->changes[general] == 1) {
			$this->tabla = "gasto_general";
			$this->campo_id = "id_gasto_general";
			unset($this->changes[general]);
		} else {
#			$this->tabla = "";
#			$this->campo_id = "id_gasto";

			if ($this->fields[id_moneda] > 0) {
				$query = "SELECT tipo_cambio FROM prm_moneda WHERE id_moneda = " . $this->fields[id_moneda];
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($tasa) = mysql_fetch_array($resp);
			}

#			$monto = $this->fields[ingreso] * $tasa;
#			$asunto = new Asunto($this->sesion);
#			$asunto->LoadByCodigo($this->fields[codigo_asunto]);
#			$cliente = new Cliente($this->sesion);
#			$cliente->LoadByCodigo($asunto->fields[codigo_cliente]);
#			if($cliente)
#				$cuenta_corriente = $cliente->TotalCuentaCorriente();
#            $this->Edit("ingreso_descontado",$monto);

			unset($this->changes['general']);
		}

		return true;
	}

	function Load($id) {
		$this->Check();
		return Objeto::Load($id);
	}

	function Write() {
		if ($this->Loaded()) {
			$query = "SELECT fecha, codigo_cliente, codigo_asunto, egreso, ingreso, monto_cobrable, descripcion, id_moneda
					FROM cta_corriente WHERE id_movimiento = " . $this->fields['id_movimiento'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($fecha, $codigo_cliente, $codigo_asunto, $egreso, $ingreso, $monto_cobrable, $descripcion, $id_moneda) = mysql_fetch_array($resp);

			if ($this->fields['egreso'] > 0) {
				$query_tipo_ingreso = $this->fields['egreso'];
				$query_valor_ingreso = $egreso;
			} else if ($this->fields['ingreso'] > 0) {
				$query_tipo_ingreso = $this->fields['ingreso'];
				$query_valor_ingreso = $ingreso;
			}

			$query = "INSERT INTO gasto_historial
						( id_movimiento, fecha, id_usuario, accion, fecha_movimiento, fecha_movimiento_modificado, codigo_cliente, codigo_cliente_modificado, codigo_asunto, codigo_asunto_modificado, ingreso, ingreso_modificado, monto_cobrable, monto_cobrable_modificado, descripcion, descripcion_modificado, id_moneda, id_moneda_modificado)
					VALUES( " . $this->fields['id_movimiento'] . ", NOW(), '" . $this->sesion->usuario->fields['id_usuario'] . "', 'MODIFICAR', '" . $fecha . "', '" . $this->fields['fecha'] . "', '" . $codigo_cliente . "', '" . $this->fields['codigo_cliente'] . "', '" . $codigo_asunto . "', '" . $this->fields['codigo_asunto'] . "', '" . $query_valor_ingreso . "', '" . $query_tipo_ingreso . "', '" . $monto_cobrable . "', '" . $this->fields['monto_cobrable'] . "', '" . addslashes($descripcion) . "', '" . addslashes($this->fields['descripcion']) . "', " . $id_moneda . ", " . $this->fields['id_moneda'] . ")";
		} else {
			$query = "SELECT MAX(id_movimiento) FROM cta_corriente";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_movimiento) = mysql_fetch_array($resp);
			$id_movimiento++;

			if ($this->fields['egreso'] > 0) {
				$query_tipo_ingreso = $this->fields['egreso'];
			} else if ($this->fields['ingreso'] > 0) {
				$query_tipo_ingreso = $this->fields['ingreso'];
			}

			$query = "INSERT INTO gasto_historial
						( id_movimiento, fecha, id_usuario, accion, fecha_movimiento_modificado, codigo_cliente_modificado, codigo_asunto_modificado, ingreso_modificado, monto_cobrable_modificado, descripcion_modificado, id_moneda_modificado)
					VALUES( " . $id_movimiento . ", NOW(), '" . $this->sesion->usuario->fields['id_usuario'] . "', 'CREAR', '" . $this->fields['fecha'] . "', '" . $this->fields['codigo_cliente'] . "', '" . $this->fields['codigo_asunto'] . "','" . $query_tipo_ingreso . "', '" . $this->fields['monto_cobrable'] . "', '" . addslashes($this->fields['descripcion']) . "', " . $this->fields['id_moneda'] . ")";
		}
		if (parent::Write()) {
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			return true;
		}
		return false;
	}

	function Eliminar() {
		if ($this->Loaded()) {
			$query = "DELETE FROM cta_corriente WHERE id_movimiento=" . $this->fields['id_movimiento'];
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			if ($resp) {
				if ($this->fields['egreso'] > 0) {
					$query_tipo_ingreso = $this->fields['egreso'];
				} else if ($this->fields['ingreso'] > 0) {
					$query_tipo_ingreso = $this->fields['ingreso'];
				}

				$query = "INSERT INTO gasto_historial
								( id_movimiento, fecha, accion, id_usuario, fecha_movimiento, codigo_cliente, codigo_asunto, ingreso, monto_cobrable, descripcion, id_moneda)
							VALUES( " . $this->fields['id_movimiento'] . ", NOW(), 'ELIMINAR', " . $this->sesion->usuario->fields['id_usuario'] . ", '" . $this->fields['fecha'] . "', '" . $this->fields['codigo_cliente'] . "', '" . $this->fields['codigo_asunto'] . "', '" . $query_tipo_ingreso . "', '" . $this->fields['monto_cobrable'] . "', '" . addslashes($this->fields['descripcion']) . "', " . $this->fields['id_moneda'] . ")";
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
		else
			return false;

		return true;
	}

	/*
	  Guarda los datos de pago de los gastos cuando en paso 6 cobro se chequea como pagados.
	 */

	function GuardaPagoGastosDelCobro($id_cobro, $fecha_pago, $documento_pago, $id) {
		#Actualiza los egresos segun sus datos
		$query = "UPDATE cta_corriente SET fecha_pago = '$fecha_pago', documento_pago = '$documento_pago', monto_pago = egreso, pagado = 1, id_movimiento_pago = '$id'
				WHERE id_cobro = '$id_cobro' AND id_movimiento_pago IS NULL";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/*
	  Elimina Ingreso desde un gasto asociado, verificando que no existan otros gastos asociados a el.
	 */

	function EliminaIngreso($id_gasto) {
		$query = "SELECT COUNT(*) FROM cta_corriente WHERE id_movimiento_pago = '" . $this->fields[id_movimiento] . "'
					AND id_movimiento != '$id_gasto'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cont) = mysql_fetch_array($resp);
		if ($cont > 0) {
			return false;
		} else {
			$query = "DELETE FROM cta_corriente WHERE id_movimiento = '" . $this->fields[id_movimiento] . "' LIMIT 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			return true;
		}
	}

	/**
	 * Descarga el reporte excel básico según configuraciones
	 */
	public function DownloadExcel($search_query) {
		require_once Conf::ServerDir() . '/classes/Reportes/SimpleReport.php';

		$SimpleReport = new SimpleReport($this->sesion);
		$SimpleReport->SetRegionalFormat(UtilesApp::ObtenerFormatoIdioma($this->sesion));
		$SimpleReport->LoadConfiguration('GASTOS');

		$results = $this->sesion->pdodbh->query($search_query)->fetchAll(PDO::FETCH_ASSOC);
		$SimpleReport->LoadResults($results);

		$writer = SimpleReport_IOFactory::createWriter($SimpleReport, 'Spreadsheet');
		$writer->save(__('Gastos'));
	}

	public function SearchQuery($where) {
		return "SELECT SQL_BIG_RESULT SQL_NO_CACHE
				cta_corriente.id_movimiento,
				DATE_FORMAT(cta_corriente.fecha, '%Y-%m-%d') AS fecha,
				DATE_FORMAT(cta_corriente.fecha_creacion, '%Y-%m-%d') AS fecha_creacion,
				cta_corriente.codigo_cliente,
				cliente.glosa_cliente,
				asunto.codigo_asunto,
				asunto.glosa_asunto,
				CONCAT(u_encargado.apellido1, ', ', u_encargado.nombre) AS encargado_comercial,
				CONCAT(u_ingresa.apellido1, ', ', u_ingresa.nombre) AS usuario_ingresa,
				CONCAT(u_ordena.apellido1, ', ', u_ordena.nombre) AS usuario_ordena,
				u_encargado.username AS username_encargado,
				u_ingresa.username AS username_ingresa,
				u_ordena.username AS username_ordena,
				prm_cta_corriente_tipo.glosa AS tipo,
				cta_corriente.descripcion,
				prm_moneda.simbolo,
				cta_corriente.egreso,
				cta_corriente.ingreso,
				IF(
					monto_cobrable = ingreso,
					monto_cobrable * (-1),
					monto_cobrable
				) AS monto_cobrable,
				cta_corriente.con_impuesto,
				cta_corriente.id_cobro,
				IFNULL(cobro.estado, 'SIN COBRO') AS estado_cobro,
				IF(cta_corriente.cobrable = 1,'SI', 'NO') AS cobrable,
				cta_corriente.numero_documento,
				prm_proveedor.rut AS rut_proveedor,
				prm_proveedor.glosa AS nombre_proveedor,
				prm_tipo_documento_asociado.glosa AS tipo_documento_asociado,
				cta_corriente.fecha_factura AS fecha_documento_asociado,
				cta_corriente.codigo_factura_gasto AS codigo_documento_asociado,
				prm_moneda.cifras_decimales,
				cta_corriente.numero_ot,
				cta_corriente.id_moneda,
				prm_moneda.codigo AS codigo_moneda,
				cta_corriente.con_impuesto
			FROM cta_corriente
			LEFT JOIN cliente ON cta_corriente.codigo_cliente = cliente.codigo_cliente
			LEFT JOIN asunto ON asunto.codigo_asunto = cta_corriente.codigo_asunto
			LEFT JOIN contrato ON asunto.id_contrato = contrato.id_contrato
			LEFT JOIN usuario AS u_ingresa ON u_ingresa.id_usuario = cta_corriente.id_usuario
			LEFT JOIN usuario AS u_ordena ON u_ordena.id_usuario = cta_corriente.id_usuario_orden
			LEFT JOIN usuario AS u_encargado ON u_encargado.id_usuario = contrato.id_usuario_responsable
			LEFT JOIN prm_cta_corriente_tipo ON (prm_cta_corriente_tipo.id_cta_corriente_tipo = cta_corriente.id_cta_corriente_tipo)
			LEFT JOIN prm_moneda ON cta_corriente.id_moneda=prm_moneda.id_moneda
			LEFT JOIN prm_tipo_documento_asociado ON cta_corriente.id_tipo_documento_asociado = prm_tipo_documento_asociado.id_tipo_documento_asociado
			LEFT JOIN prm_proveedor ON ( cta_corriente.id_proveedor = prm_proveedor.id_proveedor )
			LEFT JOIN prm_glosa_gasto ON ( cta_corriente.id_glosa_gasto = prm_glosa_gasto.id_glosa_gasto )
			LEFT JOIN cobro ON cobro.id_cobro=cta_corriente.id_cobro
			WHERE 1 AND $where";
	}
}

#end Class
if(!class_exists('ListaGastos')) {
	class ListaGastos extends Lista {

		function ListaGastos($sesion, $params, $query) {
			$this->Lista($sesion, 'Gasto', $params, $query);
		}

	}
}