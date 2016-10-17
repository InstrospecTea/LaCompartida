<?php

class GenericDAO extends AbstractDAO implements BaseDAO {

	private $class_name;

	public function __construct(Sesion $Sesion, $class_name) {
		$this->class_name = $class_name;
		parent::__construct($Sesion);
	}

	public function getClass() {
		return $this->class_name;
	}

}
