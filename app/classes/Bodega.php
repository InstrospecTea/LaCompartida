<?

require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Bodega extends Objeto
{
		function bodega($sesion, $fields = "", $params = "")
	{
		$this->tabla = "bodega";
		$this->campo_id = "id_bodega";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	
	#Carga a través de ID
	function LoadById($id_bodega)
	{
		$query = "SELECT id_bodega FROM bodega WHERE id_bodega = '$id_bodega'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		list($id) = mysql_fetch_array($resp);
		return $this->Load($id);
	}
	
	
	# Elimina Bodegas
	function Eliminar()
	{
		$id_bodega = $this->fields[id_bodega];
		if($id_bodega)
		{			
			$query = "DELETE FROM bodega WHERE id_bodega = $id_bodega";
			mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			return true;
		}
		else
			return false;
	}

}