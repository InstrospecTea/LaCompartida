<?php
/**
 * Created by PhpStorm.
 * User: dochoaj
 * Date: 12/9/14
 * Time: 10:55 AM
 */

class CoiningBusiness extends AbstractBusiness implements ICoiningBusiness {

	/**
	 * Obtiene la instancia de {@link Currency} base configurada para el Tenant.
	 * @return Currency
	 * @throws BusinessException
	 */
	function getBaseCurrency() {
		$search = new SearchCriteria('Currency');
		$search->filter('moneda_base')->restricted_by('equals')->compare_with('1');
		$this->loadBusiness('Searching');
		$searchResult = $this->SearchingBusiness->searchByCriteria($search);
		if (count($searchResult) != 1) {
			throw new BusinessException('There is a problem with the base currency definition.');
		}
		return $searchResult[0];
	}

	/**
	 * Realiza un cambio de la moneda de una cantidad.
	 * @param $amount
	 * @param Currency $fromCurrency {@link Currency} en la que actualmente está $amount.
	 * @param Currency $toCurrency {@link Currency} a la que se quiere transformar $amount.
	 * @return mixed Cantidad en la nueva moneda.
	 * @throws BusinessException
	 */
	function changeCurrency($amount, Currency $fromCurrency, Currency $toCurrency) {

		if (!is_numeric($amount)) {
			throw new BusinessException('The amount must be numeric');
		}

		if (!$fromCurrency->haveIdentity() || !$toCurrency->haveIdentity()) {
			throw new BusinessException('One of the currencies does not have an identity');
		}

		$newAmount = ($amount * $fromCurrency->get('tipo_cambio')) / $toCurrency->get('tipo_cambio');
		$newAmount = round($newAmount, $toCurrency->get('cifras_decimales'));

		return $newAmount;
	}

	/**
	 * Da formato a un monto basado en la {@link Currency} definida.
	 * @param $amount
	 * @param Currency $amountCurrency
	 * @param $separadorDecimal
	 * @param $separadorMiles
	 * @return string
	 */
	function formatAmount($amount, Currency $amountCurrency, $separadorDecimal, $separadorMiles) {
		return number_format($amount, $amountCurrency->get('cifras_decimales'), $separadorDecimal, $separadorMiles);
	}

	/**
	 * Obtiene la instancia de {@link Currency} asociada al identificador $id.
	 * @param $id
	 * @return mixed
	 */
	function getCurrency($id) {
		$this->loadService('Currency');
		return $this->CurrencyService->get($id);
	}

	/** 
	 * Obtiene todas las instancias de {@link Currency} existentes en el ambiente del cliente
	 */
	function getCurrencies() {
		$searchCriteria = new SearchCriteria('Currency');
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->searchByCriteria($searchCriteria);
	}

	/** 
	 * Obtiene un Array asociativo [identidad] => [glosa_moneda], a partir de un array de instancias de {@link Currency}.
	 */
	function currenciesToArray($currencies) {
		$result = array();
		foreach ($currencies as $currency) {
			$result[$currency->get($currency->getIdentity())] = $currency->fields['glosa_moneda'];
		}
		return $result;
	}



} 