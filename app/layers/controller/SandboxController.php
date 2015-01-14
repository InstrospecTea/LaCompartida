<?php

class SandboxController extends AbstractController {

	public $helpers = array('EntitiesListator', array('\TTB\Html', 'Html'), 'Form', 'Paginator');

	public function index() {
		$this->layoutTitle = 'Sandbox interface';
		$this->loadBusiness('Sandboxing');
		$page = empty($this->params['page']) ? null : $this->params['page'];
		$searchResult = $this->SandboxingBusiness->getSandboxResults(50, $page);
		$this->set('results', $searchResult->get('data'));
		$this->set('Pagination', $searchResult->get('Pagination'));
		$this->info('Esto es un sandbox... de gato!');
	}

	public function report() {
		$this->loadBusiness('Sandboxing');
		$report = $this->SandboxingBusiness->report($this->data);
		$report->render();
	}

	public function scales() {
		$this->loadBusiness('Charging');
		$this->ChargingBusiness->getSlidingScales(8038);
		exit;
	}

	public function charging() {
		$this->layoutTitle = 'Sandbox Charging';
		$this->loadBusiness('Charging');
		$charge = $this->ChargingBusiness->getCharge(639);
		$detail = $this->ChargingBusiness->getAmountDetailOfFees($charge);
		var_dump($detail);
	}

}

