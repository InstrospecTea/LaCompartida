<?php

abstract class AbstractService implements BaseService {

	/**
	 * @deprecated por convención
	 * @var Sesion $sesion
	 */
	public $sesion;
	public $Sesion;
	private $loadedClass = array();

	public function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->sesion = &$this->Sesion;
		$this->loadDAO($this->getDaoLayer());
	}

	public function newEntity() {
		return $this->newDao()->newEntity();
	}

	/**
	 * Persiste un objeto. Crea un nuevo registro si el objeto no lleva id. Si lleva id, se actualiza el objeto existente.
	 * @param Entity $object
	 * @param boolean $writeLog Define si se escribe o no el historial de movimientos.
	 * @throws Exception
	 */
	public function saveOrUpdate($object, $writeLog = true) {
		$this->checkNullity($object);
		$this->checkClass($object, $this->getClass());
		$dao = $this->newDao();
		try {
			return $dao->saveOrUpdate($object, $writeLog);
		} catch(CouldNotAddEntityException $ex) {
			throw new ServiceException($ex);
		} catch(CouldNotUpdateEntityException $ex) {
			throw new ServiceException($ex);
		}
	}

	/**
	 * @param $id
	 * @return mixed
	 * @throws ServiceException
	 */
	public function get($id, $fields = null) {
		$this->checkNullity($id);
		$dao = $this->newDao();
		try {
			return $dao->get($id, $fields);
		} catch(CouldNotFindEntityException $ex) {
			throw new EntityNotFound($ex);
		}
	}

	public function count() {
		$dao = $this->newDao();
		try {
			return $dao->count();
		} catch(CouldNotFindEntityException $ex) {
			throw new EntityNotFound($ex);
		}
	}

	public function findAll($restrictions = null, $fields = null, $order = null, $limit = null) {
		$dao = $this->newDao();
		try {
			return $dao->findAll($restrictions, $fields, $order, $limit);
		} catch(Exception $ex) {
			throw new Exception($ex);
		}
	}

	public function findFirst($restrictions = null, $fields = null, $order = null) {
		$result = $this->findAll($restrictions, $fields, $order, 1);
		return isset($result[0]) ? $result[0] : false;
	}

	/**
	 * @param $object
	 * @throws ServiceException
	 */
	public function delete($object) {
		$this->checkNullity($object);
		$this->checkClass($object, $this->getClass());
		$dao = $this->newDao();
		try {
			$dao->delete($object);
		} catch(CouldNotDeleteEntityException $ex) {
			throw new ServiceException($ex);
		}
	}

	/**
	 * @param $object
	 * @throws ServiceException
	 */
	public function deleteOrException($object) {
		$this->checkNullity($object);
		$this->checkClass($object, $this->getClass());
		$daoClass = $this->getDaoLayer($this->sesion);
		$dao = new $daoClass($this->sesion);
		$dao->deleteOrException($object);
	}

	/**
	 * Comprueba si un objeto es parte de la jerarquía de clases definida en la capa.
	 * @param $object Objeto que se comprobará.
	 * @param $className string Jerarquía de clases a la que debe pertenecer.
	 * @throws ServiceException Cuando no pertenece a la jerarquía de clases correspondiente.
	 */
	protected function checkClass($object, $className) {
		if (!is_a($object, $className)) {
			throw new ServiceException('Dao Exception: El objeto entregado no pertenece ni hereda a la clase definida en DAO.');
		}
	}

	/**
	 * Comprueba si un objeto es nulo o está vacío. Esto es necesario para arrojar la excepción correspondiente para cuando
	 * sean realizadas operaciones de CRUD.
	 * @param $object
	 * @throws ServiceException
	 */
	protected function checkNullity($object) {
		if (empty($object)) {
			throw new ServiceException('El identificador que está siendo utilizado para obtener el objeto' . $this->getClass() . ' está vacío o es nulo.');
		}
	}

	/**
	 * Carga un DAO al vuelo
	 * @param      $classname
	 * @param null $alias
	 */
	protected function loadDAO($class_name, $alias = null) {
		if (!preg_match('/DAO$/', $class_name)) {
			$class_name = "{$class_name}DAO";
		}
		if (empty($alias)) {
			$alias = $class_name;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = $this->newDao();
		$this->loadedClass[] = $alias;
	}

	public function getWithRelations($id, array $relations_filters = array()) {
		$dao = $this->newDao();
		try {
			return $dao->getWithRelations($id, $relations_filters);
		} catch(CouldNotFindEntityException $ex) {
			throw new ServiceException($ex);
		}
	}

	protected function newDao() {
		$dao_class = $this->getDaoLayer();
		if (class_exists($dao_class)) {
			$instance = new $dao_class($this->Sesion);
		} else {
			$instance = $this->newGenericDao($dao_class);
		}
		return $instance;
	}

	private function newGenericDao($name) {
		$class_name = preg_replace('/(.+)DAO$/', '\1', $name);
		return new GenericDAO($this->Sesion, $class_name);
	}

}
