<?php

require_once dirname(__FILE__) . '/../conf.php';
require_once Conf::ServerDir() . '/../fw/classes/Lista.php';
require_once Conf::ServerDir() . '/../fw/classes/Objeto.php';
require_once Conf::ServerDir() . '/../app/classes/Debug.php';
require_once Conf::ServerDir() . '/classes/Observacion.php';
require_once Conf::ServerDir() . '/classes/Moneda.php';
require_once Conf::ServerDir() . '/classes/UtilesApp.php';

class Contrato extends Objeto {

	//Etapa actual del proyecto
	var $etapa = null;
	//Primera etapa del proyecto
	var $primera_etapa = null;
	var $monto = null;

	/**
	 * Define los campos de la solicitud de adelanto permitidos para llenar
	 *
	 * @var array
	 */
	var $editable_fields = array(
		'id_contrato'	,
		'glosa_contrato'	,
		'activo'	,
		'id_usuario_responsable'	,
		'id_usuario_secundario'	,
		'codigo_cliente'	,
		'centro_costo'	,
		'id_documento_legal'	,
		'titulo_contacto'	,
		'contacto'	,
		'apellido_contacto'	,
		'fono_contacto'	,
		'email_contacto'	,
		'direccion_contacto'	,
		'es_periodico'	,
		'periodo_fecha_inicio'	,
		'periodo_intervalo'	,
		'periodo_unidad'	,
		'periodo_repeticiones'	,
		'monto'	,
		'condiciones_de_pago'	,
		'observaciones'	,
		'id_moneda'	,
		'forma_cobro'	,
		'retainer_horas'	,
		'retainer_usuarios'	,
		'fecha_creacion'	,
		'fecha_modificacion'	,
		'id_usuario_modificador'	,
		'id_carta'	,
		'id_formato'	,
		'rut'	,
		'factura_razon_social'	,
		'factura_giro'	,
		'factura_direccion'	,
		'factura_telefono'	,
		'factura_comuna'	,
		'factura_codigopostal'	,
		'factura_ciudad'	,
		'cod_factura_telefono'	,
		'id_tarifa'	,
		'fecha_inicio_cap'	,
		'alerta_hh'	,
		'alerta_monto'	,
		'limite_hh'	,
		'limite_monto'	,
		'opc_ver_modalidad'	,
		'opc_ver_profesional'	,
		'opc_ver_columna_cobrable'	,
		'opc_ver_profesional_iniciales'	,
		'opc_ver_profesional_categoria'	,
		'opc_ver_profesional_tarifa'	,
		'opc_ver_profesional_importe'	,
		'opc_ver_gastos'	,
		'opc_ver_concepto_gastos'	,
		'opc_ver_descuento'	,
		'opc_ver_numpag'	,
		'opc_ver_carta'	,
		'opc_ver_morosidad'	,
		'opc_ver_tipo_cambio'	,
		'opc_ver_resumen_cobro'	,
		'opc_ver_detalles_por_hora_iniciales'	,
		'opc_ver_detalles_por_hora_categoria'	,
		'opc_ver_detalles_por_hora_tarifa'	,
		'opc_ver_detalles_por_hora_importe'	,
		'opc_papel'	,
		'opc_moneda_total'	,
		'opc_moneda_gastos'	,
		'opc_ver_asuntos_separados'	,
		'opc_ver_horas_trabajadas'	,
		'opc_ver_cobrable'	,
		'incluir_en_cierre'	,
		'codigo_idioma'	,
		'porcentaje_descuento'	,
		'tipo_descuento'	,
		'descuento'	,
		'id_moneda_monto'	,
		'usa_impuesto_separado'	,
		'opc_ver_solicitante'	,
		'opc_restar_retainer'	,
		'opc_ver_detalle_retainer'	,
		'opc_ver_valor_hh_flat_fee'	,
		'opc_ver_detalles_por_hora'	,
		'correos_edicion'	,
		'id_tramite_tarifa'	,
		'id_moneda_tramite'	,
		'usa_impuesto_gastos'	,
		'separar_liquidaciones'	,
		'notificado_hr_excedido'	,
		'notificado_monto_excedido_ult_cobro'	,
		'notificado_hr_excedida_ult_cobro'	,
		'notificado_monto_excedido'	,
		'notificar_encargado_principal'	,
		'notificar_encargado_secundario'	,
		'notificar_otros_correos'	,
		'id_cuenta'	,
		'id_cuenta2'	,
		//'id_pais'	,
		'esc1_tiempo'	,
		'esc1_id_tarifa'	,
		'esc1_monto'	,
		'esc1_id_moneda'	,
		'esc1_descuento'	,
		'esc2_tiempo'	,
		'esc2_id_tarifa'	,
		'esc2_monto'	,
		'esc2_id_moneda'	,
		'esc2_descuento'	,
		'esc3_tiempo'	,
		'esc3_id_tarifa'	,
		'esc3_monto'	,
		'esc3_id_moneda'	,
		'esc3_descuento'	,
		'esc4_tiempo'	,
		'esc4_id_tarifa'	,
		'esc4_monto'	,
		'esc4_id_moneda'	,
		'esc4_descuento'	,
		'retribucion_usuario_responsable',
		'retribucion_usuario_secundario',
		'id_estudio'
	);

	function Contrato($sesion, $fields = "", $params = "") {
		$this->tabla = "contrato";
		$this->campo_id = "id_contrato";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->log_update = true;
	}

	/*
	  Setea 1 el valor de incluir en cierre
	 */

	function SetIncluirEnCierre() {
		$query = "UPDATE contrato SET contrato.incluir_en_cierre=1 WHERE contrato.incluir_en_cierre=0";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	function LoadById($id_contrato) {
		$query = "SELECT id_contrato FROM contrato WHERE id_contrato='$id_contrato'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function LoadByCodigoAsunto($codigo_asunto) {
		$query = "SELECT id_contrato FROM asunto WHERE codigo_asunto = '$codigo_asunto'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
	function LoadByCodigoAsuntoSecundario($codigo_asunto_secundario) {
		$query = "SELECT id_contrato FROM asunto WHERE codigo_asunto_secundario = '$codigo_asunto_secundario'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
	function LoadByCodigo($codigo) {
		$query = "SELECT id_contrato FROM contrato WHERE codigo_contrato='$codigo'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}

	function ActualizarAsuntos($asuntos) {
		$query = "UPDATE asunto SET id_contrato = NULL  WHERE id_contrato = '" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($asuntos != '') {
			$lista_asuntos = join("','", $asuntos);
			$query = "UPDATE asunto SET id_contrato = '" . $this->fields['id_contrato'] . "' WHERE codigo_asunto IN ('$lista_asuntos')";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}
		return true;
	}

	function IdiomaPorDefecto($sesion) {
		if (method_exists('Conf', 'GetConf'))
			$codigo_idioma = Conf::GetConf($sesion, 'IdiomaPorDefecto');

		if (empty($codigo_idioma))
			$codigo_idioma = 'es';

		return $codigo_idioma;
	}

	function IdIdiomaPorDefecto($sesion) {
		$codigo_idioma = $this->IdiomaPorDefecto($sesion);

		$query = "SELECT id_idioma FROM prm_idioma WHERE codigo_idioma = '$codigo_idioma'";
		$resp = mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $sesion->dbh);

		list($id_idioma) = mysql_fetch_array($resp);

		return $id_idioma;
	}

	#NUEVA FUNCION, acepta más de 60 asuntos en un cliente (la otra no lo permitía)

	function AddCobroAsuntos($id_cobro) {
		#Genero la parte values a través de queries
		$query = "SELECT CONCAT('(', '$id_cobro',',\'',codigo_asunto,'\')') as value FROM asunto WHERE id_contrato = '" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($values) = mysql_fetch_array($resp)) {
			if ($values != "") {
				$query2 = "INSERT INTO cobro_asunto (id_cobro, codigo_asunto) values $values";
				mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
			}
		}
		return true;
	}

	/* function AddCobroAsuntos($id_cobro)
	  {
	  #Genero la parte values a través de queries
	  $query = "SELECT CONCAT('(', '$id_cobro',',\'',codigo_asunto,'\')') as value FROM asunto WHERE id_contrato = '".$this->fields['id_contrato']."'";
	  $query = "SELECT GROUP_CONCAT(value) FROM ($query) as tabla";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	  list($values) = mysql_fetch_array($resp);

	  if($values != "")
	  {
	  $query = "INSERT INTO cobro_asunto (id_cobro, codigo_asunto) values $values";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	  }
	  return true;
	  } */

	/*
	  Elimianr contrato
	 */

	function Eliminar() {
		$id_contrato = $this->fields[id_contrato];
		if ($id_contrato) {
			$sql = "SELECT COUNT(*) FROM cobro WHERE id_contrato = $id_contrato";
			$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
			list($count) = mysql_fetch_array($resp);
			if ($count > 0) {
				$this->error = __('No se puede eliminar un contrato que tenga cobro(s) asociado(s)');
				return false;
			} else {
				$query = "DELETE FROM modificaciones_contrato WHERE id_contrato = " . $id_contrato;
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				$query = "DELETE FROM contrato WHERE id_contrato = " . $id_contrato;
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				return true;
			}
		}
		else
			return false;
	}

	/*
	  Funcion fecha inicio cobro, recupera la fecha del más antiguo cobro o del más viejo trabajo, cobrable no cobrado
	 */
	/*
	  function FechaInicioCobro()
	  {
	  $query = "SELECT
	  LEAST(trabajo.fecha, (SELECT MAX(fecha_fin) FROM cobro WHERE cobro.id_contrato = contrato.id_contrato AND cobro.estado <> 'CREADO')) AS fecha_inicio
	  FROM trabajo
	  JOIN asunto on trabajo.codigo_asunto = asunto.codigo_asunto
	  JOIN contrato on asunto.id_contrato = contrato.id_contrato
	  JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
	  LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
	  LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro
	  WHERE
	  (cobro.estado IS NULL)
	  AND trabajo.cobrable = 1 AND contrato.id_contrato = '".$this->fields['id_contrato']."'
	  GROUP BY contrato.id_contrato";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
	  list($fecha_inicio) = mysql_fetch_array($resp);
	  return $fecha_inicio;
	  }
	 */

	/*
	  Funcion cobro estimado en periodo
	  Parametros: fecha_ini, fecha_fin, id_contrato
	 */

	function ProximoCobroEstimado($fecha_ini, $fecha_fin, $id_contrato, $horas_castigadas = NULL) {

		$wheretramite=$wheregasto=$wheretrabajo=$where = '1';


		if ($fecha_ini != '') {
			$where .= " AND fecha >= '$fecha_ini'";
			$wheretrabajo .= " AND trabajo.fecha >= '$fecha_ini'";
			$wheregasto .= " AND cta_corriente.fecha >= '$fecha_ini'";
			$wheretramite .= " AND tramite.fecha >= '$fecha_ini'";
		}
		$where .= " AND fecha <= '$fecha_fin'";
		$wheretrabajo.= " AND trabajo.fecha <= '$fecha_fin'";
		$wheregasto.= " AND cta_corriente.fecha <= '$fecha_fin'";
		$wheretramite.= " AND tramite.fecha <= '$fecha_fin'";
		$query_select = '';
		$hh_castigadas = '';
		if ($horas_castigadas) {
			$query_select = " , (SUM( TIME_TO_SEC( duracion ) ) /3600) AS horas_trabajadas ";
		}
		$query = "SELECT SUM(TIME_TO_SEC(duracion_cobrada))/3600 AS horas_por_cobrar,
										(SUM(TIME_TO_SEC(duracion_cobrada)* usuario_tarifa.tarifa/3600)) AS monto_por_cobrar
										$query_select
								FROM trabajo
								JOIN asunto on trabajo.codigo_asunto = asunto.codigo_asunto
								JOIN contrato on asunto.id_contrato = contrato.id_contrato
          			LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario AND contrato.id_moneda=usuario_tarifa.id_moneda AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
								LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro
								WHERE $wheretrabajo AND
								trabajo.id_tramite=0 AND
								(cobro.estado IS NULL)
								AND trabajo.cobrable = 1 AND contrato.id_contrato = '$id_contrato'
								GROUP BY contrato.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		if ($horas_castigadas) {
			list($horas_por_cobrar, $monto_por_cobrar, $horas_trabajadas) = mysql_fetch_row($resp);
			$hh_castigadas = $horas_trabajadas - $horas_por_cobrar;
		} else {
			list($horas_por_cobrar, $monto_por_cobrar) = mysql_fetch_row($resp);
		}

		$query = "SELECT
									SUM(IF(
										tramite.tarifa_tramite_individual > 0,
										tramite.tarifa_tramite_individual * ( moneda_tramite_individual.tipo_cambio / moneda_contrato.tipo_cambio ),
										tramite_valor.tarifa
										)) as monto_por_cobrar_tramite,
									SUM(TIME_TO_SEC(tramite.duracion))/3600 AS horas_por_cobrar_tramite
								FROM tramite
								JOIN asunto on tramite.codigo_asunto = asunto.codigo_asunto
								JOIN contrato on asunto.id_contrato = contrato.id_contrato
								JOIN prm_moneda as moneda_tramite_individual ON moneda_tramite_individual.id_moneda = tramite.id_moneda_tramite_individual
								JOIN prm_moneda as moneda_contrato ON moneda_contrato.id_moneda = contrato.id_moneda
								JOIN tramite_tipo on tramite.id_tramite_tipo=tramite_tipo.id_tramite_tipo
								JOIN tramite_valor ON (tramite.id_tramite_tipo=tramite_valor.id_tramite_tipo AND contrato.id_moneda=tramite_valor.id_moneda AND contrato.id_tramite_tarifa=tramite_valor.id_tramite_tarifa)
								LEFT JOIN cobro ON tramite.id_cobro=cobro.id_cobro
								WHERE $wheretramite AND (cobro.estado IS NULL)
									AND tramite.cobrable=1
									AND contrato.id_contrato = '$id_contrato'
								GROUP BY contrato.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($monto_por_cobrar_tramite, $horas_por_cobrar_tramite) = mysql_fetch_array($resp)) {
			$monto_por_cobrar += $monto_por_cobrar_tramite;
		}
		$horas_por_cobrar += $horas_por_cobrar_tramite;

		if ($horas_por_cobrar == '' || is_null($horas_por_cobrar))
			$horas_por_cobrar = 0;
		if ($monto_por_cobrar == '' || is_null($monto_por_cobrar))
			$monto_por_cobrar = 0;

		if (!$this->monedas)
			$this->monedas = ArregloMonedas($this->sesion);

		$query = "SELECT separar_liquidaciones, opc_moneda_total, opc_moneda_gastos FROM contrato WHERE id_contrato = '$id_contrato'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($separar, $moneda_total, $moneda_gastos) = mysql_fetch_array($resp);
		if (empty($separar))
			$moneda_gastos = $moneda_total;

		$suma_gastos = 0;

		$query = "SELECT if(cta_corriente.ingreso>0,-1,1)*cta_corriente.monto_cobrable, cta_corriente.id_moneda FROM cta_corriente
						LEFT JOIN asunto ON cta_corriente.codigo_asunto = asunto.codigo_asunto
						WHERE $wheregasto AND (cta_corriente.egreso > 0 OR cta_corriente.ingreso > 0)
						AND (cta_corriente.id_cobro IS NULL)
						AND cta_corriente.incluir_en_cobro = 'SI'
						AND cta_corriente.cobrable = 1
						AND asunto.id_contrato = '$id_contrato'						";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		while (list($monto, $id_moneda) = mysql_fetch_array($resp)) {
		$suma_gastos += UtilesApp::CambiarMoneda($monto //monto_moneda_l
					, $this->monedas[$id_moneda]['tipo_cambio']//tipo de cambio ini
					, $this->monedas[$id_moneda]['cifras_decimales']//decimales ini
					, $this->monedas[$moneda_gastos]['tipo_cambio']//tipo de cambio fin
					, $this->monedas[$moneda_gastos]['cifras_decimales']//decimales fin
			);
		if($monto>=0) {
			$suma_egresos += UtilesApp::CambiarMoneda($monto //monto_moneda_l
					, $this->monedas[$id_moneda]['tipo_cambio']//tipo de cambio ini
					, $this->monedas[$id_moneda]['cifras_decimales']//decimales ini
					, $this->monedas[$moneda_gastos]['tipo_cambio']//tipo de cambio fin
					, $this->monedas[$moneda_gastos]['cifras_decimales']//decimales fin
			);
		} elseif($monto<0) {
			$suma_provisiones -= UtilesApp::CambiarMoneda($monto //monto_moneda_l
					, $this->monedas[$id_moneda]['tipo_cambio']//tipo de cambio ini
					, $this->monedas[$id_moneda]['cifras_decimales']//decimales ini
					, $this->monedas[$moneda_gastos]['tipo_cambio']//tipo de cambio fin
					, $this->monedas[$moneda_gastos]['cifras_decimales']//decimales fin
			);
		}
		}
		if($suma_gastos<0) $suma_gastos=0;
		#$query_gastos = "SELECT * FROM cta_corriente ";
		if ($horas_castigadas) {
			return array($horas_por_cobrar, $monto_por_cobrar, $hh_castigadas, $suma_gastos, $this->monedas[$moneda_gastos]['simbolo'],$suma_egresos,$suma_provisiones);
		} else {
			return array($horas_por_cobrar, $monto_por_cobrar, 0             , $suma_gastos, $this->monedas[$moneda_gastos]['simbolo'],$suma_egresos,$suma_provisiones);
		}
	}

	/*
	 * actualiza todos los contratos para el cliente especificado, donde asigna el nuevo encargado comercial
	 * esto es para PRC por ahora, no hay otro cliente que lo necesite.
	 */

	function ActualizarEncargadoComercialTodosContratosCliente($codigo_cliente, $nuevo_encargado_comercial) {
		if (isset($codigo_cliente) && $codigo_cliente != null && isset($nuevo_encargado_comercial) && $nuevo_encargado_comercial != null) {
			$query = "UPDATE contrato SET id_usuario_responsable='$nuevo_encargado_comercial' WHERE codigo_cliente='$codigo_cliente'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			return true;
		} else {
			return false;
		}
	}

	// Determina cual es la fecha del ultimo trabajo ingresado
	function FechaUltimoTrabajo($fecha_ini, $fecha_fin, $codigo_asunto = '', $pendiente = true) {
		$where = " 1 ";
		if (!empty($fecha_ini))
			$where .= " AND trabajo.fecha >= '$fecha_ini' ";
		if (!empty($fecha_fin))
			$where .= " AND trabajo.fecha <= '$fecha_fin' ";
		if (!empty($codigo_asunto))
			$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
		else
			$where .= " AND contrato.id_contrato = '" . $this->fields['id_contrato'] . "' ";
		if ($pendiente)
			$where .= " AND ( trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' )";

		$query = "SELECT MAX( trabajo.fecha )
								FROM trabajo
								JOIN asunto USING( codigo_asunto )
								JOIN contrato USING( id_contrato )
								LEFT JOIN cobro ON trabajo.id_cobro = cobro.id_cobro
								WHERE $where ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($fecha) = mysql_fetch_array($resp);
		return $fecha;
	}

	function UltimoCobro() {
		$query = " SELECT id_cobro FROM cobro WHERE cobro.id_contrato = '" . $this->fields['id_contrato'] . "' ORDER BY fecha_fin DESC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_cobro) = mysql_fetch_array($resp);
		return $id_cobro;
	}

	// Determina cual es la fecha del ultimo gasto ingresado
	function FechaUltimoGasto($fecha_ini, $fecha_fin, $codigo_asunto = '', $pendiente = true) {
		$where = " 1 ";
		if (!empty($fecha_ini))
			$where .= " AND cta_corriente.fecha >= '$fecha_ini' ";
		if (!empty($fecha_fin))
			$where .= " AND cta_corriente.fecha <= '$fecha_fin' ";
		if (!empty($codigo_asunto))
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto' ";
		else
			$where .= " AND contrato.id_contrato = '" . $this->fields['id_contrato'] . "' ";
		if ($pendiente)
			$where .= " AND ( cta_corriente.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' )";

		$query = "SELECT MAX( cta_corriente.fecha )
								FROM cta_corriente
								JOIN asunto USING( codigo_asunto )
								JOIN contrato USING( id_contrato )
								LEFT JOIN cobro ON cta_corriente.id_cobro = cobro.id_cobro
								WHERE $where ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($fecha) = mysql_fetch_array($resp);
		return $fecha;
	}

	/*
	  Se elimina los antiguos borradores del contrato
	  para que se puedan asociar las horas al nuevo borrador
	 */

	function EliminarBorrador($incluye_gastos = 1, $incluye_honorarios = 1) {
		$query = "SELECT id_cobro FROM cobro
				WHERE estado='CREADO'
				AND id_contrato='" . $this->fields['id_contrato'] . "'
				AND incluye_gastos = $incluye_gastos
				AND incluye_honorarios = $incluye_honorarios";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		while (list($id_cobro) = mysql_fetch_array($resp)) {
			#Se ingresa la anotación en el historial
			$his = new Observacion($this->sesion);
			$his->Edit('fecha', date('Y-m-d H:i:s'));
			$his->Edit('comentario', "COBRO ELIMINADO (OTRO BORRADOR)");
			$his->Edit('id_usuario', $this->sesion->usuario->fields['id_usuario']);
			$his->Edit('id_cobro', $id_cobro);
			$his->Write();
			$borrador = new Cobro($this->sesion);
			if ($borrador->Load($id_cobro))
				$borrador->Eliminar();
		}
	}

	function TotalHoras($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		$where = '';
		if (!$emitido) {
			$where = " AND (t2.estadocobro  in ('SIN COBRO','CREADO','EN REVISION')) ";
		}

		if (!empty($codigo_asunto)) {
			$where .= " AND t2.codigo_asunto = '$codigo_asunto' ";
		}

		if (!empty($fecha_ini)) {
			$where .= " AND t2.fecha >= '$fecha_ini' ";
		}

		if (!empty($fecha_fin)) {
			$where .= " AND t2.fecha <= '$fecha_fin' ";
		}

		$query = "SELECT
				SUM(TIME_TO_SEC(duracion_cobrada)) / 3600 AS hrs_no_cobradas
			FROM trabajo AS t2
				JOIN asunto ON t2.codigo_asunto = asunto.codigo_asunto
			WHERE 1 $where
				AND t2.cobrable = 1
				AND asunto.id_contrato = {$this->fields['id_contrato']}";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_horas_no_cobradas) = mysql_fetch_array($resp);

		if ($total_horas_no_cobradas) {
			return $total_horas_no_cobradas; // total de horas cobrables no cobradas
		} else {
			return 0;
		}
	}

	function TotalMonto($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		$where = '';

		list($monto_hh_contrato, $X, $Y) = $this->MHHXYC;
		list($monto_hh_asunto, $x, $y) = $this->MHHXYA;

		$factor = 1;

		$where_estado = '';
		$where_asunto = '';
		$where_fecha1 = '';
		$where_fecha2 = '';

		if (!$emitido) {
			$where_estado = " AND (t1.estadocobro  in ('SIN COBRO','CREADO','EN REVISION')) "; //el normal
		}

		if (!empty($codigo_asunto)) {
			$where_asunto = " AND t1.codigo_asunto = '$codigo_asunto' ";
		}

		if (!empty($fecha_ini)) {
			$where_fecha1 = " AND t1.fecha >= '$fecha_ini' ";
		}

		if (!empty($fecha_fin)) {
			$where_fecha2 = " AND t1.fecha <= '$fecha_fin' ";
		}

		switch ($this->fields['forma_cobro']) {
			case'RETAINER':
				$query = "SELECT $factor * plata_retainer + SUM(Macumulado) AS total_monto_trabajado, simbolo, id_moneda
				FROM (
					SELECT t1.codigo_asunto, t1.fecha, t1.plata_retainer, t1.simbolo, t1.id_moneda,
						t1.duracionh AS t_individual, @acumulado:=@acumulado + t1.duracionh AS Tacumulado,
						IF(@acumulado < t1.retainer_horas, 0, IF(@acumulado - t1.retainer_horas > t1.duracionh, t1.duracionh, @acumulado - t1.retainer_horas)) * t1.tarifa AS Macumulado
					FROM
						(
							SELECT @acumulado:=0
						) AS ac,
						(
							SELECT ut1.tarifa, c1.monto * (pm2.tipo_cambio / pm1.tipo_cambio) AS plata_retainer, c1.retainer_horas,
								pm1.simbolo, pm1.id_moneda, t1.fecha, t1.id_trabajo, t1.id_usuario,
								t1.codigo_asunto, time_to_sec(t1.duracion_cobrada) / 3600 AS duracionh
							FROM trabajo t1
								JOIN asunto a1 ON (t1.codigo_asunto = a1.codigo_asunto)
								JOIN contrato c1 ON (a1.id_contrato = c1.id_contrato)
								JOIN prm_moneda pm1 ON (c1.id_moneda = pm1.id_moneda)
								LEFT JOIN prm_moneda pm2 ON (c1.id_moneda_monto = pm2.id_moneda)
								LEFT JOIN usuario_tarifa ut1 ON (t1.id_usuario = ut1.id_usuario AND c1.id_moneda = ut1.id_moneda AND c1.id_tarifa = ut1.id_tarifa)
							WHERE 1
								$where_estado
								$where_fecha2
								AND a1.id_contrato = {$this->fields['id_contrato']}
								AND t1.cobrable = 1 AND t1.id_tramite = 0
								ORDER BY t1.fecha, t1.id_trabajo
						) AS t1
				) AS t1
				WHERE 1
					$where_fecha1
					$where_asunto
				GROUP BY plata_retainer, simbolo, id_moneda";
				break;

			case 'PROPORCIONAL':
				$subquery = "SELECT SUM((TIME_TO_SEC(duracion_cobrada) / 3600))
				FROM trabajo AS t1
					JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
					JOIN contrato ON asunto.id_contrato = contrato.id_contrato
					JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
					LEFT JOIN usuario_tarifa ON (
						t1.id_usuario = usuario_tarifa.id_usuario
						AND contrato.id_moneda = usuario_tarifa.id_moneda
						AND contrato.id_tarifa = usuario_tarifa.id_tarifa
					)
				WHERE 1
					$where_estado
					$where_asunto
					$where_fecha1
					$where_fecha2
					AND t1.cobrable = 1
					AND t1.id_tramite = 0
					AND asunto.id_contrato = {$this->fields['id_contrato']}
				GROUP BY asunto.id_contrato";

				$resp = mysql_query($subquery, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
				list($duracion_total) = mysql_fetch_array($resp);

				if (empty($duracion_total)) {
					$duracion_total = '0';
					$aporte_proporcional = 1;
				} else {
					$aporte_proporcional = number_format($factor * $this->fields['retainer_horas'] / $duracion_total, 6, '.', '');
				}

				$query = "SELECT
						contrato.monto * $factor * (pm2.tipo_cambio / pm1.tipo_cambio) + SUM(((TIME_TO_SEC(t1.duracion_cobrada) / 3600) * usuario_tarifa.tarifa) * (1 - $aporte_proporcional)),
						pm1.simbolo,
						pm1.id_moneda
					FROM trabajo t1
						JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						JOIN prm_moneda AS pm1 ON contrato.id_moneda=pm1.id_moneda
						LEFT JOIN prm_moneda AS pm2 ON contrato.id_moneda_monto = pm2.id_moneda
						LEFT JOIN usuario_tarifa ON (
							t1.id_usuario = usuario_tarifa.id_usuario
							AND contrato.id_moneda = usuario_tarifa.id_moneda
							AND contrato.id_tarifa = usuario_tarifa.id_tarifa
						)
					WHERE 1
						$where_estado
						$where_asunto
						$where_fecha1
						$where_fecha2
						AND t1.cobrable = 1 AND t1.id_tramite = 0 AND asunto.id_contrato = {$this->fields['id_contrato']}
					GROUP BY asunto.id_contrato";
				break;

			case 'FLAT FEE':
				// y por qué esta no mira la tabla trabajo?
				$query = " SELECT contrato.monto * (moneda_monto.tipo_cambio / moneda_contrato.tipo_cambio) * $factor,
					moneda_contrato.simbolo, moneda_contrato.id_moneda
				FROM contrato
					JOIN prm_moneda AS moneda_contrato ON contrato.opc_moneda_total = moneda_contrato.id_moneda
					JOIN prm_moneda AS moneda_monto ON contrato.id_moneda_monto = moneda_monto.id_moneda
				WHERE contrato.id_contrato = {$this->fields['id_contrato']}";
				break;

			case 'CAP':
				$query = " SELECT
						SUM(TIME_TO_SEC(t1.duracion_cobrada) * usuario_tarifa.tarifa * (moneda_tarifa.tipo_cambio / moneda_monto.tipo_cambio) / 3600),
						moneda_monto.simbolo,
						moneda_monto.id_moneda
					FROM trabajo t1
						JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						JOIN prm_moneda AS moneda_monto ON contrato.id_moneda_monto = moneda_monto.id_moneda
						JOIN prm_moneda AS moneda_tarifa ON contrato.id_moneda = moneda_tarifa.id_moneda
						LEFT JOIN usuario_tarifa ON (t1.id_usuario = usuario_tarifa.id_usuario
							AND contrato.id_moneda = usuario_tarifa.id_moneda
							AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
					WHERE 1
						$where_estado
						$where_asunto
						$where_fecha1
						$where_fecha2
						AND t1.cobrable = 1
						AND t1.id_tramite = 0
						AND asunto.id_contrato = {$this->fields['id_contrato']}
					GROUP BY asunto.id_contrato ";
				break;

			case 'ESCALONADA':
				$query = "SELECT SQL_CALC_FOUND_ROWS t1.duracion_cobrada,
						t1.descripcion,
						t1.fecha,
						t1.id_usuario,
						t1.monto_cobrado,
						t1.id_moneda AS id_moneda_trabajo,
						t1.id_trabajo,
						t1.tarifa_hh,
						t1.cobrable,
						t1.visible,
						t1.codigo_asunto,
						CONCAT_WS(' ', nombre, apellido1) AS nombre_usuario
					FROM trabajo t1
						JOIN usuario ON t1.id_usuario = usuario.id_usuario
						JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
					WHERE 1
						$where_estado
						$where_asunto
						$where_fecha1
						$where_fecha2
						AND t1.cobrable = 1
						AND t1.id_tramite = 0
						AND asunto.id_contrato = {$this->fields['id_contrato']}";
						break;
			default:
				$query = "SELECT
						SUM((TIME_TO_SEC(t1.duracion_cobrada) * usuario_tarifa.tarifa) / 3600),
						prm_moneda.simbolo,
						prm_moneda.id_moneda
					FROM trabajo t1
						JOIN asunto ON t1.codigo_asunto = asunto.codigo_asunto
						JOIN contrato ON asunto.id_contrato = contrato.id_contrato
						JOIN prm_moneda ON contrato.id_moneda = prm_moneda.id_moneda
						LEFT JOIN usuario_tarifa ON (t1.id_usuario = usuario_tarifa.id_usuario
						AND contrato.id_moneda = usuario_tarifa.id_moneda
						AND contrato.id_tarifa = usuario_tarifa.id_tarifa)
					WHERE 1
						$where_estado
						$where_asunto
						$where_fecha1
						$where_fecha2
						AND t1.cobrable = 1
						AND t1.id_tramite = 0
						AND asunto.id_contrato = {$this->fields['id_contrato']}
					GROUP BY asunto.id_contrato";
				break;
		}

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($total_monto_trabajado, $moneda, $id_moneda) = mysql_fetch_array($resp);

		if ($moneda) {
			return array(
				$total_monto_trabajado,
				$moneda,
				$id_moneda,
				intval($cantidad_asuntos),
				$monto_hh_contrato,
				$X,
				$Y,
				$monto_hh_asunto,
				$x,
				$y
			);
		}

		$query = "SELECT prm_moneda.simbolo, prm_moneda.id_moneda
			FROM contrato
				JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
			WHERE contrato.id_contrato = '{$this->fields['id_contrato']}'";

		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($moneda, $id_moneda) = mysql_fetch_array($resp);

		return array(
			0,
			$moneda,
			$id_moneda,
			intval($cantidad_asuntos),
			$monto_hh_contrato,
			$X,
			$Y,
			$monto_hh_asunto,
			$x,
			$y
		);
	}

	// Cargar información de escalonadas a un objeto
	function CargarEscalonadas() {
		$this->escalonadas = array();
		$this->escalonadas['num'] = 0;
		$this->escalonadas['monto_fijo'] = 0;


		$moneda_contrato = new Moneda($this->sesion);
		$moneda_contrato->Load($this->fields['id_moneda']);

		// Contador escalonadas $moneda_escalonada->Load( $this->escalondas[$x_escalonada]['id_moneda'] );
		$moneda_escalonada = new Moneda($this->sesion);


		$tiempo_inicial = 0;
		for ($i = 1; $i < 5; $i++) {
			if (empty($this->fields['esc' . $i . '_tiempo']))
				break;

			$this->escalonadas['num']++;
			$this->escalonadas[$i] = array();

			$this->escalonadas[$i]['tiempo_inicial'] = $tiempo_inicial;
			$this->escalonadas[$i]['tiempo_final'] = $salir_en_proximo_paso ? '' : $this->fields['esc' . $i . '_tiempo'] + $tiempo_inicial;
			$this->escalonadas[$i]['id_tarifa'] = $this->fields['esc' . $i . '_id_tarifa'];
			$this->escalonadas[$i]['id_moneda'] = $this->fields['esc' . $i . '_id_moneda'];

			$moneda_escalonada->Load($this->escalondas[$i]['id_moneda']);

			$this->escalonadas[$i]['monto'] = UtilesApp::CambiarMoneda(
					$this->fields['esc' . $i . '_monto'], $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
			);
			$this->escalonadas[$i]['descuento'] = $this->fields['esc' . $i . '_descuento'];

			if (!empty($this->escalonadas[$i]['monto'])) {
				$this->escalonadas[$i]['escalonada_tarificada'] = 0;
				$this->escalonadas['monto_fijo'] += $this->escalonadas[$i]['monto'];
			} else {
				$this->escalonadas[$i]['escalonada_tarificada'] = 1;
			}

			$tiempo_inicial += $this->fields['esc' . $i . '_tiempo'];
		}

		$this->escalonadas['num']++;
		$i = 4;  //ultimo campo (por la genialidad de agregar como mil campos en una tabla)
		$i2 = $this->escalonadas['num']; //proximo "slot" en el array de escalonadas

		$this->escalonadas[$i2] = array();

		$this->escalonadas[$i2]['tiempo_inicial'] = $tiempo_inicial;
		$this->escalonadas[$i2]['tiempo_final'] = '';
		$this->escalonadas[$i2]['id_tarifa'] = $this->fields['esc' . $i . '_id_tarifa'];
		$this->escalonadas[$i2]['id_moneda'] = $this->fields['esc' . $i . '_id_moneda'];

		$moneda_escalonada->Load($this->escalondas[$i2]['id_moneda']);

		$this->escalonadas[$i2]['monto'] = UtilesApp::CambiarMoneda(
				$this->fields['esc' . $i . '_monto'], $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
		);
		$this->escalonadas[$i2]['descuento'] = $this->fields['esc' . $i . '_descuento'];

		if (!empty($this->escalonadas[$i2]['monto'])) {
			$this->escalonadas[$i2]['escalonada_tarificada'] = 0;
			$this->escalonadas['monto_fijo'] += $this->escalonadas[$i2]['monto'];
		} else {
			$this->escalonadas[$i2]['escalonada_tarificada'] = 1;
		}
	}

	function MontoHonorariosEscalonados($lista_trabajos) {
		// Cargar escalonadas
		$this->CargarEscalonadas();
		$moneda_contrato = new Moneda($this->sesion);
		$moneda_contrato->Load($this->fields['id_moneda']);

		// Contador escalonadas
		$x_escalonada = 1;
		$moneda_escalonada = new Moneda($this->sesion);
		$moneda_escalonada->Load($this->escalondas[$x_escalonada]['id_moneda']);

		// Variable para sumar monto total
		$cobro_total_honorario_cobrable = $this->escalonadas['monto_fijo'];

		// Contador de duracion
		$cobro_total_duracion = 0;

		$duracion_hora_restante = 0;

		for ($z = 0; $z < $lista_trabajos->num; $z++) {
			$trabajo = $lista_trabajos->Get($z);
			$valor_trabajo = 0;
			$valor_trabajo_estandar = 0;
			$duracion_retainer_trabajo = 0;

			if ($trabajo->fields['cobrable']) {
				// Revisa duración de la hora y suma duracion que sobro del trabajo anterior, si es que se cambió de escalonada
				list($h, $m, $s) = split(":", $trabajo->fields['duracion_cobrada']);
				$duracion = $h + ($m > 0 ? ($m / 60) : '0');
				$duracion_trabajo = $duracion;

				// Mantengase en el mismo trabajo hasta que no se require un cambio de escalonada...
				while (true) {

					// Calcula tiempo del trabajo actual que corresponde a esa escalonada y tiempo que corresponde a la proxima.
					if (!empty($this->escalonadas[$x_escalonada]['tiempo_final'])) {
						$duracion_escalonada_actual = min($duracion, $this->escalonadas[$x_escalonada]['tiempo_final'] - $cobro_total_duracion);
						$duracion_hora_restante = $duracion - $duracion_escalonada_actual;
					} else {
						$duracion_escalonada_actual = $duracion;
						$duracion_hora_restante = 0;
					}

					$cobro_total_duracion += $duracion_escalonada_actual;

					if (!empty($this->escalonadas[$x_escalonada]['id_tarifa'])) {
						// Busca la tarifa según abogado y definición de la escalonada
						$tarifa_estandar = UtilesApp::CambiarMoneda(
								Funciones::TarifaDefecto($this->sesion, $trabajo->fields['id_usuario'], $this->escalonadas[$x_escalonada]['id_moneda']), $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
						);
						$tarifa = UtilesApp::CambiarMoneda(
								Funciones::Tarifa($this->sesion, $trabajo->fields['id_usuario'], $this->escalonadas[$x_escalonada]['id_moneda'], '', $this->escalonadas[$x_escalonada]['id_tarifa']), $moneda_escalonada->fields['tipo_cambio'], $moneda_escalonada->fields['cifras_decimales'], $moneda_contrato->fields['tipo_cambio'], $moneda_contrato->fields['cifras_decimales']
						);

						$valor_trabajo += ( 1 - $this->escalonadas['descuento'] / 100 ) * $duracion_escalonada_actual * $tarifa;
						$valor_trabajo_estandar += ( 1 - $this->escalonadas['descuento'] / 100 ) * $duracion_escalonada_actual * $tarifa_estandar;
					} else {
						$duracion_retainer_trabajo += $duracion_escalonada_actual;
						$valor_trabajo += 0;
						$valor_trabajo_estandar += 0;
					}

					if ($duracion_hora_restante > 0 || $cobro_total_duracion == $this->escalonadas[$x_escalonada]['tiempo_final']) {
						$x_escalonada++;
						$moneda_escalonada = new Moneda($this->sesion);
						$moneda_escalonada->Load($this->escalondas[$x_escalonada]['id_moneda']);
						if ($duracion_hora_restante > 0) {
							$duracion = $duracion_hora_restante;
						} else {
							break;
						}
					}
					else
						break;
				}
				$cobro_total_honorario_cobrable += $valor_trabajo;
			} else {
				continue;
			}
		}
		return $cobro_total_honorario_cobrable;
	}

	function CantidadAsuntosPorFacturar($fecha1, $fecha2) {
		$where_trabajo = " ( trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		$where_gasto = " ( cta_corriente.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		if ($fecha1 != '' && $fecha2 != '') {
			$where_trabajo .= " AND trabajo.fecha >= '" . $fecha1 . "' AND trabajo.fecha <= '" . $fecha2 . "'";
			$where_gasto .= " AND cta_corriente.fecha >= '" . $fecha1 . "' AND cta_corriente.fecha <= '" . $fecha2 . "' ";
		}
		$where_gasto .= " AND cta_corriente.incluir_en_cobro = 'SI' ";

		$query = "SELECT count(*)
				FROM asunto
				WHERE asunto.id_contrato = '" . $this->fields['id_contrato'] . "'
                                        AND ( ( SELECT count(*) FROM trabajo
                                        	 LEFT JOIN cobro ON cobro.id_cobro = trabajo.id_cobro
						 WHERE trabajo.codigo_asunto = asunto.codigo_asunto
					 	 AND trabajo.cobrable = 1
					 	 AND trabajo.id_tramite = 0
					 	 AND trabajo.duracion_cobrada != '00:00:00'
					 	 AND $where_trabajo ) > 0
					OR ( SELECT count(*) FROM cta_corriente
						LEFT JOIN cobro ON cobro.id_cobro = cta_corriente.id_cobro
						WHERE cta_corriente.codigo_asunto = asunto.codigo_asunto
						AND cta_corriente.cobrable = 1
                                                AND cta_corriente.monto_cobrable > 0
						AND $where_gasto ) > 0 )
					GROUP BY asunto.id_contrato ";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($cantidad_asuntos) = mysql_fetch_array($resp);
		return $cantidad_asuntos;
	}

	function MontoHHTarifaSTD($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		if (!$emitido) {
			$where = " AND (trabajo.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION') ";
		}
		if (!empty($codigo_asunto)) {
			$where .= " AND trabajo.codigo_asunto = '$codigo_asunto' ";
		}
		if (!empty($fecha_ini)) {
			$where .= " AND trabajo.fecha >= '$fecha_ini' ";
		}
		if (!empty($fecha_fin)) {
			$where .= " AND trabajo.fecha <= '$fecha_fin' ";
		}

		$query = "SELECT
							GROUP_CONCAT( usuario_tarifa.id_tarifa, prm_moneda.simbolo, usuario_tarifa.tarifa SEPARATOR '  //  ' ) as tarifas,
							SUM((TIME_TO_SEC(duracion_cobrada)/3600)*usuario_tarifa.tarifa),
							prm_moneda.simbolo,
							prm_moneda.id_moneda
							FROM trabajo
							JOIN asunto ON trabajo.codigo_asunto = asunto.codigo_asunto
							JOIN contrato ON asunto.id_contrato = contrato.id_contrato
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
							LEFT JOIN usuario_tarifa ON (trabajo.id_usuario=usuario_tarifa.id_usuario
								AND contrato.id_moneda=usuario_tarifa.id_moneda
								AND usuario_tarifa.id_tarifa = ( SELECT id_tarifa FROM tarifa WHERE tarifa_defecto = 1) )
							LEFT JOIN cobro on trabajo.id_cobro=cobro.id_cobro
							WHERE 1 $where
							AND trabajo.cobrable = 1
							AND trabajo.id_tramite = 0
							AND asunto.id_contrato='" . $this->fields['id_contrato'] . "'
							GROUP BY asunto.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($tarifas, $total_monto_trabajado, $moneda, $id_moneda) = mysql_fetch_array($resp);

		if ($moneda)
			return array($total_monto_trabajado, $moneda, $id_moneda);

		$query = "SELECT
								prm_moneda.simbolo,
								prm_moneda.id_moneda
							FROM contrato
							JOIN prm_moneda ON contrato.id_moneda=prm_moneda.id_moneda
							WHERE contrato.id_contrato='" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($moneda, $id_moneda) = mysql_fetch_array($resp);
		return array(0, $moneda, $id_moneda);
	}

	function MontoGastos($emitido = true, $codigo_asunto = '', $fecha_ini = '', $fecha_fin = '') {
		$where = " 1 AND cta_corriente.cobrable=1";
		if (!$emitido) {
			$where .= " AND ( cta_corriente.id_cobro IS NULL OR cobro.estado = 'CREADO' OR cobro.estado = 'EN REVISION' ) ";
		}
		if (!empty($codigo_asunto)) {
			$where .= " AND cta_corriente.codigo_asunto = '$codigo_asunto' ";
		}
		if (!empty($fecha_ini)) {
			$where .= " AND cta_corriente.fecha >= '$fecha_ini' ";
		}
		if (!empty($fecha_fin)) {
			$where .= " AND cta_corriente.fecha <= '$fecha_fin' ";
		}
		$where .= " AND cta_corriente.incluir_en_cobro = 'SI' ";

		$query = "SELECT
									SUM( IF( egreso IS NOT NULL, monto_cobrable, -1 * monto_cobrable ) * ( moneda_gasto.tipo_cambio / moneda_contrato.tipo_cambio ) ) as monto_gastos,
									moneda_contrato.simbolo,
									moneda_contrato.id_moneda
								FROM cta_corriente
								JOIN asunto USING( codigo_asunto )
								JOIN contrato ON contrato.id_contrato = asunto.id_contrato
								JOIN prm_moneda AS moneda_gasto ON moneda_gasto.id_moneda = cta_corriente.id_moneda
								JOIN prm_moneda as moneda_contrato ON contrato.opc_moneda_total = moneda_contrato.id_moneda
								LEFT JOIN cobro ON cta_corriente.id_cobro = cobro.id_cobro
								WHERE $where AND asunto.id_contrato = '" . $this->fields['id_contrato'] . "'
								GROUP BY asunto.id_contrato";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list( $monto_total_gastos, $simbolo_moneda, $id_moneda ) = mysql_fetch_array($resp);

		if ($id_moneda)
			return array($monto_total_gastos, $simbolo_moneda, $id_moneda);

		$query = "SELECT
								prm_moneda.simbolo,
								prm_moneda.id_moneda
							FROM contrato
							JOIN prm_moneda ON contrato.opc_moneda_total=prm_moneda.id_moneda
							WHERE contrato.id_contrato='" . $this->fields['id_contrato'] . "'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($simbolo_moneda, $id_moneda) = mysql_fetch_array($resp);
		return array(0, $simbolo_moneda, $id_moneda);
	}

	function Prepare() {
		$this->Edit("monto", str_replace(',','.',$this->fields['monto']), true);
		$this->Edit("id_usuario_responsable", (!empty($this->fields['id_usuario_responsable']) && $this->fields['id_usuario_responsable'] != -1 ) ? $this->fields['id_usuario_responsable'] : "NULL");

		if (UtilesApp::GetConf($this->sesion, 'EncargadoSecundario')) {
			$this->Edit("id_usuario_secundario", (!empty($this->fields['id_usuario_secundario']) && $this->fields['id_usuario_secundario'] != -1 ) ? $this->fields['id_usuario_secundario'] : "NULL");
		}

		if (UtilesApp::GetConf($this->sesion, 'CopiarEncargadoAlAsunto')) {
			$id_usuario_responsable_def = (!empty($this->fields['id_usuario_responsable']) && $this->fields['id_usuario_responsable'] != -1 ) ? $this->fields['id_usuario_responsable'] : "NULL";
			$sql_cambia = "UPDATE contrato SET id_usuario_responsable = $id_usuario_responsable_def  WHERE codigo_cliente = '".$this->fields['codigo_cliente']."'";
			if (!($res = mysql_query($sql_cambia, $this->sesion->dbh) )) {
				throw new Exception($sql_cambia . "---" . mysql_error());
			}
		}

		$this->Edit("id_carta", $this->fields['id_carta'] ?  $this->fields['id_carta'] : 'NULL');
		$this->Edit("codigo_idioma", $this->fields['codigo_idioma'] ?  $this->fields['codigo_idioma'] : 'es');
		$this->Edit("id_tarifa",  $this->fields['id_tarifa'] ? $this->fields['id_tarifa']  : 'NULL');
		$this->Edit("id_tramite_tarifa",$this->fields['id_tramite_tarifa']    ? $this->fields['id_tramite_tarifa']    : 'NULL' );
		$this->Edit("id_formato", empty($this->fields['id_formato']) || $this->fields['id_formato'] == 'NULL' ? '0' : $this->fields['id_formato']);
		$this->Edit("id_cuenta", empty($this->fields['id_cuenta']) || $this->fields['id_cuenta'] == 'NULL' ? '0' : $this->fields['id_cuenta']);

		if ( $this->fields['tipo_descuento'] == 'PORCENTAJE') {
			$this->Edit("porcentaje_descuento", $this->fields['porcentaje_descuento'] > 0 ? $this->fields['porcentaje_descuento'] : '0');
			$this->Edit("descuento", '0');
		} else {
			$this->Edit("descuento", $this->fields['descuento'] > 0 ? $this->fields['descuento'] : '0');
			$this->Edit("porcentaje_descuento", '0');
		}
	}

	/**
	 * Completa el objeto con los valores que vengan en $parametros
	 * También sirve para definir cuando un parámetro que no viene debe ser marcado como cero
	 *
	 * @param array $parametros entrega los campos y valores del objeto campo => valor
	 * @param boolean $edicion indica si se marcan los $parametros para edición
	 */
	function Fill($parametros, $edicion = false) {


		foreach ($parametros as $campo => $valor) {
			if (in_array($campo, $this->editable_fields)) {
				$this->fields[$campo] = $valor;



				if ($edicion) {
					$this->Edit($campo, $valor);
				}
			} else {
				$this->extra_fields[$campo] = $valor;
			}
		}
		foreach($this->editable_fields as $editable_field) {
				if(substr($editable_field,0,4)=='opc_') {
					if(empty($parametros[$editable_field])) {
						$this->Edit($editable_field,"0");
					} else {
						$this->Edit($editable_field,$parametros[$editable_field]);
					}
				}
			}

		if($this->extra_fields['activo_contrato'] || empty($this->fields['activo'])  || empty($this->fields['id_contrato']) ) {
			$this->Edit("activo",'SI');
		} else if ($this->extra_fields['desactivar_contrato']) {
			$this->Edit("activo",'NO');
		}
		$this->Edit("id_usuario_modificador", $this->sesion->usuario->fields['id_usuario']);

		//if(!empty($this->extra_fields['glosa_asunto']) && empty($this->fields['glosa_contrato']) ) $this->Edit("glosa_contrato",$this->extra_fields['glosa_asunto']);

		$this->Edit("fono_contacto",$this->extra_fields['fono_contacto_contrato']);
		$this->Edit("email_contacto",$this->extra_fields['email_contacto_contrato']);
		$this->Edit("direccion_contacto",$this->extra_fields['direccion_contacto_contrato']);

		if( is_array($this->extra_fields['usuarios_retainer']) ) {
							$retainer_usuarios = implode(',',$usuarios_retainer);
			} else {
							$retainer_usuarios = $this->extra_fields['usuarios_retainer'];
			}
			$this->Edit("retainer_usuarios",$retainer_usuarios);

			if( isset( $this->extra_fields['esc_tiempo'] ) ) {
				for( $i = 1; $i <= sizeof($this->extra_fields['esc_tiempo']) ; $i++){
					if( $this->extra_fields['esc_tiempo'][$i-1] != '' ){
						$this->Edit('esc'.$i.'_tiempo', $this->extra_fields['esc_tiempo'][$i-1] );
						if( $this->extra_fields['esc_selector'][$i-1] != 1 ){
							//caso monto
							$this->Edit('esc'.$i.'_id_tarifa', "NULL");
							$this->Edit('esc'.$i.'_monto', $this->extra_fields['esc_monto'][$i-1]);
						} else {
							//caso tarifa
							$this->Edit('esc'.$i.'_id_tarifa', $this->extra_fields['esc_id_tarifa_'.$i]);
							$this->Edit('esc'.$i.'_monto', "NULL");
						}
						$this->Edit('esc'.$i.'_id_moneda', $this->extra_fields['esc_id_moneda_'.$i]);
						$this->Edit('esc'.$i.'_descuento', $this->extra_fields['esc_descuento'][$i-1]);
					} else {
						$this->Edit('esc'.$i.'_tiempo', "NULL");
						$this->Edit('esc'.$i.'_id_tarifa', "NULL");
						$this->Edit('esc'.$i.'_monto', "NULL");
						$this->Edit('esc'.$i.'_id_moneda', "NULL");
						$this->Edit('esc'.$i.'_descuento', "NULL");
					}
				}
			}
			if ($this->extra_fields['enviar_alerta_otros_correos'] == '1') {
				$correos_separados = explode(',', $this->fields['notificar_otros_correos']);
				for ($i = 0; $i < count($correos_separados); $i++) {
					$correos_separados[$i] = trim($correos_separados[$i]);
				}
				$notificar_otros_correos = implode(',', $correos_separados);
			} else {
				$notificar_otros_correos = '';
			}
			if (UtilesApp::GetConf($this->sesion, 'ExportacionLedes')) {
				$this->Edit('exportacion_ledes', empty($this->extra_fields['exportacion_ledes']) ? '0': '1');
			}
			if(isset($this->extra_fields['factura_rut']) && $this->extra_fields['factura_rut']!='') {
				$this->Edit('rut', $this->extra_fields['factura_rut']);
			}

	}

	//La funcion Write chequea que el objeto se pueda escribir al llamar a la funcion Check()
	function Write($enviar_mail_asunto_nuevo = true) {
		$this->error = "";
		$this->Prepare();

		if (!$this->Check())
			return false;
		if (empty($this->sesion->usuario->fields['id_usuario2'])) {
			$sql = "SELECT id_usuario FROM usuario WHERE rut LIKE '%99511620%' LIMIT 1";
			$resp = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
			list($id_usuario_modificador) = mysql_fetch_array($resp);
		} else {
			$id_usuario_modificador = $this->sesion->usuario->fields['id_usuario'];
		}
		if ($this->Loaded()) {
			$query = "UPDATE " . $this->tabla . " SET ";
			if ($this->guardar_fecha )
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


					$query_log = "INSERT INTO log_db SET id_field = '" . $this->fields[$this->campo_id] . "', titulo_tabla = '" . $this->tabla . "', campo_tabla = '" . $key . "', fecha = NOW(), usuario = '" . $this->sesion->usuario->fields['id_usuario'] . "',
						valor_antiguo = '" . mysql_real_escape_string($this->valor_antiguo[$key], $this->sesion->dbh) . "', valor_nuevo ='" . mysql_real_escape_string($val, $this->sesion->dbh)."'"  ;
					$resp_log = mysql_query($query_log, $this->sesion->dbh) or Utiles::errorSQL($query_log, __FILE__, __LINE__, $this->sesion->dbh);
					$this->logear[$key] = false;
				}
			}

			$query .= " WHERE " . $this->campo_id . "='" . $this->fields[$this->campo_id] . "'";
			if ($do_update) { //Solo en caso de que se haya modificado algún campo
				$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

				//Guarda ultimo cambio en la tabla historial de modificaciones
				$query3 = " INSERT INTO modificaciones_contrato
										(id_contrato,fecha_creacion,fecha_modificacion,id_usuario,id_usuario_responsable)
                                                                           VALUES ( '" . $this->fields['id_contrato'] . "', '" . $this->fields['fecha_creacion'] . "',
                                                                                    NOW(), '" . $id_usuario_modificador . "',
                                                                                    " . (!empty($this->fields['id_usuario_responsable']) ? $this->fields['id_usuario_responsable'] : "NULL" ) . " )";
				$resp3 = mysql_query($query3, $this->sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $this->sesion->dbh);
			}
			else //Retorna true ya que si no quiere hacer update la función corrió bien
				return true;
		}
		else {
			$query = "INSERT INTO " . $this->tabla . " SET ";
			if ($this->guardar_fecha && empty($this->fields['fecha_creacion']) )
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
				try {
						$arrayparams=null;
						$insertstatement=$this->sesion->pdodbh->prepare($query);
						$insertstatement->execute($arrayparams);
						$insertid=$this->sesion->pdodbh->lastInsertId();
						$this->fields['id_contrato']=$insertid;
						$this->Edit('id_contrato',$insertid);
						$this->Load($insertid);
						print_r($contrato->fields);
					} catch (PDOException $e) {
						 if($this->sesion->usuario->fields['rut'] == '99511620') {
							$Slim=Slim::getInstance('default',true);
							$arrayPDOException=array('File'=>$e->getFile(),'Line'=>$e->getLine(),'Mensaje'=>$e->getMessage(),'Query'=>$query,'Trace'=>json_encode($e->getTrace()),'Parametros'=>json_encode($arrayparamsdebug) );
							$Slim->view()->setData($arrayPDOException);
							 $Slim->applyHook('hook_error_sql');
					 }
						 Utiles::errorSQL($query, "", "",  NULL,"",$e );
						return false;
			}



			$query3 = " INSERT INTO modificaciones_contrato
										(id_contrato,fecha_creacion,fecha_modificacion,id_usuario,id_usuario_responsable)
                                                                           VALUES ( '" . $this->fields['id_contrato'] . "', NOW(),
                                                                                    NOW(), '" . $id_usuario_modificador . "',
                                                                                    " . (!empty($this->fields['id_usuario_responsable']) ? $this->fields['id_usuario_responsable'] : "NULL" ) . " )";
			$resp3 = mysql_query($query3, $this->sesion->dbh) or Utiles::errorSQL($query3, __FILE__, __LINE__, $this->sesion->dbh);

			if ($enviar_mail_asunto_nuevo) {
				// Mandar un email al encargado comercial
				if ( UtilesApp::GetConf($this->sesion, 'CorreosModificacionAdminDatos')){
					$CorreosModificacionAdminDatos = UtilesApp::GetConf($this->sesion, 'CorreosModificacionAdminDatos');
                                } else {
					$CorreosModificacionAdminDatos = '';
				}
				if ($CorreosModificacionAdminDatos != '') {
					// En caso de cambiar a avisar a más de un encargado editar el query y cambiar el if() por while()
                                        $id_responsable = $this->fields['id_usuario_responsable'];

                                        if (empty($id_responsable)){
                                            $email = $CorreosModificacionAdminDatos;
                                            $nombre = 'Usuario';

                                            $subject = 'Creación de contrato';

                                            // Obtener el nombre del cliente asociado al contrato.
                                            $query2 = 'SELECT glosa_cliente FROM cliente WHERE codigo_cliente=' . $this->fields['codigo_cliente'];
                                            $resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
                                            list($nombre_cliente) = mysql_fetch_array($resp2);

                                            // Revisar si el contrato está asociado a algún asunto.
                                            $query2 = 'SELECT glosa_asunto FROM asunto WHERE id_contrato_indep =' . $this->fields['id_contrato'];
                                            $resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
                                            if (list($glosa_asunto) = mysql_fetch_array($resp2))
                                                    $asunto_contrato = ' asociado al asunto ' . $glosa_asunto;
                                            else
                                                    $asunto_contrato = '';
                                            $mensaje = "Estimado " . $nombre . ": \r\n   El contrato del cliente " . $nombre_cliente . $asunto_contrato . " ha sido creado por " . $this->sesion->usuario->fields['nombre'] . ' ' . $this->sesion->usuario->fields['apellido1'] . ' ' . $this->sesion->usuario->fields['apellido2'] . " el día " . date('d-m-Y') . " a las " . date('H:i') . " en el sistema de Time & Billing.";

                                            Utiles::Insertar($this->sesion, $subject, $mensaje, $email, $nombre, false);

                                        } else {

                                            $query = "SELECT CONCAT_WS(' ', nombre, apellido1, apellido2) as nombre, email FROM usuario WHERE activo=1 AND id_usuario=" . $id_responsable;
                                            $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
                                            if (list($nombre, $email) = mysql_fetch_array($resp)) {

                                                    $email .= ',' . $CorreosModificacionAdminDatos;

                                                    $subject = 'Creación de contrato';

                                                    // Obtener el nombre del cliente asociado al contrato.
                                                    $query2 = 'SELECT glosa_cliente FROM cliente WHERE codigo_cliente=' . $this->fields['codigo_cliente'];
                                                    $resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
                                                    list($nombre_cliente) = mysql_fetch_array($resp2);

                                                    // Revisar si el contrato está asociado a algún asunto.
                                                    $query2 = 'SELECT glosa_asunto FROM asunto WHERE id_contrato_indep =' . $this->fields['id_contrato'];
                                                    $resp2 = mysql_query($query2, $this->sesion->dbh) or Utiles::errorSQL($query2, __FILE__, __LINE__, $this->sesion->dbh);
                                                    if (list($glosa_asunto) = mysql_fetch_array($resp2))
                                                            $asunto_contrato = ' asociado al asunto ' . $glosa_asunto;
                                                    else
                                                            $asunto_contrato = '';
                                                    $mensaje = "Estimado " . $nombre . ": \r\n   El contrato del cliente " . $nombre_cliente . $asunto_contrato . " ha sido creado por " . $this->sesion->usuario->fields['nombre'] . ' ' . $this->sesion->usuario->fields['apellido1'] . ' ' . $this->sesion->usuario->fields['apellido2'] . " el día " . date('d-m-Y') . " a las " . date('H:i') . " en el sistema de Time & Billing.";

                                                    Utiles::Insertar($this->sesion, $subject, $mensaje, $email, $nombre, false);
                                            }
                                    }
				}
			}
		}
		return true;
	}

	function ListaSelector($codigo_cliente, $onchange = null, $selected = null, $width = 320) {
		$query = "SELECT contrato.id_contrato, SUBSTRING(GROUP_CONCAT(glosa_asunto), 1, 70) AS asuntos
			FROM contrato
			JOIN cliente ON contrato.codigo_cliente = cliente.codigo_cliente
			JOIN asunto ON asunto.id_contrato = contrato.id_contrato
			WHERE cliente.codigo_cliente = '$codigo_cliente'
				AND asunto.activo = 1 AND contrato.activo = 'SI'
			GROUP BY contrato.id_contrato";
		return Html::SelectQuery($this->sesion, $query, 'id_contrato', $selected, empty($onchange) ? null : 'onchange=' . $onchange, __("Cualquiera"), $width);
	}

	/**
	 * @var UsuarioExt
	 */
	var $EncargadoComercial;

	public function EncargadoComercial() {
		if (!$this->Loaded()) {
			return null;
		}
		if (!isset($this->EncargadoComercial)) {
			$this->EncargadoComercial = new UsuarioExt($this->sesion);
			$this->EncargadoComercial->LoadId($this->fields['id_usuario_responsable']);
		}

		return $this->EncargadoComercial;
	}

	public function FillTemplate() {
		if (!$this->Loaded()) {
			return '';
		}

		// Buscar las entidades que no tienen relacion o clase
		$query = "SELECT * FROM cuenta_banco WHERE id_cuenta = '{$this->fields['id_cuenta']}'";
		$cuenta = $this->sesion->pdodbh->query($query)->fetch(PDO::FETCH_ASSOC);

		$query = "SELECT * FROM prm_banco WHERE id_banco = '{$cuenta['id_banco']}'";
		$banco = $this->sesion->pdodbh->query($query)->fetch(PDO::FETCH_ASSOC);

		$query = "SELECT * FROM prm_moneda WHERE id_moneda = '{$cuenta['id_moneda']}'";
		$moneda_cuenta = $this->sesion->pdodbh->query($query)->fetch(PDO::FETCH_ASSOC);

		$datos = array(
			'Id' => $this->fields['id_contrato'],
			'Contacto' => array(
				'Nombre' => $this->fields['contacto'],
				'Telefono' => $this->fields['fono_contacto'],
				'Email' => $this->fields['email_contacto'],
				'Direccion' => $this->fields['direccion_contacto']
			),
			'EncargadoComercial' => $this->EncargadoComercial()->FillTemplate(),
			'CuentaBancaria' => array(
				'Banco' => $banco['nombre'],
				'Numero' => $cuenta['numero'],
				'Moneda' => $moneda_cuenta['glosa_moneda_plural']
			)
		);

		return $datos;
	}

	/**
     * Find all user generators of matter
     */
    public static function contractGenerators($sesion, $contract_id) {
        $generators = array();
        $sql = "SELECT id_contrato_generador, id_cliente, id_contrato, prm_area_usuario.glosa as area_usuario,
                                     usuario.id_usuario, usuario.nombre,  usuario.apellido1, usuario.apellido2, porcentaje_genera
                            FROM contrato_generador
                         INNER JOIN usuario on contrato_generador.id_usuario = usuario.id_usuario
                     INNER JOIN prm_area_usuario on usuario.id_area_usuario = prm_area_usuario.id
                       WHERE contrato_generador.id_contrato=:contract_id
                    ORDER BY usuario.nombre ASC";

        $Statement = $sesion->pdodbh->prepare($sql);
        $Statement->bindParam('contract_id', $contract_id);
        $Statement->execute();

        while ($generator = $Statement->fetch(PDO::FETCH_OBJ)) {
            array_push($generators,
                array(
                    'id_contrato_generador' => $generator->id_contrato_generador,
                    'id_cliente' => $generator->id_cliente,
                    'id_contrato' => $generator->id_contrato,
                    'area_usuario' => $generator->area_usuario,
                    'id_usuario' => $generator->id_usuario,
                    'nombre' => $generator->apellido1 . ' ' . $generator->apellido2 . ' ' . $generator->nombre,
                    'porcentaje_genera' => $generator->porcentaje_genera,
                )
            );
        }

        return $generators;
    }

    /**
     * Delete a generators of matter
     */
    public static function deleteContractGenerator($sesion, $generator_id) {
        $sql = "DELETE FROM `contrato_generador` WHERE `contrato_generador`.`id_contrato_generador`=:generator_id";
        $Statement = $sesion->pdodbh->prepare($sql);
        $Statement->bindParam('generator_id', $generator_id);
        return $Statement->execute();
    }

    /**
     * Update a generator of matter
     */
    public static function updateContractGenerator($sesion, $generator_id, $percent_generator) {
        $sql = "UPDATE `contrato_generador`
                SET `contrato_generador`.`porcentaje_genera`    = :percent_generator
                WHERE `contrato_generador`.`id_contrato_generador` = :generator_id";

        $Statement = $sesion->pdodbh->prepare($sql);
        $Statement->bindParam('percent_generator', $percent_generator);
        $Statement->bindParam('generator_id', $generator_id);

        return $Statement->execute();
    }

    /**
     * Create a generator of matter
     */
    public static function createContractGenerator($sesion, $client_id, $contract_id, $user_id, $percent_generator) {
        $sql = "INSERT INTO `contrato_generador`
                SET `contrato_generador`.`id_cliente`=:client_id, `contrato_generador`.`id_contrato`=:contract_id,
                        `contrato_generador`.`id_usuario`=:user_id, `contrato_generador`.`porcentaje_genera`=:percent_generator ";

        $Statement = $sesion->pdodbh->prepare($sql);
        $Statement->bindParam('client_id', $client_id);
        $Statement->bindParam('contract_id', $contract_id);
        $Statement->bindParam('user_id', $user_id);
        $Statement->bindParam('percent_generator', $percent_generator);

        return $Statement->execute();
    }

	public static function QueriesPrevias($sesion) {
		$Contrato = new Contrato($sesion);
		$updateestado = "UPDATE trabajo SET estadocobro = 'SIN COBRO' WHERE id_cobro IS NULL;";
		$updateestado .= "UPDATE cta_corriente SET estadocobro = 'SIN COBRO' WHERE id_cobro IS NULL;";
		$updateestado .= "UPDATE tramite SET estadocobro = 'SIN COBRO' WHERE id_cobro IS NULL;";
		$updateestado .= "UPDATE trabajo JOIN cobro c ON trabajo.id_cobro = c.id_cobro SET trabajo.estadocobro = c.estado WHERE c.fecha_touch >= trabajo.fecha_touch;";
		$updateestado .= "UPDATE cta_corriente JOIN cobro c ON cta_corriente.id_cobro = c.id_cobro SET cta_corriente.estadocobro = c.estado WHERE c.fecha_touch >= cta_corriente.fecha_touch;";
		$updateestado .= "UPDATE tramite JOIN cobro c ON tramite.id_cobro = c.id_cobro SET tramite.estadocobro = c.estado WHERE c.fecha_touch >= tramite.fecha_touch;";

		$Contrato->sesion->pdodbh->exec($updateestado);
	}

}

class ListaContrato extends Lista {

	function ListaContrato($sesion, $params, $query) {
		$this->Lista($sesion, 'Contrato', $params, $query);
	}

}
