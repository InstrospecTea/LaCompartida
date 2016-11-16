<?php

trait LoadManagerTrait {

	protected $loadedClass = array();

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

}
