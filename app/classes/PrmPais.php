<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmPais extends Objeto {

	public static $llave_carga_masiva = 'nombre';

	function PrmPais($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_pais';
		$this->campo_id = 'id_pais';
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

}
