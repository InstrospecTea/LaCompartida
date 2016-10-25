<?php

interface ILanguageManager extends BaseManager {
	/**
	 * Obtiene el idioma por su id
	 * @param 	string $language_id
	 * @return 	Language
	 */
	public function getById($language_id);
}
