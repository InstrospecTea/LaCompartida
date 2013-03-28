<?php

require_once dirname(__FILE__) . '/../conf.php';

class CuentaBanco extends Objeto {

	public static $llave_carga_masiva = 'glosa';

	function CuentaBanco($sesion, $fields = "", $params = "") {
		$this->tabla = 'cuenta_banco';
		$this->campo_id = 'id_cuenta';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

}
