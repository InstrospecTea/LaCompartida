<?php

class CurrencyManager extends AbstractManager implements BaseManager {

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->loadService('Currency');
	}

	/**
	 * Retorna la moneda base del estudio
	 * @return 	Currency
	 */
	public function getBaseCurrency() {
		try {
			$currency = $this->CurrencyService->findFirst(CriteriaRestriction::equals('moneda_base', 1));
		} catch (EntityNotFound $e) {
			$currency = null;
		}

		return $currency;
	}

	public function getByCode($code) {
		try {
			$currency = $this->CurrencyService->findFirst(CriteriaRestriction::equals('codigo', "'{$code}'"));
		} catch (EntityNotFound $e) {
			$currency = null;
		}

		return $currency;
	}

	public function getById($id_currency) {
		try {
			$currency = $this->CurrencyService->get($id_currency);
		} catch (EntityNotFound $e) {
			$currency = $this->CurrencyService->newEntity();
		}

		return $currency;
	}

	public function update(Entity $currency) {
		return $this->CurrencyService->saveOrUpdate($currency);
	}

}
