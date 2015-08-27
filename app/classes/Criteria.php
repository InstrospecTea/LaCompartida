<?php

/**
 *
 * Clase que permite generar criterios de b�squeda contra el medio persistente (a.k.a una query de base de datos).
 *
 * TODO Declaration (@dochoaj):
 * 	La implementaci�n est� basada en lo m�nimo indispensable para resolver el problema de los reportes. No obstante,
 * a medida de que pase el tiempo y se depure el uso, se puede cambiar a una implementaci�n mediante el uso de reflection en PHP, para
 * reducir la cantidad de querys repartidas por los distintos archivos del software.
 *
 */
class Criteria {

	/**
	 * Permite generar una conexi�n con el medio persistente.
	 * @var [type]
	 */
	private $sesion;

	/*
	  CRITERIA QUERY BUILDER PARAMS.
	 */
	private $select = 'SELECT';
	private $from = ' FROM';
	private $where = ' WHERE';
	private $grouping = ' GROUP BY';
	private $ordering = ' ORDER BY';
	private $limit = '';
	private $order_criteria = '';

	/*
	  CRITERIA SCOPE ENVELOPERS.
	 */
	private $select_clauses = array();
	private $from_clauses = array();
	private $join_clauses = array();
	private $where_clauses = array();
	private $grouping_clauses = array();
	private $ordering_clauses = array();

	/**
	 * Constructor de la clase.
	 * @param $sesion
	 */
	function __construct($sesion = null) {
		$this->sesion = $sesion;
	}

	/**
	 * Ejecuta una query en base a PDO, considerando los criterios definidos en este Criteria.
	 * @return Array asociativo de resultados.
	 * @throws Exception
	 *
	 */
	public function run() {
		if ($this->sesion == null) {
			throw new Exception('Criteria dice: No hay una sesi�n definida para Criteria, no es posible ejecutar.');
		}
		return self::query($this->get_plain_query(), $this->sesion);
	}

	/*
	  QUERY BUILDER METHODS
	 */

	/**
	 * A�ade un statement de selecci�n al criterio de b�squeda.
	 * @param string $attribute
	 * @param string $alias
	 * @return Criteria
	 */
	public function add_select($attribute, $alias = null) {
		$new_clause = $attribute . (!empty($alias) ? " AS $alias" : '');
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * A�ade un statement de selecci�n no nulo al criterio de b�squeda.
	 * @param string $attribute
	 * @param string $alias
	 * @param string $default
	 * @return Criteria
	 */
	public function add_select_not_null($attribute, $alias = null, $default = '-') {
		if (is_null($alias)) {
			$alias = $attribute;
		}
		$new_clause = 'IFNULL('.$attribute.', '."'$default'".')';
		if ($alias != '') {
			$new_clause .= " '$alias'";
		}
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * @param Criteria            $toCloneCriteria
	 * @param CriteriaRestriction $restriction
	 * @param                     $alias
	 * @return $this
	 */
	public function add_select_from_criteria(Criteria $toCloneCriteria, CriteriaRestriction $restriction, $alias) {
		$criteria = clone $toCloneCriteria;
		$criteria->add_restriction($restriction);
		$new_clause = '('.$criteria->get_plain_query().') '."'$alias'";
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * @param Criteria            $toCloneCriteria
	 * @param CriteriaRestriction $restriction
	 * @param                     $alias
	 * @param string              $default
	 * @return $this
	 */
	public function add_select_not_null_from_criteria(Criteria $toCloneCriteria, CriteriaRestriction $restriction, $alias, $default = '-') {
		$criteria = clone $toCloneCriteria;
		$criteria->add_restriction($restriction);
		$new_clause = 'IFNULL(('.$criteria->get_plain_query().'), \''.$default.'\' ) '."'$alias'";
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * @param $limit
	 * @return $this
	 * @throws Exception
	 */
	public function add_limit($limit, $from = 0) {
		if (is_numeric($limit) && $limit >= 0) {
			if ($from) {
				$this->limit = " LIMIT $from, $limit";
			} else {
				$this->limit = " LIMIT $limit";
			}
			return $this;
		} else {
			throw new Exception('Criteria dice: Par�metro asociado al limite de resultados de la query es err�neo. Se esperaba un entero mayor que cero, se obtuvo: ' . "$limit");
		}
	}

	/**
	 * A�ade una tabla al scope de b�squeda al criteria.
	 * @param string $table
	 * @param string $alias
	 * @return Criteria
	 */
	public function add_from($table, $alias = null) {
		if (is_null($alias)) {
			$alias = '';
		}
		$new_clause = '';
		$new_clause .= $table;
		$new_clause .= (strlen($alias) > 0) ? " AS $alias" : '';
		$this->from_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * A�ade un criteria al scope de b�squeda de este criteria.
	 * @param Criteria $criteria
	 * @param string   $alias
	 * @return Criteria
	 */
	public function add_from_criteria(Criteria $criteria, $alias) {
		$new_clause = '';
		$new_clause .= '(' . $criteria->get_plain_query() . ') AS ' . $alias;
		$this->from_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * A�ade un scope de b�squeda mediante un JOIN gen�rico configurable.
	 * @param        $join_table
	 *		Posibles llamadas:
	 *		 - string nombre_tabla
	 *		 - array [nombre_tabla, alias]
	 * @param        $join_condition
	 * @param string $join_type
	 * @return $this
	 */
	public function add_custom_join_with($join_table, $join_condition, $join_type = 'LEFT') {
		if (is_array($join_table)) {
			$join_table = implode(' AS ', $join_table);
		}
		$new_clause = " $join_type JOIN $join_table ON $join_condition ";
		if (!$this->check_if_exists($this->join_clauses, $new_clause)) {
			$this->join_clauses[] = $new_clause;
		}
		return $this;
	}

	private function check_if_exists($source, $new_value) {
		return in_array($new_value, $source);
	}

	/**
	 * A�ade un scope de b�squeda mediante un LEFT JOIN al criteria.
	 * @param        $join_table
	 *		Posibles llamadas:
	 *		 - string nombre_tabla
	 *		 - array [nombre_tabla, alias]
	 * @param  string $join_condition
	 * @return Criteria
	 */
	public function add_left_join_with($join_table, $join_condition) {
		return $this->add_custom_join_with($join_table, $join_condition, 'LEFT');
	}


	/**
	 * A�ade un scope de b�squeda mediante un INNER JOIN al criteria.
	 * @param        $join_table
	 *		Posibles llamadas:
	 *		 - string nombre_tabla
	 *		 - array [nombre_tabla, alias]
	 * @param  string $join_condition
	 * @return Criteria
	 */
	public function add_inner_join_with($join_table, $join_condition) {
		return $this->add_custom_join_with($join_table, $join_condition, 'INNER');
	}

	/**
	 * A�ade un criteria al scope de b�squeda a trav�s de un join configurable
	 * @param Criteria $criteria
	 * @param string   $alias
	 * @param string   $join_condition
	 * @param string   $join_type
	 * @return Criteria
	 */
	public function add_custom_join_with_criteria(Criteria $criteria, $alias, $join_condition, $join_type = 'LEFT') {
		$new_clause = " $join_type JOIN ({$criteria->get_plain_query()}) AS $alias ON $join_condition ";
		$this->join_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * A�ade un criteria al scope de b�squeda a trav�s de un left join con este criteria.
	 * @param Criteria $criteria
	 * @param string   $alias
	 * @param string   $join_condition
	 * @return Criteria
	 */
	public function add_left_join_with_criteria(Criteria $criteria, $alias, $join_condition) {
		return $this->add_custom_join_with_criteria($criteria, $alias, $join_condition, 'LEFT');
	}

	/**
	 * A�ade un criteria al scope de b�squeda a trav�s de un inner join con este criteria.
	 * @param Criteria $criteria
	 * @param string   $alias
	 * @param string   $join_condition
	 * @return Criteria
	 */
	public function add_inner_join_with_criteria(Criteria $criteria, $alias, $join_condition) {
		return $this->add_custom_join_with_criteria($criteria, $alias, $join_condition, 'INNER');
	}

	/**
	 * A�ade una restricci�n al criterio de b�squeda.
	 * @param CriteriaRestriction $restriction
	 * @return Criteria
	 */
	public function add_restriction(CriteriaRestriction $restriction) {
		$string_restriction = $restriction->get_restriction();
		if (!empty($string_restriction)) {
			$this->where_clauses[] = $restriction->get_restriction();
		}
		return $this;
	}

	/**
	 * A�ade una condici�n de agrupamiento al criterio de b�squeda.
	 * @param string $group_entity
	 * @return Criteria
	 */
	public function add_grouping($group_entity) {
		if (!in_array($group_entity, $this->grouping_clauses)) {
			$this->grouping_clauses[] = $group_entity;
		}
		return $this;
	}

	/**
	 * A�ade una condici�n de ordenamiento al criterio de b�squeda.
	 * @param        $order_entity
	 * @param string $ordering_criteria
	 * @return $this
	 * @throws Exception
	 */
	public function add_ordering($order_entity, $ordering_criteria = 'ASC') {

		if ($ordering_criteria == 'ASC' || $ordering_criteria == 'DESC') {
			$order = $order_entity.' '.$ordering_criteria;
			if (!in_array($order, $this->ordering_clauses)) {
				$this->ordering_clauses[] = $order;
			}
		} else {
			throw new Exception('Criteria dice: El criterio de orden que se pretende establecer no corresponde al lenguaje SQL. Esperado "ASC" o "DESC", obtenido "'. $ordering_criteria. '".');
		}

		return $this;
	}

	/**
	 * Establece el criterio de ordenamiento para criteria.
	 * @param $ordering_criteria
	 * @return $this
	 * @throws Exception
	 */
	public function add_ordering_criteria($ordering_criteria) {
		if ($ordering_criteria == 'ASC' || $ordering_criteria == 'DESC') {
			$this->order_criteria = $ordering_criteria;
			return $this;
		} else {
			throw new Exception('Criteria dice: El criterio de orden que se pretende establecer no corresponde al lenguaje SQL. Esperado "ASC" o "DESC", obtenido '. $ordering_criteria. '.');
		}
	}

	/*
	  PRIVATE QUERY GENERATION METHODS
	 */

	/**
	 * Genera el statement de SELECT de una query.
	 * @return string
	 * @throws Exception Cuando no se ha definido un criterio de selecci�n.
	 */
	private function generate_select_statement() {
		if (count($this->select_clauses) > 0) {
			return $this->select . " " . implode(',', $this->select_clauses);
		} else {
			throw new Exception('Criteria dice: No se han definido criterios de selecci�n. No es correcto asumir SELECT *. ');
		}
	}

	/**
	 * Genera el statement de FROM de una query.
	 * @return string
	 * @throws Exception  Cuando no se ha definido un scope de b�squeda.
	 */
	private function generate_from_statement() {
		if (count($this->from_clauses) > 0) {
			return $this->from . ' ' . implode(',', $this->from_clauses);
		} else {
			throw new Exception('Criteria dice: No se ha definido desde que tabla(s) obtener los datos.');
		}
	}

	/**
	 * Genera el statement de JOIN de una query, si hubieren.
	 * @return string
	 */
	private function generate_join_statement() {
		if (count($this->join_clauses) > 0) {
			return "\n" . implode("\n", $this->join_clauses) . "\n" ;
		} else {
			return '';
		}
	}

	/**
	 * Genera el statement WHERE de una query, si hubiere. Por defecto considera AND para unir statements.
	 * @return string
	 */
	private function generate_where_statement() {
		if (count($this->where_clauses) > 0) {
			return $this->where . ' ' . implode(' AND ', $this->where_clauses);
		} else {
			return '';
		}
	}

	/**
	 * Genera el statement GROUP BY de una query, si hubiere.
	 * @return string
	 */
	private function generate_grouping_statement() {
		if (count($this->grouping_clauses) > 0) {
			return $this->grouping . ' ' . implode(',', $this->grouping_clauses);
		} else {
			return '';
		}
	}


	/**
	 * Genera el statement ORDER BY de una query, si hubiere.
	 * @return string
	 */
	private function generate_ordering_statement(){

		$order_criteria = 'ASC';

		if (!empty($this->ordering_clauses)) {
			return $this->ordering.' '.implode(',', $this->ordering_clauses);

		} else {
			return '';
		}

	}

	/*
	  QUERY ACCESS METHODS
	 */

	/**
	 * Obtiene la versi�n 'raw' de la query generada.
	 * @return string
	 */
	public function get_plain_query() {
		return $this->generate_select_statement() .
				$this->generate_from_statement() .
				$this->generate_join_statement() .
				$this->generate_where_statement() .
				$this->generate_grouping_statement() .
				$this->generate_ordering_statement() .
				$this->limit;
	}

	public function __toString() {
		try {
			return $this->get_plain_query();
		} catch(Exception $e) {
			return $e->getLine() . ': ' . $e->getMessage();
		}
	}

	public static function query($query, Sesion $sesion) {
		$statement = $sesion->pdodbh->prepare($query);
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function reset_selection() {
		$this->select_clauses = array();
		return $this;
	}

	public function reset_limits() {
		$this->limit = '';
		return $this;
	}
}
