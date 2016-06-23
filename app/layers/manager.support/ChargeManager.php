<?php

class ChargeManager extends AbstractManager implements IChargeManager {

	/**
	 * Obtiene los adelantos utilizados en un cobro
	 * @param 	string $charge_id
	 * @return
	 */
	public function getAdvances($charge_id) {
		if (empty($charge_id) || !is_numeric($charge_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Document');

		$Documents = $this->DocumentService->findAll(
			CriteriaRestriction::equals('id_cobro', $charge_id),
			'id_documento'
		);

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
			->compare_with($Documents[0]->fields['id_documento']);

 		$DocumentPaymentsTransactions = $this->SearchManager->searchByCriteria($SearchCriteria);

		$result = array();
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

}
