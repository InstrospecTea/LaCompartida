<?php

interface IDataCalculator {

	/**
	 * Obtiene los agrupadores por los cuales se podrán agrupar y ordenar
	 * @return array
	 */
	function getAllowedGroupers();

	/**
	 * Obtiene los filtros por los cuales se podrá filtrar
	 * @return array
	 */
	function getAllowedFilters();

	/**
	 * Obtiene los agrupadores por los cuales no se podrán filtrar
	 * @return array
	 */
	function getNotAllowedFilters();

	/**
	 * Obtiene los agrupadores por los cuales no se podrán agrupar y ordenar
	 * @return array
	 */
	function getNotAllowedGroupers();

	/**
	 * Construye la query de trabajos
	 * @return void
	 */
	function buildWorkQuery();

	/**
	 * Construye la query de tramites
	 * @return void
	 */
	function buildErrandQuery();

	/**
	 * Construye la query de cobros
	 * @return void
	 */
	function buildChargeQuery();

	/**
	 * Ejecuta las querys construidas y retorna un array con
	 * los resultados de todas las queries
	 * @return array
	 */
	function calculate();

	/**
	 * Agrega los agrupadores a la Query dependiendo de los
	 * grupos definidos
	 * @param Criteria $Criteria La query a la que se agregarán los agrupadores
	 * @param String   $type     El tipo de query: [Works, Errands, Charges]
	 */
	function addGroupersToCriteria(Criteria $Criteria, $type);

	/**
	 * Agrega los filtros a la query dependiendo de filtersFields
	 * @param Criteria $Criteria Query a la que se agregarán lso filtros
	 * @param String $type       El tipo de query: [Works, Errands, Charges]
	 */
	function addFiltersToCriteria(Criteria $Criteria, $type);
}
