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
        return 'cobro_historial';
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
			'id_contrato',
			'id_moneda',
			'tipo_cambio_moneda',
			'fecha_creacion',
			'fecha_en_revision',
			'fecha_modificacion',
			'fecha_emision',
			'fecha_facturacion',
			'fecha_enviado_cliente',
			'fecha_pago_parcial'
		);
	}

	public function getInmutableLoggeableProperties() {
		return array(
			'id_cobro'
		);
	}

}