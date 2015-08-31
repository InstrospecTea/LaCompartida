<?php

interface IDependantFilterTranslator extends IFilterTranslator{

	function getParentFilter();

	function setParentFilterData($data);

	static function getNameOfDependantFilters();

	function __construct($Session, $parentData, $dependantData);

}