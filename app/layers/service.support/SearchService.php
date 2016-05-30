<?php

/**
 * Class SearchService
 */
class SearchService implements ISearchService {

	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarquía de {@link Entity}, que estén denotadas
	 * por los criterios establecidos en una instancia de {@link SearchCriteria}.
	 * Puede contar con restricciones establecidas por los scopes definidos en la capa de negocio correspondiente
	 * a la búsqueda. Cuando esto sucede, entonces se incluye una referencia a una instancia de un objeto
	 * {@link Criteria} sobre el que tiene que construirse el resto del criterio de búsqueda.
	 * @param SearchCriteria $searchCriteria
	 * @param array          $filter_properties
	 * @param Criteria       $criteria
	 * @param bool $genericMode
	 * @param bool $withIdentity
	 * @return array
	 */
	public function translateCriteria(SearchCriteria $searchCriteria, array $filter_properties = array(), Criteria $criteria = null, $withIdentity = true, $genericMode = false) {
		$criteria = $this->prepareRelationships($criteria, $searchCriteria);
		$criteria = $this->prepareRestrictions($criteria, $searchCriteria);
		$criteria = $this->prepareSelection($criteria, $searchCriteria, $filter_properties, $withIdentity, $genericMode);
		$criteria = $this->prepareGrouping($criteria, $searchCriteria);
		return $criteria;
	}

	public function counterCriteria(SearchCriteria $searchCriteria, Criteria $criteria = null) {
		$filter_properties = array('count(1) as total');
		$criteria = $this->prepareRelationships($criteria, $searchCriteria);
		$criteria = $this->prepareRestrictions($criteria, $searchCriteria);
		$criteria = $this->prepareSelection($criteria, $searchCriteria, $filter_properties, false);
		$criteria = $this->prepareGrouping($criteria, $searchCriteria);
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
		$entityName = $searchCriteria->entity();
		return $this->getData($searchCriteria, $criteria, $entityName);
	}

	/**
	 * Retorna un arreglo de instancias que pertenezcan a la jerarquía de {@link Entity}, que estén denotadas
	 * por los criterios establecidos en una instancia de {@link GenericModel}.
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria       $criteria
	 * @return array
	 */
	public function getGenericResults(SearchCriteria $searchCriteria, Criteria $criteria = null) {
		$entityName = 'GenericModel';
		return $this->getData($searchCriteria, $criteria, $entityName);
	}

	/**
	 * Retorna los datos obtenidos a través de la instancia de @{Criteria} y aplica la paginación si es que fue
	 * configurada en la instancia de @{link SearchCriteria}
	 * @param SearchCriteria $searchCriteria
	 * @param Criteria $criteria
	 * @param $entityName
	 * @return array
	 * @throws Exception
	 */
	private function getData(SearchCriteria $searchCriteria, Criteria $criteria = null, $entityName) {
		if ($searchCriteria->paginate()) {
			$criteria->add_limit($searchCriteria->Pagination->rows_per_page(), $searchCriteria->Pagination->current_row() - 1);
		}
		$entity = new ReflectionClass($entityName);
		$entity = $entity->newInstance();
		$data = $this->encapsulateArray($criteria->run(), $entity);
		if ($searchCriteria->paginate()) {
			$criteria->reset_limits()->reset_selection()->add_select('count(1)', 'total');
			$counter = $criteria->run();
			$searchCriteria->Pagination->total_rows($counter[0]['total']);
		}
		return $data;
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
			if (in_array($relationship->alias(), $usedEntities)) {
				continue;
			}
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
			if ($relationship->with_entity()) {
				$relatedEntity = $relationship->with_entity();
			} else {
				$relatedEntity = $searchCriteria->entity();
			}
			if ($relationship->with_property()) {
				$entityProperty = $relationship->with_property();
			} else {
				$entityProperty = $relatedProperty;
			}
			$constructedRestriction = CriteriaRestriction::$relatedCondition($relationship->alias() . '.' . $relatedProperty, $relatedEntity . '.' . $entityProperty)->__toString();
			$criteria->add_custom_join_with("$relatedTarget AS {$relationship->alias()}", $constructedRestriction, strtoupper($relationship->join()));
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
			$args = array_merge(
				array($this->makeRestrictionName($for, $filter->property())),
				(array) $filter->value()
			);
			$constructedRestriction = call_user_func_array(array('CriteriaRestriction', $restriction), $args)->__toString();
			if ($filter->condition() == 'AND') {
				$and_filters[] = $constructedRestriction;
			} else {
				$or_filters[] = $constructedRestriction;
			}
		}
		if (count($and_filters)) {
			$criteria->add_restriction(CriteriaRestriction::and_clause($and_filters));
		}
		if (count($or_filters)) {
			$criteria->add_restriction(CriteriaRestriction::or_clause($or_filters));
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
	private function prepareSelection(Criteria $criteria, SearchCriteria $searchCriteria, array $filterProperties, $withIdentity = true, $genericMode = false) {
		$entity = $searchCriteria->entity();
		$entity = new ReflectionClass($entity);
		$entity = $entity->newInstance();
		if (empty($filterProperties)) {
			$criteria->add_select($searchCriteria->entity() . '.*');
		} else {
			if ($withIdentity) {
				$criteria->add_select($searchCriteria->entity() . '.' . $entity->getIdentity());
			}
			foreach ($filterProperties as $filter_property) {
				$field_name = $this->makeFieldName($searchCriteria->entity(), $filter_property);
				$criteria = $this->addSelectField($criteria, $field_name, $genericMode);
			}
		}
		$criteria->add_from($entity->getPersistenceTarget(), $searchCriteria->entity());
		return $criteria;
	}

	/**
	 * Añade un campo al statement de selección al criterio de búsqueda. Cuando el nombre del campo contiene la entity
	 * o la tabla correspondiente, agrega un alias con el formato {entity|tabla}_{nombre_campo}
	 * @param $criteria
	 * @param $field_name
	 * @return mixed
	 */
	private function addSelectField($criteria, $field_name, $genericMode) {
		if ($genericMode) {
			$aliasName = $this->makeAliasName($field_name);
			$criteria->add_select($aliasName['field'], $aliasName['alias']);
		} else {
			$criteria->add_select($field_name);
		}

		return $criteria;
	}

	/**
	 * Realiza la encapsulación de un resultado de una query a la base de datos en una instancia de un objeto.
	 * @param $arrayResult
	 * @param $instance
	 * @return
	 */
	private function encapsulate($arrayResult, $instance) {
		$instance->fields = $arrayResult;
		return $instance;
	}

	/**
	 * @param $array
	 * @param $entity
	 * @return array
	 */
	private function encapsulateArray($array, $entity) {
		$total = (int) count($array);
		$result = new SplFixedArray($total);
		for ($i = 0; $i < $total; $i++) {
			$result[$i] = $this->encapsulate($array[$i], new $entity());
		}
		return $result;
	}

	private function makeFieldName($entity, $property) {
		if (preg_match('/^[a-z][a-z0-9_]+\(.*/i', $property)) { //is a function
			return $property;
		} else if (preg_match('/^\([A-Za-z0-9_]+/', $property)) { //maybe it's a subselect
			return $property;
		} else if (preg_match('/^[a-z0-9_]+\.[a-z0-9_]+/i', $property)) { //already include entity or table
			return $property;
		} else {
			return "{$entity}.{$property}";
		}
	}

	private function makeRestrictionName($for, $property) {
		if (preg_match('/\((.*)\)/i', $property, $match)) { //is a function
			return $property;
		}
		return $for . '.' . $property;
	}

	/**
	 * Retorna un arreglo con el nombre del campo y con el alias correspondiente
	 * @param  [string] $field_name
	 * @return [array] array('alias' => '', 'field' => '')
	 */
	private function makeAliasName($field_name) {
		// Si el $field_name tiene un alias asignado ej:
		// Table.field AS custom_field
		// METHOD(Table.field) AS custom_field
		if (preg_match('/^(.+)[\t\s]+as[\t\s]+(.*)$/i', $field_name, $match)) {
			$field_name = $match[1];
			$alias_name = $match[2];
			// Si el field_name es contiene un método ej:
			// METHOD(Table.field)
		} else if (preg_match('/^[a-z][a-z0-9_]+\((.*)\)/i', $field_name, $match)) {
			$alias_name = str_replace('.', '_', strtolower($match[1]));
		} else {
			$alias_name = str_replace('.', '_', strtolower($field_name));
		}

		return array('field' => $field_name, 'alias' => $alias_name);
	}

	private function prepareGrouping(Criteria $criteria, SearchCriteria $searchCriteria) {
		if (is_array($searchCriteria->groups())) {
			foreach ($searchCriteria->groups() as $group) {
				$criteria->add_grouping($this->makeFieldName($searchCriteria->entity(), $group));
			}
		}
		return $criteria;
	}

}
