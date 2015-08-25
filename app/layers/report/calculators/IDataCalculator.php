<?php

interface IDataCalculator() {

	/**
	* Exporta los datos según la instancia de {@link ReporteEngine}
	* @return mixed
	*/
	function getAllowedGroupers();

	function getAllowedFilters();

	function buildWorkQuery();

	function buildErrandQuery();

	function buildChargeQuery();

	function calculate();

}
