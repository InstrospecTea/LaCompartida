<?php
namespace Api\V2;

/**
 *
 * Clase con m�todos para Traducciones
 *
 */
class TranslationsAPI extends AbstractSlimAPI {

	public function getTranslations() {
		$this->	validateAuthTokenSendByHeaders();

		$translations = array();
		array_push($translations, array('code' => 'Matters', 'value' => __('Asuntos')));
		array_push($translations, array('code' => 'Works', 'value' => __('Trabajos')));
		array_push($translations, array('code' => 'Clients', 'value' => __('Clientes')));

		$this->outputJson($translations);
	}

}