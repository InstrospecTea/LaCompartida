<?php 

/**
 * 
 * Clase que permite generar criterios de búsqueda contra el medio persistente (una query de base de datos).
 * 
 * TODO Declaration (@dochoaj):
 * 	La implementación está basada en lo mínimo indispensable para resolver el problema de los reportes. No obstante,
 * a medida de que pase el tiempo y se depure el uso, se puede cambiar a una implementación mediante el uso de reflection en PHP, para
 * reducir la cantidad de querys repartidas por los distintos archivos del software.
 * 
 */
class Criteria
{
	/**
	 * Permite generar una conexión con el medio persistente.
	 * @var [type]
	 */
	private $sesion;
	
	/*
		CRITERIA QUERY BUILDER PARAMS.
	 */
	private $select = "SELECT";
	private $from = " FROM";
	private $where = " WHERE";
	private $grouping = " GROUP BY";
	private $ordering = " ORDER BY";
	private $left_joining = " LEFT JOIN";

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
	function __construct($sesion)
	{
		$this->sesion = $sesion;
	}

	/*
		QUERY BUILDER METHODS
	 */


	/**
	 * Añade un statement de selección al criterio de búsqueda.
	 * @param String $attribute
	 * @param String $alias
	 * @return Criteria
	 */
	public function add_select(String $attribute, String $alias = ""){
		$new_clause = "";
		$new_clause .= $attribute;
		if($alias != ""){
			$new_clause .=" '$alias'";
		}
		$this->select_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade una tabla al scope de búsqueda al criteria.
	 * @param String $table
	 * @param String $alias
	 * @return Criteria
	 */
	public function add_from(String $table, String $alias = ""){
		$new_clause = "";
		$new_clause.= $table." ".$alias;
		$this->from_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade un criteria al scope de búsqueda de este criteria.
	 * @param Criteria $criteria
	 * @param String   $alias
	 * @return Criteria
	 */
	public function add_from_criteria(Criteria $criteria, String $alias){
		$new_clause = "";
		$new_clause.= '('.$criteria->get_plain_query.') AS '.$alias;
		$this->from_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade un scope de búsqueda mediante un LEFT JOIN al criteria.
	 * @param  String $join_table
	 * @param  String $join_condition
	 * @return Criteria
	 */
	public function add_left_join_with(String $join_table, String $join_condition){
		$new_clause = "";
		$new_clause.= $this->left_joining." ";
		$new_clause.= $join_table." ON ".$join_condition;
		$this->join_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade un criteria al scope de búsqueda a través de un left join con este crtieria.
	 * @param Criteria $criteria
	 * @param String   $alias
	 * @param String   $join_condition
	 * @return Criteria
	 */
	public function add_left_join_with_criteria(Criteria $criteria, String $alias, String $join_condition){
		$new_clause = "";
		$new_clause.= $this->left_joining." ";
		$new_clause.= "(".$criteria->get_plain_query().") ".$alias." ON ".$join_condition;
		$this->join_clauses[] = $new_clause;
		return $this;
	}

	/**
	 * Añade una restricción al criterio de búsqueda.
	 * @param CriteriaRestriction $restriction
	 * @return Criteria
	 */
	public function add_restriction(CriteriaRestriction $restriction){
		$this->where_clauses[] = $restriction;
		return $this;
	}

	/**
	 * Añade una condición de agrupamiento al criterio de búsqueda.
	 * @param String $group_entity
	 * @return Criteria
	 */
	public function add_grouping(String $group_entity){
		$this->grouping_clauses[] = $group;
		return $this;
	}

	/**
	 * Añade una condición de ordenamiento al criterio de búsqueda.
	 * @param String $order
	 * @return Criteria
	 */
	public function add_ordering($order){
		$this->ordering_clauses[] = $order;
		return $this;
	}

	/*
		PRIVATE QUERY GENERATION METHODS
	 */

	/**
	 * Genera el statement de SELECT de una query.
	 * @return String
	 * @throws Exception Cuando no se ha definido un criterio de selección.
	 */
	private function generate_select_statement(){
		if(count($this->select_clauses) > 0){
			return $this->select." ".implode(",", $this->select_clauses);
		}
		else{
			throw new Exception("Criteria dice: No se han definido criterios de selección. No es correcto asumir SELECT *. ");
		}
	}

	/**
	 * Genera el statement de FROM de una query.
	 * @return String
	 * @throws Exception  Cuando no se ha definido un scope de búsqueda.
	 */
	private function generate_from_statement(){
		if(count($this->from_clauses) > 0){
			return $this->from." ".implode(",", $this->from_clauses);
		}
		else{
			throw new Exception("Criteria dice: No se ha definido desde que tabla(s) obtener los datos.");
		}
	}

	/**
	 * Genera el statement de JOIN de una query, si hubieren.
	 * @return String
	 */
	private function generate_join_statement(){
		if(count($this->join_clauses) > 0){
			return implode(" ", $this->join_clauses);
		}
		else{
			return "";
		}
	}

	/**
	 * Genera el statement WHERE de una query, si hubiere.
	 * @return String
	 */
	private function generate_where_statement(){
		if(count($this->where_clauses) > 0){
			return $this->where." ".implode(",", $this->where_clauses);
		}
		else{
			return "";
		}
	}

	/**
	 * Genera el statement GROUP BY de una query, si hubiere.
	 * @return String
	 */
	private function generate_grouping_statement(){
		if(count($this->grouping_clauses) > 0){
			return $this->grouping." ".implode(",", $this->grouping_clauses);
		}
		else{
			return "";
		}
	}

	/**
	 * Genera el statement ORDER BY de una query, si hubiere.
	 * @return String
	 */
	private function generate_ordering_statement(){
		if(count($this->ordering_clauses) > 0){
			return $this->ordering." ".implode(",", $this->ordering_clauses);
		}
		else{
			return "";
		}
	}

	/*
		QUERY ACCESS METHODS
	 */
	
	/**
	 * Obtiene la versión 'raw' de la query generada.
	 * @return String
	 */
	public function get_plain_query(){
		return 	$this->generate_select_statement().
				$this->generate_from_statement().
				$this->generate_join_statement().
				$this->generate_where_statement().
				$this->generate_grouping_statement().
				$this->generate_ordering_statement();
	}



}


?>