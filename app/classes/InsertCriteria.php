<?php

/**
* 	
*/
class InsertCriteria
{
	
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

	public function add_pivot_with_value($column_key, $value) {
		if (array_key_exists($column_key, $insert_value_clause)){
			throw new Exception("Criteria dice: Usted está sobrescribiendo un valor para la columna $column_key que ya está definido");
		} else {
			$this->insert_value_clause[$column_key] = $value;
		}
	}

	public function add_pivot($column_key) {
		if (array_key_exists($column_key, $insert_value_clause)) {
			throw new Exception("Criteria dice: Usted está declarando nuevamente la columna $column_key");
		} else {
			$this->insert_value_clause[$column_key] = '';
		}
	}

	public function set_into($table) {
		if (is_string($table)) {
			$this->table_name = $table;
		} else {
			throw new Exception("Criteria dice: El nombre de la tabla en la que se persistirán los registros debe ser String");
		}
	}

	public function values_criteria(Criteria $criteria){
		$this->select_criteria = $criteria;
	}

	private function get_pivotes_fragment() {
		$pivotes = array_keys($this->insert_value_clause);

		return '('.implode(',',$pivotes).')';

	}

	private function get_values_fragment() {
		return '('.implode(','.$this->insert_value_clause).')';
	}

	public function get_plain_query() {
		
		return $this->insert.$this->table_name.' '.$this->get_pivotes_fragment().' VALUES '.$this->get_values_fragment();
.
	}

	public function get_plain_query_from_criteria(){
		throw new Exception("No implementado aún.")
	}

}

?>