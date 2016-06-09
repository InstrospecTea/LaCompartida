<?php

/**
*
*/
class InsertCriteria
{
	/**
	 * Permite generar una conexi�n con el medio persistente.
	 * @var [type]
	 */
	private $sesion;

	/*
		CRITERIA QUERY BUILDER PARAMS.
	 */
	private $insert = 'INSERT INTO ';
	private $table_name = '';
	private $select_criteria = null;
	private $insert_criteria = array();
	private $pivotes_temp = array();

	/*
		CRITERIA SCOPE ENVELOPERS.
	 */
	private $insert_value_clause = array();
	private $insert_id = null;

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
			throw new Exception('Criteria dice: No hay una sesi�n definida para Criteria, no es posible ejecutar.');
		}

		$response = new stdClass();

		try {
			$statement = $this->sesion->pdodbh->prepare($this->get_plain_query());
			$statement->execute();

			$response->success = true;
			$this->set_last_insert_id($this->sesion->pdodbh->lastInsertId());
		} catch (Exception $e) {
			$response->success = false;
			$response->message = $e->getMessage();
		}

		return $response;
	}

	public function add_pivot_with_value($column_key, $value, $default = false) {
		if (array_key_exists($column_key, $this->insert_value_clause)){
			throw new Exception("Criteria dice: Usted est� sobrescribiendo un valor para la columna $column_key que ya est� definido");
		} else {
			if (is_null($value) || $value == 'NULL' ) {
				$this->insert_value_clause[$column_key] = 'NULL';
			} else if ($default) {
				$this->insert_value_clause[$column_key] = $value;
			} else {
				$this->insert_value_clause[$column_key] = "'" . addslashes($value) . "'";
			}
			return $this;
		}
	}

	public function add_pivot($column_key) {
		if (array_key_exists($column_key, $this->insert_value_clause)) {
			throw new Exception("Criteria dice: Usted est� declarando nuevamente la columna $column_key");
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
			throw new Exception("Criteria dice: El nombre de la tabla en la que se persistir�n los registros debe ser String");
		}
	}

	public function values_criteria(Criteria $criteria){
		$this->select_criteria = $criteria;
		return $this;
	}

	private function get_pivotes_fragment() {
		$pivotes = array_keys($this->insert_value_clause);

		if (empty($pivotes)) {
			$pivotes = $this->pivotes_temp;
		}

		return '(' . implode(', ', $pivotes) . ')';

	}

	private function get_values_fragment() {
		$pivotes = array_values($this->insert_value_clause);

		return '(' . implode(', ', $pivotes) . ')';
	}

	private function get_inserts_fragment() {
		if (!empty($this->insert_criteria)) {
			return implode(', ', $this->insert_criteria);
		} else {
			return $this->get_values_fragment();
		}
	}

	public function get_plain_query() {
		return $this->insert . $this->table_name . ' ' . $this->get_pivotes_fragment() . ' VALUES ' . $this->get_inserts_fragment();

	}

	public function get_plain_query_from_criteria(){
		throw new Exception("No implementado a�n.");
	}

  /**
   * Add a new row for sql insert statement
   * e.g. INSERT INTO table (key1, key2, ..., keyN)
   * 			VALUES (value1, value2, ..., valueN), (value1, value2, ..., valueN), ..., (value1, value2, ..., valueN);
   */
	public function add_insert() {
		$insert = $this->get_values_fragment();
		if (!empty($insert)) {
			$this->insert_criteria[] = $insert;
			$this->pivotes_temp = array_keys($this->insert_value_clause);
			$this->insert_value_clause = array();
		}
	}

	/**
	 * Set $insert_id value
	 * @param int
	 */
	public function set_last_insert_id($insert_id) {
		$this->insert_id = $insert_id;
	}

	/**
	 * Get $insert_id value
	 * @return int
	 */
	public function get_last_insert_id() {
		return $this->insert_id;
	}
}
