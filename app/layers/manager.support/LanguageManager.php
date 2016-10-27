<?php

class LanguageManager extends AbstractManager implements BaseManager {

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->loadService('Language');
	}

	public function getById($language_id) {
		try {
			$language = $this->LanguageService->get($language_id);
		} catch (EntityNotFound $e) {
			$language = $this->LanguageService->newEntity();
		}

		return $language;
	}

}
