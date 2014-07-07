<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmTipoProyecto extends Objeto {

	public static $llave_carga_masiva = 'glosa';

	public function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_tipo_proyecto';
		$this->campo_id = 'id_tipo_proyecto';
		$this->campo_glosa = 'glosa_tipo_proyecto';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

}
