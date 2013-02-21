<?php

require_once dirname(__FILE__) . '/../conf.php';

class CategoriaUsuario extends Objeto {

	public static $llave_carga_masiva = 'glosa_categoria';

	function CategoriaUsuario($sesion, $fields = "", $params = "") {
		$this->tabla = 'prm_categoria_usuario';
		$this->campo_id = 'id_categoria_usuario';
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

}
