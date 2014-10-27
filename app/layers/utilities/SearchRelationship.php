<?php

/**
 * Class SearchRelationship
 * Establece una relación entre dos entidades. Depende de una instancia de {@link SearchCriteria}.
 */
class SearchRelationship extends AbstractUtility{

	protected $entity;
	protected $property;
	protected $condition;


	function __construct($entity) {
		if (empty($entity)) {
			throw new UtilityException('La entidad de la relación no puede ser vacía.');
		}
		$this->entity = $entity;
		$this->condition = 'equals';
	}

	/**
	 * Establece la propiedad que se utilizará para construir la relación entre la entidad principal y la secundaria.
	 * @param $property
	 * @return $this
	 * @throws Exception
	 */
	function on_property($property) {
		if (empty($property)) {
			throw new UtilityException('La propiedad mediante la cual se establece de la relación, cuando se explicita, no puede ser vacía.');
		}
		$this->property = $property;
		return $this;
	}

	/**
	 * Establece la condición mediante la cuál se establecerá la relación. Debe ser uno de los métodos de
	 * {@link CriteriaRestriction}.
	 * @param $condition
	 * @return $this
	 * @throws Exception
	 */
	function by_condition($condition) {
		if (empty($condition)) {
			throw new UtilityException('La condición que establece la relación entre dos entidades, cuando se explicita, no puede ser vacía.');
		}
		$this->condition = $condition;
		return $this;
	}


} 