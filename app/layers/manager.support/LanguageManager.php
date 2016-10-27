<?php

class LanguageManager extends AbstractManager implements BaseManager {

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->loadService('Language');
	}

	/**
	 * Obtiene el idioma por su id
	 * @param 	string $language_id
	 * @return 	Language
	 */
	public function getById($language_id) {
		try {
			$language = $this->LanguageService->get($language_id);
		} catch (EntityNotFound $e) {
			$language = $this->LanguageService->newEntity();
		}

		return $language;
	}

	/**
	 * Obtiene el idioma por su codigo
	 * @param 	string $language_code
	 * @return 	Language
	 */
	public function getByCode($language_code) {
		try {
			$language = $this->LanguageService->findFirst(CriteriaRestriction::equals('codigo_idioma', "'{$language_code}'"));
		} catch (EntityNotFound $e) {
			$language = $this->LanguageService->newEntity();
		}

		return $language;
	}

}
