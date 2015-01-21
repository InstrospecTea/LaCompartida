<?php

interface BaseBusiness {

	/**
	 * Verifica la existencia de una propiedad no nula en el arreglo de propiedades.
	 * @param $properties
	 * @param $property
	 * @return bool
	 */
	function checkPropertyExistence($properties, $property);

	/**
	 * Maneja los mensajes de error que arrojan los distintos servicios y negocios, los cuales
	 * deben ser notificados al usuario de la aplicaci�n.
	 * @param $message string Si la variable es nula o no existe, entonces se retornar� el arreglo de errores.
	 * Si la variable est� definida, entonces su valor se agrega a los mensajes de error y luego
	 * se retorna el arreglo de errores.
	 * @return mixed
	 */
	function errors($message = null);

	/**
	 * Maneja los mensajes de informaci�n que arrojan los distintos servicios y negocios, los cuales
	 * deben ser notificados al usuario de la aplicaci�n.
	 * @param $message string Si la variable es nula o no existe, entonces se retornar� el arreglo de informaciones.
	 * Si la variable est� definida, entonces su valor se agrega a los mensajes de informaci�n y luego
	 * se retorna el arreglo de informaciones.
	 * @return mixed
	 */
	function infos($message = null);

}