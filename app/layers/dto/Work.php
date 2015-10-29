<?php

/**
 * Class Work
 * Clase que representa un trabajo en TheTimeBilling.
 */
class Work extends LoggeableEntity {

	/**
	 * Obtiene el nombre de la propiedad que actúa como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'id_trabajo';
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'trabajo';
	}

	/**
	 * Obtiene el nombre de la entidad del medio persistente en donde se escribirá el log.
	 * @return string
	 */
	public function getLoggingTable() {
		return 'trabajo_historial';
	}

	/**
	 * Obtiene un array con los nombres de las propiedades que serán consideradas al momento de escribir el log de la
	 * entidad.
	 * @return array
	 */
	public function getLoggeableProperties() {
		return array(
			array('fecha', 'fecha_trabajo'),
			'descripcion',
			'duracion',
			'duracion_cobrada',
			array('id_usuario', 'id_usuario_trabajador'),
			'tarifa_hh',
			'codigo_asunto',
			'cobrable'
		);
	}

	public function getInmutableLoggeableProperties() {
		return array(
			'id_trabajo'
		);
	}

	/**
	 * Obtiene los campos por defecto que debe llevar la entidad historial.
	 * @return array
	 */
	public function getDefaultHistoryProperties() {
		return array(
			true => array(
				'fecha_accion' => 'NOW()'
			)
		);
	}

	public function getTableDefaults() {
		return array();
	}

	protected function getFixedDefaults() {
		return array();
	}
}
