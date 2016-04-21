<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Clientes
 *
 */
class ClientsAPI extends AbstractSlimAPI {

	static $CilientEntity = array(
		array('id' => 'id_cliente'),
		array('code' => 'codigo_cliente'),
		array('name' => 'glosa_cliente'),
		array('active' => 'activo')
	);

	public function getUpdatedClients() {
		$this->validateAuthTokenSendByHeaders();

		$active = $this->params['active'];
		$updatedFrom = $this->params['updated_from'];

		if (!is_null($updatedFrom) && !$this->isValidTimeStamp($updatedFrom)) {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

		$Business = new \ClientsBusiness($this->session);
		$results = $Business->getUpdatedClients($active, $updatedFrom);

		$this->present($results, self::$CilientEntity);
	}

}
