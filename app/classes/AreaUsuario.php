<?php

require_once dirname(__FILE__) . '/../conf.php';

class AreaUsuario extends Objeto {

	public static $llave_carga_masiva = 'glosa';

	function AreaUsuario($sesion, $fields = "", $params = "") {
		$this->tabla = 'prm_area_usuario';
		$this->campo_id = 'id';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	public static function SelectAreas($sesion, $name, $selected = '', $opciones = '', $titulo = '', $width = '150') {
		$query_areas = 'SELECT id, glosa FROM prm_area_usuario ORDER BY glosa';
		return Html::SelectQuery($sesion, $query_areas, $name, $selected, $opciones, $titulo, $width);
	}
}
