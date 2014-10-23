<?php

/**
 * Interface ISearchBusiness
 */
interface ISearchingBusiness{

	/**
	 * Realiza una búsqueda considerando los criterios definidos en una instancia de {@link SearchCriteria}.
	 * Completa la búsqueda utilizando los distintos scopes definidos.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @return mixed|void
	 */
	function searchByCriteria(SearchCriteria $searchCriteria , array $filter_properties = array());

} 