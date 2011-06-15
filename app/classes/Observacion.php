<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class Observacion extends Objeto
{
	function Observacion($sesion, $fields = "", $params = "")
	{
		$this->tabla = "cobro_historial";
		$this->campo_id = "id_cobro_historial";
		$this->sesion = $sesion;
		$this->fields = $fields;
	}
	function Delete()
	{
		if(!$this->Loaded())
		{
			$this->error = __("Debe cargar un historial");
		}
        $query = "DELETE FROM cobro_historial WHERE id_cobro_historial = '".$this->fields['id_cobro_historial']."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        return true;
	}
}

class ListaObservaciones extends Lista
{
    function ListaObservaciones($sesion, $params, $query)
    {
        $this->Lista($sesion, 'Observacion', $params, $query);
    }
}
