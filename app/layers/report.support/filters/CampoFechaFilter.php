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

	function translateForCharges(Criteria $criteria) {
		return $criteria;
	}

	function translateForErrands(Criteria $criteria) {
		return $criteria;
	}

	function translateForWorks(Criteria $criteria) {
		return $criteria;
	}

	function getFieldName() {
		// TODO: Implement getFieldName() method.
	}
}