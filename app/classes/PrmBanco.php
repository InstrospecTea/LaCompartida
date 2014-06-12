<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmBanco extends Objeto {

	public function __construct($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_banco';
		$this->campo_id = 'id_banco';
		$this->campo_glosa = 'nombre';
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

}
