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
	public function searchByCriteria(SearchCriteria $searchCriteria , array $filter_properties = array()) {
		$criteria = $this->getCriteria($searchCriteria, $filter_properties);
		return $this->SearchService->getResults($searchCriteria, $criteria);
	}

	/**
	 * Realiza una búsqueda considerando los criterios definidos en una instancia de {@link SearchCriteria}.
	 * Completa la búsqueda utilizando los distintos scopes definidos y retorna una lista de {@link GenericModel}.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @return mixed|void
	 */
	public function searchByGenericCriteria(SearchCriteria $searchCriteria , array $filter_properties = array()) {
		$widthIdentity = false;
		$criteria = $this->getCriteria($searchCriteria, $filter_properties, $widthIdentity);
		return $this->SearchService->getGenericResults($searchCriteria, $criteria);
	}

	/**
	 * Devuelve el resultado de searchByCriteria() paginado.
	 * @param SearchCriteria $searchCriteria
	 * @param array $filter_properties
	 * @param type $page
	 * @return \stdClass
	 */
	public function paginateByCriteria(SearchCriteria $searchCriteria , array $filter_properties = array(), $page = 1) {
		$searchCriteria->Pagination->current_page($page);
		$searchCriteria->paginate(true);
		$ret = new stdClass();
		$ret->data = $this->searchByCriteria($searchCriteria, $filter_properties);
		$ret->Pagination = $searchCriteria->Pagination;
		return $ret;
	}

	/**
	 *
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return Criteria|mixed
	 */
	private function addScopes(SearchCriteria $searchCriteria, Criteria $criteria) {
		$scopes = $searchCriteria->scopes();
		if (empty($scopes)) {
			return $criteria;
		}
		//Instanciar la clase correspondiente mediante reflection.
		if (count($scopes)) {
			$scopeClass = $searchCriteria->entity() . 'Scope';
			$scopeInstance = new $scopeClass();
			foreach ($scopes as $scope) {
				$scopeMethod = new ReflectionMethod($scopeClass, $scope);
				$criteria = $scopeMethod->invoke($scopeInstance, $criteria);
			}
		}
		return $criteria;
	}

	private function getCriteria($searchCriteria, $filter_properties, $widthIdentity = true) {
		$this->loadService('Search');
		$criteria = new Criteria($this->sesion);
		$criteria = $this->SearchService->translateCriteria(
			$searchCriteria,
			$filter_properties,
			$criteria,
			$widthIdentity
		);
		return $this->addScopes($searchCriteria, $criteria);
	}

}