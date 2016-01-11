<?php

/**
 * Class Charge
 * Clase que representa un cobro en TheTimeBilling.
 */
class Charge extends LoggeableEntity {

	/**
	 * Obtiene el nombre de la propiedad que actúa como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'id_cobro';
	}

	public function getRelations() {
		return array(
			array(
				'class' => 'Agreement',
				'association_type' => 'has_one',
				'foreign_key' => 'id_contrato',
				'association_foreign_key' => null
			)
		);
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'cobro';
	}

	/**
	 * Obtiene el nombre de la entidad del medio persistente en donde se escribirá el log.
	 * @return string
	 */
	public function getLoggingTable() {
		return 'cobro_movimiento';
	}

	/**
	 * Obtiene un array con los nombres de las propiedades que serán consideradas al momento de escribir el log de la
	 * entidad.
	 * @return array
	 */
	public function getLoggeableProperties() {
		return array(
			'estado',
			'codigo_cliente',
			'id_moneda',
			'tipo_cambio_moneda',
			'fecha_ini',
			'fecha_fin',
			'forma_cobro',
			'monto'
		);
	}

	public function getInmutableLoggeableProperties() {
		return array(
			'id_cobro',
			'id_contrato'
		);
	}

	/**
	 * Obtiene los campos por defecto que debe llevar la entidad historial.
	 * @return array
	 */
	public function getDefaultHistoryProperties() {
		return array(
			true => array(
				'fecha' => 'NOW()'
			)
		);
	}

	public function getTableDefaults() {
		return array(
			'modalidad_calculo'
		);
	}

	protected function getFixedDefaults() {
		return array(
			'estado' => 'CREADO'
		);
	}
}
