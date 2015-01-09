<?php

/**
 * Class SearchRelationship
 * Establece una relaci�n entre dos entidades. Depende de una instancia de {@link SearchCriteria}.
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
			throw new UtilityException('La entidad de la relaci�n no puede ser vac�a.');
		}
		$this->entity = $entity;
		$this->alias = $entity;
		$this->condition = 'equals';
		$this->join = 'LEFT';
	}

	/**
	 * Establece la propiedad que se utilizar� para construir la relaci�n entre la entidad principal y la secundaria.
	 * @param $property
	 * @return $this
	 * @throws Exception
	 */
	public function on_property($property) {
		if (empty($property)) {
			throw new UtilityException('La propiedad mediante la cual se establece de la relaci�n, cuando se explicita, no puede ser vac�a.');
		}
		$this->property = $property;
		return $this;
	}

	/**
	 * Establece la condici�n mediante la cu�l se establecer� la relaci�n. Debe ser uno de los m�todos de
	 * {@link CriteriaRestriction}.
	 * @param $condition
	 * @return $this
	 * @throws Exception
	 */
	public function by_condition($condition) {
		if (empty($condition)) {
			throw new UtilityException('La condici�n que establece la relaci�n entre dos entidades, cuando se explicita, no puede ser vac�a.');
		}
		$this->condition = $condition;
		return $this;
	}

	/**
	 * Indica la direcci�n del join
	 * @param type $join
	 * @return \SearchRelationship
	 * @throws UtilityException
	 */
	public function with_direction($join) {
		if (!preg_match('/left|right|inner/i', $join)) {
			throw new UtilityException('La uni�n que establece la relaci�n debe ser INNER, LEFT o RIGHT.');
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
			throw new UtilityException('El de la entidad no puede ser vac�a.');
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
			throw new UtilityException('La entidad de la relaci�n, no puede ser vac�a.');
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
			throw new UtilityException('La propiedad mediante la cual se establece de la relaci�n, cuando se explicita, no puede ser vac�a.');
		}
		$this->with_property = $property;
		return $this;
	}
}