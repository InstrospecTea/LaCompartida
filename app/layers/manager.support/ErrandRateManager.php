<?php

class ErrandRateManager extends AbstractManager implements IRateManager {

	public $Sesion;
	public $ErrandRate;

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;

		$this->loadService('ErrandRate');
		$this->loadService('ErrandValue');
		$this->ErrandRate = new ErrandRate();
	}

	/**
	 * Elimina una tarifa de trámites
	 * @param type int
	 */
	public function deleteErrandRate($errand_rate_id) {
		if (empty($errand_rate_id) || !is_numeric($errand_rate_id)) {
			throw new InvalidIdentifier;
		}

		try {
			$ErrandValue = $this->ErrandValueService->get($errand_rate_id);
			$this->ErrandValueService->delete($ErrandValue);

			$ErrandRate = $this->ErrandRateService->get($errand_rate_id);
			$this->ErrandRateService->delete($ErrandRate);
		} catch (EntityNotFound $e) {
			return null;
		}

		return true;
	}

	/**
	 * Actualiza una tarifa de trámites
	 * @param type int, array, array
	 */
	public function updateErrandRate($errand_rate, $rates) {
		try {
			$ErrandValue = $this->ErrandValueService->get($errand_rate['id_tramite_tarifa']);
			$this->ErrandValueService->delete($ErrandValue);
		} catch (PDOException $e) {
			echo $e->getMessage();
		} catch (ServiceException $e) {
			// Se deja pasar esta excepción ya que salta cuando la query no retorna datos.
		}

		$insertCriteria = new InsertCriteria($this->Sesion);
		$insertCriteria->setTable('tramite_valor');
		foreach ($rates as $key => $rate) {
			foreach ($rate as $pivot => $value) {
				$insertCriteria->addPivotWithValue($pivot, $value, true);
			}
			$insertCriteria->addInsert();
		}
		$insertCriteria->run();

		if ($errand_rate['tarifa_defecto'] == 1) {
			$query = 'UPDATE tramite_tarifa SET tarifa_defecto = 0;';
			$this->Sesion->pdodbh->query($query);
		}

		$this->ErrandRate->fillFromArray($errand_rate);
		$this->ErrandRateService->saveOrUpdate($this->ErrandRate);

		return true;
	}

	/**
	 * Inserta una tarifa de trámites
	 * @param type array, array
	 */
	public function insertErrandRate($errand_rate, $rates) {
		if ($errand_rate['tarifa_defecto'] == 1) {
			$query = 'UPDATE tramite_tarifa SET tarifa_defecto = 0;';
			$this->Sesion->pdodbh->query($query);
		}

		$this->ErrandRate->fillFromArray($errand_rate);
		$ErrandRate = $this->ErrandRateService->saveOrUpdate($this->ErrandRate);

		$response = new stdClass();
		if ($ErrandRate) {
			$response->success = true;
			$response->errand_rate_id = $ErrandRate->fields['id_tramite_tarifa'];
		} else {
			$response->success = false;
			$response->message = __('Ha ocurrido un error');
		}

		if (!$response->success) {
			return $response;
		}

		$insertCriteria = new InsertCriteria($this->Sesion);
		$insertCriteria->setTable('tramite_valor');
		foreach ($rates as $key => $rate) {
			foreach ($rate as $pivot => $value) {
				$insertCriteria->addPivotWithValue($pivot, $value, true);
			}
			$insertCriteria->addPivotWithValue('id_tramite_tarifa', $response->errand_rate_id);
			$insertCriteria->addInsert();
		}
		$insertCriteria->run();

		return $response;
	}

	/**
	 * Trae las tarifas de trámites
	 * @return Array
	 */
	public function getErrandsRate() {
		$searchCriteria = new SearchCriteria('ErrandRate');
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->searchByCriteria($searchCriteria);
	}

	/**
	 * Trae los campos (monedas) correspondientes a la tarifa trámite seleccionada para generar la tabla de llenado
	 * @return Array
	 */
	public function getErrandsRateFields() {
		$Criteria = new Criteria($this->Sesion);
		$rate_fields = $Criteria
			->add_select('tramite_tipo.id_tramite_tipo')
			->add_select('tramite_tipo.glosa_tramite')
			->add_select('prm_moneda.id_moneda')
			->add_select('prm_moneda.cifras_decimales')
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
		$Criteria = new Criteria($this->Sesion);
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
		$Criteria = new Criteria($this->Sesion);
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
		$Criteria = new Criteria($this->Sesion);
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
		$id_tramite_tarifa_defecto = $this->getDefaultErrandRate();

		$query = "UPDATE contrato SET id_tramite_tarifa = {$id_tramite_tarifa_defecto} WHERE id_tramite_tarifa = {$errand_rate_id}";
		$this->Sesion->pdodbh->query($query);

		return true;
	}

	/**
	 * Cuenta las tarifas de trámites del sistema
	 * @return int
	 */
	public function countErrandsRates() {
		$Criteria = new Criteria($this->Sesion);
		$num_contratos = $Criteria->add_select('COUNT(*)', 'num_rows')
			->add_from('tramite_tarifa')
			->run();

		return $num_contratos[0]['num_rows'];
	}

	/**
	 * Retorna la tarifa trámite por defecto
	 * @return int
	 */
	public function getDefaultErrandRate() {
		$Criteria = new Criteria($this->Sesion);
		$id_tramite_tarifa = $Criteria->add_select('id_tramite_tarifa', 'id_tramite_tarifa')
			->add_from('tramite_tarifa')
			->add_restriction(CriteriaRestriction::equals('tarifa_defecto', 1))
			->run();

		return $id_tramite_tarifa[0]['id_tramite_tarifa'];
	}
}
