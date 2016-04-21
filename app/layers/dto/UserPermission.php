<?php

class UserPermission extends Entity {

	/**
	 * Obtiene el nombre de la propiedad que act�a como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'codigo_permiso';
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'usuario_permiso';
	}

	/**
	 * Obtiene los campos por defecto que debe llevar la entidad.
	 * @return array
	 */
	public function getTableDefaults() {
		return array();
	}

	protected function getFixedDefaults() {
		return array();
	}
}
