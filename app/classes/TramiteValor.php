<?php

require_once dirname(__FILE__) . '/../conf.php';

class TramiteValor extends Objeto {

	function TramiteValor($sesion, $fields = "", $params = "") {
		$this->tabla = "tramite_valor";
		$this->campo_id = "id_tramite_valor";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	/**
	 * Carga a través de ID_TARIF, ID_USUARIO, ID_MONEDA la tarifa(monto)
	 * @param integer $id_tramite_valor
	 * @param integer $id_tramite_tipo
	 * @param integer $id_moneda
	 */
	function LoadById($id_tramite_valor, $id_tramite_tipo, $id_moneda) {
		$query = "SELECT tarifa FROM tramite_valor WHERE id_tramite_valor = '$id_tramite_valor' AND id_moneda = '$id_moneda' AND id_tramite_tipo = '$id_tramite_tipo' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($tarifa) = mysql_fetch_array($resp);
		return $tarifa;
	}

	/**
	 * Guardando Tarifa
	 * @param integer $id_tramite_tarifa
	 * @param integer $id_tramite_tipo
	 * @param integer $id_moneda
	 * @param integer $valor
	 */
	function GuardarTarifa($id_tramite_tarifa, $id_tramite_tipo, $id_moneda, $valor) {
		$valor = str_replace(',', '.', $valor);
		if (empty($id_tramite_tarifa)) {
			$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			list( $id_tramite_tarifa ) = mysql_fetch_array($resp);
		}
		if (empty($id_tramite_tarifa))
			return false;
		if ($valor == '') {
			$query = "DELETE FROM tramite_valor WHERE id_moneda = '$id_moneda' AND id_tramite_tipo = '$id_tramite_tipo' AND id_tramite_tarifa = '$id_tramite_tarifa'";
		} else {
			$query = "INSERT tramite_valor SET id_moneda = '$id_moneda',
								id_tramite_tipo = '$id_tramite_tipo', id_tramite_tarifa = '$id_tramite_tarifa' , tarifa = '$valor'
								ON DUPLICATE KEY UPDATE tarifa = '$valor'";
		}
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		return true;
	}

	/**
	 * Find all Errand Values with the given params
	 */
	function findAll($params = array()) {
		$sql = $this->SearchQuery($params);

		$Statement = $this->sesion->pdodbh->prepare($sql);
		$Statement->execute();

		$values = $Statement->fetchAll(PDO::FETCH_ASSOC);

		return $values;
	}

	/**
	 * Get the SQL Query to get all the errand values with the given params
	 */
	public function SearchQuery($params) {
		$query = "SELECT
				tv.id_tramite_valor,
				tv.id_tramite_tipo,
				tv.id_tramite_tarifa,
				tv.id_moneda,
				tv.tarifa,
				m.glosa_moneda,
				m.glosa_moneda_plural,
				m.simbolo AS simbolo_moneda,
				m.codigo AS codigo_moneda
			FROM tramite_valor tv
			INNER JOIN prm_moneda m ON tv.id_moneda = m.id_moneda";

		$wheres = array();

		if (!empty($params['id_tramite_tipo'])) {
			$wheres[] = "tv.id_tramite_tipo = '{$params['id_tramite_tipo']}'";
		}

		if (!empty($params['id_moneda'])) {
			$wheres[] = "tv.id_moneda = '{$params['id_moneda']}'";
		}

		if (!empty($params['id_tramite_tarifa'])) {
			$wheres[] = "tv.id_tramite_tarifa = '{$params['id_tramite_tarifa']}'";
		}

		if (count($wheres) > 0) {
			$query .= " WHERE " . implode(' AND ', $wheres);
		}

		return $query;
	}

}
