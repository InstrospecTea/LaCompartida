<?php

class LanguageManager extends AbstractManager implements BaseManager {

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->loadService('Language');
	}

	public function getById($language_id) {
		try {
			$language = $this->LanguageService->findFirst(CriteriaRestriction::equals('id_idioma', "'{$language_id}'"));
		} catch (EntityNotFound $e) {
			$language = null;
		}

		return $language;
	}

}
