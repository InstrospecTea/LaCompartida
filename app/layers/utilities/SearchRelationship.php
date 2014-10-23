<?php

class SearchRelationship extends AbstractUtility{

	protected $entity;
	protected $property;
	protected $condition;

	function __construct($entity) {
		if (empty($entity)) {
			throw new Exception('La entidad de la relaci�n no puede ser vac�a.');
		}
		$this->entity = $entity;
		$this->condition = 'equals';
	}

	/**
	 * @param $property
	 * @return $this
	 * @throws Exception
	 */
	function on_property($property) {
		if (empty($property)) {
			throw new Exception('La propiedad mediante la cual se establece de la relaci�n, cuando se explicita, no puede ser vac�a.');
		}
		$this->property = $property;
		return $this;
	}

	/**
	 * @param $condition
	 * @return $this
	 * @throws Exception
	 */
	function by_condition($condition) {
		if (empty($condition)) {
			throw new Exception('La condici�n que establece la relaci�n entre dos entidades, cuando se explicita, no puede ser vac�a.');
		}
		$this->condition = $condition;
		return $this;
	}







} 