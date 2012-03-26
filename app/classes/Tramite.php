<?
require_once dirname(__FILE__).'/../conf.php';
require_once Conf::ServerDir().'/../fw/classes/Lista.php';
require_once Conf::ServerDir().'/../fw/classes/Objeto.php';
require_once Conf::ServerDir().'/../app/classes/Debug.php';
require_once Conf::ServerDir().'/../app/classes/Cobro.php';

class Tramite extends Objeto
{

	function Tramite($sesion, $fields = "", $params = "")
	{
		$this->tabla = "tramite";
		$this->campo_id = "id_tramite";
		$this->sesion = $sesion;
		$this->fields = $fields;
		
		if( $this->fields['duracion']=='00:00:00' ) $this->fields['duracion']='-';
	}
	
	function Eliminar()
	{
		if($this->Estado() == "Abierto")
			{
				if($this->fields['trabajo_si_no']==1) {
					$query = "DELETE FROM trabajo WHERE id_tramite='".$this->fields['id_tramite']."'";
					mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				}
			
				$query = "DELETE FROM tramite WHERE id_tramite='".$this->fields['id_tramite']."'";;
				mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
				// Si se pudo eliminar, loguear el cambio.
			}
		else
			{
				$this->error = __("No se puede eliminar un trámite que no está abierto");
				return false;
			}
		return true;
	}
	
	function get_codigo_cliente()
	{
		$query = "SELECT codigo_cliente
					FROM tramite
						JOIN asunto ON asunto.codigo_asunto=tramite.codigo_asunto
					WHERE id_tramite='".$this->fields['id_tramite']."'";
		$resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
		$row = mysql_fetch_assoc($resp);
		return $row['codigo_cliente'];
	}
	
	function Estado()
	{
		if(!$this->fields[estado_cobro] && $this->fields[id_cobro])
		{
			$cobro= new Cobro($this->sesion);
			$cobro->Load($this->fields[id_cobro]);
			$this->fields[estado_cobro] = $cobro->fields['estado'];
		}
		if($this->fields[estado_cobro] <> "CREADO" && $this->fields[estado_cobro] <> "EN REVISION" && $this->fields[estado_cobro] != '' && $this->fields[estado_cobro] != 'SIN COBRO')
			return __("Cobrado");
		if($this->fields[revisado] == 1)
			return __("Revisado");

			return __("Abierto");
	}
	
	function LoadId( $id_tramite )
    {
        $query = "SELECT * FROM tramite WHERE id_tramite='$id_tramite'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if( $this->fields = mysql_fetch_assoc($resp) )
        {
            $this->loaded = true;
            return true;
        }

        return false;
    }
}

class ListaTramites extends Lista
{
	function ListaTramites($sesion, $params, $query)
	{
		$this->Lista($sesion, 'Tramite', $params, $query);
	}
}

?>