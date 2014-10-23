<?php

class SearchRelationship extends AbstractUtility{

	protected $entity;
	protected $property;
	protected $condition;

	function __construct($entity) {
		if (empty($entity)) {
			throw new Exception('La entidad de la relación no puede ser vacía.');
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
			throw new Exception('La propiedad mediante la cual se establece de la relación, cuando se explicita, no puede ser vacía.');
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
			throw new Exception('La condición que establece la relación entre dos entidades, cuando se explicita, no puede ser vacía.');
		}
		$this->condition = $condition;
		return $this;
	}







} 