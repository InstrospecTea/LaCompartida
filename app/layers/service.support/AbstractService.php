<?php
abstract class AbstractService implements BaseService {

	var $sesion;
	private $loadedClass = array();

	public function __construct(Sesion $sesion) {
		$this->sesion = $sesion;
		$this->loadDAO($this->getDaoLayer());
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
		$daoClass = $this->getDaoLayer();
		$dao = new $daoClass($this->sesion);
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
	public function get($id) {
		$this->checkNullity($id);
		$daoClass = $this->getDaoLayer();
		$dao = new $daoClass($this->sesion);
		try {
			return $dao->get($id);
		} catch(CouldNotFindEntityException $ex) {
			throw new ServiceException($ex);
		}
	}

	public function findAll($restrictions = null, $fields = null, $order = null, $limit = null) {
		$daoClass = $this->getDaoLayer();
		$dao = new $daoClass($this->sesion);
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
		$daoClass = $this->getDaoLayer($this->sesion);
		$dao = new $daoClass($this->sesion);
		try {
			$dao->delete($object);
		} catch(CouldNotDeleteEntityException $ex) {
			throw new ServiceException($ex);
		}
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
	protected function loadDAO($classname, $alias = null) {
		if (!preg_match('/DAO$/', $classname)) {
			$classname = "{$classname}DAO";
		}
		if (empty($alias)) {
			$alias = $classname;
		}
		if (in_array($alias, $this->loadedClass)) {
			return;
		}
		$this->{$alias} = new $classname($this->sesion);
		$this->loadedClass[] = $alias;
	}
}
