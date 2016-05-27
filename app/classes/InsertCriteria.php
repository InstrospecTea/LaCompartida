<?php

/**
 *
 */
class InsertCriteria {

	/**
	 * Permite generar una conexión con el medio persistente.
	 * @var [type]
	 */
	private $sesion;

	/*
	  CRITERIA QUERY BUILDER PARAMS.
	 */
	private $action = 'INSERT INTO';
	private $table_name = '';
	private $select_criteria = null;
	private $is_update = false;
	private $restrictions = array();
	private $forced = false;

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
	 * @param type $forced Fuerza la ejecución de UPDATE si no tiene restricciones
	 * @throws Exception
	 */
	public function run($forced = false) {
		if ($this->sesion == null) {
			throw new Exception('Criteria dice: No hay una sesión definida para Criteria, no es posible ejecutar.');
		}
		$this->forced = $forced;
		$statement = $this->sesion->pdodbh->prepare($this->get_plain_query());
		$statement->execute();
	}

	public function update() {
		$this->action = 'UPDATE';
		$this->is_update = true;
		return $this;
	}

	public function addPivotWithValue($column_key, $value, $default = false) {
		if (array_key_exists($column_key, $this->insert_value_clause)) {
			throw new Exception("Criteria dice: Usted está sobrescribiendo un valor para la columna '$column_key' que ya está definido");
		} else {
			if (is_null($value) || $value == 'NULL') {
				$this->insert_value_clause[$column_key] = 'NULL';
			} else if ($default) {
				$this->insert_value_clause[$column_key] = $value;
			} else {
				$this->insert_value_clause[$column_key] = "'" . addslashes($value) . "'";
			}
			return $this;
		}
	}

	public function addPivot($column_key) {
		if (array_key_exists($column_key, $this->insert_value_clause)) {
			throw new Exception("Criteria dice: Usted está declarando nuevamente la columna '$column_key'");
		} else {
			$this->insert_value_clause[$column_key] = '';
			return $this;
		}
	}

	public function addRestriction(CriteriaRestriction $restriction) {
		$this->restrictions[] = $restriction;
		return $this;
	}

	public function setTable($table) {
		if (is_string($table)) {
			$this->table_name = $table;
			return $this;
		} else {
			throw new Exception('Criteria dice: El nombre de la tabla en la que se persistirán los registros debe ser String');
		}
	}

	public function valuesCriteria(Criteria $criteria) {
		$this->select_criteria = $criteria;
		return $this;
	}

	public function getPlainQuery() {
		if (empty($this->table_name)) {
			throw new Exception('Criteria dice: No ha especificado una tabla para la consulta.');
		}
		if ($this->is_update) {
			return $this->updatePlainQuery();
		}
		return $this->action . ' ' . $this->table_name . ' '
			. $this->getPivotesFragment() . ' VALUES ' . $this->getValuesFragment();
	}

	private function getPivotesFragment() {
		$pivotes = array_keys($this->insert_value_clause);

		return '(' . implode(',', $pivotes) . ')';
	}

	private function getValuesFragment() {
		$pivotes = array_values($this->insert_value_clause);

		return '(' . implode(',', $pivotes) . ')';
	}

	private function updatePlainQuery() {
		$plain_query = $this->action . ' ' . $this->table_name . ' SET '
			. $this->getSets() . ' ' . $this->getRestrictions();
		return $plain_query;

	}

	private function getSets() {
		$pivotes = $this->insert_value_clause;
		array_walk($pivotes, function(&$value, $key) {
			$value = "{$key} = $value";
		});
		return implode(', ', $pivotes);
	}

	private function getRestrictions() {
		if (empty($this->restrictions)) {
			if ($this->forced) {
				return '';
			}
			throw new Exception("Criteria dice: No se puede actualizar sin restricciones");
		}
		return 'WHERE ' . CriteriaRestriction::and_clause($this->restrictions);
	}

	public function get_plain_query_from_criteria() {
		throw new Exception("No implementado aún.");
	}

	/**
	 * @param Criteria $criteria
	 * @return type
	 * @deprecated use valuesCriteria()
	 */
	public function values_criteria(Criteria $criteria) {
		return $this->valuesCriteria($criteria);
	}
	/**
	 * @param type $column_key
	 * @param type $value
	 * @param type $default
	 * @return type
	 * @deprecated use addPivotWithValue()
	 */
	public function add_pivot_with_value($column_key, $value, $default = false) {
		return $this->addPivotWithValue($column_key, $value, $default);
	}
	/**
	 * @param type $column_key
	 * @deprecated use addPivot()
	 */
	public function add_pivot($column_key) {
	$this->addPivot($column_key);
	}
	/**
	 * @param type $table
	 * @return type
	 * @deprecated use setTable()
	 */
	public function set_into($table) {
		return $this->setTable($table);
	}
	/**
	 * @deprecated use getPlainQuery()
	 */
	public function get_plain_query() {
		return $this->getPlainQuery();
	}

	/**
	 * @param array $data
	 * @return $this
	 * @throws Exception
	 */
	public function addFromArray(array $data) {
		foreach ($data as $key => $value) {
			$this->addPivotWithValue($key, $value);
		}
		return $this;
	}

}
