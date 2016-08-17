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
		$this->ChargingBusiness->detachAllWorks($charge_id);
	}

	public function jsonToTable() {
		$this->layoutTitle = 'Json To Table';
		$data = json_encode(array(
			'headers' => array('a' => 'ID', 'b' => 'Name', 'd' => 'Nothing', 'c' => 'Date'),
			'rows' => array(
				array(
					'a' => ++$i,
					'b' => 'Claudio',
					'c' => date('Y-m-d', strtotime("+$i day"))
				),
				array(
					'a' => ++$i,
					'b' => 'Hugo',
					'c' => date('Y-m-d', strtotime("+$i day"))
				),
				array(
					'a' => ++$i,
					'b' => 'Diego',
					'c' => date('Y-m-d', strtotime("+$i day"))
				),
				array(
					'a' => ++$i,
					'b' => 'Sergio',
					'c' => date('Y-m-d', strtotime("+$i day"))
				),
			)
		));

		$this->set(compact('data'));
	}

	public function date() {
		$this->layoutTitle = 'Translated Dates';
		$lang = $this->data['lang'] ?: 'es';
		$custom = $this->data['custom'] ?: '';
		$date = $this->data['date'] ?: Date::now()->format('d-m-Y');
		//Cambio de Lang
		global $_LANG;
		$_LANG = array();
		include Conf::ServerDir() . "/lang/$lang.php";
		$langs = array('es' => 'es', 'en' => 'en', 'pt' => 'pt');
		$formats = array(
			Date::RFC822,
			Date::COOKIE,
			'l d \d\e F \d\e Y',
			'F, l d \d\e Y'
		);
		if (!empty($custom)) {
			$formats[] = $custom;
		}
		$this->set(compact('langs', 'formats', 'lang', 'custom', 'date'));
	}
}
