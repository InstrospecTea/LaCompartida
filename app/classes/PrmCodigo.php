<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmCodigo extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_codigo';
		$this->campo_id = 'codigo';
		$this->campo_glosa = 'glosa';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

	function LoadById($id_codigo) {
		$this->campo_id = 'id_codigo';
		return $this->Load($id_codigo);
	}
}
