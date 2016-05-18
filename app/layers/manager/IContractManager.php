<?php

interface IContractManager extends BaseManager {
	/**
	 * Obtiene un contrato mediante su id
	 * @param 	string $contract_id
	 * @return 	Contract
	 */
	public function getContract($contract_id = null);
}
