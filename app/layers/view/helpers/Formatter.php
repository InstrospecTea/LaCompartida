<?php

class Formatter extends Helper {

	/**
	 * Da formato a un monto basado en la {@link Currency} definida y el {@link Language} definido.
	 * @param $amount
	 * @param Currency $amountCurrency
	 * @param Language $language
	 * @return string
	 */
	function currency($amount, Currency $amountCurrency, Language $language, $includeSymbol = true) {
		return ($includeSymbol ? "{$amountCurrency->get('simbolo')} " : '') . number_format($amount, $amountCurrency->get('cifras_decimales'), $language->get('separador_decimales'), $language->get('separador_miles'));
	}

}
