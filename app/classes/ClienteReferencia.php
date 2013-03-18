<?php

require_once dirname(__FILE__) . '/../conf.php';

class ClienteReferencia extends Objeto {

	public static $llave_carga_masiva = 'glosa_cliente_referencia';

	function ClienteReferencia($sesion, $fields = "", $params = "") {
		$this->tabla = 'prm_cliente_referencia';
		$this->campo_id = 'id_cliente_referencia';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

}
