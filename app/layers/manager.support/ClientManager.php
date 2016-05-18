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

		$this->loadService('Client');
		$this->loadService('Contract');

		try {
			$Client = $this->ClientService->get(intval($client_id), 'id_contrato');
			$Contract = $this->ContractService->get($Client->get('id_contrato'));
		} catch (ServiceException $e) {
			return $emptyContract;
		}

		return $Contract;
	}

}
