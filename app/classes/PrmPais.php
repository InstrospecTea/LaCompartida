<?php
require_once dirname(__FILE__) . '/../conf.php';

class PrmPais extends Objeto {

	public static $llave_carga_masiva = 'nombre';

	function PrmPais($sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_pais';
		$this->campo_id = 'id_pais';
		$this->campo_glosa = 'nombre';
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function LoadByISO($acronyms) {
		$query = "SELECT id_pais FROM prm_pais WHERE iso_2siglas = '{$acronyms}' OR iso_3siglas = '{$acronyms}'";
		$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($country_id) = mysql_fetch_array($rs);

		if (!empty($country_id)) {
			$this->Load($country_id);
		}
	}

}
