<?php
class EstadoCobroFilter extends AbstractUndependantFilterTranslator {

	function getFieldName() {
		return 'cobro.estado';
	}

	function getParentFilter() {
		return $this->parent;
	}

	function setParentFilterData($data) {
		$this->parent = $data;
	}

	function translateForCharges(Criteria $Criteria) {
		$Criteria->add_restriction(CriteriaRestriction::in($this->getFieldName(), $this->getFilterData()));
	}

	function translateForErrands(Criteria $Criteria) {
		$Criteria->add_restriction(CriteriaRestriction::in($this->getFieldName(), $this->getFilterData()));
	}

	function translateForWorks(Criteria $Criteria) {
		$Criteria->add_restriction(CriteriaRestriction::in($this->getFieldName(), $this->getFilterData()));
	}

}
