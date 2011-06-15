<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class CtaCteFactMvtoNeteo extends Objeto
{
	function CtaCteFactMvtoNeteo($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cta_cte_fact_mvto_neteo";
		$this->campo_id = "id_cta_cte_mvto_neteo";
		$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
}

class ListaCtaCteFactMvtoNeteo extends Lista
{
	function ListaCtaCteFactMvtoNeteo($sesion, $params, $query)
	{
		$this->Lista($sesion, 'CtaCteFactMvtoNeteo', $params, $query);
	}
}