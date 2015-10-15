<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

#Fechas de los cobros estimados de los contratos
class CobroPendiente extends Objeto {
	function CobroPendiente($sesion, $fields = "", $params = "") {
		$this->tabla = "cobro_pendiente";
		$this->campo_id = "id_cobro_pendiente";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	#asocia los cobros pendientes por fecha y contrato
	function AsociarCobro($sesion,$id_cobro) {
		$query = "UPDATE cobro_pendiente SET id_cobro='$id_cobro' WHERE id_cobro_pendiente='".$this->fields['id_cobro_pendiente']."'";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
		return true;
	}

	#se borran todas las fechas del contrato
	function EliminarPorContrato($sesion,$id_contrato) {
		$query = "DELETE FROM cobro_pendiente WHERE id_contrato = '$id_contrato' AND id_cobro IS NULL";
		mysql_query($query, $sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$sesion->dbh);
	}

	/**
	 * Funcion que se corre una vez al mes por cron para generar las nuevas liquidaciones programados
	 * considerando las siguientes reglas:
	 *
	 * - Se considerán los contratos activos que tengan liquidaciones programadas (con una fecha de inicio)
	 * - Solo se podrá generar un máximo de 2 liquidaciones pendientes de facturar
	 * - Solo se podrá generar un máximo de liquidaciones pendientes de acuerdo a las repeticiones configuradas
	 * - No se generarán liquidaciones con mas de 2 meses de anticipación
	 * - No se consideran los cobros pendientes de HITOS
	 *
	 * @param $Sesion
	 */
	function GenerarCobrosPeriodicos($Sesion) {

		$query = "SELECT
				c.id_contrato,
				c.periodo_fecha_inicio,
				c.periodo_intervalo,
				c.periodo_repeticiones,
				c.enviar_liquidacion_al_generar,
				c.monto,
				c.forma_cobro,
				GREATEST(MAX(cp.fecha_cobro), c.periodo_fecha_inicio) AS ultima_fecha,
				SUM(IF(cp.id_contrato IS NOT NULL AND cp.id_cobro IS NOT NULL, 1, 0)) AS pendientes_cobrados,
				SUM(IF(cp.id_contrato IS NOT NULL, 1, 0)) AS pendientes_totales
			FROM contrato c
			LEFT JOIN cobro_pendiente cp
				ON cp.id_contrato = c.id_contrato
				AND cp.hito = 0
			WHERE c.activo = 'SI'
				AND c.forma_cobro != 'HITOS'
				-- REF: https://dev.mysql.com/doc/refman/5.5/en/sql-mode.html#sqlmode_no_zero_date
				AND c.periodo_fecha_inicio != '0000-00-00'
				AND DATE(c.periodo_fecha_inicio) IS NOT NULL
			GROUP BY c.id_contrato";

		$result = mysql_query($query, $Sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $Sesion->dbh);
		while ($contrato = mysql_fetch_array($result)) {
			$MAX_PENDING_MONTH_DIFF = 2;
			$MAX_PENDING_UNBILLED = 2;

			$max_pending_totals = $contrato['periodo_repeticiones'] > 0 ? $contrato['periodo_repeticiones'] : INF;

			$pending_total = $contrato['pendientes_totales'];
			$pending_billed = $contrato['pendientes_cobrados'];
			$pending_unbilled = $pending_total - $pending_billed;

			$today = date_create('now');
			$months = $contrato['periodo_intervalo'];

			if ($pending_total == 0) {
				// Si no tiene pendientes creo el primero para la fecha de inicio
				$next_date = date_create($contrato['periodo_fecha_inicio']);
			} else {
				// De lo contrario, ocupo la siguiente fecha desde la última
				$next_date = date_create($contrato['ultima_fecha']);
				date_add($next_date, date_interval_create_from_date_string("+{$months} months"));
			}

			$interval_diff = date_diff($today, $next_date);
			$months_diff = $interval_diff->m;

			if ($pending_total < $max_pending_totals
					&& $pending_unbilled < $MAX_PENDING_UNBILLED
					&& $months_diff < $MAX_PENDING_MONTH_DIFF) {

				$next_date = date_format($next_date, 'Y-m-d');
				$next_description = __('Cobro') . " N° " . ($pending_total + 1);
				$next_amount = in_array($contrato['forma_cobro'], array('FLAT FEE', 'RETAINER')) ? $contrato['monto'] : 0;

				$CobroPendiente = new CobroPendiente($Sesion);
				$CobroPendiente->Edit("id_contrato", $contrato['id_contrato']);
				$CobroPendiente->Edit("fecha_cobro", $next_date);
				$CobroPendiente->Edit("descripcion", $next_description);
				$CobroPendiente->Edit("monto_estimado", $next_amount);
				$CobroPendiente->Edit("hito", '0');
				$CobroPendiente->Write();
			}
		}

		return true;
	}

	function MontoHitosPorLiquidar($contrato) {
		$sql = "SELECT SUM(monto_estimado) AS monto FROM cobro_pendiente WHERE id_cobro IS NULL AND hito = 1 AND id_contrato = " . $contrato;
		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		list($monto) = mysql_fetch_array($query);
		return empty($monto) ? 0 : $monto;
	}

	function MontoHitosLiquidados($contrato) {
		$sql = "SELECT SUM(cobro_pendiente.monto_estimado) AS monto
		FROM cobro_pendiente
			JOIN cobro ON cobro_pendiente.id_cobro = cobro.id_cobro
		WHERE cobro_pendiente.id_cobro IS NOT NULL AND
		cobro_pendiente.hito = 1 AND
		cobro_pendiente.id_contrato = " . $contrato . " AND
		cobro.estado NOT IN ('PAGADO')";
		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		list($monto) = mysql_fetch_array($query);
		return empty($monto) ? 0 : $monto;
	}

	function MontoHitosPagados($contrato) {
		$sql = "SELECT SUM(cobro_pendiente.monto_estimado) AS monto
		FROM cobro_pendiente
			JOIN cobro ON cobro_pendiente.id_cobro = cobro.id_cobro
		WHERE cobro_pendiente.id_cobro IS NOT NULL AND
		cobro_pendiente.hito = 1 AND
		cobro_pendiente.id_contrato = " . $contrato . " AND
		cobro.estado IN ('PAGADO')";
		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		list($monto) = mysql_fetch_array($query);
		return empty($monto) ? 0 : $monto;
	}

	function ObtenerHitosCumplidosParaCorreos() {
		$sql = "SELECT
			cobro_pendiente.id_cobro_pendiente,
			cobro_pendiente.descripcion,
			cobro_pendiente.monto_estimado,
			cobro_pendiente.id_contrato,
			contrato.id_usuario_responsable,
			contrato.id_usuario_secundario,
			prm_moneda.simbolo,
			prm_moneda.cifras_decimales,
			cliente.glosa_cliente,
			cliente.codigo_cliente,
			cobro_pendiente.fecha_cobro,
			GROUP_CONCAT(asunto.glosa_asunto SEPARATOR ', ') as asuntos
		FROM
			cobro_pendiente
			LEFT JOIN contrato ON cobro_pendiente.id_contrato = contrato.id_contrato
			LEFT JOIN usuario ON contrato.id_usuario_responsable = usuario.id_usuario
			LEFT JOIN prm_moneda ON contrato.id_moneda_monto = prm_moneda.id_moneda
			LEFT JOIN cliente ON contrato.codigo_cliente = cliente.codigo_cliente
			LEFT JOIN asunto ON contrato.id_contrato = asunto.id_contrato
		WHERE
			cobro_pendiente.hito = 1 AND
			cobro_pendiente.notificado = 0 AND
			cobro_pendiente.fecha_cobro IS NOT NULL AND
			cobro_pendiente.fecha_cobro <= NOW() AND
			cobro_pendiente.id_cobro IS NULL AND
			(contrato.id_usuario_responsable IS NOT NULL OR contrato.id_usuario_secundario IS NOT NULL)
		GROUP BY cobro_pendiente.id_cobro_pendiente
		";

		$query = mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);

		$cliente_hitos = array(); //la estructura es usuario->cliente->contrato->(asunto,detalles,lista_hitos)
		while ($hito = mysql_fetch_array($query)) {
			//Monto con simbolo
			$idioma = new Objeto($this->sesion, '', '', 'prm_idioma', 'codigo_idioma');
			$idioma->Load(strtolower(UtilesApp::GetConf($this->sesion, 'Idioma')));
			$monto_hito_simbolo = $hito['simbolo'] . " " . number_format($hito['monto_estimado'], $hito['cifras_decimales'], $idioma->fields['separador_decimales'], $idioma->fields['separador_miles']);

			//Responsables
			$usuarios_responsable = array();
			if (!empty($hito['id_usuario_responsable'])) {
				$usuarios_responsable[$hito['id_usuario_responsable']] = $hito['id_usuario_responsable'];
			}
			if (!empty($hito['id_usuario_secundario'])) {
				$usuarios_responsable[$hito['id_usuario_secundario']] = $hito['id_usuario_secundario'];
			}
			if (empty($usuarios_responsable)) {
				continue;
			}

			foreach ($usuarios_responsable as $usuario_responsable) {
				if (!isset($cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['cliente'])) {
					$cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['cliente'] = array("glosa_cliente" => $hito['glosa_cliente']);
				}
				if (!isset($cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['contratos'][$hito['id_contrato']])) {
					$cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['contratos'][$hito['id_contrato']] = array(
						"asuntos" => $hito['asuntos'],
						"monto_por_liquidar" => $this->MontoHitosPorLiquidar($hito['id_contrato']),
						"monto_liquidado" => $this->MontoHitosLiquidados($hito['id_contrato']),
						"pagado" => $this->MontoHitosPagados($hito['id_contrato'])
					);
				}
				$cliente_hitos[$usuario_responsable][$hito['codigo_cliente']]['contratos'][$hito['id_contrato']]['hitos'][] = array(
					'descripcion' => $hito['descripcion'],
					'monto_estimado' => $monto_hito_simbolo,
					'fecha_cobro' => $hito['fecha_cobro']
				);
			}

			$sql = "UPDATE cobro_pendiente SET notificado = 1 WHERE hito = 1 AND id_cobro_pendiente = " . $hito['id_cobro_pendiente'];
			mysql_query($sql, $this->sesion->dbh) or Utiles::errorSQL($sql, __FILE__, __LINE__, $this->sesion->dbh);
		}

		return $cliente_hitos;
	}

	/**
	 * Obtiene el primer registro de pago pendiente para el cobro
	 * @param $id_cobro
	 * @return bool
	 * @throws Exception
	 */
	public function LoadFirstByIdCobro($id_cobro) {
		$result = array();
		$Criteria = new Criteria($this->sesion);
		$Criteria->add_select('fecha_cobro')->add_select('monto_estimado')->add_select('descripcion')->add_select('observaciones')->add_select('id_contrato')->add_from('cobro_pendiente');
		$Criteria->add_restriction(CriteriaRestriction::and_clause(array("id_cobro = $id_cobro", "hito = 1")))->add_limit(1);
		$cobro_pendiente = $Criteria->run();
		if (count($cobro_pendiente) > 0) {
			$result = $cobro_pendiente[0];
		}
		return $result;
	}
}
