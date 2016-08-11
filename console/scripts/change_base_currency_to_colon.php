<?php

class ChangeBaseCurrencyToColon extends AppShell {
	public function __construct() {
		parent::__construct();

		$this->loadModel('CurrencyManager');
		$this->loadModel('ChargeManager');
		$this->loadModel('CurrencyChargeManager');
	}
	public function main() {
		$this->changeBaseCurrency();
		$this->updateChargeRates();
	}

	private function changeBaseCurrency() {
		$base_currency = $this->CurrencyManager->getBaseCurrency();
		$colon_currency = $this->CurrencyManager->getByCode('CRC'); // colon currency

		if (!is_null($base_currency) && !is_null($colon_currency)) {
			// set temporary id to base currency
			$base_currency->set('moneda_base', 0);
			$this->CurrencyManager->update($base_currency);

			// assign base id to colon currency
			$colon_currency->set('moneda_base', 1);
			$this->CurrencyManager->update($colon_currency);
		}
	}

	private function updateChargeRates() {
		$charges = $this->ChargeManager->findAll(null, null, null, 1);
		var_dump($charges[0]->fields['fecha_emision']);
	}
}
