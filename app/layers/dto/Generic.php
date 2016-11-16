<?php

class Generic extends Entity {

	private $table_name;
	private $identity_field;

	public function __construct($table_name) {
		$this->table_name = $table_name;
		$this->identity_field = "id_{$table_name}";
	}
	/**
	 * Obtiene el nombre de la propiedad que act�a como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return $this->identity_field;
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return $this->table_name;
	}

	public function getTableDefaults() {
		return [];
	}

	protected function getFixedDefaults() {
		return [];
	}

}
