<?php

require_once dirname(__FILE__) . '/../conf.php';

class ContratoTramite extends Objeto {

	function ContratoTramite($sesion, $fields = "", $params = "") {
		$this->tabla = "contrato_tramite";
		$this->campo_id = "id_contrato_tramite";
		$this->sesion = $sesion;
		$this->fields = $fields;
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
				ct.id_contrato_tramite,
				ct.id_tramite_tipo,
				ct.id_contrato,
				c.id_tramite_tarifa,
				c.id_moneda_tramite,
				tv.tarifa AS tarifa_tramite,
				tt.glosa_tramite,
				tt.duracion_defecto,
				m.glosa_moneda,
				m.glosa_moneda_plural,
				m.simbolo AS simbolo_moneda,
				m.codigo AS codigo_moneda
			FROM contrato_tramite ct
			INNER JOIN contrato c ON c.id_contrato = ct.id_contrato
			INNER JOIN tramite_tipo tt ON tt.id_tramite_tipo = ct.id_tramite_tipo
			INNER JOIN tramite_valor tv ON
				tv.id_tramite_tipo = ct.id_tramite_tipo
				AND tv.id_tramite_tarifa = c.id_tramite_tarifa
				AND tv.id_moneda = c.id_moneda_tramite
			INNER JOIN prm_moneda m ON tv.id_moneda = m.id_moneda";

		$wheres = array();

		if (!empty($params['id_contrato'])) {
			$wheres[] = "ct.id_contrato = '{$params['id_contrato']}'";
		}

		if (count($wheres) > 0) {
			$query .= " WHERE " . implode(' AND ', $wheres);
		}

		return $query;
	}

}
