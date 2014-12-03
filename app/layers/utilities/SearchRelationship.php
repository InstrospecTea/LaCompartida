<?php

/**
 * Class SearchRelationship
 * Establece una relaci�n entre dos entidades. Depende de una instancia de {@link SearchCriteria}.
 */
class SearchRelationship extends AbstractUtility{

	protected $entity;
	protected $property;
	protected $condition;


	function __construct($entity) {
		if (empty($entity)) {
			throw new UtilityException('La entidad de la relaci�n no puede ser vac�a.');
		}
		$this->entity = $entity;
		$this->condition = 'equals';
	}

	/**
	 * Establece la propiedad que se utilizar� para construir la relaci�n entre la entidad principal y la secundaria.
	 * @param $property
	 * @return $this
	 * @throws Exception
	 */
	function on_property($property) {
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
	function by_condition($condition) {
		if (empty($condition)) {
			throw new UtilityException('La condici�n que establece la relaci�n entre dos entidades, cuando se explicita, no puede ser vac�a.');
		}
		$this->condition = $condition;
		return $this;
	}


} 