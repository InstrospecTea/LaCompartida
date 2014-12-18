<?php

abstract class AbstractUtility {

	/**
	 * Redefinición de método CALL para que las propiedades de los objetos tengan un getter automático
	 * cuando se llama la propiedad deseada como un método, incorporando un acceso unificado a las propiedades
	 * públicas y privadas mediante el API de Reflection de PHP.
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