<?php

class ContractManager extends AbstractManager implements IContractManager {
	/**
	 * Obtiene un contrato mediante su id
	 * @param 	string $contract_id
	 * @return 	SplFixedArray
	 */
	public function getContract($contract_id = null) {
		$emptySplFixedArray = new SplFixedArray();

		if (is_null($contract_id)) {
			return $emptySplFixedArray;
		}

		$this->loadManager('Search');
		$searchCriteriaContract = new SearchCriteria('Contract');
		$searchCriteriaContract
			->filter('id_contrato')
			->restricted_by('equals')
			->compare_with(intval($contract_id));

		$Contract = $this->SearchManager->searchByCriteria($searchCriteriaContract);

		if ($Contract->getSize() === 0) {
			return $emptySplFixedArray;
		}

		return $Contract;
	}
}
