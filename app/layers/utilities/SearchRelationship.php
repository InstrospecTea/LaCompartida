<?php

/**
 * Class SearchRelationship
 * Establece una relación entre dos entidades. Depende de una instancia de {@link SearchCriteria}.
 */
class SearchRelationship extends AbstractUtility{

	protected $entity;
	protected $with_entity;
	protected $with_property;
	protected $property;
	protected $condition;
	protected $join;
	protected $alias;


	public function __construct($entity) {
		if (empty($entity)) {
			throw new UtilityException('La entidad de la relación no puede ser vacía.');
		}
		$this->entity = $entity;
		$this->alias = $entity;
		$this->condition = 'equals';
		$this->join = 'LEFT';
	}

	/**
	 * Establece la propiedad que se utilizará para construir la relación entre la entidad principal y la secundaria.
	 * @param $property
	 * @return $this
	 * @throws Exception
	 */
	public function on_property($property) {
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
	public function by_condition($condition) {
		if (empty($condition)) {
			throw new UtilityException('La condición que establece la relación entre dos entidades, cuando se explicita, no puede ser vacía.');
		}
		$this->condition = $condition;
		return $this;
	}

	/**
	 * Indica la dirección del join
	 * @param type $join
	 * @return \SearchRelationship
	 * @throws UtilityException
	 */
	public function with_direction($join) {
		if (!preg_match('/left|right|inner/i', $join)) {
			throw new UtilityException('La unión que establece la relación debe ser INNER, LEFT o RIGHT.');
		}
		$this->join = $join;
		return $this;
	}

	/**
	 * Establece un alias para la entidad
	 * @param type $alias
	 * @return \SearchRelationship
	 * @throws UtilityException
	 */
	public function as_alias($alias) {
		if (empty($alias)) {
			throw new UtilityException('El de la entidad no puede ser vacía.');
		}
		$this->alias = $alias;
		return $this;
	}

	/**
	 * Relaciona la entidad con otra entidad distinta a la definida por SearchCriteria.
	 * @param type $alias
	 * @throws UtilityException
	 */
	public function related_with($alias) {
		if (empty($alias)) {
			throw new UtilityException('La entidad de la relación, no puede ser vacía.');
		}
		$this->with_entity = $alias;
		return $this;
	}

	/**
	 * Relaciona la entidad con otra entidad distinta a la definida por SearchCriteria.
	 * @param type $alias
	 * @throws UtilityException
	 */
	public function on_entity_property($property) {
		if (empty($property)) {
			throw new UtilityException('La propiedad mediante la cual se establece de la relación, cuando se explicita, no puede ser vacía.');
		}
		$this->with_property = $property;
		return $this;
	}
}