<?php

require_once dirname(__FILE__) . '/../conf.php';

class SelectorLedes {

	private $proveedores;
	private $formatos;
	private $Sesion;

	function __construct(Sesion $Sesion) {
		$this->Sesion = $Sesion;
		$this->proveedores = array(
			'serengeti' => 'Serengeti',
			'tymetrix' => 'TyMetrix',
			'counselink' => 'Counselink'
		);
		$this->formatos = array(
			'LEDES1998B' => 'LEDES1998B',
			'LEDES98BI V2' => 'LEDES98BI V2'
		);
	}

	public function instanciar($proveedor) {
		return new $this->proveedores[$proveedor]($this->Sesion);
	}

	public function getProveedores() {
		return $this->proveedores;
	}

	public function getFormatos() {
		return $this->formatos;
	}
}
