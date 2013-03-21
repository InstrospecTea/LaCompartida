<?php

require_once dirname(__FILE__) . '/../conf.php';

class Idioma extends Objeto {

	public static $llave_carga_masiva = 'glosa_idioma';

	function Idioma($sesion, $fields = "", $params = "") {
		$this->tabla = 'prm_idioma';
		$this->campo_id = 'id_idioma';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

}
