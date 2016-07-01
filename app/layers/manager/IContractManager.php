<?php

interface IContractManager extends BaseManager {
	/**
	 * Obtiene la Tarifa asociada a un Contrato
	 * @param 	string $contract_id
	 * @return 	Tarifa
	 */
	public function getDefaultFee($contract_id);
}
