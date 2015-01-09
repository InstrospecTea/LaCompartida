<?php

class ChargeCurrency extends Entity {

	/**
	 * Obtiene el nombre de la propiedad que actúa como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'id_cobro';
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'cobro_moneda';
	}

	/**
	 * Obtiene los campos por defecto que debe llevar la entidad.
	 * @return array
	 */
	protected function getDefaults() {
		return array();
	}

}