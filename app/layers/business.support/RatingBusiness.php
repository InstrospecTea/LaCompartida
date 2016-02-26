<?php

class RatingBusiness extends AbstractBusiness implements IRatingBusiness {

	/**
	 * Elimina una tarifa de trámites
	 * @param type int
	 */
	public function deleteErrandRate($errand_rate_id) {
		$query = "DELETE FROM tramite_valor WHERE id_tramite_tarifa = '{$errand_rate_id}'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = "DELETE FROM tramite_tarifa WHERE id_tramite_tarifa = '{$errand_rate_id}'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		return true;
	}

	/**
	 * Actualiza una tarifa de trámites
	 * @param type int, array, array
	 */
	public function updateErrandRate($errand_rate_id, $errand_rate, $rates) {
		$query = "DELETE FROM tramite_valor WHERE id_tramite_tarifa = '{$errand_rate_id}'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$query = 'INSERT INTO tramite_valor (id_tramite_tipo, id_moneda, tarifa, id_tramite_tarifa) VALUES ';
		$values = array();
		foreach ($rates as $rate) {
			$rate_values = implode(', ', $rate);
			$values[] = "({$rate_values})";
		}
		$query .= implode(', ', $values) . ';';
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($errand_rate['tarifa_defecto'] == 1) {
			$query = 'UPDATE tramite_tarifa SET tarifa_defecto = 0;';
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}

		$query = 'UPDATE tramite_tarifa SET ';
		$values = array();
		foreach ($errand_rate as $key => $value) {
			$values[] = "{$key} = '{$value}'";
		}

		$query .= implode(', ', $values) . " WHERE id_tramite_tarifa = '{$errand_rate_id}';";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		return true;
	}

	/**
	 * Inserta una tarifa de trámites
	 * @param type array, array
	 */
	public function insertErrandRate($errand_rate, $rates) {
		if ($errand_rate['tarifa_defecto'] == 1) {
			$query = 'UPDATE tramite_tarifa SET tarifa_defecto = 0;';
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		}

		$errand_rate['fecha_creacion'] = 'NOW()';

		$query = 'INSERT INTO tramite_tarifa (glosa_tramite_tarifa, tarifa_defecto, fecha_creacion) VALUES (' . implode(', ', $errand_rate) . ');';;
		$result = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$response = new stdClass();
		$response->success = $result == true ? true : false;
		$response->rate_id = mysql_insert_id($this->sesion->dbh);

		if (!$response->success) {
			return $response;
		}

		foreach ($rates as $key => $value) {
			$rates[$key]['id_tramite_tarifa'] = $response->rate_id;
		}

		$query = 'INSERT INTO tramite_valor (id_tramite_tipo, id_moneda, tarifa, id_tramite_tarifa) VALUES ';
		$values = array();
		foreach ($rates as $rate) {
			$rate_values = implode(', ', $rate);
			$values[] = "({$rate_values})";
		}
		$query .= implode(', ', $values) . ';';
		$result = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		$response->success = $result == true ? true : false;

		return $response;
	}

	/**
	 * Trae las tarifas de trámites
	 * @return Array
	 */
	public function getErrandsRate() {
		$Criteria = new Criteria($this->Session);
		$errands_rate = $Criteria
			->add_select('id_tramite_tarifa')
			->add_select('glosa_tramite_tarifa')
			->add_select('tarifa_defecto')
			->add_from('tramite_tarifa')
			->add_ordering('glosa_tramite_tarifa')
			->run();

		return $errands_rate;
	}

	/**
	 * Trae los campos (monedas) correspondientes a la tarifa trámite seleccionada para generar la tabla de llenado
	 * @return Array
	 */
	public function getErrandsRateFields() {
		$Criteria = new Criteria($this->Session);
		$rate_fields = $Criteria
			->add_select('tramite_tipo.id_tramite_tipo')
			->add_select('tramite_tipo.glosa_tramite')
			->add_select('prm_moneda.id_moneda')
			->add_from('tramite_tipo')
			->add_inner_join_with('prm_moneda', 'prm_moneda.id_moneda')
			->add_ordering('tramite_tipo.glosa_tramite')
			->add_ordering('tramite_tipo.id_tramite_tipo')
			->add_ordering('prm_moneda.id_moneda')
			->run();

		return $rate_fields;
	}

	/**
	 * Trae los valores de la tarifa trámite seleccionada
	 * @param type int
	 * @return Array
	 */
	public function getErrandsRateValue($errand_rate_id) {
		$Criteria = new Criteria($this->Session);
		$errands_rate_values = $Criteria
			->add_select('tramite_valor.id_tramite_tipo')
			->add_select("IF(tramite_valor.tarifa >= 0,tramite_valor.tarifa,'')", 'tarifa')
			->add_select('tramite_valor.id_moneda')
			->add_from('tramite_valor')
			->add_inner_join_with('tramite_tipo', 'tramite_valor.id_tramite_tipo = tramite_tipo.id_tramite_tipo')
			->add_restriction(CriteriaRestriction::equals('tramite_valor.id_tramite_tarifa', $errand_rate_id))
			->add_ordering('tramite_tipo.glosa_tramite')
			->add_ordering('tramite_tipo.id_tramite_tipo')
			->run();

		return $errands_rate_values;
	}

	/**
	 * Trae la el detalle de la tarifa trámite seleccionada
	 * @return Array
	 */
	public function getErrandRateDetail($errand_rate_id) {
		$Criteria = new Criteria($this->Session);
		$errand_rate_detail = $Criteria
			->add_select('id_tramite_tarifa')
			->add_select('glosa_tramite_tarifa')
			->add_select('tarifa_defecto')
			->add_from('tramite_tarifa')
			->add_restriction(CriteriaRestriction::equals('id_tramite_tarifa', $errand_rate_id))
			->add_ordering('glosa_tramite_tarifa')
			->run();

		return $errand_rate_detail[0];
	}

	/**
	 * Trae el total de contratos asociados a la tarifa trámite que se desea eliminar
	 * @return int
	 */
	public function getContractsWithErrandRate($errand_rate_id) {
		$Criteria = new Criteria($this->Session);
		$num_contratos = $Criteria->add_select('COUNT(*)', 'num_rows')
			->add_from('contrato')
			->add_restriction(CriteriaRestriction::equals('id_tramite_tarifa', $_REQUEST['id_tarifa']))
			->run();

		return $num_contratos[0];
	}

	/**
	 * Actualiza la tarifa trámite por defento en los contratos
	 * @return boolean
	 */
	public function updateDefaultErrandRateOnContract($errand_rate_id) {
		$query = "SELECT id_tramite_tarifa FROM tramite_tarifa WHERE tarifa_defecto = 1 ORDER BY id_tramite_tarifa ASC LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id_tramite_tarifa_defecto) = mysql_fetch_array($resp);

		$query = "UPDATE contrato SET id_tramite_tarifa = $id_tramite_tarifa_defecto WHERE id_tramite_tarifa = '{$errand_rate_id}'";
		$result = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		return $result;
	}

	/**
	 * Cuenta las tarifas de trámites del sistema
	 * @return int
	 */
	public function countRates() {
		$Criteria = new Criteria($this->Session);
		$num_contratos = $Criteria->add_select('COUNT(*)', 'num_rows')
			->add_from('tramite_tarifa')
			->run();

		return $num_contratos[0]['num_rows'];
	}
}
