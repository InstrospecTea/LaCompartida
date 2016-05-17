<?php

class ClientManager extends AbstractManager implements IClientManager {

	/**
	 * Obtiene el contrato principal de un cliente
	 * @param 	string $client_id
	 * @return 	Contrato
	 */
	public function getDefaultContract($client_id = null) {
		$emptyContract = new Contract();
		if (is_null($client_id)) {
			return $emptyContract;
		}

		$this->loadManager('Search');
		$searchCriteriaClient = new SearchCriteria('Client');

		$searchCriteriaClient
			->filter('id_cliente')
			->restricted_by('equals')
			->compare_with($client_id);

		$Client = $this->SearchManager->searchByCriteria($searchCriteriaClient);

		if ($Client->getSize() === 0) {
			return $emptyContract;
		}

		$searchCriteriaContract = new SearchCriteria('Contract');
		$searchCriteriaContract
			->filter('id_contrato')
			->restricted_by('equals')
			->compare_with($Client[0]->fields['id_contrato']);

		$Contract = $this->SearchManager->searchByCriteria($searchCriteriaContract);

		if ($Contract->getSize() > 0) {
			return $Contract[0];
		}

		return $emptyContract;
	}

}
