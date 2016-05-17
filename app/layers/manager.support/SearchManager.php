<?php

/**
 * Class SearchManager
 */
class SearchManager extends AbstractManager implements ISearchManager  {

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
		$criteria = $this->getCriteria($searchCriteria, $filter_properties, $widthIdentity, true);
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
		$ret = new GenericModel();
		$ret->set('data', $this->searchByCriteria($searchCriteria, $filter_properties));
		$ret->set('Pagination', $searchCriteria->Pagination);
		return $ret;
	}

	/**
	 *
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return Criteria|mixed
	 */
	private function addScopes(SearchCriteria $searchCriteria, Criteria $criteria) {
		$entity_scopes = $searchCriteria->scopes();

		if (empty($entity_scopes)) {
			return $criteria;
		}

		//Instanciar la clase correspondiente mediante reflection.
		if (count($entity_scopes)) {
			foreach ($entity_scopes as $entity => $scopes) {
				$scopeClass = $entity . 'Scope';
				$scopeInstance = new $scopeClass();
				foreach ($scopes as $scope) {
					$scope_name = $scope;
					$args = array($criteria);
					if (is_array($scope)) {
						$scope_name = $scope[0];
						$args = array_merge($args, $scope[1]);
					}
					$scopeMethod = new ReflectionMethod($scopeClass, $scope_name);
					$criteria = $scopeMethod->invokeArgs($scopeInstance, $args);
				}
			}
		}
		return $criteria;
	}

	private function getCriteria($searchCriteria, $filter_properties, $widthIdentity = true, $genericMode = false) {
		$this->loadService('Search');
		$criteria = new Criteria($this->Sesion);
		$criteria = $this->SearchService->translateCriteria(
			$searchCriteria,
			$filter_properties,
			$criteria,
			$widthIdentity,
			$genericMode
		);
		$criteria = $this->addScopes($searchCriteria, $criteria);
		return $criteria;
	}

	/**
	 *
	 * Retorna un arreglo asociativo de una entidad escogida
	 *
	 * @param Entity $entity entidad a buscar
	 * @param String $keyName nombre del índice de la tabla
	 * @param String $value glosa a listar
	 * @param String $order orden para desplegar los datos
	 * @return Array
	 */
	public function getAssociativeArray($entity, $keyName, $value, $restrictions = array(), $order = 'ASC') {
		$this->loadService('Search');
		$searchCriteria = new SearchCriteria($entity);

		$filter_properties = array(
			$keyName,
			$value
		);

		$criteria = new Criteria($this->Sesion);
		$criteria = $this->SearchService->translateCriteria(
			$searchCriteria,
			$filter_properties,
			$criteria
		);

		if (!empty($restrictions)) {
			foreach ($restrictions as $restriction) {
				$criteria->add_restriction($restriction);
			}
		}

		$criteria->add_ordering("{$entity}.{$value}", $order);

		$reportData = $this->SearchService->getResults($searchCriteria, $criteria);

		$result = array();
		foreach ($reportData as $data) {
			$result[$data->get($keyName)] = $data->fields[$value];
		}

		return $result;
	}

}
