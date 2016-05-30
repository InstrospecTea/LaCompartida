<?php

/**
 * Class SearchFilter
 * Establece un filtro de búsqueda respecto a condiciones y comparaciones. Depende de {@link SearchCriteria}.
 */
class SearchFilter extends AbstractUtility {

	protected $property;
	protected $function;
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
	 * Establece la entidad del criterio de búsqueda sobre la cual se aplica la instancia de filtrado. Cuando la entidad
	 * no se explicita, se asume que es la entidad principal del {@link SearchCriteria}.
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

	/**
	 * Establece la condicion lógica utilizada para la condicion de filtrado. Puede ser 'OR' o 'AND'.
	 * @param $condition
	 * @return $this
	 * @throws Exception
	 */
	function by_condition($condition) {
		if (!preg_match('/AND|OR/i', $condition)) {
			throw new UtilityException('La condicion de filtrado debe ser AND u OR.');
		}
		$this->condition = $condition;
		return $this;
	}

	/**
	 * Establece la restricción que se aplicará sobre el filtro, sobre la cual se van a comparar.
	 * Debe ser uno de los métodos de {@link CriteriaRestriction}.
	 * @param $restriction
	 * @return $this
	 * @throws Exception
	 */
	function restricted_by($restriction) {
		if (empty($restriction)) {
			throw new UtilityException('Se está agregando un filtro vacío.');
		}
		$this->restriction = $restriction;
		return $this;
	}

	/**
	 * Establece un método de refinamiento, basado en SQL, para el filtro de búsqueda.
	 * @Needings Crear un mecanismo para agregar functions de una manera más elegante, quizá otra clase.
	 * @param $function
	 * @return $this
	 * @throws UtilityException
	 */
	function with_function($function) {
		if (empty($function)) {
			throw new UtilityException('Se está agregando una función vacía.');
		}
		$this->function = $function;
		return $this;
	}

	/**
	 * Establece el valor con el que se compara la propiedad filtrada, bajo las condiciones y restricciones definidas.
	 * @param $value
	 * @return $this
	 */
	function compare_with() {
		$this->value = func_get_args();
		return $this;
	}
}