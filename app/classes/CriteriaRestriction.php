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
		return new CriteriaRestriction("{$left} = {$right}");
	}

	/**
	 * Sentencia not equals entre dos argumentos.
	 * @param $left
	 * @param $right
	 * @return CriteriaRestriction
	 */
	public static function not_equal($left, $right) {
		return new CriteriaRestriction("{$left} != {$right}");
	}

	/**
	 * Genera una sentencia AND entre los argumentos pasados.
	 * @return CriteriaRestriction
	 */
	public static function and_clause() {
		$args = func_get_args();
		$total_args = count($args);
		for ($key = 0; $key < $total_args; ++$key) {
			$arg = $args[$key];
			if (is_array($arg)) {
				$args[$key] = call_user_func_array(array('CriteriaRestriction', 'and_clause'), $arg);
			}
		}
		return new CriteriaRestriction('(' . implode(' AND ', $args) . ')');
	}

	/**
	 * Genera una sentencia OR entre los argumentos pasados.
	 * @return CriteriaRestriction
	 */
	public static function or_clause() {
		$args = func_get_args();
		$total_args = count($args);
		for ($key = 0; $key < $total_args; ++$key) {
			$arg = $args[$key];
			if (is_array($arg)) {
				$args[$key] = call_user_func_array(array('CriteriaRestriction', 'or_clause'), $arg);
			}
		}
		return new CriteriaRestriction('(' . implode(' OR ', $args) . ')');
	}

	/**
	 * Deprecado usar and_clause.
	 * @param array $condition_array
	 * @return CriteriaRestriction
	 * @deprecated
	 */
	public static function and_all(Array $condition_array) {
		return call_user_func_array(array('CriteriaRestriction', 'and_clause'), $condition_array);
	}

	/**
	 * Deprecado usar or_clause.
	 * @param array $condition_array
	 * @return CriteriaRestriction
	 * @deprecated
	 */
	public static function or_all(Array $condition_array) {
		return call_user_func_array(array('CriteriaRestriction', 'or_clause'), $condition_array);
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
			return new CriteriaRestriction("$column IN ('" . implode("','", $comparsion_group) . "')");
		} else {
			throw new Exception('The condition_array parameter is not an array!');
		}
	}

	/**
	 * Añade sentencia NOT IN para una columna y un conjunto de matches posibles.
	 * @param       $column
	 * @param array $comparsion_group
	 * @return CriteriaRestriction
	 * @throws Exception
	 */
	public static function not_in($column, array $comparsion_group) {
		if (is_array($comparsion_group)) {
			return new CriteriaRestriction(" {$column} NOT IN ('" . implode("','", $comparsion_group) . "')");
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
		return new CriteriaRestriction(' ' . $column . ' IN (' . $criteria->get_plain_query() . ')');
	}

	/**
	 * Añade sentencia Mayor Igual Que, para una Columna >= pivote
	 * @param $column
	 * @param $pivot
	 * @return CriteriaRestriction
	 */
	public static function greater_or_equals_than($column, $pivot) {
		return new CriteriaRestriction("{$column} >= {$pivot}");
	}

	/**
	 * Añade sentencia Mayor Que, para una Columna > pivote
	 * @param $column
	 * @param $pivot
	 * @return CriteriaRestriction
	 */
	public static function greater_than($column, $pivot) {
		return new CriteriaRestriction("{$column} > {$pivot}");
	}

	/**
	 * Añade sentencia Menor Igual Que, para una Columna <= pivote
	 * @param $column
	 * @param $pivot
	 * @return CriteriaRestriction
	 */
	public static function lower_or_equals_than($column, $pivot) {
		return new CriteriaRestriction("{$column} <= {$pivot}");
	}

	/**
	 * Añade sentencia Menor Que, para una Columna < pivote
	 * @param $column
	 * @param $pivot
	 * @return CriteriaRestriction
	 */
	public static function lower_than($column, $pivot) {
		return new CriteriaRestriction("{$column} < {$pivot}");
	}

	/**
	 * Añade sentencia que compara si una columna no tiene un valor nulo.
	 * @param $column
	 * @return CriteriaRestriction
	 */
	public static function is_not_null($column) {
		return new CriteriaRestriction($column . ' IS NOT NULL');
	}

	/**
	 * Añade sentencia que compara si una columna tiene un valor nulo.
	 * @param $column
	 * @return CriteriaRestriction
	 */
	public static function is_null($column) {
		return new CriteriaRestriction("{$column} IS NULL");
	}

	/**
	 *
	 * @param type $column
	 * @param type $left
	 * @param type $right
	 * @return \CriteriaRestriction
	 */
	public static function between($column, $left, $right) {
		return new CriteriaRestriction("({$column} BETWEEN {$left} AND {$right})");
	}

}