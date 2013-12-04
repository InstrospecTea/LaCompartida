<?php
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class PrmPais extends Objeto
{

	public static $llave_carga_masiva = 'nombre';

	function PrmPais($sesion, $fields = "", $params = "")
	{
		$this->tabla = "prm_pais";
		$this->campo_id = "id_pais";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

}
