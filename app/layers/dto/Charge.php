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
	public function getLoggeableProperties(){
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
			'id_cobro', 'id_contrato'
		);
	}

	protected function getDefaults(Sesion $Session) {
		$fields = array('modalidad_calculo');
		return array_merge(
			$this->getDefaultsFromTable($Session, $fields),
			array('estado' => 'CREADO')
		);
	}

}