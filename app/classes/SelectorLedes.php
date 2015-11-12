<?php

require_once dirname(__FILE__) . '/../conf.php';

class SelectorLedes {

	private $proveedores;
	private $Sesion;

	function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->proveedores = array(
			'serengeti' => 'Serengeti',
			'tymetrix' => 'TyMetrix',
			'counselink' => 'Counselink'
		);
	}

	public function instanciar($proveedor) {
		return new $proveedor($this->Sesion);
	}

	public function getProveedores() {
		return $this->proveedores;
	}
}
