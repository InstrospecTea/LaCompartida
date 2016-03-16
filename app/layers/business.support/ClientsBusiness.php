<?php

class ClientsBusiness extends AbstractBusiness implements IClientsBusiness {

	public function getUpdatedClients($active = null, $updatedFrom = null) {
		$results = array();
		$this->loadBusiness('Searching');

		$searchCriteria = new SearchCriteria('Client');

		if (!is_null($active)) {
			$searchCriteria
				->filter('activo')
				->restricted_by('equals')
				->compare_with($active);
		}

		if (!is_null($updatedFrom)) {
			$updatedFromDate = date('Y-m-d', $updatedFrom);
			$searchCriteria->add_scope(
				'updatedFrom',
				array('args' => array($updatedFromDate))
			);
		}

		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);

		return $results;
	}

}
