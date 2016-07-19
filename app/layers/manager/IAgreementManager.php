<?php

interface IAgreementManager extends BaseManager {
	/**
	 * Obtiene la Tarifa asociada a un Contrato
	 * @param 	string $contract_id
	 * @return 	Tarifa
	 */
	public function getDefaultFee($agreement_id);
}
