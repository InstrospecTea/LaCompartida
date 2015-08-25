<?php

interface IDataCalculator() {

	/**
	 * Obtiene los agrupadores por los cuales se podrán filtrar y posteriormente agrupar
	 * @return array
	 */
	function getAllowedGroupers();

	/**
	 * Obtiene los filtros por los cuales se podrá filtra
	 * @return array
	 */
	function getAllowedFilters();

	/**
	 * Construye la query de trabajos
	 * @return boolean
	 */
	function buildWorkQuery();

	/**
	 * Construye la query de tramites
	 * @return boolean
	 */
	function buildErrandQuery();

	/**
	 * Construye la query de cobros
	 * @return boolean
	 */
	function buildChargeQuery();

	/**
	 * Ejecuta las querys construidas
	 * @return array
	 */
	function calculate();

}
