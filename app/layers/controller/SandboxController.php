<?php

/**
 * Class SandboxController
 * @property ChargingBusiness $ChargingBusiness
 * @property SandboxingBusiness $SandboxingBusiness
 * @property CoiningBusiness $CoiningBusiness
 * @property TranslatingBusiness $TranslatingBusiness
 */
class SandboxController extends AbstractController {

	public $helpers = array('EntitiesListator', array('\TTB\Html', 'Html'), 'Form', 'Paginator');

	public function index() {
		if ($this->request['isAjax']) {
			$this->layout = 'ajax';
		}
		$this->layoutTitle = 'Sandbox interface';
		$this->loadBusiness('Sandboxing');
		$page = empty($this->params['page']) ? null : $this->params['page'];
		$searchResult = $this->SandboxingBusiness->getSandboxResults(50, $page);
		$this->set('results', $searchResult->get('data'));
		$this->set('Pagination', $searchResult->get('Pagination'));
		$this->info('Esto es un sandbox... de gato!');
	}

	public function data_calculator() {
		$this->loadModel('ValorCobradoDataCalculator');
		$this->info('Calculator.. woh wo ooo .. calculator!');
	}


	public function report() {
		$this->loadBusiness('Sandboxing');
		$report = $this->SandboxingBusiness->report($this->data);
		$report->render();
	}

	public function scales($chargeId) {
		$this->loadBusiness('Charging');
		$this->loadBusiness('Translating');
		$this->loadBusiness('Coining');
		$language = $this->TranslatingBusiness->getLanguageByCode("'es'");
		$slidingScales = $this->ChargingBusiness->getSlidingScales($chargeId);
		$charge = $this->ChargingBusiness->getCharge($chargeId);
		$this->set('slidingScales', $slidingScales);
		$this->set('charge', $charge);
	}

	public function scalesWork($chargeId) {
		$this->loadBusiness('Charging');
		$charge = $this->ChargingBusiness->getCharge($chargeId);
		$this->set('table', $this->ChargingBusiness->getSlidingScalesWorkDetail($charge));
	}

	public function charging() {
		$this->layoutTitle = 'Sandbox Charging';
		$this->loadBusiness('Charging');
		$this->loadBusiness('Coining');

		$charge = $this->ChargingBusiness->getCharge(5753);

		$currency = $this->CoiningBusiness->getCurrency($charge->get('opc_moneda_total'));
		$detail = $this->ChargingBusiness->getAmountDetailOfFees($charge, $currency);
		$invoiced = $this->ChargingBusiness->getBilledAmount($charge, $currency);
	}

	public function deleteCharge($charge_id) {
		$this->loadBusiness('Charging');
	}

}

