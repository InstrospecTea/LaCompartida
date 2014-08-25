<?php

require_once dirname(__FILE__) . '/../conf.php';

class Carta extends Objeto {

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
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);

    if ($this->fields = mysql_fetch_assoc($resp))   {
      $this->loaded = true;
      return true;
    }

    $this->error = "No existe el objeto buscado en la base de datos";
    return false;
  }

}
