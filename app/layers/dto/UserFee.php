<?php

class UserFee extends Entity {

	/**
	 * Obtiene el nombre de la propiedad que actúa como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'id_usuario_tarifa';
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'usuario_tarifa';
	}

	/**
	 * Obtiene los campos por defecto que debe llevar la entidad.
	 * @return array
	 */
	public function getTableDefaults() {
		return array();
	}

	/**
	 * Retorna una entidad con tarifa 0
	 * @return GenericModel
	 */
	public function emptyResult() {
		$entity = new GenericModel();
		$entity->set('id_usuario_tarifa', 0, false);
		$entity->set('id_usuario', 0, false);
		$entity->set('id_moneda', 0, false);
		$entity->set('tarifa', 0, false);
		$entity->set('id_tarifa', 0, 0);
		return $entity;
	}

	protected function getFixedDefaults() {
		return array();
	}
}
