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
	public function searchByCriteria(SearchCriteria $searchCriteria , array $filter_properties = array());

	/**
	 * Realiza una b�squeda considerando los criterios definidos en una instancia de {@link SearchCriteria}.
	 * Completa la b�squeda utilizando los distintos scopes definidos y retorna una lista de {@link GenericModel}.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @return mixed|void
	 */
	public function searchByGenericCriteria(SearchCriteria $searchCriteria , array $filter_properties = array());

	/**
	 * Devuelve el resultado de searchByCriteria() paginado.
	 * @param SearchCriteria $searchCriteria
	 * @param array $filter_properties
	 * @param type $page
	 * @return \stdClass
	 */
	public function paginateByCriteria(SearchCriteria $searchCriteria , array $filter_properties = array(), $page = 1);


}