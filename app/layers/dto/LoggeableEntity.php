<?php

/**
 * Class LoggeableEntity
 * Clase que debe ser extendida por toda aquella entidad que posea un log o historial de movimientos. Hereda de {@Entity}
 * y añade propiedades que son necesarias para persistir un log.
 */
abstract class LoggeableEntity extends Entity {

    /**
     * Obtiene el nombre de la tabla a la cual se escribirá el log.
     * @return string
     */
    abstract public function getLoggingTable();

	/**
	 * Obtiene un array con los nombres de las propiedades que serán consideradas al momento de escribir el log de la
	 * entidad.
	 * @return array
	 */
	abstract public function getLoggeableProperties();

	/**
	 * Obtiene un array con los nombres de las propiedades que pertenecen a la accion de escribir el log, pero que son
	 * utilizadas como referencia, es decir, no cambian por modificaciones de los usuarios.
	 * @return array
	 */
	abstract public function getInmutableLoggeableProperties();

}