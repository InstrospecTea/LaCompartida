<?
require_once Conf::ServerDir().'/fw/modulos/archivo/classes/Archivo.php';

class ArchivoForo extends Archivo
{
   function ArchivoForo($sesion, $fields=array(), $params)
	{ 
		$this->tabla = 'foro_mensaje_archivo';
      	parent::Archivo($sesion,$fields, $params); 
	}

	function Load()
	{
		if($this->tabla == "")
		{
			$this->error = "Falta nombre de tabla Load() en archivo.php";
			return false;
		}
		$query = "SELECT id_archivo,id_foro_mensaje, nombre, tipo, length(data) as tamano FROM ".$this->tabla ." WHERE id_archivo='".$this->fields['id_archivo']."'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if(!$this->fields = mysql_fetch_assoc($resp))
		{
			 $this->error= "El archivo no existe";
			return false;
		}
		return true;
	}
    function LoadByMsg($id_foro_mensaje)
    {
        if($this->tabla == "")
        {
            $this->error = "Falta nombre de tabla Load() en archivo.php";
            return false;
        }
        $query = "SELECT id_archivo, id_foro_mensaje, nombre, tipo, length(data) as tamano FROM ".$this->tabla ." 
							WHERE id_foro_mensaje='$id_foro_mensaje'";
        $resp = mysql_query($query, $this->sesion->dbh) or Utiles::errorSQL($query,__FILE__,__LINE__,$this->sesion->dbh);
        if(!$this->fields = mysql_fetch_assoc($resp))
        {
             $this->error= "El archivo no existe";
            return false;
        }
        return true;
    }

}
?>
