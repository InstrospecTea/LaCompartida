<?php

interface IFilterTranslator {

	function setFilterData($data);

	function getFilterData();

	function translateForCharges(Criteria $criteria);

	function translateForErrands(Criteria $criteria);

	function translateForWorks(Criteria $criteria);

	function __construct($data);

	function getFieldName();
}