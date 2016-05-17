<?php

class PaymentManager extends AbstractManager implements IPaymentManager {

	public function getPaymentsOfMatter($matter_id) {
		$this->loadManager('Search');
		$searchCriteriaPayment = new SearchCriteria('Payment');

		$searchCriteriaPayment
			->filter('id_neteo_documento')
			->restricted_by('equals')
			->compare_with(4);

		$payments = $this->SearchManager->searchByCriteria($searchCriteriaPayment);
		var_dump($payments); exit;

		return $payments;
	}
}
