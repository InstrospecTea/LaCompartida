<?php

abstract class AbstractDependantFilterTranslator extends BaseFilterTranslator implements IDependantFilterTranslator {
	public function __construct($parentData, $dependantData) {
		$this->setFilterData($dependantData);
		$this->setParentFilterData($parentData);
	}

	function setFilterData($data) {
		$this->data = $data;
	}

	function getFilterData() {
		return $this->data;
	}
}