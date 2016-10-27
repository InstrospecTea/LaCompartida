<?php

class Format {

	static private $currencies = array();
	static private $languages = array();
	static private $default_loaded = false;

	public static function number($number, $id_language = null) {
		if (is_null($id_language) && !self::$default_loaded) {
			$id_language = self::loadDefaultLanguage();
		}

		if (!self::$languages[$id_language]) {
			self::getLanguage($id_language);
		}

		$lenguage = self::$languages[$id_language];

		$output = number_format(
			floatval($number),
			self::getLengthDecimals(floatval($number)),
			$lenguage->get('separador_decimales'),
			$lenguage->get('separador_miles')
		);

		return $output;
	}

	public static function currency($number, $id_currency) {
		if (!self::$currencies[$id_currency]) {
			self::getCurrency($id_currency);
		}

		$currency = self::$currencies[$id_currency];

		$output = number_format(
			floatval($number),
			$currency->get('cifras_decimales'),
			$currency->get('separador_decimales'),
			$currency->get('separador_miles')
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

	private function getLengthDecimals($number) {
		preg_match("/\d+(?:,|.)(\d+)/", $number, $array_number);
		$decimals = $array_number[1];

		return $decimals == 0 ? 0 : strlen($decimals);
	}

	private function loadDefaultLanguage() {
		$Sesion = new Sesion();

		$language_code = strtolower(UtilesApp::GetConf($Sesion, 'Idioma'));
		$LanguageManager = new LanguageManager($Sesion);
		$language = $LanguageManager->getByCode($language_code);
		$id_language = $language->get('id_idioma');

		self::$languages[$id_language] = $language;
		$default_loaded = true;

		return $id_language;
	}

}
