<?

require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Carpeta extends Objeto
{
	
	function Carpeta($sesion, $fields = "", $params = "")
	{
		$this->tabla = "carpeta";
		$this->campo_id = "id_carpeta";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
  
  function LoadByCodigo($codigo)
  {
    $query = "SELECT id_carpeta FROM carpeta WHERE codigo_carpeta='$codigo'";
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    list($id) = mysql_fetch_array($resp);
    return $this->Load($id);
  }

	//funcion que entrega el nuevo codigo automatico a asignar a una carpeta
	function AsignarCodigoCarpeta()
	{
		$query = "SELECT codigo_carpeta AS x FROM carpeta ORDER BY x DESC LIMIT 1";
	  $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    list($codigo) = mysql_fetch_array($resp);
	  $codigo_carpeta=$codigo+1;
	  return $codigo_carpeta;
	}
	
	function Eliminar()
	{
		$query = "DELETE FROM carpeta WHERE codigo_carpeta = '".$this->fields['codigo_carpeta']."'";
		mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		return true;
	}


}

class ListaCarpetas extends Lista
{
    function ListaCarpetas($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Carpeta', $params, $query);
    }
}