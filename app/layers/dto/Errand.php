<?php

/**
 * Class Errand
 * Clase que representa un tr�mite en TheTimeBilling.
 */
class Errand extends LoggeableEntity {
	/**
	 * Obtiene el nombre de la propiedad que act�a como identidad de la instancia del objeto que hereda a esta clase.
	 * @return string
	 */
	public function getIdentity() {
		return 'id_tramite';
	}

	/**
	 * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
	 * a esta clase.
	 * @return string
	 */
	public function getPersistenceTarget() {
		return 'tramite';
	}

	/**
	 * Obtiene el nombre de la entidad del medio persistente en donde se escribir� el log.
	 * @return string
	 */
	public function getLoggingTable() {
		return 'tramite_historial';
	}

	/**
	 * Obtiene un array con los nombres de las propiedades que ser�n consideradas al momento de escribir el log de la
	 * entidad.
	 * @return array
	 */
	public function getLoggeableProperties(){
		return array(
			'fecha',
			'descripcion',
			'codigo_asunto',
			'codigo_actividad',
			'codigo_tarea',
			'id_tramite_tipo',
			'solicitante',
			'id_moneda_tramite',
			'tarifa_tramite',
			'id_moneda_tramite_individual',
			'tarifa_tramite_individual',
			'cobrable',
			'trabajo_si_no',
			'duracion'
		);
	}

	public function getInmutableLoggeableProperties() {
		return array(
			'id_tramite'
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
