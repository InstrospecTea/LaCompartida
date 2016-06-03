<?php

class ClientManager extends AbstractManager implements IClientManager {

	/**
	 * Obtiene el contrato principal de un cliente
	 * @param 	string $client_id
	 * @return 	Contrato
	 */
	public function getDefaultContract($client_id) {
		if (empty($client_id) || !is_numeric($client_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Client');
		$this->loadService('Contract');

		try {
			$Client = $this->ClientService->get("'{$client_id}'", 'id_contrato');
			$Contract = $this->ContractService->get($Client->get('id_contrato'));
		} catch (EntityNotFound $e) {
			return null;
		}

		return $Contract;
	}

}
