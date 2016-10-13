<?php

class GenericService extends AbstractService implements BaseService {

	private $table_name;
	private $identity_field;

	/**
	 * Devuelve una servicio generico en base al nombre de la tabla
	 * @param string $table_name Nombre de la tabla
	 * @param Sesion $Sesion
	 * @param string $identity_field nombre del campo id
	 */
	public function __construct($table_name, Sesion $Sesion, $identity_field = null) {
		$this->sesion = $Sesion;
		$this->table_name = $table_name;
		if (is_null($identity_field)) {
			$identity_field = "id_{$table_name}";
		}
		$this->identity_field = $identity_field;
	}

	public function getDaoLayer() {
		throw new Exception(__('Invalid method in GenericService'), 1);
		;
	}

	public function getClass() {
		return 'Generic';
	}


	protected function newDao() {
		return new GenericDAO($this->table_name, $this->sesion, $this->identity_field);
	}

}
