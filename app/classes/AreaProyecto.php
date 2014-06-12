<?php

require_once dirname(__FILE__) . '/../conf.php';

class AreaProyecto extends Objeto {

	public static $llave_carga_masiva = 'glosa';

	function AreaProyecto($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_area_proyecto';
		$this->campo_id = 'id_area_proyecto';
		$this->campo_glosa = 'glosa';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

}
