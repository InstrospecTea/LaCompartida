<?php

class RatingBusiness extends AbstractBusiness implements IRatingBusiness {

	/**
	 * Elimina una tarifa de trámites
	 * @param type int
	 */
	public function deleteErrandRate($errand_rate_id) {
		$query = "DELETE FROM tramite_valor WHERE id_tramite_tarifa = '{$errand_rate_id}'";
		$this->Session->pdodbh->query($query);

		$query = "DELETE FROM tramite_tarifa WHERE id_tramite_tarifa = '{$errand_rate_id}'";
		$this->Session->pdodbh->query($query);

		return true;
	}

	/**
	 * Actualiza una tarifa de trámites
	 * @param type int, array, array
	 */
	public function updateErrandRate($errand_rate_id, $errand_rate, $rates) {
		$query = "DELETE FROM tramite_valor WHERE id_tramite_tarifa = '{$errand_rate_id}'";
		$this->Session->pdodbh->query($query);

		$insertCriteria = new InsertCriteria($this->Session);
		$insertCriteria->set_into('tramite_valor');
		foreach ($rates as $key => $rate) {
			foreach ($rate as $pivot => $value) {
				$insertCriteria->add_pivot_with_value($pivot, $value, true);
			}
			$insertCriteria->add_insert();
		}

		$insertCriteria->run();

		if ($errand_rate['tarifa_defecto'] == 1) {
			$query = 'UPDATE tramite_tarifa SET tarifa_defecto = 0;';
			$this->Session->pdodbh->query($query);
		}

		$query = 'UPDATE tramite_tarifa SET ';
		$values = array();
		foreach ($errand_rate as $key => $value) {
			$values[] = "{$key} = '{$value}'";
		}

		$query .= implode(', ', $values) . " WHERE id_tramite_tarifa = '{$errand_rate_id}';";
		$this->Session->pdodbh->query($query);

		return true;
	}

	/**
	 * Inserta una tarifa de trámites
	 * @param type array, array
	 */
	public function insertErrandRate($errand_rate, $rates) {
		if ($errand_rate['tarifa_defecto'] == 1) {
			$query = 'UPDATE tramite_tarifa SET tarifa_defecto = 0;';
			$this->Session->pdodbh->query($query);
		}

		$errand_rate['fecha_creacion'] = 'NOW()';

		$insertCriteria = new InsertCriteria($this->Session);
		$insertCriteria->set_into('tramite_tarifa');

		foreach ($errand_rate as $key => $value) {
			$insertCriteria->add_pivot_with_value($key, $value, true);
		}

		$result = $insertCriteria->run();

		$response = new stdClass();
		if ($result->success) {
			$response->success = true;
			$response->rate_id = $insertCriteria->get_last_insert_id();
		} else {
			$response->success = false;
			$response->message = $result->message;
		}

		if (!$response->success) {
			return $response;
		}

		$insertCriteria = new InsertCriteria($this->Session);
		$insertCriteria->set_into('tramite_valor');
		foreach ($rates as $key => $rate) {
			foreach ($rate as $pivot => $value) {
				$insertCriteria->add_pivot_with_value($pivot, $value, true);
			}
			$insertCriteria->add_pivot_with_value('id_tramite_tarifa', $response->rate_id);
			$insertCriteria->add_insert();
		}

		$insertCriteria->run();

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
			->add_restriction(CriteriaRestriction::equals('id_tramite_tarifa', $errand_rate_id))
			->run();

		return $num_contratos[0];
	}

	/**
	 * Actualiza la tarifa trámite por defento en los contratos
	 * @return boolean
	 */
	public function updateDefaultErrandRateOnContracts($errand_rate_id) {
		$Criteria = new Criteria($this->Session);
		$id_tramite_tarifa = $Criteria->add_select('id_tramite_tarifa', 'id_tramite_tarifa')
			->add_from('tramite_tarifa')
			->add_restriction(CriteriaRestriction::equals('tarifa_defecto', 1))
			->run();

		$id_tramite_tarifa_defecto = $id_tramite_tarifa[0]['id_tramite_tarifa'];

		$query = "UPDATE contrato SET id_tramite_tarifa = {$id_tramite_tarifa_defecto} WHERE id_tramite_tarifa = {$errand_rate_id}";
		$this->Session->pdodbh->query($query);

		return true;
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
