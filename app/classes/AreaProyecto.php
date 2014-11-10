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
	  
	 /**
   * Lista la tabla con los campos indicados en la clase
   * devuelve un array con llave campo_id y valor campo_glosa
   * @param string $query_extra
   * @param string $fields
   * @return array
   * @throws exception
   */
  public function ListarExt($query_extra = '', $fields = '') {
    if (empty($this->campo_id) || empty($this->campo_glosa)) {
      throw new exception("Imposible Listar $this->tabla");
    }
    if (preg_match('/[\(\.]/', $this->campo_glosa)) { //verifica si es funcion o parte de table.field
      $glosa = $this->campo_glosa;
    } else {
      $glosa = "{$this->tabla}.{$this->campo_glosa}";
    }
    if (!empty($fields)) {
      $fields = ',' . $fields;
    }
    $query = "SELECT
            {$this->tabla}.{$this->campo_id} AS id,
            $glosa AS glosa 
            $fields
          FROM {$this->tabla} {$query_extra}";
    $qr = $this->sesion->pdodbh->query($query);
    $respuesta = $qr->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    $result = array();
    foreach ($respuesta as $key => $value) {
      $result[$key] = $value[0];
    }
    return $result;
  }

}
