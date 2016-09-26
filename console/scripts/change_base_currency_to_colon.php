<?php

class ChangeBaseCurrencyToColon extends AppShell {
	private $rates = array();

	public function __construct() {
		parent::__construct();

		$this->loadModel('CurrencyManager');
		$this->loadModel('ChargeManager');
		$this->loadModel('CurrencyChargeManager');

		$this->setAllRates();

		// initialize session by admin
		$this->Session->usuario = new UsuarioExt($this->Session, '99511620');
	}

	public function main() {
		$this->changeBaseCurrency();
		$this->updateChargeCurrencyRates();
		$this->out("Total time lapse: {$this->getTimeLapse()}");
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

	private function updateChargeCurrencyRates() {
		$total_charge = $this->ChargeManager->count();

		for ($limit_from = 0; $limit_from <= $total_charge; $limit_from += 100) {
			$this->out("\n\nFrom: {$limit_from}");
			$this->out("\nTime lapse: {$this->getTimeLapse()}");
			$charges = $this->ChargeManager->findAll(null, null, null, array('from' => $limit_from, 'limit' => 100));
			$base_currency = $this->CurrencyManager->getBaseCurrency();

			foreach ($charges as $charge) {
				$charge_id = $charge->fields['id_cobro'];
				$this->out("\nCharge: {$charge_id}");
				$date_emision = date('Y-m-d', strtotime($charge->fields['fecha_emision']));
				$rate = $this->getRate($date_emision);
				$currency_rates = $this->ChargeManager->getCurrencyRates($charge_id);

				foreach ($currency_rates as $currency_rate) {
					if ($currency_rate->fields['id_moneda'] == $base_currency->fields['id_moneda']) {
						$currency_rate->set('tipo_cambio', 1);
					} else {
						$currency_rate->set('tipo_cambio', $rate);
					}
					$this->out(" Currency[{$currency_rate->fields['id_moneda']}]: {$currency_rate->fields['tipo_cambio']}");
					$this->CurrencyChargeManager->update($currency_rate);
				}

				if (!in_array($charge->fields['estado'], array('CREADO', 'REVISION'))) {
					$this->ChargeManager->forceIssue($charge_id);
				}
			}
		}
	}

	private function setAllRates() {
		$Criteria = new Criteria($this->Session);
		$this->rates = $Criteria
			->add_select('id')
			->add_select('fecha')
			->add_select('tipo_cambio')
			->add_from('tipo_cambio_dolar_colon')
			->run();
	}

	private function getRate($date) {
		$_rate = null;

		foreach ($this->rates as $rate) {
			if ($rate['fecha'] == $date) {
				$_rate = $rate['tipo_cambio'];
				break;
			}
		}

		return $_rate;
	}
}
