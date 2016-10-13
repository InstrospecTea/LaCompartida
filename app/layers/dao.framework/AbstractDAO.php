<?php

/**
 * Class AbstractDAO
 * Clase que define un DAO abstracto que es heredada por toda aquella clase que implemente la capa de persistencia
 * para un objeto de la aplicaci�n.
 * TODO:
 *  - Persistencia para save or update.
 */
abstract class AbstractDAO extends Objeto implements BaseDAO {

	/**
	 * @deprecated $sesion
	 */
	public $sesion;
	public $Sesion;

	/**
	 * Indica a la Clase que al llamar a Write(), crear� un registro en la tabla 'log_db'
	 * @var boolean
	 */
	public $log_update = false;

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->sesion = &$this->Sesion;
	}

	/**
	 * M�todo que realiza la escritura de un log respecto a la entidad.
	 * Si la propiedad tiene anotado '@log' entonces es una propiedad que debe ser logeada.
	 * Si la pripiedad tiene anotado '@inmutable' entonces es una propiedad que no var�a en el log, por ende
	 * no tiene una columna [Nombre columna]_modificado.
	 * @param $action String que se guardar� en el campo accion de la tabla de logs.
	 * @param $object Object hereda de LoggeableEntity y por ende tiene definido el m�todo getLoggingTable().
	 * @param $legacy Object legado que es la versi�n anterior del que se guardar� ahora.
	 * @throws Exception Cuando la inserci�n falla.
	 */
	private function writeLogFromAnotations($action, $object, $legacy) {
		if ($action == 'MODIFICAR' && !$this->isReallyLoggingNecessary($object, $legacy)) {
			return;
		}
		$app_id = empty($_SESSION['app_id']) ? 1 : $_SESSION['app_id'];
		$insertCriteria = new InsertCriteria($this->sesion);
		$insertCriteria->set_into($object->getLoggingTable());
		$insertCriteria->add_pivot_with_value('accion', $action);
		$insertCriteria->add_pivot_with_value('app_id', $app_id);
		if (!is_null($this->sesion->usuario->fields['id_usuario'])) {
			$insertCriteria->add_pivot_with_value('id_usuario', $this->sesion->usuario->fields['id_usuario']);
		}

		$reflected = new ReflectionClass($this->getClass());
		$properties = $reflected->getProperties();
		foreach ($properties as $property) {
			$annotations = $this->getAnnotations($property);
			if (is_numeric(array_search('@log', $annotations))) {
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
						$property->getName() . '_modificado',
						$object->get($property->getName())
					);
				}
			}
		}
		try {
			$insertCriteria->run();
		} catch (PDOException $ex) {
			throw new CouldNotWriteLogException('No se pudo guardar el log. Msg: ' . $ex->getMessage());
		}
	}

	/**
	 * M�todo que realiza la escritura de un log respecto a una entidad.
	 * @param $action String que se guardar� en el campo accion de la tabla de logs.
	 * @param $object Entity hereda de LoggeableEntity y por ende tiene definido el m�todo getLoggingTable().
	 * @param $legacy Entity legado que es la versi�n anterior del que se guardar� ahora.
	 * @throws Exception Cuando la inserci�n falla.
	 */
	private function writeLogFromArray($action, $object, $legacy) {
		if ($action == 'MODIFICAR' && !$this->isReallyLoggingNecessary($object, $legacy)) {
			return;
		}
		$app_id = empty($_SESSION['app_id']) ? 1 : $_SESSION['app_id'];
		$insertCriteria = new InsertCriteria($this->sesion);
		$insertCriteria->set_into($object->getLoggingTable());
		$insertCriteria->add_pivot_with_value('accion', $action);
		$insertCriteria->add_pivot_with_value('app_id', $app_id);
		$insertCriteria->add_pivot_with_value('id_usuario', $this->sesion->usuario->fields['id_usuario']);
		$inmutableProperties = $object->getInmutableLoggeableProperties();
		foreach ($inmutableProperties as $inmutableProperty) {
			$insertCriteria->add_pivot_with_value($inmutableProperty, $object->get($inmutableProperty));
		}
		$defaultHistoryProperties = $object->getDefaultHistoryProperties();
		foreach ($defaultHistoryProperties as $default => $pivots) {
			foreach ($pivots as $key => $value) {
				$insertCriteria->add_pivot_with_value($key, $value, $default);
			}
		}
		$properties = $object->getLoggeableProperties();
		foreach ($properties as $property) {
			$alias = $property;
			if (is_array($property)) {
				$alias = $property[1];
				$property = $property[0];
			}
			$legacyProperty = $legacy->get($property);
			$newProperty = $object->get($property);
			$insertCriteria->add_pivot_with_value(
				$alias,
				$legacyProperty
			);
			$insertCriteria->add_pivot_with_value(
				$alias . '_modificado',
				$newProperty
			);
		}
		try {
			$insertCriteria->run();
		} catch (PDOException $ex) {
			throw new CouldNotWriteLogException('No se pudo guardar el log.' . $ex->getMessage());
		}
	}

	/**
	 * Verifica si realmente es necesario escribir el log entre dos instancias de objetos de la misma clase, que
	 * heredan de {@link LoggeableEntity}. La l�gica de verificaci�n implica evaluar si es que existe una diferencia en
	 * los campos loggeables de una clase entre el objeto nuevo y el legado. Si no hay diferencias entonces el log no deber�a
	 * escribirse.
	 * @param $newObject
	 * @param $legacyObject
	 * @return bool
	 */
	private function isReallyLoggingNecessary($newObject, $legacyObject) {
		$properties = $newObject->getLoggeableProperties();
		foreach ($properties as $mutableProperty) {
			if (is_array($mutableProperty)) {
				$mutableProperty = $mutableProperty[0];
			}
			if ($newObject->get($mutableProperty) != $legacyObject->get($mutableProperty)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Persiste un objeto. Crea un nuevo registro si el objeto no lleva id. Si lleva id, se actualiza el objeto existente.
	 * @param Entity $object
	 * @param boolean $writeLog Define si se escribe o no el historial de movimientos.
	 * @throws Exception
	 */
	public function saveOrUpdate($object, $writeLog = true) {
		//Llena los defaults de cada entidad.
		$this->checkClass($object, $this->getClass());
		$id = $object->get($object->getIdentity());
		//Si el objeto tiene definido un id, entonces hay que actualizar. Si no tiene definido un id, entonces hay
		//que crear un nuevo registro.
		if (empty($id)) {
			$object = $this->fillDefaults($object);
			$object = $this->save($object);
			if (is_subclass_of($object, 'LoggeableEntity')) {
				$this->writeLogFromArray('CREAR', $object, $this->newDtoInstance());
			}
		} else {
			$legacy = $this->get($object->get($object->getIdentity()));
			$object = $this->update($object);
			if ($writeLog && is_subclass_of($object, 'LoggeableEntity')) {
				$object = $this->merge($legacy, $object);
				$this->writeLogFromArray('MODIFICAR', $object, $legacy);
			}
		}
		return $object;
	}

	/**
	 * @param Entity $object
	 * @return Entity
	 * @throws CouldNotSaveEntityException
	 */
	private function save(Entity $object) {
		$this->tabla = $object->getPersistenceTarget();
		$this->campo_id = $object->getIdentity();
		$this->sesion = $this->sesion;
		$this->fields = $object->fields;
		$this->changes = $object->changes;
		$this->guardar_fecha = $object->save_created;
		if ($this->Write()) {
			$object->set($object->getIdentity(), $this->fields[$object->getIdentity()]);
			return $object;
		} else {
			throw new CouldNotSaveEntityException('No se ha podido persistir la entidad de tipo .' . $this->getClass());
		}
	}

	/**
	 * @param Entity $object
	 * @return Entity
	 * @throws CouldNotUpdateEntityException
	 */
	private function update(Entity $object) {
		try {
			return $this->save($object);
		} catch (Exception $ex) {
			throw new CouldNotUpdateEntityException('No se ha podido encontrar la entidad de tipo ' . $this->getClass() . '
			con identificador primario ' . $object->get($object->getIdentity()) . '.');
		}
	}

	private function merge(Entity $legacy, Entity $new) {
		$new->fields = array_merge($legacy->fields, $new->fields);
		return $new;
	}

	public function get($id, $fields = null) {
		$criteria = new Criteria($this->sesion);
		$instance = $this->newDtoInstance();
		$this->add_fields($criteria, $fields);
		$criteria->add_from($instance->getPersistenceTarget());
		$criteria->add_restriction(CriteriaRestriction::equals($instance->getIdentity(), $id));
		try {
			$resultArray = $criteria->run();
			$resultArray = $resultArray[0];
			if (empty($resultArray)) {
				throw new CouldNotFindEntityException('No se ha podido encontrar la entidad de tipo
				' . $this->getClass() . ' con identificador primario ' . $id . '.');
			}
			return $this->encapsulate($resultArray, $instance);
		} catch (PDOException $e) {
			throw new PDOException("Ha ocurrido un error al intentar ejecutar la query
			{$criteria->get_plain_query()}<br>Error {$e->getMessage()}<br>C�digo {$e->getCode()}");
		}
	}

	/**
	 * Busca todos los registros seg�n las restricciones dadas
	 * @param CriteriaRestriction|String $restrictions
	 * @param type $fields
	 * @param type $order
	 * @param type $limit
	 * @return type
	 */
	public function findAll($restrictions = null, $fields = null, $order = null, $limit = null) {
		$criteria = new Criteria($this->sesion);

		$this->add_fields($criteria, $fields);
		$this->add_restriction($criteria, $restrictions);
		$this->add_ordering($criteria, $order);
		$this->add_limits($criteria, $limit);

		$criteria->add_from($this->newDtoInstance()->getPersistenceTarget());
		$output = array();
		$results = $criteria->run();
		foreach ($results as $result) {
			$instance = $this->encapsulate($result, $this->newDtoInstance());
			$output[] = $instance;
		}
		return $output;
	}

	private function add_fields($criteria, $fields) {
		if (is_null($fields) || !is_array($fields)) {
			$criteria->add_select(empty($fields) ? '*' : $fields);
		} else {
			foreach ($fields as $field) {
				$criteria->add_select($field);
			}
		}
	}

	private function add_restriction($criteria, $restrictions) {
		$criteria->add_restriction(new CriteriaRestriction($restrictions));
	}

	private function add_ordering($criteria, $order) {
		if (is_null($order)) {
			return;
		}

		if (is_array($order)) {
			foreach ($order as $field) {
				$this->add_ordering($criteria, $field);
			}
			return;
		}

		$patt = '/(.*) (DESC|ASC)?$/i';
		if(preg_match($patt, $order, $matches)) {
			$criteria->add_ordering($matches[1], $matches[2]);
		} else {
			$criteria->add_ordering($order);
		}

		return;
	}

	private function add_limits($criteria, $limit) {
		if (!is_null($limit)) {
			if (is_array($limit)) {
				$criteria->add_limit($limit['limit'], $limit['from']);
			} else {
				$criteria->add_limit($limit);
			}
		}
	}

	public function delete($object = null) {
		if (is_subclass_of($object, 'LoggeableEntity')) {
			$newInstance = $this->newDtoInstance();
			$newInstance->set($object->getIdentity(), $object->get($object->getIdentity()));
			$this->writeLogFromArray('ELIMINAR', $newInstance, $object);
		}
		if ($object->isLoaded()) {
			$query = "DELETE FROM {$object->getPersistenceTarget()} WHERE {$object->getIdentity()} = {$object->get($object->getIdentity())}";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
			return true;
		}
		return false;
	}

	/**
	 * Comprueba si un objeto es parte de la jerarqu�a de clases definida en la capa.
	 * @param $object Objeto que se comprobar�.
	 * @param $className string de clases a la que debe pertenecer.
	 * @throws Exception Cuando no pertenece a la jerarqu�a de clases correspondiente.
	 */
	protected function checkClass($object, $className) {
		if (!is_a($object, $className)) {
			throw new DAOException('El objeto entregado no pertenece ni hereda a la clase definida en DAO.');
		}
	}

	/**
	 * Realiza la encapsulaci�n de un resultado de una query a la base de datos en una instancia de un objeto.
	 * @param $arrayResult
	 * @param $instance
	 * @return
	 */
	protected function encapsulate($arrayResult, $instance) {
		if (empty($arrayResult)) {
			return null;
		}
		foreach ($arrayResult as $property => $value) {
			$instance->set($property, $value, false);
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
		$c = str_replace(' ', '', $c);
		preg_match_all('/@\w+/', $c, $tags);
		return $tags[0];
	}

	private function fillDefaults(Entity $object) {
		$table = Descriptor::table($this->sesion, $object->getPersistenceTarget());
		$defaults = $table->getDefaults($object->getTableDefaults());
		$object->fillDefaults($defaults);
		return $object;
	}

	public function getWithRelations($id, array $relations_filters = array()) {
		// TODO: Con el arreglo $relations_filters se retornar�n solo las relaciones asignadas como elementos
		$entity = $this->get($id);
		return $this->fillRelations($entity, $relations_filters);
	}

	private function fillRelations(Entity $entity, array $relations_filters = array()) {
		foreach ($entity->getRelations() as $relation) {
			switch ($relation['association_type']) {
				case 'has_many':
					$results = $this->findAllRelated($entity, $relation, $relations_filters);
					break;
				case 'has_one':
					$results = $this->findRelated($entity, $relation, $relations_filters);
					break;
				default:
					throw new DAOException("No se especific� association_type de {$relation['class']}.");
			}

			$entity->relations[$relation['class']] = $results;
		}

		return $entity;
	}

	private function findAllRelated(Entity $entity, array $relation, array $relations_filters = array()) {
		$Criteria = new Criteria($this->sesion);
		$RelationReflected = new ReflectionClass($relation['class']);

		$Instance = $this->newDtoInstance();
		$RelationInstance = $RelationReflected->newInstance();

		$output = array();

		$results = $Criteria
			->add_select($RelationInstance->getPersistenceTarget() . '.*')
			->add_from($Instance->getPersistenceTarget())
			->add_left_join_with(
				$RelationInstance->getPersistenceTarget(),
				$RelationInstance->getPersistenceTarget() . '.' . $RelationInstance->getIdentity() . ' = ' .
				$Instance->getPersistenceTarget() . ".{$relation['foreign_key']}"
			)->add_restriction(
				CriteriaRestriction::equals($Instance->getIdentity(), $entity->fields[$Instance->getIdentity()])
			)->run();

		if (!empty($results)) {
			foreach ($results as $result) {
				$_RelationInstance = $RelationReflected->newInstance();
				$_RelationInstance = $this->encapsulate($result, $_RelationInstance);
				$output[] = $_RelationInstance;
			}
		}

		return $output;
	}

	private function findRelated(Entity $entity, array $relation, array $relations_filters = array()) {
		$output = null;
		$result = $this->findAllRelated($entity, $relation, $relations_filters);

		if (!empty($result)) {
			$output = $result[0];
		}

		return $output;
	}

	public function newEntity(array $properties = null) {
		$Instance = $this->newDtoInstance();
		if (!is_null($properties)) {
			$Instance->fillFromArray($properties, true);
		}
		return $Instance;
	}

	public function count() {
		$criteria = new Criteria($this->sesion);
		$result = $criteria
			->add_select('COUNT(*)', 'count')
			->add_from($this->newDtoInstance()->getPersistenceTarget())
			->first();

		return $result === false ? 0 : (int) $result['count'];
	}

	protected function newDtoInstance() {
		$reflected = new ReflectionClass($this->getClass());
		return $reflected->newInstance();
	}

}
