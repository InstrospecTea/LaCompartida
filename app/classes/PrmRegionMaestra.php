<?php

class PrmRegionMaestra extends Objeto {

	public $tabla = 'prm_region_maestra';
	public $campo_id = 'id';
	public $campo_glosa = 'nombre';

	public function __construct(Sesion $Sesion) {
		$this->sesion = $Sesion;
	}

}