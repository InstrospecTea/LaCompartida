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
	/**
	 * Sentencia equals entre dos argumentos.
	 * @param $left
	 * @param $right
	 * @return CriteriaRestriction
	 */
	public static function equals($left, $right) {
		return new CriteriaRestriction('(' . $left . ' = ' . $right . ')');
	}

	/**
	 * Sentencia not equals entre dos argumentos.
	 * @param $left
	 * @param $right
	 * @return CriteriaRestriction
	 */
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
	 * @param array $condition_array
	 * @return CriteriaRestriction
	 * @throws Exception
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
	 * @param array $condition_array
	 * @return CriteriaRestriction
	 * @throws Exception
	 */
	public static function or_all(array $condition_array) {
		if (is_array($condition_array)) {
			return new CriteriaRestriction(implode(' OR ', $condition_array));
		} else {
			throw new Exception('The condition_array parameter is not an array!');
		}
	}

	/**
	 * Añade sentencia IN para una columna y un conjunto de matches posibles.
	 * @param       $column
	 * @param array $comparsion_group
	 * @return CriteriaRestriction
	 * @throws Exception
	 */
	public static function in($column, array $comparsion_group) {
		if (is_array($comparsion_group)) {
			return new CriteriaRestriction(' '.$column.' IN ('.implode(',', $comparsion_group).')');
		} else {
			throw new Exception('The condition_array parameter is not an array!');
		}
	}

	/**
	 * Añade sentencia IN para una columna y una instancia de criteria que determina los valores.
	 * @param          $column
	 * @param Criteria $criteria
	 * @return CriteriaRestriction
	 */
	public static function in_from_criteria($column, Criteria $criteria) {
		return new CriteriaRestriction(' '.$column.' IN ('.$criteria->get_plain_query().')');
	}

	/**
	 * Añade sentencia Mayor Igual Que, para una Columna >= pivote
	 * @param $column
	 * @param $pivot
	 * @return CriteriaRestriction
	 */
	public static function greater_or_equals_than($column , $pivot) {
		return new CriteriaRestriction('('.$column.' >= \''.$pivot.'\')');
	}

	/**
	 * Añade sentencia Menor Igual Que, para una Columna <= pivote
	 * @param $column
	 * @param $pivot
	 * @return CriteriaRestriction
	 */
	public static function lower_or_equals_than($column , $pivot) {
		return new CriteriaRestriction('('.$column.' <= \''.$pivot.'\')');
	}

	/**
	 * Añade sentencia que compara si una columna no tiene un valor nulo.
	 * @param $column
	 * @return CriteriaRestriction
	 */
	public static function is_not_null($column) {
		return new CriteriaRestriction($column.' IS NOT NULL');
	}

}