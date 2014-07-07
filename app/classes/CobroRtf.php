<?php

require_once dirname(__FILE__) . '/../conf.php';

class CobroRtf extends Objeto {

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'cobro_rtf';
		$this->campo_id = 'id_formato';
		$this->campo_glosa = 'descripcion';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

}
