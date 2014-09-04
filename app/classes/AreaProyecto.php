<?php
require_once dirname(__FILE__) . '/../conf.php';

class AreaProyecto extends Objeto {
	public static $llave_carga_masiva = 'glosa';

	function AreaProyecto($Sesion, $fields = '', $params = '') {
		$this->tabla = 'prm_area_proyecto';
		$this->campo_id = 'id_area_proyecto';
		$this->campo_glosa = 'glosa';
		$this->sesion = $Sesion;
		$this->fields = $fields;
		$this->guardar_fecha = false;
	}

	function LoadByGlosa($glosa) {
		$query = "SELECT {$this->campo_id} FROM {$this->tabla} WHERE glosa = '{$glosa}'";
		$rs = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query, __FILE__, __LINE__, $this->sesion->dbh);
		list($project_area_id) = mysql_fetch_array($rs);
		if (!empty($project_area_id)) {
			$this->Load($project_area_id);
		}
	}
}
