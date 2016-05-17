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

		$Client = $this->getClient($client_id);

		if ($Client->getSize() === 0) {
			return $emptyContract;
		}

		$ContractManager = new ContractManager($this->Sesion);
		$Contract = $ContractManager->getContract($Client[0]->fields['id_contrato']);

		if ($Contract->getSize() > 0) {
			return $Contract[0];
		}

		return $emptyContract;
	}

	/**
	 * Obtiene un cliente mediate su id
	 * @param 	string $client_id
	 * @return 	SplFixedArray
	 */
	public function getClient($client_id = null) {
		$emptySplFixedArray = new SplFixedArray();

		if (is_null($client_id)) {
			return $emptySplFixedArray;
		}

		$this->loadManager('Search');
		$searchCriteriaClient = new SearchCriteria('Client');

		$searchCriteriaClient
			->filter('id_cliente')
			->restricted_by('equals')
			->compare_with(intval($client_id));

		$Client = $this->SearchManager->searchByCriteria($searchCriteriaClient);

		if ($Client->getSize() === 0) {
			return $emptySplFixedArray;
		}

		return $Client;
	}

}
