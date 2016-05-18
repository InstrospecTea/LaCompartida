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

		if (is_null($Client)) {
			return $emptyContract;
		}

		$ContractManager = new ContractManager($this->Sesion);
		$Contract = $ContractManager->getContract($Client->fields['id_contrato']);

		if (!is_null($Contract)) {
			return $Contract;
		}

		return $emptyContract;
	}

	/**
	 * Obtiene un cliente mediate su id
	 * @param 	string $client_id
	 * @return 	Client
	 */
	public function getClient($client_id = null) {
		if (is_null($client_id)) {
			return null;
		}

		$this->loadManager('Search');
		$searchCriteriaClient = new SearchCriteria('Client');

		$searchCriteriaClient
			->filter('id_cliente')
			->restricted_by('equals')
			->compare_with(intval($client_id));

		$Client = $this->SearchManager->searchByCriteria($searchCriteriaClient);

		if ($Client->getSize() === 0) {
			return null;
		}

		return $Client[0];
	}

}
