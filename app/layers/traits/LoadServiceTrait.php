<?php

use TTB\Configurations\TableTranslation as Table;

trait LoadServiceTrait {

	protected $loadedClass = array();

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
		if (class_exists($classname)) {
			$this->{$alias} = new $classname($this->Sesion);
		} else {
			$this->{$alias} = $this->newGeneric($name);
		}
		$this->loadedClass[] = $alias;
	}

	private function newGeneric($name) {
		return new GenericService($this->Sesion, $name);
	}
}
