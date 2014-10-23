<?php

class SearchFilter extends AbstractUtility {

	protected $property;
	protected $restriction;
	protected $condition;
	protected $value;
	protected $for;

	function __construct($property) {
		if (empty($property)) {
			throw new Exception('La propiedad por la que se pretende filtrar no puede ser vacía.');
		}
		$this->property = $property;
		$this->restriction = 'equals';
		$this->condition = 'AND';
		$this->for = '';
	}

	/**
	 * @param $condition
	 * @return $this
	 * @throws Exception
	 */
	function by_condition($condition) {
		if ($condition != 'AND' && $condition != 'OR') {
			throw new Exception('La condicion de filtrado debe ser AND u OR.');
		}
		$this->condition = $condition;
		return $this;
	}

	/**
	 * @param $value
	 * @return $this
	 */
	function compare_with($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * @param $restriction
	 * @return $this
	 * @throws Exception
	 */
	function restricted_by($restriction) {
		if (empty($restriction)) {
			throw new Exception('Se está agregando un filtro vacío.');
		}
		$this->restriction = $restriction;
		return $this;
	}

	/**
	 * @param $for
	 * @return $this
	 * @throws Exception
	 */
	function for_entity($for) {
		if (empty($for)) {
			throw new Exception('Si se explicita la entidad para la que se aplica el filtro, esta no puede ser vacía.');
		}
		$this->for = $for;
		return $this;
	}



} 