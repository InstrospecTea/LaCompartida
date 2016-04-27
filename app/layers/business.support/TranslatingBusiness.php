<?php

/**
*
*/
class TranslatingBusiness extends AbstractBusiness implements ITranslatingBusiness {

	/**
	 * Obtiene una instancia de {@link Language} en base a un c�digo definido.
	 * @param  string $languageCode C�digo del idioma, puede ser 'es', 'en' u otro definido.
	 * @throws BusinessException Cuando el c�digo del idioma no tiene asociado una entidad en el medio persistente.
	 * @return Language
	 */
	function getLanguageByCode($languageCode) {
		$searchCriteria = new SearchCriteria('Language');
		$searchCriteria->filter('codigo_idioma')->restricted_by('equals')->compare_with("'".$languageCode."'");
		$this->loadBusiness('Searching');
		$results = $this->SearchingBusiness->searchByCriteria($searchCriteria);
		if (empty($results)) {
			throw new BusinessException("There is not a defined language with provided code '$languageCode'.");
		}
		return $results[0];
	}

}
