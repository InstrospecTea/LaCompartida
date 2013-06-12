<?php

require_once dirname(__FILE__) . '/../conf.php';

class TipoCorreo extends Objeto {

	public $editable_fields = array();

	public function __construct($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_tipo_correo';
		$this->campo_id = 'id';
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->log_update = true;
		$this->x_resultados = array();
		$this->guardar_fecha = true;
	}

	public function obtenerId($nombre) {
		$query = "SELECT {$this->campo_id} FROM {$this->tabla} WHERE nombre = '{$nombre}' LIMIT 1";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $id;
	}

}