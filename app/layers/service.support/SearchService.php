<?php

/**
 * Class SearchService
 */
class SearchService implements ISearchService{

	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarquía de {@link Entity}, que estén denotadas
	 * por los criterios establecidos en una instancia de {@link SearchCriteria}.
	 * Puede contar con restricciones establecidas por los scopes definidos en la capa de negocio correspondiente
	 * a la búsqueda. Cuando esto sucede, entonces se incluye una referencia a una instancia de un objeto
	 * {@link Criteria} sobre el que tiene que construirse el resto del criterio de búsqueda.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @param Criteria       $criteria
	 * @return array
	 */
	public function translateCriteria(SearchCriteria $searchCriteria, array $filter_properties = array(), Criteria $criteria = null) {
		$criteria = $this->prepareRelationships($criteria, $searchCriteria);
		$criteria = $this->prepareRestrictions($criteria, $searchCriteria);
		$criteria = $this->prepareSelection($criteria, $searchCriteria, $filter_properties);
		return $criteria;
	}

	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarquía de {@link Entity}, que estén denotadas
	 * por los criterios establecidos en una instancia de {@link SearchCriteria}.
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return array
	 */
	public function getResults(SearchCriteria $searchCriteria, Criteria $criteria = null) {
		$entity = $searchCriteria->entity();
		$entity = new ReflectionClass($entity);
		$entity = $entity->newInstance();
		return $this->encapsulateArray($criteria->run(), $entity);
	}


	/**
	 *
	 * @param Criteria       $criteria
	 * @param SearchCriteria $searchCriteria
	 * @return Criteria
	 */
	private function prepareRelationships(Criteria $criteria, SearchCriteria $searchCriteria) {
		$usedEntities = array();
		foreach ($searchCriteria->relationships() as $relationship) {
			$relatedEntity = $relationship->entity();
			if (!in_array($relatedEntity, $usedEntities)) {
				$usedEntities[] = $relatedEntity;
				$relatedCondition = $relationship->condition();
				if (class_exists($relationship->entity())) {
					$relatedEntity = new ReflectionClass($relatedEntity);
					$relatedEntity = $relatedEntity->newInstance();
					$relatedProperty = $relationship->property();
					if (empty($relatedProperty)) {
						$relatedProperty = $relatedEntity->getIdentity();
					}
					$relatedTarget = $relatedEntity->getPersistenceTarget();
				} else {
					$reflectedEntity = new ReflectionClass($searchCriteria->entity());
					$reflectedEntity = $reflectedEntity->newInstance();
					$relatedProperty = $reflectedEntity->getIdentity();
					$relatedTarget = $relationship->entity();
				}
				$constructedRestriction = CriteriaRestriction::$relatedCondition($relationship->entity().'.'.$relatedProperty, $searchCriteria->entity().'.'.$relatedProperty)->__toString();
				$criteria->add_custom_join_with($relatedTarget." {$relationship->entity()}", $constructedRestriction, 'LEFT');
			}
		}
		return $criteria;
	}


	/**
	 * @param Criteria       $criteria
	 * @param SearchCriteria $searchCriteria
	 * @return Criteria
	 * @throws Exception
	 */
	private function prepareRestrictions(Criteria $criteria, SearchCriteria $searchCriteria) {
		$and_filters = array();
		$or_filters = array();
		foreach ($searchCriteria->filters() as $filter) {
			$restriction = $filter->restriction();
			if (!method_exists('CriteriaRestriction', $restriction)) {
				throw new Exception('La restricción de filtrado aplicada no existe en la clase CriteriaRestrictions');
			}
			$for = $filter->for();
			if ($for == '') {
				$for = $searchCriteria->entity();
			}
			$constructedRestriction = CriteriaRestriction::$restriction($for.'.'.$filter->property(), $filter->value())->__toString();
			if ($filter->condition() == 'AND') {
				$and_filters[] = $constructedRestriction;
			} else {
				$or_filters[] = $constructedRestriction;
			}
		}
		if (count($and_filters)) {
			$criteria->add_restriction(CriteriaRestriction::and_all($and_filters));
		}
		if (count($or_filters)) {
			$criteria->add_restriction(CriteriaRestriction::or_all($or_filters));
		}
		return $criteria;
	}

	/**
	 *
	 * @param Criteria       $criteria
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filterProperties
	 * @return Criteria
	 */
	private function prepareSelection(Criteria $criteria, SearchCriteria $searchCriteria, array $filterProperties) {
		$entity = $searchCriteria->entity();
		$entity = new ReflectionClass($entity);
		$entity = $entity->newInstance();
		if (empty($filterProperties)) {
			$criteria->add_select($searchCriteria->entity().'.*');
		} else {
			$criteria->add_select($searchCriteria->entity().'.'.$entity->getIdentity());
			foreach ($filterProperties as $filter_property) {
				$criteria->add_select($searchCriteria->entity().'.'.$filter_property);
			}
		}
		$criteria->add_from($entity->getPersistenceTarget(), $searchCriteria->entity());
		return $criteria;
	}


	/**
	 * Realiza la encapsulación de un resultado de una query a la base de datos en una instancia de un objeto.
	 * @param $arrayResult
	 * @param $instance
	 * @return
	 */
	private function encapsulate($arrayResult, $instance) {
		if (empty($arrayResult)) {
			return null;
		}
		foreach ($arrayResult as $property => $value) {
			$instance->set($property, $value, false);
		}
		return $instance;
	}

	/**
	 * @param $array
	 * @param $entity
	 * @return array
	 */
	private function encapsulateArray($array, $entity) {
		$result = array();
		$reflected = new ReflectionClass($entity);
		foreach ($array as $row) {
			$empty = $reflected->newInstance();
			$result[] = $this->encapsulate($row, $empty, false);
		}
		return $result;
	}



}
