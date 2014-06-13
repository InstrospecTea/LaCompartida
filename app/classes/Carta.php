<?php

require_once dirname(__FILE__) . '/../conf.php';

class Carta extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'carta';
		$this->campo_id = 'id_carta';
		$this->campo_glosa = 'descripcion';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

}
