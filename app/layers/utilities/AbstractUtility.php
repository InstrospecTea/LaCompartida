<?php

abstract class AbstractUtility {

	/**
	 * Redefinici�n de m�todo CALL para que las propiedades de los objetos tengan un getter autom�tico
	 * cuando se llama la propiedad deseada como un m�todo, incorporando un acceso unificado a las propiedades
	 * p�blicas y privadas mediante el API de Reflection de PHP.
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($name, $arguments) {
		if (method_exists($this, $name)) {
			return call_user_func($name, $arguments);
		} else {
			if (property_exists($this, $name)) {
				$reflection = new ReflectionObject($this);
				$property = $reflection->getProperty($name);
				$property->setAccessible(true);
				if (empty($arguments)) {
					return $property->getValue($this);
				} else {
					$property->setValue($this, $arguments[0]);
				}
			} else {
				throw new UtilityException("No existe referencia a $name");
			}
		}
	}

}