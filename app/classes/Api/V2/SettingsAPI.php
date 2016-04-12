<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Settings
 *
 */
class SettingsAPI extends AbstractSlimAPI {

	static $SettingEntity = array(
		'id',
		array('code' => 'glosa_opcion'),
		array('name' => 'glosa_opcion'),
		array('description' => 'comentario'),
		array('value' => 'valor_opcion'),
		array('datatype' => 'valores_posibles'),
		array('category' => 'id_configuracion_categoria'),
		'typed_value'
	);

	public function getTimeTrackingSettings() {
		$Slim = $this->slim;
		$this->validateAuthTokenSendByHeaders();
		$Business = new \SettingsBusiness($this->session);
		$results = $Business->getTimeTrackingSettings();
		$this->present($results, self::$SettingEntity);
	}

}
