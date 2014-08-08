<?php

/**
 * Class AbstractDAO
 * Clase que define un DAO abstracto que es heredada por toda aquella clase que implemente la capa de persistencia
 * para un objeto de la aplicación.
 * TODO:
 *  - Persistencia para save or update.
 */
abstract class AbstractDAO extends Objeto implements BaseDAO{

    var $sesion;

    public function __construct(Sesion $sesion) {
        $this->sesion = $sesion;
    }

    /**
     * Método que realiza la escritura de un log respecto a la entidad.
     * Si la propiedad tiene anotado '@log' entonces es una propiedad que debe ser logeada.
     * Si la pripiedad tiene anotado '@inmutable' entonces es una propiedad que no varía en el log, por ende
     * no tiene una columna [Nombre columna]_modificado.
     * @param $action String que se guardará en el campo accion de la tabla de logs.
     * @param $object Object hereda de LoggeableEntity y por ende tiene definido el método getLoggingTable().
     * @param $legacy Object legado que es la versión anterior del que se guardará ahora.
     * @param int $app Identificador de la aplicación que está realizando la operación.
     * @throws Exception Cuando la inserción falla.
     */
    private function writeLogFromAnotations($action, $object, $legacy, $app = 1) {

	    if ($action == 'MODIFICAR' && !$this->isReallyLoggingNecessary($object, $legacy)) {
			return;
	    }
	    $insertCriteria = new InsertCriteria($this->sesion);
	    $insertCriteria->set_into($object->getLoggingTable());
	    $insertCriteria->add_pivot_with_value('accion', $action);
	    $insertCriteria->add_pivot_with_value('app_id', $app);
	    $insertCriteria->add_pivot_with_value('id_usuario', $this->sesion->usuario->fields['id_usuario']);
	    $reflected = new ReflectionClass($this->getClass());
	    $properties = $reflected->getProperties();
	    foreach($properties as $property) {
		    $annotations = $this->getAnnotations($property);
		    if(is_numeric(array_search('@log', $annotations))) {
			    if (is_numeric(array_search('@inmutable', $annotations))) {
				    $insertCriteria->add_pivot_with_value(
					    $property->getName(),
					    $object->get($property->getName())
				    );
			    } else {
				    $insertCriteria->add_pivot_with_value(
					    $property->getName(),
					    $legacy->get($property->getName())
				    );
				    $insertCriteria->add_pivot_with_value(
					    $property->getName().'_modificado',
					    $object->get($property->getName())
				    );
			    }
		    }
	    }
	    try {
		    $insertCriteria->run();
	    } catch (PDOException $ex) {
		    throw new Exception('No se pudo guardar el log. Ex: ' . $ex->getTraceAsString());
	    }
    }

	/**
	 * Método que realiza la escritura de un log respecto a una entidad.
	 * @param $action String que se guardará en el campo accion de la tabla de logs.
	 * @param $object Object hereda de LoggeableEntity y por ende tiene definido el método getLoggingTable().
	 * @param $legacy Object legado que es la versión anterior del que se guardará ahora.
	 * @param int $app Identificador de la aplicación que está realizando la operación.
	 * @throws Exception Cuando la inserción falla.
	 */
	private function writeLogFromArray($action, $object, $legacy, $app = 1) {
		if ($action == 'MODIFICAR' && !$this->isReallyLoggingNecessary($object, $legacy)) {
			return;
		}
		$insertCriteria = new InsertCriteria($this->sesion);
		$insertCriteria->set_into($object->getLoggingTable());
		$insertCriteria->add_pivot_with_value('accion', $action);
		$insertCriteria->add_pivot_with_value('app_id', $app);
		$insertCriteria->add_pivot_with_value('id_usuario', $this->sesion->usuario->fields['id_usuario']);
		$inmutableProperties = $object->getInmutableLoggeableProperties();
		foreach ($inmutableProperties as $inmutableProperty) {
			$insertCriteria->add_pivot_with_value($inmutableProperty, $object->get($inmutableProperty));
		}
		$properties = $object->getLoggeableProperties();
		foreach($properties as $property) {
			$insertCriteria->add_pivot_with_value(
				$property,
				$legacy->get($property)
			);
			$insertCriteria->add_pivot_with_value(
				$property.'_modificado',
				$object->get($property)
			);
		}
		try {
			$insertCriteria->run();
		} catch (PDOException $ex) {
			print_r($ex->getMessage());
			throw new Exception('No se pudo guardar el log. Ex: ' . $ex->getTraceAsString());
		}
	}

	/**
	 * Verifica si realmente es necesario escribir el log entre dos instancias de objetos de la misma clase, que
	 * heredan de {@link LoggeableEntity}. La lógica de verificación implica evaluar si es que existe una diferencia en
	 * los campos loggeables de una clase entre el objeto nuevo y el legado. Si no hay diferencias entonces el log no debería
	 * escribirse.
	 * @param $newObject
	 * @param $legacyObject
	 * @return bool
	 */
	private function isReallyLoggingNecessary($newObject, $legacyObject) {
		$properties = $newObject->getLoggeableProperties();
		foreach ($properties as $mutableProperty) {
			if ($newObject->get($mutableProperty) != $legacyObject->get($mutableProperty)) {
				return true;
			}
		}
		return false;
	}

    public function saveOrUpdate($object) {
		$this->checkClass($object, $this->getClass());
	    $reflected = new ReflectionClass($this->getClass());
		try{
		    $id = $object->get($object->getIdentity());
			//Si el objeto tiene definido un id, entonces hay que actualizar. Si no tiene definido un id, entonces hay
			//que crear un nuevo registro.
		    if(empty($id)) {
			    $object = $this->save($object);
		        if (is_subclass_of($object, 'LoggeableEntity')){
		            $this->writeLogFromArray('CREAR', $object, $reflected->newInstance());
		        }
		    } else {
			    $legacy = $this->get($object->get($object->getIdentity()));
			    $object = $this->update($object);
		        if (is_subclass_of($object, 'LoggeableEntity')){
		            $this->writeLogFromArray('MODIFICAR', $object, $legacy);
		        }
		    }
			return $object;
		} catch(PDOException $e){
		    throw new Exception('No se ha podido persistir el objeto de tipo '.$this->getClass().'.');
		}
    }

	/**
	 * @param Entity $object
	 * @return Entity
	 * @throws Exception
	 */
	private function save(Entity $object) {
		$this->tabla = $object->getPersistenceTarget();
		$this->campo_id = $object->getIdentity();
		$this->sesion = $this->sesion;
		$this->fields = $object->fields;
		$this->changes = $object->changes;
		$this->log_update = true;
		$this->guardar_fecha = true;
		if ($this->Write()) {
			$object->set($object->getIdentity(), $this->fields[$object->getIdentity()]);
			return $object;
		} else {
			throw new Exception('No se ha podido persistir la entidad.');
		}
	}

	/**
	 * @param Entity $object
	 * @return Entity
	 */
	private function update(Entity $object) {
		return $this->save($object);
	}

    public function get($id) {
	    $criteria = new Criteria($this->sesion);
	    $reflected = new ReflectionClass($this->getClass());
	    $instance = $reflected->newInstance();
	    $criteria->add_select('*');
	    $criteria->add_from($instance->getPersistenceTarget());
	    $criteria->add_restriction(CriteriaRestriction::equals($instance->getIdentity(), $id));
		$resultArray = $criteria->run();
	    $resultArray = $resultArray[0];
		return $this->encapsulate($resultArray, $instance);
    }

	public function findAll() {
		$criteria = new Criteria($this->sesion);
		$reflected = new ReflectionClass($this->getClass());
		$criteria->add_select('*');
		$criteria->add_from($reflected->getMethod('getPersistenceTarget')->invoke($reflected->newInstance()));
		$output = array();
		$results = $criteria->run();
		foreach ($results as $result) {
			$instance = $reflected->newInstance();
			$instance = $this->encapsulate($result, $instance);
			$output[] = $instance;
		}
		return $output;
    }

    public function delete($object) {
	    $reflected = new ReflectionClass($this->getClass());
        if (is_subclass_of($object, 'LoggeableEntity')){
	        $newInstance = $reflected->newInstance();
	        $newInstance->set($object->getIdentity(), $object->get($object->getIdentity()));
            $this->writeLogFromArray('ELIMINAR', $newInstance, $object);
        }
    }

    /**
     * Comprueba si un objeto es parte de la jerarquía de clases definida en la capa.
     * @param $object Objeto que se comprobará.
     * @param $className string de clases a la que debe pertenecer.
     * @throws Exception Cuando no pertenece a la jerarquía de clases correspondiente.
     */
    protected function checkClass($object, $className) {
        if (!is_a($object, $className)) {
            throw new Exception('Dao Exception: El objeto entregado no pertenece ni hereda a la clase definida en DAO.');
        }
    }

	/**
	 * Realiza la encapsulación de un resultado de una query a la base de datos en una instancia de un objeto.
	 * @param $arrayResult
	 * @param $instance
	 */
	private function encapsulate($arrayResult, $instance) {
		foreach ($arrayResult as $property => $value) {
			$instance->set($property, $value);
		}
		return $instance;
	}

	/**
	 * Obtiene las anotaciones definidas para una propiedad de la clase, obtenidas mediante reflection.
	 * @param ReflectionProperty $property
	 * @return array Array que contiene las anotaciones.
	 */
	private function getAnnotations(ReflectionProperty $property) {
		$c = $property->getDocComment();
		$c = str_replace('/*', '', $c);
		$c = str_replace('*/', '', $c);
		$c = str_replace('*', '', $c);
		$c = str_replace(' ','',$c);
		preg_match_all('/@\w+/', $c, $tags);
		return $tags[0];
	}



}