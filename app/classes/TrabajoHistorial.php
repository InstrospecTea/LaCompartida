<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class TrabajoHistorial extends Objeto 
{
	function TrabajoHistorial($sesion, $fields = "", $params = "")
	{
		$this->tabla = "trabajo_historial";
		$this->campo_id = "id_trabajo_historial";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
}

class ListaTrabajoHistorial extends Lista
{
	function ListaTrabajoHistorial($sesion, $params, $query)
	{
		$this->Lista($sesion, 'TrabajoHistorial', $params, $query );
	}
}
?>