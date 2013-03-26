<?php

require_once dirname(__FILE__) . '/../conf.php';

class AreaProyecto extends Objeto {

	public static $llave_carga_masiva = 'glosa';

	function AreaProyecto($sesion, $fields = "", $params = "") {
		$this->tabla = 'prm_area_proyecto';
		$this->campo_id = 'id_area_proyecto';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

}
