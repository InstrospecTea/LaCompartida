<?php

class ContractManager extends AbstractManager implements IContractManager {

	/**
	 * Obtiene la Tarifa asociada a un Contrato
	 * @param 	string $contract_id
	 * @return 	Tarifa
	 */
	public function getDefaultFee($contract_id) {
		if (empty($contract_id) || !is_numeric($contract_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Contract');
		$this->loadService('Fee');

		try {
			$Contract = $this->ContractService->get("'{$contract_id}'", 'id_tarifa');
			$Fee = $this->FeeService->get($Contract->get('id_tarifa'));
		} catch (EntityNotFound $e) {
			return null;
		}

		return $Fee;
	}

}
