<?php

require_once dirname(__FILE__) . '/../conf.php';

class PrmTituloPersona extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_titulo_persona';
		$this->campo_id = 'titulo';
		$this->campo_glosa = 'glosa_titulo';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

}
