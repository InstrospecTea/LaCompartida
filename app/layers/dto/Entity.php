<?php

/**
 * Class Entity
 * Clase abstracta que define todas aquellas propiedades y métodos comunes a toda entidad del sistema.
 *
 */
abstract class Entity {

	public $fields = array();
    /**
     * Obtiene el nombre de la propiedad que actúa como identidad de la instancia del objeto que hereda a esta clase.
     * @return string
     */
    abstract public function getIdentity();

    /**
     * Obtiene el nombre del objeto del medio persistente que almacena las distintas instancias del objeto que hereda
     * a esta clase.
     * @return string
     */
    abstract public function getPersistenceTarget();

    /**
     * Obtiene el valor de una propiedad del objeto que es instancia de la clase que hereda este abstracto.
     * @param string $property Nombre de la propiedad de la cual se quiere obtener su valor.
     * @return mixin Valor de la propiedad.
     */
    public function get($property) {
		$reflected = new ReflectionClass(get_class($this));
	    $fields = $reflected->getProperty('fields')->getValue($this);
		return (empty($fields[$property])? null : $fields[$property]);
    }

    /**
     * Establece un valor a una propiedad del objeto que es instancia de la clase que hereda este abstracto.
     * @param string $property Nombre de la propiedad que se quiere establecer.
     * @param mixin $value Valor que se define para la propiedad.
     * @throws Exception Cuando hay un problema al acceder a la propiedad.
     */
    public function set($property, $value) {
		$reflected = new ReflectionClass(get_class($this));
		try{
			$fields = $reflected->getProperty('fields')->getValue($this);
			$fields[$property] = $value;
		    $reflected->getProperty('fields')->setValue($this, $fields);
		} catch (ReflectionException $ex) {
		    throw new Exception($ex->getMessage().' at '.$ex->getLine());
		}
    }

	/**
	 * Completa las propiedades de una instancia de un objeto cuya clase herede a este, con los valores definidos en un
	 * array asociativo.
	 * @param array $properties Propiedades de un objeto.
	 */
	public function fillFromArray(array $properties) {
		foreach ($properties as $propertyName => $propertyValue) {
			$this->set($propertyName, $propertyValue);
		}
	}



}