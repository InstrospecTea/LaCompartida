<?php

class ProjectAreaingBusiness extends AbstractBusiness {

	/**
	 * Obtiene todas las instancias de {@link Area} existentes en el ambiente del cliente
	 */
	function getProjectAreas() {
		$searchCriteria = new SearchCriteria('ProjectArea');
		$this->loadBusiness('Searching');
		return $this->SearchingBusiness->searchByCriteria($searchCriteria);
	}

	/**
	 * Obtiene un Array asociativo [identidad] => [glosa_area], a partir de un array de instancias de {@link Currency}.
	 */
	function projectAreasToArray($areas) {
		$result = array();
		foreach ($areas as $area) {
			$result[$area->get($area->getIdentity())] = $area->fields['glosa'];
		}
		return $result;
	}
}
