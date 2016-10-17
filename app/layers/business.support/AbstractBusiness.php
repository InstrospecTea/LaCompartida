<?php

class AbstractBusiness implements BaseBusiness {

	/**
	 * @deprecated por convención
	 * @var sesion
	 */
	public $sesion;
	/**
	 * @deprecated por convención
	 * @var Session
	 */
	public $Session;
	public $Sesion;
	public $errors = array();
	public $infos = array();
	protected $transactions = 0;

	use LoadServiceTrait;

	public function __construct(Sesion $Sesion) {
		$this->sesion = $Sesion;
		$this->Session = $Sesion;
		$this->Sesion = $Sesion;

		Configure::setSession($this->Sesion);
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
		$this->{$alias} = new $classname($this->Sesion);
		$this->loadedClass[] = $alias;
	}

	/**
	 * Carga una clase Model al vuelo
	 * @param string $classname
	 * @param string $alias
	 * @param bool $returned retorna la instancia
	 */
	protected function loadModel($classname, $alias = null, $returned = false) {
		if ($returned) {
			return new $classname($this->Sesion);
		}
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($classname, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Sesion);
		$this->loadedClass[] = $classname;
	}

	/**
	 * Carga un Reporte al vuelo
	 * @param string $name
	 * @param string $alias
	 * @return type
	 */
	protected function loadReport($name, $alias = null) {
		$classname = "{$name}Report";
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Sesion);
		$this->loadedClass[] = $alias;
	}

	/**
	 * Carga un Manager al vuelo
	 * @param string $name
	 * @param string $alias
	 * @return type
	 */
	protected function loadManager($name, $alias = null) {
		$classname = "{$name}Manager";
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Sesion);
		$this->loadedClass[] = $alias;
	}

	protected function begin() {
		if ($this->transactions === 0) {
			$this->Sesion->pdodbh->beginTransaction();
		}
		++$this->transactions;
	}

	protected function commit() {
		--$this->transactions;
		if ($this->transactions === 0) {
			$this->Sesion->pdodbh->commit();
		}
	}

	protected function rollback() {
		--$this->transactions;
		if ($this->transactions === 0) {
			$this->Sesion->pdodbh->rollback();
		}
	}


}
