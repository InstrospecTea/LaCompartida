<?php

class AgreementManager extends AbstractManager implements IAgreementManager {

	/**
	 * Obtiene la Tarifa asociada a un Contrato
	 * @param 	string $contract_id
	 * @return 	Tarifa
	 */
	public function getDefaultFee($agreement_id) {
		if (empty($agreement_id) || !is_numeric($agreement_id)) {
			throw new InvalidIdentifier;
		}

		$this->loadService('Agreement');
		$this->loadService('Fee');

		try {
			$Agreement = $this->AgreementService->get("'{$agreement_id}'", 'id_tarifa');
			$Fee = $this->FeeService->get($Agreement->get('id_tarifa'));
		} catch (EntityNotFound $e) {
			return null;
		}

		return $Fee;
	}

}
