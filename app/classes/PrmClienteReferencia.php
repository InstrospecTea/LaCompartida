<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmClienteReferencia extends ObjetoExt {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_cliente_referencia';
		$this->campo_id = 'id_cliente_referencia';
		$this->campo_glosa = 'glosa_cliente_referencia';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

}
