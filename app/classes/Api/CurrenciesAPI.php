<?php

/**
 *
 * Clase con m�todos para Monedas
 *
 */
class CurrenciesAPI extends AbstractSlimAPI {

	public function getCurrencies() {
		$Session = $this->session;
		$Slim = $this->slim;

		$results = Moneda::GetMonedas($Session, '', false);
		$this->outputJson(array('results' => $results));
	}

}
