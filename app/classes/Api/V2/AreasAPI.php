<?php

namespace Api\V2;

/**
 *
 * Clase con métodos para Areas
 *
 */
class AreasAPI extends AbstractSlimAPI {

	static $AreasEntity = array(
		array('id' => 'id_area_trabajo'),
		array('name' => 'glosa')
	);

	public function getUpdatedWorkingAreas() {
		$this->validateAuthTokenSendByHeaders();

		$active = $this->params['active'];
		$updatedFrom = $this->params['updated_from'];

		if (!is_null($updatedFrom) && !$this->isValidTimeStamp($updatedFrom)) {
			$this->halt(__('The date format is incorrect'), 'InvalidDate');
		}

		$Business = new \WorkingBusiness($this->session);
		$results = $Business->getUpdatedWorkingAreas($active, $updatedFrom);

		$this->present($results, self::$AreasEntity);
	}

}
