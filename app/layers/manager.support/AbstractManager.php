<?php

class AbstractManager implements BaseManager {

	public $Session;
	public $sesion;
	private $loadedClass = array();

	public function __construct(Sesion $sesion) {
		$this->Session = $sesion;
		$this->sesion = $sesion;

		Configure::setSession($this->Session);
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
		$this->{$alias} = new $classname($this->Session);
		$this->loadedClass[] = $alias;
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
		$this->{$alias} = new $classname($this->Session);
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
		$this->{$alias} = new $classname($this->Session);
		$this->loadedClass[] = $alias;
	}

	/**
	 * Carga una clase Model al vuelo
	 * @param string $classname
	 * @param string $alias
	 */
	protected function loadModel($classname, $alias = null, $returned = false) {
		if ($returned) {
			return new $classname($this->Session);
		}
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($classname, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->Session);
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
		$this->{$alias} = new $classname($this->Session);
		$this->loadedClass[] = $alias;
	}
}
