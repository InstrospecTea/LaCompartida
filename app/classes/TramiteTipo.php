<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';
require_once Conf::ServerDir().'/../app/classes/Trabajo.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';

class TramiteTipo extends Objeto
{

	function TramiteTipo($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tramite_tipo";
		$this->campo_id = "id_tramite_tipo";
		$this->sesion = $sesion;
		$this->fields = $fields;
		
		if( $this->fields['duracion_defecto']=='00:00:00' ) $this->fields['duracion_defecto']='-';
	}
	
	function Eliminar()
	{
			$query = "SELECT COUNT(*) FROM tramite WHERE id_tramite_tipo = '".$this->fields[id_tramite_tipo]."'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			list($count) = mysql_fetch_array($resp);
			if($count > 0)
			{
				$this->error = __('No se puede eliminar un').' '.__('tipo trámite').' '.__('que tiene trámites asociados');
				return false;
			}
		
			/*Eliminar el Trabajo del Comentario asociado*/
			$query = "DELETE FROM tramite_valor WHERE id_tramite_tipo='".$this->fields[id_tramite_tipo]."'";
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			
			$query = "DELETE FROM tramite_tipo WHERE id_tramite_tipo='".$this->fields[id_tramite_tipo]."'";;
			$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
			// Si se pudo eliminar, loguear el cambio.
			
		return true;
	}
	
	function LoadId( $id_tramite_tipo )
    {
        $query = "SELECT * FROM tramite_tipo WHERE id_tramite_tipo='$id_tramite_tipo'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $this->fields = mysql_fetch_assoc($resp) )
        {
            $this->loaded = true;
            return true;
        }

        return false;
    }
}

class ListaTramiteTipos extends Lista
{
	function ListaTramiteTipos($sesion, $params, $query)
	{
		$this->Lista($sesion, 'TramiteTipo', $params, $query);
	}
}
?>