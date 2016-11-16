<?php

interface ILanguageManager extends BaseManager {
	/**
	 * Obtiene el idioma por su id
	 * @param 	string $language_id
	 * @return 	Language
	 */
	public function getById($language_id);

	/**
	 * Obtiene el idioma por su codigo
	 * @param 	string $language_code
	 * @return 	Language
	 */
	public function getByCode($language_code);
}
