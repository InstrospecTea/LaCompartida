<?php

class RateController extends AbstractController {
	public function ErrandsRate() {
		$this->layoutTitle = 'Ingreso de Tarifas de Trámites';
		$this->javascript = '';

		$this->loadBusiness('Rating');
		$this->loadBusiness('Coining');

		$rates = $this->RatingBusiness->getErrandsRate();
		$errands_rate_fields = $this->RatingBusiness->getErrandsRateFields();

		$errands_rate_table = array();

		foreach ($errands_rate_fields as $errand) {
			$coin_errand = new stdClass();
			$coin_errand->id_moneda = $errand['id_moneda'];
			$coin_errand->id_tramite_tipo = $errand['id_tramite_tipo'];
			$errands_rate_table[$errand['glosa_tramite']][] = $coin_errand;
		}

		$this->set('rates', $rates);
		$this->set('errands_rate_table', $errands_rate_table);
		$this->set('coins', $this->CoiningBusiness->currenciesToArray($this->CoiningBusiness->getCurrencies()));
		$this->set('diseno_nuevo', Conf::GetConf($this->Session,'UsaDisenoNuevo'));
		$this->set('Html', new \TTB\Html());
		$this->set('Form', new Form($this->Session));
	}

	public function ErrandsRateValue() {
		$this->loadBusiness('Rating');
		$errands_rate_values = $this->RatingBusiness->getErrandsRateValue($this->params['id_tarifa']);
		$errand_rate_detail = $this->RatingBusiness->getErrandRateDetail($this->params['id_tarifa']);

		$response = new stdClass();
		$response->errand_rate_detail = $errand_rate_detail;
		$response->errands_rate_values = $errands_rate_values;

		$this->renderJSON($response);
	}

	public function contractsWithErrandRate() {
		$this->loadBusiness('Rating');
		$num_contracts = $this->RatingBusiness->getContractsWithErrandRate($this->params['id_tarifa']);

		$this->renderJSON($num_contracts);
	}

	public function changeDefaultErrandRateOnContract() {
		$this->loadBusiness('Rating');
		$result = $this->RatingBusiness->updateDefaultErrandRateOnContract($this->params['id_tarifa']);

		$response = new stdClass();
		$response->success = $result;

		$this->renderJSON($response);
	}

	public function deleteErrandRate() {
		$this->loadBusiness('Rating');
		$total_rates = $this->RatingBusiness->countRates();

		if ($total_rates > 1) {
			// $total_rates = $this->RatingBusiness->deleteErrandRate($id_tarifa);
		}
	}
}
