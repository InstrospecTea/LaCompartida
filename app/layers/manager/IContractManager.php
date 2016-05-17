<?php

interface IContractManager extends BaseManager {
	/**
	 * Obtiene un contrato mediante su id
	 * @param 	string $contract_id
	 * @return 	SplFixedArray
	 */
	public function getContract($contract_id = null);
}
