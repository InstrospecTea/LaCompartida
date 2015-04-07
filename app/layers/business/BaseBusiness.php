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
	 * deben ser notificados al usuario de la aplicación.
	 * @param $message string Si la variable es nula o no existe, entonces se retornará el arreglo de errores.
	 * Si la variable está definida, entonces su valor se agrega a los mensajes de error y luego
	 * se retorna el arreglo de errores.
	 * @return mixed
	 */
	function errors($message = null);

	/**
	 * Maneja los mensajes de información que arrojan los distintos servicios y negocios, los cuales
	 * deben ser notificados al usuario de la aplicación.
	 * @param $message string Si la variable es nula o no existe, entonces se retornará el arreglo de informaciones.
	 * Si la variable está definida, entonces su valor se agrega a los mensajes de información y luego
	 * se retorna el arreglo de informaciones.
	 * @return mixed
	 */
	function infos($message = null);

}