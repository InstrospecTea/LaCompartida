<?php

/**
 * Clase que permite añadir restricciones a los {@link Criteria}.
 *
 * TODO Declaration (@dochoaj):
 * 	La implementación comprende lo mínimo indispensable para generar restricciones en el reporte antiguedad deuda clientes.
 * Luego, con el uso, se pueden ir añadiendo generadores de restricciones (con distinta complejidad).
 *
 */
class CriteriaRestriction {

	private $restriction;

	/**
	 * Constructor de la clase
	 * @param String $restriction
	 */
	function __construct($restriction) {
		$this->restriction = $restriction;
	}

	/**
	 * Respuesta por defecto de la clase a una conversión a string.
	 * @return string Restriction del criteria.
	 */
	public function __toString() {
		return $this->restriction;
	}

	/**
	 * Obtiene la restricción.
	 * @return String
	 */
	public function get_restriction() {
		return $this->restriction;
	}


	/*
	  STATIC RESTRICTION GENERATORS
	 */

	public static function equals($left, $right) {
		return new CriteriaRestriction('(' . $left . ' = ' . $right . ')');
	}

	public static function not_equal($left, $right) {
		return new CriteriaRestriction('(' . $left . ' != ' . $right . ')');
	}

	/**
	 * Genera una sentencia AND entre los argumentos de la izquierda y los de la derecha.
	 * @param  String $left
	 * @param  String $right
	 * @return CriteriaRestriction
	 */
	public static function and_clause($left, $right) {
		return new CriteriaRestriction('(' . $left . ' AND ' . $right . ')');
	}

	/**
	 * Genera una sentencia OR entre los argumentos de la izquierda y los de la derecha.
	 * @param  String $left
	 * @param  String $right
	 * @return CriteriaRestriction
	 */
	public static function or_clause($left, $right) {
		return new CriteriaRestriction('(' . $left . ' OR ' . $right . ")");
	}

	/**
	 * Añade sentencias AND para anidar todas las condiciones en el array $condition_array.
	 * @param  array  $condition_array
	 * @return CriteriaRestriction
	 */
	public static function and_all(array $condition_array) {
		if (is_array($condition_array)) {
			return new CriteriaRestriction(implode(' AND ', $condition_array));
		} else {
			throw new Exception('The condition_array parameter is not an array!');
		}
	}

	/**
	 * Añade sentencias OR para anidar todas las condiciones en el array $condition_array.
	 * @param  [type] $condition_array
	 * @return [type] CriteriaRestriction
	 */
	public static function or_all(array $condition_array) {
		if (is_array($condition_array)) {
			return new CriteriaRestriction(implode(' OR ', $condition_array));
		} else {
			throw new Exception('The condition_array parameter is not an array!');
		}
	}

}