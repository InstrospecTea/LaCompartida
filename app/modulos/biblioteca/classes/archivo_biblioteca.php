<?
require_once Conf::ServerDir().'/fw/modulos/archivo/classes/Archivo.php';


class ArchivoBiblio extends Archivo
{
   function ArchivoBiblio($sesion, $fields=array(), $params)
	{ 
		$this->tabla = 'archivos_biblioteca';
      	parent::Archivo($sesion,$fields, $params); 
	}

	function Load()
	{
		if($this->tabla == "")
		{
			$this->error = "Falta nombre de tabla Load() en archivo.php";
			return false;
		}
		$query = "SELECT id_archivo,id_categoria, visible_inversionista, visible_emprendedor,nombre, descripcion, tipo, fecha_mod, length(data) as tamano FROM ".$this->tabla ." WHERE id_archivo='".$this->fields['id_archivo']."'";
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
