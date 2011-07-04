<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../fw/classes/Utiles.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/UtilesApp.php';

class Proveedor extends Objeto
{
	function Proveedor($sesion, $fields = "", $params = "")
	{
		$this->tabla = "prm_proveedor";
		$this->campo_id = "id_proveedor";
		$this->guardar_fecha = false;
		$this->sesion = $sesion;
		$this->fields = $fields;
	}

	function Id($id=null){
		if($id) $this->fields[$this->campo_id] = $id;
		if(empty($this->fields[$this->campo_id])) return false;
		return $this->fields[$this->campo_id];
	}
}
	
class ListaProveedor extends Lista
{
	function ListaProveedor($sesion, $params, $query)
	{
		$this->Lista($sesion, 'Proveedor', $params, $query);
	}
}
