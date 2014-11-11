<?php

class AbstractBusiness implements BaseBusiness {

	var $sesion;
	var $errors = array();
	var $infos = array();
	private $loadedClass = array();

	public function __construct(Sesion $sesion) {
		$this->sesion = $sesion;
	}

	/**
	 * Verifica la existencia de una propiedad no nula en el arreglo de propiedades.
	 * @param $properties
	 * @param $property
	 * @return bool
	 */
	function checkPropertyExistence($properties, $property) {
		if (array_key_exists($property, $properties)) {
			if (is_null($properties[$property])) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Maneja los mensajes de error que arrojan los distintos servicios y negocios, los cuales
	 * deben ser notificados al usuario de la aplicación.
	 * @param $message string Si la variable es nula o no existe, entonces se retornará el arreglo de errores.
	 * Si la variable está definida, entonces su valor se agrega a los mensajes de error y luego
	 * se retorna el arreglo de errores.
	 * @return mixed
	 */
	function errors($message = null) {
		if (is_null($message)) {
			return $this->errors;
		} else {
			$this->errors[] = $message;
			return $this->errors;
		}
	}

	/**
	 * Maneja los mensajes de información que arrojan los distintos servicios y negocios, los cuales
	 * deben ser notificados al usuario de la aplicación.
	 * @param $message string Si la variable es nula o no existe, entonces se retornará el arreglo de informaciones.
	 * Si la variable está definida, entonces su valor se agrega a los mensajes de información y luego
	 * se retorna el arreglo de informaciones.
	 * @return mixed
	 */
	function infos($message = null) {
		if (is_null($message)) {
			return $this->infos;
		} else {
			$this->infos[] = $message;
			return $this->errors;
		}
	}

	/**
	 * Carga un Servicio al vuelo
	 * @param string $name
	 * @param string $alias
	 * @return type
	 */
	protected function loadService($name, $alias = null) {
		$classname = "{$name}Service";
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->sesion);
		$this->loadedClass[] = $alias;
	}

	/**
	 * Carga un Negocio al vuelo
	 * @param string $name
	 * @param string $alias
	 * @return type
	 */
	protected function loadBusiness($name, $alias = null) {
		$classname = "{$name}Business";
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->sesion);
		$this->loadedClass[] = $alias;
	}

}