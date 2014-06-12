<?php

require_once dirname(__FILE__) . '/../conf.php';

class GrupoCliente extends Objeto {

	public static $llave_carga_masiva = 'glosa_grupo_cliente';

	function GrupoCliente($Sesion, $fields = '', $params = '') {
		$this->tabla = 'grupo_cliente';
		$this->campo_id = 'id_grupo_cliente';
		$this->campo_glosa = 'glosa_grupo_cliente';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

}
