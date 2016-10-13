<?php

class GenericDAO extends AbstractDAO implements BaseDAO {

	private $table_name;
	private $identity_field;

	public function __construct($table_name, Sesion $Sesion, $identity_field) {
		$this->table_name = $table_name;
		$this->identity_field = $identity_field;
		parent::__construct($Sesion);
	}

	public function getClass() {
		return 'Generic';
	}

	protected function newDtoInstance() {
		return new Generic($this->table_name, $this->identity_field);
	}
}
