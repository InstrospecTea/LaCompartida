<?

require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Ayuda extends Objeto
{
	
	function Ayuda($sesion, $fields = "", $params = "")
	{
		$this->tabla = "prm_ayuda";
		$this->campo_id = "id_ayuda";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
  
  function LoadByPagina($pagina)
  {
    $query = "SELECT id_ayuda FROM prm_ayuda WHERE pagina='$pagina'";
    $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
    list($id) = mysql_fetch_array($resp);
    return $this->Load($id);
  }

}

class ListaAyudas extends Lista
{
    function ListaAyudas($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Ayuda', $params, $query);
    }
}