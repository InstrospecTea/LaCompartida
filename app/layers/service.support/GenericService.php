<?php

class GenericService extends AbstractService implements BaseService {

	private $class_name;

	/**
	 * Devuelve una servicio generico en base al nombre de la tabla
	 * @param Sesion $Sesion
	 * @param string $class_name nombre de la clase
	 */
	public function __construct(Sesion $Sesion, $class_name) {
		$this->class_name = $class_name;
		parent::__construct($Sesion);
	}

	public function getDaoLayer() {
		return "{$this->class_name}DAO";
	}

	public function getClass() {
		return "$this->class_name";
	}

}
