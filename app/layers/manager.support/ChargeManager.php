<?php

class ChargeManager extends AbstractManager implements IChargeManager {

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->loadService('Charge');
	}

	/**
	 * Obtiene los adelantos utilizados en un cobro
	 * @param 	string $charge_id
	 * @return
	 */
	public function getAdvances($charge_id) {
		$result = array();

		if (empty($charge_id) || !is_numeric($charge_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Document');

		$Document = $this->DocumentService->findFirst(
			CriteriaRestriction::equals('id_cobro', $charge_id),
			'id_documento'
		);

		if ($Document === false) {
			return $result;
		}

		$this->loadManager('Search');
		$SearchCriteria = new SearchCriteria('DocumentPaymentsTransactions');

		$SearchCriteria
			->related_with('Document')
			->with_direction('LEFT')
			->on_property('id_documento')
			->on_entity_property('id_documento_pago');

		$SearchCriteria
			->filter('es_adelanto')
			->for_entity('Document')
			->restricted_by('equals')
			->compare_with(1);

		$SearchCriteria
			->filter('id_documento_cobro')
			->restricted_by('equals')
			->compare_with($Document->get('id_documento'));

		$DocumentPaymentsTransactions = $this->SearchManager->searchByCriteria($SearchCriteria);

		foreach ($DocumentPaymentsTransactions as $key => $value) {
			$DocumentAdvance = $this->DocumentService->get($value->get('id_documento_pago'), 'glosa_documento');
			$amount = $value->get('valor_pago_gastos') + $value->get('valor_pago_honorarios');
			$element = array(
				'id_neteo_documento' => $value->get('id_neteo_documento'),
				'monto' => $amount,
				'glosa' => $DocumentAdvance->get('glosa_documento'),
				'fecha' => $value->get('fecha_creacion')
			);
			array_push($result, $element);
		}

		return $result;
	}

	public function findAll($restrictions = null, $fields = null, $order = null, $limit = null) {
		return $this->ChargeService->findAll($restrictions, $fields, $order, $limit);
	}

	/**
	 * @param $id
	 * @return mixed
	 * @throws ServiceException
	 */
	public function get($id, $fields = null) {
		return $this->ChargeService->get($id, $fields);
	}

	/**
	 * @return mixed
	 * @throws ServiceException
	 */
	public function count() {
		return $this->ChargeService->count();
	}

	public function getCurrencyRates($charge_id) {
		if (empty($charge_id) || !is_numeric($charge_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadManager('CurrencyCharge');
		return $this->CurrencyChargeManager->findAll(CriteriaRestriction::equals('id_cobro', $charge_id));
	}

	public function forceIssue($charge_id) {
		if (empty($charge_id) || !is_numeric($charge_id)) {
			throw new InvalidIdentifier;
		}

		$Cobro = new Cobro($this->Sesion);
		$Cobro->Load($charge_id);
		$Cobro->fields['estado'] = 'EN REVISION';
		$Cobro->GuardarCobro(true);
	}

}
