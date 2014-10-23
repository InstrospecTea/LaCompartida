<?php

/**
 * Interface ISearchBusiness
 */
interface ISearchingBusiness{

	/**
	 * Realiza una b�squeda considerando los criterios definidos en una instancia de {@link SearchCriteria}.
	 * Completa la b�squeda utilizando los distintos scopes definidos.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @return mixed|void
	 */
	function searchByCriteria(SearchCriteria $searchCriteria , array $filter_properties = array());

} 