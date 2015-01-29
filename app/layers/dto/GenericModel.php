<?php

class GenericModel extends Entity{

	/**
	 * Obtiene el nombre de la propiedad que act�a como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 * @throws Exception
	 */
	public function getIdentity() {
		throw new Exception('Simple generic entity can not have identity.');
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 * @throws Exception
	 */
	public final function getPersistenceTarget() {
		throw new Exception('Generic entity can not have a persistence target!');
	}

	public function getTableDefaults() {
		throw new Exception("It's not correct the fact of a GenericModel entity having defaults values.");
	}

	protected function getFixedDefaults() {
		throw new Exception("It's not correct the fact of a GenericModel entity having defaults values.");
	}

}