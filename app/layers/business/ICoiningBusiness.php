<?php

interface ICoiningBusiness extends BaseBusiness {

	/**
	 * Obtiene la instancia de {@link Currency} base configurada para el ambiente.
	 * @return Currency
	 */
	function getBaseCurrency();

	/**
	 * Realiza un cambio de la moneda de una cantidad.
	 * @param $amount
	 * @param Currency $fromCurrency {@link Currency} en la que actualmente está $amount.
	 * @param Currency $toCurrency {@link Currency} a la que se quiere transformar $amount.
	 * @return mixed Cantidad en la nueva moneda.
	 */
	function changeCurrency($amount, Currency $fromCurrency, Currency $toCurrency);

	/**
	 * Da formato a un monto basado en la {@link Currency} definida.
	 * @param $amount
	 * @param Currency $amountCurrency
	 * @param Language $language
	 * @return string
	 */
	function formatAmount($amount, Currency $amountCurrency, Language $language);

	/**
	 * Obtiene la instancia de {@link Currency} asociada al identificador $id.
	 * @param $id
	 * @return mixed
	 */
	function getCurrency($id);

	/**
	 * Establece el tipo de cambio de una moneda segÃºn el definido para una instancia de {@link Charge} en particular.
	 * @param Currency $currency
	 * @param Charge $charge
	 * @return Currency
	 */
	function setCurrencyAmountByCharge(Currency $currency, Charge $charge);

} 