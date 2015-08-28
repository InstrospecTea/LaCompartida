	<?php

	class CampoFechaFilter extends AbstractDependantFilterTranslator {

	function getParentFilter() {
		return $this->parent;
	}

	function setParentFilterData($data) {
		$this->parent = $data;
	}

	static function getNameOfDependantFilters() {
		return array('fecha_ini', 'fecha_fin');
	}

	function translateForCharges(Criteria $Criteria) {
		return $Criteria;
	}

	function translateForErrands(Criteria $Criteria) {
		return $Criteria;
	}

	function translateForWorks(Criteria $Criteria) {
		$filters = $this->getFilterData();
		$Criteria->add_restriction(CriteriaRestriction::between('trabajo.fecha', "'{$filters['fecha_ini']}'", "'{$filters['fecha_fin']}'"));

		return $Criteria;
	}

	function getFieldName() {
		// TODO: Implement getFieldName() method.
	}
}
