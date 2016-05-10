<?php
namespace Api\V2;

/**
 *
 * Clase con métodos para Traducciones
 *
 */
class TranslationsAPI extends AbstractSlimAPI {

	public function getTranslations() {
		$this->	validateAuthTokenSendByHeaders();

		$translations = array();
		array_push($translations, array('code' => 'Projects', 'value' => __('Matters')));
		array_push($translations, array('code' => 'Project', 'value' => __('Matter')));
		array_push($translations, array('code' => 'Sin Proyecto', 'value' => __('Sin Asunto')));
		array_push($translations, array('code' => 'Proyecto', 'value' => __('Cliente/Asunto')));
		array_push($translations, array('code' => 'Proyectos', 'value' => __('Asuntos')));
		array_push($translations, array(
			'code' => 'Seleccione un proyecto',
			'value' => __('Seleccione un asunto')
		));

		$this->outputJson($translations);
	}

}
