<?php

class Format {

	static private $currencies = array();
	static private $languages = array();

	public static function number($number, $id_language = 1) {
		if(!self::$languages[$id_language]) {
			self::getLanguage($id_language);
		}

		$lenguage = self::$languages[$id_language];

		$output = number_format(
			$number,
			self::getDecimals($number),
			$lenguage->fields['separador_decimales'],
			$lenguage->fields['separador_miles']
		);

		return $output;
	}

	public static function currency($number, $id_currency) {
		if(!self::$currencies[$id_currency]) {
			self::getCurrency($id_currency);
		}

		$currency = self::$currencies[$id_currency];

		$output = number_format(
			$number,
			$currency->fields['cifras_decimales'],
			$currency->fields['separador_decimales'],
			$currency->fields['separador_miles']
		);

		return $output;
	}

	private function getCurrency($id_currency) {
		$CurrencyManager = new CurrencyManager(new Sesion());
		$currency = $CurrencyManager->getById($id_currency);

		self::$currencies[$id_currency] = $currency;
	}

	private function getLanguage($id_language) {
		$LanguageManager = new LanguageManager(new Sesion());
		$language = $LanguageManager->getById($id_language);

		self::$languages[$id_language] = $language;
	}

	private function getDecimals($number) {
		$array_number = explode(',', strval($number));
		return strlen($array_number[1]);
	}

}
