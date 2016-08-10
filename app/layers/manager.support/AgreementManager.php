<?php

class AgreementManager extends AbstractManager implements IAgreementManager {

	/**
	 * Obtiene la Tarifa asociada a un Contrato
	 * @param integer $agreement_id
	 * @return Tarifa
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

	/**
	 * Cambia el cliente al contrato indicado
	 * @param type $agreement_id
	 * @param type $new_client_code
	 * @throws Exception
	 */
	public function changeClient($agreement_id, $new_client_code) {
		$Contrato = $this->loadModel('Contrato', null, true);
		$Contrato->load($agreement_id);
		$Contrato->Edit('codigo_cliente', $new_client_code);
		if (!$Contrato->Write()) {
			throw new Exception("No se pudo cambiar el cliente del contrato {$agreement_id}");
		}
	}

}
