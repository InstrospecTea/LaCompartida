<?php

/**
 * Class Entity
 * Clase abstracta que define todas aquellas propiedades y métodos comunes a toda entidad del sistema.
 * TODO:
 *  - Todo cambio realizado mediante el método set, debe generar un array de campos cambiados.
 */
abstract class Entity {

	public $fields = array();
	public $changes = array();


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
	 * Obtiene los campos por defecto que debe llevar la entidad.
	 * @return array
	 */
	abstract protected function getDefaults(); 


    /**
     * Obtiene el valor de una propiedad del objeto que es instancia de la clase que hereda este abstracto.
     * @param string $property Nombre de la propiedad de la cual se quiere obtener su valor.
     * @return mixed Valor de la propiedad.
     */
    public function get($property) {
		$reflected = new ReflectionClass(get_class($this));
	    $fields = $reflected->getProperty('fields')->getValue($this);
	    return (!array_key_exists($property, $this->fields)? NULL : $fields[$property]);
    }

    /**
     * Establece un valor a una propiedad del objeto que es instancia de la clase que hereda este abstracto.
     * @param string $property Nombre de la propiedad que se quiere establecer.
     * @param mixed $value Valor que se define para la propiedad.
     * @param boolean $changes Boolean que determina si la propiedad se añade al array changes o no.
     * @throws Exception Cuando hay un problema al acceder a la propiedad.
     */
    public function set($property, $value, $changes = true) {
	    $reflected = new ReflectionClass(get_class($this));
	    try {
		    $fields = $reflected->getProperty('fields')->getValue($this);
		    $fields[$property] = $value;
		    $reflected->getProperty('fields')->setValue($this, $fields);
		    if ($changes) {
			    $this->changes[$property] = $value;
		    }
	    } catch (ReflectionException $ex) {
		    throw new Exception($ex->getMessage() . ' at ' . $ex->getLine());
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

	/**
	 * Completa el array de los cambios que han ocurrido en las propiedades del objeto. Será deprecado para cuando todos los campos
	 * se asignen mediante el método set.
	 * @param array $changed
	 */
	public function fillChangedFields(array $changed) {
		$this->changes = $changed;
	}

	/**
	 * Completa el objeto con los valores por defecto definidos para cada entidad.
	 */
	public function fillDefaults() {
		$defaults = $this->getDefaults();
		foreach                     ($defaults as $default => $value) {
			if (is_null($this->get($default))) {
				$this->set($default, $value);
			}
		}
	}


	/**
	 * Verifica si el objeto está cargado mediante la obtención del identificador primario.
	 * @return boolean
	 */
	public function isLoaded() {
		if ($this->get($this->getIdentity())) {
			return true;
		} else {
			return false;
		}
	}





}


