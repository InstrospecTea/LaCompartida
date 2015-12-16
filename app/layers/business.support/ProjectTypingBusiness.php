<?php

class ProjectTypingBusiness extends AbstractBusiness {

	/**
	 * Obtiene todas las instancias de {@link ProjectType} existentes en el ambiente del cliente
	 */
	function getProjectTypes() {
		$searchCriteria = new SearchCriteria('ProjectType');
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->searchByCriteria($searchCriteria);
	}

	/**
	 * Obtiene un Array asociativo [identidad] => [glosa_area], a partir de un array de instancias de {@link Currency}.
	 */
	function projectTypesToArray($areas) {
		$result = array();
		foreach ($areas as $area) {
			$result[$area->get($area->getIdentity())] = $area->fields['glosa_tipo_proyecto'];
		}
		return $result;
	}
}
