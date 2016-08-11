<?php

class CurrencyChargeManager extends AbstractManager implements BaseManager {

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->loadService('CurrencyCharge');
	}

	public function updateRate($charge_id, $currency_id, $rate) {
		// $InsertCriteria = new InsertCriteria($this->Sesion);
		// $InsertCriteria->set_into($this->CurrencyChargeService);
	}

}
