<?php

interface ITranslatingBusiness extends BaseBusiness {

	/**
	 * Obtiene una instancia de {@link Language} en base a un c�digo definido.
	 * @param  string $languageCode C�digo del idioma, puede ser 'es', 'en' u otro definido.
	 * @return Language
	 */
	function getLanguageByCode($languageCode);

}
