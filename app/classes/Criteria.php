<?php

/**
 *
 * Clase que permite generar criterios de búsqueda contra el medio persistente (a.k.a una query de base de datos).
 *
 * TODO Declaration (@dochoaj):
 * 	La implementación está basada en lo mínimo indispensable para resolver el problema de los reportes. No obstante,
 * a medida de que pase el tiempo y se depure el uso, se puede cambiar a una implementación mediante el uso de reflection en PHP, para
 * reducir la cantidad de querys repartidas por los distintos archivos del software.
 *
 */
class Criteria {

	/**
	 * Permite generar una conexión con el medio persistente.
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
	 */
	public function run() {
		if ($this->sesion == null) {
			throw new Exception('Criteria dice: No hay una sesión definida para Criteria, no es posible ejecutar.');
		}
		$statement = $this->sesion->pdodbh->prepare($this->get_plain_query());
		$statement->execute();
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	/*
	  QUERY BUILDER METHODS
	 */

	/**
	 * Añade un statement de selección al criterio de búsqueda.
	 * @param string $attribute
	 * @param string $alias
	 * @return Criteria
	 */
	public function add_select($attribute, $alias = null) {
		if (is_null($alias)) {
			$alias = '';
		}
		$new_clause = '';
		$new_clause .= $attribute;
		$new_clause .= (strlen($alias) > 0) ? " AS $alias" : '';
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade un statement de selección no nulo al criterio de búsqueda.
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
	 * [add_select_from_criteria description]
	 * @param Criteria            $criteria    [description]
	 * @param CriteriaRestriction $restriction [description]
	 * @param [type]              $alias       [description]
	 */
	public function add_select_from_criteria(Criteria $toCloneCriteria, CriteriaRestriction $restriction, $alias) {
		$criteria = clone $toCloneCriteria;
		$criteria->add_restriction($restriction);
		$new_clause = '('.$criteria->get_plain_query().') '."'$alias'";
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	public function add_select_not_null_from_criteria(Criteria $toCloneCriteria, CriteriaRestriction $restriction, $alias, $default = '-') {
		$criteria = clone $toCloneCriteria;
		$criteria->add_restriction($restriction);
		$new_clause = 'IFNULL(('.$criteria->get_plain_query().'), \''.$default.'\' ) '."'$alias'";
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade un limite a la cantidad de resultados
	 * @param  $limit Numero de resutlados
	 */
	public function add_limit($limit) {
		if (is_numeric($limit) && $limit >= 0) {
			$this->limit = ' LIMIT ' . $limit;
			return $this;
		} else {
			throw new Exception('Criteria dice: Parámetro asociado al limite de resultados de la query es erróneo. Se esperaba un entero mayor que cero, se obtuvo: ' . "$limit");
		}
	}

	/**
	 * Añade una tabla al scope de búsqueda al criteria.
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
	 * Añade un criteria al scope de búsqueda de este criteria.
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
	 * Añade un scope de búsqueda mediante un JOIN genérico configurable.
	 * @param  string $join_table
	 * @param  string $join_condition
	 * @return Criteria
	 */
	public function add_custom_join_with($join_table, $join_condition, $join_type = 'LEFT') {
		$new_clause = " $join_type JOIN $join_table ON $join_condition ";
		$this->join_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade un scope de búsqueda mediante un LEFT JOIN al criteria.
	 * @param  string $join_table
	 * @param  string $join_condition
	 * @return Criteria
	 */
	public function add_left_join_with($join_table, $join_condition) {
		return $this->add_custom_join_with($join_table, $join_condition, 'LEFT');
	}


	/**
	 * Añade un scope de búsqueda mediante un INNER JOIN al criteria.
	 * @param  string $join_table
	 * @param  string $join_condition
	 * @return Criteria
	 */
	public function add_inner_join_with($join_table, $join_condition) {
		return $this->add_custom_join_with($join_table, $join_condition, 'INNER');
	}

	/**
	 * Añade un criteria al scope de búsqueda a través de un join configurable
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
	 * Añade un criteria al scope de búsqueda a través de un left join con este criteria.
	 * @param Criteria $criteria
	 * @param string   $alias
	 * @param string   $join_condition
	 * @return Criteria
	 */
	public function add_left_join_with_criteria(Criteria $criteria, $alias, $join_condition) {
		return $this->add_custom_join_with_criteria($criteria, $alias, $join_condition, 'LEFT');
	}

	/**
	 * Añade un criteria al scope de búsqueda a través de un inner join con este criteria.
	 * @param Criteria $criteria
	 * @param string   $alias
	 * @param string   $join_condition
	 * @return Criteria
	 */
	public function add_inner_join_with_criteria(Criteria $criteria, $alias, $join_condition) {
		return $this->add_custom_join_with_criteria($criteria, $alias, $join_condition, 'INNER');
	}

	/**
	 * Añade una restricción al criterio de búsqueda.
	 * @param CriteriaRestriction $restriction
	 * @return Criteria
	 */
	public function add_restriction(CriteriaRestriction $restriction) {
		$this->where_clauses[] = $restriction->get_restriction();
		return $this;
	}

	/**
	 * Añade una condición de agrupamiento al criterio de búsqueda.
	 * @param string $group_entity
	 * @return Criteria
	 */
	public function add_grouping($group_entity) {
		$this->grouping_clauses[] = $group_entity;
		return $this;
	}

	/**
	 * Añade una condición de ordenamiento al criterio de búsqueda.
	 * @param string $order
	 * @param ordering_criteria
	 * @return Criteria
	 */
	public function add_ordering($order_entity, $ordering_criteria = 'ASC') {

		if ($ordering_criteria == 'ASC' || $ordering_criteria == 'DESC') {
			$this->ordering_clauses[] = $order_entity.' '.$ordering_criteria;
		} else {
			throw new Exception('Criteria dice: El criterio de orden que se pretende establecer no corresponde al lenguaje SQL. Esperado "ASC" o "DESC", obtenido "'. $order_criteria. '".');
		}
		
		return $this;
	}

	/**
	 *
	 * Establece el criterio de ordenamiento para criteria.
	 * @param [type] $ordering_criteria [description]
	 * @deprecated
	 *
	 */
	public function add_ordering_criteria($ordering_criteria) {
		if ($ordering_criteria == 'ASC' || $ordering_criteria == 'DESC') {
			$this->order_criteria = $ordering_criteria;
			return $this;
		} else {
			throw new Exception('Criteria dice: El criterio de orden que se pretende establecer no corresponde al lenguaje SQL. Esperado "ASC" o "DESC", obtenido '. $order_criteria. '.');
		}
	}

	/*
	  PRIVATE QUERY GENERATION METHODS
	 */

	/**
	 * Genera el statement de SELECT de una query.
	 * @return string
	 * @throws Exception Cuando no se ha definido un criterio de selección.
	 */
	private function generate_select_statement() {
		if (count($this->select_clauses) > 0) {
			return $this->select . " " . implode(',', $this->select_clauses);
		} else {
			throw new Exception('Criteria dice: No se han definido criterios de selección. No es correcto asumir SELECT *. ');
		}
	}

	/**
	 * Genera el statement de FROM de una query.
	 * @return string
	 * @throws Exception  Cuando no se ha definido un scope de búsqueda.
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
			return implode(' ', $this->join_clauses);
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
	 * Obtiene la versión 'raw' de la query generada.
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


}