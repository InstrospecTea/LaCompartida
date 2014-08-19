<?php

/**
* 	
*/
class InsertCriteria
{
	/**
	 * Permite generar una conexión con el medio persistente.
	 * @var [type]
	 */
	private $sesion;
	
	/*
		CRITERIA QUERY BUILDER PARAMS.
	 */
	private $insert = 'INSERT INTO ';
	private $table_name = '';
	private $select_criteria = null;

	/*
		CRITERIA SCOPE ENVELOPERS.
	 */
	private $insert_value_clause = array();

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
	}

	public function add_pivot_with_value($column_key, $value) {
		if (array_key_exists($column_key, $this->insert_value_clause)){
			throw new Exception("Criteria dice: Usted está sobrescribiendo un valor para la columna $column_key que ya está definido");
		} else {
			if (is_null($value) || $value == 'NULL' ) {
				$this->insert_value_clause[$column_key] = 'NULL';
			} else {
				$this->insert_value_clause[$column_key] = "'".$value."'";
			}
			return $this;
		}
	}

	public function add_pivot($column_key) {
		if (array_key_exists($column_key, $this->insert_value_clause)) {
			throw new Exception("Criteria dice: Usted está declarando nuevamente la columna $column_key");
		} else {
			$this->insert_value_clause[$column_key] = '';
			return $this;
		}
	}

	public function set_into($table) {
		if (is_string($table)) {
			$this->table_name = $table;
			return $this;
		} else {
			throw new Exception("Criteria dice: El nombre de la tabla en la que se persistirán los registros debe ser String");
		}
	}

	public function values_criteria(Criteria $criteria){
		$this->select_criteria = $criteria;
		return $this;
	}

	private function get_pivotes_fragment() {
		$pivotes = array_keys($this->insert_value_clause);

		return '('.implode(',',$pivotes).')';

	}

	private function get_values_fragment() {
		$pivotes = array_values($this->insert_value_clause);

		return '('.implode(',',$pivotes).')';
	}

	public function get_plain_query() {
		
		return $this->insert.$this->table_name.' '.$this->get_pivotes_fragment().' VALUES '.$this->get_values_fragment();

	}

	public function get_plain_query_from_criteria(){
		throw new Exception("No implementado aún.");
	}


}