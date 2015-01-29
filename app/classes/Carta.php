<?php

require_once dirname(__FILE__) . '/../conf.php';

class Carta extends Objeto {

	public $guardar_fecha = false;
	public $editable_fields = array('descripcion', 'formato', 'formato_css',
									'margen_superior', 'margen_inferior', 'margen_izquierdo', 'margen_derecho',
									'margen_encabezado', 'margen_pie_de_pagina');

	function __construct($Sesion, $fields = '', $params = '') {
		$this->tabla = 'carta';
		$this->campo_id = 'id_carta';
		$this->campo_glosa = 'descripcion';
		$this->sesion = $Sesion;
		$this->fields = $fields;
	}

	function LoadByDescripcion($descripcion) {
		$query = "SELECT * FROM {$this->tabla} WHERE descripcion = '$descripcion'";
		return $this->LoadWithQuery($query);
	}

	// TODO: ESTO HAY QUE MOVERLO A fw/classes/Objeto.php, LA CLASE Cliente.php TAMBIÉN LA CREA DE NUEVO
	function LoadWithQuery($query) {
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);

		if ($this->fields = mysql_fetch_assoc($resp)) {
			$this->loaded = true;
			return true;
		}

		$this->error = "No existe el objeto buscado en la base de datos";
		return false;
	}

	/**
	 * Busca una carta por el id
	 * @param type $id
	 * @param array $fields
	 * @return Carta|boolean Devuelve un objeto Carta o false si no se encuentra el id
	 */
	public function findById($id, Array $fields = array()) {
		$Carta = new Carta($this->sesion);
		$select = '*';
		if (!empty($fields)) {
			$select = implode(',', $fields);
		}

		$query = "SELECT {$select} FROM {$this->tabla} WHERE {$this->campo_id} = '$id'";
		if ($Carta->LoadWithQuery($query)) {
			return $Carta;
		}
		return false;
	}

}
