<?php

/**
 * Class Charge
 * Clase que representa un cobro en TheTimeBilling.
 */
class RtfCharge extends Entity {

	/**
	 * Obtiene el nombre de la propiedad que actúa como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'id_formato';
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'cobro_rtf';
	}

	public function getTableDefaults() {
		return [];
	}

	protected function getFixedDefaults() {
		return [];
	}
}
