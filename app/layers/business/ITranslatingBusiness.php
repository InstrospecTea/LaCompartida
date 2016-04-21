<?php

interface ITranslatingBusiness extends BaseBusiness {

	/**
	 * Obtiene una instancia de {@link Language} en base a un código definido.
	 * @param  string $languageCode Código del idioma, puede ser 'es', 'en' u otro definido.
	 * @return Language
	 */
	function getLanguageByCode($languageCode);

}
