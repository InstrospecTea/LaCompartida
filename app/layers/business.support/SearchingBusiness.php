<?php

/**
 * Class SearchBusiness
 */
class SearchingBusiness extends AbstractBusiness implements ISearchingBusiness  {

	/**
	 * Realiza una búsqueda considerando los criterios definidos en una instancia de {@link SearchCriteria}.
	 * Completa la búsqueda utilizando los distintos scopes definidos.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @return mixed|void
	 */
	function searchByCriteria(SearchCriteria $searchCriteria , array $filter_properties = array()) {
		$this->loadService('Search');
		$criteria = new Criteria($this->sesion);
		$criteria = $this->SearchService->translateCriteria(
			$searchCriteria,
			$filter_properties,
			$criteria
		);
		$criteria = $this->addScopes($searchCriteria, $criteria);
		return $this->SearchService->getResults(
			$searchCriteria,
			$criteria
		);
	}

	/**
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return Criteria|mixed
	 */
	private function addScopes(SearchCriteria $searchCriteria, Criteria $criteria) {
		$scopes = $searchCriteria->scopes();
		//Instanciar la clase correspondiente mediante reflection.
		$scopeClass = $searchCriteria->entity().'Scope';
		$scopeInstance = new $scopeClass();
		foreach ($scopes as $scope) {
			$scopeMethod = new ReflectionMethod($scopeClass, $scope);
			$criteria = $scopeMethod->invoke($scopeInstance, $criteria);
		}
		return $criteria;
	}

} 