<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class CtaCteFactMvtoMoneda extends Objeto
{
	function CtaCteFactMvtoMoneda($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cta_cte_fact_mvto_moneda";
		$this->campo_id = "id_cta_cte_fact_mvto_moneda";
		$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
}
