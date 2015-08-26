<?php

interface IDependantFilterTranslator extends IFilterTranslator{

	function getParentFilter();

	function setParentFilterData($data);
}