<?php
require_once dirname(__FILE__) . '/../conf.php';

class PrmDocumentoLegal extends Objeto
{
	public static $llave_carga_masiva = 'codigo';

	function PrmDocumentoLegal($sesion, $fields = "", $params = "") {
		$this->tabla = "prm_documento_legal";
		$this->campo_id = "id_documento_legal";
		$this->sesion = $sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}
}